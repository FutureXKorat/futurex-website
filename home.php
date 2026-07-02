<?php
include 'database.php';

$texts = [
    'en' => [
    'title'          => 'Home - Future X',
    'home'           => 'Home',
    'cart'           => 'Shopping Cart',
    'product'        => 'Products',
    'btnproduct'     => 'View Products',
    'about'          => 'About Us',
    'Source'         => 'Sources',
    'Welcome'        => 'Welcome to Future X, ',
    'sponsor'        => 'Sponsor Bonus',
    'dsponsor'       => 'Bonus from sponsors for 3 generations when your downline buys our product.',
    'binary'         => 'Binary Bonus',
    'dbinary'        => '30% of the points from the weak side. Please go to the official website for more details.',
    'matching'       => 'Matching Bonus',
    'dmatching'      => 'If your downline gets income from matching, you will also get it',
    'uni'            => 'Uni-level bonus',
    'duni'           => 'If your 20 level downline buys our products, you get 10% for each level.',
    'page'           => 'This is the page for 7 warehouse (Nakhon Ratchasima)',
    'login'          => 'Please log in to access your profile.',
    'login2'         => 'Please log in to see our products.',
    'profile'        => 'Edit Profile',
    'out'            => 'Log Out',
    'orders'         => 'Orders',
    'tagline'        => 'Premium products, imported from Malaysia, delivered across Thailand.',
    'stat1Title'     => '20 Warehouses',
    'stat1Sub'       => 'Across Thailand',
    'stat2Title'     => '2 Countries',
    'stat2Sub'       => 'Manufacturing in Malaysia, Distributed in Thailand',
    'stat3Title'     => 'High Quality',
    'stat3Sub'       => 'Innovative & affordable products',
    'lang'           => 'ภาษาไทย'
    ],

    'th' => [
    'title'          => 'หน้าหลัก - Future X',
    'home'           => 'หน้าหลัก',
    'product'        => 'สินค้า',
    'btnproduct'     => 'ดูสินค้า',
    'cart'           => 'ตะกร้าสินค้า',
    'about'          => 'เกี่ยวกับพวกเรา',
    'Source'         => 'แหล่งที่มา',
    'Welcome'        => 'ยินดีต้อนรับสู่ Future X, ',
    'sponsor'        => 'โบนัสสปอนเซอร์',
    'dsponsor'       => 'โบนัสจากผู้สนับสนุน 3 รุ่น เมื่อดาวน์ไลน์ของคุณซื้อผลิตภัณฑ์ของเรา',
    'binary'         => 'โบนัสจับคู่จ่าย',
    'dbinary'        => '30% ของคะแนนมาจากฝั่งอ่อน - มีรายละเอียดเพิ่มเติม กรุณาไปที่เว็บไซต์ทางการ',
    'matching'       => 'โบนัสแมทชิ่ง',
    'dmatching'      => 'ถ้าลูกเราจับคู่ เราก็ได้ด้วย',
    'uni'            => 'โบนัสยูนิเลเวล',
    'duni'           => 'หากดาวน์ไลน์ลึก 20 ชั้นของท่านมีการสั่งซื้อผลิตภัณฑ์ของเรา ท่านจะได้รับค่าตอบแทน 10% ในแต่ละชั้น',
    'page'           => 'นี่คือหน้าเพจของคลัง 7 (นครราชสีมา)',
    'login'          => 'กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์ของคุณ',
    'login2'         => 'กรุณาเข้าสู่ระบบเพื่อดูสินค้าของเรา',
    'profile'        => 'แก้ไขโปรไฟล์',
    'out'            => 'ออกจากระบบ',
    'orders'         => 'รายการที่สั่ง',
    'tagline'        => 'สินค้าคุณภาพนำเข้าจากมาเลเซีย จัดส่งทั่วประเทศไทย',
    'stat1Title'     => '20 คลังสินค้า',
    'stat1Sub'       => 'ทั่วประเทศไทย',
    'stat2Title'     => '2 ประเทศ',
    'stat2Sub'       => 'ผลิตในมาเลเซีย จัดจำหน่ายในประเทศไทย',
    'stat3Title'     => 'คุณภาพสูง',
    'stat3Sub'       => 'สินค้านวัตกรรม ราคาไม่แพง',
    'lang'           => 'English'
    ],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo ($texts[$lang]['title']) ?></title>
    <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        :root {
            --brand-color:#007BFF;
            --brand-hover:#0056b3;
            --gray-color: #ccc;
            --ink: #111111;           /* darker text for white backgrounds */
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #FFFFFF;
            color: var(--ink);
        }

        /* Navbar (kept structure, colors switched to red/white; stays opaque when scrolled) */
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

  
        /* HERO — switched to red base with soft light overlays */
        .logo-container {
            position: relative;
            background:
                radial-gradient(1200px 600px at 20% 10%, rgba(255,255,255,0.18), transparent 60%),
                radial-gradient(900px 500px at 80% 30%, rgba(255,255,255,0.12), transparent 60%),
                var(--brand-color);
            width: 100%;
            height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center; /* default: center on desktops */
            overflow: hidden;
            isolation: isolate;
        }
        .logo-container::before,
        .logo-container::after {
            content: "";
            position: absolute;
            width: 55vmax;
            height: 55vmax;
            border-radius: 50%;
            filter: blur(40px);
            opacity: 0.14;
            animation: blobFloat 24s ease-in-out infinite;
            pointer-events: none;
            background: #FFD6D6; /* soft pinkish glow */
        }
        .logo-container::before { top: -20vmax; left: -10vmax; }
        .logo-container::after  { bottom: -20vmax; right: -10vmax; animation-delay: 8s; }
        @keyframes blobFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            50%     { transform: translate(2vmax, -3vmax) scale(1.05); }
        }

        .logo-box {
            width: clamp(260px, 45vw, 680px); /* responsive size */
            aspect-ratio: 19 / 6;
            display: grid;
            place-items: center;
            transform: translateY(16px) scale(0.98);
            opacity: 0;
            transition: transform 800ms cubic-bezier(.2,.8,.2,1), opacity 800ms ease;
            position: relative;
            z-index: 1;           /* below arrow */
            pointer-events: none; /* don't block arrow clicks */
        }
        .hero-loaded .logo-box { transform: none; opacity: 1; }
        .logo-box img { width: 100%; height: 100%; object-fit: contain; pointer-events: none; }

        .hero-tagline {
            position: relative;
            z-index: 1;
            margin-top: 4px;
            color: #fff;
            font-weight: 600;
            font-size: clamp(0.95rem, 0.85rem + 0.6vw, 1.2rem);
            text-align: center;
            text-shadow: 0 2px 10px rgba(0,0,0,0.15);
            max-width: 90vw;
            transform: translateY(16px);
            opacity: 0;
            transition: transform 800ms cubic-bezier(.2,.8,.2,1) 150ms, opacity 800ms ease 150ms;
        }
        .hero-loaded .hero-tagline { transform: none; opacity: 1; }

        .hero-wave {
            position: absolute;
            left: 0;
            bottom: -1px;
            width: 100%;
            /* capped so the wave can't grow tall enough on wide screens to reach the arrow */
            height: clamp(24px, 6vw, 90px);
            line-height: 0;
            z-index: 0;
            pointer-events: none;
        }

        .scroll-down {
            position: absolute;
            /* always clears the wave's tallest point (see .hero-wave height) with room to spare */
            bottom: calc(clamp(24px, 6vw, 90px) + 32px);
            width: 60px;
            height: 60px;
            cursor: pointer;
            animation: bounce 1.5s infinite;
            z-index: 999;         /* ensure above logo */
            pointer-events: auto;
        }
        .scroll-down svg { width: 100%; height: 100%; }
        .scroll-down svg polyline { stroke: white; }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-12px); }
            60% { transform: translateY(-6px); }
        }

        /* TRUST / STAT ROW */
        .stats-row {
            max-width: 1100px;
            margin: 0 auto;
            padding: 32px 16px 4px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 16px;
        }
        .stat-card {
            flex: 1 1 220px;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 22px 18px;
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.12);
        }
        .stat-icon-wrap {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: rgba(0, 123, 255, 0.1);
            margin-bottom: 10px;
        }
        .stat-icon { width: 26px; height: 26px; }
        .stat-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--brand-hover);
        }
        .stat-sub {
            font-size: 0.9rem;
            color: #374151;
            margin-top: 2px;
        }

        /* FEATURES SLIDER — red-tinted background */
        .features-wrap { background: #ffffff; }
        .features {
            max-width: 1100px;
            margin: 0 auto;
            padding: 36px 16px 8px;
        }
        .features .carousel-inner { height: 260px; }
        .carousel .carousel-item { height: 100%; }

        .slide-inner{
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 36px 16px;
            background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
            border-radius: 20px;
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.12);
        }
        .feature-title {
            font-size: clamp(1.4rem, 1.2rem + 1vw, 2rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            color: var(--brand-hover);
            margin-bottom: 8px;
        }
        .feature-sub {
            font-size: clamp(0.95rem, 0.9rem + 0.4vw, 1.1rem);
            color: #374151;
            max-width: 780px;
            margin: 0 auto;
        }
        .feature-icon {
            width: 44px;
            height: 44px;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 50%;
            background: rgba(0, 123, 255, 0.08);
            box-sizing: content-box;
        }
        .feature-icon path { stroke: var(--brand-color); }
        .carousel-indicators [data-bs-target] { background-color: var(--brand-color); opacity: 0.35; }
        .carousel-indicators .active { opacity: 1; }

        /* CONTENT */
        .content-section {
            text-align: center;
            padding: 60px 20px 0 20px;
            margin-top: -20px;
            color: var(--ink);
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .content-section h1 {
            font-size: clamp(2rem, 1.4rem + 2.2vw, 2.8rem);
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
            color: var(--brand-color);
        }
        .content-section p.lead {
            font-size: clamp(1.05rem, 1rem + 0.5vw, 1.3rem);
            font-weight: 500;
            margin-bottom: 16px;
            color: #374151;
            line-height: 1.6;
        }
        .export-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-top: 30px;
            font-weight: 600;
            font-size: 1.05rem;
            color: var(--brand-color);
            text-transform: uppercase;
        }
        .export-image {
            max-width: 280px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 123, 255, 0.25);
        }

        /* Button (floats up on hover) — red gradient B */
        .btn-gradient {
            display: inline-block;
            background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
            color: #fff !important;
            padding: 14px 28px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 20px;
            text-decoration: none;
            box-shadow: 0 6px 16px rgba(0, 0, 204, 0.25);
            transform: translateY(0);
       			transition: transform 0.15s ease, 
                box-shadow 0.2s ease, 
                filter 0.2s ease, 
                background 0.2s ease, 
                opacity 0.15s ease;
            will-change: transform;
        }
        .btn-gradient:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(0, 0, 204, 0.35);
            text-decoration: none;
            filter: brightness(1.03);
        }

        /* Reveal on scroll */
        .reveal { opacity: 0; transform: translateY(16px); transition: opacity 700ms ease, transform 700ms ease; }
        .reveal.visible { opacity: 1; transform: none; }

        /* Phones & tablets — logo at top with ~16px padding (kept) */
        @media (max-width: 1280px) {
            .logo-container {
                justify-content: flex-start;
                padding-top: 16px;
            }
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
  	    transition: transform .15s ease, background .2s ease, opacity .15s ease;
	}
	.lang-btn:hover{
  	    background: rgba(255,255,255,0.28);
  	    transform: translateY(-1px);
	}
    .feature-diagram{
  		 /* tweak as you like */
  		height: auto;
  		margin-bottom: 10px;
	}
    .carousel-control-prev-icon {
        filter: invert(100%);
    }
	.carousel-control-next-icon {
    	filter: invert(100%); /* turns white SVG to black */
	}



        @media (prefers-reduced-motion: reduce) {
            .logo-box, .btn-gradient, .reveal { transition: none !important; }
            .scroll-down { animation: none !important; }
            .logo-container::before, .logo-container::after { animation: none !important; }
        }
        .footer-min {
            margin-top: 16px;
            padding-bottom: 20px;
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
    </style>
</head>
<body>
<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<!-- HERO -->
<div class="logo-container" id="hero">
    <div class="logo-box">
        <img src="logo_transparent.png" alt="Future X Logo">
    </div>
    <p class="hero-tagline"><?php echo ($texts[$lang]['tagline']); ?></p>
    <div class="scroll-down" id="scrollDown" aria-label="Scroll to features">
        <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
            <circle cx="30" cy="30" r="28" stroke="white" stroke-width="4"/>
            <polyline points="20,25 30,35 40,25" stroke="white" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <svg class="hero-wave" viewBox="0 0 1440 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path fill="#ffffff" d="M0,32 C240,80 480,80 720,48 C960,16 1200,16 1440,48 L1440,80 L0,80 Z"></path>
    </svg>
</div>

<!-- TRUST / STAT ROW -->
<section class="features-wrap" id="features">
    <div class="stats-row reveal">
        <div class="stat-card">
            <div class="stat-icon-wrap">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M3 21V9L12 3L21 9V21" stroke="var(--brand-color)" stroke-width="2" stroke-linejoin="round"/>
                    <path d="M9 21V13H15V21" stroke="var(--brand-color)" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="stat-title"><?php echo ($texts[$lang]['stat1Title']); ?></div>
            <div class="stat-sub"><?php echo ($texts[$lang]['stat1Sub']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="9" stroke="var(--brand-color)" stroke-width="2"/>
                    <path d="M3 12H21M12 3C14.5 5.7 15.8 8.8 15.8 12C15.8 15.2 14.5 18.3 12 21C9.5 18.3 8.2 15.2 8.2 12C8.2 8.8 9.5 5.7 12 3Z" stroke="var(--brand-color)" stroke-width="2"/>
                </svg>
            </div>
            <div class="stat-title"><?php echo ($texts[$lang]['stat2Title']); ?></div>
            <div class="stat-sub"><?php echo ($texts[$lang]['stat2Sub']); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap">
                <svg class="stat-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 3L14.6 8.6L21 9.3L16.3 13.5L17.6 20L12 16.8L6.4 20L7.7 13.5L3 9.3L9.4 8.6L12 3Z" stroke="var(--brand-color)" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </div>
            <div class="stat-title"><?php echo ($texts[$lang]['stat3Title']); ?></div>
            <div class="stat-sub"><?php echo ($texts[$lang]['stat3Sub']); ?></div>
        </div>
    </div>
</section>

<!-- FEATURES SLIDER -->
<section class="features-wrap reveal">
    <div class="features container">
        <div id="featuresCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000" data-bs-pause="hover">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="slide-inner">
                        <div>
                            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        						<!-- Top circle -->
        						<circle cx="12" cy="6" r="3" style="stroke: var(--brand-color); fill: none;" stroke-width="2"/>
        						<!-- Bottom left circle -->
        						<circle cx="6" cy="18" r="3" style="stroke: var(--brand-color);" stroke-width="2" fill="none"/>
        						<!-- Bottom right circle -->
        						<circle cx="18" cy="18" r="3" style="stroke: var(--brand-color);" stroke-width="2" fill="none"/>
        						<!-- Left diagonal line -->
        						<line x1="11" y1="9" x2="7" y2="15" style="stroke: var(--brand-color);" stroke-width="2"/>
        						<!-- Right diagonal line -->
        						<line x1="13" y1="9" x2="17" y2="15" style="stroke: var(--brand-color);" stroke-width="2"/>
      			    		</svg>
                            <div class="feature-title"><?php echo ($texts[$lang]['sponsor']) ?></div>
                            <div class="feature-sub"><?php echo ($texts[$lang]['dsponsor']) ?></div>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="slide-inner">
                        <div>
                            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        						<!-- Top circle -->
        						<circle cx="12" cy="6" r="3" style="stroke: var(--brand-color); fill: none;" stroke-width="2"/>
        						<!-- Bottom left circle -->
        						<circle cx="6" cy="18" r="3" style="stroke: var(--brand-color);" stroke-width="2" fill="none"/>
        						<!-- Bottom right circle -->
        						<circle cx="18" cy="18" r="3" style="stroke: var(--brand-color);" stroke-width="2" fill="none"/>
        						<!-- Left diagonal line -->
        						<line x1="11" y1="9" x2="7" y2="15" style="stroke: var(--brand-color);" stroke-width="2"/>
        						<!-- Right diagonal line -->
        						<line x1="13" y1="9" x2="17" y2="15" style="stroke: var(--brand-color);" stroke-width="2"/>
      			    		</svg>

                            <div class="feature-title"><?php echo ($texts[$lang]['binary']) ?></div>
                            <div class="feature-sub"><?php echo ($texts[$lang]['dbinary']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="carousel-item">
                    <div class="slide-inner">
                        <div>
                            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        						<!-- Top circle -->
        						<circle cx="12" cy="6" r="3" style="stroke: var(--brand-color); fill: none;" stroke-width="2"/>
        						<!-- Bottom left circle -->
        						<circle cx="6" cy="18" r="3" stroke="var(--brand-color)" stroke-width="2" fill="var(--brand-color)"/>
        						<!-- Bottom right circle -->
        						<circle cx="18" cy="18" r="3" style="stroke: var(--brand-color);" stroke-width="2" fill="var(--brand-color)"/>
        						<!-- Left diagonal line -->
        						<line x1="11" y1="9" x2="7" y2="15" style="stroke: var(--brand-color);" stroke-width="2"/>
        						<!-- Right diagonal line -->
        						<line x1="13" y1="9" x2="17" y2="15" style="stroke: var(--brand-color);" stroke-width="2"/>
      			    		</svg>
                            <div class="feature-title"><?php echo ($texts[$lang]['matching']) ?></div>
                            <div class="feature-sub"><?php echo ($texts[$lang]['dmatching']) ?></div>
                        </div>
                    </div>
                </div>
            
                <div class="carousel-item">
                    <div class="slide-inner">
                        <div>
                            <svg viewBox="0 0 40 50" width="164" height="164" class="feature-icon" aria-hidden="true">
    							<!-- Top circle -->
  								<circle cx="20" cy="6" r="3" stroke="var(--brand-color)" stroke-width="2" fill="none"/>

  								<!-- Vertical line -->
  								<line x1="20" y1="9" x2="20" y2="21" stroke="var(--brand-color)" stroke-width="2"/>

  <!-- Bottom circle -->
  <circle cx="20" cy="24" r="3" stroke="var(--brand-color)" stroke-width="2" fill="none"/>

  <!-- Left label -->
  <text x="10" y="17" text-anchor="middle" font-size="6" fill="var(--brand-color)">20</text>
</svg>


                            <div class="feature-title"><?php echo ($texts[$lang]['uni']) ?></div>
                            <div class="feature-sub"><?php echo ($texts[$lang]['duni']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#featuresCarousel" data-bs-slide="prev" aria-label="Previous slide">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#featuresCarousel" data-bs-slide="next" aria-label="Next slide">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>

            <div class="carousel-indicators mt-3">
                <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="0" class="active" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                <button type="button" data-bs-target="#featuresCarousel" data-bs-slide-to="3" aria-label="Slide 4"></button>
            </div>
        </div>
    </div>
</section>

<!-- CONTENT -->
<div class="content-section reveal" id="content">
    <h1 class="reveal">👋 <?php echo isset($_navUserName) ? $texts[$lang]['Welcome'] . $_navUserName : 'Welcome to Future X'; ?></h1>
    <p class="lead reveal"><?php echo ($texts[$lang]['page']) ?></p>
    <?php if (isset($_SESSION["user_id"])): ?>
       <div class="reveal">
    		<a href="products.php" class="btn-gradient mt-4">
        	<?php echo ($texts[$lang]['btnproduct']); ?>
    		</a>
		</div>
	<?php else: ?>
    	<div class="reveal">
            <a href="index.php" class="btn-gradient mt-4">
                <?php echo ($texts[$lang]['login2']); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="footer-min reveal">
    <span class="small-muted">V22.4</span>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Scroll down button
    const scrollDown = document.getElementById("scrollDown");
    if (scrollDown) {
        scrollDown.addEventListener("click", () => {
            const target = document.getElementById("features");
            if (target) target.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }

    // Hero entrance
    window.addEventListener('DOMContentLoaded', () => {
        document.body.classList.add('hero-loaded');
    });

    // Reveal on scroll
    const revealEls = document.querySelectorAll('.reveal');
    const io = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.12 });
    revealEls.forEach(el => io.observe(el));
</script>
</body>
</html>
