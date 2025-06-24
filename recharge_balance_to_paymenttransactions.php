<?php
header('Content-Type: application/json');
session_start();

// Validate POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Ensure user is logged in
if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Input validation
$UserID = $_SESSION['UserID'];
$amount = $_POST['amount'] ?? null;
$method = $_POST['method'] ?? null;
$transactionId = $_POST['transactionId'] ?? null;
$receiptPath = null;

if (!$amount || !$method || !$transactionId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// Handle file upload
if (isset($_FILES['receiptImage']) && $_FILES['receiptImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = uniqid() . '_' . basename($_FILES['receiptImage']['name']);
    $targetFile = $uploadDir . $filename;
    if (move_uploaded_file($_FILES['receiptImage']['tmp_name'], $targetFile)) {
        $receiptPath = $targetFile;
    }
}

// Connect to SQL Server
$serverName = "ahmedzahran\SQL";
$connectionOptions = [
    "Database" => "platform",
    "Uid" => "sa",
    "PWD" => "123123"
];
$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Insert into PaymentTransactions table
$sql = "INSERT INTO dbo.PaymentTransactions (
    UserID, TestID, Amount, PaymentMethod, TransactionReference,
    Status, CreatedAt, UpdatedAt, RechargeFlag, TransactionType
) VALUES (?, NULL, ?, ?, ?, 'pending', GETDATE(), GETDATE(), 1, 'manual')";

$params = [$UserID, $amount, $method, $transactionId];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt) {
    echo json_encode(['status' => 'success', 'message' => 'Recharge request submitted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert transaction']);
}
?>
