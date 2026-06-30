<?php
declare(strict_types=1);
include '../database.php'; // gives $conn, $lang, session

if (!isset($_SESSION['user_id'])) {
    header('Location: https://futurexthailand.com/index.php'); exit;
}

// Admin check
$_mailCfgPath = dirname(__DIR__) . '/secure-config/futurex_mail.php';
$_mailCfg = is_file($_mailCfgPath) ? require $_mailCfgPath : [];
$ADMIN_EMAIL = strtolower(trim((string)($_mailCfg['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'futurexkorat@gmail.com')));

$_stmt = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
$_stmt->bind_param('i', $_SESSION['user_id']);
$_stmt->execute();
$_row = $_stmt->get_result()->fetch_assoc();
$_stmt->close();
$_userEmail = strtolower(trim((string)($_row['email'] ?? '')));
if ($_userEmail === '' || $_userEmail !== $ADMIN_EMAIL) {
    header('Location: https://futurexthailand.com/home.php'); exit;
}

// Main site URL (used to build slip image URLs)
$mainSiteUrl = 'https://futurexthailand.com';

// i18n
$texts = [
    'en' => [
        'title'          => 'Order Log — Future X Admin',
        'heading'        => 'Order Log',
        'subtitle'       => 'Future X Admin Panel',
        'stat_total'     => 'Total Orders',
        'stat_pending'   => 'Pending Review',
        'stat_approved'  => 'Approved',
        'stat_rejected'  => 'Rejected',
        'col_id'         => 'Order ID',
        'col_customer'   => 'Customer',
        'col_items'      => 'Items',
        'col_total'      => 'Total',
        'col_delivery'   => 'Delivery',
        'col_status'     => 'Status',
        'col_date'       => 'Date',
        'col_actions'    => 'Actions',
        'btn_view'       => 'View Details',
        'btn_approve'    => 'Approve',
        'btn_reject'     => 'Reject',
        'no_orders'      => 'No orders yet.',
        'tab_all'        => 'All',
        'tab_pending'    => 'Pending',
        'tab_approved'   => 'Approved',
        'tab_rejected'   => 'Rejected',
        'modal_slip'     => 'Payment Slip',
        'modal_no_slip'  => 'No slip uploaded',
        'modal_details'  => 'Order Details',
        'modal_items'    => 'Item',
        'modal_qty'      => 'Qty',
        'modal_line'     => 'Total',
        'modal_subtotal' => 'Subtotal',
        'modal_shipping' => 'Shipping Fee',
        'modal_total'    => 'Grand Total',
        'modal_delivery' => 'Delivery',
        'modal_address'  => 'Address',
        'modal_customer' => 'Customer',
        'modal_email'    => 'Email',
        'modal_status'   => 'Status',
        'modal_close'    => 'Close',
        'del_pickup'     => 'Pick Up',
        'del_ship'       => 'Shipping',
        'st_pending'     => 'Pending Review',
        'st_approved'    => 'Approved',
        'st_rejected'    => 'Rejected',
        'back'           => '← Dashboard',
        'lang'           => 'ภาษาไทย',
        'confirm_reject' => 'Reject this order?',
        'items_label'    => 'items',
        'click_slip'     => 'Click image to open full size',
        'view_original'  => 'Open original',
        'img_not_found'  => 'Image not available',
        'order_id_label' => 'Order ID',
        'created_label'  => 'Placed',
        'updated_label'  => 'Updated',
        'search_ph'           => 'Search by ID or customer…',
        'bulk_approve'        => 'Approve Selected',
        'bulk_reject'         => 'Reject Selected',
        'bulk_selected'       => 'selected',
        'bulk_clear'          => 'Clear selection',
        'confirm_bulk_approve'=> 'Approve {n} order(s)?',
        'confirm_bulk_reject' => 'Reject {n} order(s)?',
    ],
    'th' => [
        'title'          => 'บันทึกคำสั่งซื้อ — Future X Admin',
        'heading'        => 'บันทึกคำสั่งซื้อ',
        'subtitle'       => 'Future X แอดมิน',
        'stat_total'     => 'ทั้งหมด',
        'stat_pending'   => 'รอตรวจสอบ',
        'stat_approved'  => 'อนุมัติแล้ว',
        'stat_rejected'  => 'ปฏิเสธ',
        'col_id'         => 'เลขที่คำสั่งซื้อ',
        'col_customer'   => 'ลูกค้า',
        'col_items'      => 'รายการ',
        'col_total'      => 'ยอดรวม',
        'col_delivery'   => 'การส่ง',
        'col_status'     => 'สถานะ',
        'col_date'       => 'วันที่',
        'col_actions'    => 'จัดการ',
        'btn_view'       => 'ดูรายละเอียด',
        'btn_approve'    => 'อนุมัติ',
        'btn_reject'     => 'ปฏิเสธ',
        'no_orders'      => 'ยังไม่มีคำสั่งซื้อ',
        'tab_all'        => 'ทั้งหมด',
        'tab_pending'    => 'รอตรวจ',
        'tab_approved'   => 'อนุมัติ',
        'tab_rejected'   => 'ปฏิเสธ',
        'modal_slip'     => 'สลิปการชำระเงิน',
        'modal_no_slip'  => 'ไม่มีสลิปที่อัปโหลด',
        'modal_details'  => 'รายละเอียดคำสั่งซื้อ',
        'modal_items'    => 'รายการสินค้า',
        'modal_qty'      => 'จำนวน',
        'modal_line'     => 'ยอด',
        'modal_subtotal' => 'ราคารวม',
        'modal_shipping' => 'ค่าส่ง',
        'modal_total'    => 'ยอดสุทธิ',
        'modal_delivery' => 'วิธีรับสินค้า',
        'modal_address'  => 'ที่อยู่',
        'modal_customer' => 'ลูกค้า',
        'modal_email'    => 'อีเมล',
        'modal_status'   => 'สถานะ',
        'modal_close'    => 'ปิด',
        'del_pickup'     => 'รับเอง',
        'del_ship'       => 'จัดส่งถึงบ้าน',
        'st_pending'     => 'รอตรวจสอบ',
        'st_approved'    => 'อนุมัติแล้ว',
        'st_rejected'    => 'ปฏิเสธ',
        'back'           => '← แดชบอร์ด',
        'lang'           => 'English',
        'confirm_reject' => 'ต้องการปฏิเสธคำสั่งซื้อนี้?',
        'items_label'    => 'รายการ',
        'click_slip'     => 'คลิกรูปเพื่อดูขนาดเต็ม',
        'view_original'  => 'เปิดต้นฉบับ',
        'img_not_found'  => 'ไม่พบรูปภาพ',
        'order_id_label' => 'เลขที่',
        'created_label'  => 'สั่งเมื่อ',
        'updated_label'  => 'อัปเดต',
        'search_ph'           => 'ค้นหาด้วยเลขที่หรือลูกค้า…',
        'bulk_approve'        => 'อนุมัติที่เลือก',
        'bulk_reject'         => 'ปฏิเสธที่เลือก',
        'bulk_selected'       => 'รายการที่เลือก',
        'bulk_clear'          => 'ยกเลิกการเลือก',
        'confirm_bulk_approve'=> 'อนุมัติ {n} คำสั่งซื้อ?',
        'confirm_bulk_reject' => 'ปฏิเสธ {n} คำสั่งซื้อ?',
    ],
];
$t = $texts[$lang] ?? $texts['en'];

// Handle POST: delete order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_order') {
    $oid = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['order_id'] ?? ''));
    if ($oid !== '') {
        $stmt = $conn->prepare("DELETE FROM `orders` WHERE order_id = ?");
        $stmt->bind_param('s', $oid);
        $stmt->execute();
        $stmt->close();
    }
    $qs = $lang !== 'en' ? '?lang=' . urlencode($lang) : '';
    header('Location: orders.php' . $qs);
    exit;
}

// Handle POST: status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $oid       = preg_replace('/[^A-Za-z0-9_-]/', '', (string)($_POST['order_id'] ?? ''));
    $allowed   = ['approved', 'rejected', 'awaiting_review'];
    $newStatus = in_array($_POST['status'] ?? '', $allowed, true) ? $_POST['status'] : '';
    if ($oid !== '' && $newStatus !== '') {
        // Cut stock when approving — guard: only deduct if order wasn't already approved
        if ($newStatus === 'approved') {
            $chk = $conn->prepare("SELECT status, data FROM `orders` WHERE order_id = ? LIMIT 1");
            $chk->bind_param('s', $oid);
            $chk->execute();
            $chkRow = $chk->get_result()->fetch_assoc();
            $chk->close();
            if ($chkRow && $chkRow['status'] !== 'approved') {
                $orderData = json_decode((string)$chkRow['data'], true);
                foreach ((array)($orderData['items'] ?? []) as $item) {
                    $iName = (string)($item['name'] ?? '');
                    $iQty  = max(1, (int)($item['qty'] ?? 1));
                    if ($iName !== '') {
                        $upd = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE name = ?");
                        $upd->bind_param('is', $iQty, $iName);
                        $upd->execute();
                        $upd->close();
                    }
                }
            }
        }
        $stmt = $conn->prepare("UPDATE `orders` SET status = ?, updated_at = NOW() WHERE order_id = ?");
        $stmt->bind_param('ss', $newStatus, $oid);
        $stmt->execute();
        $stmt->close();
    }
    $qs = $lang !== 'en' ? '?lang=' . urlencode($lang) : '';
    header('Location: orders.php' . $qs);
    exit;
}

