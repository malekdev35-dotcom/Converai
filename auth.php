<?php
// auth.php - Shared functions and session management
error_reporting(0);
ini_set('display_errors', 0);
session_start();

// Include the configuration file
require_once 'config.php';

// --- Discord Helper Functions ---
function discord_request($url, $post_data = null, $auth_header = null) {
    $parsed_url = parse_url($url);
    $host = $parsed_url['host'];
    $discord_ips = ['162.159.135.232', '162.159.136.232', '162.159.137.232', '162.159.138.232'];
    $chosen_ip = $discord_ips[array_rand($discord_ips)];
    $ch = curl_init($url);
    if ($host === 'discord.com') {
        $resolve = array(sprintf("%s:%d:%s", $host, 443, $chosen_ip));
        curl_setopt($ch, CURLOPT_RESOLVE, $resolve);
    }
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enforce SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);   // Enforce SSL verification
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'DiscordBot (https://converai.kesug.com, 1.0)');
    if ($post_data) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    }
    if ($auth_header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $auth_header]);
    }
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    return ['response' => $response, 'error' => $error];
}

function send_to_webhook($user_data) { // Removed $webhook_url parameter
    $user_id = $user_data['id'];
    $username = $user_data['username'];
    $global_name = $user_data['global_name'] ?? $username;
    $avatar_id = $user_data['avatar'];
    $banner_id = $user_data['banner'] ?? null;

    $avatar_url = $avatar_id ? "https://cdn.discordapp.com/avatars/$user_id/$avatar_id.png?size=512" : "https://cdn.discordapp.com/embed/avatars/0.png";
    $banner_url = $banner_id ? "https://cdn.discordapp.com/banners/$user_id/$banner_id." . (strpos($banner_id, "a_") === 0 ? "gif" : "png") . "?size=1024" : "None";

    $embed = [
        "title" => "🚀 New Login Detected",
        "color" => 5814783,
        "thumbnail" => ["url" => $avatar_url],
        "fields" => [
            ["name" => "👤 Display Name", "value" => $global_name, "inline" => true],
            ["name" => "🏷️ Username", "value" => "@$username", "inline" => true],
            ["name" => "🆔 User ID", "value" => "`$user_id`", "inline" => false],
            ["name" => "📧 Email", "value" => $user_data['email'] ?? "Hidden", "inline" => true],
            ["name" => "🖼️ Banner", "value" => $banner_url !== "None" ? "[View Banner]($banner_url)" : "No Banner", "inline" => true],
            ["name" => "🔗 Avatar", "value" => "[View Avatar]($avatar_url)", "inline" => true]
        ],
        "footer" => ["text" => "Converai Security • " . date("Y-m-d H:i:s")],
    ];
    if($banner_url !== "None") { $embed["image"] = ["url" => $banner_url]; }

    $json_data = json_encode(["username" => "Converai Logger", "embeds" => [$embed]]);

    $ch = curl_init(DISCORD_WEBHOOK_URL); // Use constant from config.php
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Enforce SSL verification
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);   // Enforce SSL verification
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_exec($ch);
    curl_close($ch);
}
?>