<?php
include 'database.php';

$texts = [
    'th' => [
        'title'           => 'เกี่ยวกับ - Future X',
        'home'            => 'หน้าหลัก',
        'product'         => 'สินค้า',
        'cart'            => 'ตะกร้าสินค้า',
        'orders'          => 'รายการที่สั่ง',
        'about'           => 'เกี่ยวกับพวกเรา',
        'source'          => 'แหล่งที่มา',
        'profile'         => 'แก้ไขโปรไฟล์',
        'out'             => 'ออกจากระบบ',
        'login'           => 'กรุณาเข้าสู่ระบบเพื่อแก้ไขโปรไฟล์ของคุณ'
    ],
    
    'en' => [
        'title'           => 'About Us - Future X',
        'home'            => 'Home',
        'product'         => 'Products',
        'cart'            => 'Shopping Cart',
        'orders'          => 'Orders',
        'about'           => 'About Us',
        'source'          => 'Sources',
        'profile'         => 'Edit Profile',
        'out'             => 'Log Out',
        'login'           => 'Please log in to access your profile.'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $texts[$lang]['title']; ?></title>
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
            --brand-hover-deep:#003f7f;
            --gray-color: #ccc;
        }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: #1F2937;
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
  	    transition: transform .15s ease, background .2s ease, opacity .15s ease;
	}
	.lang-btn:hover{
  	    background: rgba(255,255,255,0.28);
  	    transform: translateY(-1px);
	}

        .spacer-nav { height: 20px; }

        .content-section {
            text-align: left;
            padding: 30px 20px 30px 20px; /* ✅ equal top & bottom padding */
            max-width: 900px;
            margin: auto;
        }
        .content-section h1 {
            font-size: 2.4rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--brand-color); /* ✅ red heading */
            text-align: center;
        }
        .content-section p.lead {
            font-size: 1.05rem;
            margin-bottom: 20px;
            color: #374151;
            text-align: center;
        }

        /* Card style */
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
        .credits p {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #374151;
            margin-bottom: 12px;
        }
        .credits ul {
            margin-bottom: 8px;
        }
        .credits li {
            margin: 6px 0;
        }
        .values-list {
            text-align: left;
            list-style-position: inside;
        }
        .divider {
            height: 1px;
            background: rgba(0,0,0,0.07);
            margin: 20px 0;
        }
        .footer-min {
            margin-top: 10px;
            font-size: 0.95rem;
            color: #4B5563;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <div class="spacer-nav"></div>

    <!-- Main Content -->
    <div class="content-section">
        <h1><?php echo ($texts[$lang]['about']) ?></h1>
        <p class="lead">
            <?php echo ($lang === 'en') ? 'We are an MLM company that imports products from a factory in Malaysia called harvest group and sell it in Thailand.' : 'เราเป็นบริษัท MLM ที่นำเข้าสินค้าจากโรงงานในมาเลเซียที่มีชื่อว่า Harvest Group และจำหน่ายในประเทศไทย'; ?>
        </p>

        <!-- Card wrapper -->
        <div class="credits">
            <h2><?php echo ($lang === 'en') ? 'Our Mission' : 'เป้าหมายของเรา'; ?></h2>
            <p>
                <?php echo ($lang === 'en') ? 'Coming Soon' : 'Coming Soon'; ?>
            </p>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'Our Vision' : 'มุมมองของเรา'; ?></h2>
           
            <p>
                <?php echo ($lang === 'en') ? 'Coming Soon' : 'Coming Soon'; ?>
            </p>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'Our Values' : 'คุณภาพของเรา'; ?></h2>
            <ul class="values-list">
                <li><strong><?php echo ($lang === 'en') ? 'Product Distribution' : 'การกระจายสินค้า'; ?></strong><?php echo ($lang === 'en') ? ' — We have 17 Warehouse across Thailand.' : ' — เรามี 17 คลังสินค้าทั่วไทย'; ?></li>
                <li><strong><?php echo ($lang === 'en') ? 'Products' : 'สินค้า'; ?></strong> — <?php echo ($lang === 'en') ? ' Our products are innovative, high quality, and affordable.' : ' สินค้านวัตกรรม คุณภาพสูง ราคาไม่แพง'; ?></li>
                <li><strong><?php echo ($lang === 'en') ? 'Leadership' : 'การบริหาร'; ?></strong> — <?php echo ($lang === 'en') ? 'We have an experienced leader.' : 'ผู้บริหารมีประสบการณ์'; ?></li>
            </ul>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'History & Background' : 'ประวัติ และ พื้นหลัง'; ?></h2>
            <p>Details coming soon.</p>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'Team' : 'ทีม'; ?></h2>
            <p class="mb-1"><strong><?php echo ($lang === 'en') ? 'Owner' : 'เจ้าของ'; ?></strong></p>
            <p class="mb-2"><?php echo ($lang === 'en') ? "Dato' Dr. Chong Kak Loong (Chair Man)" : 'ดาโต้ ชอง (ประธาน)'; ?></p>
            <p class="mb-2"><?php echo ($lang === 'en') ? 'Pimchaya Wattanakulyothin 
            (Vice President)' : 'คุณ พิมพ์ชญา วัฒนกุลโยธิน (รองประธาน)'; ?></p>
            <p class="mb-1"><strong><?php echo ($lang === 'en') ? 'Website Programmer 
(futurexthailand.com)': 'โปรแกรมเมอร์ (futurexthailand.com)'; ?></strong></p>
            <p class="mb-2"><?php echo ($lang === 'en') ? 'Decha Thanajitchai' : 'เดชา ธนจิตชัย'; ?></p>
            <p class="mb-0">ChatGPT</p>

            <div class="divider"></div>

            <h2><?php echo ($lang === 'en') ? 'Why choose Future X' : 'ทำไมถึงต้องเลือก Future X'; ?></h2>
            <ul>
                <li><?php echo ($lang === 'en') ? 'We have a large factory in Malaysia and Indonesia.' : 'เรามีโรงงานขนาดใหญ่ที่มาเลเซีย และ อินโดนีเซีย'; ?></li>
                <li><?php echo ($lang === 'en') ? 'The center of our manufactory and distribution of products are our own.' : 'จุด Center การผลิต และ การกระจายสินค้าเป็นของตัวเอง'; ?></li>
                <li><?php echo ($lang === 'en') ? 'Products are innovative, high quality, and able to compete in the online market.' : 'สินค้านวัตกรรม คุณภาพสูง ราคาแข่งขันกับตลาดออนไลน์ได้'; ?></li>
                <li><?php echo ($lang === 'en') ? 'Experienced Leader' : 'ผู้บริหารมีประสบการณ์'; ?></li>
                <li><?php echo ($lang === 'en') ? 'A business that’s easy to start and truly achievable.' : 'ธุรกิจเริ่มต้นง่ายทำได้จริง'; ?></li>
            </ul>

            <div class="footer-min">
                <?php echo ($lang === 'en') ? 'Thank you for your interest in Future X.' : 'ขอบคุณที่ท่านเลื่อก Future X'; ?>
            </div>
        </div>
    </div>

</body>
</html>