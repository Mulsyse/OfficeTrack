<?php
// Memulai session
session_start();

// Memuat file koneksi database dan helper
require_once '../config/database.php';
require_once '../config/helpers.php'; // --- TAMBAHKAN BARIS INI ---

// Periksa login dan role user
check_login();
check_role('admin');

// --- PERBAIKAN CARA KONEKSI ---
// Ambil koneksi database dari kelas Database (pola Singleton)
 $conn = Database::getConnection();

try {
    // Siapkan dan jalankan query untuk menghapus semua log
    $stmt = $conn->prepare("DELETE FROM log_aktivitas");
    $stmt->execute();

    // --- HAPUS BARIS INI ---
    // $stmt->close(); // Method ini tidak ada di PDO

    // --- HAPUS BARIS INI (LOGIKA TIDAK KONSISTEN) ---
    // log_activity($_SESSION['user']['id'], "Menghapus semua log aktivitas");

    // Set pesan sukses dan arahkan kembali
    $_SESSION['success'] = "Semua log aktivitas berhasil dihapus.";
    header("Location: log_activity.php");
    exit();

} catch (PDOException $e) {
    // Jika terjadi error, tampilkan pesan error
    $_SESSION['error'] = "Gagal menghapus log: " . $e->getMessage();
    header("Location: log_activity.php");
    exit();
}
?>