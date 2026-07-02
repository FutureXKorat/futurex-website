<?php
include_once 'database.php';

$clientId    = getenv('GOOGLE_CLIENT_ID');
$redirectUri = 'https://www.futurexthailand.com/google_callback.php';
$action      = in_array($_GET['action'] ?? '', ['login', 'link']) ? $_GET['action'] : 'login';

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state']  = $state;
$_SESSION['google_oauth_action'] = $action;

$params = http_build_query([
    'client_id'     => $clientId,
    'redirect_uri'  => $redirectUri,
    'response_type' => 'code',
    'scope'         => 'openid email profile',
    'state'         => $state,
    'prompt'        => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
exit();
