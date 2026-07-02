<?php
include_once '../database.php';
include_once '../send_otp.php';


include 'auth.php';

// Settings page queries the users table — not applicable for employee-admins
if ($isEmployeeAdmin && !$isSuperAdmin) {
    header('Location: /admin/index.php'); exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /index.php');
    exit();
}

$userId       = (int)$_SESSION['user_id'];
$success      = "";
$errors       = [];
$pwErrors     = [];
$emailErrors  = [];
$deleteErrors = [];

require_once __DIR__ . '/../cloudinary.php';

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── AJAX: upload cropped profile picture ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cropped_image'])) {
    $imgData = base64_decode(str_replace('data:image/png;base64,', '', $_POST['cropped_image']));
    $tmpFile = tempnam(sys_get_temp_dir(), 'pfp_') . '.png';
    file_put_contents($tmpFile, $imgData);
    $cloudUrl = uploadProfilePicToCloudinary($tmpFile, 'user_' . $userId . '_' . time());
    @unlink($tmpFile);
    if ($cloudUrl === null) {
        echo json_encode(["success" => false, "error" => "Upload to Cloudinary failed"]);
        exit();
    }
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $cloudUrl, $userId);
    $stmt->execute();
    echo json_encode(["success" => true, "url" => $cloudUrl]);
    exit();
}

// ── Delete profile picture ────────────────────────────────────────────────
if (isset($_POST['delete_profile_picture'])) {
    if (!empty($user['profile_picture'])) {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $success = ($lang === 'en') ? "Profile picture deleted successfully." : "ลบรูปโปรไฟล์สำเร็จแล้ว";
        $user['profile_picture'] = null;
    } else {
        $errors[] = ($lang === 'en') ? "No profile picture to delete." : "ไม่มีรูปโปรไฟล์ที่จะลบ";
    }
}

// ── Change Password — Step 1: validate & send OTP ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pw_step1'])) {
    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password']     ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPw, $user['password'])) {
        $pwErrors[] = ($lang === 'en') ? 'Current password is incorrect.' : 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    } elseif (strlen($newPw) < 6 || strlen($newPw) > 12) {
        $pwErrors[] = ($lang === 'en') ? 'New password must be 6–12 characters.' : 'รหัสผ่านใหม่ต้องมี 6–12 ตัวอักษร';
    } elseif (!preg_match('/[0-9]/', $newPw)) {
        $pwErrors[] = ($lang === 'en') ? 'New password must contain at least one number.' : 'รหัสผ่านใหม่ต้องมีตัวเลขอย่างน้อยหนึ่งตัว';
    } elseif (!preg_match('/[a-z]/', $newPw)) {
        $pwErrors[] = ($lang === 'en') ? 'New password must contain at least one lowercase letter.' : 'รหัสผ่านใหม่ต้องมีตัวพิมพ์เล็กอย่างน้อยหนึ่งตัว';
    } elseif (!preg_match('/[A-Z]/', $newPw)) {
        $pwErrors[] = ($lang === 'en') ? 'New password must contain at least one capital letter.' : 'รหัสผ่านใหม่ต้องมีตัวพิมพ์ใหญ่อย่างน้อยหนึ่งตัว';
    } elseif ($newPw !== $confirmPw) {
        $pwErrors[] = ($lang === 'en') ? 'Passwords do not match.' : 'รหัสผ่านไม่ตรงกัน';
    } elseif (empty($user['email'])) {
        $pwErrors[] = ($lang === 'en')
            ? 'Your account has no email address. Please contact support.'
            : 'บัญชีของคุณไม่มีที่อยู่อีเมล กรุณาติดต่อผู้ดูแลระบบ';
    } else {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        // Mask email for display: d***@gmail.com
        $emailParts  = explode('@', $user['email']);
        $maskedEmail = substr($emailParts[0], 0, 1)
                     . str_repeat('*', max(1, strlen($emailParts[0]) - 1))
                     . '@' . $emailParts[1];
        $_SESSION['pw_change'] = [
            'otp'          => $otp,
            'expires'      => time() + 300,
            'hash'         => password_hash($newPw, PASSWORD_DEFAULT),
            'masked_email' => $maskedEmail,
        ];
        if (sendOTPEmail($user['email'], $otp)) {
            // OTP step is shown via session state below
        } else {
            unset($_SESSION['pw_change']);
            $pwErrors[] = ($lang === 'en')
                ? 'Failed to send OTP email. Please try again.'
                : 'ไม่สามารถส่ง OTP ได้ กรุณาลองใหม่';
        }
    }
}

// ── Change Password — Step 2: verify OTP & apply ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pw_step2'])) {
    $pending = $_SESSION['pw_change'] ?? null;
    $entered = trim($_POST['otp_code'] ?? '');

    if (!$pending) {
        $pwErrors[] = ($lang === 'en') ? 'Session expired. Please start over.' : 'เซสชันหมดอายุ กรุณาเริ่มใหม่';
    } elseif (time() > $pending['expires']) {
        unset($_SESSION['pw_change']);
        $pwErrors[] = ($lang === 'en') ? 'OTP has expired. Please start over.' : 'OTP หมดอายุแล้ว กรุณาเริ่มใหม่';
    } elseif ($entered !== $pending['otp']) {
        $pwErrors[] = ($lang === 'en') ? 'Incorrect OTP. Please try again.' : 'OTP ไม่ถูกต้อง กรุณาลองใหม่';
    } else {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $pending['hash'], $userId);
        $stmt->execute();
        $stmt->close();
        unset($_SESSION['pw_change']);
        $success = ($lang === 'en') ? 'Password changed successfully.' : 'เปลี่ยนรหัสผ่านสำเร็จแล้ว';
    }
}

// ── Change Password — Resend OTP ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pw_resend'])) {
    $pending = $_SESSION['pw_change'] ?? null;
    if ($pending) {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['pw_change']['otp']     = $otp;
        $_SESSION['pw_change']['expires'] = time() + 300;
        if (!sendOTPEmail($user['email'], $otp)) {
            $pwErrors[] = ($lang === 'en') ? 'Failed to resend OTP. Please try again.' : 'ไม่สามารถส่ง OTP ซ้ำได้ กรุณาลองใหม่';
        }
    } else {
        $pwErrors[] = ($lang === 'en') ? 'Session expired. Please start over.' : 'เซสชันหมดอายุ กรุณาเริ่มใหม่';
    }
}

