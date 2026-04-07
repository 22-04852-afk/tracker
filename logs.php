<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All OJT Logs</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #ffd6e0;
            color: #555;
            min-height: 100vh;
            padding: 15px;
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
            color: #ff8fab;
            margin-bottom: 5px;
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
            background: linear-gradient(90deg, #ffc2d1, #ff8fab);
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

        .search-box {
            margin-bottom: 20px;
        }

        .search-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }

        .search-input:focus {
            outline: none;
            border-color: #ff8fab;
            box-shadow: 0 0 0 3px rgba(255, 139, 171, 0.1);
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
            border: 1px solid #f2dbe4;
            border-radius: 10px;
            background: #fff7fa;
        }

        .cleanup-meta {
            font-size: 12px;
            color: #a56b81;
            font-weight: 600;
        }

        .cleanup-btn {
            border: none;
            background: #ff8fab;
            color: white;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            cursor: pointer;
        }

        .cleanup-btn:hover {
            background: #ff6b9d;
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
            background: #fff5f7;
            border-color: #ffc2d1;
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
            color: #ff8fab;
            font-size: 16px;
        }

        .log-delete {
            margin-left: 10px;
            background: #ffe0e6;
            color: #ff6b9d;
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
            background: #ff6b9d;
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 40px 15px;
            color: #bbb;
            font-size: 14px;
        }

        .empty-message a {
            color: #ff8fab;
            text-decoration: none;
            font-weight: 600;
        }

        .btn-back {
            display: block;
            text-align: center;
            padding: 12px;
            background: linear-gradient(90deg, #ffc2d1, #ff8fab);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 139, 171, 0.3);
        }

        .app-topbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid #f0d9e1;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 14px;
            z-index: 1000;
        }

        .app-title {
            font-size: 15px;
            font-weight: 700;
            color: #ff6b9d;
        }

        .app-menu-btn {
            border: none;
            background: #ffe6ee;
            color: #ff6b9d;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: none;
        }

        .app-desktop-btn {
            width: 34px;
            height: 34px;
            border: 1px solid #ead9e3;
            border-radius: 10px;
            background: #fff;
            color: #a06784;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .app-sidebar {
            position: fixed;
            top: 56px;
            left: 0;
            bottom: 0;
            width: 220px;
            background: #fff;
            border-right: 1px solid #f0d9e1;
            padding: 14px;
            z-index: 999;
            overflow-y: auto;
            transition: width 0.2s ease, transform 0.25s ease;
            overflow-x: hidden;
        }

        .app-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .app-nav a {
            text-decoration: none;
            color: #a56b81;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .app-nav a .nav-icon {
            width: 18px;
            text-align: center;
            flex-shrink: 0;
        }

        .app-nav a:hover,
        .app-nav a.active {
            background: #fff2f6;
            color: #ff6b9d;
            border-color: #ffd4e2;
        }

        .app-page {
            margin-left: 220px;
            padding-top: 72px;
            transition: margin-left 0.2s ease;
        }

        body.sidebar-collapsed .app-sidebar {
            width: 86px;
            padding-left: 10px;
            padding-right: 10px;
        }

        body.sidebar-collapsed .app-nav a {
            justify-content: center;
            padding: 10px;
        }

        body.sidebar-collapsed .app-nav a .nav-label {
            display: none;
        }

        body.sidebar-collapsed .app-page {
            margin-left: 86px;
        }

        body.sidebar-hidden .app-sidebar {
            transform: translateX(-100%);
        }

        body.sidebar-hidden .app-page {
            margin-left: 0;
        }

        .app-overlay {
            display: none;
        }

        @media (max-width: 900px) {
            .app-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .app-desktop-btn {
                display: none;
            }

            .app-sidebar {
                transform: translateX(-100%);
                transition: transform 0.25s ease;
            }

            body.sidebar-open .app-sidebar {
                transform: translateX(0);
            }

            .app-page {
                margin-left: 0;
            }

            body.sidebar-collapsed .app-page {
                margin-left: 0;
            }

            .app-overlay {
                position: fixed;
                inset: 56px 0 0 0;
                background: rgba(0, 0, 0, 0.2);
                display: none;
                z-index: 998;
            }

            body.sidebar-open .app-overlay {
                display: block;
            }
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
    $total_days = getTotalOJTDays($conn);
    $days_left = calculateDaysLeft($conn, 500);
    ?>

    <div class="app-topbar">
        <button class="app-menu-btn" type="button" onclick="toggleSidebar()">☰</button>
        <button class="app-desktop-btn" type="button" onclick="toggleCollapse()" title="Collapse or expand sidebar">◧</button>
        <button class="app-desktop-btn" type="button" onclick="toggleHideSidebar()" title="Hide or show sidebar">↔</button>
        <div class="app-title">OJT Tracker Navigation</div>
    </div>

    <aside class="app-sidebar">
        <nav class="app-nav">
            <a href="index.php"><span class="nav-icon">🏠</span><span class="nav-label">Dashboard</span></a>
            <a href="log_entry.php"><span class="nav-icon">➕</span><span class="nav-label">Add Log Entry</span></a>
            <a href="logs.php" class="active"><span class="nav-icon">📋</span><span class="nav-label">All Logs</span></a>
            <a href="upload.php"><span class="nav-icon">📤</span><span class="nav-label">Upload Sheets</span></a>
            <a href="holidays.php"><span class="nav-icon">🎉</span><span class="nav-label">Manage Holidays</span></a>
        </nav>
    </aside>

    <div class="app-overlay" onclick="closeSidebar()"></div>

    <div class="app-page">

    <div class="container">
        <div class="header">
            <h1>📋 All Log Entries</h1>
            <p>Your complete training history</p>
        </div>

        <?php if ($cleanupMessage): ?>
            <div class="notice success"><?php echo htmlspecialchars($cleanupMessage); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="stats-bar">
                <div class="stat-chip">
                    <span>Total Hours</span>
                    <strong><?php echo number_format($total_hours, 0); ?></strong>
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

            <div class="search-box">
                <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search by date (e.g., Mar 10)...">
            </div>

            <?php if ($unlinkedCount > 0): ?>
                <div class="cleanup-row">
                    <div class="cleanup-meta">Unlinked logs in MySQL: <?php echo $unlinkedCount; ?></div>
                    <form method="POST" onsubmit="return confirm('Delete all unlinked logs? This cannot be undone.');">
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
                        $hours = number_format($row['hours'], 1);

                        echo "
                        <div class='log-item' data-date='$date_formatted'>
                            <div class='log-content'>
                                <div class='log-date'>$date_formatted</div>
                                <div class='log-time'>$time_in → $time_out</div>
                            </div>
                            <div class='log-hours'>$hours hrs</div>
                            <form method='POST' style='display: inline;' onsubmit=\"return confirm('Delete this log?');\">
                                <input type='hidden' name='delete_id' value='{$row['id']}'>
                                <button type='submit' class='log-delete'>Delete</button>
                            </form>
                        </div>
                        ";
                    }
                } else {
                    echo "<div class='empty-message'>No logs yet. <a href='log_entry.php'>Add your first log!</a></div>";
                }
                ?>
            </div>
        </div>

        <a href="index.php" class="btn-back">← Back to Dashboard</a>
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

        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const logItems = document.querySelectorAll('.log-item');

            logItems.forEach(item => {
                const date = item.getAttribute('data-date').toLowerCase();
                if (date.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
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
</div>
</body>
</html>
