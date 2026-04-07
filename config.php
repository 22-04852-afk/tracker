<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'ojt_tracker';

// Create connection
$conn = mysqli_connect($host, $username, $password);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if (!mysqli_query($conn, $sql)) {
    die("Error creating database: " . mysqli_error($conn));
}

// Select database
mysqli_select_db($conn, $database);

// Set charset
mysqli_set_charset($conn, "utf8");

// Ensure users table exists before auth routing checks.
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$publicPages = array('login.php', 'signup.php', 'logout.php', 'setup.php');

$hasRegisteredUser = false;
$usersCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total_users FROM users");
if ($usersCountResult && ($usersCountRow = mysqli_fetch_assoc($usersCountResult))) {
    $hasRegisteredUser = ((int) ($usersCountRow['total_users'] ?? 0)) > 0;
}

if ($currentPage === 'login.php' && !$hasRegisteredUser) {
    header('Location: signup.php');
    exit;
}

if (!in_array($currentPage, $publicPages, true) && empty($_SESSION['user_id'])) {
    header('Location: ' . ($hasRegisteredUser ? 'login.php' : 'signup.php'));
    exit;
}

$hoursColumn = mysqli_query($conn, "SHOW COLUMNS FROM ojt_logs LIKE 'hours'");
if ($hoursColumn && mysqli_num_rows($hoursColumn) > 0) {
    $hoursInfo = mysqli_fetch_assoc($hoursColumn);
    $hoursType = strtolower($hoursInfo['Type'] ?? '');

    if (strpos($hoursType, 'int') === false) {
        mysqli_query($conn, "UPDATE ojt_logs SET hours = ROUND(hours, 0)");
        mysqli_query($conn, "ALTER TABLE ojt_logs MODIFY hours INT NOT NULL");
    }
}

