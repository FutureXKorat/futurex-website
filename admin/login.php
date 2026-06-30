<?php
declare(strict_types=1);
include '../database.php';

// Already logged in? Go to admin dashboard
if (isset($_SESSION['admin_id']) || isset($_SESSION['user_id'])) {
    header('Location: /admin/index.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim((string)($_POST['login'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = $lang === 'th' ? 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน' : 'Please enter your username/email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, password FROM admins WHERE username = ? OR email = ? LIMIT 1");
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row && password_verify($password, (string)$row['password'])) {
            $_SESSION['admin_id'] = (int)$row['id'];
            header('Location: /admin/index.php'); exit;
        } else {
            $error = $lang === 'th' ? 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง' : 'Incorrect username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $lang === 'th' ? 'เข้าสู่ระบบแอดมิน — Future X' : 'Admin Login — Future X' ?></title>
  <link rel="icon" type="image/png" href="/logo_transparent_onlyblack.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Inter', sans-serif;
      padding: 20px;
    }
    @supports (min-height: 100dvh) { body { min-height: 100dvh; } }
    .login-card {
      background: rgba(255,255,255,0.7);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 40px 36px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.12);
      width: 100%;
      max-width: 400px;
    }
    .login-logo { display: block; height: 48px; margin: 0 auto 24px; }
    h1 { font-size: 1.4rem; font-weight: 700; text-align: center; margin-bottom: 4px; }
    .subtitle { font-size: .84rem; color: #666; text-align: center; margin-bottom: 28px; }
    label { font-size: .88rem; font-weight: 600; }
    .form-control { border-radius: 10px; }
    .btn-signin {
      background: #007BFF; color: #fff; border: none;
      width: 100%; padding: 12px; font-weight: 600;
      border-radius: 10px; font-size: .95rem; cursor: pointer;
      transition: background .2s;
    }
    .btn-signin:hover { background: #0056b3; }
    .back-link { display: block; text-align: center; margin-top: 18px; font-size: .84rem; color: #888; text-decoration: none; }
    .back-link:hover { color: #007BFF; }
  </style>
</head>
<body>
<div class="login-card">
  <img src="/logo_transparent.png" alt="FutureX" class="login-logo">
  <h1><?= $lang === 'th' ? 'เข้าสู่ระบบแอดมิน' : 'Admin Login' ?></h1>
  <p class="subtitle"><?= $lang === 'th' ? 'สำหรับพนักงานแอดมิน' : 'Employee admin sign-in' ?></p>

  <?php if ($error !== ''): ?>
    <div class="alert alert-danger py-2" style="font-size:.88rem;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="post" novalidate>
    <div class="mb-3">
      <label for="login"><?= $lang === 'th' ? 'ชื่อผู้ใช้ หรือ อีเมล' : 'Username or Email' ?></label>
      <input
        type="text"
        id="login"
        name="login"
        class="form-control"
        autocomplete="username"
        value="<?= htmlspecialchars((string)($_POST['login'] ?? '')) ?>"
        required
      >
    </div>
    <div class="mb-4">
      <label for="password"><?= $lang === 'th' ? 'รหัสผ่าน' : 'Password' ?></label>
      <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-signin">
      <?= $lang === 'th' ? 'เข้าสู่ระบบ' : 'Sign In' ?>
    </button>
  </form>

  <a href="https://futurexthailand.com/index.php" class="back-link">
    ← <?= $lang === 'th' ? 'กลับหน้าหลัก' : 'Back to main site' ?>
  </a>
</div>
</body>
</html>
