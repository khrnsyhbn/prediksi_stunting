<?php
/**
 * AUTH HELPER
 * ----------------------------------------
 * Fungsi universal untuk menangani:
 * - Cek apakah user sudah login
 * - Cek apakah sesi masih aktif
 * - Cek role user (opsional)
 * - Manajemen refresh sesi otomatis
 * ----------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Durasi sesi (24 jam)
$SESSION_LIFETIME = 24 * 60 * 60;


/**
 * Cek apakah user login + apakah sesi aktif
 */
function checkLogin()
{
    global $SESSION_LIFETIME;

    // 1. Belum login → redirect
    if (!isset($_SESSION['id_user'])) {
        header("Location: ../login.php?error=Silakan login terlebih dahulu");
        exit;
    }

    // 2. Session expired → logout paksa
    if (!isset($_SESSION['login_time']) || (time() - $_SESSION['login_time']) > $SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header("Location: ../login.php?error=Sesi Anda telah habis, silakan login kembali");
        exit;
    }
    
    // 3. Perpanjang sesi setiap request
    $_SESSION['login_time'] = time();
}


/**
 * Cek login + validasi role
 * 
 * @param string|array $roles Role tunggal atau multiple
 */
function checkRole($roles)
{
    // Pastikan user login dulu
    checkLogin();

    // Ubah ke array jika hanya 1 role
    if (!is_array($roles)) {
        $roles = [$roles];
    }

    // Cek apakah role user sesuai
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $roles)) {

        // Kode status akses ditolak
        http_response_code(403);

        // Redirect ke halaman forbidden
        header("Location: ../dashboard/403.php");
        exit;
    }
}
?>
