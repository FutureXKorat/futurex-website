<?php
declare(strict_types=1);
include '../database.php';

include 'auth.php';

// Count orders for the stat card
$totalOrders = 0;
$pendingOrders = 0;
$_oRes = $conn->query("SELECT status FROM `orders`");
if ($_oRes) {
    while ($_oRow = $_oRes->fetch_assoc()) {
        $totalOrders++;
        if ($_oRow['status'] === 'awaiting_review') $pendingOrders++;
    }
    $_oRes->free();
}

// Count low-stock products (active, stock <= 5)
$lowStockCount = 0;
$_sRes = $conn->query("SELECT COUNT(*) AS cnt FROM products WHERE active=1 AND stock <= 5");
if ($_sRes) {
    $lowStockCount = (int)(($_sRes->fetch_assoc())['cnt'] ?? 0);
    $_sRes->free();
}

// Count total products
$productCount = 0;
$_pRes = $conn->query("SELECT COUNT(*) AS cnt FROM products");
if ($_pRes) {
    $productCount = (int)(($_pRes->fetch_assoc())['cnt'] ?? 0);
    $_pRes->free();
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      min-height: 100vh;
      padding: 0;
      color: #111;
    }
    @supports (height: 100dvh) { body { min-height: 100dvh; } }
    .page-wrap { max-width: 900px; margin: 0 auto; padding: 0 20px; }
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

    <a href="stock.php" class="admin-card">
      <div class="card-icon">📦</div>
      <div class="card-name"><?= $lang === 'th' ? 'จัดการสต็อก' : 'Stock Management' ?></div>
      <div class="card-desc"><?= $lang === 'th' ? 'เพิ่ม/ลดสต็อก และเปิด-ปิดสินค้า' : 'Add or remove stock, enable or disable products' ?></div>
      <?php if ($lowStockCount > 0): ?>
        <div class="card-badge"><?= $lowStockCount ?> <?= $lang === 'th' ? 'สินค้าใกล้หมด' : 'low stock' ?></div>
      <?php else: ?>
        <div class="card-badge blue"><?= $lang === 'th' ? 'สต็อกพร้อม' : 'All stocked up' ?></div>
      <?php endif; ?>
    </a>

    <a href="products.php" class="admin-card">
      <div class="card-icon">🛍️</div>
      <div class="card-name"><?= $lang === 'th' ? 'สินค้า' : 'Products' ?></div>
      <div class="card-desc"><?= $lang === 'th' ? 'เพิ่ม แก้ไข หรือลบสินค้า' : 'Add, edit, or delete products' ?></div>
      <div class="card-badge blue"><?= $productCount ?> <?= $lang === 'th' ? 'สินค้าทั้งหมด' : 'total products' ?></div>
    </a>

    <?php if ($isSuperAdmin): ?>
    <a href="users.php" class="admin-card">
      <div class="card-icon">👥</div>
      <div class="card-name"><?= $lang === 'th' ? 'ผู้ใช้แอดมิน' : 'Admin Users' ?></div>
      <div class="card-desc"><?= $lang === 'th' ? 'เพิ่มหรือลบบัญชีแอดมินพนักงาน' : 'Add or remove employee admin accounts' ?></div>
    </a>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
