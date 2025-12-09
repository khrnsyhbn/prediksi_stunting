<?php
function hitungUmurBulan($tanggal_lahir, $tanggal_pengukuran) {
    // Ubah ke format DateTime
    $lahir = new DateTime($tanggal_lahir);
    $ukur = new DateTime($tanggal_pengukuran);

    // Jika tanggal pengukuran lebih kecil, swap (hindari minus)
    if ($ukur < $lahir) {
        return 0;
    }

    // Ambil selisih awal
    $selisih = $lahir->diff($ukur);
    $tahun = $selisih->y;
    $bulan = $selisih->m;
    $hari = $selisih->d;

    // Jika hari >= 30, tambahkan 1 bulan
    if ($hari >= 30) {
        $bulan += 1;
    }

    // Total umur dalam bulan penuh
    $umur_bulan = ($tahun * 12) + $bulan;

    return $umur_bulan;
}
?>
