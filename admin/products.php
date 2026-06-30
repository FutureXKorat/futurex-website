<?php
declare(strict_types=1);
include '../database.php';
include '../cloudinary.php';

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

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'add_product') {
        $sku     = trim((string)($_POST['sku']     ?? ''));
        $name    = trim((string)($_POST['name']    ?? ''));
        $price   = (float)($_POST['price']   ?? 0);
        $pv      = (float)($_POST['pv']      ?? 0);
        $unit_en = trim((string)($_POST['unit_en'] ?? ''));
        $unit_th = trim((string)($_POST['unit_th'] ?? ''));
        $imgUrl  = '';
        if (!empty($_FILES['img']['tmp_name'])) {
            $pubId  = 'product_' . time() . '_' . rand(1000, 9999);
            $up     = uploadProductImageToCloudinary($_FILES['img']['tmp_name'], $pubId);
            if ($up) $imgUrl = $up;
        }
        if ($sku !== '' && $name !== '') {
            $s = $conn->prepare("INSERT INTO products (sku, name, price, pv, unit_en, unit_th, img, stock, active, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), NOW())");
            $s->bind_param('ssddsss', $sku, $name, $price, $pv, $unit_en, $unit_th, $imgUrl);
            $s->execute(); $s->close();
        }

    } elseif ($action === 'edit_product') {
        $pid     = (int)($_POST['product_id'] ?? 0);
        $sku     = trim((string)($_POST['sku']     ?? ''));
        $name    = trim((string)($_POST['name']    ?? ''));
        $price   = (float)($_POST['price']   ?? 0);
        $pv      = (float)($_POST['pv']      ?? 0);
        $unit_en = trim((string)($_POST['unit_en'] ?? ''));
        $unit_th = trim((string)($_POST['unit_th'] ?? ''));
        if ($pid > 0 && $sku !== '' && $name !== '') {
            if (!empty($_FILES['img']['tmp_name'])) {
                $pubId = 'product_' . $pid . '_' . time();
                $up    = uploadProductImageToCloudinary($_FILES['img']['tmp_name'], $pubId);
                if ($up) {
                    $s = $conn->prepare("UPDATE products SET sku=?, name=?, price=?, pv=?, unit_en=?, unit_th=?, img=?, updated_at=NOW() WHERE id=?");
                    $s->bind_param('ssddsssi', $sku, $name, $price, $pv, $unit_en, $unit_th, $up, $pid);
                    $s->execute(); $s->close();
                } else {
                    // Upload failed — save without changing image
                    $s = $conn->prepare("UPDATE products SET sku=?, name=?, price=?, pv=?, unit_en=?, unit_th=?, updated_at=NOW() WHERE id=?");
                    $s->bind_param('ssddssi', $sku, $name, $price, $pv, $unit_en, $unit_th, $pid);
                    $s->execute(); $s->close();
                }
            } else {
                $s = $conn->prepare("UPDATE products SET sku=?, name=?, price=?, pv=?, unit_en=?, unit_th=?, updated_at=NOW() WHERE id=?");
                $s->bind_param('ssddssi', $sku, $name, $price, $pv, $unit_en, $unit_th, $pid);
                $s->execute(); $s->close();
            }
        }

    } elseif ($action === 'delete_product') {
        $pid     = (int)($_POST['product_id']  ?? 0);
        $confirm = trim((string)($_POST['confirm_text'] ?? ''));
        if ($pid > 0 && $confirm === 'delete-product') {
            $s = $conn->prepare("DELETE FROM products WHERE id=?");
            $s->bind_param('i', $pid);
            $s->execute(); $s->close();
        }
    }

    $qs = $lang !== 'en' ? '?lang=' . urlencode($lang) : '';
    header('Location: products.php' . $qs);
    exit;
}

// Fetch all products
$products = [];
$res = $conn->query("SELECT id, sku, name, price, pv, img, unit_en, unit_th, active FROM products ORDER BY id ASC");
if ($res) {
    while ($row = $res->fetch_assoc()) { $products[] = $row; }
    $res->free();
}

