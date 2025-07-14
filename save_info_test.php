<?php
// =============================================
// ملف حفظ بيانات الاختبار - الإصدار النهائي
// =============================================

/* **********************************************
 * ١. إعدادات الرأس والأذونات
 * *********************************************/
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

/* **********************************************
 * ٢. تسجيل الطلبات لأغراض التصحيح
 * *********************************************/
$debugLog = 
    "[" . date('Y-m-d H:i:s') . "]\n" .
    "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n" .
    "REMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'] . "\n" .
    "HTTP_X_REQUESTED_WITH: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'NOT SET') . "\n" .
    "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n" .
    "BODY: " . file_get_contents('php://input') . "\n" .
    "=================================\n\n";

file_put_contents('logs/requests_debug.log', $debugLog, FILE_APPEND);

/* **********************************************
 * ٣. التحقق من طريقة الطلب
 * *********************************************/
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode([
        'status' => 'error',
        'code' => 'METHOD_NOT_ALLOWED',
        'message' => 'يسمح فقط بطلبات POST',
        'received_method' => $_SERVER['REQUEST_METHOD']
    ], JSON_UNESCAPED_UNICODE));
}

/* **********************************************
 * ٤. معالجة بيانات الإدخال
 * *********************************************/
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// التحقق من صحة JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode([
        'status' => 'error',
        'code' => 'INVALID_JSON',
        'message' => 'تنسيق JSON غير صالح',
        'json_error' => json_last_error_msg(),
        'input_sample' => substr($input, 0, 100)
    ], JSON_UNESCAPED_UNICODE));
}

// التحقق من الحقول المطلوبة
$requiredFields = ['testTitle', 'testType', 'categorytest'];
$missingFields = [];

foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    die(json_encode([
        'status' => 'error',
        'code' => 'MISSING_FIELDS',
        'message' => 'حقول مطلوبة ناقصة',
        'missing_fields' => $missingFields
    ], JSON_UNESCAPED_UNICODE));
}

/* **********************************************
 * ٥. اتصال قاعدة البيانات
 * *********************************************/
define('DB_HOST', 'localhost');
define('DB_NAME', 'mathplatform');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode([
        'status' => 'error',
        'code' => 'DB_CONNECTION_FAILED',
        'message' => 'فشل الاتصال بقاعدة البيانات',
        'error_details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE));
}

/* **********************************************
 * ٦. إجراءات الحفظ
 * *********************************************/
try {
    // بدء المعاملة
    $pdo->beginTransaction();
    
    // إعداد الاستعلام
    $stmt = $pdo->prepare("INSERT INTO tests 
        (testTitle, testType, categorytest, breakDuration, price, createdAt) 
        VALUES (:title, :type, :category, :duration, :price, NOW())");
    
    // تنفيذ الاستعلام
    $stmt->execute([
        ':title' => htmlspecialchars(strip_tags($data['testTitle'])),
        ':type' => $data['testType'],
        ':category' => $data['categorytest'],
        ':duration' => $data['breakDuration'] ?? 5,
        ':price' => $data['price'] ?? 0
    ]);
    
    // الحصول على ID الأخير
    $testId = $pdo->lastInsertId();
    
    // تأكيد المعاملة
    $pdo->commit();
    
    // الرد الناجح
    http_response_code(201);
    echo json_encode([
        'status' => 'success',
        'code' => 'TEST_CREATED',
        'message' => 'تم إنشاء الاختبار بنجاح',
        'testId' => $testId,
        'createdAt' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    // التراجع عن المعاملة في حالة الخطأ
    $pdo->rollBack();
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'code' => 'DB_OPERATION_FAILED',
        'message' => 'فشل في تنفيذ العملية',
        'error_details' => $e->getMessage(),
        'error_code' => $e->getCode()
    ], JSON_UNESCAPED_UNICODE);
}

/* **********************************************
 * ٧. تسجيل النتيجة النهائية
 * *********************************************/
file_put_contents('logs/transactions.log', 
    "[" . date('Y-m-d H:i:s') . "] " . 
    "Test ID: " . ($testId ?? 'NULL') . " | " .
    "Status: " . (isset($e) ? 'FAILED' : 'SUCCESS') . "\n",
    FILE_APPEND
);
?>