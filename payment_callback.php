<?php
require_once 'db_config.php';

// معالجة رد QNB
if (isset($_GET['qnb_reference'])) {
    $reference = sanitizeInput($_GET['qnb_reference']);
    
    $conn = getDBConnection();
    $sql = "SELECT * FROM RechargeTransactions 
            WHERE TransactionReference = ? AND Status = 'Pending'";
    $stmt = sqlsrv_prepare($conn, $sql, [$reference]);
    
    if (sqlsrv_execute($stmt) && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // التحقق من حالة الدفع مع QNB
        $paymentStatus = verifyQNBPayment($reference);
        
        if ($paymentStatus === 'success') {
            // تحديث الرصيد
            $updateSql = "UPDATE UserBalance 
                          SET Balance = Balance + ?,
                              LastRechargeDate = GETDATE()
                          WHERE UserID = ?";
            sqlsrv_prepare($conn, $updateSql, [$row['Amount'], $row['UserID']])->execute();
            
            // تحديث المعاملة
            $updateTrans = "UPDATE RechargeTransactions 
                            SET Status = 'Completed',
                                CompletedAt = GETDATE()
                            WHERE RechargeID = ?";
            sqlsrv_prepare($conn, $updateTrans, [$row['RechargeID']])->execute();
            
            // توجيه المستخدم
            header("Location: payment_success.html?txid=" . $reference);
        } else {
            header("Location: payment_failed.html?reason=payment_failed");
        }
    } else {
        header("Location: payment_failed.html?reason=invalid_reference");
    }
}

// دالة التحقق من حالة الدفع مع QNB
function verifyQNBPayment($reference) {
    $url = QNB_ENDPOINT . '/verify?reference=' . $reference;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . QNB_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        return $result['status']; // 'success' أو 'failed'
    }
    
    return 'failed';
}
?>