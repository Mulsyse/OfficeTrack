<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
check_login();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="text-danger">Akses Ditolak</h3>
                        <p class="text-muted">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                        <p class="text-muted">Role Anda: <strong><?php echo ucfirst($_SESSION['role']); ?></strong></p>
                        <a href="../auth/logout.php" class="btn btn-primary">Kembali ke Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
