<?php
session_start();
include 'database.php'; // defines $conn and $lang

$texts = [
    'en' => [
        'title'       => 'Verify OTP - Future X',
        'h2'          => 'Verify Your OTP',
        'placeholder' => '000000',
        'verify_btn'  => 'Verify',
        'verifying'   => 'Verifying...',
        'invalid'     => 'Invalid OTP. Please try again.',
        'no_account'  => 'No account found.',
        'expired'     => 'OTP has expired.',
        'lang'        => 'ภาษาไทย',
    ],
    'th' => [
        'title'       => 'ยืนยัน OTP - Future X',
        'h2'          => 'ยืนยันรหัส OTP ของคุณ',
        'placeholder' => '000000',
        'verify_btn'  => 'ยืนยัน',
        'verifying'   => 'กำลังยืนยัน...',
        'invalid'     => 'รหัส OTP ไม่ถูกต้อง โปรดลองอีกครั้ง',
        'no_account'  => 'ไม่พบบัญชีผู้ใช้',
        'expired'     => 'รหัส OTP หมดอายุแล้ว',
        'lang'        => 'English',
    ],
];

$username = $_GET['username'] ?? '';
$errors   = [];

// Require a username in the URL
if (empty($username)) {
    die($texts[$lang]['no_account']);
}

// Decide which table to use: login flow vs registration flow
$isLogin = isset($_GET['login']) && $_GET['login'] === '1';
$table   = $isLogin ? 'users' : 'pending_users';

