<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('admin');

// Get statistics
 $db = new Database();
 $conn = $db->getConnection();

// Total users
 $stmt_users = $conn->prepare("SELECT COUNT(*) as total FROM users");
 $stmt_users->execute();
 $result_users = $stmt_users->get_result();
 $total_users = $result_users->fetch_assoc()['total'];

// Total alat
 $stmt_alat = $conn->prepare("SELECT COUNT(*) as total FROM alat");
 $stmt_alat->execute();
 $result_alat = $stmt_alat->get_result();
 $total_alat = $result_alat->fetch_assoc()['total'];

// Total peminjaman
 $stmt_peminjaman = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman");
 $stmt_peminjaman->execute();
 $result_peminjaman = $stmt_peminjaman->get_result();
 $total_peminjaman = $result_peminjaman->fetch_assoc()['total'];

// Peminjaman pending
 $stmt_pending = $conn->prepare("SELECT COUNT(*) as total FROM peminjaman WHERE status = 'pending'");
 $stmt_pending->execute();
 $result_pending = $stmt_pending->get_result();
 $total_pending = $result_pending->fetch_assoc()['total'];

// Recent activities
 $stmt_activities = $conn->prepare("SELECT la.*, u.nama FROM log_aktivitas la JOIN users u ON la.user_id = u.id ORDER BY la.waktu DESC LIMIT 10");
 $stmt_activities->execute();
 $result_activities = $stmt_activities->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin</title>
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
                <h1 class="page-title">Dashboard Admin</h1>
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
                            <div class="stat-value"><?php echo $total_users; ?></div>
                            <div class="stat-label">Total User</div>
                        </div>
                        <div class="stat-icon primary">
                            <i data-lucide="users"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_alat; ?></div>
                            <div class="stat-label">Total Alat</div>
                        </div>
                        <div class="stat-icon success">
                            <i data-lucide="wrench"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_peminjaman; ?></div>
                            <div class="stat-label">Total Peminjaman</div>
                        </div>
                        <div class="stat-icon info">
                            <i data-lucide="hand"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_pending; ?></div>
                            <div class="stat-label">Peminjaman Pending</div>
                        </div>
                        <div class="stat-icon warning">
                            <i data-lucide="clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="activity-card">
                    <div class="card-header-custom">
                        <h5 class="mb-0">Aktivitas Terbaru</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Aktivitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($activity = $result_activities->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($activity['waktu'])); ?></td>
                                    <td><?php echo $activity['nama']; ?></td>
                                    <td><?php echo $activity['aktivitas']; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
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