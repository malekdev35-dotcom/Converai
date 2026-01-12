<?php
// stats.php - Handles interaction tracking
require_once __DIR__ . '/auth.php';

$stats_file = __DIR__ . '/site_stats.json';

// Initialize stats file if it doesn't exist
if (!file_exists($stats_file)) {
    $initial_data = [
        'unique_visitors' => [],
        'total_interactions' => 0,
        'users_data' => []
    ];
    file_put_contents($stats_file, json_encode($initial_data));
}

// Track unique visitor by IP
$current_ip = $_SERVER['REMOTE_ADDR'];
$stats_data = json_decode(file_get_contents($stats_file), true);
if (!in_array($current_ip, $stats_data['unique_visitors'])) {
    $stats_data['unique_visitors'][] = $current_ip;
    file_put_contents($stats_file, json_encode($stats_data));
}

// Endpoint to update stats on message send (called from frontend)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['discord_user'])) {
    $user = $_SESSION['discord_user'];
    $uid = $user['id'];

    // Reload data to prevent race conditions
    $stats_data = json_decode(file_get_contents($stats_file), true);

    // Update total interactions
    $stats_data['total_interactions']++;

    // Update user-specific data
    if (!isset($stats_data['users_data'][$uid])) {
        $stats_data['users_data'][$uid] = [
            'username' => $user['username'],
            'email' => $user['email'] ?? 'N/A',
            'global_name' => $user['global_name'] ?? $user['username'],
            'msg_count' => 0,
            'last_active' => date('Y-m-d H:i:s')
        ];
    }
    $stats_data['users_data'][$uid]['msg_count']++;
    $stats_data['users_data'][$uid]['last_active'] = date('Y-m-d H:i:s');

    file_put_contents($stats_file, json_encode($stats_data));

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// If just visited, we don't return anything, just track the IP.
http_response_code(204); // No Content
?>