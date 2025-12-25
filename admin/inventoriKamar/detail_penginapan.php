<?php
require_once '../../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* PROTEKSI ADMIN */
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== 'admin') {
    header("Location: ../../autentikasi/login.php");
    exit;
}

// Ambil ID penginapan
$id_penginapan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_penginapan <= 0) {
    header("Location: inventori.php");
    exit;
}

// Query detail penginapan
$sql = "SELECT p.*, kc.nama_kecamatan, kb.nama_kabupaten
        FROM penginapan p
        LEFT JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
        LEFT JOIN kabupaten kb ON p.id_kabupaten = kb.id_kabupaten
        WHERE p.id_penginapan = $id_penginapan";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error query penginapan: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) == 0) {
    header("Location: inventori.php");
    exit;
}

$penginapan = mysqli_fetch_assoc($result);

// Query gambar penginapan
$sql_gambar = "SELECT * FROM gambar_penginapan WHERE id_penginapan = $id_penginapan ORDER BY is_thumbnail DESC";
$result_gambar = mysqli_query($conn, $sql_gambar);

if (!$result_gambar) {
    die("Error query gambar: " . mysqli_error($conn));
}

// Query tipe kamar 
$sql_kamar = "SELECT tk.*
              FROM tipe_kamar tk 
              WHERE tk.id_penginapan = $id_penginapan 
              ORDER BY tk.harga_per_malam ASC";
$result_kamar = mysqli_query($conn, $sql_kamar);

if (!$result_kamar) {
    die("Error query tipe kamar: " . mysqli_error($conn));
}

// Query fasilitas
$sql_fasilitas = "SELECT f.* 
                  FROM fasilitas f
                  INNER JOIN penginapan_fasilitas pf ON f.id_fasilitas = pf.id_fasilitas
                  WHERE pf.id_penginapan = $id_penginapan";
$result_fasilitas = mysqli_query($conn, $sql_fasilitas);

if (!$result_fasilitas) {
    die("Error query fasilitas: " . mysqli_error($conn));
}

// Query kontak
$sql_kontak = "SELECT * FROM kontak_penginapan WHERE id_penginapan = $id_penginapan";
$result_kontak = mysqli_query($conn, $sql_kontak);

if (!$result_kontak) {
    die("Error query kontak: " . mysqli_error($conn));
}

$kontak_data = [];
while($k = mysqli_fetch_assoc($result_kontak)) {
    $kontak_data[$k['jenis_kontak']] = $k['isi_kontak'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail - <?= htmlspecialchars($penginapan['nama_penginapan']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #fff7ed;
}

/* ADMIN HEADER */
.admin-header {
    background: white;
    padding: 20px 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 100;
}

.admin-header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #f3f4f6;
    border-radius: 10px;
    text-decoration: none;
    color: #374151;
    font-weight: 600;
    transition: all 0.3s;
}

.back-btn:hover {
    background: #e5e7eb;
    transform: translateX(-5px);
}

.admin-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    padding: 10px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-edit {
    background: #fef3c7;
    color: #92400e;
}

.btn-edit:hover {
    background: #fde68a;
    transform: translateY(-2px);
}

.btn-delete {
    background: #fee2e2;
    color: #991b1b;
}

.btn-delete:hover {
    background: #fecaca;
    transform: translateY(-2px);
}

/* HERO SECTION */
.hero-section {
    background: white;
    padding: 30px 0 20px;
}

.hero-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.hero-header {
    margin-bottom: 20px;
}

.hero-title {
    font-size: 32px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 8px;
}

.hero-location {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #666;
    font-size: 16px;
}

.hero-location-icon {
    color: #f5a742;
    font-size: 20px;
}

/* GALLERY */
.gallery-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 10px;
    border-radius: 20px;
    overflow: hidden;
    max-height: 400px;
}

.gallery-main {
    position: relative;
    overflow: hidden;
}

.gallery-main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-sidebar {
    display: grid;
    grid-template-rows: 1fr 1fr;
    gap: 10px;
}

