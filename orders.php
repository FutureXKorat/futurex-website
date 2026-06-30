<?php
declare(strict_types=1);
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php'); exit;
}

$userId = (int)$_SESSION['user_id'];

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_order') {
    $oid = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['order_id'] ?? ''));
    if ($oid !== '') {
        $stmt = $conn->prepare("DELETE FROM `orders` WHERE order_id = ? AND user_id = ?");
        $stmt->bind_param('si', $oid, $userId);
        $stmt->execute();
        $stmt->close();
    }
    $qs = $lang !== 'en' ? '?lang=' . urlencode($lang) : '';
    header('Location: orders.php' . $qs); exit;
}

$texts = [
    'en' => [
        'title'        => 'My Orders — Future X',
        'heading'      => 'My Orders',
        'no_orders'    => "You haven't placed any orders yet.",
        'shop_now'     => 'Shop Now',
        'view_details' => 'View Details',
        'st_pending'   => 'Pending Review',
        'st_approved'  => 'Approved',
        'st_rejected'  => 'Rejected',
        'del_pickup'   => 'Pick Up',
        'del_ship'     => 'Shipping',
        'total'        => 'Total',
        'items_label'  => 'items',
        'modal_items'  => 'Item',
        'modal_qty'    => 'Qty',
        'modal_price'  => 'Price',
        'modal_line'   => 'Total',
        'modal_subtotal'=> 'Subtotal',
        'modal_shipping'=> 'Shipping Fee',
        'modal_total'  => 'Grand Total',
        'modal_delivery'=> 'Delivery',
        'modal_address' => 'Shipping Address',
        'modal_slip'   => 'Your Payment Slip',
        'modal_no_slip'=> 'No slip uploaded',
        'modal_close'  => 'Close',
        'modal_status' => 'Status',
        'placed_on'    => 'Placed',
        'order_id'     => 'Order',
        'note_pending' => 'We received your payment slip and are reviewing it. We\'ll confirm shortly.',
        'note_approved'=> 'Your payment has been verified. Your order is confirmed!',
        'note_rejected'=> 'Your payment could not be verified. Please contact us for help.',
        'click_slip'     => 'Click to open full size',
        'img_error'      => 'Image not available',
        'lang'           => 'ภาษาไทย',
        'btn_delete'     => 'Delete',
        'confirm_delete' => 'Delete this order? This cannot be undone.',
        'rejection_reason_label' => 'Reason:',
        'pickup_appt'    => 'Pick-Up Appointment',
        'view_map'       => 'View store on Google Maps',
    ],
    'th' => [
        'title'        => 'คำสั่งซื้อของฉัน — Future X',
        'heading'      => 'คำสั่งซื้อของฉัน',
        'no_orders'    => 'คุณยังไม่มีคำสั่งซื้อ',
        'shop_now'     => 'เลือกซื้อสินค้า',
        'view_details' => 'ดูรายละเอียด',
        'st_pending'   => 'รอตรวจสอบ',
        'st_approved'  => 'อนุมัติแล้ว',
        'st_rejected'  => 'ไม่อนุมัติ',
        'del_pickup'   => 'รับเอง',
        'del_ship'     => 'จัดส่งถึงบ้าน',
        'total'        => 'ยอดรวม',
        'items_label'  => 'รายการ',
        'modal_items'  => 'รายการสินค้า',
        'modal_qty'    => 'จำนวน',
        'modal_price'  => 'ราคา',
        'modal_line'   => 'ยอด',
        'modal_subtotal'=> 'ราคารวม',
        'modal_shipping'=> 'ค่าส่ง',
        'modal_total'  => 'ยอดสุทธิ',
        'modal_delivery'=> 'วิธีรับสินค้า',
        'modal_address' => 'ที่อยู่จัดส่ง',
        'modal_slip'   => 'สลิปการชำระเงิน',
        'modal_no_slip'=> 'ไม่มีสลิปที่อัปโหลด',
        'modal_close'  => 'ปิด',
        'modal_status' => 'สถานะ',
        'placed_on'    => 'สั่งเมื่อ',
        'order_id'     => 'คำสั่งซื้อ',
        'note_pending' => 'เราได้รับสลิปของคุณแล้วและกำลังตรวจสอบ จะแจ้งผลเร็ว ๆ นี้',
        'note_approved'=> 'ยืนยันการชำระเงินแล้ว คำสั่งซื้อของคุณได้รับการยืนยัน!',
        'note_rejected'=> 'ไม่สามารถยืนยันการชำระเงินได้ กรุณาติดต่อเราเพื่อขอความช่วยเหลือ',
        'click_slip'     => 'คลิกเพื่อดูขนาดเต็ม',
        'img_error'      => 'ไม่พบรูปภาพ',
        'lang'           => 'English',
        'btn_delete'     => 'ลบ',
        'confirm_delete' => 'ลบคำสั่งซื้อนี้? ไม่สามารถกู้คืนได้',
        'rejection_reason_label' => 'เหตุผล:',
        'pickup_appt'    => 'นัดรับสินค้า',
        'view_map'       => 'ดูที่ตั้งร้านบน Google Maps',
    ],
];
$t = $texts[$lang] ?? $texts['en'];

