<?php
include 'config.php';
include 'functions.php';

$stats = getDashboardStats($conn);
$total_hours = $stats['total_hours'];
$total_days = $stats['total_days'];
$days_left = $stats['days_left'];
$required_hours = $stats['required_hours'];
$remaining_hours = $stats['remaining_hours'];
$progress_percent = min(100, ($required_hours > 0 ? ($total_hours / $required_hours) * 100 : 0));
$displayName = trim((string) ($_SESSION['user_name'] ?? ''));
if ($displayName === '') {
    $displayName = 'Intern';
}

$showDashboardIntro = !empty($_SESSION['show_dashboard_intro']);
if ($showDashboardIntro) {
    unset($_SESSION['show_dashboard_intro']);
}

$dashboardStats = array(
    'total_hours' => $total_hours,
    'required_hours' => $required_hours,
    'progress_percent' => $progress_percent,
);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
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
            background-color: var(--bg, #ececf1);
            color: var(--ink, #2f3240);
            min-height: 100vh;
            padding: 0;
        }

        body.intro-active {
            overflow: hidden;
        }

        body.intro-active .app-layout {
            opacity: 0;
            filter: blur(12px);
            transform: scale(1.02);
            pointer-events: none;
        }

        body.intro-active .dashboard-intro {
            opacity: 1;
            pointer-events: auto;
        }

        .dashboard-intro {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            background:
                radial-gradient(circle at top, var(--pink-soft, #fff2f7), var(--panel, #f6f6fa) 48%, var(--bg, #ececf1) 100%),
                linear-gradient(180deg, var(--pink-soft, #fff2f7) 0%, var(--panel, #f6f6fa) 100%);
                transition: opacity 0.35s ease;
            opacity: 1;
            pointer-events: auto;
        }

        .intro-card {
            width: min(760px, 100%);
            min-height: 440px;
            border-radius: 34px;
            background: rgba(255, 255, 255, 0.66);
            border: 1px solid var(--accent-border, #f1bfd2);
            box-shadow: 0 24px 70px rgba(68, 75, 102, 0.14);
            backdrop-filter: blur(12px);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 32px 24px;
        }

        .intro-card::before,
        .intro-card::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(232, 123, 166, 0.12);
            filter: blur(2px);
            animation: floatGlow 8s ease-in-out infinite;
        }

        .intro-card::before {
            width: 170px;
            height: 170px;
            left: -36px;
            top: -24px;
        }

        .intro-card::after {
            width: 220px;
            height: 220px;
            right: -60px;
            bottom: -70px;
            animation-delay: -2.5s;
        }

        .intro-content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 540px;
        }

        .intro-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid var(--accent-border, #f1bfd2);
            color: var(--pink-strong, #d86d9d);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 20px;
                animation: fadeUp 0.35s ease both;
        }

        .intro-title {
            font-size: clamp(32px, 5vw, 58px);
            line-height: 0.98;
            font-weight: 800;
            color: #26222e;
            margin-bottom: 12px;
                animation: fadeUp 0.45s ease 0.05s both;
        }

        .intro-title span {
            color: var(--pink-strong, #d86d9d);
        }

        .intro-subtitle {
            color: #7f8597;
            font-size: 16px;
            line-height: 1.6;
            max-width: 460px;
            margin: 0 auto 24px;
                animation: fadeUp 0.55s ease 0.1s both;
        }

        .intro-visual {
            margin: 0 auto 24px;
            width: min(560px, 100%);
            height: 150px;
            position: relative;
            border-radius: 26px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.55), rgba(255, 255, 255, 0.24));
            border: 1px solid var(--accent-border, #f1bfd2);
            overflow: hidden;
                animation: fadeUp 0.65s ease 0.12s both;
        }

        .intro-wave {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent 0%, var(--pink-soft, #fff2f7) 50%, transparent 100%);
            transform: translateX(-100%);
            animation: sweep 3.8s linear infinite;
        }

        .intro-line {
            position: absolute;
            left: 7%;
            right: 7%;
            height: 10px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--pink-soft, #fff2f7), var(--pink, #e486ad), var(--accent-grad-end, #dd669e));
            opacity: 0.8;
            box-shadow: 0 0 24px rgba(233, 111, 155, 0.18);
        }

        .intro-line.one {
            top: 28px;
            animation: pulseLine 2.4s ease-in-out infinite;
        }

        .intro-line.two {
            top: 62px;
            width: 72%;
            left: 14%;
            animation: pulseLine 2.8s ease-in-out infinite;
            animation-delay: 0.3s;
        }

        .intro-line.three {
            bottom: 28px;
            width: 54%;
            left: 23%;
            animation: pulseLine 3.1s ease-in-out infinite;
            animation-delay: 0.6s;
        }

        .intro-dots {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
        }

        .intro-dots span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--pink, #e486ad);
            box-shadow: 0 0 18px rgba(239, 124, 168, 0.35);
            animation: bounce 1.5s ease-in-out infinite;
        }

        .intro-dots span:nth-child(2) { animation-delay: 0.12s; }
        .intro-dots span:nth-child(3) { animation-delay: 0.24s; }
        .intro-dots span:nth-child(4) { animation-delay: 0.36s; }

        .intro-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #8a8fa0;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
                animation: fadeUp 0.6s ease 0.16s both;
        }

        .intro-progress {
            width: min(320px, 100%);
            height: 8px;
            border-radius: 999px;
            background: #ece8ef;
            overflow: hidden;
            margin: 14px auto 0;
            box-shadow: inset 0 1px 2px rgba(68, 75, 102, 0.06);
        }

        .intro-progress span {
            display: block;
            width: 100%;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--accent-grad-start, #ea7fab), var(--accent-grad-end, #dd669e));
            animation: introLoad 5s linear forwards;
            transform-origin: left;
        }

        .intro-fade-out {
            opacity: 0;
            pointer-events: none;
        }

        @keyframes introLoad {
            from { transform: scaleX(0); }
            to { transform: scaleX(1); }
        }

        @keyframes sweep {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        @keyframes pulseLine {
            0%, 100% { transform: scaleX(0.92); opacity: 0.7; }
            50% { transform: scaleX(1); opacity: 1; }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); opacity: 0.55; }
            50% { transform: translateY(-14px); opacity: 1; }
        }

        @keyframes floatGlow {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            50% { transform: translate3d(16px, 10px, 0) scale(1.08); }
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 640px) {
            .intro-card {
                min-height: 420px;
                border-radius: 26px;
                padding: 24px 18px;
            }

            .intro-subtitle {
                font-size: 14px;
            }

            .intro-visual {
                height: 128px;
            }

            .intro-footer {
                font-size: 12px;
            }
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

        .side-nav {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 2px;
            flex: 1;
        }

        .side-link {
            width: 100%;
            text-decoration: none;
            color: #000;
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
            text-align: center;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .side-link .icon i {
            font-size: 14px;
        }

        .side-link:hover,
        .side-link.active {
            background: var(--pink-soft);
            border-color: var(--accent-border, var(--line));
            color: var(--pink-strong);
        }

        .side-divider {
            height: 1px;
            background: #ececf3;
            margin: 2px -14px;
        }

        .logout-link {
            width: 100%;
            text-decoration: none;
            color: #000;
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

        .usage-card {
            background: var(--pink-soft);
            border: 1px solid var(--accent-border, var(--line));
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
            background: linear-gradient(90deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
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
            background: linear-gradient(135deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: #fff;
            border-color: transparent;
        }

        .header-btn.secondary {
            background: var(--pink-soft);
            color: var(--pink-strong);
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

        .menu-btn {
            display: none;
        }

        .main {
            max-width: 980px;
            margin: 0 auto;
            padding: 24px 24px 90px;
        }

        .progress-card {
            background: var(--pink-soft);
            border: 1px solid var(--accent-border, var(--line));
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

        .progress-label {
            font-size: 13px;
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
            background: var(--pink-soft);
            overflow: hidden;
            margin-bottom: 8px;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
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
            background: linear-gradient(135deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: white;
            border: none;
        }

        .btn-secondary {
            background: white;
            color: var(--pink-strong);
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
            color: var(--pink-strong);
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
            color: var(--pink-strong);
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
        body.sidebar-collapsed .profile,
        body.sidebar-collapsed .side-divider,
        body.sidebar-collapsed .usage-card,
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
            .sidebar {
                width: 300px;
                padding: 10px;
            }

            .topbar {
                padding: 0 10px;
                min-height: 76px;
            }

            .header-greet {
                min-width: auto;
                width: 100%;
            }

            .header-greet h2 {
                font-size: 30px;
            }

            .header-chip {
                min-width: 100%;
            }

            .header-actions {
                margin-left: 0;
                width: 100%;
                justify-content: flex-start;
            }

            .header-btn {
                font-size: 14px;
            }

            .main {
                max-width: 100%;
                padding: 14px 12px 70px;
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
<body class="<?php echo $showDashboardIntro ? 'intro-active' : ''; ?>">
    <?php if ($showDashboardIntro): ?>
    <div class="dashboard-intro" id="dashboardIntro" aria-hidden="false">
        <div class="intro-card">
            <div class="intro-content">
                <div class="intro-kicker"><i class="fa-solid fa-sparkles" aria-hidden="true"></i>Welcome Sequence</div>
                <div class="intro-title">Hello, <span><?php echo htmlspecialchars($displayName); ?></span>!<br>Welcome to your logs.</div>
                <div class="intro-subtitle">Your dashboard is loading. Take a moment while your training progress comes into view.</div>
                <div class="intro-visual" aria-hidden="true">
                    <div class="intro-wave"></div>
                    <div class="intro-line one"></div>
                    <div class="intro-line two"></div>
                    <div class="intro-line three"></div>
                    <div class="intro-dots"><span></span><span></span><span></span><span></span></div>
                </div>
                <div class="intro-footer"><i class="fa-regular fa-circle-play" aria-hidden="true"></i>Auto-opening dashboard in 5 seconds</div>
                <div class="intro-progress"><span></span></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php renderDashboardShell('index.php', $dashboardStats); ?>
                <section class="progress-card">
                    <div class="progress-label">Overall Progress</div>
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
                        <div class="value"><?php echo number_format($total_hours, 0); ?></div>
                    </div>
                    <div class="stat">
                        <div class="title">Remaining</div>
                        <div class="value"><?php echo number_format($remaining_hours, 0); ?></div>
                    </div>
                    <div class="stat">
                        <div class="title">Days Rendered</div>
                        <div class="value"><?php echo (int)$total_days; ?></div>
                    </div>
                </section>

                <section class="actions">
                    <a href="logs.php" class="btn btn-primary">Logs</a>
                    <a href="uploads.php" class="btn btn-secondary">Uploads</a>
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

        (function startIntroSequence() {
            const intro = document.getElementById('dashboardIntro');

            if (!intro) {
                document.body.classList.remove('intro-active');
                return;
            }

            const dismissIntro = function() {
                intro.classList.add('intro-fade-out');
                document.body.classList.remove('intro-active');

                window.setTimeout(function() {
                    if (intro && intro.parentNode) {
                        intro.remove();
                    }
                }, 350);
            };

            window.setTimeout(dismissIntro, 5000);

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                return;
            }

            document.addEventListener('DOMContentLoaded', function() {
                window.setTimeout(dismissIntro, 5000);
            }, { once: true });
        })();
    </script>

    <?php closeDashboardShell(); ?>
</body>
</html>
