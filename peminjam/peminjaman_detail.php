<?php
session_start();
require_once '../config/database.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get database connection
 $db = new Database();
 $conn = $db->getConnection();
 $user_id = $_SESSION['user_id'];

// Get peminjaman detail
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT p.*, a.nama_alat, a.kondisi as kondisi_alat FROM peminjaman p JOIN alat a ON p.alat_id = a.id WHERE p.id = ? AND p.user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $peminjaman = $result->fetch_assoc();
    $stmt->close();
    
    if (!$peminjaman) {
        header("Location: peminjaman_saya.php");
        exit();
    }
} else {
    header("Location: peminjaman_saya.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Peminjaman - Sistem Peminjaman</title>
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

        /* Cards */
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
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }

        .card-header-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .card-header-icon.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        .card-header-icon.success {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .card-header-icon.info {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--info-color);
        }

        .card-header-icon.warning {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 400;
            margin: 0;
        }

        /* Detail Rows */
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f1f1f1;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 400;
            color: #495057;
            width: 200px;
            display: flex;
            align-items: center;
        }

        .detail-label i {
            width: 16px;
            height: 16px;
            margin-right: 8px;
        }

        .detail-value {
            color: #333;
            flex: 1;
        }

        /* Badges */
        .badge-custom {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 400;
            display: inline-flex;
            align-items: center;
            gap: 5px;
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

        /* Status Alert */
        .status-alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .status-alert i {
            width: 24px;
            height: 24px;
            margin-right: 12px;
        }

        .status-alert.warning {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .status-alert.danger {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        .status-alert.success {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .status-alert.info {
            background-color: rgba(0, 180, 216, 0.1);
            color: var(--info-color);
        }

        .status-alert.primary {
            background-color: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }

        /* Buttons */
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 300;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
        }

        .btn-success-custom {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success-custom:hover {
            background-color: #05d693;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(6, 255, 165, 0.3);
        }

        .btn-warning-custom {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning-custom:hover {
            background-color: #e6a500;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 190, 11, 0.3);
        }

        .btn-secondary-custom {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary-custom:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }

        /* Notes Section */
        .notes-section {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .notes-title {
            font-weight: 400;
            color: #495057;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .notes-title i {
            width: 16px;
            height: 16px;
            margin-right: 8px;
        }

        .notes-list {
            margin: 0;
            padding-left: 20px;
        }

        .notes-list li {
            margin-bottom: 5px;
            color: #6c757d;
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
                width: 120px;
            }

            .btn-custom {
                width: 100%;
                justify-content: center;
            }
        }

        /* Timeline */
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
            font-size: 0.9rem;
            color: #333;
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
            <a href="daftar_alat.php" class="menu-item">
                <i data-lucide="package" class="menu-icon"></i>
                <span class="menu-text">Daftar Alat</span>
            </a>
            <a href="pinjam_alat.php" class="menu-item">
                <i data-lucide="plus-circle" class="menu-icon"></i>
                <span class="menu-text">Ajukan Peminjaman</span>
            </a>
            <a href="peminjaman_saya.php" class="menu-item active">
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

        <div class="row">
            <div class="col-lg-8">
                <!-- Informasi Peminjaman Card -->
                <div class="detail-card">
                    <div class="card-header-custom">
                        <div class="card-header-icon primary">
                            <i data-lucide="file-text"></i>
                        </div>
                        <h5 class="card-title">Informasi Peminjaman</h5>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="hash"></i>
                            ID Peminjaman
                        </div>
                        <div class="detail-value">
                            <span class="badge-custom badge-primary">
                                #<?php echo str_pad($peminjaman['id'], 6, '0', STR_PAD_LEFT); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="user"></i>
                            Peminjam
                        </div>
                        <div class="detail-value"><?php echo $_SESSION['nama']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="package"></i>
                            Alat
                        </div>
                        <div class="detail-value"><?php echo $peminjaman['nama_alat']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="calendar"></i>
                            Tanggal Pinjam
                        </div>
                        <div class="detail-value"><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="calendar-check"></i>
                            Tanggal Kembali
                        </div>
                        <div class="detail-value">
                            <?php echo $peminjaman['tanggal_kembali'] ? format_tanggal($peminjaman['tanggal_kembali']) : 'Belum dikembalikan'; ?>
                        </div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="layers"></i>
                            Jumlah
                        </div>
                        <div class="detail-value"><?php echo $peminjaman['jumlah']; ?> unit</div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="info"></i>
                            Status
                        </div>
                        <div class="detail-value">
                            <span class="badge-custom badge-<?php 
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
                        </div>
                    </div>
                    
                    <?php if ($peminjaman['keterangan']): ?>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="message-square"></i>
                            Keterangan
                        </div>
                        <div class="detail-value"><?php echo $peminjaman['keterangan']; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="clock"></i>
                            Tanggal Diajukan
                        </div>
                        <div class="detail-value"><?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?></div>
                    </div>
                </div>
                
                <!-- Status Peminjaman Card -->
                <div class="detail-card">
                    <div class="card-header-custom">
                        <div class="card-header-icon info">
                            <i data-lucide="activity"></i>
                        </div>
                        <h5 class="card-title">Status Peminjaman</h5>
                    </div>
                    
                    <?php if ($peminjaman['status'] == 'pending'): ?>
                        <div class="status-alert warning">
                            <i data-lucide="clock"></i>
                            <div>
                                <strong>Menunggu Persetujuan</strong><br>
                                <span style="font-size: 0.9rem;">Peminjaman Anda sedang ditinjau oleh petugas. Silakan tunggu konfirmasi.</span>
                            </div>
                        </div>
                    <?php elseif ($peminjaman['status'] == 'ditolak'): ?>
                        <div class="status-alert danger">
                            <i data-lucide="x-circle"></i>
                            <div>
                                <strong>Ditolak</strong><br>
                                <span style="font-size: 0.9rem;">
                                    Maaf, peminjaman Anda ditolak. 
                                    <?php echo $peminjaman['keterangan'] ? 'Alasan: ' . $peminjaman['keterangan'] : ''; ?>
                                </span>
                            </div>
                        </div>
                    <?php elseif ($peminjaman['status'] == 'disetujui'): ?>
                        <div class="status-alert success">
                            <i data-lucide="check-circle"></i>
                            <div>
                                <strong>Disetujui</strong><br>
                                <span style="font-size: 0.9rem;">Peminjaman Anda telah disetujui. Silakan ambil alat sesuai jadwal.</span>
                            </div>
                        </div>
                    <?php elseif ($peminjaman['status'] == 'dipinjam'): ?>
                        <div class="status-alert primary">
                            <i data-lucide="package"></i>
                            <div>
                                <strong>Sedang Dipinjam</strong><br>
                                <span style="font-size: 0.9rem;">Alat sedang dalam peminjaman. Harap dikembalikan tepat waktu.</span>
                            </div>
                        </div>
                    <?php elseif ($peminjaman['status'] == 'dikembalikan'): ?>
                        <div class="status-alert info">
                            <i data-lucide="rotate-ccw"></i>
                            <div>
                                <strong>Telah Dikembalikan</strong><br>
                                <span style="font-size: 0.9rem;">Terima kasih telah mengembalikan alat tepat waktu.</span>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 d-flex gap-2">
                        <?php if ($peminjaman['status'] == 'disetujui' || $peminjaman['status'] == 'dipinjam'): ?>
                        <a href="pengembalian.php" class="btn-custom btn-warning-custom">
                            <i data-lucide="rotate-ccw"></i>
                            Ajukan Pengembalian
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($peminjaman['status'] == 'ditolak'): ?>
                        <a href="pinjam_alat.php?ulang=<?php echo $peminjaman['id']; ?>" class="btn-custom btn-warning-custom">
                            <i data-lucide="refresh-cw"></i>
                            Ajukan Ulang
                        </a>
                        <?php endif; ?>
                        
                        <a href="peminjaman_saya.php" class="btn-custom btn-secondary-custom">
                            <i data-lucide="arrow-left"></i>
                            Kembali
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Informasi Alat Card -->
                <div class="detail-card">
                    <div class="card-header-custom">
                        <div class="card-header-icon success">
                            <i data-lucide="package"></i>
                        </div>
                        <h5 class="card-title">Informasi Alat</h5>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="tag"></i>
                            Nama Alat
                        </div>
                        <div class="detail-value"><?php echo $peminjaman['nama_alat']; ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">
                            <i data-lucide="check-circle"></i>
                            Kondisi Awal
                        </div>
                        <div class="detail-value">
                            <span class="badge-custom badge-<?php echo $peminjaman['kondisi_alat'] == 'baik' ? 'success' : ($peminjaman['kondisi_alat'] == 'rusak_ringan' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($peminjaman['kondisi_alat']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="notes-section">
                        <div class="notes-title">
                            <i data-lucide="info"></i>
                            Catatan Penting
                        </div>
                        <ul class="notes-list">
                            <li>Jaga alat dengan baik</li>
                            <li>Kembalikan tepat waktu</li>
                            <li>Hubungi petugas jika ada masalah</li>
                            <li>Periksa kondisi alat saat menerima</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Timeline Card -->
                <div class="detail-card">
                    <div class="card-header-custom">
                        <div class="card-header-icon warning">
                            <i data-lucide="clock"></i>
                        </div>
                        <h5 class="card-title">Riwayat</h5>
                    </div>
                    
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($peminjaman['created_at'])); ?></div>
                            <div class="timeline-content">Peminjaman diajukan</div>
                        </div>
                        
                        <?php if ($peminjaman['status'] != 'pending'): ?>
                        <div class="timeline-item">
                            <div class="timeline-date">
                                <?php 
                                $updated_at = date('d/m/Y H:i', strtotime($peminjaman['updated_at'] ?? $peminjaman['created_at']));
                                echo $updated_at;
                                ?>
                            </div>
                            <div class="timeline-content">
                                Status diubah menjadi <strong><?php echo ucfirst($peminjaman['status']); ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($peminjaman['tanggal_kembali']): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date('d/m/Y H:i', strtotime($peminjaman['tanggal_kembali'])); ?></div>
                            <div class="timeline-content">Alat dikembalikan</div>
                        </div>
                        <?php endif; ?>
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
        
        // Reinitialize Lucide icons after DOM changes
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>