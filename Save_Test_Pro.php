<?php
include 'db_connection.php';

$testTitle = $_POST['TestTitle'];
$testType = $_POST['TestType'];
$category = $_POST['Category'];
$price = $_POST['Price'] ?? 0;
$sections = json_decode($_POST['Sections'], true);

// إدراج الاختبار في جدول Tests
$stmt = $conn->prepare("INSERT INTO Tests (TestTitle, TestType, CategoryTest, Price, BreakDuration) VALUES (?, ?, ?, ?, 0)");
$stmt->bind_param("sssd", $testTitle, $testType, $category, $price);
$stmt->execute();
$testID = $stmt->insert_id;
$stmt->close();

// إدراج السكاشن والأسئلة
foreach ($sections as $i => $sec) {
    $sectionTitle = $sec['SectionTitle'];
    $duration = (int)$sec['Duration'];
    $numQuestions = count($sec['Questions']);

    $stmt = $conn->prepare("INSERT INTO Sections (TestID, SectionNumber, NumberOfQuestions, Duration, SectionTitle) VALUES (?, ?, ?, ?, ?)");
    $sectionNumber = $i + 1;
    $stmt->bind_param("iiiss", $testID, $sectionNumber, $numQuestions, $duration, $sectionTitle);
    $stmt->execute();
    $sectionID = $stmt->insert_id;
    $stmt->close();

    foreach ($sec['Questions'] as $j => $q) {
        $text = $q['QuestionText'];
        $type = $q['QuestionType'];
        $questionImage = null;

        // معالجة صورة السؤال
        $fileKey = "questionImage_{$i}_{$j}";
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === 0) {
            $questionImage = "uploads/" . time() . "_q_{$i}_{$j}_" . basename($_FILES[$fileKey]['name']);
            move_uploaded_file($_FILES[$fileKey]['tmp_name'], $questionImage);
        }

        $stmt = $conn->prepare("INSERT INTO Questions (SectionID, QuestionText, QuestionType, QuestionImage) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $sectionID, $text, $type, $questionImage);
        $stmt->execute();
        $questionID = $stmt->insert_id;
        $stmt->close();

        if (strtolower($type) === "gridin") {
        $correctAnswer = $q['CorrectAnswer'] ?? '';
        if ($correctAnswer !== '') {
            $stmt = $conn->prepare("INSERT INTO AnswerOptions (QuestionID, OptionText, IsCorrect) VALUES (?, ?, 1)");
            $stmt->bind_param("is", $questionID, $correctAnswer);
            $stmt->execute();
            $stmt->close();
        }
    }

    if ($type === "MCQ") {
            foreach ($q['Options'] as $k => $opt) {
                $optText = $opt['text'];
                $isCorrect = $opt['isCorrect'] ? 1 : 0;
                $optImage = null;

                $optKey = "optionImage_{$i}_{$j}_{$k}";
                if (isset($_FILES[$optKey]) && $_FILES[$optKey]['error'] === 0) {
                    $optImage = "uploads/" . time() . "_opt_{$i}_{$j}_{$k}_" . basename($_FILES[$optKey]['name']);
                    move_uploaded_file($_FILES[$optKey]['tmp_name'], $optImage);
                }

                $stmt = $conn->prepare("INSERT INTO AnswerOptions (QuestionID, OptionText, IsCorrect, OptionImage) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isis", $questionID, $optText, $isCorrect, $optImage);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($type === "GridIn") {
            $answer = $q['GridAnswer'];
            $stmt = $conn->prepare("INSERT INTO AnswerOptions (QuestionID, OptionText, IsCorrect) VALUES (?, ?, 1)");
            $stmt->bind_param("is", $questionID, $answer);
            $stmt->execute();
            $stmt->close();
        }
    }
}

echo "✅ Test saved successfully!";
?>
