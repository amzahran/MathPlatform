<?php
session_start();
header('Content-Type: application/json');

require_once 'db_config.php';

try {
    // التحقق من المصادقة
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("يجب تسجيل الدخول أولاً");
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $amount = floatval($input['amount']);
    $testId = intval($input['test_id']);
    $cardDetails = $input['card_details']; // يتم تشفيرها قبل الإرسال

    // 1. التحقق من بيانات الاختبار
    $test = getTestDetails($testId);
    if (!$test) {
        throw new Exception("الاختبار غير موجود");
    }

    // 2. معالجة الدفع عبر البنك الأهلي
    $paymentResult = processAhliPayment([
        'card_number' => encryptData($cardDetails['number']),
        'expiry' => $cardDetails['expiry'],
        'cvv' => encryptData($cardDetails['cvv']),
        'amount' => $amount,
        'description' => "شراء اختبار: " . $test['TestTitle']
    ]);

    // 3. إذا نجحت العملية
    if ($paymentResult['status'] === 'success') {
        // تسجيل الشراء
        recordPurchase($_SESSION['user_id'], $testId, $amount, 'ahli', $paymentResult['transaction_id']);
        
        echo json_encode([
            'status' => 'success',
            'transaction_id' => $paymentResult['transaction_id'],
            'receipt_url' => $paymentResult['receipt_url']
        ]);
    } else {
        throw new Exception($paymentResult['message']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * معالجة الدفع عبر بوابة البنك الأهلي
 */
function processAhliPayment($paymentData) {
    $url = AHLI_PAYMENT_ENDPOINT;
    
    $headers = [
        'Authorization: Bearer ' . AHLI_API_KEY,
        'Content-Type: application/json',
        'Merchant-ID: ' . AHLI_MERCHANT_ID
    ];

    $payload = [
        'card_number' => $paymentData['card_number'],
        'expiry_date' => $paymentData['expiry'],
        'cvv' => $paymentData['cvv'],
        'amount' => $paymentData['amount'],
        'currency' => 'SAR',
        'merchant_reference' => 'TEST_' . time(),
        'customer_ip' => $_SERVER['REMOTE_ADDR']
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return [
            'status' => 'success',
            'transaction_id' => $result['transaction_id'],
            'receipt_url' => $result['receipt_url'],
            'message' => 'تمت عملية الدفع بنجاح'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'فشل في عملية الدفع: ' . ($response ?: 'خطأ غير معروف')
        ];
    }
}

/**
 * تشفير البيانات الحساسة
 */
function encryptData($data) {
    return openssl_encrypt(
        $data, 
        'AES-256-CBC', 
        AHLI_ENCRYPTION_KEY, 
        0, 
        AHLI_IV_KEY
    );
}
?>