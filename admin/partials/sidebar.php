<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();

    header("Location: $baseUrl/autentikasi/login.php");
    exit;
}



$current = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['nama'] ?? 'Admin';

/* BASE URL ROOT */
$baseUrl = "/UAS/YogyaStay-WebAkomodasi";

/* BASE URL ADMIN */
$baseAdmin = $baseUrl . "/admin";

/* LOGO */
$logoYogyaStay = $baseUrl . "/assets/img/logo.png";
$logoAdmin     = $baseUrl . "/assets/img/admin.png";

?>

<!-- LINK FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">

<style>
/* ========== STYLING SIDEBAR ========== */
.sidebar{
    width:240px;
    height:100vh;
    background:#9aa7c5;
    color:white;
    position:fixed;
    left:0;
    top:0;
    display:flex;
    flex-direction:column;
    z-index:1001;
    transition:.3s;
}

.sidebar-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.4);
    display:none;
    z-index:1000;
}

.sidebar.active{transform:translateX(0)}

.sidebar-header{
    padding:25px 20px;
    border-bottom:1px solid rgba(255,255,255,.3);
}

.sidebar-header img{width:42px;margin-bottom:10px}

.footer-user{
    display:flex;
    align-items:center;
    gap:10px;
}

.footer-user i{
    font-size:36px;
    color:white;
}

.footer-user-text{
    display:flex;
    flex-direction:column;
    line-height:1.1;
}

.footer-user-text strong{
    font-size:14px;
    font-weight:600;
}

.footer-user-text span{
    font-size:12px;
    opacity:.85;
}

.logout-btn{
    color:white;
    font-size:20px;
    text-decoration:none;
}

.logout-btn:hover{
    opacity:.7;
}


.menu{
    padding:20px 15px;
    display:flex;
    flex-direction:column;
    gap:10px;
}

.menu a{
    color:white;
    text-decoration:none;
    padding:12px 14px;
    border-radius:10px;
    display:flex;
    gap:10px;
    align-items:center;
}

.menu a:hover{background:rgba(255,255,255,.2)}
.menu a.active{background:#f4b740;font-weight:600}

.sidebar-footer{
    margin-top:auto;
    padding:15px 20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-top:1px solid rgba(255,255,255,.3);
}

.user{display:flex;gap:10px;align-items:center}
.user img{width:36px;border-radius:50%}

/* ========================== */
/* MAIN CONTENT FIXED PADDING */
/* ========================== */
.main-content {
    margin-left: 240px; /* agar konten tidak tertutup sidebar */
    padding: 40px;
    box-sizing: border-box;
    min-height: 100vh;
}

@media(max-width:992px){
    .sidebar{transform:translateX(-100%)}
    .sidebar-overlay.active{display:block}
}
</style>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">

    <div class="sidebar-header">
        <img src="<?= $logoYogyaStay ?>">
        <h2>YogyaStay</h2>
    </div>

    <nav class="menu">
        <a href="<?= $baseAdmin ?>/dashboard.php"
           class="<?= $current=='dashboard.php'?'active':'' ?>">
           <i class="fas fa-home"></i> Dashboard
        </a>

        <a href="<?= $baseAdmin ?>/inventoriKamar/inventori.php"
           class="<?= $current=='inventori.php'?'active':'' ?>">
           <i class="fas fa-bed"></i> Inventori Kamar & Harga
        </a>

        <a href="<?= $baseAdmin ?>/managemenUser/users.php"
           class="<?= $current=='users.php'?'active':'' ?>">
           <i class="fas fa-users"></i> Manajemen User
        </a>

        <a href="<?= $baseAdmin ?>/pembatalan/pembatalan.php"
           class="<?= $current=='pembatalan.php'?'active':'' ?>">
           <i class="fas fa-times-circle"></i> Pembatalan Kamar
        </a>

        <a href="<?= $baseAdmin ?>/manajemenKonten/konten.php"
           class="<?= $current=='konten.php'?'active':'' ?>">
           <i class="fas fa-file-alt"></i> Manajemen Konten
        </a>

        <a href="<?= $baseAdmin ?>/log/log.php"
           class="<?= $current=='log.php'?'active':'' ?>">
           <i class="fas fa-check"></i> Log Aktivitas
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="footer-user">
            <i class="fas fa-user-circle"></i>
            <div class="footer-user-text">
                <strong><?= $adminName ?></strong>
                <span>Admin</span>
            </div>
        </div>

       <a href="<?= $baseUrl ?>/autentikasi/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
        </a>


    </div>

</div>

<script>
function toggleSidebar(){
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
</script>
