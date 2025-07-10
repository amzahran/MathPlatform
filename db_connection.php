<?php
$host = 'localhost';
$db   = 'mathplatform';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// إنشاء الاتصال
$conn = new mysqli($host, $user, $pass, $db);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
