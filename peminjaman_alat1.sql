-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2026 at 12:42 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `peminjaman_alat`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `laporan_peminjaman_periode` (IN `p_tanggal_mulai` DATE, IN `p_tanggal_selesai` DATE)   BEGIN
    SELECT 
        p.id,
        p.tanggal_pinjam,
        p.tanggal_kembali AS tanggal_batas_kembali,
        u.nama AS nama_peminjam,
        a.nama_alat,
        p.jumlah,
        p.status,
        COALESCE(peng.denda, 0) AS denda,
        peng.tanggal_kembali AS tanggal_dikembalikan
    FROM peminjaman p
    JOIN users u ON p.user_id = u.id -- Menggunakan user_id
    JOIN alat a ON p.alat_id = a.id
    LEFT JOIN pengembalian peng ON p.id = peng.peminjaman_id -- Menggunakan peminjaman_id
    WHERE p.tanggal_pinjam BETWEEN p_tanggal_mulai AND p_tanggal_selesai
    ORDER BY p.tanggal_pinjam DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `proses_pengembalian` (IN `p_id_peminjaman` INT, IN `p_tanggal_kembali` DATE, IN `p_kondisi_kembali` VARCHAR(20), IN `p_keterangan_pengembalian` TEXT, OUT `p_id_pengembalian` INT, OUT `p_total_denda` DECIMAL(10,2), OUT `p_status` VARCHAR(255))   BEGIN
    DECLARE v_denda_temp DECIMAL(10,2);
    DECLARE v_user_id_peminjam INT;
    DECLARE v_tanggal_pinjam_temp DATE;
    DECLARE v_tanggal_kembali_target DATE;
    
    -- Ambil data peminjaman yang diperlukan
    SELECT user_id, tanggal_pinjam, tanggal_kembali
    INTO v_user_id_peminjam, v_tanggal_pinjam_temp, v_tanggal_kembali_target
    FROM peminjaman
    WHERE id = p_id_peminjaman;
    
    -- Hitung denda (hanya satu item)
    SET v_denda_temp = hitung_denda(v_tanggal_pinjam_temp, v_tanggal_kembali_target, p_tanggal_kembali);
    
    -- Insert data pengembalian dengan nama kolom yang benar
    INSERT INTO pengembalian (peminjaman_id, tanggal_kembali, kondisi_kembali, denda, keterangan)
    VALUES (p_id_peminjaman, p_tanggal_kembali, p_kondisi_kembali, v_denda_temp, p_keterangan_pengembalian);
    
    SET p_id_pengembalian = LAST_INSERT_ID();
    SET p_total_denda = v_denda_temp;
    
    -- Update status peminjaman
    UPDATE peminjaman
    SET status = 'dikembalikan'
    WHERE id = p_id_peminjaman;
    
    -- Log activity (menggunakan kolom 'waktu')
    INSERT INTO log_aktivitas (user_id, aktivitas, waktu)
    VALUES (v_user_id_peminjam, CONCAT('Melakukan pengembalian peminjaman ID: ', p_id_peminjaman), NOW());
    
    SET p_status = 'Success';
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `tambah_peminjaman` (IN `p_user_id` INT, IN `p_alat_id` INT, IN `p_tanggal_pinjam` DATE, IN `p_tanggal_kembali` DATE, IN `p_jumlah` INT, IN `p_keterangan` TEXT, OUT `p_id_peminjaman` INT, OUT `p_status` VARCHAR(255))   BEGIN
    DECLARE v_stok_cukup BOOLEAN;
    
    -- Cek ketersediaan stok
    SET v_stok_cukup = cek_ketersediaan_alat(p_alat_id);
    
    IF v_stok_cukup = FALSE THEN
        SET p_status = 'Error: Stok alat tidak mencukupi.';
        SET p_id_peminjaman = NULL;
    ELSE
        -- Insert data peminjaman
        INSERT INTO peminjaman (user_id, alat_id, tanggal_pinjam, tanggal_kembali, jumlah, keterangan, status)
        VALUES (p_user_id, p_alat_id, p_tanggal_pinjam, p_tanggal_kembali, p_jumlah, p_keterangan, 'pending');
        
        -- Get ID peminjaman yang baru dibuat
        SET p_id_peminjaman = LAST_INSERT_ID();
        
        -- Log activity (menggunakan kolom 'waktu')
        INSERT INTO log_aktivitas (user_id, aktivitas, waktu)
        VALUES (p_user_id, CONCAT('Mengajukan peminjaman dengan ID: ', p_id_peminjaman), NOW());
        
        SET p_status = 'Success';
    END IF;
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `cek_ketersediaan_alat` (`alat_id` INT) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE v_stok_total INT DEFAULT 0;
    DECLARE v_jumlah_dipinjam INT DEFAULT 0;
    
    -- Ambil total stok alat
    SELECT stok INTO v_stok_total
    FROM alat 
    WHERE id = alat_id;
    
    -- Hitung total jumlah alat yang sedang dalam status 'dipinjam' atau 'disetujui'
    SELECT COALESCE(SUM(jumlah), 0) INTO v_jumlah_dipinjam
    FROM peminjaman 
    WHERE alat_id = alat_id AND status IN ('dipinjam', 'disetujui');
    
    -- Kembalikan TRUE jika stok masih mencukupi
    RETURN (v_stok_total > v_jumlah_dipinjam);
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `hitung_denda` (`tanggal_pinjam` DATE, `tanggal_kembali_target` DATE, `tanggal_pengembalian_aktual` DATE) RETURNS DECIMAL(10,2) DETERMINISTIC BEGIN
    DECLARE denda DECIMAL(10,2);
    DECLARE terlambat INT;
    DECLARE tarif_denda DECIMAL(10,2) DEFAULT 5000.00; -- Rp 5000 per hari
    
    -- Hitung keterlambatan
    IF tanggal_pengembalian_aktual > tanggal_kembali_target THEN
        SET terlambat = DATEDIFF(tanggal_pengembalian_aktual, tanggal_kembali_target);
        SET denda = terlambat * tarif_denda;
    ELSE
        SET denda = 0;
    END IF;
    
    RETURN denda;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `hitung_total_peminjaman` (`user_id` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE total INT;
    
    SELECT COUNT(*) INTO total
    FROM peminjaman 
    WHERE user_id = user_id AND status = 'dipinjam'; -- Menggunakan user_id
    
    RETURN total;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `alat`
--

CREATE TABLE `alat` (
  `id` int(11) NOT NULL,
  `nama_alat` varchar(100) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `kondisi` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `alat`
--

INSERT INTO `alat` (`id`, `nama_alat`, `kategori_id`, `stok`, `kondisi`, `deskripsi`, `created_at`) VALUES
(1, 'Laptop', 1, 0, 'baik', 'Laptop untuk keperluan kerja', '2026-02-09 02:08:47'),
(2, 'Proyektor', 1, 3, 'baik', 'Proyektor untuk presentasi', '2026-02-09 02:08:47'),
(3, 'Meja', 2, 0, 'baik', 'Meja kerja', '2026-02-09 02:08:47'),
(4, 'Kursi', 2, 10, 'baik', 'Kursi kantor', '2026-02-09 02:08:47'),
(5, 'Bola Basket', NULL, 0, 'baik', 'Bola basket standar', '2026-02-09 02:08:47'),
(6, 'Sekop', 4, 6, 'baik', 'Sekop untuk berkebun', '2026-02-09 02:08:47'),
(7, 'Kabel HDMI', 1, 0, 'baik', 'KABEL UNTUK MENGHUBUNGKAN KE HDMI', '2026-02-11 02:00:01');

--
-- Triggers `alat`
--
DELIMITER $$
CREATE TRIGGER `log_perubahan_alat` AFTER UPDATE ON `alat` FOR EACH ROW BEGIN
    IF OLD.nama_alat != NEW.nama_alat OR OLD.stok != NEW.stok OR OLD.kategori_id != NEW.kategori_id THEN
        INSERT INTO log_activity (id_user, aktivitas, created_at)
        VALUES (
            @current_user_id,
            CONCAT('Mengupdate alat: ', OLD.nama_alat, ' -> ', NEW.nama_alat),
            NOW()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `validasi_stok_alat` BEFORE UPDATE ON `alat` FOR EACH ROW BEGIN
    DECLARE total_dipinjam INT;
    
    -- Hitung total alat yang sedang dipinjam
    SELECT COALESCE(SUM(dp.jumlah), 0) INTO total_dipinjam
    FROM detail_peminjaman dp
    JOIN peminjaman p ON dp.id_peminjaman = p.id
    WHERE dp.id_alat = NEW.id AND p.status = 'dipinjam';
    
    -- Jika stok baru kurang dari yang dipinjam, batalkan update
    IF NEW.stok < total_dipinjam THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Tidak dapat mengurangi stok. Stok tidak boleh kurang dari jumlah yang dipinjam.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `created_at`) VALUES
(1, 'electro', '2026-02-09 02:08:47'),
(2, 'Peralatan Kantor', '2026-02-09 02:08:47'),
(4, 'Peralatan Kebun', '2026-02-09 02:08:47'),
(5, 'Lain-lain', '2026-02-09 02:08:47'),
(6, 'test', '2026-02-12 04:37:43'),
(7, 'kaya', '2026-02-12 04:38:56'),
(8, 'hehe', '2026-02-12 04:39:19'),
(9, 'hehe', '2026-02-12 04:46:02'),
(10, 'subjek', '2026-02-12 04:46:10'),
(11, 'welee', '2026-04-05 18:03:53');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aktivitas`, `waktu`) VALUES
(1, 1, 'Login ke sistem', '2026-02-09 02:17:57'),
(2, 1, 'Logout dari sistem', '2026-02-09 03:48:29'),
(3, 3, 'Login ke sistem', '2026-02-09 03:48:38'),
(4, 3, 'Logout dari sistem', '2026-02-09 03:48:44'),
(5, 1, 'Login ke sistem', '2026-02-09 03:48:48'),
(6, 1, 'Logout dari sistem', '2026-02-09 05:14:52'),
(7, 1, 'Login ke sistem', '2026-02-09 05:14:54'),
(8, 1, 'Login ke sistem', '2026-02-09 07:41:11'),
(9, 1, 'Login ke sistem', '2026-02-10 06:58:26'),
(10, 1, 'Logout dari sistem', '2026-02-10 06:59:23'),
(11, 2, 'Login ke sistem', '2026-02-10 06:59:46'),
(12, 2, 'Logout dari sistem', '2026-02-10 07:07:35'),
(13, 3, 'Login ke sistem', '2026-02-10 08:00:48'),
(14, 3, 'Logout dari sistem', '2026-02-10 08:00:54'),
(15, 1, 'Login ke sistem', '2026-02-10 08:00:58'),
(16, 1, 'Login ke sistem', '2026-02-11 01:26:24'),
(17, 1, 'Menambahkan alat: Kabel HDMI', '2026-02-11 02:00:01'),
(18, 1, 'Mengedit alat: Kabel HDMI', '2026-02-11 02:00:13'),
(19, 1, 'Logout dari sistem', '2026-02-11 02:34:25'),
(20, 1, 'Login ke sistem', '2026-02-11 02:34:27'),
(21, 1, 'Logout dari sistem', '2026-02-11 02:34:41'),
(22, 3, 'Login ke sistem', '2026-02-11 02:34:46'),
(23, 3, 'Logout dari sistem', '2026-02-11 02:34:52'),
(24, 2, 'Login ke sistem', '2026-02-11 02:35:10'),
(25, 2, 'Logout dari sistem', '2026-02-11 02:35:14'),
(26, 1, 'Login ke sistem', '2026-02-11 02:35:16'),
(27, 1, 'Login ke sistem', '2026-02-11 03:38:19'),
(28, 1, 'Logout dari sistem', '2026-02-11 03:39:30'),
(29, 3, 'Login ke sistem', '2026-02-11 03:39:36'),
(30, 3, 'Mengajukan peminjaman alat ID: 7', '2026-02-11 03:39:50'),
(31, 3, 'Logout dari sistem', '2026-02-11 03:40:03'),
(32, 2, 'Login ke sistem', '2026-02-11 03:40:09'),
(33, 2, 'Menyetujui peminjaman ID: 1', '2026-02-11 03:40:21'),
(34, 2, 'Logout dari sistem', '2026-02-11 03:40:23'),
(35, 3, 'Login ke sistem', '2026-02-11 03:40:26'),
(36, 3, 'Logout dari sistem', '2026-02-11 03:40:34'),
(37, 1, 'Login ke sistem', '2026-02-11 03:40:37'),
(38, 1, 'Logout dari sistem', '2026-02-11 04:24:16'),
(39, 3, 'Login ke sistem', '2026-02-11 04:24:20'),
(40, 3, 'Mengajukan pengembalian peminjaman ID: 1', '2026-02-11 04:24:35'),
(41, 3, 'Logout dari sistem', '2026-02-11 04:24:38'),
(42, 2, 'Login ke sistem', '2026-02-11 04:24:41'),
(43, 2, 'Logout dari sistem', '2026-02-11 04:24:51'),
(44, 1, 'Login ke sistem', '2026-02-11 04:24:53'),
(45, 1, 'Logout dari sistem', '2026-02-11 04:27:30'),
(46, 2, 'Login ke sistem', '2026-02-11 04:27:33'),
(47, 2, 'Logout dari sistem', '2026-02-11 04:27:41'),
(48, 2, 'Login ke sistem', '2026-02-11 04:40:12'),
(49, 2, 'Logout dari sistem', '2026-02-11 04:50:01'),
(50, 2, 'Login ke sistem', '2026-02-11 04:50:03'),
(51, 2, 'Logout dari sistem', '2026-02-11 04:50:06'),
(52, 1, 'Login ke sistem', '2026-02-11 04:50:25'),
(53, 1, 'Logout dari sistem', '2026-02-11 04:55:33'),
(54, 3, 'Login ke sistem', '2026-02-11 04:55:40'),
(55, 3, 'Logout dari sistem', '2026-02-11 04:55:48'),
(56, 2, 'Login ke sistem', '2026-02-11 04:55:52'),
(57, 2, 'Logout dari sistem', '2026-02-11 04:55:56'),
(58, 2, 'Login ke sistem', '2026-02-11 04:56:01'),
(59, 2, 'Logout dari sistem', '2026-02-11 04:58:38'),
(60, 3, 'Login ke sistem', '2026-02-11 04:58:40'),
(61, 3, 'Logout dari sistem', '2026-02-11 05:17:13'),
(62, 1, 'Login ke sistem', '2026-02-11 05:17:15'),
(63, 1, 'Logout dari sistem', '2026-02-11 05:27:11'),
(64, 3, 'Login ke sistem', '2026-02-11 05:27:17'),
(65, 3, 'Logout dari sistem', '2026-02-11 05:28:26'),
(66, 1, 'Login ke sistem', '2026-02-11 05:28:30'),
(67, 1, 'Logout dari sistem', '2026-02-11 05:28:33'),
(68, 3, 'Login ke sistem', '2026-02-11 05:28:37'),
(69, 3, 'Login ke sistem', '2026-02-11 06:54:59'),
(70, 3, 'Login ke sistem', '2026-02-11 06:54:59'),
(71, 3, 'Mengajukan peminjaman alat ID: 7', '2026-02-11 07:22:07'),
(72, 3, 'Mengajukan peminjaman alat ID: 5', '2026-02-11 07:27:26'),
(73, 3, 'Mengajukan peminjaman alat ID: 4', '2026-02-11 07:28:32'),
(74, 3, 'Mengajukan peminjaman alat ID: 5', '2026-02-11 07:30:18'),
(75, 3, 'Mengajukan peminjaman alat ID: 5', '2026-02-11 07:34:10'),
(76, 3, 'Logout dari sistem', '2026-02-11 07:34:37'),
(77, 2, 'Login ke sistem', '2026-02-11 07:34:41'),
(78, 2, 'Menyetujui peminjaman ID: 6', '2026-02-11 07:34:48'),
(79, 2, 'Menolak peminjaman ID: 5', '2026-02-11 07:34:50'),
(80, 2, 'Menyetujui peminjaman ID: 4', '2026-02-11 07:34:52'),
(81, 2, 'Menyetujui peminjaman ID: 3', '2026-02-11 07:34:58'),
(82, 2, 'Menolak peminjaman ID: 2', '2026-02-11 07:34:59'),
(83, 2, 'Logout dari sistem', '2026-02-11 07:35:01'),
(84, 3, 'Login ke sistem', '2026-02-11 07:35:05'),
(85, 3, 'Mengajukan pengembalian peminjaman ID: 6', '2026-02-11 07:36:05'),
(86, 3, 'Mengajukan pengembalian peminjaman ID: 4', '2026-02-11 07:36:40'),
(87, 3, 'Mengajukan pengembalian peminjaman ID: 3', '2026-02-11 07:36:46'),
(88, 3, 'Logout dari sistem', '2026-02-11 07:36:50'),
(89, 2, 'Login ke sistem', '2026-02-11 07:36:57'),
(90, 2, 'Logout dari sistem', '2026-02-11 07:37:12'),
(91, 3, 'Login ke sistem', '2026-02-11 07:37:18'),
(92, 3, 'Logout dari sistem', '2026-02-11 08:01:31'),
(93, 2, 'Login ke sistem', '2026-02-11 08:01:37'),
(94, 2, 'Login ke sistem', '2026-02-12 01:16:20'),
(95, 2, 'Logout dari sistem', '2026-02-12 01:22:18'),
(96, 3, 'Login ke sistem', '2026-02-12 01:22:26'),
(97, 3, 'Mengajukan peminjaman alat ID: 5', '2026-02-12 01:22:38'),
(98, 3, 'Logout dari sistem', '2026-02-12 01:22:45'),
(99, 2, 'Login ke sistem', '2026-02-12 01:22:48'),
(100, 2, 'Logout dari sistem', '2026-02-12 01:22:54'),
(101, 3, 'Login ke sistem', '2026-02-12 01:22:58'),
(102, 3, 'Logout dari sistem', '2026-02-12 01:31:35'),
(103, 3, 'Login ke sistem', '2026-02-12 01:31:36'),
(104, 3, 'Logout dari sistem', '2026-02-12 01:32:32'),
(105, 3, 'Login ke sistem', '2026-02-12 01:32:33'),
(106, 3, 'Logout dari sistem', '2026-02-12 01:47:15'),
(107, 2, 'Login ke sistem', '2026-02-12 01:47:21'),
(108, 2, 'Logout dari sistem', '2026-02-12 01:55:56'),
(109, 1, 'Login ke sistem', '2026-02-12 01:55:59'),
(110, 1, 'Logout dari sistem', '2026-02-12 01:56:28'),
(111, 2, 'Login ke sistem', '2026-02-12 01:58:26'),
(112, 2, 'Logout dari sistem', '2026-02-12 02:06:40'),
(113, 3, 'Login ke sistem', '2026-02-12 02:06:43'),
(114, 3, 'Logout dari sistem', '2026-02-12 02:22:39'),
(115, 3, 'Login ke sistem', '2026-02-12 02:22:47'),
(116, 3, 'Mengajukan peminjaman alat ID: 4', '2026-02-12 02:23:04'),
(117, 3, 'Logout dari sistem', '2026-02-12 02:23:12'),
(118, 2, 'Login ke sistem', '2026-02-12 02:23:20'),
(119, 2, 'Menyetujui peminjaman ID: 8', '2026-02-12 02:23:32'),
(120, 2, 'Logout dari sistem', '2026-02-12 02:23:37'),
(121, 3, 'Login ke sistem', '2026-02-12 02:23:41'),
(122, 3, 'Logout dari sistem', '2026-02-12 02:36:59'),
(123, 2, 'Login ke sistem', '2026-02-12 02:37:02'),
(124, 2, 'Logout dari sistem', '2026-02-12 02:51:41'),
(125, 3, 'Login ke sistem', '2026-02-12 02:51:47'),
(126, 3, 'Mengajukan peminjaman alat ID: 5', '2026-02-12 02:52:10'),
(127, 3, 'Logout dari sistem', '2026-02-12 02:52:15'),
(128, 2, 'Login ke sistem', '2026-02-12 02:52:18'),
(129, 2, 'Logout dari sistem', '2026-02-12 02:55:15'),
(130, 3, 'Login ke sistem', '2026-02-12 02:55:19'),
(131, 3, 'Logout dari sistem', '2026-02-12 02:55:28'),
(132, 2, 'Login ke sistem', '2026-02-12 02:55:32'),
(133, 2, 'Logout dari sistem', '2026-02-12 04:14:37'),
(134, 1, 'Login ke sistem', '2026-02-12 04:14:50'),
(135, 1, 'Logout dari sistem', '2026-02-12 04:14:54'),
(136, 2, 'Login ke sistem', '2026-02-12 04:14:58'),
(137, 2, 'Logout dari sistem', '2026-02-12 04:15:01'),
(138, 3, 'Login ke sistem', '2026-02-12 04:15:05'),
(139, 3, 'Logout dari sistem', '2026-02-12 04:15:08'),
(140, 1, 'Login ke sistem', '2026-02-12 04:15:15'),
(141, 1, 'Menambahkan user baru: Djob micel', '2026-02-12 04:15:47'),
(142, 1, 'Mengedit user: Michael', '2026-02-12 04:16:18'),
(143, 1, 'Menghapus user dengan ID: 4', '2026-02-12 04:16:34'),
(144, 1, 'Menambahkan alat: keyboard Rexus', '2026-02-12 04:17:04'),
(145, 1, 'Mengedit alat: keyboard Ajaz', '2026-02-12 04:17:41'),
(146, 1, 'Menghapus alat dengan ID: 8', '2026-02-12 04:17:51'),
(147, 1, 'Mengedit kategori: Lontong', '2026-02-12 04:18:12'),
(148, 1, 'Mengedit kategori: lontong', '2026-02-12 04:18:33'),
(149, 1, 'Mengedit kategori: tes', '2026-02-12 04:18:49'),
(150, 1, 'Mengedit kategori: tes', '2026-02-12 04:19:16'),
(151, 1, 'Mengedit kategori: gg', '2026-02-12 04:19:59'),
(152, 1, 'Mengedit kategori: electro', '2026-02-12 04:20:04'),
(153, 1, 'Menghapus kategori dengan ID: 3', '2026-02-12 04:20:14'),
(154, 1, 'Mengedit kategori: test', '2026-02-12 04:26:32'),
(155, 1, 'Mengedit kategori: c  c', '2026-02-12 04:29:03'),
(156, 1, 'Mengedit kategori: knrlr', '2026-02-12 04:29:49'),
(157, 1, 'Mengedit kategori: test', '2026-02-12 04:31:44'),
(158, 1, 'Menambahkan kategori: test', '2026-02-12 04:37:43'),
(159, 1, 'Menambahkan kategori: kaya', '2026-02-12 04:38:56'),
(160, 1, 'Menambahkan kategori: hehe', '2026-02-12 04:39:19'),
(161, 1, 'Menambahkan kategori: hehe', '2026-02-12 04:46:02'),
(162, 1, 'Menambahkan kategori: subjek', '2026-02-12 04:46:10'),
(163, 1, 'Logout dari sistem', '2026-02-12 04:47:51'),
(164, 3, 'Login ke sistem', '2026-02-12 04:48:04'),
(165, 3, 'Mengajukan peminjaman alat ID: 7', '2026-02-12 04:48:38'),
(166, 3, 'Logout dari sistem', '2026-02-12 04:48:45'),
(167, 2, 'Login ke sistem', '2026-02-12 04:49:54'),
(168, 2, 'Logout dari sistem', '2026-02-12 04:50:42'),
(169, 3, 'Login ke sistem', '2026-02-12 04:50:47'),
(170, 3, 'Logout dari sistem', '2026-02-13 04:51:32'),
(171, 2, 'Login ke sistem', '2026-02-13 04:51:35'),
(172, 2, 'Logout dari sistem', '2026-02-15 05:17:36'),
(173, 3, 'Login ke sistem', '2026-02-15 05:17:44'),
(174, 3, 'Mengajukan peminjaman alat ID: 3', '2026-02-15 05:18:03'),
(175, 3, 'Logout dari sistem', '2026-02-15 05:18:06'),
(176, 2, 'Login ke sistem', '2026-02-15 05:18:10'),
(177, 2, 'Logout dari sistem', '2026-02-15 05:18:23'),
(178, 1, 'Login ke sistem', '2026-02-15 05:18:26'),
(179, 1, 'Logout dari sistem', '2026-02-12 07:19:14'),
(180, 1, 'Login ke sistem', '2026-02-12 07:19:17'),
(181, 1, 'Logout dari sistem', '2026-02-12 07:19:24'),
(182, 3, 'Login ke sistem', '2026-02-12 07:19:27'),
(183, 3, 'Mengajukan peminjaman alat ID: 1', '2026-02-12 07:19:51'),
(184, 3, 'Logout dari sistem', '2026-02-12 07:19:54'),
(185, 2, 'Login ke sistem', '2026-02-12 07:19:57'),
(186, 2, 'Logout dari sistem', '2026-02-12 07:20:09'),
(187, 3, 'Login ke sistem', '2026-02-12 07:20:13'),
(188, 3, 'Logout dari sistem', '2026-02-12 07:20:22'),
(189, 2, 'Login ke sistem', '2026-02-12 07:20:25'),
(190, 2, 'Logout dari sistem', '2026-02-12 07:27:33'),
(191, 1, 'Login ke sistem', '2026-02-12 07:27:36'),
(192, 1, 'Login ke sistem', '2026-02-25 04:17:58'),
(193, 1, 'Logout dari sistem', '2026-02-25 04:18:08'),
(194, 1, 'Login ke sistem', '2026-02-26 03:40:33'),
(195, 1, 'Logout dari sistem', '2026-02-26 03:40:48'),
(196, 2, 'Login ke sistem', '2026-02-26 03:40:55'),
(197, 2, 'Logout dari sistem', '2026-02-26 03:41:05'),
(198, 3, 'Login ke sistem', '2026-02-26 03:41:13'),
(199, 3, 'Logout dari sistem', '2026-02-26 03:48:04'),
(200, 3, 'Login ke sistem', '2026-03-02 03:28:56'),
(201, 1, 'Login ke sistem', '2026-03-04 03:22:45'),
(202, 1, 'Login ke sistem', '2026-03-12 07:03:04'),
(203, 1, 'Login ke sistem', '2026-04-05 10:36:58'),
(204, 1, 'Logout dari sistem', '2026-04-05 10:37:08'),
(205, 1, 'Login ke sistem', '2026-04-05 13:29:19'),
(206, 1, 'Logout dari sistem', '2026-04-05 15:38:52'),
(207, 3, 'Login ke sistem', '2026-04-05 15:38:56'),
(208, 3, 'Logout dari sistem', '2026-04-05 15:43:22'),
(209, 2, 'Login ke sistem', '2026-04-05 15:43:28'),
(210, 2, 'Login ke sistem', '2026-04-05 15:43:28'),
(211, 2, 'Login ke sistem', '2026-04-05 16:58:42'),
(212, 1, 'Login ke sistem', '2026-04-05 17:04:34'),
(213, 1, 'Logout dari sistem', '2026-04-05 17:21:57'),
(214, 1, 'User admin berhasil login', '2026-04-05 17:30:04'),
(215, 1, 'Logout dari sistem', '2026-04-05 17:30:36'),
(216, 1, 'User admin berhasil login', '2026-04-05 17:30:52'),
(217, 1, 'Logout dari sistem', '2026-04-05 17:31:46'),
(218, 1, 'User admin berhasil login', '2026-04-05 17:34:54'),
(219, 1, 'Menambahkan kategori baru: walaa', '2026-04-05 18:03:53'),
(220, 1, 'Mengedit kategori menjadi: welee', '2026-04-05 18:03:59'),
(221, 1, 'Mengedit data user ID: 1 (Nama: aku jawa)', '2026-04-05 19:16:20'),
(222, 1, 'Logout dari sistem', '2026-04-05 19:17:58'),
(223, 1, 'User admin berhasil login', '2026-04-05 19:18:00'),
(224, 1, 'Logout dari sistem', '2026-04-05 19:18:24'),
(225, 3, 'User peminjam berhasil login', '2026-04-05 19:19:10'),
(226, 3, 'Logout dari sistem', '2026-04-05 19:26:15'),
(227, 3, 'User peminjam berhasil login', '2026-04-05 19:26:20'),
(228, 3, 'Logout dari sistem', '2026-04-05 19:46:13'),
(229, 3, 'User peminjam berhasil login', '2026-04-05 19:46:20'),
(230, 3, 'Logout dari sistem', '2026-04-05 19:49:46'),
(231, 3, 'User peminjam berhasil login', '2026-04-05 19:49:50'),
(232, 3, 'Logout dari sistem', '2026-04-05 19:55:42'),
(233, 1, 'User admin berhasil login', '2026-04-05 19:55:45'),
(234, 1, 'Logout dari sistem', '2026-04-05 19:56:50'),
(235, 3, 'User peminjam berhasil login', '2026-04-05 19:56:54');

-- --------------------------------------------------------

--
-- Table structure for table `peminjaman`
--

CREATE TABLE `peminjaman` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alat_id` int(11) NOT NULL,
  `tanggal_pinjam` date NOT NULL,
  `tanggal_kembali` date DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','disetujui','ditolak','dipinjam','menunggu_konfirmasi','dikembalikan') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `peminjaman`
--

INSERT INTO `peminjaman` (`id`, `user_id`, `alat_id`, `tanggal_pinjam`, `tanggal_kembali`, `jumlah`, `status`, `keterangan`, `created_at`) VALUES
(1, 3, 7, '2026-02-11', '2026-02-11', 2, 'dikembalikan', 'bagus', '2026-02-11 03:39:50'),
(2, 3, 7, '2026-02-11', '2026-02-12', 4, 'ditolak', '', '2026-02-11 07:22:07'),
(3, 3, 5, '2026-02-11', '2026-02-12', 5, 'dikembalikan', '', '2026-02-11 07:27:26'),
(4, 3, 4, '2026-02-11', '2026-02-11', 10, 'dikembalikan', '', '2026-02-11 07:28:32'),
(5, 3, 5, '2026-02-11', '2026-02-11', 1, 'ditolak', '', '2026-02-11 07:30:18'),
(6, 3, 5, '2026-02-11', '2026-02-20', 2, 'dikembalikan', '', '2026-02-11 07:34:10'),
(7, 3, 5, '2026-02-12', '2026-02-13', 2, '', '', '2026-02-12 01:22:38'),
(8, 3, 4, '2026-02-12', '2026-02-12', 10, '', '', '2026-02-12 02:23:04'),
(9, 3, 5, '2026-02-12', '2026-02-12', 3, 'dikembalikan', '', '2026-02-12 02:52:10'),
(10, 3, 7, '2026-02-12', '2026-02-12', 1, 'dikembalikan', '', '2026-02-12 04:48:38'),
(11, 3, 3, '2026-02-12', '2026-02-14', 10, 'pending', '', '2026-02-15 05:18:03'),
(12, 3, 1, '2026-02-12', '2026-02-12', 5, 'dikembalikan', '', '2026-02-12 07:19:51');

-- --------------------------------------------------------

--
-- Table structure for table `pengembalian`
--

CREATE TABLE `pengembalian` (
  `id` int(11) NOT NULL,
  `peminjaman_id` int(11) NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `kondisi_kembali` enum('baik','rusak_ringan','rusak_berat') NOT NULL,
  `denda` decimal(10,2) DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengembalian`
--

INSERT INTO `pengembalian` (`id`, `peminjaman_id`, `tanggal_kembali`, `kondisi_kembali`, `denda`, `keterangan`, `created_at`) VALUES
(1, 7, '2026-02-12', 'baik', 0.00, 'HE', '2026-02-12 01:47:13'),
(2, 9, '2026-02-12', 'rusak_ringan', 50000.00, 'rusak bayar dee', '2026-02-12 04:03:30'),
(3, 10, '2026-02-13', 'rusak_ringan', 60000.00, '', '2026-02-13 04:52:30'),
(4, 12, '2026-02-12', 'rusak_ringan', 50000.00, '', '2026-02-12 07:20:47');

--
-- Triggers `pengembalian`
--
DELIMITER $$
CREATE TRIGGER `cek_batas_pengembalian` BEFORE INSERT ON `pengembalian` FOR EACH ROW BEGIN
    -- Update status peminjaman menjadi terlambat jika melewati tanggal kembali
    UPDATE peminjaman
    SET status = CASE 
        WHEN NEW.tanggal_kembali > tanggal_kembali THEN 'terlambat'
        ELSE 'dikembalikan'
    END
    WHERE id = NEW.peminjaman_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','peminjam') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'aku jawa', 'admin', '$2y$10$RjwbBS8vkPZLjhykQ8r1TOESywHN8C/6pF1Rp3JomCvBmqBc1qrMK', 'admin', '2026-02-09 02:08:47'),
(2, 'Petugas', 'petugas', '$2y$10$kaimsZktRmB2siaYskOvwudaa42d8QuLKo.Z66WzjX0pW7R8CAIuq', 'petugas', '2026-02-09 02:08:47'),
(3, 'Peminjam', 'peminjam', '$2y$10$1bnQamRbh6b1.dluM67lRecNlMDFu69AXBNBu9ifKqOAlpRPCxJs6', 'peminjam', '2026-02-09 02:08:47');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `log_user_baru` AFTER INSERT ON `users` FOR EACH ROW BEGIN
    INSERT INTO log_activity (id_user, aktivitas, created_at)
    VALUES (
        NEW.id,
        CONCAT('User baru terdaftar: ', NEW.nama, ' (', NEW.username, ')'),
        NOW()
    );
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `alat_id` (`alat_id`);

--
-- Indexes for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peminjaman_id` (`peminjaman_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `alat`
--
ALTER TABLE `alat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=236;

--
-- AUTO_INCREMENT for table `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `alat`
--
ALTER TABLE `alat`
  ADD CONSTRAINT `alat_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`alat_id`) REFERENCES `alat` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
