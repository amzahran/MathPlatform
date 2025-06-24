<?php
// الاتصال بقاعدة بيانات SQL Server
$serverName = "127.0.0.1\\sql"; // لاحظ أن \ يجب أن تُكتب مرتين
$connectionOptions = array(
    "Database" => "platform",
    "Uid" => "sa",
    "PWD" => "123123",
    "CharacterSet" => "UTF-8"
);

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    echo json_encode([
        "status" => "error",
        "message" => sqlsrv_errors()
    ]);
    exit;
}

// طباعة نجاح الاتصال
echo json_encode([
    "status" => "success",
    "message" => "تم الاتصال بنجاح بقاعدة البيانات."
]);
?>