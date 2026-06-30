<?php
// file: checkout.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'database.php'; // must define $conn and $lang

// require login like your cart
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// i18n
$texts = [
    'en' => [
        'tabbar'         => 'Checkout - Future X',
        'heading'        => 'Checkout',
        'order_summary'  => 'Order Summary',
        'items'          => 'Items',
        'qty'            => 'Qty',
        'price'          => 'Price',
        'subtotal'       => 'Subtotal',
        'shipping'       => 'Shipping Fee',
        'total'          => 'Total',
        'back_to_cart'   => 'Back to Cart',
        'place_order'    => 'Place Order',
        'delivery'       => 'Delivery Method',
        'pickup'              => 'Pick up at store',
        'ship'                => 'Ship to address',
        'address'             => 'Shipping Address',
        'payment'             => 'Payment Method',
        'pay_qr'              => 'PromptPay (QR)',
        'empty_cart'          => 'Your cart is empty.',
        'go_products'         => 'Go to Products',
        'success'             => 'Order created. Proceed to payment.',
        'missing'             => 'Please fill the required fields.',
        'missing_pickup_time' => 'Please select a pick-up date and time.',
        'lang'                => 'ภาษาไทย',
        'store_location'      => 'Store Location',
        'view_map'            => 'View on Google Maps',
        'pickup_appt'         => 'Pick-Up Appointment',
        'pickup_date_label'   => 'Date',
        'pickup_time_label'   => 'Time',
        'pickup_time_ph'      => '— Select a time —',
    ],
    'th' => [
        'tabbar'         => 'ชำระเงิน - Future X',
        'heading'        => 'ชำระเงิน',
        'order_summary'  => 'สรุปรายการสั่งซื้อ',
        'items'          => 'รายการ',
        'qty'            => 'จำนวน',
        'price'          => 'ราคา',
        'subtotal'       => 'ราคารวม',
        'shipping'       => 'ค่าส่ง',
        'total'          => 'สุทธิ',
        'back_to_cart'   => 'กลับไปตะกร้า',
        'place_order'    => 'สั่งซื้อ',
        'delivery'       => 'วิธีรับสินค้า',
        'pickup'              => 'รับที่ร้าน',
        'ship'                => 'จัดส่งถึงที่อยู่',
        'address'             => 'ที่อยู่สำหรับจัดส่ง',
        'payment'             => 'ช่องทางชำระเงิน',
        'pay_qr'              => 'พร้อมเพย์ (QR)',
        'empty_cart'          => 'ตะกร้าสินค้าว่าง',
        'go_products'         => 'ไปที่สินค้า',
        'success'             => 'สร้างคำสั่งซื้อแล้ว โปรดดำเนินการชำระเงิน',
        'missing'             => 'โปรดกรอกข้อมูลให้ครบ',
        'missing_pickup_time' => 'โปรดเลือกวันและเวลานัดรับสินค้า',
        'lang'                => 'English',
        'store_location'      => 'ที่ตั้งร้าน',
        'view_map'            => 'ดูบน Google Maps',
        'pickup_appt'         => 'นัดรับสินค้า',
        'pickup_date_label'   => 'วันที่',
        'pickup_time_label'   => 'เวลา',
        'pickup_time_ph'      => '— เลือกเวลา —',
    ],
];

// cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$items = $_SESSION['cart'];

$subtotal = 0.0;
foreach ($items as $it) {
    $qty   = (int)($it['qty'] ?? 1);
    $price = (float)($it['price'] ?? 0);
    $subtotal += $qty * $price;
}
// base fee you charge for shipping
$shipping_base = 50.00;

// default view (before submit) is pickup, so no shipping fee
$preselected_delivery = 'pickup';
$shipping_fee = ($preselected_delivery === 'ship') ? $shipping_base : 0.00;
$total = $subtotal + $shipping_fee;

$errors = [];
$success = "";

// submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($items)) $errors[] = $texts[$lang]['empty_cart'];

    $delivery = $_POST['delivery'] ?? 'pickup';
    $method   = 'qr'; // only PromptPay
    $address  = trim($_POST['address'] ?? '');
    $shipping_fee = ($delivery === 'ship') ? $shipping_base : 0.00;
    $total = $subtotal + $shipping_fee;

    // Require address only when shipping
    if ($delivery === 'ship' && $address === '') $errors[] = $texts[$lang]['missing'];

    // Capture and validate pickup appointment
    $pickup_time = '';
    if ($delivery === 'pickup') {
        $pickup_date = trim($_POST['pickup_date'] ?? '');
        $pickup_hour = trim($_POST['pickup_hour'] ?? '');
        if ($pickup_date === '' || $pickup_hour === '') {
            $errors[] = $texts[$lang]['missing_pickup_time'];
        } else {
            try {
                $dt = new DateTime($pickup_date);
                $pickup_time = $dt->format('j M Y') . ', ' . $pickup_hour;
            } catch (Exception $e) {
                $pickup_time = $pickup_date . ' ' . $pickup_hour;
            }
        }
    }

    if (!$errors) {
        $order_id = 'FX'.date('YmdHis').rand(100,999);

        $_SESSION['pending_checkout'] = [
            'order_id'       => $order_id,
            'user_id'        => (int)$_SESSION['user_id'],
            'delivery'       => $delivery,
            'address'        => ($delivery === 'ship') ? $address : '',
            'pickup_time'    => $pickup_time,
            'payment_method' => $method,
            'items'          => $items,
            'amounts'        => ['subtotal'=>$subtotal, 'shipping'=>$shipping_fee, 'total'=>$total],
            'created_at'     => date('c'),
        ];
        header("Location: promptpay.php");
		exit;
        // next step: create PromptPay QR, then redirect there
        // header("Location: pay_promptpay.php?order=".$order_id); exit;

        $success = $texts[$lang]['success'];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($texts[$lang]['tabbar']); ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Inter + Bootstrap -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: #111;
            padding: 60px 20px 40px;
        }
        @media (max-width: 460px) { body { padding: 80px 20px 40px; } }
        @supports (height: 100dvh) { body { min-height: 100dvh; } }

        .form-container {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 28px 24px;
            width: 100%;
            max-width: 980px;
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            position: relative;
        }
        .form-container h2 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 12px;
            text-align: center;
        }

        .form-control, textarea {
            border-radius: 12px;
            padding: 12px;
            font-size: 1rem;
        }
        .section-title { font-weight: 700; margin-bottom: 10px; }

        .btn-modern {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; margin-top: 12px; padding: 14px; font-size: 1.1rem; font-weight: 600;
            border-radius: 14px; transition: all .3s ease;
        }
        .btn-modern.btn-primary {
            background: linear-gradient(135deg, #007BFF, #0056b3);
            border: none; color: #fff;
        }
        .btn-modern.btn-primary:hover {
            background: linear-gradient(135deg, #0056b3, #003f7f);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,.35);
        }

        /* Summary card */
        .summary-card {
            background: rgba(233, 242, 255, 0.9);
            border-radius: 16px;
            padding: 16px;
        }
        .summary-head {
            display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;
            font-weight:700;
        }
        .item-table { width:100%; border-collapse: collapse; }
        .item-table th, .item-table td { padding: 8px 6px; }
        .item-table thead th { font-weight:600; color:#333; border-bottom:1px solid rgba(0,0,0,.1); }
        .item-table tbody tr + tr td { border-top: 1px dashed rgba(0,0,0,.08); }
        .col-name { width:60%; }
        .col-qty  { width:15%; text-align:center; }
        .col-price{ width:25%; text-align:right; white-space:nowrap; min-width:90px; }

        .summary-totals { margin-top:10px; font-size: .98rem; }
        .summary-row { display:flex; justify-content:space-between; padding:6px 0; }
        .summary-row.total { border-top:1px solid rgba(0,0,0,.1); margin-top:6px; padding-top:10px; font-weight:700; font-size:1.15rem; }

        /* Pickup info box */
        .pickup-box {
            background: rgba(233,242,255,0.7);
            border: 1.5px solid rgba(0,123,255,0.18);
            border-radius: 14px;
            padding: 14px 16px;
            margin-top: 12px;
        }
        .pickup-box .store-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .pickup-box .store-name {
            font-weight: 600;
            font-size: 0.92rem;
            color: #111;
            margin-bottom: 2px;
        }
        .pickup-box .map-link {
            font-size: 0.82rem;
            color: #007BFF;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }
        .pickup-box .map-link:hover { text-decoration: underline; }
        .pickup-appt-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .pickup-appt-row .appt-field { flex: 1; min-width: 130px; }
        .pickup-appt-row label { font-size: 0.82rem; font-weight: 500; color: #555; margin-bottom: 4px; }

        .lang-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.7);
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
	    	color: #007BFF;
        }
        .lang-switch:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

<!-- Language switch -->
<a class="lang-switch" href="?lang=<?php echo $lang === 'en' ? 'th' : 'en'; ?>">
    <?php echo $texts[$lang]['lang']; ?>
</a>

<div class="form-container">
    <h2><?php echo htmlspecialchars($texts[$lang]['heading']); ?></h2>

    <?php if (empty($items)): ?>
        <div class="text-center py-3">
            <p class="mb-3"><?php echo htmlspecialchars($texts[$lang]['empty_cart']); ?></p>
            <a href="products.php" class="btn btn-modern btn-primary" style="max-width:320px;margin:0 auto;">
                <?php echo htmlspecialchars($texts[$lang]['go_products']); ?>
            </a>
        </div>
    <?php else: ?>
    <form method="post" id="checkoutForm">
        <div class="row g-3">
            <!-- Left: Delivery + Address + Payment -->
            <div class="col-12 col-lg-7">
                <div class="mb-3">
                    <div class="section-title"><?php echo htmlspecialchars($texts[$lang]['delivery']); ?></div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="delivery" id="del_pickup" value="pickup" checked>
                        <label class="form-check-label" for="del_pickup"><?php echo htmlspecialchars($texts[$lang]['pickup']); ?></label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="delivery" id="del_ship" value="ship">
                        <label class="form-check-label" for="del_ship"><?php echo htmlspecialchars($texts[$lang]['ship']); ?></label>
                    </div>
                </div>

                <!-- Pick-up: store location + appointment time -->
                <div id="pickupWrap" class="mb-4">
                  <div class="pickup-box">
                    <div class="store-row">
                      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#007BFF" stroke-width="1.8" flex-shrink="0" style="flex-shrink:0;">
                        <path d="M21 10c0 7-9 13-9 13S3 17 3 10a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                      </svg>
                      <div>
                        <div class="store-name">Future X Korat</div>
                        <a class="map-link" href="https://maps.app.goo.gl/q2r3e8apCCvh5XAs6" target="_blank" rel="noopener">
                          <?= htmlspecialchars($texts[$lang]['view_map']) ?>
                          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </a>
                      </div>
                    </div>
                    <div style="font-size:.82rem;font-weight:600;color:#333;margin-bottom:8px;">
                      <?= htmlspecialchars($texts[$lang]['pickup_appt']) ?> *
                    </div>
                    <div class="pickup-appt-row">
                      <div class="appt-field">
                        <label for="pickup_date"><?= htmlspecialchars($texts[$lang]['pickup_date_label']) ?></label>
                        <input type="date" id="pickup_date" name="pickup_date" class="form-control"
                               min="<?= date('Y-m-d') ?>" style="border-radius:10px;">
                      </div>
                      <div class="appt-field">
                        <label for="pickup_hour"><?= htmlspecialchars($texts[$lang]['pickup_time_label']) ?></label>
                        <?php
                        // value = English AM/PM (stored in DB); label changes by language
                        $timeSlots = [
                            '10:00 AM' => ['en' => '10:00 AM', 'th' => '10 น.'],
                            '11:00 AM' => ['en' => '11:00 AM', 'th' => '11 น.'],
                            '12:00 PM' => ['en' => '12:00 PM', 'th' => '12 น.'],
                            '1:00 PM'  => ['en' => '1:00 PM',  'th' => '13 น.'],
                            '2:00 PM'  => ['en' => '2:00 PM',  'th' => '14 น.'],
                            '3:00 PM'  => ['en' => '3:00 PM',  'th' => '15 น.'],
                            '4:00 PM'  => ['en' => '4:00 PM',  'th' => '16 น.'],
                            '5:00 PM'  => ['en' => '5:00 PM',  'th' => '17 น.'],
                        ];
                        ?>
                        <select id="pickup_hour" name="pickup_hour" class="form-control" style="border-radius:10px;">
                          <option value=""><?= htmlspecialchars($texts[$lang]['pickup_time_ph']) ?></option>
                          <?php foreach ($timeSlots as $val => $labels): ?>
                          <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($labels[$lang] ?? $labels['en']) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div id="addressWrap" class="mb-4" style="display:none;">
                    <label class="form-label"><?php echo htmlspecialchars($texts[$lang]['address']); ?> *</label>
                    <textarea name="address" id="address" class="form-control" rows="4" placeholder="<?php echo ($lang==='en' ? 'Full shipping address' : 'กรอกที่อยู่สำหรับจัดส่ง'); ?>"></textarea>
                </div>

                <div class="mb-2 section-title"><?php echo htmlspecialchars($texts[$lang]['payment']); ?></div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="pay_qr" value="qr" checked>
                    <label class="form-check-label" for="pay_qr"><?php echo htmlspecialchars($texts[$lang]['pay_qr']); ?></label>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <a href="cart.php" class="text-decoration-none"><?php echo htmlspecialchars($texts[$lang]['back_to_cart']); ?></a>
                    <button type="submit" id="placeBtn" class="btn btn-modern btn-primary" style="max-width: 260px;">
                        <?php echo htmlspecialchars($texts[$lang]['place_order']); ?>
                    </button>
                </div>
            </div>

            <!-- Right: Summary -->
            <div class="col-12 col-lg-5">
                <div class="section-title"><?php echo htmlspecialchars($texts[$lang]['order_summary']); ?></div>
                <div class="summary-card">
                    <div class="summary-head">
                        <div><?php echo htmlspecialchars($texts[$lang]['items']); ?></div>
                        <div><?php echo number_format(count($items)); ?></div>
                    </div>

                    <table class="item-table">
                        <thead>
                            <tr>
                                <th class="col-name"><?php echo htmlspecialchars($texts[$lang]['items']); ?></th>
                                <th class="col-qty"><?php echo htmlspecialchars($texts[$lang]['qty']); ?></th>
                                <th class="col-price"><?php echo htmlspecialchars($texts[$lang]['price']); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $it):
                            $n = (string)($it['name'] ?? '');
                            $q = (int)($it['qty'] ?? 1);
                            $p = (float)($it['price'] ?? 0);
                            $ln = $q * $p;
                        ?>
                            <tr>
                                <td class="col-name"><?php echo htmlspecialchars($n); ?></td>
                                <td class="col-qty">× <?php echo number_format($q); ?></td>
                                <td class="col-price"><?php echo number_format($ln, 2); ?> ฿</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="summary-totals">
                        <div class="summary-row">
                            <div><?php echo htmlspecialchars($texts[$lang]['subtotal']); ?></div>
                            <div id="subtotalVal"><?php echo number_format($subtotal, 2); ?> ฿</div>
                        </div>
                        <div class="summary-row">
                            <div><?php echo htmlspecialchars($texts[$lang]['shipping']); ?></div>
                            <div id="shippingVal"><?php echo number_format($shipping_fee, 2); ?> ฿</div>
                        </div>
                        <div class="summary-row total">
                            <div><?php echo htmlspecialchars($texts[$lang]['total']); ?></div>
                            <div id="totalVal"><?php echo number_format($total, 2); ?> ฿</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Toasts -->
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1080">
  <?php if (!empty($success)): ?>
    <div class="toast align-items-center text-bg-success border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
      <div class="d-flex">
        <div class="toast-body"><?php echo htmlspecialchars($success); ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <?php foreach ($errors as $e): ?>
      <div class="toast align-items-center text-bg-danger border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="7000">
        <div class="d-flex">
          <div class="toast-body"><?php echo htmlspecialchars($e); ?></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // show toasts
  document.querySelectorAll('.toast').forEach(function(toastEl){
    new bootstrap.Toast(toastEl).show();
  });

  // toggle address / pickup sections by delivery
  const pickup      = document.getElementById('del_pickup');
  const ship        = document.getElementById('del_ship');
  const pickupWrap  = document.getElementById('pickupWrap');
  const pickupDate  = document.getElementById('pickup_date');
  const pickupHour  = document.getElementById('pickup_hour');
  const wrap        = document.getElementById('addressWrap');
  const addr        = document.getElementById('address');

  const shippingValEl = document.getElementById('shippingVal');
  const totalValEl    = document.getElementById('totalVal');
  const subtotalNum   = <?php echo json_encode($subtotal); ?>;
  const shipFeeNum    = <?php echo json_encode($shipping_base); ?>;

  function fmt(n){
    return Number(n).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }

  function refreshTotals() {
    const fee = ship.checked ? shipFeeNum : 0;
    if (shippingValEl) shippingValEl.textContent = fmt(fee) + ' ฿';
    if (totalValEl)    totalValEl.textContent    = fmt(subtotalNum + fee) + ' ฿';
  }

  function refreshDelivery() {
    const isShip   = ship.checked;
    const isPickup = pickup.checked;

    // Address section
    wrap.style.display = isShip ? '' : 'none';
    addr.required = isShip;
    if (!isShip) addr.value = '';

    // Pickup appointment section
    if (pickupWrap) pickupWrap.style.display = isPickup ? '' : 'none';
    if (pickupDate) pickupDate.required = isPickup;
    if (pickupHour) pickupHour.required = isPickup;

    refreshTotals();
  }
  pickup.addEventListener('change', refreshDelivery);
  ship.addEventListener('change', refreshDelivery);
  refreshDelivery();

  // disable button on submit
  const form = document.getElementById('checkoutForm');
  const btn  = document.getElementById('placeBtn');
  if (form && btn) {
    form.addEventListener('submit', function(){
      btn.disabled = true;
      btn.innerHTML = `<?php echo htmlspecialchars($texts[$lang]['place_order']); ?> <span class="spinner-border spinner-border-sm ms-2 text-light" role="status"></span>`;
    });
  }
});
</script>

</body>
</html>