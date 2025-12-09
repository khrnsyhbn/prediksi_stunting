<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Login - Prediksi Stunting</title>

    <!-- SB Admin 2 CSS -->
    <link href="assets/vendor/fontawesome-free/css/all.css" rel="stylesheet" type="text/css">
    <link href="assets/css/sb-admin-2.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,700" rel="stylesheet">
</head>

<body class="bg-gradient-primary">

    <div class="container">

        <!-- Outer Row -->
        <div class="row justify-content-center">

            <div class="col-xl-5 col-lg-6 col-md-8">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">

                        <div class="p-5">
                            <div class="text-center mb-4">
                                <h1 class="h4 text-gray-900">Selamat Datang 👋</h1>
                            </div>

                            <!-- Pesan Error & Success -->
                            <?php if (isset($_GET['message'])) : ?>
                                <div class="alert alert-success">
                                    <?= htmlspecialchars($_GET['message']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (isset($_GET['error'])) : ?>
                                <div class="alert alert-danger">
                                    <?= htmlspecialchars($_GET['error']); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Form Login -->
                            <form class="user" action="proses_login.php" method="POST">
                                <div class="form-group">
                                    <input type="text"
                                           class="form-control form-control-user"
                                           name="username"
                                           placeholder="Masukkan Username..."
                                           autocomplete="username"
                                           >
                                </div>

                                <div class="form-group position-relative">
                                    <input type="password"
                                           class="form-control form-control-user"
                                           id="password"
                                           name="password"
                                           placeholder="Masukkan Password"
                                           autocomplete="current-password"
                                           >
                                    <span class="position-absolute"
                                          style="top: 50%; right: 15px; transform: translateY(-50%); cursor:pointer;">
                                        <i class="fas fa-eye text-gray-500" id="togglePassword"></i>
                                    </span>
                                </div>

                                <button type="submit"
                                        class="btn btn-primary btn-user btn-block mb-3">
                                    Login
                                </button>
                            </form>

                            <!-- Tombol Logout jika user sudah login -->
                            <?php if (isset($_GET['error']) && strpos($_GET['error'], 'Anda sudah login') !== false): ?>
                                <a href="logout.php" class="btn btn-danger btn-block mt-4">
                                    Logout Sekarang
                                </a>
                            <?php endif; ?>

                            <hr>

                        </div>

                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- JS Files -->
    <script src="assets/vendor/jquery/jquery.js"></script>
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.js"></script>
    <script src="assets/vendor/jquery-easing/jquery.easing.js"></script>
    <script src="assets/js/sb-admin-2.js"></script>

    <!-- Toggle Password -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordInput = document.querySelector('#password');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.type === "password" ? "text" : "password";
            passwordInput.type = type;
            this.classList.toggle('fa-eye-slash');
        });
    </script>

</body>
</html>
