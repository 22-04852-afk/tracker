<?php
include 'config.php';
include_once 'functions.php';

$message = '';
$error = '';

$selectedMonth = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$selectedYear = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = (int) date('n');
}

if ($selectedYear < 2000 || $selectedYear > 2100) {
    $selectedYear = (int) date('Y');
}

seedPhilippineHolidays($conn, $selectedYear);

$monthStart = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$monthTs = strtotime($monthStart);
$daysInMonth = (int) date('t', $monthTs);
$startWeekday = (int) date('w', $monthTs);
$monthTitle = date('F Y', $monthTs);

$prevTs = strtotime('-1 month', $monthTs);
$nextTs = strtotime('+1 month', $monthTs);

$prevMonth = (int) date('n', $prevTs);
$prevYear = (int) date('Y', $prevTs);
$nextMonth = (int) date('n', $nextTs);
$nextYear = (int) date('Y', $nextTs);

$monthHolidays = array();
$holidayQuery = "SELECT id, holiday_date, holiday_name FROM holidays WHERE YEAR(holiday_date) = $selectedYear AND MONTH(holiday_date) = $selectedMonth ORDER BY holiday_date ASC";
$holidayResult = mysqli_query($conn, $holidayQuery);

if ($holidayResult) {
    while ($row = mysqli_fetch_assoc($holidayResult)) {
        $monthHolidays[$row['holiday_date']] = $row;
    }
}

$totalHours = getTotalHours($conn);
$requiredHours = getRequiredHours($conn);
$dashboardStats = array(
    'total_hours' => $totalHours,
    'required_hours' => $requiredHours,
    'progress_percent' => min(100, ($requiredHours > 0 ? ($totalHours / $requiredHours) * 100 : 0)),
);

