<?php
$serverName = "127.0.0.1\\sql";
$connectionOptions = array(
  "Database" => "Platform",
  "Uid" => "sa",
  "PWD" => "123123"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if (!$conn) {
    die(json_encode(["error" => "Connection failed", "details" => sqlsrv_errors()]));
}
?>
