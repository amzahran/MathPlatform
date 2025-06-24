<?php
$serverName = "127.0.0.1\\sql";
$connectionOptions = array(
  "Database" => "Platform",
  "Uid" => "sa",
  "PWD" => "123123"
);
$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
  die("Connection failed");
}

$id = $_POST['id'];
$query = "SELECT Url FROM Resources WHERE ID = ?";
$stmt = sqlsrv_query($conn, $query, [$id]);
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if ($row && file_exists($row["Url"])) {
    unlink($row["Url"]);
}

$sql = "DELETE FROM Resources WHERE ID = ?";
$stmt = sqlsrv_query($conn, $sql, [$id]);
echo $stmt ? "success" : "error";
?>