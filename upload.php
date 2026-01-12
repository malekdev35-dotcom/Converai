<?php
// upload.php - Handles image uploads
require_once __DIR__ . '/auth.php';

if (!isset($_SESSION['discord_user'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (isset($_FILES['image_upload'])) {
    $upload_dir = __DIR__ . '/uploads';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $file = $_FILES['image_upload'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array(strtolower($ext), $allowed)) {
        echo json_encode(['error' => 'Only images allowed']);
        exit;
    }

    $filename = uniqid('img_') . '.' . $ext;
    $target_path = $upload_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $base_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
        $base_url = rtrim($base_url, '/');
        echo json_encode(['success' => true, 'url' => $base_url . '/uploads/' . $filename]);
    } else {
        echo json_encode(['error' => 'Upload failed']);
    }
    exit;
}

echo json_encode(['error' => 'No file uploaded.']);
?>