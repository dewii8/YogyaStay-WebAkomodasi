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

$page_title = 'Beranda';
include 'header.php';
?>

    <style>
        body {
            background: #f5f5f5;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #f5b342 0%, #e8a130 100%);
            padding: 80px 60px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><circle cx="20" cy="20" r="15" fill="white"/><circle cx="80" cy="60" r="20" fill="white"/><circle cx="50" cy="80" r="10" fill="white"/></svg>');
            opacity: 0.1;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero h1 {
            font-size: 48px;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .hero p {
            font-size: 20px;
            margin-bottom: 35px;
            opacity: 0.95;
        }
        
        .btn-cari {
            background: white;
            color: #f5b342;
            padding: 15px 45px;
            border: none;
            border-radius: 30px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            text-decoration: none;
        }
        
        .btn-cari:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        
        /* Content */
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
            border-left: 5px solid #f5b342;
        }
        
        .welcome-message h2 {
            color: #f5b342;
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
            border-top-color: #f5b342;
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
            color: #f5b342;
            margin-bottom: 15px;
            font-size: 22px;
            font-weight: 700;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
            font-size: 15px;
        }
    </style>

    <section class="hero">
        <div class="hero-content">
            <h1>Selamat Datang di YogyaStay</h1>
            <p>Temukan penginapan terbaik untuk liburan Anda di Yogyakarta</p>
            <a href="penginapan.php" class="btn-cari">Cari Penginapan</a>
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
    </div>

<?php include 'footer.php'; ?>