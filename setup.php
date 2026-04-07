<?php
include 'config.php';
include_once 'functions.php';

// Create ojt_logs table
$create_ojt_logs = "CREATE TABLE IF NOT EXISTS ojt_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time_in TIME NOT NULL,
    time_out TIME NOT NULL,
    hours INT NOT NULL,
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

// Auto-seed Philippine holidays for nearby years so users don't need manual input.
$baseYear = (int) date('Y');
for ($year = $baseYear - 1; $year <= $baseYear + 2; $year++) {
    seedPhilippineHolidays($conn, $year);
}

echo "Database setup completed successfully!";

?>

