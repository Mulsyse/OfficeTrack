<?php

// Memastikan file Database.php sudah di-include
require_once 'database.php';

/**
 * Fungsi untuk memeriksa apakah user sudah login.
 * Jika belum, akan diarahkan ke halaman login.
 */
if (!function_exists('check_login')) {
    function check_login() {
        if (!isset($_SESSION['user'])) {
            header("Location: ../auth/login.php");
            exit();
        }
    }
}

/**
 * Fungsi untuk memeriksa apakah user memiliki peran (role) yang sesuai.
 * @param string $required_role Peran yang diperlukan untuk mengakses halaman (misal: 'admin', 'user').
 */
if (!function_exists('check_role')) {
    function check_role($required_role) {
        check_login(); // Pastikan user sudah login
        
        if ($_SESSION['user']['role'] !== $required_role) {
            http_response_code(403);
            echo "
                <!DOCTYPE html>
                <html lang='id'>
                <head>
                    <meta charset='UTF-8'>
                    <title>Akses Ditolak</title>
                    <style>
                        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5f5; text-align: center; }
                        .container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                        h1 { color: #d9534f; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <h1>Akses Ditolak</h1>
                        <p>Anda tidak memiliki izin untuk mengakses halaman ini.</p>
                        <a href='../auth/login.php'>Kembali ke Login</a>
                    </div>
                </body>
                </html>
            ";
            exit();
        }
    }
}

/**
 * Fungsi untuk mencatat aktivitas user ke dalam tabel log_aktivitas.
 * @param int $user_id ID user yang melakukan aktivitas.
 * @param string $aktivitas Deskripsi aktivitas yang dilakukan.
 * @return bool Mengembalikan true jika berhasil, false jika gagal.
 */
if (!function_exists('log_activity')) {
    function log_activity($user_id, $aktivitas) {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, waktu) VALUES (?, ?, NOW())");
            return $stmt->execute([$user_id, $aktivitas]);
        } catch (PDOException $e) {
            // error_log($e->getMessage()); // Opsional: log error ke file
            return false;
        }
    }
}
if (!function_exists('sanitize_input')) {
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
}

/**
 * Fungsi untuk memformat tanggal dari format Y-m-d menjadi format Indonesia (d F Y)
 * Contoh: 2024-01-25 menjadi 25 Januari 2024
 *
 * @param string $tanggal Tanggal dalam format Y-m-d
 * @return string Tanggal yang sudah diformat atau '-' jika kosong
 */
function format_tanggal($tanggal) {
    // Jika tanggal kosong atau null, kembalikan string '-'
    if (empty($tanggal)) {
        return '-';
    }
    
    // Array untuk nama bulan dalam bahasa Indonesia
    $bulan = [
        '01' => 'Januari',
        '02' => 'Februari',
        '03' => 'Maret',
        '04' => 'April',
        '05' => 'Mei',
        '06' => 'Juni',
        '07' => 'Juli',
        '08' => 'Agustus',
        '09' => 'September',
        '10' => 'Oktober',
        '11' => 'November',
        '12' => 'Desember',
    ];

    // Pecah string tanggal (asumsi format Y-m-d)
    $pecahkan = explode('-', $tanggal);

    // Jika format tidak sesuai, kembalikan tanggal asli
    if (count($pecahkan) !== 3) {
        return $tanggal;
    }
    
    // Return format: d F Y (contoh: 25 Januari 2024)
    return $pecahkan[2] . ' ' . $bulan[$pecahkan[1]] . ' ' . $pecahkan[0];
}

?>