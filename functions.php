<?php

// Compute hours between time_in and time_out
function computeHours($time_in, $time_out) {
    $in = strtotime($time_in);
    $out = strtotime($time_out);
    
    if ($out > $in) {
        $hours = ($out - $in) / 3600;
        // Subtract 1 hour for lunch break
        $netHours = $hours - 1;

        if ($netHours <= 0) {
            return 0;
        }

        return round($netHours, 2);
    }
    
    return 0;
}

// Parse spreadsheet dates from Excel serials or human-readable formats
function parseSpreadsheetDate($rawValue, $formattedValue = '') {
    if (is_numeric($rawValue) && class_exists('PhpOffice\\PhpSpreadsheet\\Shared\\Date')) {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$rawValue)->format('Y-m-d');
    }

    $candidates = array();
    $formattedValue = trim((string)$formattedValue);
    $rawString = trim((string)$rawValue);

    if ($formattedValue !== '') {
        $candidates[] = $formattedValue;
    }

    if ($rawString !== '' && $rawString !== $formattedValue) {
        $candidates[] = $rawString;
    }

    $formats = array('F j, Y', 'F j Y', 'F d, Y', 'F d Y', 'M j, Y', 'M j Y', 'm/d/Y', 'n/j/Y', 'Y-m-d');

    foreach ($candidates as $candidate) {
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $candidate);
            if ($dateTime instanceof DateTime) {
                $errors = DateTime::getLastErrors();
                if (empty($errors['warning_count']) && empty($errors['error_count'])) {
                    return $dateTime->format('Y-m-d');
                }
            }
        }

        $timestamp = strtotime($candidate);
        if ($timestamp) {
            return date('Y-m-d', $timestamp);
        }
    }

    return '';
}

// Parse spreadsheet times from Excel serials/fractions or human-readable formats
function parseSpreadsheetTime($rawValue, $formattedValue = '') {
    $candidates = array();

    if ($formattedValue !== null && $formattedValue !== '') {
        $candidates[] = $formattedValue;
    }

    if ($rawValue !== null && $rawValue !== '' && $rawValue !== $formattedValue) {
        $candidates[] = $rawValue;
    }

    foreach ($candidates as $candidate) {
        if (is_numeric($candidate)) {
            $numeric = (float) $candidate;

            // Excel stores time as day fractions and datetime as serial days.
            if ($numeric > 1) {
                $numeric = $numeric - floor($numeric);
            }

            if ($numeric >= 0 && $numeric < 1) {
                $seconds = (int) round($numeric * 86400);
                $seconds = $seconds % 86400;
                return gmdate('H:i:s', $seconds);
            }
        }

        $candidateText = trim((string) $candidate);
        if ($candidateText === '') {
            continue;
        }

        $formats = array('g:i A', 'g:iA', 'h:i A', 'h:iA', 'H:i:s', 'H:i');
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $candidateText);
            if ($dateTime instanceof DateTime) {
                $errors = DateTime::getLastErrors();
                if (empty($errors['warning_count']) && empty($errors['error_count'])) {
                    return $dateTime->format('H:i:s');
                }
            }
        }

        $timestamp = strtotime($candidateText);
        if ($timestamp !== false) {
            return date('H:i:s', $timestamp);
        }
    }

    return '';
}

// Get total hours from all logs
function getTotalHours($conn) {
    $query = "SELECT SUM(hours) as total FROM ojt_logs";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (float)($row['total'] ?? 0);
}

// Get total OJT days (excluding holidays)
function getTotalOJTDays($conn) {
    $query = "
        SELECT COUNT(DISTINCT date) as days
        FROM ojt_logs
        WHERE date NOT IN (SELECT holiday_date FROM holidays)
    ";
    
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)($row['days'] ?? 0);
}

function ensureAppSettingsTable($conn) {
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $query = "CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    mysqli_query($conn, $query);
    $initialized = true;
}

