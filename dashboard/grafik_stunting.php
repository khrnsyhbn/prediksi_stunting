<?php
require 'auth.php';
include '../koneksi.php';
checkLogin();

$role = $_SESSION['role'] ?? '';
$id_puskesmas = $_SESSION['id_puskesmas'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Stunting Interaktif</title>

<link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
<link href="../assets/css/sb-admin-2.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<style>
.chart-container { margin-bottom: 30px; }
canvas { width: 100% !important; max-height: 400px; }
.filter-container .btn { margin-left: 10px; }
.card-level { margin-bottom: 10px; }
.card-level .card-body { padding: 0.5rem; }
.card-level p { font-size: 0.8rem; margin-bottom: 0.2rem; }
.card-level h2 { font-size: 1.2rem; margin: 0; }
#doughnutLegend div { display: flex; align-items: center; margin-bottom: 5px; }
#doughnutLegend div div { width: 20px; height: 20px; margin-right: 10px; }
.doughnut-wrapper { position: relative; width: 100%; max-width: 500px; margin: auto; aspect-ratio: 1; }
</style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php include 'sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-fluid">

                <h1 class="h3 mb-4 text-gray-800 text-center font-weight-bold">
                    <i class="fas fa-chart-bar text-primary"></i> Dashboard Stunting Interaktif
                </h1>

                <!-- Filter -->
                <div class="card shadow mb-4 border-left-primary">
                    <div class="card-body">
                        <form class="form-inline justify-content-center filter-container mb-3">
                            <!-- Filter Bulan -->
                            

                            <!-- Filter Tahun -->
                            <select id="tahun" class="form-control mr-2">
                                <?php
                                $tahun_now = date('Y');
                                for($t = $tahun_now; $t >= 2020; $t--) {
                                    echo "<option value='$t'>$t</option>";
                                }
                                ?>
                            </select>

                            <!-- Filter Kelurahan -->
                            <select id="kelurahan" class="form-control mr-2">
                                <option value="">Semua Kelurahan</option>
                                <?php
                                if ($role === 'admin' || preg_match('/^DK/i', $id_puskesmas)) {
                                    $q = mysqli_query($koneksi,"SELECT DISTINCT id_kelurahan, nama_kelurahan FROM kelurahan ORDER BY nama_kelurahan ASC");
                                } else {
                                    $q = mysqli_query($koneksi,
                                        "SELECT DISTINCT k.id_kelurahan, k.nama_kelurahan
                                        FROM kelurahan k
                                        JOIN puskesmas p ON p.id_puskesmas = k.id_puskesmas
                                        WHERE p.id_puskesmas = '$id_puskesmas'
                                        ORDER BY k.nama_kelurahan ASC;");
                                }
                                while($d = mysqli_fetch_assoc($q)){
                                    echo '<option value="'.$d['id_kelurahan'].'">'.$d['nama_kelurahan'].'</option>';
                                }
                                ?>
                            </select>

                            <button type="button" class="btn btn-primary" onclick="loadData()">
                                <i class="fas fa-filter"></i> Tampilkan
                            </button>

                            <button type="button" class="btn btn-success" onclick="downloadCharts()">
                                <i class="fas fa-download"></i> Simpan Grafik
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Info Cards -->
                <div class="row" id="infoCards"></div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-lg-12 chart-container">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-info text-white">
                                <h6 class="m-0 font-weight-bold">📊 Tren Stunting Anak per Bulan</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="lineChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-12 chart-container">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 bg-warning text-white">
                                <h6 class="m-0 font-weight-bold">🍩 Distribusi Level Stunting Anak</h6>
                            </div>
                            <div class="card-body">
                                <div class="doughnut-wrapper">
                                    <canvas id="doughnutChart" height="300"></canvas>
                                </div>
                                <div id="doughnutLegend" class="mt-3"></div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>

        <?php include 'footer.php'; ?>
    </div>
</div>
<!-- Logout Modal-->
    <?php include 'logout_alert.php'; ?>

<script src="../assets/vendor/jquery/jquery.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
<script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>
<script src="../assets/js/sb-admin-2.js"></script>

<script>
const levelColors = {
    'STUNTING_PARAH': '#FF6384',
    'STUNTING_SEDANG': '#36A2EB',
    'STUNTING_RINGAN': '#FFCE56',
    'NORMAL': '#4BC0C0',
    'OBESITAS': '#9966FF'
};

let lineChartInstance = null;
let doughnutChartInstance = null;

async function loadData() {
    const kelurahan = document.getElementById('kelurahan').value;
    const tahun = document.getElementById('tahun').value;

    const response = await fetch(`get_grafik_data.php?kelurahan=${kelurahan}&tahun=${tahun}`);
    const data = await response.json();

    renderCards(data.total_level, data.total_anak);
    renderLineChart(data.tren.labels, data.tren.datasets);
    renderDoughnutChart(data.total_level);
}

function renderCards(totalLevel, totalAnak){
    const container = document.getElementById('infoCards');
    container.innerHTML = '';

    container.innerHTML += `
        <div class="col-lg-2 col-md-3 col-sm-4 card-level">
            <div class="card shadow border-left-success">
                <div class="card-body text-center">
                    <p class="font-weight-bold">TOTAL ANAK</p>
                    <h2>${totalAnak}</h2>
                </div>
            </div>
        </div>
    `;

    for(const [level, jumlah] of Object.entries(totalLevel)){
        container.innerHTML += `
            <div class="col-lg-2 col-md-3 col-sm-4 card-level">
                <div class="card shadow border-left-primary">
                    <div class="card-body text-center">
                        <p class="font-weight-bold">${level.replace('_',' ')}</p>
                        <h2>${jumlah}</h2>
                    </div>
                </div>
            </div>
        `;
    }
}

function renderLineChart(labels, datasetsInput) {
    const ctx = document.getElementById('lineChart').getContext('2d');
    Chart.register(ChartDataLabels);

    const monthNames = [
        'Januari','Februari','Maret','April','Mei','Juni',
        'Juli','Agustus','September','Oktober','November','Desember'
    ];

    const convertedLabels = labels.map(b => monthNames[parseInt(b) - 1] || b);

    const datasets = Object.entries(datasetsInput).map(([level, dataArr]) => ({
        label: level.replace('_', ' '),
        data: dataArr,
        borderColor: levelColors[level] || '#333',   // warna garis tegas
        backgroundColor: 'transparent',              // hilangkan fill agar garis tajam
        tension: 0.2,                                // kurva halus tapi tegas
        fill: false,                                 // tanpa area transparan
        pointRadius: 5,
        pointHoverRadius: 7,
        borderWidth: 3                               // garis lebih tegas
    }));

    if (lineChartInstance) lineChartInstance.destroy();

    lineChartInstance = new Chart(ctx, {
        type: 'line',
        data: { labels: convertedLabels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                datalabels: { display: false },
                tooltip: { enabled: true },
                legend: { position: 'top' },
                title: { display: true, text: 'Tren Stunting Anak per Bulan', font: { size: 16, weight: 'bold' } }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Bulan', font: { size: 14, weight: 'bold' } },
                    ticks: { autoSkip: false, font: { size: 12 } }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Jumlah Anak', font: { size: 14, weight: 'bold' } },
                    ticks: { stepSize: 1, font: { size: 12 } }
                }
            },
            hover: { mode: 'nearest', intersect: true },
            devicePixelRatio: window.devicePixelRatio || 1
        }
    });
}

