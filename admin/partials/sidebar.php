<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil nama admin dari session
$adminName = $_SESSION['nama'] ?? 'Admin';
$adminRole = $_SESSION['role_id'] ?? 'admin';

// Deteksi halaman aktif
$current_page = basename($_SERVER['PHP_SELF']);

// BASE URL - sesuaikan dengan struktur folder Anda
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script = $_SERVER['SCRIPT_NAME'];
$path_parts = explode('/', $script);

// Cari posisi 'admin' di path
$admin_pos = array_search('admin', $path_parts);
if ($admin_pos !== false) {
    $base_path = implode('/', array_slice($path_parts, 0, $admin_pos));
    $baseUrl = $protocol . $host . $base_path;
    $baseAdmin = $baseUrl . '/admin';
} else {
    // Fallback jika struktur berbeda
    $baseUrl = dirname(dirname($protocol . $host . $script));
    $baseAdmin = $baseUrl . '/admin';
}

// Logo path
$logoYogyaStay = $baseUrl . "/assets/img/logo.png";
?>

<!-- LINK FONT AWESOME -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ========== SIDEBAR STYLING ========== */
.sidebar {
    width: 240px;
    height: 100vh;
    background: #9aa7c5;
    color: white;
    position: fixed;
    left: 0;
    top: 0;
    display: flex;
    flex-direction: column;
    z-index: 1001;
    transition: transform 0.3s ease;
    overflow-y: auto;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.sidebar-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    z-index: 1000;
}

.sidebar-overlay.show {
    display: block;
}

.sidebar-header {
    padding: 25px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.3);
}

.sidebar-header img {
    width: 42px;
    margin-bottom: 10px;
}

.sidebar-header h2 {
    font-size: 24px;
    font-weight: 700;
    margin: 0;
}

.menu {
    padding: 20px 15px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    flex: 1;
}

.menu a {
    color: white;
    text-decoration: none;
    padding: 12px 14px;
    border-radius: 10px;
    display: flex;
    gap: 10px;
    align-items: center;
    transition: all 0.2s;
}

.menu a:hover {
    background: rgba(255, 255, 255, 0.2);
}

.menu a.active {
    background: #f4b740;
    font-weight: 600;
}

.sidebar-footer {
    margin-top: auto;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-top: 1px solid rgba(255, 255, 255, 0.3);
}

.footer-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.footer-user i {
    font-size: 36px;
    color: white;
}

.footer-user-text {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
}

.footer-user-text strong {
    font-size: 14px;
    font-weight: 600;
}

.footer-user-text span {
    font-size: 12px;
    opacity: 0.85;
}

.logout-btn {
    color: white;
    font-size: 20px;
    text-decoration: none;
}

.logout-btn:hover {
    opacity: 0.7;
}

/* ========================== */
/* RESPONSIVE */
/* ========================== */
@media(max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
}
</style>

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <!-- HEADER -->
    <div class="sidebar-header">
        <?php if(file_exists($logoYogyaStay)): ?>
        <img src="<?= $logoYogyaStay ?>" alt="YogyaStay">
        <?php endif; ?>
        <h2>YogyaStay</h2>
    </div>

    <!-- MENU NAVIGATION -->
    <nav class="menu">
        <a href="<?= $baseAdmin ?>/dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= $baseAdmin ?>/inventoriKamar/inventori.php" class="<?= $current_page == 'inventori.php' ? 'active' : '' ?>">
            <i class="fas fa-bed"></i>
            <span>Inventori Kamar & Harga</span>
        </a>

        <a href="<?= $baseAdmin ?>/managemenUser/users.php" class="<?= $current_page == 'users.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i>
            <span>Manajemen User</span>
        </a>

        <a href="<?= $baseAdmin ?>/pembatalan/pembatalan.php" class="<?= $current_page == 'pembatalan.php' ? 'active' : '' ?>">
            <i class="fas fa-times-circle"></i>
            <span>Pembatalan Kamar</span>
        </a>

        <a href="<?= $baseAdmin ?>/manajemenKonten/konten.php" class="<?= $current_page == 'konten.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i>
            <span>Manajemen Konten</span>
        </a>

        <a href="<?= $baseAdmin ?>/log/log.php" class="<?= $current_page == 'log.php' ? 'active' : '' ?>">
            <i class="fas fa-check"></i>
            <span>Log Aktivitas</span>
        </a>
    </nav>

    <!-- FOOTER -->
    <div class="sidebar-footer">
        <div class="footer-user">
            <i class="fas fa-user-circle"></i>
            <div class="footer-user-text">
                <strong><?= htmlspecialchars($adminName) ?></strong>
                <span><?= ucfirst($adminRole) ?></span>
            </div>
        </div>

        <a href="<?= $baseUrl ?>/autentikasi/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar) {
        sidebar.classList.toggle('active');
    }
    
    if (overlay) {
        overlay.classList.toggle('show');
    }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const hamburger = document.querySelector('.hamburger');
    
    if (window.innerWidth <= 992 && 
        sidebar && 
        sidebar.classList.contains('active') &&
        !sidebar.contains(e.target) && 
        (!hamburger || !hamburger.contains(e.target))) {
        
        sidebar.classList.remove('active');
        if(overlay) overlay.classList.remove('show');
    }
});

// Close sidebar on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            if(overlay) overlay.classList.remove('show');
        }
    }
});
</script>