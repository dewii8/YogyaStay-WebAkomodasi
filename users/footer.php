<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Footer YogyaStay</title>

    <!-- GOOGLE FONT POPPINS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    /* ========= BASE STYLES ========= */
    * { 
        font-family: "Poppins", sans-serif;
        box-sizing: border-box;
    }

    /* WRAPPER */
    .yst-footer {
        position: relative;
        background: url("../assets/img/footer2.jpg") center/cover no-repeat;
        padding: 70px 40px 40px;
        color: white;
    }

    /* OVERLAY */
    .yst-footer::before {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.70);
        z-index: 0;
    }

    .yst-footer-container {
        position: relative;
        z-index: 2;
        display: grid;
        grid-template-columns: 1.2fr 1fr 1fr;
        gap: 50px;
        max-width: 1200px;
        margin: auto;
    }

    /* LOGO */
    .yst-logo-area {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 22px;
    }

    .yst-logo {
        width: 50px;
        height: auto;
        flex-shrink: 0;
    }

    .yst-logo-area h3 {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
    }

    /* SOCIAL ICONS */
    .yst-social {
        display: flex;
        gap: 16px;
    }

    .yst-social a {
        width: 48px;
        height: 48px;
        background: rgba(255,255,255,0.15);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: white;
        transition: all 0.25s;
        flex-shrink: 0;
    }

    .yst-social a:hover {
        background: #F7B24C;
        transform: translateY(-3px);
    }

    /* HEADING */
    .yst-col h4 {
        color: #F6B049;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
        margin-top: 0;
    }

    /* CONTACT ITEM */
    .yst-contact-item {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15px;
        color: #FFFFFF;
        margin-bottom: 12px;
        line-height: 1.6;
    }

    .yst-contact-item i {
        font-size: 18px;
        flex-shrink: 0;
    }

    .yst-contact-item a {
        text-decoration: none;
        color: inherit;
        word-break: break-word;
    }

    .yst-contact-item a:hover {
        text-decoration: underline;
        color: #F6B049;
    }

    /* LINKS */
    .yst-link {
        display: block;
        margin-bottom: 12px;
        font-size: 15px;
        color: #FFFFFF;
        text-decoration: none;
        transition: all 0.25s;
    }

    .yst-link:hover {
        color: #F6B049;
        padding-left: 5px;
    }

    /* BOTTOM */
    .yst-bottom {
        border-top: 1px solid rgba(255,255,255,0.25);
        margin-top: 50px;
        padding-top: 22px;
        text-align: center;
        font-size: 14px;
        color: #FFFFFF;
        font-weight: 500;
        position: relative;
        z-index: 5;
    }

    .yst-col:nth-child(2),
    .yst-col:nth-child(3) {
        text-align: right;
    }

    .yst-col:nth-child(2) .yst-contact-item {
        justify-content: flex-end;
    }

    /* ========= RESPONSIVE DESIGN ========= */

    /* Tablet Landscape (992px - 1200px) */
    @media (max-width: 1200px) {
        .yst-footer {
            padding: 60px 30px 35px;
        }

        .yst-footer-container {
            gap: 40px;
        }
    }

    /* Tablet Portrait (768px - 991px) */
    @media (max-width: 991px) {
        .yst-footer {
            padding: 50px 25px 30px;
        }

        .yst-footer-container {
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        /* Column 1 full width */
        .yst-col:first-child {
            grid-column: 1 / -1;
            text-align: center;
        }

        .yst-col:first-child .yst-logo-area {
            justify-content: center;
        }

        .yst-col:first-child .yst-social {
            justify-content: center;
        }

        /* Column 2 & 3 side by side */
        .yst-col:nth-child(2) {
            text-align: left;
        }

        .yst-col:nth-child(2) .yst-contact-item {
            justify-content: flex-start;
        }

        .yst-col:nth-child(3) {
            text-align: left;
        }

        .yst-bottom {
            margin-top: 40px;
            padding-top: 20px;
        }
    }

    /* Mobile Landscape (576px - 767px) */
    @media (max-width: 767px) {
        .yst-footer {
            padding: 40px 20px 25px;
        }

        .yst-footer-container {
            grid-template-columns: 1fr;
            gap: 35px;
            text-align: center;
        }

        .yst-col {
            text-align: center !important;
        }

        .yst-logo-area {
            justify-content: center;
        }

        .yst-logo-area h3 {
            font-size: 22px;
        }

        .yst-social {
            justify-content: center;
        }

        .yst-social a {
            width: 45px;
            height: 45px;
            font-size: 20px;
        }

        .yst-col h4 {
            font-size: 18px;
            margin-bottom: 18px;
        }

        .yst-contact-item {
            justify-content: center !important;
            font-size: 14px;
        }

        .yst-link {
            text-align: center;
            font-size: 14px;
        }

        .yst-link:hover {
            padding-left: 0;
        }

        .yst-bottom {
            margin-top: 35px;
            padding-top: 18px;
            font-size: 13px;
        }
    }

    /* Mobile Portrait (< 576px) */
    @media (max-width: 575px) {
        .yst-footer {
            padding: 35px 15px 20px;
        }

        .yst-footer-container {
            gap: 30px;
        }

        .yst-logo {
            width: 45px;
        }

        .yst-logo-area h3 {
            font-size: 20px;
        }

        .yst-social {
            gap: 12px;
        }

        .yst-social a {
            width: 42px;
            height: 42px;
            font-size: 18px;
        }

        .yst-col h4 {
            font-size: 17px;
            margin-bottom: 16px;
        }

        .yst-contact-item {
            font-size: 13px;
            gap: 10px;
            margin-bottom: 10px;
        }

        .yst-contact-item i {
            font-size: 16px;
        }

        .yst-link {
            font-size: 13px;
            margin-bottom: 10px;
        }

        .yst-bottom {
            margin-top: 30px;
            padding-top: 16px;
            font-size: 12px;
        }
    }

    /* Extra Small Mobile (< 400px) */
    @media (max-width: 399px) {
        .yst-footer {
            padding: 30px 12px 18px;
        }

        .yst-footer-container {
            gap: 25px;
        }

        .yst-logo {
            width: 40px;
        }

        .yst-logo-area {
            gap: 10px;
        }

        .yst-logo-area h3 {
            font-size: 18px;
        }

        .yst-social {
            gap: 10px;
        }

        .yst-social a {
            width: 38px;
            height: 38px;
            font-size: 16px;
        }

        .yst-col h4 {
            font-size: 16px;
            margin-bottom: 14px;
        }

        .yst-contact-item {
            font-size: 12px;
            gap: 8px;
        }

        .yst-contact-item i {
            font-size: 14px;
        }

        .yst-link {
            font-size: 12px;
            margin-bottom: 8px;
        }

        .yst-bottom {
            margin-top: 25px;
            padding-top: 14px;
            font-size: 11px;
            line-height: 1.5;
        }
    }
    </style>
</head>
<body>

<footer class="yst-footer">
    <div class="yst-footer-container">

        <!-- COLUMN 1 -->
        <div class="yst-col">
            <div class="yst-logo-area">
                <img src="../assets/img/logo.png" alt="YogyaStay" class="yst-logo">
                <h3>YogyaStay</h3>
            </div>

            <div class="yst-social">
                <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                <a href="#" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
            </div>
        </div>

        <!-- COLUMN 2 -->
        <div class="yst-col">
            <h4>Hubungi Kami</h4>

            <p class="yst-contact-item">
                <i class="bi bi-envelope"></i>
                <a href="mailto:delivra.yogyastay@gmail.com">
                    delivra.yogyastay@gmail.com
                </a>
            </p>

            <p class="yst-contact-item">
                <i class="bi bi-telephone"></i>
                <a href="tel:+62274123456">
                    (0274) 123456
                </a>
            </p>

            <p class="yst-contact-item">
                <i class="bi bi-whatsapp"></i>
                <a href="https://wa.me/6281234567890" target="_blank" rel="noopener noreferrer">
                    Chat WhatsApp
                </a>
            </p>
        </div>

        <!-- COLUMN 3 -->
        <div class="yst-col">
            <h4>YogyaStay</h4>

            <a href="#" class="yst-link">Tentang Kami</a>
            <a href="blog.php" class="yst-link">Blog</a>
            <a href="#" class="yst-link">Kebijakan Privasi</a>
        </div>
    </div>

    <div class="yst-bottom">
        Copyright © 2025 YogyaStay. All rights reserved.
    </div>
</footer>

</body>
</html>