// Handle POST: bulk status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'bulk_update') {
    $allowed   = ['approved', 'rejected'];
    $newStatus = in_array($_POST['status'] ?? '', $allowed, true) ? $_POST['status'] : '';
    $rawIds    = is_array($_POST['order_ids'] ?? null) ? $_POST['order_ids'] : [];
    if ($newStatus !== '' && !empty($rawIds)) {
        foreach ($rawIds as $rawId) {
            $oid = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$rawId);
            if ($oid === '') continue;
            if ($newStatus === 'approved') {
                $chk = $conn->prepare("SELECT status, data FROM `orders` WHERE order_id = ? LIMIT 1");
                $chk->bind_param('s', $oid);
                $chk->execute();
                $chkRow = $chk->get_result()->fetch_assoc();
                $chk->close();
                if ($chkRow && $chkRow['status'] !== 'approved') {
                    $orderData = json_decode((string)$chkRow['data'], true);
                    foreach ((array)($orderData['items'] ?? []) as $item) {
                        $iName = (string)($item['name'] ?? '');
                        $iQty  = max(1, (int)($item['qty'] ?? 1));
                        if ($iName !== '') {
                            $upd = $conn->prepare("UPDATE products SET stock = GREATEST(0, stock - ?) WHERE name = ?");
                            $upd->bind_param('is', $iQty, $iName);
                            $upd->execute();
                            $upd->close();
                        }
                    }
                }
            }
            // Only update if still awaiting_review (prevents re-processing)
            $stmt = $conn->prepare("UPDATE `orders` SET status = ?, updated_at = NOW() WHERE order_id = ? AND status = 'awaiting_review'");
            $stmt->bind_param('ss', $newStatus, $oid);
            $stmt->execute();
            $stmt->close();
        }
    }
    $qs = $lang !== 'en' ? '?lang=' . urlencode($lang) : '';
    header('Location: orders.php' . $qs);
    exit;
}