.gallery-item {
    position: relative;
    overflow: hidden;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.gallery-item:hover img {
    transform: scale(1.1);
}

/* TAB NAVIGATION */
.tab-navigation {
    background: white;
    border-bottom: 2px solid #e5e7eb;
    position: sticky;
    top: 80px;
    z-index: 90;
    margin-top: 20px;
}

.tab-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    gap: 30px;
}

.tab-item {
    padding: 15px 0;
    font-size: 15px;
    font-weight: 600;
    color: #666;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s;
    text-decoration: none;
}

.tab-item:hover {
    color: #f5a742;
}

.tab-item.active {
    color: #f5a742;
    border-bottom-color: #f5a742;
}

/* MAIN CONTENT */
.detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.detail-main {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

/* SECTION CARD */
.section-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.section-title {
    font-size: 24px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 3px solid #f5a742;
}

.section-description {
    color: #555;
    line-height: 1.8;
    font-size: 15px;
}

/* TIPE KAMAR */
.room-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.room-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s;
}

.room-card:hover {
    border-color: #f5a742;
    box-shadow: 0 5px 20px rgba(245, 167, 66, 0.2);
}

.room-image {
    height: 180px;
    overflow: hidden;
}

.room-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.room-info {
    padding: 20px;
}

.room-name {
    font-size: 18px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 10px;
}

.room-features {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 15px;
}

.room-price-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #e5e7eb;
}

.room-price-label {
    font-size: 13px;
    color: #666;
}

.room-price {
    font-size: 18px;
    font-weight: bold;
    color: #f5a742;
}

/* FASILITAS */
.facilities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 15px;
}

.facility-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-align: center;
    transition: all 0.3s;
}

.facility-item:hover {
    border-color: #f5a742;
    background: #fef9e7;
}

.facility-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.facility-name {
    font-size: 14px;
    color: #555;
    font-weight: 600;
}

/* LOKASI */
.location-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.map-container {
    border-radius: 15px;
    overflow: hidden;
    height: 250px;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
}

.address-box {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.address-label {
    font-size: 16px;
    font-weight: 600;
    color: #333;
    margin-bottom: 10px;
}

.address-text {
    color: #666;
    line-height: 1.8;
    font-size: 14px;
    margin-bottom: 15px;
}

/* KONTAK */
.contact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 10px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s;
}

.contact-item:hover {
    border-color: #f5a742;
    background: #fef9e7;
}

.contact-icon {
    font-size: 24px;
    color: #f5a742;
}

.contact-info {
    flex: 1;
}

.contact-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 3px;
}

.contact-value {
    font-size: 14px;
    color: #333;
    font-weight: 600;
}

/* RESPONSIVE */
@media (max-width: 968px) {
    .gallery-grid {
        grid-template-columns: 1fr;
        max-height: none;
    }
    
    .gallery-sidebar {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto;
    }
    
    .location-content {
        grid-template-columns: 1fr;
    }
    
    .room-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-header-content {
        flex-direction: column;
        gap: 15px;
    }
}
</style>
</head>

<body>

<!-- ADMIN HEADER -->
<div class="admin-header">
    <div class="admin-header-content">
        <a href="inventori.php?tipe=<?= strtolower($penginapan['tipe_penginapan']) ?>" class="back-btn">
            <span>←</span>
            <span>Kembali ke Inventori</span>
        </a>
        <div class="admin-actions">
            <a href="edit_penginapan.php?id=<?= $id_penginapan ?>" class="btn-action btn-edit">
                <i class="fa-solid fa-pen"></i>
                <span>Edit</span>
            </a>
            <a href="javascript:void(0)" onclick="confirmDelete()" class="btn-action btn-delete">
                <i class="fa-solid fa-trash"></i>
                <span>Hapus</span>
            </a>
        </div>
    </div>
</div>

