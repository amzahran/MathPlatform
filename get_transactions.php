<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized']));
}

require_once 'db_config.php';

try {
    $conn = new PDO("sqlsrv:Server=$serverName;Database=$database", $username, $password);
    
    $sql = "SELECT 
                'Test Purchase' as description,
                amount,
                purchase_date as date
            FROM [platform].[dbo].[UserPurchases]
            WHERE user_id = ?
            UNION ALL
            SELECT 
                'Deposit' as description,
                amount,
                transaction_date as date
            FROM [platform].[dbo].[Deposits]
            WHERE user_id = ?
            ORDER BY date DESC
            LIMIT 20";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>