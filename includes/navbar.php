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
        'profile' => 'Edit Profile',
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
        'profile' => 'แก้ไขโปรไฟล์',
        'out'     => 'ออกจากระบบ',
        'login'   => 'กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์ของคุณ',
    ],
];
$_navL = $_navT[$lang] ?? $_navT['en'];

$_navPic      = 'avatar.png';
$_navUserName = null;

if (isset($_SESSION['user_id'])) {
    $_navUid  = (int)$_SESSION['user_id'];
    $_navStmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
    $_navStmt->bind_param("i", $_navUid);
    $_navStmt->execute();
    $_navRow = $_navStmt->get_result()->fetch_assoc();
    $_navStmt->close();
    if ($_navRow) {
        $_navUserName = htmlspecialchars($_navRow['name'] ?? '');
        if (!empty($_navRow['profile_picture'])) {
            $_navPic = 'uploads/profile_pics/' . htmlspecialchars($_navRow['profile_picture']);
        }
    }
}
?>
<div class="top-banner" id="topBanner">
  <div class="nav-links-container" id="navLinksContainer">
    <div class="nav-scroll-indicator" id="navScrollIndicator"></div>
    <div class="nav-links" id="navLinks">
      <a href="home.php"><?php echo $_navL['home']; ?></a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="products.php"><?php echo $_navL['product']; ?></a>
        <a href="cart.php"><?php echo $_navL['cart']; ?></a>
        <a href="orders.php"><?php echo $_navL['orders']; ?></a>
      <?php endif; ?>
      <a href="about.php"><?php echo $_navL['about']; ?></a>
      <a href="source.php"><?php echo $_navL['source']; ?></a>
    </div>
  </div>

  <div class="right-actions">
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
      <img src="<?php echo htmlspecialchars($_navPic); ?>" alt="Profile" class="profile-img" id="profileIcon">
      <div id="dropdownMenu" class="profile-dropdown-content">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="profile.php"><?php echo $_navL['profile']; ?></a>
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
