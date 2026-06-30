<?php
// =======================================
// file: pay_success.php
// =======================================
declare(strict_types=1);
include 'database.php';

$orderId = (string)($_GET['order'] ?? '');
if (isset($_SESSION['pending_checkout'])) {
    // Optional: extra safety—only clear if matches
    $pendingId = (string)($_SESSION['pending_checkout']['order_id'] ?? '');
    if ($pendingId === '' || $orderId === '' || $orderId === $pendingId) {
        unset($_SESSION['cart']);
        unset($_SESSION['pending_checkout']);
    }
}
$texts = [
  'en' => ['title'=>'Payment Successful - Future X','msg'=>'Thank you! Please wait for the admin to check your transaction.','back'=>'Back to Home'],
  'th' => ['title'=>'ชำระเงินสำเร็จ - Future X','msg'=>'ขอบคุณ! กรุณารอแอดมินอนุมัติการจ่าย','back'=>'กลับหน้าหลัก'],
];
$t = $texts[$lang] ?? $texts['en'];
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo htmlspecialchars($t['title']); ?></title>
<link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body{margin:0;font-family:Inter,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#E6F0FF,#CCE0FF,#FFFFFF);padding:40px 20px}
  .cardx{background:rgba(255,255,255,.25);backdrop-filter:blur(12px);border-radius:20px;box-shadow:0 12px 32px rgba(0,0,0,.15);padding:22px;max-width:720px;width:100%;text-align:center}
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
</style>
</head>
<body>
  <div class="cardx">
    <h2 class="fw-bold mb-2"><?php echo ($lang === 'en' ? 'Payment Successful' : 'ชำระเงินสำเร็จ'); ?></h2>
    <p class="text-muted mb-3"><?php echo htmlspecialchars($t['msg']); ?></p>
    <?php if ($orderId): ?>
      <div class="mb-3">#<?php echo htmlspecialchars($orderId); ?></div>
    <?php endif; ?>
    <a class="btn btn-modern btn-primary" href="home.php"><?php echo htmlspecialchars($t['back']); ?></a>
  </div>
    </body>
</html>
