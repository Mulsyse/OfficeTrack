<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('petugas');

 $db = new Database();
 $conn = $db->getConnection();

// Handle date filter
 $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
 $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get peminjaman data with filtering
 $query = "SELECT p.*, u.nama as nama_user, a.nama_alat FROM peminjaman p 
          JOIN users u ON p.user_id = u.id 
          JOIN alat a ON p.alat_id = a.id 
          WHERE p.tanggal_pinjam BETWEEN ? AND ? 
          ORDER BY p.tanggal_pinjam DESC";
 $stmt = $conn->prepare($query);
 $stmt->bind_param("ss", $start_date, $end_date);
 $stmt->execute();
 $result = $stmt->get_result();

// Get statistics for the period
 $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ?");
 $stmt_total->bind_param("ss", $start_date, $end_date);
 $stmt_total->execute();
 $total_transaksi = $stmt_total->get_result()->fetch_assoc()['total'];

 $stmt_alat = $conn->prepare("SELECT SUM(jumlah) as total FROM peminjaman WHERE tanggal_pinjam BETWEEN ? AND ?");
 $stmt_alat->bind_param("ss", $start_date, $end_date);
 $stmt_alat->execute();
 $total_alat = $stmt_alat->get_result()->fetch_assoc()['total'] ?: 0;

