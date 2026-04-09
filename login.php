<?php
include 'config.php';
include 'functions.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = authenticateUser($conn, $username, $password);

    if (!empty($result['success'])) {
        initializeUserSession($result['user']);
        $_SESSION['show_dashboard_intro'] = 1;
        header('Location: index.php');
        exit;
    }

    $error = $result['message'] ?? 'Unable to sign in.';
}

$themeKey = getThemeKey($conn);
$themePreset = getThemePreset($themeKey);
$themeTokens = $themePreset['tokens'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OJT Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        :root {
            --bg: <?php echo htmlspecialchars($themeTokens['bg']); ?>;
            --panel: <?php echo htmlspecialchars($themeTokens['panel']); ?>;
            --card: <?php echo htmlspecialchars($themeTokens['card']); ?>;
            --ink: <?php echo htmlspecialchars($themeTokens['ink']); ?>;
            --muted: <?php echo htmlspecialchars($themeTokens['muted']); ?>;
            --line: <?php echo htmlspecialchars($themeTokens['line']); ?>;
            --accent: <?php echo htmlspecialchars($themeTokens['accent']); ?>;
            --accent-strong: <?php echo htmlspecialchars($themeTokens['accent_strong']); ?>;
            --accent-soft: <?php echo htmlspecialchars($themeTokens['accent_soft']); ?>;
            --accent-border: <?php echo htmlspecialchars($themeTokens['accent_border']); ?>;
            --accent-grad-start: <?php echo htmlspecialchars($themeTokens['accent_gradient_start']); ?>;
            --accent-grad-end: <?php echo htmlspecialchars($themeTokens['accent_gradient_end']); ?>;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: radial-gradient(circle at top, var(--accent-soft), var(--panel) 45%, var(--bg) 100%);
            color: var(--ink);
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .auth-shell {
            width: min(1040px, 100%);
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            gap: 20px;
            align-items: stretch;
        }

        .hero-panel,
        .form-panel {
            border-radius: 30px;
            box-shadow: 0 24px 70px rgba(64, 46, 61, 0.12);
            overflow: hidden;
        }

        .hero-panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(255, 247, 250, 0.92));
            border: 1px solid var(--accent-border);
            padding: 34px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 24px;
            min-height: 640px;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            color: var(--accent-strong);
            border: 1px solid var(--accent-border);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero-panel h1 {
            margin: 18px 0 14px;
            font-size: clamp(36px, 5vw, 64px);
            line-height: 0.96;
            color: #26222f;
            font-weight: 800;
        }

        .hero-panel h1 span {
            color: var(--accent-strong);
        }

        .hero-panel p {
            margin: 0;
            max-width: 520px;
            font-size: 16px;
            line-height: 1.65;
            color: #7f8597;
        }

        .hero-visual {
            margin-top: 26px;
            border-radius: 26px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.25));
            border: 1px solid var(--accent-border);
            padding: 24px;
            position: relative;
            overflow: hidden;
            min-height: 290px;
        }

        .hero-visual::before,
        .hero-visual::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(232, 123, 166, 0.14);
            animation: floatGlow 8s ease-in-out infinite;
        }

        .hero-visual::before {
            width: 150px;
            height: 150px;
            right: -20px;
            top: -20px;
        }

        .hero-visual::after {
            width: 220px;
            height: 220px;
            left: -40px;
            bottom: -70px;
            animation-delay: -2.5s;
        }

        .hero-pulse {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, var(--accent-soft), transparent);
            transform: translateX(-100%);
            animation: sweep 4s linear infinite;
        }

        .hero-line {
            position: relative;
            z-index: 1;
            height: 12px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--accent-soft), var(--accent), var(--accent-grad-end));
            box-shadow: 0 0 24px rgba(233, 111, 155, 0.18);
            margin-bottom: 18px;
            animation: pulseLine 2.5s ease-in-out infinite;
        }

        .hero-line.small {
            width: 68%;
        }

        .hero-line.medium {
            width: 82%;
            animation-delay: 0.3s;
        }

        .hero-line.large {
            width: 92%;
            animation-delay: 0.6s;
        }

        .hero-dots {
            position: absolute;
            right: 26px;
            bottom: 28px;
            display: flex;
            gap: 12px;
        }

        .hero-dots span {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--accent);
            box-shadow: 0 0 18px rgba(239, 124, 168, 0.35);
            animation: bounce 1.5s ease-in-out infinite;
        }

        .hero-dots span:nth-child(2) { animation-delay: 0.12s; }
        .hero-dots span:nth-child(3) { animation-delay: 0.24s; }
        .hero-dots span:nth-child(4) { animation-delay: 0.36s; }

        .form-panel {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(236, 227, 236, 0.9);
            padding: 48px 56px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h2 {
            font-size: 32px;
            line-height: 1.1;
            margin: 0 0 8px;
            color: #26222f;
            max-width: 340px;
        }

        .form-panel p {
            margin: 0 0 24px;
            color: #7f8597;
            font-size: 14px;
            line-height: 1.6;
            max-width: 340px;
        }

        .form-panel form,
        .form-panel .alert,
        .form-panel .form-links,
        .form-panel .intro-caption {
            width: 100%;
            max-width: 340px;
        }

        .alert {
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }

        .alert.error {
            background: var(--accent-soft);
            color: var(--accent-strong);
            border: 1px solid var(--accent-border);
        }

        .field {
            margin-bottom: 18px;
        }

        .password-field {
            position: relative;
        }

        .password-input-wrap {
            position: relative;
        }

        .password-input-wrap input {
            padding-right: 48px;
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #9aa1b5;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            padding: 0;
        }

        .password-toggle:hover {
            color: var(--accent-strong);
            background: var(--accent-soft);
        }

        .field label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #61697d;
        }

        .field input {
            width: 100%;
            border: 1px solid #e2e5ef;
            border-radius: 14px;
            padding: 14px 16px;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            color: #27222f;
            background: #fff;
            outline: none;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(228, 134, 173, 0.12);
        }

        .primary-btn {
            width: 100%;
            border: none;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--accent-grad-start), var(--accent-grad-end));
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 800;
            padding: 13px 18px;
            cursor: pointer;
            margin-top: 8px;
        }

        .primary-btn:hover {
            filter: brightness(0.98);
        }

        .form-links {
            margin-top: 16px;
            font-size: 13px;
            color: #7f8597;
            text-align: center;
        }

        .form-links a {
            color: var(--accent-strong);
            text-decoration: none;
            font-weight: 800;
        }

        .form-links a:hover {
            text-decoration: underline;
        }

        .intro-caption {
            margin-top: 14px;
            color: #8a8fa0;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            text-align: center;
        }

        @keyframes introLoad { from { transform: scaleX(0); } to { transform: scaleX(1); } }
        @keyframes sweep { 0% { transform: translateX(-100%); } 100% { transform: translateX(100%); } }
        @keyframes pulseLine { 0%, 100% { transform: scaleX(0.92); opacity: 0.7; } 50% { transform: scaleX(1); opacity: 1; } }
        @keyframes bounce { 0%, 100% { transform: translateY(0); opacity: 0.55; } 50% { transform: translateY(-14px); opacity: 1; } }
        @keyframes floatGlow { 0%, 100% { transform: translate3d(0, 0, 0) scale(1); } 50% { transform: translate3d(16px, 10px, 0) scale(1.08); } }

        @media (max-width: 920px) {
            .auth-shell {
                grid-template-columns: 1fr;
            }

            .hero-panel {
                min-height: 340px;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 12px;
            }

            .hero-panel,
            .form-panel {
                padding: 22px;
                border-radius: 24px;
            }

            .hero-panel h1 {
                font-size: 34px;
            }

            .form-panel h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-shell">
        <section class="hero-panel">
            <div>
                <div class="hero-kicker"><i class="fa-solid fa-lock" aria-hidden="true"></i> Secure Access</div>
                <h1>Hello, intern.<br><span>Welcome back.</span></h1>
                <p>Sign in to continue your OJT tracker session. Every logout clears access, so you always come back through a protected login.</p>
            </div>
            <div>
                <div class="hero-visual" aria-hidden="true">
                    <div class="hero-pulse"></div>
                    <div class="hero-line large"></div>
                    <div class="hero-line medium"></div>
                    <div class="hero-line small"></div>
                    <div class="hero-dots"><span></span><span></span><span></span><span></span></div>
                </div>
            </div>
        </section>

        <section class="form-panel">
            <h2>Login</h2>
            <p>Enter your account details to open the dashboard.</p>

            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="field password-field">
                    <label for="password">Password</label>
                    <div class="password-input-wrap">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="password-toggle" aria-label="Show password" data-target="password">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="primary-btn">Sign In</button>
            </form>

            <div class="form-links">
                No account yet? <a href="signup.php">Create one</a>
            </div>
            <div class="intro-caption">Protected session required</div>
        </section>
    </div>

    <script>
        document.querySelectorAll('.password-toggle').forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');
                const isHidden = input.type === 'password';

                input.type = isHidden ? 'text' : 'password';
                icon.className = isHidden ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
                this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            });
        });
    </script>
</body>
</html>
