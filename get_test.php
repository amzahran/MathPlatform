<?php
header('Content-Type: application/json');

// إعداد الاتصال بقاعدة البيانات
$serverName = "127.0.0.1\\sql";
$connectionOptions = array(
    "Database" => "Platform",
    "Uid" => "sa",
    "PWD" => "123123"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    echo json_encode(["success" => false, "message" => "DB connection failed", "details" => sqlsrv_errors()]);
    exit;
}

// التحقق من وجود معرف الاختبار
$testId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($testId <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid test ID"]);
    exit;
}

// جلب بيانات الاختبار
$testQuery = "SELECT TestID, TestTitle, TestType, BreakDuration FROM Tests WHERE TestID = ?";
$testStmt = sqlsrv_query($conn, $testQuery, [$testId]);
if (!$testStmt) {
    echo json_encode(["success" => false, "message" => "Failed to fetch test", "details" => sqlsrv_errors()]);
    exit;
}

$test = sqlsrv_fetch_array($testStmt, SQLSRV_FETCH_ASSOC);
if (!$test) {
    echo json_encode(["success" => false, "message" => "Test not found"]);
    exit;
}

// جلب الأقسام
$sectionQuery = "SELECT SectionID, SectionNumber, Duration, NumberOfQuestions FROM Sections WHERE TestID = ?";
$sectionStmt = sqlsrv_query($conn, $sectionQuery, [$testId]);
if (!$sectionStmt) {
    echo json_encode(["success" => false, "message" => "Failed to fetch sections", "details" => sqlsrv_errors()]);
    exit;
}

$sections = [];
while ($section = sqlsrv_fetch_array($sectionStmt, SQLSRV_FETCH_ASSOC)) {
    $sectionId = $section['SectionID'];

    // جلب الأسئلة
    $questionQuery = "SELECT QuestionID, QuestionText, QuestionType, CorrectAnswer, QuestionImage FROM Questions WHERE SectionID = ?";
    $questionStmt = sqlsrv_query($conn, $questionQuery, [$sectionId]);
    if (!$questionStmt) {
        echo json_encode(["success" => false, "message" => "Failed to fetch questions", "details" => sqlsrv_errors()]);
        exit;
    }

    $questions = [];
    while ($question = sqlsrv_fetch_array($questionStmt, SQLSRV_FETCH_ASSOC)) {
        $questionId = $question['QuestionID'];

        // جلب الخيارات
        $optionQuery = "SELECT OptionText, OptionImage FROM Options WHERE QuestionID = ?";
        $optionStmt = sqlsrv_query($conn, $optionQuery, [$questionId]);
        if (!$optionStmt) {
            echo json_encode(["success" => false, "message" => "Failed to fetch options", "details" => sqlsrv_errors()]);
            exit;
        }

        $options = [];
        while ($option = sqlsrv_fetch_array($optionStmt, SQLSRV_FETCH_ASSOC)) {
            $options[] = [
                'OptionText' => $option['OptionText'],
                'OptionImage' => $option['OptionImage']
            ];
        }

        $questions[] = [
            'QuestionID' => $question['QuestionID'],
            'QuestionText' => $question['QuestionText'],
            'QuestionType' => $question['QuestionType'],
            'CorrectAnswer' => $question['CorrectAnswer'],
            'QuestionImage' => $question['QuestionImage'],
            'options' => $options
        ];
    }

    $sections[] = [
        'sectionNumber' => $section['SectionNumber'],
        'duration' => $section['Duration'],
        'questionCount' => $section['NumberOfQuestions'],
        'questions' => $questions
    ];
}

echo json_encode([
    "success" => true,
    "data" => [
        'TestID' => $test['TestID'],
        'TestTitle' => $test['TestTitle'],
        'TestType' => $test['TestType'],
        'breakDuration' => $test['BreakDuration'],
        'sections' => $sections
    ]
]);
?>