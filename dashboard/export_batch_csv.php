<?php
// === Konfigurasi awal ===
include '../koneksi.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === Cek apakah batch_id dikirim ===
if (!isset($_GET['batch']) || empty($_GET['batch'])) {
    die("❌ Parameter 'batch' tidak ditemukan.");
}

$batch_id = mysqli_real_escape_string($koneksi, $_GET['batch']);

// === Query untuk ambil data berdasarkan batch_id ===
$query = "
    SELECT 
        ot.nik,
        ot.nama AS nama_orang_tua,
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
        u.full_name AS nama_petugas,
        p.level_stunting,
        p.saran,
        p.tanggal_prediksi,
        p.batch_id
    FROM pengukuran_raw p
    JOIN balita b ON p.id_balita = b.id_balita
    JOIN orang_tua ot ON b.nik = ot.nik
    JOIN users u ON p.id_user = u.id_user
    join puskesmas pm on p.id_puskesmas = pm.id_puskesmas
    join kelurahan k on p.id_kelurahan = k.id_kelurahan
    join posyandu py on p.id_posyandu = py.id_posyandu
    WHERE p.batch_id = '$batch_id'
    ORDER BY p.tanggal_prediksi DESC
";

$result = mysqli_query($koneksi, $query);

// === Cek data ===
if (!$result || mysqli_num_rows($result) == 0) {
    die("❌ Tidak ada data untuk batch ID: " . htmlspecialchars($batch_id));
}

// === Header untuk download CSV ===
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="data_batch_' . $batch_id . '.csv"');

// === Tulis data ke output ===
$output = fopen('php://output', 'w');

// === Header kolom CSV ===
fputcsv($output, [
    'NIK', 'Nama Orang Tua', 'Alamat', 'No HP', 'Nama Balita', 
    'Jenis Kelamin', 'Tanggal Lahir', 'Tanggal Pengukuran', 'Umur (Bulan)',
    'Berat Badan', 'Tinggi Badan', 'BB/U', 'TB/U', 'BB/TB',
    'Puskesmas', 'Kecamatan', 'Kelurahan', 'Posyandu',
    'Nama Petugas', 'Level Stunting', 'Saran', 'Tanggal Prediksi', 'Batch ID'
]);

// === Isi data baris demi baris ===
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['nik'],
        $row['nama_orang_tua'],
        $row['alamat'],
        $row['no_hp'],
        $row['nama_balita'],
        $row['jenis_kelamin_balita'],
        $row['tanggal_lahir'],
        $row['tanggal_pengukuran'],
        $row['umur_dalam_bulan'],
        $row['berat'],
        $row['tinggi'],
        $row['bbu'],
        $row['tbu'],
        $row['bbtb'],
        $row['nama_puskesmas'],
        $row['kecamatan'],
        $row['nama_kelurahan'],
        $row['nama_posyandu'],
        $row['nama_petugas'],
        $row['level_stunting'],
        $row['saran'],
        $row['tanggal_prediksi'],
        $row['batch_id']
    ]);
}

// Tutup file output
fclose($output);
exit;
?>
