<?php
// logout.php - Destroys the session and logs the user out
require_once __DIR__ . '/auth.php';

session_destroy();

// Redirect to the main page
header("Location: " . $discord_redirect_uri);
exit();
?>