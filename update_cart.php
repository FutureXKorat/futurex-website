<?php
// update_cart.php — sets/removes qty in cart with stock capping; returns JSON (no redirects)
session_start();
include 'database.php'; // add near the top (after session_start)
header('Content-Type: application/json; charset=UTF-8');

// Require login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'not_logged_in']);
    exit();
}

// Require POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed']);
    exit();
}

$name  = isset($_POST['name'])  ? trim($_POST['name'])  : '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;
$qty   = isset($_POST['qty'])   ? (int)$_POST['qty']     : 1;

if ($name === '' || $price <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'invalid_input']);
    exit();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Find the item
$idx = -1;
foreach ($_SESSION['cart'] as $i => $it) {
    $itName  = isset($it['name'])  ? $it['name']  : '';
    $itPrice = isset($it['price']) ? (float)$it['price'] : 0;
    if ($itName === $name && $itPrice == $price) {
        $idx = $i; break;
    }
}
if ($idx === -1) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'not_found']);
    exit();
}


$stmt = $conn->prepare("
    SELECT stock, price
    FROM products
    WHERE name = ?
      AND active = 1
      AND ABS(price - ?) < 0.005
    LIMIT 1
");
$stmt->bind_param("sd", $name, $price);
$stmt->execute();
$stmt->bind_result($base, $dbPrice);
$found = $stmt->fetch();
$stmt->close();
if (!$found) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'product_not_found']); exit(); }

// Remove?
if ($qty <= 0) {
    unset($_SESSION['cart'][$idx]);
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    // Recompute totals
    $cartTotal = 0.0; $cartCount = 0;
    foreach ($_SESSION['cart'] as $it) {
        $q = isset($it['qty']) ? (int)$it['qty'] : 1;
        $p = isset($it['price']) ? (float)$it['price'] : 0.0;
        $cartTotal += $q * $p; $cartCount += $q;
    }
    echo json_encode([
        'ok'        => true,
        'removed'   => true,
        'empty'     => empty($_SESSION['cart']),
        'itemQty'   => 0,
        'lineTotal' => number_format(0, 2, '.', ''),
        'cartTotal' => number_format($cartTotal, 2, '.', ''),
        'cartCount' => $cartCount,
        'capped'    => false,
        'stockLeft' => $base
    ]);
    exit();
}

// Cap to base stock
$qty = max(1, (int)$qty);
$capped = false;
if ($qty > $base) { $qty = $base; $capped = true; }
$_SESSION['cart'][$idx]['qty'] = $qty;

// Recompute totals
$cartTotal = 0.0; $cartCount = 0;
foreach ($_SESSION['cart'] as $it) {
    $q = isset($it['qty']) ? (int)$it['qty'] : 1;
    $p = isset($it['price']) ? (float)$it['price'] : 0.0;
    $cartTotal += $q * $p; $cartCount += $q;
}

// Line total
$lineTotal = $qty * (float)$dbPrice;

echo json_encode([
    'ok'        => true,
    'removed'   => false,
    'empty'     => empty($_SESSION['cart']),
    'itemQty'   => $qty,
    'lineTotal' => number_format($lineTotal, 2, '.', ''),
    'cartTotal' => number_format($cartTotal, 2, '.', ''),
    'cartCount' => $cartCount,
    'capped'    => $capped,
    'stockLeft' => max(0, $base - $qty),
    'baseStock' => $base
]);
