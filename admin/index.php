<?php
declare(strict_types=1);
include '../database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: https://futurexthailand.com/index.php'); exit;
}

// Admin check
$_mailCfgPath = dirname(__DIR__) . '/secure-config/futurex_mail.php';
$_mailCfg     = is_file($_mailCfgPath) ? require $_mailCfgPath : [];
$ADMIN_EMAIL  = strtolower(trim((string)($_mailCfg['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'futurexkorat@gmail.com')));
$_stmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
$_stmt->bind_param('i', $_SESSION['user_id']);
$_stmt->execute();
$_row = $_stmt->get_result()->fetch_assoc();
$_stmt->close();
$_userEmail = strtolower(trim((string)($_row['email'] ?? '')));
if ($_userEmail === '' || $_userEmail !== $ADMIN_EMAIL) {
    header('Location: https://futurexthailand.com/home.php'); exit;
}

// Count orders for the stat card
$ordersDir = dirname(__DIR__) . '/storage/orders';
$totalOrders = 0;
$pendingOrders = 0;
if (is_dir($ordersDir)) {
    foreach (glob($ordersDir . '/*.json') ?: [] as $f) {
        $rec = json_decode(@file_get_contents($f), true);
        if (is_array($rec) && isset($rec['order_id'])) {
            $totalOrders++;
            if (($rec['status'] ?? 'awaiting_review') === 'awaiting_review') $pendingOrders++;
        }
    }
}

$title = $lang === 'th' ? 'แอดมิน — Future X' : 'Admin — Future X';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="icon" type="image/png" href="/logo_transparent_onlyblack.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      min-height: 100vh;
      padding: 0 20px 56px;
      color: #111;
    }
    @supports (height: 100dvh) { body { min-height: 100dvh; } }
    .page-wrap { max-width: 900px; margin: 0 auto; }
    .page-title { font-size: 1.75rem; font-weight: 700; margin: 0 0 4px; }
    .page-subtitle { font-size: 0.84rem; color: #666; margin-bottom: 32px; }
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
      gap: 20px;
    }
    .admin-card {
      background: rgba(255,255,255,0.45);
      backdrop-filter: blur(10px);
      border-radius: 18px;
      padding: 26px 24px 22px;
      box-shadow: 0 6px 22px rgba(0,0,0,0.09);
      text-decoration: none;
      color: inherit;
      border-top: 3px solid #007BFF;
      transition: transform .22s, box-shadow .22s;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .admin-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.14); color: inherit; }
    .card-icon { font-size: 2rem; line-height: 1; }
    .card-name { font-size: 1.1rem; font-weight: 700; }
    .card-desc { font-size: .84rem; color: #666; }
    .card-badge {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: .78rem; font-weight: 600;
      background: #FFF3CD; color: #856404;
      padding: 4px 10px; border-radius: 20px; width: fit-content;
    }
    .card-badge.blue { background: rgba(0,123,255,0.1); color: #0056b3; }
  </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="page-wrap" style="margin-top: 36px;">
  <h1 class="page-title"><?= $lang === 'th' ? 'แอดมิน แดชบอร์ด' : 'Admin Dashboard' ?></h1>
  <p class="page-subtitle">Future X Admin Panel</p>

  <div class="cards-grid">
    <a href="orders.php" class="admin-card">
      <div class="card-icon">📋</div>
      <div class="card-name"><?= $lang === 'th' ? 'คำสั่งซื้อ' : 'Orders' ?></div>
      <div class="card-desc"><?= $lang === 'th' ? 'ดู อนุมัติ หรือปฏิเสธคำสั่งซื้อ' : 'View, approve, or reject customer orders' ?></div>
      <?php if ($pendingOrders > 0): ?>
        <div class="card-badge"><?= $pendingOrders ?> <?= $lang === 'th' ? 'รอตรวจสอบ' : 'pending' ?></div>
      <?php else: ?>
        <div class="card-badge blue"><?= $totalOrders ?> <?= $lang === 'th' ? 'คำสั่งซื้อทั้งหมด' : 'total orders' ?></div>
      <?php endif; ?>
    </a>
  </div>
</div>

</body>
</html>