// Get status statistics
 $stmt_approved = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'disetujui' AND tanggal_pinjam BETWEEN ? AND ?");
 $stmt_approved->bind_param("ss", $start_date, $end_date);
 $stmt_approved->execute();
 $total_approved = $stmt_approved->get_result()->fetch_assoc()['total'];

 $stmt_pending = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'pending' AND tanggal_pinjam BETWEEN ? AND ?");
 $stmt_pending->bind_param("ss", $start_date, $end_date);
 $stmt_pending->execute();
 $total_pending = $stmt_pending->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Peminjaman</title>
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

        /* Statistics Cards */
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .stat-icon.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .stat-icon.success {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .stat-icon.info {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--info-color);
        }

        .stat-icon.warning {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Report Card */
        .report-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .card-header-custom {
            padding: 20px 25px;
            border-bottom: 1px solid #eaeaea;
            font-weight: 400;
            font-size: 1.1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-custom {
            margin-bottom: 0;
            width: 100%;
        }

        .table-custom thead th {
            border-bottom: 1px solid #eaeaea;
            font-weight: 400;
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 25px;
            white-space: nowrap;
        }

        .table-custom tbody td {
            padding: 15px 25px;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-badge.success {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .status-badge.danger {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        .status-badge.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .status-badge.info {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--info-color);
        }

        .status-badge.warning {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        /* Filter Card */
        .filter-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 400;
            margin-bottom: 8px;
            color: #495057;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #eaeaea;
            padding: 10px 15px;
            font-weight: 300;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.1);
        }

        .btn-custom {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 400;
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

        .btn-info-custom {
            background-color: var(--info-color);
            color: white;
        }

        .btn-info-custom:hover {
            background-color: #0096c7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 180, 216, 0.3);
        }

        /* Summary Grid */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }

        .summary-item {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }

        .summary-label {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 400;
            color: #333;
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

            .stat-value {
                font-size: 1.5rem;
            }

            .table-custom {
                font-size: 0.85rem;
            }

            .table-custom thead th,
            .table-custom tbody td {
                padding: 10px 15px;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .sidebar {
                display: none !important;
            }

            .top-header {
                display: none !important;
            }

            .filter-card {
                display: none !important;
            }

            .stat-card {
                display: none !important;
            }

            .report-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }

            .table-custom {
                font-size: 12px !important;
            }

            .summary-grid {
                display: block !important;
            }

            .summary-item {
                margin-bottom: 10px;
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
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#transaksiDropdown" aria-expanded="false">
                <i data-lucide="arrow-right-left" class="menu-icon"></i>
                <span class="menu-text">Transaksi</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse dropdown-menu-custom" id="transaksiDropdown">
                <a href="peminjaman.php" class="menu-item">
                    <span class="menu-text">Data Peminjaman</span>
                </a>
                <a href="pengembalian.php" class="menu-item">
                    <span class="menu-text">Data Pengembalian</span>
                </a>
            </div>
            <!-- Laporan Dropdown -->
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#laporanDropdown" aria-expanded="true">
                <i data-lucide="file-text" class="menu-icon"></i>
                <span class="menu-text">Laporan</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse dropdown-menu-custom show" id="laporanDropdown">
                <a href="laporan_peminjaman.php" class="menu-item active">
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
        <div class="top-header no-print">
            <div style="display: flex; align-items: center;">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i data-lucide="menu"></i>
                </button>
                <h1 class="page-title">Laporan Peminjaman</h1>
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
        <div class="row no-print">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_transaksi; ?></div>
                            <div class="stat-label">Total Transaksi</div>
                        </div>
                        <div class="stat-icon primary">
                            <i data-lucide="file-text"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_alat; ?></div>
                            <div class="stat-label">Total Alat Dipinjam</div>
                        </div>
                        <div class="stat-icon success">
                            <i data-lucide="package"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_approved; ?></div>
                            <div class="stat-label">Disetujui</div>
                        </div>
                        <div class="stat-icon info">
                            <i data-lucide="check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_pending; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                        <div class="stat-icon warning">
                            <i data-lucide="clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card no-print">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="submit" class="btn-custom btn-primary-custom">
                            <i data-lucide="filter"></i>
                            Filter Data
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Report Card -->
        <div class="report-card">
            <div class="card-header-custom">
                <h5 class="mb-0">Data Peminjaman</h5>
                <div class="no-print">
                    <button onclick="window.print()" class="btn-custom btn-info-custom">
                        <i data-lucide="printer"></i>
                        Cetak Laporan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Pinjam</th>
                                <th>Peminjam</th>
                                <th>Alat</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; 
                            $total_peminjaman = 0; 
                            if ($result->num_rows > 0):
                                while ($peminjaman = $result->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></td>
                                <td><?php echo $peminjaman['nama_user']; ?></td>
                                <td><?php echo $peminjaman['nama_alat']; ?></td>
                                <td><?php echo $peminjaman['jumlah']; ?></td>
                                <td>
                                    <span class="status-badge <?php 
                                        echo $peminjaman['status'] == 'disetujui' ? 'success' : 
                                            ($peminjaman['status'] == 'ditolak' ? 'danger' : 
                                            ($peminjaman['status'] == 'dipinjam' ? 'primary' : 
                                            ($peminjaman['status'] == 'dikembalikan' ? 'info' : 'warning'))); 
                                    ?>">
                                        <i data-lucide="<?php 
                                            echo $peminjaman['status'] == 'disetujui' ? 'check-circle' : 
                                                ($peminjaman['status'] == 'ditolak' ? 'x-circle' : 
                                                ($peminjaman['status'] == 'dipinjam' ? 'package' : 
                                                ($peminjaman['status'] == 'dikembalikan' ? 'rotate-ccw' : 'clock'))); 
                                        ?>" style="width: 14px; height: 14px;"></i>
                                        <?php echo ucfirst($peminjaman['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $peminjaman['keterangan'] ?: '-'; ?></td>
                            </tr>
                            <?php 
                            $total_peminjaman += $peminjaman['jumlah'];
                            endwhile; 
                            else:
                            ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">Tidak ada data peminjaman pada periode ini</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if ($result->num_rows > 0): ?>
                        <tfoot>
                            <tr style="background-color: #f8f9fa; font-weight: 400;">
                                <td colspan="4">Total</td>
                                <td><?php echo $total_peminjaman; ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
                
                <!-- Summary -->
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-label">Total Transaksi</div>
                        <div class="summary-value"><?php echo $total_transaksi; ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total Alat Dipinjam</div>
                        <div class="summary-value"><?php echo $total_alat; ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Periode</div>
                        <div class="summary-value"><?php echo format_tanggal($start_date); ?> - <?php echo format_tanggal($end_date); ?></div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Dicetak oleh</div>
                        <div class="summary-value"><?php echo $_SESSION['nama']; ?></div>
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
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(item => {
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