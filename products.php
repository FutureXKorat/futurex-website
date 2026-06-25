<?php
session_start();
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

// Precompute remaining per product 

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
            --ink: #111111;           /* darker text for white backgrounds */
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
		#profileIcon + .profile-dropdown-content {
  	    	margin-top: 8px; /* or whatever gap you want */
		}
	.right-actions{
  	    display:flex;
  	    align-items:center;
  	    gap:10px;
  	    margin-right:12px;
	}

        .profile-dropdown {
            transition: transform .15s ease;
            will-change: transform;
        }

        .profile-dropdown:hover {
            transform: translateY(-1px);
        }
        .profile-dropdown-content {
            display: none;
            position: absolute;
            right: 10px;
            background-color: white;
            min-width: 190px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            border-radius: 8px;
            overflow: hidden;
            padding: 0;
        }
        .profile-dropdown-content a {
            display: block;
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            transition: background 0.3s;
            white-space: nowrap;
        }
        .profile-dropdown-content a:hover { background-color: #f2f2f2; color: #333;}
        .profile-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            background-color: var(--gray-color);
            border: none; /* no border */
        }
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

        .cart-dot { position:absolute; top:4px; right:-6px; width:10px; height:10px; background:#ef4444; border-radius:50%; display:none; box-shadow:0 0 0 2px rgba(255,255,255,.9); }
        .cart-dot.show { display:inline-block; }

        .content-section { padding:60px 20px; max-width:1100px; margin:0 auto; color:#1F2937; }
        hr.divider { border:none; border-top:2px solid #999; margin:1rem 0; }

        /* 🔴 Headings now brand red */
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
        /* 🔴 Color sample swaps blue for brand red */
        .product-image.color { background: linear-gradient(135deg, #F59E0B, #10B981, #FF0000); border:2px dashed rgba(255,255,255,.6); }

        .product-name { margin-top:8px; font-weight:700; text-align:center; }

        .product-meta { margin:6px 0 10px; font-size:.95rem; text-align:center; }
        .product-meta strong { color: var(--brand-color); } /* 🔴 strong text now brand red */

        .qty-row { display:flex; gap:8px; align-items:center; justify-content:center; margin-bottom:10px; }
        .qty-row input[type="number"] { width:90px; text-align:center; border-radius:10px; border:1px solid #d1d5db; padding:6px 8px; font-weight:600; }

        .note { text-align:center; font-size:.85rem; color:#6b7280; min-height:1.2em; }

        /* 🔴 Buy button now red gradient */
        .btn-buy {
            display:block; width:100%;
            background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
            color:#fff; padding:10px 16px; font-size:1rem; font-weight:600;
            border:none; border-radius:14px; transition:transform .2s, filter .2s, box-shadow .2s;
        }
        .btn-buy:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--brand-hover), var(--brand-hover-deep));
            box-shadow: 0 6px 16px rgba(0,0,204,0.25);
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

        .col-product { padding:10px; }
        @media (min-width:576px){ .col-product{ width:50%; float:left; } }
        @media (min-width:768px){ .col-product{ width:33.3333%; } }
        .row-products::after{ content:""; display:table; clear:both; }

        .disabled { opacity:.6; pointer-events:none; }
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
						<div class="qty-row">
							<label for="qty-<?php echo $i; ?>" class="me-1"><?php echo ($lang === 'en') ? 'Qty' : 'จำนวน'; ?></label>
							<input id="qty-<?php echo $i; ?>" class="qty-input" type="number" min="1" step="1" value="1"
								   max="<?php echo $left; ?>" inputmode="numeric" pattern="[0-9]*">
						</div>
						<div class="note" data-note></div>
						<button type="button" class="btn-buy buy-btn"
								data-name="<?php echo htmlspecialchars($name); ?>"
								data-price="<?php echo htmlspecialchars($price); ?>"
								data-remaining="<?php echo $left; ?>"><?php echo ($lang === 'en') ? 'Buy' : 'ซื้อ'; ?></button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
    </div>

    <!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

        const cartDot = document.querySelector('.cart-dot');

        function syncRemaining(name, price, left) {
            document.querySelectorAll('.product-card').forEach(card => {
                const n = card.dataset.name, p = card.dataset.price;
                if (n === name && String(p) === String(price)) {
                    const btn = card.querySelector('.buy-btn');
                    const qty = card.querySelector('.qty-input');
                    const note = card.querySelector('[data-note]');
                    if (btn) btn.dataset.remaining = left;
                    if (qty) {
                        qty.max = left;
                        if (parseInt(qty.value || '1', 10) > left) qty.value = left > 0 ? left : 1;
                    }
                    if (left <= 0) {
                        btn.classList.add('disabled');
                        btn.textContent = 'Out of stock';
                    } else {
                        btn.classList.remove('disabled');
                        btn.textContent = <?php echo json_encode(($lang === 'en') ? 'Buy' : 'ซื้อ'); ?>;

                        if (note) note.textContent = '';
                    }
                }
            });
        }

        document.querySelectorAll('.buy-btn').forEach(btn => {
            const card = btn.closest('.product-card');
            const qtyInput = card.querySelector('.qty-input');
            const note = card.querySelector('[data-note]');

            // Disable if no remaining at load
            if (parseInt(btn.dataset.remaining || '0', 10) <= 0) {
                btn.classList.add('disabled');
                btn.textContent = <?php echo json_encode(($lang === 'en') ? 'Out of Stock' : 'สินค้าหมด'); ?>;
            }

            btn.addEventListener('click', async () => {
                const name  = btn.dataset.name;
                const price = btn.dataset.price;
                let qty = parseInt(qtyInput && qtyInput.value ? qtyInput.value : '1', 10);
                if (isNaN(qty) || qty < 1) qty = 1;

                try {
                    const resp = await fetch('add_to_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ name, price, qty })
                    });
                    if (!resp.ok) { console.error('Add failed:', resp.status); return; }
                    const data = await resp.json();
                    if (!data.ok) return;

                    // Turn on red dot
                    if (cartDot) cartDot.classList.add('show');

                    // Update remaining across all identical cards
                    syncRemaining(name, price, parseInt(data.stockLeft, 10));

                    // Button cue reflecting actual added (may be capped)
                    const added = parseInt(data.added, 10);
                    const original = btn.textContent;
                    if (added > 0) {
                        btn.disabled = true; btn.textContent = <?php echo json_encode(($lang === 'en') ? 'Added to Cart × ' : 'เพิ่มลงตะกร้า × '); ?> + added;
                        setTimeout(() => { btn.disabled = false; btn.textContent = original; }, 1200);
                        if (note) note.textContent = data.capped ? 'Capped to remaining stock.' : '';
                    } else {
                        // Nothing added, already max
                    }
                } catch (e) {
                    console.error('Network error', e);
                }
            });
        });
    </script>
</body>
</html>
