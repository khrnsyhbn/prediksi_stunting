<?php
session_start();
include '../koneksi.php';
include 'fungsi_umur.php';
include 'fungsi_bb_u.php';
include 'fungsi_tb_u.php';
include 'fungsi_bb_tb.php';
include 'fungsi_saran.php';

// SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
@ob_end_flush();
ini_set('max_execution_time', 600);
ini_set('output_buffering', 'off');
ini_set('zlib.output_compression', false);

//cek data csv valid di session
if (!isset($_SESSION['data_csv_valid'])) {
    echo "data: ".json_encode(['done'=>true,'message'=>'Tidak ada data CSV valid.'])."\n\n";
    flush();
    exit;
}
// Ambil id_user, id_puskesmas dan nama puskesmas pengguna dari database/session login
$id_puskesmas_user = $_SESSION['id_puskesmas'] ?? '';
$nama_puskesmas_user = '';

if ($id_puskesmas_user !== '') {
    $stmt = $koneksi->prepare("SELECT nama_puskesmas FROM puskesmas WHERE id_puskesmas = ? LIMIT 1");
    $stmt->bind_param("s", $id_puskesmas_user);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $nama_puskesmas_user = $row['nama_puskesmas'];
    } else {
        $nama_puskesmas_user = 'Tidak ditemukan';
    }
    $stmt->close();
}

$id_user = $_SESSION['id_user'];
$data_csv = $_SESSION['data_csv_valid'];
$total_data = count($data_csv);
$api_url = "http://127.0.0.1:8000/stunting/batch";
// kode untuk menjalankan api fuzzy di terminal jangan direct sesuai folder, contoh C:/xampp/htdocs/prediksi_stunting/dashboard/fuzzy_api 
// open folder prediksi_stunting dan diterminal jalankan: cd prediksi_stunting\dashboard lalu jalankan:
// "python -m uvicorn fuzzy_api:app --reload --host 127.0.0.1 --port 8000"

$batch_id = date('YmdHis');
$fuzzy_batch = [];
$data_tersimpan = [];
$log_gagal = [];

// 1️⃣ Hitung z-score
foreach($data_csv as $index=>$row){
    $tgl_lahir = $row['tanggal_lahir'];
    $tgl_ukur  = $row['tanggal_pengukuran']; 
    $jk        = $row['jenis_kelamin'];
    $bb        = (float)$row['berat_badan'];
    $tb        = (float)$row['tinggi_badan'];

    $umur_bulan = hitungUmurBulan($tgl_lahir, $tgl_ukur);
    $bb_u  = hitung_zscore_bbu($bb, $jk, $umur_bulan);
    $tb_u  = hitung_zscore_tbu($tb, $jk, $umur_bulan);
    $bb_tb = hitung_zscore_bbTb($bb, $jk, $tb, $umur_bulan);

    if(!is_numeric($bb_u) || !is_numeric($tb_u) || !is_numeric($bb_tb)){
        $log_gagal[]=['baris'=>$index+2,'nama_balita'=>$row['nama_balita'],'alasan'=>"Z-score tidak valid",'id_puskesmas'=>$id_puskesmas_user,'nama_puskesmas'=>$nama_puskesmas_user];
        continue;
    }

    $fuzzy_batch[]=['bbu'=>$bb_u,'tbu'=>$tb_u,'bbtb'=>$bb_tb];
    $data_tersimpan[]=array_merge($row,['umur_bulan'=>$umur_bulan]);

    $progress=round(($index+1)/$total_data*40);
    echo "data: ".json_encode(['progress'=>$progress,'message'=>"Menghitung z-score... ".($index+1)." dari $total_data"])."\n\n";
    flush();
}

if(empty($fuzzy_batch)){
    echo "data: ".json_encode(['done'=>true,'message'=>'Semua data gagal dihitung z-score.'])."\n\n";
    flush();
    exit;
}

// 2️⃣ Kirim ke API Fuzzy
echo "data: ".json_encode(['progress'=>50,'message'=>'Mengirim data ke API Fuzzy...'])."\n\n"; flush();
$payload=json_encode(['data'=>$fuzzy_batch]);
$ch=curl_init($api_url);
curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER=>true,
    CURLOPT_HTTPHEADER=>['Content-Type: application/json'],
    CURLOPT_POST=>true,
    CURLOPT_POSTFIELDS=>$payload
]);
$response=curl_exec($ch);
curl_close($ch);
$api_result=json_decode($response,true);

if(!isset($api_result['results'])){
    echo "data: ".json_encode(['done'=>true,'message'=>"❌ Gagal menerima respon dari API"])."\n\n"; flush(); exit;
}
$hasil_fuzzy = $api_result['results'];
echo "data: ".json_encode(['progress'=>60,'message'=>'API berhasil memberikan hasil prediksi...'])."\n\n"; flush();

