<?php
require_once 'auth.php';
include '../koneksi.php';
checkRole(['admin', 'user','supervisor']);

$id_user = (int)$_SESSION['id_user'];
$stmt = $koneksi->prepare("SELECT username, full_name, email, id_puskesmas, foto, role FROM users WHERE id_user = ?");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil</title>
    <link href="../assets/vendor/fontawesome-free/css/all.css" rel="stylesheet">
    <link href="../assets/css/sb-admin-2.css" rel="stylesheet">
    <style>
        .input-with-icon {
            position: relative;
            display: flex;
            align-items: center; /* vertikal center */
        }

        .input-with-icon input.form-control {
            flex: 1;
            padding-right: 40px;
        }

        .input-with-icon .toggle-password {
            position: absolute;
            right: 10px;
            cursor: pointer;
            color: #6c757d;
            font-size: 1rem;
        }
    </style>
</head>
<body id="page-top">

<div id="wrapper">
    <?php 
    $activePage = basename($_SERVER['PHP_SELF']); 
    include 'sidebar.php'; 
    ?>

    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <div class="container-fluid mt-5">
                <div class="card shadow">
                    <div class="card-header">
                        <h5 class="m-0 font-weight-bold text-primary">Edit Profil</h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?= !empty($user['foto']) ? htmlspecialchars($user['foto']) : '../assets/img/default_profile.svg' ?>" 
                             width="150" height="150" class="rounded-circle mb-3" style="object-fit:cover;">

                        <form action="proses_edit_profil.php" method="POST" enctype="multipart/form-data" id="editProfilForm">
                            <input type="hidden" name="id_user" value="<?= $id_user ?>">

                            <div class="form-group">
                                <label>Nama</label>
                                <input type="text" name="nama" class="form-control text-center" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control text-center" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Puskesmas</label>
                                <input type="text" name="puskesmas" class="form-control text-center" 
                                       value="<?= htmlspecialchars($user['id_puskesmas']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control text-center" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Role</label>
                                <input type="text" name="role" class="form-control text-center" 
                                       value="<?= htmlspecialchars($user['role']) ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Ganti Foto Profil</label>
                                <input type="file" name="foto" class="form-control-file" accept="image/*" id="fotoInput">
                                <small class="text-muted">Hanya file JPG, JPEG, PNG (maks 2 MB)</small>
                            </div>

                            <hr>
                            <h6 class="text-left">Ganti Password</h6>

                            <div class="form-group input-with-icon">
                                <label>Password Lama :</label>
                                <input type="password" name="password_lama" class="form-control" placeholder="Isi jika ingin ganti password">
                                <i class="fas fa-eye toggle-password"></i>
                            </div>

                            <div class="form-group input-with-icon">
                                <label>Password Baru :</label>
                                <input type="password" name="password_baru" class="form-control" placeholder="Minimal 6 karakter">
                                <i class="fas fa-eye toggle-password"></i>
                            </div>

                            <div class="form-group input-with-icon">
                                <label>Ulangi Password Baru :</label>
                                <input type="password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password baru">
                                <i class="fas fa-eye toggle-password"></i>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>

                    <div class="card-footer text-center">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'logout_alert.php'; ?>
<script src="../assets/vendor/jquery/jquery.js"></script>
<script src="../assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
<script src="../assets/js/sb-admin-2.js"></script>
<script>
$(document).ready(function() {
    var current = "<?= $activePage ?>";
    $('.nav-item a').each(function() {
        if($(this).attr('href') === current) {
            $(this).addClass('active');
            $(this).closest('.collapse').addClass('show');
        }
    });

    // Toggle password
    document.querySelectorAll('.toggle-password').forEach(function(icon) {
        icon.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.add('fa-eye-slash');
                this.classList.remove('fa-eye');
            } else {
                input.type = 'password';
                this.classList.add('fa-eye');
                this.classList.remove('fa-eye-slash');
            }
        });
    });

    // Validasi ukuran foto & panjang password
    $('#editProfilForm').on('submit', function(e) {
        var fileInput = $('#fotoInput')[0];
        if(fileInput.files.length > 0){
            if(fileInput.files[0].size > 2*1024*1024){
                alert('❌ Ukuran file terlalu besar, maksimal 2 MB.');
                e.preventDefault();
                return false;
            }
        }

        var pwBaru = $('input[name="password_baru"]').val();
        if(pwBaru && pwBaru.length < 6){
            alert('❌ Password baru minimal 6 karakter.');
            e.preventDefault();
            return false;
        }
    });
});
</script>
</body>
</html>
