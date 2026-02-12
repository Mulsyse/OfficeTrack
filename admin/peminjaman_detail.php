<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('admin');

// --- KONFIGURASI NAMA KOLOM DATABASE ---
// SESUAIKAN NAMA KOLOM INI DENGAN STRUKTUR DATABASE ANDA.
// Anda bisa mengeceknya melalui phpMyAdmin atau alat database lainnya.

// Jika di tabel 'alat' kolom namanya adalah 'nama_barang', ganti 'nama' menjadi 'nama_barang'
 $db_alat_nama_kolom = 'nama_alat'; // << UBAH INI JIKA PERLU
 $db_alat_kondisi_kolom = 'kondisi'; // << UBAH INI JIKA PERLU

// Jika di tabel 'users' kolom namanya adalah 'nama_lengkap', ganti 'nama' menjadi 'nama_lengkap'
 $db_users_nama_kolom = 'nama'; // << UBAH INI JIKA PERLU

// Jika di tabel 'peminjaman' foreign key ke 'alat' adalah 'id_alat', ganti 'alat_id' menjadi 'id_alat'
 $db_peminjaman_alat_id_kolom = 'alat_id'; // << UBAH INI JIKA PERLU
 $db_peminjaman_user_id_kolom = 'user_id'; // << UBAH INI JIKA PERLU
// --- AKHIR KONFIGURASI ---


// Get peminjaman data
if (!isset($_GET['id'])) {
    header('Location: peminjaman.php');
    exit();
}

 $id = $_GET['id'];
 $db = new Database();
 $conn = $db->getConnection();

// Get peminjaman details (menggunakan variabel konfigurasi kolom)
 $stmt = $conn->prepare("
    SELECT p.*, u.{$db_users_nama_kolom} as nama_user, u.username, a.{$db_alat_nama_kolom} as nama_alat, a.{$db_alat_kondisi_kolom} as kondisi_alat
    FROM peminjaman p
    JOIN users u ON p.{$db_peminjaman_user_id_kolom} = u.id
    JOIN alat a ON p.{$db_peminjaman_alat_id_kolom} = a.id
    WHERE p.id = ?
");
 $stmt->bind_param("i", $id);
 $stmt->execute();
 $result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: peminjaman.php');
    exit();
}

 $peminjaman = $result->fetch_assoc();

