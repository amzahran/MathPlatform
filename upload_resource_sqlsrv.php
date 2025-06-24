<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = isset($_POST['fileType']) ? $_POST['fileType'] : '';
    $category = isset($_POST['category']) ? $_POST['category'] : '';
    $isPublic = isset($_POST['isPublic']) ? 1 : 0;
    $link = isset($_POST['linkInput']) ? $_POST['linkInput'] : null;
    $fileName = '';

    // Normalize type
    switch (strtolower($type)) {
        case 'pdf': case 'book': $type = 'Book'; break;
        case 'word': $type = 'Book'; break;
        case 'test-pdf': case 'test-word': case 'test': $type = 'Test'; break;
        case 'video': $type = 'Video'; break;
        case 'image': $type = 'Picture'; break;
        case 'link': $type = 'Other (Web Link)'; break;
    }

    // Normalize category
    switch (strtoupper($category)) {
        case 'ACT-I': $category = 'ACT I'; break;
        case 'ACT-II': $category = 'ACT II'; break;
        case 'EST-I': $category = 'EST I'; break;
        case 'EST-II': $category = 'EST II'; break;
        case 'SAT-I': $category = 'Digital SAT'; break;
        case 'UNIV-COURSES': $category = 'University Courses'; break;
    }

    // SQL Server connection
    $serverName = "127.0.0.1\\sql";
    $connectionOptions = array(
        "Database" => "Platform",
        "Uid" => "sa",
        "PWD" => "123123"
    );
    $conn = sqlsrv_connect($serverName, $connectionOptions);

    if (!$conn) {
        die("❌ Connection failed: " . print_r(sqlsrv_errors(), true));
    }

    // Handle file upload
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['fileUpload']['name']);
        $targetPath = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES['fileUpload']['tmp_name'], $targetPath)) {
            echo '❌ Failed to move uploaded file.';
            exit;
        }
        $url = $targetPath;
    } else if ($link) {
        $url = $link;
    } else {
        $error = $_FILES['fileUpload']['error'];
        echo '⚠️ No file or link provided. FILE ERROR CODE: ' . $error;
        exit;
    }

    // Insert into SQL Server
    $sql = "INSERT INTO resources (Name, Type, Category, UploadDate, Url, IsPublic) VALUES (?, ?, ?, GETDATE(), ?, ?)";
    $params = [$fileName ?: $link, $type, $category, $url, $isPublic];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt) {
        echo '✅ Upload successful!';
    } else {
        echo '❌ Upload failed: ';
        print_r(sqlsrv_errors());
    }

    sqlsrv_close($conn);
} else {
    echo '❌ Invalid request.';
}
?>
