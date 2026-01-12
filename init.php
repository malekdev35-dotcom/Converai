<?php
// init.php - Provides initial application state to the frontend
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// --- Security Headers ---
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

$admin_email_target = "devmalek7@gmail.com";
$stats_file = __DIR__ . '/site_stats.json';

// --- Initialize State ---
$response_data = [
    'is_logged_in' => false,
    'is_admin' => false,
    'is_share_mode' => false,
    'user' => null,
    'share_data' => [
        'conversation' => null,
        'error' => null
    ],
    'dev_display' => [
        'name' => 'Malek',
        'username' => 'Developer',
        'avatar' => 'https://cdn.discordapp.com/embed/avatars/0.png',
        'banner' => 'https://files.catbox.moe/placeholder_banner.jpg',
    ],
    'stats' => [
        'unique_visitors' => 0,
        'total_interactions' => 0,
        'users_data' => [],
    ]
];

// --- Check for Shared Conversation ---
if (isset($_GET['view']) && !empty($_GET['view']) && $_GET['view'] !== 'admin') {
    $share_id = basename($_GET['view']);
    $file_path = __DIR__ . '/shares/' . $share_id . '.json';
    if (file_exists($file_path)) {
        $json_content = file_get_contents($file_path);
        $share_data = json_decode($json_content, true);
        if ($share_data && isset($share_data['conversation'])) {
            $response_data['is_share_mode'] = true;
            $response_data['share_data']['conversation'] = $share_data['conversation'];
        } else {
            $response_data['share_data']['error'] = "Corrupted share file.";
        }
    } else {
        $response_data['share_data']['error'] = "This shared conversation does not exist.";
    }
}

// --- Check User Session ---
if (isset($_SESSION['discord_user'])) {
    $response_data['is_logged_in'] = true;
    $response_data['user'] = [
        'id' => $_SESSION['discord_user']['id'],
        'username' => $_SESSION['discord_user']['username'],
        'avatar' => $_SESSION['discord_user']['avatar']
    ];
    if (isset($_SESSION['discord_user']['email']) && $_SESSION['discord_user']['email'] === $admin_email_target) {
        $response_data['is_admin'] = true;
    }
}

// --- Load Developer Info ---
if (file_exists('dev_config.json')) {
    $saved_dev = json_decode(file_get_contents('dev_config.json'), true);
    if ($saved_dev) {
        $response_data['dev_display']['name'] = $saved_dev['name'];
        $response_data['dev_display']['username'] = $saved_dev['username'];
        if($saved_dev['avatar']) $response_data['dev_display']['avatar'] = $saved_dev['avatar'];
        if($saved_dev['banner']) $response_data['dev_display']['banner'] = $saved_dev['banner'];
    }
}

// --- Load Stats (Only for Admin View) ---
if (isset($_GET['view']) && $_GET['view'] === 'admin' && $response_data['is_admin']) {
    if (file_exists($stats_file)) {
        $current_stats = json_decode(file_get_contents($stats_file), true);

        // Ensure keys exist
        $unique_visitors = $current_stats['unique_visitors'] ?? [];
        $total_interactions = $current_stats['total_interactions'] ?? 0;
        $users_data = $current_stats['users_data'] ?? [];

        // Sort users by last active time
        uasort($users_data, function($a, $b) {
            return strtotime($b['last_active']) - strtotime($a['last_active']);
        });

        $response_data['stats'] = [
            'unique_visitors' => count($unique_visitors),
            'total_interactions' => $total_interactions,
            'users_data' => $users_data,
        ];
    }
}


echo json_encode($response_data);
?>