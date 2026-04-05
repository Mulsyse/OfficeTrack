<?php
// Memulai session
session_start();

// Memuat file koneksi database dan helper
require_once '../config/database.php';
require_once '../config/helpers.php';  // Memuat fungsi-fungsi bantu

// --- TAMBAHKAN BARIS INI ---
// Ambil koneksi database dari kelas Database dan simpan ke variabel $conn
 $conn = Database::getConnection();

// Periksa login dan role user
check_login();
check_role('admin');

// Cek dan ambil pesan dari session untuk ditampilkan
 $success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
 $error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Hapus pesan dari session agar tidak muncul lagi saat halaman direfresh
unset($_SESSION['success']);
unset($_SESSION['error']);

// --- PROSES PENGELOLAAN DATA ---

// Handle Hapus Data
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $sql = "DELETE FROM kategori WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        log_activity($_SESSION['user']['id'], "Menghapus kategori dengan ID: $id");
        $_SESSION['success'] = "Kategori berhasil dihapus!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Gagal menghapus kategori: " . $e->getMessage();
    }
    header("Location: kategori.php");
    exit();
}

// Handle Tambah/Edit Data (dari POST form)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan bersihkan input
    $nama_kategori = sanitize_input($_POST['nama_kategori']);
    $id = isset($_POST['id']) ? $_POST['id'] : null;

    // Validasi input
    if (empty($nama_kategori)) {
        $_SESSION['error'] = "Nama kategori tidak boleh kosong!";
    } else {
        try {
            if (!empty($id)) {
                // --- PROSES EDIT ---
                $sql = "UPDATE kategori SET nama_kategori = :nama_kategori WHERE id = :id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':nama_kategori', $nama_kategori);
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->execute();

                log_activity($_SESSION['user']['id'], "Mengedit kategori menjadi: $nama_kategori");
                $_SESSION['success'] = "Kategori berhasil diperbarui!";
            } else {
                // --- PROSES TAMBAH ---
                $sql = "INSERT INTO kategori (nama_kategori) VALUES (:nama_kategori)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':nama_kategori', $nama_kategori);
                $stmt->execute();

                log_activity($_SESSION['user']['id'], "Menambahkan kategori baru: $nama_kategori");
                $_SESSION['success'] = "Kategori berhasil ditambahkan!";
            }
        } catch (PDOException $e) {
            // Cek apakah error karena duplikat data (kode error 1062 untuk duplikat entry)
            if ($e->errorInfo[1] == 1062) {
                $_SESSION['error'] = "Gagal! Nama kategori '$nama_kategori' sudah ada.";
            } else {
                $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
            }
        }
    }
    // Redirect untuk mencegah resubmission form saat refresh
    header("Location: kategori.php");
    exit();
}

// --- AMBIL DATA UNTUK DITAMPILKAN ---
 $kategori_list = [];
