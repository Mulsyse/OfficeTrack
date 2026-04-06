<?php
session_start();
require_once '../config/database.php';
require_once '../config/helpers.php';

// Pastikan user sudah login dan rolenya peminjam
check_login();
check_role('peminjam');

// --- PERBAIKAN 1: Ambil user_id dari sesi dengan struktur yang benar ---
 $user_data = $_SESSION['user'] ?? null;
if ($user_data === null) {
    $_SESSION['error'] = "Sesi tidak valid. Silakan login ulang.";
    header("Location: pengembalian.php");
    exit();
}
 $user_id = $user_data['id'];

// --- PERBAIKAN 2: Dapatkan koneksi PDO ---
 $conn = Database::getConnection();

// Proses hanya jika ada request POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $peminjaman_id = $_POST['peminjaman_id'];
    $tanggal_kembali = $_POST['tanggal_kembali']; 
    $kondisi_kembali = $_POST['kondisi_kembali'];
    $keterangan = sanitize_input($_POST['keterangan']);
    
    // --- VERIFIKASI DATA (Menggunakan PDO) ---
    // Pastikan peminjaman ini milik user yang sedang login dan statusnya 'disetujui'
    // Sekaligus, kita ambil tanggal batas kembali untuk menghitung denda
    $sql_verify = "SELECT id, tanggal_kembali AS batas_kembali FROM peminjaman WHERE id = :peminjaman_id AND user_id = :user_id AND status = 'disetujui'";
    $stmt_verify = $conn->prepare($sql_verify);
    $stmt_verify->bindParam(':peminjaman_id', $peminjaman_id, PDO::PARAM_INT);
    $stmt_verify->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_verify->execute();

    // fetch() akan mengembalikan data jika valid, false jika tidak
    $peminjaman_data = $stmt_verify->fetch(PDO::FETCH_ASSOC);

    if ($peminjaman_data) {
        // --- LOGIKA PERHITUNGAN DENDA ---
        $denda = 0;
        $batas_kembali = new DateTime($peminjaman_data['batas_kembali']);
        $hari_ini = new DateTime();
        
        // Cek apakah terlambat
        if ($hari_ini > $batas_kembali) {
            $selisih = $hari_ini->diff($batas_kembali);
            $hari_terlambat = $selisih->days;
            $denda_per_hari = 10000; // Misal denda Rp 10.000/hari
            $denda = $hari_terlambat * $denda_per_hari;
        }

        // Jika data valid, mulai transaksi database
        $conn->beginTransaction();
        
        try {
            // --- LANGKAH 1: Simpan data pengajuan ke tabel 'pengembalian' ---
            // Tambahkan kolom 'denda' ke query INSERT
            $sql_pengembalian = "INSERT INTO pengembalian (peminjaman_id, tanggal_kembali, kondisi_kembali, keterangan, denda) VALUES (:peminjaman_id, :tanggal_kembali, :kondisi_kembali, :keterangan, :denda)";
            $stmt_pengembalian = $conn->prepare($sql_pengembalian);
            
            $stmt_pengembalian->bindParam(':peminjaman_id', $peminjaman_id, PDO::PARAM_INT);
            $stmt_pengembalian->bindParam(':tanggal_kembali', $tanggal_kembali);
            $stmt_pengembalian->bindParam(':kondisi_kembali', $kondisi_kembali);
            $stmt_pengembalian->bindParam(':keterangan', $keterangan);
            // --- TAMBAHKAN BIND PARAM UNTUK DENDA ---
            $stmt_pengembalian->bindParam(':denda', $denda, PDO::PARAM_INT);
            $stmt_pengembalian->execute();
            
            // --- LANGKAH 2: Update status peminjaman ---
// --- LANGKAH 2: Update status peminjaman menjadi 'menunggu_konfirmasi' ---
            $sql_update = "UPDATE peminjaman SET status = 'menunggu_konfirmasi' WHERE id = :peminjaman_id";
            $stmt_update_peminjaman = $conn->prepare($sql_update);
            $stmt_update_peminjaman->bindParam(':peminjaman_id', $peminjaman_id, PDO::PARAM_INT);
            $stmt_update_peminjaman->execute();
            
            // Jika semua query berhasil, commit transaksi
            $conn->commit();
            
            $_SESSION['success'] = "Pengajuan pengembalian berhasil dikirim! Menunggu persetujuan admin.";
            
        } catch (PDOException $e) {
            // Jika ada error, rollback
            $conn->rollback();
            $_SESSION['error'] = "Terjadi kesalahan saat mengajukan pengembalian. Error: " . $e->getMessage();
        }
        
    } else {
        $_SESSION['error'] = "Data peminjaman tidak valid atau tidak ditemukan.";
    }
    
    // Redirect kembali ke halaman pengembalian
    header("Location: pengembalian.php");
    exit();
} else {
    header("Location: pengembalian.php");
    exit();
}
?>