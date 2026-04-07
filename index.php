<?php
include 'config.php';
include 'functions.php';

$stats = getDashboardStats($conn, 500);
$total_hours = $stats['total_hours'];
$total_days = $stats['total_days'];
$days_left = $stats['days_left'];
$required_hours = $stats['required_hours'];
$remaining_hours = $stats['remaining_hours'];
$progress_percent = min(100, ($required_hours > 0 ? ($total_hours / $required_hours) * 100 : 0));

$recentLogs = mysqli_query(
    $conn,
    "SELECT date, time_in, time_out, hours FROM ojt_logs ORDER BY date DESC LIMIT 5"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OJT Tracker Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #ececf1;
            --panel: #f6f6fa;
            --card: #ffffff;
            --ink: #2f3240;
            --muted: #8d93a7;
            --pink: #e486ad;
            --pink-strong: #d86d9d;
            --pink-soft: #fff2f7;
            --line: #ececf3;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg);
            color: var(--ink);
            min-height: 100vh;
        }

        .app-layout {
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: 328px;
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            z-index: 1000;
            padding: 14px;
            transition: width 0.22s ease, transform 0.25s ease;
            overflow: hidden;
        }

        .sidebar-card {
            height: 100%;
            border-radius: 22px;
            background: var(--card);
            border: 1px solid #e4e6ef;
            box-shadow: 0 10px 20px rgba(68, 75, 102, 0.09);
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow: hidden;
        }

        .sidebar-head {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-badge {
            width: 36px;
            height: 36px;
            border-radius: 12px;
            background: var(--pink);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #2a2e3e;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 32px;
            line-height: 1;
            font-weight: 700;
            color: #2a2e3e;
            flex: 1;
        }

        .collapse-icon {
            font-size: 24px;
            color: #8f96ab;
            cursor: pointer;
            line-height: 1;
        }

        .profile {
            text-align: center;
            margin-top: 4px;
        }

        .avatar {
            width: 82px;
            height: 82px;
            border-radius: 50%;
            margin: 0 auto 8px;
            border: 4px solid #f7f1d9;
            position: relative;
            background: radial-gradient(circle at 38% 30%, #f4c5ba, #b8897d 70%);
        }

        .avatar::after {
            content: '';
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #45ca74;
            border: 2px solid #fff;
            position: absolute;
            right: 6px;
            bottom: 6px;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 4px;
        }

        .profile-level {
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        .search-box {
            background: #fef2f7;
            border: 1px solid #f5e2eb;
            border-radius: 16px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #a2a9bd;
            font-size: 14px;
            margin-top: 4px;
        }

        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 2px;
            flex: 1;
        }

        .side-link {
            text-decoration: none;
            color: #6f7890;
            border-radius: 16px;
            padding: 12px 14px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .side-link .icon {
            width: 22px;
            text-align: center;
            flex-shrink: 0;
            font-size: 17px;
        }

        .side-link:hover,
        .side-link.active {
            background: var(--pink);
            border-color: var(--pink);
            color: #2b2f40;
        }

        .side-divider {
            height: 1px;
            background: #ececf3;
            margin: 2px -14px;
        }

        .logout-link {
            text-decoration: none;
            color: #6f7890;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .usage-card {
            background: #fdf3f7;
            border: 1px solid #f2e3eb;
            border-radius: 16px;
            padding: 12px;
        }

        .usage-title {
            text-align: center;
            color: #8f95ab;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: 0.06em;
        }

        .usage-track {
            height: 7px;
            border-radius: 999px;
            background: #f0f0f4;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .usage-fill {
            width: 75%;
            height: 100%;
            background: linear-gradient(90deg, #ea80ad, #dd669e);
            border-radius: 999px;
        }

        .usage-text {
            text-align: center;
            color: #5f667f;
            font-size: 12px;
            font-weight: 600;
        }

        .main-area {
            margin-left: 328px;
            width: calc(100% - 328px);
            min-height: 100vh;
            background: var(--panel);
            transition: margin-left 0.22s ease, width 0.22s ease;
        }

        .topbar {
            height: 58px;
            border-bottom: 1px solid #e7e8f1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 16px;
            background: #fcfcff;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .menu-btn,
        .desktop-btn {
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

        .menu-btn {
            display: none;
        }

        .bar-title {
            color: #ff6b9d;
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
        }

        .main {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 24px 90px;
        }

        .hello {
            font-size: 34px;
            line-height: 1;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .hello-sub {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 14px;
        }

        .progress-card {
            background: #fff6fa;
            border: 1px solid #f3dce7;
            border-radius: 14px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .progress-head {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 8px;
        }

        .label {
            font-size: 10px;
            letter-spacing: 0.08em;
            font-weight: 700;
            color: var(--pink);
            text-transform: uppercase;
        }

        .score {
            font-size: 36px;
            font-weight: 700;
            line-height: 1;
        }

        .score small {
            font-size: 15px;
            color: #666f8b;
            font-weight: 600;
        }

        .complete {
            font-size: 12px;
            color: #747d98;
            font-weight: 600;
        }

        .bar {
            width: 100%;
            height: 10px;
            border-radius: 999px;
            background: #f1d8e4;
            overflow: hidden;
            margin-bottom: 8px;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #f07eaf, #dc5c99);
            border-radius: 999px;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .mini {
            font-size: 11px;
            color: #7d849b;
            font-weight: 600;
        }

        .mini strong {
            display: block;
            font-size: 13px;
            color: var(--ink);
            margin-top: 1px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }

        .stat {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 12px;
            text-align: center;
            padding: 10px 6px;
        }

        .stat .title {
            font-size: 10px;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.07em;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .stat .value {
            font-size: 21px;
            font-weight: 700;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .btn {
            border-radius: 12px;
            padding: 11px 8px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            border: 1px solid var(--line);
        }

        .btn-primary {
            background: linear-gradient(135deg, #ee88b2, #e1679f);
            color: white;
            border: none;
        }

        .btn-secondary {
            background: white;
            color: #dd6ea0;
        }

        .activity-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 9px;
        }

        .activity-head h2 {
            font-size: 20px;
        }

        .activity-head a {
            color: #df77a6;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .feed-item {
            background: white;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .feed-date {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .feed-time {
            font-size: 11px;
            color: #97a0b7;
        }

        .feed-hours {
            font-size: 14px;
            font-weight: 700;
            color: #dd6c9e;
        }

        .quote {
            text-align: center;
            color: #a8aeca;
            font-size: 11px;
            margin-top: 14px;
        }

        .empty {
            text-align: center;
            font-size: 12px;
            color: #95a0ba;
            padding: 20px;
        }

        .overlay {
            display: none;
        }

        body.sidebar-collapsed .sidebar {
            width: 112px;
        }

        body.sidebar-collapsed .main-area {
            margin-left: 112px;
            width: calc(100% - 112px);
        }

        body.sidebar-collapsed .brand-name,
        body.sidebar-collapsed .collapse-icon,
        body.sidebar-collapsed .profile,
        body.sidebar-collapsed .search-box,
        body.sidebar-collapsed .side-divider,
        body.sidebar-collapsed .usage-card,
        body.sidebar-collapsed .logout-link .label,
        body.sidebar-collapsed .side-link .label {
            display: none;
        }

        body.sidebar-collapsed .sidebar-head {
            justify-content: center;
        }

        body.sidebar-collapsed .side-link,
        body.sidebar-collapsed .logout-link {
            justify-content: center;
        }

        body.sidebar-hidden .sidebar {
            transform: translateX(-100%);
        }

        body.sidebar-hidden .main-area {
            margin-left: 0;
            width: 100%;
        }

        @media (max-width: 980px) {
            .menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .desktop-btn {
                display: none;
            }

            .sidebar {
                transform: translateX(-100%);
            }

            body.sidebar-open .sidebar {
                transform: translateX(0);
            }

            .main-area {
                margin-left: 0;
                width: 100%;
            }

            body.sidebar-collapsed .main-area {
                margin-left: 0;
                width: 100%;
            }

            .overlay {
                position: fixed;
                inset: 0;
                background: rgba(32, 34, 45, 0.35);
                z-index: 990;
                display: none;
            }

            body.sidebar-open .overlay {
                display: block;
            }
        }

        @media (max-width: 640px) {
            .sidebar {
                width: 300px;
                padding: 10px;
            }

            .topbar {
                padding: 0 10px;
            }

            .bar-title {
                font-size: 24px;
            }

            .main {
                max-width: 100%;
                padding: 14px 12px 70px;
            }

            .hello {
                font-size: 28px;
            }

            .score {
                font-size: 30px;
            }

            .stats,
            .actions {
                grid-template-columns: 1fr;
            }

            .activity-head h2 {
                font-size: 16px;
             }
         }
    </style>
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-head">
                    <span class="brand-badge">L</span>
                    <div class="brand-name">Lumina</div>
                    <span class="collapse-icon" onclick="toggleCollapse()">‹</span>
                </div>

                <div class="profile">
                    <div class="avatar"></div>
                    <div class="sidebar-title">Hi, Liz</div>
                    <div class="sidebar-sub">Admin Level</div>
                </div>

                <div class="search-box">
                    <span>🔍</span>
                    <span>Search...</span>
                </div>

                <nav class="side-nav">
                    <a href="index.php" class="side-link active"><span class="icon">⌘</span><span class="label">Dashboard</span></a>
                    <a href="logs.php" class="side-link"><span class="icon">📄</span><span class="label">Logs</span></a>
                    <a href="holidays.php" class="side-link"><span class="icon">📅</span><span class="label">Calendar</span></a>
                    <a href="upload.php" class="side-link"><span class="icon">⤴</span><span class="label">Uploads</span></a>
                    <a href="log_entry.php" class="side-link"><span class="icon">⚙</span><span class="label">Settings</span></a>
                </nav>

                <div class="side-divider"></div>

                <div class="sidebar-bottom">
                    <a href="index.php" class="logout-link"><span class="icon">⇤</span><span class="label">Logout</span></a>
                    <div class="usage-card">
                        <div class="usage-title">STORAGE USAGE</div>
                        <div class="usage-track"><div class="usage-fill"></div></div>
                        <div class="usage-text">75% used of 2GB</div>
                    </div>
                </div>
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <button class="menu-btn" type="button" onclick="toggleSidebar()">☰</button>
                <button class="desktop-btn" type="button" onclick="toggleCollapse()" title="Collapse or expand sidebar">◧</button>
                <button class="desktop-btn" type="button" onclick="toggleHideSidebar()" title="Hide or show sidebar">↔</button>
                <div class="bar-title">OJT Tracker Navigation</div>
            </header>

            <main class="main">
                <h1 class="hello">Hi, Liz</h1>
                <p class="hello-sub">Here is a look at your OJT progress today.</p>

                <section class="progress-card">
                    <div class="label">Overall Progress</div>
                    <div class="progress-head">
                        <div class="score"><?php echo number_format($total_hours, 0); ?> <small>/ <?php echo (int)$required_hours; ?> hrs</small></div>
                        <div class="complete"><?php echo number_format($progress_percent, 0); ?>% Complete</div>
                    </div>
                    <div class="bar">
                        <div class="bar-fill" style="width: <?php echo $progress_percent; ?>%"></div>
                    </div>
                    <div class="mini-grid">
                        <div class="mini">Remaining<strong><?php echo number_format($remaining_hours, 0); ?> Hours</strong></div>
                        <div class="mini">Est. Days Left<strong><?php echo (int)$days_left; ?> Days</strong></div>
                    </div>
                </section>

                <section class="stats">
                    <div class="stat">
                        <div class="title">Total Hrs</div>
                        <div class="value"><?php echo number_format($total_hours, 1); ?></div>
                    </div>
                    <div class="stat">
                        <div class="title">Remaining</div>
                        <div class="value"><?php echo number_format($remaining_hours, 1); ?></div>
                    </div>
                    <div class="stat">
                        <div class="title">Days Rendered</div>
                        <div class="value"><?php echo (int)$total_days; ?></div>
                    </div>
                </section>

                <section class="actions">
                    <a href="log_entry.php" class="btn btn-primary">Add Log</a>
                    <a href="upload.php" class="btn btn-secondary">Upload Excel</a>
                </section>

                <section>
                    <div class="activity-head">
                        <h2>Recent Activity</h2>
                        <a href="logs.php">View All</a>
                    </div>

                    <?php if ($recentLogs && mysqli_num_rows($recentLogs) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($recentLogs)): ?>
                            <article class="feed-item">
                                <div>
                                    <div class="feed-date"><?php echo date('F d, Y', strtotime($row['date'])); ?></div>
                                    <div class="feed-time"><?php echo date('h:i A', strtotime($row['time_in'])); ?> to <?php echo date('h:i A', strtotime($row['time_out'])); ?></div>
                                </div>
                                <div class="feed-hours"><?php echo number_format((float)$row['hours'], 1); ?></div>
                            </article>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty">No logs yet. Start adding your first entry.</div>
                    <?php endif; ?>
                </section>

                <p class="quote">Every small step counts toward your big goal.</p>
            </main>
        </div>
    </div>

    <div class="overlay" onclick="closeSidebar()"></div>

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
</body>
</html>
