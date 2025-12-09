<?php
require_once 'auth.php';
checkLogin();

// --- 1️⃣ Pastikan ada hasil Fuzzy ---
if (!isset($_SESSION['hasil_fuzzy'])) {
    echo "<script>alert('Anda tidak dapat membuka halaman ini tanpa terlebih dahulu mengisi Upload Manual.'); window.location='upload_data.php';</script>";
    exit;
}

$batch_id = $_GET['batch_id'];

// Ambil seluruh hasil fuzzy
$hasil = $_SESSION['hasil_fuzzy'];
unset($_SESSION['hasil_fuzzy']);

// --- 2️⃣ Ambil data session ---
$nik             = $_SESSION['nik']             ?? '-';
$nama_orangtua   = $_SESSION['nama_orangtua']   ?? '-';
$nama_balita     = $_SESSION['nama_balita']     ?? '-';
$berat_badan     = $_SESSION['berat_badan']     ?? '-';
$tinggi_badan    = $_SESSION['tinggi_badan']    ?? '-';

$level_stunting  = $hasil['level_stunting']     ?? 'Tidak diketahui';
$prediksi        = $hasil['prediksi']           ?? '-';
$saran           = $hasil['saran_gizi']         ?? 'Tidak ada saran';

// --- 3️⃣ Tentukan warna berdasarkan level ---
$warna = "success"; // default

if (stripos($level_stunting, "parah") !== false) {
    $warna = "danger";
} elseif (stripos($level_stunting, "sedang") !== false) {
    $warna = "warning";
} elseif (stripos($level_stunting, "ringan") !== false) {
    $warna = "info";
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Hasil Prediksi Stunting</title>

    <!-- Custom fonts for this template-->
    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900"
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

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">📊 Hasil Prediksi Status Stunting</h1>

                    <!-- Hasil Card -->
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card shadow mb-4 border-left-<?php echo $warna; ?>">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-<?php echo $warna; ?> text-white">
                                    <h6 class="m-0 font-weight-bold">Hasil Analisis Batch ID: <?php echo htmlspecialchars($batch_id); ?></h6>
                                    <a href="hasil_analisis.php?batch_id=<?php echo htmlspecialchars($batch_id); ?>" class="btn btn-light btn-sm">
                                        <i class="fas fa-download"></i> Download Hasil
                                    </a>
                                </div>

                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <tr><th>NIK</th><td><?php echo htmlspecialchars($nik); ?></td></tr>
                                            <tr><th>Nama Orang Tua</th><td><?php echo htmlspecialchars($nama_orangtua); ?></td></tr>
                                            <tr><th>Nama Balita</th><td><?php echo htmlspecialchars($nama_balita); ?></td></tr>
                                            <tr><th>Berat Badan (kg)</th><td><?php echo htmlspecialchars($berat_badan); ?></td></tr>
                                            <tr><th>Tinggi Badan (cm)</th><td><?php echo htmlspecialchars($tinggi_badan); ?></td></tr>
                                            <tr><th>Level Stunting</th>
                                                <td><span class="badge badge-<?php echo $warna; ?> p-2">
                                                    <?php echo htmlspecialchars($level_stunting); ?>
                                                </span></td>
                                            </tr>
                                            <tr><th>Prediksi (Skor Fuzzy)</th><td><?php echo htmlspecialchars($prediksi); ?></td></tr>
                                        </table>
                                    </div>
                                    <div class="alert alert-<?php echo $warna; ?> mt-4" role="alert">
                                        <strong>Saran:</strong><br>
                                        <?php echo htmlspecialchars($saran); ?>
                                    </div>

                                    <a href="upload_data.php" class="btn btn-secondary btn-icon-split mt-2">
                                        <span class="icon text-white-50">
                                            <i class="fas fa-arrow-left"></i>
                                        </span>
                                        <span class="text">Kembali ke Form Upload</span>
                                    </a>
                                </div>
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