$isth = $lang === 'th';
$t = [
    'title'        => $isth ? 'จัดการสินค้า — Future X Admin' : 'Products — Future X Admin',
    'heading'      => $isth ? 'จัดการสินค้า' : 'Products',
    'subtitle'     => 'Future X Admin Panel',
    'back'         => $isth ? '← แดชบอร์ด' : '← Dashboard',
    'btn_add'      => $isth ? '+ เพิ่มสินค้า' : '+ Add Product',
    'col_product'  => $isth ? 'สินค้า' : 'Product',
    'col_sku'      => 'SKU',
    'col_price'    => $isth ? 'ราคา' : 'Price',
    'col_pv'       => 'PV',
    'col_units'    => $isth ? 'หน่วย' : 'Units',
    'col_status'   => $isth ? 'สถานะ' : 'Status',
    'col_actions'  => $isth ? 'จัดการ' : 'Actions',
    'modal_add'    => $isth ? 'เพิ่มสินค้าใหม่' : 'Add New Product',
    'modal_edit'   => $isth ? 'แก้ไขสินค้า' : 'Edit Product',
    'modal_del'    => $isth ? 'ลบสินค้า' : 'Delete Product',
    'lbl_sku'      => 'SKU',
    'lbl_name'     => $isth ? 'ชื่อสินค้า' : 'Product Name',
    'lbl_price'    => $isth ? 'ราคา (฿)' : 'Price (฿)',
    'lbl_pv'       => 'PV',
    'lbl_unit_en'  => $isth ? 'หน่วย (EN)' : 'Unit (EN)',
    'lbl_unit_th'  => $isth ? 'หน่วย (TH)' : 'Unit (TH)',
    'lbl_img'      => $isth ? 'รูปภาพ' : 'Image',
    'lbl_img_keep' => $isth ? 'เว้นว่างเพื่อเก็บรูปเดิม' : 'Leave empty to keep current image',
    'btn_save'     => $isth ? 'บันทึก' : 'Save',
    'btn_cancel'   => $isth ? 'ยกเลิก' : 'Cancel',
    'btn_edit'     => $isth ? 'แก้ไข' : 'Edit',
    'btn_delete'   => $isth ? 'ลบ' : 'Delete',
    'btn_del_confirm' => $isth ? 'ยืนยันการลบ' : 'Confirm Delete',
    'del_msg'      => $isth ? 'การกระทำนี้ไม่สามารถย้อนกลับได้ พิมพ์' : 'This cannot be undone. Type',
    'del_to'       => $isth ? 'เพื่อยืนยัน' : 'to confirm',
    'del_ph'       => $isth ? 'พิมพ์ delete-product' : 'Type delete-product',
    'status_on'    => $isth ? '● เปิดใช้งาน' : '● Active',
    'status_off'   => $isth ? '○ ปิด' : '○ Off',
    'current_img'  => $isth ? 'รูปปัจจุบัน' : 'Current image',
    'no_products'  => $isth ? 'ยังไม่มีสินค้า' : 'No products found.',
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

    .page-wrap { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

    .page-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 12px; margin-bottom: 28px; flex-wrap: wrap;
    }
    .page-title   { font-size: 1.75rem; font-weight: 700; margin: 0 0 2px; line-height: 1.2; }
    .page-subtitle { font-size: 0.82rem; color: #666; }

    .btn-back {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,0.55); border: 1px solid rgba(0,0,0,0.1);
      padding: 8px 18px; border-radius: 10px; font-weight: 500; font-size: 0.88rem;
      color: #333; text-decoration: none; transition: all .2s; white-space: nowrap;
      backdrop-filter: blur(6px);
    }
    .btn-back:hover { background: rgba(255,255,255,0.9); color: #000; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }

    .btn-add {
      display: inline-flex; align-items: center; gap: 6px;
      background: linear-gradient(135deg, #007BFF, #0056b3); color: #fff;
      padding: 9px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem;
      border: none; cursor: pointer; transition: all .2s; white-space: nowrap;
      text-decoration: none;
    }
    .btn-add:hover { box-shadow: 0 4px 14px rgba(0,123,255,0.35); transform: translateY(-1px); color: #fff; }

    .main-card {
      background: rgba(255,255,255,0.30);
      backdrop-filter: blur(14px);
      border-radius: 20px;
      padding: 22px 24px 28px;
      box-shadow: 0 12px 34px rgba(0,0,0,0.13);
    }

    /* Table */
    .prod-table { width: 100%; border-collapse: collapse; min-width: 780px; }
    .prod-table thead th {
      font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.07em; color: #666; padding: 10px 12px;
      border-bottom: 2px solid rgba(0,0,0,0.09); white-space: nowrap;
    }
    .prod-table tbody td { padding: 14px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); vertical-align: middle; }
    .prod-table tbody tr:hover td { background: rgba(0,123,255,0.03); }
    .prod-table tbody tr:last-child td { border-bottom: none; }

    /* Product cell */
    .prod-cell { display: flex; align-items: center; gap: 12px; }
    .prod-thumb {
      width: 52px; height: 52px; border-radius: 10px;
      object-fit: cover; border: 1.5px solid #e5e7eb; flex-shrink: 0;
      background: #f4f6f9;
    }
    .prod-name { font-weight: 600; font-size: 0.9rem; }

    /* SKU badge */
    .sku-badge {
      display: inline-block; font-size: 0.72rem; font-weight: 600;
      background: rgba(0,123,255,0.08); color: #0056b3;
      padding: 3px 9px; border-radius: 20px; letter-spacing: 0.03em;
      font-family: monospace;
    }

    /* Status pill */
    .status-pill {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 13px; border-radius: 20px;
      font-size: 0.78rem; font-weight: 600; white-space: nowrap;
    }
    .status-pill.on  { background: #D1E7DD; color: #0A3622; }
    .status-pill.off { background: #e9ecef; color: #6c757d; }

    /* Units */
    .unit-en { font-weight: 600; font-size: 0.85rem; }
    .unit-th { color: #888; font-size: 0.77rem; margin-top: 2px; }

    /* Action buttons */
    .act-btn {
      padding: 5px 14px; border-radius: 8px; border: 1.5px solid transparent;
      font-size: 0.8rem; font-weight: 600; cursor: pointer;
      transition: all .15s; white-space: nowrap;
    }
    .act-btn.edit {
      background: rgba(0,123,255,0.09); color: #0056b3;
      border-color: rgba(0,123,255,0.2);
    }
    .act-btn.edit:hover { background: rgba(0,123,255,0.18); transform: translateY(-1px); }
    .act-btn.del {
      background: rgba(220,53,69,0.07); color: #9c1826;
      border-color: rgba(220,53,69,0.18);
    }
    .act-btn.del:hover { background: rgba(220,53,69,0.17); transform: translateY(-1px); }

    .empty-state { text-align: center; padding: 52px 20px; color: #999; }

    /* Modals */
    .modal-content {
      border-radius: 16px; border: none;
      box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    }
    .modal-header { border-bottom: 1px solid rgba(0,0,0,0.07); padding: 20px 24px 16px; }
    .modal-body   { padding: 20px 24px; }
    .modal-footer { border-top: 1px solid rgba(0,0,0,0.07); padding: 16px 24px 20px; }

    .img-preview-wrap {
      width: 72px; height: 72px; border-radius: 10px;
      overflow: hidden; border: 1.5px solid #e5e7eb; background: #f4f6f9; flex-shrink: 0;
    }
    .img-preview-wrap img { width: 100%; height: 100%; object-fit: cover; }

    .del-keyword { font-weight: 700; color: #dc3545; font-family: monospace; }

    @media (max-width: 576px) {
      .page-title { font-size: 1.35rem; }
      .main-card  { padding: 16px 14px 24px; }
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page-wrap" style="margin-top: 36px; padding-bottom: 30px;">

  <div class="page-header">
    <div>
      <h1 class="page-title"><?= htmlspecialchars($t['heading']) ?></h1>
      <div class="page-subtitle"><?= htmlspecialchars($t['subtitle']) ?></div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addModal">
        <?= htmlspecialchars($t['btn_add']) ?>
      </button>
      <a href="index.php" class="btn-back"><?= htmlspecialchars($t['back']) ?></a>
    </div>
  </div>

  <div class="main-card">
    <?php if (empty($products)): ?>
    <div class="empty-state">
      <p><?= htmlspecialchars($t['no_products']) ?></p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="prod-table">
        <thead>
          <tr>
            <th><?= htmlspecialchars($t['col_product']) ?></th>
            <th><?= htmlspecialchars($t['col_sku']) ?></th>
            <th><?= htmlspecialchars($t['col_price']) ?></th>
            <th><?= htmlspecialchars($t['col_pv']) ?></th>
            <th><?= htmlspecialchars($t['col_units']) ?></th>
            <th><?= htmlspecialchars($t['col_status']) ?></th>
            <th><?= htmlspecialchars($t['col_actions']) ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p):
            $imgSrc = (string)$p['img'];
            if ($imgSrc !== '' && !str_starts_with($imgSrc, 'http')) {
                $imgSrc = '/' . ltrim($imgSrc, '/');
            }
            if ($imgSrc === '') $imgSrc = '/avatar.png';
          ?>
          <tr>
            <td>
              <div class="prod-cell">
                <img src="<?= htmlspecialchars($imgSrc) ?>" class="prod-thumb" alt=""
                     onerror="this.style.opacity='.25'">
                <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
              </div>
            </td>
            <td><span class="sku-badge"><?= htmlspecialchars($p['sku']) ?></span></td>
            <td style="font-weight:600;">฿<?= number_format((float)$p['price'], 2) ?></td>
            <td><?= htmlspecialchars((string)$p['pv']) ?></td>
            <td>
              <div class="unit-en"><?= htmlspecialchars($p['unit_en']) ?></div>
              <div class="unit-th"><?= htmlspecialchars($p['unit_th']) ?></div>
            </td>
            <td>
              <span class="status-pill <?= $p['active'] ? 'on' : 'off' ?>">
                <?= $p['active'] ? htmlspecialchars($t['status_on']) : htmlspecialchars($t['status_off']) ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:6px;">
                <button class="act-btn edit"
                  data-bs-toggle="modal" data-bs-target="#editModal"
                  data-id="<?= $p['id'] ?>"
                  data-sku="<?= htmlspecialchars($p['sku'],     ENT_QUOTES) ?>"
                  data-name="<?= htmlspecialchars($p['name'],   ENT_QUOTES) ?>"
                  data-price="<?= htmlspecialchars((string)$p['price'], ENT_QUOTES) ?>"
                  data-pv="<?= htmlspecialchars((string)$p['pv'], ENT_QUOTES) ?>"
                  data-unit-en="<?= htmlspecialchars($p['unit_en'], ENT_QUOTES) ?>"
                  data-unit-th="<?= htmlspecialchars($p['unit_th'], ENT_QUOTES) ?>"
                  data-img="<?= htmlspecialchars($imgSrc, ENT_QUOTES) ?>"
                ><?= htmlspecialchars($t['btn_edit']) ?></button>
                <button class="act-btn del"
                  data-bs-toggle="modal" data-bs-target="#deleteModal"
                  data-id="<?= $p['id'] ?>"
                  data-name="<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>"
                ><?= htmlspecialchars($t['btn_delete']) ?></button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- ── ADD MODAL ── -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_product">
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><?= htmlspecialchars($t['modal_add']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_sku']) ?></label>
            <input type="text" name="sku" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_name']) ?></label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_price']) ?></label>
              <input type="number" name="price" step="0.01" min="0" class="form-control" required>
            </div>
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_pv']) ?></label>
              <input type="number" name="pv" step="0.01" min="0" class="form-control" required>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_unit_en']) ?></label>
              <input type="text" name="unit_en" class="form-control">
            </div>
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_unit_th']) ?></label>
              <input type="text" name="unit_th" class="form-control">
            </div>
          </div>
          <div>
            <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_img']) ?></label>
            <input type="file" name="img" class="form-control" accept="image/*" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t['btn_cancel']) ?></button>
          <button type="submit" class="btn btn-primary fw-semibold"><?= htmlspecialchars($t['btn_save']) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── EDIT MODAL ── -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit_product">
        <input type="hidden" name="product_id" id="edit_pid">
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><?= htmlspecialchars($t['modal_edit']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_sku']) ?></label>
            <input type="text" name="sku" id="edit_sku" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_name']) ?></label>
            <input type="text" name="name" id="edit_name" class="form-control" required>
          </div>
          <div class="row g-3 mb-3">
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_price']) ?></label>
              <input type="number" name="price" id="edit_price" step="0.01" min="0" class="form-control" required>
            </div>
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_pv']) ?></label>
              <input type="number" name="pv" id="edit_pv" step="0.01" min="0" class="form-control" required>
            </div>
          </div>
          <div class="row g-3 mb-3">
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_unit_en']) ?></label>
              <input type="text" name="unit_en" id="edit_unit_en" class="form-control">
            </div>
            <div class="col">
              <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_unit_th']) ?></label>
              <input type="text" name="unit_th" id="edit_unit_th" class="form-control">
            </div>
          </div>
          <div>
            <label class="form-label fw-semibold"><?= htmlspecialchars($t['lbl_img']) ?></label>
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:8px;">
              <div class="img-preview-wrap">
                <img id="edit_img_preview" src="" alt="current">
              </div>
              <span style="font-size:0.78rem;color:#888;"><?= htmlspecialchars($t['current_img']) ?></span>
            </div>
            <input type="file" name="img" class="form-control" accept="image/*">
            <div style="font-size:0.77rem;color:#888;margin-top:4px;"><?= htmlspecialchars($t['lbl_img_keep']) ?></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t['btn_cancel']) ?></button>
          <button type="submit" class="btn btn-primary fw-semibold"><?= htmlspecialchars($t['btn_save']) ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── DELETE MODAL ── -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post">
        <input type="hidden" name="action" value="delete_product">
        <input type="hidden" name="product_id" id="del_pid">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" style="color:#dc3545;"><?= htmlspecialchars($t['modal_del']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p style="font-size:0.9rem;color:#444;margin-bottom:6px;">
            <?= htmlspecialchars($t['del_msg']) ?>
            <span class="del-keyword">delete-product</span>
            <?= htmlspecialchars($t['del_to']) ?>
          </p>
          <p id="del_name" style="font-weight:700;font-size:0.95rem;margin-bottom:14px;"></p>
          <input type="text" name="confirm_text" id="del_input"
                 class="form-control" placeholder="<?= htmlspecialchars($t['del_ph']) ?>"
                 autocomplete="off">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= htmlspecialchars($t['btn_cancel']) ?></button>
          <button type="submit" id="del_btn" class="btn btn-danger fw-semibold" disabled>
            <?= htmlspecialchars($t['btn_del_confirm']) ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate Edit modal from data attributes on the Edit button
document.getElementById('editModal').addEventListener('show.bs.modal', function (e) {
    var b = e.relatedTarget;
    document.getElementById('edit_pid').value         = b.dataset.id;
    document.getElementById('edit_sku').value         = b.dataset.sku;
    document.getElementById('edit_name').value        = b.dataset.name;
    document.getElementById('edit_price').value       = b.dataset.price;
    document.getElementById('edit_pv').value          = b.dataset.pv;
    document.getElementById('edit_unit_en').value     = b.dataset.unitEn;
    document.getElementById('edit_unit_th').value     = b.dataset.unitTh;
    document.getElementById('edit_img_preview').src   = b.dataset.img;
});

// Populate Delete modal and reset confirm input
document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
    var b = e.relatedTarget;
    document.getElementById('del_pid').value  = b.dataset.id;
    document.getElementById('del_name').textContent = b.dataset.name;
    document.getElementById('del_input').value      = '';
    document.getElementById('del_btn').disabled     = true;
});

// Enable delete button only when exact keyword is typed
document.getElementById('del_input').addEventListener('input', function () {
    document.getElementById('del_btn').disabled = this.value !== 'delete-product';
});
</script>
</body>
</html>
