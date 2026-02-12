-- Database: peminjaman_alat
CREATE DATABASE IF NOT EXISTS peminjaman_alat;
USE peminjaman_alat;

-- Tabel users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'petugas', 'peminjam') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel kategori
CREATE TABLE kategori (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_kategori VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel alat
CREATE TABLE alat (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nama_alat VARCHAR(100) NOT NULL,
    kategori_id INT,
    stok INT NOT NULL DEFAULT 0,
    kondisi ENUM('baik', 'rusak_ringan', 'rusak_berat') NOT NULL DEFAULT 'baik',
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori(id) ON DELETE SET NULL
);

-- Tabel peminjaman
CREATE TABLE peminjaman (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    alat_id INT NOT NULL,
    tanggal_pinjam DATE NOT NULL,
    tanggal_kembali DATE,
    jumlah INT NOT NULL DEFAULT 1,
    status ENUM('pending', 'disetujui', 'ditolak', 'dipinjam', 'dikembalikan') NOT NULL DEFAULT 'pending',
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (alat_id) REFERENCES alat(id) ON DELETE CASCADE
);

-- Tabel pengembalian
CREATE TABLE pengembalian (
    id INT PRIMARY KEY AUTO_INCREMENT,
    peminjaman_id INT NOT NULL,
    tanggal_kembali DATE NOT NULL,
    kondisi_kembali ENUM('baik', 'rusak_ringan', 'rusak_berat') NOT NULL,
    denda DECIMAL(10,2) DEFAULT 0,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (peminjaman_id) REFERENCES peminjaman(id) ON DELETE CASCADE
);

-- Tabel log_aktivitas
CREATE TABLE log_aktivitas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    aktivitas VARCHAR(255) NOT NULL,
    waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert data awal
-- Insert admin user (password: admin123)
INSERT INTO users (nama, username, password, role) VALUES 
('Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert petugas user (password: petugas123)
INSERT INTO users (nama, username, password, role) VALUES 
('Petugas', 'petugas', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'petugas');

-- Insert peminjam user (password: peminjam123)
INSERT INTO users (nama, username, password, role) VALUES 
('Peminjam', 'peminjam', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'peminjam');

-- Insert kategori
INSERT INTO kategori (nama_kategori) VALUES 
('Elektronik'),
('Peralatan Kantor'),
('Peralatan Olahraga'),
('Peralatan Kebun'),
('Lain-lain');

-- Insert alat
INSERT INTO alat (nama_alat, kategori_id, stok, kondisi, deskripsi) VALUES 
('Laptop', 1, 5, 'baik', 'Laptop untuk keperluan kerja'),
('Proyektor', 1, 3, 'baik', 'Proyektor untuk presentasi'),
('Meja', 2, 10, 'baik', 'Meja kerja'),
('Kursi', 2, 20, 'baik', 'Kursi kantor'),
('Bola Basket', 3, 8, 'baik', 'Bola basket standar'),
('Sekop', 4, 6, 'baik', 'Sekop untuk berkebun');
