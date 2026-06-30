<?php
// file: promptpay.php
declare(strict_types=1);
ini_set('display_errors', '1'); error_reporting(E_ALL);
include 'database.php'; // for $conn, $lang
require_once __DIR__ . '/cloudinary.php';

// must arrive from checkout
if (empty($_SESSION['pending_checkout'])) {
    header('Location: checkout.php'); exit;
}

$order = $_SESSION['pending_checkout'];
$order_id    = $order['order_id'];
$totalTHB    = (float)($order['amounts']['total'] ?? 0);
$delivery    = $order['delivery'] ?? 'pickup';
$address     = $order['address'] ?? '';
$pickup_time = $order['pickup_time'] ?? '';
$items       = $order['items'] ?? [];

$slipPathWeb = ''; // will hold Cloudinary URL after successful upload

// i18n (minimal)
$texts = [
  'en' => [
    'title'      => 'Pay via PromptPay',
    'scan'       => 'Scan & Pay',
    'ppid'       => 'PromptPay ID',
    'amt'        => 'Amount',
    'addr'       => 'Shipping address',
    'pickup_appt'=> 'Pick-Up Appointment',
    'view_map'   => 'View store on Google Maps',
    'upload'     => 'Upload payment slip',
    'submit'     => 'I have paid',
    'note'       => 'We will review your payment and confirm your order.',
    'order'      => 'Order',
    'back'       => 'Back to Checkout',
    'err_nofile' => 'Please upload your payment slip before confirming payment.',
    'err_type'   => 'Unsupported file type. Please upload JPG, PNG, WEBP, GIF, HEIC, or HEIF.',
    'err_size'   => 'File is too large. Max size is 10 MB.',
    'err_move'   => 'Could not save the file. Please try again.',
  ],
  'th' => [
    'title'      => 'ชำระเงินด้วยพร้อมเพย์',
    'scan'       => 'สแกนเพื่อชำระเงิน',
    'ppid'       => 'พร้อมเพย์',
    'amt'        => 'ยอดชำระ',
    'addr'       => 'ที่อยู่จัดส่ง',
    'pickup_appt'=> 'นัดรับสินค้า',
    'view_map'   => 'ดูที่ตั้งร้านบน Google Maps',
    'upload'     => 'อัปโหลดสลิปโอนเงิน',
    'submit'     => 'ชำระเงินแล้ว',
    'note'       => 'เราจะตรวจสอบการชำระเงินและยืนยันคำสั่งซื้อ',
    'order'      => 'คำสั่งซื้อ',
    'back'       => 'กลับไปหน้าชำระเงิน',
    'err_nofile' => 'กรุณาอัปโหลดสลิปการโอนเงินก่อนยืนยันการชำระเงิน',
    'err_type'   => 'ประเภทไฟล์ไม่รองรับ โปรดอัปโหลด JPG, PNG, WEBP, GIF, HEIC หรือ HEIF',
    'err_size'   => 'ไฟล์มีขนาดใหญ่เกินไป ขนาดสูงสุด 10 MB',
    'err_move'   => 'ไม่สามารถบันทึกไฟล์ได้ โปรดลองอีกครั้ง',
  ],
];
$t = $texts[$lang] ?? $texts['en'];

// TODO: put your PromptPay ID/number here (phone or e-Wallet ID)
$PROMPTPAY_ID = '061-969-9249';

