<?php
// add_to_cart.php — adds item(s) to session cart with stock capping; returns JSON (no redirects)
session_start();
include 'database.php';
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

if ($qty < 1) $qty = 1;

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Current qty in cart for this (name+price)
$current = 0;
$index   = -1;
foreach ($_SESSION['cart'] as $i => $it) {
    $itName  = isset($it['name'])  ? $it['name']  : '';
    $itPrice = isset($it['price']) ? (float)$it['price'] : 0;
    if ($itName === $name && $itPrice == $price) {
        $current += isset($it['qty']) ? (int)$it['qty'] : 1;
        $index = $i;
        break;
    }
}

// authoritative stock from DB (tolerate float rounding on price)
$stmt = $conn->prepare("
    SELECT stock
    FROM products
    WHERE name = ?
      AND active = 1
      AND ABS(price - ?) < 0.005
    LIMIT 1
");
$stmt->bind_param("sd", $name, $price);
$stmt->execute();
$stmt->bind_result($base);
$found = $stmt->fetch();
$stmt->close();
if (!$found) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'product_not_found']);
    exit();
}
$base = (int)$base;

$remaining = max(0, $base - $current);

// Cap to remaining
$toAdd = min($qty, $remaining);

// Apply
if ($toAdd > 0) {
    if ($index >= 0) {
        $_SESSION['cart'][$index]['qty'] = $current + $toAdd;
    } else {
        $_SESSION['cart'][] = ['name' => $name, 'price' => $price, 'qty' => $toAdd];
    }
    $_SESSION['cart_seen'] = false; // light the red dot
    $newQty = $current + $toAdd;
} else {
    // Nothing to add (already at max)
    $newQty = $current;
}

// Totals
$cartCount = 0;
foreach ($_SESSION['cart'] as $x) {
    $cartCount += isset($x['qty']) ? (int)$x['qty'] : 1;
}

echo json_encode([
    'ok'        => true,
    'added'     => $toAdd,
    'capped'    => ($toAdd < $qty),          // true if we capped
    'itemQty'   => $newQty,                  // new qty of this item in cart
    'stockLeft' => max(0, $base - $newQty),  // remaining stock after add
    'baseStock' => $base,
    'cartCount' => $cartCount
]);
