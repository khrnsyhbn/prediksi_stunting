<?php
require_once 'auth.php';
require_once '../koneksi.php';
checkRole(['user','supervisor']);

$id_puskesmas = $_SESSION['id_puskesmas'] ?? '';
$kelurahan_list = [];
$nama_puskesmas = '';
$selected_posyandu = 0;

$raw_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if(!$raw_id){
    echo "<script>alert('ID data tidak ditemukan!'); window.location='index.php';</script>";
    exit;
}

// Ambil data pengukuran + balita + orang tua + kelurahan + puskesmas
$query = "
SELECT pr.*, b.nama_balita, b.jenis_kelamin_balita, b.tanggal_lahir, 
       ot.nik, ot.nama AS nama_orangtua, ot.alamat, ot.no_hp, 
       p.nama_puskesmas, k.id_kelurahan, k.nama_kelurahan, py.nama_posyandu
FROM pengukuran_raw pr
LEFT JOIN balita b ON pr.id_balita = b.id_balita
LEFT JOIN orang_tua ot ON b.nik = ot.nik
LEFT JOIN puskesmas p ON pr.id_puskesmas = p.id_puskesmas
LEFT JOIN kelurahan k ON pr.id_kelurahan = k.id_kelurahan
LEFT JOIN posyandu py ON pr.id_posyandu = py.id_posyandu
WHERE pr.raw_id = '$raw_id' AND pr.id_puskesmas = '$id_puskesmas'
LIMIT 1
";
$result = mysqli_query($koneksi, $query);
if(!$result || mysqli_num_rows($result) == 0){
    echo "<script>alert('Data tidak ditemukan atau Anda tidak memiliki akses!'); window.location='index.php';</script>";
    exit;
}

$row = mysqli_fetch_assoc($result);
$nama_puskesmas = $row['nama_puskesmas'] ?? '';
$selected_posyandu = intval($row['id_posyandu'] ?? 0);
$id_kelurahan = $row['id_kelurahan'] ?? 'ada';
// Ambil list kelurahan sesuai puskesmas
if($id_puskesmas){
    $query_kel = "SELECT id_kelurahan, nama_kelurahan FROM kelurahan WHERE id_puskesmas = '$id_puskesmas' ORDER BY nama_kelurahan ASC";
    $result_k = mysqli_query($koneksi, $query_kel);
    while($kel = mysqli_fetch_assoc($result_k)){
        $kelurahan_list[] = $kel;
    }
}

