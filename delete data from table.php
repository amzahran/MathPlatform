<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mathplatform";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// تأكد من الترتيب لأنه يوجد قيود foreign key
$conn->query("DELETE FROM AnswerOptions");
$conn->query("DELETE FROM Questions");
$conn->query("DELETE FROM Sections");
$conn->query("DELETE FROM Tests");

// إعادة تصفير auto_increment
$conn->query("ALTER TABLE AnswerOptions AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE Questions AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE Sections AUTO_INCREMENT = 1");
$conn->query("ALTER TABLE Tests AUTO_INCREMENT = 1");

$conn->close();
echo "✅ All data cleared and IDs reset.";
?>
