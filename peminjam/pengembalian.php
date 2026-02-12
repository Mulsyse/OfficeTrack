<?php
session_start();
require_once '../config/database.php';

// Pastikan hanya peminjam yang bisa akses
check_login();
check_role('peminjam');

 $db = new Database();
 $conn = $db->getConnection();

// Proses pengajuan pengembalian
if (isset($_POST['submit_pengajuan'])) {
    $peminjaman_id = $_POST['peminjaman_id'];
    $current_user_id = $_SESSION['user_id'];

    // Keamanan: Pastikan peminjaman ini milik user yang sedang login
    $stmt = $conn->prepare("UPDATE peminjaman SET status = 'menunggu_konfirmasi' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $peminjaman_id, $current_user_id);
    
    if ($stmt->execute()) {
        header('Location: pengembalian.php?success=1');
        exit();
    } else {
        $error = "Gagal mengajukan pengembalian. Silakan coba lagi.";
    }
}

// --- PERBAIKAN QUERY ---
// Query untuk menampilkan peminjaman aktif milik user yang login
// Kita ambil tanggal_kembali untuk menghitung durasi
 $stmt = $conn->prepare("
    SELECT
        p.id,
        p.tanggal_pinjam,
        p.tanggal_kembali, -- Ambil tanggal kembali
        a.nama_alat,
        p.jumlah
    FROM
        peminjaman p
    JOIN
        alat a ON p.alat_id = a.id
    WHERE
        p.user_id = ? AND p.status = 'disetujui'
    ORDER BY
        p.tanggal_pinjam DESC
");
 $stmt->bind_param("i", $_SESSION['user_id']);
 $stmt->execute();
 $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pengembalian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --primary-color: #4361ee; --secondary-color: #3f37c9; --success-color: #06ffa5; --info-color: #00b4d8; --warning-color: #ffbe0b; --danger-color: #fb5607; --light-color: #f8f9fa; --dark-color: #212529; --sidebar-width: 250px; }
        * { box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 300; background-color: #f5f7fb; color: #333; margin: 0; padding: 0; }
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width); background-color: white; box-shadow: 0 0 20px rgba(0, 0, 0, 0.05); z-index: 1000; transition: all 0.3s ease; overflow-y: auto; overflow-x: hidden; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #eaeaea; display: flex; align-items: center; }
        .sidebar-logo { font-size: 1.25rem; font-weight: 500; color: var(--primary-color); margin-left: 10px; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; color: #6c757d; text-decoration: none; transition: all 0.2s ease; cursor: pointer; }
        .menu-item:hover { background-color: #f8f9fa; color: var(--primary-color); }
        .menu-item.active { background-color: rgba(67, 97, 238, 0.1); color: var(--primary-color); border-left: 3px solid var(--primary-color); }
        .menu-icon { margin-right: 10px; flex-shrink: 0; }
        .menu-text { font-weight: 300; }
        .main-content { margin-left: var(--sidebar-width); height: 100vh; overflow-y: auto; overflow-x: hidden; transition: all 0.3s ease; padding: 20px; }
        .top-header { background-color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: 300; margin: 0; }
        .user-profile { display: flex; align-items: center; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; margin-right: 10px; }
        .activity-card { background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 25px; }
        .card-header-custom { padding: 20px 25px; border-bottom: 1px solid #eaeaea; font-weight: 400; font-size: 1.1rem; }
        .table-custom { margin-bottom: 0; width: 100%; }
        .table-custom thead th { border-bottom: 1px solid #eaeaea; font-weight: 400; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px 25px; white-space: nowrap; }
        .table-custom tbody td { padding: 15px 25px; vertical-align: middle; border-top: 1px solid #f1f1f1; }
        .table-custom tbody tr:hover { background-color: #f8f9fa; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; color: var(--primary-color); }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } .mobile-menu-btn { display: block; } .table-custom { font-size: 0.85rem; } .table-custom thead th, .table-custom tbody td { padding: 10px 15px; } }
        .badge { padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 400; }
        .btn-custom { padding: 8px 16px; border-radius: 8px; font-weight: 400; font-size: 0.9rem; border: none; transition: all 0.2s; display: inline-flex; align-items: center; }
        .btn-primary-custom { background-color: var(--primary-color); color: white; }
        .btn-primary-custom:hover { background-color: var(--secondary-color); color: white; }
        .btn-sm-custom { padding: 5px 12px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i data-lucide="layers"></i>
            <span class="sidebar-logo">Sistem Peminjaman</span>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i data-lucide="home" class="menu-icon"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="daftar_alat.php" class="menu-item">
                <i data-lucide="package" class="menu-icon"></i>
                <span class="menu-text">Daftar Alat</span>
            </a>
            <a href="pinjam_alat.php" class="menu-item">
                <i data-lucide="plus-circle" class="menu-icon"></i>
                <span class="menu-text">Ajukan Peminjaman</span>
            </a>
            <a href="peminjaman_saya.php" class="menu-item">
                <i data-lucide="clipboard-list" class="menu-icon"></i>
                <span class="menu-text">Peminjaman Saya</span>
            </a>
            <a href="pengembalian.php" class="menu-item active">
                <i data-lucide="rotate-ccw" class="menu-icon"></i>
                <span class="menu-text">Pengembalian</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Header -->
        <div class="top-header">
            <div style="display: flex; align-items: center;">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i data-lucide="menu"></i>
                </button>
                <h1 class="page-title">Ajukan Pengembalian</h1>
            </div>
            <div class="user-profile">
                <span style="margin-right: 10px;">Selamat datang, <?php echo $_SESSION['nama']; ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i data-lucide="check-circle" style="width: 16px; height: 16px; margin-right: 8px;"></i>
                Pengajuan pengembalian berhasil dikirim! Silakan tunggu konfirmasi dari petugas.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i data-lucide="alert-circle" style="width: 16px; height: 16px; margin-right: 8px;"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Table -->
        <div class="activity-card">
            <div class="card-header-custom">
                <h5 class="mb-0">Barang Yang Sedang Dipinjam</h5>
                <span class="text-muted">Klik tombol untuk mengajukan pengembalian</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Alat</th>
                                <th>Jumlah</th>
                                <th>Tgl. Pinjam</th>
                                <th>Durasi</th>
                                <th>Tgl. Kembali</th> <!-- Header diubah -->
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php $no = 1; while ($peminjaman = $result->fetch_assoc()): ?>
                                    <?php
                                    // --- PERBAIKAN LOGIKA PHP ---
                                    // Hitung durasi di sini menggunakan objek DateTime
                                    $date_pinjam = new DateTime($peminjaman['tanggal_pinjam']);
                                    $date_kembali = new DateTime($peminjaman['tanggal_kembali']); // Gunakan tanggal_kembali
                                    $durasi = $date_kembali->diff($date_pinjam)->days;
                                    ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $peminjaman['nama_alat']; ?></td>
                                    <td><?php echo $peminjaman['jumlah']; ?></td>
                                    <td><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></td>
                                    <td><?php echo $durasi; ?> Hari</td>
                                    <td><?php echo format_tanggal($peminjaman['tanggal_kembali']); ?></td> <!-- Data diubah -->
                                    <td>
                                        <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin mengajukan pengembalian untuk alat ini?');">
                                            <input type="hidden" name="peminjaman_id" value="<?php echo $peminjaman['id']; ?>">
                                            <button type="submit" name="submit_pengajuan" class="btn-custom btn-primary-custom btn-sm-custom">
                                                <i data-lucide="send" style="width: 14px; height: 14px; margin-right: 4px;"></i>
                                                Ajukan Pengembalian
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Anda tidak memiliki barang yang sedang dipinjam.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>