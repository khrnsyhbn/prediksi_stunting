<?php
$log_file = __DIR__ . '/log_gagal.csv';

if (file_exists($log_file)) {
    // Kosongkan isi file tapi tetap pertahankan header
    $handle = fopen($log_file, 'w');
    if ($handle) {
        fputcsv($handle, ['Baris', 'Nama Balita', 'Alasan']); // header default
        fclose($handle);
        echo "<script>
            alert('✅ Log gagal berhasil dihapus.');
            window.location.href = 'log_gagal.php';
        </script>";
    } else {
        echo "<script>
            alert('❌ Gagal menghapus log.');
            window.location.href = 'log_gagal.php';
        </script>";
    }
} else {
    echo "<script>
        alert('🚫 File log tidak ditemukan.');
        window.location.href = 'log_gagal.php';
    </script>";
}
?>
