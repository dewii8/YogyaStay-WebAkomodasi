<?php
require_once '../config.php';

// Cek Jika Sudah Login
if (isset($_SESSION['user_id'])) {
    header('Location: ../users/beranda.php');
    exit();
}

$error = '';
$success = '';
$redirect = '';

// Cek Remember Me Cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_user'])) {
    $cookie_value = $_COOKIE['remember_user'];
    list($user_id, $token) = explode(':', $cookie_value);
    
    // Verifikasi Token
    $query = "SELECT * FROM users WHERE id_user = '$user_id'";
    $result = mysqli_query($conn, $query);
    
    if ($user = mysqli_fetch_assoc($result)) {
        $expected_token = hash('sha256', $user['email'] . $user['password']);
        if ($token === $expected_token) {
            // Set Session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['last_activity'] = time();
            
            // Redirect berdasarkan Role
            if ($user['role_id'] == 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../users/beranda.php');
            }
            exit();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi!';
    } else {
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {

                // SET SESSION
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['last_activity'] = time();

                // REMEMBER ME
                if ($remember) {
                    $token = hash('sha256', $user['email'] . $user['password']);
                    $cookie_value = $user['id_user'] . ':' . $token;
                    setcookie('remember_user', $cookie_value, time() + (30 * 24 * 60 * 60), '/');
                }

                // SUCCESS
                $success = 'Login berhasil! Selamat datang 👋';

                // REDIRECT TUJUAN
                $redirect = ($user['role_id'] == 'admin')
                    ? '../admin/dashboard.php'
                    : '../users/beranda.php';

            } else {
                $error = 'Email atau password salah!';
            }
        } else {
            $error = 'Email atau password salah!';
        }
    }
}

$page_title = 'Login';
include '../users/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login YogyaStay</title>

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

        .login-container {
            background: #FFFFFF;
            width: 100%;
            max-width: 420px;
            padding: 36px 32px;
            border-radius: 18px;
            border: 3px solid #F5B342;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
        }

        .login-container h2 {
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
            color: #AAAAAA;
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

        /* REMEMBER ME */
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 12px 0 18px;
            font-size: 14px;
            color: #555555;
        }

        /* BUTTON */
        .btn-masuk {
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
        }

        .btn-masuk:hover {
            background: #8D91AD;
        }

        /* REGISTER */
        .register-link {
            text-align: center;
            margin-top: 18px;
            font-size: 14px;
            color: #666666;
        }

        .register-link a {
            color: #F5B342;
            font-weight: 600;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width: 480px) {
            main {
                padding: 24px 14px;
            }

            .login-container {
                padding: 28px 20px;
                border-radius: 14px;
            }

            .login-container h2 {
                font-size: 22px;
                margin-bottom: 22px;
            }

            .input-wrapper input {
                padding: 11px 40px;
                font-size: 13px;
            }

            .btn-masuk {
                font-size: 14px;
                padding: 11px;
            }

            .register-link {
                font-size: 13px;
            }

            .remember-me input {
                width: 16px;
                height: 16px;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                max-width: 380px;
                padding: 32px 26px;
            }

            .login-container h2 {
                font-size: 24px;
            }
        }

        @media (min-width: 1200px) {
            main {
                padding: 60px 20px;
            }

            .login-container {
                max-width: 460px;
                padding: 40px 36px;
            }

            .login-container h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="login-container">
            <h2>LOGIN ACCOUNT</h2>
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
                        <i class="bi bi-eye-slash toggle-password" id="toggleIcon" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Ingat Saya</label>
                </div>

                <button type="submit" class="btn-masuk">MASUK</button>
            </form>
            
            <div class="register-link">
                Belum punya akun? <a href="register.php">Register di sini</a>
            </div>
        </div>
    </main>

    <script>
    function togglePassword() {
        const input = document.getElementById('password');
        const icon = document.getElementById('toggleIcon');

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