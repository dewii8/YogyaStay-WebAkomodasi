<?php
require_once '../config.php';

// Cek apakah user sudah login
$is_logged_in = isset($_SESSION['user_id']);

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Ambil ID penginapan dari URL
$id_penginapan = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_penginapan <= 0) {
    header("Location: penginapan.php");
    exit;
}

// Query untuk mengambil detail penginapan
$sql = "SELECT p.*, kc.nama_kecamatan, kb.nama_kabupaten
        FROM penginapan p
        LEFT JOIN kecamatan kc ON p.id_kecamatan = kc.id_kecamatan
        LEFT JOIN kabupaten kb ON p.id_kabupaten = kb.id_kabupaten
        WHERE p.id_penginapan = $id_penginapan";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header("Location: penginapan.php");
    exit;
}

$penginapan = mysqli_fetch_assoc($result);

// Query untuk mengambil GAMBAR PENGINAPAN
$sql_gambar = "SELECT * FROM gambar_penginapan 
               WHERE id_penginapan = $id_penginapan 
               ORDER BY is_thumbnail DESC, created_at ASC";
$result_gambar = mysqli_query($conn, $sql_gambar);

// Simpan gambar ke array
$gambar_array = [];
if ($result_gambar && mysqli_num_rows($result_gambar) > 0) {
    while($img = mysqli_fetch_assoc($result_gambar)) {
        $gambar_array[] = $img['path_gambar'];
    }
}

// Jika tidak ada gambar, gunakan default
if (empty($gambar_array)) {
    $gambar_array = ['uploads/penginapan/default.jpg'];
}

// Query untuk mengambil tipe kamar
$sql_kamar = "SELECT * FROM tipe_kamar 
              WHERE id_penginapan = $id_penginapan 
              ORDER BY harga_per_malam ASC";
$result_kamar = mysqli_query($conn, $sql_kamar);

if (!$result_kamar) {
    die("Error query tipe kamar: " . mysqli_error($conn));
}

// Query untuk mengambil FASILITAS
$sql_fasilitas = "SELECT f.* 
                  FROM fasilitas f
                  INNER JOIN penginapan_fasilitas pf ON f.id_fasilitas = pf.id_fasilitas
                  WHERE pf.id_penginapan = $id_penginapan";
$result_fasilitas = mysqli_query($conn, $sql_fasilitas);

if (!$result_fasilitas) {
    die("Error query fasilitas: " . mysqli_error($conn));
}

// Query untuk mengambil KONTAK
$sql_kontak = "SELECT * FROM kontak_penginapan WHERE id_penginapan = $id_penginapan";
$result_kontak = mysqli_query($conn, $sql_kontak);

if (!$result_kontak) {
    die("Error query kontak: " . mysqli_error($conn));
}

$kontak_data = [];
while($k = mysqli_fetch_assoc($result_kontak)) {
    $kontak_data[$k['jenis_kontak']] = $k['isi_kontak'];
}

// Include header
$page_title = 'Detail Penginapan';
require_once 'header.php';
?>

<style>
/* ========= HERO SECTION ========= */
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

/* ========= GALLERY ========= */
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

