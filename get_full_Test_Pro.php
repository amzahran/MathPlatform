<?php
header('Content-Type: application/json');

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'mathplatform';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

$testID = isset($_GET['testID']) ? (int)$_GET['testID'] : 0;
if ($testID <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Test ID.']);
    exit;
}

// جلب بيانات الاختبار
$test = $conn->query("SELECT * FROM Tests WHERE TestID = $testID")->fetch_assoc();
if (!$test) {
    echo json_encode(['status' => 'error', 'message' => 'Test not found.']);
    exit;
}

// جلب السكاشن
$sections = [];
$sectionsQuery = $conn->query("SELECT * FROM Sections WHERE TestID = $testID");
while ($section = $sectionsQuery->fetch_assoc()) {
    $sectionID = $section['SectionID'];
    $questions = [];

    $questionsQuery = $conn->query("SELECT * FROM Questions WHERE SectionID = $sectionID");
    while ($question = $questionsQuery->fetch_assoc()) {
        $questionID = $question['QuestionID'];
        $options = [];

        $optionsQuery = $conn->query("SELECT * FROM AnswerOptions WHERE QuestionID = $questionID");
        while ($option = $optionsQuery->fetch_assoc()) {
            $options[] = $option;
        }

        $question['AnswerOptions'] = $options;
        $questions[] = $question;
    }

    $section['Questions'] = $questions;
    $sections[] = $section;
}

echo json_encode([
    'status' => 'success',
    'test' => $test,
    'sections' => $sections
]);
$conn->close();
?>
