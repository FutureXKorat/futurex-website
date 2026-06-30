<?php
echo "PHP is running"; exit;

// Set timezone to Kuala Lumpur
date_default_timezone_set('Asia/Kuala_Lumpur');

$texts = [
    'en' => [
        'title'    => 'Reset Password - Future X',
	'Pass'     => 'New Password',
        'Con'      => 'Confirm Password',
        'h2t'      => 'Reset Password',
        'now'      => 'Updating...',
        'lang'     => 'ภาษาไทย'
    ],
    'th' => [
	'title'    => 'เปลี่ยนรหัสผ่าน - Future X',
        'Pass'     => 'รหัสผ่านใหม่',
        'Con'      => 'ยืนยันรหัสผ่าน',
        'h2t'      => 'รีเซ็ตรหัสผ่าน',
        'now'      => 'กำลังอัปเดต...',
        'lang'     => 'English'
    ],
];

$errors = [];
$token = $_GET['token'] ?? '';
$success = false;

// Validate token
if (empty($token)) {
    die("Invalid request. No token provided.");
}

$stmt = $conn->prepare("SELECT id, username FROM users WHERE reset_token = ? AND reset_expires > NOW()");
if (!$stmt) {
    die("Database error. Please request a new reset link.");
}
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if (!$result || $result->num_rows === 0) {
    die("Invalid or expired token. Please request a new reset link.");
}

