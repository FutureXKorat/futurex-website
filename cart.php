<?php
include 'database.php';

$texts = [
    'en' => [
    'title'          => 'Shopping Cart - Future X',
    'titlein'        => 'Products',
    'home'           => 'Home',
    'cart'           => 'Shopping Cart',
    'product'        => 'Products',
    'btnproduct'     => 'View Products',
    'about'          => 'About Us',
    'Source'         => 'Sources',
    'out'            => 'Log Out',
    'profile'        => 'Edit Profile',
    'login'          => 'Please login to access your profile.',
    'qty'            => 'Qty',
    'orders'         => 'Orders',
    'lang'           => 'ภาษาไทย'
    ],

    'th' => [
    'title'          => 'ตะกร้าสินค้า - Future X',
    'titlein'        => 'สินค้า',
    'home'           => 'หน้าหลัก',
    'product'        => 'สินค้า',
    'btnproduct'     => 'ดูสินค้า',
    'cart'           => 'ตะกร้าสินค้า',
    'about'          => 'เกี่ยวกับพวกเรา',
    'Source'         => 'แหล่งที่มา',
    'profile'        => 'แก้ไขโปรไฟล์',
    'login'          => 'กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์',
    'out'            => 'ออกจากระบบ',
    'qty'            => 'จำนวน',
    'orders'          => 'สินค้าที่สั่ง',
    'lang'           => 'English'
    ],
];

// Require login to view cart
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}


// Session cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$items = $_SESSION['cart'];

// Legacy remove via GET
if (isset($_GET['idu'])) {
    $i = (int)$_GET['idu'];
    if (isset($items[$i])) {
        unset($items[$i]);
        $items = array_values($items);
        $_SESSION['cart'] = $items;
    }
}

// Compute total
$total = 0.0;
foreach ($items as $it) {
    $qty = isset($it['qty']) ? (int)$it['qty'] : 1;
    $price = isset($it['price']) ? (float)$it['price'] : 0.0;
    $total += $qty * $price;
}

// Mark seen so red dot disappears
$_SESSION['cart_seen'] = true;
$hasUnseen = false;
?>
<?php
// Inline AJAX: update/remove by (name, price) — no new file needed
if (
$_SERVER['REQUEST_METHOD'] === 'POST' &&
isset($_POST['ajax']) && $_POST['ajax'] === '1' &&
isset($_POST['name'], $_POST['price'], $_POST['qty'])
) {
header('Content-Type: application/json; charset=UTF-8');


if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) { $_SESSION['cart'] = []; }
$items =& $_SESSION['cart'];


$name = (string)$_POST['name'];
$price = (float)$_POST['price'];
$qtyIn = (int)$_POST['qty'];


// find by (name, price)
$idx = null;
foreach ($items as $i => $it) {
if ((string)($it['name'] ?? '') === $name && (float)($it['price'] ?? 0) == $price) { $idx = $i; break; }
}
if ($idx === null) { echo json_encode(['ok'=>false,'error'=>'item_not_found']); exit; }


// delete when qty <= 0
if ($qtyIn <= 0) {
unset($items[$idx]);
$items = array_values($items);
$cartTotal = 0.0; foreach ($items as $x){ $cartTotal += ((int)($x['qty']??1))*((float)($x['price']??0)); }
echo json_encode(['ok'=>true,'removed'=>true,'empty'=>empty($items),'cartTotal'=>$cartTotal]); exit;
}


// clamp to stock if known; unknown => unlimited
if (!function_exists('base_stock')) {
    function base_stock($name, $price) { return 0; } // 0 = unknown, treated as unlimited
}
$stock = base_stock($name, $price);
$max = $stock > 0 ? $stock : PHP_INT_MAX; // why: avoid locking when unknown
$qty = max(1, min($qtyIn, $max));


$items[$idx]['qty'] = $qty;


$lineTotal = $qty * (float)$items[$idx]['price'];
$cartTotal = 0.0; foreach ($items as $x){ $cartTotal += ((int)($x['qty']??1))*((float)($x['price']??0)); }


