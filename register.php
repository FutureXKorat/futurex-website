<?php
include 'database.php';
include 'send_otp.php';

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

// Language text map (keep this)
$texts = [
	'en' => [
		'tabbar'      => 'Register Page - Future X',
		'title'       => 'Register an Account',
		'name'        => 'Name',
		'surname'     => 'Surname',
		'email'       => 'Email',
		'phoneno'     => 'Phone Number',
		'username'    => 'Username',
		'usererror2'  => 'Username can only include English letters, numbers, . or _',
		'usererror3'  => 'Username must be at least 5 characters long',
		'password'    => 'Warehouse Password',
		'confirm'     => 'Confirm Password',
		'register'    => 'Register',
		'registering' => 'Registering...',
		'login'       => 'Login here',
		'guest'       => 'Continue as Guest',
		'already'     => 'Already have an account?',
		'lang'        => 'ภาษาไทย'
	],
	'th' => [
		'tabbar'      => 'หน้าสมัครบัญชี - Future X',
		'title'       => 'สร้างบัญชีผู้ใช้',
		'name'        => 'ชื่อ',
		'surname'     => 'นามสกุล',
		'username'    => 'ชื่อผู้ใช้',
		'usererror2'  => 'ชื่อผู้ใช้สามารถมีได้เฉพาะตัวอักษรภาษาอังกฤษ, ตัวเลข, . หรือ _ เท่านั้น',
		'usererror3'  => 'ชื่อผู้ใช้จะต้องมีอย่างน้อย 5 ตัวอักษร',
		'email'       => 'อีเมล',
		'phoneno'     => 'เบอร์โทร',
		'password'    => 'รหัสผ่านของคลัง',
		'confirm'     => 'ยืนยันรหัสผ่าน',
		'register'    => 'ลงทะเบียน',
		'registering' => 'กำลังลงทะเบียน...',
		'login'       => 'เข้าสู่ระบบที่นี่',
		'guest'       => 'เข้าสู่เว็บไซต์โดยไม่ลงชื่อ',
		'already'     => 'มีบัญชีอยู่แล้ว?',
		'lang'        => 'English'
	]
];

// ✅ DO NOT set $lang here; database.php already did it from the cookie

