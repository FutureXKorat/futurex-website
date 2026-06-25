<?php
session_start();
include 'database.php';

$texts = [
    'th' => [
        'tabbar'         => 'แก้ไขโปรไฟล์ - Future X',
        'home'           => 'หน้าหลัก',
    	'product'        => 'สินค้า',
    	'cart'           => 'ตะกร้าสินค้า',
        'orders'         => 'รายการที่สั่ง',
    	'about'          => 'เกี่ยวกับพวกเรา',
    	'Source'         => 'แหล่งที่มา',
        'login'          => 'กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์ของคุณ',
        'profile'        => 'แก้ไขโปรไฟล์',
        'out'            => 'ออกจากระบบ'
    ],
    
    'en' => [
        'tabbar'         => 'Profile Settings - Future X',
        'home'           => 'Home',
        'cart'           => 'Shopping Cart',
        'orders'         => 'Orders',
    	'product'        => 'Products',
    	'about'          => 'About Us',
    	'Source'         => 'Sources',
        'login'          => 'Please log in to access your profile.',
        'profile'        => 'Edit Profile',
        'out'            => 'Log Out'
    ]
];

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$userId = $_SESSION['user_id'];
$success = "";
$errors = [];

$uploadDir = 'uploads/profile_pics/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$sql = "SELECT * FROM users WHERE id = $userId";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cropped_image'])) {
    $data = $_POST['cropped_image'];
    $data = str_replace('data:image/png;base64,', '', $data);
    $data = base64_decode($data);
    $newFileName = 'user_' . $userId . '_' . time() . '.png';
    $filePath = $uploadDir . $newFileName;
    file_put_contents($filePath, $data);

    if (!empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture'])) {
        unlink($uploadDir . $user['profile_picture']);
    }

    $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->bind_param("si", $newFileName, $userId);
    $stmt->execute();

    echo json_encode(["success" => true, "filename" => $newFileName]);
    exit();
}

