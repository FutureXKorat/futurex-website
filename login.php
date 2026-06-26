<?php
session_start();
include 'database.php'; // defines $conn and $lang

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

$texts = [
    'en' => [
        'tabbar'        => 'Log In - Future X',
        'heading'       => 'Log In',
        'username_ph'   => 'Username',
        'password_ph'   => 'Password',
        'login_btn'     => 'Log In',
        'no_account'    => "Don't have an account?",
        'register_here' => 'Register here',
        'forgot_pw'     => 'Forgot password?',
        'reset_here'    => 'Reset here',
        'guest'         => 'Continue as Guest',
        'incorrect_pw'  => 'Incorrect password.',
        'no_account_err'=> 'No account found with that username.',
        'db_error'      => 'Database error. Please try again.',
        'reset_success' => 'Your password has been reset successfully.',
    	'reg_success'   => 'Your account has been created successfully! Please log in.',
        'remember_me'   => 'Remember me today',
        'lang'          => 'ภาษาไทย',
    ],
    'th' => [
        'tabbar'        => 'เข้าสู่ระบบ - Future X',
        'heading'       => 'เข้าสู่ระบบ',
        'username_ph'   => 'ชื่อผู้ใช้',
        'password_ph'   => 'รหัสผ่าน',
        'login_btn'     => 'เข้าสู่ระบบ',
        'no_account'    => 'ยังไม่มีบัญชี?',
        'register_here' => 'สมัครสมาชิก',
        'forgot_pw'     => 'ลืมรหัสผ่าน?',
        'reset_here'    => 'กู้รหัสผ่าน',
        'guest'         => 'เข้าสู่เว็บไซต์แบบไม่ลงชื่อ',
        'incorrect_pw'  => 'รหัสผ่านไม่ถูกต้อง',
        'no_account_err'=> 'ไม่พบบัญชีที่ใช้ชื่อผู้ใช้นี้',
        'db_error'      => 'เกิดข้อผิดพลาดของฐานข้อมูล โปรดลองอีกครั้ง',
        'reset_success' => 'เปลี่ยนรหัสผ่านสำเร็จ',
            'reg_success'   => 'สร้างบัญชีสำเร็จ',
        'remember_me'   => 'จดจำฉันวันนี้',
        'lang'          => 'English',
    ],
];

$errors = [];
$username = "";
$success = "";

// flash success from verify/register
if (!empty($_SESSION['flash_success'])) {
    $flash = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
    $success = isset($texts[$lang][$flash]) ? $texts[$lang][$flash] : $flash;
}