// Registration logic
$errors = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$name             = trim($_POST["name"] ?? '');
	$surname          = trim($_POST["surname"] ?? '');
	$username         = trim($_POST["username"] ?? '');
	$email            = $_POST["email"] ?? '';
	// country code from hidden input (custom dropdown)
	$cc               = $_POST["cc"] ?? '+66';
	$phoneno_input    = trim($_POST["phoneno"] ?? '');
	$password         = $_POST["password"] ?? '';
	$confirm_password = $_POST["confirm_password"] ?? '';

	// sanitize phone digits, then combine with cc
	$allowed_cc = ['+66', '+60', '+856'];
	if (!in_array($cc, $allowed_cc, true)) { $cc = '+66'; }
	$digits_only   = preg_replace('/\D+/', '', $phoneno_input);
	$phoneno_full  = $cc . $digits_only;

	// Required fields
	if ($name === '' || $surname === '' || $username === '' || $email === '' || $digits_only === '' || $password === '' || $confirm_password === '') {
		$errors[] = "All fields are required.";
	}

	// Username rules
	if (!preg_match('/^[A-Za-z0-9._]+$/', $username)) {
		$errors[] = $texts[$lang]['usererror2'];
	} elseif (strlen($username) < 5) {
		$errors[] = $texts[$lang]['usererror3'];
	}

	// Email must be valid and end with .com
	if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/@[^@]+\.com$/i', $email)) {
		$errors[] = ($lang === 'th')
			? 'กรุณากรอกอีเมลที่ลงท้ายด้วย .com'
			: 'Please enter a valid email address that ends with .com.';
	}

	// Phone: must be one of the selected country codes + reasonable length
	if (!preg_match('/^\+(66|60|856)\d{7,12}$/', $phoneno_full)) {
		$errors[] = ($lang === 'th') ? 'รูปแบบเบอร์โทรไม่ถูกต้อง' : 'Invalid phone number format.';
	}

	// Password checks
	if ($password !== $confirm_password) {
		$errors[] = ($lang === 'th') ? 'รหัสผ่านไม่ตรงกัน' : 'Passwords do not match.';
	}
	if (strlen($password) < 6 || strlen($password) > 12) {
		$errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร และ ไม่เกิน 12 ตัวอักษร' : 'Password must have at least 6 characters and not exceed 12 characters.';
	}
	if (!preg_match('/[0-9]/', $password)) {
		$errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีเลขอย่างน้อย 1 ตัว' : 'Password must contain at least one number.';
	}
	if (!preg_match('/[a-zA-Z]/', $password)) {
		$errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีตัวอักษรอย่างน้อย 1 ตัว' : 'Password must contain at least one letter.';
	}
	if (!preg_match('/[A-Z]/', $password)) {
		$errors[] = ($lang === 'th') ? 'รหัสผ่านต้องมีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว' : 'Password must contain at least one capital letter.';
	}

	// Duplicates in users
	$stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$stmt->store_result();
	if ($stmt->num_rows > 0) {
		$errors[] = ($lang === 'th') ? 'ชื่อผู้ใช้ถูกใช้แล้ว' : 'This username is already taken.';
	}
	$stmt->close();

	// Duplicates in pending_users (avoid multiple pending rows)
	$stmt = $conn->prepare("SELECT 1 FROM pending_users WHERE username = ?");
	$stmt->bind_param("s", $username);
	$stmt->execute();
	$stmt->store_result();
	if ($stmt->num_rows > 0) {
		$errors[] = ($lang === 'th') ? 'ชื่อผู้ใช้กำลังรอยืนยัน' : 'This username is already pending verification.';
	}
	$stmt->close();

	if (empty($errors)) {
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);
		$otp             = random_int(100000, 999999);

		$dt = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
		$dt->modify('+5 minutes');
		$expires_at = $dt->format('Y-m-d H:i:s');


		$stmt = $conn->prepare(
			"INSERT INTO pending_users
				(name, surname, username, email, phoneno, password_hash, otp_code, expires_at)
			 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
		);
		if (!$stmt) {
			$errors[] = "Database error (prepare).";
		} else {
			$stmt->bind_param("ssssssis", $name, $surname, $username, $email, $phoneno_full, $hashed_password, $otp, $expires_at);
			$ok = $stmt->execute();
			$stmt->close();

			if ($ok) {
				if (sendOTPEmail($email, $otp)) {
					header("Location: verify_otp.php?username=" . urlencode($username));
					exit();
				} else {
					$errors[] = "Failed to send OTP email. Please try again.";
					// optional cleanup if sending failed
					$stmt = $conn->prepare("DELETE FROM pending_users WHERE email = ?");
					$stmt->bind_param("s", $email);
					$stmt->execute();
					$stmt->close();
				}
			} else {
				$errors[] = "Something went wrong saving your data. Please try again.";
			}
		}
	}
}
?>
<?php
// keep entered values after validation errors
function old($key) {
	return htmlspecialchars($_POST[$key] ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
	<meta charset="UTF-8">
	<title><?php echo $texts[$lang]['tabbar']; ?></title>
	<link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
			box-shadow: 0 12px 32px rgba(204, 0, 0, 0.15);
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
	    	color: #007BFF;
        }
        .lang-switch:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

		/* ─── Phone input ─────────────────────────────────────── */
		.cc-dropdown-wrap { position: relative; }
		.phone-wrap {
			display: flex; align-items: center;
			background: #fff; border: 1.5px solid #dee2e6;
			border-radius: 12px; overflow: visible;
			transition: border-color .2s, box-shadow .2s;
		}
		.phone-wrap:focus-within {
			border-color: #86b7fe;
			box-shadow: 0 0 0 0.25rem rgba(13,110,253,.25);
		}
		.phone-cc {
			display: flex; align-items: center; gap: 5px;
			padding: 12px 10px 12px 14px;
			cursor: pointer; white-space: nowrap;
			font-weight: 600; font-size: .95rem; color: #212529;
			border-radius: 12px 0 0 12px;
			user-select: none; flex-shrink: 0;
			transition: background .15s;
		}
		.phone-cc:hover { background: rgba(0,123,255,.06); }
		.cc-chevron { transition: transform .2s; flex-shrink: 0; }
		.cc-chevron.open { transform: rotate(180deg); }
		.phone-divider { width: 1px; height: 20px; background: #dee2e6; flex-shrink: 0; }
		.phone-num-input {
			flex: 1; border: none; outline: none;
			padding: 12px 14px; font-size: 1rem;
			font-family: 'Inter', sans-serif;
			background: transparent; border-radius: 0 12px 12px 0;
			color: #212529; min-width: 0;
		}
		.phone-num-input::placeholder { color: #adb5bd; }
		.cc-dropdown {
			display: none; position: absolute; top: calc(100% + 6px); left: 0;
			z-index: 1060; background: #fff; border-radius: 12px;
			box-shadow: 0 8px 24px rgba(0,0,0,.14); overflow: hidden; min-width: 190px;
		}
		.cc-dropdown.open { display: block; }
		.cc-opt {
			display: flex; align-items: center; gap: 10px;
			padding: 11px 16px; font-size: .95rem; font-weight: 500; cursor: pointer;
			transition: background .15s, color .15s;
		}
		.cc-opt:hover { background: #007BFF; color: #fff; }
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

		/* Password requirements checklist */
		.pw-reqs {
			list-style: none;
			margin: 7px 0 0;
			padding: 0;
			display: flex;
			flex-direction: column;
			gap: 5px;
		}
		.pw-req {
			display: flex;
			align-items: center;
			gap: 8px;
			font-size: 0.79rem;
			color: #9ca3af;
			transition: color .2s;
		}
		.pw-req.met { color: #15803d; }
		.pw-req-dot {
			width: 17px; height: 17px;
			border-radius: 50%;
			border: 2px solid #d1d5db;
			display: inline-flex; align-items: center; justify-content: center;
			flex-shrink: 0;
			transition: background .2s, border-color .2s;
		}
		.pw-req.met .pw-req-dot {
			background: #16a34a;
			border-color: #16a34a;
		}
		.pw-req-dot svg { display: none; }
		.pw-req.met .pw-req-dot svg { display: block; }
	</style>
</head>
<body>
<!-- Language switch -->
<a class="lang-switch" href="?lang=<?php echo $lang === 'en' ? 'th' : 'en'; ?>">
	<?php echo $texts[$lang]['lang']; ?>
</a>

<div class="form-container">
	<h2><?php echo $texts[$lang]['title']; ?></h2>

	<?php if (!empty($errors)): ?>
		<div class="alert alert-danger">
			<ul class="mb-0">
				<?php foreach ($errors as $error): ?>
					<li><?php echo htmlspecialchars($error); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<form method="post" id="registerForm">
		<div class="mb-3">
			<input type="text" name="name" class="form-control"
				   value="<?php echo old('name'); ?>"
				   placeholder="<?php echo $texts[$lang]['name']; ?>" required>
		</div>

		<div class="mb-3">
			<input type="text" name="surname" class="form-control"
				   value="<?php echo old('surname'); ?>"
				   placeholder="<?php echo $texts[$lang]['surname']; ?>" required>
		</div>

		<div class="mb-3">
			<input type="text" name="username" class="form-control"
				   value="<?php echo old('username'); ?>"
				   placeholder="<?php echo $texts[$lang]['username']; ?>" required>
		</div>

		<div class="mb-3">
			<input type="email" name="email" class="form-control"
				   value="<?php echo old('email'); ?>"
				   placeholder="<?php echo $texts[$lang]['email']; ?>" required>
		</div>

		<!-- Phone with country code -->
		<div class="mb-3 cc-dropdown-wrap">
			<?php
				$posted_cc = $_POST['cc'] ?? '+66';
				$ccDisplayLabel = $posted_cc === '+60' ? '🇲🇾 +60' : ($posted_cc === '+856' ? '🇱🇦 +856' : '🇹🇭 +66');
			?>
			<div class="phone-wrap">
				<div class="phone-cc" id="ccToggle">
					<span id="selectedCC"><?php echo $ccDisplayLabel; ?></span>
					<svg class="cc-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
				</div>
				<div class="phone-divider"></div>
				<input type="tel" name="phoneno" class="phone-num-input"
					value="<?php echo old('phoneno'); ?>"
					placeholder="<?php echo $texts[$lang]['phoneno']; ?>" required>
			</div>
			<input type="hidden" name="cc" id="ccInput" value="<?php echo htmlspecialchars($posted_cc); ?>">
			<div class="cc-dropdown" id="ccDropdown">
				<div class="cc-opt" data-cc="+66" data-label="🇹🇭 +66">🇹🇭 &nbsp;+66 &nbsp; Thailand</div>
				<div class="cc-opt" data-cc="+60" data-label="🇲🇾 +60">🇲🇾 &nbsp;+60 &nbsp; Malaysia</div>
				<div class="cc-opt" data-cc="+856" data-label="🇱🇦 +856">🇱🇦 &nbsp;+856 &nbsp; Laos</div>
			</div>
		</div>

		<div class="mb-3">
			<div class="pw-wrap">
				<input type="password" name="password" id="pwInput" class="form-control"
					   placeholder="<?php echo $texts[$lang]['password']; ?>" required>
				<button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
			</div>
			<ul class="pw-reqs">
				<li class="pw-req" id="req-min">
					<span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					<span><?php echo $lang === 'en' ? 'At least 6 characters' : 'อย่างน้อย 6 ตัวอักษร'; ?></span>
				</li>
				<li class="pw-req" id="req-max">
					<span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					<span><?php echo $lang === 'en' ? 'No more than 12 characters' : 'ไม่เกิน 12 ตัวอักษร'; ?></span>
				</li>
				<li class="pw-req" id="req-num">
					<span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					<span><?php echo $lang === 'en' ? 'At least one number' : 'มีตัวเลขอย่างน้อย 1 ตัว'; ?></span>
				</li>
				<li class="pw-req" id="req-letter">
					<span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					<span><?php echo $lang === 'en' ? 'At least one letter' : 'มีตัวอักษรอย่างน้อย 1 ตัว'; ?></span>
				</li>
				<li class="pw-req" id="req-upper">
					<span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					<span><?php echo $lang === 'en' ? 'At least one capital letter' : 'มีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว'; ?></span>
				</li>
			</ul>
		</div>

		<div class="mb-3">
			<div class="pw-wrap">
				<input type="password" name="confirm_password" id="cfInput" class="form-control"
					   placeholder="<?php echo $texts[$lang]['confirm']; ?>" required>
				<button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
			</div>
			<ul class="pw-reqs">
				<li class="pw-req" id="req-match">
					<span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
					<span><?php echo $lang === 'en' ? 'Passwords match' : 'รหัสผ่านตรงกัน'; ?></span>
				</li>
			</ul>
		</div>

		<button type="submit" class="btn btn-modern btn-primary" id="registerBtn">
			<?php echo $texts[$lang]['register']; ?>
		</button>
	</form>

	<div class="link-section">
		<p>
			<?php echo $texts[$lang]['already']; ?>
			<a href="login.php" class="login-link">
				<?php echo $texts[$lang]['login']; ?>
			</a>
		</p>
		<p><a href="home.php"><?php echo $texts[$lang]['guest']; ?></a></p>
	</div>
</div>

<!-- Bootstrap JS bundle (required for dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById("registerForm").addEventListener("submit", function() {
	const btn = document.getElementById("registerBtn");
	btn.disabled = true;
	btn.innerHTML = `<?php echo $texts[$lang]['registering']; ?> <span class="spinner-border spinner-border-sm ms-2" role="status"></span>`;
});

document.querySelectorAll('.pwd-eye').forEach(function(btn) {
    var inp = btn.previousElementSibling;
    btn.addEventListener('mousedown',  function()  { inp.type = 'text'; });
    btn.addEventListener('mouseup',    function()  { inp.type = 'password'; });
    btn.addEventListener('mouseleave', function()  { inp.type = 'password'; });
    btn.addEventListener('touchstart', function(e) { e.preventDefault(); inp.type = 'text'; }, { passive: false });
    btn.addEventListener('touchend',   function()  { inp.type = 'password'; });
});

// Password requirements live check
const pwInput = document.getElementById('pwInput');
const cfInput = document.getElementById('cfInput');

function checkReqs() {
	const v = pwInput.value;
	const set = (id, met) => {
		const el = document.getElementById(id);
		if (el) el.classList.toggle('met', met);
	};
	set('req-min', v.length >= 6);
	set('req-max', v.length > 0 && v.length <= 12);
	set('req-num', /[0-9]/.test(v));
	set('req-letter', /[a-zA-Z]/.test(v));
	set('req-upper', /[A-Z]/.test(v));
	checkMatch();
}
function checkMatch() {
	const el = document.getElementById('req-match');
	if (el) el.classList.toggle('met', cfInput.value.length > 0 && cfInput.value === pwInput.value);
}
if (pwInput) pwInput.addEventListener('input', checkReqs);
if (cfInput) cfInput.addEventListener('input', checkMatch);

// Country code custom dropdown
(function() {
	var toggle   = document.getElementById('ccToggle');
	var dropdown = document.getElementById('ccDropdown');
	var hidden   = document.getElementById('ccInput');
	var display  = document.getElementById('selectedCC');
	if (!toggle || !dropdown) return;
	var chevron  = toggle.querySelector('.cc-chevron');

	toggle.addEventListener('click', function(e) {
		e.stopPropagation();
		var open = dropdown.classList.toggle('open');
		if (chevron) chevron.classList.toggle('open', open);
	});

	dropdown.querySelectorAll('.cc-opt').forEach(function(opt) {
		opt.addEventListener('click', function(e) {
			e.stopPropagation();
			display.textContent = this.dataset.label;
			hidden.value = this.dataset.cc;
			dropdown.classList.remove('open');
			if (chevron) chevron.classList.remove('open');
		});
	});

	document.addEventListener('click', function() {
		dropdown.classList.remove('open');
		if (chevron) chevron.classList.remove('open');
	});
})();
</script>
</body>
</html>