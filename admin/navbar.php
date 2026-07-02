<?php
/**
 * Admin navbar — include right after <body>.
 * Requires: session started, $conn (mysqli), $lang (from ../database.php).
 */
$_anT = [
    'en' => ['orders' => 'Orders', 'stock' => 'Stock', 'products' => 'Products', 'users' => 'Users', 'admins' => 'Admins', 'main_page' => 'Main Page', 'settings' => 'Settings', 'logout' => 'Log Out'],
    'th' => ['orders' => 'คำสั่งซื้อ', 'stock' => 'สต็อก', 'products' => 'สินค้า', 'users' => 'ผู้ใช้', 'admins' => 'แอดมิน', 'main_page' => 'เว็บหลัก', 'settings' => 'การตั้งค่า', 'logout' => 'ออกจากระบบ'],
];
$_anL = $_anT[$lang] ?? $_anT['en'];

$_navPic = '/avatar.png';
$_adminDisplayName = null;

if (isset($_SESSION['admin_id'])) {
    // Employee-admin: profile from admins table
    $_s = $conn->prepare("SELECT name, profile_picture FROM admins WHERE id = ? LIMIT 1");
    $_s->bind_param('i', $_SESSION['admin_id']);
    $_s->execute();
    $_r = $_s->get_result()->fetch_assoc();
    $_s->close();
    if ($_r) {
        $_adminDisplayName = htmlspecialchars((string)($_r['name'] ?? ''));
        $picFile = (string)($_r['profile_picture'] ?? '');
        if ($picFile !== '' && str_starts_with($picFile, 'https://')) {
            $_navPic = htmlspecialchars($picFile);
        }
    }
} elseif (isset($_SESSION['user_id'])) {
    // Super-admin: profile from users table
    $_s = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ? LIMIT 1");
    $_s->bind_param('i', $_SESSION['user_id']);
    $_s->execute();
    $_r = $_s->get_result()->fetch_assoc();
    $_s->close();
    if ($_r) {
        $_adminDisplayName = htmlspecialchars((string)($_r['name'] ?? ''));
        $picFile = (string)($_r['profile_picture'] ?? '');
        if ($picFile !== '') {
            if (str_starts_with($picFile, 'https://')) {
                $_navPic = htmlspecialchars($picFile);
            } elseif (file_exists(dirname(__DIR__) . '/uploads/profile_pics/' . $picFile)) {
                $_navPic = '/uploads/profile_pics/' . htmlspecialchars($picFile);
            }
        }
    }
}
?>
<style>
/* Fixed navbar — reliable on all mobile browsers */
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
</style>
<div class="top-banner" id="topBanner">
  <div class="nav-links-container" id="navLinksContainer">
    <div class="nav-scroll-indicator" id="navScrollIndicator"></div>
    <div class="nav-links">
      <a href="index.php" class="nav-logo-link">
        <img src="/logo_transparent.png" alt="FutureX Admin" class="nav-logo-img">
      </a>
      <a href="orders.php"><?= htmlspecialchars($_anL['orders']) ?></a>
      <a href="stock.php"><?= htmlspecialchars($_anL['stock']) ?></a>
      <a href="products.php"><?= htmlspecialchars($_anL['products']) ?></a>
      <a href="users.php"><?= htmlspecialchars($_anL['users']) ?></a>
      <?php if (!empty($isSuperAdmin)): ?>
        <a href="admins.php"><?= htmlspecialchars($_anL['admins']) ?></a>
      <?php endif; ?>
    </div>
  </div>
  <div class="right-actions">

    <!-- Language dropdown — same as main site -->
    <div class="lang-dropdown">
      <button class="lang-btn-icon" id="langIcon" aria-haspopup="true" aria-expanded="false" aria-label="Change language">
        <svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true">
          <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
          <path d="M2 12h20M12 2c3 3 3 15 0 20M12 2c-3 3-3 15 0 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>
      <div id="langMenu" class="lang-dropdown-content">
        <a href="?lang=en"<?= $lang === 'en' ? ' class="active"' : '' ?>>English</a>
        <a href="?lang=th"<?= $lang === 'th' ? ' class="active"' : '' ?>>ไทย</a>
      </div>
    </div>

    <!-- Profile picture dropdown -->
    <div class="profile-dropdown">
      <img
        src="<?= htmlspecialchars($_navPic) ?>"
        onerror="this.onerror=null;this.src='/avatar.png';"
        alt="Profile"
        class="profile-img"
        id="profileIcon"
      >
      <div id="dropdownMenu" class="profile-dropdown-content">
        <?php if (!empty($isSuperAdmin)): ?>
          <a href="/home.php" style="color:#007BFF;font-weight:600;"><?= htmlspecialchars($_anL['main_page']) ?></a>
        <?php endif; ?>
        <a href="/admin/settings.php"><?= htmlspecialchars($_anL['settings']) ?></a>
        <a href="/logout.php"><?= htmlspecialchars($_anL['logout']) ?></a>
      </div>
    </div>

  </div>
</div>
<script>
(function () {
    var pIcon  = document.getElementById('profileIcon');
    var pMenu  = document.getElementById('dropdownMenu');
    var lIcon  = document.getElementById('langIcon');
    var lMenu  = document.getElementById('langMenu');
    var banner = document.getElementById('topBanner');
    var nlc    = document.getElementById('navLinksContainer');
    var nsi    = document.getElementById('navScrollIndicator');

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