// Get statistics
 $stmt_total = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman");
 $stmt_total->execute();
 $total_peminjaman = $stmt_total->get_result()->fetch_assoc()['total'];

 $stmt_menunggu = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'menunggu'");
 $stmt_menunggu->execute();
 $total_menunggu = $stmt_menunggu->get_result()->fetch_assoc()['total'];

 $stmt_disetujui = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'disetujui'");
 $stmt_disetujui->execute();
 $total_disetujui = $stmt_disetujui->get_result()->fetch_assoc()['total'];

 $stmt_dipinjam = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dipinjam'");
 $stmt_dipinjam->execute();
 $total_dipinjam = $stmt_dipinjam->get_result()->fetch_assoc()['total'];

 $stmt_dikembalikan = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'dikembalikan'");
 $stmt_dikembalikan->execute();
 $total_dikembalikan = $stmt_dikembalikan->get_result()->fetch_assoc()['total'];

 $stmt_ditolak = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'ditolak'");
 $stmt_ditolak->execute();
 $total_ditolak = $stmt_ditolak->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman - Sistem Peminjaman Alat</title>
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

        .menu-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        
        .menu-item[aria-expanded="true"] .menu-arrow {
            transform: rotate(180deg);
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

        .stat-icon.danger {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
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

        /* Detail Cards */
        .detail-card {
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
            align-items: center;
        }

        .card-header-custom i {
            margin-right: 10px;
        }

        .card-body-custom {
            padding: 25px;
        }

        .detail-item {
            display: flex;
            margin-bottom: 20px;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 500;
            color: #6c757d;
            min-width: 150px;
        }

        .detail-value {
            color: #333;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .status-menunggu {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .status-disetujui {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .status-dipinjam {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--info-color);
        }

        .status-dikembalikan {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .status-ditolak {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        .kondisi-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .kondisi-baik {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .kondisi-rusak-ringan {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .kondisi-rusak-berat {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #eaeaea;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: var(--primary-color);
        }

        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 5px;
        }

        .timeline-content {
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .btn-custom i {
            width: 18px;
            height: 18px;
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
            background-color: #f8f9fa;
            color: #6c757d;
            border: 1px solid #eaeaea;
        }

        .btn-secondary-custom:hover {
            background-color: #eaeaea;
            color: #333;
        }

        .btn-danger-custom {
            background-color: var(--danger-color);
            color: white;
            border: none;
        }

        .btn-danger-custom:hover {
            background-color: #e04a06;
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

            .detail-item {
                flex-direction: column;
            }

            .detail-label {
                margin-bottom: 5px;
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
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#transaksiDropdown" aria-expanded="true">
                <i data-lucide="arrow-right-left" class="menu-icon"></i>
                <span class="menu-text">Transaksi</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse show dropdown-menu-custom" id="transaksiDropdown">
                <a href="peminjaman.php" class="menu-item active">
                    <span class="menu-text">Data Peminjaman</span>
                </a>
                <a href="pengembalian.php" class="menu-item">
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
    

        <!-- Detail Information -->
        <div class="row">
            <div class="col-12">
                <div class="detail-card">
                    <div class="card-header-custom">
                        <i data-lucide="info"></i>
                        Informasi Peminjaman
                    </div>
                    <div class="card-body-custom">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">ID Peminjaman:</div>
                                    <div class="detail-value">#<?php echo str_pad($peminjaman['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Peminjam:</div>
                                    <div class="detail-value"><?php echo $peminjaman['nama_user']; ?> (@<?php echo $peminjaman['username']; ?>)</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Nama Alat:</div>
                                    <div class="detail-value"><?php echo $peminjaman['nama_alat']; ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Kondisi Alat:</div>
                                    <div class="detail-value">
                                        <span class="kondisi-badge kondisi-<?php echo $peminjaman['kondisi_alat']; ?>">
                                            <?php echo ucfirst($peminjaman['kondisi_alat']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">Jumlah:</div>
                                    <div class="detail-value"><?php echo $peminjaman['jumlah']; ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Tanggal Pinjam:</div>
                                    <div class="detail-value"><?php echo date('d/m/Y', strtotime($peminjaman['tanggal_pinjam'])); ?></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Tanggal Kembali:</div>
                                    <div class="detail-value">
                                        <?php echo $peminjaman['tanggal_kembali'] ? date('d/m/Y', strtotime($peminjaman['tanggal_kembali'])) : 'Belum dikembalikan'; ?>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Status:</div>
                                    <div class="detail-value">
                                        <span class="status-badge status-<?php echo $peminjaman['status']; ?>">
                                            <?php echo ucfirst($peminjaman['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($peminjaman['keterangan']): ?>
                        <div class="detail-item mt-3">
                            <div class="detail-label">Keterangan:</div>
                            <div class="detail-value"><?php echo $peminjaman['keterangan']; ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="row">
            <div class="col-12">
                <div class="detail-card">
                    <div class="card-header-custom">
                        <i data-lucide="activity"></i>
                        Riwayat Aktivitas
                    </div>
                    <div class="card-body-custom">
                        <div class="timeline">
                            <div class="timeline-item">
                                <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?></div>
                                <div class="timeline-content">Peminjaman diajukan oleh <?php echo $peminjaman['nama_user']; ?></div>
                            </div>
                            <?php if ($peminjaman['status'] != 'menunggu'): ?>
                            <div class="timeline-item">
                                <div class="timeline-date">
                                    <?php 
                                    // Cek apakah kolom updated_at ada dan tidak kosong
                                    if (isset($peminjaman['updated_at']) && !empty($peminjaman['updated_at'])) {
                                        echo date('d/m/Y H:i', strtotime($peminjaman['updated_at']));
                                    } else {
                                        // Jika tidak ada, gunakan created_at sebagai alternatif untuk mencegah error
                                        echo date('d/m/Y H:i', strtotime($peminjaman['created_at']));
                                    }
                                    ?>
                                </div>
                                <div class="timeline-content">Status diperbarui menjadi <span class="status-badge status-<?php echo $peminjaman['status']; ?>"><?php echo ucfirst($peminjaman['status']); ?></span></div>
                            </div>
                            <?php endif; ?>
                            <?php if ($peminjaman['tanggal_kembali']): ?>
                            <div class="timeline-item">
                                <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($peminjaman['tanggal_kembali'])); ?></div>
                                <div class="timeline-content">Alat telah dikembalikan</div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="row">
            <div class="col-12">
                <div class="detail-card">
                    <div class="card-header-custom">
                        <i data-lucide="settings"></i>
                        Tindakan
                    </div>
                    <div class="card-body-custom">
                        <div class="action-buttons">
                            <a href="peminjaman.php" class="btn btn-custom btn-secondary-custom">
                                <i data-lucide="arrow-left"></i>
                                Kembali
                            </a>
                            <a href="print_peminjaman.php?id=<?php echo $peminjaman['id']; ?>" class="btn btn-custom btn-secondary-custom" target="_blank">
                                <i data-lucide="printer"></i>
                                Cetak
                            </a>
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