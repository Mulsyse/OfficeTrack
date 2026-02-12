<?php
session_start();
require_once '../config/database.php';

// Check login and role
check_login();
check_role('peminjam');

 $db = new Database();
 $conn = $db->getConnection();
 $user_id = $_SESSION['user_id'];

 $error = '';
 $success = '';

// Get alat data if specified
 $selected_alat = null;
if (isset($_GET['alat_id'])) {
    $alat_id = $_GET['alat_id'];
    $stmt = $conn->prepare("SELECT * FROM alat WHERE id = ? AND stok > 0");
    $stmt->bind_param("i", $alat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_alat = $result->fetch_assoc();
    $stmt->close();
}

// Get all available alat for dropdown
 $stmt_alat = $conn->prepare("SELECT a.*, k.nama_kategori FROM alat a LEFT JOIN kategori k ON a.kategori_id = k.id WHERE a.stok > 0 ORDER BY a.nama_alat");
 $stmt_alat->execute();
 $result_alat = $stmt_alat->get_result();

// Handle form submission
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $alat_id = $_POST['alat_id'];
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali'];
    $jumlah = $_POST['jumlah'];
    $keterangan = sanitize_input($_POST['keterangan']);
    
    // Validate stock
    $stmt_stock = $conn->prepare("SELECT stok FROM alat WHERE id = ?");
    $stmt_stock->bind_param("i", $alat_id);
    $stmt_stock->execute();
    $result_stock = $stmt_stock->get_result();
    $alat_stock = $result_stock->fetch_assoc();
    $stmt_stock->close();
    
    if ($alat_stock['stok'] < $jumlah) {
        $error = "Stok tidak mencukupi! Stok tersedia: " . $alat_stock['stok'];
    } else {
        // Insert peminjaman
        $stmt = $conn->prepare("INSERT INTO peminjaman (user_id, alat_id, tanggal_pinjam, tanggal_kembali, jumlah, keterangan) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissis", $user_id, $alat_id, $tanggal_pinjam, $tanggal_kembali, $jumlah, $keterangan);
        
        if ($stmt->execute()) {
            // Update stock
            $stmt_update = $conn->prepare("UPDATE alat SET stok = stok - ? WHERE id = ?");
            $stmt_update->bind_param("ii", $jumlah, $alat_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            log_activity($user_id, "Mengajukan peminjaman alat ID: $alat_id");
            
            // Set pesan sukses ke session dan redirect
            $_SESSION['success_message'] = "Peminjaman berhasil diajukan! Menunggu persetujuan petugas.";
            header("Location: peminjaman_saya.php");
            exit(); // Penting untuk menghentikan eksekusi script
        } else {
            $error = "Gagal mengajukan peminjaman!";
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
    <title>Ajukan Peminjaman</title>
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
            overflow-x: hidden;
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

        /* Form Card */
        .form-card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-card:hover {
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

        /* Info Card */
        .info-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            margin-bottom: 25px;
        }

        .info-card h5 {
            font-weight: 400;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .info-list li {
            padding: 8px 0;
            display: flex;
            align-items: flex-start;
            font-weight: 300;
        }

        .info-list li i {
            margin-right: 10px;
            flex-shrink: 0;
            margin-top: 2px;
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
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .btn-success-custom {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success-custom:hover {
            background-color: #05d693;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(6, 255, 165, 0.3);
        }

        .btn-secondary-custom {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary-custom:hover {
            background-color: #5a6268;
            transform: translateY(-2px);
        }

        /* Alerts */
        .alert-custom {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease;
        }

        .alert-success-custom {
            background-color: rgba(6, 255, 165, 0.1);
            color: var(--success-color);
        }

        .alert-danger-custom {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Selected Alat Preview */
        .alat-preview {
            background-color: rgba(67, 97, 238, 0.05);
            border: 1px solid rgba(67, 97, 238, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .alat-preview-title {
            font-weight: 400;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .alat-preview-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .alat-preview-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .alat-preview-item i {
            margin-right: 5px;
            color: var(--primary-color);
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
            <a href="pinjam_alat.php" class="menu-item active">
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
                <h1 class="page-title">Ajukan Peminjaman</h1>
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

        <!-- Alerts -->
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

        <div class="row">
            <div class="col-lg-8">
                <!-- Form Card -->
                <div class="form-card">
                    <h5 style="font-weight: 400; margin-bottom: 25px; display: flex; align-items: center;">
                        <i data-lucide="file-text" style="margin-right: 10px;"></i>
                        Form Peminjaman
                    </h5>
                    
                    <?php if ($selected_alat): ?>
                    <div class="alat-preview">
                        <div class="alat-preview-title">
                            <i data-lucide="package" style="margin-right: 8px;"></i>
                            Alat Dipilih
                        </div>
                        <div class="alat-preview-details">
                            <div class="alat-preview-item">
                                <i data-lucide="tag"></i>
                                <?php echo $selected_alat['nama_alat']; ?>
                            </div>
                            <div class="alat-preview-item">
                                <i data-lucide="layers"></i>
                                Stok: <?php echo $selected_alat['stok']; ?>
                            </div>
                            <div class="alat-preview-item">
                                <i data-lucide="check-circle"></i>
                                <?php echo ucfirst($selected_alat['kondisi']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="alat_id" class="form-label">
                                <i data-lucide="package" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                                Pilih Alat
                            </label>
                            <select class="form-select" id="alat_id" name="alat_id" required>
                                <option value="">Pilih Alat</option>
                                <?php 
                                $stmt_alat->data_seek(0);
                                while ($alat = $result_alat->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $alat['id']; ?>" <?php echo ($selected_alat && $selected_alat['id'] == $alat['id']) ? 'selected' : ''; ?>>
                                    <?php echo $alat['nama_alat']; ?> (Stok: <?php echo $alat['stok']; ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="tanggal_pinjam" class="form-label">
                                        <i data-lucide="calendar" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                                        Tanggal Pinjam
                                    </label>
                                    <input type="date" class="form-control" id="tanggal_pinjam" name="tanggal_pinjam" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="tanggal_kembali" class="form-label">
                                        <i data-lucide="calendar" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                                        Tanggal Kembali (Rencana)
                                    </label>
                                    <input type="date" class="form-control" id="tanggal_kembali" name="tanggal_kembali" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label for="jumlah" class="form-label">
                                        <i data-lucide="hash" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                                        Jumlah
                                    </label>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah" min="1" value="1" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="keterangan" class="form-label">
                                <i data-lucide="message-square" style="width: 16px; height: 16px; margin-right: 5px;"></i>
                                Keterangan
                            </label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Jelaskan keperluan peminjaman (opsional)"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-custom btn-success-custom">
                                <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                                Ajukan Peminjaman
                            </button>
                            <a href="daftar_alat.php" class="btn-custom btn-secondary-custom">
                                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                                Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Info Card -->
                <div class="info-card">
                    <h5>
                        <i data-lucide="info" style="margin-right: 8px;"></i>
                        Petunjuk Peminjaman
                    </h5>
                    <ul class="info-list">
                        <li>
                            <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            Pilih alat yang tersedia
                        </li>
                        <li>
                            <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            Masukkan tanggal pinjam dan rencana pengembalian
                        </li>
                        <li>
                            <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            Jumlah tidak boleh melebihi stok tersedia
                        </li>
                        <li>
                            <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            Peminjaman perlu disetujui oleh petugas
                        </li>
                        <li>
                            <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            Anda akan menerima notifikasi setelah peminjaman disetujui
                        </li>
                    </ul>
                </div>
                
                <!-- Status Card -->
                <div class="form-card">
                    <h5 style="font-weight: 400; margin-bottom: 20px; display: flex; align-items: center;">
                        <i data-lucide="user" style="margin-right: 10px;"></i>
                        Status Anda
                    </h5>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <div style="display: flex; align-items: center;">
                            <i data-lucide="user" style="width: 16px; height: 16px; margin-right: 10px; color: var(--primary-color);"></i>
                            <span style="font-weight: 300;">Nama: <strong><?php echo $_SESSION['nama']; ?></strong></span>
                        </div>
                        <div style="display: flex; align-items: center;">
                            <i data-lucide="shield" style="width: 16px; height: 16px; margin-right: 10px; color: var(--primary-color);"></i>
                            <span style="font-weight: 300;">Role: <strong>Peminjam</strong></span>
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
        
        // Reinitialize Lucide icons after DOM changes
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
        
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('tanggal_pinjam').min = today;
        document.getElementById('tanggal_kembali').min = today;
        
        // Set default dates
        document.getElementById('tanggal_pinjam').value = today;
        
        // Calculate minimum return date (1 day after borrow date)
        document.getElementById('tanggal_pinjam').addEventListener('change', function() {
            const borrowDate = new Date(this.value);
            borrowDate.setDate(borrowDate.getDate() + 1);
            const minReturn = borrowDate.toISOString().split('T')[0];
            document.getElementById('tanggal_kembali').min = minReturn;
            
            if (document.getElementById('tanggal_kembali').value < minReturn) {
                document.getElementById('tanggal_kembali').value = minReturn;
            }
        });
        
        // Refresh icons when alerts are shown
        <?php if ($error || $success): ?>
        setTimeout(() => lucide.createIcons(), 100);
        <?php endif; ?>
                
        function handleSubmit(event) {
            // Nonaktifkan tombol submit dan ubah teksnya
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i data-lucide="loader-2" style="width: 16px; height: 16px; animation: spin 1s linear infinite;"></i> Mengajukan...';
                
            // Re-render icon untuk animasi loader
            lucide.createIcons();
                
            // Biarkan form submit
            return true;
        }
                
        // Animasi sederhana untuk loader
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
                
    </script>
</body>
</html>