<?php
// submit_manual_recharge.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$amount = $_POST['amount'] ?? null;
$method = $_POST['method'] ?? null;
$transactionId = $_POST['transactionId'] ?? null;
$receiptPath = null;

if (!$amount || !$method || !$transactionId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

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

$logData = [
    'datetime' => date('Y-m-d H:i:s'),
    'amount' => $amount,
    'method' => $method,
    'transactionId' => $transactionId,
    'receipt' => $receiptPath
];

file_put_contents('recharge_log.json', json_encode($logData, JSON_PRETTY_PRINT) . PHP_EOL, FILE_APPEND);

echo json_encode(['status' => 'success', 'message' => 'Recharge request submitted successfully!']);
?>
