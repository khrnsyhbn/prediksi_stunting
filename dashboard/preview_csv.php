<?php
require_once 'auth.php';
checkLogin();
require_once '../koneksi.php';

// Helper: escape
function e($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

// Normalisasi string untuk pencocokan (lower, trim, remove 'posyandu' kata)
function norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = preg_replace('/\s+/', ' ', $s);

    // Hapus kata 'posyandu' di awal atau di mana saja
    $s = preg_replace('/^posyandu\s+/u', '', $s);   // posyandu di awal
    $s = preg_replace('/\bposyandu\b/u', '', $s);   // fallback posyandu di tengah

    $s = trim($s, " -_");
    return $s;
}

// Konversi tanggal (kembalikan yyyy-mm-dd atau empty string)
function konversiTanggal($tgl) {
    $tgl = trim((string)$tgl);
    if ($tgl === '') return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl)) return $tgl;

    // Tambahkan '!' untuk mode STRICT (mematikan rollover/overflow)
    // dan prioritaskan m/d/Y di depan d/m/Y jika pemisah sama.
    $formats = [
        '!m/d/Y', // Prioritas untuk 08/19/2024 (MM/DD/YYYY)
        '!d/m/Y', // Prioritas untuk 19/08/2024 (DD/MM/YYYY)
        '!Y-m-d', 
        '!d-m-Y',
        '!d.m.Y'
    ];
    
    foreach ($formats as $f) {
        // Ambil format tanpa '!' untuk perbandingan format()
        $format_bersih = ltrim($f, '!'); 
        
        $d = DateTime::createFromFormat($f, $tgl);
        
        // Kondisi ketat: $d harus valid DAN format output harus sama persis dengan input $tgl
        if ($d && $d->format($format_bersih) === $tgl) { 
            return $d->format('Y-m-d');
        }
    }
    
    // Sebagai fallback, coba date_create() tanpa mode strict (seperti fungsi Anda)
    $d = date_create($tgl);
    if ($d) return $d->format('Y-m-d');
    
    return '';
}


// HTML cell helper dengan tooltip
function td($val, $valid, $note = '') {
    $class = $valid ? '' : 'bg-danger text-white';
    $tooltip = $note ? ' title="' . e($note) . '" data-bs-toggle="tooltip" data-bs-placement="top"' : '';
    return "<td class='$class'{$tooltip}>" . e($val) . "</td>";
}

// Fungsi untuk mendapatkan ID Posyandu berdasarkan nama yang sudah dibersihkan dan ID Kelurahan
function getPosyanduId(mysqli $koneksi, $posyandu_norm_clean, $id_kelurahan) {
    $sql = "SELECT id_posyandu FROM posyandu WHERE nama_posyandu = ? AND id_kelurahan = ?";
    $stmt = $koneksi->prepare($sql);

    if ($stmt === false) {
        error_log("Error preparing statement: " . $koneksi->error);
        return null;
    }

    // Mengikat parameter menggunakan nama variabel input yang sudah bersih
    $stmt->bind_param("si", $posyandu_norm_clean, $id_kelurahan); 

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    if ($row) {
        return (int)$row['id_posyandu'];
    } else {
        return null;
    }
}

// cek POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['preview'])) {
    echo "<div style='padding:20px;font-family:Arial;'>Akses tidak valid. Kembali ke <a href='form_upload_csv.php'>form upload</a>.</div>";
    exit;
}

if (!isset($_FILES['file_csv']) || !is_uploaded_file($_FILES['file_csv']['tmp_name'])) {
    echo "<div style='padding:20px;font-family:Arial;color:#a94442;background:#f2dede;border:1px solid #ebccd1;'>File CSV tidak ditemukan. Kembali ke <a href='form_upload_csv.php'>form upload</a>.</div>";
    exit;
}

$file_tmp = $_FILES['file_csv']['tmp_name'];

// load referensi
$kelurahan_list = [];
$q = $koneksi->query("SELECT id_kelurahan, nama_kelurahan FROM kelurahan");
while ($r = $q->fetch_assoc()) {
    $kelurahan_list[norm($r['nama_kelurahan'])] = (int)$r['id_kelurahan'];
}

