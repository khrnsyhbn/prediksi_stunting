<?php
require_once 'auth.php';
require('../assets/fpdf/fpdf.php');
include '../koneksi.php';
include 'fungsi_bb_u.php';
checkLogin();

// Ambil data berdasarkan ID
$id = $_GET['id'] ?? '';

function hitung_umum_bbu($jenis_kelamin, $umur_bulan) {
    global $koneksi;
    if (trim($jenis_kelamin) == "Perempuan") {
        $jk = "P";
    } elseif (trim($jenis_kelamin) == "Laki-laki") {
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
        $median  = $row['median'];

        return  number_format(round($median, 2), 2);
    } else {
        return "Data standar tidak ditemukan untuk umur $umur_bulan bulan ($jenis_kelamin)";
    }
}
function hitung_umum_tbu($jenis_kelamin, $umur_bulan) {
    global $koneksi;
    if (trim($jenis_kelamin) == "Perempuan") {
        $jk = "P";
    } elseif (trim($jenis_kelamin) == "Laki-laki") {
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
    $sql = "SELECT sd_neg1, median, sd_pos1 
            FROM standar_tbu 
            WHERE jenis_kelamin = '$jk' 
            AND umur_bulan = $umur_bulan
            LIMIT 1";
    $result = mysqli_query($koneksi, $sql);
   
    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $median  = $row['median'];

        return  number_format(round($median, 2), 2);
    } else {
        return "Data standar tidak ditemukan untuk umur $umur_bulan bulan ($jenis_kelamin)";
    }
}



$data = [];
if($id){
    $sql = "
    SELECT p.*, b.nama_balita, b.nik, b.nama, b.jenis_kelamin_balita
    FROM pengukuran_raw p
    LEFT JOIN balita b ON p.id_balita = b.id_balita
    WHERE raw_id = ?";
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
}

// Contoh kategori fuzzy dan saran
$kategori_fuzzy = $data['status_gizi'] ?? 'normal';
$saran = [];

