<?php

// Compute hours between time_in and time_out
function computeHours($time_in, $time_out) {
    $in = strtotime($time_in);
    $out = strtotime($time_out);
    
    if ($out > $in) {
        $hours = ($out - $in) / 3600;
        // Subtract 1 hour for lunch break
        return round($hours - 1, 2);
    }
    
    return 0;
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

// Check if date is a holiday
function isHoliday($conn, $date) {
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
function getDashboardStats($conn, $required_hours = 500) {
    return array(
        'total_hours' => getTotalHours($conn),
        'total_days' => getTotalOJTDays($conn),
        'days_left' => calculateDaysLeft($conn, $required_hours),
        'required_hours' => $required_hours,
        'remaining_hours' => max(0, $required_hours - getTotalHours($conn))
    );
}

?>
