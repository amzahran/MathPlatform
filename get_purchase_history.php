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
    
    $sql = "SELECT 
                transaction_id,
                purchase_date,
                amount,
                payment_method,
                CASE 
                    WHEN test_id IS NULL THEN 'Balance Recharge'
                    ELSE (SELECT TestTitle FROM Tests WHERE TestID = test_id)
                END AS description
            FROM UserPurchases
            WHERE user_id = ?
            ORDER BY purchase_date DESC";
    
    $stmt = sqlsrv_query($conn, $sql, array($userId));
    
    if ($stmt === false) {
        throw new Exception('Query failed');
    }

    $transactions = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $row['purchase_date'] = $row['purchase_date']->format('Y-m-d H:i:s');
        $transactions[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $transactions
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