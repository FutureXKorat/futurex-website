<?php
/**
 * Admin navbar — include right after <body>.
 * Requires: session started, $conn (mysqli), $lang (from ../database.php).
 */
$_anT = [
    'en' => ['orders' => 'Orders', 'main_site' => 'Main Site', 'logout' => 'Log Out'],
    'th' => ['orders' => 'คำสั่งซื้อ', 'main_site' => 'เว็บหลัก', 'logout' => 'ออกจากระบบ'],
];
$_anL = $_anT[$lang] ?? $_anT['en'];

$_adminDisplayName = 'Admin';
if (isset($_SESSION['user_id'])) {
    $_s = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $_s->bind_param('i', $_SESSION['user_id']);
    $_s->execute();
    $_r = $_s->get_result()->fetch_assoc();
    $_s->close();
    if ($_r) $_adminDisplayName = htmlspecialchars((string)($_r['name'] ?? 'Admin'));
}
?>
<div class="top-banner" id="topBanner">
  <div class="nav-links-container" id="navLinksContainer">
    <div class="nav-scroll-indicator" id="navScrollIndicator"></div>
    <div class="nav-links">
      <a href="index.php" class="nav-logo-link">
        <img src="/logo_transparent.png" alt="FutureX" class="nav-logo-img">
      </a>
      <a href="orders.php"><?= htmlspecialchars($_anL['orders']) ?></a>
    </div>
  </div>
  <div class="right-actions">
    <span class="nav-admin-name"><?= $_adminDisplayName ?></span>
    <a href="https://futurexthailand.com/home.php" class="nav-btn nav-btn-ghost">
      <?= htmlspecialchars($_anL['main_site']) ?>
    </a>
    <a href="https://futurexthailand.com/logout.php" class="nav-btn nav-btn-danger">
      <?= htmlspecialchars($_anL['logout']) ?>
    </a>
  </div>
</div>
<script>
(function () {
    var banner = document.getElementById('topBanner');
    var nlc    = document.getElementById('navLinksContainer');
    var nsi    = document.getElementById('navScrollIndicator');
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
