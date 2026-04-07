<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add OJT Log Entry</title>
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
            max-width: 500px;
            margin: 0 auto;
        }

        .card {
            background: #ffffff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }

        .heading {
            font-size: 24px;
            font-weight: 700;
            color: #ff8fab;
            margin-bottom: 5px;
        }

        .subheading {
            font-size: 13px;
            color: #999;
            margin-bottom: 25px;
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
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="date"],
        input[type="time"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: #555;
            transition: border-color 0.3s ease;
        }

        input[type="date"]:focus,
        input[type="time"]:focus {
            outline: none;
            border-color: #ff8fab;
            box-shadow: 0 0 0 3px rgba(255, 139, 171, 0.1);
        }

        .hint {
            font-size: 12px;
            color: #bbb;
            margin-top: 5px;
        }

        .warning {
            background: #fff3e0;
            border-left: 4px solid #ff9800;
            padding: 12px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 13px;
            color: #555;
            display: none;
        }

        .warning.show {
            display: block;
        }

        .computed {
            background: #fff5f7;
            border-left: 4px solid #ff8fab;
            padding: 12px;
            border-radius: 10px;
            margin-top: 15px;
            font-size: 13px;
            display: none;
        }

        .computed.show {
            display: block;
        }

        .computed strong {
            color: #ff8fab;
            font-size: 16px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 13px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-submit {
            background: linear-gradient(90deg, #ffc2d1, #ff8fab);
            color: white;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 139, 171, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-back {
            background: #f8f8f8;
            color: #ff8fab;
            border: 2px solid #ffc2d1;
        }

        .btn-back:hover {
            background: #fff5f7;
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
            .card {
                padding: 20px;
            }

            .heading {
                font-size: 20px;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php
    include 'config.php';
    include 'functions.php';

    $message = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $date = $_POST['date'] ?? '';
        $time_in = $_POST['time_in'] ?? '';
        $time_out = $_POST['time_out'] ?? '';

        // Validation
        if (!$date || !$time_in || !$time_out) {
            $error = '⚠️ Please fill in all fields!';
        } elseif (strtotime($time_out) <= strtotime($time_in)) {
            $error = '⚠️ Time out must be after time in!';
        } elseif (isHoliday($conn, $date)) {
            $error = '🚫 Holiday yan teh 😭 bawal i-count! Please choose another date.';
        } else {
            // Check if date already exists
            $check_query = "SELECT id FROM ojt_logs WHERE date = '$date'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                $error = '⚠️ Entry already exists for this date!';
            } else {
                // Compute hours
                $hours = computeHours($time_in, $time_out);

                if ($hours < 0) {
                    $error = '⚠️ Hours computed is invalid!';
                } else {
                    // Insert into database
                    $insert_query = "INSERT INTO ojt_logs (date, time_in, time_out, hours) VALUES ('$date', '$time_in', '$time_out', $hours)";

                    if (mysqli_query($conn, $insert_query)) {
                        $message = "✅ Log entry added successfully! Hours recorded: $hours hours";
                    } else {
                        $error = "❌ Error: " . mysqli_error($conn);
                    }
                }
            }
        }
    }

    // Get today's date
    $today = date('Y-m-d');
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
            <a href="log_entry.php" class="active"><span class="nav-icon">➕</span><span class="nav-label">Add Log Entry</span></a>
            <a href="logs.php"><span class="nav-icon">📋</span><span class="nav-label">All Logs</span></a>
            <a href="upload.php"><span class="nav-icon">📤</span><span class="nav-label">Upload Sheets</span></a>
            <a href="holidays.php"><span class="nav-icon">🎉</span><span class="nav-label">Manage Holidays</span></a>
        </nav>
    </aside>

    <div class="app-overlay" onclick="closeSidebar()"></div>

    <div class="app-page">

    <div class="container">
        <div class="card">
            <div class="heading">📝 Add Log Entry</div>
            <div class="subheading">Record your daily training hours</div>

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

            <form id="logForm" method="POST">
                <div class="form-group">
                    <label for="date">📅 Date</label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        required
                        max="<?php echo $today; ?>"
                        onchange="checkHoliday(this.value)"
                    >
                    <div class="warning" id="holidayWarning">
                        🚫 This is a holiday! Can't log on holidays.
                    </div>
                </div>

                <div class="form-group">
                    <label for="time_in">🕐 Time In</label>
                    <input 
                        type="time" 
                        id="time_in" 
                        name="time_in" 
                        required
                        onchange="computeDisplay()"
                    >
                    <div class="hint">24-hour format (e.g., 09:00)</div>
                </div>

                <div class="form-group">
                    <label for="time_out">🕑 Time Out</label>
                    <input 
                        type="time" 
                        id="time_out" 
                        name="time_out" 
                        required
                        onchange="computeDisplay()"
                    >
                    <div class="hint">24-hour format (e.g., 17:30)</div>
                </div>

                <div id="computedHours" class="computed">
                    Hours to log: <strong id="hoursDisplay">0.00</strong> hrs
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-submit">✅ Submit</button>
                    <a href="index.php" class="btn btn-back">← Back</a>
                </div>
            </form>
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

        // Holidays list (fetched from server)
        const holidays = <?php
            $holidays_query = "SELECT holiday_date FROM holidays";
            $holidays_result = mysqli_query($conn, $holidays_query);
            $holidays_array = [];
            while ($row = mysqli_fetch_assoc($holidays_result)) {
                $holidays_array[] = $row['holiday_date'];
            }
            echo json_encode($holidays_array);
        ?>;

        function checkHoliday(date) {
            const warning = document.getElementById('holidayWarning');
            const submitBtn = document.querySelector('.btn-submit');

            if (holidays.includes(date)) {
                warning.style.display = 'block';
                submitBtn.disabled = true;
            } else {
                warning.style.display = 'none';
                submitBtn.disabled = false;
            }
        }

        function computeDisplay() {
            const timeIn = document.getElementById('time_in').value;
            const timeOut = document.getElementById('time_out').value;
            const container = document.getElementById('computedHours');

            if (timeIn && timeOut) {
                const inTime = new Date(`1970-01-01T${timeIn}:00`);
                const outTime = new Date(`1970-01-01T${timeOut}:00`);

                if (outTime > inTime) {
                    let hours = (outTime - inTime) / (1000 * 60 * 60);
                    // Subtract 1 hour for lunch
                    hours = hours - 1;

                    if (hours > 0) {
                        document.getElementById('hoursDisplay').textContent = hours.toFixed(2);
                        container.classList.add('show');
                    } else {
                        container.classList.remove('show');
                    }
                } else {
                    container.classList.remove('show');
                }
            } else {
                container.classList.remove('show');
            }
        }

        // Set max date to today
        document.getElementById('date').max = new Date().toISOString().split('T')[0];

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
