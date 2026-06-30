<?php
include 'database.php';

$texts = [
    'en' => [
    'title'          => 'Products - Future X',
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
    'orders'         => 'Orders',
    'lang'           => 'ภาษาไทย'
    ],

    'th' => [
    'title'          => 'สินค้า - Future X',
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
    'orders'         => 'รายการที่สั่ง',
    'lang'           => 'English'
    ],
];

// Require login to view products
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit();
}

// Helpers for stock/remaining
function qty_in_cart($name, $price) {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) return 0;
    $sum = 0;
    foreach ($_SESSION['cart'] as $it) {
        $n = isset($it['name'])  ? $it['name']  : '';
        $p = isset($it['price']) ? (float)$it['price'] : 0;
        if ($n === $name && $p == $price) $sum += isset($it['qty']) ? (int)$it['qty'] : 1;
    }
    return $sum;
}

// Red dot if there are items and cart not seen yet
$hasUnseen = !empty($_SESSION['cart']) && (empty($_SESSION['cart_seen']) || $_SESSION['cart_seen'] === false);

		// Optional: small helper for image fallback
		function product_img($path) {
			return (is_string($path) && file_exists($path)) ? $path : 'assets/products/placeholder.jpg';
		}

$products = [];
$q = "SELECT id, name, price, pv, stock, img, unit_en, unit_th FROM products WHERE active=1 ORDER BY id ASC";
$res = $conn->query($q);
if ($res) { while ($row = $res->fetch_assoc()) { $products[] = $row; } $res->close(); }
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
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        :root {
            --brand-color:#007BFF;
            --brand-hover:#0056b3;
	    	--brand-hover-deep:#000099;
            --gray-color: #ccc;
            --ink: #111111;
        }

        body {
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            margin: 0;
            font-family: 'Inter', sans-serif;
        }

       .top-banner {
            background-color: var(--brand-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }
        .top-banner.scrolled {
            background-color: var(--brand-color);
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
            background: #fff;
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
        .lang-dropdown{
                position: relative;
                flex-shrink: 0;
        }
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

        .cart-dot { position:absolute; top:4px; right:-6px; width:10px; height:10px; background:#ef4444; border-radius:50%; display:none; box-shadow:0 0 0 2px rgba(255,255,255,.9); }
        .cart-dot.show { display:inline-block; }

        .content-section { padding:60px 20px; max-width:1100px; margin:0 auto; color:#1F2937; }
        hr.divider { border:none; border-top:2px solid #999; margin:1rem 0; }

        .content-section h2 { font-size:2.4rem; font-weight:700; margin-bottom:8px; text-align:center; color:var(--brand-color); }
        .content-section h4 { font-size:1.5rem; font-weight:500; margin-bottom:16px; text-align:center; color:#374151; }

        .product-card { padding:8px; }
        .product-image {
            width:100%;
            aspect-ratio:1/1;
            border-radius:10px;
            border:2px solid #e5e7eb;
            user-select:none;
            object-fit: cover;
            background: #f8fafc;
        }
        .product-image.color { background: linear-gradient(135deg, #F59E0B, #10B981, var(--brand-color)); border:2px dashed rgba(255,255,255,.6); }

        .product-name { margin-top:8px; font-weight:700; text-align:center; }

        .product-meta { margin:6px 0 10px; font-size:.95rem; text-align:center; }
        .product-meta strong { color: var(--brand-color); }

        .note { text-align:center; font-size:.85rem; color:#6b7280; min-height:1.2em; }

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

        .col-product { padding:10px; }
        @media (min-width:576px){ .col-product{ width:50%; float:left; } }
        @media (min-width:768px){ .col-product{ width:33.3333%; } }
        .row-products::after{ content:""; display:table; clear:both; }

        /* ── Stepper control (Big C style) ── */
        .stepper-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 50px;
            margin-top: 10px;
        }

        /* Zero state: single round + button */
        .stepper-zero {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: var(--brand-color);
            color: #fff;
            border: none;
            font-size: 1.8rem;
            font-weight: 400;
            line-height: 46px;
            padding: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .15s, box-shadow .15s;
            box-shadow: 0 3px 10px rgba(0,123,255,.4);
        }
        .stepper-zero:hover { transform: scale(1.1); box-shadow: 0 5px 16px rgba(0,123,255,.5); }
        .stepper-zero:disabled { background: #9ca3af; box-shadow: none; cursor: not-allowed; }

        /* Active pill */
        .stepper-pill {
            display: inline-flex;
            align-items: center;
            background: #fff;
            border: 2px solid var(--brand-color);
            border-radius: 999px;
            box-shadow: 0 2px 8px rgba(0,123,255,.18);
            overflow: hidden;
        }
        .stepper-btn {
            width: 44px;
            height: 44px;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background .12s;
            flex-shrink: 0;
            padding: 0;
        }
        .stepper-btn:hover { background: rgba(0,0,0,.06); }
        .stepper-btn:disabled { opacity: .35; cursor: not-allowed; }
        .stepper-inc { color: var(--brand-color); font-size: 1.5rem; font-weight: 700; }
        .stepper-dec { color: var(--brand-color); font-size: 1.5rem; font-weight: 700; }
        .stepper-dec.trash { color: #dc2626; }
        .stepper-count {
            min-width: 36px;
            text-align: center;
            font-weight: 700;
            font-size: 1.05rem;
            color: #111;
            padding: 0 2px;
            user-select: none;
        }

        .out-of-stock-label {
            font-size: .85rem;
            color: #9ca3af;
            font-weight: 600;
            letter-spacing: .02em;
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

    <div class="content-section">
        <h2><?php echo ($texts[$lang]['titlein']) ?></h2>
        <hr class="divider">

        <!-- Product grid: one square per item -->
		<div class="row-products">
			<?php foreach ($products as $i => $p): ?>
				<?php
					$name   = $p['name'];
					$price  = (float)$p['price'];
                    $point  = (float)$p['pv'];
					$base   = (int)$p['stock'];
					$img    = product_img($p['img']);
					$unit = ($lang === 'en') ? $p['unit_en'] : $p['unit_th'];

					$inCart = qty_in_cart($name, $price);
					$left   = max(0, $base - $inCart);
				?>
				<div class="col-12 col-product">
					<div class="product-card" data-name="<?php echo htmlspecialchars($name); ?>" data-price="<?php echo htmlspecialchars($price); ?>">
						<img
							class="product-image"
							src="<?php echo htmlspecialchars($img); ?>"
							alt="<?php echo htmlspecialchars($name); ?>"
							loading="lazy"
						>
						<div class="product-name"><?php echo htmlspecialchars($name); ?></div>
						<div class="product-meta">
							<div><strong><?php echo ($lang === 'en') ? 'In stock:' : 'จำนวนสินค้าที่มีในคลัง:'; ?></strong> <?php echo number_format($base); ?></div>
							<div><strong><?php echo ($lang === 'en') ? 'Price:' : 'ราคา:'; ?></strong> <?php echo number_format($price, 2, '.', ','); ?> <?php echo htmlspecialchars($unit); ?> </div>
                            <div><strong>PV:</strong> <?php echo number_format($point); ?></div>
						</div>
						<div class="stepper-wrap"
							 data-name="<?php echo htmlspecialchars($name); ?>"
							 data-price="<?php echo htmlspecialchars($price); ?>"
							 data-in-cart="<?php echo $inCart; ?>"
							 data-stock="<?php echo $left; ?>">
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
    </div>

    <!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const cartDot = document.querySelector('.cart-dot');

const trashSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
  <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
  <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
</svg>`;

function renderStepper(wrap) {
    const inCart = parseInt(wrap.dataset.inCart || '0', 10);
    const stock  = parseInt(wrap.dataset.stock  || '0', 10);

    if (inCart <= 0) {
        if (stock <= 0) {
            wrap.innerHTML = `<span class="out-of-stock-label"><?php echo ($lang === 'en') ? 'Out of Stock' : 'สินค้าหมด'; ?></span>`;
        } else {
            wrap.innerHTML = `<button class="stepper-zero" title="<?php echo ($lang === 'en') ? 'Add to cart' : 'เพิ่มลงตะกร้า'; ?>" aria-label="Add">+</button>`;
            wrap.querySelector('.stepper-zero').addEventListener('click', () => stepperAdd(wrap));
        }
    } else {
        const isOne    = inCart === 1;
        const decClass = isOne ? 'stepper-btn stepper-dec trash' : 'stepper-btn stepper-dec';
        const decIcon  = isOne ? trashSVG : '−';
        const decTitle = isOne ? '<?php echo ($lang === 'en') ? 'Remove' : 'ลบออก'; ?>' : '<?php echo ($lang === 'en') ? 'Decrease' : 'ลด'; ?>';
        const incDis   = stock <= 0 ? 'disabled' : '';
        wrap.innerHTML = `
            <div class="stepper-pill">
                <button class="${decClass}" title="${decTitle}" aria-label="${decTitle}">${decIcon}</button>
                <span class="stepper-count">${inCart}</span>
                <button class="stepper-btn stepper-inc" ${incDis} title="<?php echo ($lang === 'en') ? 'Increase' : 'เพิ่ม'; ?>" aria-label="Increase">+</button>
            </div>`;
        wrap.querySelector('.stepper-dec').addEventListener('click', () => stepperDec(wrap));
        wrap.querySelector('.stepper-inc').addEventListener('click', () => stepperAdd(wrap));
    }
}

function syncSteppers(name, price, inCart, stock) {
    document.querySelectorAll('.stepper-wrap').forEach(w => {
        if (w.dataset.name === name && String(w.dataset.price) === String(price)) {
            w.dataset.inCart = inCart;
            w.dataset.stock  = stock;
            renderStepper(w);
        }
    });
    updateCartDot();
}

function updateCartDot() {
    if (!cartDot) return;
    let any = false;
    document.querySelectorAll('.stepper-wrap').forEach(w => {
        if (parseInt(w.dataset.inCart || '0', 10) > 0) any = true;
    });
    cartDot.classList.toggle('show', any);
}

async function stepperAdd(wrap) {
    const name  = wrap.dataset.name;
    const price = wrap.dataset.price;
    try {
        const resp = await fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ name, price, qty: 1 })
        });
        if (!resp.ok) return;
        const data = await resp.json();
        if (!data.ok) return;
        syncSteppers(name, price, data.itemQty, data.stockLeft);
    } catch (e) { console.error(e); }
}

async function stepperDec(wrap) {
    const name   = wrap.dataset.name;
    const price  = wrap.dataset.price;
    const inCart = parseInt(wrap.dataset.inCart || '0', 10);
    const stock  = parseInt(wrap.dataset.stock  || '0', 10);
    const newQty = inCart - 1;
    try {
        const resp = await fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ name, price, qty: newQty, ajax: '1' })
        });
        if (!resp.ok) return;
        const data = await resp.json();
        if (!data.ok) return;
        const newInCart = data.removed ? 0 : (data.qty ?? newQty);
        const baseStock = inCart + stock;
        syncSteppers(name, price, newInCart, baseStock - newInCart);
    } catch (e) { console.error(e); }
}

// Render all steppers on load
document.querySelectorAll('.stepper-wrap').forEach(renderStepper);
</script>
</body>
</html>
