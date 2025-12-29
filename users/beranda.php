<?php
require_once '../config.php';

// Cek session jika sudah login
$is_logged_in = false;
$user_name = '';

if (isset($_SESSION['user_id'])) {
    // Cek session timeout
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        
        if ($inactive_time > SESSION_TIMEOUT) {
            session_unset();
            session_destroy();
            if (isset($_COOKIE['remember_user'])) {
                setcookie('remember_user', '', time() - 3600, '/');
            }
        } else {
            $is_logged_in = true;
            $user_name = $_SESSION['nama'] ?: 'User';
            $_SESSION['last_activity'] = time();
        }
    } else {
        $is_logged_in = true;
        $user_name = $_SESSION['nama'] ?: 'User';
        $_SESSION['last_activity'] = time();
    }
}

// Query untuk mengambil 3 artikel terbaru
$artikel_query = mysqli_query($conn, "
    SELECT id_blog, judul, konten, thumbnail, tanggal_publish
    FROM blog
    WHERE status = 'publish'
    ORDER BY tanggal_publish DESC
    LIMIT 3
");

$page_title = 'Beranda';
include 'header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda YogyaStay</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: #FFF9F2;
            margin: 0;
            padding: 0;
        }
        
        /* HERO SECTION */
        .hero-section {
            width: 100%;
            min-height: 100vh;
            background:
                linear-gradient(
                rgba(255, 249, 242, 0.6),
                rgba(255, 249, 242, 0.6)
                ),
                url("../assets/img/jogja.jpg");
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 65px 20px 0px;
            box-sizing: border-box;
        }

        .hero-container {
            width: 100%;
            max-width: 1100px;
            margin: 0px auto;
            text-align: center;
        }

        .hero-title {
            font-size: 44px;
            font-weight: 700;
            color: #000;
            line-height: 1.25;
            margin-bottom: 18px;
        }

        .hero-brand {
            display: inline-block;
            margin-top: 6px;
            font-size: 40px;
            font-weight: 700;
            color: #FFF9F2;
            -webkit-text-stroke: 1.5px #F6B049;
        }

        .hero-subtitle {
            max-width: 820px;
            margin: 0 auto;
            font-size: 15px;
            color: #000000;
            line-height: 1.7;
        }

        .hero-title,
        .hero-brand,
        .hero-subtitle {
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .search-card {
            width: 100%;
            max-width: 1200px;
            margin: 60px auto 80px;
            background: #fff;
            border-radius: 22px;
            padding: 48px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
            border: 3px solid #F2D965;
            box-sizing: border-box;
        }

        .search-title {
            font-size: 34px;
            font-weight: bold;
            color: #929FC1;
            margin-bottom: 35px;
            text-align: center;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 22px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .form-group select,
        .form-group input,
        .guest-input {
            height: 54px;
            padding: 0 18px;
            border: 1.5px solid #ddd;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 400;
            background: white;
            transition: all 0.25s ease;
        }

        .form-group input::placeholder {
            color: #999;
        }

        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #929FC1;
            box-shadow: 0 0 0 3px rgba(232, 208, 111, 0.1);
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .select-icon {
            position: relative;
        }

        .select-icon select {
            width: 100%;
            height: 54px;
            padding: 0 44px 0 18px;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
        }

        .select-icon .right-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            color: #888;
            font-size: 18px;
        }

        .guest-selector {
            position: relative;
        }

        .guest-input {
            padding: 14px 18px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 15px;
            background: white;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .guest-input:hover {
            border-color: #E8D06F;
        }

        .guest-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            z-index: 100;
            display: none;
        }

        .guest-dropdown.active {
            display: block;
        }

        .guest-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .guest-row:last-child {
            border-bottom: none;
        }

        .guest-label {
            font-size: 15px;
            font-weight: 500;
            color: #333;
            flex: 1;
            text-align: left;
        }

        .guest-label small {
            display: block;
            font-size: 12px;
            color: #999;
            font-weight: 400;
        }

        .guest-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .guest-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #4A90E2;
            background: white;
            color: #4A90E2;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .guest-btn:hover:not(:disabled) {
            background: #4A90E2;
            color: white;
        }

        .guest-btn:disabled {
            border-color: #ddd;
            color: #ddd;
            cursor: not-allowed;
        }

        .guest-count {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            min-width: 30px;
            text-align: center;
        }

        .btn-search {
            background: #F2D965;
            color: #2B2B2B;
            padding: 16px 64px;
            border: none;
            border-radius: 14px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 0 auto;
            min-width: 260px;

            transition: 
                transform 0.25s ease,
                box-shadow 0.25s ease,
                background-color 0.25s ease,
                color 0.25s ease;
        }

        .btn-search:hover {
            background-color: #929FC1;
            color: #FFFFFF;
            transform: translateY(-3px);
            box-shadow: 
                0 10px 28px rgba(146, 159, 193, 0.45),
                inset 0 1px 0 rgba(255,255,255,0.35);
        }

        .btn-search:hover i,
        .btn-search:hover svg {
            color: #FFFFFF;
            fill: #FFFFFF;
        }

        .btn-search:active {
            transform: translateY(0);
        }

        .input-with-icon {
            position: relative;
            width: 100%;
        }

        .input-with-icon input {
            width: 100%;
            height: 54px;
            padding: 0 16px 0 44px;
            box-sizing: border-box;
        }

        .input-with-icon .left-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 18px;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .search-form {
                grid-template-columns: 1fr;
            }

            .hero-title {
                font-size: 30px;
            }

            .hero-brand {
                font-size: 28px;
            }

            .hero-subtitle {
                font-size: 14px;
                padding: 0 10px;
            }

            .search-card {
                padding: 30px 25px;
            }

            .form-group {
                width: 100%;
            }

            .input-with-icon input {
                height: 52px;
                font-size: 14px;
            }
        }

        @media (max-width: 992px) {
            .hero-section {
                padding: 70px 16px;
            }

            .search-form {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .hero-section {
                min-height: auto;
                padding: 60px 14px;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .search-card {
                padding: 26px 18px;
            }

            .form-group label {
                font-size: 13px;
            }
        }
        
        /* CONTENT */
        .content {
            padding: 60px 60px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .welcome-message {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 40px;
            border-left: 5px solid #E8D06F;
        }
        
        .welcome-message h2 {
            color: #E8D06F;
            margin-bottom: 15px;
            font-size: 28px;
            font-weight: 700;
        }
        
        .welcome-message p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s ease;
            border-top: 4px solid transparent;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            border-top-color: #E8D06F;
        }
        
        .feature-icon {
            font-size: 56px;
            margin-bottom: 20px;
            display: inline-block;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
        }
        
        .feature-card h3 {
            color: #8B9DC3;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: 700;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
            font-size: 15px;
        }

        /* ARTIKEL SECTION */
        .artikel-section {
            max-width: 1400px;
            margin: 60px auto;
            padding: 0 60px;
        }

        .artikel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
        }

        .artikel-title {
            font-size: 32px;
            font-weight: 700;
            color: #000;
        }

        .btn-lihat-semua {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 28px;
            background: linear-gradient(135deg, #8B9DC3 0%, #7A8DB5 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 157, 195, 0.3);
        }

        .btn-lihat-semua:hover {
            background: linear-gradient(135deg, #7A8DB5 0%, #6A7DA5 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 157, 195, 0.4);
        }

        .btn-lihat-semua i {
            transition: transform 0.3s ease;
        }

        .btn-lihat-semua:hover i {
            transform: translateX(4px);
        }

        .artikel-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .artikel-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .artikel-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }

        .artikel-card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .artikel-card-content {
            padding: 24px;
        }

        .artikel-card h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .artikel-card p {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .artikel-card .read-more {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
            color: #8B9DC3;
            text-decoration: none;
            transition: color 0.25s ease, transform 0.25s ease;
        }

        .artikel-card .read-more:hover {
            color: #E8D06F;
            transform: translateX(3px);
        }

        .artikel-card .read-more i {
            transition: transform 0.25s ease;
        }

        .artikel-card .read-more:hover i {
            transform: translateX(4px);
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .artikel-section {
                padding: 0 20px;
                margin: 40px auto;
            }

            .artikel-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }

            .artikel-title {
                font-size: 26px;
            }

            .artikel-grid {
                grid-template-columns: 1fr;
            }
        }

        /* FAQ SECTION */
        .faq-section {
            max-width: 1400px;
            margin: 60px auto 10px;
            background: white;
            padding: 40px 50px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.06);
        }

        .faq-title {
            font-size: 32px;
            font-weight: 700;
            color: #8B9DC3;
            margin-bottom: 30px;
            border-left: 6px solid #8B9DC3;
            padding-left: 12px;
        }

        .faq-item {
            margin-bottom: 18px;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e6e6e6;
            transition: all .25s ease;
        }

        .faq-item:hover {
            box-shadow: 0 6px 20px rgba(232, 208, 111, 0.15);
        }

        .faq-question {
            width: 100%;
            padding: 20px 26px;
            background: #E8D06F;
            border: none;
            outline: none;
            cursor: pointer;
            font-size: 17px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 14px;
            transition: all .3s ease;
        }

        .faq-question:hover {
            background: #8B9DC3;
            color: #FFFFFF;
        }

        .faq-question:hover svg {
            stroke: white;
        }

        .faq-icon {
            font-size: 22px;
            transition: transform .35s ease, color .3s ease;
            color: #6a5e2e;
        }

        .faq-item.active .faq-icon {
            transform: rotate(180deg);
            color: #FFFFFF;
        }

        .faq-answer {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: 
                max-height .5s ease,
                opacity .35s ease,
                padding .35s ease;
            background: #E9ECF5;
            padding: 0 26px;
            font-size: 16px;
            line-height: 1.65;
            color: #000000;
            border-top: 1px solid #f0f0f0;
        }

        .faq-item.active .faq-answer {
            padding: 18px 26px 22px;
            max-height: 400px;
            opacity: 1;
        }

        .faq-item.active .faq-question {
            background: #8B9DC3;
            color: white; 
        }

        .faq-item.active .faq-question svg {
            stroke: white;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .faq-section {
                margin: 40px 16px 60px;
                padding: 28px 24px;
                border-radius: 16px;
            }

            .faq-title {
                font-size: 24px;
                margin-bottom: 22px;
                border-left-width: 4px;
                padding-left: 10px;
            }

            .faq-question {
                padding: 16px 18px;
                font-size: 15px;
            }

            .faq-icon {
                font-size: 20px;
            }

            .faq-answer {
                font-size: 14px;
                line-height: 1.6;
                padding: 0 18px;
            }

            .faq-item.active .faq-answer {
                padding: 14px 18px 18px;
                max-height: 600px;
            }
        }

        @media (max-width: 480px) {
            .faq-section {
                margin: 32px 12px 50px;
                padding: 22px 18px;
                border-radius: 14px;
            }

            .faq-title {
                font-size: 20px;
                margin-bottom: 18px;
            }

            .faq-question {
                padding: 14px 16px;
                font-size: 14px;
                gap: 12px;
            }

            .faq-icon {
                font-size: 18px;
            }

            .faq-answer {
                font-size: 13.5px;
                line-height: 1.55;
            }
        }
    </style>
</head>
<body>
    <!-- HERO SECTION -->
    <section class="hero-section">
        <div class="hero-container">
            <h1 class="hero-title">
                Jelajahi Indahnya Yogyakarta bersama<br>
                <span class="hero-brand">YogyaStay</span>
            </h1>
            <p class="hero-subtitle">
                Akomodasi terbaik, lokasi strategis, dan layanan bintang lima di jantung D. I. Yogyakarta.
                Pesan sekarang dan rasakan pengalaman menginap tak terlupakan.
            </p>

            <div class="search-card">
                <h2 class="search-title">Cari Akomodasi Impian Anda</h2>
            
                <form class="search-form" id="searchForm" action="penginapan.php" method="GET">
                    <div class="form-group">
                        <label for="kabupaten">Kabupaten</label>
                        <div class="input-with-icon select-icon">
                            <select id="kabupaten" name="kabupaten">
                                <option value="">Semua</option>
                            </select>
                            <i class="bi bi-chevron-down right-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="kecamatan">Kecamatan</label>
                        <div class="input-with-icon select-icon">
                            <select id="kecamatan" name="kecamatan" disabled>
                                <option value="">Semua</option>
                            </select>
                            <i class="bi bi-chevron-down right-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="checkin">Check-in</label>
                        <div class="input-with-icon">
                            <i class="bi bi-calendar-event left-icon"></i>
                            <input
                                type="text"
                                id="checkin"
                                name="checkin"
                                placeholder="dd/mm/yyyy"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="checkout">Check-out</label>
                        <div class="input-with-icon">
                            <i class="bi bi-calendar-event left-icon"></i>
                            <input
                                type="text"
                                id="checkout"
                                name="checkout"
                                placeholder="dd/mm/yyyy"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Kamar dan Tamu</label>
                        <div class="guest-selector">
                            <div class="guest-input" id="guestInput">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <i class="bi bi-people-fill"></i>
                                    <span id="guestDisplay">1 Kamar, 1 Dewasa, 0 Anak</span>
                                </div>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                            <div class="guest-dropdown" id="guestDropdown">
                                <div class="guest-row">
                                    <div class="guest-label">Kamar</div>
                                    <div class="guest-controls">
                                        <button type="button" class="guest-btn" id="roomMinus">−</button>
                                        <span class="guest-count" id="roomCount">1</span>
                                        <button type="button" class="guest-btn" id="roomPlus">+</button>
                                    </div>
                                </div>
                                <div class="guest-row">
                                    <div class="guest-label">Dewasa</div>
                                    <div class="guest-controls">
                                        <button type="button" class="guest-btn" id="adultMinus">−</button>
                                        <span class="guest-count" id="adultCount">1</span>
                                        <button type="button" class="guest-btn" id="adultPlus">+</button>
                                    </div>
                                </div>
                                <div class="guest-row">
                                    <div class="guest-label">
                                        Anak-anak
                                        <small>(dibawah 17 tahun)</small>
                                    </div>
                                    <div class="guest-controls">
                                        <button type="button" class="guest-btn" id="childMinus">−</button>
                                        <span class="guest-count" id="childCount">0</span>
                                        <button type="button" class="guest-btn" id="childPlus">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="rooms" id="roomsInput" value="1">
                        <input type="hidden" name="adults" id="adultsInput" value="1">
                        <input type="hidden" name="children" id="childrenInput" value="0">
                    </div>
                </form>

                <div style="margin-top:32px; text-align:center;">
                    <button type="submit" form="searchForm" class="btn-search">
                        <i class="bi bi-search"></i>
                        Cari Hotel
                    </button>
                </div>
            </div>
        </div>
    </section>

    <div class="content">
        <?php if ($is_logged_in): ?>
            <div class="welcome-message">
                <h2>Halo, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
                <p>Selamat datang kembali di YogyaStay. Siap merencanakan petualangan Anda berikutnya?</p>
            </div>
        <?php endif; ?>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon">🏨</div>
                <h3>Penginapan Berkualitas</h3>
                <p>Berbagai pilihan penginapan dengan fasilitas terbaik dan pelayanan maksimal untuk kenyamanan Anda</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Harga Terjangkau</h3>
                <p>Dapatkan penawaran terbaik dengan harga kompetitif yang sesuai dengan budget liburan Anda</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📍</div>
                <h3>Lokasi Strategis</h3>
                <p>Dekat dengan destinasi wisata populer di Yogyakarta dan akses mudah ke berbagai tempat menarik</p>
            </div>
        </div>

        <!-- ARTIKEL SECTION -->
        <div class="artikel-section">
            <div class="artikel-header">
                <h2 class="artikel-title">Artikel dan Berita</h2>
                <a href="blog.php" class="btn-lihat-semua">
                    Baca Semua Blog
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="artikel-grid">
                <?php if (mysqli_num_rows($artikel_query) > 0): ?>
                    <?php while ($artikel = mysqli_fetch_assoc($artikel_query)): ?>
                        <div class="artikel-card">
                            <img src="../assets/blog/<?= htmlspecialchars($artikel['thumbnail']); ?>" 
                                alt="<?= htmlspecialchars($artikel['judul']); ?>">
                            <div class="artikel-card-content">
                                <h3><?= htmlspecialchars($artikel['judul']); ?></h3>
                                <p>
                                    <?= substr(strip_tags($artikel['konten']), 0, 120); ?>...
                                </p>
                                <a href="detailblog.php?id=<?= $artikel['id_blog']; ?>" class="read-more">
                                    Baca Selengkapnya
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; color:#999; grid-column: 1/-1;">
                        Belum ada artikel tersedia.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- FAQ SECTION -->
        <div class="faq-section">
            <h2 class="faq-title">Frequently Asked Question (FAQ)</h2>

            <div class="faq-item">
                <button class="faq-question" type="button">
                    Apakah YogyaStay ini menyediakan informasi penginapan di seluruh Indonesia?
                    <span class="faq-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    Tidak, YogyaStay hanya menyediakan informasi mengenai penginapan yang ada di Provinsi D. I. Yogyakarta.
                    Anda bisa menemukan seluruh penginapan di D. I. Yogyakarta berdasarkan Kabupaten dan Kecamatan yang ada.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" type="button">
                    Bagaimana cara memesan penginapan di YogyaStay?
                    <span class="faq-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    Anda dapat menggunakan form pencarian di halaman utama untuk mencari penginapan berdasarkan lokasi, tanggal check-in/check-out, dan jumlah tamu. Setelah menemukan penginapan yang sesuai, Anda dapat melihat detail lengkap dan melakukan pemesanan.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" type="button">
                    Apakah saya harus membuat akun untuk mencari penginapan?
                    <span class="faq-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    Tidak, Anda dapat mencari dan melihat informasi penginapan tanpa membuat akun. Namun, untuk melakukan pemesanan dan mengakses fitur lengkap, Anda perlu mendaftar dan login terlebih dahulu.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" type="button">
                    Bagaimana cara menghubungi customer service YogyaStay?
                    <span class="faq-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    Anda dapat menghubungi customer service kami melalui email atau telepon yang tertera di halaman kontak. Tim kami siap membantu Anda dengan pertanyaan atau kendala yang Anda hadapi.
                </div>
            </div>

            <div class="faq-item">
                <button class="faq-question" type="button">
                    Apakah ada biaya tambahan saat melakukan pemesanan?
                    <span class="faq-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </span>
                </button>
                <div class="faq-answer">
                    Harga yang ditampilkan sudah termasuk semua biaya. Namun, beberapa penginapan mungkin memiliki kebijakan biaya tambahan untuk layanan tertentu yang akan dijelaskan pada halaman detail penginapan.
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('searchForm').addEventListener('submit', function (e) {
    const checkin = document.getElementById('checkin').value.trim();
    const checkout = document.getElementById('checkout').value.trim();

    if (!checkin || !checkout) {
        e.preventDefault(); // hentikan submit

        Swal.fire({
            icon: 'warning',
            title: 'Tanggal belum lengkap',
            text: 'Silakan pilih tanggal check-in dan check-out terlebih dahulu',
            confirmButtonColor: '#E8D06F'
        });
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// Guest Selector
let rooms = 1, adults = 1, children = 0;

const guestInput = document.getElementById('guestInput');
const guestDropdown = document.getElementById('guestDropdown');
const guestDisplay = document.getElementById('guestDisplay');

guestInput.addEventListener('click', (e) => {
    e.stopPropagation();
    guestDropdown.classList.toggle('active');
});

document.addEventListener('click', (e) => {
    if (!guestDropdown.contains(e.target) && e.target !== guestInput) {
        guestDropdown.classList.remove('active');
    }
});

function updateGuestDisplay() {
    guestDisplay.textContent = `${rooms} Kamar, ${adults} Dewasa, ${children} Anak`;
    document.getElementById('roomsInput').value = rooms;
    document.getElementById('adultsInput').value = adults;
    document.getElementById('childrenInput').value = children;
}

function updateButtons() {
    document.getElementById('roomMinus').disabled = rooms <= 1;
    document.getElementById('adultMinus').disabled = adults <= 1;
    document.getElementById('childMinus').disabled = children <= 0;
}

document.getElementById('roomPlus').addEventListener('click', () => {
    rooms++;
    document.getElementById('roomCount').textContent = rooms;
    updateGuestDisplay();
    updateButtons();
});

document.getElementById('roomMinus').addEventListener('click', () => {
    if (rooms > 1) {
        rooms--;
        document.getElementById('roomCount').textContent = rooms;
        updateGuestDisplay();
        updateButtons();
    }
});

document.getElementById('adultPlus').addEventListener('click', () => {
    adults++;
    document.getElementById('adultCount').textContent = adults;
    updateGuestDisplay();
    updateButtons();
});

document.getElementById('adultMinus').addEventListener('click', () => {
    if (adults > 1) {
        adults--;
        document.getElementById('adultCount').textContent = adults;
        updateGuestDisplay();
        updateButtons();
    }
});

document.getElementById('childPlus').addEventListener('click', () => {
    children++;
    document.getElementById('childCount').textContent = children;
    updateGuestDisplay();
    updateButtons();
});

document.getElementById('childMinus').addEventListener('click', () => {
    if (children > 0) {
        children--;
        document.getElementById('childCount').textContent = children;
        updateGuestDisplay();
        updateButtons();
    }
});

// Flatpickr Date Picker
const today = new Date();
const tomorrow = new Date(today);
tomorrow.setDate(tomorrow.getDate() + 1);

const checkinPicker = flatpickr("#checkin", {
    dateFormat: "d/m/Y",
    minDate: "today",
    onChange: function(selectedDates, dateStr, instance) {
        checkoutPicker.set('minDate', selectedDates[0] || today);
        if (selectedDates[0]) {
            const nextDay = new Date(selectedDates[0]);
            nextDay.setDate(nextDay.getDate() + 1);
            if (!document.getElementById('checkout').value) {
                checkoutPicker.setDate(nextDay);
            }
        }
    }
});

const checkoutPicker = flatpickr("#checkout", {
    dateFormat: "d/m/Y",
    minDate: tomorrow
});

// Load Kabupaten with AJAX
fetch('get_kabupaten.php')
    .then(response => response.json())
    .then(data => {
        const kabupatenSelect = document.getElementById('kabupaten');
        data.forEach(kab => {
            const option = document.createElement('option');
            option.value = kab.id;
            option.textContent = kab.nama;
            kabupatenSelect.appendChild(option);
        });
    })
    .catch(error => console.error('Error loading kabupaten:', error));

// Load Kecamatan based on selected Kabupaten
const kabupatenSelect = document.getElementById('kabupaten');
const kecamatanSelect = document.getElementById('kecamatan');

// Pastikan kondisi awal
kecamatanSelect.disabled = true;

kabupatenSelect.addEventListener('change', function () {
    const kabupatenId = this.value;

    // Reset kecamatan setiap kabupaten berubah
    kecamatanSelect.innerHTML = '<option value="">Semua Kecamatan</option>';

    // Jika pilih "Semua Kabupaten"
    if (kabupatenId === "") {
        kecamatanSelect.disabled = true;
        return;
    }

    // Jika pilih kabupaten tertentu
    kecamatanSelect.disabled = true;

    fetch(`get_kecamatan.php?id_kabupaten=${kabupatenId}`)
        .then(response => response.json())
        .then(data => {
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.nama;
                kecamatanSelect.appendChild(option);
            });

            kecamatanSelect.disabled = false;
        })
        .catch(error => {
            console.error('Gagal load kecamatan:', error);
            kecamatanSelect.disabled = true;
        });
});

// FAQ Toggle
document.querySelectorAll(".faq-item").forEach(item => {
    item.querySelector(".faq-question").addEventListener("click", () => {
        document.querySelectorAll(".faq-item").forEach(i => {
            if (i !== item) i.classList.remove("active");
        });
        item.classList.toggle("active");
    });
});

updateButtons();
</script>

<?php include 'footer.php'; ?>

</body>
</html>