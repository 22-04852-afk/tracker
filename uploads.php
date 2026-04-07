<?php
include 'config.php';
include_once 'functions.php';

$createUploadedFilesTable = "CREATE TABLE IF NOT EXISTS uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    uploaded_count INT NOT NULL DEFAULT 0,
    skipped_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createUploadedFilesTable);

$statusMessage = '';
$statusType = '';

if (isset($_GET['deleted'])) {
    if ($_GET['deleted'] === '1') {
        $deletedRows = isset($_GET['deleted_rows']) ? (int) $_GET['deleted_rows'] : 0;
        $statusMessage = 'Uploaded sheet deleted successfully. Removed imported logs: ' . $deletedRows . '.';
        $statusType = 'success';
    } elseif ($_GET['deleted'] === '0') {
        $statusMessage = 'Unable to delete uploaded file.';
        $statusType = 'error';
    }
}

$uploadedFiles = mysqli_query($conn, "SELECT * FROM uploaded_files ORDER BY created_at DESC LIMIT 20");
$totalHours = getTotalHours($conn);
$requiredHours = getRequiredHours($conn);
$dashboardStats = array(
    'total_hours' => $totalHours,
    'required_hours' => $requiredHours,
    'progress_percent' => min(100, ($requiredHours > 0 ? ($totalHours / $requiredHours) * 100 : 0)),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uploads - OJT Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .uploads-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 10px 18px 28px;
        }

        .welcome-hero {
            text-align: center;
            margin: 6px 0 18px;
        }

        .welcome-hero h1 {
            color: #221f2a;
            font-size: clamp(30px, 4vw, 52px);
            line-height: 1.1;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .welcome-hero p {
            color: #8b8f9a;
            font-size: 16px;
            line-height: 1.4;
            max-width: 520px;
            margin: 0 auto;
        }

        .status-banner {
            padding: 12px 14px;
            border-radius: 12px;
            margin: 0 auto 14px;
            max-width: 760px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-banner.success {
            background: #e9f8ed;
            color: #2f7f45;
            border: 1px solid #c7e8d1;
        }

        .status-banner.error {
            background: #ffedf0;
            color: #bf3d63;
            border: 1px solid #f3ced8;
        }

        .upload-card {
            max-width: 760px;
            margin: 0 auto;
            background: var(--pink-soft);
            border: 2px dashed var(--accent-border, var(--line));
            border-radius: 26px;
            padding: 38px 30px 34px;
            text-align: center;
            transition: border-color 0.2s ease, transform 0.2s ease, background 0.2s ease;
        }

        .upload-card.drag-over {
            border-color: var(--pink-strong);
            background: var(--pink-soft);
            transform: translateY(-1px);
        }

        .upload-badge {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 6px 18px rgba(215, 127, 162, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--pink-strong);
            font-size: 28px;
            margin-bottom: 16px;
        }

        .upload-card h2 {
            font-size: clamp(24px, 3vw, 38px);
            line-height: 1.15;
            font-weight: 800;
            color: #221f2a;
            margin-bottom: 8px;
        }

        .upload-card p {
            color: #8c93a0;
            font-size: 14px;
            line-height: 1.3;
            margin: 0 auto 22px;
            max-width: 390px;
        }

        .file-input {
            position: absolute;
            opacity: 0;
            width: 1px;
            height: 1px;
            pointer-events: none;
        }

        .select-file-btn,
        .submit-file-btn {
            border: none;
            border-radius: 999px;
            padding: 12px 28px;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .select-file-btn {
            background: linear-gradient(90deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: #fff;
            font-size: 26px;
            min-width: 230px;
            box-shadow: 0 14px 24px rgba(226, 85, 141, 0.28);
        }

        .select-file-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 30px rgba(226, 85, 141, 0.4);
        }

        .selected-file {
            margin-top: 14px;
            font-size: 14px;
            color: #5b6778;
            font-weight: 600;
            display: none;
        }

        .selected-file.show {
            display: block;
        }

        .submit-row {
            margin-top: 14px;
            display: none;
            justify-content: center;
        }

        .submit-row.show {
            display: flex;
        }

        .submit-file-btn {
            background: #ffffff;
            color: var(--pink-strong);
            border: 1px solid var(--accent-border, var(--line));
            font-size: 16px;
            padding: 10px 20px;
        }

        .submit-file-btn:hover {
            transform: translateY(-1px);
        }

        .feature-grid {
            margin: 16px auto 0;
            max-width: 760px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .feature-card {
            background: #fff;
            border-radius: 18px;
            border: 1px solid #f0e8ee;
            padding: 18px;
            box-shadow: 0 6px 14px rgba(40, 27, 37, 0.04);
        }

        .feature-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--pink-soft);
            color: var(--pink-strong);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .feature-card h3 {
            color: #24222d;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .feature-card p {
            color: #8b93a0;
            font-size: 13px;
            line-height: 1.5;
        }

        .history-wrap {
            max-width: 760px;
            margin: 18px auto 0;
            background: #fff;
            border: 1px solid #eee4ea;
            border-radius: 18px;
            padding: 16px;
        }

        .history-wrap h4 {
            color: #24222d;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .history-item {
            border: 1px solid #f0e8ee;
            border-radius: 12px;
            padding: 11px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .history-name {
            font-size: 13px;
            font-weight: 700;
            color: #3a3340;
            margin-bottom: 3px;
            word-break: break-word;
        }

        .history-meta {
            font-size: 11px;
            color: #8f94a2;
        }

        .btn-delete-file {
            border: none;
            background: var(--pink-soft);
            color: var(--pink-strong);
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .btn-delete-file:hover {
            background: var(--pink-strong);
            color: #fff;
        }

        .empty-history {
            font-size: 13px;
            color: #8f94a2;
            text-align: center;
            padding: 14px;
            border: 1px dashed #eddce4;
            border-radius: 10px;
            background: #fffbfc;
        }

        body.modal-open .uploads-page {
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
            font-size: 32px;
            font-weight: 800;
            line-height: 1.15;
            margin: 0 0 10px;
        }

        .confirm-modal p {
            font-size: 14px;
            color: #8f95a1;
            line-height: 1.45;
            margin: 0 auto 18px;
            max-width: 320px;
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

        .confirm-delete-btn:hover {
            filter: brightness(0.97);
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

        .cancel-delete-btn:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .uploads-page {
                padding: 10px 12px 20px;
            }

            .upload-card {
                padding: 26px 16px 22px;
            }

            .upload-card p {
                font-size: 13px;
            }

            .select-file-btn {
                font-size: 20px;
                min-width: 184px;
                padding: 11px 22px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .history-item {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php renderDashboardShell('uploads.php', $dashboardStats); ?>

    <div class="uploads-page">
        <section class="welcome-hero">
            <h1>Welcome back, Intern!</h1>
            <p>Ready to log your progress? Upload your latest Excel sheet to sync your training hours.</p>
        </section>

        <?php if ($statusMessage): ?>
            <div class="status-banner <?php echo $statusType; ?>">
                <?php echo htmlspecialchars($statusMessage); ?>
            </div>
        <?php endif; ?>

        <form id="uploadForm" method="POST" action="upload_handler.php" enctype="multipart/form-data" class="upload-card">
            <div class="upload-badge"><i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i></div>
            <h2>Upload your Excel tracker</h2>
            <p>Drag and drop your .xlsx file here, or click to browse your computer.</p>

            <input type="file" name="excel" id="fileInput" class="file-input" accept=".xlsx,.xls,.csv" required>
            <button type="button" class="select-file-btn" id="selectFileBtn">Select File</button>

            <div class="selected-file" id="fileName"></div>
            <div class="submit-row" id="submitRow">
                <button type="submit" class="submit-file-btn" id="submitBtn">Upload now</button>
            </div>
        </form>

        <section class="feature-grid">
            <article class="feature-card">
                <span class="feature-icon"><i class="fa-regular fa-file-excel" aria-hidden="true"></i></span>
                <h3>Supported Formats</h3>
                <p>Standard Excel .xlsx and .xls exports from common tracker templates are supported.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
                <h3>Smart Deduplication</h3>
                <p>Entries with matching dates and times are automatically skipped to keep logs clean.</p>
            </article>
        </section>

        <section class="history-wrap">
            <h4>Recent Uploads</h4>
            <div class="history-list">
                <?php if ($uploadedFiles && mysqli_num_rows($uploadedFiles) > 0): ?>
                    <?php while ($fileRow = mysqli_fetch_assoc($uploadedFiles)): ?>
                        <div class="history-item">
                            <div>
                                <div class="history-name"><?php echo htmlspecialchars($fileRow['original_name']); ?></div>
                                <div class="history-meta">
                                    Imported: <?php echo (int) $fileRow['uploaded_count']; ?>, Skipped: <?php echo (int) $fileRow['skipped_count']; ?>
                                    | <?php echo date('M d, Y h:i A', strtotime($fileRow['created_at'])); ?>
                                </div>
                            </div>
                            <form method="POST" action="delete_upload.php" class="delete-upload-form" data-file-name="<?php echo htmlspecialchars($fileRow['original_name']); ?>">
                                <input type="hidden" name="file_id" value="<?php echo (int) $fileRow['id']; ?>">
                                <button type="submit" class="btn-delete-file">Delete File</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-history">No uploaded files yet.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="confirm-modal-overlay" id="deleteConfirmModal" aria-hidden="true">
        <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
            <div class="confirm-modal-icon"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i></div>
            <h5 id="deleteModalTitle">Are you sure you want to delete this file?</h5>
            <p>Deleting <strong id="deleteModalFileName">this file</strong> cannot be undone. Your log history might be affected.</p>
            <button type="button" class="confirm-delete-btn" id="confirmDeleteBtn">Confirm Delete</button>
            <button type="button" class="cancel-delete-btn" id="cancelDeleteBtn">Cancel</button>
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

        const uploadForm = document.getElementById('uploadForm');
        const fileInput = document.getElementById('fileInput');
        const selectFileBtn = document.getElementById('selectFileBtn');
        const fileNameDisplay = document.getElementById('fileName');
        const submitRow = document.getElementById('submitRow');
        const deleteConfirmModal = document.getElementById('deleteConfirmModal');
        const deleteModalFileName = document.getElementById('deleteModalFileName');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

        let pendingDeleteForm = null;

        function showSelectedFile(file) {
            fileNameDisplay.textContent = 'Selected: ' + file.name;
            fileNameDisplay.classList.add('show');
            submitRow.classList.add('show');
        }

        selectFileBtn.addEventListener('click', function() {
            fileInput.click();
        });

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                showSelectedFile(this.files[0]);
            }
        });

        uploadForm.addEventListener('dragover', function(event) {
            event.preventDefault();
            uploadForm.classList.add('drag-over');
        });

        uploadForm.addEventListener('dragleave', function() {
            uploadForm.classList.remove('drag-over');
        });

        uploadForm.addEventListener('drop', function(event) {
            event.preventDefault();
            uploadForm.classList.remove('drag-over');

            if (event.dataTransfer.files && event.dataTransfer.files[0]) {
                fileInput.files = event.dataTransfer.files;
                showSelectedFile(event.dataTransfer.files[0]);
            }
        });

        function openDeleteModal(form) {
            pendingDeleteForm = form;
            const fileName = form.getAttribute('data-file-name') || 'this file';
            deleteModalFileName.textContent = '"' + fileName + '"';
            deleteConfirmModal.classList.add('show');
            deleteConfirmModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
        }

        function closeDeleteModal() {
            deleteConfirmModal.classList.remove('show');
            deleteConfirmModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            pendingDeleteForm = null;
        }

        document.querySelectorAll('.delete-upload-form').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                openDeleteModal(form);
            });
        });

        confirmDeleteBtn.addEventListener('click', function() {
            if (!pendingDeleteForm) {
                closeDeleteModal();
                return;
            }

            const formToSubmit = pendingDeleteForm;
            closeDeleteModal();
            formToSubmit.submit();
        });

        cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        deleteConfirmModal.addEventListener('click', function(event) {
            if (event.target === deleteConfirmModal) {
                closeDeleteModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && deleteConfirmModal.classList.contains('show')) {
                closeDeleteModal();
            }
        });

        function toggleSidebar() {
            document.body.classList.toggle('sidebar-open');
        }

        function closeSidebar() {
            document.body.classList.remove('sidebar-open');
        }

        function toggleCollapse() {
            if (window.matchMedia('(max-width: 980px)').matches) {
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
            if (window.matchMedia('(max-width: 980px)').matches) {
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
