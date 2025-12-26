<?php
require_once '../config.php';

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

// Query untuk mengambil tipe kamar
$sql_kamar = "SELECT * FROM tipe_kamar WHERE id_penginapan = $id_penginapan ORDER BY harga_per_malam ASC";
$result_kamar = mysqli_query($conn, $sql_kamar);

// Include header
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

/* ========= MAIN CONTENT ========= */
.detail-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 30px;
}

.detail-main {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.detail-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
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

/* ========= BOOKING CARD (SIDEBAR) ========= */
.booking-card {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    border: 2px solid #e5e7eb;
}

.booking-price {
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e5e7eb;
}

.price-label {
    font-size: 14px;
    color: #666;
    margin-bottom: 5px;
}

.price-amount {
    font-size: 32px;
    font-weight: bold;
    color: #f5a742;
}

.price-period {
    font-size: 16px;
    color: #666;
    font-weight: normal;
}

.booking-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

.form-input {
    padding: 12px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-input:focus {
    outline: none;
    border-color: #f5a742;
    box-shadow: 0 0 0 3px rgba(245, 167, 66, 0.1);
}

.btn-booking {
    width: 100%;
    background: #f5a742;
    color: white;
    border: none;
    padding: 15px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 700;
    font-size: 16px;
    margin-top: 10px;
    transition: all 0.3s;
}

.btn-booking:hover {
    background: #e89632;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(245, 167, 66, 0.4);
}

.booking-note {
    font-size: 12px;
    color: #666;
    text-align: center;
    margin-top: 10px;
}

/* ========= RESPONSIVE ========= */
@media (max-width: 968px) {
    .detail-container {
        grid-template-columns: 1fr;
    }
    
    .detail-sidebar {
        position: static;
        order: -1;
    }
    
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
}
</style>

<!-- ========= HERO SECTION ========= -->
<div class="hero-section">
    <div class="hero-container">
        <div class="hero-header">
            <h1 class="hero-title"><?= htmlspecialchars($penginapan['nama_penginapan']) ?></h1>
            <div class="hero-location">
                <span class="hero-location-icon">📍</span>
                <span><?= htmlspecialchars($penginapan['nama_kecamatan']) ?>, <?= htmlspecialchars($penginapan['nama_kabupaten']) ?></span>
            </div>
        </div>
        
        <!-- Gallery -->
        <div class="gallery-grid">
            <div class="gallery-main">
                <img src="<?= htmlspecialchars($penginapan['gambar']) ?>" alt="<?= htmlspecialchars($penginapan['nama_penginapan']) ?>">
            </div>
            <div class="gallery-sidebar">
                <div class="gallery-item">
                    <img src="<?= htmlspecialchars($penginapan['gambar']) ?>" alt="Gallery 1">
                </div>
                <div class="gallery-item">
                    <img src="<?= htmlspecialchars($penginapan['gambar']) ?>" alt="Gallery 2">
                </div>
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

<!-- ========= MAIN CONTENT ========= -->
<div class="detail-container">
    <!-- Main Content -->
    <div class="detail-main">
        
        <!-- Deskripsi -->
        <div id="deskripsi" class="section-card">
            <h2 class="section-title">Deskripsi</h2>
            <div class="section-description">
                <?= nl2br(htmlspecialchars($penginapan['deskripsi'] ?? 'Desain dan arsitektur menjadi salah satu faktor penentu kenyamanan Anda di hotel. Liberta Malioboro South menyediakan tempat menginap yang tak hanya nyaman untuk beristirahat, tetapi juga desain cantik yang memanjakan mata Anda.')) ?>
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
                    <div class="room-image">
                        <img src="<?= htmlspecialchars($kamar['gambar'] ?? $penginapan['gambar']) ?>" alt="<?= htmlspecialchars($kamar['nama_tipe']) ?>">
                    </div>
                    <div class="room-info">
                        <h3 class="room-name"><?= htmlspecialchars($kamar['nama_tipe']) ?></h3>
                        <div class="room-features">
                            <?= htmlspecialchars($kamar['deskripsi'] ?? 'Kamar nyaman dengan fasilitas lengkap') ?>
                        </div>
                        <div class="room-price-box">
                            <div>
                                <div class="room-price-label">Mulai dari</div>
                                <div class="room-price">Rp <?= number_format($kamar['harga_per'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <button class="btn-book-room" onclick="window.location.href='booking.php?id_penginapan=<?= $id_penginapan ?>&id_tipe_kamar=<?= $kamar['id_tipe_kamar'] ?>&checkin=&checkout=&jumlah_kamar=1'">Pesan</button>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    // Jika tidak ada tipe kamar, tampilkan default
                ?>
                <div class="room-card">
                    <div class="room-image">
                        <img src="<?= htmlspecialchars($penginapan['gambar']) ?>" alt="Standard Room">
                    </div>
                    <div class="room-info">
                        <h3 class="room-name">Standard Room</h3>
                        <div class="room-features">
                            Kamar standar dengan fasilitas lengkap dan kenyamanan maksimal
                        </div>
                        <div class="room-price-box">
                            <div>
                                <div class="room-price-label">Mulai dari</div>
                                <div class="room-price">Rp <?= number_format($penginapan['harga_mulai'], 0, ',', '.') ?></div>
                            </div>
                        </div>
                        <button class="btn-book-room" onclick="window.location.href='booking.php?id_penginapan=<?= $id_penginapan ?>&id_tipe_kamar=0&checkin=&checkout=&jumlah_kamar=1'">Pesan</button>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        
        <!-- Fasilitas -->
        <div id="fasilitas" class="section-card">
            <h2 class="section-title">Fasilitas</h2>
            <div class="facilities-grid">
                <div class="facility-item">
                    <div class="facility-icon">📶</div>
                    <div class="facility-name">WiFi</div>
                </div>
                <div class="facility-item">
                    <div class="facility-icon">🍽️</div>
                    <div class="facility-name">Kolam Renang Outdoor</div>
                </div>
                <div class="facility-item">
                    <div class="facility-icon">✈️</div>
                    <div class="facility-name">Restoran & Cafe</div>
                </div>
                <div class="facility-item">
                    <div class="facility-icon">🚗</div>
                    <div class="facility-name">Parkir Gratis</div>
                </div>
                <div class="facility-item">
                    <div class="facility-icon">🧺</div>
                    <div class="facility-name">Layanan Laundry</div>
                </div>
                <div class="facility-item">
                    <div class="facility-icon">🚫</div>
                    <div class="facility-name">Bebas Rokok</div>
                </div>
            </div>
        </div>
        
        <!-- Lokasi -->
        <div id="lokasi" class="section-card">
            <h2 class="section-title">Peta dan Alamat Lokasi</h2>
            <div class="location-content">
                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3953.0!2d110.36!3d-7.78!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zN8KwNDYnNDguMCJTIDExMMKwMjEnMzYuMCJF!5e0!3m2!1sid!2sid!4v1234567890" allowfullscreen="" loading="lazy"></iframe>
                </div>
                <div class="address-box">
                    <div class="address-label">Alamat:</div>
                    <div class="address-text">
                        <?= htmlspecialchars($penginapan['alamat'] ?? 'Jl. Pakuningratan No. 17 Gedong Tengen, Jalan Malioboro, Yogyakarta, Daerah Istimewa Yogyakarta, Indonesia') ?>
                    </div>
                    <a href="https://maps.google.com/?q=<?= urlencode($penginapan['nama_penginapan']) ?>" target="_blank" class="btn-maps">
                        Lihat di Google Maps →
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Tentang Kami & Kontak -->
        <div class="info-grid">
            <!-- Tentang Kami -->
            <div id="tentang" class="section-card">
                <h2 class="section-title">Tentang Kami</h2>
                <div class="section-description">
                    <?= nl2br(htmlspecialchars($penginapan['tentang'] ?? 'Liberta Malioboro South adalah jaringan hotel yang perkembangannya menginap yang menyenangkan dan berkomitmen untuk menjadi akomodasi pilihan di Yogyakarta, menawarkan nilai terbaik dengan lokasi yang strategis.')) ?>
                </div>
            </div>
            
            <!-- Kontak -->
            <div id="kontak" class="section-card">
                <h2 class="section-title">Kontak Kami</h2>
                <div class="contact-item">
                    <div class="contact-icon">📞</div>
                    <div class="contact-info">
                        <div class="contact-label">Telepon</div>
                        <div class="contact-value"><?= htmlspecialchars($penginapan['telepon'] ?? '(0274) 388700') ?></div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">📧</div>
                    <div class="contact-info">
                        <div class="contact-label">Email</div>
                        <div class="contact-value">
                            <a href="mailto:<?= htmlspecialchars($penginapan['email'] ?? 'reservation@hotel.com') ?>">
                                <?= htmlspecialchars($penginapan['email'] ?? 'reservation@hotel.com') ?>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">🌐</div>
                    <div class="contact-info">
                        <div class="contact-label">Website</div>
                        <div class="contact-value">
                            <a href="<?= htmlspecialchars($penginapan['website'] ?? '#') ?>" target="_blank">
                                <?= htmlspecialchars($penginapan['website'] ?? 'www.hotel.com') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- Sidebar Booking Card -->
    <div class="detail-sidebar">
        <div class="booking-card">
            <div class="booking-price">
                <div class="price-label">Harga mulai dari</div>
                <div class="price-amount">
                    Rp <?= number_format($penginapan['harga_mulai'], 0, ',', '.') ?>
                    <span class="price-period">/malam</span>
                </div>
            </div>
            
            <form class="booking-form" method="GET" action="booking.php">
                <input type="hidden" name="id_penginapan" value="<?= $id_penginapan ?>">
                <input type="hidden" name="id_tipe_kamar" value="0">
                
                <div class="form-group">
                    <label class="form-label">Check-in</label>
                    <input type="date" name="checkin" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Check-out</label>
                    <input type="date" name="checkout" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Jumlah Kamar</label>
                    <input type="number" name="jumlah_kamar" class="form-input" value="1" min="1" required>
                </div>
                <button type="submit" class="btn-booking">Pesan Sekarang</button>
            </form>
        </div>
    </div>
</div>

<script>
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
            document.querySelectorAll('.tab-item').forEach(tab => {
                tab.classList.remove('active');
                if (tab.getAttribute('href') === '#' + id) {
                    tab.classList.add('active');
                }
            });
        }
    });
});
</script>

<?php
// Include footer
require_once 'footer.php';
?>