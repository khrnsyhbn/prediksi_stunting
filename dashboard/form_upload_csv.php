<?php
require_once 'auth.php';
require_once '../koneksi.php';
checkRole(['user','supervisor']);

// Pastikan folder assets ada
$folder_path = __DIR__ . "/../assets";
if (!file_exists($folder_path)) {
    mkdir($folder_path, 0777, true);
}

// Path file contoh CSV
$file_path = $folder_path . "/contoh.csv";

// Jika file belum ada, buat otomatis
if (!file_exists($file_path)) {
    $header = [
        'NIK', 'Nama Orang Tua', 'Alamat', 'No Telepon',
        'Nama Balita', 'Jenis Kelamin', 'Tanggal Lahir',
        'Tanggal Pengukuran', 'Berat Badan', 'Tinggi Badan',
        'Puskesmas', 'Kecamatan', 'Kelurahan', 'Posyandu'
    ];

    $contoh_data = [
        '6371031501010001', 'Siti Rahmah', 'Jl. Mawar No. 12', '081234567890',
        'Ahmad', 'Laki-laki', '2020-05-15',
        '2025-10-31', '12.5', '88',
        'Puskesmas Sungai Andai', 'Banjarmasin Utara', 'Sungai Andai', 'Posyandu Melati'
    ];

    // Tulis ke file contoh.csv
    $fp = fopen($file_path, 'w');
    fputcsv($fp, $header);
    fputcsv($fp, $contoh_data);
    fclose($fp);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Batch Upload</title>

    <!-- Custom fonts for this template-->
    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../assets/css/sb-admin-2.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include 'header.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Form Upload</h1>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Project Card Example -->
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Form Upload CSV</h6>
                            </div>
                            <div class="card-body">
                                <form action="preview_csv.php" method="POST" enctype="multipart/form-data">
                                    <h4 class="small font-weight-bold">Pilih File CSV:</h4><br>
                                    <input type="file" name="file_csv" accept=".csv" class="" required><br><br>
                                    <button type="submit" name="preview" class="btn btn-info btn-icon-split btn-sm">
                                        <span class="icon text-white-50">
                                            <i class="fas fa-info-circle"></i>
                                        </span>
                                        <span class="text">Preview Data</span>
                                    </button>
                                </form>
                                <hr>
                                <h6 class="small font-weight-bold">Catatan: Pastikan format CSV sesuai urutan kolom berikut.</h6>
                                <p>
                                NIK, Nama Orang Tua, Alamat, No Telepon,
                                Nama Balita, Jenis Kelamin, Tanggal Lahir,
                                Tanggal Pengukuran, Berat Badan, Tinggi Badan,
                                Kelurahan, Posyandu
                                </p>

                                <h6 class="small font-weight-bold">Klik link berikut untuk mengunduh <a href="../assets/contoh.csv" download>contoh file CSV:</a></h6>
                            </div>
                        </div>
                        
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include 'footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Logout Modal-->
    <?php include 'logout_alert.php'; ?>

    <!-- Bootstrap core JavaScript-->
    <script src="../assets/vendor/jquery/jquery.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../assets/js/sb-admin-2.js"></script>

</body>

</html>