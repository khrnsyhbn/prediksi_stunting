<?php
require_once 'auth.php';
include '../koneksi.php';
checkRole(['user']);
header('Content-Type: application/json');


$id_puskesmas = $_SESSION['id_puskesmas'] ?? '';
$role = $_SESSION['role'] ?? '';
$is_admin_dinkes = ($role === 'admin' || preg_match('/^DK/i', $id_puskesmas));

$kelurahan_filter = $_GET['kelurahan'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');

// ========== WHERE DASAR =============
$where = "WHERE 1=1";

if(!$is_admin_dinkes){
    $where .= " AND id_puskesmas = '$id_puskesmas' AND status_qc = 'approved' ";
}

if($kelurahan_filter != ''){
    $where .= " AND id_kelurahan = '$kelurahan_filter' AND status_qc = 'approved'";
}


// =======================================================================
// TOTAL ANAK (berdasarkan pengukuran terakhir)
// =======================================================================
$q_total_anak = "
SELECT COUNT(*) AS total
FROM (
    SELECT id_balita, MAX(tanggal_pengukuran)
    FROM pengukuran_raw
    $where
    GROUP BY id_balita
) AS x
";

$r_total_anak = mysqli_query($koneksi, $q_total_anak);
$total_anak = ($row = mysqli_fetch_assoc($r_total_anak)) ? (int)$row['total'] : 0;


// =======================================================================
// TOTAL PER LEVEL (Doughnut)
// =======================================================================
$q_level = "
SELECT p.level_stunting, COUNT(*) AS jumlah
FROM pengukuran_raw p
JOIN (
    SELECT id_balita, MAX(tanggal_pengukuran) AS latest
    FROM pengukuran_raw
    $where
    GROUP BY id_balita
) L ON L.id_balita = p.id_balita AND L.latest = p.tanggal_pengukuran
$where
GROUP BY p.level_stunting
";

$r_level = mysqli_query($koneksi, $q_level);

$total_level = [
    'STUNTING_PARAH' => 0,
    'STUNTING_SEDANG'=> 0,
    'STUNTING_RINGAN'=> 0,
    'NORMAL'         => 0,
    'OBESITAS'       => 0
];

while($row = mysqli_fetch_assoc($r_level)){
    if(isset($total_level[$row['level_stunting']])){
        $total_level[$row['level_stunting']] = (int)$row['jumlah'];
    }
}


// =======================================================================
// LINE CHART (TREN TAHUNAN) — tanpa filter bulan
// =======================================================================

// 1) Ambil data tahun target
$q_target = "
SELECT id_balita, level_stunting, tanggal_pengukuran,
MONTH(tanggal_pengukuran) AS bln
FROM pengukuran_raw
$where
AND YEAR(tanggal_pengukuran) = '$tahun'
ORDER BY id_balita, tanggal_pengukuran
";

$r_target = mysqli_query($koneksi, $q_target);

// 2) Ambil status terakhir sebelum tahun target
$q_prev = "
SELECT p1.id_balita, p1.level_stunting
FROM pengukuran_raw p1
JOIN (
    SELECT id_balita, MAX(tanggal_pengukuran) AS last_date
    FROM pengukuran_raw
    WHERE YEAR(tanggal_pengukuran) < '$tahun'
    GROUP BY id_balita
) p2 ON p1.id_balita = p2.id_balita AND p1.tanggal_pengukuran = p2.last_date
$where
";

$r_prev = mysqli_query($koneksi, $q_prev);


// ===== SIMPAN STATUS BALITA =======
$hist = [];

// data tahun sebelum
while($row = mysqli_fetch_assoc($r_prev)){
    $hist[$row['id_balita']] = ['last' => $row['level_stunting'], 'data' => []];
}

// data tahun target
while($row = mysqli_fetch_assoc($r_target)){
    $id = $row['id_balita'];
    $bulan = (int)$row['bln'];

    if(!isset($hist[$id])) $hist[$id] = ['last'=>null, 'data'=>[]];

    $hist[$id]['data'][$bulan] = $row['level_stunting'];
}


// ===== HITUNG PER BULAN ============
$tren_count = [
    'STUNTING_PARAH' => array_fill(0,12,0),
    'STUNTING_SEDANG'=> array_fill(0,12,0),
    'STUNTING_RINGAN'=> array_fill(0,12,0),
    'NORMAL'         => array_fill(0,12,0),
    'OBESITAS'       => array_fill(0,12,0),
];

foreach($hist as $id => $v){
    $last = $v['last'];

    for($m=1;$m<=12;$m++){
        if(isset($v['data'][$m])) $last = $v['data'][$m];

        if($last !== null && isset($tren_count[$last])){
            $tren_count[$last][$m-1]++;
        }
    }
}


// =======================================================================
// OUTPUT JSON
// =======================================================================
echo json_encode([
    'total_level' => $total_level,
    'total_anak' => $total_anak,
    'tren' => [
        'labels' => ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"],
        'datasets' => $tren_count
    ]
]);
