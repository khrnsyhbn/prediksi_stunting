<?php
// menghubungkan ke database dan fungsi umum
include '../koneksi.php'; 

// === Fungsi hitung Z-Score TB/U ===
function hitung_zscore_bbu($berat_badan, $jenis_kelamin, $umur_bulan) {
    global $koneksi;
    if (trim($jenis_kelamin) == "Perempuan") {
        $jk = "P";
    } elseif (trim($jenis_kelamin) == "Laki-laki") {
        $jk = "L";
    } elseif (strtolower(trim($jenis_kelamin)) == "p") {
        $jk = "P";
    } elseif (strtolower(trim($jenis_kelamin)) == "l") {
        $jk = "L";
    } else {
        return "Jenis kelamin tidak valid";
    }
     
    // Pastikan umur_bulan tidak lebih dari 60
    $umur_bulan = intval($umur_bulan); // pastikan integer
    if ($umur_bulan > 60) {
        $umur_bulan = 60;
    }
    // Ambil data standar dari tabel standar_tbu berdasarkan jenis kelamin & umur
    $sql = "SELECT min1sd, median, plus1sd 
            FROM standar_bbu 
            WHERE jenis_kelamin = '$jk' 
            AND umur_bulan = $umur_bulan
            LIMIT 1";
    $result = mysqli_query($koneksi, $sql);
   
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $sd_neg1 = $row['min1sd'];
        $median  = $row['median'];
        $sd_pos1 = $row['plus1sd'];

        // Tentukan SBi
        if ($berat_badan > $median) {
            $SBi = $sd_pos1 - $median;
        } else {
            $SBi = $median - $sd_neg1;
        }

        // Hitung Z-score
        $zscore = ($berat_badan - $median) / $SBi;

        return  number_format(round($zscore, 2), 2);
    } else {
        return "Data standar tidak ditemukan untuk umur $umur_bulan bulan ($jenis_kelamin)";
    }
}
?>
