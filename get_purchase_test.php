<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// بيانات الاتصال بـ SQL Server
$serverName = "ahmedzahran\\SQL"; // اسم السيرفر كما في SSMS
$connectionOptions = [
    "Database" => "platform",
    "Uid" => "sa",
    "PWD" => "123123"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Connection failed.']);
    exit;
}

// استقبال البيانات من الواجهة
$input = json_decode(file_get_contents("php://input"), true);
$testID = $input['testID'];
$userID = 1; // يمكن تغييره لاحقًا بناءً على الجلسة

// جلب السعر من جدول الاختبارات
$sql = "SELECT Price FROM Tests WHERE TestID = ?";
$stmt = sqlsrv_prepare($conn, $sql, [$testID]);
sqlsrv_execute($stmt);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'Test not found.']);
    exit;
}
$price = $row['Price'];

// التحقق من رصيد المستخدم
$sql = "SELECT balance FROM UserBalance WHERE user_id = ?";
$stmt = sqlsrv_prepare($conn, $sql, [$userID]);
sqlsrv_execute($stmt);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if (!$row || $row['balance'] < $price) {
    echo json_encode(['status' => 'error', 'message' => 'Insufficient balance.']);
    exit;
}

// تنفيذ عملية الشراء (خصم الرصيد وتسجيل الشراء داخل TRANSACTION)
sqlsrv_begin_transaction($conn);

try {
    // خصم الرصيد
    $sql = "UPDATE UserBalance SET balance = balance - ?, last_updated = GETDATE() WHERE user_id = ?";
    $stmt = sqlsrv_prepare($conn, $sql, [$price, $userID]);
    sqlsrv_execute($stmt);

    // إضافة إلى UserPurchases
    $sql = "INSERT INTO UserPurchases (user_id, test_id, purchase_date, amount, payment_method, transaction_id)
            VALUES (?, ?, GETDATE(), ?, 'Balance', NEWID())";
    $stmt = sqlsrv_prepare($conn, $sql, [$userID, $testID, $price]);
    sqlsrv_execute($stmt);

    sqlsrv_commit($conn);
    echo json_encode(['status' => 'success', 'message' => 'Purchase successful.']);

} catch (Exception $e) {
    sqlsrv_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed.']);
}
?>
