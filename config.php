<?php
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

?>
