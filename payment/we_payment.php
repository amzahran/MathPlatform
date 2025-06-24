<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $amount = floatval($input['amount']);
    $testId = intval($input['test_id']);
    $weToken = sanitizeInput($input['we_token']); // Token من واجهة We

    // 1. التحقق من بيانات الاختبار
    $test = getTestDetails($testId);
    
    // 2. معالجة الدفع عبر We
    $paymentResult = processWePayment($weToken, $amount, $test['TestTitle']);
    
    if ($paymentResult['status'] === 'success') {
        recordPurchase($_SESSION['user_id'], $testId, $amount);
        echo json_encode([
            'status' => 'success',
            'transaction_id' => $paymentResult['transaction_id']
        ]);
    } else {
        throw new Exception($paymentResult['message']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

function processWePayment($token, $amount, $description) {
    // التواصل مع واجهة برمجة تطبيقات We
    return [
        'status' => 'success',
        'transaction_id' => 'WE'.time(),
        'message' => 'We payment processed'
    ];
}
?>