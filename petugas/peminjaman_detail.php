<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('petugas');

 $db = new Database();
 $conn = $db->getConnection();

// Get peminjaman ID
 $id = isset($_GET['id']) ? $_GET['id'] : 0;

// Validate ID
if (!is_numeric($id) || $id <= 0) {
    $_SESSION['error'] = "ID peminjaman tidak valid!";
    header('Location: peminjaman.php');
    exit();
}

// Get peminjaman data
 $query = "SELECT p.*, u.nama as nama_user, u.username, a.nama_alat, a.kondisi as kondisi_alat 
          FROM peminjaman p 
          JOIN users u ON p.user_id = u.id 
          JOIN alat a ON p.alat_id = a.id 
          WHERE p.id = ?";
 $stmt = $conn->prepare($query);
 $stmt->bind_param("i", $id);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Data peminjaman tidak ditemukan!";
    header('Location: peminjaman.php');
    exit();
}

 $peminjaman = $result->fetch_assoc();

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $keterangan = $_POST['keterangan'] ?? '';
    
    if ($action === 'approve') {
        $status = 'disetujui';
        // Update stock
        $update_stock = "UPDATE alat SET stok = stok - ? WHERE id = ?";
        $stmt_stock = $conn->prepare($update_stock);
        $stmt_stock->bind_param("ii", $peminjaman['jumlah'], $peminjaman['alat_id']);
        $stmt_stock->execute();
    } elseif ($action === 'reject') {
        $status = 'ditolak';
    }
    
    $update_query = "UPDATE peminjaman SET status = ?, keterangan = ? WHERE id = ?";
    $stmt_update = $conn->prepare($update_query);
    $stmt_update->bind_param("ssi", $status, $keterangan, $id);
    
    if ($stmt_update->execute()) {
        $_SESSION['success'] = "Peminjaman berhasil " . ($action === 'approve' ? 'disetujui' : 'ditolak') . "!";
        header('Location: peminjaman.php');
        exit();
    } else {
        $_SESSION['error'] = "Gagal memperbarui status peminjaman!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #06ffa5;
            --info-color: #00b4d8;
            --warning-color: #ffbe0b;
            --danger-color: #fb5607;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 300;
            background-color: #f5f7fb;
            color: #333;
            margin: 0;
            padding: 0;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background-color: white;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: all 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .sidebar-header {
            padding: 20px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            align-items: center;
        }

        .sidebar-logo {
            font-size: 1.25rem;
            font-weight: 500;
            color: var(--primary-color);
            margin-left: 10px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            color: #6c757d;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .menu-item:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }

        .menu-item.active {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border-left: 3px solid var(--primary-color);
        }

        .menu-icon {
            margin-right: 10px;
            flex-shrink: 0;
        }

        .menu-text {
            font-weight: 300;
        }

        .dropdown-menu-custom {
            background-color: transparent;
            border: none;
            padding-left: 35px;
        }

        .dropdown-menu-custom .menu-item {
            font-size: 0.9rem;
            padding: 8px 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: all 0.3s ease;
            padding: 20px;
        }

        .main-content::-webkit-scrollbar {
            width: 4px;
        }

        .main-content::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .main-content::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }

        .main-content::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        .top-header {
            background-color: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 300;
            margin: 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }

        /* Cards */
        .detail-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .detail-card-header {
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .detail-card-header h5 {
            font-weight: 400;
            margin: 0;
            color: #333;
        }

        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            flex: 0 0 150px;
            font-weight: 400;
            color: #6c757d;
        }

        .detail-value {
            flex: 1;
            color: #333;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 400;
        }

        /* Buttons */
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 300;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-success-custom {
            background-color: var(--success-color);
            color: #333;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(6, 255, 165, 0.3);
        }

        .btn-danger-custom {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(251, 86, 7, 0.3);
        }

        .btn-secondary-custom {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary-custom:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        /* Forms */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #eaeaea;
            padding: 10px 15px;
            font-weight: 300;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.1);
        }

        /* Mobile Responsiveness */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: block;
            }

            .detail-label {
                flex: 0 0 100px;
            }
        }

        .menu-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
        }

        .menu-item[aria-expanded="true"] .menu-arrow {
            transform: rotate(180deg);
        }
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
            <!-- Transaksi Dropdown -->
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#transaksiDropdown" aria-expanded="true">
                <i data-lucide="arrow-right-left" class="menu-icon"></i>
                <span class="menu-text">Transaksi</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse dropdown-menu-custom show" id="transaksiDropdown">
                <a href="peminjaman.php" class="menu-item active">
                    <span class="menu-text">Data Peminjaman</span>
                </a>
                <a href="pengembalian.php" class="menu-item">
                    <span class="menu-text">Data Pengembalian</span>
                </a>
            </div>
            <!-- Laporan Dropdown -->
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#laporanDropdown" aria-expanded="false">
                <i data-lucide="file-text" class="menu-icon"></i>
                <span class="menu-text">Laporan</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse dropdown-menu-custom" id="laporanDropdown">
                <a href="laporan_peminjaman.php" class="menu-item">
                    <span class="menu-text">Laporan Peminjaman</span>
                </a>
                <a href="laporan_pengembalian.php" class="menu-item">
                    <span class="menu-text">Laporan Pengembalian</span>
                </a>
            </div>
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
                <h1 class="page-title">Detail Peminjaman</h1>
            </div>
            <div class="user-profile">
                <span style="margin-right: 10px;">Selamat datang, <?php echo $_SESSION['nama']; ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Detail Card -->
        <div class="row">
            <div class="col-md-8">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h5>Informasi Peminjaman</h5>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">ID Peminjaman:</div>
                        <div class="detail-value">#<?php echo str_pad($peminjaman['id'], 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Peminjam:</div>
                        <div class="detail-value"><?php echo $peminjaman['nama_user']; ?> (<?php echo $peminjaman['username']; ?>)</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Alat:</div>
                        <div class="detail-value"><?php echo $peminjaman['nama_alat']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Tanggal Pinjam:</div>
                        <div class="detail-value"><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Tanggal Kembali:</div>
                        <div class="detail-value"><?php echo $peminjaman['tanggal_kembali'] ? format_tanggal($peminjaman['tanggal_kembali']) : 'Belum dikembalikan'; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Jumlah:</div>
                        <div class="detail-value"><?php echo $peminjaman['jumlah']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value">
                            <span class="badge bg-<?php 
                                echo $peminjaman['status'] == 'disetujui' ? 'success' : 
                                    ($peminjaman['status'] == 'ditolak' ? 'danger' : 
                                    ($peminjaman['status'] == 'dipinjam' ? 'primary' : 
                                    ($peminjaman['status'] == 'dikembalikan' ? 'info' : 'warning'))); 
                            ?>">
                                <?php echo ucfirst($peminjaman['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($peminjaman['keterangan']): ?>
                    <div class="detail-row">
                        <div class="detail-label">Keterangan:</div>
                        <div class="detail-value"><?php echo $peminjaman['keterangan']; ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <div class="detail-label">Tanggal Dibuat:</div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?></div>
                    </div>
                    <div style="margin-top: 20px;">
                        <a href="peminjaman.php" class="btn-custom btn-secondary-custom">
                            <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                            Kembali
                        </a>
                    </div>
                </div>
                
                <?php if ($peminjaman['status'] == 'pending'): ?>
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h5>Proses Persetujuan</h5>
                    </div>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Tambahkan keterangan (opsional)"></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="approve" class="btn-custom btn-success-custom">
                                <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                                Setujui
                            </button>
                            <button type="submit" name="action" value="reject" class="btn-custom btn-danger-custom">
                                <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                                Tolak
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <div class="detail-card">
                    <div class="detail-card-header">
                        <h5>Informasi Alat</h5>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Nama Alat:</div>
                        <div class="detail-value"><?php echo $peminjaman['nama_alat']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Kondisi Awal:</div>
                        <div class="detail-value">
                            <span class="badge bg-<?php echo $peminjaman['kondisi_alat'] == 'baik' ? 'success' : ($peminjaman['kondisi_alat'] == 'rusak_ringan' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($peminjaman['kondisi_alat']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Handle dropdown arrows rotation
        document.querySelectorAll('.dropdown-toggle').forEach(item => {
            item.addEventListener('click', function() {
                const expanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !expanded);

                // Reinitialize Lucide icons to update arrow rotation
                setTimeout(() => {
                    lucide.createIcons();
                }, 10);
            });
        });

        // Reinitialize Lucide icons after DOM changes
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>