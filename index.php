<?php
session_start();
include 'database.php';

if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit();
}

// Define translations
$texts = [
    'en' => [
	'title'    => 'Index Page - Future X',
        'welcome'  => 'Welcome to',
        'register' => 'Register Here',
        'login'    => 'Log In Here',
        'guest'    => 'Continue as Guest',
        'redirect' => 'Log In at Official Website',
        'klang'    => 'Log In<wbr> to<wbr> 7<wbr> Warehouse<wbr> (Nakhon Ratchasima)',
        'lang'     => 'ภาษาไทย',
        'register_klang'    => 'Register<wbr> for<wbr> 7<wbr> Warehouse<wbr> (Nakhon Ratchasima)',
        'register_official' => 'Register at Official Website'
    ],
    'th' => [
        'title'    => 'หน้าเข้าสู่เว็บไซต์ - Future X',
        'welcome'  => 'ยินดีต้อนรับสู่',
        'register' => 'ลงทะเบียน',
        'login'    => 'เข้าสู่ระบบ',
        'guest'    => 'เข้าสู่เว็บไซต์โดยไม่ลงชื่อ',
        'redirect' => 'เข้าสู่ระบบที่เว็บไซต์ทางการ',
        'klang'    => 'เข้าสู่ระบบที่คลัง<wbr> 7<wbr> (นครราชสีมา)',
        'lang'     => 'English',
        'register_klang'    => 'ลงทะเบียนสำหรับคลัง<wbr> 7<wbr> (นครราชสีมา)',
        'register_official' => 'ลงทะเบียนที่เว็บไซต์ทางการ'
    ]
];

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
  <meta charset="UTF-8">
  <title><?php echo $texts[$lang]['title']; ?></title>
  <link rel="icon" type="image/png" href="logo_transparent_onlyblack.png">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
        :root{
            --brand-color:#007BFF;
            --brand-hover:#0056b3;
            --brand-hover-deep:#003f7f;
            --ink:#111111;
        }

        /* Dropdown menu styling */
		#loginDropdown + .dropdown-menu,
      	#registerDropdown + .dropdown-menu {
            background-color: transparent !important;
            padding: 0 !important; 
            overflow: hidden;
            border-radius: 12px;  
            border: none;
            margin-top: 10px !important;
      }
      
      /* 2) Default item row color (fills full width) */
#loginDropdown + .dropdown-menu .dropdown-item,
#registerDropdown + .dropdown-menu .dropdown-item {
  background-color: var(--brand-color);
}

      /* 3) Full-row hover state */
#loginDropdown + .dropdown-menu .dropdown-item:hover,
#loginDropdown + .dropdown-menu .dropdown-item:focus,
#registerDropdown + .dropdown-menu .dropdown-item:hover,
#registerDropdown + .dropdown-menu .dropdown-item:focus {
  background-color: var(--brand-hover) !important;
}


		/* Dropdown item text */
		.dropdown-item {
			color: #fff !important;
			font-weight: 600;
			padding: 10px 16px;
			transition: background 0.3s ease;
		}

		/* Hover effect */
		.dropdown-item:hover,
		.dropdown-item:focus {
			background-color: var(--brand-hover) !important;
			color: #fff !important;
		}

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #E6F0FF, #CCE0FF, #FFFFFF);
            color: var(--ink);
            padding: 40px 20px;
            position: relative;
        }

        /* Language switch button */
        .lang-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(255, 255, 255, 0.7);
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
	    	color: var(--brand-color);
        }
        .lang-switch:hover {
            background: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
        }

        .welcome-container {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 40px 30px;
            max-width: 462px;
            width: 95%;
            text-align: center;
            box-shadow: 0 12px 32px rgba(204, 0, 0, 0.15);
            position: relative;
            z-index: 10;
        }

        .welcome-container h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--ink);
        }

        .logo-wrapper {
            position: relative;
            display: inline-block;
            padding: 10px;
        }

        .logo-wrapper::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: min(260px, 90vw);
            height: min(260px, 90vw);
            background: radial-gradient(circle, rgba(0,123,255,0.22) 0%, rgba(0,123,255,0) 70%);
            border-radius: 50%;
            z-index: 0;
            pointer-events: none;
        }

        .logo {
            max-width: 210px;
            height: auto;
            position: relative;
            z-index: 1;
        }

        .btn-modern {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            margin-bottom: 14px;
            padding: 14px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 14px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .btn-modern.btn-primary {
            background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
            border: none;
            color: #fff;
        }
        .btn-modern.btn-primary:hover {
            background: linear-gradient(135deg, var(--brand-hover), var(--brand-hover-deep));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 204, 0.35);
        }

        .btn-modern.btn-secondary {
            background: rgba(255, 255, 255, 0.7);
            color: var(--ink);
            border: none;
            box-shadow: inset 0 0 8px rgba(255, 255, 255, 0.7);
        }
        .btn-modern.btn-secondary:hover {
            background: rgba(255, 255, 255, 0.85);
            box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
	/* ✅ use the real IDs/classes you have */
  </style>
</head>
<body>
    <!-- Language switch button -->
    <a class="lang-switch" href="?lang=<?php echo $lang === 'en' ? 'th' : 'en'; ?>">
        <?php echo $texts[$lang]['lang']; ?>
    </a>

    <div class="welcome-container">
        <h1><?php echo $texts[$lang]['welcome']; ?></h1>
        <div class="logo-wrapper">
            <img src="logo_transparent.png" alt="Future X Logo" class="logo">
        </div>

		<div class="dropdown mb-3">
		  <button class="btn btn-modern btn-primary dropdown-toggle w-100" type="button"
		          id="registerDropdown" data-bs-toggle="dropdown" aria-expanded="false">
		    <?php echo $texts[$lang]['register']; ?>
		  </button>
		  <ul class="dropdown-menu w-100 text-center" aria-labelledby="registerDropdown">
		    <li><a class="dropdown-item" href="register.php"><?php echo $texts[$lang]['register_klang']; ?></a></li>
		    <li><a class="dropdown-item" href="https://mbo.futurex.today/registrationpublic.aspx?UplineCode=Dr.Khai&Position=0"><?php echo $texts[$lang]['register_official']; ?></a></li>
		  </ul>
		</div>
        <!-- Login dropdown -->
        <div class="dropdown mb-3">
            <button class="btn btn-modern btn-primary dropdown-toggle w-100" type="button" id="loginDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo $texts[$lang]['login']; ?>
            </button>
            <ul class="dropdown-menu w-100 text-center" aria-labelledby="loginDropdown">
                <li>
                    <a class="dropdown-item" href="login.php"><?php echo $texts[$lang]['klang']; ?></a>
                </li>
                <li>
                    <a class="dropdown-item" href="https://mbo.futurex.today/Login.aspx"><?php echo $texts[$lang]['redirect']; ?></a>
                </li>
            </ul>
        </div>
        <a href="home.php" class="btn btn-modern btn-secondary"><?php echo $texts[$lang]['guest']; ?></a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