$posyandu_list = [];
// $q2 = $koneksi->query("SELECT id_posyandu, nama_posyandu, id_kelurahan FROM posyandu");
// while ($r = $q2->fetch_assoc()) {
//     $posyandu_list[norm($r['nama_posyandu'])] = [
//         'id_posyandu' => (int)$r['id_posyandu'],
//         'id_kelurahan' => (int)$r['id_kelurahan'],
//         'raw_name' => $r['nama_posyandu']
//     ];
// }

// buka CSV
$handle = fopen($file_tmp, "r");
if ($handle === false) {
    echo "<div style='padding:20px;font-family:Arial;color:#a94442;background:#f2dede;border:1px solid #ebccd1;'>Gagal membuka file CSV.</div>";
    exit;
}

// baca header & mapping
$header = fgetcsv($handle, 0, ",");
$map = [];
if ($header !== false) {
    foreach ($header as $i => $h) {
        $lower = mb_strtolower(trim($h), 'UTF-8');
        $lower = preg_replace('/\s+/', '_', $lower);
        if (in_array($lower, ['nik','no_ktp','no_kk'])) $map['nik'] = $i;
        if (in_array($lower, ['nama_orangtua','nama','nama_ortu','nama_orang_tua'])) $map['nama_orangtua'] = $i;
        if (in_array($lower, ['alamat','alamat_orangtua'])) $map['alamat'] = $i;
        if (in_array($lower, ['no_hp','no_telp','telepon','hp'])) $map['no_hp'] = $i;
        if (in_array($lower, ['nama_balita','nama_anak','nama_bayi'])) $map['nama_balita'] = $i;
        if (in_array($lower, ['jenis_kelamin','jenis_kelamin_balita','jk','jkel'])) $map['jenis_kelamin'] = $i;
        if (in_array($lower, ['tanggal_lahir','tgl_lahir','lahir'])) $map['tanggal_lahir'] = $i;
        if (in_array($lower, ['tanggal_pengukuran','tgl_pengukuran','tgl_ukur','tanggal_ukur'])) $map['tanggal_pengukuran'] = $i;
        if (in_array($lower, ['berat_badan','berat','bb','berat_badan_kg'])) $map['berat_badan'] = $i;
        if (in_array($lower, ['tinggi_badan','tinggi','tb','tinggi_cm'])) $map['tinggi_badan'] = $i;
        if (in_array($lower, ['nama_kelurahan','kelurahan'])) $map['nama_kelurahan'] = $i;
        if (in_array($lower, ['nama_posyandu','posyandu','nama_pos'])) $map['nama_posyandu'] = $i;
    }
}

// fallback mapping
$defaults = [
    'nik'=>0,'nama_orangtua'=>1,'alamat'=>2,'no_hp'=>3,
    'nama_balita'=>4,'jenis_kelamin'=>5,'tanggal_lahir'=>6,'tanggal_pengukuran'=>7,
    'berat_badan'=>8,'tinggi_badan'=>9,'nama_kelurahan'=>10,'nama_posyandu'=>11
];
foreach ($defaults as $k=>$idx) {
    if (!isset($map[$k])) $map[$k] = $idx;
}