try {
    $sql = "SELECT * FROM kategori ORDER BY nama_kategori ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $kategori_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Gagal memuat data kategori: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kategori</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- CSS Anda tetap sama, saya tidak menuliskannya ulang untuk menghemat ruang -->
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

        /* Kategori Table Card */
        .table-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .card-header-custom {
            padding: 20px 25px;
            border-bottom: 1px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-add {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 300;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }

        .btn-add:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-add i {
            margin-right: 8px;
        }

        .table-custom {
            margin-bottom: 0;
        }

        .table-custom thead th {
            border-bottom: 1px solid #eaeaea;
            font-weight: 400;
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px 25px;
        }

        .table-custom tbody td {
            padding: 15px 25px;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background-color: rgba(255, 190, 11, 0.1);
            color: var(--warning-color);
        }

        .btn-edit:hover {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-delete {
            background-color: rgba(251, 86, 7, 0.1);
            color: var(--danger-color);
        }

        .btn-delete:hover {
            background-color: var(--danger-color);
            color: white;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 10px;
            border: none;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            border-bottom: 1px solid #eaeaea;
            padding: 20px 25px;
        }

        .modal-title {
            font-weight: 300;
            font-size: 1.25rem;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            border-top: 1px solid #eaeaea;
            padding: 15px 25px;
        }

        .form-label {
            font-weight: 400;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 10px 15px;
            font-weight: 300;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.1);
        }

        .btn-primary-custom {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 300;
            transition: all 0.2s ease;
        }

        .btn-primary-custom:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary-custom {
            background-color: transparent;
            color: #6c757d;
            border: 1px solid #eaeaea;
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 300;
            transition: all 0.2s ease;
        }

        .btn-secondary-custom:hover {
            background-color: #f8f9fa;
            color: #6c757d;
        }

        .alert-custom {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 300;
            border: none;
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
            <span class="sidebar-logo">Office Track</span>
        </div>
        <nav class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i data-lucide="home" class="menu-icon"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#masterDataDropdown" aria-expanded="true">
                <i data-lucide="database" class="menu-icon"></i>
                <span class="menu-text">Master Data</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse show dropdown-menu-custom" id="masterDataDropdown">
                <a href="users.php" class="menu-item">
                    <span class="menu-text">Data User</span>
                </a>
                <a href="alat.php" class="menu-item">
                    <span class="menu-text">Data Alat</span>
                </a>
                <a href="kategori.php" class="menu-item active">
                    <span class="menu-text">Data Kategori</span>
                </a>
            </div>
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
                <h1 class="page-title">Data Kategori</h1>
            </div>
            <div class="user-profile">
                <span style="margin-right: 10px;"><?php echo htmlspecialchars($_SESSION['user']['nama']); ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['user']['nama'], 0, 1)); ?>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error_message): ?>
            <div class="alert alert-danger-custom alert-custom"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success-custom alert-custom"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <!-- Kategori Table -->
        <div class="table-card">
            <div class="card-header-custom">
                <h5 class="mb-0">Daftar Kategori</h5>
                <button class="btn-add" data-bs-toggle="modal" data-bs-target="#kategoriModal">
                    <i data-lucide="plus"></i> Tambah Kategori
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Kategori</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; if (!empty($kategori_list)): ?>
                            <?php foreach ($kategori_list as $kategori): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($kategori['nama_kategori']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($kategori['created_at'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn-action btn-edit" onclick="editKategori(<?php echo $kategori['id']; ?>, '<?php echo htmlspecialchars($kategori['nama_kategori'], ENT_QUOTES); ?>')">
                                            <i data-lucide="edit-2"></i>
                                        </button>
                                        <a href="kategori.php?delete=<?php echo $kategori['id']; ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">
                                            <i data-lucide="trash-2"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">Belum ada data kategori.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal -->
    <div class="modal fade" id="kategoriModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Tambah Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="kategori.php">
                    <div class="modal-body">
                        <input type="hidden" id="kategoriId" name="id">
                        <div class="mb-3">
                            <label for="nama_kategori" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary-custom" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn-primary-custom">Simpan</button>
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

        // Handle dropdown toggles
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(element => {
            element.addEventListener('click', function() {
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
                setTimeout(() => {
                    lucide.createIcons();
                }, 10);
            });
        });

        // Edit kategori function
        function editKategori(id, nama) {
            document.getElementById('modalTitle').textContent = 'Edit Kategori';
            document.getElementById('kategoriId').value = id;
            document.getElementById('nama_kategori').value = nama;
            const modal = new bootstrap.Modal(document.getElementById('kategoriModal'));
            modal.show();
            
            setTimeout(() => {
                lucide.createIcons();
            }, 100);
        }
        
        // Reset modal when closed
        document.getElementById('kategoriModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('modalTitle').textContent = 'Tambah Kategori';
            document.getElementById('kategoriId').value = '';
            document.getElementById('nama_kategori').value = '';
        });

        // Reinitialize Lucide icons after DOM changes
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>