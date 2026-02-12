<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('peminjam');

 $db = new Database();
 $conn = $db->getConnection();
 $user_id = $_SESSION['user_id'];

// Get user's peminjaman
 $stmt = $conn->prepare("SELECT p.*, a.nama_alat FROM peminjaman p JOIN alat a ON p.alat_id = a.id WHERE p.user_id = ? ORDER BY p.created_at DESC");
 $stmt->bind_param("i", $user_id);
 $stmt->execute();
 $result = $stmt->get_result();

 // Cek dan ambil pesan sukses dari session
 $popup_message = '';
if (isset($_SESSION['success_message'])) {
    $popup_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Hapus pesan agar tidak muncul lagi
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Saya</title>
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
            height: 100vh;
            overflow-y: auto;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
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

        .stat-content h3 {
            font-size: 1.5rem;
            font-weight: 400;
            margin: 0;
        }

        .stat-content p {
            font-size: 0.85rem;
            color: #6c757d;
            margin: 0;
            font-weight: 300;
        }

        /* Peminjaman Cards */
        .peminjaman-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .peminjaman-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .peminjaman-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .peminjaman-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .peminjaman-title {
            font-size: 1.1rem;
            font-weight: 400;
            color: #333;
            margin-bottom: 5px;
        }

        .peminjaman-id {
            font-size: 0.75rem;
            color: #adb5bd;
            font-weight: 300;
        }

        .peminjaman-details {
            margin-bottom: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .detail-label {
            color: #6c757d;
            font-weight: 300;
        }

        .detail-value {
            color: #333;
            font-weight: 400;
        }

        .peminjaman-badges {
            display: flex;
            gap: 8px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .badge-custom {
            padding: 5px 10px;
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

        .peminjaman-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f1f1f1;
            gap: 10px;
        }

        .btn-custom {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 300;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            flex: 1;
            justify-content: center;
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

        .btn-warning-custom {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-warning-custom:hover {
            background-color: #e6a700;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 190, 11, 0.3);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .empty-state-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            color: #adb5bd;
        }

        .empty-state-title {
            font-size: 1.25rem;
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

            .peminjaman-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Filter Card */
        .filter-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .filter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-control, .form-select {
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 300;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.1);
        }

        .form-label {
            font-weight: 400;
            color: #495057;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body data-popup-message="<?php echo htmlspecialchars($popup_message, ENT_QUOTES, 'UTF-8'); ?>">
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
                <h1 class="page-title">Peminjaman Saya</h1>
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

        <!-- Stats Cards -->
        <?php
        // Calculate statistics
        $total_peminjaman = $result->num_rows;
        $disetujui = 0;
        $ditolak = 0;
        $dipinjam = 0;
        $dikembalikan = 0;
        
        // Reset result pointer
        $result->data_seek(0);
        while ($p = $result->fetch_assoc()) {
            switch($p['status']) {
                case 'disetujui': $disetujui++; break;
                case 'ditolak': $ditolak++; break;
                case 'dipinjam': $dipinjam++; break;
                case 'dikembalikan': $dikembalikan++; break;
            }
        }
        // Reset result pointer again
        $result->data_seek(0);
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i data-lucide="file-text"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_peminjaman; ?></h3>
                    <p>Total Peminjaman</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i data-lucide="check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $disetujui + $dipinjam; ?></h3>
                    <p>Disetujui & Dipinjam</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i data-lucide="rotate-ccw"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $dikembalikan; ?></h3>
                    <p>Dikembalikan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i data-lucide="x-circle"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $ditolak; ?></h3>
                    <p>Ditolak</p>
                </div>
            </div>
        </div>

        <!-- Peminjaman Grid -->
        <?php if ($result->num_rows > 0): ?>
            <div class="peminjaman-grid">
                <?php $no = 1; while ($peminjaman = $result->fetch_assoc()): ?>
                <div class="peminjaman-card">
                    <div class="peminjaman-card-header">
                        <div>
                            <h5 class="peminjaman-title"><?php echo $peminjaman['nama_alat']; ?></h5>
                        </div>
                        <span class="peminjaman-id">#<?php echo str_pad($peminjaman['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    
                    <div class="peminjaman-details">
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Pinjam:</span>
                            <span class="detail-value"><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Tanggal Kembali:</span>
                            <span class="detail-value"><?php echo $peminjaman['tanggal_kembali'] ? format_tanggal($peminjaman['tanggal_kembali']) : '-'; ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Jumlah:</span>
                            <span class="detail-value"><?php echo $peminjaman['jumlah']; ?> unit</span>
                        </div>
                    </div>
                    
                    <div class="peminjaman-badges">
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
                            ?>" style="width: 12px; height: 12px;"></i>
                            <?php echo ucfirst($peminjaman['status']); ?>
                        </span>
                    </div>
                    
                    <div class="peminjaman-actions">
                        <a href="peminjaman_detail.php?id=<?php echo $peminjaman['id']; ?>" class="btn-custom btn-primary-custom">
                            <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                            Detail
                        </a>
                        <?php if ($peminjaman['status'] == 'ditolak'): ?>
                        <button class="btn-custom btn-warning-custom" onclick="ajukanUlang(<?php echo $peminjaman['id']; ?>)">
                            <i data-lucide="redo" style="width: 16px; height: 16px;"></i>
                            Ajukan Ulang
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="inbox" style="width: 80px; height: 80px;"></i>
                </div>
                <h3 class="empty-state-title">Belum Ada Peminjaman</h3>
                <p class="empty-state-text">Anda belum pernah mengajukan peminjaman alat.</p>
                <a href="daftar_alat.php" class="btn-custom btn-primary-custom" style="margin-top: 15px;">
                    <i data-lucide="list" style="width: 16px; height: 16px;"></i>
                    Lihat Daftar Alat
                </a>
            </div>
        <?php endif; ?>
    </main>
<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div style="color: var(--success-color); margin-bottom: 15px;">
                    <i data-lucide="check-circle" style="width: 64px; height: 64px;"></i>
                </div>
                <h5 class="modal-title" id="successModalLabel" style="font-weight: 400;">Berhasil!</h5>
                <p class="mt-3" style="font-weight: 300; color: #6c757d;" id="modal-message-text">
                    <!-- Pesan akan dimasukkan di sini oleh JavaScript -->
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button type="button" class="btn-custom btn-primary-custom" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

        document.addEventListener('DOMContentLoaded', function() {
            // Ambil pesan dari atribut data pada body
            const body = document.body;
            const message = body.getAttribute('data-popup-message');
                
            // Jika ada pesan, tampilkan modal
            if (message) {
                // Set teks pesan ke dalam modal
                document.getElementById('modal-message-text').textContent = message;
                
                // Inisialisasi dan tampilkan modal Bootstrap
                const successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
                
                // Re-render Lucide icons di dalam modal
                lucide.createIcons();
            }
        });
                
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

        function ajukanUlang(peminjamanId) {
            if (confirm('Apakah Anda ingin mengajukan ulang peminjaman ini?')) {
                // Redirect to pinjam page with previous data
                window.location.href = `pinjam_alat.php?ulang=${peminjamanId}`;
            }
        }
    </script>

</body>
</html>