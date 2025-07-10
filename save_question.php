<?php
include("db-connection.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid request."]);
    exit;
}

try {
    $sectionID = $_POST['sectionID'] ?? null;
    $questionID = $_POST['questionID'] ?? null;
    $questionText = $_POST['questionText'] ?? '';
    $questionType = $_POST['questionType'] ?? '';
    $score = $_POST['score'] ?? 15;
    $explanation = $_POST['explanation'] ?? '';
    $correctAnswerIndex = $_POST['correctAnswerIndex'] ?? null;

    if (!$sectionID || !$questionText || !$questionType) {
        throw new Exception("Missing required fields: sectionID, questionText, or questionType.");
    }

    function uploadFile($file, $prefix) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        $uploadDir = 'Uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $fileName = $prefix . '_' . uniqid() . '_' . basename($file['name']);
        $filePath = $uploadDir . $fileName;
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $filePath;
        }
        throw new Exception("Failed to upload file: $fileName");
    }

    $questionImage = isset($_FILES['questionImage']) ? uploadFile($_FILES['questionImage'], 'question') : null;
    $explanationImage = isset($_FILES['explanationImage']) ? uploadFile($_FILES['explanationImage'], 'explanation') : null;

    if ($questionID) {
        $stmt = $conn->prepare("UPDATE questions SET SectionID=?, QuestionText=?, QuestionType=?, QuestionImage=?, Explanation=?, ExplanationImage=?, Score=? WHERE QuestionID=?");
        $stmt->bind_param("isssssii", $sectionID, $questionText, $questionType, $questionImage, $explanation, $explanationImage, $score, $questionID);
        $stmt->execute();
        $stmt->close();

        $conn->query("DELETE FROM answeroptions WHERE QuestionID = $questionID");
    } else {
        $stmt = $conn->prepare("INSERT INTO questions (SectionID, QuestionText, QuestionType, QuestionImage, Explanation, ExplanationImage, Score) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssi", $sectionID, $questionText, $questionType, $questionImage, $explanation, $explanationImage, $score);
        $stmt->execute();
        $questionID = $stmt->insert_id;
        $stmt->close();
    }

    if ($questionType === 'MCQ') {
        $options = json_decode($_POST['Options'], true);
        if (!$options) {
            throw new Exception("Failed to parse MCQ options.");
        }
        foreach ($options as $index => $option) {
            $optionText = $option['OptionText'];
            $optionImage = isset($_FILES[$option['OptionImage']]) ? uploadFile($_FILES[$option['OptionImage'], "option_$index") : null;
            $isCorrect = ($index == $correctAnswerIndex) ? 1 : 0;
            $stmt = $conn->prepare("INSERT INTO answeroptions (QuestionID, OptionText, OptionImage, IsCorrect) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("issi", $questionID, $optionText, $optionImage, $isCorrect);
            $stmt->execute();
            $stmt->close();
        }
    } elseif ($questionType === 'GridIn') {
        $gridAnswer = $_POST['GridAnswer'] ?? '';
        $stmt = $conn->prepare("INSERT INTO answeroptions (QuestionID, OptionText, IsCorrect) VALUES (?, ?, 1)");
        $stmt->bind_param("is", $questionID, $gridAnswer);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(["status" => "success", "message" => "Question saved.", "questionID" => $questionID]);
} catch (Exception $e) {
    error_log("Error in save_question.php: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Failed to save question: " . $e->getMessage()]);
}
?>