<!-- HERO SECTION -->
<div class="hero-section">
    <div class="hero-container">
        <div class="hero-header">
            <h1 class="hero-title"><?= htmlspecialchars($penginapan['nama_penginapan']) ?></h1>
            <div class="hero-location">
                <i class="fa-solid fa-location-dot hero-location-icon"></i>
                <span><?= htmlspecialchars($penginapan['nama_kecamatan']) ?>, <?= htmlspecialchars($penginapan['nama_kabupaten']) ?></span>
            </div>
        </div>
        
        <!-- Gallery -->
        <div class="gallery-grid">
            <?php 
            $gambar_array = [];
            if ($result_gambar && mysqli_num_rows($result_gambar) > 0) {
                mysqli_data_seek($result_gambar, 0);
                while($img = mysqli_fetch_assoc($result_gambar)) {
                    $gambar_array[] = $img['path_gambar'];
                }
            }
            
            // default gambar jika tidak ada upload gambar
            if (empty($gambar_array)) {
                $gambar_array = ['uploads/penginapan/default.jpg'];
            }
            ?>
            
            <div class="gallery-main">
                <img src="../../<?= htmlspecialchars($gambar_array[0]) ?>" alt="<?= htmlspecialchars($penginapan['nama_penginapan']) ?>" onerror="this.src='../../uploads/penginapan/default.jpg'">
            </div>
            <div class="gallery-sidebar">
                <?php for($i = 1; $i <= 2; $i++) { 
                    $img_src = isset($gambar_array[$i]) ? $gambar_array[$i] : $gambar_array[0];
                ?>
                <div class="gallery-item">
                    <img src="../../<?= htmlspecialchars($img_src) ?>" alt="Gallery <?= $i ?>" onerror="this.src='../../uploads/penginapan/default.jpg'">
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- TAB NAVIGATION -->
<div class="tab-navigation">
    <div class="tab-container">
        <a href="#deskripsi" class="tab-item active">Deskripsi</a>
        <a href="#tipe-kamar" class="tab-item">Tipe Kamar</a>
        <a href="#fasilitas" class="tab-item">Fasilitas</a>
        <a href="#lokasi" class="tab-item">Lokasi</a>
        <a href="#kontak" class="tab-item">Kontak</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="detail-container">
    <div class="detail-main">
        
        <!-- Deskripsi -->
        <div id="deskripsi" class="section-card">
            <h2 class="section-title">Deskripsi</h2>
            <div class="section-description">
                <?= nl2br(htmlspecialchars($penginapan['deskripsi'] ?? 'Tidak ada deskripsi')) ?>
            </div>
        </div>
        
        <!-- Tipe Kamar -->
        <div id="tipe-kamar" class="section-card">
            <h2 class="section-title">Tipe Kamar<?php if ($result_kamar && mysqli_num_rows($result_kamar) > 0) { echo ' (' . mysqli_num_rows($result_kamar) . ' Tipe)'; } ?></h2>
            <div class="room-grid">
                <?php 
                if ($result_kamar && mysqli_num_rows($result_kamar) > 0) {
                    mysqli_data_seek($result_kamar, 0);
                    while($kamar = mysqli_fetch_assoc($result_kamar)) { 
                        // gambar penginapan 
                        $gambar_kamar = isset($gambar_array[0]) ? $gambar_array[0] : 'uploads/penginapan/default.jpg';
                ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="../../<?= htmlspecialchars($gambar_kamar) ?>" alt="<?= htmlspecialchars($kamar['nama_tipe']) ?>" onerror="this.src='../../uploads/penginapan/default.jpg'">
                    </div>
                    <div class="room-info">
                        <h3 class="room-name"><?= htmlspecialchars($kamar['nama_tipe']) ?></h3>
                        <div class="room-features">
                            • Kapasitas: <?= $kamar['kapasitas_orang'] ?> orang<br>
                            • Tersedia: <?= $kamar['jumlah_kamar'] ?> kamar<br>
                            <?php if (!empty($kamar['deskripsi'])): ?>
                            • <?= htmlspecialchars($kamar['deskripsi']) ?>
                            <?php endif; ?>
                        </div>
                        <div class="room-price-box">
                            <div>
                                <div class="room-price-label">Per malam</div>
                                <div class="room-price">Rp <?= number_format($kamar['harga_per_malam'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else { ?>
                <div style="text-align: center; padding: 40px; color: #9ca3af;">
                    <div style="font-size: 48px; margin-bottom: 15px;">🏠</div>
                    <p style="font-size: 16px; margin: 0;">Belum ada data tipe kamar</p>
                </div>
                <?php } ?>
            </div>
        </div>
        
        <!-- Fasilitas -->
        <div id="fasilitas" class="section-card">
            <h2 class="section-title">Fasilitas</h2>
            <div class="facilities-grid">
                <?php 
                if ($result_fasilitas && mysqli_num_rows($result_fasilitas) > 0) {
                    mysqli_data_seek($result_fasilitas, 0);
                    while($fas = mysqli_fetch_assoc($result_fasilitas)) { 
                ?>
                <div class="facility-item">
                    <div class="facility-icon">✨</div>
                    <div class="facility-name"><?= htmlspecialchars($fas['nama_fasilitas']) ?></div>
                </div>
                <?php 
                    }
                } else { ?>
                <p style="color: #666;">Belum ada data fasilitas</p>
                <?php } ?>
            </div>
        </div>
        
        <!-- Lokasi -->
        <div id="lokasi" class="section-card">
            <h2 class="section-title">Lokasi</h2>
            <div class="location-content">
                <div class="map-container">
                    <p style="color: #9ca3af;">📍 Peta Google Maps</p>
                </div>
                <div class="address-box">
                    <div class="address-label">Alamat Lengkap:</div>
                    <div class="address-text">
                        <strong><?= htmlspecialchars($penginapan['nama_kecamatan']) ?>, <?= htmlspecialchars($penginapan['nama_kabupaten']) ?></strong><br>
                        <?= nl2br(htmlspecialchars($penginapan['alamat'] ?? 'Alamat tidak tersedia')) ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tentang & Kontak -->
        <?php if (!empty($penginapan['tentang_kami']) || !empty($kontak_data)): ?>
        <div class="section-card">
            <h2 class="section-title">Tentang Kami</h2>
            <div class="section-description">
                <?= nl2br(htmlspecialchars($penginapan['tentang_kami'] ?? 'Tidak ada informasi')) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Kontak -->
        <?php if (!empty($kontak_data)): ?>
        <div id="kontak" class="section-card">
            <h2 class="section-title">Kontak Kami</h2>
            <div class="contact-grid">
                <?php if (isset($kontak_data['telepon'])): ?>
                <div class="contact-item">
                    <div class="contact-icon">📞</div>
                    <div class="contact-info">
                        <div class="contact-label">Telepon</div>
                        <div class="contact-value"><?= htmlspecialchars($kontak_data['telepon']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($kontak_data['whatsapp'])): ?>
                <div class="contact-item">
                    <div class="contact-icon">💬</div>
                    <div class="contact-info">
                        <div class="contact-label">WhatsApp</div>
                        <div class="contact-value"><?= htmlspecialchars($kontak_data['whatsapp']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (isset($kontak_data['email'])): ?>
                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div class="contact-info">
                        <div class="contact-label">Email</div>
                        <div class="contact-value"><?= htmlspecialchars($kontak_data['email']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<script>
// Tab navigation
document.querySelectorAll('.tab-item').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        
        document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offset = 150;
            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// tab aktif saat scroll
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('.section-card');
    const scrollPos = window.pageYOffset + 200;
    
    sections.forEach(section => {
        if (section.offsetTop <= scrollPos && (section.offsetTop + section.offsetHeight) > scrollPos) {
            const id = section.getAttribute('id');
            if (id) {
                document.querySelectorAll('.tab-item').forEach(tab => {
                    tab.classList.remove('active');
                    if (tab.getAttribute('href') === '#' + id) {
                        tab.classList.add('active');
                    }
                });
            }
        }
    });
});

// konfirmasi hapus
function confirmDelete() {
    if (confirm('⚠️ PERINGATAN!\n\nApakah Anda yakin ingin menghapus penginapan ini?\n\nSemua data termasuk:\n• Tipe kamar\n• Gambar\n• Fasilitas\n• Kontak\n\nakan dihapus PERMANEN dan tidak dapat dikembalikan!')) {
        window.location.href = 'inventori.php?delete=<?= $id_penginapan ?>&from_tipe=<?= strtolower($penginapan['tipe_penginapan']) ?>';
    }
}
</script>

</body>
</html>