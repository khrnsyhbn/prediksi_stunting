<?php
require_once 'auth.php';
checkLogin();
include '../koneksi.php'; // koneksi ke DB
// Ambil data user yang sedang login
$id_user = $_SESSION['id_user'] ?? null;
$query = "SELECT full_name, foto FROM users WHERE id_user = '$id_user'";
$result = mysqli_query($koneksi, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    $nama_user = $row['full_name'];
    $foto_user = !empty($row['foto']) ? $row['foto'] : '../assets/img/undraw_profile.svg';
} else {
   header('Location: ../login.php');
   exit;
}
?>

<!-- === NAVBAR PROFILE DROPDOWN === -->
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

    <!-- Sidebar Toggle (Topbar) -->
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>

    <!-- Topbar Navbar -->
    <ul class="navbar-nav ml-auto">
        
        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- Nav Item - User Information -->
        <li class="nav-item dropdown no-arrow">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" 
                aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">
                    <?= htmlspecialchars($nama_user) ?>
                </span>
                <img class="img-profile rounded-circle" src="<?= htmlspecialchars($foto_user) ?>" alt="Foto Profil"
                        style="width:40px; height:40px; object-fit:cover;">
            </a>
            <!-- Dropdown - User Information -->
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                aria-labelledby="userDropdown">
                <a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                    Profile
                </a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="" data-toggle="modal" data-target="#logoutModal">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                    Logout
                </a>
            </div>
        </li>

    </ul>

</nav>