// 3️⃣ Simpan ke DB
mysqli_begin_transaction($koneksi);
try{
    $total_sukses = count($data_tersimpan);
    foreach($data_tersimpan as $i=>$row){
        $nik = mysqli_real_escape_string($koneksi, $row['nik']);
        $nama = mysqli_real_escape_string($koneksi, $row['nama_orangtua']);
        $alamat = mysqli_real_escape_string($koneksi, $row['alamat']);
        $no_hp = mysqli_real_escape_string($koneksi, $row['no_hp']);
        $nama_balita = mysqli_real_escape_string($koneksi, $row['nama_balita']);
        $jk = $row['jenis_kelamin'];
        $tgl_lahir = $row['tanggal_lahir'];
        $tgl_ukur = $row['tanggal_pengukuran'];
        $bb = (float)$row['berat_badan'];
        $tb = (float)$row['tinggi_badan'];
        $umur_bulan = (int)$row['umur_bulan'];
        $id_kelurahan = $row['id_kelurahan'];
        $id_posyandu = $row['id_posyandu'];
        $id_puskesmas = $row['id_puskesmas'];

        

        $bb_u = $fuzzy_batch[$i]['bbu'];
        $tb_u = $fuzzy_batch[$i]['tbu'];
        $bb_tb = $fuzzy_batch[$i]['bbtb'];
        $level = mysqli_real_escape_string($koneksi,$hasil_fuzzy[$i]['level_stunting']??'Tidak diketahui');
        $prediksi = (float)($hasil_fuzzy[$i]['prediksi']??0);
        $saran_gizi = generateSaranGizi($hasil_fuzzy[$i]['membership_input']??[]);

        // --- Cek & simpan orang tua ---
        $cek_ortu = mysqli_query($koneksi,"SELECT nama FROM orang_tua WHERE nik='$nik'");
        if(mysqli_num_rows($cek_ortu)>0){
            $row_ortu = mysqli_fetch_assoc($cek_ortu);
            if(strcasecmp($row_ortu['nama'],$nama)!==0){
                $log_gagal[]=['baris'=>$i+2,'nama_balita'=>$nama_balita,'alasan'=>"NIK $nik sudah ada dengan nama berbeda",'id_puskesmas'=>$id_puskesmas_user,'nama_puskesmas'=>$nama_puskesmas_user];
                continue;
            }
            $id_orang_tua = $row_ortu['id_orang_tua'];
        }else{
            mysqli_query($koneksi,"INSERT INTO orang_tua (nik,nama,alamat,no_hp) VALUES ('$nik','$nama','$alamat','$no_hp')");
            $id_orang_tua = mysqli_insert_id($koneksi);
        }

        // --- Cek & simpan balita ---
        $cek_balita = mysqli_query($koneksi,"SELECT id_balita FROM balita WHERE nama_balita='$nama_balita' AND nik='$nik'");
        if(mysqli_num_rows($cek_balita)>0){
            $row_balita = mysqli_fetch_assoc($cek_balita);
            $id_balita = $row_balita['id_balita'];
        }else{
            mysqli_query($koneksi,"INSERT INTO balita (nik,nama,nama_balita,tanggal_lahir,jenis_kelamin_balita) VALUES ('$nik','$nama','$nama_balita','$tgl_lahir','$jk')");
            $id_balita = mysqli_insert_id($koneksi);
        }

        // --- Cek duplikasi pengukuran ---
        $cek_sql="SELECT COUNT(*) AS jumlah FROM pengukuran_raw WHERE id_balita='$id_balita' AND tanggal_pengukuran='$tgl_ukur'";
        $cek_result=mysqli_query($koneksi,$cek_sql);
        $cek=mysqli_fetch_assoc($cek_result);
        if($cek['jumlah']>0){
            $log_gagal[]=['baris'=>$i+2,'nama_balita'=>$nama_balita,'alasan'=>"Duplikasi pengukuran",'id_puskesmas'=>$id_puskesmas_user,'nama_puskesmas'=>$nama_puskesmas_user];
            continue;
        }

        // --- Insert pengukuran ---
        $query_pengukuran="INSERT INTO pengukuran_raw 
        (id_balita,id_user,tanggal_pengukuran,umur_dalam_bulan,berat,tinggi,bbu,tbu,bbtb,
        id_puskesmas,id_kelurahan,id_posyandu,batch_id,level_stunting,skor_prediksi,saran,tanggal_prediksi)
        VALUES 
        ('$id_balita','$id_user','$tgl_ukur','$umur_bulan','$bb','$tb','$bb_u','$tb_u','$bb_tb',
        '$id_puskesmas','$id_kelurahan','$id_posyandu','$batch_id','$level','$prediksi','$saran_gizi',NOW())";
        mysqli_query($koneksi,$query_pengukuran);
        $id_pengukuran = mysqli_insert_id($koneksi);


        $progress=60+round(($i+1)/$total_sukses*40);
        echo "data: ".json_encode(['progress'=>$progress,'message'=>"Menyimpan ke DB... ".($i+1)." dari $total_sukses"])."\n\n";
        flush();
    }

    mysqli_commit($koneksi);
    unset($_SESSION['data_csv_valid']);

    $jumlah_gagal=count($log_gagal);
    if($jumlah_gagal>0){
        $fp=fopen("log_gagal.csv","w");
        fputcsv($fp,['Baris','Nama Balita','Alasan','ID Puskesmas','Nama Puskesmas']);
        foreach($log_gagal as $g) fputcsv($fp,[$g['baris'],$g['nama_balita'],$g['alasan'],$g['id_puskesmas'],$g['nama_puskesmas']]);
        fclose($fp);

        echo "data: ".json_encode([
            'done'=>true,
            'message'=>"⚠️ $jumlah_gagal data gagal. Lihat log_gagal.csv",
            'redirect'=>'log_gagal.php',
            'batch_id'=>$batch_id,
            'gagal'=>$jumlah_gagal
        ])."\n\n";
    }else{
        echo "data: ".json_encode([
            'done'=>true,
            'message'=>"✅ Semua data berhasil disimpan",
            'redirect'=>'hasil_analisis.php?batch='.$batch_id,
            'batch_id'=>$batch_id,
            'gagal'=>0
        ])."\n\n";
    }
    flush();

}catch(Exception $e){
    mysqli_rollback($koneksi);
    echo "data: ".json_encode(['done'=>true,'message'=>"❌ Error: ".$e->getMessage()])."\n\n";
    flush();
}
?>
