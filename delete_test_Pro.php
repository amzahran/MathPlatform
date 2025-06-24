<?php
header('Content-Type: application/json');

// الاتصال بقاعدة البيانات
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'mathplatform';

$conn = new mysqli($host, $user, $password, $database);

// التحقق من الاتصال
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// قراءة بيانات JSON
$data = json_decode(file_get_contents('php://input'), true);
$testID = isset($data['testID']) ? (int)$data['testID'] : 0;

if ($testID <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Test ID.']);
    exit;
}

// حذف الاختبار (الباقي يتم حذفه تلقائيًا بسبب ON DELETE CASCADE)
$stmt = $conn->prepare("DELETE FROM Tests WHERE TestID = ?");
$stmt->bind_param("i", $testID);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete test.']);
}

$stmt->close();
$conn->close();
?>
