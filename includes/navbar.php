<?php
/**
 * Shared navbar — include this once per page, right after <body>.
 * Requires: session already started, $conn (mysqli) available, $lang set.
 * Sets: $_navUserName (string|null) — the logged-in user's display name.
 */

$_navT = [
    'en' => [
        'home'    => 'Home',
        'product' => 'Products',
        'cart'    => 'Shopping Cart',
        'orders'  => 'Orders',
        'about'   => 'About Us',
        'source'  => 'Sources',
        'version' => 'Updates',
        'profile' => 'Settings',
        'out'     => 'Log Out',
        'login'   => 'Please log in to access your profile.',
    ],
    'th' => [
        'home'    => 'หน้าหลัก',
        'product' => 'สินค้า',
        'cart'    => 'ตะกร้าสินค้า',
        'orders'  => 'รายการที่สั่ง',
        'about'   => 'เกี่ยวกับพวกเรา',
        'source'  => 'แหล่งที่มา',
        'version' => 'อัปเดต',
        'profile' => 'การตั้งค่า',
        'out'     => 'ออกจากระบบ',
        'login'   => 'กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์ของคุณ',
    ],
];
$_navL = $_navT[$lang] ?? $_navT['en'];

$_navPic      = 'avatar.png';
$_navUserName = null;

