<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
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
        throw new Exception("Database connection failed");
    }

    $userId = $_SESSION["user_id"];
    $sql = "SELECT balance FROM UserBalance WHERE user_id = ?";
    $params = array($userId);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        throw new Exception("Query failed");
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($row) {
        $response = [
            "status" => "success",
            "balance" => number_format((float)$row["balance"], 2)
        ];
    } else {
        // إذا لم يكن للمستخدم رصيد، ننشئ له سجلًا جديدًا
        $sql = "INSERT INTO UserBalance (user_id, balance, last_updated) VALUES (?, 0.00, GETDATE())";
        $stmt = sqlsrv_query($conn, $sql, array($userId));
        
        if ($stmt === false) {
            throw new Exception("Failed to create balance record");
        }
        
        $response = [
            "status" => "success",
            "balance" => "0.00"
        ];
    }

    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        sqlsrv_close($conn);
    }
}
?>