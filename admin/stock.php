<?php
declare(strict_types=1);
include '../database.php';

include 'auth.php';

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $pid    = (int)($_POST['product_id'] ?? 0);
    if ($pid > 0) {
        if ($action === 'add_stock') {
            $amount = max(1, (int)($_POST['amount'] ?? 1));
            $s = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $s->bind_param('ii', $amount, $pid);
            $s->execute(); $s->close();
        } elseif ($action === 'remove_stock') {
            $amount = max(0, (int)($_POST['amount'] ?? 0));
            if ($amount === 0) {
                $s = $conn->prepare("UPDATE products SET stock = 0 WHERE id = ?");
                $s->bind_param('i', $pid);
            } else {
                $s = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE id = ?");
                $s->bind_param('ii', $amount, $pid);
            }
            $s->execute(); $s->close();
        } elseif ($action === 'toggle_active') {
            $s = $conn->prepare("UPDATE products SET active = IF(active=1, 0, 1) WHERE id = ?");
            $s->bind_param('i', $pid);
            $s->execute(); $s->close();
        }
    }
    $qs = $lang !== 'en' ? '?lang=' . urlencode($lang) : '';
    header('Location: stock.php' . $qs);
    exit;
}

// Fetch all products (active and inactive)
$products = [];
$res = $conn->query("SELECT id, name, price, stock, img, active FROM products ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) { $products[] = $row; }
    $res->free();
}

