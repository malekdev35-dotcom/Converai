<?php
// share.php - Creates shareable links for conversations
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$raw_input = file_get_contents('php://input');
$json_input = json_decode($raw_input, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($json_input['action']) && $json_input['action'] === 'create_share') {
    if (empty($json_input['conversation'])) {
        echo json_encode(['error' => 'No conversation data']);
        exit;
    }

    $shares_dir = __DIR__ . '/shares';
    if (!is_dir($shares_dir)) {
        mkdir($shares_dir, 0755, true);
        file_put_contents($shares_dir . '/index.php', '<?php // Silence');
    }

    $share_id = bin2hex(random_bytes(8));
    $filename = $shares_dir . '/' . $share_id . '.json';

    $author = 'Anonymous';
    if (isset($_SESSION['discord_user']['username'])) {
        $author = $_SESSION['discord_user']['username'];
    }

    $data_to_save = [
        'created_at' => date('Y-m-d H:i:s'),
        'author' => $author,
        'conversation' => $json_input['conversation']
    ];

    if (file_put_contents($filename, json_encode($data_to_save))) {
        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . strtok($_SERVER["REQUEST_URI"], '?');
        // Construct the URL to point to the new index.html
        $share_url = rtrim(dirname($base_url), '/') . '/index.html?view=' . $share_id;
        echo json_encode(['success' => true, 'share_url' => $share_url]);
    } else {
        echo json_encode(['error' => 'Failed to write file']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid request.']);
?>