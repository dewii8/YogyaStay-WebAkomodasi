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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


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

  /* PENGINAPAN SECTION */
.penginapan-section {
    max-width: 1400px;
    margin: 60px auto;
    padding: 0 60px;
}

/* Ganti header dengan section-header */
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.section-header h2 {
    font-size: 28px;
    font-weight: 700;
    color: #333;
}

/* Tombol lihat semua baru - update warna teks */
.view-all {
    display: inline-flex;
    align-items: center;
    background-color: #6E88C0;
    color: white;
    padding: 8px 20px;
    border-radius: 30px;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600; /* Lebih bold */
    transition: background 0.3s;
}

.view-all:after {
    content: "→";
    margin-left: 8px;
    font-size: 16px;
}

.view-all:hover {
    background-color: #5A75B0;
    text-decoration: none;
    color: white;
}

/* Tab kabupaten styling */
.location-filter {
    display: flex;
    border-bottom: 1px solid #e0e0e0;
    margin-bottom: 25px;
    overflow-x: auto;
    gap: 0;
    padding-bottom: 0;
    flex-wrap: nowrap;
    scrollbar-width: none;
}

.location-filter::-webkit-scrollbar {
    display: none;
}

.filter-btn {
    background: none;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    font-size: 14px;
    color: #333;
    transition: all 0.2s;
    position: relative;
    white-space: nowrap;
    font-weight: 500;
    border-radius: 0;
}

.filter-btn:hover {
    color: #4A90E2;
}

.filter-btn.active {
    background-color: transparent;
    color: #4A90E2;
    font-weight: 600;
}

.filter-btn.active::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: #4A90E2;
}


.filter-btn:not(:first-child):hover {
    color: #4A90E2;
}

.filter-btn.active:not(:first-child) {
    background-color: transparent;
    color: #4A90E2;
    font-weight: 600;
}

.filter-btn.active:not(:first-child)::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 2px;
    background-color: #4A90E2;
}

/* Grid container penginapan */
.penginapan-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.penginapan-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.3s;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.penginapan-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

/* Badge tipe penginapan */
.penginapan-type {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 15px;
    border-radius: 30px;
    font-size: 14px;
    font-weight: 600;
    background-color: rgba(0, 0, 0, 0.7);
    color: white;
}

/* Gambar penginapan */
.penginapan-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

