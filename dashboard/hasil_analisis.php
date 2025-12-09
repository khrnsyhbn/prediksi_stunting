<?php
// Mulai session (pastikan tidak duplikat)
require_once 'auth.php'; 
include '../koneksi.php';
checkLogin();

error_reporting(E_ALL);
ini_set('display_errors', 1);


$id_puskesmas = $_SESSION['id_puskesmas'] ?? '';
$is_admin_dinkes = preg_match('/^DK/i', $id_puskesmas); // Cek prefix "DK"

// === 1️⃣ Ambil daftar batch unik ===
$batch_query = "
    SELECT DISTINCT batch_id, MIN(tanggal_prediksi) AS batch_time
    FROM pengukuran_raw
    WHERE id_puskesmas = '$id_puskesmas'
    GROUP BY batch_id
    ORDER BY batch_time DESC
";
$batch_result = mysqli_query($koneksi, $batch_query);

// === 2️⃣ Ambil batch pilihan dari user ===
$selected_batch = isset($_GET['batch']) ? mysqli_real_escape_string($koneksi, $_GET['batch']) : '';

// === 3️⃣ Query utama ===
$query = "
SELECT 
    p.raw_id,
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
WHERE p.id_puskesmas = '$id_puskesmas'
";

if (!empty($selected_batch)) {
    $query .= " AND p.batch_id = '".mysqli_real_escape_string($koneksi, $selected_batch)."' ";
}

$query .= " ORDER BY p.batch_id ASC, p.tanggal_prediksi DESC";

$result = mysqli_query($koneksi, $query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Hasil Prediksi Stunting</title>
    
<link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
<link href="../assets/css/sb-admin-2.css" rel="stylesheet">
</head>
<body>
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
                    <h1 class="h3 mb-4 text-gray-800 text-center font-weight-bold">
                        <i class="fas fa-chart-bar text-primary"></i> Data Hasil Prediksi Stunting (Per Batch)
                    </h1>

                    <!-- Filter Form -->
                    <div class="card shadow mb-4 border-left-primary">
                        <div class="card-body">
                            <form method="GET" class="form-inline justify-content-center">
                                <label for="batch" class="mr-2 font-weight-bold text-gray-700">Pilih Batch:</label>
                                <select name="batch" id="batch" class="form-control mr-2">
                                    <option value="">-- Tampilkan Semua --</option>
                                    <?php while ($row = mysqli_fetch_assoc($batch_result)): ?>
                                        <option value="<?php echo htmlspecialchars($row['batch_id']); ?>"
                                            <?php if ($selected_batch == $row['batch_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($row['batch_id']); ?> (<?php echo htmlspecialchars($row['batch_time']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>

                                <button type="submit" class="btn btn-primary mr-2">
                                    <i class="fas fa-filter"></i> Tampilkan
                                </button>

                                <a href="hasil_analisis.php" class="btn btn-secondary">
                                    <i class="fas fa-sync"></i> Reset
                                </a>
                            </form>

                            <!-- Tombol Download BERADA DI LUAR FORM -->
                            <?php if (!empty($selected_batch)): ?>
                                <div class="text-center mt-3">
                                    <a href="export_batch_csv.php?batch=<?php echo urlencode($selected_batch); ?>" 
                                    class="btn btn-success btn-lg">
                                        <i class="fas fa-download"></i> Download Batch Ini (CSV)
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>


                    <!-- Data Table -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">Tabel Data Prediksi</h6>
                        </div>
                        <div class="card-body">
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
                                            <th>Berat Badan (Kg)</th>
                                            <th>Tinggi Badan (Cm)</th>
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
                                            <th>Status Kontrol Kualitas</th>
                                            <th>Supervisor</th>
                                            <th>Catatan Perubahan</th>
                                            <th>Dibuat Pada </th>
                                            <th>Diperbarui Pada</th>
                                            <th>Batch ID</th>
                                            <th>Download</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($result) > 0): ?>
                                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['nik']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_orangtua']); ?></td>
                                                <td><?php echo htmlspecialchars($row['alamat']); ?></td>
                                                <td><?php echo htmlspecialchars($row['no_hp']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_balita']); ?></td>
                                                <td><?php echo htmlspecialchars($row['jenis_kelamin_balita']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tanggal_lahir']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tanggal_pengukuran']); ?></td>
                                                <td><?php echo htmlspecialchars($row['umur_dalam_bulan']); ?></td>
                                                <td><?php echo htmlspecialchars($row['berat']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tinggi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['bbu']); ?></td>
                                                <td><?php echo htmlspecialchars($row['tbu']); ?></td>
                                                <td><?php echo htmlspecialchars($row['bbtb']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_puskesmas']); ?></td>
                                                <td><?php echo htmlspecialchars($row['kecamatan']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_kelurahan']); ?></td>
                                                <td><?php echo htmlspecialchars($row['nama_posyandu']??'-'); ?></td>
                                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['level_stunting']); ?></td>
                                                <td><?php echo htmlspecialchars($row['skor_prediksi']); ?></td>
                                                <td><?php echo htmlspecialchars($row['saran']??'-'); ?></td>
                                                <td><?php echo htmlspecialchars($row['tanggal_prediksi']); ?></td>
                                                <td>
                                                    <?php 
                                                        $status = strtolower($row['status_qc']); // lowercase untuk konsistensi
                                                        $color = 'black'; // default
                                                        switch($status) {
                                                            case 'pending':
                                                                $color = '#00BFFF';
                                                                break;
                                                            case 'approved':
                                                                $color = '#28a745';
                                                                break;
                                                            case 'returned':
                                                                $color = '#ffc107'; // kuning agak sulit dibaca di background putih
                                                                break;
                                                            case 'deleted':
                                                                $color = '#dc3545';
                                                                break;
                                                        }
                                                    ?>
                                                    <span style="color: <?php echo $color; ?>; font-weight: bold;">
                                                        <?php echo htmlspecialchars(ucfirst($row['status_qc'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['supervisor_id']??'-'); ?></td>
                                                <td><?php echo htmlspecialchars($row['catatan_supervisor']??'-'); ?></td>
                                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                                                <td><?php echo htmlspecialchars($row['batch_id']); ?></td>
                                                <td>
                                                    <!-- Download -->
                                                <a href="tampil_download.php?action=lihat&id=<?php echo $row['raw_id'] ?>" class="btn btn-success btn-sm mb-1">
                                                <i class="fas fa-file-alt"></i> Download
                                                </a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="22" class="text-center text-muted">Tidak ada data yang tersimpan</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
<script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>
<script src="../assets/js/sb-admin-2.js"></script>
</body>
</html>


