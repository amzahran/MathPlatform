<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mathplatform";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die(json_encode(['status' => 'error', 'message' => $conn->connect_error]));
}

$testID = $_POST['testID'] ?? null;
$testTitle = $_POST['TestTitle'] ?? '';
$testType = $_POST['TestType'] ?? '';
$category = $_POST['Category'] ?? '';
$price = floatval($_POST['Price'] ?? 0);
$breakDuration = intval($_POST['BreakDuration'] ?? 0);
$updatedAt = date("Y-m-d H:i:s");

if (!$testID) {
  echo json_encode(['status' => 'error', 'message' => 'Missing test ID']);
  exit;
}

// Update test data
$conn->query("UPDATE Tests SET TestTitle='$testTitle', TestType='$testType', CategoryTest='$category', Price=$price, BreakDuration=$breakDuration, UpdatedAt='$updatedAt' WHERE TestID=$testID");

$sections = json_decode($_POST['Sections'], true);

foreach ($sections as $secIndex => $section) {
  $sectionNumber = intval($section['SectionNumber']);
  $sectionTitle = $conn->real_escape_string($section['SectionTitle']);
  $numQuestions = intval($section['NumberOfQuestions']);
  $duration = intval($section['Duration']);

  $res = $conn->query("SELECT SectionID FROM Sections WHERE TestID=$testID AND SectionNumber=$sectionNumber");
  if ($row = $res->fetch_assoc()) {
    $sectionID = $row['SectionID'];
    $conn->query("UPDATE Sections SET SectionTitle='$sectionTitle', NumberOfQuestions=$numQuestions, Duration=$duration WHERE SectionID=$sectionID");
  } else {
    $conn->query("INSERT INTO Sections (TestID, SectionNumber, SectionTitle, NumberOfQuestions, Duration) VALUES ($testID, $sectionNumber, '$sectionTitle', $numQuestions, $duration)");
    $sectionID = $conn->insert_id;
  }

  $conn->query("DELETE FROM Questions WHERE SectionID=$sectionID");

  foreach ($section['Questions'] as $qIndex => $question) {
    $questionText = $conn->real_escape_string($question['QuestionText']);
    $questionType = $conn->real_escape_string($question['QuestionType']);
    $imgKey = $question['QuestionImage'] ?? '';
    $imgPath = '';
    $targetDir = "uploads/";

    if ($imgKey && isset($_FILES[$imgKey]) && $_FILES[$imgKey]['error'] === 0) {
      $imgPath = $targetDir . basename($_FILES[$imgKey]['name']);
      move_uploaded_file($_FILES[$imgKey]['tmp_name'], $imgPath);
    } else if (strpos($imgKey, 'http') === 0 || strpos($imgKey, 'uploads/') === 0) {
      $imgPath = $imgKey;
    }

    $conn->query("INSERT INTO Questions (SectionID, QuestionText, QuestionType, QuestionImage) VALUES ($sectionID, '$questionText', '$questionType', '$imgPath')");
    $questionID = $conn->insert_id;

    foreach ($question['AnswerOptions'] as $optIndex => $option) {
      $optText = trim($option['OptionText'] ?? '');
      $optImgKey = $option['OptionImage'] ?? '';
      $isCorrect = isset($option['IsCorrect']) && $option['IsCorrect'] ? 1 : 0;
      $optImgPath = '';

      // ✅ تخطي الاختيارات الفارغة كليًا
      if ($optText === '' && $optImgKey === '') continue;

      if ($optImgKey && isset($_FILES[$optImgKey]) && $_FILES[$optImgKey]['error'] === 0) {
        $optImgPath = $targetDir . basename($_FILES[$optImgKey]['name']);
        move_uploaded_file($_FILES[$optImgKey]['tmp_name'], $optImgPath);
      } else if (strpos($optImgKey, 'http') === 0 || strpos($optImgKey, 'uploads/') === 0) {
        $optImgPath = $optImgKey;
      }

      $conn->query("INSERT INTO AnswerOptions (QuestionID, OptionText, OptionImage, IsCorrect) VALUES ($questionID, '$optText', '$optImgPath', $isCorrect)");
    }
  }
}

$conn->close();
echo json_encode(['status' => 'success', 'message' => 'Test saved successfully']);
?>