<?php
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$userId  = (int)$_SESSION['user_id'];
$success  = "";
$errors   = [];
$pwSuccess = "";
$pwErrors  = [];

$uploadDir = 'uploads/profile_pics/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// AJAX: upload cropped profile picture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cropped_image'])) {
    $data = str_replace('data:image/png;base64,', '', $_POST['cropped_image']);
    $data = base64_decode($data);
    $newFileName = 'user_' . $userId . '_' . time() . '.png';
    file_put_contents($uploadDir . $newFileName, $data);
    if (!empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture']))
        unlink($uploadDir . $user['profile_picture']);
    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $newFileName, $userId);
    $stmt->execute();
    echo json_encode(["success" => true, "filename" => $newFileName]);
    exit();
}

// Delete profile picture
if (isset($_POST['delete_profile_picture'])) {
    if (!empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture'])) {
        unlink($uploadDir . $user['profile_picture']);
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $success = ($lang === 'en') ? "Profile picture deleted successfully." : "ลบรูปโปรไฟล์สำเร็จแล้ว";
        $user['profile_picture'] = null;
    } else {
        $errors[] = ($lang === 'en') ? "No profile picture to delete." : "ไม่มีรูปโปรไฟล์ที่จะลบ";
    }
}

// Change password — requires current password for security
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password']     ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPw, $user['password'])) {
        $pwErrors[] = ($lang === 'en') ? 'Current password is incorrect.' : 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
    } elseif (strlen($newPw) < 8) {
        $pwErrors[] = ($lang === 'en') ? 'New password must be at least 8 characters.' : 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
    } elseif (!preg_match('/\d/', $newPw)) {
        $pwErrors[] = ($lang === 'en') ? 'New password must contain at least one number.' : 'รหัสผ่านใหม่ต้องมีตัวเลขอย่างน้อยหนึ่งตัว';
    } elseif ($newPw !== $confirmPw) {
        $pwErrors[] = ($lang === 'en') ? 'Passwords do not match.' : 'รหัสผ่านไม่ตรงกัน';
    } else {
        $hashed = password_hash($newPw, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $userId);
        $stmt->execute();
        $stmt->close();
        $pwSuccess = ($lang === 'en') ? 'Password changed successfully.' : 'เปลี่ยนรหัสผ่านสำเร็จแล้ว';
    }
}