// reset password success
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = $texts[$lang]['reset_success'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];

    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();

            if (!password_verify($password, $user["password"])) {
                $errors[] = $texts[$lang]['incorrect_pw'];
            } else {
                // ✅ generate OTP and store on users table
                $otp = random_int(100000, 999999);
                $upd = $conn->prepare("UPDATE users SET otp_code = ? WHERE id = ?");
                $upd->bind_param("si", $otp, $user["id"]);
                $upd->execute();
                $upd->close();

                // send OTP
                $email = $user['email'];
                include_once 'send_otp.php';
                if (function_exists('sendOtp')) {
                    sendOtp($email, $otp, ($lang === 'th' ? 'รหัส OTP สำหรับเข้าสู่ระบบ' : 'Your Login OTP'));
                } elseif (function_exists('sendOTPEmail')) {
                    sendOTPEmail($email, $otp);
                }

                // carry the "remember me" choice through the OTP step
                $_SESSION['pending_remember'] = !empty($_POST['remember_me']);

                // redirect to verify page
                header("Location: verify_otp.php?username=" . urlencode($username) . "&login=1");
                exit();
            }
        } else {
            $errors[] = $texts[$lang]['no_account_err'];
        }
        $stmt->close();
    } else {
        $errors[] = $texts[$lang]['db_error'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($texts[$lang]['tabbar']); ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Inter font like register.php -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: #111;
            padding: 40px 20px;
        }

        @media (max-width: 460px) {
            body { padding: 80px 20px 40px; }
        }
        @supports (height: 100dvh) {
            body { min-height: 100dvh; }
        }

        .form-container {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px 35px;
            width: 95%;
            max-width: 462px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        .form-container h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px;
            font-size: 1rem;
        }
        .btn-modern {
            display: block;
            width: 100%;
            margin-top: 12px;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 14px;
            transition: all 0.3s ease;
        }
        .btn-modern.btn-primary {
            background: linear-gradient(135deg, #007BFF, #0056b3);
            border: none;
            color: #fff;
        }
        .btn-modern.btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #003f7f);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.35);
        }
        .link-section {
            margin-top: 18px;
            text-align: center;
            font-size: 1rem;
        }
        .link-section a {
            color: #0056b3;
            font-weight: 600;
            text-decoration: none;
        }
        .link-section a:hover {
            color: #003f7f;
            text-decoration: underline;
        }

        .login-link { display: inline; margin-left: 6px; }
        @media (max-width: 460px) {
            .login-link { display: block; margin-left: 0; margin-top: 6px; }
        }

        /* Language button */
        .lang-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.7);
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
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>
<!-- 🔵 Language switch -->
<a class="lang-switch" href="?lang=<?php echo $lang === 'en' ? 'th' : 'en'; ?>">
    <?php echo $texts[$lang]['lang']; ?>
</a>

<div class="form-container">
    <h2><?php echo htmlspecialchars($texts[$lang]['heading']); ?></h2>

    <form method="post" id="loginForm">
        <div class="mb-3">
            <input type="text" name="username" class="form-control"
                   placeholder="<?php echo htmlspecialchars($texts[$lang]['username_ph']); ?>"
                   required value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control"
                   placeholder="<?php echo htmlspecialchars($texts[$lang]['password_ph']); ?>"
                   required>
        </div>
        <div class="mb-3 d-flex align-items-center gap-2">
            <input type="checkbox" name="remember_me" id="remember_me"
                   class="form-check-input" style="width:1.1em;height:1.1em;cursor:pointer;flex-shrink:0;">
            <label for="remember_me" class="mb-0" style="cursor:pointer;font-size:0.95rem;user-select:none;">
                <?php echo htmlspecialchars($texts[$lang]['remember_me']); ?>
            </label>
        </div>
        <button type="submit" class="btn btn-modern btn-primary" id="loginBtn">
            <?php echo htmlspecialchars($texts[$lang]['login_btn']); ?>
        </button>
    </form>

    <div class="link-section">
        <p>
            <?php echo $texts[$lang]['no_account']; ?>
            <a href="register.php" class="login-link"><?php echo $texts[$lang]['register_here']; ?></a>
        </p>
        <p>
            <?php echo $texts[$lang]['forgot_pw']; ?>
            <a href="forgot_password.php"><?php echo $texts[$lang]['reset_here']; ?></a>
        </p>
        <p><a href="home.php"><?php echo $texts[$lang]['guest']; ?></a></p>
    </div>
</div>

<script>
document.getElementById("loginForm").addEventListener("submit", function() {
    const btn = document.getElementById("loginBtn");
    btn.disabled = true;
    btn.innerHTML = `<?php echo $texts[$lang]['login_btn']; ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>`;
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Toasts container (top-right) -->
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
  <?php if (!empty($success)): ?>
    <div class="toast align-items-center text-bg-success border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
      <div class="d-flex">
        <div class="toast-body">
          <?php echo htmlspecialchars($success); ?>
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $e): ?>
      <div class="toast align-items-center text-bg-danger border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="6000">
        <div class="d-flex">
          <div class="toast-body">
            <?php echo htmlspecialchars($e); ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Auto-show all toasts on page load
  document.querySelectorAll('.toast').forEach(function(toastEl){
    var t = new bootstrap.Toast(toastEl);
    t.show();
  });
});
</script>

</body>
</html>