<?php
session_start();
include 'database.php';

$welcomeText = "Welcome to Canasia";
$profilePicture = "avatar.png"; // default fallback

if (isset($_SESSION["user_id"])) {
    $userId = (int)$_SESSION["user_id"];
    $sql = "SELECT name, profile_picture FROM users WHERE id = $userId";
    $result = $conn->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
        $welcomeText = "Welcome to Canasia, " . htmlspecialchars($row["name"]);
        if (!empty($row["profile_picture"])) {
            $profilePicture = "uploads/profile_pics/" . htmlspecialchars($row["profile_picture"]);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Products - Canasia</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="icon" href="favicon.png" type="image/png">
    <style>
        :root {
            --brand-color: #3B82F6;
            --brand-hover: #2563EB;
            --gray-color: #ccc;
        }
        body {
            background-color: #f0f9f0;
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
            background-color: white;
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
        .nav-links::-webkit-scrollbar {
            display: none;
        }
        .nav-scroll-indicator {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            background: #333;
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
            transition: background 0.3s;
        }
        .top-banner.scrolled .nav-links a {
            color: #333;
        }
        .nav-links a:hover {
            background-color: rgba(255,255,255,0.15);
        }
        .profile-dropdown {
            position: relative;
            flex-shrink: 0;
            margin-right: 12px;
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
        .profile-dropdown-content a:hover {
            background-color: #f2f2f2;
            color: #333;
        }
        .profile-img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
            background-color: var(--gray-color);
            border: none;
        }
        .logo-container {
            background-color: var(--brand-color);
            width: 100%;
            height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .logo-box {
            width: 90%;
            max-width: 1000px;
            aspect-ratio: 19 / 6;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .logo-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .scroll-down {
            position: absolute;
            bottom: 4%;
            width: 60px;
            height: 60px;
            cursor: pointer;
            animation: bounce 1.5s infinite;
        }
        .scroll-down svg {
            width: 100%;
            height: 100%;
        }
        .scroll-down svg polyline {
            stroke: white;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-12px); }
            60% { transform: translateY(-6px); }
        }
        .content-section {
            text-align: center;
            padding: 60px 20px 30px 20px;
            margin-top: -20px;
            color: #1F2937;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        .content-section h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
            color: #2563EB;
        }
        .content-section p.lead {
            font-size: 1.3rem;
            font-weight: 500;
            margin-bottom: 32px;
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
            font-size: 1.2rem;
            color: #2563EB;
            text-transform: uppercase;
        }
        .export-image {
            max-width: 280px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.25);
        }
        .btn-gradient {
            display: inline-block;
            background: linear-gradient(135deg, var(--brand-color), var(--brand-hover));
            color: #fff;
            padding: 14px 28px;
            font-size: 1.3rem;
            font-weight: 700;
            border-radius: 20px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="top-banner" id="topBanner">
        <div class="nav-links-container" id="navLinksContainer">
            <div class="nav-scroll-indicator" id="navScrollIndicator"></div>
            <div class="nav-links" id="navLinks">
                <a href="home.php">Home</a>
                <a href="products.php">Products</a>
                <a href="cart.php">Shopping Cart</a>
                <a href="about.php">About Us</a>
                <a href="source.php">Source</a>
            </div>
        </div>
        <div class="profile-dropdown">
            <img src="<?php echo file_exists($profilePicture) ? $profilePicture : 'avatar.png'; ?>" alt="Profile" class="profile-img" id="profileIcon">
            <div id="dropdownMenu" class="profile-dropdown-content">
                <?php if (isset($_SESSION["user_id"])): ?>
                    <a href="profile.php">Edit Profile</a>
                    <a href="logout.php">Log Out</a>
                <?php else: ?>
                    <a href="index.php">Please log in to access your profile.</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="content-section">
        <h1>Products</h1>
        <!-- TODO: Add your product listings here -->
    </div>

    <script>
        const profileIcon = document.getElementById("profileIcon");
        const dropdownMenu = document.getElementById("dropdownMenu");
        profileIcon.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === "block" ? "none" : "block";
        });
        document.addEventListener("click", () => { dropdownMenu.style.display = "none"; });

        const topBanner = document.getElementById("topBanner");
        window.addEventListener("scroll", () => {
            topBanner.classList.toggle("scrolled", window.scrollY > 10);
        });

        const navLinksContainer = document.getElementById("navLinksContainer");
        const navScrollIndicator = document.getElementById("navScrollIndicator");

        function updateScrollIndicator() {
            const maxScroll = navLinksContainer.scrollWidth - navLinksContainer.clientWidth;
            const currentScroll = navLinksContainer.scrollLeft;
            const progress = (currentScroll / maxScroll) * 100;
            navScrollIndicator.style.width = progress + "%";
        }

        navLinksContainer.addEventListener("scroll", updateScrollIndicator);
        window.addEventListener("resize", updateScrollIndicator);
        updateScrollIndicator();
    </script>
</body>
</html>
