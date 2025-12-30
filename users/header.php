<?php
if (!isset($_SESSION)) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    if (!isset($_SESSION['nama']) || trim($_SESSION['nama']) === '') {
        $user_name = 'User';
    } else {
        $user_name = ($_SESSION['nama']);
    }
} else {
    $user_name = '';
}

$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page) {
    global $current_page;
    return $current_page === $page ? 'active' : '';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - YogyaStay' : 'YogyaStay'; ?></title>
       <link rel="icon" type="image/jpeg" href="../assets/img/logonw.png">

    <!-- GOOGLE FONT : POPPINS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #F5F5F5;
        }

        /* HEADER CONTAINER */
        .header-wrapper {
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 3px 12px rgba(0,0,0,0.15);
        }

        /* HEADER */
        .main-header {
            background: #F6B049;
            height: 75px;
            padding: 0 clamp(20px, 4vw, 64px);
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            position: relative;
        }

        /* LOGO */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 21px;
            font-weight: 700;
            color: #FFFFFF;
            white-space: nowrap;
        }

        .logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        /* NAVIGATION */
        .nav-center {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: clamp(36px, 5vw, 52px);
        }

        /* MENU LINK */
        .menu-link {
            position: relative;
            font-size: 15px;
            font-weight: 600;
            color: #1F2937;
            text-decoration: none;
            padding: 4px 0;
            transition: color 0.25s ease;
            white-space: nowrap;
        }

        .menu-link::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: -6px;
            width: 0;
            height: 2px;
            background: #FFFFFF;
            border-radius: 2px;
            transition: width 0.25s ease;
        }

        .menu-link:hover {
            color: #FFFFFF;
        }

        .menu-link:hover::after {
            width: 100%;
        }

        .menu-link.active {
            color: #FFFFFF;
        }

        .menu-link.active::after {
            width: 100%;
        }

        .nav-right {
            display: flex;
            align-items: center;
        }

        /* BUTTON LOGIN */
        .btn-auth {
            background: #929FC1;
            color: #FFFFFF;
            padding: 7px 18px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            white-space: nowrap;
            border: none;
            cursor: pointer;
        }

        .btn-auth:hover {
            background: #7F8CB2;
            box-shadow: 0 4px 14px rgba(0,0,0,0.2);
            transform: translateY(-1px);
        }

        .user-icon svg {
            width: 18px;
            height: 18px;
        }

        /* HAMBURGER */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 8px;
            background: transparent;
            border: none;
            transition: transform 0.3s ease;
        }

        .hamburger span {
            width: 26px;
            height: 3px;
            background: #FFFFFF;
            border-radius: 3px;
            transition: all 0.3s ease;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(7px, 7px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* MOBILE NAVIGATION */
        .mobile-nav {
            display: none;
            flex-direction: column;
            background: #F6B049;
            width: 100%;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .mobile-nav.show {
            display: flex;
            max-height: 500px;
        }

        .mobile-nav a {
            padding: 16px clamp(20px, 4vw, 64px);
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            color: #1F2937;
            transition: all 0.25s ease;
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }

        .mobile-nav a:hover,
        .mobile-nav a.active {
            background: rgba(255,255,255,0.2);
            color: #FFFFFF;
            padding-left: calc(clamp(20px, 4vw, 64px) + 10px);
        }

        .mobile-divider {
            height: 1px;
            background: rgba(0,0,0,0.15);
            margin: 8px 0;
        }

        .mobile-profile-btn {
            margin: 16px clamp(20px, 4vw, 64px) 20px;
            justify-content: center;
            border: none;
        }

        .mobile-profile-btn:hover {
            padding-left: clamp(20px, 4vw, 64px);
        }

        /* RESPONSIVE */
        @media (max-width: 1024px) {
            .nav-center {
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .main-header {
                height: 65px;
            }

            .nav-center {
                gap: 28px;
            }

            .menu-link {
                font-size: 14px;
            }
        }

        @media (max-width: 640px) {
            .main-header {
                height: 60px;
                grid-template-columns: 1fr auto 1fr;
                justify-items: center;
            }

            .nav-center,
            .nav-right {
                display: none;
            }

            .hamburger {
                display: flex;
                justify-self: start;
                order: -1;
            }

            .logo {
                font-size: 18px;
                justify-self: center;
            }

            .logo img {
                width: 28px;
                height: 28px;
            }
        }
    </style>
</head>
<body>

<!-- HEADER WRAPPER -->
<div class="header-wrapper">
    <!-- HEADER -->
    <header class="main-header">
        <!-- LOGO -->
        <a href="beranda.php" class="logo">
            <img src="../assets/img/logo.png" alt="YogyaStay Logo">
            <span>YogyaStay</span>
        </a>

        <!-- NAVIGASI DESKTOP -->
        <nav class="nav-center">
            <a href="../users/beranda.php" class="menu-link <?= isActive('beranda.php') ?>">Dashboard</a>
            <a href="../users/blog.php" class="menu-link <?= isActive('blog.php') ?>">Blog</a>
            <a href="../users/checkin_online.php" class="menu-link <?= isActive('checkin_online.php') ?>">Check-in Online</a>
        </nav>

        <!-- LOGIN / USER -->
        <div class="nav-right">
            <?php if ($is_logged_in): ?>
                <a href="profil.php" class="btn-auth">
                    <?= htmlspecialchars($user_name); ?>
                    <span class="user-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                        </svg>
                    </span>
                </a>
            <?php else: ?>
                <a href="../autentikasi/login.php" class="btn-auth">
                    Masuk / Daftar
                    <span class="user-icon">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                        </svg>
                    </span>
                </a>
            <?php endif; ?>
        </div>

        <!-- HAMBURGER (MOBILE) -->
        <button class="hamburger" id="hamburgerBtn" onclick="toggleMobileNav()" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
    </header>

    <!-- MOBILE NAVIGATION -->
    <nav class="mobile-nav" id="mobileNav">
        <a href="../users/beranda.php" class="<?= isActive('beranda.php') ?>">Dashboard</a>
        <a href="../users/blog.php" class="<?= isActive('blog.php') ?>">Blog</a>
        <a href="../users/checkin_online.php" class="<?= isActive('checkin_online.php') ?>">Check-in Online</a>

        <div class="mobile-divider"></div>

        <?php if ($is_logged_in): ?>
            <a href="profil.php" class="btn-auth mobile-profile-btn">
                <?= htmlspecialchars($user_name); ?>
                <span class="user-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                    </svg>
                </span>
            </a>
        <?php else: ?>
            <a href="../autentikasi/login.php" class="btn-auth mobile-profile-btn">
                Masuk / Daftar
                <span class="user-icon">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5zm0 2c-3.3 0-10 1.7-10 5v3h20v-3c0-3.3-6.7-5-10-5z"/>
                    </svg>
                </span>
            </a>
        <?php endif; ?>
    </nav>
</div>

<script>
function toggleMobileNav() {
    const mobileNav = document.getElementById('mobileNav');
    const hamburger = document.getElementById('hamburgerBtn');
    
    mobileNav.classList.toggle('show');
    hamburger.classList.toggle('active');
}

// Close Mobile Nav when Clicking Outside
document.addEventListener('click', function(event) {
    const mobileNav = document.getElementById('mobileNav');
    const hamburger = document.getElementById('hamburgerBtn');
    const isClickInsideNav = mobileNav.contains(event.target);
    const isClickOnHamburger = hamburger.contains(event.target);
    
    if (!isClickInsideNav && !isClickOnHamburger && mobileNav.classList.contains('show')) {
        mobileNav.classList.remove('show');
        hamburger.classList.remove('active');
    }
});

// Close Mobile Nav on Window Resize
window.addEventListener('resize', () => {
    if (window.innerWidth > 640) {
        const mobileNav = document.getElementById('mobileNav');
        const hamburger = document.getElementById('hamburgerBtn');
        mobileNav.classList.remove('show');
        hamburger.classList.remove('active');
    }
});
</script>

</body>
</html>