if (!function_exists('renderDashboardShell')) {
    function renderDashboardShell($activePage = 'index.php', array $stats = array()) {
        global $conn;

        $totalHours = isset($stats['total_hours']) ? (float)$stats['total_hours'] : 0;
        $requiredHours = isset($stats['required_hours']) ? (float)$stats['required_hours'] : 500;
        $progressPercent = isset($stats['progress_percent']) ? (float)$stats['progress_percent'] : 0;
        $themeKey = 'cotton_candy';
        $themeTokens = array(
            'bg' => '#f4f6fb',
            'panel' => '#faf7fd',
            'card' => '#ffffff',
            'ink' => '#2f3240',
            'muted' => '#8d93a7',
            'line' => '#ececf3',
            'accent' => '#e486ad',
            'accent_strong' => '#d86d9d',
            'accent_soft' => '#fff2f7',
            'accent_border' => '#f1bfd2',
            'accent_gradient_start' => '#ea7fab',
            'accent_gradient_end' => '#dd669e',
        );

        if (function_exists('getThemeKey') && function_exists('getThemePreset')) {
            $themeKey = getThemeKey($conn, 'cotton_candy');
            $themePreset = getThemePreset($themeKey);
            if (isset($themePreset['tokens']) && is_array($themePreset['tokens'])) {
                $themeTokens = array_merge($themeTokens, $themePreset['tokens']);
            }
        }

        $displayName = trim((string) ($_SESSION['user_name'] ?? ''));
        if ($displayName === '') {
            $displayName = 'Intern';
        }

        static $stylesPrinted = false;

        if (!$stylesPrinted) {
            $stylesPrinted = true;
            echo <<<'CSS'
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

                body {
                    font-family: 'Poppins', sans-serif;
                    background: var(--bg);
                    color: var(--ink);
                    min-height: 100vh;
                    padding: 0;
                }

                .app-layout {
                    min-height: 100vh;
                    display: flex;
                    background: var(--bg);
                    color: var(--ink);
                }

                .sidebar {
                    width: 328px;
                    position: fixed;
                    left: 0;
                    top: 0;
                    bottom: 0;
                    z-index: 1000;
                    padding: 14px;
                    box-sizing: border-box;
                    transition: width 0.22s ease, transform 0.25s ease;
                    overflow: hidden;
                }

                .sidebar-card {
                    height: 100%;
                    box-sizing: border-box;
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
                    width: 34px;
                    height: 34px;
                    border-radius: 50%;
                    color: #8f96ab;
                    cursor: pointer;
                    line-height: 1;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .collapse-icon i {
                    font-size: 17px;
                    transition: transform 0.2s ease;
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

                .sidebar-title {
                    font-size: 32px;
                    font-weight: 700;
                    line-height: 1;
                    margin-bottom: 4px;
                }

                .sidebar-sub {
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

                .search-box i {
                    font-size: 13px;
                }

                .side-nav {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    margin-top: 2px;
                    flex: 1;
                    align-items: stretch;
                }

                .side-link {
                    width: calc(100% - 6px);
                    margin: 0 3px;
                    box-sizing: border-box;
                    text-decoration: none;
                    color: #6f7890;
                    border-radius: 16px;
                    padding: 10px 12px;
                    font-size: 13px;
                    font-weight: 700;
                    line-height: 1.2;
                    transition: all 0.2s ease;
                    border: 1px solid transparent;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                }

                .side-link .icon {
                    width: 22px;
                    min-width: 22px;
                    text-align: center;
                    flex-shrink: 0;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .side-link .icon i {
                    font-size: 14px;
                    display: inline-block;
                    visibility: visible;
                    opacity: 1;
                    color: inherit;
                }

                .side-link .icon .icon-glyph {
                    font-size: 14px;
                    line-height: 1;
                    display: inline-block;
                    color: inherit;
                    font-weight: 700;
                }

                .logout-link .icon {
                    width: 22px;
                    min-width: 22px;
                    text-align: center;
                    flex-shrink: 0;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .logout-link .icon i {
                    font-size: 14px;
                    display: inline-block;
                    visibility: visible;
                    opacity: 1;
                    color: inherit;
                }

                .side-link:hover,
                .side-link.active {
                    background: #f4d7e3;
                    border-color: #f1bfd2;
                    color: #e35f95;
                    box-shadow: none;
                }

                .side-divider {
                    height: 1px;
                    background: #ececf3;
                    margin: 2px -14px;
                }

                .sidebar-bottom {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }

                .logout-link {
                    width: calc(100% - 6px);
                    margin: 0 3px;
                    box-sizing: border-box;
                    text-decoration: none;
                    color: #6f7890;
                    border-radius: 16px;
                    padding: 10px 12px;
                    font-size: 13px;
                    font-weight: 700;
                    line-height: 1.2;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                }

                .main-area {
                    margin-left: 340px;
                    width: calc(100% - 340px);
                    min-height: 100vh;
                    background: var(--panel);
                    transition: margin-left 0.22s ease, width 0.22s ease;
                }

                .topbar {
                    min-height: 96px;
                    border-bottom: 1px solid #e7e8f1;
                    display: flex;
                    align-items: center;
                    padding: 12px 16px;
                    background: #fcfcff;
                    position: sticky;
                    top: 0;
                    z-index: 900;
                }

                .topbar-content {
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    width: 100%;
                    flex-wrap: wrap;
                }

                .header-greet {
                    min-width: 190px;
                }

                .header-greet h2 {
                    font-size: 38px;
                    line-height: 1;
                    color: #2a2f41;
                    margin-bottom: 4px;
                }

                .header-greet p {
                    color: #6d748d;
                    font-size: 13px;
                    font-weight: 600;
                }

                .header-search {
                    flex: 1;
                    min-width: 260px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    background: #f4f4f8;
                    border: 1px solid #e9e9f1;
                    border-radius: 999px;
                    padding: 10px 14px;
                    color: #8f96aa;
                    font-size: 16px;
                    font-weight: 500;
                }

                .header-search i {
                    font-size: 14px;
                    color: #9ca4ba;
                }

                .header-chip {
                    min-width: 220px;
                    background: #f7f2ee;
                    border: 1px solid #ece3dc;
                    border-radius: 20px;
                    padding: 8px 12px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    gap: 10px;
                }

                .header-chip .meta {
                    font-size: 11px;
                    color: #7f8598;
                    font-weight: 700;
                    line-height: 1.2;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                }

                .header-chip .meta strong {
                    display: block;
                    font-size: 16px;
                    color: #313649;
                    letter-spacing: normal;
                    text-transform: none;
                }

                .header-chip .pct {
                    width: 48px;
                    height: 48px;
                    border-radius: 50%;
                    border: 3px solid #e7b579;
                    color: #d08f4b;
                    font-size: 12px;
                    font-weight: 700;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    background: #fff9f2;
                }

                .header-actions {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    margin-left: auto;
                    flex-wrap: wrap;
                }

                .header-btn {
                    border-radius: 999px;
                    padding: 10px 18px;
                    font-size: 14px;
                    font-weight: 700;
                    text-decoration: none;
                    border: 1px solid #f0c8da;
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                    white-space: nowrap;
                }

                .header-btn i {
                    font-size: 13px;
                }

                .header-btn.primary {
                    background: linear-gradient(135deg, #ea7fab, #dd669e);
                    color: #fff;
                    border-color: transparent;
                }

                .header-btn.secondary {
                    background: #fff9fc;
                    color: #df77a6;
                }

                .header-icon-btn {
                    width: 38px;
                    height: 38px;
                    border-radius: 50%;
                    border: 1px solid #e3e5ee;
                    background: #fff;
                    color: #8a92a9;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 16px;
                }

                .header-icon-btn i {
                    font-size: 15px;
                }

                .header-avatar {
                    width: 42px;
                    height: 42px;
                    border-radius: 50%;
                    border: 2px solid #f3ead1;
                    background: radial-gradient(circle at 38% 30%, #f4c5ba, #b8897d 70%);
                    display: inline-block;
                }

                .header-caret {
                    color: #8a92a9;
                    font-size: 14px;
                    line-height: 1;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .menu-btn {
                    width: 38px;
                    height: 38px;
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

                .menu-btn i {
                    font-size: 17px;
                }

                .main {
                    max-width: 980px;
                    margin: 0 auto;
                    padding: 24px 24px 90px;
                }

                .overlay {
                    display: none;
                }

                body.sidebar-collapsed .sidebar {
                    width: 112px;
                }

                body.sidebar-collapsed .main-area {
                    margin-left: 124px;
                    width: calc(100% - 124px);
                }

                body.sidebar-collapsed .brand-name,
                body.sidebar-collapsed .profile,
                body.sidebar-collapsed .search-box,
                body.sidebar-collapsed .side-divider,
                body.sidebar-collapsed .logout-link .label,
                body.sidebar-collapsed .side-link .label {
                    display: none;
                }

                body.sidebar-collapsed .sidebar-head {
                    justify-content: center;
                }

                body.sidebar-collapsed .collapse-icon i {
                    transform: rotate(180deg);
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
                    .main {
                        padding: 16px 16px 70px;
                    }

                    .topbar-content {
                        gap: 12px;
                    }

                    .header-greet h2 {
                        font-size: 32px;
                    }

                    .header-search,
                    .header-chip,
                    .header-actions {
                        min-width: 100%;
                    }

                    .header-actions {
                        justify-content: flex-start;
                    }
                }
            </style>
CSS;

            echo '<style>
                :root {
                    --bg: ' . htmlspecialchars($themeTokens['bg']) . ';
                    --panel: ' . htmlspecialchars($themeTokens['panel']) . ';
                    --card: ' . htmlspecialchars($themeTokens['card']) . ';
                    --ink: ' . htmlspecialchars($themeTokens['ink']) . ';
                    --muted: ' . htmlspecialchars($themeTokens['muted']) . ';
                    --line: ' . htmlspecialchars($themeTokens['line']) . ';
                    --pink: ' . htmlspecialchars($themeTokens['accent']) . ';
                    --pink-strong: ' . htmlspecialchars($themeTokens['accent_strong']) . ';
                    --pink-soft: ' . htmlspecialchars($themeTokens['accent_soft']) . ';
                    --accent-border: ' . htmlspecialchars($themeTokens['accent_border']) . ';
                    --accent-grad-start: ' . htmlspecialchars($themeTokens['accent_gradient_start']) . ';
                    --accent-grad-end: ' . htmlspecialchars($themeTokens['accent_gradient_end']) . ';
                }

                .brand-badge {
                    background: linear-gradient(135deg, var(--accent-grad-start), var(--accent-grad-end));
                    color: #fff;
                }

                .search-box {
                    background: var(--pink-soft);
                    border-color: var(--accent-border);
                }

                .side-link:hover,
                .side-link.active {
                    background: var(--pink-soft);
                    border-color: var(--accent-border);
                    color: var(--pink-strong);
                }

                .header-btn.primary {
                    background: linear-gradient(135deg, var(--accent-grad-start), var(--accent-grad-end));
                }

                .header-btn.secondary {
                    background: var(--pink-soft);
                    border-color: var(--accent-border);
                    color: var(--pink-strong);
                }
            </style>';
        }

        $navItems = array(
            'index.php' => array('icon' => 'glyph:▦', 'label' => 'Dashboard'),
            'logs.php' => array('icon' => 'glyph:▤', 'label' => 'Logs'),
            'add_logs.php' => array('icon' => 'glyph:+', 'label' => 'Add Logs'),
            'calendar.php' => array('icon' => 'glyph:▣', 'label' => 'Calendar'),
            'uploads.php' => array('icon' => 'glyph:⤴', 'label' => 'Uploads'),
            'settings.php' => array('icon' => 'glyph:⚙', 'label' => 'Settings'),
        );

        echo '<div class="app-layout">';
        echo '<aside class="sidebar"><div class="sidebar-card">';
        echo '<div class="sidebar-head"><span class="brand-badge">L</span><div class="brand-name">Lumina</div><span class="collapse-icon" onclick="toggleCollapse()"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></span></div>';
        echo '<div class="profile"><div class="avatar"></div><div class="sidebar-title">Hi, ' . htmlspecialchars($displayName) . '</div><div class="sidebar-sub">Admin Level</div></div>';
        echo '<div class="search-box"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Search...</span></div>';
        echo '<nav class="side-nav">';

        foreach ($navItems as $page => $item) {
            $isActive = ($page === $activePage) ? ' active' : '';
            if (strpos($item['icon'], 'glyph:') === 0) {
                $glyph = substr($item['icon'], 6);
                $iconHtml = '<span class="icon-glyph" aria-hidden="true">' . $glyph . '</span>';
            } else {
                $iconHtml = '<i class="' . $item['icon'] . '" aria-hidden="true"></i>';
            }

            echo '<a href="' . $page . '" class="side-link' . $isActive . '"><span class="icon">' . $iconHtml . '</span><span class="label">' . $item['label'] . '</span></a>';
        }

        echo '</nav><div class="side-divider"></div>';
        echo '<div class="sidebar-bottom"><a href="logout.php" class="logout-link"><span class="icon"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></span><span class="label">Logout</span></a></div>';
        echo '</div></aside>';
        echo '<div class="main-area"><header class="topbar"><div class="topbar-content"><button class="menu-btn" type="button" onclick="toggleSidebar()"><i class="fa-solid fa-bars" aria-hidden="true"></i></button><div class="header-greet"><h2>Hi, ' . htmlspecialchars($displayName) . '</h2><p>Your OJT Progress</p></div><div class="header-search"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> <span>Search your logs...</span></div><div class="header-chip"><div class="meta">Completed<strong>' . number_format($totalHours, 0) . ' / ' . (int)$requiredHours . ' hrs</strong></div><span class="pct">' . number_format($progressPercent, 0) . '%</span></div><div class="header-actions"><a href="add_logs.php" class="header-btn primary"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add Log</a><a href="logs.php" class="header-btn secondary"><i class="fa-regular fa-file-lines" aria-hidden="true"></i> Logs</a><a href="uploads.php" class="header-btn secondary"><i class="fa-solid fa-arrow-up-from-bracket" aria-hidden="true"></i> Uploads</a><span class="header-icon-btn"><i class="fa-regular fa-bell" aria-hidden="true"></i></span><span class="header-avatar"></span><span class="header-caret"><i class="fa-solid fa-chevron-down" aria-hidden="true"></i></span></div></div></header><main class="main">';
        echo '<div class="overlay" onclick="closeSidebar()"></div>';
    }

    function closeDashboardShell() {
        echo '</main></div></div>';
    }
}

?>