/* ========= TAB NAVIGATION ========= */
.tab-navigation {
    background: white;
    border-bottom: 2px solid #e5e7eb;
    position: sticky;
    top: 0;
    z-index: 100;
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

/* ========= MAIN CONTENT (CENTERED, NO SIDEBAR) ========= */
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

/* ========= SECTION CARD ========= */
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

/* ========= TIPE KAMAR ========= */
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

.btn-book-room {
    width: 100%;
    background: #fde047;
    border: none;
    padding: 12px;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    margin-top: 15px;
    transition: all 0.3s;
}

.btn-book-room:hover {
    background: #fcd34d;
    transform: translateY(-2px);
}

/* ========= FASILITAS ========= */
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

/* ========= LOKASI ========= */
.location-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.map-container {
    border-radius: 15px;
    overflow: hidden;
    height: 250px;
}

.map-container iframe {
    width: 100%;
    height: 100%;
    border: none;
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

.btn-maps {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #f5a742;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-maps:hover {
    gap: 12px;
}

/* ========= TENTANG & KONTAK ========= */
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f9fafb;
    border-radius: 10px;
    margin-bottom: 10px;
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

.contact-value a {
    color: #333;
    text-decoration: none;
}

.contact-value a:hover {
    color: #f5a742;
}

/* ========= LOGIN POPUP MODAL ========= */
.login-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

.login-modal.show {
    display: flex;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.login-modal-content {
    background: white;
    border-radius: 20px;
    padding: 40px;
    max-width: 450px;
    width: 90%;
    text-align: center;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        transform: translateY(50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.login-modal-icon {
    font-size: 60px;
    margin-bottom: 20px;
}

.login-modal-title {
    font-size: 24px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 15px;
}

.login-modal-message {
    color: #666;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 30px;
}

.login-modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.btn-login-modal {
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

.btn-login-primary {
    background: #f5a742;
    color: white;
}

.btn-login-primary:hover {
    background: #e89632;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 167, 66, 0.4);
}

.btn-login-secondary {
    background: #e5e7eb;
    color: #666;
}

.btn-login-secondary:hover {
    background: #d1d5db;
}

/* ========= RESPONSIVE ========= */
@media (max-width: 968px) {
    .gallery-grid {
        grid-template-columns: 1fr;
        max-height: none;
    }

    .gallery-sidebar {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto;
    }

    .location-content,
    .info-grid {
        grid-template-columns: 1fr;
    }

    .room-grid {
        grid-template-columns: 1fr;
    }

    .tab-container {
        overflow-x: auto;
        gap: 20px;
    }

    .login-modal-content {
        padding: 30px 25px;
    }

    .login-modal-buttons {
        flex-direction: column;
    }

    .btn-login-modal {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .hero-title {
        font-size: 24px;
    }

    .section-card {
        padding: 20px;
    }

    .section-title {
        font-size: 20px;
    }

    .facilities-grid {
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    }
}
</style>

<!-- ========= HERO SECTION ========= -->
<div class="hero-section">
    <div class="hero-container">
        <div class="hero-header">
            <h1 class="hero-title"><?= htmlspecialchars($penginapan['nama_penginapan']) ?></h1>
            <div class="hero-location">
                <i class="bi bi-geo-alt"></i>
                <span><?= htmlspecialchars($penginapan['nama_kecamatan']) ?>, <?= htmlspecialchars($penginapan['nama_kabupaten']) ?></span>
            </div>
        </div>

        <!-- Gallery -->
        <div class="gallery-grid">
            <div class="gallery-main">
                <img src="../<?= htmlspecialchars($gambar_array[0]) ?>" alt="<?= htmlspecialchars($penginapan['nama_penginapan']) ?>" onerror="this.src='../uploads/penginapan/default.jpg'">
            </div>
            <div class="gallery-sidebar">
                <?php for($i = 1; $i <= 2; $i++) { 
                    $img_src = isset($gambar_array[$i]) ? $gambar_array[$i] : $gambar_array[0];
                ?>
                <div class="gallery-item">
                    <img src="../<?= htmlspecialchars($img_src) ?>" alt="Gallery <?= $i ?>" onerror="this.src='../uploads/penginapan/default.jpg'">
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- ========= TAB NAVIGATION ========= -->
<div class="tab-navigation">
    <div class="tab-container">
        <a href="#deskripsi" class="tab-item active">Deskripsi</a>
        <a href="#tipe-kamar" class="tab-item">Tipe Kamar</a>
        <a href="#fasilitas" class="tab-item">Fasilitas</a>
        <a href="#lokasi" class="tab-item">Lokasi</a>
        <a href="#tentang" class="tab-item">Tentang Kami</a>
        <a href="#kontak" class="tab-item">Kontak</a>
    </div>
</div>

<!-- ========= MAIN CONTENT (CENTERED, NO SIDEBAR) ========= -->
<div class="detail-container">
    <div class="detail-main">

        <!-- Deskripsi -->
        <div id="deskripsi" class="section-card">
            <h2 class="section-title">Deskripsi</h2>
            <div class="section-description">
                <?= nl2br(htmlspecialchars($penginapan['deskripsi'] ?? 'Tidak ada deskripsi tersedia.')) ?>
            </div>
        </div>

        <!-- Tipe Kamar -->
        <div id="tipe-kamar" class="section-card">
            <h2 class="section-title">Tipe Kamar</h2>
            <div class="room-grid">
                <?php 
                if ($result_kamar && mysqli_num_rows($result_kamar) > 0) {
                    while($kamar = mysqli_fetch_assoc($result_kamar)) { 
                ?>
                <div class="room-card">
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
                        <button class="btn-book-room" onclick="checkLoginBeforeBooking(<?= $id_penginapan ?>, <?= $kamar['id_tipe_kamar'] ?>)">Pesan Kamar</button>
                    </div>
                </div>
                <?php 
                    }
                } else {
                ?>
                <div style="text-align: center; padding: 40px; color: #9ca3af; grid-column: 1/-1;">
                    <i class="bi bi-house"></i>
                    <p style="font-size: 16px;">Belum ada data tipe kamar</p>
                </div>
                <?php } ?>
            </div>
        </div>

        <!-- Fasilitas -->
        <div id="fasilitas" class="section-card">
            <h2 class="section-title">Fasilitas</h2>
            <div class="facilities-grid">
                <?php 
                // Mapping fasilitas ke ikon Bootstrap
                $icon_map = [
                    'WiFi' => 'bi-wifi',
                    'AC' => 'bi-snow',
                    'TV' => 'bi-tv',
                    'Kamar Mandi Dalam' => 'bi-water',
                    'Air Panas' => 'bi-droplet-fill',
                    'Kolam Renang' => 'bi-water',
                    'Parkir' => 'bi-p-square',
                    'Resepsionis 24 Jam' => 'bi-clock',
                    'Sarapan' => 'bi-cup-hot',
                    'Dapur' => 'bi-fire',
                    'Private Pool' => 'bi-water',
                    'Balkon' => 'bi-door-open',
                    'View Alam' => 'bi-eye',
                    'Lift' => 'bi-arrow-up-square',
                    'Gym' => 'bi-heart-pulse',
                    'Meeting Room' => 'bi-people'
                ];

                if ($result_fasilitas && mysqli_num_rows($result_fasilitas) > 0) {
                    while($fas = mysqli_fetch_assoc($result_fasilitas)) { 
                        // Cari icon yang cocok berdasarkan nama fasilitas
                        $icon_class = 'bi-star'; // default icon
                        foreach ($icon_map as $key => $icon) {
                            if (stripos($fas['nama_fasilitas'], $key) !== false) {
                                $icon_class = $icon;
                                break;
                            }
                        }
                ?>
                <div class="facility-item">
                    <div class="facility-icon">
                        <i class="bi <?= $icon_class ?>"></i>
                    </div>
                    <div class="facility-name"><?= htmlspecialchars($fas['nama_fasilitas']) ?></div>
                </div>
                <?php 
                    }
                } else { 
                ?>
                <p style="color: #666; grid-column: 1/-1;">Belum ada data fasilitas</p>
                <?php } ?>
            </div>
        </div>

        <!-- Lokasi -->
        <div id="lokasi" class="section-card">
            <h2 class="section-title">Peta dan Alamat Lokasi</h2>
            <div class="location-content">
                <div class="map-container">
                    <?php 
                    // Cek apakah data koordinat tersedia dan valid
                    if (!empty($penginapan['latitude']) && !empty($penginapan['longitude']) 
                        && isValidCoordinate($penginapan['latitude'], $penginapan['longitude'])) {
                        // Buat URL Google Maps dengan koordinat dari database
                        $latitude = $penginapan['latitude'];
                        $longitude = $penginapan['longitude'];

                        // URL Google Maps tanpa API key
                        $map_url = "https://maps.google.com/maps?q={$latitude},{$longitude}&z=15&output=embed";
                    } else {
                        // Fallback: gunakan nama penginapan dan alamat untuk pencarian
                        $search_query = urlencode($penginapan['nama_penginapan'] . ' ' . $penginapan['alamat'] . ' ' . $penginapan['nama_kecamatan'] . ' ' . $penginapan['nama_kabupaten']);
                        $map_url = "https://maps.google.com/maps?q={$search_query}&z=15&output=embed";
                    }
                    ?>
                    <iframe 
                        src="<?= htmlspecialchars($map_url) ?>" 
                        allowfullscreen="" 
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                <div class="address-box">
                    <div class="address-label">Alamat Lengkap:</div>
                    <div class="address-text">
                        <?php 
                        $alamat_parts = [];

                        if (!empty($penginapan['alamat'])) {
                            $alamat_parts[] = $penginapan['alamat'];
                        }

                        if (!empty($penginapan['nama_kecamatan'])) {
                            $alamat_parts[] = $penginapan['nama_kecamatan'];
                        }

                        if (!empty($penginapan['nama_kabupaten'])) {
                            $alamat_parts[] = $penginapan['nama_kabupaten'];
                        }

                        $alamat_lengkap = !empty($alamat_parts) ? implode(', ', $alamat_parts) : 'Alamat belum tersedia';
                        echo htmlspecialchars($alamat_lengkap);
                        ?>
                    </div>

                    <?php 
                    // Tombol Google Maps dengan koordinat atau alamat
                    if (!empty($penginapan['latitude']) && !empty($penginapan['longitude']) 
                        && isValidCoordinate($penginapan['latitude'], $penginapan['longitude'])) {
                        // Jika ada koordinat, gunakan koordinat
                        $maps_link = "https://www.google.com/maps/search/?api=1&query={$penginapan['latitude']},{$penginapan['longitude']}";
                    } else {
                        // Jika tidak ada koordinat, gunakan nama dan alamat
                        $search_text = urlencode($penginapan['nama_penginapan'] . ' ' . implode(' ', $alamat_parts));
                        $maps_link = "https://www.google.com/maps/search/?api=1&query={$search_text}";
                    }
                    ?>
                    <a href="<?= htmlspecialchars($maps_link) ?>" target="_blank" class="btn-maps">
                        Lihat di Google Maps →
                    </a>

                    <?php 
                    // Tampilkan koordinat jika tersedia
                    if (!empty($penginapan['latitude']) && !empty($penginapan['longitude']) 
                        && isValidCoordinate($penginapan['latitude'], $penginapan['longitude'])) { 
                    ?>
                    <div class="coordinate-info" style="margin-top: 10px; font-size: 12px; color: #666;">
                        <i class="bi bi-pin-map"></i> Koordinat: <?= htmlspecialchars($penginapan['latitude']) ?>, <?= htmlspecialchars($penginapan['longitude']) ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Tentang Kami & Kontak -->
        <div class="info-grid">
            <!-- Tentang Kami -->
            <div id="tentang" class="section-card">
                <h2 class="section-title">Tentang Kami</h2>
                <div class="section-description">
                    <?= nl2br(htmlspecialchars($penginapan['tentang_kami'] ?? 'Informasi tentang kami belum tersedia.')) ?>
                </div>
            </div>

            <!-- Kontak -->
            <?php if (!empty($kontak_data)): ?>
            <div id="kontak" class="section-card">
                <h2 class="section-title">Kontak Kami</h2>

                <?php if (isset($kontak_data['telepon'])): ?>
                <div class="contact-item">
                    <i class="bi bi-telephone"></i>
                    <div class="contact-info">
                        <div class="contact-label">Telepon</div>
                        <div class="contact-value">
                            <a href="tel:<?= htmlspecialchars($kontak_data['telepon']) ?>"><?= htmlspecialchars($kontak_data['telepon']) ?></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($kontak_data['whatsapp'])): ?>
                <div class="contact-item">
                    <i class="bi bi-chat-dots"></i>
                    <div class="contact-info">
                        <div class="contact-label">WhatsApp</div>
                        <div class="contact-value">
                            <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $kontak_data['whatsapp']) ?>" target="_blank"><?= htmlspecialchars($kontak_data['whatsapp']) ?></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (isset($kontak_data['email'])): ?>
                <div class="contact-item">
                    <i class="bi bi-envelope"></i>
                    <div class="contact-info">
                        <div class="contact-label">Email</div>
                        <div class="contact-value">
                            <a href="mailto:<?= htmlspecialchars($kontak_data['email']) ?>"><?= htmlspecialchars($kontak_data['email']) ?></a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div id="kontak" class="section-card">
                <h2 class="section-title">Kontak Kami</h2>
                <p style="color: #666;">Informasi kontak belum tersedia.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- ========= LOGIN POPUP MODAL ========= -->
<div id="loginModal" class="login-modal">
    <div class="login-modal-content">
       <div class="login-modal-icon"><i class="bi bi-lock-fill"></i></div>
        <h3 class="login-modal-title">Login untuk Memesan Kamar</h3>
        <p class="login-modal-message">Anda harus login terlebih dahulu untuk melakukan pemesanan kamar</p>
        <div class="login-modal-buttons">
            <button class="btn-login-modal btn-login-primary" onclick="goToLogin()">Login Sekarang</button>
            <button class="btn-login-modal btn-login-secondary" onclick="closeLoginModal()">Batal</button>
        </div>
    </div>
</div>

<script>
// Pass PHP variable to JavaScript
const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;

// Function to check login before booking
function checkLoginBeforeBooking(idPenginapan, idTipeKamar) {
    if (isLoggedIn) {
        // User sudah login, redirect ke halaman booking
        window.location.href = `booking.php?id_penginapan=${idPenginapan}&id_tipe_kamar=${idTipeKamar}&checkin=&checkout=&jumlah_kamar=1`;
    } else {
        // User belum login, tampilkan popup
        showLoginModal();
    }
}

// Show login modal
function showLoginModal() {
    document.getElementById('loginModal').classList.add('show');
    document.body.style.overflow = 'hidden'; // Prevent scroll
}

// Close login modal
function closeLoginModal() {
    document.getElementById('loginModal').classList.remove('show');
    document.body.style.overflow = 'auto'; // Enable scroll
}

// Go to login page
function goToLogin() {
    window.location.href = '../autentikasi/login.php';
}

// Close modal when clicking outside
document.getElementById('loginModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLoginModal();
    }
});

// Smooth scroll untuk tab navigation
document.querySelectorAll('.tab-item').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();

        // Remove active class from all tabs
        document.querySelectorAll('.tab-item').forEach(t => t.classList.remove('active'));

        // Add active class to clicked tab
        this.classList.add('active');

        // Smooth scroll to section
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            const offset = 100;
            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// Update active tab on scroll
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('.section-card');
    const scrollPos = window.pageYOffset + 150;

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
</script>

<?php
// Include footer
require_once 'footer.php';

// Fungsi untuk validasi koordinat
function isValidCoordinate($lat, $lng) {
    // Cek apakah koordinat dalam format angka
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return false;
    }

    // Validasi range latitude (-90 sampai 90)
    if ($lat < -90 || $lat > 90) {
        return false;
    }

    // Validasi range longitude (-180 sampai 180)
    if ($lng < -180 || $lng > 180) {
        return false;
    }

    return true;
}

?>