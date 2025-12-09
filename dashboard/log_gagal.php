<?php
require_once 'auth.php';
checkLogin();
require_once '../koneksi.php';

// Ambil ID Puskesmas dari session
$id_puskesmas = $_SESSION['id_puskesmas'] ?? '';

// Lokasi file log gagal
$log_file = __DIR__ . '/log_gagal.csv';

// Fungsi hapus log gagal user
if (isset($_GET['hapus']) && $_GET['hapus'] == '1') {
    if (file_exists($log_file)) {
        $rows = [];
        $header = [];
        if (($handle = fopen($log_file, "r")) !== FALSE) {
            $header = fgetcsv($handle);
            while (($data = fgetcsv($handle)) !== FALSE) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        if (!empty($header) && !empty($rows)) {
            $id_col = array_search('ID Puskesmas', $header);
            if ($id_col !== false) {
                // Filter: hapus baris sesuai id_puskesmas user
                $rows_ke_simpan = array_filter($rows, function($row) use ($id_col, $id_puskesmas) {
                    return isset($row[$id_col]) && $row[$id_col] !== $id_puskesmas;
                });

                // Tulis ulang file
                $fp = fopen($log_file, 'w');
                fputcsv($fp, $header);
                foreach ($rows_ke_simpan as $r) {
                    fputcsv($fp, $r);
                }
                fclose($fp);
            }
        }
    }
    header("Location: log_gagal.php");
    exit;
}

// Baca file log gagal
$rows = [];
$header = [];
if (file_exists($log_file)) {
    if (($handle = fopen($log_file, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Ambil header
        while (($data = fgetcsv($handle)) !== FALSE) {
            // Tampilkan hanya baris yang sesuai ID Puskesmas user
            $id_col = array_search('ID Puskesmas', $header);
            if ($id_col !== false && $data[$id_col] === $id_puskesmas) {
                $rows[] = $data;
            }
        }
        fclose($handle);
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Log Gagal Upload CSV</title>

    <!-- Custom fonts and styles for SB Admin 2 -->
    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700,800,900" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.css" rel="stylesheet">

    <!-- DataTables Bootstrap5 -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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

                    <h1 class="h3 mb-4 text-gray-800">📋 Log Data Gagal Upload CSV</h1>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-danger">Log Gagal CSV</h6>
                            <div>
                                <a href="?hapus=1" class="btn btn-warning btn-sm me-2"
                                   onclick="return confirm('⚠️ Apakah Anda yakin ingin menghapus log gagal Anda?')">
                                   🗑️ Hapus Log Gagal
                                </a>
                                <a href="index.php" class="btn btn-light btn-sm">⬅️ Kembali</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($rows)): ?>
                                <div class="alert alert-success text-center">
                                    ✅ Tidak ada data gagal untuk puskesmas Anda.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="logTable" class="table table-striped table-bordered align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <?php foreach ($header as $col): ?>
                                                    <th><?= htmlspecialchars($col) ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rows as $row): ?>
                                                <tr>
                                                    <?php foreach ($row as $cell): ?>
                                                        <td><?= htmlspecialchars($cell) ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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

    <!-- Logout Modal -->
    <?php include 'logout_alert.php'; ?>

    <!-- Bootstrap core JavaScript -->
    <script src="../assets/vendor/jquery/jquery.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>
    <script src="../assets/js/sb-admin-2.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#logTable').DataTable({
                language: {
                    search: "🔍 Cari:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Tidak ditemukan data yang sesuai",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data tersedia",
                    infoFiltered: "(disaring dari _MAX_ total data)"
                },
                pageLength: 10
            });
        });
    </script>

</body>
</html>
