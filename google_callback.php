<?php
include_once 'database.php';

$clientId     = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri  = 'https://futurexthailand.com/google_callback.php';

$loginError    = 'login.php';
$settingsError = 'settings.php';

// CSRF check
if (empty($_GET['state']) || $_GET['state'] !== ($_SESSION['google_oauth_state'] ?? '')) {
    error_log('Google OAuth: state mismatch. Got: ' . ($_GET['state'] ?? 'none'));
    header('Location: ' . $loginError);
    exit();
}
unset($_SESSION['google_oauth_state']);

$code   = $_GET['code'] ?? '';
$action = $_SESSION['google_oauth_action'] ?? 'login';
unset($_SESSION['google_oauth_action']);

if (empty($code)) {
    error_log('Google OAuth: no code in callback');
    header('Location: ' . $loginError);
    exit();
}

// ── Exchange code for access token ────────────────────────────────────────
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_POSTFIELDS     => http_build_query([
        'code'          => $code,
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri'  => $redirectUri,
        'grant_type'    => 'authorization_code',
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
]);
$tokenRaw  = curl_exec($ch);
$curlError = curl_error($ch);
$curlErrno = curl_errno($ch);
curl_close($ch);

if ($curlErrno || $tokenRaw === false) {
    error_log("Google OAuth: token exchange curl failed [{$curlErrno}] {$curlError}");
    $_SESSION['flash_google_error'] = ($lang === 'en')
        ? 'Could not connect to Google. Please try again.'
        : 'ไม่สามารถเชื่อมต่อ Google ได้ กรุณาลองใหม่';
    header('Location: ' . ($action === 'link' ? $settingsError : $loginError));
    exit();
}

$tokenData   = json_decode($tokenRaw, true);
$accessToken = $tokenData['access_token'] ?? '';

if (empty($accessToken)) {
    error_log('Google OAuth: no access_token in response: ' . $tokenRaw);
    $_SESSION['flash_google_error'] = ($lang === 'en')
        ? 'Google login failed. Please try again.'
        : 'เข้าสู่ระบบ Google ล้มเหลว กรุณาลองใหม่';
    header('Location: ' . ($action === 'link' ? $settingsError : $loginError));
    exit();
}

// ── Fetch user info ───────────────────────────────────────────────────────
$ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $accessToken],
]);
$userInfoRaw = curl_exec($ch);
$curlError   = curl_error($ch);
$curlErrno   = curl_errno($ch);
curl_close($ch);

if ($curlErrno || $userInfoRaw === false) {
    error_log("Google OAuth: userinfo curl failed [{$curlErrno}] {$curlError}");
    $_SESSION['flash_google_error'] = ($lang === 'en')
        ? 'Could not fetch Google profile. Please try again.'
        : 'ไม่สามารถดึงข้อมูล Google ได้ กรุณาลองใหม่';
    header('Location: ' . ($action === 'link' ? $settingsError : $loginError));
    exit();
}

$userInfo = json_decode($userInfoRaw, true);
$googleId = $userInfo['id']    ?? '';
$email    = $userInfo['email'] ?? '';

if (empty($googleId) || empty($email)) {
    error_log('Google OAuth: missing id or email in userinfo: ' . $userInfoRaw);
    $_SESSION['flash_google_error'] = ($lang === 'en')
        ? 'Google login failed. Please try again.'
        : 'เข้าสู่ระบบ Google ล้มเหลว กรุณาลองใหม่';
    header('Location: ' . ($action === 'link' ? $settingsError : $loginError));
    exit();
}

// ── Link Google to an existing logged-in account ──────────────────────────
if ($action === 'link') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    $userId = (int)$_SESSION['user_id'];

    $chk = $conn->prepare("SELECT id FROM users WHERE google_id = ? AND id != ?");
    $chk->bind_param("si", $googleId, $userId);
    $chk->execute();
    $chk->store_result();
    $alreadyTaken = $chk->num_rows > 0;
    $chk->close();

    if ($alreadyTaken) {
        $_SESSION['flash_google_error'] = ($lang === 'en')
            ? 'That Google account is already linked to a different account.'
            : 'บัญชี Google นี้ถูกเชื่อมต่อกับบัญชีอื่นแล้ว';
    } else {
        $upd = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
        $upd->bind_param("si", $googleId, $userId);
        $upd->execute();
        $upd->close();
        $_SESSION['flash_google_success'] = ($lang === 'en')
            ? 'Google account linked successfully.'
            : 'เชื่อมต่อบัญชี Google สำเร็จแล้ว';
    }
    header('Location: settings.php');
    exit();
}

// ── Login flow ─────────────────────────────────────────────────────────────
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

// 1) Match by google_id (returning Google user)
$stmt = $conn->prepare("SELECT id, username FROM users WHERE google_id = ?");
$stmt->bind_param("s", $googleId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $stmt->close();
    $_SESSION['user_id']  = $row['id'];
    $_SESSION['username'] = $row['username'];
    header('Location: home.php');
    exit();
}
$stmt->close();

// 2) Match by email — auto-link on first Google login
$stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $stmt->close();
    $upd = $conn->prepare("UPDATE users SET google_id = ? WHERE id = ?");
    $upd->bind_param("si", $googleId, $row['id']);
    $upd->execute();
    $upd->close();
    $_SESSION['user_id']  = $row['id'];
    $_SESSION['username'] = $row['username'];
    header('Location: home.php');
    exit();
}
$stmt->close();

// 3) No matching account
$_SESSION['flash_google_error'] = ($lang === 'en')
    ? 'No account found for that Google email. Please register first.'
    : 'ไม่พบบัญชีที่ใช้ Google อีเมลนี้ กรุณาสมัครสมาชิกก่อน';
header('Location: login.php');
exit();