$completionDetails = getCompletionDateDetails($conn, $requiredHours);
$completionMode = (string) ($completionDetails['mode'] ?? 'unavailable');
$completionDate = (string) ($completionDetails['date'] ?? '');
$completionDateLabel = $completionDate !== '' ? date('F d, Y', strtotime($completionDate)) : '';
$completionDaysLeft = (int) ($completionDetails['days_left'] ?? 0);
$completionRemainingHours = (float) ($completionDetails['remaining_hours'] ?? max(0, $requiredHours - $totalHours));
$completionAverageHours = (float) ($completionDetails['average_hours_per_day'] ?? 0);

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - OJT Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .holiday-page {
            max-width: 980px;
            margin: 0 auto;
            padding: 14px 22px 28px;
        }

        .hero {
            text-align: center;
            margin-bottom: 16px;
        }

        .hero h1 {
            color: #2f2530;
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 800;
            margin-bottom: 8px;
        }

        .hero p {
            color: #6f6870;
            font-size: 16px;
        }

        .calendar-shell {
            background: #ffffff;
            border: 1px solid #f1ebef;
            border-radius: 28px;
            padding: 18px;
            box-shadow: 0 20px 38px rgba(41, 24, 36, 0.06);
        }

        .alert {
            padding: 12px 14px;
            border-radius: 12px;
            margin-bottom: 14px;
            font-size: 13px;
            border-left: 4px solid;
        }

        .alert.error {
            background: #fff0f4;
            border-left-color: #e56992;
            color: #b3466b;
        }

        .alert.success {
            background: #edf8ef;
            border-left-color: #4caf50;
            color: #2f7c41;
        }

        .toolbar {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            margin-bottom: 14px;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .pill {
            border: 1px solid var(--accent-border, var(--line));
            border-radius: 999px;
            background: #fff;
            color: #6f6270;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .pill.active {
            background: linear-gradient(90deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            border-color: transparent;
            color: #fff;
            box-shadow: 0 8px 20px rgba(237, 151, 182, 0.25);
        }

        .toolbar select,
        .toolbar input[type="text"],
        .toolbar input[type="date"] {
            border: 1px solid #ecdde3;
            border-radius: 999px;
            padding: 8px 14px;
            color: #5f5961;
            background: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 600;
        }

        .month-row {
            display: grid;
            grid-template-columns: 56px 1fr 56px;
            gap: 8px;
            align-items: center;
            margin: 8px 0 16px;
        }

        .nav-btn {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid #f3e3e9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: var(--pink-strong);
            text-decoration: none;
            font-size: 14px;
            background: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .nav-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 22px rgba(220, 154, 180, 0.24);
        }

        .month-title {
            text-align: center;
        }

        .month-title span {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.18em;
            color: var(--pink-strong);
            margin-bottom: 4px;
        }

        .month-title h2 {
            font-size: clamp(24px, 3.2vw, 36px);
            color: #2f2530;
            line-height: 1.1;
            font-weight: 800;
        }

        .calendar-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .calendar-meta h3 {
            color: #2f2530;
            font-size: 30px;
            font-weight: 800;
        }

        .days-pill {
            border: 1px solid var(--accent-border, var(--line));
            padding: 6px 12px;
            border-radius: 999px;
            color: var(--pink-strong);
            font-size: 12px;
            font-weight: 700;
        }

        .weekday-row,
        .day-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
        }

        .weekday {
            text-align: center;
            font-size: 12px;
            color: #b6a7b0;
            font-weight: 700;
            padding: 4px 0;
        }

        .day-cell {
            height: 64px;
            border-radius: 16px;
            border: 1px solid #f2edf0;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 3px;
            font-weight: 600;
            color: #6b626a;
            position: relative;
            overflow: hidden;
            padding: 6px 4px;
        }

        .day-num {
            font-size: 14px;
            line-height: 1;
            font-weight: 700;
        }

        .day-holiday-label {
            max-width: 100%;
            font-size: 10px;
            font-weight: 600;
            line-height: 1.1;
            text-align: center;
            color: var(--pink-strong);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding: 0 2px;
        }

        .day-cell.empty {
            border-color: transparent;
            background: transparent;
        }

        .day-cell.today {
            border-color: var(--pink);
            box-shadow: inset 0 0 0 1px var(--pink);
        }

        .day-cell.holiday {
            background: linear-gradient(180deg, var(--pink-soft), #ffffff);
            color: var(--pink-strong);
            border-color: var(--accent-border, var(--line));
        }

        .holiday-dot {
            position: absolute;
            right: 8px;
            bottom: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--pink-strong);
        }

        .month-holiday-list {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .completion-card {
            border: 1px solid #ecdde3;
            border-radius: 16px;
            padding: 12px 14px;
            margin-bottom: 14px;
            background: linear-gradient(140deg, #fff9fb, #ffffff);
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 12px;
            align-items: center;
        }

        .completion-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--pink-soft);
            color: var(--pink-strong);
            font-size: 16px;
            border: 1px solid var(--accent-border, var(--line));
        }

        .completion-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .completion-kicker {
            color: #a58f99;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .completion-date {
            color: #2f2530;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.2;
        }

        .completion-note {
            color: #6f6870;
            font-size: 12px;
            font-weight: 600;
        }

        .holiday-row {
            border: 1px solid #ecdde3;
            border-radius: 12px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: #5f5961;
            background: #fff9fb;
        }

        .holiday-row-name {
            color: var(--pink-strong);
            font-weight: 600;
            font-size: 13px;
        }

        .legend {
            margin: 18px auto 0;
            max-width: 520px;
            border: 1px solid #ece3e8;
            border-radius: 18px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            color: #5f5961;
            font-size: 13px;
            font-weight: 600;
            background: #fff;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .legend-swatch {
            width: 16px;
            height: 16px;
            border-radius: 6px;
            border: 1px solid #ead9e0;
        }

        .legend-swatch.holiday {
            background: var(--pink-soft);
            border-color: var(--accent-border, var(--line));
        }

        .legend-swatch.work {
            background: #fff;
        }

        .day-cell.completion-target {
            border-color: #6ea8ff;
            box-shadow: inset 0 0 0 1px #6ea8ff;
            background: linear-gradient(180deg, #eaf4ff, #ffffff);
            color: #2e5f9d;
        }

        .day-cell.completion-target .day-holiday-label {
            color: #2e5f9d;
        }

        .completion-dot {
            position: absolute;
            left: 8px;
            bottom: 8px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2e5f9d;
        }

        @media (max-width: 760px) {
            .holiday-page {
                padding: 10px 12px 20px;
            }

            .calendar-shell {
                border-radius: 22px;
                padding: 14px;
            }

            .toolbar {
                grid-template-columns: 1fr;
            }

            .month-row {
                grid-template-columns: 42px 1fr 42px;
            }

            .day-cell {
                height: 52px;
                border-radius: 12px;
                font-size: 13px;
            }

            .calendar-meta h3 {
                font-size: 24px;
            }

            .legend {
                flex-wrap: wrap;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <?php renderDashboardShell('calendar.php', $dashboardStats); ?>

    <div class="holiday-page">
        <div class="hero">
            <h1>Holiday Journey</h1>
            <p>Embrace your well-deserved breaks. Plan your restorative days with ease.</p>
        </div>

        <div class="calendar-shell">
            <?php if ($message): ?>
                <div class="alert success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="toolbar">
                <div class="toolbar-group">
                    <span class="pill active"><i class="fa-regular fa-calendar" aria-hidden="true"></i> Monthly</span>
                    <span class="pill"><i class="fa-regular fa-calendar-check" aria-hidden="true"></i> Yearly</span>
                </div>

                <form class="toolbar-group" method="GET">
                    <select name="year" aria-label="Select year">
                        <?php for ($y = $selectedYear - 3; $y <= $selectedYear + 3; $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y === $selectedYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="month" aria-label="Select month">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php echo $m === $selectedMonth ? 'selected' : ''; ?>><?php echo date('F', strtotime(sprintf('2024-%02d-01', $m))); ?></option>
                        <?php endfor; ?>
                    </select>
                    <button class="pill" type="submit">GO</button>
                </form>
            </div>

            <div class="completion-card">
                <span class="completion-icon">
                    <i class="fa-solid fa-flag-checkered" aria-hidden="true"></i>
                </span>
                <div class="completion-meta">
                    <?php if ($completionMode === 'completed' && $completionDate !== ''): ?>
                        <span class="completion-kicker">OJT Completed On</span>
                        <span class="completion-date"><?php echo htmlspecialchars($completionDateLabel); ?></span>
                        <span class="completion-note">Target reached. Great job!</span>
                    <?php elseif ($completionMode === 'projected' && $completionDate !== ''): ?>
                        <span class="completion-kicker">Projected OJT End Date</span>
                        <span class="completion-date"><?php echo htmlspecialchars($completionDateLabel); ?></span>
                        <span class="completion-note">~<?php echo $completionDaysLeft; ?> OJT day(s) left, <?php echo number_format($completionRemainingHours, 2); ?> hour(s) remaining at <?php echo number_format($completionAverageHours, 2); ?> hour(s)/day pace.</span>
                    <?php else: ?>
                        <span class="completion-kicker">Projected OJT End Date</span>
                        <span class="completion-date">Not available yet</span>
                        <span class="completion-note">Add more OJT logs so we can calculate your exact finish date.</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="month-row">
                <a class="nav-btn" href="calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" aria-label="Previous month">
                    <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
                </a>
                <div class="month-title">
                    <span>EXPLORE</span>
                    <h2><?php echo htmlspecialchars($monthTitle); ?></h2>
                </div>
                <a class="nav-btn" href="calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" aria-label="Next month">
                    <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="calendar-meta">
                <h3><?php echo date('F', $monthTs); ?></h3>
                <span class="days-pill"><?php echo $daysInMonth; ?> Days</span>
            </div>

            <div class="weekday-row">
                <div class="weekday">SUN</div>
                <div class="weekday">MON</div>
                <div class="weekday">TUE</div>
                <div class="weekday">WED</div>
                <div class="weekday">THU</div>
                <div class="weekday">FRI</div>
                <div class="weekday">SAT</div>
            </div>

            <div class="day-grid">
                <?php for ($blank = 0; $blank < $startWeekday; $blank++): ?>
                    <div class="day-cell empty"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php
                    $dateKey = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $day);
                    $isToday = $dateKey === $today;
                    $isHoliday = isset($monthHolidays[$dateKey]);

                    $classNames = 'day-cell';
                    if ($isToday) {
                        $classNames .= ' today';
                    }
                    if ($isHoliday) {
                        $classNames .= ' holiday';
                    }
                    if ($completionDate !== '' && $dateKey === $completionDate) {
                        $classNames .= ' completion-target';
                    }
                    ?>
                    <div class="<?php echo $classNames; ?>" title="<?php echo $isHoliday ? htmlspecialchars($monthHolidays[$dateKey]['holiday_name']) : ''; ?>">
                        <span class="day-num"><?php echo $day; ?></span>
                        <?php if ($isHoliday): ?>
                            <span class="day-holiday-label"><?php echo htmlspecialchars($monthHolidays[$dateKey]['holiday_name']); ?></span>
                        <?php endif; ?>
                        <?php if ($completionDate !== '' && $dateKey === $completionDate): ?>
                            <span class="day-holiday-label">OJT End</span>
                        <?php endif; ?>
                        <?php if ($isHoliday): ?>
                            <span class="holiday-dot" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?php if ($completionDate !== '' && $dateKey === $completionDate): ?>
                            <span class="completion-dot" aria-hidden="true"></span>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <?php if (!empty($monthHolidays)): ?>
                <div class="month-holiday-list">
                    <?php foreach ($monthHolidays as $holiday): ?>
                        <div class="holiday-row">
                            <span><?php echo htmlspecialchars(date('M d, Y', strtotime($holiday['holiday_date']))); ?></span>
                            <span class="holiday-row-name"><?php echo htmlspecialchars($holiday['holiday_name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="legend">
            <span class="legend-item"><span class="legend-swatch holiday"></span>Holiday</span>
            <span class="legend-item"><span class="legend-swatch work"></span>Working Day</span>
            <span class="legend-item"><span class="legend-swatch" style="background:#eaf4ff; border-color:#6ea8ff;"></span>OJT End Date</span>
            <span class="legend-item">Today</span>
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
