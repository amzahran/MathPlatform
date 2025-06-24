<?php
require_once 'db_config.php';

// معالجة إشعار QNB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qnb_notification'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // التحقق من التوقيع
    if (verifyQNBSignature($data)) {
        $transactionId = $data['transaction_id'];
        $status = $data['status'];
        
        // تحديث حالة الطلب في قاعدة البيانات
        updateTransactionStatus($transactionId, $status);
        
        if ($status === 'completed') {
            // تنفيذ أي إجراءات إضافية
        }
        
        http_response_code(200);
        echo 'OK';
    }
}

// معالجة إشعار We
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['we_notification'])) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (verifyWeSignature($data)) {
        $transactionId = $data['txn_id'];
        $status = $data['payment_status'];
        
        updateTransactionStatus($transactionId, $status);
        http_response_code(200);
        echo 'OK';
    }
}

function verifyQNBSignature($data) {
    // تنفيذ التحقق من توقيع QNB
    return true;
}

function verifyWeSignature($data) {
    // تنفيذ التحقق من توقيع We
    return true;
}
?>