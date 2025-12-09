<?php
require('../assets/fpdf/fpdf.php');
include '../koneksi.php';
require_once 'auth.php';
checkLogin();

$id_puskesmas_user = $_SESSION['id_puskesmas'];

// Ambil nama puskesmas user login
$qPm = mysqli_query($koneksi, 
    "SELECT nama_puskesmas FROM puskesmas WHERE id_puskesmas='$id_puskesmas_user'"
);
$pmRow = mysqli_fetch_assoc($qPm);

// Jika tidak ada, default
$namaPuskesmasUser = $pmRow ? $pmRow['nama_puskesmas'] : 'Dinas Kesehatan Banjarmasin';


// === FILTER DATA BERDASARKAN PUSKESMAS USER LOGIN ===

// Jika ID puskesmas user login mengandung 'DK' (contoh: DK001)
if (strpos($id_puskesmas_user, 'DK') === 0) {
    // Tidak ada filter puskesmas → tampilkan semua data
    $wherePkm = "";
} else {
    // Filter data hanya milik puskesmas user login
    $wherePkm = "WHERE p.id_puskesmas = '$id_puskesmas_user'";
}

// === QUERY UTAMA ===
$query = "
SELECT 
    ot.nik,
    ot.nama AS nama_orangtua,
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
    u.full_name,
    p.level_stunting,
    p.skor_prediksi,
    p.saran,
    p.tanggal_prediksi,
    p.status_qc,
    p.supervisor_id,
    p.catatan_supervisor,
    p.created_at,
    p.updated_at,
    p.batch_id
FROM pengukuran_raw p
JOIN users u ON p.id_user = u.id_user
JOIN balita b ON p.id_balita = b.id_balita
JOIN orang_tua ot ON b.nik = ot.nik
JOIN puskesmas pm ON p.id_puskesmas = pm.id_puskesmas
JOIN kelurahan k ON p.id_kelurahan = k.id_kelurahan
LEFT JOIN posyandu py ON p.id_posyandu = py.id_posyandu
INNER JOIN (
    SELECT id_balita, MAX(tanggal_pengukuran) AS latest_pengukuran
    FROM pengukuran_raw
    WHERE status_qc = 'approved'
    GROUP BY id_balita
) AS latest 
    ON latest.id_balita = p.id_balita 
    AND latest.latest_pengukuran = p.tanggal_pengukuran
$wherePkm
ORDER BY p.tanggal_pengukuran DESC
";

$result = mysqli_query($koneksi, $query);

// Subclass PDF
class PDF extends FPDF {
    public $logoPath = '../assets/img/logo.jpg';
    public $title = '';

    function HeaderTitle() {
        if(file_exists($this->logoPath)) $this->Image($this->logoPath,10,10,50);
        $this->SetFont('Arial','B',12);
        $this->Cell(0,10,$this->title,0,1,'C');
        $this->SetFont('Arial','',8);
        $this->Cell(0,6,'Tanggal Cetak: '.date('d-m-Y'),0,1,'C');
        $this->Ln(5);
    }

    function PrintHeaderRow($header, $widths) {
        $this->SetFont('Arial', 'B', 9);

        // 1. Hitung jumlah baris tiap header
        $lineHeights = [];
        for ($i = 0; $i < count($header); $i++) {
            $lineHeights[] = $this->NbLines($widths[$i], $header[$i]);
        }

        // 2. Tentukan tinggi baris tertinggi
        $maxLines = max($lineHeights);
        $rowHeight = 6 * $maxLines; // 6mm per baris header

        // 3. Render header
        $xStart = $this->GetX();
        $yStart = $this->GetY();

        for ($i = 0; $i < count($header); $i++) {

            $x = $this->GetX();
            $y = $this->GetY();

            // Draw cell rectangle
            $this->Rect($x, $y, $widths[$i], $rowHeight);

            // Write text (MultiCell menyesuaikan baris otomatis)
            $this->MultiCell($widths[$i], 6, $header[$i], 0, 'C');

            // Set posisi X ke kolom berikutnya
            $this->SetXY($x + $widths[$i], $y);
        }

        // 4. Setelah selesai, pindah ke baris bawah
        $this->Ln($rowHeight);

        // Kembalikan font normal
        $this->SetFont('Arial', '', 8);
    }


    function RowPartial($data,$cols,$widths,$alignCols=[]){
        $nb=[];
        for($i=0;$i<count($cols);$i++){
            $nb[]= $this->NbLines($widths[$i],$data[$cols[$i]]);
        }
        $h = 5 * max($nb);

        $x0=$this->GetX();
        $y0=$this->GetY();
        for($i=0;$i<count($cols);$i++){
            $x=$this->GetX();
            $y=$this->GetY();
            $this->Rect($x,$y,$widths[$i],$h);
            $align = in_array($i,$alignCols)?'L':'C';
            $this->MultiCell($widths[$i],5,$data[$cols[$i]],0,$align);
            $this->SetXY($x+$widths[$i],$y);
        }
        $this->Ln($h);
    }

    function NbLines($w,$txt){
        $cw=$this->CurrentFont['cw'];
        if($w==0)$w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 && $s[$nb-1]=="\n") $nb--;
        $sep=-1;$i=0;$j=0;$l=0;$nl=1;
        while($i<$nb){
            $c=$s[$i];
            if($c=="\n"){$i++;$sep=-1;$j=$i;$l=0;$nl++;continue;}
            if($c==' ')$sep=$i;
            $l+=$cw[$c];
            if($l>$wmax){if($sep==-1){if($i==$j)$i++;}else $i=$sep+1;$sep=-1;$j=$i;$l=0;$nl++;}else $i++;
        }
        return $nl;
    }

    function Footer(){
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo(),0,0,'C');
    }
}

// Buat objek PDF
$pdf = new PDF('L', 'mm', array(330,210));
$pdf->title = "Laporan Pengukuran Balita - $namaPuskesmasUser";
$pdf->SetAutoPageBreak(true,10);
$pdf->SetFont('Arial','',8);

// Semua kolom yang ingin ditampilkan
$cols=['nik','nama_balita','jenis_kelamin_balita',
       'tanggal_pengukuran','umur_dalam_bulan','bbu',
       'tbu','bbtb','nama_puskesmas','kecamatan',
       'nama_kelurahan','full_name','level_stunting',
       'saran','tanggal_prediksi'];

$headers=['  NIK  Orang Tua','Nama Balita','JK',
        'Tgl Ukur (Y-M-D)','Umur (Bulan)', 'BB/U',
        'TB/U','BB/TB','Puskesmas','Kecamatan',
        'Kelurahan','Nama Petugas','Level Stunting',
        'Saran','Tanggal Prediksi'];

// Tentukan lebar kolom, sesuaikan total dengan lebar F4 landscape 330mm
$widths=[20,25,18, //63
        18,22,10,   //46
        10,12,22,22,    //69
        22,20,22,    //75
        40,25]; //65 //total 361

// Tambahkan halaman
$pdf->AddPage();
$pdf->HeaderTitle();
$pdf->PrintHeaderRow($headers,$widths);

// Tampilkan semua data dalam satu halaman
while($row=mysqli_fetch_assoc($result)){
    $pdf->RowPartial($row,$cols,$widths,[2,3,16,20]); // kolom yang align kiri
}

$pdf->Output('D','Laporan_Pengukuran_Balita.pdf');
?>