function renderDoughnutChart(totalLevel){
    const ctx = document.getElementById('doughnutChart').getContext('2d');
    const labels = Object.keys(totalLevel).map(l => l.replace('_',' '));
    const dataValues = Object.values(totalLevel);
    const colors = Object.keys(totalLevel).map(l => levelColors[l] || '#333');

    if(doughnutChartInstance) doughnutChartInstance.destroy();

    doughnutChartInstance = new Chart(ctx,{
        type:'doughnut',
        data:{labels,datasets:[{data:dataValues,backgroundColor:colors}]},
        options:{responsive:true,maintainAspectRatio:true}
    });

    const total = dataValues.reduce((a,b)=>a+b,0);
    const legendDiv = document.getElementById('doughnutLegend');
    legendDiv.innerHTML = '';
    labels.forEach((label,i)=>{
        let percent = 0;

        // Hindari NaN jika total = 0
        if (total > 0) {
            percent = ((dataValues[i] / total) * 100).toFixed(1);
        }

        legendDiv.innerHTML += `
            <div>
                <div style="background:${colors[i]}"></div>
                <strong>${label}:</strong> ${percent}%
            </div>
        `;
    });
}

function hexToRgba(hex, alpha=1){
    const bigint = parseInt(hex.replace('#',''),16);
    return `rgba(${(bigint>>16)&255}, ${(bigint>>8)&255}, ${bigint&255}, ${alpha})`;
}

function downloadCharts(){
    [
        {chart: lineChartInstance, name:'tren_stunting.png'},
        {chart: doughnutChartInstance, name:'distribusi_stunting.png'}
    ].forEach(c=>{
        const link = document.createElement('a');
        link.download = c.name;
        link.href = c.chart.toBase64Image();
        link.click();
    });
}

// Load awal
loadData();
</script>

</body>
</html>