switch($kategori_fuzzy){
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

// Contoh data perbandingan BB/TB menurut umur
$umur = $data['umur_dalam_bulan'] ?? ''; // bulan
$bb_normal = hitung_umum_bbu($data['jenis_kelamin_balita'], $umur);
$tb_normal = hitung_umum_tbu($data['jenis_kelamin_balita'], $umur);
$bbu_normal = '-2.25 s.d 1.25';
$tbu_normal = '-2.25 s.d 3.25';
$status_giziBaik = '-2.25 s.d 1.25';
$saran = $data['saran'];

// Pecah per kalimat (dengan menghapus spasi kosong di awal/akhir)
$kalimat = array_filter(array_map('trim', explode('.', $saran)));

// --- Mapping saran → status gizi ---
$mapping_saran_ke_status = [
    // BB/U
    "Segera lakukan pemantauan intensif dan berikan asupan gizi tinggi energi serta protein" => "Berat Badan menurut Umur (BB/U) : Sangat Kurang",
    "Tingkatkan asupan makanan bergizi seimbang dan rutin melakukan penimbangan" => "Berat Badan menurut Umur (BB/U) : Kurang",
    "Pertahankan pola makan dan pemantauan rutin" => "Berat Badan menurut Umur (BB/U) : Normal",
    "Kurangi makanan tinggi gula, garam, dan lemak serta perbanyak aktivitas fisik" => "Berat Badan menurut Umur (BB/U) : Risiko Berat Badan Lebih",
    // TB/U
    "Lakukan intervensi gizi jangka panjang karena risiko stunting sangat tinggi" => "Tinggi Badan menurut Umur (TB/U) : Sangat Pendek",
    "Optimalkan konsumsi protein hewani serta vitamin dan mineral penting" => "Tinggi Badan menurut Umur (TB/U) : Pendek",
    "Pemantauan pertumbuhan tetap diperlukan" => "Tinggi Badan menurut Umur (TB/U) : Normal",
    "Pastikan kebutuhan energi terpenuhi sesuai usia" => "Tinggi Badan menurut Umur (TB/U) : Tinggi",
    // BB/TB
    "Segera rujuk ke fasilitas kesehatan terdekat dan lakukan penanganan gizi buruk" => "Berat Badan menurut Tinggi Badan (BB/TB) : Gizi Buruk",
    "Tambahkan makanan padat gizi, terutama protein hewani" => "Berat Badan menurut Tinggi Badan (BB/TB) : Gizi Kurang",
    "Pertahankan pola makan sesuai pedoman gizi seimbang" => "Berat Badan menurut Tinggi Badan (BB/TB) : Gizi Baik",
    "Kurangi konsumsi makanan manis, berlemak, dan berkalori tinggi" => "Berat Badan menurut Tinggi Badan (BB/TB) : Risiko Gizi Lebih",
    "Kendalikan porsi makan, batasi camilan tidak sehat, dan tingkatkan aktivitas fisik" => "Berat Badan menurut Tinggi Badan (BB/TB) : Gizi Lebih / Obesitas"
];

// --- Ambil status gizi dari kalimat ---
$status_gizi = [];
foreach ($kalimat as $k) {
    if (isset($mapping_saran_ke_status[$k])) {
        $status_gizi[] = $mapping_saran_ke_status[$k];
    }
}

// Gabungkan menjadi string untuk ditampilkan di field form
$status_gizi_str = implode(', ', $status_gizi);


?>
<!DOCTYPE html>
<html>
<head>
    <title>Hasil Analisis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
@media print {
    @page {
        size: A4;          /* Set ukuran kertas A4 */
        margin: 0;         /* Margin minimum */
    }

    body {
        -webkit-print-color-adjust: exact; /* Untuk background graphics */
        print-color-adjust: exact;
        overflow: hidden; /* jangan biarkan scroll atau overflow memicu halaman baru */
        /* transform: scale(1);             Scale 90% */
        transform-origin: top left;
        font-size: 12px; /* atau lebih kecil jika perlu */
        padding: 0;
    }

    /* Hilangkan header/footer browser */
    header, footer, .no-print {
        display: none !important;
    }

    /* Semua elemen bisa full-width */
    html, body {
        width: 210mm; /* Lebar A4 */
        /* height: 297mm; Tinggi A4 */
    }
    
}
.container mt-4{
    max-height: 297mm;
    overflow: hidden; /* paksa konten tetap di satu halaman */
}
</style>
</head>
<body>
<div class="container mt-4">
    <div class="card border-success">
        <div class="card-header bg-success text-white">
            Hasil Analisis Batch ID: <?php echo $data['batch_id']?? ''; ?>
            <a href="tampil_download.php?action=download&id=<?php echo $data['raw_id'] ?>" class="btn btn-light btn-sm float-end" onclick="triggerPrint()">
                <i class="fas fa-file-alt"></i> Download Hasil
            </a>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th>NIK</th>
                    <td><?php echo $data['nik'] ?? ''; ?></td>
                </tr>
                <tr>
                    <th>Nama Orang Tua</th>
                    <td><?php echo $data['nama'] ?? ''; ?></td>
                </tr>
                <tr>
                    <th>Nama Balita</th>
                    <td><?php echo $data['nama_balita'] ?? ''; ?></td>
                </tr>
                <tr>
                    <th>Jenis Kelamin Balita</th>
                    <td><?php echo $data['jenis_kelamin_balita'] ?? ''; ?></td>
                </tr>
                <tr>
                    <th>Umur (bulan)</th>
                    <td><?php echo $data['umur_dalam_bulan'] ?? ''; ?></td>
                </tr>
                <tr>
                    <th>Berat Badan (kg)</th>
                    <td>Sekarang: <?php echo $data['berat'] ?? ''; ?> | Normal: <?php echo round($bb_normal,1); ?></td>
                </tr>
                <tr>
                    <th>Tinggi Badan (cm)</th>
                    <td>Sekarang: <?php echo $data['tinggi'] ?? ''; ?> | Normal: <?php echo round($tb_normal,1); ?></td>
                </tr>
                <tr>
                    <th>Status Gizi</th>
                    <td>
                        Normal vs Sekarang
                        <br>
                        <strong>Note : </strong> Semakin kecil nilai (negatif) maka buruk dan semakin besar nilai (positif) maka baik sampai berlebihan.
                        <hr>
                        Berat Badan menurut Umur <strong>(BB/U)</strong>:
                        <br>
                        Nilai z-score normal: <strong><?php echo $bbu_normal; ?></strong> VS Sekarang:  <strong><?php echo $data['bbu'] ?? ''; ?></strong>
                        <br><br>
                        Tinggi Badan menurut Umur <strong>(TB/U)</strong>:
                        <br>
                        Nilai z-score normal: <strong><?php echo $tbu_normal; ?></strong> VS Sekarang:  <strong><?php echo $data['tbu'] ?? ''; ?></strong>
                        <br><br>
                        Berat Badan menurut Tinggi Badan <strong>(BB/TB)</strong>:
                        <br>
                        Nilai z-score gizi baik: <strong><?php echo $status_giziBaik; ?></strong> VS Sekarang:  <strong><?php echo $data['bbtb'] ?? ''; ?></strong>
                        <br>

                    </td>
                </tr>
                <tr>
                    <th>Status Gizi</th>
                    <td>
                        <?php foreach ($status_gizi as $sg): ?>
                            <span class="badge bg-success"><?php echo htmlspecialchars($sg); ?></span>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <tr>
                    <th>Level Stunting</th>
                    <td><span class="badge bg-success"><?php echo $data['level_stunting'] ?? ''; ?></span></td>
                </tr>
            </table>
            <div class="alert alert-success mt-3">
                <strong>Saran:</strong>
                <?php
                // Tampilkan per poin
                if (!empty($kalimat)) {
                    echo '<ul>';
                    foreach ($kalimat as $k) {
                        if ($k !== '') {
                            echo '<li>' . htmlspecialchars($k) . '.</li>';
                        }
                    }
                    echo '</ul>';
                } else{
                    echo "Tidak ada saran";
                }
                ?>
            </div>
            <a href="hasil_analisis.php" class="btn btn-secondary">Kembali ke Form Upload</a>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a2e0e6f2b1.js" crossorigin="anonymous"></script>
<script>
    function triggerPrint() {
        window.print();
    }
</script>
</body>
</html>