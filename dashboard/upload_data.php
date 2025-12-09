<?php
require_once 'auth.php';
require_once '../koneksi.php';
checkRole(['user','supervisor']);

// Ambil ID Puskesmas dari session
$id_puskesmas = $_SESSION['id_puskesmas'] ?? '';
$kelurahan_list = [];
$nama_puskesmas = '';

if($id_puskesmas){
    // Nama puskesmas
    $query_puskesmas = "SELECT nama_puskesmas FROM puskesmas WHERE id_puskesmas = '$id_puskesmas'";
    $result_p = mysqli_query($koneksi, $query_puskesmas);
    if($row = mysqli_fetch_assoc($result_p)){
        $nama_puskesmas = $row['nama_puskesmas'];
    }

    // Daftar kelurahan
    $query_kel = "SELECT id_kelurahan, nama_kelurahan FROM kelurahan WHERE id_puskesmas = '$id_puskesmas' ORDER BY nama_kelurahan ASC";
    $result_k = mysqli_query($koneksi, $query_kel);
    while($row = mysqli_fetch_assoc($result_k)){
        $kelurahan_list[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Tambah Data Balita</title>

    <!-- Custom fonts for this template-->
    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="../assets/css/sb-admin-2.css" rel="stylesheet">

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">

        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
        <!-- End of Sidebar -->

        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

                <!-- Topbar -->
                <?php include 'header.php'; ?>
                <!-- End of Topbar -->

                <!-- Begin Page Content -->
                <div class="container-fluid">

                    <!-- Page Heading -->
                    <h1 class="h3 mb-4 text-gray-800">Tambah Data Balita & Pengukuran</h1>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Project Card Example -->
                        <div class="col-lg-10">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-primary">
                                        Form Tambah Data Orang Tua, Balita, dan Pengukuran
                                    </h6>
                                    <a href="list_data.php" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </a>
                                </div>

                                <div class="card-body">
                                    <form id="formSimpan" action="proses_upload.php" method="POST">
                                        <div class="row">
                                            <!-- Data Orang Tua -->
                                            <div class="col-md-6">
                                                <h5 class="text-primary font-weight-bold mb-3">Data Orang Tua</h5>
                                                <div class="form-group">
                                                    <label>NIK <span class="text-danger">*</span>:</label>
                                                    <input type="text" name="nik" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Nama Orang Tua <span class="text-danger">*</span>:</label>
                                                    <input type="text" name="nama" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Alamat <span class="text-danger">*</span>:</label>
                                                    <textarea name="alamat" class="form-control" rows="2" required></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>No. Telepon:</label>
                                                    <input type="text" name="no_telepon" class="form-control" >
                                                </div>
                                            </div>

                                            <!-- Data Balita -->
                                            <div class="col-md-6">
                                                <h5 class="text-primary font-weight-bold mb-3">Data Balita</h5>
                                                <div class="form-group">
                                                    <label>Nama Balita <span class="text-danger">*</span>:</label>
                                                    <input type="text" name="nama_balita" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Jenis Kelamin <span class="text-danger">*</span>:</label>
                                                    <select name="jenis_kelamin_balita" class="form-control" required>
                                                        <option value="">-- Pilih --</option>
                                                        <option value="Laki-laki">Laki-laki</option>
                                                        <option value="Perempuan">Perempuan</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Tanggal Lahir <span class="text-danger">*</span>:</label>
                                                    <input type="date" name="tanggal_lahir" class="form-control" required>
                                                </div>
                                            </div>
                                        </div>

                                        <hr>

                                        <!-- Data Pengukuran -->
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5 class="text-primary font-weight-bold mb-3">Data Pengukuran</h5>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Tanggal Pengukuran <span class="text-danger">*</span>:</label>
                                                    <input type="date" name="tanggal_pengukuran" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Berat Badan (Kg) <span class="text-danger">*</span>:</label>
                                                    <input type="number" step="0.1" name="berat_badan" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Tinggi Badan (cm) <span class="text-danger">*</span>:</label>
                                                    <input type="number" step="0.1" name="tinggi_badan" class="form-control" required>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Desa/Kelurahan <span class="text-danger">*</span>:</label>
                                                    <select name="kelurahan" id="kelurahan" class="form-control" required>
                                                        <option value="">-- Pilih Kelurahan --</option>
                                                        <?php foreach($kelurahan_list as $kel) : ?>
                                                            <option value="<?= $kel['id_kelurahan'] ?>"><?= $kel['nama_kelurahan'] ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label>Posyandu:</label>
                                                    <select name="posyandu" id="posyandu" class="form-control">
                                                        <option value="">-- Pilih Posyandu (boleh kosong) --</option>
                                                    </select>
                                                </div>

                                                <div class="form-group">
                                                    <label>Puskesmas: </label>
                                                    <input type="hidden" name="id_puskesmas" value="<?= $id_puskesmas ?>">
                                                    <input type="text" name="puskesmas" id="puskesmas" class="form-control" value="<?= $nama_puskesmas ?>" readonly>
                                                </div>

                                                <div class="form-group">
                                                    <label>Kecamatan:</label>
                                                    <input type="text" name="kecamatan" id="kecamatan" class="form-control" readonly>
                                                </div>
                                                
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <button 
                                                type="button" 
                                                class="btn btn-success btn-icon-split" 
                                                data-toggle="modal" 
                                                data-target="#konfirmasiSimpanModal"
                                                >
                                                <span class="icon text-white-50">
                                                    <i class="fas fa-save"></i> 
                                                </span>
                                                <span class="text">Simpan Data</span>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->

            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <?php include 'footer.php'; ?>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <div class="modal fade" id="konfirmasiSimpanModal" tabindex="-1" aria-labelledby="konfirmasiSimpanModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="konfirmasiSimpanModalLabel">Konfirmasi Penyimpanan Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menyimpan data yang sudah divalidasi ini ke database? Aksi ini tidak dapat dibatalkan.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnKonfirmasiSimpan">Ya, Simpan Sekarang</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Logout Modal-->
    <?php include 'logout_alert.php'; ?>

    <!-- Bootstrap core JavaScript-->
    <script src="../assets/vendor/jquery/jquery.js"></script>
    <script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="../assets/vendor/jquery-easing/jquery.easing.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="../assets/js/sb-admin-2.js"></script>

    <!-- JQuery untuk posyandu dan kelurahan -->
   <script>
    $(document).ready(function() {
        $('#kelurahan').change(function() {
            var id_kelurahan = $(this).val(); // gunakan id_kelurahan

            if(id_kelurahan){
                $.ajax({
                    url: 'ajax/get_posyandu_kecamatan.php',
                    type: 'GET',
                    data: { id_kelurahan: id_kelurahan }, // kirim id_kelurahan
                    dataType: 'json',
                    success: function(data){
                        // Isi dropdown posyandu
                        var options = '<option value="">-- Pilih Posyandu (boleh kosong) --</option>';
                        $.each(data.posyandu, function(i, p){
                            options += '<option value="'+p.id_posyandu+'">'+p.nama_posyandu+'</option>';
                        });
                        $('#posyandu').html(options);

                        // Isi kecamatan
                        $('#kecamatan').val(data.kecamatan);
                    }
                });
            } else {
                $('#posyandu').html('<option value="">-- Pilih Posyandu (boleh kosong) --</option>');
                $('#kecamatan').val('');
            }
        });
    });
    // Tambahkan kode jQuery untuk submit form setelah modal konfirmasi:
    $('#btnKonfirmasiSimpan').on('click', function() {
        // Sembunyikan modal menggunakan jQuery
        $('#konfirmasiSimpanModal').modal('hide'); 
        
        // Jalankan submit form
        $('#formSimpan').submit();
    });
    </script>


</body>
</html>
