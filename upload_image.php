<?php
$data = json_decode(file_get_contents("php://input"), true);
$imageData = $data['image'];

if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
    $imageData = substr($imageData, strpos($imageData, ',') + 1);
    $type = strtolower($type[1]);
    if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Unsupported image type']);
        exit;
    }

    $imageData = base64_decode($imageData);
    if ($imageData === false) {
        http_response_code(400);
        echo json_encode(['error' => 'Base64 decode failed']);
        exit;
    }

    $filename = 'uploads/' . uniqid() . '.' . $type;
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
    }

    file_put_contents($filename, $imageData);
    echo json_encode(['imagePath' => $filename]);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image data']);
}