// Columns we need from each table
$cols = $isLogin
    ? 'id, username, otp_code'
    : 'id, name, surname, username, email, phoneno, password_hash, otp_code, expires_at';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp_input = preg_replace('/\D/', '', $_POST['otp'] ?? '');

    // Lookup by username in the chosen table
    $sql  = "SELECT $cols FROM $table WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();

        if ((string)$row['otp_code'] === (string)$otp_input) {
            if ($isLogin) {
                // ----- LOGIN OTP PATH -----
                $upd = $conn->prepare("UPDATE users SET otp_code = NULL, verified = 1 WHERE id = ?");
                $upd->bind_param("i", $row["id"]);
                $upd->execute();
                $upd->close();

                $_SESSION["user_id"]  = $row["id"];
                $_SESSION['username'] = (string)($row['username'] ?? '');

                if (!empty($_SESSION['pending_remember'])) {
                    // Expire at midnight tonight (Asia/Kuala_Lumpur)
                    $tz       = new DateTimeZone('Asia/Kuala_Lumpur');
                    $midnight = (new DateTime('tomorrow midnight', $tz))->getTimestamp();
                    $_SESSION['session_expires'] = $midnight;

                    // Overwrite the session cookie so it survives browser close until midnight
                    $p = session_get_cookie_params();
                    setcookie(session_name(), session_id(), [
                        'expires'  => $midnight,
                        'path'     => $p['path'],
                        'domain'   => $p['domain'],
                        'secure'   => $p['secure'],
                        'httponly' => $p['httponly'],
                        'samesite' => 'Lax',
                    ]);
                }
                unset($_SESSION['pending_remember']);

                header("Location: home.php");
                exit();
            } else {
                // ----- REGISTRATION OTP PATH -----

                // 1) Check expiry in Asia/Kuala_Lumpur
                $now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
                if (!empty($row['expires_at'])) {
                    $exp = new DateTime($row['expires_at'], new DateTimeZone('Asia/Kuala_Lumpur'));
                    if ($now > $exp) {
                        $errors[] = $texts[$lang]['expired'];
                    }
                }

                if (empty($errors)) {
                    // 2) Promote pending_users -> users in a transaction
                    $conn->begin_transaction();

                    // NOTE: Map to your actual users table columns:
                    // - If your users table stores the hash in `password`, keep as below.
                    // - If it uses a different column name, change it here.
                    $ins = $conn->prepare("
                        INSERT INTO users (name, surname, username, email, phoneno, password, verified, otp_code)
                        VALUES (?, ?, ?, ?, ?, ?, 1, NULL)
                    ");
                    $ins->bind_param(
                        "ssssss",
                        $row['name'],
                        $row['surname'],
                        $row['username'],
                        $row['email'],
                        $row['phoneno'],
                        $row['password_hash']   // ← goes into users.password
                    );
                    $ok1 = $ins->execute();
                    $ins->close();

                    $del = $conn->prepare("DELETE FROM pending_users WHERE id = ?");
                    $del->bind_param("i", $row['id']);
                    $ok2 = $del->execute();
                    $del->close();

                    if ($ok1 && $ok2) {
                        $conn->commit();
                        $_SESSION['flash_success'] = 'reg_success';
                        header("Location: login.php");
                        exit();
                    } else {
                        $conn->rollback();
                        $errors[] = ($lang === 'th')
                            ? 'เกิดข้อผิดพลาดในการสร้างบัญชี'
                            : 'Failed to create your account.';
                    }
                }
            }
        } else {
            $errors[] = $texts[$lang]['invalid'];
        }
    } else {
        $errors[] = $texts[$lang]['no_account'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($texts[$lang]['title']); ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root { --brand:#007BFF; --brand-hover:#0056b3; --brand-deep:#003f7f; --ink:#111; }
        body{
            margin:0;
            font-family:'Inter', sans-serif;
            min-height:100vh;
            display:flex;align-items:center;justify-content:center;
            background:linear-gradient(135deg,#E6F0FF,#CCE0FF,#FFFFFF);
            color:var(--ink);
            padding:40px 20px;
        }
        @media (max-width:460px){ body{ padding:80px 20px 40px; } }
        @supports (height:100dvh){ body{ min-height:100dvh; } }
        input, button, select, textarea, .form-control, .btn {
            font-family: 'Inter', sans-serif !important;
        }
        .form-container{
            background:rgba(255,255,255,.25); backdrop-filter:blur(12px);
            border-radius:20px; padding:40px 35px; width:95%; max-width:462px;
            box-shadow:0 12px 32px rgba(0,0,0,.15); position:relative; text-align:center;
        }
        .form-container h2{
            font-size:1.8rem; font-weight:700; margin-bottom:20px; text-align:center;
        }
        .form-control{
            border-radius:12px; padding:12px; font-size:1rem; border-color:#E5E7EB;
            transition:box-shadow .2s ease, border-color .2s ease;
        }
        .form-control:focus{ border-color:var(--brand); box-shadow:0 0 0 .25rem rgba(0,123,255,.25); }
        .otp-input{
            text-align:center;
            letter-spacing:4px;
            font-weight:700;
            font-size:1.3rem;
        }
        @media (max-width:400px){
            .otp-input{ font-size:1rem; letter-spacing:2px; }
            .form-container{ padding:32px 20px; }
        }
        .btn-modern{ display:block; width:100%; margin-top:16px; padding:14px;
            font-size:1.1rem; font-weight:600; border-radius:14px; transition:all .3s ease; border:none; color:#fff; }
        .btn-modern.btn-primary{ background:linear-gradient(135deg,var(--brand),var(--brand-hover)); }
        .btn-modern.btn-primary:hover{ background:linear-gradient(135deg,var(--brand-hover),var(--brand-deep)); transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,123,255,.35); }
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
	    	color: #007BFF;
        }
        .lang-switch:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

<!-- Keep username & login flag when switching language -->
<a class="lang-switch"
   href="?username=<?php echo urlencode($username); ?>&login=<?php echo $isLogin ? '1' : ''; ?>&lang=<?php echo $lang === 'en' ? 'th' : 'en'; ?>">
    <?php echo $texts[$lang]['lang']; ?>
</a>

<div class="form-container">
    <h2><?php echo htmlspecialchars($texts[$lang]['h2']); ?></h2>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><ul class="mb-0">
            <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
        </ul></div>
    <?php endif; ?>

    <form method="post" id="otpForm">
        <div class="mb-3">
            <input
  				id="otp"
  				type="tel"
  				name="otp"
  				class="form-control otp-input"
  				inputmode="numeric"
  				autocomplete="one-time-code"
  				maxlength="6"
  				pattern="^\d{6}$"
  				placeholder="<?php echo htmlspecialchars($texts[$lang]['placeholder']); ?>"
  				required
			/>
        </div>
        <button type="submit" class="btn btn-modern btn-primary" id="verifyBtn">
            <?php echo htmlspecialchars($texts[$lang]['verify_btn']); ?>
        </button>
    </form>
</div>


<script>
  (function () {
    const otp = document.getElementById('otp');
    const form = document.getElementById('otpForm');
    const btn = document.getElementById('verifyBtn');
    const MAX = 6;

    // Allow only digits for printable keys; don't block function keys.
    otp.addEventListener('keydown', (e) => {
      const k = e.key;

      // allow shortcuts/navigation
      if (
        e.ctrlKey || e.metaKey || e.altKey ||
        k === 'Backspace' || k === 'Delete' ||
        k === 'Tab' || k === 'Enter' ||
        k === 'ArrowLeft' || k === 'ArrowRight' ||
        k === 'Home' || k === 'End'
      ) return;

      // allow any non-printable key (e.g., F1..F12, PageUp/Down) while focused
      if (k.length > 1) return;

      // allow only digits for printable single-char keys
      if (!/^\d$/.test(k)) e.preventDefault();
    });

    // Sanitize paste/IME and enforce max length
    otp.addEventListener('input', () => {
      const digits = otp.value.replace(/\D/g, '').slice(0, MAX);
      if (otp.value !== digits) otp.value = digits; // why: ensures only digits persist
    });

    // Optional: handle drop text safely
    otp.addEventListener('drop', (e) => {
      e.preventDefault();
      const data = (e.dataTransfer?.getData('text') || '').replace(/\D/g, '').slice(0, MAX);
      const start = otp.selectionStart ?? otp.value.length;
      const end = otp.selectionEnd ?? otp.value.length;
      otp.value = (otp.value.slice(0, start) + data + otp.value.slice(end)).slice(0, MAX);
    });

    // Single submit handler (avoid duplicates)
    form.addEventListener('submit', () => {
      btn.disabled = true;
      btn.innerHTML = `<?php echo $texts[$lang]['verifying']; ?> <span class="spinner-border spinner-border-sm ms-2 text-light"></span>`;
    });
  })();
</script>
</body>
</html>