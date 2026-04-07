<?php
include 'config.php';

// Create ojt_logs table
$create_ojt_logs = "CREATE TABLE IF NOT EXISTS ojt_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    hours DECIMAL(5, 2) NOT NULL,
    source_upload_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date (date)
)";

// Create holidays table
$create_holidays = "CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_date DATE NOT NULL UNIQUE,
    holiday_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Create uploaded_files table
$create_uploaded_files = "CREATE TABLE IF NOT EXISTS uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    uploaded_count INT NOT NULL DEFAULT 0,
    skipped_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Execute queries
if (!mysqli_query($conn, $create_ojt_logs)) {
    die("Error creating ojt_logs table: " . mysqli_error($conn));
}

if (!mysqli_query($conn, $create_holidays)) {
    die("Error creating holidays table: " . mysqli_error($conn));
}

if (!mysqli_query($conn, $create_uploaded_files)) {
    die("Error creating uploaded_files table: " . mysqli_error($conn));
}

// Migration for existing databases
$checkSourceColumn = mysqli_query($conn, "SHOW COLUMNS FROM ojt_logs LIKE 'source_upload_id'");
if ($checkSourceColumn && mysqli_num_rows($checkSourceColumn) === 0) {
    if (!mysqli_query($conn, "ALTER TABLE ojt_logs ADD COLUMN source_upload_id INT NULL AFTER hours")) {
        die("Error adding source_upload_id column: " . mysqli_error($conn));
    }
}

// Insert Philippine holidays for 2026
$holidays_data = array(
    array('2026-01-01', 'New Year Day'),
    array('2026-02-10', 'EDSA Revolution Anniversary'),
    array('2026-02-11', 'Chinese New Year'),
    array('2026-02-12', 'Chinese New Year'),
    array('2026-04-09', 'Day of Valor'),
    array('2026-04-17', 'Maundy Thursday'),
    array('2026-04-18', 'Good Friday'),
    array('2026-04-19', 'Black Saturday'),
    array('2026-05-01', 'Labor Day'),
    array('2026-06-12', 'Independence Day'),
    array('2026-06-24', 'Feast of St. John'),
    array('2026-08-13', 'Feast of Sto. Nino'),
    array('2026-08-21', 'Ninoy Aquino Day'),
    array('2026-08-31', 'National Heroes Day'),
    array('2026-11-01', 'All Saints Day'),
    array('2026-11-30', 'Bonifacio Day'),
    array('2026-12-08', 'Immaculate Conception'),
    array('2026-12-25', 'Christmas Day'),
    array('2026-12-26', 'Additional Special Day'),
    array('2026-12-30', 'Rizal Day'),
    array('2026-12-31', 'New Years Eve')
);

// Insert holidays (ignore if already exists)
foreach ($holidays_data as $holiday) {
    $date = $holiday[0];
    $name = $holiday[1];
    $insert_holiday = "INSERT IGNORE INTO holidays (holiday_date, holiday_name) VALUES ('$date', '$name')";
    mysqli_query($conn, $insert_holiday);
}

echo "Database setup completed successfully!";

?>

