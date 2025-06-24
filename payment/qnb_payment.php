<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

try {
    // التحقق من المصادقة
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("Authentication required");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $amount = floatval($input['amount']);
    $testId = intval($input['test_id']);

    // 1. التحقق من بيانات الاختبار
    $test = getTestDetails($testId);
    
    // 2. بدء معاملة الدفع مع QNB
    $paymentResult = processQNBPayment($_SESSION['user_id'], $amount, $test['TestTitle']);
    
    // 3. إذا نجحت العملية
    if ($paymentResult['status'] === 'success') {
        // تسجيل الشراء
        recordPurchase($_SESSION['user_id'], $testId, $amount);
        
        // خصم المبلغ من رصيد المستخدم (إذا كان النظام يستخدم رصيد داخلي)
        updateUserBalance($_SESSION['user_id'], -$amount);
        
        echo json_encode([
            'status' => 'success',
            'transaction_id' => $paymentResult['transaction_id'],
            'balance' => getCurrentBalance($_SESSION['user_id'])
        ]);
    } else {
        throw new Exception($paymentResult['message']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// دالات مساعدة
function processQNBPayment($userId, $amount, $description) {
    // هنا يتم التواصل مع بوابة QNB الدفع
    // هذه بيانات وهمية للتوضيح
    return [
        'status' => 'success',
        'transaction_id' => 'QNB'.time(),
        'message' => 'Payment processed successfully'
    ];
}
?>