// Map stored English reason keys → translated display text
$rejectionReasonMap = [
    'Slip is Incorrect'    => ['en' => 'Slip is Incorrect',    'th' => 'สลิปไม่ถูกต้อง'],
    'Payment Not Received' => ['en' => 'Payment Not Received', 'th' => 'ยังไม่ได้รับเงิน'],
    'Address is Incorrect' => ['en' => 'Address is Incorrect', 'th' => 'ที่อยู่ไม่ถูกต้อง'],
    'No More Stock'        => ['en' => 'No More Stock',        'th' => 'สินค้าหมดแล้ว'],
];

// Read only this user's orders from MySQL (newest first)
$orders = [];
$stmt = $conn->prepare(
    "SELECT data, status, created_at, updated_at FROM `orders` WHERE user_id = ? ORDER BY created_at DESC"
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $rec = json_decode((string)$row['data'], true);
    if (is_array($rec)) {
        $rec['status']     = $row['status']; // DB column is authoritative
        $rec['created_at'] = $row['created_at'];
        if ($row['updated_at']) $rec['updated_at'] = $row['updated_at'];
        $orders[] = $rec;
    }
}
$stmt->close();

// Helpers
function ordFmtDate(string $iso): string {
    if ($iso === '') return '—';
    try {
        return (new DateTime($iso))->format('d/m/Y  H:i');
    } catch (Exception $e) {
        return substr($iso, 0, 16);
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($t['title']) ?></title>
  <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
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
      padding: 0 20px 24px;
      color: #111;
    }
    @supports (height: 100dvh) { body { min-height: 100dvh; } }

    .top-banner {
      position: fixed;
      top: 0; left: 0; right: 0;
      height: 60px;
      z-index: 1000;
    }

    .page-wrap { max-width: 780px; margin: 50px auto 0; padding: 0 0 8px; }

    /* ── Page Heading ── */
    .page-heading {
      font-size: 1.7rem; font-weight: 700; margin-bottom: 22px; text-align: center;
    }

    /* ── Order Card ── */
    .order-card {
      background: rgba(255,255,255,0.35);
      backdrop-filter: blur(12px);
      border-radius: 18px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.10);
      margin-bottom: 18px;
      overflow: hidden;
      transition: transform .2s, box-shadow .2s;
      border-left: 5px solid #ccc;
    }
    .order-card:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(0,0,0,0.14); }
    .order-card.s-pending  { border-left-color: #ffc107; }
    .order-card.s-approved { border-left-color: #198754; }
    .order-card.s-rejected { border-left-color: #dc3545; }

    /* ── Card Header ── */
    .card-head {
      display: flex; align-items: center; justify-content: space-between;
      padding: 14px 20px 12px; gap: 10px; flex-wrap: wrap;
      border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .card-head-left  { display: flex; flex-direction: column; gap: 2px; }
    .card-oid { font-family: 'Courier New', monospace; font-size: 0.8rem; color: #555; }
    .card-date { font-size: 0.75rem; color: #999; }

    /* ── Status Badge ── */
    .status-badge {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 13px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;
    }
    .status-badge .dot {
      width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
    }
    .badge-pending  { background: #FFF3CD; color: #856404; }
    .badge-pending .dot  { background: #ffc107; }
    .badge-approved { background: #D1E7DD; color: #0A3622; }
    .badge-approved .dot { background: #198754; }
    .badge-rejected { background: #F8D7DA; color: #58151C; }
    .badge-rejected .dot { background: #dc3545; }

    /* ── Card Body ── */
    .card-body-inner { padding: 14px 20px; }

    /* ── Status Note ── */
    .status-note {
      font-size: 0.82rem; padding: 9px 14px; border-radius: 10px; margin-bottom: 12px;
    }
    .note-pending  { background: rgba(255,193,7,0.12); color: #856404; }
    .note-approved { background: rgba(25,135,84,0.10); color: #0a4724; }
    .note-rejected { background: rgba(220,53,69,0.10); color: #721c24; }

    /* ── Items List ── */
    .items-list { list-style: none; padding: 0; margin: 0 0 14px; }
    .items-list li {
      display: flex; justify-content: space-between; align-items: center;
      padding: 5px 0; border-bottom: 1px dashed rgba(0,0,0,0.07);
      font-size: 0.88rem;
    }
    .items-list li:last-child { border-bottom: none; }
    .item-name { color: #222; }
    .item-qty  { color: #666; font-size: 0.8rem; margin-left: 8px; white-space: nowrap; }

    /* ── Card Footer ── */
    .card-foot {
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 10px;
      padding: 12px 20px 14px;
      border-top: 1px solid rgba(0,0,0,0.06);
      background: rgba(255,255,255,0.25);
    }
    .foot-left { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
    .delivery-pill {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(0,0,0,0.06); border-radius: 20px;
      padding: 4px 12px; font-size: 0.78rem; color: #555; font-weight: 500;
    }
    .grand-total { font-size: 1.05rem; font-weight: 700; color: #111; }

    /* ── Buttons ── */
    .btn-details {
      display: inline-flex; align-items: center; gap: 6px;
      background: linear-gradient(135deg, #007BFF, #0056b3);
      color: #fff; border: none; padding: 8px 18px; border-radius: 10px;
      font-size: 0.85rem; font-weight: 600; cursor: pointer;
      transition: all .25s; text-decoration: none;
    }
    .btn-details:hover {
      background: linear-gradient(135deg, #0056b3, #003f7f);
      transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,86,179,0.3);
      color: #fff;
    }
    .btn-delete {
      display: inline-flex; align-items: center; gap: 5px;
      background: rgba(220,53,69,0.1); color: #b02a37; border: 1px solid rgba(220,53,69,0.25);
      padding: 8px 14px; border-radius: 10px; font-size: 0.85rem; font-weight: 600;
      cursor: pointer; transition: all .2s;
    }
    .btn-delete:hover {
      background: #dc3545; color: #fff; border-color: #dc3545;
      transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220,53,69,0.3);
    }

    /* ── Empty State ── */
    .empty-card {
      background: rgba(255,255,255,0.35);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.09);
      padding: 52px 24px; text-align: center;
    }
    .empty-icon { opacity: 0.3; margin-bottom: 16px; }
    .empty-card p { color: #777; margin-bottom: 20px; font-size: 0.95rem; }
    .btn-shop {
      display: inline-flex; align-items: center; gap: 8px;
      background: linear-gradient(135deg, #007BFF, #0056b3);
      color: #fff; border: none; padding: 12px 28px; border-radius: 12px;
      font-size: 1rem; font-weight: 600; cursor: pointer;
      transition: all .25s; text-decoration: none;
    }
    .btn-shop:hover {
      background: linear-gradient(135deg, #0056b3, #003f7f);
      transform: translateY(-2px); box-shadow: 0 4px 14px rgba(0,86,179,0.35);
      color: #fff;
    }

    /* ── Modal ── */
    .modal-content {
      border-radius: 20px !important;
      background: rgba(255,255,255,0.98) !important;
      backdrop-filter: blur(16px);
      border: none !important;
      box-shadow: 0 24px 64px rgba(0,0,0,0.18);
    }
    .modal-header { border-bottom: 1px solid rgba(0,0,0,0.07) !important; padding: 18px 22px !important; }
    .modal-footer { border-top: 1px solid rgba(0,0,0,0.07) !important; padding: 14px 20px !important; }

    .modal-status-bar {
      border-radius: 12px; padding: 12px 16px; margin-bottom: 18px;
      font-size: 0.88rem; display: flex; align-items: center; gap: 10px;
    }
    .modal-status-bar.s-pending  { background: rgba(255,193,7,0.15); color: #856404; }
    .modal-status-bar.s-approved { background: rgba(25,135,84,0.12); color: #0a4724; }
    .modal-status-bar.s-rejected { background: rgba(220,53,69,0.12); color: #721c24; }
    .modal-status-bar .dot {
      width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0;
    }
    .modal-status-bar.s-pending .dot  { background: #ffc107; }
    .modal-status-bar.s-approved .dot { background: #198754; }
    .modal-status-bar.s-rejected .dot { background: #dc3545; }

    .items-table { width: 100%; border-collapse: collapse; font-size: 0.86rem; }
    .items-table th {
      font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.06em; color: #888; padding: 7px 6px;
      border-bottom: 2px solid rgba(0,0,0,0.08);
    }
    .items-table td { padding: 9px 6px; border-bottom: 1px dashed rgba(0,0,0,0.06); }
    .items-table tr:last-child td { border-bottom: none; }

    .totals-box {
      background: rgba(233,242,255,0.7); border-radius: 12px;
      padding: 14px 16px; margin-top: 14px; font-size: 0.9rem;
    }
    .total-row { display: flex; justify-content: space-between; padding: 5px 0; }
    .total-row.grand {
      border-top: 1.5px solid rgba(0,0,0,0.1); margin-top: 6px;
      padding-top: 10px; font-weight: 700; font-size: 1.05rem;
    }

    .slip-wrap { margin-top: 20px; }
    .slip-label { font-size: 0.78rem; font-weight: 600; text-transform: uppercase;
                  letter-spacing: 0.06em; color: #888; margin-bottom: 8px; }
    .slip-img {
      max-width: 100%; max-height: 320px; width: 100%;
      object-fit: contain; border-radius: 12px;
      border: 1px solid rgba(0,0,0,0.08); display: block;
      cursor: zoom-in; transition: transform .2s;
    }
    .slip-img:hover { transform: scale(1.01); }
    .slip-hint { font-size: 0.74rem; color: #bbb; margin-top: 5px; text-align: center; }
    .slip-placeholder {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      height: 100px; background: #f4f6f9; border-radius: 12px;
      color: #ccc; font-size: 0.85rem; border: 2px dashed #e0e0e0;
    }

    .info-row {
      display: flex; align-items: flex-start; gap: 10px;
      font-size: 0.87rem; padding: 6px 0;
      border-bottom: 1px dashed rgba(0,0,0,0.06);
    }
    .info-row:last-child { border-bottom: none; }
    .info-row-label { color: #888; font-weight: 500; min-width: 110px; flex-shrink: 0; }
    .info-row-val   { color: #111; word-break: break-word; }

    @media (max-width: 480px) {
      body { padding: 72px 12px 48px; }
      .page-heading { font-size: 1.4rem; }
      .card-head { padding: 12px 14px 10px; }
      .card-body-inner { padding: 12px 14px; }
      .card-foot { padding: 10px 14px 12px; }
    }
  </style>
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="page-wrap">

  <h1 class="page-heading"><?= htmlspecialchars($t['heading']) ?></h1>

  <?php if (empty($orders)): ?>
  <!-- Empty state -->
  <div class="empty-card">
    <div class="empty-icon">
      <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#007BFF" stroke-width="1.2">
        <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 0 1-8 0"/>
      </svg>
    </div>
    <p><?= htmlspecialchars($t['no_orders']) ?></p>
    <a href="products.php" class="btn-shop"><?= htmlspecialchars($t['shop_now']) ?></a>
  </div>

  <?php else: ?>
  <!-- Order Cards -->
  <?php foreach ($orders as $ord):
    $oid     = $ord['order_id'] ?? '';
    $status  = $ord['status']   ?? 'awaiting_review';
    $items   = $ord['items']    ?? [];
    $amounts = $ord['amounts']  ?? [];
    $total   = (float)($amounts['total'] ?? 0);
    $deliv   = ($ord['delivery'] ?? '') === 'ship' ? $t['del_ship'] : $t['del_pickup'];
    $dateStr = ordFmtDate((string)($ord['created_at'] ?? ''));

    $statusClass = match($status) {
      'approved' => 'approved', 'rejected' => 'rejected', default => 'pending'
    };
    $badgeClass = 'badge-' . $statusClass;
    $statusLabel = match($status) {
      'approved' => $t['st_approved'], 'rejected' => $t['st_rejected'], default => $t['st_pending']
    };
    $noteClass = 'note-' . $statusClass;
    $noteText = match($status) {
      'approved' => $t['note_approved'], 'rejected' => $t['note_rejected'], default => $t['note_pending']
    };
    if ($status === 'rejected' && !empty($ord['rejection_reason'])) {
      $rawReason = (string)$ord['rejection_reason'];
      $reason = $rejectionReasonMap[$rawReason][$lang] ?? $rawReason;
      $noteText .= ' ' . $t['rejection_reason_label'] . ' ' . $reason;
    }

    $dataJson = htmlspecialchars(
      json_encode($ord, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG),
      ENT_QUOTES, 'UTF-8'
    );
  ?>
  <div class="order-card s-<?= $statusClass ?>">

    <!-- Card Header -->
    <div class="card-head">
      <div class="card-head-left">
        <span class="card-oid">#<?= htmlspecialchars($oid) ?></span>
        <span class="card-date"><?= htmlspecialchars($dateStr) ?></span>
      </div>
      <span class="status-badge <?= $badgeClass ?>">
        <span class="dot"></span>
        <?= htmlspecialchars($statusLabel) ?>
      </span>
    </div>

    <!-- Card Body -->
    <div class="card-body-inner">
      <!-- Status note -->
      <div class="status-note <?= $noteClass ?>"><?= htmlspecialchars($noteText) ?></div>

      <?php if (($ord['delivery'] ?? '') !== 'ship' && !empty($ord['pickup_time'])): ?>
      <!-- Pickup appointment info -->
      <div style="display:flex;align-items:center;gap:10px;background:rgba(233,242,255,0.7);border:1.5px solid rgba(0,123,255,0.15);border-radius:10px;padding:10px 13px;margin-bottom:12px;font-size:.84rem;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#007BFF" stroke-width="2" style="flex-shrink:0;"><path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <div>
          <span style="color:#555;font-weight:500;"><?= htmlspecialchars($t['pickup_appt']) ?>:</span>
          <strong style="color:#111;margin-left:4px;"><?= htmlspecialchars((string)$ord['pickup_time']) ?></strong>
          &nbsp;·&nbsp;
          <a href="https://maps.app.goo.gl/q2r3e8apCCvh5XAs6" target="_blank" rel="noopener" style="color:#007BFF;font-size:.78rem;text-decoration:none;"><?= htmlspecialchars($t['view_map']) ?> ↗</a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Items list (first 3 items, hint if more) -->
      <ul class="items-list">
        <?php
          $preview = array_slice($items, 0, 3);
          $extra   = count($items) - count($preview);
          foreach ($preview as $it):
            $iname = (string)($it['name'] ?? '');
            $iqty  = (int)($it['qty'] ?? 1);
        ?>
        <li>
          <span class="item-name"><?= htmlspecialchars($iname) ?></span>
          <span class="item-qty">× <?= $iqty ?></span>
        </li>
        <?php endforeach; ?>
        <?php if ($extra > 0): ?>
        <li style="color:#aaa;font-size:.8rem;">+ <?= $extra ?> more <?= htmlspecialchars($t['items_label']) ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Card Footer -->
    <div class="card-foot">
      <div class="foot-left">
        <span class="delivery-pill">
          <?php if (($ord['delivery'] ?? '') === 'ship'): ?>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          <?php else: ?>
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <?php endif; ?>
          <?= htmlspecialchars($deliv) ?>
        </span>
        <span class="grand-total"><?= number_format($total, 2) ?> ฿</span>
      </div>
      <div class="d-flex gap-2">
        <button class="btn-details" data-order="<?= $dataJson ?>" onclick="openModal(this)">
          <?= htmlspecialchars($t['view_details']) ?>
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
        </button>
        <form method="post" style="display:contents;">
          <input type="hidden" name="action"   value="delete_order">
          <input type="hidden" name="order_id" value="<?= htmlspecialchars($oid) ?>">
          <button type="submit" class="btn-delete"
                  onclick="return confirm(<?= json_encode($t['confirm_delete']) ?>)">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>
            <?= htmlspecialchars($t['btn_delete']) ?>
          </button>
        </form>
      </div>
    </div>

  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div><!-- /page-wrap -->

<!-- ══════════════════════════════════════
     Order Detail Modal
══════════════════════════════════════ -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modalTitle">—</h5>
          <div id="modalDate" style="font-size:.78rem;color:#999;margin-top:2px;"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" id="modalBody" style="padding:20px 22px;"></div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" id="modalCloseBtn">—</button>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const i18n = <?= json_encode([
  'modal_items'    => $t['modal_items'],
  'modal_qty'      => $t['modal_qty'],
  'modal_price'    => $t['modal_price'],
  'modal_line'     => $t['modal_line'],
  'modal_subtotal' => $t['modal_subtotal'],
  'modal_shipping' => $t['modal_shipping'],
  'modal_total'    => $t['modal_total'],
  'modal_delivery' => $t['modal_delivery'],
  'modal_address'  => $t['modal_address'],
  'modal_slip'     => $t['modal_slip'],
  'modal_no_slip'  => $t['modal_no_slip'],
  'modal_close'    => $t['modal_close'],
  'modal_status'   => $t['modal_status'],
  'del_pickup'     => $t['del_pickup'],
  'del_ship'       => $t['del_ship'],
  'st_pending'     => $t['st_pending'],
  'st_approved'    => $t['st_approved'],
  'st_rejected'    => $t['st_rejected'],
  'note_pending'   => $t['note_pending'],
  'note_approved'  => $t['note_approved'],
  'note_rejected'  => $t['note_rejected'],
  'click_slip'     => $t['click_slip'],
  'img_error'      => $t['img_error'],
  'placed_on'              => $t['placed_on'],
  'rejection_reason_label' => $t['rejection_reason_label'],
  'pickup_appt'            => $t['pickup_appt'],
  'view_map'               => $t['view_map'],
], JSON_UNESCAPED_UNICODE) ?>;

// Map stored English reason keys → current language for JS modal display
const REASON_MAP = <?= json_encode([
  'Slip is Incorrect'    => $rejectionReasonMap['Slip is Incorrect'][$lang]    ?? 'Slip is Incorrect',
  'Payment Not Received' => $rejectionReasonMap['Payment Not Received'][$lang] ?? 'Payment Not Received',
  'Address is Incorrect' => $rejectionReasonMap['Address is Incorrect'][$lang] ?? 'Address is Incorrect',
  'No More Stock'        => $rejectionReasonMap['No More Stock'][$lang]        ?? 'No More Stock',
], JSON_UNESCAPED_UNICODE) ?>;

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmt(n) {
  return Number(n).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function fmtDate(iso) {
  if (!iso) return '';
  try {
    const d = new Date(iso);
    const p = n => String(n).padStart(2,'0');
    return `${p(d.getDate())}/${p(d.getMonth()+1)}/${d.getFullYear()}  ${p(d.getHours())}:${p(d.getMinutes())}`;
  } catch(e) { return iso.substring(0,16); }
}

let _modal = null;

function openModal(btn) {
  const ord    = JSON.parse(btn.getAttribute('data-order'));
  const items  = ord.items   || [];
  const amounts= ord.amounts || {};
  const slip   = ord.slip    || '';
  const status = ord.status  || 'awaiting_review';
  const deliv  = ord.delivery === 'ship' ? i18n.del_ship : i18n.del_pickup;

  // Status config
  const stMap = {
    'awaiting_review': { cls: 's-pending',  label: i18n.st_pending,  note: i18n.note_pending  },
    'approved':        { cls: 's-approved', label: i18n.st_approved, note: i18n.note_approved },
    'rejected':        { cls: 's-rejected', label: i18n.st_rejected, note: i18n.note_rejected },
  };
  const st = stMap[status] || stMap['awaiting_review'];

  // Title & date
  document.getElementById('modalTitle').textContent = '#' + (ord.order_id || '');
  document.getElementById('modalDate').textContent = i18n.placed_on + ': ' + fmtDate(ord.created_at || '');
  document.getElementById('modalCloseBtn').textContent = i18n.modal_close;

  // Items table
  const rows = items.map(it => {
    const qty   = parseInt(it.qty)    || 1;
    const price = parseFloat(it.price) || 0;
    return `<tr>
      <td>${esc(it.name||'')}</td>
      <td style="text-align:center;">×${qty}</td>
      <td style="text-align:right;white-space:nowrap;">${fmt(price)} ฿</td>
      <td style="text-align:right;white-space:nowrap;">${fmt(qty*price)} ฿</td>
    </tr>`;
  }).join('') || `<tr><td colspan="4" style="text-align:center;color:#aaa;">—</td></tr>`;

  // Delivery info rows
  let infoHtml = `
    <div class="info-row">
      <span class="info-row-label">${esc(i18n.modal_delivery)}</span>
      <span class="info-row-val">${esc(deliv)}</span>
    </div>`;
  if (ord.delivery === 'ship' && ord.address) {
    infoHtml += `
    <div class="info-row">
      <span class="info-row-label">${esc(i18n.modal_address)}</span>
      <span class="info-row-val" style="white-space:pre-wrap;">${esc(ord.address)}</span>
    </div>`;
  }
  if (ord.delivery !== 'ship' && ord.pickup_time) {
    infoHtml += `
    <div class="info-row">
      <span class="info-row-label">${esc(i18n.pickup_appt)}</span>
      <span class="info-row-val">
        <strong>${esc(ord.pickup_time)}</strong><br>
        <a href="https://maps.app.goo.gl/q2r3e8apCCvh5XAs6" target="_blank" rel="noopener"
           style="font-size:.78rem;color:#007BFF;text-decoration:none;">${esc(i18n.view_map)} ↗</a>
      </span>
    </div>`;
  }

  // Slip section
  let slipHtml;
  if (slip) {
    slipHtml = `
      <a href="${esc(slip)}" target="_blank" rel="noopener">
        <img src="${esc(slip)}" class="slip-img" alt="Payment Slip"
          onerror="this.closest('a').outerHTML='<div class=\\'slip-placeholder\\'>${esc(i18n.img_error)}</div>'">
      </a>
      <div class="slip-hint">${esc(i18n.click_slip)}</div>`;
  } else {
    slipHtml = `<div class="slip-placeholder">${esc(i18n.modal_no_slip)}</div>`;
  }

  let noteDisplay = st.note;
  if (status === 'rejected' && ord.rejection_reason) {
    const translatedReason = REASON_MAP[ord.rejection_reason] || ord.rejection_reason;
    noteDisplay += ' ' + i18n.rejection_reason_label + ' ' + esc(translatedReason);
  }

  document.getElementById('modalBody').innerHTML = `
    <!-- Status bar -->
    <div class="modal-status-bar ${st.cls}">
      <span class="dot"></span>
      <span><strong>${esc(st.label)}</strong> — ${noteDisplay}</span>
    </div>

    <!-- Items table -->
    <table class="items-table">
      <thead>
        <tr>
          <th style="width:45%;">${esc(i18n.modal_items)}</th>
          <th style="width:12%;text-align:center;">${esc(i18n.modal_qty)}</th>
          <th style="width:20%;text-align:right;">${esc(i18n.modal_price)}</th>
          <th style="width:23%;text-align:right;">${esc(i18n.modal_line)}</th>
        </tr>
      </thead>
      <tbody>${rows}</tbody>
    </table>

    <!-- Totals -->
    <div class="totals-box">
      <div class="total-row"><span>${esc(i18n.modal_subtotal)}</span><span>${fmt(amounts.subtotal||0)} ฿</span></div>
      <div class="total-row"><span>${esc(i18n.modal_shipping)}</span><span>${fmt(amounts.shipping||0)} ฿</span></div>
      <div class="total-row grand"><span>${esc(i18n.modal_total)}</span><span>${fmt(amounts.total||0)} ฿</span></div>
    </div>

    <!-- Delivery info -->
    <div style="margin-top:16px;">${infoHtml}</div>

    <!-- Slip -->
    <div class="slip-wrap">
      <div class="slip-label">${esc(i18n.modal_slip)}</div>
      ${slipHtml}
    </div>`;

  if (!_modal) _modal = new bootstrap.Modal(document.getElementById('orderModal'));
  _modal.show();
}
</script>
</body>
</html>
