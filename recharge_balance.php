<?php
header('Content-Type: application/json');
session_start();

// التحقق من أن الطلب من مصدر موثوق (يمكنك إضافة تحقق إضافي)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

// التحقق من وجود مستخدم مسجل دخوله
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['status' => 'error', 'message' => 'Unauthorized access']));
}

// استقبال البيانات
$data = json_decode(file_get_contents('php://input'), true);

// التحقق من صحة البيانات
if (!isset($data['amount']) || !isset($data['method'])) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Missing required fields']));
}

$amount = floatval($data['amount']);
$method = htmlspecialchars($data['method']);

// التحقق من أن المبلغ صحيح
if ($amount <= 0 || $amount > 1000) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid amount']));
}

// التحقق من طريقة الدفع الصالحة
$allowedMethods = ['credit_card', 'paypal', 'bank_transfer'];
if (!in_array($method, $allowedMethods)) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid payment method']));
}

// هنا نربط بقاعدة البيانات (مثال باستخدام PDO)
try {
    $db = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // بدء المعاملة
    $db->beginTransaction();

    // 1. الحصول على الرصيد الحالي
    $stmt = $db->prepare("SELECT balance FROM users WHERE id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    $currentBalance = floatval($user['balance']);

    // 2. تحديث الرصيد
    $newBalance = $currentBalance + $amount;
    $stmt = $db->prepare("UPDATE users SET balance = :balance WHERE id = :user_id");
    $stmt->execute([
        ':balance' => $newBalance,
        ':user_id' => $_SESSION['user_id']
    ]);

    // 3. تسجيل المعاملة
    $stmt = $db->prepare("
        INSERT INTO transactions 
        (user_id, amount, payment_method, transaction_type, status, created_at) 
        VALUES 
        (:user_id, :amount, :method, 'recharge', 'completed', NOW())
    ");
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':amount' => $amount,
        ':method' => $method
    ]);

    $transactionId = $db->lastInsertId();

    // إتمام المعاملة
    $db->commit();

    // إرسال الاستجابة الناجحة
    echo json_encode([
        'status' => 'success',
        'message' => 'تم شحن الرصيد بنجاح',
        'new_balance' => $newBalance,
        'transaction_id' => $transactionId
    ]);

} catch (PDOException $e) {
    // في حالة خطأ في قاعدة البيانات
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>