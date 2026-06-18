<?php
session_start();
include 'database.php';

$texts = [
    'th' => [
        'title'          => 'แหล่งที่มา - Future X',
    	'home'           => 'หน้าหลัก',
    	'product'        => 'สินค้า',
    	'cart'           => 'ตะกร้าสินค้า',
        'orders'         => 'สินค้าที่สั่ง',
    	'about'          => 'เกี่ยวกับพวกเรา',
    	'Source'         => 'แหล่งที่มา',
        'login'          => 'กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์ของคุณ',
        'out'            => 'ออกจากระบบ',
        'profile'        => 'แก้ไขโปรไฟล์'
    ],
    
    'en' => [
        'title'          => 'Sources - Future X',
        'home'           => 'Home',
    	'cart'           => 'Shopping Cart',
    	'product'        => 'Products',
        'orders'         => 'Orders',
    	'about'          => 'About Us',
    	'Source'         => 'Sources',
        'login'          => 'Please log in to access your profile.',
        'out'            => 'Log Out',
        'profile'        => 'Edit Profile'
    ]
];

$welcomeText = "Welcome to Future X";
$profilePicture = "avatar.png"; // default fallback

if (isset($_SESSION["user_id"])) {
    $userId = (int)$_SESSION["user_id"];
    $sql = "SELECT name, profile_picture FROM users WHERE id = $userId";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $welcomeText = "Welcome to Future X" . htmlspecialchars($row["name"]);
        if (!empty($row["profile_picture"])) {
            $profilePicture = "uploads/profile_pics/" . htmlspecialchars($row["profile_picture"]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo($texts[$lang]['title']) ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        :root {
            --brand-color: #007BFF;
            --brand-hover: #0056b3;
            --gray-color: #ccc; 
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: #1F2937;
        }
        .spacer-nav { height: 20px; }

        .content-section {
            text-align: left;
            padding: 30px 20px 30px 20px; /* equal top & bottom padding */
            max-width: 900px;
            margin: auto;
        }
        .content-section h1 {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--brand-color);
            text-align: center;
        }
        .content-section p.lead {
            font-size: 1.05rem;
            margin-bottom: 20px;
            color: #374151;
            text-align: center;
        }

        .credits {
            background: #ffffffcc;
            backdrop-filter: blur(6px);
            border-radius: 14px;
            padding: 22px 20px;
            box-shadow: 0 10px 18px rgba(0,0,0,0.06);
        }
        .credits h2 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 18px 0 10px;
            color: #1F2937;
        }
        .credits ul { margin-bottom: 8px; }
        .credits li { margin: 6px 0; }
        .credits a { color: var(--brand-color); text-decoration: none; font-weight: 600; }
        .credits a:hover { text-decoration: underline; }

        .note {
            font-size: 0.95rem;
            color: #4B5563;
            margin-top: 14px;
        }
        .footer-min {
            margin-top: 16px;
            font-size: 0.95rem;
            color: #4B5563;
            text-align: center;
        }
        .small-muted {
            display: block;
            margin-top: 6px;
            font-size: 0.9rem;
            color: #6B7280;
            text-align: center;
        }
        .divider {
            height: 1px;
            background: rgba(0,0,0,0.07);
            margin: 20px 0;
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
        .right-actions{
  	    display:flex;
  	    align-items:center;
  	    gap:10px;
  	    margin-right:12px;
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
  	    transition: transform .15s ease, background .2s ease, opacity .15s ease;s
	}
	.lang-btn:hover{
  	    background: rgba(255,255,255,0.28);
  	    transform: translateY(-1px);
	}
    </style>
</head>
<body>
<div class="top-banner" id="topBanner">
  <div class="nav-links-container" id="navLinksContainer">
    <div class="nav-scroll-indicator" id="navScrollIndicator"></div>
    <div class="nav-links" id="navLinks">
      <a href="home.php"><?php echo ($texts[$lang]['home']) ?></a>

      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="products.php"><?php echo ($texts[$lang]['product']) ?></a>
        <a href="cart.php"><?php echo ($texts[$lang]['cart']) ?></a>
        <a href="orders.php"><?php echo ($texts[$lang]['orders']) ?></a>
      <?php endif; ?>

      <a href="about.php"><?php echo ($texts[$lang]['about']) ?></a>
      <a href="source.php"><?php echo ($texts[$lang]['Source']) ?></a>
    </div>
  </div>

  <div class="right-actions">
  <div class="lang-dropdown">
  <button class="lang-btn-icon" id="langIcon" aria-haspopup="true" aria-expanded="false" aria-label="Change language">
    <!-- Globe SVG -->
    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
      <path d="M2 12h20M12 2c3 3 3 15 0 20M12 2c-3 3-3 15 0 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
  </button>

  <div id="langMenu" class="lang-dropdown-content">
    <a href="?lang=en" class="<?php echo ($lang==='en'?'active':''); ?>">English</a>
    <a href="?lang=th" class="<?php echo ($lang==='th'?'active':''); ?>">ไทย</a>
  </div>
</div>
  
  <!-- Profile dropdown -->
  <div class="profile-dropdown">
    <img src="<?php echo file_exists($profilePicture) ? $profilePicture : 'avatar.png'; ?>" alt="Profile" class="profile-img" id="profileIcon">
    <div id="dropdownMenu" class="profile-dropdown-content">
      <?php if (isset($_SESSION["user_id"])): ?>
        <a href="profile.php"><?php echo ($texts[$lang]['profile']) ?></a>
        <a href="logout.php"><?php echo ($texts[$lang]['out']) ?></a>
      <?php else: ?>
        <a href="index.php"><?php echo ($texts[$lang]['login']) ?></a>
      <?php endif; ?>
    </div>
  </div>
  </div>
  </div>

    <div class="spacer-nav"></div>

    <!-- Main Content -->
    <div class="content-section">
        <h1><?php echo ($lang === 'en') ? 'Sources & Credits' : 'แหล่งที่มา & เครดิต' ?></h1>
        <p class="lead"><?php echo ($lang === 'en') ? 'This page lists tools and libraries used in this site to help ensure transparency and avoid plagiarism.' : 'หน้านี้แสดงรายการเครื่องมือและไลบรารีที่ใช้ในเว็บไซต์นี้ เพื่อช่วยให้เกิดความโปร่งใสและป้องกันการลอกเลียนแบบ' ?></p>

        <div class="credits">
            <h2><?php echo ($lang === 'en') ? 'Text Content' : 'เนื้อหาข้อความ' ?></h2>
            <ul>
                <li><?php echo ($lang === 'th') ? 'เนื้อหาเขียนขึ้นโดยได้รับความช่วยเหลือจาก' : 'Text written with assistance from' ?>
                    <a href="https://openai.com/" target="_blank" rel="noopener noreferrer">ChatGPT (OpenAI)</a>.
                </li>
            </ul>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'Design' : 'การออกแบบ' ?></h2>
            <ul>
                <li><?php echo ($lang === 'en') ? 'Logo created using' : 'โลโก้สร้างขึ้นโดยใช้'?>
                    <a href="https://looka.com/" target="_blank" rel="noopener noreferrer">Looka.com</a>.
                </li>
                <li><?php echo ($lang === 'en') ? 'Image editing & exports via' : 'การแก้ไขและส่งออกรูปภาพผ่าน'?>
                    <a href="https://www.photopea.com/" target="_blank" rel="noopener noreferrer">Photopea</a>.
                </li>
            </ul>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'Fonts' : 'แบบอักษร' ?></h2>
            <ul>
                <li>
                    <a href="https://fonts.google.com/specimen/Inter" target="_blank" rel="noopener noreferrer">Google Fonts — Inter</a>
                </li>
            </ul>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'Frameworks & Libraries' : 'เฟรมเวิร์กและไลบรารี' ?></h2>
            <ul>
                <li>
                    <a href="https://getbootstrap.com/docs/5.3/getting-started/introduction/" target="_blank" rel="noopener noreferrer">Bootstrap 5.3.2</a>
                </li>
                <li>
                    <a href="https://github.com/PHPMailer/PHPMailer" target="_blank" rel="noopener noreferrer">PHPMailer</a>
                </li>
                <li>
                    <a href="https://github.com/fengyuanchen/cropperjs" target="_blank" rel="noopener noreferrer">Cropper.js</a>
                </li>
            </ul>

            <div class="note">
                <?php echo ($lang === 'en') ? 'All third-party trademarks, logos, and assets are the property of their respective owners.
                Links are provided for attribution and do not imply endorsement.' : 'เครื่องหมายการค้า โลโก้ และทรัพย์สินของบุคคลที่สามทั้งหมดเป็นกรรมสิทธิ์ของเจ้าของที่เกี่ยวข้อง ลิงก์ที่ให้ไว้มีวัตถุประสงค์เพื่อการอ้างอิงเท่านั้น และไม่ได้หมายความถึงการรับรอง' ?>
            </div>

            <div class="divider"></div>

            <div class="footer-min">
                <?php echo ($lang === 'en') ? 'Future X Korat — All rights reserved.' : 'Future X Korat — สงวนลิขสิทธิ์' ?>
                <span class="small-muted"><?php echo ($lang === 'en') ? 'Last updated:' : 'การอัพเดตครั้งสุดท้าย:' ?> <?php echo date('F j, Y'); ?></span>
            </div>
        </div>
    </div>
    <script>
    // Profile dropdown
    const profileIcon = document.getElementById("profileIcon");
    const dropdownMenu = document.getElementById("dropdownMenu");
    if (profileIcon) {
        profileIcon.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
        });
        document.addEventListener("click", () => { dropdownMenu.style.display = "none"; });
    }

    // Navbar scroll state (kept, but background remains red)
    const topBanner = document.getElementById("topBanner");
    window.addEventListener("scroll", () => {
        topBanner.classList.toggle("scrolled", window.scrollY > 10);
    });

// Language dropdown
const langIcon = document.getElementById("langIcon");
const langMenu = document.getElementById("langMenu");
const profileIconEl = document.getElementById("profileIcon");
const profileMenuEl = document.getElementById("dropdownMenu");

if (langIcon) {
    langIcon.addEventListener("click", (e) => {
        e.stopPropagation();
        // close profile menu if open
        if (profileMenuEl) 
            profileMenuEl.style.display = "none";
        // toggle language menu
        const open = langMenu.style.display === "block";
        langMenu.style.display = open ? "none" : "block";
        langIcon.setAttribute("aria-expanded", open ? "false" : "true");
    });
}

// Close both menus when clicking outside
document.addEventListener("click", () => {
    if (langMenu) 
        langMenu.style.display = "none";
    if (profileMenuEl) 
        profileMenuEl.style.display = "none";
});

// Optional: close on Escape key
document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
        if (langMenu) 
            langMenu.style.display = "none";
        if (profileMenuEl) 
            profileMenuEl.style.display = "none";
        if (langIcon) 
            langIcon.setAttribute("aria-expanded","false");
    }
});
</script>

</body>
</html>
