
<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('petugas');

$db = new Database();
$conn = $db->getConnection();

// Handle approval/rejection
if (isset($_POST['action']) && isset($_POST['peminjaman_id'])) {
    $peminjaman_id = $_POST['peminjaman_id'];
    $action = $_POST['action'];
    $keterangan = sanitize_input($_POST['keterangan']);
    
    if ($action == 'approve') {
        $status = 'disetujui';
        log_activity($_SESSION['user_id'], "Menyetujui peminjaman ID: $peminjaman_id");
    } else {
        $status = 'ditolak';
        log_activity($_SESSION['user_id'], "Menolak peminjaman ID: $peminjaman_id");
    }
    
    $stmt = $conn->prepare("UPDATE peminjaman SET status = ?, keterangan = ? WHERE id = ?");
    $stmt->bind_param("ssi", $status, $keterangan, $peminjaman_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: peminjaman.php");
    exit();
}

// Handle date filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get all peminjaman with user and alat info and filtering
$query = "SELECT p.*, u.nama as nama_user, a.nama_alat FROM peminjaman p JOIN users u ON p.user_id = u.id JOIN alat a ON p.alat_id = a.id WHERE p.created_at BETWEEN ? AND ? ORDER BY p.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE created_at BETWEEN ? AND ?");
$stmt_total->bind_param("ss", $start_date, $end_date);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$total_transaksi = $result_total->fetch_assoc()['total'];

$stmt_pending = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'pending' AND created_at BETWEEN ? AND ?");
$stmt_pending->bind_param("ss", $start_date, $end_date);
$stmt_pending->execute();
$result_pending = $stmt_pending->get_result();
$total_pending = $result_pending->fetch_assoc()['total'];

$stmt_approved = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'disetujui' AND created_at BETWEEN ? AND ?");
$stmt_approved->bind_param("ss", $start_date, $end_date);
$stmt_approved->execute();
$result_approved = $stmt_approved->get_result();
$total_disetujui = $result_approved->fetch_assoc()['total'];

$stmt_total_alat = $conn->prepare("SELECT SUM(jumlah) as total FROM peminjaman WHERE created_at BETWEEN ? AND ?");
$stmt_total_alat->bind_param("ss", $start_date, $end_date);
$stmt_total_alat->execute();
$result_total_alat = $stmt_total_alat->get_result();
$total_alat = $result_total_alat->fetch_assoc()['total'] ?: 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Peminjaman</title>
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

        .btn-info-custom {
            background-color: var(--info-color);
            color: white;
        }

        .btn-info-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 180, 216, 0.3);
        }

        .btn-warning-custom {
            background-color: var(--warning-color);
            color: #333;
        }

        .btn-warning-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 190, 11, 0.3);
        }

        .btn-danger-custom {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(251, 86, 7, 0.3);
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

        /* Badge styling */
        .badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 400;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .btn-sm-custom {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 6px;
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
                <h1 class="page-title">Data Peminjaman</h1>
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
        <div class="row">
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
                            <div class="stat-value"><?php echo $total_pending; ?></div>
                            <div class="stat-label">Menunggu Persetujuan</div>
                        </div>
                        <div class="stat-icon warning">
                            <i data-lucide="clock"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_disetujui; ?></div>
                            <div class="stat-label">Disetujui</div>
                        </div>
                        <div class="stat-icon success">
                            <i data-lucide="check-circle"></i>
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
                        <div class="stat-icon info">
                            <i data-lucide="package"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Form -->
        <div class="filter-card">
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
                            Cetak
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Data Table -->
        <div class="activity-card">
            <div class="card-header-custom">
                <h5 class="mb-0">Data Peminjaman</h5>
                <span class="text-muted">Periode: <?php echo format_tanggal($start_date); ?> - <?php echo format_tanggal($end_date); ?></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Peminjam</th>
                                <th>Alat</th>
                                <th>Tanggal Pinjam</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($peminjaman = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $peminjaman['nama_user']; ?></td>
                                <td><?php echo $peminjaman['nama_alat']; ?></td>
                                <td><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></td>
                                <td><?php echo $peminjaman['jumlah']; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $peminjaman['status'] == 'disetujui' ? 'success' : 
                                            ($peminjaman['status'] == 'ditolak' ? 'danger' : 
                                            ($peminjaman['status'] == 'dipinjam' ? 'primary' : 
                                            ($peminjaman['status'] == 'dikembalikan' ? 'info' : 'warning'))); 
                                    ?>">
                                        <?php echo ucfirst($peminjaman['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="peminjaman_detail.php?id=<?php echo $peminjaman['id']; ?>" class="btn-custom btn-info-custom btn-sm-custom">
                                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                            Detail
                                        </a>
                                        <?php if ($peminjaman['status'] == 'pending'): ?>
                                        <button class="btn-custom btn-success-custom btn-sm-custom" onclick="approvePeminjaman(<?php echo $peminjaman['id']; ?>)">
                                            <i data-lucide="check" style="width: 14px; height: 14px;"></i>
                                            Setujui
                                        </button>
                                        <button class="btn-custom btn-danger-custom btn-sm-custom" onclick="rejectPeminjaman(<?php echo $peminjaman['id']; ?>)">
                                            <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                                            Tolak
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Persetujuan Peminjaman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" id="peminjaman_id" name="peminjaman_id">
                    <input type="hidden" id="action" name="action">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Tambahkan keterangan (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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

        function approvePeminjaman(id) {
            document.getElementById('modalTitle').textContent = 'Setujui Peminjaman';
            document.getElementById('peminjaman_id').value = id;
            document.getElementById('action').value = 'approve';
            document.getElementById('submitBtn').className = 'btn btn-success';
            document.getElementById('submitBtn').textContent = 'Setujui';
            new bootstrap.Modal(document.getElementById('approvalModal')).show();
        }
        
        function rejectPeminjaman(id) {
            document.getElementById('modalTitle').textContent = 'Tolak Peminjaman';
            document.getElementById('peminjaman_id').value = id;
            document.getElementById('action').value = 'reject';
            document.getElementById('submitBtn').className = 'btn btn-danger';
            document.getElementById('submitBtn').textContent = 'Tolak';
            new bootstrap.Modal(document.getElementById('approvalModal')).show();
        }
        
        // Reset modal when closed
        document.getElementById('approvalModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('keterangan').value = '';
        });
    </script>
</body>
</html>