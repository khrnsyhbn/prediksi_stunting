<?php
require_once 'auth.php';
include '../koneksi.php';
include 'fungsi_umur.php';
include 'fungsi_bb_u.php';
include 'fungsi_tb_u.php';
include 'fungsi_bb_tb.php';
include 'fungsi_saran.php';
// include 'ajax/get_posyandu_kecamatan.php';
checkLogin();

$nik = trim($_POST['nik']);
$nama = trim($_POST['nama']);
$alamat = trim($_POST['alamat']);
$no_hp = trim($_POST['no_telepon']);
$nama_balita = trim($_POST['nama_balita']);
$jenis_kelamin_balita = trim($_POST['jenis_kelamin_balita']);
$tanggal_lahir = trim($_POST['tanggal_lahir']);
$tanggal_pengukuran = trim($_POST['tanggal_pengukuran']);
$berat_badan = trim($_POST['berat_badan']);
$tinggi_badan = trim($_POST['tinggi_badan']);
$id_puskesmas = trim($_POST['id_puskesmas']);
$id_kelurahan = trim($_POST['kelurahan']);
$id_posyandu = trim($_POST['posyandu'] ?? null);
$id_user = $_SESSION['id_user'];
$batch_id = date('YmdHis');

// --- Validasi awal ---
if (empty($nik) || empty($nama)) {
    echo "<script>alert('NIK dan Nama wajib diisi!'); window.history.back();</script>";
    exit;
}

// --- 1️⃣ Cek data orang tua ---
$query_cek_ortu = "SELECT nik, nama FROM orang_tua WHERE nik = '$nik' LIMIT 1";
$result_ortu = mysqli_query($koneksi, $query_cek_ortu);
if (mysqli_num_rows($result_ortu) > 0) {
    $row_ortu = mysqli_fetch_assoc($result_ortu);
    if (strcasecmp($row_ortu['nama'], $nama) !== 0) {
        echo "<script>alert('⚠️ NIK $nik sudah digunakan oleh nama berbeda!'); window.history.back();</script>";
        exit;
    }
    $id_orang_tua = $row_ortu['nik'];
} else {
    $query_insert_ortu = "INSERT INTO orang_tua (nik, nama, alamat, no_hp)
                          VALUES ('$nik', '$nama', '$alamat', '$no_hp')";
    mysqli_query($koneksi, $query_insert_ortu);
    $id_orang_tua = mysqli_insert_id($koneksi);
}

// --- 2️⃣ Cek data balita ---
$query_cek_balita = "SELECT id_balita FROM balita WHERE nama_balita = '$nama_balita' AND nik = '$nik' LIMIT 1";
$result_balita = mysqli_query($koneksi, $query_cek_balita);
if (mysqli_num_rows($result_balita) > 0) {
    $row_balita = mysqli_fetch_assoc($result_balita);
    $id_balita = $row_balita['id_balita'];
} else {
    $query_insert_balita = "INSERT INTO balita (nik, nama, nama_balita, tanggal_lahir, jenis_kelamin_balita)
                            VALUES ('$nik', '$nama', '$nama_balita', '$tanggal_lahir', '$jenis_kelamin_balita')";
    mysqli_query($koneksi, $query_insert_balita);
    $id_balita = mysqli_insert_id($koneksi);
}

// --- 3️⃣ Cek duplikasi pengukuran ---
$cek_duplikat = "
    SELECT COUNT(*) AS jumlah
    FROM pengukuran_raw p
    JOIN balita b ON p.id_balita = b.id_balita
    WHERE b.nama_balita = '$nama_balita'
      AND b.nik = '$nik'
      AND p.tanggal_pengukuran = '$tanggal_pengukuran'
      AND p.status_qc = 'pending'
";
$res_cek = mysqli_query($koneksi, $cek_duplikat);
$data_cek = mysqli_fetch_assoc($res_cek);

if ($data_cek['jumlah'] > 0) {
    echo "<script>alert('⚠️ Data pengukuran balita sudah ada dengan tanggal yang sama!'); window.history.back();</script>";
    exit;
}

// --- 4️⃣ Hitung umur & Z-Score ---
$umur_dalam_bulan = hitungUmurBulan($tanggal_lahir, $tanggal_pengukuran);
$BB_U = hitung_zscore_bbu($berat_badan, $jenis_kelamin_balita, $umur_dalam_bulan);
$TB_U = hitung_zscore_tbu($tinggi_badan, $jenis_kelamin_balita, $umur_dalam_bulan); 
$BB_TB = hitung_zscore_bbTb($berat_badan, $jenis_kelamin_balita, $tinggi_badan, $umur_dalam_bulan);

// --- 5️⃣ Kirim ke API ---
// kode untuk menjalankan api fuzzy di terminal "python -m uvicorn fuzzy_api:app --reload --host 127.0.0.1 --port 8000"
$api_url = "http://127.0.0.1:8000/stunting";
$data_fuzzy = json_encode([
    'bbu' => $BB_U,
    'tbu' => $TB_U,
    'bbtb' => $BB_TB
]);

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_fuzzy);
$result_api = curl_exec($ch);
curl_close($ch);

$response_fuzzy = json_decode($result_api, true);
$level_stunting = $response_fuzzy['level_stunting'] ?? 'Tidak ada data';
$prediksi = $response_fuzzy['prediksi'] ?? '-';
$saran_gizi = generateSaranGizi($response_fuzzy['membership_input'] ?? []);


// --- 6️⃣ Siapkan nilai untuk INSERT pengukuran ---
$id_posyandu = ($id_posyandu === '' ? null : $id_posyandu); // jika kosong, jadikan NULL

// --- 7️⃣ Insert ke pengukuran_raw ---
$query_pengukuran = "
    INSERT INTO pengukuran_raw 
(id_balita, id_user, tanggal_pengukuran, umur_dalam_bulan, berat, tinggi, bbu, tbu, bbtb, 
 id_puskesmas, id_kelurahan, id_posyandu, batch_id, level_stunting, skor_prediksi, saran, tanggal_prediksi)
VALUES 
('$id_balita', '$id_user', '$tanggal_pengukuran', '$umur_dalam_bulan', '$berat_badan', '$tinggi_badan',
 '$BB_U', '$TB_U', '$BB_TB', '$id_puskesmas', '$id_kelurahan', 
 ".($id_posyandu !== null ? "'$id_posyandu'" : "NULL").", 
 '$batch_id', '$level_stunting', '$prediksi', '$saran_gizi', NOW())

";
mysqli_query($koneksi, $query_pengukuran);

// --- 8️⃣ Simpan data ke session ---
$_SESSION['hasil_fuzzy'] = [    
    'level_stunting' => $level_stunting,
    'prediksi' => $prediksi,
    'saran_gizi' => $saran_gizi
];
$_SESSION['nik'] = $nik;
$_SESSION['nama_orangtua'] = $nama;
$_SESSION['nama_balita'] = $nama_balita;
$_SESSION['berat_badan'] = $berat_badan;
$_SESSION['tinggi_badan'] = $tinggi_badan;

// --- 9️⃣ Redirect ke hasil ---
header("Location: hasil_stunting.php?batch_id=$batch_id");
exit;
?>
