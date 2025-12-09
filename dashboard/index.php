<?php
// index.php (supervisor)
require_once 'auth.php';
include '../koneksi.php';
checkLogin();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_user = (int) ($_SESSION['id_user'] ?? 0);
$id_puskesmas = $koneksi->real_escape_string($_SESSION['id_puskesmas'] ?? '');
$is_admin_dinkes = preg_match('/^DK/i', $id_puskesmas);

// total pending
$q1 = "SELECT COUNT(*) AS total_pending FROM pengukuran_raw WHERE status_qc = 'pending' AND id_puskesmas = '{$id_puskesmas}' AND id_user = '{$id_user}'";
$r1 = mysqli_query($koneksi, $q1); $total_pending = mysqli_fetch_assoc($r1)['total_pending'] ?? 0;

// total validated by this supervisor
$q2 = "SELECT COUNT(*) AS total_validated FROM pengukuran_raw WHERE status_qc IN ('deleted', 'approved') AND id_user = {$id_user} AND id_puskesmas = '{$id_puskesmas}'";
$r2 = mysqli_query($koneksi, $q2); $total_validated = mysqli_fetch_assoc($r2)['total_validated'] ?? 0;

//total return
$q3 = "SELECT COUNT(*) AS total_pending FROM pengukuran_raw WHERE status_qc = 'returned' AND id_puskesmas = '{$id_puskesmas}' AND id_user = '{$id_user}'";
$r3 = mysqli_query($koneksi, $q3); $total_return = mysqli_fetch_assoc($r3)['total_pending'] ?? 0;

// batch list
$batch_query = "
    SELECT DISTINCT batch_id, MIN(tanggal_prediksi) AS batch_time
    FROM pengukuran_raw
    WHERE id_puskesmas = '{$id_puskesmas}' 
    GROUP BY batch_id
    ORDER BY batch_time DESC
";
$batch_result = mysqli_query($koneksi, $batch_query);
$selected_batch = isset($_GET['batch']) ? mysqli_real_escape_string($koneksi, $_GET['batch']) : '';

// main query (only pending, same as you wanted)
$where = " WHERE p.id_user = '{$id_user}' AND p.id_puskesmas = '{$id_puskesmas}' ";

if (!empty($selected_batch)) {
    $where .= " AND p.batch_id = '".mysqli_real_escape_string($koneksi, $selected_batch)."' ";
}


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
    p.updated_at,
    p.batch_id,
    p.created_at
FROM pengukuran_raw p
JOIN users u ON p.id_user = u.id_user
JOIN balita b ON p.id_balita = b.id_balita
JOIN orang_tua ot ON b.nik = ot.nik
JOIN puskesmas pm ON p.id_puskesmas = pm.id_puskesmas
JOIN kelurahan k ON p.id_kelurahan = k.id_kelurahan
LEFT JOIN posyandu py ON p.id_posyandu = py.id_posyandu
{$where}
ORDER BY p.batch_id ASC, p.tanggal_prediksi DESC
";
$result = mysqli_query($koneksi, $query);



?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>User - QC List</title>

