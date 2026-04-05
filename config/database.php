<?php

class Database {
    // Properti untuk menyimpan instance kelas (Singleton Pattern)
    private static $instance = null;
    
    // Properti untuk menyimpan objek PDO
    private $pdo;

    // Konstruktor private untuk mencegah pembuatan objek baru dari luar kelas
    private function __construct() {
        // --- SESUAIKAN KONFIGURASI DATABASE ANDA DI SINI ---
        $host = 'localhost';
        $db   = 'peminjaman_alat'; // Nama database Anda
        $user = 'root';          // Username database Anda
        $pass = '';              // Password database Anda
        $charset = 'utf8mb4';

        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            // Jika koneksi gagal, hentikan skrip dan tampilkan pesan error
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    // Metode statis untuk mendapatkan instance koneksi (Singleton Pattern)
    public static function getConnection() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    // Mencegah kloning objek dari luar
    private function __clone() {}

    // Mencegah unserialisasi objek dari luar
    public function __wakeup() {}
}
?>