$hasPic = !empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture']);
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
      --gray-color:  #ccc;
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

    /* ── Page layout ── */
    .page-wrapper {
      display: flex;
      align-items: flex-start;
      gap: 28px;
      max-width: 900px;
      width: 100%;
      margin: 50px auto;
      padding: 0 20px 60px;
      box-sizing: border-box;
    }

    .settings-main { flex: 1; min-width: 0; }

    /* ── Settings cards ── */
    .settings-card {
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 32px 32px;
      box-shadow: 0 12px 32px rgba(0,0,0,0.12);
      margin-bottom: 24px;
    }

    .settings-card h2 {
      font-size: 1.25rem;
      font-weight: 700;
      margin: 0 0 20px;
      color: var(--brand-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* ── TOC sidebar (Railway-style) ── */
    .toc-sidebar {
      width: 175px;
      flex-shrink: 0;
      position: sticky;
      top: 76px;
      background: rgba(255,255,255,0.35);
      backdrop-filter: blur(10px);
      border-radius: 14px;
      padding: 14px 0 10px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.08);
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

    @media (max-width: 768px) {
      .toc-sidebar  { display: none; }
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
      transition: background-color 0.2s ease; width: 150px; text-align: center;
    }
    .choose-btn:hover { background-color: #e5e7eb; }
    .choose-btn input { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .file-label { flex: 1; font-size: 0.95rem; color: #4B5563; }

    /* ── Buttons ── */
    .btn-modern {
      width: 100%; margin-top: 12px; padding: 14px; font-size: 1.1rem;
      font-weight: 600; border-radius: 14px; transition: all 0.3s ease;
      display: block; border: none; cursor: pointer; text-align: center;
    }
    .btn-modern.btn-primary {
      background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
      color: #fff;
    }
    .btn-modern.btn-primary:hover {
      background: linear-gradient(135deg, var(--brand-hover), var(--brand-deep));
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,123,255,0.35);
    }

    /* ── Password inputs ── */
    .pw-input {
      width: 100%;
      border-radius: 12px;
      padding: 12px;
      font-size: 1rem;
      border: 1px solid #E5E7EB;
      transition: box-shadow .2s ease, border-color .2s ease;
      margin-bottom: 12px;
      box-sizing: border-box;
    }
    .pw-input:focus {
      border-color: var(--brand-color);
      box-shadow: 0 0 0 0.25rem rgba(0,123,255,0.2);
      outline: none;
    }

    /* ── Crop controls ── */
    .cropper-crop-box, .cropper-view-box { border-radius: 50%; }
    #cropControls {
      display: flex; justify-content: space-between; gap: 8px;
      padding: 0 16px 16px;
    }
    #cropControls .control-btn {
      flex: 1; padding: 10px 0; font-size: 1.1rem; font-weight: 700;
      color: #fff; border: none; border-radius: 8px; cursor: pointer;
    }
    #cropControls .cancel  { background-color: #ef4444; }
    #cropControls .confirm { background-color: #22c55e; }
    .modal-body    { max-height: 65vh; overflow: auto; }
    .modal-content { background-color: #f8f9fa; border-radius: 12px; overflow: hidden; }
    .modal-header  { border-bottom: 1px solid #dcdcdc; }

    /* ── Navbar overrides ── */
    .top-banner {
      background-color: var(--brand-color);
      display: flex; justify-content: space-between; align-items: center;
      height: 60px; position: sticky; top: 0; z-index: 1000;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
      transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }
    .top-banner.scrolled { background-color: var(--brand-color); box-shadow: none; }
    .nav-links-container { flex: 1; overflow-x: auto; position: relative; padding: 12px 20px; }
    .nav-links { display: flex; gap: 12px; white-space: nowrap; }
    .nav-links::-webkit-scrollbar { display: none; }
    .nav-scroll-indicator {
      position: absolute; top: 0; left: 0; height: 3px;
      background: #fff; border-radius: 2px; width: 0%; transition: width 0.2s linear;
    }
    .nav-links a {
      text-decoration: none; color: #fff; font-weight: 500;
      padding: 8px 12px; border-radius: 4px; flex-shrink: 0;
      transition: background 0.3s, transform 0.15s ease, opacity 0.15s ease;
    }
    .nav-links a:hover { background-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
    .lang-dropdown { position: relative; flex-shrink: 0; }
    .lang-btn-icon {
      width: 42px; height: 42px; display: grid; place-items: center;
      border: 1px solid rgba(255,255,255,0.35); background: rgba(255,255,255,0.18);
      color: #fff; border-radius: 50%; cursor: pointer;
      transition: transform .15s ease, background .2s ease;
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
      transition: transform .15s ease, background .2s ease;
    }
    .lang-btn:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); }
  </style>
</head>
<body>

  <?php include 'includes/navbar.php'; ?>

  <div class="page-wrapper">

    <!-- ── Main content ── -->
    <div class="settings-main">

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
               src="<?php echo $hasPic ? $uploadDir . htmlspecialchars($user['profile_picture']) : 'avatar.png'; ?>"
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
            class="btn btn-danger mb-2 w-100 fw-bold"
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
        <?php if ($pwSuccess): ?>
          <div class="alert alert-success"><?= htmlspecialchars($pwSuccess) ?></div>
        <?php endif; ?>

        <form method="POST" id="pwForm">
          <input class="pw-input" type="password" name="current_password"
            placeholder="<?php echo ($lang === 'en') ? 'Current Password' : 'รหัสผ่านปัจจุบัน'; ?>" required>
          <input class="pw-input" type="password" name="new_password"
            placeholder="<?php echo ($lang === 'en') ? 'New Password' : 'รหัสผ่านใหม่'; ?>" required>
          <input class="pw-input" type="password" name="confirm_password"
            placeholder="<?php echo ($lang === 'en') ? 'Confirm New Password' : 'ยืนยันรหัสผ่านใหม่'; ?>" required>
          <button type="submit" name="change_password" class="btn-modern btn-primary" id="pwBtn">
            <?php echo ($lang === 'en') ? 'Update Password' : 'อัปเดตรหัสผ่าน'; ?>
          </button>
        </form>
      </div>

      <!-- ── Linked Accounts (coming soon) ──
      <div class="settings-card" id="section-linked-accounts">
        <h2>Link Your Accounts</h2>
        <button class="btn btn-outline-success w-100 mb-2" disabled>Link Google Account (coming soon)</button>
        <button class="btn btn-outline-success w-100" disabled>Link Apple Account (coming soon)</button>
      </div>
      -->

      <!-- ── Security (coming soon) ──
      <div class="settings-card" id="section-security">
        <h2>Security</h2>
        <button class="btn btn-outline-success w-100" disabled>Create Passkey (coming soon)</button>
      </div>
      -->

    </div><!-- /.settings-main -->

    <!-- ── TOC Sidebar ── -->
    <nav class="toc-sidebar" aria-label="On this page">
      <div class="toc-title"><?php echo ($lang === 'en') ? 'On This Page' : 'ในหน้านี้'; ?></div>
      <a class="toc-link" href="#section-profile">
        <?php echo ($lang === 'en') ? 'Profile Picture' : 'รูปโปรไฟล์'; ?>
      </a>
      <a class="toc-link" href="#section-password">
        <?php echo ($lang === 'en') ? 'Change Password' : 'เปลี่ยนรหัสผ่าน'; ?>
      </a>
    </nav>

  </div><!-- /.page-wrapper -->

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
        <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">
          <?php echo ($lang === 'en') ? 'Cancel' : 'ยกเลิก'; ?>
        </button>
        <form action="" method="POST" class="w-100">
          <button type="submit" name="delete_profile_picture" class="btn btn-danger w-100">
            <?php echo ($lang === 'en') ? 'Yes, Delete' : 'ใช่, ลบ'; ?>
          </button>
        </form>
      </div>
    </div></div>
  </div>

  <!-- ── Success modal (profile picture deleted) ── -->
  <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><?php echo ($lang === 'en') ? 'Profile Picture Deleted' : 'รูปโปรไฟล์ถูกลบแล้ว'; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php echo ($lang === 'en') ? 'Your profile picture has been successfully deleted.' : 'ลบรูปโปรไฟล์สำเร็จแล้ว'; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal">
          <?php echo ($lang === 'en') ? 'Close' : 'ปิด'; ?>
        </button>
      </div>
    </div></div>
  </div>

  <!-- ── Cropper modal ── -->
  <div class="modal fade" id="cropModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body p-3">
          <img id="cropImage" class="d-block mx-auto" style="max-width:100%;">
        </div>
        <div id="cropControls" class="px-3 pb-3">
          <button type="button" class="control-btn cancel"  onclick="cancelCrop()">✕</button>
          <button type="button" class="control-btn confirm" onclick="uploadCropped()">✔</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
  <script>
    // ── Cropper ──
    let cropper, cropModalInstance;

    function handleFileSelect(e) {
      const file = e.target.files[0];
      if (!file) return;
      document.getElementById('fileLabel').textContent = file.name;
      const reader = new FileReader();
      reader.onload = () => {
        const img = document.getElementById('cropImage');
        img.src = reader.result;
        const modalEl = document.getElementById('cropModal');
        cropModalInstance = new bootstrap.Modal(modalEl);
        modalEl.addEventListener('shown.bs.modal', () => {
          if (cropper) cropper.destroy();
          cropper = new Cropper(img, {
            aspectRatio: 1, viewMode: 1, background: false,
            zoomable: true, dragMode: 'move', cropBoxResizable: true, autoCropArea: 1,
            ready() {
              const c    = cropper.getContainerData();
              const size = Math.min(c.width, c.height) * 0.6;
              cropper.setCropBoxData({ width: size, height: size, left: (c.width - size) / 2, top: (c.height - size) / 2 });
              document.querySelector('.cropper-crop-box').style.borderRadius = '50%';
              document.querySelector('.cropper-view-box').style.borderRadius = '50%';
            }
          });
        }, { once: true });
        cropModalInstance.show();
      };
      reader.readAsDataURL(file);
    }

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
          const newSrc = '<?= $uploadDir ?>' + d.filename + '?t=' + Date.now();
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

    // Show success modal after profile picture deletion
    <?php if ($success): ?>
      new bootstrap.Modal(document.getElementById('successModal')).show();
    <?php endif; ?>

    // Password form — show spinner while submitting
    document.getElementById('pwForm').addEventListener('submit', function () {
      const btn = document.getElementById('pwBtn');
      btn.disabled = true;
      btn.innerHTML = '<?php echo ($lang === 'en') ? 'Updating...' : 'กำลังอัปเดต...'; ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>';
    });

    // TOC active section highlight on scroll
    const tocLinks  = document.querySelectorAll('.toc-link');
    const sections  = ['section-profile', 'section-password']
                        .map(id => document.getElementById(id))
                        .filter(Boolean);

    function updateToc() {
      let current = sections[0]?.id ?? '';
      sections.forEach(sec => {
        if (window.scrollY + 120 >= sec.offsetTop) current = sec.id;
      });
      tocLinks.forEach(link => {
        link.classList.toggle('active', link.getAttribute('href') === '#' + current);
      });
    }

    window.addEventListener('scroll', updateToc, { passive: true });
    updateToc();
  </script>
</body>
</html>
