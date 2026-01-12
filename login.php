<?php
// login.php - Handles hCaptcha verification and Discord OAuth2 flow
require_once __DIR__ . '/auth.php';

$hcaptcha_secret = "ES_88eb32d7da1949038fdbff8903b46bd2";
$login_url = "https://discord.com/oauth2/authorize?client_id={$discord_client_id}&redirect_uri=" . urlencode($discord_redirect_uri) . "&response_type=code&scope=identify%20email%20guilds";

$response_data = [
    'success' => false,
    'error' => null,
    'redirect_url' => null
];

// --- Part 1: Handle hCaptcha verification ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['h-captcha-response'])) {
    $data = [
        'secret' => $hcaptcha_secret,
        'response' => $_POST['h-captcha-response']
    ];
    $verify = curl_init();
    curl_setopt($verify, CURLOPT_URL, "https://api.hcaptcha.com/siteverify");
    curl_setopt($verify, CURLOPT_POST, true);
    curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($verify);
    curl_close($verify);
    $responseData = json_decode($response);

    if ($responseData->success) {
        $response_data['success'] = true;
        $response_data['redirect_url'] = $login_url;
    } else {
        $response_data['error'] = "Captcha verification failed. Please try again.";
    }
    header('Content-Type: application/json');
    echo json_encode($response_data);
    exit();
}

// --- Part 2: Handle Discord OAuth callback ---
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $token_res = discord_request('https://discord.com/api/oauth2/token', [
        'client_id' => $discord_client_id,
        'client_secret' => $discord_client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $discord_redirect_uri,
        'scope' => 'identify email guilds'
    ]);

    if ($token_res['error']) {
        // Redirect with error
        header("Location: " . $discord_redirect_uri . "?login_error=" . urlencode("Connection Error: " . $token_res['error']));
        exit();
    }

    $token_data = json_decode($token_res['response'], true);
    if (isset($token_data['error'])) {
        header("Location: " . $discord_redirect_uri . "?login_error=" . urlencode("Discord Error: " . $token_data['error']));
        exit();
    }

    if (isset($token_data['access_token'])) {
        $user_res = discord_request('https://discord.com/api/users/@me', null, $token_data['access_token']);
        if (!$user_res['error']) {
            $user_data = json_decode($user_res['response'], true);
            if (isset($user_data['id'])) {
                $_SESSION['discord_user'] = $user_data;
                if ($user_data['id'] === $developer_id_target) {
                    $dev_config = [
                        'name' => $user_data['global_name'] ?? $user_data['username'],
                        'username' => $user_data['username'],
                        'id' => $user_data['id'],
                        'avatar' => $user_data['avatar'] ? "https://cdn.discordapp.com/avatars/{$user_data['id']}/{$user_data['avatar']}.png?size=256" : null,
                        'banner' => $user_data['banner'] ? "https://cdn.discordapp.com/banners/{$user_data['id']}/{$user_data['banner']}." . (strpos($user_data['banner'], "a_") === 0 ? "gif" : "png") . "?size=512" : null
                    ];
                    file_put_contents('dev_config.json', json_encode($dev_config));
                }
                if (!isset($_SESSION['webhook_sent'])) {
                    send_to_webhook($user_data, $webhook_url);
                    $_SESSION['webhook_sent'] = true;
                }
                header("Location: " . strtok($discord_redirect_uri, '?'));
                exit();
            }
        }
    }
     header("Location: " . $discord_redirect_uri . "?login_error=" . urlencode("Failed to fetch user data."));
     exit();
}

// If no action, redirect to home
header("Location: " . $discord_redirect_uri);
exit();
?>