/* Info penginapan */
.penginapan-info {
    padding: 15px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.penginapan-name {
    font-size: 18px;
    font-weight: 700;
    color: #333;
    margin-bottom: 8px;
}

.penginapan-location {
    display: flex;
    align-items: center;
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.penginapan-location i {
    margin-right: 5px;
    color: #888;
}

/* Fasilitas ikon */
.penginapan-facilities {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.facility i {
    color: #999; 
    font-size: 16px;
}

/* Rating */
.penginapan-rating {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.stars {
    color: #FFD700;
    margin-right: 5px;
}

.review-count {
    font-size: 14px;
    color: #666;
}

/* Harga dan tombol */
.penginapan-price {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
}

.price-info {
    display: flex;
    flex-direction: column;
}

.price-value, .price-label {
    color: #F44336; 
    font-weight: 700;
}

.price-value {
    font-size: 18px;
}

.price-label {
    font-size: 12px;
    font-weight: normal;
}

/* Tombol lihat detail */
.btn-lihat-detail {
    background-color: #FDD835;
    color: #000;
    font-weight: 600;
    padding: 8px 16px; 
    border-radius: 30px;
    text-decoration: none;
    font-size: 14px;
    border: none;
    cursor: pointer;
    transition: background 0.3s;
    text-align: center;
    white-space: nowrap;
}

.btn-lihat-detail:hover {
    background-color: #FBC02D;
}

/* Status messages */
.loading, .error, .no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 30px 0;
    color: #666;
}

.error {
    color: #F44336;
}

/* Responsif */
@media (max-width: 1200px) {
    .penginapan-container {
        grid-template-columns: repeat(3, 1fr);
    }
    
    .btn-lihat-detail {
        padding: 6px 12px;
        font-size: 13px;
    }
}

@media (max-width: 992px) {
    .btn-lihat-detail {
        padding: 5px 10px;
        font-size: 12px;
    }
}

@media (max-width: 768px) {
    .penginapan-container {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .penginapan-section {
        padding: 0 20px;
        margin: 40px auto 60px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .penginapan-name {
        font-size: 16px;
    }
    
    .price-value {
        font-size: 16px;
    }
    
    .btn-lihat-detail {
        padding: 5px 10px;
        font-size: 12px;
    }
}

@media (max-width: 500px) {
    .penginapan-container {
        grid-template-columns: 1fr;
    }
    
    .btn-lihat-detail {
        padding: 8px 16px;
        font-size: 14px;
    }
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
            border-radius: 50px;
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

        /* ========= PROMO SECTION ========= */
.promo-section {
    max-width: 1400px;
    margin: 40px auto 60px;
    padding: 0 40px;
}

.promo-container {
    background: transparent;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    border: none;
}

.promo-header {
    margin-bottom: 30px;
}

.promo-title {
    font-size: 24px;
    font-weight: 700;
    color: #2B2B2B;
    text-align: center;
    margin: 0;
}

.promo-carousel {
    position: relative;
    z-index: 20;
    overflow: visible;
}

.promo-track-wrapper {
    overflow: hidden;
    border-radius: 16px;
}

.promo-track {
    display: flex;
    gap: 20px;
    transition: transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.promo-card {
    position: relative;
    min-width: calc(33.333% - 14px);
    aspect-ratio: 16/9;
    border-radius: 16px;
    overflow: hidden;
    cursor: pointer;
    transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.promo-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 16px 40px rgba(0,0,0,0.2);
}

.promo-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.promo-card:hover img {
    transform: scale(1.1);
}

.promo-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    color: #F5A742;
    z-index: 3;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.promo-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(0,0,0,0.85) 0%,
        rgba(0,0,0,0.4) 50%,
        transparent 100%
    );
    display: flex;
    align-items: flex-end;
    padding: 25px;
    z-index: 2;
}

.promo-content {
    color: white;
    width: 100%;
}

.promo-event {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.promo-desc {
    font-size: 14px;
    margin-bottom: 8px;
    opacity: 0.95;
}

.promo-discount {
    font-size: 42px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 5px;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    text-shadow: 0 4px 12px rgba(255,215,0,0.4);
}

.promo-label {
    display: inline-block;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
}

.promo-terms {
    font-size: 11px;
    opacity: 0.8;
    margin-top: 8px;
}

/* Navigation Buttons */
.promo-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    color: #2B2B2B;
    font-size: 24px;
    cursor: pointer;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
    transition: all 0.3s ease;
}

.promo-nav:hover {
    background: #F2D965;
    color: white;
    transform: translateY(-50%) scale(1.1);
    box-shadow: 0 6px 20px rgba(242,217,101,0.4);
}

.promo-prev {
    left: -25px;
}

.promo-next {
    right: -25px;
}

/* Dots Indicator */
.promo-dots {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 25px;
}

.promo-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #ddd;
    cursor: pointer;
    transition: all 0.3s ease;
}

.promo-dot.active {
    background: #F2D965;
    width: 30px;
    border-radius: 5px;
}

/* ========= RESPONSIVE ========= */
@media (max-width: 1200px) {
    .promo-card {
        min-width: calc(50% - 10px);
    }
}

@media (max-width: 768px) {
    .promo-section {
        padding: 0 20px;
        margin: -30px auto 40px;
    }

    .promo-container {
        padding: 25px 20px;
    }

    .promo-title {
        font-size: 18px;
    }

    .promo-card {
        min-width: 100%;
    }

    .promo-nav {
        width: 40px;
        height: 40px;
        font-size: 20px;
    }

    .promo-prev {
        left: -15px;
    }

    .promo-next {
        right: -15px;
    }

    .promo-event {
        font-size: 16px;
    }

    .promo-discount {
        font-size: 32px;
    }

    .promo-overlay {
        padding: 20px;
    }
}

@media (max-width: 480px) {
    .promo-section {
        margin: -20px auto 30px;
        padding: 0 15px;
    }

    .promo-container {
        padding: 20px 15px;
        border-radius: 16px;
    }

    .promo-title {
        font-size: 16px;
    }

    .promo-nav {
        width: 36px;
        height: 36px;
        font-size: 18px;
    }

    .promo-prev {
        left: -10px;
    }

    .promo-next {
        right: -10px;
    }

    .promo-badge {
        font-size: 11px;
        padding: 6px 12px;
        top: 10px;
        right: 10px;
    }

    .promo-event {
        font-size: 14px;
    }

    .promo-discount {
        font-size: 28px;
    }

    .promo-label {
        font-size: 14px;
    }

    .promo-terms {
        font-size: 10px;
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
    
        <!-- Bagian Penginapan di JogjaStay -->
        <section class="penginapan-section container">
            <div class="section-header">
                <h2>Top Penginapan di YogyaStay</h2>
                <a href="penginapan.php" class="view-all" id="view-all-link">Lihat Semua</a>
            </div>
            
            <!-- Filter Kabupaten -->
            <div class="location-filter">
                <button class="filter-btn active" data-kabupaten="all">Semua</button>
                <button class="filter-btn" data-kabupaten="1">Kota Yogyakarta</button>
                <button class="filter-btn" data-kabupaten="2">Kabupaten Sleman</button>
                <button class="filter-btn" data-kabupaten="3">Kabupaten Bantul</button>
                <button class="filter-btn" data-kabupaten="4">Kabupaten Kulon Progo</button>
                <button class="filter-btn" data-kabupaten="5">Kabupaten Gunungkidul</button>
            </div>
            
            <!-- Penginapan Cards -->
            <div class="penginapan-container">
            </div>
        </section>

        
        <!-- ========= PROMO BANNER SECTION ========= -->
        <section class="promo-section">
            <div class="promo-container">
                <div class="promo-header">
                    <h2 class="promo-title">Jangan sampai kelewatan promo berikut!</h2>
                </div>
                
                <div class="promo-carousel">
                    <button class="promo-nav promo-prev" aria-label="Previous">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    
                    <div class="promo-track-wrapper">
                        <div class="promo-track">
                            <!-- Promo 1 -->
                            <div class="promo-card">
                                <div class="promo-badge">🎊 Tahun Baru</div>
                                <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80" alt="Promo Tahun Baru">
                                <div class="promo-overlay">
                                    <div class="promo-content">
                                        <h3 class="promo-event">12.12 Pesta Meriah</h3>
                                        <p class="promo-desc">Extra Cashback hingga</p>
                                        <div class="promo-discount">15%</div>
                                        <span class="promo-label">diskon</span>
                                        <p class="promo-terms">*Syarat & Ketentuan Berlaku</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Promo 2 -->
                            <div class="promo-card">
                                <div class="promo-badge">✨ Spesial</div>
                                <img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&q=80" alt="Promo Spesial">
                                <div class="promo-overlay">
                                    <div class="promo-content">
                                        <h3 class="promo-event">Temukan semua</h3>
                                        <p class="promo-desc">promo Anda</p>
                                        <div class="promo-discount">di sini!</div>
                                        <p class="promo-terms">Nikmati penawaran terbaik</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Promo 3 -->
                            <div class="promo-card">
                                <div class="promo-badge">🏖️ Liburan</div>
                                <img src="https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=800&q=80" alt="Promo Liburan">
                                <div class="promo-overlay">
                                    <div class="promo-content">
                                        <h3 class="promo-event">🌴 MID HAPPY WEEK 🌴</h3>
                                        <p class="promo-desc">Diskon hingga</p>
                                        <div class="promo-discount">15%</div>
                                        <span class="promo-label">Paket Liburan</span>
                                        <p class="promo-terms">*Booking sekarang!</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Promo 4 -->
                            <div class="promo-card">
                                <div class="promo-badge">🔥 Hot Deal</div>
                                <img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&q=80" alt="Promo Hot Deal">
                                <div class="promo-overlay">
                                    <div class="promo-content">
                                        <h3 class="promo-event">Flash Sale!</h3>
                                        <p class="promo-desc">Hemat hingga</p>
                                        <div class="promo-discount">20%</div>
                                        <span class="promo-label">Untuk hari ini</span>
                                        <p class="promo-terms">*Terbatas!</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Promo 5 -->
                            <div class="promo-card">
                                <div class="promo-badge">💎 Premium</div>
                                <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&q=80" alt="Promo Premium">
                                <div class="promo-overlay">
                                    <div class="promo-content">
                                        <h3 class="promo-event">Member Exclusive</h3>
                                        <p class="promo-desc">Cashback ekstra</p>
                                        <div class="promo-discount">25%</div>
                                        <span class="promo-label">Member VIP</span>
                                        <p class="promo-terms">*Khusus member</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="promo-nav promo-next" aria-label="Next">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

                <!-- Dots Indicator -->
                <div class="promo-dots"></div>
            </div>
        </section>
                                                    
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
    dateFormat: "Y-m-d",  
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
    dateFormat: "Y-m-d", 
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

<!-- Script untuk fitur penginapan di JogjaStay -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Tambahkan pengujian apakah elemen penginapan ada sebelum menjalankan kode
    if (document.querySelector('.penginapan-container')) {
        loadPenginapan('all');
        
        const filterButtons = document.querySelectorAll('.filter-btn');
        const viewAllLink = document.getElementById('view-all-link');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const kabupatenId = this.getAttribute('data-kabupaten');
                
                // Hapus kelas active dari semua tombol
                filterButtons.forEach(btn => btn.classList.remove('active'));
                
                // Tambahkan kelas active ke tombol yang diklik
                this.classList.add('active');
                
                // Load penginapan berdasarkan kabupaten
                loadPenginapan(kabupatenId);
                
                // ✅ UPDATE LINK "LIHAT SEMUA" BERDASARKAN TAB YANG AKTIF
                if (kabupatenId === 'all') {
                    viewAllLink.href = 'penginapan.php';
                } else {
                    viewAllLink.href = `penginapan.php?kabupaten=${kabupatenId}`;
                }
            });
        });
    }
});

