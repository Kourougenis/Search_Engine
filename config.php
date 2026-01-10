<?php
/* 
* Copyright (c) 2026 Aggelos Kourougenis
* Licensed under the MIT License.
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    http_response_code(403);
    exit('Forbidden');
}

// ENVIRONMENT
define('APP_ENV', 'production'); // άλλαξε σε 'development' αν θες debug

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
}

// SECURITY HEADERS
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer");
header("X-XSS-Protection: 1; mode=block");

// DATABASE CONNECTION

// LOCAL (XAMPP)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "search_engine";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Database connection error: " . $conn->connect_error);
}
?>