$user = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if (strlen($password) < 6 || strlen($password) > 12) {
        $errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมี 6–12 ตัวอักษร' : 'Password must be 6–12 characters.';
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีอย่างน้อย 1 ตัวเลข' : 'Password must contain at least one number.';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีตัวพิมพ์เล็กอย่างน้อย 1 ตัว' : 'Password must contain at least one lowercase letter.';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว' : 'Password must contain at least one capital letter.';
    }
    if ($password !== $confirm_password) {
        $errors[] = ($lang === 'th') ? 'รหัสผ่านไม่ตรงกัน' : 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $upd = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        if (!$upd) {
            $errors[] = "Database error. Please try again.";
        } else {
            $upd->bind_param("si", $hashed_password, $user["id"]);
            if ($upd->execute()) {
                $upd->close();
                header("Location: login.php?reset=success");
                exit();
            } else {
                $upd->close();
                $errors[] = "Failed to update password. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo ($texts[$lang]['title']) ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
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
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: var(--ink);
            padding: 20px 20px;
        }
        .form-container {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px 35px;
            width: 95%;          /* ✅ Matches login.php */
            max-width: 462px;    /* ✅ Matches login.php */
            box-shadow: 0 12px 32px rgba(0, 123, 255, 0.15);
        }
        .form-container h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            color: var(--ink);
        }
        .form-control {
            border-radius: 12px;
            padding: 12px;
            font-size: 1rem;
            border-color: #E5E7EB;
            transition: box-shadow .2s ease, border-color .2s ease;
        }
        .form-control:focus {
            border-color: var(--brand-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 0, 255, 0.25);
            outline: none;
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
            border: none;
            color: #fff;
        }
        .btn-modern.btn-primary {
            background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
        }
        .btn-modern.btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-hover), var(--brand-deep));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.35);
        }

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

        .spinner-border { color: var(--brand-color); }
        :focus-visible { outline: 2px solid var(--brand-color); outline-offset: 2px; }
        ::selection { background: rgba(0,123,255,0.2); color: #111827; }

        /* ── Password requirements checklist ── */
        .pw-wrap { position: relative; }
        .pw-wrap .form-control { padding-right: 2.75rem; }
        .pwd-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; padding: 0; cursor: pointer;
            color: #6B7280; line-height: 0; user-select: none; -webkit-user-select: none;
            touch-action: none;
        }
        .pwd-eye:focus { outline: none; }
        .pwd-eye:hover { color: #374151; }
        .pw-reqs { list-style: none; margin: 8px 0 4px; padding: 0; display: flex; flex-direction: column; gap: 5px; }
        .pw-req { display: flex; align-items: center; gap: 8px; font-size: 0.79rem; color: #9ca3af; transition: color .2s; }
        .pw-req.met { color: #15803d; }
        .pw-req-dot {
            width: 17px; height: 17px; border-radius: 50%; border: 2px solid #d1d5db;
            display: inline-flex; align-items: center; justify-content: center;
            flex-shrink: 0; transition: background .2s, border-color .2s;
        }
        .pw-req.met .pw-req-dot { background: #16a34a; border-color: #16a34a; }
        .pw-req-dot svg { display: none; }
        .pw-req.met .pw-req-dot svg { display: block; }
    </style>
</head>
<body>
<a class="lang-switch"
   href="?token=<?= urlencode($token) ?>&lang=<?= ($lang === 'en' ? 'th' : 'en') ?>">
  <?= htmlspecialchars($texts[$lang]['lang']) ?>
</a>
<div class="form-container">
    <h2><?php echo htmlspecialchars ($texts[$lang]['h2t']); ?></h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" id="resetForm">
        <div class="mb-3">
            <div class="pw-wrap">
                <input type="password" name="password" id="rpNewPw" class="form-control" placeholder="<?php echo htmlspecialchars($texts[$lang]['Pass']); ?>" required>
                <button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
            <ul class="pw-reqs">
                <li class="pw-req" id="rp-req-min"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least 6 characters' : 'อย่างน้อย 6 ตัวอักษร'; ?></span></li>
                <li class="pw-req" id="rp-req-max"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'No more than 12 characters' : 'ไม่เกิน 12 ตัวอักษร'; ?></span></li>
                <li class="pw-req" id="rp-req-num"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least one number' : 'มีตัวเลขอย่างน้อย 1 ตัว'; ?></span></li>
                <li class="pw-req" id="rp-req-lower"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least one lowercase letter' : 'มีตัวพิมพ์เล็กอย่างน้อย 1 ตัว'; ?></span></li>
                <li class="pw-req" id="rp-req-upper"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least one capital letter' : 'มีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว'; ?></span></li>
            </ul>
        </div>
        <div class="mb-3">
            <div class="pw-wrap">
                <input type="password" name="confirm_password" id="rpCfPw" class="form-control" placeholder="<?php echo htmlspecialchars ($texts[$lang]['Con']); ?>" required>
                <button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
            </div>
            <ul class="pw-reqs">
                <li class="pw-req" id="rp-req-match"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'Passwords match' : 'รหัสผ่านตรงกัน'; ?></span></li>
            </ul>
        </div>
        <button type="submit" class="btn btn-modern btn-primary" id="resetBtn"><?php echo htmlspecialchars ($texts[$lang]['h2t']); ?></button>
    </form>
</div>

<script>
document.getElementById("resetForm").addEventListener("submit", function() {
    const btn = document.getElementById("resetBtn");
    btn.disabled = true;
    btn.innerHTML = `<?php echo htmlspecialchars ($texts[$lang]['now']) ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>`;
});

(function() {
    var newPw = document.getElementById('rpNewPw');
    var cfPw  = document.getElementById('rpCfPw');
    if (!newPw) return;
    function set(id, met) {
        var el = document.getElementById(id);
        if (el) el.classList.toggle('met', met);
    }
    function checkReqs() {
        var v = newPw.value;
        set('rp-req-min',   v.length >= 6);
        set('rp-req-max',   v.length > 0 && v.length <= 12);
        set('rp-req-num',   /[0-9]/.test(v));
        set('rp-req-lower', /[a-z]/.test(v));
        set('rp-req-upper', /[A-Z]/.test(v));
        checkMatch();
    }
    function checkMatch() {
        if (!cfPw) return;
        set('rp-req-match', cfPw.value.length > 0 && cfPw.value === newPw.value);
    }
    newPw.addEventListener('input', checkReqs);
    if (cfPw) cfPw.addEventListener('input', checkMatch);
})();

document.querySelectorAll('.pwd-eye').forEach(function(btn) {
    var inp = btn.previousElementSibling;
    btn.addEventListener('mousedown',  function()  { inp.type = 'text'; });
    btn.addEventListener('mouseup',    function()  { inp.type = 'password'; });
    btn.addEventListener('mouseleave', function()  { inp.type = 'password'; });
    btn.addEventListener('touchstart', function(e) { e.preventDefault(); inp.type = 'text'; }, { passive: false });
    btn.addEventListener('touchend',   function()  { inp.type = 'password'; });
});
</script>
</body>
</html>
