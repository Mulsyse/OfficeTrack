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

// Get search and filter parameters
 $search = isset($_GET['search']) ? $_GET['search'] : '';
 $kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Get categories for filter
 $stmt_kategori = $conn->prepare("SELECT * FROM kategori ORDER BY nama_kategori");
 $stmt_kategori->execute();
 $result_kategori = $stmt_kategori->get_result();

// Build query
 $query = "SELECT a.*, k.nama_kategori FROM alat a LEFT JOIN kategori k ON a.kategori_id = k.id WHERE 1=1";
 $params = [];

if (!empty($search)) {
    $query .= " AND a.nama_alat LIKE ?";
    $params[] = "%$search%";
}

if (!empty($kategori_filter)) {
    $query .= " AND a.kategori_id = ?";
    $params[] = $kategori_filter;
}

 $query .= " ORDER BY a.nama_alat";

// Prepare and execute statement
 $stmt = $conn->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
 $stmt->execute();
 $result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Alat - Sistem Peminjaman</title>
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

        /* Alat Cards */
        .alat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .alat-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .alat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .alat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }

        .alat-title {
            font-size: 1.1rem;
            font-weight: 400;
            color: #333;
            margin-bottom: 5px;
        }

        .alat-kategori {
            font-size: 0.85rem;
            color: #6c757d;
        }

        .alat-id {
            font-size: 0.75rem;
            color: #adb5bd;
            font-weight: 300;
        }

        .alat-badges {
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

        .alat-description {
            color: #6c757d;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .alat-actions {
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

        .btn-success-custom {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success-custom:hover {
            background-color: #05d693;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(6, 255, 165, 0.3);
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

        /* Modal */
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-bottom: 1px solid #eaeaea;
            padding: 20px 25px;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: 1px solid #eaeaea;
            padding: 15px 25px;
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

            .alat-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Additional enhancements */
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
            <a href="daftar_alat.php" class="menu-item active">
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
                <h1 class="page-title">Daftar Alat Tersedia</h1>
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

        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-6">
                        <label for="search" class="form-label">
                            <i data-lucide="search" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                            Cari Alat
                        </label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo $search; ?>" placeholder="Masukkan nama alat...">
                    </div>
                    <div class="col-md-4">
                        <label for="kategori" class="form-label">
                            <i data-lucide="filter" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                            Filter Kategori
                        </label>
                        <select class="form-select" id="kategori" name="kategori">
                            <option value="">Semua Kategori</option>
                            <?php while ($kategori = $result_kategori->fetch_assoc()): ?>
                            <option value="<?php echo $kategori['id']; ?>" <?php echo $kategori_filter == $kategori['id'] ? 'selected' : ''; ?>>
                                <?php echo $kategori['nama_kategori']; ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label><br>
                        <button type="submit" class="btn-custom btn-primary-custom w-100">
                            <i data-lucide="search" style="width: 16px; height: 16px;"></i>
                            Cari
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Alat Grid -->
        <?php if ($result->num_rows > 0): ?>
            <div class="alat-grid">
                <?php while ($alat = $result->fetch_assoc()): ?>
                <div class="alat-card">
                    <div class="alat-card-header">
                        <div>
                            <h5 class="alat-title"><?php echo $alat['nama_alat']; ?></h5>
                            <span class="alat-kategori"><?php echo $alat['nama_kategori'] ?: 'Tidak ada kategori'; ?></span>
                        </div>
                        <span class="alat-id">#<?php echo str_pad($alat['id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    
                    <div class="alat-badges">
                        <span class="badge-custom badge-<?php echo $alat['stok'] > 5 ? 'success' : ($alat['stok'] > 0 ? 'warning' : 'danger'); ?>">
                            <i data-lucide="package" style="width: 12px; height: 12px;"></i>
                            Stok: <?php echo $alat['stok']; ?>
                        </span>
                        <span class="badge-custom badge-<?php echo $alat['kondisi'] == 'baik' ? 'success' : ($alat['kondisi'] == 'rusak_ringan' ? 'warning' : 'danger'); ?>">
                            <i data-lucide="check-circle" style="width: 12px; height: 12px;"></i>
                            <?php echo ucfirst($alat['kondisi']); ?>
                        </span>
                    </div>
                    
                    <p class="alat-description"><?php echo substr($alat['deskripsi'] ?: 'Tidak ada deskripsi', 0, 100); ?></p>
                    
                    <div class="alat-actions">
                        <button class="btn-custom btn-primary-custom" onclick="showDetail(<?php echo $alat['id']; ?>)">
                            <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                            Detail
                        </button>
                        <a href="pinjam_alat.php?alat_id=<?php echo $alat['id']; ?>" class="btn-custom btn-success-custom">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            Pinjam
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="package-x" style="width: 80px; height: 80px;"></i>
                </div>
                <h3 class="empty-state-title">Tidak ada alat tersedia</h3>
                <p class="empty-state-text">Coba ubah filter atau kata kunci pencarian</p>
            </div>
        <?php endif; ?>
    </main>

    <!-- Detail Modal -->
    <div class="modal fade" id="detailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Alat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn-custom btn-success-custom" id="pinjamBtn">
                        <i data-lucide="plus" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                        Pinjam Alat Ini
                    </button>
                </div>
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
        
        // Reinitialize Lucide icons after DOM changes
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });

        function showDetail(alatId) {
            // Load alat detail via AJAX (simplified version)
            fetch(`get_alat_detail.php?id=${alatId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('modalContent').innerHTML = data;
                    document.getElementById('pinjamBtn').onclick = function() {
                        window.location.href = `pinjam_alat.php?alat_id=${alatId}`;
                    };
                    const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                    modal.show();
                    // Reinitialize icons in modal
                    setTimeout(() => lucide.createIcons(), 100);
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback: redirect to pinjam page
                    window.location.href = `pinjam_alat.php?alat_id=${alatId}`;
                });
        }
    </script>
</body>
</html>