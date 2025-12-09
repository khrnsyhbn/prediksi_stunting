<?php
require_once 'auth.php';
include '../koneksi.php';
checkLogin();

$query = "SELECT * FROM standar_bbtb ORDER BY tngg_bdn ASC";
$result = mysqli_query($koneksi, $query);
$total = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Standar BB/TB</title>

    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
    <style>
        td, th {
            text-align: center;
            vertical-align: middle;
        }
    </style>

</head>
<body id="page-top">
<div id="wrapper">

    <?php include 'sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800 text-center font-weight-bold">
                    Standar Berat Badan / Tinggi Badan (BB/TB)
                </h1>

                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Daftar Standar BB/TB</h6>
                    </div>

                    <div class="card-body">
                        <p><strong>Total Data:</strong> <?= $total ?></p>
                        <p>Data ini digunakan untuk menentukan z-score BB/U, TB/U dan BB/TB balita umur <strong>24 - 60 bulan</strong></p>
                        <p><strong>Referensi : </strong> <a href="https://peraturan.bpk.go.id/Details/152505/permenkes-no-2-tahun-2020">PERMENKES NO.2 TAHUN 2020</a></p>
                        <p><strong>L : </strong>Laki-laki</p>
                        <p><strong>P : </strong>Perempuan</p>

                        <a href="index.php" class="btn btn-secondary mb-3">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%">
                                <thead class="bg-primary text-white text-center">
                                    <tr>
                                        <th>ID</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Tinggi Badan (cm)</th>
                                        <th>SD -1</th>
                                        <th>Median (Rata-rata)</th>
                                        <th>SD +1</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                                    <tr>
                                        <td><?= $row['id_bbtb'] ?></td>
                                        <td><?= $row['jenis_kelamin'] ?></td>
                                        <td><?= $row['tngg_bdn'] ?></td>
                                        <td><?= $row['sd_neg1'] ?></td>
                                        <td><?= $row['median'] ?></td>
                                        <td><?= $row['sd_pos1'] ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

        </div>

        <?php include 'footer.php'; ?>
    </div>

</div>

<?php include 'logout_alert.php'; ?>

<script src="../assets/vendor/jquery/jquery.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>
<script src="../assets/js/sb-admin-2.js"></script>

<script src="../assets/vendor/datatables/jquery.dataTables.js"></script>
<script src="../assets/vendor/datatables/dataTables.bootstrap4.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable();
});
</script>

</body>
</html>
