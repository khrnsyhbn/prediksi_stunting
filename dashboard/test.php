<?php
require_once 'auth.php';
checkLogin();
require_once '../koneksi.php';
// ... (require_once auth.php dan koneksi.php) ...

function norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = preg_replace('/\s+/', ' ', $s);

    // Hapus kata 'posyandu' di awal atau di mana saja (dipertahankan)
    $s = preg_replace('/^posyandu\s+/u', '', $s); 
    $s = preg_replace('/\bposyandu\b/u', '', $s); 

    // PENTING: Jika Anda ingin menghapus SEMUA spasi untuk mencocokkan di database:
    $s = str_replace(' ', '', $s); 
    
    $s = trim($s, " -_");
    return $s;
}

// ASUMSI: $id_puskesmas_user sudah terisi.
$id_puskesmas_user = 1; // Contoh saja

// Query untuk mendapatkan nama Puskesmas user
$stmt = $koneksi->prepare("SELECT nama_pus FROM puskesmas WHERE id_puskesmas = ?");
$stmt->bind_param("s", $id_puskesmas_user);
$stmt->execute();
$res_puskesmas = $stmt->get_result();
$r_puskesmas = $res_puskesmas->fetch_assoc();

// --- PERBAIKAN BUG KRITIS 1: Mengambil nama puskesmas dengan KEY yang benar ---
$nama_kelurahan = $r_puskesmas['nama_kelurahan'] ?? ''; // Gunakan KEY 'nama_kelurahan'

$stmt->close();

// Normalisasi nama Puskesmas (Contoh: jika DB "Teluk Dalam" -> "telukdalam")
$puskesmas_norm = norm($nama_puskesmas_user); 
echo "1. Nama Puskesmas Asli: '$nama_puskesmas_user'<br>";
echo "2. Nama Puskesmas Norm: '$puskesmas_norm'<br>"; // Harus "telukdalam" (tanpa spasi)

// --- INISIALISASI VARIABEL DARI INPUT SIMULASI ---
$kel_norm = norm("Teluk Dalam");
$pos_norm_input = norm("Melati Teluk Dalam"); // Hasil norm: "melatitelukdalam"
$pos_norm = $pos_norm_input; // Default: pakai input asli

echo "3. Nama Posyandu Input Norm: '$pos_norm_input'<br>"; // Harus "melatitelukdalam"

// --- LOGIKA PEMBESIHAN POSYANDU ---
if (!empty($puskesmas_norm)) {
    // Bersihkan nama posyandu dari nama puskesmas yang dinormalisasi
    $pos_norm_cleaned = str_replace($puskesmas_norm, '', $pos_norm_input);
    
    echo "4. DEBUG: Pembersihan Posyandu: Input='$pos_norm_input', Puskesmas='$puskesmas_norm', Cleaned='$pos_norm_cleaned'<br>";
    
    // Gunakan hasil pembersihan jika valid dan lebih pendek (sudah memotong)
    if (trim($pos_norm_cleaned) !== '' && strlen($pos_norm_cleaned) < strlen($pos_norm_input)) {
        $pos_norm = trim($pos_norm_cleaned); // $pos_norm sekarang: "melati"
        echo "5. HASIL AKHIR POSYANDU BERSIH: '$pos_norm'<br>";
    }
} else {
    echo "DEBUG: Nama Puskesmas Norm kosong, pembersihan diabaikan.<br>";
}

// ... Selanjutnya Anda bisa melanjutkan dengan LOGIKA VALIDASI C, D, E ...
?>