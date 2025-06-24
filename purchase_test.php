<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$testID = isset($input['testID']) ? (int)$input['testID'] : 0;

if ($testID <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid test ID']);
    exit;
}

$serverName = "ahmedzahran\\SQL";
$connectionOptions = [
    "Database" => "platform",
    "Uid" => "sa",
    "PWD" => "123123"
];

try {
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $userId = $_SESSION['user_id'];
    
    // بدء المعاملة
    sqlsrv_begin_transaction($conn);
    
    try {
        // 1. جلب سعر الاختبار
        $sql = "SELECT Price FROM Tests WHERE TestID = ?";
        $stmt = sqlsrv_query($conn, $sql, array($testID));
        
        if ($stmt === false || !sqlsrv_has_rows($stmt)) {
            throw new Exception('Test not found');
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $price = (float)$row['Price'];
        
        // 2. التحقق من رصيد المستخدم
        $sql = "SELECT balance FROM UserBalance WHERE user_id = ?";
        $stmt = sqlsrv_query($conn, $sql, array($userId));
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        
        if (!$row || (float)$row['balance'] < $price) {
            throw new Exception('Insufficient balance');
        }
        
        // 3. خصم المبلغ من رصيد المستخدم
        $sql = "UPDATE UserBalance SET balance = balance - ?, last_updated = GETDATE() WHERE user_id = ?";
        $stmt = sqlsrv_query($conn, $sql, array($price, $userId));
        
        if ($stmt === false) {
            throw new Exception('Failed to deduct balance');
        }
        
        // 4. تسجيل عملية الشراء
        $transactionId = uniqid('PUR_');
        $sql = "INSERT INTO UserPurchases (user_id, test_id, amount, payment_method, transaction_id, purchase_date)
                VALUES (?, ?, ?, 'Balance', ?, GETDATE())";
        $stmt = sqlsrv_query($conn, $sql, array($userId, $testID, $price, $transactionId));
        
        if ($stmt === false) {
            throw new Exception('Failed to record purchase');
        }
        
        // إتمام المعاملة
        sqlsrv_commit($conn);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Test purchased successfully',
            'transaction_id' => $transactionId
        ]);
        
    } catch (Exception $e) {
        sqlsrv_rollback($conn);
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        sqlsrv_close($conn);
    }
}
?>