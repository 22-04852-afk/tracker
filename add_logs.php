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
        $error = 'Please fill in all fields.';
    } elseif (strtotime($time_out) <= strtotime($time_in)) {
        $error = 'Time out must be after time in.';
    } elseif (isHoliday($conn, $date)) {
        $error = 'This date is a holiday and cannot be counted. Please choose another date.';
    } else {
        // Check if date already exists
        $check_query = "SELECT id FROM ojt_logs WHERE date = '$date'";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Entry already exists for this date.';
        } else {
            // Compute hours
            $hours = computeHours($time_in, $time_out);

            if ($hours < 0) {
                $error = 'Computed hours are invalid.';
            } else {
                // Insert into database
                $insert_query = "INSERT INTO ojt_logs (date, time_in, time_out, hours) VALUES ('$date', '$time_in', '$time_out', $hours)";

                if (mysqli_query($conn, $insert_query)) {
                    $message = "Log entry added successfully. Hours recorded: $hours hours.";
                } else {
                    $error = "Error: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Get today's date
$today = date('Y-m-d');
seedPhilippineHolidays($conn, (int) date('Y') - 1);
seedPhilippineHolidays($conn, (int) date('Y'));
seedPhilippineHolidays($conn, (int) date('Y') + 1);
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
    <title>Add Log - OJT Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .add-log-container {
            max-width: 980px;
            margin: 0 auto;
            padding: 0 24px 20px;
        }

        .add-log-card {
            max-width: 600px;
            margin: 0 auto;
            background: var(--card);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--line);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .add-log-heading {
            font-size: 24px;
            font-weight: 700;
            color: var(--ink);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-log-heading i {
            color: var(--pink-strong);
        }

        .add-log-subheading {
            font-size: 14px;
            color: var(--muted);
            margin-bottom: 25px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            display: none;
            border-left: 4px solid;
        }

        .alert.show {
            display: block;
        }

        .alert.error {
            background: var(--pink-soft);
            border-left-color: var(--pink-strong);
            color: var(--pink-strong);
        }

        .alert.success {
            background: #e8f5e9;
            border-left-color: #4caf50;
            color: #2e7d32;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--ink);
            margin-bottom: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group label i {
            color: var(--pink);
            font-size: 15px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--line);
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            color: var(--ink);
            background: var(--card);
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--pink);
            box-shadow: 0 0 0 3px rgba(228, 134, 173, 0.1);
        }

        .form-hint {
            font-size: 12px;
            color: var(--muted);
            margin-top: 5px;
        }

        .warning {
            background: #fef3e0;
            border-left: 4px solid #ff9800;
            padding: 12px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 13px;
            color: #e65100;
            display: none;
        }

        .warning.show {
            display: block;
        }

        .computed {
            background: var(--pink-soft);
            border-left: 4px solid var(--pink);
            padding: 14px;
            border-radius: 8px;
            margin-top: 20px;
            margin-bottom: 20px;
            font-size: 13px;
            display: none;
        }

        .computed.show {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .computed strong {
            color: var(--pink-strong);
            font-size: 18px;
        }

        .computed i {
            color: var(--pink);
            font-size: 20px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 2px solid transparent;
        }

        .btn-submit {
            background: linear-gradient(90deg, var(--pink), var(--pink-strong));
            color: white;
            flex: 1.3;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(228, 134, 173, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-back {
            background: transparent;
            color: var(--pink);
            border: 2px solid var(--line);
        }

        .btn-back:hover {
            background: var(--pink-soft);
            border-color: var(--pink);
        }

        @media (max-width: 600px) {
            .add-log-container {
                padding: 0 12px 16px;
            }

            .add-log-card {
                max-width: 100%;
                padding: 20px;
            }

            .add-log-heading {
                font-size: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-submit {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <?php renderDashboardShell('add_logs.php', $dashboardStats); ?>

    <div class="add-log-container">
        <div class="add-log-card">
            <div class="add-log-heading">
                <i class="fa-solid fa-pencil" aria-hidden="true"></i>
                Add Log Entry
            </div>
            <div class="add-log-subheading">Record your daily training hours</div>

            <?php if ($message): ?>
                <div class="alert success show">
                    <i class="fa-solid fa-check-circle" style="margin-right: 8px;"></i><?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error show">
                    <i class="fa-solid fa-exclamation-circle" style="margin-right: 8px;"></i><?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form id="logForm" method="POST">
                <div class="form-group">
                    <label for="date">
                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                        Date
                    </label>
                    <input 
                        type="date" 
                        id="date" 
                        name="date" 
                        required
                        max="<?php echo $today; ?>"
                        onchange="checkHoliday(this.value)"
                    >
                    <div class="warning" id="holidayWarning">
                        <i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i>This is a holiday. Cannot log on holidays.
                    </div>
                </div>

                <div class="form-group">
                    <label for="time_in">
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        Time In
                    </label>
                    <input 
                        type="time" 
                        id="time_in" 
                        name="time_in" 
                        required
                        onchange="computeDisplay()"
                    >
                    <div class="form-hint">24-hour format (e.g., 09:00)</div>
                </div>

                <div class="form-group">
                    <label for="time_out">
                        <i class="fa-regular fa-clock" aria-hidden="true"></i>
                        Time Out
                    </label>
                    <input 
                        type="time" 
                        id="time_out" 
                        name="time_out" 
                        required
                        onchange="computeDisplay()"
                    >
                    <div class="form-hint">24-hour format (e.g., 17:30)</div>
                </div>

                <div id="computedHours" class="computed">
                    <i class="fa-solid fa-hourglass-end" aria-hidden="true"></i>
                    <span>Hours to log: <strong id="hoursDisplay">0</strong> hrs</span>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-submit">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Submit
                    </button>
                    <a href="logs.php" class="btn btn-back">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        Back
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Holidays list (fetched from server)
        const holidayMap = <?php
            $holidays_query = "SELECT holiday_date, holiday_name FROM holidays";
            $holidays_result = mysqli_query($conn, $holidays_query);
            $holidays_array = array();
            while ($row = mysqli_fetch_assoc($holidays_result)) {
                $holidayName = trim((string) ($row['holiday_name'] ?? 'Holiday'));
                if ($holidayName === '') {
                    $holidayName = 'Holiday';
                }
                $holidays_array[$row['holiday_date']] = $holidayName;
            }
            echo json_encode($holidays_array);
        ?>;

        function checkHoliday(date) {
            const warningElement = document.getElementById('holidayWarning');
            const holidayName = holidayMap[date];

            if (holidayName) {
                warningElement.innerHTML = `<i class="fa-solid fa-triangle-exclamation" style="margin-right: 8px;"></i>${holidayName}. Cannot log on holidays.`;
                warningElement.classList.add('show');
            } else {
                warningElement.classList.remove('show');
            }
        }

        function computeDisplay() {
            const timeIn = document.getElementById('time_in').value;
            const timeOut = document.getElementById('time_out').value;

            if (timeIn && timeOut) {
                const timeInDate = new Date('2000-01-01 ' + timeIn);
                const timeOutDate = new Date('2000-01-01 ' + timeOut);

                if (timeOutDate > timeInDate) {
                    const hours = (timeOutDate - timeInDate) / (1000 * 60 * 60);
                    document.getElementById('hoursDisplay').textContent = hours.toFixed(2);
                    document.getElementById('computedHours').classList.add('show');
                } else {
                    document.getElementById('computedHours').classList.remove('show');
                }
            } else {
                document.getElementById('computedHours').classList.remove('show');
            }
        }

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
    </script>

    <?php closeDashboardShell(); ?>
</body>
</html>
