<?php
// menghubungkan ke database dan fungsi umum
include '../koneksi.php'; 

// === Fungsi untuk membulatkan ke kelipatan 0,5 ===
function bulat_0_5($angka) {
    return round($angka * 2) / 2;
}

// === Fungsi hitung Z-Score BB/TB ===
function hitung_zscore_bbTb($berat_badan, $jenis_kelamin, $tinggi_badan, $umur_bulan) {
    global $koneksi;

    // Tentukan jenis kelamin
    $jk = null;
    $jenis_kelamin = trim(strtolower($jenis_kelamin));
    if ($jenis_kelamin == "perempuan" || $jenis_kelamin == "p") {
        $jk = "P";
    } elseif ($jenis_kelamin == "laki-laki" || $jenis_kelamin == "l") {
        $jk = "L";
    } else {
        return "Jenis kelamin tidak valid";
    }

    // Bulatkan tinggi badan ke 0,5 cm
    $tinggi_bulat = bulat_0_5(floatval($tinggi_badan));

    // Pilih tabel dan range tinggi berdasarkan umur
    if ($umur_bulan >= 0 && $umur_bulan <= 24) {
        $tabel_standar = "standar_bbpb";
        $min_tb = 45.0;
        $max_tb = 110.0;
    } elseif ($umur_bulan > 24 && $umur_bulan <= 60) {
        $tabel_standar = "standar_bbtb";
        $min_tb = 65.0;
        $max_tb = 120.0;
    } else {
        return "Umur bulan di luar rentang 0–60 bulan";
    }

    // Sanitasi tinggi badan
    if ($tinggi_bulat < $min_tb) {
        $tinggi_bulat = $min_tb;
    } elseif ($tinggi_bulat > $max_tb) {
        $tinggi_bulat = $max_tb;
    }

    // Ambil data standar dari tabel
    $sql = "SELECT sd_neg1, median, sd_pos1 
            FROM $tabel_standar 
            WHERE jenis_kelamin = '$jk' 
            AND tngg_bdn = $tinggi_bulat
            LIMIT 1";
    $result = mysqli_query($koneksi, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $sd_neg1 = $row['sd_neg1'];
        $median  = $row['median'];
        $sd_pos1 = $row['sd_pos1'];

        // Tentukan SBi
        $SBi = ($berat_badan > $median) ? ($sd_pos1 - $median) : ($median - $sd_neg1);

        // Hitung Z-score
        $zscore = ($berat_badan - $median) / $SBi;
        return round($zscore, 2);
    } else {
        return "Data standar tidak ditemukan untuk tinggi $tinggi_bulat dan jenis kelamin ($jenis_kelamin)";
    }
}
?>
