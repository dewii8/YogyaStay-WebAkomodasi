<?php
// ===============================
// HEADER GLOBAL YogyaStay
// ===============================
if (!isset($_SESSION)) {
    session_start();
}

$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? ($_SESSION['nama'] ?? 'User') : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - YogyaStay' : 'YogyaStay'; ?></title>

    <!-- GOOGLE FONT : POPPINS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* ================= RESET ================= */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }

        /* ================= HEADER ================= */
        .main-header {
            background: #F6B049;
            height: 68px;
            padding: 0 clamp(20px, 4vw, 64px);
            display: grid;
            grid-template-columns: auto 1fr auto;
            align-items: center;
            box-shadow: 0 3px 12px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        /* ================= LOGO ================= */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            font-size: 21px;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
        }

        .logo img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        /* ================= NAV CENTER ================= */
        .nav-center {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: clamp(36px, 5vw, 52px);
        }

        /* ================= MENU LINK ================= */
        .menu-link {
            position: relative;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
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
            background: #ffffff;
            border-radius: 2px;
            transition: width 0.25s ease;
        }

        .menu-link:hover {
            color: #ffffff;
        }

        .menu-link:hover::after {
            width: 100%;
        }

        /* ================= RIGHT AREA ================= */
        .nav-right {
            display: flex;
            align-items: center;
        }

        /* ================= AUTH BUTTON ================= */
        .btn-auth {
            background: #929FC1;
            color: #ffffff;
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
        }

        .btn-auth:hover {
            background: #7f8cb2;
            box-shadow: 0 4px 14px rgba(0,0,0,0.2);
            transform: translateY(-1px);
        }

        .user-icon svg {
            width: 18px;
            height: 18px;
        }

        /* ================= RESPONSIVE ================= */
        @media (max-width: 1024px) {
            .nav-center {
                gap: 40px;
            }
        }

        @media (max-width: 768px) {
            .main-header {
                height: 60px;
            }

            .nav-center {
                gap: 28px;
            }

            .menu-link {
                font-size: 14px;
            }
        }

        @media (max-width: 640px) {
            .nav-center {
                display: none; /* siap dikembangkan ke hamburger */
            }
        }
    </style>
</head>
<body>

<!-- ================= HEADER ================= -->
<header class="main-header">

    <!-- LOGO -->
    <a href="beranda.php" class="logo">
        <img src="assets/img/logo.png" alt="YogyaStay Logo">
        <span>YogyaStay</span>
    </a>

    <!-- NAVIGASI -->
    <nav class="nav-center">
        <a href="beranda.php" class="menu-link">Beranda</a>
        <a href="blog.php" class="menu-link">Blog</a>
        <a href="checkin.php" class="menu-link">Check-in Online</a>
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

</header>
