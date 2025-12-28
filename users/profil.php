<?php
require_once '../config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../autentikasi/login.php");
    exit;
}

$success = '';
$error = '';

// Ambil data user dari database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id_user = '$user_id'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($result);

if (!$user) {
    // User tidak ditemukan, logout
    session_destroy();
    header("Location: ../autentikasi/login.php");
    exit;
}

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));
    $no_telepon = mysqli_real_escape_string($conn, trim($_POST['no_telepon']));

    if (empty($nama)) {
        $error = 'Nama tidak boleh kosong!';
    } else {
        // Update nama dan no telepon
        $update_query = "UPDATE users SET 
                            nama = '$nama',
                            no_telepon = '$no_telepon'
                         WHERE id_user = '$user_id'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['nama'] = $nama;
            $success = 'Perubahan data berhasil disimpan!';

            // Refresh data user
            $result = mysqli_query($conn, $query);
            if ($result) {
                $user = mysqli_fetch_assoc($result);
            }
        } else {
            $error = 'Gagal menyimpan perubahan: ' . mysqli_error($conn);
        }
    }
}

$user_name = $user['nama'] ?? 'User';
$user_email = $user['email'] ?? 'user@gmail.com';

// Include header
require_once 'header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ========= MAIN LAYOUT ========= */
.profile-wrapper {
    min-height: 80vh;
    background: #f5f5f0;
    padding: 40px 20px;
}

.profile-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}

.page-title {
    font-size: 36px;
    font-weight: bold;
    color: #8da6daff;
    margin-bottom: 30px;
    grid-column: 1 / -1;
}

/* ========= SIDEBAR PROFILE ========= */
.profile-sidebar {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    height: fit-content;
    position: sticky;
    top: 20px;
}

.profile-header {
    text-align: center;
    padding-bottom: 25px;
    border-bottom: 2px solid #e5e7eb;
    margin-bottom: 25px;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8da6daff 0%, #5073b8ff 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    font-weight: bold;
    margin: 0 auto 15px;
}

.profile-name {
    font-size: 20px;
    font-weight: bold;
    color: #1a1a1a;
    margin-bottom: 5px;
}

.profile-email {
    font-size: 13px;
    color: #666;
}

.profile-menu {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    color: #666;
    font-size: 15px;
    font-weight: 500;
}

.menu-item:hover {
    background: #f9fafb;
    color: #8da6daff;
}

.menu-item.active {
    background: #e8eef9;
    color: #5073b8ff;
    font-weight: 600;
}

.menu-icon {
    font-size: 20px;
}

.btn-logout {
    margin-top: 20px;
    width: 100%;
    padding: 12px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-logout:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

/* ========= MAIN CONTENT ========= */
.content-area {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
}

.content-header {
    margin-bottom: 35px;
    padding-bottom: 20px;
    border-bottom: 3px solid #8da6daff;
}

.content-title {
    font-size: 28px;
    font-weight: bold;
    color: #1a1a1a;
}

.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 10px;
    color: #333;
    font-weight: 600;
    font-size: 15px;
}

.form-group input {
    width: 100%;
    padding: 14px 18px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-group input:focus {
    outline: none;
    border-color: #8da6daff;
    box-shadow: 0 0 0 4px rgba(141, 166, 218, 0.1);
}

.form-group input:disabled {
    background: #f9fafb;
    cursor: not-allowed;
    color: #999;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.btn-save {
    padding: 14px 35px;
    background: #8da6daff;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 15px;
}

.btn-save:hover {
    background: #5073b8ff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(141, 166, 218, 0.4);
}

.alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 25px;
    font-size: 15px;
    font-weight: 500;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 2px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 2px solid #fecaca;
}

/* ========= RESPONSIVE ========= */
@media (max-width: 968px) {
    .profile-container {
        grid-template-columns: 1fr;
    }
    
    .profile-sidebar {
        position: static;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="profile-wrapper">
    <div class="profile-container">
        
        <!-- Sidebar Profile -->
        <div class="profile-sidebar">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user_name, 0, 2)) ?>
                </div>
                <div class="profile-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="profile-email"><?= htmlspecialchars($user_email) ?></div>
            </div>
            
            <div class="profile-menu">
                <a href="riwayat_reservasi.php" class="menu-item">
                    <span class="menu-icon">🔄</span>
                    <span>Riwayat Reservasi</span>
                </a>
                <a href="profil.php" class="menu-item active">
                    <span class="menu-icon">✏️</span>
                    <span>Edit Data Pribadi</span>
                </a>
                <a href="status_pembatalan.php" class="menu-item">
                    <span class="menu-icon">✖️</span>
                    <span>Status Pembatalan</span>
                </a>
            </div>
            
            <button class="btn-logout" onclick="if(confirm('Yakin ingin keluar?')) window.location.href='../autentikasi/logout.php'">
                <span>🚪</span>
                <span>Keluar</span>
            </button>
        </div>
        
        <!-- Main Content -->
        <div class="content-area">
            <div class="content-header">
                <h2 class="content-title">Edit Data Pribadi</h2>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="profileForm">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" placeholder="Masukkan nama lengkap" 
                           value="<?= htmlspecialchars($user['nama'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input type="tel" name="no_telepon" placeholder="08xxxxxxxxxx" 
                           value="<?= htmlspecialchars($user['no_telepon'] ?? '') ?>">
                </div>
                
                <button type="submit" class="btn-save">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('profileForm').addEventListener('submit', function (e) {
    e.preventDefault();

    Swal.fire({
        title: 'Simpan Perubahan?',
        text: 'Apakah kamu yakin ingin menyimpan perubahan data?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#8da6daff',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Simpan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
});
</script>

<?php if ($success): ?>
<script>
setTimeout(function() {
    Swal.fire({
        title: 'Berhasil!',
        text: '<?= $success ?>',
        icon: 'success',
        timer: 2500,
        showConfirmButton: false
    });
}, 100);
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
setTimeout(function() {
    Swal.fire({
        title: 'Gagal!',
        text: '<?= $error ?>',
        icon: 'error',
        timer: 2500,
        showConfirmButton: false
    });
}, 100);
</script>
<?php endif; ?>

<?php
// Include footer
require_once 'footer.php';
?>