if (isset($_POST['delete_profile_picture'])) {
    if (!empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture'])) {
        unlink($uploadDir . $user['profile_picture']);
        $stmt = $conn->prepare("UPDATE users SET profile_picture = NULL WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $success = "Profile picture deleted successfully.";
    } else {
        $errors[] = "No profile picture to delete.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title><?php echo $texts[$lang]['tabbar']; ?></title>
  <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap & Cropper CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">

  <style>
    :root{
      /* 🔴 Brand (red theme) */
      --brand-color:#007BFF;     /* main red */
      --brand-hover:#0056b3;     /* darker red */
      --brand-deep:#003f7f;      /* deepest red for hover/active */
      --gray-color:#ccc;    
    }

    /* Body & Layout */
    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      /* 🔴 red-tinted gradient */
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      color: #1F2937;
      min-height: 100vh;
    }

    /* NAVBAR */

    /* Form Container */
    .form-container {
      margin-top: 50px;      /* fallback; JS will override for equal spacing */
      margin-bottom: 50px;   /* fallback; JS will override for equal spacing */
      background: rgba(255,255,255,0.25);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      margin-left: auto;
      margin-right: auto;
      padding: 40px 35px;
      width: 100%;
      max-width: 560px;
      box-shadow: 0 12px 32px rgba(0,0,0,0.15);
      position: relative;
    }
    .form-container h2 { text-align: center; font-size: 1.8rem; font-weight: 700; margin-bottom: 20px; }
    .form-section { margin-top: 20px; }
    .profile-pic { max-width: 120px; border-radius: 50%; margin-bottom: 10px; background-color: #ccc; }
    .centered { display: flex; justify-content: center; align-items: center; margin-bottom: 10px; }

    /* File Input */
    .file-row { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .choose-btn {
      position: relative; overflow: hidden; background-color: #fff; border: 1px solid #d1d5db; border-radius: 12px;
      font-weight: 600; font-size: 0.95rem; padding: 10px 20px; cursor: pointer; transition: background-color 0.2s ease;
      width: 150px; text-align: center;
    }
    .choose-btn:hover { background-color: #e5e7eb; }
    .choose-btn input { position: absolute; left: 0; top: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .file-label { flex: 1; font-size: 0.95rem; color: #4B5563; }

    /* Primary Button (🔴 red gradient) */
    .btn-modern {
      width: 100%; margin-top: 12px; padding: 14px; font-size: 1.1rem; font-weight: 600; border-radius: 14px; transition: all 0.3s ease;
    }
    .btn-modern.btn-primary {
      background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
      border: none; color: #fff;
    }
    .btn-modern.btn-primary:hover {
      background: linear-gradient(135deg, var(--brand-hover), var(--brand-deep));
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(204,0,0,0.35);
    }

    /* Modals */
    .modal-content { background-color: #f8f9fa; border-radius: 12px; overflow: hidden; }
    .modal-header { border-bottom: 1px solid #dcdcdc; }
    .btn-danger { background-color: #dc3545; color: #fff; padding: 12px 20px; font-size: 1rem; border: none; border-radius: 8px; transition: background-color 0.3s ease; }
    .btn-danger:hover { background-color: #c82333; }
    .btn-secondary { background-color: #6c757d; color: #fff; padding: 12px 20px; font-size: 1rem; border: none; border-radius: 8px; transition: background-color 0.3s ease; }

    /* Cropper */
    .cropper-crop-box, .cropper-view-box { border-radius: 50%; }

    /* Crop controls (red X, green ✔) */
    #cropControls {
      display: flex; justify-content: space-between; gap: 8px;
      padding: 0 16px 16px 16px;
    }
    #cropControls .control-btn {
      flex: 1; padding: 10px 0; font-size: 1.1rem; font-weight: 700; color: #fff;
      border: none; border-radius: 8px; cursor: pointer;
    }
    #cropControls .cancel { background-color: #ef4444; }  /* red X */
    #cropControls .confirm { background-color: #22c55e; } /* green ✔ */

    /* Keep image inside rounded modal and away from controls */
    .modal-body { max-height: 65vh; overflow: auto; }
      
      .top-banner {
            background-color: var(--brand-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .top-banner.scrolled {
            background-color: var(--brand-color); /* keep red when scrolled */
            box-shadow: none;
        }
        .nav-links-container {
            flex: 1;
            overflow-x: auto;
            position: relative;
            padding: 12px 20px;
        }
        .nav-links {
            display: flex;
            gap: 12px;
            white-space: nowrap;
        }
        .nav-links::-webkit-scrollbar { display: none; }
        .nav-scroll-indicator {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: #fff; /* white line for contrast on red */
            border-radius: 2px;
            width: 0%;
            transition: width 0.2s linear;
        }
        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            flex-shrink: 0;
            transition: background 0.3s, transform 0.15s ease, opacity 0.15s ease;
        }
        .top-banner.scrolled .nav-links a { color: #fff; }
        .nav-links a:hover { background-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
		#profileIcon + .profile-dropdown-content {
  	    	margin-top: 8px; /* or whatever gap you want */
		}
        .profile-dropdown {
            transition: transform .15s ease;
            will-change: transform;
        }

        .profile-dropdown:hover {
            transform: translateY(-1px);
        }
        .profile-dropdown-content {
            display: none;
            position: absolute;
            right: 10px;
            background-color: white;
            min-width: 190px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            border-radius: 8px;
            overflow: hidden;
            padding: 0;
        }
        .profile-dropdown-content a {
            display: block;
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            transition: background 0.3s;
            white-space: nowrap;
        }
        .profile-dropdown-content a:hover { background-color: #f2f2f2; color: #333;}
        .profile-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            background-color: var(--gray-color);
            border: none; /* no border */
        }
        /* Language dropdown container */
        .lang-dropdown{
                position: relative;
                flex-shrink: 0;
        }

        /* Round icon button (glassy like your theme) */
        .lang-btn-icon{
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border: 1px solid rgba(255,255,255,0.35);
                background: rgba(255,255,255,0.18);
                color: #fff;
                border-radius: 50%;
                cursor: pointer;
                transition: transform .15s ease, background .2s ease, opacity .15s ease;
        }
        .lang-btn-icon:hover{ 
                background: rgba(255,255,255,0.28); 
                transform: translateY(-1px); 
        }
        .lang-btn-icon:focus{ 
                outline: 2px solid rgba(255,255,255,0.6); 
                outline-offset: 2px; 
        }

        /* Dropdown panel (same look as profile) */
        .lang-dropdown-content{
                display: none;
                position: absolute;
                right: 0;
                top: calc(100% + 8px);
                background-color: #fff;
                min-width: 190px;
                box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
                border-radius: 8px;
                overflow: hidden;
                padding: 0;
        }

        .lang-dropdown-content a{
                display:block;
                color:#333;
                padding:12px 16px;
                text-decoration:none;
                transition: background .2s ease;
                white-space: nowrap;
        }
        .lang-dropdown-content a:hover{ 
                background:#f2f2f2; 
        }
        .lang-dropdown-content a.active{
                font-weight:700;
                background:#f7f7f7;
        }
        .right-actions{
  	    display:flex;
  	    align-items:center;
  	    gap:10px;
  	    margin-right:12px;
	}

	.lang-btn{
  	    display:inline-block;
  	    padding:8px 12px;
  	    border-radius:10px;
  	    font-weight:600;
  	    text-decoration:none;
  	    color:#fff;
  	    background: rgba(255,255,255,0.18);
  	    border:1px solid rgba(255,255,255,0.3);
  	    backdrop-filter: blur(4px);
  	    transition: transform .15s ease, background .2s ease, opacity .15s ease;s
	}
	.lang-btn:hover{
  	    background: rgba(255,255,255,0.28);
  	    transform: translateY(-1px);
	}
      
     
  </style>
</head>
<body>

  <!-- NAVBAR -->
  <?php include 'includes/navbar.php'; ?>

  <!-- EDIT PROFILE FORM -->
  <div class="form-container" id="formContainer">
    <h2><?php echo ($lang === 'en') ? 'Edit Profile' : 'แก้ไขโปรไฟล์' ?></h2>

    <?php if ($errors): ?>
      <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <div class="form-section text-center">
      <hr>
      <p><strong><?php echo ($lang === 'en') ? 'Edit Profile Picture:' : 'แก้ไขรูปโปรไฟล์:' ?></strong></p>
    </div>
    <div class="form-section">
      <div class="centered">
        <?php if (!empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture'])): ?>
          <img id="currentProfilePic" src="<?= $uploadDir . $user['profile_picture'] ?>" class="profile-pic" alt="Profile">
        <?php else: ?>
          <img id="currentProfilePic" src="avatar.png" class="profile-pic" alt="Avatar">
        <?php endif; ?>
      </div>
      <div class="file-row">
        <label class="choose-btn"><?php echo ($lang === 'en') ? 'Choose File' : 'เลือกไฟล์' ?>
          <input type="file" accept="image/*" onchange="handleFileSelect(event)">
        </label>
        <div class="file-label"><?php echo ($lang === 'en') ? 'No file chosen' : 'ยังไม่ได้เลือกไฟล์' ?></div>
      </div>
    </div>

    <div class="form-section text-center">
      <?php if (!empty($user['profile_picture']) && file_exists($uploadDir . $user['profile_picture'])): ?>
        <button
  			id="deleteProfileBtn"
  			type="button"
  			class="btn btn-danger mb-2 w-100 fw-bold"
  			data-bs-toggle="modal"
  			data-bs-target="#deleteModal"
  			style="display: none;"
		>
  			<?php echo ($lang === 'en') ? 'Delete Profile Picture' : 'ลบรูปโปรไฟล์' ?>
		</button>
      <?php endif; ?>
    </div>

    <div class="form-section text-center">
      <hr>
      <p><strong><?php echo ($lang === 'en') ? 'Link your accounts:' : 'เชื่อมบัญชีของคุณ:' ?></strong></p>
      <!-- Keep green outline buttons -->
      <button class="btn btn-outline-success w-100 mb-2" disabled><?php echo ($lang === 'en') ? 'Link Google Account (coming soon)' : 'เชื่อมบัญชี Google (กำลังมา)' ?></button>
      <button class="btn btn-outline-success w-100" disabled><?php echo ($lang === 'en') ? 'Link Apple Account (coming soon)' : 'เชื่อมบัญชี Apple (กำลังมา)' ?></button>
      <hr>
      <p><strong><?php echo ($lang === 'en') ? 'Security:' : 'ความปลอดภัย' ?></strong></p>
      <button class="btn btn-outline-success w-100" disabled><?php echo ($lang === 'en') ? 'Create Passkey (coming soon)' : 'สร้างพาสคีย์ (กำลังมา)' ?></button>
    </div>
  </div>

  <!-- DELETE CONFIRMATION MODAL -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalLabel"><?php echo ($lang === 'en') ? 'Delete Profile Picture' : 'ลบรูปโปรไฟล์' ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body"><?php echo ($lang === 'en') ? 'Are you sure you want to delete your profile picture?' : 'คุณแน่ใจหรือไม่ว่าต้องการลบรูปโปรไฟล์ของคุณ' ?></div>
        <div class="modal-footer d-flex flex-column gap-2">
          <button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal"><?php echo ($lang === 'en') ? 'Cancel' : 'ยกเลิก' ?></button>
          <form action="" method="POST" class="w-100">
            <button type="submit" name="delete_profile_picture" class="btn btn-danger w-100"><?php echo ($lang === 'en') ? 'Yes, delete' : 'ใช่, ลบ' ?></button>
          </form>
        </div>
      </div></div>
  </div>

  <!-- SUCCESS MODAL -->
  <div class="modal fade success-modal" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="successModalLabel"><?php echo ($lang === 'en') ? 'Profile Picture Deleted' : 'รูปโปรไฟล์ถูกลบแล้ว' ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body"><?php echo ($lang === 'en') ? 'Your profile picture has been successfully deleted.' : 'รูปโปรไฟล์ถูกลบอย่างสำเร็จแล้ว' ?></div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success w-100" data-bs-dismiss="modal"><?php echo ($lang === 'en') ? 'Close' : 'ปิด' ?></button>
        </div>
      </div></div>
  </div>

  <!-- CROPPER MODAL -->
  <div class="modal fade" id="cropModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body p-3">
          <img id="cropImage" class="d-block mx-auto" style="max-width:100%;">
        </div>
        <div id="cropControls" class="px-3 pb-3">
          <button type="button" class="control-btn cancel" onclick="cancelCrop()">X</button>
          <button type="button" class="control-btn confirm" onclick="uploadCropped()">✔</button>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
  <script>

    // Cropper logic
    let cropper, cropModalInstance;
    function handleFileSelect(e) {
      const file = e.target.files[0];
      if (!file) return;
      document.querySelector('.file-label').textContent = file.name;

      const reader = new FileReader();
      reader.onload = () => {
        const img = document.getElementById('cropImage');
        img.src = reader.result;

        const modalEl = document.getElementById('cropModal');
        cropModalInstance = new bootstrap.Modal(modalEl);

        modalEl.addEventListener('shown.bs.modal', () => {
          if (cropper) cropper.destroy();

          cropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 1,
            background: false,
            zoomable: true,
            dragMode: 'move',
            cropBoxResizable: true,
            autoCropArea: 1,
            ready() {
              const c = cropper.getContainerData();
              const size = Math.min(c.width, c.height) * 0.6;
              cropper.setCropBoxData({
                width: size,
                height: size,
                left: (c.width - size) / 2,
                top: (c.height - size) / 2
              });
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

    		// update profile image in form
    		document.getElementById('currentProfilePic').src = newSrc;

    		// ✅ update navbar profile image instantly
    		const navProfileImg = document.getElementById('profileIcon');
    		if (navProfileImg) navProfileImg.src = newSrc;
            
            const deleteBtn = document.getElementById('deleteProfileBtn');
			if (deleteBtn) deleteBtn.style.display = 'block';

    		cropModalInstance.hide();
    		cropper.destroy();
    		cropper = null;
  		}
      });
    }
      
    function deleteProfilePicture() {
  fetch('', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'delete_profile_picture=1'
  })
  .then(r => r.text())
  .then(() => {
    // ✅ THIS IS WHERE IT GOES ⬇️⬇️⬇️

    const deleteBtn = document.getElementById('deleteProfileBtn');
    if (deleteBtn) deleteBtn.style.display = 'none';

    // reset images
    const avatar = 'avatar.png';
    document.getElementById('currentProfilePic').src = avatar;

    const navProfileImg = document.getElementById('profileIcon');
    if (navProfileImg) navProfileImg.src = avatar;

    // close modal
    bootstrap.Modal.getInstance(
      document.getElementById('deleteModal')
    ).hide();
  });
}


    function cancelCrop() {
      if (cropper) { cropper.destroy(); cropper = null; }
      document.querySelector('.file-label').textContent = '<?php echo ($lang === 'en') ? 'No file chosen': 'ยังไม่ได้เลือกไฟล์' ?>';
      document.getElementById('cropImage').src = '';
      if (cropModalInstance) cropModalInstance.hide();
      adjustCardSpacing();
    }

    <?php if ($success): ?>
      new bootstrap.Modal(document.getElementById('successModal')).show();
    <?php endif; ?>
  </script>

</body>
</html>
