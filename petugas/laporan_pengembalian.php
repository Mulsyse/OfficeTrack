
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

// Get pengembalian data with filtering
$query = "SELECT pr.*, p.tanggal_pinjam, p.user_id, p.alat_id, p.jumlah, u.nama as nama_user, a.nama_alat FROM pengembalian pr JOIN peminjaman p ON pr.peminjaman_id = p.id JOIN users u ON p.user_id = u.id JOIN alat a ON p.alat_id = a.id WHERE pr.tanggal_kembali BETWEEN ? AND ? ORDER BY pr.tanggal_kembali DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics for report
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM pengembalian WHERE tanggal_kembali BETWEEN ? AND ?");
$stmt_total->bind_param("ss", $start_date, $end_date);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_transaksi = $result_total->fetch_assoc()['total'];

$stmt_total_alat = $conn->prepare("SELECT SUM(p.jumlah) as total FROM pengembalian pr JOIN peminjaman p ON pr.peminjaman_id = p.id WHERE pr.tanggal_kembali BETWEEN ? AND ?");
$stmt_total_alat->bind_param("ss", $start_date, $end_date);
$stmt_total_alat->execute();
$result_total_alat = $stmt_total_alat->get_result();
$total_alat_kembali = $result_total_alat->fetch_assoc()['total'];

$stmt_total_denda = $conn->prepare("SELECT SUM(pr.denda) as total FROM pengembalian pr WHERE pr.tanggal_kembali BETWEEN ? AND ?");
$stmt_total_denda->bind_param("ss", $start_date, $end_date);
$stmt_total_denda->execute();
$result_total_denda = $stmt_total_denda->get_result();
$total_denda = $result_total_denda->fetch_assoc()['total'] ?: 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pengembalian</title>
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
            word-break: break-all;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        /* Activity Table */
        .activity-card {
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

        /* Filter Form */
        .filter-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

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
        }

        .menu-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
        }

        .menu-item[aria-expanded="true"] .menu-arrow {
            transform: rotate(180deg);
        }

        /* Print Styles */
        @media print {
            .no-print { 
                display: none !important; 
            }
            .print-only { 
                display: block !important; 
            }
            .sidebar {
                display: none !important;
            }
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }
            .top-header {
                display: none !important;
            }
            .filter-card {
                display: none !important;
            }
            .activity-card {
                box-shadow: none !important;
                border: 1px solid #000 !important;
            }
            .table-custom {
                font-size: 12px !important;
            }
        }
        .print-only { 
            display: none; 
        }

        /* Badge styling */
        .badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 400;
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
                <a href="laporan_peminjaman.php" class="menu-item">
                    <span class="menu-text">Laporan Peminjaman</span>
                </a>
                <a href="laporan_pengembalian.php" class="menu-item active">
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
                <h1 class="page-title">Laporan Pengembalian</h1>
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
                            <div class="stat-value"><?php echo $total_alat_kembali ?: 0; ?></div>
                            <div class="stat-label">Alat Dikembalikan</div>
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
                            <div class="stat-value">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></div>
                            <div class="stat-label">Total Denda</div>
                        </div>
                        <div class="stat-icon warning">
                            <i data-lucide="alert-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $result->num_rows; ?></div>
                            <div class="stat-label">Data Ditampilkan</div>
                        </div>
                        <div class="stat-icon info">
                            <i data-lucide="database"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
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
                            <i data-lucide="filter" style="width: 16px; height: 16px;"></i>
                            Filter Data
                        </button>
                        <button type="button" onclick="window.print()" class="btn-custom btn-success-custom">
                            <i data-lucide="printer" style="width: 16px; height: 16px;"></i>
                            Cetak Laporan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Report Header (Print Only) -->
        <div class="print-only text-center mb-4">
            <h3>LAPORAN PENGEMBALIAN ALAT</h3>
            <p>Periode: <?php echo format_tanggal($start_date); ?> - <?php echo format_tanggal($end_date); ?></p>
            <p>Dicetak pada: <?php echo date('d/m/Y H:i'); ?></p>
            <hr>
        </div>

        <!-- Report Table -->
        <div class="activity-card">
            <div class="card-header-custom no-print">
                <h5 class="mb-0">Data Pengembalian</h5>
                <span class="text-muted">Periode: <?php echo format_tanggal($start_date); ?> - <?php echo format_tanggal($end_date); ?></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Kembali</th>
                                <th>Peminjam</th>
                                <th>Alat</th>
                                <th>Jumlah</th>
                                <th>Kondisi</th>
                                <th>Denda</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; $total_pengembalian = 0; $total_denda_table = 0; 
                            if ($result->num_rows > 0): 
                                while ($pengembalian = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo format_tanggal($pengembalian['tanggal_kembali']); ?></td>
                                <td><?php echo $pengembalian['nama_user']; ?></td>
                                <td><?php echo $pengembalian['nama_alat']; ?></td>
                                <td><?php echo $pengembalian['jumlah']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $pengembalian['kondisi_kembali'] == 'baik' ? 'success' : ($pengembalian['kondisi_kembali'] == 'rusak_ringan' ? 'warning' : 'danger'); ?>">
                                        <?php echo ucfirst($pengembalian['kondisi_kembali']); ?>
                                    </span>
                                </td>
                                <td>Rp <?php echo number_format($pengembalian['denda'], 0, ',', '.'); ?></td>
                                <td><?php echo $pengembalian['keterangan'] ?: '-'; ?></td>
                            </tr>
                            <?php 
                            $total_pengembalian += $pengembalian['jumlah'];
                            $total_denda_table += $pengembalian['denda'];
                            endwhile; 
                            else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Tidak ada data pengembalian pada periode ini</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if ($result->num_rows > 0): ?>
                        <?php endif; ?>
                    </table>
                </div>
                
                <!-- Summary -->
                <?php if ($result->num_rows > 0): ?>
                <div class="row mt-4 no-print">
                    <div class="col-md-12">
                        <div class="filter-card">
                            <div class="card-header-custom">
                                <h6 class="mb-0">Ringkasan Laporan</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Total Transaksi:</strong><br>
                                        <span class="text-primary"><?php echo $total_transaksi; ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Alat Dikembalikan:</strong><br>
                                        <span class="text-success"><?php echo $total_alat_kembali ?: 0; ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Denda:</strong><br>
                                        <span class="text-warning">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Periode:</strong><br>
                                        <span class="text-info"><?php echo format_tanggal($start_date); ?> - <?php echo format_tanggal($end_date); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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