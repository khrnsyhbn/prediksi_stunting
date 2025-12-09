<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon rotate-n-15">
            <i class="fas fa-laugh-wink"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Prediksi Level Stunting</div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Nav Item - Dashboard -->
    <li class="nav-item <?php if($currentPage=='index.php') echo 'active'; ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Informasi</div>

    <!-- Nav Item - Pengukuran -->
    <?php
    $pengukuranPages = ['tampil_kelurahan.php','tampil_puskesmas.php','tampil_posyandu.php',
                         'tampil_standar_bb_pb.php','tampil_standar_bb_tb.php','tampil_standar_bb_u.php',
                         'tampil_standar_tb_u.php'];
    ?>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePengukuran"
           aria-expanded="true" aria-controls="collapsePengukuran">
            <i class="fas fa-fw fa-table"></i>
            <span>Pengukuran</span>
        </a>
        <div id="collapsePengukuran" class="collapse <?php if(in_array($currentPage,$pengukuranPages)) echo 'show'; ?>" 
             aria-labelledby="headingPengukuran" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Tabel:</h6>
                <a class="collapse-item <?php if($currentPage=='tampil_kelurahan.php') echo 'active'; ?>" href="tampil_kelurahan.php">Kelurahan</a>
                <a class="collapse-item <?php if($currentPage=='tampil_puskesmas.php') echo 'active'; ?>" href="tampil_puskesmas.php">Puskesmas</a>
                <a class="collapse-item <?php if($currentPage=='tampil_posyandu.php') echo 'active'; ?>" href="tampil_posyandu.php">Posyandu</a>
                <a class="collapse-item <?php if($currentPage=='tampil_standar_bb_pb.php') echo 'active'; ?>" href="tampil_standar_bb_pb.php">Standar BB/PB</a>
                <a class="collapse-item <?php if($currentPage=='tampil_standar_bb_tb.php') echo 'active'; ?>" href="tampil_standar_bb_tb.php">Standar BB/TB</a>
                <a class="collapse-item <?php if($currentPage=='tampil_standar_bb_u.php') echo 'active'; ?>" href="tampil_standar_bb_u.php">Standar BB/U</a>
                <a class="collapse-item <?php if($currentPage=='tampil_standar_tb_u.php') echo 'active'; ?>" href="tampil_standar_tb_u.php">Standar TB/U</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Profil -->
    <li class="nav-item <?php if($currentPage=='profile.php') echo 'active'; ?>">
        <a class="nav-link" href="profile.php">
            <i class="fas fa-fw fa-user"></i>
            <span>Profil</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Fitur</div>

    <!-- Nav Item - Upload -->
    <?php
    $uploadPages = ['upload_data.php','form_upload_csv.php'];
    ?>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUpload"
           aria-expanded="true" aria-controls="collapseUpload">
            <i class="fas fa-fw fa-cog"></i>
            <span>Upload</span>
        </a>
        <div id="collapseUpload" class="collapse <?php if(in_array($currentPage,$uploadPages)) echo 'show'; ?>" 
             aria-labelledby="headingUpload" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Mode Upload:</h6>
                <a class="collapse-item <?php if($currentPage=='upload_data.php') echo 'active'; ?>" href="upload_data.php">Manual</a>
                <a class="collapse-item <?php if($currentPage=='form_upload_csv.php') echo 'active'; ?>" href="form_upload_csv.php">Batch</a>
            </div>
        </div>
    </li>

    <hr class="sidebar-divider">

    <!-- Heading -->
    <div class="sidebar-heading">Hasil</div>

    <!-- Nav Item - Download -->
    <?php
    $downloadPages = ['hasil_stunting.php','hasil_analisis.php','log_gagal.php'];
    ?>
    <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseDownload"
           aria-expanded="true" aria-controls="collapseDownload">
            <i class="fas fa-fw fa-folder"></i>
            <span>Perhitungan</span>
        </a>
        <div id="collapseDownload" class="collapse <?php if(in_array($currentPage,$downloadPages)) echo 'show'; ?>" 
             aria-labelledby="headingDownload" data-parent="#accordionSidebar">
            <div class="bg-white py-2 collapse-inner rounded">
                <h6 class="collapse-header">Login Screens:</h6>
                <a class="collapse-item <?php if($currentPage=='hasil_stunting.php') echo 'active'; ?>" href="hasil_stunting.php">Manual</a>
                <a class="collapse-item <?php if($currentPage=='hasil_analisis.php') echo 'active'; ?>" href="hasil_analisis.php">Batch</a>
                <a class="collapse-item <?php if($currentPage=='hasil_analisis.php') echo 'active'; ?>" href="hasil_analisis.php">Semua</a>
                <a class="collapse-item <?php if($currentPage=='log_gagal.php') echo 'active text-warning'; ?>" href="log_gagal.php">Log Gagal</a>
            </div>
        </div>
    </li>

    <!-- Nav Item - Charts -->
    <li class="nav-item <?php if($currentPage=='grafik_stunting.php') echo 'active'; ?>">
        <a class="nav-link" href="grafik_stunting.php">
            <i class="fas fa-fw fa-chart-pie"></i>
            <span>Grafik</span>
        </a>
    </li>

    <!-- Nav Item - Laporan -->
    <li class="nav-item <?php if($currentPage=='laporan.php') echo 'active'; ?>">
        <a class="nav-link" href="laporan.php">
            <i class="fas fa-fw fa-table"></i>
            <span>Laporan</span>
        </a>
    </li>

    <hr class="sidebar-divider d-none d-md-block">

    <!-- Nav Item - Logout -->
    <li class="nav-item">
        <a class="nav-link" href="#" data-toggle="modal" data-target="#logoutModal">
            <i class="fas fa-fw fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </li>

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
