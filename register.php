<?php
session_start();
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
	    	color: var(--brand-color);
        }
        .lang-switch:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

		/* ===== Country code dropdown (outside stays white) ===== */
		.phone-input { position: relative; }

		/* Left button should look like a white input */
		.cc-btn {
			display: block;              /* full block */
    		width: 100%;                 /* take all width of its cell */
    		text-align: left;
			border-radius: 12px 0 0 12px;
			font-weight: 600;
			padding: 12px 16px;
			background-color: #fff;      /* stay white outside */
			border-color: #ced4da;       /* match Bootstrap input border */
			color: #212529;
		}

		.cc-btn:hover {
    		background-color: #f1f1f1;
    		border-color: #80bdff;
		}
		.cc-btn:focus {
			box-shadow: 0 0 0 0.25rem rgba(0,123,255,0.25);
			border-color: #80bdff;
		}

		/* Make the input connect seamlessly with the left button */
		.phone-input .form-control {
			border-top-left-radius: 0;
			border-bottom-left-radius: 0;
		}
		/* Remove double border between button and input */
		.cc-btn { border-right-width: 0; }
		.phone-input .form-control:focus { position: relative; z-index: 2; }

		/* The opened menu can stay white (modern shadow + rounded) */
		.phone-input .dropdown-menu {
			border-radius: 12px;
			background-color: #fff;      /* white menu */
			box-shadow: 0 8px 20px rgba(0,0,0,0.15);
			overflow: hidden;
			margin-top: 8px;             /* space from button */
			z-index: 1055;
		}
		.phone-input .dropdown-item {
			padding: 10px 16px;
			font-size: 1rem;
			font-weight: 500;
			transition: background 0.2s, color 0.2s;
		}
		.phone-input .dropdown-item:hover {
			background-color: #007BFF;
			color: #fff;
		}
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

		<!-- Phone with country code (outside remains white) -->
		<div class="mb-3">
			<div class="input-group phone-input">
				<div class="dropdown">
					<button class="btn btn-outline-secondary dropdown-toggle cc-btn" type="button" id="ccDropdown" data-bs-toggle="dropdown" aria-expanded="false">
						<span id="selectedCC"><?php
							$posted_cc = $_POST['cc'] ?? '+66';
							$label = $posted_cc === '+60' ? '🇲🇾 +60' : ($posted_cc === '+856' ? '🇱🇦 +856' : '🇹🇭 +66');
							echo $label;
						?></span>
					</button>
					<ul class="dropdown-menu" aria-labelledby="ccDropdown">
						<li><a class="dropdown-item cc-option" href="#" data-cc="+66">🇹🇭 +66</a></li>
						<li><a class="dropdown-item cc-option" href="#" data-cc="+60">🇲🇾 +60</a></li>
						<li><a class="dropdown-item cc-option" href="#" data-cc="+856">🇱🇦 +856</a></li>
					</ul>
				</div>

				<input type="hidden" name="cc" id="ccInput" value="<?php echo htmlspecialchars($_POST['cc'] ?? '+66'); ?>">
				<input type="tel" name="phoneno" class="form-control"
					value="<?php echo old('phoneno'); ?>"
					placeholder="<?php echo $texts[$lang]['phoneno']; ?>" required>
			</div>
		</div>

		<div class="mb-3">
			<input type="password" name="password" class="form-control"
				   placeholder="<?php echo $texts[$lang]['password']; ?>" required>
		</div>

		<div class="mb-3">
			<input type="password" name="confirm_password" class="form-control"
				   placeholder="<?php echo $texts[$lang]['confirm']; ?>" required>
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

// Country code selection logic (custom dropdown)
document.querySelectorAll(".cc-option").forEach(function(el){
	el.addEventListener("click", function(e){
		e.preventDefault();
		var label = this.innerText;
		var code  = this.dataset.cc;
		document.getElementById("selectedCC").innerText = label;
		document.getElementById("ccInput").value = code;

		// close dropdown after select
		var dd = bootstrap.Dropdown.getOrCreateInstance(document.getElementById('ccDropdown'));
		dd.hide();
	});
});
</script>
</body>
</html>