// ── Cancel OTP step ───────────────────────────────────────────────────────
if (isset($_POST['pw_cancel'])) {
    unset($_SESSION['pw_change']);
}

// ── Change Email — Step 1: validate & send OTP to new email ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_step1'])) {
    $currentPw = $_POST['email_current_password'] ?? '';
    $newEmail  = trim($_POST['new_email'] ?? '');

    if (!password_verify($currentPw, $user['password'])) {
        $emailErrors[] = ($lang === 'en') ? 'Current password is incorrect.' : 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $emailErrors[] = ($lang === 'en') ? 'Please enter a valid email address.' : 'กรุณากรอกที่อยู่อีเมลที่ถูกต้อง';
    } elseif (strtolower($newEmail) === strtolower($user['email'] ?? '')) {
        $emailErrors[] = ($lang === 'en') ? 'New email is the same as your current email.' : 'อีเมลใหม่ตรงกับอีเมลปัจจุบันของคุณ';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk->bind_param("si", $newEmail, $userId);
        $chk->execute();
        $chk->store_result();
        $taken = $chk->num_rows > 0;
        $chk->close();
        if ($taken) {
            $emailErrors[] = ($lang === 'en') ? 'That email address is already in use.' : 'ที่อยู่อีเมลนี้ถูกใช้งานแล้ว';
        } else {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['email_change'] = [
                'otp'       => $otp,
                'expires'   => time() + 300,
                'new_email' => $newEmail,
            ];
            if (!sendOTPEmail($newEmail, $otp)) {
                unset($_SESSION['email_change']);
                $emailErrors[] = ($lang === 'en') ? 'Failed to send OTP. Please try again.' : 'ไม่สามารถส่ง OTP ได้ กรุณาลองใหม่';
            }
        }
    }
}

// ── Change Email — Step 2: verify OTP & apply ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_step2'])) {
    $pending = $_SESSION['email_change'] ?? null;
    $entered = trim($_POST['email_otp_code'] ?? '');

    if (!$pending) {
        $emailErrors[] = ($lang === 'en') ? 'Session expired. Please start over.' : 'เซสชันหมดอายุ กรุณาเริ่มใหม่';
    } elseif (time() > $pending['expires']) {
        unset($_SESSION['email_change']);
        $emailErrors[] = ($lang === 'en') ? 'OTP has expired. Please start over.' : 'OTP หมดอายุแล้ว กรุณาเริ่มใหม่';
    } elseif ($entered !== $pending['otp']) {
        $emailErrors[] = ($lang === 'en') ? 'Incorrect OTP. Please try again.' : 'OTP ไม่ถูกต้อง กรุณาลองใหม่';
    } else {
        $newEmail = $pending['new_email'];
        $upd = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $upd->bind_param("si", $newEmail, $userId);
        $upd->execute();
        $upd->close();
        unset($_SESSION['email_change']);
        $user['email'] = $newEmail;
        $success = ($lang === 'en') ? 'Email changed successfully.' : 'เปลี่ยนอีเมลสำเร็จแล้ว';
    }
}

// ── Change Email — Resend OTP ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email_resend'])) {
    $pending = $_SESSION['email_change'] ?? null;
    if ($pending) {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['email_change']['otp']     = $otp;
        $_SESSION['email_change']['expires'] = time() + 300;
        if (!sendOTPEmail($pending['new_email'], $otp)) {
            $emailErrors[] = ($lang === 'en') ? 'Failed to resend OTP. Please try again.' : 'ไม่สามารถส่ง OTP ซ้ำได้ กรุณาลองใหม่';
        }
    } else {
        $emailErrors[] = ($lang === 'en') ? 'Session expired. Please start over.' : 'เซสชันหมดอายุ กรุณาเริ่มใหม่';
    }
}

// ── Cancel email change ───────────────────────────────────────────────────
if (isset($_POST['email_cancel'])) {
    unset($_SESSION['email_change']);
}

// ── Delete Account ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirmText = trim($_POST['confirm_delete'] ?? '');
    $expected    = ($user['username'] ?? '') . '-delete';
    $delPassword = $_POST['delete_password'] ?? '';
    if ($confirmText !== $expected) {
        $deleteErrors[] = ($lang === 'en') ? 'Confirmation text does not match.' : 'ข้อความยืนยันไม่ตรงกัน';
    } elseif (!password_verify($delPassword, $user['password'])) {
        $deleteErrors[] = ($lang === 'en') ? 'Incorrect password.' : 'รหัสผ่านไม่ถูกต้อง';
    } else {
        $del = $conn->prepare("DELETE FROM users WHERE id = ?");
        $del->bind_param("i", $userId);
        $del->execute();
        $del->close();
        $_SESSION = [];
        session_destroy();
        header('Location: index.php');
        exit();
    }
}

// ── Google — flash messages from callback ────────────────────────────────
$googleErrors = [];
if (!empty($_SESSION['flash_google_error'])) {
    $googleErrors[] = $_SESSION['flash_google_error'];
    unset($_SESSION['flash_google_error']);
}
if (!empty($_SESSION['flash_google_success'])) {
    $success = $_SESSION['flash_google_success'];
    unset($_SESSION['flash_google_success']);
}

// ── Google — unlink ───────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unlink_google'])) {
    $upd = $conn->prepare("UPDATE users SET google_id = NULL WHERE id = ?");
    $upd->bind_param("i", $userId);
    $upd->execute();
    $upd->close();
    $user['google_id'] = null;
    $success = ($lang === 'en') ? 'Google account unlinked.' : 'ยกเลิกการเชื่อมต่อ Google สำเร็จแล้ว';
}

$googleLinked = !empty($user['google_id']);
$profilePicUrl = (!empty($user['profile_picture']) && str_starts_with($user['profile_picture'], 'https://'))
    ? $user['profile_picture'] : '';
