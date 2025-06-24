<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$serverName = "127.0.0.1\\sql";
$connectionOptions = array(
  "Database" => "Platform",
  "Uid" => "sa",
  "PWD" => "123123"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "❌ Database connection failed", "details" => sqlsrv_errors()]);
  exit;
}

$isAdmin = isset($_GET["admin"]) && $_GET["admin"] === "1";
$sql = $isAdmin ? "SELECT * FROM Resources" : "SELECT * FROM Resources WHERE IsPublic = 1";

$stmt = sqlsrv_query($conn, $sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["status" => "error", "message" => "❌ Query failed", "details" => sqlsrv_errors()]);
  exit;
}

$resources = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
  if (isset($row["FileName"])) {
    $row["url"] = "uploads/" . $row["FileName"];
  }
  $resources[] = $row;
}

echo json_encode(["status" => "success", "data" => $resources]);
?>