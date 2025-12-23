<?php
// Handler Logout
session_start();

// Hapus Semua Session
session_unset();
session_destroy();

// Hapus Remember Me Cookie
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Redirect ke Halaman Login
header('Location: login.php?logout=success');
exit();
?>