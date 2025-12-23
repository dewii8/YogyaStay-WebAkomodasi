<?php
require_once '../config.php';

// Cek Jika Sudah Login
if (isset($_SESSION['user_id'])) {
    header('Location: ../users/beranda.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi Input
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek Apakah Email Sudah Terdaftar
        $check_query = "SELECT id_user FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Hash Password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert User Baru dengan role_id = 'user'
            $insert_query = "INSERT INTO users (email, password, role_id, created_at) 
                           VALUES ('$email', '$hashed_password', 'user', NOW())";
            
            if (mysqli_query($conn, $insert_query)) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Registrasi gagal: ' . mysqli_error($conn);
            }
        }
    }
}

$page_title = 'Register';
include '../users/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register YogyaStay</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }

        body {
            background: #FFF9F2;
            min-height: 100vh;
            margin: 0;
        }

        main {
            min-height: calc(100vh - 120px);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }

        .register-container {
            background: #ffffff;
            width: 100%;
            max-width: 420px;
            padding: 36px 32px;
            border-radius: 18px;
            border: 3px solid #F5B342;
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }

        .register-container h2 {
            text-align: center;
            color: #A4A8C1;
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 28px;
        }

        /* FORM */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
            color: #222222;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 12px 42px;
            border-radius: 10px;
            border: 2px solid #E2E2E2;
            font-size: 14px;
            transition: 0.3s;
        }

        .input-wrapper input::placeholder {
            color: #B0B0B0;
            font-weight: 400;
        }

        .input-wrapper input:focus {
            border-color: #A4A8C1;
            outline: none;
        }

        /* ICON */
        .input-icon,
        .toggle-password {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: #999999;
        }

        .input-icon {
            left: 14px;
        }

        .toggle-password {
            right: 14px;
            cursor: pointer;
        }

        /* BUTTON */
        .btn-register {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            background: #A4A8C1;
            border: none;
            color: #FFFFFF;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-register:hover {
            background: #8D91AD;
        }

        /* LINK */
        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #666666;
        }

        .login-link a {
            color: #F5B432;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            main {
                padding: 24px 14px;
            }

            .register-container {
                padding: 28px 20px;
                border-radius: 14px;
            }

            .register-container h2 {
                font-size: 22px;
            }

            .input-wrapper input {
                font-size: 13px;
                padding: 11px 40px;
            }

            .btn-register {
                font-size: 14px;
            }
        }

        @media (min-width: 1200px) {
            .register-container {
                max-width: 460px;
                padding: 40px 36px;
            }

            .register-container h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="register-container">
            <h2>REGISTER ACCOUNT</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <div class="input-wrapper">
                        <i class="bi bi-envelope input-icon"></i>
                        <input type="email" name="email" placeholder="contoh@gmail.com"
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" name="password" id="password" placeholder="••••••••" required>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Konfirmasi Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required>
                        <i class="bi bi-eye-slash toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>

                <button type="submit" class="btn-register">DAFTAR</button>
            </form>
            
            <div class="login-link">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </main>

    <script>
    function togglePassword(id, icon) {
        const input = document.getElementById(id);

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    }
    </script>

<?php if ($success): ?>
<script>
Swal.fire({
    title: "Berhasil!",
    text: "<?php echo $success; ?>",
    icon: "success",
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    willOpen: () => {
        Swal.getPopup().classList.add('swal-success');
    }
}).then(() => {
    window.location.href = "login.php";
});
</script>
<?php endif; ?>

<?php if ($error): ?>
<script>
Swal.fire({
    title: "Oops!",
    text: "<?php echo $error; ?>",
    icon: "error",
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    willOpen: () => {
        Swal.getPopup().classList.add('swal-error');
    }
});
</script>
<?php endif; ?>

<?php include '../users/footer.php'; ?>

</body>
</html>