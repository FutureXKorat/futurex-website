<?php
session_start();
include 'database.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$token = $_GET['token'] ?? '';
$mode  = !empty($token) ? 'reset' : 'forgot';

$texts = [
    'en' => [
        'title_forgot' => 'Reset Password - Future X',
        'h2_forgot'    => 'Reset Password',
        'enter'        => 'Enter your username',
        'send'         => 'Send Reset Link',
        'back'         => 'Back to Login Page',
        'sending'      => 'Sending...',
        'title_reset'  => 'New Password - Future X',
        'h2_reset'     => 'Set New Password',
        'pass'         => 'New Password',
        'confirm'      => 'Confirm Password',
        'update'       => 'Update Password',
        'updating'     => 'Updating...',
        'lang'         => 'ภาษาไทย',
    ],
    'th' => [
        'title_forgot' => 'เปลี่ยนรหัสผ่าน - Future X',
        'h2_forgot'    => 'เปลี่ยนรหัส',
        'enter'        => 'ป้อนชื่อผู้ใช้ของคุณ',
        'send'         => 'ส่งลิงค์รีเซ็ตรหัสผ่าน',
        'back'         => 'กลับไปที่หน้าเข้าสู่ระบบ',
        'sending'      => 'กำลังส่ง...',
        'title_reset'  => 'รหัสผ่านใหม่ - Future X',
        'h2_reset'     => 'ตั้งรหัสผ่านใหม่',
        'pass'         => 'รหัสผ่านใหม่',
        'confirm'      => 'ยืนยันรหัสผ่าน',
        'update'       => 'อัปเดตรหัสผ่าน',
        'updating'     => 'กำลังอัปเดต...',
        'lang'         => 'English',
    ],
];

$errors  = [];
$success = '';

// ── RESET MODE (token in URL) ──────────────────────────────────────────────
if ($mode === 'reset') {
    $stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    if (!$stmt) {
        die("Database error. Please request a new reset link.");
    }
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if (!$result || $result->num_rows === 0) {
        die("Invalid or expired token. Please <a href='forgot_password.php'>request a new reset link</a>.");
    }

    $user = $result->fetch_assoc();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password         = $_POST['password']         ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($password) < 8) {
            $errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร' : 'Password must be at least 8 characters.';
        }
        if (!preg_match('/\d/', $password)) {
            $errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีอย่างน้อย 1 ตัวเลข' : 'Password must contain at least one number.';
        }
        if ($password !== $confirm_password) {
            $errors[] = ($lang === 'th') ? 'รหัสผ่านไม่ตรงกัน' : 'Passwords do not match.';
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            if (!$upd) {
                $errors[] = ($lang === 'th') ? 'เกิดข้อผิดพลาด กรุณาลองอีกครั้ง' : 'Database error. Please try again.';
            } else {
                $upd->bind_param("si", $hashed, $user['id']);
                if ($upd->execute()) {
                    $upd->close();
                    header("Location: login.php?reset=success");
                    exit();
                } else {
                    $upd->close();
                    $errors[] = ($lang === 'th') ? 'ไม่สามารถอัปเดตรหัสผ่านได้' : 'Failed to update password. Please try again.';
                }
            }
        }
    }

// ── FORGOT MODE (no token) ────────────────────────────────────────────────
} else {
    include 'send_reset_email.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');

        $stmt = $conn->prepare("SELECT id, email FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result && $result->num_rows > 0) {
            $row     = $result->fetch_assoc();
            $tkn     = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime('+30 minutes'));

            $upd = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            $upd->bind_param("ssi", $tkn, $expires, $row['id']);
            $upd->execute();
            $upd->close();

            if (sendResetEmail($row['email'], $tkn)) {
                $success = ($lang === 'th')
                    ? 'ลิงค์รีเซ็ตรหัสผ่านได้ถูกส่งไปยังอีเมล์ของคุณแล้ว'
                    : 'A password reset link has been sent to your email.';
            } else {
                $errors[] = ($lang === 'th') ? 'ไม่สามารถส่งอีเมลได้ กรุณาลองอีกครั้ง' : 'Failed to send the email. Please try again.';
            }
        } else {
            $errors[] = ($lang === 'th') ? 'ไม่เจอบัญชีที่มี Username นี้' : 'No account found with that username.';
        }
    }
}

