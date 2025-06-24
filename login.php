<?php
session_start();
header('Content-Type: application/json');

$serverName = "ahmedzahran\SQL";
$connectionOptions = [
    "Database" => "platform",
    "Uid" => "sa",
    "PWD" => "123123"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);
if (!$conn) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$username = $data["username"];
$password = $data["password"];

$sql = "SELECT ID, Password FROM Users WHERE Username = ?";
$params = [$username];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(["status" => "error", "message" => "Query failed"]);
    exit;
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if ($row && password_verify($password, $row["Password"])) {
    $_SESSION["user_id"] = $row["ID"];
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "redirect" => "viewer_user_Computerized_Test_List_DB.html"
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid username or password."]);
}
?>
