<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

$id_user = (int)$_SESSION['id_user']; // cast aman
$username = trim($_POST['username']);
$full_name = trim($_POST['nama']);
$email = trim($_POST['email']);
$id_puskesmas = trim($_POST['puskesmas']);
$password_lama = $_POST['password_lama'] ?? '';
$password_baru = $_POST['password_baru'] ?? '';
$konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

// Upload foto
$upload_dir = "../assets/img/";
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$foto_path_db = null;
if (!empty($_FILES['foto']['name'])) {
    $file_name = basename($_FILES['foto']['name']);
    $file_tmp = $_FILES['foto']['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $allowed_ext = ['jpg','jpeg','png'];
    if (!in_array($file_ext, $allowed_ext)) {
        echo "<script>alert('❌ Format file tidak diizinkan! Gunakan JPG, JPEG, atau PNG.'); window.history.back();</script>";
        exit;
    }

    $new_name = "user_{$id_user}_" . time() . "." . $file_ext;
    $file_path = $upload_dir . $new_name;

    if (move_uploaded_file($file_tmp, $file_path)) {
        $foto_path_db = $file_path;
    } else {
        echo "<script>alert('❌ Gagal mengunggah foto ke server.'); window.history.back();</script>";
        exit;
    }
}

// Update password jika diisi
$password_sql = '';
if ($password_lama || $password_baru || $konfirmasi_password) {
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        echo "<script>alert('❌ Semua kolom password harus diisi jika ingin mengganti password.'); window.history.back();</script>";
        exit;
    }
    if ($password_baru !== $konfirmasi_password) {
        echo "<script>alert('❌ Konfirmasi password baru tidak cocok.'); window.history.back();</script>";
        exit;
    }

    // Ambil hash password lama dari DB
    $stmt = $koneksi->prepare("SELECT password FROM users WHERE id_user=?");
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!password_verify($password_lama, $row['password'])) {
        echo "<script>alert('❌ Password lama salah.'); window.history.back();</script>";
        exit;
    }

    // Hash password baru
    $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
    $password_sql = ", password='$password_hash'";
}

// Update data
$stmt = $koneksi->prepare("
    UPDATE users SET 
        full_name=?, 
        username=?, 
        email=?, 
        id_puskesmas=? 
        " . ($foto_path_db ? ", foto=?" : "") . " 
        $password_sql
    WHERE id_user=?
");

if ($foto_path_db) {
    $stmt->bind_param("sssssi", $full_name, $username, $email, $id_puskesmas, $foto_path_db, $id_user);
} else {
    $stmt->bind_param("ssssi", $full_name, $username, $email, $id_puskesmas, $id_user);
}

if ($stmt->execute()) {
    // Update session
    $_SESSION['full_name'] = $full_name;
    if ($foto_path_db) $_SESSION['foto'] = $foto_path_db;

    echo "<script>alert('Profil berhasil diperbarui!'); window.location='profile.php';</script>";
} else {
    echo "<script>alert('❌ Terjadi kesalahan saat memperbarui profil.'); window.history.back();</script>";
}

$stmt->close();
$koneksi->close();
?>