$isth = $lang === 'th';
$t = [
    'title'       => $isth ? 'จัดการสต็อก — Future X Admin' : 'Stock Management — Future X Admin',
    'heading'     => $isth ? 'จัดการสต็อก' : 'Stock Management',
    'subtitle'    => 'Future X Admin Panel',
    'back'        => $isth ? '← แดชบอร์ด' : '← Dashboard',
    'col_product' => $isth ? 'สินค้า' : 'Product',
    'col_stock'   => $isth ? 'สต็อก' : 'Stock',
    'col_status'  => $isth ? 'สถานะ' : 'Status',
    'col_add'     => $isth ? 'เพิ่มสต็อก' : 'Add Stock',
    'col_remove'  => $isth ? 'ลดสต็อก' : 'Remove Stock',
    'btn_active'  => $isth ? '● เปิดใช้งาน' : '● Active',
    'btn_off'     => $isth ? '○ ปิด' : '○ Off',
    'placeholder' => $isth ? 'ระบุจำนวน' : 'Custom amount',
    'btn_add'     => $isth ? '+ เพิ่ม' : '+ Add',
    'set_zero'    => $isth ? 'ล้างสต็อก' : 'Set 0',
    'tag_out'     => $isth ? 'หมด' : 'Out',
    'tag_low'     => $isth ? 'ใกล้หมด' : 'Low',
    'no_products' => $isth ? 'ยังไม่มีสินค้า' : 'No products found.',
    'confirm_zero'=> $isth ? 'ตั้งสต็อกเป็น 0 ใช่ไหม?' : 'Set stock to 0?',
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($t['title']) ?></title>
  <link rel="icon" type="image/png" href="/logo_transparent_onlyblack.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      color: #111;
    }
    @supports (height: 100dvh) { body { min-height: 100dvh; } }

    .page-wrap { max-width: 1300px; margin: 0 auto; padding: 0 20px; }

    .page-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 12px; margin-bottom: 28px; flex-wrap: wrap;
    }
    .page-title  { font-size: 1.75rem; font-weight: 700; margin: 0 0 2px; line-height: 1.2; }
    .page-subtitle { font-size: 0.82rem; color: #666; }

    .btn-back {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,0.55); border: 1px solid rgba(0,0,0,0.1);
      padding: 8px 18px; border-radius: 10px; font-weight: 500; font-size: 0.88rem;
      color: #333; text-decoration: none; transition: all .2s; white-space: nowrap;
      backdrop-filter: blur(6px);
    }
    .btn-back:hover { background: rgba(255,255,255,0.9); color: #000; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }

    .main-card {
      background: rgba(255,255,255,0.30);
      backdrop-filter: blur(14px);
      border-radius: 20px;
      padding: 22px 24px 28px;
      box-shadow: 0 12px 34px rgba(0,0,0,0.13);
    }

    /* Table */
    .stock-table { width: 100%; border-collapse: collapse; min-width: 860px; }
    .stock-table thead th {
      font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.07em; color: #666; padding: 10px 12px;
      border-bottom: 2px solid rgba(0,0,0,0.09); white-space: nowrap;
    }
    .stock-table tbody td { padding: 14px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); vertical-align: middle; }
    .stock-table tbody tr:hover td { background: rgba(0,123,255,0.03); }
    .stock-table tbody tr:last-child td { border-bottom: none; }

    /* Product cell */
    .prod-cell { display: flex; align-items: center; gap: 12px; }
    .prod-thumb {
      width: 52px; height: 52px; border-radius: 10px;
      object-fit: cover; border: 1.5px solid #e5e7eb; flex-shrink: 0;
      background: #f4f6f9;
    }
    .prod-name { font-weight: 600; font-size: 0.9rem; }
    .prod-price { font-size: 0.78rem; color: #888; margin-top: 2px; }

    /* Stock badge */
    .stock-badge {
      display: inline-flex; align-items: center; gap: 7px;
      font-size: 1.35rem; font-weight: 700; line-height: 1;
    }
    .stock-badge.stock-ok  { color: #198754; }
    .stock-badge.stock-low { color: #c98a00; }
    .stock-badge.stock-out { color: #dc3545; }
    .stock-tag {
      font-size: 0.65rem; font-weight: 700; padding: 2px 7px;
      border-radius: 20px; text-transform: uppercase; letter-spacing: 0.04em;
    }
    .stock-ok  .stock-tag { background: #D1E7DD; color: #0A3622; display: none; }
    .stock-low .stock-tag { background: #FFF3CD; color: #856404; }
    .stock-out .stock-tag { background: #F8D7DA; color: #58151C; }

    /* Toggle button */
    .toggle-btn {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 6px 14px; border-radius: 20px; border: none;
      font-size: 0.8rem; font-weight: 600; cursor: pointer;
      transition: all .2s; white-space: nowrap;
    }
    .toggle-btn.is-on  { background: #D1E7DD; color: #0A3622; }
    .toggle-btn.is-on:hover  { background: #b6d9c8; }
    .toggle-btn.is-off { background: #e9ecef; color: #6c757d; }
    .toggle-btn.is-off:hover { background: #dee2e6; }

    /* Quick action buttons */
    .qbtn {
      padding: 5px 10px; border-radius: 8px; border: 1.5px solid transparent;
      font-size: 0.8rem; font-weight: 600; cursor: pointer;
      transition: all .15s; white-space: nowrap; line-height: 1.4;
    }
    .qbtn.add {
      background: rgba(25,135,84,0.1); color: #0f5132;
      border-color: rgba(25,135,84,0.25);
    }
    .qbtn.add:hover { background: rgba(25,135,84,0.2); border-color: rgba(25,135,84,0.5); transform: translateY(-1px); }

    .qbtn.add-custom {
      background: linear-gradient(135deg,#198754,#0f5132); color: #fff;
      border-color: transparent;
    }
    .qbtn.add-custom:hover { box-shadow: 0 3px 10px rgba(25,135,84,0.35); transform: translateY(-1px); }

    .qbtn.rem {
      background: rgba(220,53,69,0.08); color: #9c1826;
      border-color: rgba(220,53,69,0.2);
    }
    .qbtn.rem:hover { background: rgba(220,53,69,0.18); border-color: rgba(220,53,69,0.45); transform: translateY(-1px); }

    .qbtn.zero {
      background: rgba(108,117,125,0.1); color: #495057;
      border-color: rgba(108,117,125,0.25);
    }
    .qbtn.zero:hover { background: rgba(108,117,125,0.2); transform: translateY(-1px); }

    /* Button rows */
    .btn-row { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 7px; }
    .btn-row:last-child { margin-bottom: 0; }

    /* Custom add row */
    .custom-row { display: flex; gap: 6px; align-items: center; }
    .custom-input {
      width: 120px; padding: 5px 10px; border-radius: 8px;
      border: 1.5px solid rgba(0,0,0,0.15); font-size: 0.82rem;
      background: rgba(255,255,255,0.7); outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .custom-input:focus { border-color: #198754; box-shadow: 0 0 0 3px rgba(25,135,84,0.15); }
    .custom-input::placeholder { color: #aaa; }
    .custom-input::-webkit-outer-spin-button,
    .custom-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .custom-input[type=number] { -moz-appearance: textfield; }

    /* Separate the quick form and custom form visually */
    .ctrl-group { display: flex; flex-direction: column; gap: 0; }

    .empty-state { text-align: center; padding: 52px 20px; color: #999; }

    @media (max-width: 576px) {
      .page-title { font-size: 1.35rem; }
      .main-card { padding: 16px 14px 24px; }
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>
<?php include 'scroll-restore.php'; ?>

<div class="page-wrap" style="margin-top: 36px;">

  <div class="page-header">
    <div>
      <h1 class="page-title"><?= htmlspecialchars($t['heading']) ?></h1>
      <div class="page-subtitle"><?= htmlspecialchars($t['subtitle']) ?></div>
    </div>
    <a href="index.php" class="btn-back"><?= htmlspecialchars($t['back']) ?></a>
  </div>

  <div class="main-card">
    <?php if (empty($products)): ?>
    <div class="empty-state">
      <p><?= htmlspecialchars($t['no_products']) ?></p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="stock-table">
        <thead>
          <tr>
            <th><?= htmlspecialchars($t['col_product']) ?></th>
            <th><?= htmlspecialchars($t['col_stock']) ?></th>
            <th><?= htmlspecialchars($t['col_status']) ?></th>
            <th><?= htmlspecialchars($t['col_add']) ?></th>
            <th><?= htmlspecialchars($t['col_remove']) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p):
            $pid      = (int)$p['id'];
            $stockNum = (int)$p['stock'];
            $stockCls = $stockNum <= 0 ? 'stock-out' : ($stockNum <= 5 ? 'stock-low' : 'stock-ok');

            // Build image src: root-relative for local paths, pass-through for external URLs
            $imgSrc = (string)$p['img'];
            if ($imgSrc !== '' && !str_starts_with($imgSrc, 'http')) {
                $imgSrc = '/' . ltrim($imgSrc, '/');
            }
            if ($imgSrc === '') $imgSrc = '/avatar.png';
          ?>
          <tr>
            <!-- Product -->
            <td>
              <div class="prod-cell">
                <img src="<?= htmlspecialchars($imgSrc) ?>" class="prod-thumb" alt=""
                     onerror="this.style.opacity='.25'">
                <div>
                  <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                  <div class="prod-price">฿<?= number_format((float)$p['price'], 2) ?></div>
                </div>
              </div>
            </td>

            <!-- Stock count -->
            <td>
              <span class="stock-badge <?= $stockCls ?>">
                <?= $stockNum ?>
                <span class="stock-tag">
                  <?= $stockNum <= 0 ? htmlspecialchars($t['tag_out']) : htmlspecialchars($t['tag_low']) ?>
                </span>
              </span>
            </td>

            <!-- Active toggle -->
            <td>
              <form method="post">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <button type="submit" class="toggle-btn <?= $p['active'] ? 'is-on' : 'is-off' ?>">
                  <?= $p['active'] ? htmlspecialchars($t['btn_active']) : htmlspecialchars($t['btn_off']) ?>
                </button>
              </form>
            </td>

            <!-- Add stock -->
            <td>
              <div class="ctrl-group">
                <!-- Quick preset buttons — each button carries its own amount -->
                <form method="post">
                  <input type="hidden" name="action" value="add_stock">
                  <input type="hidden" name="product_id" value="<?= $pid ?>">
                  <div class="btn-row">
                    <button type="submit" name="amount" value="1"  class="qbtn add">+1</button>
                    <button type="submit" name="amount" value="5"  class="qbtn add">+5</button>
                    <button type="submit" name="amount" value="10" class="qbtn add">+10</button>
                    <button type="submit" name="amount" value="50" class="qbtn add">+50</button>
                  </div>
                </form>
                <!-- Custom amount — separate form so it doesn't conflict with the preset buttons -->
                <form method="post" class="custom-row">
                  <input type="hidden" name="action" value="add_stock">
                  <input type="hidden" name="product_id" value="<?= $pid ?>">
                  <input type="number" name="amount" min="1" step="1"
                         placeholder="<?= htmlspecialchars($t['placeholder']) ?>"
                         class="custom-input" required>
                  <button type="submit" class="qbtn add-custom"><?= htmlspecialchars($t['btn_add']) ?></button>
                </form>
              </div>
            </td>

            <!-- Remove stock -->
            <td>
              <form method="post">
                <input type="hidden" name="action" value="remove_stock">
                <input type="hidden" name="product_id" value="<?= $pid ?>">
                <div class="btn-row">
                  <button type="submit" name="amount" value="1"  class="qbtn rem">−1</button>
                  <button type="submit" name="amount" value="5"  class="qbtn rem">−5</button>
                  <button type="submit" name="amount" value="10" class="qbtn rem">−10</button>
                  <button type="submit" name="amount" value="0"  class="qbtn zero"
                          onclick="return confirm(<?= json_encode($t['confirm_zero']) ?>)">
                    <?= htmlspecialchars($t['set_zero']) ?>
                  </button>
                </div>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
