<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('peminjam');

 $db = new Database();
 $conn = $db->getConnection();
 $user_id = $_SESSION['user_id'];

// Get user's peminjaman statistics
 $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ?");
 $stmt_total->bind_param("i", $user_id);
 $stmt_total->execute();
 $result_total = $stmt_total->get_result();
 $total_peminjaman = $result_total->fetch_assoc()['total'];

 $stmt_pending = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'pending'");
 $stmt_pending->bind_param("i", $user_id);
 $stmt_pending->execute();
 $result_pending = $stmt_pending->get_result();
 $total_pending = $result_pending->fetch_assoc()['total'];

 $stmt_disetujui = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status IN ('disetujui', 'dipinjam')");
 $stmt_disetujui->bind_param("i", $user_id);
 $stmt_disetujui->execute();
 $result_disetujui = $stmt_disetujui->get_result();
 $total_disetujui = $result_disetujui->fetch_assoc()['total'];

 $stmt_dikembalikan = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE user_id = ? AND status = 'dikembalikan'");
 $stmt_dikembalikan->bind_param("i", $user_id);
 $stmt_dikembalikan->execute();
 $result_dikembalikan = $stmt_dikembalikan->get_result();
 $total_dikembalikan = $result_dikembalikan->fetch_assoc()['total'];

// Get recent peminjaman
 $stmt_recent = $conn->prepare("SELECT p.*, a.nama_alat FROM peminjaman p JOIN alat a ON p.alat_id = a.id WHERE p.user_id = ? ORDER BY p.created_at DESC LIMIT 5");
 $stmt_recent->bind_param("i", $user_id);
 $stmt_recent->execute();
 $result_recent = $stmt_recent->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peminjam</title>
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

        body {
            overflow: hidden;
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-weight: 300;
            background-color: #f5f7fb;
            color: #333;
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
        }

        .menu-text {
            font-weight: 300;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s ease;
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

        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--primary-color);
        }

        .stat-card.primary::before { background: var(--primary-color); }
        .stat-card.warning::before { background: var(--warning-color); }
        .stat-card.success::before { background: var(--success-color); }
        .stat-card.info::before { background: var(--info-color); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            color: #6c757d;
            font-size: 0.9rem;
            font-weight: 300;
            margin: 0;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .stat-icon.warning {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .stat-icon.success {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .stat-icon.info {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--info-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 400;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-change {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Quick Actions Card */
        .quick-actions-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .quick-actions-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-title-custom {
            font-size: 1.1rem;
            font-weight: 400;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .card-title-custom i {
            margin-right: 10px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .btn-custom {
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 300;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
        }

        .btn-success-custom {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success-custom:hover {
            background-color: #05d693;
        }

        .btn-info-custom {
            background-color: var(--info-color);
            color: white;
        }

        .btn-info-custom:hover {
            background-color: #0096c7;
        }

        .btn-warning-custom {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning-custom:hover {
            background-color: #e6ac00;
        }

        /* Recent Transactions Card */
        .recent-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .recent-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header-custom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
        }

        .table-custom th {
            font-weight: 400;
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            padding: 12px;
            border-bottom: 1px solid #eaeaea;
        }

        .table-custom td {
            padding: 12px;
            border-bottom: 1px solid #f1f1f1;
            font-weight: 300;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 3px;
        }

        .badge-success {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .badge-warning {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .badge-danger {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        .badge-info {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--info-color);
        }

        .badge-primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            color: #adb5bd;
        }

        .empty-state-title {
            font-size: 1.1rem;
            font-weight: 400;
            color: #495057;
            margin-bottom: 10px;
        }

        .empty-state-text {
            color: #6c757d;
            font-weight: 300;
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

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
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
            <a href="dashboard.php" class="menu-item active">
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
            <a href="pengembalian.php" class="menu-item">
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
                <h1 class="page-title">Dashboard Peminjam</h1>
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

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-header">
                    <p class="stat-title">Total Peminjaman</p>
                    <div class="stat-icon primary">
                        <i data-lucide="handshake"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $total_peminjaman; ?></h3>
                <p class="stat-change">Semua peminjaman Anda</p>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <p class="stat-title">Menunggu Persetujuan</p>
                    <div class="stat-icon warning">
                        <i data-lucide="clock"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $total_pending; ?></h3>
                <p class="stat-change">Peminjaman pending</p>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <p class="stat-title">Disetujui/Dipinjam</p>
                    <div class="stat-icon success">
                        <i data-lucide="check-circle"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $total_disetujui; ?></h3>
                <p class="stat-change">Sedang dipinjam</p>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <p class="stat-title">Dikembalikan</p>
                    <div class="stat-icon info">
                        <i data-lucide="rotate-ccw"></i>
                    </div>
                </div>
                <h3 class="stat-value"><?php echo $total_dikembalikan; ?></h3>
                <p class="stat-change">Selesai dikembalikan</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-card">
            <h5 class="card-title-custom">
                <i data-lucide="zap"></i>
                Aksi Cepat
            </h5>
            <div class="action-buttons">
                <a href="daftar_alat.php" class="btn-custom btn-primary-custom">
                    <i data-lucide="package"></i>
                    Lihat Daftar Alat
                </a>
                <a href="pinjam_alat.php" class="btn-custom btn-success-custom">
                    <i data-lucide="plus-circle"></i>
                    Ajukan Peminjaman
                </a>
                <a href="peminjaman_saya.php" class="btn-custom btn-info-custom">
                    <i data-lucide="clipboard-list"></i>
                    Riwayat Peminjaman
                </a>
                <a href="pengembalian.php" class="btn-custom btn-warning-custom">
                    <i data-lucide="rotate-ccw"></i>
                    Ajukan Pengembalian
                </a>
            </div>
        </div>

        <!-- Recent Peminjaman -->
        <div class="recent-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i data-lucide="clock"></i>
                    Peminjaman Terbaru
                </h5>
                <a href="peminjaman_saya.php" class="btn-custom btn-primary-custom" style="padding: 8px 16px;">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                <?php if ($result_recent->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Alat</th>
                                    <th>Tanggal Pinjam</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($peminjaman = $result_recent->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $peminjaman['nama_alat']; ?></td>
                                    <td><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></td>
                                    <td><?php echo $peminjaman['jumlah']; ?></td>
                                    <td>
                                        <span class="badge-custom badge-<?php 
                                            echo $peminjaman['status'] == 'disetujui' ? 'success' : 
                                                ($peminjaman['status'] == 'ditolak' ? 'danger' : 
                                                ($peminjaman['status'] == 'dipinjam' ? 'primary' : 
                                                ($peminjaman['status'] == 'dikembalikan' ? 'info' : 'warning'))); 
                                        ?>">
                                            <?php echo ucfirst($peminjaman['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="peminjaman_detail.php?id=<?php echo $peminjaman['id']; ?>" class="btn-custom btn-primary-custom" style="padding: 6px 12px; font-size: 0.85rem;">
                                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i data-lucide="inbox"></i>
                        </div>
                        <h5 class="empty-state-title">Belum ada peminjaman</h5>
                        <p class="empty-state-text">Anda belum pernah melakukan peminjaman.</p>
                        <a href="pinjam_alat.php" class="btn-custom btn-primary-custom" style="margin-top: 15px;">
                            <i data-lucide="plus-circle"></i>
                            Ajukan Peminjaman Pertama
                        </a>
                    </div>
                <?php endif; ?>
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
        
        // Reinitialize Lucide icons after DOM changes
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });

        // Add smooth transitions for stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>