<?php
require_once 'auth.php';
include '../koneksi.php';
include 'fungsi_umur.php';
include 'fungsi_bb_u.php';
include 'fungsi_tb_u.php';
include 'fungsi_bb_tb.php';
include 'fungsi_saran.php';
checkLogin();

// Ambil semua data input
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
$id_posyandu = trim($_POST['posyandu'] ?? null); // Mengizinkan null jika kosong
$id_user = $_SESSION['id_user'];
$batch_id = date('YmdHis');

$errors = []; // Array untuk menampung pesan error

// --- 1. Validasi Data Orang Tua ---
// NIK (Wajib, 16 digit angka)
if (empty($nik)) {
    $errors[] = "NIK wajib diisi.";
} elseif (!preg_match('/^[0-9]{16}$/', $nik)) {
    $errors[] = "NIK tidak valid, NIK harus terdiri dari 16 digit angka.";
}

// Nama Orang Tua (Wajib, hanya huruf dan spasi)
if (empty($nama)) {
    $errors[] = "Nama Orang Tua wajib diisi.";
} elseif (!preg_match('/^[a-zA-Z\s]+$/', $nama)) {
    $errors[] = "Nama Orang Tua hanya boleh mengandung huruf dan spasi.";
}

// Alamat (Wajib)
if (empty($alamat)) {
    $errors[] = "Alamat wajib diisi.";
}

// Nomor Telepon (Opsional, hanya angka, max 15 digit)
if (!empty($no_hp) && !preg_match('/^[0-9]{8,15}$/', $no_hp)) {
    $errors[] = "Nomor Telepon tidak valid, hanya boleh mengandung angka.";
}

// --- 2. Validasi Data Balita ---
// Nama Balita (Wajib)
if (empty($nama_balita)) {
    $errors[] = "Nama Balita wajib diisi.";
}

// Jenis Kelamin (Wajib, harus salah satu nilai yang diizinkan)
$allowed_jk = ['Laki-laki', 'Perempuan'];
if (empty($jenis_kelamin_balita) || !in_array($jenis_kelamin_balita, $allowed_jk)) {
    $errors[] = "Jenis Kelamin Balita wajib diisi.";
}

// Tanggal Lahir (Wajib, format tanggal, tidak boleh di masa depan)
if (empty($tanggal_lahir)) {
    $errors[] = "Tanggal Lahir wajib diisi.";
} elseif (strtotime($tanggal_lahir) > time()) {
    $errors[] = "Tanggal Lahir tidak boleh di masa depan.";
} 

// --- 3. Validasi Data Pengukuran ---
// Tanggal Pengukuran (Wajib, format tanggal, tidak boleh di masa depan)
if (empty($tanggal_pengukuran)) {
    $errors[] = "Tanggal Pengukuran wajib diisi.";
} elseif (strtotime($tanggal_pengukuran) > time()) {
    $errors[] = "Tanggal Pengukuran tidak boleh di masa depan.";
} elseif (strtotime($tanggal_pengukuran) < strtotime($tanggal_lahir)) {
    $errors[] = "Tanggal Pengukuran tidak boleh sebelum Tanggal Lahir.";
}

// Berat Badan (Wajib, angka positif)
if (empty($berat_badan)) {
    $errors[] = "Berat Badan wajib diisi.";
} elseif (!is_numeric($berat_badan) || $berat_badan <= 0 || $berat_badan > 30) { // Batasan logis (misalnya maks 30 kg)
    $errors[] = "Berat Badan tidak valid (harus angka positif) dan tidak boleh lebih dari 30 kg.";
}

// Tinggi Badan (Wajib, angka positif)
if (empty($tinggi_badan)) {
    $errors[] = "Tinggi Badan wajib diisi.";
} elseif (!is_numeric($tinggi_badan) || $tinggi_badan <= 0 || $tinggi_badan > 120) { // Batasan logis (misalnya maks 150 cm)
    $errors[] = "Tinggi Badan tidak valid (harus angka positif) dan tidak boleh lebih dari 120 cm.";
}

// --- 4. Validasi Data Lokasi ---
// Kelurahan (Wajib)
if (empty($id_kelurahan)) {
    $errors[] = "Kelurahan wajib dipilih.";
}

// Puskesmas (Wajib, seharusnya sudah terisi otomatis dari session)
if (empty($id_puskesmas)) {
    $errors[] = "ID Puskesmas hilang (Sesi error).";
}


// --- Penanganan Error ---
if (count($errors) > 0) {
    // Gabungkan semua pesan error menjadi satu string atau tampilan
    $error_message = "Validasi Gagal:\\n" . implode("\\n", $errors);
    
    // Redirect kembali dengan pesan error
    echo "<script>alert('$error_message'); window.history.back();</script>";
    exit;
}

// Jika lolos semua validasi, lanjutkan ke proses penyimpanan database

// --- 1️⃣ Cek data orang tua ---
$query_cek_ortu = "SELECT nik, nama FROM orang_tua WHERE nik = '$nik' LIMIT 1";
$result_ortu = mysqli_query($koneksi, $query_cek_ortu);
if (mysqli_num_rows($result_ortu) > 0) {
    $row_ortu = mysqli_fetch_assoc($result_ortu);
    if (strcasecmp($row_ortu['nama'], $nama) !== 0) {
        echo "<script>alert('⚠️ NIK $nik telah digunakan dengan nama yang berbeda!'); window.history.back();</script>";
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


$response_fuzzy = json_decode($result_api, true);
$level_stunting = $response_fuzzy['level_stunting'] ?? 'Tidak ada data';
$prediksi = $response_fuzzy['prediksi'] ?? '-';
$saran_gizi = generateSaranGizi($response_fuzzy['membership_input'] ?? []);


// --- 6️⃣ Siapkan nilai untuk INSERT pengukuran --
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
