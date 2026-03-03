-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 26 Feb 2026 pada 04.28
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

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

-- --------------------------------------------------------

--
-- Struktur dari tabel `alat`
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
-- Dumping data untuk tabel `alat`
--

INSERT INTO `alat` (`id`, `nama_alat`, `kategori_id`, `stok`, `kondisi`, `deskripsi`, `created_at`) VALUES
(1, 'Laptop', 1, 0, 'baik', 'Laptop untuk keperluan kerja', '2026-02-09 02:08:47'),
(2, 'Proyektor', 1, 3, 'baik', 'Proyektor untuk presentasi', '2026-02-09 02:08:47'),
(3, 'Meja', 2, 0, 'baik', 'Meja kerja', '2026-02-09 02:08:47'),
(4, 'Kursi', 2, 10, 'baik', 'Kursi kantor', '2026-02-09 02:08:47'),
(5, 'Bola Basket', NULL, 0, 'baik', 'Bola basket standar', '2026-02-09 02:08:47'),
(6, 'Sekop', 4, 6, 'baik', 'Sekop untuk berkebun', '2026-02-09 02:08:47'),
(7, 'Kabel HDMI', 1, 0, 'baik', 'KABEL UNTUK MENGHUBUNGKAN KE HDMI', '2026-02-11 02:00:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kategori`
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
(10, 'subjek', '2026-02-12 04:46:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `aktivitas` varchar(255) NOT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_aktivitas`
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
(193, 1, 'Logout dari sistem', '2026-02-25 04:18:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `peminjaman`
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
-- Dumping data untuk tabel `peminjaman`
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
-- Struktur dari tabel `pengembalian`
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
-- Dumping data untuk tabel `pengembalian`
--

INSERT INTO `pengembalian` (`id`, `peminjaman_id`, `tanggal_kembali`, `kondisi_kembali`, `denda`, `keterangan`, `created_at`) VALUES
(1, 7, '2026-02-12', 'baik', 0.00, 'HE', '2026-02-12 01:47:13'),
(2, 9, '2026-02-12', 'rusak_ringan', 50000.00, 'rusak bayar dee', '2026-02-12 04:03:30'),
(3, 10, '2026-02-13', 'rusak_ringan', 60000.00, '', '2026-02-13 04:52:30'),
(4, 12, '2026-02-12', 'rusak_ringan', 50000.00, '', '2026-02-12 07:20:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
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
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin', '$2y$10$1RSKjNseIZctEt8bSXONQOnFksgOK97h8oNil7JgtsujyzsaQb8IC', 'admin', '2026-02-09 02:08:47'),
(2, 'Petugas', 'petugas', '$2y$10$kaimsZktRmB2siaYskOvwudaa42d8QuLKo.Z66WzjX0pW7R8CAIuq', 'petugas', '2026-02-09 02:08:47'),
(3, 'Peminjam', 'peminjam', '$2y$10$1bnQamRbh6b1.dluM67lRecNlMDFu69AXBNBu9ifKqOAlpRPCxJs6', 'peminjam', '2026-02-09 02:08:47');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `alat`
--
ALTER TABLE `alat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indeks untuk tabel `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `alat_id` (`alat_id`);

--
-- Indeks untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peminjaman_id` (`peminjaman_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `alat`
--
ALTER TABLE `alat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `alat`
--
ALTER TABLE `alat`
  ADD CONSTRAINT `alat_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `peminjaman`
--
ALTER TABLE `peminjaman`
  ADD CONSTRAINT `peminjaman_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `peminjaman_ibfk_2` FOREIGN KEY (`alat_id`) REFERENCES `alat` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengembalian`
--
ALTER TABLE `pengembalian`
  ADD CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`peminjaman_id`) REFERENCES `peminjaman` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
