<?php
include 'config.php';
include 'functions.php';

$cleanupMessage = '';

// Delete log entry
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $delete_query = "DELETE FROM ojt_logs WHERE id = $delete_id";

    if (mysqli_query($conn, $delete_query)) {
        header("Location: logs.php?deleted=1");
        exit;
    }
}

// Delete unlinked logs (legacy rows not attached to uploaded sheets)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_unlinked') {
    $hasSourceColumn = false;
    $checkColumnForDelete = mysqli_query($conn, "SHOW COLUMNS FROM ojt_logs LIKE 'source_upload_id'");
    if ($checkColumnForDelete && mysqli_num_rows($checkColumnForDelete) > 0) {
        $hasSourceColumn = true;
    }

    if ($hasSourceColumn && mysqli_query($conn, "DELETE FROM ojt_logs WHERE source_upload_id IS NULL")) {
        $cleaned = max(0, (int)mysqli_affected_rows($conn));
        header("Location: logs.php?cleaned=$cleaned");
        exit;
    }
}

if (isset($_GET['cleaned'])) {
    $cleanupMessage = 'Deleted unlinked logs: ' . max(0, (int)$_GET['cleaned']) . '.';
}

$unlinkedCount = 0;
$checkSourceColumn = mysqli_query($conn, "SHOW COLUMNS FROM ojt_logs LIKE 'source_upload_id'");
if ($checkSourceColumn && mysqli_num_rows($checkSourceColumn) > 0) {
    $unlinkedResult = mysqli_query($conn, "SELECT COUNT(*) AS count_unlinked FROM ojt_logs WHERE source_upload_id IS NULL");
    if ($unlinkedResult) {
        $unlinkedRow = mysqli_fetch_assoc($unlinkedResult);
        $unlinkedCount = (int)($unlinkedRow['count_unlinked'] ?? 0);
    }
}

// Get all logs
$logs_query = "
    SELECT l.*, 
           CASE 
               WHEN h.holiday_date IS NOT NULL THEN h.holiday_name
               ELSE 'Regular day'
           END as day_type
    FROM ojt_logs l
    LEFT JOIN holidays h ON l.date = h.holiday_date
    ORDER BY l.date DESC
";

$logs_result = mysqli_query($conn, $logs_query);
$total_logs = mysqli_num_rows($logs_result);