echo json_encode([
'ok' => true,
'qty' => $qty,
'lineTotal' => $lineTotal,
'cartTotal' => $cartTotal,
'note' => ($stock>0 && $qtyIn>$max) ? "Adjusted to max {$max}" : ''
]);
exit; // important so the HTML below is not output for AJAX
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo ($texts[$lang]['title']) ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --brand-color:#007BFF;
            --brand-hover:#0056b3;
	    	--brand-hover-deep:#000099;
            --gray-color: #ccc;
            --ink: #111111;           /* darker text for white backgrounds */
        }
        body { 
            margin:0; 
            font-family:'Inter',sans-serif;
            min-height:100vh; 
            background:linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF); 
            color:#1F2937; 
            padding:40px 20px; 
        }

        .top-banner {
            background-color: var(--brand-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            position: fixed;
  			top:0; left:0; right:0; /* ensures full width and flush to top */
  			height:60px;
  			z-index:1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .content-section{ margin-top:80px; } /* 60px bar + some breathing room */
        .top-banner.scrolled {
            background-color: var(--brand-color); /* keep red when scrolled */
            box-shadow: none;
        }
        .nav-links-container {
            flex: 1;
            overflow-x: auto;
            position: relative;
            padding: 12px 20px;
        }
        .nav-links {
            display: flex;
            gap: 12px;
            white-space: nowrap;
        }
        .nav-links::-webkit-scrollbar { display: none; }
        .nav-scroll-indicator {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: #fff; /* white line for contrast on red */
            border-radius: 2px;
            width: 0%;
            transition: width 0.2s linear;
        }
        .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: 500;
            padding: 8px 12px;
            border-radius: 4px;
            flex-shrink: 0;
            transition: background 0.3s, transform 0.15s ease, opacity 0.15s ease;
        }
        .top-banner.scrolled .nav-links a { color: #fff; }
        .nav-links a:hover { background-color: rgba(255,255,255,0.15); transform: translateY(-1px); }
        /* Language dropdown container */
        .lang-dropdown{
                position: relative;
                flex-shrink: 0;
        }

        /* Round icon button (glassy like your theme) */
        .lang-btn-icon{
                width: 42px;
                height: 42px;
                display: grid;
                place-items: center;
                border: 1px solid rgba(255,255,255,0.35);
                background: rgba(255,255,255,0.18);
                color: #fff;
                border-radius: 50%;
                cursor: pointer;
                transition: transform .15s ease, background .2s ease, opacity .15s ease;
        }
        .lang-btn-icon:hover{ 
                background: rgba(255,255,255,0.28); 
                transform: translateY(-1px); 
        }
        .lang-btn-icon:focus{ 
                outline: 2px solid rgba(255,255,255,0.6); 
                outline-offset: 2px; 
        }

        /* Dropdown panel (same look as profile) */
        .lang-dropdown-content{
                display: none;
                position: absolute;
                right: 0;
                top: calc(100% + 8px);
                background-color: #fff;
                min-width: 190px;
                box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
                border-radius: 8px;
                overflow: hidden;
                padding: 0;
        }

        .lang-dropdown-content a{
                display:block;
                color:#333;
                padding:12px 16px;
                text-decoration:none;
                transition: background .2s ease;
                white-space: nowrap;
        }
        .lang-dropdown-content a:hover{ 
                background:#f2f2f2; 
        }
        .lang-dropdown-content a.active{
                font-weight:700;
                background:#f7f7f7;
        }
        .cart-dot{ position:absolute; top:4px; right:-6px; width:10px; height:10px; background:#ef4444; border-radius:50%; display:none; box-shadow:0 0 0 2px rgba(255,255,255,.9); }
        .cart-dot.show{ display:inline-block; }

        .content-section{ margin-top:60px; max-width:800px; margin-left:auto; margin-right:auto; }
        .cart-row{ display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #000000; font-size:1.1rem; }
        .cart-left{ display:flex; flex-direction:column; gap:6px; }
        .qty-edit{ font-size:.95rem; display:flex; align-items:center; gap:8px; }
        .qty-edit input[type="number"]{ width:90px; text-align:center; border-radius:10px; border:1px solid #d1d5db; padding:6px 8px; font-weight:600; }
        .cap-note{ font-size:.85rem; color:#6b7280; min-height:1.2em; }
        .cart-row a.delete{ color:var(--brand-color); text-decoration:none; margin-left:8px; font-size:1.2rem; }
        .line-total{ min-width:100px; display:inline-block; text-align:right; }
        .total-line{ text-align:right; font-weight:600; font-size:1.3rem; margin-top:20px; }

        .btn-purchase{
            display:inline-block;
            background:linear-gradient(135deg,var(--brand-color),var(--brand-hover));
            color:#fff; padding:12px 24px; font-size:1.2rem; font-weight:700;
            border-radius:20px; text-decoration:none; float:right; margin-top:10px;
            transition:transform .15s ease, box-shadow .15s ease, filter .15s ease;
            margin-bottom: 20px; /* add spacing from page bottom */
        }
        .btn-purchase:hover{
            transform:translateY(-2px);
            background:linear-gradient(135deg,var(--brand-hover),var(--brand-hover-deep));
            box-shadow:0 8px 18px rgba(0,0,204,.25);
            text-decoration:none;
        }
	.lang-btn{
  	    display:inline-block;
  	    padding:8px 12px;
  	    border-radius:10px;
  	    font-weight:600;
  	    text-decoration:none;
  	    color:#fff;
  	    background: rgba(255,255,255,0.18);
  	    border:1px solid rgba(255,255,255,0.3);
  	    backdrop-filter: blur(4px);
  	    transition: transform .15s ease, background .2s ease, opacity .15s ease;
	}
	.lang-btn:hover{
  	    background: rgba(255,255,255,0.28);
  	    transform: translateY(-1px);
	}


        /* Empty state styled like a row */
        .cart-empty{ justify-content:center; }
        .cart-empty .cart-left{ text-align:center; gap:10px; }
        .btn-browse{
            display:inline-block;
            background:linear-gradient(135deg,var(--brand-color),var(--brand-hover));
            color:#fff; padding:10px 16px; font-weight:600; border-radius:14px;
            text-decoration:none; transition:transform .15s ease, box-shadow .15s ease;
        }
        .btn-browse:hover{
            transform:translateY(-2px);
            background:linear-gradient(135deg,var(--brand-hover),var(--brand-hover-deep));
            box-shadow:0 6px 16px rgba(0,0,204,.25);
            text-decoration:none;
        }
    </style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>


    <div class="content-section" id="cartContent">
        <?php if (empty($items)): ?>
            <!-- Empty state row -->
            <div class="cart-row cart-empty">
                <div class="cart-left">
                    <div><strong><?php echo ($lang === 'en') ? 'Browse here for items' : 'ดูสินค้าที่นี่'; ?></strong></div>
                    <a class="btn-browse" href="products.php"><?php echo ($lang === 'en') ? 'Go to Products' : 'ไปที่สินค้า'; ?></a>
                </div>
                <div></div>
            </div>
        <?php else: ?>
            <?php foreach ($items as $idx => $item): ?>
                <?php
                    $name  = isset($item['name']) ? $item['name'] : '';
                    $price = isset($item['price']) ? (float)$item['price'] : 0.0;
                    $qty   = isset($item['qty']) ? (int)$item['qty'] : 1;
                    $line  = $qty * $price;
                    $base = 0;
					$stmt = $conn->prepare("SELECT stock FROM products WHERE name = ? AND active = 1 AND ABS(price - ?) < 0.005 LIMIT 1");
					$stmt->bind_param("sd", $name, $price);
					$stmt->execute();
					$stmt->bind_result($base);
					$stmt->fetch();
					$stmt->close();
					$base = (int)$base;
					$maxAttr = ($base > 0) ? ' max="'. $base .'"' : '';

				?>
                <div class="cart-row" data-index="<?php echo $idx; ?>">
                    <div class="cart-left">
                        <div>
                            <?php echo htmlspecialchars($name); ?>
                            × <span class="qty-x" id="qtyx-<?php echo $idx; ?>"><?php echo $qty; ?></span>
                        </div>
                        <div class="qty-edit">
                            <label for="qty-<?php echo $idx; ?>" class="me-1"><?php echo ($texts[$lang]['qty']); ?></label>
                            <input
                                id="qty-<?php echo $idx; ?>"
                                class="qty-input"
                                type="number"
                                min="1"<?php echo $maxAttr; ?>
                                step="1"
                                value="<?php echo $qty; ?>"
                                data-name="<?php echo htmlspecialchars($name); ?>"
                                data-price="<?php echo htmlspecialchars($price); ?>"
                            >
                        </div>
                        <div class="cap-note" id="note-<?php echo $idx; ?>"></div>
                    </div>
                    <div>
                        <span class="line-total" id="linetotal-<?php echo $idx; ?>">
                            <?php echo number_format($line, 2); ?>
                        </span> ฿
                        <a href="cart.php?idu=<?php echo $idx; ?>" class="delete"
                           data-name="<?php echo htmlspecialchars($name); ?>"
                           data-price="<?php echo htmlspecialchars($price); ?>"
                           title="Remove">✕</a>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="total-line">
                <?php echo ($lang === 'en') ? 'Total:' : 'สุทธิ:'; ?> <span id="grandTotal"><?php echo number_format($total, 2); ?></span> ฿
            </div>
            <a href="checkout.php" class="btn-purchase"><?php echo ($lang === 'en') ? 'Purchase' : 'สั่งซื้อ'; ?></a>
        <?php endif; ?>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// force comma thousands, 2 decimals
function fmt(n){
  const num = Number(n);
  if (!Number.isFinite(num)) return '0.00';
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function setGrandTotal(val){
  const gt=document.getElementById('grandTotal');
  if(gt) gt.textContent = fmt(val);
}
</script>

</script>
<script>
        // Delete via AJAX (falls back to GET if fails)
        document.querySelectorAll('.delete').forEach(a => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const name  = a.dataset.name;
                const price = a.dataset.price;

                fetch('cart.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: new URLSearchParams({ name, price, qty: 0, ajax: '1' })
				})
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) return;
                    const row = a.closest('.cart-row');
                    if (row) row.remove();
                    if (data.empty) {
                        const c = document.getElementById('cartContent');
                        c.innerHTML = `
                            <div class="cart-row cart-empty">
                                <div class="cart-left">
                                    <div><strong><?php echo ($lang === 'en') ? 'Browse here for items' : 'ดูสินค้าที่นี่';?></strong></div>
                                    <a class="btn-browse" href="products.php"><?php echo ($lang === 'en') ? 'Go to Products' : 'ไปที่รายการสินค้า';?></a>
                                </div>
                                <div></div>
                            </div>
                        `;
                    } else {
                        setGrandTotal(data.cartTotal);
                    }
                })
                .catch(() => { window.location.href = a.getAttribute('href'); });
            });
        });
    </script>
 <script>