<link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
<link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/sb-admin-2.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'sidebar.php'; ?>

    <div id="content-wrapper" class="d-flex flex-column">
      <div id="content">

        <div class="container-fluid">
          <h1 class="h3 mb-4 text-gray-800 text-center font-weight-bold">
            <i class="fas fa-chart-bar text-primary"></i> Data Hasil Prediksi Stunting (Per Batch)
          </h1>

          <!-- counters -->
          <div class="row mb-4">
            <div class="col-md-4">
              <div class="card shadow border-left-warning">
                <div class="card-body">
                  <h5 class="text-danger font-weight-bold"><i class="fas fa-exclamation-circle"></i> Total Data Perlu Upload Ulang</h5>
                  <h3 class="font-weight-bold text-gray-800"><?= (int)$total_return ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card shadow border-left-danger">
                <div class="card-body">
                  <h5 class="text-danger font-weight-bold"><i class="fas fa-exclamation-circle"></i> Total Data Belum Divalidasi</h5>
                  <h3 class="font-weight-bold text-gray-800"><?= (int)$total_pending ?></h3>
                </div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="card shadow border-left-success">
                <div class="card-body">
                  <h5 class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Total Data Sudah Di Validasi</h5>
                  <h3 class="font-weight-bold text-gray-800"><?= (int)$total_validated ?></h3>
                </div>
              </div>
            </div>
          </div>

          <!-- filter -->
          <div class="card shadow mb-4 border-left-primary">
            <div class="card-body">
              <form method="GET" class="form-inline justify-content-center">

                <label for="batch" class="mr-2 font-weight-bold text-gray-700">Pilih Batch:</label>
                <select name="batch" id="batch" class="form-control mr-2">
                  <option value="">-- Tampilkan Semua --</option>
                  <?php while ($b = mysqli_fetch_assoc($batch_result)): ?>
                    <option value="<?= htmlspecialchars($b['batch_id']) ?>" <?= $selected_batch == $b['batch_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($b['batch_id']) ?> (<?= htmlspecialchars($b['batch_time']) ?>)
                    </option>
                  <?php endwhile; ?>
                </select>

                <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-filter"></i> Tampilkan</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-sync"></i> Reset</a>
              </form>
            </div>
          </div>

          <!-- table -->
          <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
              <h6 class="m-0 font-weight-bold">Tabel Data Prediksi</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                  <thead class="bg-primary text-white text-center">
                    <tr>
                      <th>NIK</th><th>Nama Orang Tua</th><th>Alamat</th><th>No Telepon</th>
                      <th>Nama Balita</th><th>Jenis Kelamin</th><th>Tanggal Lahir</th>
                      <th>Tanggal Pengukuran</th><th>Umur (Bulan)</th><th>Berat</th><th>Tinggi</th>
                      <th>BB/U</th><th>TB/U</th><th>BB/TB</th><th>Puskesmas</th>
                      <th>Kecamatan</th><th>Kelurahan</th><th>Posyandu</th><th>Nama Petugas</th>
                      <th>Level Stunting</th><th>Skor Prediksi</th><th>Saran</th><th>Tanggal Prediksi</th>
                      <th>Status QC</th><th>Supervisor</th><th>Catatan</th><th>Dibuat</th><th>Diperbarui</th>
                      <th>Batch ID</th><th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (mysqli_num_rows($result) > 0): ?>
                      <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                          <td><?= htmlspecialchars($row['nik']) ?></td>
                          <td><?= htmlspecialchars($row['nama_orangtua']) ?></td>
                          <td><?= htmlspecialchars($row['alamat']) ?></td>
                          <td><?= htmlspecialchars($row['no_hp']) ?></td>
                          <td><?= htmlspecialchars($row['nama_balita']) ?></td>
                          <td><?= htmlspecialchars($row['jenis_kelamin_balita']) ?></td>
                          <td><?= htmlspecialchars($row['tanggal_lahir']) ?></td>
                          <td><?= htmlspecialchars($row['tanggal_pengukuran']) ?></td>
                          <td><?= htmlspecialchars($row['umur_dalam_bulan']) ?></td>
                          <td><?= htmlspecialchars($row['berat']) ?></td>
                          <td><?= htmlspecialchars($row['tinggi']) ?></td>
                          <td><?= htmlspecialchars($row['bbu']) ?></td>
                          <td><?= htmlspecialchars($row['tbu']) ?></td>
                          <td><?= htmlspecialchars($row['bbtb']) ?></td>
                          <td><?= htmlspecialchars($row['nama_puskesmas']) ?></td>
                          <td><?= htmlspecialchars($row['kecamatan']) ?></td>
                          <td><?= htmlspecialchars($row['nama_kelurahan']) ?></td>
                          <td><?= htmlspecialchars($row['nama_posyandu'] ?? '-') ?></td>
                          <td><?= htmlspecialchars($row['full_name']) ?></td>
                          <td><?= htmlspecialchars($row['level_stunting']) ?></td>
                          <td><?= htmlspecialchars($row['skor_prediksi']) ?></td>
                          <td><?= htmlspecialchars($row['saran'] ?? '-') ?></td>
                          <td><?= htmlspecialchars($row['tanggal_prediksi']) ?></td>
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
                            <?= htmlspecialchars($row['status_qc']) ?>
                          </td>
                          <td><?= htmlspecialchars($row['supervisor_id'] ?? '-') ?></td>
                          <td><?= htmlspecialchars($row['catatan_supervisor'] ?? '-') ?></td>
                          <td><?= htmlspecialchars($row['created_at']) ?></td>
                          <td><?= htmlspecialchars($row['updated_at']) ?></td>
                          <td><?= htmlspecialchars($row['batch_id']) ?></td>
                          <td>
                             <!-- Edit -->
                            <?php if ($row['status_qc'] == 'returned'): ?>
                              <a href="edit_pengukuran.php?id=<?php echo $row['raw_id'] ?>" class="btn btn-warning btn-sm mb-1">
                                <i class="fas fa-edit"></i> Edit 
                              </a>
                            <?php endif; ?>
                            <?php if ($row['status_qc'] == 'returned'): ?>
                              <a href="statusDelete.php?action=selesaiEdit&id=<?php echo $row['raw_id'] ?>" class="btn btn-warning btn-sm mb-1">
                                <i class="fas fa-edit"></i> Selesai Edit 
                              </a>
                            <?php endif; ?>

                            <?php if ($row['status_qc'] == 'returned'): ?>
                                <p class="btn btn-danger btn-sm btn-delete mb-1">
                                  <i class="fas fa-note"></i> Anda harus menekan tombol "Edit", maka sistem akan membuat data pengukuran baru dan silahkan perbaiki berdasarkan catatan supervisor.
                                </p>
                            <?php endif; ?>
                            <?php if ($row['status_qc'] == 'deleted'): ?>
                                <p class="btn btn-danger btn-sm btn-delete mb-1">
                                  <i class="fas fa-note"></i> Dideteksi baris ini melakukan pelanggaran dan di hilangkan oleh supervisor, tidak boleh diinput ulang.
                                </p>
                            <?php endif; ?>
                            
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr><td colspan="30" class="text-center text-muted">Tidak ada data yang tersimpan</td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Modal Delete -->
          <div class="modal fade" id="modalDelete" tabindex="-1">
            <div class="modal-dialog">
              <form method="POST" action="qc_action.php?action=delete" id="formDelete">
                <div class="modal-content">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Konfirmasi Hapus Data</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="id_pengukuran" id="delete_id">
                    <p>Anda akan menghapus data ini. <strong>Catatan supervisor wajib diisi!</strong></p>
                    <textarea class="form-control" name="catatan_supervisor" id="catatan_supervisor" rows="3" placeholder="Masukkan alasan..." required></textarea>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Hapus</button>
                  </div>
                </div>
              </form>
            </div>
          </div>
          <!-- Modal return -->
           <div class="modal fade" id="modalReturn" tabindex="-1">
            <div class="modal-dialog">
              <form action="qc_action.php?action=return" method="POST">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>

                  <div class="modal-body">
                    <input type="hidden" name="return_id" id="return_id">

                    <div class="mb-3">
                      <label>Catatan Return</label>
                      <textarea name="catatan_return" id="catatan_return" class="form-control"></textarea>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Return</button>
                  </div>
                </div>
              </form>
            </div>
          </div>


          <!-- Toast (show on redirect) -->
          <div class="toast" id="toastSuccess" style="position: fixed; top: 20px; right: 20px; z-index:2000;" data-delay="3500">
            <div class="toast-header bg-success text-white">
              <strong class="mr-auto">Sukses</strong>
              <button type="button" class="ml-2 mb-1 close" data-dismiss="toast">&times;</button>
            </div>
            <div class="toast-body" id="toastBody">Aksi berhasil.</div>
          </div>

        </div><!-- /.container-fluid -->
      </div><!-- /#content -->

      <?php include 'footer.php'; ?>
    </div><!-- /#content-wrapper -->
</div><!-- /#wrapper -->

<!-- Logout Modal-->
    <?php include 'logout_alert.php'; ?>

<!-- JS (jQuery first) -->
<script src="../assets/vendor/jquery/jquery.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>
<script src="../assets/js/sb-admin-2.js"></script> 

<script>
$(function(){
  // tombol delete klik -> isi hidden & buka modal
  $(document).on('click', '.btn-delete', function(){
    var id = $(this).data('id');
    $('#delete_id').val(id);
    $('#catatan_supervisor').val('');
    $('#modalDelete').modal('show');
  });

  // tampilkan toast bila ada param success di URL
  const params = new URLSearchParams(window.location.search);
  if (params.get('success') === '1') {
    var msg = params.get('msg') ? decodeURIComponent(params.get('msg')) : 'Aksi berhasil';
    $('#toastBody').text(msg);
    $('#toastSuccess').toast('show');
  } else if (params.get('error') === '1') {
    var emsg = params.get('msg') ? decodeURIComponent(params.get('msg')) : 'Terjadi kesalahan';
    alert('Error: ' + emsg);
  }
});

$(function(){

  // klik tombol return -> isi hidden input + buka modal
  $(document).on('click', '.btn-return', function(){
    var id = $(this).data('id');
    $('#return_id').val(id);
    $('#catatan_return').val('');
    $('#modalReturn').modal('show');
  });

  // tampilkan toast bila success/error
  const params = new URLSearchParams(window.location.search);
  if (params.get('success') === '1') {
    var msg = params.get('msg') ? decodeURIComponent(params.get('msg')) : 'Aksi berhasil';
    $('#toastBody').text(msg);
    $('#toastSuccess').toast('show');
  } else if (params.get('error') === '1') {
    var emsg = params.get('msg') ? decodeURIComponent(params.get('msg')) : 'Terjadi kesalahan';
    $('#toastErrorBody').text(emsg);
    $('#toastError').toast('show');
  }

});

</script>

</body>
</html>