$_navIsAdmin = false;
if (isset($_SESSION['user_id'])) {
    $_navUid  = (int)$_SESSION['user_id'];
    $_navStmt = $conn->prepare("SELECT name, email, profile_picture FROM users WHERE id = ?");
    $_navStmt->bind_param("i", $_navUid);
    $_navStmt->execute();
    $_navRow = $_navStmt->get_result()->fetch_assoc();
    $_navStmt->close();
    if ($_navRow) {
        $_navUserName = htmlspecialchars($_navRow['name'] ?? '');
        if (!empty($_navRow['profile_picture']) && str_starts_with($_navRow['profile_picture'], 'https://')) {
            $_navPic = htmlspecialchars($_navRow['profile_picture']);
        }
        // Check if this user is the admin
        $_navMailCfg  = is_file(dirname(__DIR__) . '/secure-config/futurex_mail.php') ? require dirname(__DIR__) . '/secure-config/futurex_mail.php' : [];
        $_navAdminEmail = strtolower(trim((string)($_navMailCfg['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL') ?: 'futurexkorat@gmail.com')));
        $_navIsAdmin  = strtolower(trim((string)($_navRow['email'] ?? ''))) === $_navAdminEmail;
    }
}
?>
<style>
/* Fixed navbar — reliable on all mobile browsers, no -webkit-sticky quirks */
.top-banner {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
    z-index: 1000 !important;
}
/* Push page content below the fixed navbar */
body {
    padding-top: 60px !important;
}
.nav-logo-link { padding: 4px 8px !important; display: inline-flex !important; align-items: center !important; border-radius: 4px; transition: background 0.3s, transform 0.15s ease !important; }
.nav-logo-link:hover { background: rgba(255,255,255,0.15) !important; transform: translateY(-1px) !important; }
.nav-logo-img { height: 36px; width: auto; display: block; }
.nav-cart-btn {
  width: 42px; height: 42px;
  display: grid; place-items: center;
  border: 1px solid rgba(255,255,255,0.35);
  background: rgba(255,255,255,0.18);
  color: #fff;
  border-radius: 50%;
  cursor: pointer;
  text-decoration: none;
  flex-shrink: 0;
  transition: transform .15s ease, background .2s ease;
}
.nav-cart-btn:hover { background: rgba(255,255,255,0.28); transform: translateY(-1px); color: #fff; }
</style>
<div class="top-banner" id="topBanner">
  <div class="nav-links-container" id="navLinksContainer">
    <div class="nav-scroll-indicator" id="navScrollIndicator"></div>
    <div class="nav-links" id="navLinks">
      <a href="home.php" class="nav-logo-link"><img src="logo_transparent.png" alt="Home" class="nav-logo-img"></a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="products.php"><?php echo $_navL['product']; ?></a>
        <a href="orders.php"><?php echo $_navL['orders']; ?></a>
      <?php endif; ?>
      <a href="about.php"><?php echo $_navL['about']; ?></a>
      <a href="source.php"><?php echo $_navL['source']; ?></a>
      <a href="version.php"><?php echo $_navL['version']; ?></a>
    </div>
  </div>

  <div class="right-actions">
    <?php if (isset($_SESSION['user_id'])): ?>
    <a href="cart.php" class="nav-cart-btn" aria-label="<?php echo $_navL['cart']; ?>">
      <svg viewBox="0 0 16 16" width="22" height="22" fill="currentColor" aria-hidden="true">
        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
      </svg>
    </a>
    <?php endif; ?>
    <div class="lang-dropdown">
      <button class="lang-btn-icon" id="langIcon" aria-haspopup="true" aria-expanded="false" aria-label="Change language">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M2 12h20M12 2c3 3 3 15 0 20M12 2c-3 3-3 15 0 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      <div id="langMenu" class="lang-dropdown-content">
        <a href="?lang=en"<?php echo $lang === 'en' ? ' class="active"' : ''; ?>>English</a>
        <a href="?lang=th"<?php echo $lang === 'th' ? ' class="active"' : ''; ?>>ไทย</a>
      </div>
    </div>

    <div class="profile-dropdown">
      <img src="<?php echo htmlspecialchars($_navPic); ?>" onerror="this.onerror=null;this.src='avatar.png';" alt="Profile" class="profile-img" id="profileIcon">
      <div id="dropdownMenu" class="profile-dropdown-content">
        <?php if (isset($_SESSION['user_id'])): ?>
          <?php if ($_navIsAdmin): ?>
            <a href="/admin/" style="color:#007BFF;font-weight:600;">Admin Page</a>
          <?php endif; ?>
          <a href="settings.php"><?php echo $_navL['profile']; ?></a>
          <a href="logout.php"><?php echo $_navL['out']; ?></a>
        <?php else: ?>
          <a href="index.php"><?php echo $_navL['login']; ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script>
(function () {
    var pIcon = document.getElementById('profileIcon');
    var pMenu = document.getElementById('dropdownMenu');
    var lIcon = document.getElementById('langIcon');
    var lMenu = document.getElementById('langMenu');
    var banner = document.getElementById('topBanner');
    var nlc = document.getElementById('navLinksContainer');
    var nsi = document.getElementById('navScrollIndicator');

    function closeAll() {
        if (pMenu) pMenu.style.display = 'none';
        if (lMenu) lMenu.style.display = 'none';
    }

    if (pIcon) {
        pIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            if (lMenu) lMenu.style.display = 'none';
            pMenu.style.display = pMenu.style.display === 'block' ? 'none' : 'block';
        });
    }
    if (lIcon) {
        lIcon.addEventListener('click', function (e) {
            e.stopPropagation();
            if (pMenu) pMenu.style.display = 'none';
            var open = lMenu.style.display === 'block';
            lMenu.style.display = open ? 'none' : 'block';
            lIcon.setAttribute('aria-expanded', open ? 'false' : 'true');
        });
    }

    document.addEventListener('click', closeAll);
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeAll();
            if (lIcon) lIcon.setAttribute('aria-expanded', 'false');
        }
    });

    if (banner) {
        window.addEventListener('scroll', function () {
            banner.classList.toggle('scrolled', window.scrollY > 10);
        });
    }

    function updateScroll() {
        if (!nlc || !nsi) return;
        var max = nlc.scrollWidth - nlc.clientWidth;
        nsi.style.width = (max > 0 ? (nlc.scrollLeft / max) * 100 : 0) + '%';
    }
    if (nlc) {
        nlc.addEventListener('scroll', updateScroll);
        window.addEventListener('resize', updateScroll);
        updateScroll();
    }
})();
</script>
