<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('admin');

// Handle form submission
 $error = '';
 $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate input
    if (empty($nama) || empty($username) || empty($password) || empty($role)) {
        $error = "Semua field harus diisi!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter!";
    } else {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nama, $username, $hashed_password, $role);
            
            if ($stmt->execute()) {
                log_activity($_SESSION['user_id'], "Menambahkan user baru: $nama");
                $success = "User berhasil ditambahkan!";
                header("Location: users.php");
                exit();
            } else {
                $error = "Gagal menambahkan user!";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah User</title>
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

        /* Form Card */
        .form-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header-custom {
            padding: 20px 25px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-custom h5 {
            font-weight: 400;
            font-size: 1.1rem;
            margin: 0;
        }

        .card-body-custom {
            padding: 30px;
        }

        /* Form Styling */
        .form-label {
            font-weight: 400;
            color: #495057;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
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

        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
            margin-top: 5px;
        }

        /* Buttons */
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 400;
            transition: all 0.2s ease;
            border: none;
            display: inline-flex;
            align-items: center;
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

        .btn-secondary-custom {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary-custom:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.3);
        }

        /* Alerts */
        .alert-custom {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
            display: flex;
            align-items: center;
        }

        .alert-danger-custom {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        .alert-success-custom {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
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

            .card-body-custom {
                padding: 20px;
            }
        }

        .menu-arrow {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        
        .menu-item[aria-expanded="true"] .menu-arrow {
            transform: rotate(180deg);
        }

        /* Additional styles for better form appearance */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-col {
            flex: 1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        /* Icon styling */
        .icon-sm {
            width: 16px;
            height: 16px;
            margin-right: 5px;
        }

        /* Password strength indicator */
        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 3px;
            background-color: #eaeaea;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s ease;
        }

        .strength-weak {
            background-color: var(--danger-color);
            width: 33%;
        }

        .strength-medium {
            background-color: var(--warning-color);
            width: 66%;
        }

        .strength-strong {
            background-color: var(--success-color);
            width: 100%;
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
            <div class="collapse dropdown-menu-custom show" id="masterDataDropdown">
                <a href="users.php" class="menu-item active">
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
                <h1 class="page-title">Tambah User</h1>
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

        <!-- Form Card -->
        <div class="form-card">
            <div class="card-header-custom">
                <h5>Form Tambah User</h5>
                <span class="badge-custom badge-admin">
                    User Baru
                </span>
            </div>
            <div class="card-body-custom">
                <?php if ($error): ?>
                    <div class="alert-custom alert-danger-custom">
                        <i data-lucide="alert-circle" style="margin-right: 10px;"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert-custom alert-success-custom">
                        <i data-lucide="check-circle" style="margin-right: 10px;"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="nama" class="form-label">
                                    <i data-lucide="user" class="icon-sm"></i>
                                    Nama Lengkap
                                </label>
                                <input type="text" class="form-control" id="nama" name="nama" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="username" class="form-label">
                                    <i data-lucide="at-sign" class="icon-sm"></i>
                                    Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="password" class="form-label">
                                    <i data-lucide="lock" class="icon-sm"></i>
                                    Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="password-strength">
                                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                </div>
                                <div class="form-text">Minimal 6 karakter</div>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="role" class="form-label">
                                    <i data-lucide="shield" class="icon-sm"></i>
                                    Role
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">Pilih Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="petugas">Petugas</option>
                                    <option value="peminjam">Peminjam</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-custom btn-primary-custom">
                            <i data-lucide="save" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                            Simpan User
                        </button>
                        <a href="users.php" class="btn-custom btn-secondary-custom">
                            <i data-lucide="arrow-left" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                            Kembali
                        </a>
                    </div>
                </form>
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

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            
            // Remove all strength classes
            strengthBar.classList.remove('strength-weak', 'strength-medium', 'strength-strong');
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
            } else if (password.length < 6) {
                strengthBar.classList.add('strength-weak');
            } else if (password.length < 10) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });
    </script>
</body>
</html>