// Read all orders — pending first, then approved/rejected (newest first within each group)
$orders = [];
$result = $conn->query(
    "SELECT data, status, created_at, updated_at FROM `orders`
     ORDER BY CASE status WHEN 'awaiting_review' THEN 0 ELSE 1 END ASC, created_at DESC"
);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $rec = json_decode((string)$row['data'], true);
        if (is_array($rec) && isset($rec['order_id'])) {
            $rec['status']     = $row['status']; // DB column is authoritative
            $rec['created_at'] = $row['created_at'];
            if ($row['updated_at']) $rec['updated_at'] = $row['updated_at'];
            $orders[] = $rec;
        }
    }
}

// Stats
$cnt = ['all' => count($orders), 'awaiting_review' => 0, 'approved' => 0, 'rejected' => 0];
foreach ($orders as $o) {
    $s = $o['status'] ?? 'awaiting_review';
    if (isset($cnt[$s])) $cnt[$s]++;
}

// Helpers
function fmtDate(string $iso): string {
    if ($iso === '') return '—';
    try {
        return (new DateTime($iso))->format('d/m/Y  H:i');
    } catch (Exception $e) {
        return substr($iso, 0, 16);
    }
}
function statusLabel(string $status, array $t): string {
    return match ($status) {
        'awaiting_review' => $t['st_pending'],
        'approved'        => $t['st_approved'],
        'rejected'        => $t['st_rejected'],
        default           => $status,
    };
}
function statusClass(string $status): string {
    return match ($status) {
        'awaiting_review' => 'badge-pending',
        'approved'        => 'badge-approved',
        'rejected'        => 'badge-rejected',
        default           => 'badge-secondary text-white',
    };
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($t['title']) ?></title>
  <link rel="icon" type="image/png" href="/logo_transparent_onlyblack.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
      padding: 0;
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

    .stat-card {
      background: rgba(255,255,255,0.45);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 18px 20px 16px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.07);
      text-align: center;
      border-top: 3px solid transparent;
      transition: transform .25s, box-shadow .25s;
      cursor: default;
    }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
    .stat-card.c-total   { border-top-color: #007BFF; }
    .stat-card.c-pending { border-top-color: #ffc107; }
    .stat-card.c-approved{ border-top-color: #198754; }
    .stat-card.c-rejected{ border-top-color: #dc3545; }
    .stat-num { font-size: 2.1rem; font-weight: 700; line-height: 1; }
    .stat-num.c-total    { color: #007BFF; }
    .stat-num.c-pending  { color: #c98a00; }
    .stat-num.c-approved { color: #198754; }
    .stat-num.c-rejected { color: #dc3545; }
    .stat-label { font-size: 0.78rem; color: #666; margin-top: 5px; font-weight: 500; }

    .main-card {
      background: rgba(255,255,255,0.30);
      backdrop-filter: blur(14px);
      border-radius: 20px;
      padding: 22px 24px 28px;
      box-shadow: 0 12px 34px rgba(0,0,0,0.13);
    }

    .toolbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
    .search-box {
      flex: 1; min-width: 180px; max-width: 300px;
      padding: 8px 14px; border-radius: 10px; border: 1.5px solid rgba(0,0,0,0.12);
      background: rgba(255,255,255,0.6); font-size: 0.88rem; outline: none;
      transition: border-color .2s, box-shadow .2s;
    }
    .search-box:focus { border-color: #007BFF; box-shadow: 0 0 0 3px rgba(0,123,255,0.15); }

    .filter-wrap { display: flex; gap: 8px; flex-wrap: wrap; }
    .ftab {
      padding: 6px 16px; border-radius: 20px; border: 1.5px solid rgba(0,0,0,0.12);
      background: rgba(255,255,255,0.4); cursor: pointer; font-size: 0.84rem; font-weight: 500;
      color: #444; transition: all .2s; line-height: 1.4; white-space: nowrap;
    }
    .ftab:hover  { background: rgba(0,123,255,0.08); border-color: #007BFF; color: #007BFF; }
    .ftab.active { background: #007BFF; color: #fff; border-color: #007BFF; }

    .order-table { width: 100%; border-collapse: collapse; min-width: 700px; }
    .order-table thead th {
      font-size: 0.72rem; font-weight: 600; text-transform: uppercase;
      letter-spacing: 0.07em; color: #666; padding: 10px 12px;
      border-bottom: 2px solid rgba(0,0,0,0.09); white-space: nowrap;
    }
    .order-table tbody td { padding: 13px 12px; border-bottom: 1px solid rgba(0,0,0,0.05); vertical-align: middle; }
    .order-table tbody tr { transition: background .15s; }
    .order-table tbody tr:hover td { background: rgba(0,123,255,0.04); }
    .order-table tbody tr:last-child td { border-bottom: none; }

    .badge { display: inline-block; font-size: 0.73rem; padding: 4px 10px; border-radius: 20px; font-weight: 600; white-space: nowrap; }
    .badge-pending  { background: #FFF3CD; color: #856404; }
    .badge-approved { background: #D1E7DD; color: #0A3622; }
    .badge-rejected { background: #F8D7DA; color: #58151C; }

    .act-btn {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 5px 12px; border-radius: 8px; border: none;
      font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all .2s;
      line-height: 1.3; white-space: nowrap;
    }
    .act-btn:disabled { opacity: .5; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
    .act-view    { background: rgba(0,123,255,0.10); color: #0056b3; }
    .act-view:hover   { background: rgba(0,123,255,0.2); transform: translateY(-1px); }
    .act-approve { background: linear-gradient(135deg, #198754, #0f5132); color: #fff; }
    .act-approve:hover { box-shadow: 0 3px 10px rgba(25,135,84,0.4); transform: translateY(-1px); }
    .act-reject  { background: linear-gradient(135deg, #dc3545, #9c1826); color: #fff; }
    .act-reject:hover  { box-shadow: 0 3px 10px rgba(220,53,69,0.4); transform: translateY(-1px); }
    .act-delete  { background: rgba(220,53,69,0.1); color: #dc3545; border: 1px solid rgba(220,53,69,0.3); }
    .act-delete:hover  { background: rgba(220,53,69,0.2); transform: translateY(-1px); }

    .oid {
      font-family: 'Courier New', monospace; font-size: 0.75rem;
      color: #555; background: rgba(0,0,0,0.06); padding: 3px 7px;
      border-radius: 5px; white-space: nowrap;
    }

    .lang-switch {
      position: fixed; top: 16px; right: 16px; z-index: 300;
      background: rgba(255,255,255,0.75); border: none;
      padding: 7px 14px; border-radius: 9px; font-weight: 600;
      cursor: pointer; text-decoration: none; color: #007BFF;
      transition: all .2s; font-size: 0.85rem; backdrop-filter: blur(8px);
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .lang-switch:hover { background: rgba(255,255,255,0.97); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.14); color: #0056b3; }

    .modal-content {
      border-radius: 20px !important;
      background: rgba(255,255,255,0.98) !important;
      backdrop-filter: blur(16px);
      border: none !important;
      box-shadow: 0 24px 64px rgba(0,0,0,0.2);
    }
    .modal-header { border-bottom: 1px solid rgba(0,0,0,0.07) !important; padding: 18px 24px !important; }
    .modal-footer { border-top: 1px solid rgba(0,0,0,0.07) !important; padding: 14px 20px !important; gap: 8px; }

    .slip-wrap { position: relative; }
    .slip-img {
      max-width: 100%; max-height: 360px; width: 100%;
      object-fit: contain; border-radius: 12px;
      border: 1px solid rgba(0,0,0,0.09);
      transition: transform .25s; cursor: zoom-in; display: block;
    }
    .slip-img:hover { transform: scale(1.015); }
    .slip-placeholder {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      height: 180px; background: #f4f6f9; border-radius: 12px;
      color: #aaa; font-size: 0.88rem; border: 2px dashed #ddd; gap: 8px;
    }

    .info-grid { display: grid; grid-template-columns: auto 1fr; gap: 6px 16px; font-size: 0.88rem; }
    .info-label { color: #666; font-weight: 500; white-space: nowrap; }
    .info-val   { color: #111; word-break: break-word; }

    .items-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
    .items-table th {
      font-weight: 600; color: #555; padding: 7px 6px;
      border-bottom: 2px solid rgba(0,0,0,0.08); font-size: 0.72rem;
      text-transform: uppercase; letter-spacing: 0.05em;
    }
    .items-table td { padding: 8px 6px; border-bottom: 1px dashed rgba(0,0,0,0.06); }
    .items-table tr:last-child td { border-bottom: none; }

    .totals-box {
      background: rgba(233,242,255,0.7); border-radius: 12px;
      padding: 14px 16px; margin-top: 14px; font-size: 0.9rem;
    }
    .total-row { display: flex; justify-content: space-between; padding: 5px 0; }
    .total-row.grand { border-top: 1.5px solid rgba(0,0,0,0.1); margin-top: 6px; padding-top: 10px; font-weight: 700; font-size: 1.05rem; }

    .empty-state { text-align: center; padding: 52px 20px; color: #999; }
    .empty-icon  { opacity: 0.3; margin-bottom: 14px; }

    .order-row.hidden { display: none; }

    /* Checkbox column */
    .cb-col { width: 42px; text-align: center !important; padding-left: 8px !important; }
    .order-cb { width: 16px; height: 16px; cursor: pointer; accent-color: #007BFF; vertical-align: middle; }
    .order-cb:disabled { opacity: 0.28; cursor: not-allowed; }

    /* Bulk action bar */
    .bulk-bar {
      display: none;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
      padding: 10px 16px;
      background: rgba(0,123,255,0.07);
      border: 1.5px solid rgba(0,123,255,0.2);
      border-radius: 12px;
      margin-bottom: 16px;
      font-size: 0.85rem;
      animation: fadeSlide .18s ease;
    }
    @keyframes fadeSlide { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .bulk-count { font-weight: 600; color: #0056b3; flex: 1; min-width: 60px; }

    @media (max-width: 576px) {
      body { padding: 52px 12px 40px; }
      .page-title { font-size: 1.35rem; }
      .stat-num  { font-size: 1.7rem; }
      .main-card { padding: 16px 14px 24px; }
    }
  </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="page-wrap" style="margin-top: 36px;">

  <div class="page-header">
    <div>
      <h1 class="page-title"><?= htmlspecialchars($t['heading']) ?></h1>
      <div class="page-subtitle"><?= htmlspecialchars($t['subtitle']) ?></div>
    </div>
    <a href="index.php" class="btn-back"><?= htmlspecialchars($t['back']) ?></a>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
      <div class="stat-card c-total">
        <div class="stat-num c-total"><?= $cnt['all'] ?></div>
        <div class="stat-label"><?= htmlspecialchars($t['stat_total']) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card c-pending">
        <div class="stat-num c-pending"><?= $cnt['awaiting_review'] ?></div>
        <div class="stat-label"><?= htmlspecialchars($t['stat_pending']) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card c-approved">
        <div class="stat-num c-approved"><?= $cnt['approved'] ?></div>
        <div class="stat-label"><?= htmlspecialchars($t['stat_approved']) ?></div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="stat-card c-rejected">
        <div class="stat-num c-rejected"><?= $cnt['rejected'] ?></div>
        <div class="stat-label"><?= htmlspecialchars($t['stat_rejected']) ?></div>
      </div>
    </div>
  </div>

  <!-- Main Card -->
  <div class="main-card">

    <div class="toolbar">
      <input
        type="text"
        id="searchInput"
        class="search-box"
        placeholder="<?= htmlspecialchars($t['search_ph']) ?>"
        autocomplete="off"
      >
      <div class="filter-wrap">
        <button class="ftab active" data-filter="all"><?= htmlspecialchars($t['tab_all']) ?> (<?= $cnt['all'] ?>)</button>
        <button class="ftab" data-filter="awaiting_review"><?= htmlspecialchars($t['tab_pending']) ?> (<?= $cnt['awaiting_review'] ?>)</button>
        <button class="ftab" data-filter="approved"><?= htmlspecialchars($t['tab_approved']) ?> (<?= $cnt['approved'] ?>)</button>
        <button class="ftab" data-filter="rejected"><?= htmlspecialchars($t['tab_rejected']) ?> (<?= $cnt['rejected'] ?>)</button>
      </div>
    </div>

    <!-- Bulk action bar (shown when ≥1 checkbox is checked) -->
    <div class="bulk-bar" id="bulkBar">
      <span class="bulk-count" id="bulkCount">0 <?= htmlspecialchars($t['bulk_selected']) ?></span>
      <button type="button" class="act-btn act-approve" onclick="submitBulk('approved')"><?= htmlspecialchars($t['bulk_approve']) ?></button>
      <button type="button" class="act-btn act-reject"  onclick="submitBulk('rejected')"><?= htmlspecialchars($t['bulk_reject']) ?></button>
      <button type="button" class="act-btn act-view"    id="bulkClear"><?= htmlspecialchars($t['bulk_clear']) ?></button>
    </div>

    <?php if (empty($orders)): ?>
    <div class="empty-state">
      <div class="empty-icon">
        <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="#007BFF" stroke-width="1.2">
          <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
          <rect x="9" y="3" width="6" height="4" rx="1"/>
          <line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="12" y2="16"/>
        </svg>
      </div>
      <p><?= htmlspecialchars($t['no_orders']) ?></p>
    </div>
    <?php else: ?>

    <div class="table-responsive">
      <table class="order-table">
        <thead>
          <tr>
            <th class="cb-col"><input type="checkbox" id="selectAll" class="order-cb" title="<?= $lang === 'th' ? 'เลือกทั้งหมด' : 'Select all pending' ?>"></th>
            <th><?= htmlspecialchars($t['col_id']) ?></th>
            <th><?= htmlspecialchars($t['col_customer']) ?></th>
            <th><?= htmlspecialchars($t['col_items']) ?></th>
            <th><?= htmlspecialchars($t['col_total']) ?></th>
            <th><?= htmlspecialchars($t['col_delivery']) ?></th>
            <th><?= htmlspecialchars($t['col_status']) ?></th>
            <th><?= htmlspecialchars($t['col_date']) ?></th>
            <th><?= htmlspecialchars($t['col_actions']) ?></th>
          </tr>
        </thead>
        <tbody id="ordersBody">
          <?php foreach ($orders as $ord):
            $oid     = $ord['order_id'] ?? '';
            $uname   = $ord['username'] ?? '—';
            $email   = $ord['user_email'] ?? '';
            $nitems  = count($ord['items'] ?? []);
            $total   = (float)($ord['amounts']['total'] ?? 0);
            $status  = $ord['status'] ?? 'awaiting_review';
            $deliv   = ($ord['delivery'] ?? '') === 'ship' ? $t['del_ship'] : $t['del_pickup'];
            $dateStr = fmtDate((string)($ord['created_at'] ?? ''));
            $dataJson = htmlspecialchars(json_encode($ord, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG), ENT_QUOTES, 'UTF-8');
            $searchIdx = strtolower($oid . ' ' . $uname . ' ' . $email);
          ?>
          <tr class="order-row" data-status="<?= htmlspecialchars($status) ?>" data-search="<?= htmlspecialchars($searchIdx) ?>">
            <td class="cb-col">
              <input type="checkbox" class="order-cb" value="<?= htmlspecialchars($oid) ?>"
                     <?= $status !== 'awaiting_review' ? 'disabled' : '' ?>>
            </td>
            <td><span class="oid"><?= htmlspecialchars($oid) ?></span></td>
            <td>
              <div class="fw-500"><?= htmlspecialchars($uname) ?></div>
              <?php if ($email): ?>
                <div style="font-size:.76rem;color:#888;"><?= htmlspecialchars($email) ?></div>
              <?php endif; ?>
            </td>
            <td><?= $nitems ?> <?= htmlspecialchars($t['items_label']) ?></td>
            <td class="fw-bold" style="white-space:nowrap;"><?= number_format($total, 2) ?> ฿</td>
            <td><?= htmlspecialchars($deliv) ?></td>
            <td>
              <span class="badge <?= statusClass($status) ?>">
                <?= htmlspecialchars(statusLabel($status, $t)) ?>
              </span>
            </td>
            <td style="white-space:nowrap;font-size:.8rem;color:#666;"><?= htmlspecialchars($dateStr) ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">

                <button class="act-btn act-view" data-order="<?= $dataJson ?>" onclick="openModal(this)">
                  <?= htmlspecialchars($t['btn_view']) ?>
                </button>

                <?php if ($status === 'awaiting_review'): ?>
                  <form method="post" style="display:contents;">
                    <input type="hidden" name="action"   value="update_status">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($oid) ?>">
                    <input type="hidden" name="status"   value="approved">
                    <button type="submit" class="act-btn act-approve">
                      <?= htmlspecialchars($t['btn_approve']) ?>
                    </button>
                  </form>
                  <form method="post" style="display:contents;">
                    <input type="hidden" name="action"   value="update_status">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($oid) ?>">
                    <input type="hidden" name="status"   value="rejected">
                    <button type="submit" class="act-btn act-reject"
                            onclick="return confirm(<?= json_encode($t['confirm_reject']) ?>)">
                      <?= htmlspecialchars($t['btn_reject']) ?>
                    </button>
                  </form>
                <?php endif; ?>

                <button class="act-btn act-delete" onclick="confirmDeleteOrder('<?= htmlspecialchars($oid, ENT_QUOTES) ?>')">
                  <?= $lang === 'th' ? 'ลบ' : 'Delete' ?>
                </button>

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

<!-- Modal: Order Details -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title fw-bold mb-0" id="modalTitle">—</h5>
          <div id="modalSubtitle" style="font-size:.8rem;color:#888;margin-top:2px;"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="modalBody" style="padding:22px 24px;"></div>
      <div class="modal-footer d-flex flex-wrap" id="modalFooter" style="padding:14px 20px;gap:8px;"></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const MAIN_SITE_URL = <?= json_encode($mainSiteUrl) ?>;

const i18n = <?= json_encode([
  'modal_slip'     => $t['modal_slip'],
  'modal_no_slip'  => $t['modal_no_slip'],
  'modal_items'    => $t['modal_items'],
  'modal_qty'      => $t['modal_qty'],
  'modal_line'     => $t['modal_line'],
  'modal_subtotal' => $t['modal_subtotal'],
  'modal_shipping' => $t['modal_shipping'],
  'modal_total'    => $t['modal_total'],
  'modal_delivery' => $t['modal_delivery'],
  'modal_address'  => $t['modal_address'],
  'modal_customer' => $t['modal_customer'],
  'modal_email'    => $t['modal_email'],
  'modal_status'   => $t['modal_status'],
  'modal_close'    => $t['modal_close'],
  'btn_approve'    => $t['btn_approve'],
  'btn_reject'     => $t['btn_reject'],
  'del_pickup'     => $t['del_pickup'],
  'del_ship'       => $t['del_ship'],
  'st_pending'     => $t['st_pending'],
  'st_approved'    => $t['st_approved'],
  'st_rejected'    => $t['st_rejected'],
  'confirm_reject' => $t['confirm_reject'],
  'click_slip'     => $t['click_slip'],
  'view_original'  => $t['view_original'],
  'img_not_found'  => $t['img_not_found'],
  'order_id_label' => $t['order_id_label'],
  'created_label'  => $t['created_label'],
  'updated_label'  => $t['updated_label'],
  'items_label'    => $t['items_label'],
], JSON_UNESCAPED_UNICODE) ?>;

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmt(n) {
  return Number(n).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
}
function statusBadge(status) {
  const map = {
    'awaiting_review': ['badge-pending',  i18n.st_pending],
    'approved':        ['badge-approved', i18n.st_approved],
    'rejected':        ['badge-rejected', i18n.st_rejected],
  };
  const [cls, label] = map[status] || ['badge bg-secondary text-white', status];
  return `<span class="badge ${cls}">${esc(label)}</span>`;
}
function formatIso(iso) {
  if (!iso) return '—';
  try {
    const d = new Date(iso);
    const dd = String(d.getDate()).padStart(2,'0');
    const mm = String(d.getMonth()+1).padStart(2,'0');
    const yy = d.getFullYear();
    const hh = String(d.getHours()).padStart(2,'0');
    const mi = String(d.getMinutes()).padStart(2,'0');
    return `${dd}/${mm}/${yy} ${hh}:${mi}`;
  } catch(e) { return iso.substring(0,16); }
}

let _modal = null;

function openModal(btn) {
  const ord    = JSON.parse(btn.getAttribute('data-order'));
  const items  = ord.items   || [];
  const amounts= ord.amounts || {};
  const status = ord.status  || 'awaiting_review';

  // Slip images are stored as relative paths on the main site — prefix with main domain
  const slip_raw = ord.slip || '';
  const slip = slip_raw
    ? (slip_raw.startsWith('http') ? slip_raw : MAIN_SITE_URL + '/' + slip_raw)
    : '';

  document.getElementById('modalTitle').textContent = '#' + (ord.order_id || '');
  document.getElementById('modalSubtitle').textContent =
    esc(i18n.created_label) + ': ' + formatIso(ord.created_at || '') +
    (ord.updated_at ? '  ·  ' + esc(i18n.updated_label) + ': ' + formatIso(ord.updated_at) : '');

  const itemRows = items.map(it => {
    const qty   = parseInt(it.qty)   || 1;
    const price = parseFloat(it.price) || 0;
    return `<tr>
      <td>${esc(it.name||'')}</td>
      <td style="text-align:center;">×${qty}</td>
      <td style="text-align:right;white-space:nowrap;">${fmt(qty*price)} ฿</td>
    </tr>`;
  }).join('');

  const delLabel = (ord.delivery === 'ship') ? i18n.del_ship : i18n.del_pickup;

  let slipHtml;
  if (slip) {
    slipHtml = `
      <a href="${esc(slip)}" target="_blank" rel="noopener" title="${esc(i18n.view_original)}">
        <img
          src="${esc(slip)}"
          class="slip-img"
          alt="Slip"
          onerror="this.closest('a').outerHTML='<div class=\\'slip-placeholder\\'><svg width=\\'32\\' height=\\'32\\' fill=\\'none\\' stroke=\\'#ccc\\' stroke-width=\\'1.5\\'><path d=\\'M4 4h16v16H4z\\'/></svg><span>${esc(i18n.img_not_found)}</span></div>'"
        >
      </a>
      <div style="font-size:.74rem;color:#aaa;margin-top:5px;text-align:center;">${esc(i18n.click_slip)}</div>`;
  } else {
    slipHtml = `<div class="slip-placeholder">
      <svg width="36" height="36" fill="none" stroke="#ccc" stroke-width="1.5">
        <rect x="4" y="2" width="16" height="20" rx="2"/>
        <line x1="7" y1="8" x2="17" y2="8"/>
        <line x1="7" y1="12" x2="14" y2="12"/>
      </svg>
      <span>${esc(i18n.modal_no_slip)}</span>
    </div>`;
  }

  const infoRows = [
    [i18n.modal_status,   statusBadge(status)],
    [i18n.modal_customer, esc(ord.username || '—')],
    ord.user_email ? [i18n.modal_email, esc(ord.user_email)] : null,
    [i18n.modal_delivery, esc(delLabel)],
    (ord.delivery === 'ship' && ord.address) ? [i18n.modal_address, `<span style="white-space:pre-wrap;">${esc(ord.address)}</span>`] : null,
  ].filter(Boolean).map(([lbl,val]) =>
    `<div class="info-label">${esc(lbl)}</div><div class="info-val">${val}</div>`
  ).join('');

  document.getElementById('modalBody').innerHTML = `
    <div class="row g-3 g-md-4">
      <div class="col-12 col-md-5">
        <div class="fw-bold mb-2" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:#666;">
          ${esc(i18n.modal_slip)}
        </div>
        <div class="slip-wrap mb-3">${slipHtml}</div>
        <div class="info-grid">${infoRows}</div>
      </div>
      <div class="col-12 col-md-7">
        <div class="fw-bold mb-2" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;color:#666;">
          ${esc(i18n.modal_items)}
        </div>
        <table class="items-table">
          <thead>
            <tr>
              <th style="width:55%;">${esc(i18n.modal_items)}</th>
              <th style="width:15%;text-align:center;">${esc(i18n.modal_qty)}</th>
              <th style="width:30%;text-align:right;">${esc(i18n.modal_line)}</th>
            </tr>
          </thead>
          <tbody>${itemRows || '<tr><td colspan="3" class="text-muted text-center">—</td></tr>'}</tbody>
        </table>
        <div class="totals-box">
          <div class="total-row"><span>${esc(i18n.modal_subtotal)}</span><span>${fmt(amounts.subtotal||0)} ฿</span></div>
          <div class="total-row"><span>${esc(i18n.modal_shipping)}</span><span>${fmt(amounts.shipping||0)} ฿</span></div>
          <div class="total-row grand"><span>${esc(i18n.modal_total)}</span><span>${fmt(amounts.total||0)} ฿</span></div>
        </div>
      </div>
    </div>`;

  let footer = `<button class="btn btn-secondary ms-auto" data-bs-dismiss="modal">${esc(i18n.modal_close)}</button>`;
  const oidSafe = esc(ord.order_id || '');
  if (status === 'awaiting_review') {
    footer = `<form method="post">
      <input type="hidden" name="action"   value="update_status">
      <input type="hidden" name="order_id" value="${oidSafe}">
      <input type="hidden" name="status"   value="approved">
      <button type="submit" class="act-btn act-approve" style="padding:9px 20px;font-size:.9rem;">${esc(i18n.btn_approve)}</button>
    </form>
    <form method="post">
      <input type="hidden" name="action"   value="update_status">
      <input type="hidden" name="order_id" value="${oidSafe}">
      <input type="hidden" name="status"   value="rejected">
      <button type="submit" class="act-btn act-reject" style="padding:9px 20px;font-size:.9rem;"
              onclick="return confirm(${JSON.stringify(i18n.confirm_reject)})">${esc(i18n.btn_reject)}</button>
    </form>` + footer;
  }
  document.getElementById('modalFooter').innerHTML = footer;

  if (!_modal) _modal = new bootstrap.Modal(document.getElementById('orderModal'));
  _modal.show();
}

// Filter tabs
let _activeFilter = 'all';
let _searchTerm   = '';

function applyFilters() {
  document.querySelectorAll('#ordersBody .order-row').forEach(row => {
    const matchFilter = _activeFilter === 'all' || row.dataset.status === _activeFilter;
    const matchSearch = _searchTerm === '' || (row.dataset.search || '').includes(_searchTerm);
    row.classList.toggle('hidden', !(matchFilter && matchSearch));
  });
  updateBulkBar();
}

document.querySelectorAll('.ftab').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.ftab').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    _activeFilter = this.dataset.filter;
    applyFilters();
  });
});

const searchInput = document.getElementById('searchInput');
if (searchInput) {
  searchInput.addEventListener('input', function() {
    _searchTerm = this.value.toLowerCase().trim();
    applyFilters();
  });
}

// Bulk selection
const selectAll = document.getElementById('selectAll');

function getVisibleCheckable() {
  return Array.from(document.querySelectorAll('.order-cb:not(#selectAll):not(:disabled)'))
    .filter(cb => !cb.closest('.order-row').classList.contains('hidden'));
}

function updateBulkBar() {
  const checked = document.querySelectorAll('.order-cb:not(#selectAll):checked');
  const bar     = document.getElementById('bulkBar');
  const countEl = document.getElementById('bulkCount');
  if (bar) bar.style.display = checked.length > 0 ? 'flex' : 'none';
  if (countEl) countEl.textContent = checked.length + ' <?= htmlspecialchars($t['bulk_selected']) ?>';
  // Sync select-all indeterminate state
  const vis = getVisibleCheckable();
  if (selectAll) {
    const checkedVis = vis.filter(cb => cb.checked);
    selectAll.checked       = vis.length > 0 && checkedVis.length === vis.length;
    selectAll.indeterminate = checkedVis.length > 0 && checkedVis.length < vis.length;
  }
}

if (selectAll) {
  selectAll.addEventListener('change', function() {
    getVisibleCheckable().forEach(cb => { cb.checked = this.checked; });
    updateBulkBar();
  });
}
document.querySelectorAll('.order-cb:not(#selectAll)').forEach(cb => {
  cb.addEventListener('change', updateBulkBar);
});

function submitBulk(status) {
  const checked = Array.from(document.querySelectorAll('.order-cb:not(#selectAll):checked'));
  if (checked.length === 0) return;
  const tpl = status === 'approved'
    ? <?= json_encode($t['confirm_bulk_approve']) ?>
    : <?= json_encode($t['confirm_bulk_reject']) ?>;
  if (!confirm(tpl.replace('{n}', checked.length))) return;
  const form      = document.getElementById('bulkForm');
  const statusInp = document.getElementById('bulkStatus');
  const container = document.getElementById('bulkIds');
  statusInp.value  = status;
  container.innerHTML = '';
  checked.forEach(cb => {
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'order_ids[]'; inp.value = cb.value;
    container.appendChild(inp);
  });
  form.submit();
}

document.getElementById('bulkClear')?.addEventListener('click', function() {
  document.querySelectorAll('.order-cb:not(#selectAll)').forEach(cb => { cb.checked = false; });
  if (selectAll) { selectAll.checked = false; selectAll.indeterminate = false; }
  updateBulkBar();
});

function confirmDeleteOrder(oid) {
  var input = prompt('Type "delete-order" to permanently delete this order:');
  if (input === null) return;
  if (input.trim() !== 'delete-order') {
    alert('Text did not match. Order not deleted.');
    return;
  }
  var form = document.createElement('form');
  form.method = 'post';
  form.innerHTML =
    '<input type="hidden" name="action" value="delete_order">' +
    '<input type="hidden" name="order_id" value="' + oid + '">';
  document.body.appendChild(form);
  form.submit();
}
</script>

<!-- Hidden form used by bulk JS to POST selected order IDs -->
<form id="bulkForm" method="post" style="display:none;">
  <input type="hidden" name="action" value="bulk_update">
  <input type="hidden" name="status" id="bulkStatus" value="">
  <div id="bulkIds"></div>
</form>
</body>
</html>
