<?php
// qc_action.php
require_once 'auth.php';
include '../koneksi.php';
checkLogin();

// Ambil info user
$id_puskesmas = $koneksi->real_escape_string($_SESSION['id_puskesmas'] ?? '');

// ACTION: periksa GET dulu, kalau tidak ada pakai POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';
// $action = strtolower($action);

// default redirect (kembali ke index)
$redirect = 'index.php';

// helper: redirect with message
function redirect_with($url, $params = []) {
    $qs = http_build_query($params);
    header("Location: {$url}" . ($qs ? "?{$qs}" : ''));
    exit;
}

// --- Ambil ID berdasarkan metode ---
$id_pengukuran = 0;
if ($action === 'approve') {
    $id_pengukuran = intval($_GET['id'] ?? 0);
} elseif ($action === 'delete') {
    // POST expected
    $id_pengukuran = intval($_POST['id_pengukuran'] ?? 0);
} elseif ($action === 'selesaiEdit') {
    // GET expected
    $id_pengukuran = intval($_GET['id'] ?? 0);
} elseif ($action === 'edit') {
    // GET expected
    $id_pengukuran = intval($_GET['id'] ?? 0);
} elseif ($action === 'return') {
    // POST expected
    $id_pengukuran = intval($_POST['return_id'] ?? 0);
} else {
    redirect_with($redirect, ['error' => 1, 'msg' => 'Aksi tidak valid']);
}

if ($id_pengukuran <= 0) {
    redirect_with($redirect, ['error' => 1, 'msg' => 'ID tidak valid']);
}

// cek kepemilikan + status pending
$sql = "SELECT status_qc, id_puskesmas FROM pengukuran_raw WHERE raw_id = {$id_pengukuran} LIMIT 1";
$res = mysqli_query($koneksi, $sql);
$row = mysqli_fetch_assoc($res);
if (!$row) {
    redirect_with($redirect, ['error' => 1, 'msg' => 'Data tidak ditemukan']); 
}
if ($row['id_puskesmas'] !== $id_puskesmas) {
    redirect_with($redirect, ['error' => 1, 'msg' => 'Data bukan milik puskesmas Anda']);
}
$allowed_status = ['pending', 'returned'];

if (!in_array($row['status_qc'], $allowed_status)) {
    redirect_with($redirect, ['error' => 1, 'msg' => 'Data sudah divalidasi sebelumnya']);
}


// proses sesuai action
if ($action === 'approve') {
    $status_baru = 'approved';
    $status_qc ='approve';
    $catatan = 'Data telah disetujui oleh supervisor';
} elseif ($action === 'selesaiEdit'){ // delete
    $status_baru = 'Deleted';
    $status_qc ='delete';
    $catatan = trim('Data telah selesai diedit/input ulang oleh user berdasarkan permintaan supervisor');
    // Query SELECT untuk mengambil catatan_supervisor
    $sql = mysqli_query($koneksi, "
        SELECT catatan_supervisor, supervisor_id
        FROM pengukuran_raw 
        WHERE raw_id = '$id_pengukuran' AND status_qc = 'returned'
    ");
    // Ambil hasil query
    $data = mysqli_fetch_assoc($sql);
    $id_supervisor = $data['supervisor_id'];

} elseif ($action === 'edit'){ // delete
    $status_baru = 'pending';
    $status_qc ='edit';
    
    // Ambil catatan supervisor dari database
    $catatan= '';

    // Query SELECT untuk mengambil catatan_supervisor
    $sql = mysqli_query($koneksi, "
        SELECT catatan_supervisor, supervisor_id
        FROM pengukuran_raw 
        WHERE raw_id = '$id_pengukuran' AND status_qc = 'pending'
    ");
    // Ambil hasil query
    $data = mysqli_fetch_assoc($sql);
    $id_supervisor = $data['supervisor_id'];

    // Pastikan data tidak null
    $catatan = trim($data['catatan_supervisor'] ?? '');

    if ($catatan === '') {
        redirect_with($redirect, ['error' => 1, 'msg' => 'Catatan supervisor wajib diisi']);
    }
    // escape
    $catatan = mysqli_real_escape_string($koneksi, $catatan);
} elseif ($action === 'return'){ // return
    $status_baru = 'returned';
    $status_qc ='return';
    $catatan = trim($_POST['catatan_return'] ?? '');
    if ($catatan === '') {
        redirect_with($redirect, ['error' => 1, 'msg' => 'Catatan supervisor wajib diisi']);
    }
    // escape
    $catatan = mysqli_real_escape_string($koneksi, $catatan);
} else {
    
    $status_baru = 'deleted';
    $status_qc ='delete';
    $catatan = trim($_POST['catatan_supervisor'] ?? '');
    if ($catatan === '') {
        redirect_with($redirect, ['error' => 1, 'msg' => 'Catatan supervisor wajib diisi']);
    }
    // escape
    $catatan = mysqli_real_escape_string($koneksi, $catatan);
}

// Update pengukuran_raw
$update_sql = "
    UPDATE pengukuran_raw
    SET status_qc = '{$status_baru}',
        supervisor_id = {$id_supervisor},
        catatan_supervisor = " . ($catatan === '' ? "NULL" : "'{$catatan}'") . ",
        updated_at = NOW()
    WHERE raw_id = {$id_pengukuran}
";
mysqli_query($koneksi, $update_sql);

// Insert ke qc_log (sesuaikan nama kolom tabel qc_log Anda)
$log_action = mysqli_real_escape_string($koneksi, $status_qc);
$log_reason = $catatan === '' ? '' : $catatan;
$insert_log = "
    INSERT INTO qc_log (id_pengukuran, supervisor_id, action, reason, `timestamp`)
    VALUES ({$id_pengukuran}, {$id_supervisor}, '{$log_action}', " . ($log_reason === '' ? "''" : "'{$log_reason}'") . ", NOW())
";
mysqli_query($koneksi, $insert_log);

// Redirect kembali dengan pesan sukses
$messages = [
    'approve' => 'Data disetujui',
    'selesaiEdit'    => 'Data berhasil diubah menjadi Deleted',
    'edit'    => 'Data berhasil diedit',
    'return' => 'Data dikembalikan'
];

// Ambil pesan berdasarkan action, kalau tidak ada → default
$msg = $messages[$action] ?? 'Data dihapus (status deleted)';

redirect_with($redirect, ['success' => 1, 'msg' => $msg]);

