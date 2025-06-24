<?php
header('Content-Type: application/json');
include 'db_connection.php';

$sql = "SELECT * FROM tests";
$result = $conn->query($sql);

$tests = [];

while ($row = $result->fetch_assoc()) {
    $tests[] = $row;
}

echo json_encode(["status" => "success", "data" => $tests]);
?>
