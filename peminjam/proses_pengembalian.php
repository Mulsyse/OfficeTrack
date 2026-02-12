<?php
session_start();
require_once '../config/database.php';

// Fungsi ini biasanya ada di file helpers atau config
// Jika belum ada, Anda bisa menambahkannya
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

// Fungsi untuk log aktivitas (asumsi sudah ada)
if (!function_exists('log_activity')) {
    function log_activity($user_id, $action) {
        // Implementasi log aktivitas Anda, misalnya menyimpan ke database
        // true untuk contoh ini
        return true; 
    }
}


// Pastikan user sudah login dan rolenya peminjam
check_login();
check_role('peminjam');

 $db = new Database();
 $conn = $db->getConnection();
 $user_id = $_SESSION['user_id'];

// Proses hanya jika ada request POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $peminjaman_id = $_POST['peminjaman_id'];
    // Tanggal kembali diambil dari form, yang sudah di-set otomatis ke hari ini
    $tanggal_kembali = $_POST['tanggal_kembali']; 
    $kondisi_kembali = $_POST['kondisi_kembali'];
    $keterangan = sanitize_input($_POST['keterangan']);
    
    // --- VERIFIKASI DATA ---
    // Pastikan peminjaman ini milik user yang sedang login dan statusnya 'disetujui' atau 'dipinjam'
    $stmt_verify = $conn->prepare("SELECT id FROM peminjaman WHERE id = ? AND user_id = ? AND status IN ('disetujui', 'dipinjam')");
    $stmt_verify->bind_param("ii", $peminjaman_id, $user_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    
    if ($result_verify->num_rows > 0) {
        // Jika data valid, mulai transaksi database
        $conn->begin_transaction();
        
        try {
            // --- LANGKAH 1: Simpan data pengajuan pengembalian ke tabel 'pengembalian' ---
            $stmt_pengembalian = $conn->prepare("INSERT INTO pengembalian (peminjaman_id, tanggal_kembali, kondisi_kembali, keterangan) VALUES (?, ?, ?, ?)");
            $stmt_pengembalian->bind_param("isss", $peminjaman_id, $tanggal_kembali, $kondisi_kembali, $keterangan);
            $stmt_pengembalian->execute();
            
            // --- LANGKAH 2: Update status peminjaman menjadi 'pengajuan_pengembalian' ---
            // Status ini menunggu persetujuan dari admin
            $stmt_update_peminjaman = $conn->prepare("UPDATE peminjaman SET status = 'pengajuan_pengembalian' WHERE id = ?");
            $stmt_update_peminjaman->bind_param("i", $peminjaman_id);
            $stmt_update_peminjaman->execute();
            
            // Jika semua query berhasil, commit transaksi
            $conn->commit();
            
            // Log aktivitas user
            log_activity($user_id, "Mengajukan pengembalian untuk peminjaman ID: $peminjaman_id");
            
            // Set pesan sukses ke session
            $_SESSION['success'] = "Pengajuan pengembalian berhasil dikirim! Menunggu persetujuan admin.";
            
        } catch (Exception $e) {
            // Jika ada error, rollback semua perubahan
            $conn->rollback();
            
            // Set pesan error ke session
            $_SESSION['error'] = "Terjadi kesalahan saat mengajukan pengembalian. Silakan coba lagi. Error: " . $e->getMessage();
        }
        
    } else {
        // Set pesan error jika peminjaman tidak valid
        $_SESSION['error'] = "Data peminjaman tidak valid atau tidak ditemukan.";
    }
    
    // Tutup statement
    if (isset($stmt_verify)) $stmt_verify->close();
    if (isset($stmt_pengembalian)) $stmt_pengembalian->close();
    if (isset($stmt_update_peminjaman)) $stmt_update_peminjaman->close();
    
    // Redirect kembali ke halaman pengembalian
    header("Location: pengembalian.php");
    exit();
} else {
    // Jika bukan metode POST, redirect ke halaman pengembalian
    header("Location: pengembalian.php");
    exit();
}
?>