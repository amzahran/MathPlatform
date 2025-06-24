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

    // Database connection (adjust as needed)
    $pdo = new PDO("sqlsrv:Server=localhost;Database=your_database", "your_username", "your_password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle file upload
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['fileUpload']['name']);
        $targetPath = $uploadDir . $fileName;
        move_uploaded_file($_FILES['fileUpload']['tmp_name'], $targetPath);
        $url = $targetPath;
    } else if ($link) {
        $url = $link;
    } else {
        echo 'No file or link provided.';
        exit;
    }

    // Insert into database
    $stmt = $pdo->prepare("INSERT INTO resources (Name, Type, Category, UploadDate, Url, IsPublic) VALUES (?, ?, ?, GETDATE(), ?, ?)");
    $stmt->execute([$fileName ?: $link, $type, $category, $url, $isPublic]);

    echo '✅ Upload successful!';
} else {
    echo '❌ Invalid request.';
}
?>
