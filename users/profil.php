<?php
require_once '../config.php';
require_once '../autentikasi/check_session.php';

$success = '';
$error = '';

// Ambil data user dari database
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id_user = '$user_id'";
$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, trim($_POST['nama']));

    if (empty($nama)) {
        $error = 'Nama tidak boleh kosong!';
    } else {
        // Update hanya nama
        $update_query = "UPDATE users SET 
                            nama = '$nama',
                            updated_at = NOW()
                         WHERE id_user = '$user_id'";

        if (mysqli_query($conn, $update_query)) {
            $_SESSION['nama'] = $nama;
            $success = 'Perubahan data berhasil disimpan!';

            // Refresh data user
            $result = mysqli_query($conn, $query);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error = 'Gagal menyimpan perubahan!';
        }
    }
}

$page_title = 'Profil';
include 'header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(to bottom, #fff 30%, #4a4a4a 30%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Main Content */
        main {
            flex: 1;
            padding: 40px 60px;
        }
        
        .greeting {
            color: #a4a8c1;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 35px;
        }
        
        .profile-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 35px;
        }
        
        .sidebar {
            background: white;
            padding: 35px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .user-avatar {
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #f5b342, #e8a130);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(245, 179, 66, 0.3);
        }
        
        .user-details h3 {
            color: #333;
            font-size: 19px;
            margin-bottom: 6px;
            font-weight: 700;
        }
        
        .user-details p {
            color: #999;
            font-size: 14px;
        }
        
        .menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px;
            margin-bottom: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            color: #666;
            text-decoration: none;
            font-weight: 500;
        }
        
        .menu-item:hover {
            background: #f8f8f8;
            transform: translateX(5px);
        }
        
        .menu-item.active {
            background: #e8f0fe;
            color: #1a73e8;
            font-weight: 600;
        }
        
        .menu-item .icon {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        .menu-item.logout {
            color: #dc3545;
            border-top: 2px solid #f0f0f0;
            margin-top: 25px;
            padding-top: 25px;
        }
        
        .menu-item.logout:hover {
            background: #fee;
        }
        
        .content-area {
            background: white;
            padding: 45px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .content-area h2 {
            color: #a4a8c1;
            font-size: 26px;
            margin-bottom: 35px;
            font-weight: 700;
        }
        
        .form-group {
            margin-bottom: 28px;
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
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #a4a8c1;
            box-shadow: 0 0 0 3px rgba(164, 168, 193, 0.1);
        }
        
        .form-group input:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }
        
        .btn-save {
            padding: 14px 35px;
            background: #a4a8c1;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn-save:hover {
            background: #8d91ad;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(164, 168, 193, 0.3);
        }
        
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 15px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
    </style>

    <main>
        <h1 class="greeting">Hallo, <?php echo $user['nama'] ? htmlspecialchars($user['nama']) : 'User'; ?> !</h1>
        
        <div class="profile-container">
            <div class="sidebar">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['nama'] ?: 'U', 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h3><?php echo $user['nama'] ?: 'User'; ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <a href="#" class="menu-item">
                    <span class="icon">📋</span>
                    <span>Riwayat Reservasi</span>
                </a>
                
                <a href="profil.php" class="menu-item active">
                    <span class="icon">✏️</span>
                    <span>Edit Data Pribadi</span>
                </a>
                
                <a href="#" class="menu-item">
                    <span class="icon">📊</span>
                    <span>Status Pembatalan</span>
                </a>
                
                <a href="../autentikasi/logout.php" class="menu-item logout">
                    <span class="icon">🚪</span>
                    <span>Keluar</span>
                </a>
            </div>
            
            <div class="content-area">
                <h2>Edit Data Pribadi</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" placeholder="Anh Jimin" 
                               value="<?php echo htmlspecialchars($user['nama'] ?: ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                disabled>
                    </div>
                    
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </main>

<script>
document.querySelector('form').addEventListener('submit', function (e) {
    e.preventDefault();

    Swal.fire({
        title: 'Simpan Perubahan?',
        text: 'Apakah kamu yakin ingin menyimpan perubahan data?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#a4a8c1',
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
Swal.fire({
    title: 'Berhasil!',
    text: '<?= $success ?>',
    icon: 'success',
    timer: 2500,
    showConfirmButton: false
});
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
Swal.fire({
    title: 'Gagal!',
    text: '<?= $error ?>',
    icon: 'error',
    timer: 2500,
    showConfirmButton: false
});
</script>
<?php endif; ?>

<?php include 'footer.php'; ?>