<?php
include 'config.php';
include 'functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_avatar'])) {
        $currentUserId = (int) ($_SESSION['user_id'] ?? 0);

        if ($currentUserId <= 0) {
            $error = 'You must be logged in to upload a profile picture.';
        } elseif (!isset($_FILES['avatar'])) {
            $error = 'Please choose an image file first.';
        } else {
            $avatarFile = $_FILES['avatar'];
            $uploadError = (int) ($avatarFile['error'] ?? UPLOAD_ERR_NO_FILE);

            if ($uploadError !== UPLOAD_ERR_OK) {
                $error = 'Unable to upload image. Please try again.';
            } elseif ((int) ($avatarFile['size'] ?? 0) > (5 * 1024 * 1024)) {
                $error = 'Image is too large. Maximum file size is 5MB.';
            } else {
                $tmpPath = (string) ($avatarFile['tmp_name'] ?? '');
                $originalName = (string) ($avatarFile['name'] ?? '');
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                $allowedExtensions = array('jpg', 'jpeg', 'png', 'webp', 'gif');
                if (!in_array($extension, $allowedExtensions, true)) {
                    $error = 'Invalid image format. Please upload JPG, PNG, WEBP, or GIF.';
                } else {
                    $mimeType = '';
                    if (function_exists('finfo_open')) {
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $mimeType = (string) finfo_file($finfo, $tmpPath);
                            finfo_close($finfo);
                        }
                    }

                    $allowedMime = array('image/jpeg', 'image/png', 'image/webp', 'image/gif');
                    if ($mimeType !== '' && !in_array($mimeType, $allowedMime, true)) {
                        $error = 'Invalid image file. Please upload a valid image.';
                    } else {
                        $avatarDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
                        if (!is_dir($avatarDir) && !mkdir($avatarDir, 0755, true)) {
                            $error = 'Avatar upload directory is not writable.';
                        } else {
                            $oldAvatarPath = '';
                            $oldResult = mysqli_query($conn, "SELECT avatar_path FROM users WHERE id = $currentUserId LIMIT 1");
                            if ($oldResult && ($oldRow = mysqli_fetch_assoc($oldResult))) {
                                $oldAvatarPath = trim((string) ($oldRow['avatar_path'] ?? ''));
                            }

                            if ($extension === 'jpeg') {
                                $extension = 'jpg';
                            }

                            $randomSuffix = function_exists('random_bytes') ? bin2hex(random_bytes(6)) : uniqid('', true);
                            $storedName = 'avatar_' . $currentUserId . '_' . $randomSuffix . '.' . $extension;
                            $targetPath = $avatarDir . DIRECTORY_SEPARATOR . $storedName;
                            $relativePath = 'uploads/avatars/' . $storedName;

                            if (!move_uploaded_file($tmpPath, $targetPath)) {
                                $error = 'Failed to save uploaded image. Please try again.';
                            } else {
                                $escapedPath = mysqli_real_escape_string($conn, $relativePath);

                                if (!mysqli_query($conn, "UPDATE users SET avatar_path = '$escapedPath' WHERE id = $currentUserId LIMIT 1")) {
                                    @unlink($targetPath);
                                    $error = 'Unable to update profile picture right now.';
                                } else {
                                    $_SESSION['user_avatar'] = $relativePath;
                                    $message = 'Profile picture updated successfully.';

                                    if ($oldAvatarPath !== '' && $oldAvatarPath !== $relativePath && strpos($oldAvatarPath, 'uploads/avatars/') === 0) {
                                        $oldAbsolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $oldAvatarPath);
                                        if (is_file($oldAbsolutePath)) {
                                            @unlink($oldAbsolutePath);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    } elseif (isset($_POST['required_hours'])) {
        $newRequiredHours = (int) ($_POST['required_hours'] ?? 0);

        if ($newRequiredHours < 1) {
            $error = 'Required hours must be greater than 0.';
        } elseif ($newRequiredHours > 5000) {
            $error = 'Required hours is too high. Please enter 5000 or below.';
        } else {
            if (updateRequiredHours($conn, $newRequiredHours)) {
                $message = 'Required hours updated successfully.';
            } else {
                $error = 'Unable to update required hours right now.';
            }
        }
    } elseif (isset($_POST['theme_key'])) {
        $selectedTheme = trim((string) ($_POST['theme_key'] ?? ''));

        if ($selectedTheme === '') {
            $error = 'Please choose a theme.';
        } elseif (updateThemeKey($conn, $selectedTheme)) {
            $message = 'Theme updated successfully.';
        } else {
            $error = 'Unable to update theme right now.';
        }
    }
}

$requiredHours = getRequiredHours($conn);
$themePresets = getThemePresets();
$currentThemeKey = getThemeKey($conn);
$stats = getDashboardStats($conn, $requiredHours);
$total_hours = $stats['total_hours'];
$currentAvatarPath = trim((string) ($_SESSION['user_avatar'] ?? ''));

if ($currentAvatarPath === '') {
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    if ($currentUserId > 0) {
        $avatarQuery = mysqli_query($conn, "SELECT avatar_path FROM users WHERE id = $currentUserId LIMIT 1");
        if ($avatarQuery && ($avatarRow = mysqli_fetch_assoc($avatarQuery))) {
            $currentAvatarPath = trim((string) ($avatarRow['avatar_path'] ?? ''));
            $_SESSION['user_avatar'] = $currentAvatarPath;
        }
    }
}

$dashboardStats = array(
    'total_hours' => $total_hours,
    'required_hours' => $requiredHours,
    'progress_percent' => min(100, ($requiredHours > 0 ? ($total_hours / $requiredHours) * 100 : 0)),
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - OJT Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
            margin-top: 8px;
        }

        .settings-card {
            background: #fff;
            border: 1px solid #e8e9f2;
            border-radius: 20px;
            padding: 18px;
            box-shadow: 0 10px 20px rgba(68, 75, 102, 0.06);
        }

        .settings-card h3 {
            font-size: 18px;
            color: #2f3240;
            margin-bottom: 8px;
        }

        .settings-card p {
            color: #6d748d;
            font-size: 14px;
            line-height: 1.6;
        }

        .settings-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: var(--pink-soft);
            border: 1px solid var(--accent-border, var(--line));
            color: var(--pink-strong);
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .settings-status {
            margin-bottom: 10px;
            padding: 12px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }

        .settings-status.success {
            background: #e9f8ed;
            color: #2f7f45;
            border: 1px solid #c7e8d1;
        }

        .settings-status.error {
            background: #ffedf0;
            color: #bf3d63;
            border: 1px solid #f3ced8;
        }

        .required-hours-form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .required-hours-form label {
            display: block;
            color: #56607a;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .required-hours-form input {
            width: 180px;
            border: 1px solid #e1e4f0;
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 16px;
            font-weight: 700;
            color: #2f3240;
            font-family: 'Poppins', sans-serif;
        }

        .required-hours-form button {
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }

        .required-hours-helper {
            margin-top: 10px;
            font-size: 12px;
            color: #6d748d;
        }

        .theme-form {
            margin-top: 6px;
        }

        .theme-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .theme-option {
            display: block;
            border: 1px solid #e2e6f2;
            border-radius: 14px;
            padding: 10px;
            cursor: pointer;
            background: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .theme-option input {
            display: none;
        }

        .theme-option.active {
            border-color: var(--pink-strong);
            box-shadow: 0 0 0 3px rgba(217, 115, 164, 0.15);
        }

        .theme-preview {
            height: 36px;
            border-radius: 10px;
            margin-bottom: 8px;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .theme-name {
            font-size: 13px;
            font-weight: 700;
            color: #2f3240;
            line-height: 1.25;
            margin-bottom: 2px;
        }

        .theme-description {
            font-size: 11px;
            color: #6d748d;
            line-height: 1.35;
        }

        .theme-save {
            margin-top: 12px;
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }

        .account-panel {
            margin-top: 12px;
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .account-avatar {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            border: 3px solid #f1e6c9;
            background: radial-gradient(circle at 38% 30%, #f4c5ba, #b8897d 70%);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            flex-shrink: 0;
        }

        .avatar-form {
            flex: 1;
            min-width: 210px;
        }

        .avatar-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #56607a;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .avatar-input {
            display: block;
            width: 100%;
            font-size: 13px;
            color: #3c4357;
            margin-bottom: 8px;
        }

        .avatar-save-btn {
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            background: linear-gradient(135deg, var(--accent-grad-start, var(--pink)), var(--accent-grad-end, var(--pink-strong)));
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
        }

        .avatar-hint {
            margin-top: 8px;
            font-size: 11px;
            color: #6d748d;
        }

        @media (max-width: 740px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }

            .theme-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php renderDashboardShell('settings.php', $dashboardStats); ?>
    <?php if ($message): ?>
        <div class="settings-status success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="settings-status error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <section class="settings-grid">
        <div class="settings-card">
            <div class="settings-badge"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Progress Target</div>
            <h3>Required Hours</h3>
            <p>Set how many total internship hours you need to complete.</p>

            <form method="POST" class="required-hours-form">
                <div>
                    <label for="required_hours">Hours Needed</label>
                    <input type="number" id="required_hours" name="required_hours" min="1" max="5000" step="1" value="<?php echo (int) $requiredHours; ?>" required>
                </div>
                <button type="submit">Save Hours</button>
            </form>
            <div class="required-hours-helper">Current requirement: <?php echo (int) $requiredHours; ?> hours.</div>
        </div>

        <div class="settings-card">
            <div class="settings-badge"><i class="fa-solid fa-user-gear" aria-hidden="true"></i> Account</div>
            <h3>Profile</h3>
            <p>Manage the dashboard owner details and account identity shown in the sidebar.</p>

            <div class="account-panel">
                <div class="account-avatar"<?php echo $currentAvatarPath !== '' ? ' style="background-image:url(\'' . htmlspecialchars($currentAvatarPath, ENT_QUOTES) . '\');"' : ''; ?>></div>

                <form method="POST" enctype="multipart/form-data" class="avatar-form">
                    <label class="avatar-label" for="avatar">
                        <i class="fa-solid fa-image" aria-hidden="true"></i>
                        Profile Picture
                    </label>
                    <input type="file" id="avatar" name="avatar" class="avatar-input" accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif" required>
                    <button type="submit" name="upload_avatar" value="1" class="avatar-save-btn">Upload Picture</button>
                    <div class="avatar-hint">Allowed: JPG, PNG, WEBP, GIF. Max size: 5MB.</div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-badge"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Preferences</div>
            <h3>Theme Color</h3>
            <p>Pick your preferred cute color theme. This is saved in Settings and stays the same even after logout.</p>

            <form method="POST" class="theme-form">
                <div class="theme-grid">
                    <?php foreach ($themePresets as $themeKey => $themePreset): ?>
                        <?php
                            $tokens = $themePreset['tokens'];
                            $isActive = ($themeKey === $currentThemeKey);
                        ?>
                        <label class="theme-option<?php echo $isActive ? ' active' : ''; ?>">
                            <input type="radio" name="theme_key" value="<?php echo htmlspecialchars($themeKey); ?>" <?php echo $isActive ? 'checked' : ''; ?>>
                            <div class="theme-preview" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($tokens['accent_gradient_start']); ?>, <?php echo htmlspecialchars($tokens['accent_gradient_end']); ?>);"></div>
                            <div class="theme-name"><?php echo htmlspecialchars($themePreset['name']); ?></div>
                            <div class="theme-description"><?php echo htmlspecialchars($themePreset['description']); ?></div>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="theme-save">Save Theme</button>
            </form>
        </div>

        <div class="settings-card">
            <div class="settings-badge"><i class="fa-solid fa-database" aria-hidden="true"></i> Data</div>
            <h3>Tracker Records</h3>
            <p>Review the stored logs, uploaded files, and imported sheet data from one place.</p>
        </div>
    </section>

    <script>
        (function enableThemeCardHighlight() {
            const themeInputs = document.querySelectorAll('.theme-form input[name="theme_key"]');

            function refreshThemeHighlight(selectedInput) {
                themeInputs.forEach(function(input) {
                    const card = input.closest('.theme-option');
                    if (!card) {
                        return;
                    }

                    card.classList.toggle('active', input === selectedInput && input.checked);
                });
            }

            themeInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    refreshThemeHighlight(input);
                });
            });

            const initiallyChecked = document.querySelector('.theme-form input[name="theme_key"]:checked');
            if (initiallyChecked) {
                refreshThemeHighlight(initiallyChecked);
            }
        })();
    </script>
<?php closeDashboardShell(); ?>
</body>
</html>
