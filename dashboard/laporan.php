<?php
require_once 'auth.php';
include '../koneksi.php';
checkRole(['user']);

error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_puskesmas = $_SESSION['id_puskesmas'] ?? '';
$is_admin_dinkes = preg_match('/^DK/i', $id_puskesmas); // Cek prefix "DK"

// === QUERY: Ambil hanya pengukuran terakhir setiap balita ===
$query = "
SELECT 
    ot.nik,
    ot.nama AS nama_orangtua,
    ot.alamat,
    ot.no_hp,
    b.nama_balita,
    b.jenis_kelamin_balita,
    b.tanggal_lahir,
    p.tanggal_pengukuran,
    p.umur_dalam_bulan,
    p.berat,
    p.tinggi,
    p.bbu,
    p.tbu,
    p.bbtb,
    pm.nama_puskesmas,
    k.kecamatan,
    k.nama_kelurahan,
    py.nama_posyandu,
    u.full_name,
    p.level_stunting,
    p.skor_prediksi,
    p.saran,
    p.tanggal_prediksi,
    p.status_qc,
    p.supervisor_id,
    p.catatan_supervisor,
    p.created_at,
    p.updated_at,
    p.batch_id
FROM pengukuran_raw p
JOIN users u ON p.id_user = u.id_user
JOIN balita b ON p.id_balita = b.id_balita
JOIN orang_tua ot ON b.nik = ot.nik
JOIN puskesmas pm ON p.id_puskesmas = pm.id_puskesmas
JOIN kelurahan k ON p.id_kelurahan = k.id_kelurahan
LEFT JOIN posyandu py ON p.id_posyandu = py.id_posyandu
INNER JOIN (
    SELECT id_balita, MAX(tanggal_pengukuran) AS latest_pengukuran
    FROM pengukuran_raw
    WHERE status_qc = 'approved'
    GROUP BY id_balita
) AS latest ON latest.id_balita = p.id_balita AND latest.latest_pengukuran = p.tanggal_pengukuran
WHERE 
    (" . ($is_admin_dinkes ? "1=1" : "p.id_puskesmas = '$id_puskesmas'") . ")
ORDER BY p.tanggal_pengukuran DESC
";
// Jika user Dinkes (DK…) → WHERE 1=1 → semua data muncul.
// Jika user puskesmas biasa → WHERE p.id_puskesmas = $id_puskesmas

$result = mysqli_query($koneksi, $query);
$total_data = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Data Pengukuran Balita</title>

    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.css" rel="stylesheet">
    <link href="../assets/vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
</head>

<body id="page-top">

<!-- Page Wrapper -->
<div id="wrapper">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    <!-- End Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">

            <!-- Topbar -->
            <?php  ?>
            <!-- End of Topbar -->

            <!-- Begin Page Content -->
            <div class="container-fluid">

                <!-- Page Heading -->
                <h1 class="h3 mb-4 text-gray-800 text-center font-weight-bold">
                    📋 Laporan Data Pengukuran Terakhir Balita
                </h1>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 bg-primary text-white">
                        <h6 class="m-0 font-weight-bold">Daftar Data Terbaru</h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Total Data:</strong> <?= number_format($total_data); ?> balita</p>

                        <a href="laporan_pdf.php" target="_blank" class="btn btn-success mb-3">
                            <i class="fas fa-print"></i> Cetak Laporan (PDF)
                        </a>

                        <a href="index.php" class="btn btn-secondary mb-3 ml-2">
                            <i class="fas fa-arrow-left"></i> Kembali ke Halaman Utama
                        </a>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                <thead class="bg-primary text-white text-center">
                                    <tr>
                                        <th>NIK</th>
                                        <th>Nama Orang Tua</th>
                                        <th>Alamat</th>
                                        <th>No Telepon</th>
                                        <th>Nama Balita</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Tanggal Lahir</th>
                                        <th>Tanggal Pengukuran</th>
                                        <th>Umur (Bulan)</th>
                                        <th>Berat Badan</th>
                                        <th>Tinggi Badan</th>
                                        <th>BB/U</th>
                                        <th>TB/U</th>
                                        <th>BB/TB</th>
                                        <th>Puskesmas</th>
                                        <th>Kecamatan</th>
                                        <th>Kelurahan</th>
                                        <th>Posyandu</th>
                                        <th>Nama Petugas</th>
                                        <th>Level Stunting</th>
                                        <th>Skor Prediksi</th>
                                        <th>Saran</th>
                                        <th>Tanggal Prediksi</th>
                                        <th>Status QC</th>
                                        <th>Supervisor</th>
                                        <th>Catatan</th>
                                        <th>Dibuat</th>
                                        <th>Diperbarui</th>
                                        <th>Batch</th>
                                    </tr>
                                </thead>

                                <tbody>
                                <?php if ($total_data > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['nik']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_orangtua']); ?></td>
                                        <td><?= htmlspecialchars($row['alamat']); ?></td>
                                        <td><?= htmlspecialchars($row['no_hp']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_balita']); ?></td>
                                        <td><?= htmlspecialchars($row['jenis_kelamin_balita']); ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_lahir']); ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_pengukuran']); ?></td>
                                        <td><?= htmlspecialchars($row['umur_dalam_bulan']); ?></td>
                                        <td><?= htmlspecialchars($row['berat']); ?></td>
                                        <td><?= htmlspecialchars($row['tinggi']); ?></td>
                                        <td><?= htmlspecialchars($row['bbu']); ?></td>
                                        <td><?= htmlspecialchars($row['tbu']); ?></td>
                                        <td><?= htmlspecialchars($row['bbtb']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_puskesmas']); ?></td>
                                        <td><?= htmlspecialchars($row['kecamatan']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_kelurahan']); ?></td>
                                        <td><?= htmlspecialchars($row['nama_posyandu'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['full_name']); ?></td>
                                        <td><?= htmlspecialchars($row['level_stunting']); ?></td>
                                        <td><?= htmlspecialchars($row['skor_prediksi']); ?></td>
                                        <td><?= htmlspecialchars($row['saran'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['tanggal_prediksi']); ?></td>
                                        <td>
                                            <?php 
                                                $status = strtolower($row['status_qc']);
                                                $color = match($status) {
                                                    'pending' => '#00BFFF',
                                                    'approved' => '#28a745',
                                                    'returned' => '#ffc107',
                                                    'deleted' => '#dc3545',
                                                    default => 'black'
                                                };
                                            ?>
                                            <span style="color: <?= $color ?>; font-weight:bold;">
                                                <?= ucfirst($row['status_qc']); ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($row['supervisor_id'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['catatan_supervisor'] ?? '-'); ?></td>
                                        <td><?= htmlspecialchars($row['created_at']); ?></td>
                                        <td><?= htmlspecialchars($row['updated_at']); ?></td>
                                        <td><?= htmlspecialchars($row['batch_id']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="29" class="text-center text-muted">Tidak ada data</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>
            <!-- End of container-fluid -->

        </div>
        <!-- End Main Content -->

        <!-- Footer -->
        <?php include 'footer.php'; ?>
        <!-- End Footer -->

    </div>
    <!-- End Content Wrapper -->

</div>
<!-- End Page Wrapper -->

<!-- Scroll to Top -->
<a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
</a>

<!-- Logout Modal -->
<?php include 'logout_alert.php'; ?>

<!-- Scripts -->
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
