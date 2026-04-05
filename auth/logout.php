<?php
session_start();
require_once '../config/database.php';

// --- PERUBAHAN 1: Gunakan struktur sesi yang baru ---
// Cek apakah user benar-benar sudah login
if (!isset($_SESSION['user']['id'])) {
    // Jika tidak, langsung arahkan ke login
    header("Location: login.php");
    exit();
}

// --- PERUBAHAN 2: Hapus pemanggilan fungsi log_activity() yang lama ---
// --- PERUBAHAN 3: Ganti dengan query PDO langsung ---
// Catat aktivitas logout ke database
 $pdo = Database::getConnection();
 $user_id = $_SESSION['user']['id'];
 $log_stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, waktu) VALUES (?, ?, NOW())");
 $log_stmt->execute([$user_id, "Logout dari sistem"]);

// Hancurkan semua data sesi
session_unset();
session_destroy();

// Arahkan kembali ke halaman login
header("Location: login.php");
exit();
?>