$langToggle = ($lang === 'en') ? 'th' : 'en';
$langHref   = ($mode === 'reset')
    ? '?token=' . urlencode($token) . '&lang=' . $langToggle
    : '?lang=' . $langToggle;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($texts[$lang][$mode === 'reset' ? 'title_reset' : 'title_forgot']); ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand-color: #007BFF;
            --brand-hover: #0056b3;
            --brand-deep:  #003f7f;
            --ink: #111111;
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: var(--ink);
            padding: 40px 20px;
        }
        .form-container {
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px 35px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 12px 32px rgba(0,0,255,0.15);
            text-align: center;
        }
        .form-container h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--ink);
        }
        .form-control {
            border-radius: 12px;
            padding: 12px;
            font-size: 1rem;
            margin-bottom: 15px;
            border-color: #E5E7EB;
            transition: box-shadow .2s ease, border-color .2s ease;
        }
        .form-control:focus {
            border-color: var(--brand-color);
            box-shadow: 0 0 0 0.25rem rgba(0,0,255,0.25);
            outline: none;
        }
        .btn-modern, .btn-gray {
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 14px;
            border: none;
            color: #fff;
            transition: all 0.3s ease;
            margin-bottom: 15px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
        }
        .btn-modern {
            background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
        }
        .btn-modern:hover {
            background: linear-gradient(135deg, var(--brand-hover), var(--brand-deep));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,204,0.35);
        }
        .btn-gray { background: #6B7280; }
        .btn-gray:hover {
            background: #4B5563;
            transform: translateY(-2px);
            text-decoration: none;
        }
        .lang-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(255,255,255,0.7);
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--brand-color);
        }
        .lang-switch:hover {
            background: rgba(255,255,255,0.9);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
        .pw-wrap { position: relative; }
        .pw-wrap .form-control { padding-right: 2.75rem; margin-bottom: 15px; }
        .pw-wrap .eye-btn {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-70%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            color: #6B7280;
            font-size: 1rem;
            line-height: 1;
            user-select: none;
        }
        .spinner-border { color: var(--brand-color); }
        :focus-visible { outline: 2px solid var(--brand-color); outline-offset: 2px; }
        ::selection { background: rgba(0,123,255,0.2); color: #111827; }
    </style>
</head>
<body>

<a class="lang-switch" href="<?php echo htmlspecialchars($langHref); ?>">
    <?php echo htmlspecialchars($texts[$lang]['lang']); ?>
</a>

<div class="form-container">

<?php if ($mode === 'reset'): ?>

    <h2><?php echo htmlspecialchars($texts[$lang]['h2_reset']); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 text-start">
                <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="?token=<?php echo urlencode($token); ?>&lang=<?php echo htmlspecialchars($lang); ?>" id="resetForm">
        <div class="pw-wrap">
            <input type="password" id="pw1" name="password" class="form-control"
                   placeholder="<?php echo htmlspecialchars($texts[$lang]['pass']); ?>" required>
            <button type="button" class="eye-btn" data-target="pw1">👁</button>
        </div>
        <div class="pw-wrap">
            <input type="password" id="pw2" name="confirm_password" class="form-control"
                   placeholder="<?php echo htmlspecialchars($texts[$lang]['confirm']); ?>" required>
            <button type="button" class="eye-btn" data-target="pw2">👁</button>
        </div>
        <button type="submit" class="btn-modern" id="resetBtn">
            <?php echo htmlspecialchars($texts[$lang]['update']); ?>
        </button>
    </form>

<?php else: ?>

    <h2><?php echo htmlspecialchars($texts[$lang]['h2_forgot']); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars(implode(' ', $errors)); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="post" id="forgotForm">
        <input type="text" name="username" class="form-control"
               placeholder="<?php echo htmlspecialchars($texts[$lang]['enter']); ?>" required>
        <button type="submit" class="btn-modern" id="forgotBtn">
            <?php echo htmlspecialchars($texts[$lang]['send']); ?>
        </button>
    </form>

    <a href="login.php" class="btn-gray"><?php echo htmlspecialchars($texts[$lang]['back']); ?></a>

<?php endif; ?>

</div>

<script>
<?php if ($mode === 'reset'): ?>
document.getElementById('resetForm').addEventListener('submit', function () {
    const btn = document.getElementById('resetBtn');
    btn.disabled = true;
    btn.innerHTML = `<?php echo htmlspecialchars($texts[$lang]['updating']); ?> <span class="spinner-border spinner-border-sm ms-2 text-light"></span>`;
});
document.querySelectorAll('.eye-btn').forEach(function(btn) {
    btn.addEventListener('mousedown', function() {
        document.getElementById(btn.dataset.target).type = 'text';
    });
    btn.addEventListener('mouseup', function() {
        document.getElementById(btn.dataset.target).type = 'password';
    });
    btn.addEventListener('mouseleave', function() {
        document.getElementById(btn.dataset.target).type = 'password';
    });
    btn.addEventListener('touchstart', function(e) {
        e.preventDefault();
        document.getElementById(btn.dataset.target).type = 'text';
    });
    btn.addEventListener('touchend', function() {
        document.getElementById(btn.dataset.target).type = 'password';
    });
});
<?php else: ?>
document.getElementById('forgotForm').addEventListener('submit', function () {
    const btn = document.getElementById('forgotBtn');
    btn.disabled = true;
    btn.innerHTML = `<?php echo htmlspecialchars($texts[$lang]['sending']); ?> <span class="spinner-border spinner-border-sm ms-2 text-light"></span>`;
});
<?php endif; ?>
</script>

</body>
</html>
