<?php
session_start();
require_once '../config/database.php';


// Pastikan hanya petugas yang bisa akses
check_login();
check_role('petugas');

 $db = new Database();
 $conn = $db->getConnection();

// Proses konfirmasi pengembalian
if (isset($_POST['submit_konfirmasi'])) {
    $conn->begin_transaction();
    try {
        $peminjaman_id = $_POST['peminjaman_id'];
        $kondisi_kembali = $_POST['kondisi_kembali'];
        $denda = (int)$_POST['denda'];
        $keterangan = $_POST['keterangan'];

        // 1. Ambil data peminjaman untuk update stok
        $stmt_peminjaman = $conn->prepare("SELECT alat_id, jumlah FROM peminjaman WHERE id = ?");
        $stmt_peminjaman->bind_param("i", $peminjaman_id);
        $stmt_peminjaman->execute();
        $data_peminjaman = $stmt_peminjaman->get_result()->fetch_assoc();

        // 2. Update stok alat
        $stmt_update_stok = $conn->prepare("UPDATE alat SET stok = stok + ? WHERE id = ?");
        $stmt_update_stok->bind_param("ii", $data_peminjaman['jumlah'], $data_peminjaman['alat_id']);
        $stmt_update_stok->execute();

        // 3. Insert ke tabel pengembalian
        $stmt_pengembalian = $conn->prepare("INSERT INTO pengembalian (peminjaman_id, tanggal_kembali, kondisi_kembali, denda, keterangan) VALUES (?, NOW(), ?, ?, ?)");
        $stmt_pengembalian->bind_param("isis", $peminjaman_id, $kondisi_kembali, $denda, $keterangan);
        $stmt_pengembalian->execute();

        // 4. Update status peminjaman
        $stmt_update_status = $conn->prepare("UPDATE peminjaman SET status = 'dikembalikan' WHERE id = ?");
        $stmt_update_status->bind_param("i", $peminjaman_id);
        $stmt_update_status->execute();

        $conn->commit();
        header('Location: pengembalian.php?success=1');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Query untuk menampilkan peminjaman yang menunggu konfirmasi
 $stmt = $conn->prepare("
    SELECT p.id, p.tanggal_pinjam, p.tanggal_kembali, u.nama AS nama_user, a.nama_alat, p.jumlah
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id
    JOIN alat a ON p.alat_id = a.id
    WHERE p.status = 'menunggu_konfirmasi'
    ORDER BY p.tanggal_pinjam ASC
");
 $stmt->execute();
 $result = $stmt->get_result();
 $total_menunggu = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="id">
<!-- ... (Kopikan seluruh bagian <head> dan <style> dari file sebelumnya di sini) ... -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pengembalian</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Gunakan CSS yang sama -->
    <style>
        /* ... (Sisipkan CSS yang sama dari file sebelumnya) ... */
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
        /* ... (Lanjutkan CSS lainnya) ... */
        * { box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-weight: 300; background-color: #f5f7fb; color: #333; margin: 0; padding: 0; }
        .sidebar { position: fixed; top: 0; left: 0; height: 100vh; width: var(--sidebar-width); background-color: white; box-shadow: 0 0 20px rgba(0, 0, 0, 0.05); z-index: 1000; transition: all 0.3s ease; overflow-y: auto; overflow-x: hidden; }
        .sidebar-header { padding: 20px; border-bottom: 1px solid #eaeaea; display: flex; align-items: center; }
        .sidebar-logo { font-size: 1.25rem; font-weight: 500; color: var(--primary-color); margin-left: 10px; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 12px 20px; display: flex; align-items: center; color: #6c757d; text-decoration: none; transition: all 0.2s ease; cursor: pointer; }
        .menu-item:hover { background-color: #f8f9fa; color: var(--primary-color); }
        .menu-item.active { background-color: rgba(67, 97, 238, 0.1); color: var(--primary-color); border-left: 3px solid var(--primary-color); }
        .menu-icon { margin-right: 10px; flex-shrink: 0; }
        .menu-text { font-weight: 300; }
        .dropdown-menu-custom { background-color: transparent; border: none; padding-left: 35px; }
        .dropdown-menu-custom .menu-item { font-size: 0.9rem; padding: 8px 20px; }
        .main-content { margin-left: var(--sidebar-width); height: 100vh; overflow-y: auto; overflow-x: hidden; transition: all 0.3s ease; padding: 20px; }
        .top-header { background-color: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 1.5rem; font-weight: 300; margin: 0; }
        .user-profile { display: flex; align-items: center; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; margin-right: 10px; }
        .stat-card { background-color: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); margin-bottom: 25px; transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100%; }
        .stat-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .stat-icon.primary { background-color: rgba(67, 97, 238, 0.1); color: var(--primary-color); }
        .stat-icon.success { background-color: rgba(6, 255, 165, 0.1); color: var(--success-color); }
        .stat-icon.info { background-color: rgba(0, 180, 216, 0.1); color: var(--info-color); }
        .stat-icon.warning { background-color: rgba(255, 190, 11, 0.1); color: var(--warning-color); }
        .stat-icon.danger { background-color: rgba(251, 86, 7, 0.1); color: var(--danger-color); }
        .stat-value { font-size: 2rem; font-weight: 300; margin-bottom: 5px; }
        .stat-label { color: #6c757d; font-size: 0.9rem; }
        .activity-card { background-color: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 25px; }
        .card-header-custom { padding: 20px 25px; border-bottom: 1px solid #eaeaea; font-weight: 400; font-size: 1.1rem; display: flex; justify-content: space-between; align-items: center; }
        .table-custom { margin-bottom: 0; width: 100%; }
        .table-custom thead th { border-bottom: 1px solid #eaeaea; font-weight: 400; color: #6c757d; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px 25px; white-space: nowrap; }
        .table-custom tbody td { padding: 15px 25px; vertical-align: middle; border-top: 1px solid #f1f1f1; }
        .table-custom tbody tr:hover { background-color: #f8f9fa; }
        .mobile-menu-btn { display: none; background: none; border: none; font-size: 1.5rem; color: var(--primary-color); }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } .mobile-menu-btn { display: block; } .stat-value { font-size: 1.5rem; } .table-custom { font-size: 0.85rem; } .table-custom thead th, .table-custom tbody td { padding: 10px 15px; } }
        .menu-arrow { margin-left: auto; transition: transform 0.3s ease; }
        .menu-item[aria-expanded="true"] .menu-arrow { transform: rotate(180deg); }
        .badge { padding: 5px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 400; }
        .btn-custom { padding: 8px 16px; border-radius: 8px; font-weight: 400; font-size: 0.9rem; border: none; transition: all 0.2s; }
        .btn-primary-custom { background-color: var(--primary-color); color: white; }
        .btn-primary-custom:hover { background-color: var(--secondary-color); color: white; }
        .btn-sm-custom { padding: 5px 12px; font-size: 0.8rem; }
    </style>
</head>
<body>
    <!-- Sidebar (Tetap Sama) -->
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
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#transaksiDropdown" aria-expanded="true">
                <i data-lucide="arrow-right-left" class="menu-icon"></i>
                <span class="menu-text">Transaksi</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse dropdown-menu-custom show" id="transaksiDropdown">
                <a href="peminjaman.php" class="menu-item">
                    <span class="menu-text">Data Peminjaman</span>
                </a>
                <a href="pengembalian.php" class="menu-item active">
                    <span class="menu-text">Konfirmasi Pengembalian</span>
                </a>
            </div>
            <!-- Laporan Dropdown -->
            <div class="menu-item" data-bs-toggle="collapse" data-bs-target="#laporanDropdown" aria-expanded="false">
                <i data-lucide="file-text" class="menu-icon"></i>
                <span class="menu-text">Laporan</span>
                <i data-lucide="chevron-down" class="menu-arrow"></i>
            </div>
            <div class="collapse dropdown-menu-custom " id="laporanDropdown">
                <a href="laporan_peminjaman.php" class="menu-item">
                    <span class="menu-text">Laporan Peminjaman</span>
                </a>
                <a href="laporan_pengembalian.php" class="menu-item">
                    <span class="menu-text">Laporan Pengembalian</span>
                </a>
            </div>
            <!-- ... (menu lainnya) ... -->
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
                <h1 class="page-title">Konfirmasi Pengembalian</h1>
            </div>
            <div class="user-profile">
                <span style="margin-right: 10px;">Selamat datang, <?php echo $_SESSION['nama']; ?></span>
                <div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i data-lucide="check-circle" style="width: 16px; height: 16px; margin-right: 8px;"></i>
                Pengembalian berhasil dikonfirmasi!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i data-lucide="alert-circle" style="width: 16px; height: 16px; margin-right: 8px;"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value"><?php echo $total_menunggu; ?></div>
                            <div class="stat-label">Menunggu Konfirmasi</div>
                        </div>
                        <div class="stat-icon info"><i data-lucide="clock"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value">10.000</div>
                            <div class="stat-label">Denda / Hari</div>
                        </div>
                        <div class="stat-icon warning"><i data-lucide="calendar-x"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value">50.000</div>
                            <div class="stat-label">Denda Kerusakan</div>
                        </div>
                        <div class="stat-icon danger"><i data-lucide="wrench"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-value">Aktif</div>
                            <div class="stat-label">Status Sistem</div>
                        </div>
                        <div class="stat-icon success"><i data-lucide="activity"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="activity-card">
            <div class="card-header-custom">
                <h5 class="mb-0">Daftar Pengajuan Pengembalian</h5>
                <span class="text-muted">Peminjaman yang menunggu untuk diverifikasi</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
            <table class="table-custom">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Peminjam</th>
                    <th>Alat</th>
                    <th>Tgl. Pinjam</th>
                    <!-- 1. Header diubah dari "Durasi" menjadi "Batas Kembali" -->
                    <th>Batas Kembali</th>
                    <th>Keterlambatan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
    <?php if ($result->num_rows > 0): ?>
    <?php $no = 1; while ($peminjaman = $result->fetch_assoc()): ?>
    <?php
        // PERUBAHAN 1: Tidak perlu menghitung jatuh tempo lagi,
        // karena kolom 'tanggal_kembali' sudah menyimpan tanggalnya.

        // PERUBAHAN 2: Perhitungan keterlambatan yang baru.
        // Keterlambatan = (Hari Ini) - (Tanggal Pinjam)
        $pinjam_date = new DateTime($peminjaman['tanggal_pinjam']);
        $today_date = new DateTime();
        
        // Hanya hitung jika hari ini lebih besar dari tanggal pinjam
        $terlambat = ($today_date > $pinjam_date) ? $today_date->diff($pinjam_date)->days : 0;
    ?>
    <tr>
        <td><?php echo $no++; ?></td>
        <td><?php echo $peminjaman['nama_user']; ?></td>
        <td><?php echo $peminjaman['nama_alat']; ?></td>
        <td><?php echo format_tanggal($peminjaman['tanggal_pinjam']); ?></td>
        <!-- PERUBAHAN 3: Tampilkan langsung data dari kolom 'tanggal_kembali' -->
        <td><?php echo format_tanggal($peminjaman['tanggal_kembali']); ?></td>
        <td>
            <?php if ($terlambat > 0): ?>
                <span class="badge bg-danger"><?php echo $terlambat; ?> Hari</span>
            <?php else: ?>
                <span class="badge bg-success">Tepat Waktu</span>
            <?php endif; ?>
        </td>
        <td>
            <button class="btn-custom btn-primary-custom btn-sm-custom" onclick="openKonfirmasiModal(<?php echo $peminjaman['id']; ?>, '<?php echo $peminjaman['nama_user']; ?>', '<?php echo $peminjaman['nama_alat']; ?>', <?php echo $terlambat; ?>)">
                <i data-lucide="check-square" style="width: 14px; height: 14px;"></i>
                Konfirmasi
            </button>
        </td>
    </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="7" class="text-center">Tidak ada pengajuan pengembalian yang menunggu.</td>
    </tr>
<?php endif; ?>
            </tbody>
        </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Konfirmasi -->
    <div class="modal fade" id="konfirmasiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Pengembalian</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" id="peminjaman_id" name="peminjaman_id">
                    <input type="hidden" id="denda" name="denda">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <strong>Detail Peminjaman:</strong><br>
                            <span id="modal_info"></span>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kondisi Alat Saat Dikembalikan</label>
                            <select class="form-select" id="kondisi_kembali" name="kondisi_kembali" required onchange="hitungDenda()">
                                <option value="">Pilih Kondisi</option>
                                <option value="baik">Baik</option>
                                <option value="rusak_ringan">Rusak Ringan</option>
                                <option value="rusak_berat">Rusak Berat</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Denda</label>
                            <input type="text" class="form-control" id="denda_display" readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="3" placeholder="Tambahkan keterangan (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="submit_konfirmasi" class="btn-custom btn-primary-custom">
                            <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            Ya, Konfirmasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let dendaKeterlambatan = 0;
        const dendaKerusakan = 50000;
        function openKonfirmasiModal(id, namaUser, namaAlat, terlambat) {
            document.getElementById('peminjaman_id').value = id;
            document.getElementById('modal_info').innerHTML = `Peminjam: ${namaUser}<br>Alat: ${namaAlat}<br>Keterlambatan: ${terlambat} Hari`;
            dendaKeterlambatan = terlambat * 10000;
            document.getElementById('denda_display').value = 'Rp ' + dendaKeterlambatan.toLocaleString('id-ID');
            document.getElementById('kondisi_kembali').value = '';
            document.getElementById('denda').value = dendaKeterlambatan;
            document.getElementById('keterangan').value = '';
            const modal = new bootstrap.Modal(document.getElementById('konfirmasiModal'));
            modal.show();
        }
        function hitungDenda() {
            const kondisi = document.getElementById('kondisi_kembali').value;
            let totalDenda = dendaKeterlambatan;
            if (kondisi && kondisi !== 'baik') {
                totalDenda += dendaKerusakan;
            }
            document.getElementById('denda').value = totalDenda;
            document.getElementById('denda_display').value = 'Rp ' + totalDenda.toLocaleString('id-ID');
        }
        lucide.createIcons();
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        document.querySelectorAll('.dropdown-toggle').forEach(item => {
            item.addEventListener('click', function() {
                const expanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !expanded);
                setTimeout(() => lucide.createIcons(), 10);
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
            lucide.createIcons();
        });
    </script>
</body>
</html>