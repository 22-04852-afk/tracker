<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Holidays</title>
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

        .alert {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert.error {
            background: #ffe0e6;
            border-left: 4px solid #ff6b9d;
            color: #c2185b;
        }

        .alert.success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            color: #2e7d32;
        }

        .form-group {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 10px;
            margin-bottom: 20px;
            align-items: flex-end;
        }

        label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 6px;
            font-size: 13px;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 2px solid #f0f0f0;
            border-radius: 10px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
        }

        input:focus {
            outline: none;
            border-color: #ff8fab;
            box-shadow: 0 0 0 3px rgba(255, 139, 171, 0.1);
        }

        .btn-add {
            padding: 10px 15px;
            background: linear-gradient(90deg, #ffc2d1, #ff8fab);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 139, 171, 0.3);
        }

        .holiday-list h2 {
            font-size: 18px;
            color: #ff8fab;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .holiday-count {
            font-size: 12px;
            color: #bbb;
            display: inline-block;
            margin-left: 8px;
        }

        .holiday-item {
            padding: 12px;
            border: 1px solid #f0f0f0;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .holiday-item:hover {
            background: #fff5f7;
            border-color: #ffc2d1;
        }

        .holiday-info {
            flex: 1;
        }

        .holiday-date {
            font-weight: 700;
            color: #555;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .holiday-name {
            font-size: 12px;
            color: #bbb;
        }

        .btn-delete {
            background: #ffe0e6;
            color: #ff6b9d;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #ff6b9d;
            color: white;
        }

        .empty-message {
            text-align: center;
            padding: 30px 15px;
            color: #bbb;
            font-size: 14px;
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

            .form-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php
    include 'config.php';

    $message = '';
    $error = '';

    // Add holiday
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
        $holiday_date = $_POST['holiday_date'] ?? '';
        $holiday_name = $_POST['holiday_name'] ?? '';

        if (!$holiday_date) {
            $error = '⚠️ Please select a date!';
        } else {
            $insert_query = "INSERT IGNORE INTO holidays (holiday_date, holiday_name) VALUES ('$holiday_date', '$holiday_name')";

            if (mysqli_query($conn, $insert_query)) {
                if (mysqli_affected_rows($conn) > 0) {
                    $message = '✅ Holiday added successfully!';
                } else {
                    $error = '⚠️ This date is already marked as a holiday!';
                }
            } else {
                $error = '❌ Error: ' . mysqli_error($conn);
            }
        }
    }

    // Delete holiday
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'delete') {
        $holiday_id = $_POST['holiday_id'] ?? '';

        if ($holiday_id) {
            $delete_query = "DELETE FROM holidays WHERE id = $holiday_id";

            if (mysqli_query($conn, $delete_query)) {
                $message = '✅ Holiday removed successfully!';
            } else {
                $error = '❌ Error: ' . mysqli_error($conn);
            }
        }
    }

    // Get all holidays
    $holidays_query = "SELECT * FROM holidays ORDER BY holiday_date ASC";
    $holidays_result = mysqli_query($conn, $holidays_query);
    $total_holidays = mysqli_num_rows($holidays_result);
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
            <a href="logs.php"><span class="nav-icon">📋</span><span class="nav-label">All Logs</span></a>
            <a href="upload.php"><span class="nav-icon">📤</span><span class="nav-label">Upload Sheets</span></a>
            <a href="holidays.php" class="active"><span class="nav-icon">🎉</span><span class="nav-label">Manage Holidays</span></a>
        </nav>
    </aside>

    <div class="app-overlay" onclick="closeSidebar()"></div>

    <div class="app-page">

    <div class="container">
        <div class="header">
            <h1>🎉 Manage Holidays</h1>
            <p>Add or remove holidays from your tracking</p>
        </div>

        <div class="card">
            <?php if ($message): ?>
                <div class="alert success show">
                    ✅ <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error show">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="action" value="add">

                <div class="form-group">
                    <div>
                        <label for="holiday_date">📅 Date</label>
                        <input type="date" id="holiday_date" name="holiday_date" required>
                    </div>
                    <div>
                        <label for="holiday_name">📝 Holiday</label>
                        <input type="text" id="holiday_name" name="holiday_name" placeholder="e.g., Holy Week" required>
                    </div>
                    <button type="submit" class="btn-add">+ Add</button>
                </div>
            </form>
        </div>

        <div class="card holiday-list">
            <h2>Holiday List <span class="holiday-count"><?php echo $total_holidays; ?> holidays</span></h2>

            <?php
            if ($total_holidays > 0) {
                mysqli_data_seek($holidays_result, 0);
                while ($row = mysqli_fetch_assoc($holidays_result)) {
                    $date = $row['holiday_date'];
                    $name = $row['holiday_name'];
                    $formatted_date = date('M d, Y', strtotime($date));

                    echo "
                    <div class='holiday-item'>
                        <div class='holiday-info'>
                            <div class='holiday-date'>$formatted_date</div>
                            <div class='holiday-name'>$name</div>
                        </div>
                        <form method='POST' style='display: inline;' onsubmit=\"return confirm('Delete this holiday?');\">
                            <input type='hidden' name='action' value='delete'>
                            <input type='hidden' name='holiday_id' value='{$row['id']}'>
                            <button type='submit' class='btn-delete'>Delete</button>
                        </form>
                    </div>
                    ";
                }
            } else {
                echo "<div class='empty-message'>No holidays added yet. Start by adding one!</div>";
            }
            ?>
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
