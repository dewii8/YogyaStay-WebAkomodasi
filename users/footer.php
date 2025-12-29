<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Footer YogyaStay</title>

    <!-- GOOGLE FONT POPPINS -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
    /* FONT */
    * { font-family: "Poppins", sans-serif; }

    /* WRAPPER */
    .yst-footer {
        position: relative;
        background: url("../assets/img/footer.jpg") center/cover no-repeat;
        padding: 70px 70px 40px;
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
        transition: .25s;
    }

    .yst-social a:hover {
        background: #F7B24C;
    }

    /* HEADING */
    .yst-col h4 {
        color: #F6B049;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 20px;
    }

    /* CONTACT ITEM */
    .yst-contact-item {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15px;
        color: #FFFFFF;
        margin-bottom: 12px;
    }

    .yst-contact-item i {
        font-size: 18px;
    }

    .yst-contact-item a {
        text-decoration: none;
        color: inherit;
    }

    .yst-contact-item a:hover {
        text-decoration: underline;
    }

    /* LINKS */
    .yst-link {
        display: block;
        margin-bottom: 12px;
        font-size: 15px;
        color: #FFFFFF;
        text-decoration: none;
    }

    .yst-link:hover {
        color: #F6B049;
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

    /* RESPONSIVE */
    @media (max-width: 900px) {
        .yst-footer-container {
            grid-template-columns: 1fr;
            gap: 40px;
        }
    }

    @media (max-width: 900px) {
        .yst-footer-container {
            grid-template-columns: 1fr;
            gap: 40px;
            text-align: center;
        }

        .yst-logo-area{
            justify-content: center;
        }
        
        .yst-col {
            text-align: center !important;
        }

        .yst-social {
            justify-content: center;
        }

        .yst-contact-item {
            justify-content: center !important;
        }

        .yst-link {
            text-align: center;
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
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-tiktok"></i></a>
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
                <a href="https://wa.me/6281234567890" target="_blank">
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