$hasPic = $profilePicUrl !== '';
$otpPending      = !empty($_SESSION['pw_change'])     && time() <= $_SESSION['pw_change']['expires'];
$emailOtpPending = !empty($_SESSION['email_change'])  && time() <= $_SESSION['email_change']['expires'];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
  <title><?php echo ($lang === 'en') ? 'Settings - Future X' : 'การตั้งค่า - Future X'; ?></title>
  <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">

  <style>
    :root {
      --brand-color: #007BFF;
      --brand-hover: #0056b3;
      --brand-deep:  #003f7f;
      --ink: #1F2937;
    }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      display: flex;
      flex-direction: column;
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      color: var(--ink);
      min-height: 100vh;
    }

    /* ── Centered content column ── */
    .page-wrapper {
      max-width: 580px;
      width: 100%;
      margin: 50px auto 60px;
      padding: 0 20px;
      box-sizing: border-box;
    }

    /* ── Settings cards ── */
    .settings-card {
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 32px;
      box-shadow: 0 12px 32px rgba(0,0,0,0.12);
      margin-bottom: 24px;
      scroll-margin-top: 76px;
    }

    .settings-card h2 {
      font-size: 1.2rem;
      font-weight: 700;
      margin: 0 0 20px;
      color: var(--brand-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* ── TOC — fixed to viewport right, never shifts content ── */
    .toc-sidebar {
      position: fixed;
      top: 76px;
      right: 24px;
      width: 160px;
      background: rgba(255,255,255,0.35);
      backdrop-filter: blur(10px);
      border-radius: 14px;
      padding: 14px 0 10px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
      z-index: 50;
    }

    .toc-title {
      font-size: 0.68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.09em;
      color: #9ca3af;
      padding: 0 16px 10px;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      margin-bottom: 6px;
    }

    .toc-link {
      display: block;
      padding: 7px 16px;
      font-size: 0.85rem;
      color: #374151;
      text-decoration: none;
      border-left: 2px solid transparent;
      transition: color 0.2s, border-color 0.2s, background 0.2s;
    }

    .toc-link:hover {
      color: var(--brand-color);
      border-left-color: var(--brand-color);
      background: rgba(0,123,255,0.05);
      text-decoration: none;
    }

    .toc-link.active {
      color: var(--brand-color);
      border-left-color: var(--brand-color);
      font-weight: 600;
      background: rgba(0,123,255,0.08);
    }

    /* Hide TOC when screen is too narrow to show it beside the content */
    @media (max-width: 900px) {
      .toc-sidebar { display: none; }
    }

    @media (max-width: 640px) {
      .page-wrapper { margin-top: 24px; }
    }

    /* ── Profile picture ── */
    .profile-pic { max-width: 120px; border-radius: 50%; margin-bottom: 10px; background-color: #ccc; }
    .centered    { display: flex; justify-content: center; align-items: center; margin-bottom: 10px; }

    .file-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .choose-btn {
      position: relative; overflow: hidden; background-color: #fff;
      border: 1px solid #d1d5db; border-radius: 12px; font-weight: 600;
      font-size: 0.95rem; padding: 10px 20px; cursor: pointer;
      transition: background-color 0.2s; width: 150px; text-align: center;
    }
    .choose-btn:hover { background-color: #e5e7eb; }
    .choose-btn input { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .file-label { flex: 1; font-size: 0.95rem; color: #4B5563; }

    /* ── Primary button ── */
    .btn-modern {
      width: 100%; margin-top: 12px; padding: 14px; font-size: 1.1rem;
      font-weight: 600; border-radius: 14px; transition: all 0.3s ease;
      display: block; border: none; cursor: pointer; text-align: center; color: #fff;
      background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
    }
    .btn-modern:hover {
      background: linear-gradient(135deg, var(--brand-hover), var(--brand-deep));
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,123,255,0.35);
    }
    .btn-modern:disabled { opacity: 0.7; transform: none; cursor: not-allowed; }

    /* ── Red gradient button (destructive actions) ── */
    .btn-modern-red {
      width: 100%; margin-top: 12px; padding: 14px; font-size: 1.1rem;
      font-weight: 600; border-radius: 14px; transition: all 0.3s ease;
      display: block; border: none; cursor: pointer; text-align: center; color: #fff;
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }
    .btn-modern-red:hover {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(239,68,68,0.35);
    }
    .btn-modern-red:disabled { opacity: 0.7; transform: none; cursor: not-allowed; }

    /* ── Secondary button (cancel / resend) ── */
    .btn-secondary-flat {
      width: 100%; margin-top: 8px; padding: 12px; font-size: 1rem;
      font-weight: 600; border-radius: 14px; transition: all 0.3s ease;
      display: block; border: none; cursor: pointer; text-align: center;
      background: #6B7280; color: #fff;
    }
    .btn-secondary-flat:hover { background: #4B5563; transform: translateY(-2px); }

    /* ── Form inputs ── */
    .pw-input {
      width: 100%; border-radius: 12px; padding: 12px; font-size: 1rem;
      border: 1px solid #E5E7EB; transition: box-shadow .2s, border-color .2s;
      margin-bottom: 12px; box-sizing: border-box; background: #fff;
    }
    .pw-input:focus {
      border-color: var(--brand-color);
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.2);
      outline: none;
    }

    /* ── OTP input — large digits ── */
    .otp-input {
      width: 100%; border-radius: 12px; padding: 16px; font-size: 2rem;
      font-weight: 700; letter-spacing: 0.4em; text-align: center;
      border: 1px solid #E5E7EB; transition: box-shadow .2s, border-color .2s;
      margin-bottom: 12px; box-sizing: border-box; background: #fff;
    }
    .otp-input:focus {
      border-color: var(--brand-color);
      box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.2);
      outline: none;
    }

    .otp-hint {
      text-align: center; font-size: 0.9rem; color: #6b7280; margin-bottom: 16px;
    }

    /* ── Crop modal ── */
    .cropper-crop-box, .cropper-view-box { border-radius: 50%; }
    .modal-body    { max-height: 65vh; overflow: auto; }
    .modal-content { background-color: #f8f9fa; border-radius: 12px; overflow: hidden; }
    .modal-header  { border-bottom: 1px solid #dcdcdc; }

    #cropModal .modal-content {
      border-radius: 20px; border: none;
      box-shadow: 0 12px 40px rgba(0,0,0,0.18);
    }
    #cropModal .modal-header { padding: 18px 22px; }
    #cropModal .modal-title  { font-weight: 700; font-size: 1.05rem; color: #1f2937; }
    #cropModal .modal-body   { padding: 22px !important; max-height: none; overflow: visible; }

    #cropContainer {
      width: 100%; height: min(55vh, 420px);
      background: #f1f3f5; border-radius: 16px; overflow: hidden; touch-action: none;
    }

    /* ── Zoom controls ── */
    #zoomControls { display: flex; align-items: center; gap: 12px; margin-top: 18px; }
    #zoomControls input[type=range] { flex: 1; accent-color: var(--brand-color); height: 4px; cursor: pointer; }
    .zoom-btn {
      width: 38px; height: 38px; flex-shrink: 0; border-radius: 50%;
      border: 1px solid #e5e7eb; background: #fff; color: #374151;
      font-size: 1.3rem; font-weight: 700; line-height: 1; padding-bottom: 2px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: all 0.2s; user-select: none;
    }
    .zoom-btn:hover  { background: #f3f4f6; border-color: #d1d5db; }
    .zoom-btn:active { transform: scale(0.94); }

    /* ── Cancel / Confirm ── */
    #cropControls { display: flex; gap: 12px; margin-top: 20px; }
    #cropControls .control-btn {
      flex: 1; padding: 13px 0; font-size: 1rem; font-weight: 700;
      border: none; border-radius: 14px; cursor: pointer; transition: all 0.25s ease;
    }
    #cropControls .cancel { background: #f3f4f6; color: #4b5563; }
    #cropControls .cancel:hover { background: #e5e7eb; transform: translateY(-2px); }
    #cropControls .confirm {
      color: #fff;
      background: linear-gradient(135deg, #22c55e, #16a34a);
      box-shadow: 0 4px 12px rgba(34,197,94,0.3);
    }
    #cropControls .confirm:hover {
      background: linear-gradient(135deg, #16a34a, #15803d);
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(34,197,94,0.4);
    }

    /* ── Navbar overrides ── */
    .top-banner {
      background-color: var(--brand-color);
      display: flex; justify-content: space-between; align-items: center;
      height: 60px; position: sticky; top: 0; z-index: 1000;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
      transition: background-color 0.3s, box-shadow 0.3s;
    }
    .top-banner.scrolled { background-color: var(--brand-color); box-shadow: none; }
    .nav-links-container { flex: 1; overflow-x: auto; position: relative; padding: 12px 20px; }
    .nav-links { display: flex; gap: 12px; white-space: nowrap; }
    .nav-links::-webkit-scrollbar { display: none; }
    .nav-scroll-indicator {
      position: absolute; top: 0; left: 0; height: 3px;
      background: #fff; border-radius: 2px; width: 0%; transition: width 0.2s;
    }
    .nav-links a {
      text-decoration: none; color: #fff; font-weight: 500;
      padding: 8px 12px; border-radius: 4px; flex-shrink: 0;
      transition: background 0.3s, transform 0.15s, opacity 0.15s;
    }
    .nav-links a:hover { background-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
    .lang-dropdown { position: relative; flex-shrink: 0; }
    .lang-btn-icon {
      width: 42px; height: 42px; display: grid; place-items: center;
      border: 1px solid rgba(255,255,255,0.35); background: rgba(255,255,255,0.18);
      color: #fff; border-radius: 50%; cursor: pointer;
      transition: transform .15s, background .2s;
    }
    .lang-btn-icon:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
    .lang-btn-icon:focus { outline: 2px solid rgba(255,255,255,0.6); outline-offset: 2px; }
    .lang-dropdown-content {
      display: none; position: absolute; right: 0; top: calc(100% + 8px);
      background-color: #fff; min-width: 140px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.2); border-radius: 8px; overflow: hidden;
    }
    .lang-dropdown-content a {
      display: block; color: #333; padding: 12px 16px;
      text-decoration: none; transition: background .2s; white-space: nowrap;
    }
    .lang-dropdown-content a:hover { background: #f2f2f2; }
    .lang-dropdown-content a.active { font-weight: 700; background: #f7f7f7; }
    .right-actions { display: flex; align-items: center; gap: 10px; margin-right: 12px; }
    .lang-btn {
      display: inline-block; padding: 8px 12px; border-radius: 10px; font-weight: 600;
      text-decoration: none; color: #fff; background: rgba(255,255,255,0.18);
      border: 1px solid rgba(255,255,255,0.3); backdrop-filter: blur(4px);
      transition: transform .15s, background .2s;
    }
    .lang-btn:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
    .pw-wrap { position: relative; margin-bottom: 12px; }
    .pw-wrap .pw-input { padding-right: 2.75rem; margin-bottom: 0; }
    .pwd-eye {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      background: none; border: none; padding: 0; cursor: pointer;
      color: #6B7280; line-height: 0; user-select: none; -webkit-user-select: none;
      touch-action: none;
    }
    .pwd-eye:focus { outline: none; }
    .pwd-eye:hover { color: #374151; }

    /* ── Password requirements checklist ── */
    .pw-reqs { list-style: none; margin: 0 0 10px; padding: 0; display: flex; flex-direction: column; gap: 5px; }
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

  <?php include 'navbar.php'; ?>
  <?php include 'scroll-restore.php'; ?>

  <!-- ── TOC sidebar (fixed, never shifts content) ── -->
  <nav class="toc-sidebar" aria-label="On this page">
    <div class="toc-title"><?php echo ($lang === 'en') ? 'On This Page' : 'ในหน้านี้'; ?></div>
    <a class="toc-link" href="#section-profile">
      <?php echo ($lang === 'en') ? 'Profile Picture' : 'รูปโปรไฟล์'; ?>
    </a>
    <a class="toc-link" href="#section-password">
      <?php echo ($lang === 'en') ? 'Change Password' : 'เปลี่ยนรหัสผ่าน'; ?>
    </a>
    <a class="toc-link" href="#section-email">
      <?php echo ($lang === 'en') ? 'Change Email' : 'เปลี่ยนอีเมล'; ?>
    </a>
    <a class="toc-link" href="#section-linked-accounts">
      <?php echo ($lang === 'en') ? 'Linked Accounts' : 'บัญชีที่เชื่อมต่อ'; ?>
    </a>
    <a class="toc-link" href="#section-delete" style="color:#ef4444;">
      <?php echo ($lang === 'en') ? 'Delete Account' : 'ลบบัญชี'; ?>
    </a>
  </nav>

  <div class="page-wrapper">

    <!-- ── Profile Picture ── -->
    <div class="settings-card" id="section-profile">
      <h2>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
        <?php echo ($lang === 'en') ? 'Profile Picture' : 'รูปโปรไฟล์'; ?>
      </h2>

      <?php if ($errors): ?>
        <div class="alert alert-danger"><ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <div class="centered">
        <img id="currentProfilePic"
             src="<?php echo $hasPic ? htmlspecialchars($profilePicUrl) : '/avatar.png'; ?>"
             class="profile-pic" alt="Profile picture">
      </div>

      <div class="file-row">
        <label class="choose-btn">
          <?php echo ($lang === 'en') ? 'Choose File' : 'เลือกไฟล์'; ?>
          <input type="file" accept="image/*" onchange="handleFileSelect(event)">
        </label>
        <div class="file-label" id="fileLabel">
          <?php echo ($lang === 'en') ? 'No file chosen' : 'ยังไม่ได้เลือกไฟล์'; ?>
        </div>
      </div>

      <div class="text-center mt-2">
        <button
          id="deleteProfileBtn"
          type="button"
          class="btn-modern-red"
          data-bs-toggle="modal"
          data-bs-target="#deleteModal"
          <?php echo $hasPic ? '' : 'style="display:none;"'; ?>
        >
          <?php echo ($lang === 'en') ? 'Delete Profile Picture' : 'ลบรูปโปรไฟล์'; ?>
        </button>
      </div>
    </div>

    <!-- ── Change Password ── -->
    <div class="settings-card" id="section-password">
      <h2>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        <?php echo ($lang === 'en') ? 'Change Password' : 'เปลี่ยนรหัสผ่าน'; ?>
      </h2>

      <?php if ($pwErrors): ?>
        <div class="alert alert-danger"><ul class="mb-0">
          <?php foreach ($pwErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <?php if ($otpPending): ?>
        <!-- ── Step 2: Enter OTP ── -->
        <p class="otp-hint">
          <?php
          $maskedDisplay = htmlspecialchars($_SESSION['pw_change']['masked_email'] ?? '');
          if ($lang === 'en') {
              echo "An OTP has been sent to <strong>{$maskedDisplay}</strong>. Check your inbox (and spam folder), then enter it below.";
          } else {
              echo "ส่ง OTP ไปยัง <strong>{$maskedDisplay}</strong> แล้ว ตรวจสอบกล่องจดหมาย (และสแปม) แล้วกรอกรหัสด้านล่าง";
          }
          ?>
        </p>
        <form method="POST" id="otpForm">
          <input type="hidden" name="pw_step2" value="1">
          <input class="otp-input" type="text" name="otp_code"
                 inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                 placeholder="000000" required>
          <button type="submit" class="btn-modern" id="otpBtn">
            <?php echo ($lang === 'en') ? 'Confirm Password Change' : 'ยืนยันการเปลี่ยนรหัสผ่าน'; ?>
          </button>
        </form>
        <form method="POST" style="margin-top:8px;">
          <button type="submit" name="pw_resend" class="btn-secondary-flat">
            <?php echo ($lang === 'en') ? 'Resend OTP' : 'ส่ง OTP ใหม่'; ?>
          </button>
        </form>
        <form method="POST" style="margin-top:8px;">
          <button type="submit" name="pw_cancel" class="btn btn-outline-secondary w-100">
            <?php echo ($lang === 'en') ? 'Cancel' : 'ยกเลิก'; ?>
          </button>
        </form>

      <?php else: ?>
        <!-- ── Step 1: Enter passwords ── -->
        <form method="POST" id="pwForm">
          <input type="hidden" name="pw_step1" value="1">
          <div class="pw-wrap">
            <input class="pw-input" type="password" name="current_password"
              placeholder="<?php echo ($lang === 'en') ? 'Current Password' : 'รหัสผ่านปัจจุบัน'; ?>" required>
            <button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
          <div class="pw-wrap">
            <input class="pw-input" type="password" name="new_password" id="aspNewPw"
              placeholder="<?php echo ($lang === 'en') ? 'New Password' : 'รหัสผ่านใหม่'; ?>" required>
            <button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
          <ul class="pw-reqs">
            <li class="pw-req" id="asp-req-min"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least 6 characters' : 'อย่างน้อย 6 ตัวอักษร'; ?></span></li>
            <li class="pw-req" id="asp-req-max"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'No more than 12 characters' : 'ไม่เกิน 12 ตัวอักษร'; ?></span></li>
            <li class="pw-req" id="asp-req-num"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least one number' : 'มีตัวเลขอย่างน้อย 1 ตัว'; ?></span></li>
            <li class="pw-req" id="asp-req-lower"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least one lowercase letter' : 'มีตัวพิมพ์เล็กอย่างน้อย 1 ตัว'; ?></span></li>
            <li class="pw-req" id="asp-req-upper"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'At least one capital letter' : 'มีตัวพิมพ์ใหญ่อย่างน้อย 1 ตัว'; ?></span></li>
          </ul>
          <div class="pw-wrap">
            <input class="pw-input" type="password" name="confirm_password" id="aspCfPw"
              placeholder="<?php echo ($lang === 'en') ? 'Confirm New Password' : 'ยืนยันรหัสผ่านใหม่'; ?>" required>
            <button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
          <ul class="pw-reqs">
            <li class="pw-req" id="asp-req-match"><span class="pw-req-dot"><svg width="9" height="7" viewBox="0 0 9 7" fill="none"><path d="M1 3.5L3.5 6L8 1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span><?php echo ($lang === 'en') ? 'Passwords match' : 'รหัสผ่านตรงกัน'; ?></span></li>
          </ul>
          <button type="submit" class="btn-modern" id="pwBtn">
            <?php echo ($lang === 'en') ? 'Send OTP to Email' : 'ส่ง OTP ไปยังอีเมล'; ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- ── Change Email ── -->
    <div class="settings-card" id="section-email">
      <h2>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
        </svg>
        <?php echo ($lang === 'en') ? 'Change Email' : 'เปลี่ยนอีเมล'; ?>
      </h2>

      <?php if ($emailErrors): ?>
        <div class="alert alert-danger"><ul class="mb-0">
          <?php foreach ($emailErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <?php if ($emailOtpPending): ?>
        <!-- ── Step 2: Enter OTP ── -->
        <p class="otp-hint">
          <?php
          $newEmailDisplay = htmlspecialchars($_SESSION['email_change']['new_email'] ?? '');
          $parts = explode('@', $_SESSION['email_change']['new_email'] ?? '');
          $masked = substr($parts[0], 0, 1) . str_repeat('*', max(1, strlen($parts[0]) - 1)) . '@' . ($parts[1] ?? '');
          if ($lang === 'en') {
              echo "An OTP has been sent to <strong>" . htmlspecialchars($masked) . "</strong>. Check your inbox (and spam folder), then enter it below.";
          } else {
              echo "ส่ง OTP ไปยัง <strong>" . htmlspecialchars($masked) . "</strong> แล้ว ตรวจสอบกล่องจดหมาย (และสแปม) แล้วกรอกรหัสด้านล่าง";
          }
          ?>
        </p>
        <form method="POST" id="emailOtpForm">
          <input type="hidden" name="email_step2" value="1">
          <input class="otp-input" type="text" name="email_otp_code"
                 inputmode="numeric" maxlength="6" autocomplete="one-time-code"
                 placeholder="000000" required>
          <button type="submit" class="btn-modern" id="emailOtpBtn">
            <?php echo ($lang === 'en') ? 'Confirm Email Change' : 'ยืนยันการเปลี่ยนอีเมล'; ?>
          </button>
        </form>
        <form method="POST" style="margin-top:8px;">
          <button type="submit" name="email_resend" class="btn-secondary-flat">
            <?php echo ($lang === 'en') ? 'Resend OTP' : 'ส่ง OTP ใหม่'; ?>
          </button>
        </form>
        <form method="POST" style="margin-top:8px;">
          <button type="submit" name="email_cancel" class="btn btn-outline-secondary w-100">
            <?php echo ($lang === 'en') ? 'Cancel' : 'ยกเลิก'; ?>
          </button>
        </form>

      <?php else: ?>
        <!-- ── Step 1: Enter new email + current password ── -->
        <?php if (!empty($user['email'])): ?>
          <p style="font-size:0.9rem;color:#6b7280;margin-bottom:16px;">
            <?php echo ($lang === 'en') ? 'Current email: ' : 'อีเมลปัจจุบัน: '; ?>
            <strong><?= htmlspecialchars($user['email']) ?></strong>
          </p>
        <?php endif; ?>
        <form method="POST" id="emailForm">
          <input type="hidden" name="email_step1" value="1">
          <input class="pw-input" type="email" name="new_email"
            placeholder="<?php echo ($lang === 'en') ? 'New Email Address' : 'ที่อยู่อีเมลใหม่'; ?>" required>
          <div class="pw-wrap">
            <input class="pw-input" type="password" name="email_current_password"
              placeholder="<?php echo ($lang === 'en') ? 'Current Password' : 'รหัสผ่านปัจจุบัน'; ?>" required>
            <button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
          </div>
          <button type="submit" class="btn-modern" id="emailBtn">
            <?php echo ($lang === 'en') ? 'Send OTP to New Email' : 'ส่ง OTP ไปยังอีเมลใหม่'; ?>
          </button>
        </form>
      <?php endif; ?>
    </div>

    <!-- ── Linked Accounts ── -->
    <div class="settings-card" id="section-linked-accounts">
      <h2>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
        </svg>
        <?php echo ($lang === 'en') ? 'Linked Accounts' : 'บัญชีที่เชื่อมต่อ'; ?>
      </h2>

      <?php if ($googleErrors): ?>
        <div class="alert alert-danger"><ul class="mb-0">
          <?php foreach ($googleErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid rgba(0,0,0,0.06);">
        <div style="display:flex;align-items:center;gap:12px;">
          <svg width="22" height="22" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path fill="#4285F4" d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844a4.14 4.14 0 0 1-1.796 2.716v2.259h2.908C16.658 14.013 17.64 11.705 17.64 9.2Z"/>
            <path fill="#34A853" d="M9 18c2.43 0 4.467-.806 5.956-2.18l-2.908-2.259c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332A8.997 8.997 0 0 0 9 18Z"/>
            <path fill="#FBBC05" d="M3.964 10.71A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.71V4.958H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.042l3.007-2.332Z"/>
            <path fill="#EA4335" d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0A8.997 8.997 0 0 0 .957 4.958L3.964 6.29C4.672 4.163 6.656 3.58 9 3.58Z"/>
          </svg>
          <div>
            <div style="font-weight:600;font-size:0.95rem;">Google</div>
            <div style="font-size:0.82rem;color:#6b7280;">
              <?php echo $googleLinked
                ? (($lang === 'en') ? 'Connected' : 'เชื่อมต่อแล้ว')
                : (($lang === 'en') ? 'Not connected' : 'ยังไม่ได้เชื่อมต่อ'); ?>
            </div>
          </div>
        </div>
        <?php if ($googleLinked): ?>
          <form method="POST">
            <button type="submit" name="unlink_google"
              style="background:none;border:1.5px solid #e5e7eb;border-radius:10px;padding:7px 14px;font-size:0.85rem;font-weight:600;color:#6b7280;cursor:pointer;transition:all 0.2s;"
              onmouseover="this.style.borderColor='#ef4444';this.style.color='#ef4444';"
              onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#6b7280';">
              <?php echo ($lang === 'en') ? 'Unlink' : 'ยกเลิกการเชื่อมต่อ'; ?>
            </button>
          </form>
        <?php else: ?>
          <a href="/google_auth.php?action=link"
            style="display:inline-flex;align-items:center;gap:6px;border:1.5px solid #e5e7eb;border-radius:10px;padding:7px 14px;font-size:0.85rem;font-weight:600;color:#374151;text-decoration:none;transition:all 0.2s;background:#fff;"
            onmouseover="this.style.background='#f9fafb';this.style.boxShadow='0 2px 6px rgba(0,0,0,0.08)';"
            onmouseout="this.style.background='#fff';this.style.boxShadow='none';">
            <?php echo ($lang === 'en') ? 'Connect' : 'เชื่อมต่อ'; ?>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Delete Account ── -->
    <div class="settings-card" id="section-delete" style="border: 1px solid rgba(239,68,68,0.35);">
      <h2 style="color:#ef4444;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
        </svg>
        <?php echo ($lang === 'en') ? 'Delete Account' : 'ลบบัญชี'; ?>
      </h2>

      <p style="color:#6b7280;font-size:0.9rem;margin-bottom:16px;">
        <?php echo ($lang === 'en')
          ? 'This permanently deletes your account and all data. This cannot be undone.'
          : 'การดำเนินการนี้จะลบบัญชีและข้อมูลทั้งหมดอย่างถาวร ไม่สามารถยกเลิกได้'; ?>
      </p>

      <?php if ($deleteErrors): ?>
        <div class="alert alert-danger"><ul class="mb-0">
          <?php foreach ($deleteErrors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul></div>
      <?php endif; ?>

      <form method="POST" id="deleteForm">
        <input type="hidden" name="delete_account" value="1">
        <label style="font-size:0.9rem;font-weight:600;display:block;margin-bottom:6px;">
          <?php
          $uname = htmlspecialchars($user['username'] ?? '');
          echo ($lang === 'en')
            ? "Type <strong>{$uname}-delete</strong> to confirm:"
            : "พิมพ์ <strong>{$uname}-delete</strong> เพื่อยืนยัน:";
          ?>
        </label>
        <input class="pw-input" type="text" name="confirm_delete"
          placeholder="<?php echo htmlspecialchars(($user['username'] ?? '') . '-delete'); ?>"
          autocomplete="off" required>
        <div class="pw-wrap">
          <input class="pw-input" type="password" name="delete_password"
            placeholder="<?php echo ($lang === 'en') ? 'Current Password' : 'รหัสผ่านปัจจุบัน'; ?>" required>
          <button type="button" class="pwd-eye" aria-label="Hold to show password"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
        <button type="submit" class="btn-modern" id="deleteBtn"
          style="background:linear-gradient(135deg,#ef4444,#b91c1c);">
          <?php echo ($lang === 'en') ? 'Delete My Account' : 'ลบบัญชีของฉัน'; ?>
        </button>
      </form>
    </div>

    <!-- ── Security (coming soon) ──
    <div class="settings-card" id="section-security">
      <h2>Security</h2>
      <button class="btn btn-outline-success w-100" disabled>Create Passkey (coming soon)</button>
    </div>
    -->

  </div><!-- /.page-wrapper -->

  <!-- ── Toast notifications (top-right, same as login.php) ── -->
  <div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index:1080;">
    <?php if (!empty($success)): ?>
      <div class="toast align-items-center text-bg-success border-0 mb-2" role="alert" data-bs-autohide="true" data-bs-delay="4000">
        <div class="d-flex">
          <div class="toast-body"><?= htmlspecialchars($success) ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endif; ?>
    <?php if (!empty($errors) || !empty($pwErrors) || !empty($emailErrors) || !empty($deleteErrors) || !empty($googleErrors)): ?>
      <?php foreach (array_merge($errors, $pwErrors, $emailErrors, $deleteErrors, $googleErrors) as $e): ?>
        <div class="toast align-items-center text-bg-danger border-0 mb-2" role="alert" data-bs-autohide="true" data-bs-delay="6000">
          <div class="d-flex">
            <div class="toast-body"><?= htmlspecialchars($e) ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── Delete confirmation modal ── -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo ($lang === 'en') ? 'Delete Profile Picture' : 'ลบรูปโปรไฟล์'; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php echo ($lang === 'en') ? 'Are you sure you want to delete your profile picture?' : 'คุณแน่ใจหรือไม่ว่าต้องการลบรูปโปรไฟล์ของคุณ?'; ?>
      </div>
      <div class="modal-footer d-flex flex-column gap-2">
        <button type="button" class="btn-secondary-flat" data-bs-dismiss="modal">
          <?php echo ($lang === 'en') ? 'Cancel' : 'ยกเลิก'; ?>
        </button>
        <form action="" method="POST" class="w-100">
          <button type="submit" name="delete_profile_picture" class="btn-modern-red">
            <?php echo ($lang === 'en') ? 'Yes, Delete' : 'ใช่, ลบ'; ?>
          </button>
        </form>
      </div>
    </div></div>
  </div>

  <!-- ── Cropper modal ── -->
  <div class="modal fade" id="cropModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><?php echo ($lang === 'en') ? 'Crop Profile Picture' : 'ครอบตัดรูปโปรไฟล์'; ?></h5>
          <button type="button" class="btn-close" onclick="cancelCrop()"></button>
        </div>
        <div class="modal-body">
          <div id="cropContainer">
            <img id="cropImage" style="max-width:100%;">
          </div>
          <div id="zoomControls">
            <button type="button" class="zoom-btn" id="zoomOutBtn" aria-label="Zoom out">&minus;</button>
            <input type="range" id="zoomSlider" min="0" max="100" value="0" step="1">
            <button type="button" class="zoom-btn" id="zoomInBtn" aria-label="Zoom in">&plus;</button>
          </div>
          <div id="cropControls">
            <button type="button" class="control-btn cancel"  onclick="cancelCrop()"><?php echo ($lang === 'en') ? 'Cancel' : 'ยกเลิก'; ?></button>
            <button type="button" class="control-btn confirm" onclick="uploadCropped()"><?php echo ($lang === 'en') ? 'Use Photo' : 'ใช้รูปนี้'; ?></button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
  <script>
    // ── Show all pending toasts ──
    document.querySelectorAll('.toast').forEach(el => new bootstrap.Toast(el).show());

    // ── Cropper ──
    let cropper, cropModalInstance;

    function handleFileSelect(e) {
      const file = e.target.files[0];
      if (!file) return;
      document.getElementById('fileLabel').textContent = file.name;
      const reader = new FileReader();
      reader.onload = () => {
        const img    = document.getElementById('cropImage');
        img.src      = reader.result;
        const modalEl = document.getElementById('cropModal');
        cropModalInstance = new bootstrap.Modal(modalEl);
        modalEl.addEventListener('shown.bs.modal', () => {
          if (cropper) cropper.destroy();
          cropper = new Cropper(img, {
            aspectRatio: 1, viewMode: 1, background: false,
            zoomable: true, zoomOnWheel: true, zoomOnTouch: true, wheelZoomRatio: 0.05,
            dragMode: 'none', cropBoxMovable: true, cropBoxResizable: true, autoCropArea: 1,
            ready() {
              const c    = cropper.getContainerData();
              const size = Math.min(c.width, c.height) * 0.7;
              cropper.setCropBoxData({ width: size, height: size, left: (c.width - size) / 2, top: (c.height - size) / 2 });
              document.querySelector('.cropper-crop-box').style.borderRadius = '50%';
              document.querySelector('.cropper-view-box').style.borderRadius = '50%';
              zoomSlider.value = 0;
              lastZoomSliderValue = 0;
            }
          });
        }, { once: true });
        cropModalInstance.show();
      };
      reader.readAsDataURL(file);
    }

    // ── Zoom controls — buttons + slider, same behavior for mouse/touchpad/touch ──
    const zoomSlider = document.getElementById('zoomSlider');
    let lastZoomSliderValue = 0;

    function zoomBy(ratio) {
      if (!cropper) return;
      cropper.zoom(ratio);
    }

    document.getElementById('zoomInBtn').addEventListener('click', () => zoomBy(0.1));
    document.getElementById('zoomOutBtn').addEventListener('click', () => zoomBy(-0.1));

    zoomSlider.addEventListener('input', () => {
      const value = Number(zoomSlider.value);
      const delta = (value - lastZoomSliderValue) / 100; // slider spans 0-100 → zoom ratio -1..1
      lastZoomSliderValue = value;
      zoomBy(delta);
    });

    function uploadCropped() {
      if (!cropper) return;
      const dataURL = cropper.getCroppedCanvas({ width: 300, height: 300 }).toDataURL();
      fetch('', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'cropped_image=' + encodeURIComponent(dataURL)
      })
      .then(r => r.json())
      .then(d => {
        if (d.success) {
          const newSrc = d.url;
          document.getElementById('currentProfilePic').src = newSrc;
          const navPic = document.getElementById('profileIcon');
          if (navPic) navPic.src = newSrc;
          const deleteBtn = document.getElementById('deleteProfileBtn');
          if (deleteBtn) deleteBtn.style.display = 'block';
          cropModalInstance.hide();
          cropper.destroy();
          cropper = null;
        }
      });
    }

    function cancelCrop() {
      if (cropper) { cropper.destroy(); cropper = null; }
      document.getElementById('fileLabel').textContent = '<?php echo ($lang === 'en') ? 'No file chosen' : 'ยังไม่ได้เลือกไฟล์'; ?>';
      document.getElementById('cropImage').src = '';
      if (cropModalInstance) cropModalInstance.hide();
    }

    // ── Spinners on form submit ──
    const pwForm  = document.getElementById('pwForm');
    const otpForm = document.getElementById('otpForm');

    if (pwForm) {
      pwForm.addEventListener('submit', () => {
        const btn = document.getElementById('pwBtn');
        btn.disabled = true;
        btn.innerHTML = '<?php echo ($lang === 'en') ? 'Sending OTP...' : 'กำลังส่ง OTP...'; ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>';
      });
    }

    if (otpForm) {
      otpForm.addEventListener('submit', () => {
        const btn = document.getElementById('otpBtn');
        btn.disabled = true;
        btn.innerHTML = '<?php echo ($lang === 'en') ? 'Verifying...' : 'กำลังตรวจสอบ...'; ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>';
      });
    }

    const emailForm    = document.getElementById('emailForm');
    const emailOtpForm = document.getElementById('emailOtpForm');

    if (emailForm) {
      emailForm.addEventListener('submit', () => {
        const btn = document.getElementById('emailBtn');
        btn.disabled = true;
        btn.innerHTML = '<?php echo ($lang === 'en') ? 'Sending OTP...' : 'กำลังส่ง OTP...'; ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>';
      });
    }
    if (emailOtpForm) {
      emailOtpForm.addEventListener('submit', () => {
        const btn = document.getElementById('emailOtpBtn');
        btn.disabled = true;
        btn.innerHTML = '<?php echo ($lang === 'en') ? 'Verifying...' : 'กำลังตรวจสอบ...'; ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>';
      });
    }

    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
      deleteForm.addEventListener('submit', () => {
        const btn = document.getElementById('deleteBtn');
        btn.disabled = true;
        btn.innerHTML = '<?php echo ($lang === 'en') ? 'Deleting...' : 'กำลังลบ...'; ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>';
      });
    }

    // ── TOC active highlight ──
    const tocLinks = document.querySelectorAll('.toc-link');
    const sections = Array.from(document.querySelectorAll('.settings-card[id]'));
    const OFFSET   = 80; // px from viewport top that triggers a section as active

    function updateToc() {
      // When scrolled to the bottom, always mark the last section active.
      // This handles short pages where the last section never crosses OFFSET.
      const atBottom = window.scrollY > 0 &&
                       (window.scrollY + window.innerHeight) >= (document.documentElement.scrollHeight - 20);

      let bestId = sections[0]?.id ?? '';

      if (atBottom) {
        bestId = sections[sections.length - 1]?.id ?? bestId;
      } else {
        // Walk in order; last section whose top has passed OFFSET wins.
        sections.forEach(sec => {
          if (sec.getBoundingClientRect().top < OFFSET) bestId = sec.id;
        });
      }

      tocLinks.forEach(link =>
        link.classList.toggle('active', link.getAttribute('href') === '#' + bestId)
      );
    }

    window.addEventListener('scroll', updateToc, { passive: true });
    window.addEventListener('resize', updateToc, { passive: true });
    updateToc();

    document.querySelectorAll('.pwd-eye').forEach(function(btn) {
      var inp = btn.previousElementSibling;
      btn.addEventListener('mousedown',  function()  { inp.type = 'text'; });
      btn.addEventListener('mouseup',    function()  { inp.type = 'password'; });
      btn.addEventListener('mouseleave', function()  { inp.type = 'password'; });
      btn.addEventListener('touchstart', function(e) { e.preventDefault(); inp.type = 'text'; }, { passive: false });
      btn.addEventListener('touchend',   function()  { inp.type = 'password'; });
    });

    // ── Live password requirements checklist (Change Password section) ──
    (function() {
      var newPw = document.getElementById('aspNewPw');
      var cfPw  = document.getElementById('aspCfPw');
      if (!newPw) return;
      function set(id, met) {
        var el = document.getElementById(id);
        if (el) el.classList.toggle('met', met);
      }
      function checkReqs() {
        var v = newPw.value;
        set('asp-req-min',   v.length >= 6);
        set('asp-req-max',   v.length > 0 && v.length <= 12);
        set('asp-req-num',   /[0-9]/.test(v));
        set('asp-req-lower', /[a-z]/.test(v));
        set('asp-req-upper', /[A-Z]/.test(v));
        checkMatch();
      }
      function checkMatch() {
        if (!cfPw) return;
        set('asp-req-match', cfPw.value.length > 0 && cfPw.value === newPw.value);
      }
      newPw.addEventListener('input', checkReqs);
      if (cfPw) cfPw.addEventListener('input', checkMatch);
    })();
  </script>
</body>
</html>
