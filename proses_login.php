<?php
session_start();
include 'koneksi.php';

// Jika sudah login → cegah login role lain
if (isset($_SESSION['id_user'])) {
    header("Location: login.php?error=Anda sudah login. Harap logout terlebih dahulu.");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // --- LOGIKA PENGECEKAN FIELD KOSONG  ---
    if (empty($username) || empty($password)) {
        
        $error_message = '';
        
        if (empty($username) && empty($password)) {
            $error_message = 'Maaf, harap masukkan Username dan Password anda.';
        } elseif (empty($username)) {
            $error_message = 'Maaf, harap masukkan Username anda.';
        } elseif (empty($password)) {
            $error_message = 'Maaf, harap masukkan Password anda.';
        }
        
        header("Location: login.php?error=" . urlencode($error_message));
        exit(); // Hentikan eksekusi jika ada field kosong
    }

    // --- Prepare statement ---
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Cek username 
    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        // Cek password
        if (password_verify($password, $user['password'])) {

            // Keamanan: regenerasi session ID
            session_regenerate_id(true);

            // Set session
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['login_time'] = time();
            $_SESSION['id_puskesmas'] = $user['id_puskesmas'];

            unset($_POST['password']);

            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                header("Location: dashboard/admin/welcome.php");
            } elseif ($user['role'] === 'supervisor') {
                header("Location: dashboard/supervisor/welcome.php");
            } else {
                header("Location: dashboard/welcome.php");
            }
            exit;

        } else {
            header("Location: login.php?error=Maaf, password salah!");
            exit;
        }

    } else {
        header("Location: login.php?error=Maaf, username tidak ditemukan!");
        exit;
    }
}
?>
