<?php
// Fungsi untuk mencari kategori yang bernilai 1
function getKategoriAktif($arr) {
    foreach ($arr as $kategori => $nilai) {
        if ((int)$nilai === 1) {
            return $kategori;
        }
    }
    return null;
}

// Fungsi untuk menghasilkan saran berbasis kategori API
function generateSaranGizi($apiData) {

    // Ambil status utama
    $status_bbu  = getKategoriAktif($apiData['bbu']);
    $status_tbu  = getKategoriAktif($apiData['tbu']);
    $status_bbtb = getKategoriAktif($apiData['bbtb']);

    // Template saran standar
    $saran = [];

    // --- BB/U ---
    switch ($status_bbu) {
        case 'sangat_kurang':
            $saran[] = "Segera lakukan pemantauan intensif dan berikan asupan gizi tinggi energi serta protein.";
            break;
        case 'kurang':
            $saran[] = "Tingkatkan asupan makanan bergizi seimbang dan rutin melakukan penimbangan.";
            break;
        case 'normal':
            $saran[] = "Pertahankan pola makan dan pemantauan rutin.";
            break;
        case 'risiko_berat_lebih':
            $saran[] = "Kurangi makanan tinggi gula, garam, dan lemak. Perbanyak aktivitas fisik.";
            break;
    }

    // --- TB/U ---
    switch ($status_tbu) {
        case 'sangat_pendek':
            $saran[] = "Lakukan intervensi gizi jangka panjang karena risiko stunting sangat tinggi.";
            break;
        case 'pendek':
            $saran[] = "Optimalkan konsumsi protein hewani serta vitamin dan mineral penting.";
            break;
        case 'normal':
            $saran[] = "Pemantauan pertumbuhan tetap diperlukan.";
            break;
        case 'tinggi':
            $saran[] = "Pastikan kebutuhan energi terpenuhi sesuai usia.";
            break;
    }

    // --- BB/TB ---
    switch ($status_bbtb) {
        case 'gizi_buruk':
            $saran[] = "Segera rujuk ke fasilitas kesehatan terdekat dan lakukan penanganan gizi buruk.";
            break;
        case 'gizi_kurang':
            $saran[] = "Tambahkan makanan padat gizi, terutama protein hewani.";
            break;
        case 'gizi_baik':
            $saran[] = "Pertahankan pola makan sesuai pedoman gizi seimbang.";
            break;
        case 'resiko_gizi_lebih':
            $saran[] = "Kurangi konsumsi makanan manis, berlemak, dan berkalori tinggi.";
            break;
        case 'gizi_lebih':
            $saran[] = "Atur pola makan dengan porsi seimbang dan tingkatkan aktivitas fisik.";
            break;
        case 'obesitas':
            $saran[] = "Kendalikan porsi makan, batasi camilan tidak sehat, dan tingkatkan aktivitas fisik.";
            break;
    }

    // Gabungkan semua saran menjadi paragraf
    return implode(" ", $saran);
}
