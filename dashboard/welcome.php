<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit;
}

// Ambil nama pengguna dari session
$nama = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selamat Datang | Prediksi Stunting Anak</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: 'Poppins', sans-serif;
      color: #fff;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
      background: url('../assets/img/bg2.jpeg') no-repeat center center/cover;
      overflow: hidden;
    }

    body::before {
      content: "";
      position: absolute;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(6px);
      z-index: -1;
    }

    .welcome-box {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(8px);
      border-radius: 20px;
      text-align: center;
      padding: 50px 40px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
      max-width: 600px;
      animation: fadeIn 1s ease-in-out;
    }

    .welcome-box img {
      width: 100px;
      margin-bottom: 20px;
      animation: bounceIn 1.2s ease;
    }

    h1 {
      font-weight: 700;
      font-size: 2.3rem;
      margin-bottom: 10px;
    }

    h2 {
      font-size: 1.3rem;
      font-weight: 400;
      color: #e0e0e0;
      margin-bottom: 30px;
    }

    .btn-main {
      background-color: #fff;
      color: #007bff;
      font-weight: 600;
      border-radius: 30px;
      padding: 10px 25px;
      transition: 0.3s;
      text-decoration: none;
    }

    .btn-main:hover {
      background-color: #007bff;
      color: #fff;
      transform: translateY(-3px);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes bounceIn {
      0% { transform: translateY(-30px); opacity: 0; }
      100% { transform: translateY(0); opacity: 1; }
    }

    footer {
      position: absolute;
      bottom: 15px;
      color: #ccc;
      font-size: 0.9rem;
      width: 100%;
      text-align: center;
    }
  </style>

  <!-- 🔁 Auto redirect ke dashboard dalam 10 detik -->
  <meta http-equiv="refresh" content="10;url=index.php">
</head>
<body>

  <div class="welcome-box">
    <h1>Selamat Datang, <span><?= htmlspecialchars($nama) ?></span>!</h1>
    <h2>Anda berhasil masuk ke sistem prediksi level stunting anak sebagai <?= htmlspecialchars($_SESSION['role']) ?>.</h2>
    <p class="text-light">Anda akan diarahkan ke dashboard secara otomatis atau tekan tombol dibawah</p>
    <a href="index.php" class="btn-main mt-3">Masuk Sekarang</a>
  </div>

  <footer>
    ©  Sistem Prediksi Level Stunting 2025
  </footer>

</body>
</html>
