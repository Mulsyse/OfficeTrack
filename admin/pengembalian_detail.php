<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('admin');

 $db = new Database();
 $conn = $db->getConnection();

// Get pengembalian detail
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT pr.*, p.tanggal_pinjam, p.user_id, p.alat_id, p.jumlah, u.nama as nama_user, u.username, a.nama_alat FROM pengembalian pr JOIN peminjaman p ON pr.peminjaman_id = p.id JOIN users u ON p.user_id = u.id JOIN alat a ON p.alat_id = a.id WHERE pr.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $pengembalian = $result->fetch_assoc();
    $stmt->close();
    
    if (!$pengembalian) {
        header("Location: pengembalian.php");
        exit();
    }
} else {
    header("Location: pengembalian.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pengembalian</title>
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

        /* Detail Cards */
        .detail-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        .detail-row {
            display: flex;
            padding: 15px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 400;
            color: #6c757d;
            min-width: 150px;
        }

        .detail-value {
            color: #333;
            flex: 1;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 400;
        }

        .badge-success {
            background-color: rgba(6, 255, 165, 0.1);
            color: #06ffa5;
        }

        .badge-warning {
            background-color: rgba(255, 190, 11, 0.1);
            color: #ffbe0b;
        }

        .badge-danger {
            background-color: rgba(251, 86, 7, 0.1);
            color: #fb5607;
        }

        .summary-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            height: 100%;
        }

        .summary-item {
            padding: 12px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            font-weight: 400;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .summary-value {
            font-size: 1.1rem;
            font-weight: 300;
            color: #333;
            margin-top: 5px;
        }

        .alert-custom {
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }

        .alert-warning-custom {
            background-color: rgba(255, 190, 11, 0.1);
            border: 1px solid rgba(255, 190, 11, 0.3);
            color: #ffbe0b;
        }

        /* Buttons */
        .btn-custom {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 300;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary-custom {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .btn-secondary-custom:hover {
            background-color: #5a6268;
            color: white;
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
            <!-- Master Data Dropdown -->
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#masterDataDropdown" aria-expanded="false">
                <i data-lucide="database" class="menu-icon"></i>
                <span class="menu-text">Master Data</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse dropdown-menu-custom" id="masterDataDropdown">
                <a href="users.php" class="menu-item">
                    <span class="menu-text">Data User</span>
                </a>
                <a href="alat.php" class="menu-item">
                    <span class="menu-text">Data Alat</span>
                </a>
                <a href="kategori.php" class="menu-item">
                    <span class="menu-text">Data Kategori</span>
                </a>
            </div>

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
                <a href="pengembalian.php" class="menu-item active">
                    <span class="menu-text">Data Pengembalian</span>
                </a>
            </div>
            <a href="log_activity.php" class="menu-item">
                <i data-lucide="file-text" class="menu-icon"></i>
                <span class="menu-text">Log Aktivitas</span>
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
                <h1 class="page-title">Detail Pengembalian</h1>
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

        <!-- Detail Content -->
        <div class="row">
            <div class="col-md-8">
                <div class="detail-card">
                    <div class="card-header-custom">
                        <h5 class="mb-0">Informasi Pengembalian</h5>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <div class="detail-label">ID Pengembalian:</div>
                            <div class="detail-value">#<?php echo str_pad($pengembalian['id'], 6, '0', STR_PAD_LEFT); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Peminjam:</div>
                            <div class="detail-value"><?php echo $pengembalian['nama_user']; ?> (<?php echo $pengembalian['username']; ?>)</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Alat:</div>
                            <div class="detail-value"><?php echo $pengembalian['nama_alat']; ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Tanggal Pinjam:</div>
                            <div class="detail-value"><?php echo format_tanggal($pengembalian['tanggal_pinjam']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Tanggal Kembali:</div>
                            <div class="detail-value"><?php echo format_tanggal($pengembalian['tanggal_kembali']); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Jumlah:</div>
                            <div class="detail-value"><?php echo $pengembalian['jumlah']; ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Kondisi Kembali:</div>
                            <div class="detail-value">
                                <span class="badge-custom badge-<?php echo $pengembalian['kondisi_kembali'] == 'baik' ? 'success' : ($pengembalian['kondisi_kembali'] == 'rusak_ringan' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($pengembalian['kondisi_kembali']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Denda:</div>
                            <div class="detail-value">Rp <?php echo number_format($pengembalian['denda'], 0, ',', '.'); ?></div>
                        </div>
                        <?php if ($pengembalian['keterangan']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Keterangan:</div>
                            <div class="detail-value"><?php echo $pengembalian['keterangan']; ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="detail-row">
                            <div class="detail-label">Tanggal Dicatat:</div>
                            <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($pengembalian['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="summary-card">
                    <div class="card-header-custom">
                        <h5 class="mb-0">Ringkasan</h5>
                    </div>
                    <div class="card-body">
                        <div class="summary-item">
                            <div class="summary-label">Lama Peminjaman:</div>
                            <div class="summary-value">
                                <?php 
                                $tgl_pinjam = new DateTime($pengembalian['tanggal_pinjam']);
                                $tgl_kembali = new DateTime($pengembalian['tanggal_kembali']);
                                $selisih = $tgl_kembali->diff($tgl_pinjam);
                                echo $selisih->days . ' hari';
                                ?>
                            </div>
                        </div>
                        <div class="summary-item">
                            <div class="summary-label">Status Kondisi:</div>
                            <div class="summary-value">
                                <?php if ($pengembalian['kondisi_kembali'] == 'baik'): ?>
                                    <span style="color: var(--success-color);">Tidak ada kerusakan</span>
                                <?php else: ?>
                                    <span style="color: var(--warning-color);">Ada kerusakan</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($pengembalian['denda'] > 0): ?>
                        <div class="alert-custom alert-warning-custom">
                            <strong>Perlu Dibayar:</strong><br>
                            Rp <?php echo number_format($pengembalian['denda'], 0, ',', '.'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-4">
            <a href="pengembalian.php" class="btn btn-secondary-custom btn-custom">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                Kembali
            </a>
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