function getRequiredHours($conn, $fallback = 500) {
    ensureAppSettingsTable($conn);

    $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_key = 'required_hours' LIMIT 1");

    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $value = (int)($row['setting_value'] ?? 0);
        if ($value > 0) {
            return $value;
        }
    }

    $fallback = max(1, (int)$fallback);
    mysqli_query($conn, "INSERT INTO app_settings (setting_key, setting_value) VALUES ('required_hours', '$fallback') ON DUPLICATE KEY UPDATE setting_value = setting_value");

    return $fallback;
}

function updateRequiredHours($conn, $hours) {
    ensureAppSettingsTable($conn);

    $hours = max(1, (int)$hours);
    $value = (string)$hours;

    return mysqli_query($conn, "INSERT INTO app_settings (setting_key, setting_value) VALUES ('required_hours', '$value') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
}

function getThemePresets() {
    return array(
        'cotton_candy' => array(
            'name' => 'Cotton Candy Pink',
            'description' => 'Soft pink gradient with warm cute tones.',
            'tokens' => array(
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
            ),
        ),
        'sky_milk' => array(
            'name' => 'Sky Milk Blue',
            'description' => 'Blue to milky white gradient look.',
            'tokens' => array(
                'bg' => '#eef5ff',
                'panel' => '#f7fbff',
                'card' => '#ffffff',
                'ink' => '#25324a',
                'muted' => '#7386a2',
                'line' => '#dce8fb',
                'accent' => '#6ea8ff',
                'accent_strong' => '#4b86e8',
                'accent_soft' => '#e9f2ff',
                'accent_border' => '#c8dbfa',
                'accent_gradient_start' => '#78b4ff',
                'accent_gradient_end' => '#d9ebff',
            ),
        ),
        'lilac_cloud' => array(
            'name' => 'Lilac Cloud',
            'description' => 'Light purple pastel palette.',
            'tokens' => array(
                'bg' => '#f3f0ff',
                'panel' => '#faf8ff',
                'card' => '#ffffff',
                'ink' => '#342f4d',
                'muted' => '#8e88a8',
                'line' => '#e6dff6',
                'accent' => '#b091ea',
                'accent_strong' => '#956fdd',
                'accent_soft' => '#f2ebff',
                'accent_border' => '#d7c8f4',
                'accent_gradient_start' => '#b79bf0',
                'accent_gradient_end' => '#d9c9fb',
            ),
        ),
        'peach_bloom' => array(
            'name' => 'Peach Bloom',
            'description' => 'Cute peach and blush tones.',
            'tokens' => array(
                'bg' => '#fff4f0',
                'panel' => '#fff8f5',
                'card' => '#ffffff',
                'ink' => '#3f2f2f',
                'muted' => '#9c7f7f',
                'line' => '#f3dfd8',
                'accent' => '#f39a8f',
                'accent_strong' => '#e97a8b',
                'accent_soft' => '#fff0ec',
                'accent_border' => '#f5cfc8',
                'accent_gradient_start' => '#f4a88d',
                'accent_gradient_end' => '#f08cb7',
            ),
        ),
        'mint_dream' => array(
            'name' => 'Mint Dream',
            'description' => 'Minty pastel with soft green-blue accent.',
            'tokens' => array(
                'bg' => '#eefaf7',
                'panel' => '#f6fffd',
                'card' => '#ffffff',
                'ink' => '#25403d',
                'muted' => '#6d8f8a',
                'line' => '#d7eee8',
                'accent' => '#67c9b4',
                'accent_strong' => '#43b39c',
                'accent_soft' => '#e7f9f4',
                'accent_border' => '#bfe9de',
                'accent_gradient_start' => '#6ed7bd',
                'accent_gradient_end' => '#9fd9ff',
            ),
        ),
    );
}

function getThemePreset($themeKey) {
    $presets = getThemePresets();
    $themeKey = trim((string) $themeKey);

    if (isset($presets[$themeKey])) {
        return $presets[$themeKey];
    }

    return $presets['cotton_candy'];
}

function getThemeKey($conn, $fallback = 'cotton_candy') {
    ensureAppSettingsTable($conn);

    $presets = getThemePresets();
    $fallback = isset($presets[$fallback]) ? $fallback : 'cotton_candy';

    $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_key = 'theme_key' LIMIT 1");
    if ($result && ($row = mysqli_fetch_assoc($result))) {
        $value = trim((string) ($row['setting_value'] ?? ''));
        if (isset($presets[$value])) {
            return $value;
        }
    }

    $escapedFallback = mysqli_real_escape_string($conn, $fallback);
    mysqli_query($conn, "INSERT INTO app_settings (setting_key, setting_value) VALUES ('theme_key', '$escapedFallback') ON DUPLICATE KEY UPDATE setting_value = setting_value");

    return $fallback;
}

function updateThemeKey($conn, $themeKey) {
    ensureAppSettingsTable($conn);

    $presets = getThemePresets();
    $themeKey = trim((string) $themeKey);
    if (!isset($presets[$themeKey])) {
        return false;
    }

    $escapedTheme = mysqli_real_escape_string($conn, $themeKey);
    return mysqli_query($conn, "INSERT INTO app_settings (setting_key, setting_value) VALUES ('theme_key', '$escapedTheme') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
}

function ensureUsersTable($conn) {
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $query = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        avatar_path VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    mysqli_query($conn, $query);

    $avatarColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'avatar_path'");
    if ($avatarColumn && mysqli_num_rows($avatarColumn) === 0) {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) NULL AFTER password_hash");
    }

    $initialized = true;
}

function findUserByUsername($conn, $username) {
    ensureUsersTable($conn);

    $statement = mysqli_prepare($conn, "SELECT id, full_name, username, password_hash, avatar_path FROM users WHERE username = ? LIMIT 1");
    if (!$statement) {
        return null;
    }

    mysqli_stmt_bind_param($statement, 's', $username);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($statement);

    return $user ?: null;
}

function registerUser($conn, $fullName, $username, $password) {
    ensureUsersTable($conn);

    $fullName = trim((string) $fullName);
    $username = trim((string) $username);
    $password = (string) $password;

    if ($fullName === '' || $username === '' || $password === '') {
        return array('success' => false, 'message' => 'Please fill in all fields.');
    }

    if (findUserByUsername($conn, $username)) {
        return array('success' => false, 'message' => 'Username already exists.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $statement = mysqli_prepare($conn, "INSERT INTO users (full_name, username, password_hash) VALUES (?, ?, ?)");

    if (!$statement) {
        return array('success' => false, 'message' => 'Unable to create account right now.');
    }

    mysqli_stmt_bind_param($statement, 'sss', $fullName, $username, $passwordHash);
    $success = mysqli_stmt_execute($statement);
    $insertId = mysqli_insert_id($conn);
    mysqli_stmt_close($statement);

    if (!$success) {
        return array('success' => false, 'message' => 'Unable to create account right now.');
    }

    return array(
        'success' => true,
        'user' => array(
            'id' => $insertId,
            'full_name' => $fullName,
            'username' => $username,
        ),
    );
}

function authenticateUser($conn, $username, $password) {
    ensureUsersTable($conn);

    $username = trim((string) $username);
    $password = (string) $password;

    if ($username === '' || $password === '') {
        return array('success' => false, 'message' => 'Please enter your username and password.');
    }

    $user = findUserByUsername($conn, $username);
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return array('success' => false, 'message' => 'Invalid username or password.');
    }

    return array(
        'success' => true,
        'user' => array(
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'username' => $user['username'],
            'avatar_path' => (string) ($user['avatar_path'] ?? ''),
        ),
    );
}

function initializeUserSession(array $user) {
    $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
    $_SESSION['user_name'] = (string) ($user['full_name'] ?? '');
    $_SESSION['user_username'] = (string) ($user['username'] ?? '');
    $_SESSION['user_avatar'] = (string) ($user['avatar_path'] ?? '');
}

// Get total OJT days (excluding holidays and weekends)
function getTotalOJTDaysExcludeWeekends($conn) {
    $query = "
        SELECT COUNT(DISTINCT date) as days
        FROM ojt_logs
        WHERE 
            date NOT IN (SELECT holiday_date FROM holidays)
            AND DAYOFWEEK(date) NOT IN (1, 7)
    ";
    
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return (int)($row['days'] ?? 0);
}

// Calculate days left based on average hours per day
function calculateDaysLeft($conn, $required_hours = 500) {
    $total_hours = getTotalHours($conn);
    $total_days = getTotalOJTDays($conn);
    
    if ($total_days == 0) {
        return 0;
    }
    
    $avg_per_day = $total_hours / $total_days;
    $remaining_hours = $required_hours - $total_hours;
    
    if ($avg_per_day > 0 && $remaining_hours > 0) {
        return ceil($remaining_hours / $avg_per_day);
    }
    
    return $remaining_hours > 0 ? 0 : 0;
}

function isWorkingOJTDate($conn, $date) {
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return false;
    }

    $weekday = (int) date('w', $timestamp);
    if ($weekday === 0 || $weekday === 6) {
        return false;
    }

    $year = (int) date('Y', $timestamp);
    seedPhilippineHolidays($conn, $year);

    $escapedDate = mysqli_real_escape_string($conn, date('Y-m-d', $timestamp));
    $holidayResult = mysqli_query($conn, "SELECT id FROM holidays WHERE holiday_date = '$escapedDate' LIMIT 1");

    return !($holidayResult && mysqli_num_rows($holidayResult) > 0);
}

function getProjectedCompletionDate($conn, $workingDaysNeeded, $startDate = null) {
    $workingDaysNeeded = max(0, (int) $workingDaysNeeded);
    if ($workingDaysNeeded <= 0) {
        return '';
    }

    $cursor = $startDate ? strtotime($startDate) : strtotime(date('Y-m-d'));
    if ($cursor === false) {
        $cursor = strtotime(date('Y-m-d'));
    }

    $countedDays = 0;
    while ($countedDays < $workingDaysNeeded) {
        $cursor = strtotime('+1 day', $cursor);
        if ($cursor === false) {
            return '';
        }

        $dateKey = date('Y-m-d', $cursor);
        if (isWorkingOJTDate($conn, $dateKey)) {
            $countedDays++;
        }
    }

    return date('Y-m-d', $cursor);
}

function getCompletionDateDetails($conn, $required_hours = 500) {
    $required_hours = max(1, (int) $required_hours);
    $total_hours = getTotalHours($conn);
    $remaining_hours = max(0, $required_hours - $total_hours);
    $total_days = getTotalOJTDays($conn);

    $details = array(
        'mode' => 'unavailable',
        'date' => '',
        'days_left' => 0,
        'remaining_hours' => $remaining_hours,
        'average_hours_per_day' => 0,
    );

    if ($remaining_hours <= 0) {
        $logResult = mysqli_query($conn, "SELECT date, hours FROM ojt_logs ORDER BY date ASC");
        $runningHours = 0;
        $completionDate = '';

        if ($logResult) {
            while ($row = mysqli_fetch_assoc($logResult)) {
                $runningHours += (float) ($row['hours'] ?? 0);
                if ($runningHours >= $required_hours) {
                    $completionDate = (string) ($row['date'] ?? '');
                    break;
                }
            }
        }

        if ($completionDate === '') {
            $latestResult = mysqli_query($conn, "SELECT MAX(date) AS latest_date FROM ojt_logs");
            if ($latestResult && ($latestRow = mysqli_fetch_assoc($latestResult))) {
                $completionDate = (string) ($latestRow['latest_date'] ?? '');
            }
        }

        $details['mode'] = 'completed';
        $details['date'] = $completionDate;

        return $details;
    }

    if ($total_days <= 0 || $total_hours <= 0) {
        return $details;
    }

    $averagePerDay = $total_hours / $total_days;
    if ($averagePerDay <= 0) {
        return $details;
    }

    $daysLeft = (int) ceil($remaining_hours / $averagePerDay);
    $projectedDate = getProjectedCompletionDate($conn, $daysLeft);

    $details['mode'] = $projectedDate !== '' ? 'projected' : 'unavailable';
    $details['date'] = $projectedDate;
    $details['days_left'] = $daysLeft;
    $details['average_hours_per_day'] = round($averagePerDay, 2);

    return $details;
}

function getEasterSundayTimestamp($year) {
    if (function_exists('easter_date')) {
        return easter_date($year);
    }

    $a = $year % 19;
    $b = (int) floor($year / 100);
    $c = $year % 100;
    $d = (int) floor($b / 4);
    $e = $b % 4;
    $f = (int) floor(($b + 8) / 25);
    $g = (int) floor(($b - $f + 1) / 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = (int) floor($c / 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = (int) floor(($a + 11 * $h + 22 * $l) / 451);
    $month = (int) floor(($h + $l - 7 * $m + 114) / 31);
    $day = (($h + $l - 7 * $m + 114) % 31) + 1;

    return strtotime(sprintf('%04d-%02d-%02d', $year, $month, $day));
}

function getPhilippineHolidayMap($year) {
    $easterTs = getEasterSundayTimestamp($year);

    $holidays = array(
        sprintf('%04d-01-01', $year) => "New Year's Day",
        sprintf('%04d-02-25', $year) => 'EDSA People Power Revolution Anniversary',
        sprintf('%04d-04-09', $year) => 'Araw ng Kagitingan',
        sprintf('%04d-05-01', $year) => 'Labor Day',
        sprintf('%04d-06-12', $year) => 'Independence Day',
        sprintf('%04d-08-21', $year) => 'Ninoy Aquino Day',
        sprintf('%04d-11-01', $year) => "All Saints' Day",
        sprintf('%04d-11-30', $year) => 'Bonifacio Day',
        sprintf('%04d-12-08', $year) => 'Feast of the Immaculate Conception',
        sprintf('%04d-12-25', $year) => 'Christmas Day',
        sprintf('%04d-12-30', $year) => 'Rizal Day',
        sprintf('%04d-12-31', $year) => "New Year's Eve",
        date('Y-m-d', strtotime('last monday of august ' . $year)) => 'National Heroes Day',
    );

    $holidays[date('Y-m-d', strtotime('-3 days', $easterTs))] = 'Maundy Thursday';
    $holidays[date('Y-m-d', strtotime('-2 days', $easterTs))] = 'Good Friday';
    $holidays[date('Y-m-d', strtotime('-1 day', $easterTs))] = 'Black Saturday';

    ksort($holidays);

    return $holidays;
}

function seedPhilippineHolidays($conn, $year) {
    $year = (int) $year;

    if ($year < 2000 || $year > 2100) {
        return;
    }

    $holidayMap = getPhilippineHolidayMap($year);

    foreach ($holidayMap as $holidayDate => $holidayName) {
        $escapedName = mysqli_real_escape_string($conn, $holidayName);
        $query = "
            INSERT INTO holidays (holiday_date, holiday_name)
            VALUES ('$holidayDate', '$escapedName')
            ON DUPLICATE KEY UPDATE
                holiday_name = IF(holiday_name IS NULL OR holiday_name = '', VALUES(holiday_name), holiday_name)
        ";
        mysqli_query($conn, $query);
    }
}

// Check if date is a holiday
function isHoliday($conn, $date) {
    $year = (int) date('Y', strtotime($date));
    if ($year > 0) {
        seedPhilippineHolidays($conn, $year);
    }

    $query = "SELECT * FROM holidays WHERE holiday_date = '$date'";
    $result = mysqli_query($conn, $query);
    return mysqli_num_rows($result) > 0;
}

// Get all logs for display
function getAllLogs($conn) {
    $query = "
        SELECT l.*, 
               CASE 
                   WHEN h.holiday_date IS NOT NULL THEN h.holiday_name
                   ELSE 'Regular day'
               END as day_type
        FROM ojt_logs l
        LEFT JOIN holidays h ON l.date = h.holiday_date
        ORDER BY l.date DESC
    ";
    
    return mysqli_query($conn, $query);
}

// Get dashboard stats
function getDashboardStats($conn, $required_hours = null) {
    if ($required_hours === null) {
        $required_hours = getRequiredHours($conn);
    }

    $required_hours = max(1, (int)$required_hours);

    return array(
        'total_hours' => getTotalHours($conn),
        'total_days' => getTotalOJTDays($conn),
        'days_left' => calculateDaysLeft($conn, $required_hours),
        'required_hours' => $required_hours,
        'remaining_hours' => max(0, $required_hours - getTotalHours($conn))
    );
}

?>
