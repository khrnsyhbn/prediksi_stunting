<?php
require_once 'auth.php';
include '../koneksi.php';
checkLogin(); // Semua role boleh akses, jadi TIDAK menggunakan checkRole()

// Ambil semua kelurahan
$query = "SELECT * FROM puskesmas ORDER BY id_puskesmas ASC";
$result = mysqli_query($koneksi, $query);
$total = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Puskesmas</title>

    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
</head>
<body id="page-top">

<div id="wrapper">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <!-- End Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <div id="content">

            <!-- Topbar -->
            
            <!-- End of Topbar -->

            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800 text-center font-weight-bold">
                    Data Puskesmas
                </h1>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Daftar Puskesmas</h6>
                    </div>
                    <div class="card-body">

                        <p><strong>Total Puskesmas:</strong> <?= $total ?> puskesmas</p>
                        <a href="index.php" class="btn btn-secondary mb-3">
                            <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
                        </a>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                <thead class="bg-primary text-white text-center">
                                    <tr>
                                        <th>ID Puskesmas</th>
                                        <th>Nama Puskesmas</th>
                                        <th>Alamat</th>
                                        <th>No Telepon</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id_puskesmas']) ?></td>
                                            <td><?= htmlspecialchars($row['nama_puskesmas']) ?></td>
                                            <td><?= htmlspecialchars($row['alamat']) ?></td>
                                            <td><?= htmlspecialchars($row['telepon']) ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <?php include 'footer.php'; ?>
        <!-- End Footer -->

    </div>
</div>

<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<?php include 'logout_alert.php'; ?>

<script src="../assets/vendor/jquery/jquery.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>
<script src="../assets/js/sb-admin-2.js"></script>

<script src="../assets/vendor/datatables/jquery.dataTables.js"></script>
<script src="../assets/vendor/datatables/dataTables.bootstrap4.js"></script>

<script>
$(document).ready(function() {
    $('#dataTable').DataTable({
        "language": {
            "lengthMenu": "Tampilkan _MENU_ entri",
            "zeroRecords": "Tidak ada data ditemukan",
            "info": "Menampilkan _PAGE_ dari _PAGES_",
            "search": "Cari:",
            "paginate": {
                "next": "Berikutnya",
                "previous": "Sebelumnya"
            }
        }
    });
});
</script>

</body>
</html>
