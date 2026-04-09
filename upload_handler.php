<?php
require 'vendor/autoload.php';
include 'config.php';
include 'functions.php';

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

$uploaded = 0;
$skipped = 0;
$skipReasons = [];
$errors = [];
$success = false;
$saved_file_id = null;
$storedFilePath = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['excel'])) {
    try {
        $tmpFile = $_FILES['excel']['tmp_name'] ?? '';
        $filename = $_FILES['excel']['name'] ?? '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';

        // Validate file type
        if (!$tmpFile || !in_array($extension, ['xlsx', 'xls', 'csv'])) {
            $errors[] = 'Invalid file type. Please upload .xlsx, .xls, or .csv files.';
        } else {
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new Exception('Upload directory is not writable.');
            }

            $storedName = uniqid('upload_', true) . '.' . $extension;
            $storedFilePath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
            $relativePath = 'uploads/' . $storedName;

            if (!move_uploaded_file($tmpFile, $storedFilePath)) {
                throw new Exception('Failed to save uploaded file. Please try again.');
            }

            $safeOriginalName = mysqli_real_escape_string($conn, $filename);
            $safeStoredName = mysqli_real_escape_string($conn, $storedName);
            $safeRelativePath = mysqli_real_escape_string($conn, $relativePath);
            $createUploadBatchQuery = "
                INSERT INTO uploaded_files (original_name, stored_name, file_path, uploaded_count, skipped_count)
                VALUES ('$safeOriginalName', '$safeStoredName', '$safeRelativePath', 0, 0)
            ";

            if (!mysqli_query($conn, $createUploadBatchQuery)) {
                throw new Exception('Failed to create upload batch record.');
            }

            $saved_file_id = mysqli_insert_id($conn);

            // Load spreadsheet
            $spreadsheet = IOFactory::load($storedFilePath);
            $sheet = $spreadsheet->getActiveSheet();

            $recordSkip = function ($rowIndex, $dateLabel, $reason) use (&$skipReasons) {
                $label = trim((string) $dateLabel);
                if ($label === '') {
                    $label = 'Row ' . $rowIndex;
                }

                $skipReasons[] = array(
                    'row' => (int) $rowIndex,
                    'date' => $label,
                    'reason' => (string) $reason,
                );
            };

            // Iterate through rows
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                // Skip header row
                if ($rowIndex == 1) continue;

                // Get cell values
                $dateCell = $sheet->getCell("A$rowIndex");
                $dateValue = $dateCell->getValue();
                $dateFormattedValue = $dateCell->getFormattedValue();
                $timeInCell = $sheet->getCell("B$rowIndex");
                $timeOutCell = $sheet->getCell("C$rowIndex");
                $timeInRawValue = $timeInCell->getValue();
                $timeOutRawValue = $timeOutCell->getValue();
                $timeInFormattedValue = $timeInCell->getFormattedValue();
                $timeOutFormattedValue = $timeOutCell->getFormattedValue();

                // Skip empty rows
                if (!$dateValue || ($timeInRawValue === null || $timeInRawValue === '') || ($timeOutRawValue === null || $timeOutRawValue === '')) {
                    continue;
                }

                // Convert date
                $date = parseSpreadsheetDate($dateValue, $dateFormattedValue);

                if (!$date || $date === '1970-01-01') {
                    $skipped++;
                    $recordSkip($rowIndex, $dateFormattedValue, 'Invalid date format.');
                    continue;
                }

                $timeInValue = parseSpreadsheetTime($timeInRawValue, $timeInFormattedValue);
                $timeOutValue = parseSpreadsheetTime($timeOutRawValue, $timeOutFormattedValue);

                if ($timeInValue === '' || $timeOutValue === '') {
                    $skipped++;
                    $recordSkip($rowIndex, $dateFormattedValue, 'Invalid time format.');
                    continue;
                }

                // Check if entry already exists
                $checkDuplicate = mysqli_query($conn, "SELECT id FROM ojt_logs WHERE date = '$date' LIMIT 1");
                if (mysqli_num_rows($checkDuplicate) > 0) {
                    $skipped++;
                    $recordSkip($rowIndex, $dateFormattedValue, 'Duplicate date already exists in logs.');
                    continue;
                }

                // Compute hours
                $hours = computeHours($timeInValue, $timeOutValue);

                // Validate hours
                if ($hours <= 0) {
                    $skipped++;
                    $recordSkip($rowIndex, $dateFormattedValue, 'Computed hours is zero or negative.');
                    continue;
                }

                // Insert into database
                $insertQuery = "INSERT INTO ojt_logs (date, time_in, time_out, hours, source_upload_id) 
                               VALUES ('$date', '$timeInValue', '$timeOutValue', '$hours', $saved_file_id)";

                if (mysqli_query($conn, $insertQuery)) {
                    $uploaded++;
                } else {
                    $skipped++;
                    $recordSkip($rowIndex, $dateFormattedValue, 'Database insert failed.');
                    $errors[] = "Error inserting row $rowIndex: " . mysqli_error($conn);
                }
            }

            $success = true;

            mysqli_query(
                $conn,
                "UPDATE uploaded_files SET uploaded_count = $uploaded, skipped_count = $skipped WHERE id = $saved_file_id"
            );
        }
    } catch (Exception $e) {
        $errors[] = "Error reading file: " . $e->getMessage();

        if ($saved_file_id) {
            mysqli_query($conn, "DELETE FROM uploaded_files WHERE id = $saved_file_id");
            mysqli_query($conn, "DELETE FROM ojt_logs WHERE source_upload_id = $saved_file_id");
        }

        if ($storedFilePath && is_file($storedFilePath)) {
            @unlink($storedFilePath);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Results - OJT Tracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(180deg, #ffd6e0 0%, #ffe8f0 100%);
            color: #555;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 520px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #ff6b9d;
            margin-bottom: 10px;
        }

        .card {
            background: #ffffff;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .results-summary {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .result-item {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .result-item.success {
            background: #e8f5e9;
            border: 1px solid #c8e6c9;
        }

        .result-item.skipped {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
        }

        .result-value {
            font-size: 32px;
            font-weight: 700;
            display: block;
            margin-bottom: 6px;
        }

        .result-item.success .result-value {
            color: #2e7d32;
        }

        .result-item.skipped .result-value {
            color: #e65100;
        }

        .result-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .result-item.success .result-label {
            color: #558b2f;
        }

        .result-item.skipped .result-label {
            color: #d84315;
        }

        .message {
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
        }

        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
            border-left: 3px solid #ff6b9d;
        }

        .error-list {
            background: #fff5f7;
            border: 1px solid #ffeaef;
            border-radius: 10px;
            padding: 12px;
            margin-top: 12px;
            font-size: 12px;
            max-height: 200px;
            overflow-y: auto;
        }

        .error-list li {
            margin-bottom: 6px;
            color: #c62828;
        }

        .skip-reason-wrap {
            background: #fff8eb;
            border: 1px solid #ffe1b0;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .skip-reason-title {
            color: #a04b00;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .skip-reason-list {
            margin: 0;
            padding-left: 18px;
            font-size: 12px;
            color: #6a4100;
            max-height: 180px;
            overflow-y: auto;
        }

        .skip-reason-list li {
            margin-bottom: 6px;
        }

        .button-group {
            display: flex;
            gap: 12px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-dashboard {
            background: linear-gradient(135deg, #ffc2d1 0%, #ff8fab 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(255, 139, 171, 0.2);
        }

        .btn-dashboard:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(255, 139, 171, 0.35);
        }

        .btn-upload {
            background: white;
            color: #ff8fab;
            border: 2px solid #ffc2d1;
        }

        .btn-upload:hover {
            background: #fff5f7;
            border-color: #ff8fab;
        }

        @media (max-width: 512px) {
            .header h1 {
                font-size: 28px;
            }

            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $success && $uploaded > 0 ? '✅ Upload Complete!' : '⚠️ Upload Result'; ?></h1>
        </div>

        <div class="card">
            <?php if ($success): ?>
                <?php if ($uploaded > 0): ?>
                    <div class="message success">
                        🎉 Successfully imported <?php echo $uploaded; ?> log entry(ies)!
                    </div>
                <?php endif; ?>

                <div class="results-summary">
                    <div class="result-item success">
                        <span class="result-value"><?php echo $uploaded; ?></span>
                        <span class="result-label">Imported</span>
                    </div>
                    <div class="result-item skipped">
                        <span class="result-value"><?php echo $skipped; ?></span>
                        <span class="result-label">Skipped</span>
                    </div>
                </div>

                <?php if (!empty($skipReasons)): ?>
                    <div class="skip-reason-wrap">
                        <div class="skip-reason-title">Skipped details:</div>
                        <ul class="skip-reason-list">
                            <?php foreach ($skipReasons as $skipReason): ?>
                                <li>
                                    Row <?php echo (int) $skipReason['row']; ?>
                                    (<?php echo htmlspecialchars((string) $skipReason['date']); ?>):
                                    <?php echo htmlspecialchars((string) $skipReason['reason']); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="message error">
                        ⚠️ Some issues detected:
                        <ul class="error-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="message error">
                    ❌ Upload failed. Please check:
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="button-group">
                <a href="index.php" class="btn btn-dashboard">📊 Dashboard</a>
                <a href="upload.php" class="btn btn-upload">📤 Upload Again</a>
            </div>
        </div>
    </div>
</body>
</html>

