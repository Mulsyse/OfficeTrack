<?php
session_start();

// Jika pengguna sudah login, tapi mencoba akses halaman yang tidak boleh,
// lebih baik arahkan mereka ke dashboard yang sesuai.
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'];
    
    // --- PERUBAHAN: Gunakan switch untuk logika yang lebih jelas dan aman ---
    switch ($role) {
        case 'admin':
            header('Location: ../admin/dashboard.php');
            break;
        case 'petugas':
            header('Location: ../petugas/dashboard.php');
            break;
        case 'peminjam':
            header('Location: ../peminjam/dashboard.php');
            break;
        default:
            // Jika role tidak dikenal, arahkan ke login
            header('Location: login.php');
            break;
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="height: 100vh;">
        <div class="card text-center shadow" style="width: 400px;">
            <div class="card-body">
                <h1 class="card-title text-danger">403</h1>
                <h5 class="card-title">Akses Ditolak</h5>
                <p class="card-text">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href="../auth/login.php" class="btn btn-primary">Kembali ke Login</a>
            </div>
        </div>
    </div>
</body>
</html>