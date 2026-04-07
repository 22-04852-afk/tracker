<?php
include 'config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$createUploadedFilesTable = "CREATE TABLE IF NOT EXISTS uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL UNIQUE,
    file_path VARCHAR(255) NOT NULL,
    uploaded_count INT NOT NULL DEFAULT 0,
    skipped_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $createUploadedFilesTable);

$checkSourceColumn = mysqli_query($conn, "SHOW COLUMNS FROM ojt_logs LIKE 'source_upload_id'");
if ($checkSourceColumn && mysqli_num_rows($checkSourceColumn) === 0) {
    mysqli_query($conn, "ALTER TABLE ojt_logs ADD COLUMN source_upload_id INT NULL AFTER hours");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['file_id'])) {
    header('Location: upload.php?deleted=0');
    exit;
}

$fileId = (int)$_POST['file_id'];
$getFileQuery = "SELECT id, file_path FROM uploaded_files WHERE id = $fileId LIMIT 1";
$getFileResult = mysqli_query($conn, $getFileQuery);

if (!$getFileResult || mysqli_num_rows($getFileResult) === 0) {
    header('Location: upload.php?deleted=0');
    exit;
}

$fileData = mysqli_fetch_assoc($getFileResult);
$relativePath = $fileData['file_path'];
$fullPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

$deletedRows = 0;

// Primary delete path: rows linked by source_upload_id.
if (!mysqli_query($conn, "DELETE FROM ojt_logs WHERE source_upload_id = $fileId")) {
    header('Location: upload.php?deleted=0');
    exit;
}

$deletedRows += max(0, (int)mysqli_affected_rows($conn));

// Fallback for legacy uploads created before source_upload_id existed.
if (is_file($fullPath)) {
    try {
        $spreadsheet = IOFactory::load($fullPath);
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex == 1) {
                continue;
            }

            $dateValue = $sheet->getCell("A$rowIndex")->getValue();
            $timeInValue = trim((string)$sheet->getCell("B$rowIndex")->getFormattedValue());
            $timeOutValue = trim((string)$sheet->getCell("C$rowIndex")->getFormattedValue());

            if (!$dateValue || !$timeInValue || !$timeOutValue) {
                continue;
            }

            if (is_numeric($dateValue)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateValue)->format('Y-m-d');
            } else {
                $date = date('Y-m-d', strtotime((string)$dateValue));
            }

            if (!$date || $date === '1970-01-01') {
                continue;
            }

            $safeDate = mysqli_real_escape_string($conn, $date);
            $legacyDeleteQuery = "
                DELETE FROM ojt_logs
                WHERE source_upload_id IS NULL
                  AND date = '$safeDate'
            ";

            if (mysqli_query($conn, $legacyDeleteQuery)) {
                $deletedRows += max(0, (int)mysqli_affected_rows($conn));
            }
        }
    } catch (Exception $e) {
        // Continue: source-linked deletion above already ran successfully.
    }
}

$deleteOk = true;
if (is_file($fullPath)) {
    $deleteOk = unlink($fullPath);
}

if (!$deleteOk) {
    header('Location: upload.php?deleted=0');
    exit;
}

$deleteRowQuery = "DELETE FROM uploaded_files WHERE id = $fileId";
if (!mysqli_query($conn, $deleteRowQuery)) {
    header('Location: upload.php?deleted=0');
    exit;
}

header('Location: upload.php?deleted=1&deleted_rows=' . max(0, (int)$deletedRows));
exit;
