<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
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
    
    // جلب الاختبارات المشتراة مع معلومات إضافية
    $sql = "SELECT up.test_id, t.TestTitle, up.amount, up.purchase_date 
            FROM UserPurchases up
            JOIN Tests t ON up.test_id = t.TestID
            WHERE up.user_id = ? AND up.test_id IS NOT NULL
            ORDER BY up.purchase_date DESC";
    
    $stmt = sqlsrv_query($conn, $sql, array($userId));
    
    if ($stmt === false) {
        throw new Exception('Query failed');
    }

    $purchasedTests = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $row['purchase_date'] = $row['purchase_date']->format('Y-m-d H:i:s');
        $purchasedTests[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $purchasedTests
    ]);
    
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