(function(){


document.querySelectorAll('.qty-input').forEach(inp => {
const row = inp.closest('.cart-row'); if (!row) return;
const idx = row.dataset.index;
const name = inp.dataset.name;
const price = inp.dataset.price;


const qtyx = document.getElementById('qtyx-'+idx);
const line = document.getElementById('linetotal-'+idx);
const note = document.getElementById('note-'+idx);
const grand = document.getElementById('grandTotal');


const commit = (q) => fetch('cart.php', {
method: 'POST',
headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
body: new URLSearchParams({ name, price, qty: q, ajax: '1' })
}).then(r=>r.json()).then(d=>{
if (!d || !d.ok) return;
if (d.removed) {
row.remove();
if (d.empty) {
const c = document.getElementById('cartContent');
c.innerHTML = `
<div class="cart-row cart-empty">
<div class="cart-left">
<div><strong><?php echo ($lang === 'en') ? 'Browse here for items' : 'ดูสินค้าที่นี่'; ?></strong></div>
<a class="btn-browse" href="products.php"><?php echo ($lang === 'en') ? 'Go to Products' : 'ไปที่สินค้า'; ?></a>
</div>
<div></div>
</div>`;
}
return;
}
if (typeof d.qty!=='undefined') { inp.value=d.qty; if (qtyx) qtyx.textContent=d.qty; }
if (typeof d.lineTotal!=='undefined' && line) line.textContent = fmt(d.lineTotal);
if (typeof d.cartTotal!=='undefined' && grand) grand.textContent = fmt(d.cartTotal);
if (note) note.textContent = d.note || '';
});


inp.addEventListener('change', () => {
let q = parseInt(inp.value, 10);
const htmlMax = parseInt(inp.max || '0', 10);
if (!Number.isFinite(q) || q < 1) q = 1;
if (htmlMax > 0 && q > htmlMax) q = htmlMax; // mirror server clamp
inp.value = q;
commit(q);
});
inp.addEventListener('keydown', (e) => {
if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } // triggers change
});
});
})();
</script>
</body>
</html>