// ======= HTML =======
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Preview CSV</title>
<!-- CDN Bootstrap & FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="../assets/css/sb-admin-2.css" rel="stylesheet">
<style>
.table td { vertical-align: middle; white-space: nowrap; }
.small { font-size:0.85rem; }
.bg-danger { background:#d9534f !important; }
.text-white { color:#fff !important; }
</style>
</head>
<body id="page-top">
<div id="wrapper">
<?php include 'sidebar.php'; ?>
<div id="content-wrapper" class="d-flex flex-column">
  <div id="content">
    <?php include 'header.php'; ?>
    <div class="container-fluid">
      <h1 class="h3 mb-4 text-gray-800">Preview Data CSV</h1>
      <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-table"></i> Hasil Preview CSV</h6>
          <a href="form_upload_csv.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>
        <div class="card-body">

<?php
$data_valid = [];
$error_found = false;
$valid_count = 0;
$row_no = 1;

echo "<div class='table-responsive'>";
echo "<table class='table table-bordered table-striped small'>";
echo "<thead class='thead-light'>
<tr>
<th>No</th>
<th>NIK</th>
<th>Nama Orang Tua</th>
<th>Alamat</th>
<th>No HP</th>
<th>Nama Balita</th>
<th>Jenis Kelamin</th>
<th>Tgl Lahir</th>
<th>Tgl Pengukuran</th>
<th>BB (kg)</th>
<th>TB (cm)</th>
<th>Kelurahan</th>
<th>Posyandu</th>
</tr></thead><tbody>";

// Ambil id_puskesmas pengguna dari session (pastikan tersimpan saat login)
$id_puskesmas_user = $_SESSION['id_puskesmas'] ?? '';
$nama_puskesmas_user = '';

if ($id_puskesmas_user !== '') {
    $stmt = $koneksi->prepare("SELECT nama_puskesmas FROM puskesmas WHERE id_puskesmas = ? LIMIT 1");
    $stmt->bind_param("s", $id_puskesmas_user); // 's' = string
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $nama_puskesmas_user = $row['nama_puskesmas'];
    } else {
        $nama_puskesmas_user = 'Tidak ditemukan';
    }
    $stmt->close();
}



// load kelurahan hanya yang sesuai dengan id_puskesmas pengguna
$kelurahan_list = [];
$q = $koneksi->prepare("SELECT id_kelurahan, nama_kelurahan 
                    FROM kelurahan
                    WHERE id_puskesmas = ?");

$q->bind_param('s', $id_puskesmas_user);
$q->execute();
$res = $q->get_result();
while ($r = $res->fetch_assoc()) {
    $kelurahan_list[norm($r['nama_kelurahan'])] = [
        'id_kelurahan' => (int)$r['id_kelurahan']
    ];
}
$q->close();
// baca CSV
while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {;
    $get = function($k) use ($map,$data){ $idx=$map[$k]??null; return $idx!==null && isset($data[$idx])?trim($data[$idx]):''; };

    $nik=$get('nik');
    $nama_ot=$get('nama_orangtua');
    $alamat=$get('alamat');
    $no_hp=$get('no_hp');
    $nama_balita=$get('nama_balita');
    $jk_raw=$get('jenis_kelamin');
    $tgl_lahir_raw=$get('tanggal_lahir');
    $tgl_ukur_raw=$get('tanggal_pengukuran');
    $berat=$get('berat_badan');
    $tinggi=$get('tinggi_badan');
    $nama_kelurahan=$get('nama_kelurahan');
    $nama_posyandu=$get('nama_posyandu');

    echo "<tr>";
    $row_valid = true;
    $notes = [];

    // NIK
    $nik_digits = preg_replace('/\D/','',$nik);
    $nik_valid = ($nik!=='' && ctype_digit($nik_digits) && strlen($nik_digits)==16);
    if(!$nik_valid){ $row_valid=false; $notes[]="NIK harus 16 digit angka."; }

    // Nama orang tua
    $nama_ot_valid = ($nama_ot!=='');
    if(!$nama_ot_valid){ $row_valid=false; $notes[]="Nama orang tua kosong."; }

    //Alamat
    $alamat_valid = ($alamat!=='');
    if(!$alamat_valid){ $row_valid=false; $notes[]="Alamat kosong."; }
    

    // Nama balita
    $nama_balita_valid = ($nama_balita!=='');
    if(!$nama_balita_valid){ $row_valid=false; $notes[]="Nama balita kosong."; }

    // JK
    $jk_norm = mb_strtoupper(trim($jk_raw));
    if(in_array($jk_norm,['L','LAKI','LAKI-LAKI','LAKI LAKI'])) $jk_label='Laki-laki';
    elseif(in_array($jk_norm,['P','PER','PEREMPUAN'])) $jk_label='Perempuan';
    else $jk_label='';
    $jk_valid = ($jk_label!=='');
    if(!$jk_valid){ $row_valid=false; $notes[]="Jenis kelamin tidak valid."; }

    // Tanggal
    $tgl_lahir_fix = konversiTanggal($tgl_lahir_raw);
    $tgl_ukur_fix = konversiTanggal($tgl_ukur_raw);
    $tgl_lahir_valid = ($tgl_lahir_fix!=='');
    $tgl_ukur_valid = ($tgl_ukur_fix!=='');
    if(!$tgl_lahir_valid){ $row_valid=false; $notes[]="Tanggal lahir tidak valid."; }
    if(!$tgl_ukur_valid){ $row_valid=false; $notes[]="Tanggal pengukuran tidak valid."; }

    if ($tgl_lahir_valid && $tgl_ukur_valid) {

        $date_lahir = strtotime($tgl_lahir_fix);
        $date_ukur = strtotime($tgl_ukur_fix);
        $today = strtotime(date('Y-m-d'));

        // 1. Pengukuran di masa depan
        if ($date_ukur > $today) {
            $row_valid = false;
            $notes[] = "Tanggal pengukuran tidak boleh di masa depan.";
            $tgl_ukur_valid = false;
        }

        // 2. Tanggal lahir > tanggal pengukuran
        if ($date_lahir > $date_ukur) {
            $row_valid = false;
            $notes[] = "Tanggal lahir tidak boleh lebih besar dari tanggal pengukuran.";
            $tgl_lahir_valid = false;
            $tgl_ukur_valid = false;
        }

        // 3. Pengukuran sebelum anak lahir
        if ($date_ukur < $date_lahir) {
            $row_valid = false;
            $notes[] = "Tanggal pengukuran tidak boleh sebelum anak lahir.";
            $tgl_ukur_valid = false;
        }
    }

   // Konversi string ke float
    $berat_num = floatval(str_replace(',', '.', $berat));
    $tinggi_num = floatval(str_replace(',', '.', $tinggi));

    // Validasi berat badan
    if (is_numeric($berat_num) && $berat_num > 0 && $berat_num < 50) {
        $berat_valid = true;
    } else {
        $berat_valid = false;
        $row_valid = false;
        $notes[] = "Berat badan tidak valid (0-50 kg).";
    }

    // Validasi tinggi badan
    if (is_numeric($tinggi_num) && $tinggi_num > 40 && $tinggi_num < 200) {
        $tinggi_valid = true;
    } else {
        $tinggi_valid = false;
        $row_valid = false;
        $notes[] = "Tinggi badan tidak valid (40-200 cm).";
    }



    // ======= VALIDASI KELURAHAN & POSYANDU =======
    // Inisialisasi status validasi
    $kel_norm = norm($nama_kelurahan);
    $pos_norm = norm($nama_posyandu);

    $provided_kel = null;
    $kel_valid = true;
    $pos_valid = true;
    $notes_k = [];
    $pos_entry = null;
    $pos_found = false;

        // 1. Cek di daftar kelurahan yang di-load user (wilayah Puskesmas user)
        if (isset($kelurahan_list[$kel_norm])) {
            // Kelurahan valid dan masuk wilayah Puskesmas
            $provided_kel = (int)$kelurahan_list[$kel_norm]['id_kelurahan'];
        } else {
            // 2. Fallback: Cari di seluruh database kelurahan (untuk toleransi typo/nama baru)
            $stmt = $koneksi->prepare("SELECT id_kelurahan FROM kelurahan WHERE LOWER(REPLACE(nama_kelurahan,' ','')) = ?");
            $tmp = str_replace(' ', '', $kel_norm);
            $stmt->bind_param("s", $tmp);
            $stmt->execute();
            $res2 = $stmt->get_result();
            $r2 = $res2->fetch_assoc();
            $provided_kel = $r2['id_kelurahan'] ?? null;
            $stmt->close();

            // 3. Tentukan Status Kelurahan
            if (!is_null($provided_kel)) {
                // Kelurahan Ditemukan, tapi TIDAK di wilayah Puskesmas user
                $kel_valid = false;
                $kel_note = "Kelurahan ($nama_kelurahan) ada di sistem, tetapi tidak terdaftar di wilayah Puskesmas Anda (" . $nama_puskesmas_user . ").";
                $notes_k[] = $kel_note;
            } else {
                // Kelurahan sama sekali TIDAK ditemukan di database
                $kel_valid = false;
                $notes_k[] = "Kelurahan ($nama_kelurahan) tidak ditemukan di sistem.";
            }
        }


    // --- D. VALIDASI POSYANDU (Mencari Posyandu yang cocok) ---

    // // Logika Pencarian Posyandu: Prioritaskan Pencocokan Ketat (Exact Match)
    // $matches_exact = [];
    // $matches_partial = [];

    // // $pos_norm sudah bersih (insan)
    // foreach ($posyandu_list as $kname => $entry) {
    //     // 1. Prioritas: Pencocokan yang persis sama (Lebih akurat)
    //     if ($kname === $pos_norm) {
    //         $matches_exact[] = $entry;
    //     }
    //     // 2. Fallback: Pencocokan parsial (Hanya jika tidak ada Exact Match)
    //     elseif (empty($matches_exact) && (strpos($kname, $pos_norm) !== false || strpos($pos_norm, $kname) !== false)) {
    //         $matches_partial[] = $entry;
    //     }
    // }
    // $matches = !empty($matches_exact) ? $matches_exact : $matches_partial;


    // --- E. LOGIKA PENCARIAN & PENENTUAN MATCH ---
    $pos_norm_cleaned = str_replace($kel_norm, '', $pos_norm);

    $posyandu_id = getPosyanduId($koneksi, $pos_norm_cleaned, $provided_kel);

    // echo "<h2>Hasil Pencarian ID Posyandu</h2>";
    // echo "Mencari Posyandu: '{$pos_norm_cleaned}' (ID Kelurahan: {$provided_kel})<br>";
    // echo "Mencari Posyandu: SELECT '{$pos_norm_cleaned}' (ID Kelurahan: {$provided_kel})<br>";

    if (empty($nama_posyandu)) {
        // KASUS 1: NAMA POSYANDU DI CSV KOSONG.
        // Sesuai permintaan: 'jika kosong pos valid = true'.
        // Tidak perlu melakukan pencarian DB.
        $pos_entry = null;
        $pos_found = false;
        $notes_k[] = "Posyandu dikosongkan (diabaikan dari validasi).";
        
    } else {
        // KASUS 2 & 3: NAMA POSYANDU DI CSV ADA. Lakukan pencarian ID.
        
        // ASUMSI: Fungsi getPosyanduId sudah dipanggil sebelumnya dan hasilnya ada di $posyandu_id
        // $posyandu_id = getPosyanduId($koneksi, $pos_norm, $provided_kel);
        
        if ($posyandu_id !== null) {
            // KASUS 2: DITEMUKAN. Sesuai permintaan: 'tidak masalah jika ditemuka pos_valid = true'.
            $pos_found = true;
            // $pos_entry diisi di sini jika Anda menggunakannya
            // $pos_entry = ['id_posyandu' => $posyandu_id]; 
            
        } else {
            // KASUS 3: TIDAK DITEMUKAN. Sesuai permintaan: 'jika tidak ditemukan (kosong) baru pos_valid = false'.
            $pos_found = false;
            $pos_valid = false;
            $row_valid = false; // Baris data tidak valid
            $notes_k[] = "Posyandu [$nama_posyandu] (Norm: $pos_norm) tidak ditemukan di Kelurahan [$nama_kelurahan].";
        }
    }

    // Lanjutkan dengan penggabungan notes dan tampilan HTML...


    // --- F. FINALISASI STATUS VALIDASI BARIS ---

    // Gabungkan notes
    if(!empty($notes_k)){
        // Asumsi: $notes adalah array note utama baris
        $notes = array_merge($notes, $notes_k);
    }

    // Set status validasi baris utama
    if (!$kel_valid || !$pos_valid) {
        $row_valid = false;
    }

    
    if(!$row_valid) $error_found=true;
    $note_all = implode(" | ",$notes);

    echo "<td>" . e($row_no) . "</td>";
    echo td($nik,$nik_valid,$note_all);
    echo td($nama_ot,$nama_ot_valid,$note_all);
    echo td($alamat,$alamat!=='',$note_all);
    // echo td($no_hp,$no_hp_valid,$note_all);
    echo td($no_hp,true,$note_all);
    echo td($nama_balita,$nama_balita_valid,$note_all);
    echo td($jk_label,$jk_valid,$note_all);
    echo td($tgl_lahir_fix?:$tgl_lahir_raw,$tgl_lahir_valid,$note_all);
    echo td($tgl_ukur_fix?:$tgl_ukur_raw,$tgl_ukur_valid,$note_all);
    echo td($berat,$berat_valid,$note_all);
    echo td($tinggi,$tinggi_valid,$note_all);
    echo td($nama_kelurahan, $kel_valid, $note_all);
    echo td($nama_posyandu, $pos_valid, implode(" | ",$notes));



    echo "</tr>";

    if($row_valid){
        $data_valid[] = [
            'nik'=>preg_replace('/\D/','',$nik),
            'nama_orangtua'=>$nama_ot,
            'alamat'=>$alamat,
            'no_hp'=>$no_hp,
            'nama_balita'=>$nama_balita,
            'jenis_kelamin'=>$jk_label,
            'tanggal_lahir'=>$tgl_lahir_fix,
            'tanggal_pengukuran'=>$tgl_ukur_fix,
            'berat_badan'=>(float)str_replace(',','.',$berat),
            'tinggi_badan'=>(float)str_replace(',','.',$tinggi),
            'id_kelurahan'=>$provided_kel,
            'id_posyandu'=>$posyandu_id,
            'id_puskesmas'=>$id_puskesmas_user,
            'nama_puskesmas'=>$nama_puskesmas_user
        ];
        $valid_count++;
    }

    $row_no++;
}

echo "</tbody></table></div>";
fclose($handle);
$_SESSION['data_csv_valid']=$data_valid;
?>

<?php if($error_found): ?>
<div class="alert alert-warning mt-3">
    ⚠️ Terdapat baris yang tidak valid (sel berwarna merah). Hover untuk melihat catatan error.
</div>
<?php endif; ?>

<?php if($valid_count>0): ?>
<div class="alert alert-success mt-3">
    ✅ <strong><?php echo $valid_count;?></strong> baris valid dan siap disimpan.
</div>
<form id="formSimpan" action="upload_progres.html" method="GET">
<button 
    type="button" 
    data-bs-toggle="modal" data-bs-target="#konfirmasiSimpanModal"
    class="btn btn-success btn-icon-split btn-sm"
    <?php echo $error_found ? 'disabled data-bs-toggle="tooltip" title="Terdapat baris merah / error, perbaiki dulu sebelum menyimpan."' : ''; ?>
>
    <span class="icon text-white-50"><i class="fas fa-database"></i></span>
    <span class="text" >Simpan ke Database</span>
</button>
</form>
<?php else: ?>
<div class="alert alert-secondary mt-3">
    Tidak ada baris valid untuk disimpan.
</div>
<?php endif; ?>

    </div>
  </div>
</div>
<?php include 'footer.php'; ?>
</div>
</div>
<?php include 'logout_alert.php'; ?>

<!-- Bootstrap JS & jQuery CDN -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<div class="modal fade" id="konfirmasiSimpanModal" tabindex="-1" aria-labelledby="konfirmasiSimpanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="konfirmasiSimpanModalLabel">Konfirmasi Penyimpanan Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menyimpan data ini ke database? Aksi ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-success" id="btnKonfirmasiSimpan">Ya, Simpan Sekarang</button>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    // aktifkan semua tooltip
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) })
});
// Ambil elemen form dan tombol konfirmasi
const formSimpan = document.getElementById('formSimpan');
const btnKonfirmasiSimpan = document.getElementById('btnKonfirmasiSimpan');

// Dengarkan klik pada tombol Konfirmasi di dalam Modal
btnKonfirmasiSimpan.addEventListener('click', function() {
    
    // 1. Sembunyikan Modal (jika perlu)
    const modalElement = document.getElementById('konfirmasiSimpanModal');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (modalInstance) {
        modalInstance.hide();
    } else {
        // Jika modalInstance tidak ditemukan, buat instance baru dan sembunyikan
        const newModalInstance = new bootstrap.Modal(modalElement);
        newModalInstance.hide();
    }

    // 2. Jalankan Submit Form
    // Setelah modal disembunyikan, panggil fungsi submit() pada form
    formSimpan.submit();
});
</script>
</body>
</html>