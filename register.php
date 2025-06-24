<?php
header('Content-Type: application/json');
header('Charset: UTF-8');
session_start();

// تمكين سجلات الأخطاء
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// معالجة البيانات المدخلة
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// تنظيف المدخلات
$fullName = trim($input['fullName'] ?? '');
$grade = trim($input['grade'] ?? '');
$mobile = trim($input['mobile'] ?? '');
$email = trim($input['email'] ?? '');
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirmPassword'] ?? '';

// التحقق من الصحة
$errors = [];
if (empty($fullName)) $errors[] = 'Full name is required';
if (empty($grade)) $errors[] = 'Grade is required';
if (empty($mobile) || !preg_match('/^[0-9]+$/', $mobile)) $errors[] = 'Valid mobile number is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
if (empty($username)) $errors[] = 'Username is required';
if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => implode('; ', $errors)]);
    exit;
}

// اتصال قاعدة البيانات
$serverName = "ahmedzahran\\SQL";
$connectionOptions = [
    "Database" => "platform",
    "Uid" => "sa",
    "PWD" => "123123",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true
];

try {
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if (!$conn) {
        throw new Exception("Database connection failed: " . print_r(sqlsrv_errors(), true));
    }

    // التحقق من وجود المستخدم
    $checkSql = "SELECT Username FROM Users WHERE Username = ?";
    $checkParams = [$username];
    $checkStmt = sqlsrv_prepare($conn, $checkSql, $checkParams);
    
    if (!$checkStmt || !sqlsrv_execute($checkStmt)) {
        throw new Exception("Database query failed: " . print_r(sqlsrv_errors(), true));
    }

    if (sqlsrv_fetch($checkStmt)) {
        echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
        exit;
    }

    // تشفير كلمة المرور
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // إدراج المستخدم الجديد
    $insertSql = "INSERT INTO Users (FullName, Grade, Mobile, Email, Username, Password) VALUES (?, ?, ?, ?, ?, ?)";
    $insertParams = [$fullName, $grade, $mobile, $email, $username, $hashedPassword];
    $insertStmt = sqlsrv_prepare($conn, $insertSql, $insertParams);
    
    if (!$insertStmt || !sqlsrv_execute($insertStmt)) {
        $errors = sqlsrv_errors();
        error_log("SQL Error: " . print_r($errors, true));
        throw new Exception("Insert failed: " . print_r($errors, true));
    }

    echo json_encode(['status' => 'success', 'message' => 'Registration successful']);
    
} catch (Exception $e) {
    error_log("Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred. Please try again.']);
} finally {
    if (isset($checkStmt)) sqlsrv_free_stmt($checkStmt);
    if (isset($insertStmt)) sqlsrv_free_stmt($insertStmt);
    if (isset($conn)) sqlsrv_close($conn);
}
?>