// Calculate stats
$total_hours = getTotalHours($conn);
$required_hours = getRequiredHours($conn);
$total_days = getTotalOJTDays($conn);
$days_left = calculateDaysLeft($conn, $required_hours);
$dashboardStats = array(
    'total_hours' => $total_hours,
    'required_hours' => $required_hours,
    'progress_percent' => min(100, ($required_hours > 0 ? ($total_hours / $required_hours) * 100 : 0)),
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All OJT Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--panel);
            color: var(--ink);
            min-height: 100vh;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--pink-strong);
            margin-bottom: 5px;
        }

        .header h1 i {
            margin-right: 8px;
            font-size: 22px;
        }

        .header p {
            font-size: 13px;
            color: #999;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 15px;
        }

        .stats-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            overflow-x: auto;
        }

        .stat-chip {
            background: linear-gradient(90deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: white;
            padding: 10px 15px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .stat-chip strong {
            display: block;
            font-size: 14px;
        }

        .notice {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .notice.success {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
        }

        .cleanup-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 14px;
            padding: 10px 12px;
            border: 1px solid var(--accent-border, var(--line));
            border-radius: 10px;
            background: var(--pink-soft);
        }

        .cleanup-meta {
            font-size: 12px;
            color: var(--pink-strong);
            font-weight: 600;
        }

        .cleanup-btn {
            border: none;
            background: var(--pink);
            color: white;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            cursor: pointer;
        }

        .cleanup-btn:hover {
            background: var(--pink-strong);
        }

        .log-item {
            padding: 15px;
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .log-item:hover {
            background: var(--pink-soft);
            border-color: var(--accent-border, var(--line));
        }

        .log-content {
            flex: 1;
        }

        .log-date {
            font-weight: 700;
            color: #555;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .log-time {
            font-size: 12px;
            color: #bbb;
        }

        .log-hours {
            text-align: right;
            font-weight: 700;
            color: var(--pink-strong);
            font-size: 16px;
        }

        .log-delete {
            margin-left: 10px;
            background: var(--pink-soft);
            color: var(--pink-strong);
            border: none;
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .log-delete:hover {
            background: var(--pink-strong);
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 40px 15px;
            color: #bbb;
            font-size: 14px;
        }

        .empty-message a {
            color: var(--pink-strong);
            text-decoration: none;
            font-weight: 600;
        }

        .btn-back {
            display: block;
            text-align: center;
            padding: 12px;
            background: linear-gradient(90deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-back i {
            margin-right: 8px;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 139, 171, 0.3);
        }

        body.modal-open .container {
            filter: blur(7px);
            pointer-events: none;
            user-select: none;
        }

        .confirm-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(252, 239, 247, 0.58);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1200;
            padding: 16px;
        }

        .confirm-modal-overlay.show {
            display: flex;
        }

        .confirm-modal {
            width: min(430px, 100%);
            background: #fff;
            border-radius: 18px;
            border: 1px solid #f2e7ed;
            box-shadow: 0 18px 44px rgba(43, 30, 37, 0.16);
            padding: 24px 22px 18px;
            text-align: center;
        }

        .confirm-modal-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: var(--pink-soft);
            color: var(--pink-strong);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 12px;
        }

        .confirm-modal h5 {
            color: #27222f;
            font-size: 30px;
            font-weight: 800;
            line-height: 1.15;
            margin: 0 0 10px;
        }

        .confirm-modal p {
            font-size: 14px;
            color: #8f95a1;
            line-height: 1.45;
            margin: 0 auto 18px;
            max-width: 330px;
        }

        .confirm-modal p strong {
            color: #4a4051;
            font-weight: 700;
        }

        .confirm-delete-btn {
            width: 100%;
            border: none;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 700;
            padding: 12px 18px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .cancel-delete-btn {
            border: none;
            background: transparent;
            color: var(--pink-strong);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            font-weight: 700;
            padding: 8px 12px;
            cursor: pointer;
        }

        @media (max-width: 480px) {
            .container {
                max-width: 100%;
            }

            .header h1 {
                font-size: 22px;
            }

            .log-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .log-hours {
                text-align: left;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <?php renderDashboardShell('logs.php', $dashboardStats); ?>

    <div class="container">
        <div class="header">
            <h1><i class="fa-regular fa-file-lines" aria-hidden="true"></i>All Log Entries</h1>
            <p>Your complete training history</p>
        </div>

        <?php if ($cleanupMessage): ?>
            <div class="notice success"><?php echo htmlspecialchars($cleanupMessage); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="stats-bar">
                <div class="stat-chip">
                    <span>Total Hours</span>
                    <strong><?php echo number_format((float)$total_hours, 2); ?></strong>
                </div>
                <div class="stat-chip">
                    <span>OJT Days</span>
                    <strong><?php echo $total_days; ?></strong>
                </div>
                <div class="stat-chip">
                    <span>Days Left</span>
                    <strong><?php echo $days_left; ?></strong>
                </div>
            </div>

            <?php if ($unlinkedCount > 0): ?>
                <div class="cleanup-row">
                    <div class="cleanup-meta">Unlinked logs in MySQL: <?php echo $unlinkedCount; ?></div>
                    <form method="POST" class="confirm-action-form" data-item-label="all unlinked logs" data-warning-text="Deleting all unlinked logs cannot be undone.">
                        <input type="hidden" name="action" value="delete_unlinked">
                        <button type="submit" class="cleanup-btn">Delete Unlinked</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div id="logsList">
                <?php
                if ($total_logs > 0) {
                    $logs_result = mysqli_query($conn, "
                        SELECT l.*, 
                               CASE 
                                   WHEN h.holiday_date IS NOT NULL THEN h.holiday_name
                                   ELSE 'Regular day'
                               END as day_type
                        FROM ojt_logs l
                        LEFT JOIN holidays h ON l.date = h.holiday_date
                        ORDER BY l.date DESC
                    ");

                    while ($row = mysqli_fetch_assoc($logs_result)) {
                        $date_formatted = date('M d, Y', strtotime($row['date']));
                        $time_in = date('h:i A', strtotime($row['time_in']));
                        $time_out = date('h:i A', strtotime($row['time_out']));
                        $hours = number_format((float)$row['hours'], 2);
                        $date_attr = htmlspecialchars($date_formatted, ENT_QUOTES);

                        echo "
                        <div class='log-item' data-date='$date_formatted'>
                            <div class='log-content'>
                                <div class='log-date'>$date_formatted</div>
                                <div class='log-time'>$time_in → $time_out</div>
                            </div>
                            <div class='log-hours'>$hours hrs</div>
                            <form method='POST' style='display: inline;' class='confirm-action-form' data-item-label='log entry on $date_attr' data-warning-text='Deleting this log cannot be undone.'>
                                <input type='hidden' name='delete_id' value='{$row['id']}'>
                                <button type='submit' class='log-delete'>Delete</button>
                            </form>
                        </div>
                        ";
                    }
                } else {
                    echo "<div class='empty-message'>No logs yet.</div>";
                }
                ?>
            </div>
        </div>

        <a href="index.php" class="btn-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to Dashboard</a>
    </div>

    <div class="confirm-modal-overlay" id="actionConfirmModal" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="actionModalTitle">
            <div class="confirm-modal-icon"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i></div>
            <h5 id="actionModalTitle">Are you sure you want to delete this?</h5>
            <p><strong id="actionModalSubject">This item</strong> <span id="actionModalWarning">cannot be undone.</span></p>
            <button type="button" class="confirm-delete-btn" id="confirmActionBtn">Confirm Delete</button>
            <button type="button" class="cancel-delete-btn" id="cancelActionBtn">Cancel</button>
        </div>
    </div>

    <script>
        (function applyStoredSidebarState() {
            const collapsed = localStorage.getItem('sidebarCollapsed') === '1';
            const hidden = localStorage.getItem('sidebarHidden') === '1';

            if (collapsed) {
                document.body.classList.add('sidebar-collapsed');
            }

            if (hidden) {
                document.body.classList.add('sidebar-hidden');
            }
        })();

        const actionConfirmModal = document.getElementById('actionConfirmModal');
        const actionModalTitle = document.getElementById('actionModalTitle');
        const actionModalSubject = document.getElementById('actionModalSubject');
        const actionModalWarning = document.getElementById('actionModalWarning');
        const confirmActionBtn = document.getElementById('confirmActionBtn');
        const cancelActionBtn = document.getElementById('cancelActionBtn');

        let pendingActionForm = null;

        function openActionModal(form) {
            pendingActionForm = form;

            const itemLabel = form.getAttribute('data-item-label') || 'this item';
            const warningText = form.getAttribute('data-warning-text') || 'cannot be undone.';

            actionModalTitle.textContent = 'Are you sure you want to delete this?';
            actionModalSubject.textContent = itemLabel;
            actionModalWarning.textContent = warningText;

            actionConfirmModal.classList.add('show');
            actionConfirmModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function closeActionModal() {
            actionConfirmModal.classList.remove('show');
            actionConfirmModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            pendingActionForm = null;
        }

        document.querySelectorAll('.confirm-action-form').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                openActionModal(form);
            });
        });

        confirmActionBtn.addEventListener('click', function() {
            if (!pendingActionForm) {
                closeActionModal();
                return;
            }

            const formToSubmit = pendingActionForm;
            closeActionModal();
            formToSubmit.submit();
        });

        cancelActionBtn.addEventListener('click', closeActionModal);

        actionConfirmModal.addEventListener('click', function(event) {
            if (event.target === actionConfirmModal) {
                closeActionModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && actionConfirmModal.classList.contains('show')) {
                closeActionModal();
            }
        });

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }

        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }

        function toggleCollapse() {
            if (window.matchMedia('(max-width: 900px)').matches) {
                return;
            }

            if (document.body.classList.contains('sidebar-hidden')) {
                document.body.classList.remove('sidebar-hidden');
                localStorage.setItem('sidebarHidden', '0');
            }

            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        }

        function toggleHideSidebar() {
            if (window.matchMedia('(max-width: 900px)').matches) {
                toggleSidebar();
                return;
            }

            document.body.classList.toggle('sidebar-hidden');
            localStorage.setItem('sidebarHidden', document.body.classList.contains('sidebar-hidden') ? '1' : '0');

            if (document.body.classList.contains('sidebar-hidden')) {
                document.body.classList.remove('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', '0');
            }
        }
    </script>
    <?php closeDashboardShell(); ?>
</body>
</html>