$errors = [];           // <-- collect errors for display
// handle submit (save order to MySQL + slip to Cloudinary)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Must have a file
    // When post_max_size is exceeded PHP silently empties $_FILES entirely.
    // When upload_max_filesize is exceeded tmp_name is empty but error code is set.
    if (empty($_FILES) || !isset($_FILES['slip'])) {
        $errors[] = $t['err_size'];
    } else {
        $slipErr = (int)($_FILES['slip']['error'] ?? UPLOAD_ERR_NO_FILE);
        $tmpName  = (string)($_FILES['slip']['tmp_name'] ?? '');
        if ($slipErr === UPLOAD_ERR_NO_FILE) {
            $errors[] = $t['err_nofile'];
        } elseif ($slipErr === UPLOAD_ERR_INI_SIZE || $slipErr === UPLOAD_ERR_FORM_SIZE) {
            $errors[] = $t['err_size'];
        } elseif ($slipErr !== UPLOAD_ERR_OK || $tmpName === '' || !file_exists($tmpName)) {
            $errors[] = $t['err_move'];
        } else {
            $okType = ['image/jpeg','image/png','image/webp','image/gif','image/heic','image/heif'];
            $size   = (int)$_FILES['slip']['size'];

            // MIME sniff
            $mime = '';
            if (function_exists('finfo_open')) {
                $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $buf  = @file_get_contents($tmpName);
                    $mime = $buf !== false ? finfo_buffer($finfo, $buf) : '';
                    @finfo_close($finfo);
                }
            }
            if (!$mime) {
                $mime = $_FILES['slip']['type'] ?: 'application/octet-stream';
            }

            if (!in_array($mime, $okType, true)) {
                $errors[] = $t['err_type'];
            }
            if ($size > 10*1024*1024) {
                $errors[] = $t['err_size'];
            }

            // Upload to Cloudinary when no validation errors
            if (!$errors) {
                $slipPublicId = $order_id . '_' . time();
                $cloudUrl = uploadSlipToCloudinary($tmpName, $slipPublicId);
                if ($cloudUrl !== null) {
                    $slipPathWeb = $cloudUrl;
                } else {
                    $errors[] = $t['err_move'];
                }
            }
        }
    }

    // If valid, persist and redirect; otherwise stay and show errors
    if (!$errors) {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $userPhone = '';
        if ($userId > 0) {
            $ps = $conn->prepare("SELECT phoneno FROM users WHERE id = ? LIMIT 1");
            $ps->bind_param('i', $userId);
            $ps->execute();
            $ps->bind_result($userPhone);
            $ps->fetch();
            $ps->close();
            $userPhone = (string)($userPhone ?? '');
        }

        $record = [
            'order_id'    => $order_id,
            'user_id'     => $userId,
            'username'    => (string)($_SESSION['username']),
            'user_email'  => (string)($_SESSION['user_email'] ?? $_SESSION['email'] ?? ''),
            'phone'       => $userPhone,
            'delivery'    => $delivery,
            'address'     => $address,
            'pickup_time' => $pickup_time,
            'items'       => $items,
            'amounts'     => $order['amounts'],
            'status'      => 'awaiting_review',
            'slip'        => $slipPathWeb,
            'created_at'  => date('c'),
        ];
        $orderJson  = json_encode($record, JSON_UNESCAPED_UNICODE);
        $createdDt  = date('Y-m-d H:i:s');
        $insertStmt = $conn->prepare(
            "INSERT INTO `orders` (order_id, user_id, status, data, created_at)
             VALUES (?, ?, 'awaiting_review', ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()"
        );
        $insertStmt->bind_param('siss', $order_id, $record['user_id'], $orderJson, $createdDt);
        $insertStmt->execute();
        $insertStmt->close();

        // clear cart
        $_SESSION['cart'] = [];
        // keep minimal info for thank you
        $_SESSION['last_submitted_order'] = $order_id;
        unset($_SESSION['pending_checkout']);
        
        require_once __DIR__ . '/send_order.php';
			try {
    			send_order_mail($order_id); // ignore result; don't block user
			} catch (\Throwable $e) {
   				 // Optional: log it
    			// error_log('send_order failed: '.$e->getMessage());
			}

        header('Location: pay_success.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($t['title']); ?></title>
<link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{
      margin:0;
      font-family:Inter,sans-serif;
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      background:linear-gradient(135deg,#E6F0FF,#CCE0FF,#FFFFFF);
      padding:40px 20px
    }
  .cardx {
      background:rgba(255,255,255,.25);
      backdrop-filter:blur(12px);
      border-radius:20px;
      box-shadow:0 12px 32px rgba(0,0,0,.15);
      padding:22px;max-width:940px;width:100%
    }
  .hdr {
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:8px
    }
  .ttl {
      font-size:1.3rem;
      font-weight:700
    }
  .qr{
      display:grid;
      place-items:center;
      background:#fff;
      border-radius:16px;
      padding:12px;
      border:1px solid rgba(0,0,0,.06)
    }
    .qr img{
        max-width:50%;
        max-height:50%;
    }
  .muted{
      color:#555
    }
  .ppid{
      font-weight:700;
      font-size:1.1rem
    }
  .btn-modern{
    display:block;
    width:100%;
    margin-top:12px;
    padding:14px;
    font-size:1.1rem;
    font-weight:600;
    border-radius:14px;
    transition:all .3s ease;
    border:0;
  }
  .btn-modern.btn-primary{
    background:linear-gradient(135deg,#007BFF,#0056b3);
    color:#fff;
  }
  .btn-modern.btn-primary:hover{
    background:linear-gradient(135deg,#0056b3,#003f7f);
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(0,0,0,.35);
  }
  .btn-modern:focus-visible{
    outline:3px solid rgba(0,123,255,.5);
    outline-offset:2px;
  }
  .btn-modern:disabled{
    opacity:.65; cursor:not-allowed; transform:none; box-shadow:none;
  }

  /* Custom file UI */
  .file-input-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 1rem;
  }
  .file-input-row .form-label {
    display: inline-block;
    margin-bottom: 0;
  }
  .choose-btn {
    position: relative;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    padding: 10px 16px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    user-select: none;
    margin: 0;
    vertical-align: middle;
  }
  .choose-btn:hover {
    background: #f3f4f6;
  }
  .file-label {
    display: inline-block;
    color: #4b5563;
    vertical-align: middle;
    margin-left: 0;
  }
  .visually-hidden {
    position: absolute !important;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    border: 0;
  }
  .inline-file-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: nowrap;
    white-space: nowrap;
  }
  .inline-file-row .form-label {
    display: inline-block;
    margin: 0;
    line-height: 1.25;
  }
  .inline-file-row .choose-btn { /* keep inline */
    position: relative;
    overflow: hidden;
    background: #ffffff;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    padding: 10px 16px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    user-select: none;
    margin: 0;
    vertical-align: middle;
  }
  .inline-file-row .choose-btn:hover { background: #f3f4f6; }
  .inline-file-row .file-label {
    display: inline-block;
    color: #4b5563;
    margin: 0;
    vertical-align: middle;
  }
  .file-inline {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    min-width: 0;
  }
  .file-label {
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    min-width: 0;
  }
  .choose-btn {
    display: inline-flex !important;
    align-items: center !important;
    margin-top: 6px !important; /* fixed spacing */
  }
  .file-label {
    display: inline-block !important;
    margin: 0 !important;
    vertical-align: middle !important;
  }
</style>
</head>
<body>
<div class="cardx">
  <div class="hdr">
    <div class="ttl"><?php echo htmlspecialchars($t['title']); ?></div>
    <div class="muted">#<?php echo htmlspecialchars($order_id); ?></div>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0">
        <?php foreach ($errors as $e): ?>
          <li><?php echo htmlspecialchars($e); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-6">
      <div class="qr">
        <!-- Put a static PNG of your PromptPay QR here if you have one -->
        <div class="text-center p-3">
          <div class="ppid"><?php echo htmlspecialchars($t['ppid']); ?>: <?php echo htmlspecialchars($PROMPTPAY_ID); ?></div>
          <div class="mt-2"><?php echo htmlspecialchars($t['amt']); ?>: <strong><?php echo number_format($totalTHB,2); ?> ฿</strong></div>
          <div class="small mt-3 text-muted"><?php echo htmlspecialchars($t['scan']); ?></div>
          <div class="mt-3">
            <img src="code.jpg" alt="PromptPay QR">
          </div>
        </div>
      </div>
      <?php if ($delivery === 'ship' && $address): ?>
      <div class="mt-3">
        <div class="muted mb-1"><?php echo htmlspecialchars($t['addr']); ?></div>
        <div class="border rounded p-2 bg-light"><?php echo nl2br(htmlspecialchars($address)); ?></div>
      </div>
      <?php endif; ?>
      <?php if ($delivery === 'pickup'): ?>
      <div class="mt-3" style="background:rgba(233,242,255,0.7);border:1.5px solid rgba(0,123,255,0.18);border-radius:14px;padding:14px 16px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#007BFF" stroke-width="2" style="flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
          <div>
            <div style="font-weight:600;font-size:.88rem;">Future X Korat</div>
            <a href="https://maps.app.goo.gl/q2r3e8apCCvh5XAs6" target="_blank" rel="noopener"
               style="font-size:.78rem;color:#007BFF;text-decoration:none;">
              <?php echo htmlspecialchars($t['view_map']); ?> ↗
            </a>
          </div>
        </div>
        <?php if ($pickup_time): ?>
        <div style="font-size:.82rem;color:#555;font-weight:500;margin-bottom:4px;"><?php echo htmlspecialchars($t['pickup_appt']); ?></div>
        <div style="font-weight:700;font-size:.95rem;color:#111;"><?php echo htmlspecialchars($pickup_time); ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="col-12 col-md-6">
      <form method="post" enctype="multipart/form-data" novalidate>
        <div class="mb-3">
          <!-- label sits on its own line -->
          <label class="form-label d-block mb-1">
            <?php echo htmlspecialchars($t['upload']); ?>
          </label>

          <!-- real file input -->
          <input
            type="file"
            id="slip"
            name="slip"
            accept="image/jpeg,image/png,image/webp,image/gif,image/heic,image/heif,image/*"
            class="visually-hidden"
            required
          >

          <!-- button + filename on the SAME line -->
          <div class="file-inline">
            <label for="slip" class="choose-btn mb-0">
              <?php echo ($lang === 'en') ? 'Choose Slip' : 'เลือกสลิป'; ?>
            </label>
            <span id="fileLabel" class="file-label">
              <?php echo ($lang === 'en') ? 'No file chosen' : 'ไม่มีไฟล์ที่ท่านได้เลือก'; ?>
            </span>
          </div>
        </div>

        <button id="submitBtn" type="submit" class="btn-modern btn-primary" disabled>
          <?php echo htmlspecialchars($t['submit']); ?>
        </button>

        <div class="form-text mt-2"><?php echo htmlspecialchars($t['note']); ?></div>
        <div class="mt-3"><a href="checkout.php">&larr; <?php echo htmlspecialchars($t['back']); ?></a></div>
      </form>
    </div>
  </div>
</div>
<script>
  (function () {
    var input = document.getElementById('slip');
    var label = document.getElementById('fileLabel');
    var submitBtn = document.getElementById('submitBtn');

    var allowed = [
      'image/jpeg','image/png','image/webp','image/gif','image/heic','image/heif'
    ];
    var maxSize = 10 * 1024 * 1024;

    function setDisabled(state) {
      if (submitBtn) submitBtn.disabled = state;
    }

    // Start disabled until a valid file is chosen
    setDisabled(true);

    if (!input || !label) return;

    input.addEventListener('change', function () {
      if (!input.files || !input.files[0]) {
        label.textContent = <?php echo json_encode(($lang === 'en') ? 'No file chosen' : 'ไม่มีไฟล์ที่ท่านได้เลือก'); ?>;
        setDisabled(true);
        return;
      }

      var f = input.files[0];
      var kb = Math.round(f.size / 1024);

      // Client-side checks (extra UX; server-side still authoritative)
      if (f.size > maxSize) {
        label.textContent = <?php echo json_encode($t['err_size']); ?> + ' (' + kb.toLocaleString() + ' KB)';
        setDisabled(true);
        return;
      }
      // Some browsers may not set type correctly; still try:
      if (f.type && allowed.indexOf(f.type) === -1) {
        label.textContent = <?php echo json_encode($t['err_type']); ?>;
        setDisabled(true);
        return;
      }

      label.textContent = f.name + ' (' + kb.toLocaleString() + ' KB)';
      setDisabled(false);
    });
  })();
</script>
</body>
</html>