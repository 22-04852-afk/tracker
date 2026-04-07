<?php
include 'config.php';

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
        $deletedRows = isset($_GET['deleted_rows']) ? (int)$_GET['deleted_rows'] : 0;
        $statusMessage = 'Uploaded sheet deleted successfully. Removed imported logs: ' . $deletedRows . '.';
        $statusType = 'success';
    } elseif ($_GET['deleted'] === '0') {
        $statusMessage = 'Unable to delete uploaded file.';
        $statusType = 'error';
    }
}

$uploadedFiles = mysqli_query($conn, "SELECT * FROM uploaded_files ORDER BY created_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Excel - OJT Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(180deg, #ffd6e0 0%, #ffe8f0 100%);
            color: #555;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 520px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #ff6b9d;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 14px;
            color: #999;
        }

        .card {
            background: #ffffff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #ff6b9d;
            margin-bottom: 16px;
            display: block;
        }

        .instruction-box {
            background: #fff5f7;
            border: 1px solid #ffeaef;
            border-left: 3px solid #ff8fab;
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #555;
            line-height: 1.6;
        }

        .instruction-box strong {
            color: #ff6b9d;
        }

        .instruction-box ol {
            margin-left: 18px;
            margin-top: 8px;
        }

        .instruction-box li {
            margin-bottom: 6px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .file-upload {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            border: 2px dashed #ffc2d1;
            border-radius: 16px;
            background: #fff5f7;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-height: 140px;
        }

        .file-upload:hover {
            border-color: #ff8fab;
            background: linear-gradient(135deg, #fff5f7, #fffafc);
        }

        .file-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .upload-icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }

        .upload-text {
            font-weight: 600;
            color: #ff8fab;
            margin-bottom: 4px;
            font-size: 15px;
        }

        .upload-hint {
            font-size: 12px;
            color: #bbb;
        }

        .file-name {
            font-size: 13px;
            color: #4CAF50;
            font-weight: 600;
            margin-top: 10px;
            display: none;
        }

        .file-name.show {
            display: block;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ffc2d1 0%, #ff8fab 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 8px 20px rgba(255, 139, 171, 0.2);
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(255, 139, 171, 0.35);
        }

        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #ff8fab;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .status-banner {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-banner.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .status-banner.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .history-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .history-item {
            border: 1px solid #f0f0f0;
            border-radius: 12px;
            padding: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .history-item:hover {
            border-color: #ffc2d1;
            background: #fff5f7;
        }

        .history-name {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            word-break: break-all;
            margin-bottom: 4px;
        }

        .history-meta {
            font-size: 11px;
            color: #999;
        }

        .btn-delete-file {
            border: none;
            background: #ffe0e6;
            color: #c2185b;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-delete-file:hover {
            background: #ff6b9d;
            color: #fff;
        }

        .empty-history {
            font-size: 13px;
            color: #999;
            text-align: center;
            padding: 14px;
            border: 1px dashed #ffd6e0;
            border-radius: 12px;
            background: #fffafb;
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

        /* Results Section */
        .results-card {
            margin-top: 20px;
            display: none;
        }

        .results-card.show {
            display: block;
        }

        .results-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .result-item {
            padding: 14px;
            border-radius: 12px;
            text-align: center;
        }

        .result-item.success {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
        }

        .result-item.skipped {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
        }

        .result-value {
            font-size: 24px;
            font-weight: 700;
            display: block;
            margin-bottom: 4px;
        }

        .result-item.success .result-value {
            color: #2e7d32;
        }

        .result-item.skipped .result-value {
            color: #e65100;
        }

        .result-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .result-item.success .result-label {
            color: #558b2f;
        }

        .result-item.skipped .result-label {
            color: #e6550;
        }

        .error-message {
            background: #ffebee;
            border: 1px solid #ffcdd2;
            border-left: 3px solid #ff6b9d;
            color: #c62828;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 12px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .error-list {
            margin: 0;
            padding-left: 18px;
            margin-top: 8px;
        }

        .error-list li {
            margin-bottom: 4px;
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

        @media (max-width: 512px) {
            .container {
                padding: 0;
            }

            .card {
                padding: 20px;
                border-radius: 20px;
            }

            .header h1 {
                font-size: 28px;
            }

            .file-upload {
                padding: 24px 16px;
                min-height: 120px;
            }
        }
    </style>
</head>
<body>
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
            <a href="logs.php"><span class="nav-icon">📋</span><span class="nav-label">All Logs</span></a>
            <a href="upload.php" class="active"><span class="nav-icon">📤</span><span class="nav-label">Upload Sheets</span></a>
            <a href="holidays.php"><span class="nav-icon">🎉</span><span class="nav-label">Manage Holidays</span></a>
        </nav>
    </aside>

    <div class="app-overlay" onclick="closeSidebar()"></div>

    <div class="app-page">
    <div class="container">
        <div class="header">
            <h1>📊 Upload Excel</h1>
            <p>Import your training logs in bulk</p>
        </div>

        <?php if ($statusMessage): ?>
            <div class="status-banner <?php echo $statusType; ?>">
                <?php echo htmlspecialchars($statusMessage); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <span class="section-title">📋 File Format</span>
            <div class="instruction-box">
                <strong>Your Excel file should have this structure:</strong>
                <ol>
                    <li><strong>Column A:</strong> Date (e.g., 2026-04-06)</li>
                    <li><strong>Column B:</strong> Time In (e.g., 09:00)</li>
                    <li><strong>Column C:</strong> Time Out (e.g., 17:30)</li>
                </ol>
                <p style="margin-top: 10px;">⚠️ First row (header) will be skipped automatically. Holidays and duplicates are excluded.</p>
            </div>

            <form id="uploadForm" method="POST" action="upload_handler.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label class="file-upload">
                        <span class="upload-icon">📁</span>
                        <span class="upload-text">Choose Excel File</span>
                        <span class="upload-hint">or drag and drop</span>
                        <span class="file-name" id="fileName"></span>
                        <input type="file" name="excel" id="fileInput" accept=".xlsx,.xls,.csv" required>
                    </label>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn">🌸 Upload File</button>
            </form>
        </div>

        <div class="back-link">
            <a href="index.php">← Back to Dashboard</a>
        </div>

        <div class="card">
            <span class="section-title">🗂 Uploaded Files</span>
            <div class="history-list">
                <?php if ($uploadedFiles && mysqli_num_rows($uploadedFiles) > 0): ?>
                    <?php while ($fileRow = mysqli_fetch_assoc($uploadedFiles)): ?>
                        <div class="history-item">
                            <div>
                                <div class="history-name"><?php echo htmlspecialchars($fileRow['original_name']); ?></div>
                                <div class="history-meta">
                                    Imported: <?php echo (int)$fileRow['uploaded_count']; ?>, Skipped: <?php echo (int)$fileRow['skipped_count']; ?>
                                    • <?php echo date('M d, Y h:i A', strtotime($fileRow['created_at'])); ?>
                                </div>
                            </div>
                            <form method="POST" action="delete_upload.php" onsubmit="return confirm('Delete this uploaded file?');">
                                <input type="hidden" name="file_id" value="<?php echo (int)$fileRow['id']; ?>">
                                <button type="submit" class="btn-delete-file">Delete File</button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-history">No uploaded files yet.</div>
                <?php endif; ?>
            </div>
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

        const fileInput = document.getElementById('fileInput');
        const fileNameDisplay = document.getElementById('fileName');
        const fileUploadArea = document.querySelector('.file-upload');

        // Handle file selection
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                fileNameDisplay.textContent = '✅ ' + this.files[0].name;
                fileNameDisplay.classList.add('show');
                fileUploadArea.style.borderColor = '#4CAF50';
                fileUploadArea.style.background = '#e8f5e9';
            }
        });

        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.style.borderColor = '#ff8fab';
            fileUploadArea.style.background = 'linear-gradient(135deg, #fff5f7, #fffafc)';
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.style.borderColor = '#ffc2d1';
            fileUploadArea.style.background = '#fff5f7';
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileNameDisplay.textContent = '✅ ' + e.dataTransfer.files[0].name;
                fileNameDisplay.classList.add('show');
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
</div>
</body>
</html>