if($id_kelurahan){
    // Ambil posyandu berdasarkan id_kelurahan
    $q_pos = "SELECT id_posyandu, nama_posyandu FROM posyandu WHERE id_kelurahan='$id_kelurahan' ORDER BY nama_posyandu ASC";
    $res_pos = mysqli_query($koneksi, $q_pos);
    while($pos = mysqli_fetch_assoc($res_pos)){
        $posyandu_list[] = $pos;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Edit Data Balita & Pengukuran</title>
<link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
<link href="../assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/sb-admin-2.css" rel="stylesheet">
<script src="../assets/vendor/jquery/jquery.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
</head>
<body id="page-top">
<div id="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            
            <div class="container-fluid">
                <h1 class="h3 mb-4 text-gray-800">Edit Data Balita & Pengukuran</h1>
                <div class="row">
                    <div class="col-lg-10">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                <h6 class="m-0 font-weight-bold text-primary">Form Edit Data</h6>
                                <a href="index.php" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                            <div class="card-body">
                                <form action="proses_edit_pengukuran.php" method="POST">
                                    <input type="hidden" name="raw_id" value="<?= htmlspecialchars($row['raw_id'] ?? '') ?>">

                                    <div class="row">
                                        <!-- Data Orang Tua -->
                                        <div class="col-md-6">
                                            <h5 class="text-primary font-weight-bold mb-3">Data Orang Tua</h5>
                                            <div class="form-group">
                                                <label>NIK <span class="text-danger">*</span>:</label>
                                                <input type="text" name="nik" class="form-control" readonly value="<?= htmlspecialchars($row['nik'] ?? '') ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Nama Orang Tua <span class="text-danger">*</span>:</label>
                                                <input type="text" name="nama" class="form-control" readonly value="<?= htmlspecialchars($row['nama_orangtua'] ?? '') ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Alamat <span class="text-danger">*</span>:</label>
                                                <textarea name="alamat" class="form-control" rows="2" readonly value="<?= htmlspecialchars($row['alamat'] ?? '') ?>"><?= htmlspecialchars($row['alamat'] ?? '') ?></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label>No. Telepon:</label>
                                                <input type="text" name="no_telepon" class="form-control" readonly value="<?= htmlspecialchars($row['no_hp'] ?? '') ?>">
                                            </div>
                                        </div>

                                        <!-- Data Balita -->
                                        <div class="col-md-6">
                                            <h5 class="text-primary font-weight-bold mb-3">Data Balita</h5>
                                            <div class="form-group">
                                                <label>Nama Balita <span class="text-danger">*</span>:</label>
                                                <input type="text" name="nama_balita" class="form-control" readonly value="<?= htmlspecialchars($row['nama_balita'] ?? '') ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Jenis Kelamin <span class="text-danger">*</span>:</label>
                                                <select name="jenis_kelamin_balita" class="form-control" readonly>
                                                    <option value="">-- Pilih --</option>
                                                    <option value="<?= htmlspecialchars($row['jenis_kelamin_balita'] ?? '') ?>">Laki-laki</option>
                                                    <option value="Perempuan" <?= htmlspecialchars($row['jenis_kelamin_balita'] ?? '')=='Perempuan'?'selected':'' ?>>Perempuan</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Tanggal Lahir <span class="text-danger">*</span>:</label>
                                                <input type="date" name="tanggal_lahir" class="form-control" readonly value="<?= htmlspecialchars($row['tanggal_lahir'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <!-- Data Pengukuran -->
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tanggal Pengukuran <span class="text-danger">*</span>:</label>
                                                <input type="date" name="tanggal_pengukuran" class="form-control" required value="<?= htmlspecialchars($row['tanggal_pengukuran'] ?? '') ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Berat Badan (kg) <span class="text-danger">*</span>:</label>
                                                <input type="number" step="0.1" name="berat_badan" class="form-control" required value="<?= htmlspecialchars($row['berat'] ?? '') ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Tinggi Badan (cm) <span class="text-danger">*</span>:</label>
                                                <input type="number" step="0.1" name="tinggi_badan" class="form-control" required value="<?= htmlspecialchars($row['tinggi'] ?? '') ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Desa/Kelurahan <span class="text-danger">*</span>:</label>
                                                <select name="kelurahan" id="kelurahan" class="form-control" required>
                                                    <option value="">-- Pilih Kelurahan --</option>
                                                    <?php foreach($kelurahan_list as $kel): ?>
                                                        <option value="<?= $kel['id_kelurahan'] ?>" <?= ($kel['id_kelurahan']==($row['id_kelurahan'] ?? 0))?'selected':'' ?>>
                                                            <?= htmlspecialchars($kel['nama_kelurahan']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Posyandu:</label>
                                                <select name="posyandu" id="posyandu" class="form-control">
                                                    <option value="">-- Pilih Posyandu (boleh kosong) --</option>
                                                    <?php foreach($posyandu_list as $psy): ?>
                                                        <option value="<?= $psy['id_posyandu'] ?>" <?= ($psy['id_posyandu']==($row['id_posyandu'] ?? 0))?'selected':'' ?>>
                                                            <?= htmlspecialchars($psy['nama_posyandu']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Puskesmas:</label>
                                                <input type="hidden" name="id_puskesmas" value="<?= htmlspecialchars($id_puskesmas) ?>">
                                                <input type="text" class="form-control" value="<?= htmlspecialchars($nama_puskesmas) ?>" readonly>
                                            </div>
                                            <!-- <div class="form-group">
                                                <label>Kecamatan:</label>
                                                <input type="text" name="kecamatan" id="kecamatan" class="form-control" readonly>
                                            </div> -->
                                            <div class="form-group">
                                                <label>Kecamatan:</label>
                                                <input type="text" name="kecamatan" id="kecamatan" class="form-control" value="<?= htmlspecialchars($row['nama_kelurahan'] ?? '') ?>" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label>Catatan Perubahan:</label>  
                                                <textarea name="catatan_perubahan" class="form-control" rows="3" placeholder="Tulis catatan perubahan jika ada"><?= htmlspecialchars($row['catatan_supervisor'] ?? '') ?></textarea>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="submit" id="btnSimpan" class="btn btn-success btn-icon-split">
                                            <span class="icon text-white-50"><i class="fas fa-save"></i></span>
                                            <span class="text">Simpan Perubahan</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <?php include 'footer.php'; ?>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#btnSimpan').click(function(e){
        // Mencegah form langsung submit
        e.preventDefault();
        // Tampilkan popup konfirmasi
        var yakin = confirm("Anda yakin ingin menyimpan perubahan?");
        if(yakin){
            // Jika OK, submit form
            $(this).closest('form').submit();
        }
    });
});
</script>
</body>
</html>