function loadPenginapan(kabupatenId) {
    const penginapanContainer = document.querySelector('.penginapan-container');
    if (!penginapanContainer) return;
    
    penginapanContainer.innerHTML = '<div class="loading">Memuat data penginapan...</div>';
    
    fetch(`get_penginapan.php?kabupaten=${kabupatenId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (Array.isArray(data) && data.length > 0) {
                penginapanContainer.innerHTML = '';
                
                // Batasi penginapan yang ditampilkan menjadi 4
                const penginapanToShow = data.slice(0, 4);
                
                // Render setiap penginapan
                penginapanToShow.forEach(item => {
                    penginapanContainer.innerHTML += createPenginapanCard(item);
                });
            } else {
                penginapanContainer.innerHTML = '<div class="no-results">Tidak ada penginapan ditemukan di wilayah ini</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            penginapanContainer.innerHTML = '<div class="error">Terjadi kesalahan saat memuat data</div>';
        });
}

function createPenginapanCard(penginapan) {
    // Hitung rating dalam bentuk bintang
    const rating = parseFloat(penginapan.rating) || 0;
    let starsHTML = '<i class="fas fa-star"></i>'; 
    
    // Format fasilitas sebagai ikon
    const fasilitasHTML = `
        <i class="fas fa-wifi"></i>
        <i class="fas fa-coffee"></i>
        <i class="fas fa-swimming-pool"></i>
    `;
    
    // Format harga
    const harga = parseInt(penginapan.harga_mulai) || 0;
    const formattedHarga = new Intl.NumberFormat('id-ID').format(harga);
    
    return `
        <div class="penginapan-card">
            <div style="position: relative;">
                <img src="../${penginapan.gambar}" alt="${penginapan.nama_penginapan}" class="penginapan-image">
                <div class="penginapan-type">${penginapan.tipe_penginapan || 'Villa'}</div>
            </div>
            <div class="penginapan-info">
                <h3 class="penginapan-name">${penginapan.nama_penginapan}</h3>
                <div class="penginapan-location">
                    <i class="fas fa-map-marker-alt"></i>
                    ${penginapan.nama_kecamatan}, ${penginapan.nama_kabupaten}
                </div>
                <div class="penginapan-facilities">
                    <div class="facility"><i class="fas fa-wifi"></i></div>
                    <div class="facility"><i class="fas fa-coffee"></i></div>
                    <div class="facility"><i class="fas fa-swimming-pool"></i></div>
                </div>
                <hr>
                <div class="penginapan-rating">
                    <div class="stars">${starsHTML}</div>
                    <strong>${rating.toFixed(1)}</strong> 
                    <span class="review-count">(${penginapan.jumlah_review || 0})</span>
                </div>
                <div class="penginapan-price">
                    <div class="price-info">
                        <span class="price-value">Rp ${formattedHarga}</span>
                        <span class="price-label">/malam</span>
                    </div>
                    <a href="detail.php?id=${penginapan.id_penginapan}" class="btn-lihat-detail">Lihat Detail</a>
                </div>
            </div>
        </div>
    `;
}

// ========= PROMO CAROUSEL =========
(function() {
    const track = document.querySelector('.promo-track');
    const cards = document.querySelectorAll('.promo-card');
    const prevBtn = document.querySelector('.promo-prev');
    const nextBtn = document.querySelector('.promo-next');
    const dotsContainer = document.querySelector('.promo-dots');
    
    if (!track || cards.length === 0) return;
    
    let currentIndex = 0;
    let cardsPerView = 3;
    let autoplayInterval;
    
    // Update cards per view based on screen size
    function updateCardsPerView() {
        const width = window.innerWidth;
        if (width <= 768) {
            cardsPerView = 1;
        } else if (width <= 1200) {
            cardsPerView = 2;
        } else {
            cardsPerView = 3;
        }
    }
    
    // Calculate total slides
    function getTotalSlides() {
        return Math.ceil(cards.length / cardsPerView);
    }
    
    // Create dots
    function createDots() {
        dotsContainer.innerHTML = '';
        const totalSlides = getTotalSlides();
        
        for (let i = 0; i < totalSlides; i++) {
            const dot = document.createElement('div');
            dot.classList.add('promo-dot');
            if (i === 0) dot.classList.add('active');
            dot.addEventListener('click', () => goToSlide(i));
            dotsContainer.appendChild(dot);
        }
    }
    
    // Update dots
    function updateDots() {
        const dots = document.querySelectorAll('.promo-dot');
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === currentIndex);
        });
    }
    
    // Go to specific slide
    function goToSlide(index) {
        const totalSlides = getTotalSlides();
        currentIndex = Math.max(0, Math.min(index, totalSlides - 1));
        
        const cardWidth = cards[0].offsetWidth;
        const gap = 20;
        const offset = -(currentIndex * cardsPerView * (cardWidth + gap));
        
        track.style.transform = `translateX(${offset}px)`;
        updateDots();
    }
    
    // Next slide
    function nextSlide() {
        const totalSlides = getTotalSlides();
        if (currentIndex < totalSlides - 1) {
            goToSlide(currentIndex + 1);
        } else {
            goToSlide(0); // Loop back to first
        }
    }
    
    // Previous slide
    function prevSlide() {
        if (currentIndex > 0) {
            goToSlide(currentIndex - 1);
        } else {
            goToSlide(getTotalSlides() - 1); // Loop to last
        }
    }
    
    // Autoplay
    function startAutoplay() {
        stopAutoplay();
        autoplayInterval = setInterval(nextSlide, 4000);
    }
    
    function stopAutoplay() {
        if (autoplayInterval) {
            clearInterval(autoplayInterval);
        }
    }
    
    // Event listeners
    nextBtn.addEventListener('click', () => {
        nextSlide();
        stopAutoplay();
        startAutoplay();
    });
    
    prevBtn.addEventListener('click', () => {
        prevSlide();
        stopAutoplay();
        startAutoplay();
    });
    
    // Pause on hover
    track.addEventListener('mouseenter', stopAutoplay);
    track.addEventListener('mouseleave', startAutoplay);
    
    // Touch/Swipe support
    let touchStartX = 0;
    let touchEndX = 0;
    
    track.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoplay();
    });
    
    track.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
        startAutoplay();
    });
    
    function handleSwipe() {
        if (touchEndX < touchStartX - 50) nextSlide();
        if (touchEndX > touchStartX + 50) prevSlide();
    }
    
    // Resize handler
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            updateCardsPerView();
            createDots();
            goToSlide(0);
        }, 250);
    });
    
    // Initialize
    updateCardsPerView();
    createDots();
    startAutoplay();
})();
</script>

<?php include 'footer.php'; ?>

</body>
</html>