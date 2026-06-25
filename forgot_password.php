<?php
session_start();
include 'database.php';
include 'send_reset_email.php'; // Your email sending function file

// ✅ Set timezone to Kuala Lumpur
date_default_timezone_set('Asia/Kuala_Lumpur');

$texts = [
    'en' => [
        'title'   => 'Reset Password - Future X',
        'h2t'     => 'Reset Password',
        'enter'   => 'Enter your username',
        'send'    => 'Send Reset Link',
        'back'    => 'Back to Login Page',
        'now'     => 'Sending...',
        'lang'    => 'ภาษาไทย'
    ],

    'th' => [
        'title'   => 'เปลี่ยนรหัสผ่าน - Future X',
        'h2t'     => 'เปลี่ยนรหัส',
        'enter'   => 'ป้อนชื่อผู้ใช้ของคุณ',
        'send'    => 'ส่งลิงค์รีเซ็ตรหัสผ่าน',
        'back'    => 'กลับไปที่หน้าเข้าสู่ระบบ',
        'now'     => 'กำลังส่ง...',
        'lang'    => 'English'
    ]
];

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+30 minutes')); // ✅ Expiry 30 mins

        // Update DB
        $update = "UPDATE users SET reset_token = '$token', reset_expires = '$expires' WHERE username = '$username'";
        $conn->query($update);

        if (sendResetEmail($email, $token)) {
            $success = ($lang === 'th') ? 'ลิงค์รีเซ็ตรหัสผ่านได้ถูกส่งไปยังอีเมล์ของคุณแล้ว' : "A password reset link has been sent to your email.";
        } else {
            $errors[] = ($lang === 'th') ? 'ไม่สามารถส่งอีเมลได้ กรุณาลองอีกครั้ง' : 'Failed to send the email. Please try again.';
        }
    } else {
        $errors[] = ($lang === 'th') ? 'ไม่เจอบัญชีที่มี Username นี้' : 'No account found with that username.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($texts[$lang]['title']); ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root{
            --brand-color:#007BFF;
            --brand-hover:#0056b3;
            --brand-deep:#003f7f;
            --ink:#111111;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            /* 🔴 Lighter red gradient like register.php */
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: var(--ink);
            padding: 40px 20px;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px 35px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 12px 32px rgba(0, 0, 255, 0.15);
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
            margin-bottom: 15px; /* ✅ consistent spacing */
            border-color: #E5E7EB;
            transition: box-shadow .2s ease, border-color .2s ease;
        }
        .form-control:focus {
            border-color: var(--brand-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 0, 255, 0.25);
            outline: none;
        }

        .btn-modern,
        .btn-gray {
            width: 100%;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 14px;
            border: none;
            color: #fff;
            transition: all 0.3s ease;
            margin-bottom: 15px; /* ✅ same spacing for all buttons */
            text-decoration: none;
            display: inline-block;
        }
        /* 🔴 Primary action -> brand red gradient */
        .btn-modern {
            background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
        }
        .btn-modern:hover {
            background: linear-gradient(135deg, var(--brand-hover), var(--brand-deep));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 204, 0.35);
        }

        /* Gray secondary button (Back to Login Page) */
        .btn-gray {
            background: #6B7280;
        }
        .btn-gray:hover {
            background: #4B5563;
            transform: translateY(-2px);
            text-decoration: none;
        }

        .lang-switch {
            position: absolute;
            top: 20px;
            right: 20px;
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

        /* Accents */
        .spinner-border { color: var(--brand-color); } /* default spinner color */
        :focus-visible { outline: 2px solid var(--brand-color); outline-offset: 2px; }
        ::selection { background: rgba(0,123,255,0.2); color: #111827; }
    </style>
</head>
<body>
<a class="lang-switch" href="?lang=<?php echo $lang === 'en' ? 'th' : 'en'; ?>">
	<?php echo $texts[$lang]['lang']; ?>
</a>
<div class="form-container">
    <h2><?php echo htmlspecialchars ($texts[$lang]['h2t']) ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php echo implode("<br>", $errors); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="post" id="forgotForm">
        <input type="text" name="username" class="form-control" placeholder="<?php echo htmlspecialchars($texts[$lang]['enter']); ?>" required>
        <button type="submit" class="btn-modern" id="forgotBtn">
            <?php echo htmlspecialchars ($texts[$lang]['send']); ?>
        </button>
    </form>

    <a href="login.php" class="btn-gray"><?php echo htmlspecialchars ($texts[$lang]['back']); ?> </a>
</div>

<script>
document.getElementById("forgotForm").addEventListener("submit", function() {
    const btn = document.getElementById("forgotBtn");
    btn.disabled = true;
    // Keep spinner white for contrast on red button
    btn.innerHTML = `<?php echo htmlspecialchars ($texts[$lang]['now']); ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>`;
});
</script>
</body>
</html>
