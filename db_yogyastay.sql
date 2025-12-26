-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 26, 2025 at 04:02 AM
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
-- Database: `db_yogyastay2`
--

-- --------------------------------------------------------

--
-- Table structure for table `blog`
--

CREATE TABLE `blog` (
  `id_blog` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `konten` text NOT NULL,
  `thumbnail` varchar(255) DEFAULT NULL,
  `tanggal_publish` date DEFAULT NULL,
  `id_admin` int(11) NOT NULL,
  `status` enum('publish','draft') DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog`
--

INSERT INTO `blog` (`id_blog`, `judul`, `konten`, `thumbnail`, `tanggal_publish`, `id_admin`, `status`) VALUES
(7, '5 Destinasi Wajib Dikunjungi di Jogja Saat Liburan', 'Yogyakarta selalu menjadi destinasi favorit wisatawan lokal maupun mancanegara. Kota ini menawarkan perpaduan budaya, sejarah, dan keindahan alam yang sulit dilupakan.\r\n\r\nBeberapa destinasi wajib yang tidak boleh kamu lewatkan antara lain:\r\n1. Malioboro – pusat belanja dan kuliner khas Jogja.\r\n2. Keraton Yogyakarta – saksi sejarah dan budaya Jawa.\r\n3. Candi Prambanan – candi Hindu terbesar di Indonesia.\r\n4. Pantai Parangtritis – pantai ikonik dengan panorama matahari terbenam.\r\n5. Tebing Breksi – destinasi modern dengan pemandangan kota dari ketinggian.\r\n\r\nDengan memilih penginapan yang strategis, perjalanan liburanmu di Jogja akan semakin nyaman dan berkesan.', 'destinasi-jogja.jpg', '2025-01-10', 6, 'publish'),
(8, 'Panduan Check-in Online Anti Ribet untuk Traveler', 'Proses check-in sering kali memakan waktu, terutama saat musim liburan. Namun kini, banyak penginapan dan hotel di Yogyakarta sudah menyediakan layanan check-in online.\r\n\r\nBeberapa tips agar proses check-in online berjalan lancar:\r\n- Siapkan identitas diri (KTP atau Paspor) dalam bentuk digital.\r\n- Pastikan data reservasi sesuai dengan identitas.\r\n- Datang sesuai jam check-in yang ditentukan.\r\n- Simpan bukti pemesanan di ponsel.\r\n\r\nDengan memanfaatkan fitur check-in online, kamu bisa menghemat waktu dan langsung menikmati pengalaman menginap tanpa antre panjang.', 'checkin-online.jpg', '2025-01-12', 6, 'publish'),
(9, 'Tips Memilih Penginapan Nyaman dan Terjangkau di Jogja', 'Memilih penginapan yang tepat adalah kunci liburan yang menyenangkan. Di Yogyakarta, tersedia berbagai pilihan penginapan mulai dari hotel berbintang hingga homestay yang ramah di kantong.\r\n\r\nBeberapa tips memilih penginapan:\r\n- Pilih lokasi dekat tujuan wisata.\r\n- Sesuaikan harga dengan fasilitas yang ditawarkan.\r\n- Cek ulasan dan rating pengunjung.\r\n- Pastikan keamanan dan kenyamanan lingkungan.\r\n\r\nDengan perencanaan yang matang, kamu bisa mendapatkan penginapan yang nyaman tanpa harus menguras budget liburan.', 'tips-penginapan-jogja.jpg', '2025-01-15', 6, 'publish');

-- --------------------------------------------------------

--
-- Table structure for table `booking`
--

CREATE TABLE `booking` (
  `id_booking` int(11) NOT NULL,
  `kode_booking` varchar(20) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_penginapan` int(11) NOT NULL,
  `id_tipe_kamar` int(11) NOT NULL,
  `tanggal_checkin` date NOT NULL,
  `tanggal_checkout` date NOT NULL,
  `jumlah_kamar` int(11) NOT NULL,
  `jumlah_orang` int(11) NOT NULL,
  `total_harga` decimal(12,2) NOT NULL,
  `status_reservasi` enum('pending','confirmed','check-in','selesai','dibatalkan') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checkin`
--

CREATE TABLE `checkin` (
  `id_checkin` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL,
  `email_pemesan` varchar(100) NOT NULL,
  `foto_ktp` varchar(255) DEFAULT NULL,
  `status_checkin` enum('menunggu','valid','ditolak') DEFAULT 'menunggu',
  `waktu_checkin` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fasilitas`
--

CREATE TABLE `fasilitas` (
  `id_fasilitas` int(11) NOT NULL,
  `nama_fasilitas` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fasilitas`
--

INSERT INTO `fasilitas` (`id_fasilitas`, `nama_fasilitas`) VALUES
(1, 'WiFi'),
(2, 'AC'),
(3, 'TV'),
(4, 'Kamar Mandi Dalam'),
(5, 'Air Panas'),
(6, 'Kolam Renang'),
(7, 'Parkir'),
(8, 'Resepsionis 24 Jam'),
(9, 'Sarapan'),
(10, 'Dapur'),
(11, 'Private Pool'),
(12, 'Balkon'),
(13, 'View Alam'),
(14, 'Lift'),
(15, 'Gym'),
(16, 'Meeting Room');

-- --------------------------------------------------------

--
-- Table structure for table `gambar_penginapan`
--

CREATE TABLE `gambar_penginapan` (
  `id_gambar` int(11) NOT NULL,
  `id_penginapan` int(11) NOT NULL,
  `path_gambar` varchar(255) NOT NULL,
  `is_thumbnail` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gambar_penginapan`
--

INSERT INTO `gambar_penginapan` (`id_gambar`, `id_penginapan`, `path_gambar`, `is_thumbnail`, `created_at`) VALUES
(1, 2, 'assets/penginapan/2/thumb.jpg', 1, '2025-12-24 02:06:24'),
(2, 3, 'assets/penginapan/3/thumb.jpg', 1, '2025-12-24 02:06:24'),
(3, 4, 'assets/penginapan/4/thumb.jpg', 1, '2025-12-24 02:06:24'),
(4, 5, 'assets/penginapan/5/thumb.jpg', 1, '2025-12-24 02:06:24'),
(5, 9, 'assets/penginapan/9/thumb.jpg', 1, '2025-12-24 02:06:24'),
(6, 11, 'assets/penginapan/11/thumb.jpg', 1, '2025-12-24 02:06:24'),
(7, 14, 'assets/penginapan/14/thumb.jpg', 1, '2025-12-24 02:06:24'),
(8, 17, 'assets/penginapan/17/thumb.jpg', 1, '2025-12-24 02:06:24'),
(9, 21, 'assets/penginapan/21/thumb.jpg', 1, '2025-12-24 02:06:24'),
(10, 22, 'assets/penginapan/22/thumb.jpg', 1, '2025-12-24 02:06:24'),
(11, 24, 'assets/penginapan/24/thumb.jpg', 1, '2025-12-24 02:06:24'),
(12, 27, 'assets/penginapan/27/thumb.jpg', 1, '2025-12-24 02:06:24'),
(13, 33, 'assets/penginapan/33/thumb.jpg', 1, '2025-12-24 02:06:24'),
(14, 39, 'assets/penginapan/39/thumb.jpg', 1, '2025-12-24 02:06:24'),
(15, 45, 'assets/penginapan/45/thumb.jpg', 1, '2025-12-24 02:06:24'),
(16, 47, 'assets/penginapan/47/thumb.jpg', 1, '2025-12-24 02:06:24'),
(17, 48, 'assets/penginapan/48/thumb.jpg', 1, '2025-12-24 02:06:24'),
(18, 54, 'assets/penginapan/54/thumb.jpg', 1, '2025-12-24 02:06:24'),
(19, 59, 'assets/penginapan/59/thumb.jpg', 1, '2025-12-24 02:06:24'),
(20, 60, 'assets/penginapan/60/thumb.jpg', 1, '2025-12-24 02:06:24'),
(21, 62, 'assets/penginapan/62/thumb.jpg', 1, '2025-12-24 02:06:24'),
(22, 65, 'assets/penginapan/65/thumb.jpg', 1, '2025-12-24 02:06:24'),
(23, 72, 'assets/penginapan/72/thumb.jpg', 1, '2025-12-24 02:06:24'),
(24, 76, 'assets/penginapan/76/thumb.jpg', 1, '2025-12-24 02:06:24'),
(25, 78, 'assets/penginapan/78/thumb.jpg', 1, '2025-12-24 02:06:24'),
(26, 12, 'assets/penginapan/12/thumb.jpg', 1, '2025-12-24 02:06:24'),
(27, 15, 'assets/penginapan/15/thumb.jpg', 1, '2025-12-24 02:06:24'),
(28, 16, 'assets/penginapan/16/thumb.jpg', 1, '2025-12-24 02:06:24'),
(29, 18, 'assets/penginapan/18/thumb.jpg', 1, '2025-12-24 02:06:24'),
(30, 20, 'assets/penginapan/20/thumb.jpg', 1, '2025-12-24 02:06:24'),
(31, 25, 'assets/penginapan/25/thumb.jpg', 1, '2025-12-24 02:06:24'),
(32, 26, 'assets/penginapan/26/thumb.jpg', 1, '2025-12-24 02:06:24'),
(33, 28, 'assets/penginapan/28/thumb.jpg', 1, '2025-12-24 02:06:24'),
(34, 31, 'assets/penginapan/31/thumb.jpg', 1, '2025-12-24 02:06:24'),
(35, 40, 'assets/penginapan/40/thumb.jpg', 1, '2025-12-24 02:06:24'),
(36, 44, 'assets/penginapan/44/thumb.jpg', 1, '2025-12-24 02:06:24'),
(37, 67, 'assets/penginapan/67/thumb.jpg', 1, '2025-12-24 02:06:24'),
(38, 73, 'assets/penginapan/73/thumb.jpg', 1, '2025-12-24 02:06:24'),
(39, 1, 'assets/penginapan/1/thumb.jpg', 1, '2025-12-24 02:06:24'),
(40, 6, 'assets/penginapan/6/thumb.jpg', 1, '2025-12-24 02:06:24'),
(41, 7, 'assets/penginapan/7/thumb.jpg', 1, '2025-12-24 02:06:24'),
(42, 8, 'assets/penginapan/8/thumb.jpg', 1, '2025-12-24 02:06:24'),
(43, 10, 'assets/penginapan/10/thumb.jpg', 1, '2025-12-24 02:06:24'),
(44, 13, 'assets/penginapan/13/thumb.jpg', 1, '2025-12-24 02:06:24'),
(45, 19, 'assets/penginapan/19/thumb.jpg', 1, '2025-12-24 02:06:24'),
(46, 23, 'assets/penginapan/23/thumb.jpg', 1, '2025-12-24 02:06:24'),
(47, 29, 'assets/penginapan/29/thumb.jpg', 1, '2025-12-24 02:06:24'),
(48, 30, 'assets/penginapan/30/thumb.jpg', 1, '2025-12-24 02:06:24'),
(49, 32, 'assets/penginapan/32/thumb.jpg', 1, '2025-12-24 02:06:24'),
(50, 34, 'assets/penginapan/34/thumb.jpg', 1, '2025-12-24 02:06:24'),
(51, 35, 'assets/penginapan/35/thumb.jpg', 1, '2025-12-24 02:06:24'),
(52, 36, 'assets/penginapan/36/thumb.jpg', 1, '2025-12-24 02:06:24'),
(53, 37, 'assets/penginapan/37/thumb.jpg', 1, '2025-12-24 02:06:24'),
(54, 38, 'assets/penginapan/38/thumb.jpg', 1, '2025-12-24 02:06:24'),
(55, 41, 'assets/penginapan/41/thumb.jpg', 1, '2025-12-24 02:06:24'),
(56, 42, 'assets/penginapan/42/thumb.jpg', 1, '2025-12-24 02:06:24'),
(57, 43, 'assets/penginapan/43/thumb.jpg', 1, '2025-12-24 02:06:24'),
(58, 46, 'assets/penginapan/46/thumb.jpg', 1, '2025-12-24 02:06:24'),
(59, 49, 'assets/penginapan/49/thumb.jpg', 1, '2025-12-24 02:06:24'),
(60, 50, 'assets/penginapan/50/thumb.jpg', 1, '2025-12-24 02:06:24'),
(61, 51, 'assets/penginapan/51/thumb.jpg', 1, '2025-12-24 02:06:24'),
(62, 52, 'assets/penginapan/52/thumb.jpg', 1, '2025-12-24 02:06:24'),
(63, 53, 'assets/penginapan/53/thumb.jpg', 1, '2025-12-24 02:06:24'),
(64, 55, 'assets/penginapan/55/thumb.jpg', 1, '2025-12-24 02:06:24'),
(65, 56, 'assets/penginapan/56/thumb.jpg', 1, '2025-12-24 02:06:24'),
(66, 57, 'assets/penginapan/57/thumb.jpg', 1, '2025-12-24 02:06:24'),
(67, 58, 'assets/penginapan/58/thumb.jpg', 1, '2025-12-24 02:06:24'),
(68, 61, 'assets/penginapan/61/thumb.jpg', 1, '2025-12-24 02:06:24'),
(69, 63, 'assets/penginapan/63/thumb.jpg', 1, '2025-12-24 02:06:24'),
(70, 64, 'assets/penginapan/64/thumb.jpg', 1, '2025-12-24 02:06:24'),
(71, 66, 'assets/penginapan/66/thumb.jpg', 1, '2025-12-24 02:06:24'),
(72, 68, 'assets/penginapan/68/thumb.jpg', 1, '2025-12-24 02:06:24'),
(73, 69, 'assets/penginapan/69/thumb.jpg', 1, '2025-12-24 02:06:24'),
(74, 70, 'assets/penginapan/70/thumb.jpg', 1, '2025-12-24 02:06:24'),
(75, 71, 'assets/penginapan/71/thumb.jpg', 1, '2025-12-24 02:06:24'),
(76, 74, 'assets/penginapan/74/thumb.jpg', 1, '2025-12-24 02:06:24'),
(77, 75, 'assets/penginapan/75/thumb.jpg', 1, '2025-12-24 02:06:24'),
(78, 77, 'assets/penginapan/77/thumb.jpg', 1, '2025-12-24 02:06:24'),
(128, 2, 'assets/penginapan/2/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(129, 3, 'assets/penginapan/3/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(130, 4, 'assets/penginapan/4/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(131, 5, 'assets/penginapan/5/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(132, 9, 'assets/penginapan/9/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(133, 11, 'assets/penginapan/11/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(134, 14, 'assets/penginapan/14/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(135, 17, 'assets/penginapan/17/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(136, 21, 'assets/penginapan/21/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(137, 22, 'assets/penginapan/22/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(138, 24, 'assets/penginapan/24/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(139, 27, 'assets/penginapan/27/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(140, 33, 'assets/penginapan/33/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(141, 39, 'assets/penginapan/39/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(142, 45, 'assets/penginapan/45/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(143, 47, 'assets/penginapan/47/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(144, 48, 'assets/penginapan/48/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(145, 54, 'assets/penginapan/54/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(146, 59, 'assets/penginapan/59/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(147, 60, 'assets/penginapan/60/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(148, 62, 'assets/penginapan/62/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(149, 65, 'assets/penginapan/65/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(150, 72, 'assets/penginapan/72/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(151, 76, 'assets/penginapan/76/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(152, 78, 'assets/penginapan/78/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(153, 12, 'assets/penginapan/12/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(154, 15, 'assets/penginapan/15/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(155, 16, 'assets/penginapan/16/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(156, 18, 'assets/penginapan/18/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(157, 20, 'assets/penginapan/20/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(158, 25, 'assets/penginapan/25/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(159, 26, 'assets/penginapan/26/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(160, 28, 'assets/penginapan/28/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(161, 31, 'assets/penginapan/31/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(162, 40, 'assets/penginapan/40/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(163, 44, 'assets/penginapan/44/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(164, 67, 'assets/penginapan/67/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(165, 73, 'assets/penginapan/73/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(166, 1, 'assets/penginapan/1/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(167, 6, 'assets/penginapan/6/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(168, 7, 'assets/penginapan/7/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(169, 8, 'assets/penginapan/8/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(170, 10, 'assets/penginapan/10/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(171, 13, 'assets/penginapan/13/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(172, 19, 'assets/penginapan/19/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(173, 23, 'assets/penginapan/23/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(174, 29, 'assets/penginapan/29/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(175, 30, 'assets/penginapan/30/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(176, 32, 'assets/penginapan/32/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(177, 34, 'assets/penginapan/34/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(178, 35, 'assets/penginapan/35/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(179, 36, 'assets/penginapan/36/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(180, 37, 'assets/penginapan/37/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(181, 38, 'assets/penginapan/38/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(182, 41, 'assets/penginapan/41/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(183, 42, 'assets/penginapan/42/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(184, 43, 'assets/penginapan/43/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(185, 46, 'assets/penginapan/46/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(186, 49, 'assets/penginapan/49/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(187, 50, 'assets/penginapan/50/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(188, 51, 'assets/penginapan/51/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(189, 52, 'assets/penginapan/52/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(190, 53, 'assets/penginapan/53/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(191, 55, 'assets/penginapan/55/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(192, 56, 'assets/penginapan/56/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(193, 57, 'assets/penginapan/57/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(194, 58, 'assets/penginapan/58/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(195, 61, 'assets/penginapan/61/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(196, 63, 'assets/penginapan/63/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(197, 64, 'assets/penginapan/64/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(198, 66, 'assets/penginapan/66/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(199, 68, 'assets/penginapan/68/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(200, 69, 'assets/penginapan/69/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(201, 70, 'assets/penginapan/70/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(202, 71, 'assets/penginapan/71/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(203, 74, 'assets/penginapan/74/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(204, 75, 'assets/penginapan/75/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(205, 77, 'assets/penginapan/77/gallery-1.jpg', 0, '2025-12-24 02:06:24'),
(255, 2, 'assets/penginapan/2/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(256, 3, 'assets/penginapan/3/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(257, 4, 'assets/penginapan/4/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(258, 5, 'assets/penginapan/5/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(259, 9, 'assets/penginapan/9/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(260, 11, 'assets/penginapan/11/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(261, 14, 'assets/penginapan/14/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(262, 17, 'assets/penginapan/17/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(263, 21, 'assets/penginapan/21/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(264, 22, 'assets/penginapan/22/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(265, 24, 'assets/penginapan/24/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(266, 27, 'assets/penginapan/27/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(267, 33, 'assets/penginapan/33/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(268, 39, 'assets/penginapan/39/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(269, 45, 'assets/penginapan/45/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(270, 47, 'assets/penginapan/47/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(271, 48, 'assets/penginapan/48/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(272, 54, 'assets/penginapan/54/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(273, 59, 'assets/penginapan/59/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(274, 60, 'assets/penginapan/60/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(275, 62, 'assets/penginapan/62/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(276, 65, 'assets/penginapan/65/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(277, 72, 'assets/penginapan/72/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(278, 76, 'assets/penginapan/76/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(279, 78, 'assets/penginapan/78/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(280, 12, 'assets/penginapan/12/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(281, 15, 'assets/penginapan/15/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(282, 16, 'assets/penginapan/16/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(283, 18, 'assets/penginapan/18/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(284, 20, 'assets/penginapan/20/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(285, 25, 'assets/penginapan/25/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(286, 26, 'assets/penginapan/26/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(287, 28, 'assets/penginapan/28/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(288, 31, 'assets/penginapan/31/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(289, 40, 'assets/penginapan/40/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(290, 44, 'assets/penginapan/44/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(291, 67, 'assets/penginapan/67/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(292, 73, 'assets/penginapan/73/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(293, 1, 'assets/penginapan/1/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(294, 6, 'assets/penginapan/6/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(295, 7, 'assets/penginapan/7/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(296, 8, 'assets/penginapan/8/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(297, 10, 'assets/penginapan/10/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(298, 13, 'assets/penginapan/13/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(299, 19, 'assets/penginapan/19/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(300, 23, 'assets/penginapan/23/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(301, 29, 'assets/penginapan/29/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(302, 30, 'assets/penginapan/30/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(303, 32, 'assets/penginapan/32/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(304, 34, 'assets/penginapan/34/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(305, 35, 'assets/penginapan/35/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(306, 36, 'assets/penginapan/36/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(307, 37, 'assets/penginapan/37/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(308, 38, 'assets/penginapan/38/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(309, 41, 'assets/penginapan/41/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(310, 42, 'assets/penginapan/42/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(311, 43, 'assets/penginapan/43/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(312, 46, 'assets/penginapan/46/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(313, 49, 'assets/penginapan/49/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(314, 50, 'assets/penginapan/50/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(315, 51, 'assets/penginapan/51/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(316, 52, 'assets/penginapan/52/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(317, 53, 'assets/penginapan/53/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(318, 55, 'assets/penginapan/55/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(319, 56, 'assets/penginapan/56/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(320, 57, 'assets/penginapan/57/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(321, 58, 'assets/penginapan/58/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(322, 61, 'assets/penginapan/61/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(323, 63, 'assets/penginapan/63/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(324, 64, 'assets/penginapan/64/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(325, 66, 'assets/penginapan/66/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(326, 68, 'assets/penginapan/68/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(327, 69, 'assets/penginapan/69/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(328, 70, 'assets/penginapan/70/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(329, 71, 'assets/penginapan/71/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(330, 74, 'assets/penginapan/74/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(331, 75, 'assets/penginapan/75/gallery-2.jpg', 0, '2025-12-24 02:06:24'),
(332, 77, 'assets/penginapan/77/gallery-2.jpg', 0, '2025-12-24 02:06:24');

-- --------------------------------------------------------

--
-- Table structure for table `kabupaten`
--

CREATE TABLE `kabupaten` (
  `id_kabupaten` int(11) NOT NULL,
  `nama_kabupaten` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kabupaten`
--

INSERT INTO `kabupaten` (`id_kabupaten`, `nama_kabupaten`) VALUES
(1, 'Kota Yogyakarta'),
(2, 'Sleman'),
(3, 'Bantul'),
(4, 'Kulon Progo'),
(5, 'Gunungkidul');

-- --------------------------------------------------------

--
-- Table structure for table `kecamatan`
--

CREATE TABLE `kecamatan` (
  `id_kecamatan` int(11) NOT NULL,
  `id_kabupaten` int(11) NOT NULL,
  `nama_kecamatan` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kecamatan`
--

INSERT INTO `kecamatan` (`id_kecamatan`, `id_kabupaten`, `nama_kecamatan`) VALUES
(1, 1, 'Danurejan'),
(2, 1, 'Gedongtengen'),
(3, 1, 'Gondokusuman'),
(4, 1, 'Gondomanan'),
(5, 1, 'Jetis'),
(6, 1, 'Kotagede'),
(7, 1, 'Kraton'),
(8, 1, 'Mantrijeron'),
(9, 1, 'Mergangsan'),
(10, 1, 'Ngampilan'),
(11, 1, 'Pakualaman'),
(12, 1, 'Tegalrejo'),
(13, 1, 'Umbulharjo'),
(14, 1, 'Wirobrajan'),
(15, 2, 'Berbah'),
(16, 2, 'Cangkringan'),
(17, 2, 'Depok'),
(18, 2, 'Gamping'),
(19, 2, 'Godean'),
(20, 2, 'Kalasan'),
(21, 2, 'Minggir'),
(22, 2, 'Mlati'),
(23, 2, 'Moyudan'),
(24, 2, 'Ngaglik'),
(25, 2, 'Ngemplak'),
(26, 2, 'Pakem'),
(27, 2, 'Prambanan'),
(28, 2, 'Seyegan'),
(29, 2, 'Sleman'),
(30, 2, 'Tempel'),
(31, 2, 'Turi'),
(32, 3, 'Bambanglipuro'),
(33, 3, 'Banguntapan'),
(34, 3, 'Bantul'),
(35, 3, 'Dlingo'),
(36, 3, 'Imogiri'),
(37, 3, 'Jetis'),
(38, 3, 'Kasihan'),
(39, 3, 'Kretek'),
(40, 3, 'Pajangan'),
(41, 3, 'Pandak'),
(42, 3, 'Piyungan'),
(43, 3, 'Pleret'),
(44, 3, 'Pundong'),
(45, 3, 'Sanden'),
(46, 3, 'Sedayu'),
(47, 3, 'Sewon'),
(48, 3, 'Srandakan'),
(49, 4, 'Galur'),
(50, 4, 'Girimulyo'),
(51, 4, 'Kalibawang'),
(52, 4, 'Kokap'),
(53, 4, 'Lendah'),
(54, 4, 'Nanggulan'),
(55, 4, 'Panjatan'),
(56, 4, 'Pengasih'),
(57, 4, 'Samigaluh'),
(58, 4, 'Sentolo'),
(59, 4, 'Temon'),
(60, 4, 'Wates'),
(61, 5, 'Gedangsari'),
(62, 5, 'Girisubo'),
(63, 5, 'Karangmojo'),
(64, 5, 'Ngawen'),
(65, 5, 'Nglipar'),
(66, 5, 'Paliyan'),
(67, 5, 'Panggang'),
(68, 5, 'Patuk'),
(69, 5, 'Playen'),
(70, 5, 'Ponjong'),
(71, 5, 'Purwosari'),
(72, 5, 'Rongkop'),
(73, 5, 'Saptosari'),
(74, 5, 'Semanu'),
(75, 5, 'Semin'),
(76, 5, 'Tanjungsari'),
(77, 5, 'Tepus'),
(78, 5, 'Wonosari');

-- --------------------------------------------------------

--
-- Table structure for table `kontak_penginapan`
--

CREATE TABLE `kontak_penginapan` (
  `id_kontak` int(11) NOT NULL,
  `id_penginapan` int(11) NOT NULL,
  `jenis_kontak` enum('telepon','email','website') NOT NULL,
  `isi_kontak` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kontak_penginapan`
--

INSERT INTO `kontak_penginapan` (`id_kontak`, `id_penginapan`, `jenis_kontak`, `isi_kontak`) VALUES
(23, 401, 'telepon', '0271 8930 478'),
(24, 401, '', '081789092345'),
(25, 401, 'email', 'deluna@gmail.com'),
(26, 402, 'telepon', '0271 8930 478'),
(27, 402, '', '081789092345'),
(28, 402, 'email', 'deluna@gmail.com'),
(29, 403, 'telepon', '0271 8930 478'),
(30, 403, '', '081789092345'),
(31, 403, 'email', 'deluna@gmail.com'),
(32, 404, 'telepon', '092834753'),
(33, 404, '', '081789092345'),
(34, 404, 'email', 'deluna@gmail.com'),
(35, 405, 'telepon', '08983923'),
(36, 405, '', '081789092345'),
(37, 405, 'email', 'deluna@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas_admin`
--

CREATE TABLE `log_aktivitas_admin` (
  `id_log` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `aksi` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas_admin`
--

INSERT INTO `log_aktivitas_admin` (`id_log`, `id_admin`, `aksi`, `deskripsi`, `created_at`) VALUES
(1, 6, 'Update Status User', 'Admin ID 6 mengubah status user ID 7 menjadi aktif', '2025-12-25 02:45:20'),
(2, 6, 'Tambah Blog', 'Admin ID 6 menambahkan blog berjudul \'Tips pilih hotel untuk traveler\'', '2025-12-25 02:59:23'),
(3, 6, 'Hapus Blog', 'Admin ID 6 menghapus blog ID 25 berjudul \'Tips pilih hotel untuk traveler\'', '2025-12-25 03:01:39'),
(4, 6, 'Tambah Blog', 'Admin ID 6 menambahkan blog berjudul \'Tips pilih hotel untuk traveler\'', '2025-12-25 03:02:03'),
(5, 6, 'Edit Blog', 'Admin ID 6 mengubah blog ID 27 menjadi judul \'\'', '2025-12-25 03:02:06'),
(6, 6, 'Edit Blog', 'Admin ID 6 mengubah blog ID 27 menjadi judul \'\'', '2025-12-25 03:03:43'),
(7, 6, 'Edit Blog', 'Admin ID 6 mengubah blog ID 27 menjadi judul \'\'', '2025-12-25 03:04:18'),
(8, 6, 'Edit Blog', 'Admin ID 6 mengubah blog ID 27 berjudul \'Tips pilih hotel untuk traveler\' dari status \'draft\' menjadi \'publish\'', '2025-12-25 03:07:03'),
(9, 6, 'Edit Blog', 'Admin ID 6 mengubah blog ID 27 berjudul \'Tips pilih hotel dan penginapan untuk traveler\'', '2025-12-25 03:07:18'),
(10, 6, 'Hapus Blog', 'Admin ID 6 menghapus blog ID 27 berjudul \'Tips pilih hotel dan penginapan untuk traveler\'', '2025-12-25 03:07:42'),
(11, 6, 'Tambah Blog', 'Admin ID 6 menambahkan blog berjudul \'Tips pilih hotel untuk traveler\'', '2025-12-25 03:08:26'),
(12, 6, 'Hapus Blog', 'Admin ID 6 menghapus blog ID 28 berjudul \'Tips pilih hotel untuk traveler\'', '2025-12-25 03:08:48');

-- --------------------------------------------------------

--
-- Table structure for table `pembatalan`
--

CREATE TABLE `pembatalan` (
  `id_pembatalan` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL,
  `alasan_pembatalan` text NOT NULL,
  `status_pembatalan` enum('diajukan','disetujui','ditolak') DEFAULT 'diajukan',
  `tanggal_pengajuan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `total_bayar` decimal(12,2) NOT NULL,
  `status_pembayaran` enum('paid','refund') DEFAULT 'paid',
  `tanggal_bayar` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penginapan`
--

CREATE TABLE `penginapan` (
  `id_penginapan` int(11) NOT NULL,
  `nama_penginapan` varchar(150) NOT NULL,
  `tipe_penginapan` enum('hotel','villa','homestay') NOT NULL,
  `id_kabupaten` int(11) NOT NULL,
  `id_kecamatan` int(11) NOT NULL,
  `alamat` text NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `tentang_kami` text DEFAULT NULL,
  `harga_mulai` decimal(12,2) NOT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `jumlah_review` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penginapan`
--

INSERT INTO `penginapan` (`id_penginapan`, `nama_penginapan`, `tipe_penginapan`, `id_kabupaten`, `id_kecamatan`, `alamat`, `deskripsi`, `tentang_kami`, `harga_mulai`, `rating`, `jumlah_review`, `is_featured`, `latitude`, `longitude`, `status`, `created_at`) VALUES
(1, 'Simply Homy Malioboro', 'homestay', 1, 1, 'Jl. Mojo No. 56, Danurejan', 'Homestay keluarga dengan fasilitas lengkap, menawarkan ketenangan di tengah pusat kota Yogyakarta.', 'Simply Homy adalah jaringan guesthouse yang menjamin standar kebersihan dan kenyamanan rumah pribadi bagi wisatawan rombongan.', 450000.00, 0.00, 0, 0, -7.79400000, 110.37520000, 'aktif', '2025-12-23 09:37:31'),
(2, 'Royal Malioboro by ASTON', 'hotel', 1, 2, 'Jl. Pasar Kembang No. 29, Gedongtengen', 'Hotel mewah bintang 4 tepat di depan Stasiun Tugu, dilengkapi kolam renang rooftop dan sky lounge panorama.', 'Royal Malioboro memposisikan diri sebagai hub utama wisatawan kelas atas di kawasan Malioboro dengan layanan ramah khas Jogja.', 1474024.00, 4.50, 120, 1, -7.79150000, 110.36380000, 'aktif', '2025-12-23 09:37:31'),
(3, 'Hotel New Saphir', 'hotel', 1, 3, 'Jl. Laksda Adisucipto No. 38, Gondokusuman', 'Akomodasi bisnis legendaris dengan lokasi strategis di koridor komersial utama, dekat pusat perbelanjaan.', 'Kami adalah hotel bintang 4 yang berfokus pada pelayanan tamu bisnis dengan fasilitas meeting room terlengkap.', 750000.00, 4.50, 120, 0, -7.78300000, 110.39200000, 'aktif', '2025-12-23 09:37:31'),
(4, 'Melia Purosani Yogyakarta', 'hotel', 1, 4, 'Jl. Suryotomo No. 31, Gondomanan', 'Resort urban dengan taman tropis luas dan kolam renang laguna, hanya berjarak jalan kaki ke Pasar Beringharjo.', 'Melia Purosani menghadirkan kemewahan bintang 5 dengan sentuhan budaya Spanyol-Jawa di jantung sejarah Yogyakarta.', 1600000.00, 4.50, 120, 1, -7.79800000, 110.36950000, 'aktif', '2025-12-23 09:37:31'),
(5, 'Hotel Tentrem Yogyakarta', 'hotel', 1, 5, 'Jl. AM. Sangaji No. 72A, Jetis', 'Definisi baru kemewahan lokal dengan fasilitas modern terbaik dan filosofi ketenangan jiwa (Tentrem).', 'Tentrem adalah merek hospitalitas asli Indonesia yang memberikan pengalaman menginap paling prestisius di Yogyakarta.', 2415852.00, 4.70, 85, 1, -7.77700000, 110.36700000, 'aktif', '2025-12-23 09:37:31'),
(6, 'Putra Pandawa Homestay', 'homestay', 1, 6, 'Purbayan, Kotagede', 'Penginapan ekonomis di pusat sejarah kerajaan Mataram, dikelilingi sentra kerajinan perak legendaris.', 'Kami menyediakan hunian ramah bagi backpacker yang ingin mendalami budaya dan sejarah autentik kota tua Kotagede.', 300000.00, 0.00, 0, 0, -7.82800000, 110.39800000, 'aktif', '2025-12-23 09:37:31'),
(7, 'Omah Sawo Guesthouse', 'homestay', 1, 7, 'Jl. Sawojajar No. 2B, Kraton', 'Hunian butik estetis di dalam benteng Keraton, menawarkan suasana damai dekat pusat kebudayaan Jawa.', 'Omah Sawo adalah guesthouse keluarga yang baru direnovasi, mengedepankan desain minimalis tropis.', 350000.00, 0.00, 0, 0, -7.80350000, 110.36550000, 'aktif', '2025-12-23 09:37:31'),
(8, 'Omah Jago Homestay', 'homestay', 1, 8, 'Jl. DI Panjaitan, Mantrijeron', 'Struktur Joglo megah dengan kolam renang pribadi di tengah kampung turis Prawirotaman.', 'Omah Jago merayakan arsitektur kayu tradisional Jawa dengan tetap menjamin privasi dan kemewahan bagi tamu.', 1500000.00, 0.00, 0, 0, -7.81700000, 110.36150000, 'aktif', '2025-12-23 09:37:31'),
(9, 'Java Villas Hotel', 'hotel', 1, 9, 'Jl. Gerilya No. 395, Mergangsan', 'Hotel butik eksotis di area Prawirotaman dengan desain arsitektur kolonial-lokal.', 'Java Villas didedikasikan bagi petualang perkotaan yang mencari atmosfer penginapan yang hidup, artistik, dan penuh cerita.', 600000.00, 4.70, 85, 0, -7.82050000, 110.37100000, 'aktif', '2025-12-23 09:37:31'),
(10, 'Ngampilan Residence', 'homestay', 1, 10, 'Jl. Letjen Suprapto, Ngampilan', 'Penginapan bersih dan syar\'i di kawasan Bakpia Pathok, memudahkan akses wisata kuliner keluarga.', 'Kami berkomitmen menyediakan akomodasi terjangkau di dekat jantung ekonomi kota Yogyakarta.', 250000.00, 0.00, 0, 0, -7.80100000, 110.35500000, 'aktif', '2025-12-23 09:37:31'),
(11, '1O1 STYLE Malioboro', 'hotel', 1, 11, 'Jl. Gajah Mada No. 30, Pakualaman', 'Hotel bergaya hidup dinamis dengan interior chic dekat Puro Pakualaman dan pusat kota timur.', 'Bagian dari jaringan hotel modern yang menyasar traveler aktif yang menghargai efisiensi dan estetika.', 615503.00, 4.70, 85, 0, -7.79950000, 110.37350000, 'aktif', '2025-12-23 09:37:31'),
(12, 'Eze Villa', 'villa', 1, 12, 'Jl. Jatimulyo No. 428, Tegalrejo', 'Vila privat dengan desain industrial-minimalis, oase ketenangan di tengah kesibukan Tegalrejo.', 'Eze Villa menyediakan ruang tinggal eksklusif dengan fasilitas dapur lengkap dan taman terbuka.', 1200000.00, 0.00, 0, 0, -7.78150000, 110.35300000, 'aktif', '2025-12-23 09:37:31'),
(13, 'Ayra Homestay', 'homestay', 1, 13, 'Jl. Menteri Supeno No. 91, Umbulharjo', 'Akomodasi keluarga dengan akses sangat dekat menuju Kebun Binatang Gembira Loka.', 'Ayra Homestay fokus pada hunian sementara yang nyaman dengan pelayanan kekeluargaan yang tulus.', 350000.00, 0.00, 0, 0, -7.80800000, 110.39500000, 'aktif', '2025-12-23 09:37:31'),
(14, 'YATS Colony', 'hotel', 1, 14, 'Jl. Patangpuluhan No. 23, Wirobrajan', 'Hotel butik artistik dengan kolam renang air asin dan lingkungan kreatif komunitas.', 'Destinasi di mana desain, seni, dan gaya hidup bersatu dalam harmoni hospitalitas.', 550000.00, 4.80, 200, 0, -7.80450000, 110.35250000, 'aktif', '2025-12-23 09:37:31'),
(15, 'Calma Jatimas Klayar Villa', 'villa', 2, 15, 'Jl. Kemasan Klayar, Berbah', 'Villa mewah dengan kolam renang infinity dan view sawah, hanya 10 menit dari Bandara Adisucipto.', 'Calma Jatimas menawarkan kemewahan privat di tengah alam Sleman untuk jiwa yang membutuhkan jeda.', 562747.00, 0.00, 0, 0, -7.79600000, 110.42850000, 'aktif', '2025-12-23 09:37:31'),
(16, 'The Cangkringan Villas', 'villa', 2, 16, 'Umbulharjo, Cangkringan', 'Resort mewah lereng Merapi dengan udara sejuk, fasilitas lapangan golf, dan kolam renang luas.', 'Pelopor resort pegunungan di Yogyakarta yang mengedepankan privasi dan keindahan lanskap alam.', 950000.00, 0.00, 0, 0, -7.63800000, 110.45500000, 'aktif', '2025-12-23 09:37:31'),
(17, 'Eastparc Hotel Yogyakarta', 'hotel', 2, 17, 'Jl. Laksda Adisucipto Km. 6.5, Depok', 'Hotel keluarga bertema taman hijau dengan fasilitas staycation terlengkap (bioskop anak & mini zoo).', 'Peraih penghargaan hotel ramah anak terbaik dengan standar kebersihan dan kelestarian lingkungan.', 120000.00, 4.80, 200, 1, -7.78250000, 110.41250000, 'aktif', '2025-12-23 09:37:31'),
(18, 'Skava House', 'villa', 2, 18, 'Ambarketawang, Gamping', 'Villa modern dengan desain menawan dan fasilitas jacuzzi, strategis di pintu masuk barat Sleman.', 'Menghadirkan kemewahan hunian perkotaan ke dalam lingkungan tenang bagi kelompok kecil.', 1300000.00, 0.00, 0, 0, -7.79200000, 110.33400000, 'aktif', '2025-12-23 09:37:31'),
(19, 'Omah Embun', 'homestay', 2, 19, 'Sidomulyo, Godean', 'Homestay sederhana dikelilingi vegetasi hijau, menawarkan udara segar kehidupan pedesaan.', 'Merasakan kembali kesederhanaan hidup melalui akomodasi bersih dan pelayanan ramah lokal.', 100000.00, 0.00, 0, 0, -7.77100000, 110.29200000, 'aktif', '2025-12-23 09:37:31'),
(20, 'Villa Kamar Tamu Selomartani', 'villa', 2, 20, 'Kaliwaru, Kalasan', 'Villa bertema rustik di tengah hamparan sawah dengan pemandangan langsung ke puncak Merapi.', 'Manifestasi kecintaan pada alam, menawarkan ruang tinggal puitis dan menginspirasi.', 400000.00, 0.00, 0, 0, -7.72900000, 110.46800000, 'aktif', '2025-12-23 09:37:31'),
(21, 'Hotel O Pondok Inspirasi', 'hotel', 2, 21, 'Sendangarum, Minggir', 'Akomodasi budget standar OYO di wilayah Sleman Barat, cocok mengeksplorasi sisi agraris DIY.', 'Menyediakan fasilitas dasar berkualitas bagi wisatawan petualang dengan harga ekonomis.', 111171.00, 4.80, 200, 0, -7.74500000, 110.23500000, 'aktif', '2025-12-23 09:37:31'),
(22, 'The Rich Jogja Hotel', 'hotel', 2, 22, 'Jl. Magelang Km. 6, Mlati', 'Hotel bintang 4 yang terhubung langsung dengan Jogja City Mall, memudahkan akses hiburan.', 'Ikon hospitalitas Sleman Utara yang melayani kebutuhan bisnis dengan standar internasional.', 800000.00, 0.00, 0, 1, -7.75150000, 110.35900000, 'aktif', '2025-12-23 09:37:31'),
(23, 'Omah Kecebong', 'homestay', 2, 23, 'Sumberrahayu, Moyudan', 'Wisata budaya menawarkan penginapan tradisional dengan aktivitas naik gerobak sapi dan membatik.', 'Ruang apresiasi budaya Jawa mengombinasikan akomodasi asri dengan edukasi kearifan lokal.', 450000.00, 0.00, 0, 0, -7.76800000, 110.27150000, 'aktif', '2025-12-23 09:37:31'),
(24, 'Sofia Boutique Residence', 'hotel', 2, 24, 'Jl. Palagan Tentara Pelajar, Ngaglik', 'Hotel butik eksklusif desain Eropa dengan aksen emas di kawasan elit Palagan.', 'Berkomitmen pada detail layanan premium dan privasi tamu dalam balutan atmosfer mewah.', 1100000.00, 0.00, 0, 0, -7.74200000, 110.37050000, 'aktif', '2025-12-23 09:37:31'),
(25, 'JK Private Villa', 'villa', 2, 25, 'Sukoharjo, Ngemplak', 'Villa keluarga kapasitas besar dengan kolam renang luas, strategis di koridor menuju Kaliurang.', 'Dirancang untuk mengakomodasi momen kumpul keluarga besar dengan kenyamanan terjamin.', 2000000.00, 0.00, 0, 0, -7.69100000, 110.40500000, 'aktif', '2025-12-23 09:37:31'),
(26, 'Villa Pakem Yogyakarta', 'villa', 2, 26, 'Pakembinangun, Pakem', 'Penginapan mewah dengan taman luas, kolam renang outdoor, dan udara pegunungan sejuk.', 'Tempat peristirahatan sempurna bagi yang ingin melarikan diri dari rutinitas kota.', 1500000.00, 0.00, 0, 0, -7.66200000, 110.41500000, 'aktif', '2025-12-23 09:37:31'),
(27, 'Hotel O Prambanan', 'hotel', 2, 27, 'Bokoharjo, Prambanan', 'Hotel syariah ekonomi sangat dekat dengan Candi Prambanan dan Tebing Breksi.', 'Akomodasi bersih dan terjangkau bagi wisatawan budaya dan peziarah sejarah.', 200000.00, 0.00, 0, 0, -7.75500000, 110.49100000, 'aktif', '2025-12-23 09:37:31'),
(28, 'Villa Samara', 'villa', 2, 28, 'Margoagung, Seyegan', 'Vila privat asri dengan kolam renang pribadi dan view persawahan Seyegan.', 'Memadukan estetika bangunan modern tropis dengan keramahan lingkungan desa.', 600000.00, 0.00, 0, 0, -7.73400000, 110.30150000, 'aktif', '2025-12-23 09:37:31'),
(29, 'Rumah Uti Paviliun', 'homestay', 2, 29, 'Tridadi, Sleman', 'Homestay gaya paviliun tenang di pusat pemerintahan, cocok untuk urusan dinas.', 'Menawarkan kenyamanan rumah sendiri dengan fasilitas memadai dekat jalan utama.', 400000.00, 0.00, 0, 0, -7.71200000, 110.35100000, 'aktif', '2025-12-23 09:37:31'),
(30, 'nDalem Eyang Dwijo', 'homestay', 2, 30, 'Margorejo, Tempel', 'Homestay tradisional arsitektur kayu Jawa autentik, atmosfer masa lalu di pedesaan.', 'Wujud pelestarian warisan rumah Jawa untuk merasakan kedamaian hidup desa.', 132231.00, 0.00, 0, 0, -7.64800000, 110.32500000, 'aktif', '2025-12-23 09:37:31'),
(31, 'Tenang Jiwa Villa', 'villa', 2, 31, 'Donokerto, Turi', 'Villa eksklusif dikelilingi kebun salak pondoh, menyajikan kesejukan lereng Merapi.', 'Tempat bagi pencari kesunyian dan penyatuan kembali dengan elemen alam Sleman.', 850000.00, 0.00, 0, 0, -7.62500000, 110.36800000, 'aktif', '2025-12-23 09:37:31'),
(32, 'Wisma Sanggrahan', 'homestay', 3, 32, 'Jl. Ganjuran, Bambanglipuro', 'Penginapan tenang lingkungan residensial, dekat objek wisata religi Gereja Ganjuran.', 'Menyediakan hunian sederhana bersih bagi peziarah yang menginginkan akses cepat Bantul Selatan.', 200000.00, 0.00, 0, 0, -7.92500000, 110.32450000, 'aktif', '2025-12-23 09:37:31'),
(33, 'The Mangkoro Hotel', 'hotel', 3, 33, 'Jl. Garuda No. 419, Banguntapan', 'Hotel butik mewah interior klasik Jawa-Kolonial, standar hospitalitas tinggi.', 'Simbol elegansi di Bantul Utara, memadukan kenyamanan modern dengan sentuhan seni heritage.', 340164.00, 0.00, 0, 1, -7.82450000, 110.39950000, 'aktif', '2025-12-23 09:37:31'),
(34, 'Omah Putih Homestay', 'homestay', 3, 34, 'Bangunjiwo, Bantul', 'Rumah sewa harian luas desain serba putih minimalis, cocok untuk rombongan keluarga.', 'Fasilitas rumah tinggal lengkap dengan area komunal luas untuk momen kebersamaan.', 1192500.00, 0.00, 0, 0, -7.84200000, 110.31800000, 'aktif', '2025-12-23 09:37:31'),
(35, 'Homestay Mangunan', 'homestay', 3, 35, 'Mangunan, Dlingo', 'Penginapan kayu dataran tinggi, akses prioritas ke Hutan Pinus Mangunan.', 'Pengalaman menginap \"di atas awan\" dengan panorama kabut pagi menakjubkan.', 250000.00, 0.00, 0, 0, -7.93500000, 110.42800000, 'aktif', '2025-12-23 09:37:31'),
(36, 'Homestay Wukirsari', 'homestay', 3, 36, 'Wukirsari, Imogiri', 'Penginapan di desa wisata batik, belajar kriya batik langsung dari pengrajin lokal.', 'Mengedepankan konsep ekowisata untuk memberikan pengalaman budaya mendalam.', 200000.00, 0.00, 0, 0, -7.91500000, 110.38800000, 'aktif', '2025-12-23 09:37:31'),
(37, 'Omah Zunie', 'homestay', 3, 37, 'Potorono, Jetis', 'Homestay mungil bersih fasilitas AC penuh, dekat kompleks Stadion Sultan Agung.', 'Pilihan cerdas akomodasi ekonomis dengan standar kenyamanan terjaga di Bantul.', 200000.00, 0.00, 0, 0, -7.88800000, 110.37200000, 'aktif', '2025-12-23 09:37:31'),
(38, 'Kamala House', 'homestay', 3, 38, 'Jl. Tambak, Kasihan', 'Akomodasi butik hangat pilihan tipe kamar beragam, dekat sentra gerabah Kasongan.', 'Menghadirkan suasana hunian menginspirasi bagi traveler muda dan komunitas kreatif.', 316375.00, 0.00, 0, 0, -7.80150000, 110.34450000, 'aktif', '2025-12-23 09:37:31'),
(39, 'Queen of The South Resort', 'hotel', 3, 39, 'Parangtritis, Kretek', 'Resort mewah tepi pantai infinity pool menghadap Samudra Hindia.', 'Resort tebing di Bantul dengan pemandangan matahari terbenam terbaik DIY.', 1282278.00, 0.00, 0, 1, -8.02500000, 110.32100000, 'aktif', '2025-12-23 09:37:31'),
(40, 'Amartha Indotama Villa', 'villa', 3, 40, 'Guwosari, Pajangan', 'Villa kolam renang pribadi kawasan Pajangan tenang, dekat situs Goa Selarong.', 'Tempat pelarian sempurna bagi yang menginginkan privasi total desain modern.', 876197.00, 0.00, 0, 1, -7.86800000, 110.28800000, 'aktif', '2025-12-23 09:37:31'),
(41, 'Yukke Tembi Homestay', 'homestay', 3, 41, 'Desa Wisata Tembi, Pandak', 'Rumah singgah tradisional jantung Desa Tembi, atmosfer pedesaan Jawa sawah terbuka.', 'Mengajak tamu melambat menikmati kehidupan desa sembari belajar kearifan lokal.', 318755.00, 0.00, 0, 0, -7.86450000, 110.35850000, 'aktif', '2025-12-23 09:37:31'),
(42, 'Joglo Ndalem Sabine', 'homestay', 3, 42, 'Sitimulyo, Piyungan', 'Penginapan keluarga syariah arsitektur Joglo artistik di jalur utama Gunungkidul.', 'Ruang istirahat aman tertib bagi wisatawan keluarga mengutamakan kesantunan Jawa.', 250000.00, 0.00, 0, 1, -7.82800000, 110.45200000, 'aktif', '2025-12-23 09:37:31'),
(43, 'Omah Andini', 'homestay', 3, 43, 'Jl. Janti, Pleret', 'Homestay asri kawasan bersejarah Pleret, dekat pusat kuliner Sate Klatak.', 'Keramahtamahan Bantul lokasi memudahkan eksplorasi warisan budaya selatan kota.', 400000.00, 0.00, 0, 0, -7.86800000, 110.40100000, 'aktif', '2025-12-23 09:37:31'),
(44, 'VillaCantik Yogyakarta', 'villa', 3, 44, 'Pundong', 'Vila eksklusif pemandangan perbukitan Pundong, fasilitas premium dekat sentra Miedes.', 'Staycation baru di Bantul menggabungkan keindahan kontur lahan arsitektur elegan.', 1299992.00, 0.00, 0, 0, -7.94500000, 110.34500000, 'aktif', '2025-12-23 09:37:31'),
(45, 'Hotel Puri Brata', 'hotel', 3, 45, 'Gadingharjo, Sanden', 'Hotel konsep ekologi pesisir selatan, dekat Pantai Goa Cemara & konservasi.', 'Berkomitmen pada hidup berkelanjutan, menyatu harmoni laut dan hutan cemara.', 438160.00, 0.00, 0, 0, -7.99500000, 110.27800000, 'aktif', '2025-12-23 09:37:31'),
(46, 'Banbili Homestay', 'homestay', 3, 46, 'Argomulyo, Sedayu', 'Akomodasi nyaman strategis Sedayu, titik transit ideal menuju Bandara YIA.', 'Fasilitas hunian standar lengkap mendukung perjalanan bisnis maupun wisata.', 400000.00, 0.00, 0, 0, -7.81800000, 110.25500000, 'aktif', '2025-12-23 09:37:31'),
(47, 'Ros In Hotel', 'hotel', 3, 47, 'Jl. Ringroad Selatan, Sewon', 'Hotel bintang 4 megah jalur lingkar selatan, fasilitas MICE kolam renang olimpiade.', 'Hub hospitalitas utama wilayah Sewon melayani tamu korporat standar tinggi.', 401850.00, 0.00, 0, 0, -7.83800000, 110.36500000, 'aktif', '2025-12-23 09:37:31'),
(48, 'Hotel Dewata YIA', 'hotel', 3, 48, 'Trimurti, Srandakan', 'Akomodasi budget dekat Jembatan Pandansimo, menghubungkan pesisir Bantul-KP.', 'Kemudahan akses bagi pelintas kabupaten membutuhkan tempat istirahat bersih aman.', 150000.00, 0.00, 0, 1, -7.95500000, 110.22800000, 'aktif', '2025-12-23 09:37:31'),
(49, 'Yusman Homestay Syariah', 'homestay', 4, 49, 'Palihan, Galur', 'Penginapan sangat dekat terminal bandara YIA, konsep pelayanan syariah tamu transit.', 'Berfokus pada penyediaan tempat istirahat higienis terjangkau pelintas udara.', 132786.00, 0.00, 0, 0, -7.89800000, 110.06350000, 'aktif', '2025-12-23 09:37:31'),
(50, 'Omah Watu Blencong', 'homestay', 4, 50, 'Sokomoyo, Girimulyo', 'Penginapan ekowisata Perbukitan Menoreh panorama lembah memukau arsitektur tradisional.', 'Menikmati kedamaian pegunungan sembari mencicipi kuliner lokal autentik.', 100000.00, 0.00, 0, 0, -7.75500000, 110.12800000, 'aktif', '2025-12-23 09:37:31'),
(51, 'Homestay Kalibawang', 'homestay', 4, 51, 'Kalibawang', 'Akomodasi jalur wisata utara dekat Sendangsono dan kebun durian lokal.', 'Hunian sederhana asri petualang alam mengeksplorasi pegunungan Menoreh.', 150000.00, 0.00, 0, 0, -7.66800000, 110.22800000, 'aktif', '2025-12-23 09:37:31'),
(52, 'Jemakir Homestay', 'homestay', 4, 52, 'Hargotirto, Kokap', 'Penginapan pemandangan Waduk Sermo, cocok aktivitas outdoor dan fotografi alam.', 'Keramahan penduduk lokal dalam balutan suasana perbukitan Kokap yang asri.', 115837.00, 0.00, 0, 0, -7.83500000, 110.10800000, 'aktif', '2025-12-23 09:37:31'),
(53, 'Homestay Galuh', 'homestay', 4, 53, 'Jatirejo, Lendah', 'Akomodasi jantung sentra Batik Lendah, akses mudah belajar membatik.', 'Berkomitmen mempromosikan pariwisata berbasis kerajinan tangan Kulon Progo.', 150000.00, 0.00, 0, 0, -7.92500000, 110.19800000, 'aktif', '2025-12-23 09:37:31'),
(54, 'The Swantari by ARBA', 'hotel', 4, 54, 'Nanggulan', 'Hotel panorama persawahan Nanggulan fenomenal, kuliner khas desa.', 'Ruang menikmati ketenangan lanskap sawah menyerupai suasana Ubud Bali.', 501552.00, 0.00, 0, 0, -7.78500000, 110.17800000, 'aktif', '2025-12-23 09:37:31'),
(55, 'Homestay Panjatan', 'homestay', 4, 55, 'Panjatan', 'Penginapan strategis akses mudah kawasan pantai dan pusat pemerintahan.', 'Akomodasi bersih fungsional bagi pelancong bisnis maupun wisatawan.', 120000.00, 0.00, 0, 1, -7.91500000, 110.14500000, 'aktif', '2025-12-23 09:37:31'),
(56, 'Griya Harja Homestay', 'homestay', 4, 56, 'Majaksingi, Pengasih', 'Akomodasi dekat stasiun Wates, efisiensi mobilitas tamu luar kota.', 'Rumah singgah hangat bagi kepentingan dinas atau keluarga di wilayah Pengasih.', 123967.00, 0.00, 0, 1, -7.84800000, 110.15500000, 'aktif', '2025-12-23 09:37:31'),
(57, 'Homestay Banyumili', 'homestay', 4, 57, 'Gerbosari, Samigaluh', 'Penginapan puncak perbukitan Kebun Teh Nglinggo, udara pegunungan segar.', 'Pengalaman menginap \"back to nature\" autentik dikelola kekeluargaan.', 50000.00, 0.00, 0, 0, -7.64500000, 110.11800000, 'aktif', '2025-12-23 09:37:31'),
(58, 'Homestay Sentolo', 'homestay', 4, 58, 'Sentolo', 'Penginapan budget praktis dekat stasiun Sentolo transit jalur kereta Jawa.', 'Mengedepankan kebersihan kemudahan akses koridor utama DIY Utara.', 150000.00, 0.00, 0, 0, -7.83800000, 110.21800000, 'aktif', '2025-12-23 09:37:31'),
(59, 'Cordia Hotel', 'hotel', 4, 59, 'Arrival Terminal YIA, Temon', 'Hotel bandara bintang 3 di dalam terminal YIA kenyamanan maksimal tamu transit.', 'Hadir dengan desain modern fasilitas lounge gym traveler global.', 800000.00, 0.00, 0, 0, -7.90500000, 110.05800000, 'aktif', '2025-12-23 09:37:31'),
(60, 'King Hotel Wates', 'hotel', 4, 60, 'Jl. Nagung-Wates No. 1, Wates', 'Hotel bisnis ibukota Kulon Progo dekat Alun-alun Wates pusat administrasi.', 'Standar pelayanan profesional bagi tamu dinas maupun wisatawan.', 300000.00, 0.00, 0, 0, -7.85800000, 110.16200000, 'aktif', '2025-12-23 09:37:31'),
(61, 'Homestay Gedangsari', 'homestay', 5, 61, 'Gedangsari', 'Akomodasi puncak Green Village Gedangsari sensasi ketinggian udara pegunungan.', 'Penginapan komunitas mendukung ekowisata petualangan utara Gunungkidul.', 150000.00, 0.00, 0, 0, -7.81800000, 110.55800000, 'aktif', '2025-12-23 09:37:31'),
(62, 'Jungwok Blue Ocean', 'hotel', 5, 62, 'Pendowo, Jepitu, Girisubo', 'Resort Santorini Indonesia desain putih-biru ikonik menghadap laut lepas.', 'Standar baru hospitalitas pesisir pantai privat spa arsitektur kelas dunia.', 701361.00, 0.00, 0, 0, -8.19450000, 110.71250000, 'aktif', '2025-12-23 09:37:31'),
(63, 'Amini Guest House', 'homestay', 5, 63, 'Gedangrejo, Karangmojo', 'Penginapan dikelilingi taman asri dekat destinasi tubing Goa Pindul.', 'Lingkungan tenang fasilitas lengkap pencari kedamaian aktivitas alam.', 137968.00, 0.00, 0, 0, -7.94500000, 110.66500000, 'aktif', '2025-12-23 09:37:31'),
(64, 'Homestay Ngawen', 'homestay', 5, 64, 'Ngawen', 'Homestay keluarga bersih area situs Candi Risan atmosfer pedesaan kental.', 'Akomodasi penjelajah situs-situs Mataram Kuno perbatasan Jateng-DIY.', 150000.00, 0.00, 0, 0, -7.82800000, 110.68800000, 'aktif', '2025-12-23 09:37:31'),
(65, 'Hotel Santika Gunungkidul', 'hotel', 5, 65, 'Logandeng, Nglipar', 'Hotel bintang 3 standar internasional pertama gerbang masuk pusat kabupaten.', 'Menghadirkan hospitalitas Indonesia hangat pelancong bisnis keluarga.', 600000.00, 0.00, 0, 0, -7.94200000, 110.58200000, 'aktif', '2025-12-23 09:37:31'),
(66, 'Homestay Paliyan', 'homestay', 5, 66, 'Paliyan', 'Penginapan asri dekat suaka margasatwa ketenangan pencinta alam biodiversitas.', 'Konsep hidup selaras alam menyatu lingkungan hutan konservasi.', 120000.00, 0.00, 0, 0, -7.99500000, 110.53800000, 'aktif', '2025-12-23 09:37:31'),
(67, 'HeHa Ocean Glamping', 'villa', 5, 67, 'Bolang, Girikarto, Panggang', 'Glamping mewah tebing pantai selatan mini pool pribadi panorama laut 180 derajat.', 'Destinasi staycation viral DIY kemewahan modern keindahan tebing laut.', 900000.00, 0.00, 0, 0, -8.11450000, 110.45200000, 'aktif', '2025-12-23 09:37:31'),
(68, 'Maryam Homestay Syariah 2', 'homestay', 5, 68, 'Bukit Bintang, Patuk', 'Penginapan syariah Bukit Bintang pemandangan City Light spektakuler ketinggian.', 'Hunian nyaman terjangkau menikmati keindahan malam gerbang masuk Gunungkidul.', 96340.00, 0.00, 0, 0, -7.84800000, 110.48500000, 'aktif', '2025-12-23 09:37:31'),
(69, 'Omah Anggur', 'homestay', 5, 69, 'Gading, Playen', 'Homestay unik perkebunan anggur pekarangan edukasi bercocok tanam.', 'Memadukan hobi pertanian hospitalitas suasana homey inspiratif keluarga.', 150000.00, 0.00, 0, 0, -7.96500000, 110.54500000, 'aktif', '2025-12-23 09:37:31'),
(70, 'Homestay Alrisqi', 'homestay', 5, 70, 'Genjahan, Ponjong', 'Akomodasi kawasan karst Ponjong dikelilingi gua alam telaga udara sejuk.', 'Penyediaan hunian wisatawan minat khusus eksplorasi geologi Gunungkidul Timur.', 150000.00, 0.00, 0, 1, -7.98500000, 110.72800000, 'aktif', '2025-12-23 09:37:31'),
(71, 'Khayla Homestay Syariah', 'homestay', 5, 71, 'Giripurwo, Purwosari', 'Penginapan syariah tenang jalur Pantai Parangtritis perbukitan Purwosari.', 'Fasilitas menginap tertib bersih rombongan keluarga pesisir barat.', 499993.00, 0.00, 0, 0, -8.01200000, 110.35500000, 'aktif', '2025-12-23 09:37:31'),
(72, 'PALEO Stone Age', 'hotel', 5, 72, 'Rongkop', 'Hotel desain bebatuan unik menyerupai gua purbakala fasilitas bintang 3.', 'Representasi kreatif kekayaan alam karst Gunungkidul akomodasi unik.', 751314.00, 0.00, 0, 0, -8.08500000, 110.78500000, 'aktif', '2025-12-23 09:37:31'),
(73, 'Teras Kaca', 'villa', 5, 73, 'Pantai Nguluran, Saptosari', 'Villa eksklusif tebing menyatu objek wisata Teras Kaca sensasi memicu adrenalin.', 'Akses tak terbatas spot foto ikonik pinggir tebing panorama laut megah.', 685084.00, 0.00, 0, 0, -8.11800000, 110.46500000, 'aktif', '2025-12-23 09:37:31'),
(74, 'Arta Graha', 'homestay', 5, 74, 'Semanu', 'Penginapan strategis jalur utama pantai selatan akses Baron Kukup Krakal.', 'Kamar luas fasilitas memadai petualang eksplorasi keindahan pesisir.', 341303.00, 0.00, 0, 0, -8.01500000, 110.63800000, 'aktif', '2025-12-23 09:37:31'),
(75, 'Homestay Semin', 'homestay', 5, 75, 'Semin', 'Akomodasi keluarga asri perbatasan Jateng-DIY jauh hiruk-pikuk kota.', 'Standar pelayanan rumah tangga ramah kebersihan terjamin tamu bisnis Semin.', 120000.00, 0.00, 0, 1, -7.86800000, 110.75500000, 'aktif', '2025-12-23 09:37:31'),
(76, 'Hotel Kukup Indah', 'hotel', 5, 76, 'Pantai Kukup, Tanjungsari', 'Hotel budget beberapa meter bibir pantai Kukup akses wisata pantai keluarga.', 'Fasilitas parkir luas kedai makan laut memudahkan liburan pantai.', 149862.00, 0.00, 0, 0, -8.13200000, 110.55200000, 'aktif', '2025-12-23 09:37:31'),
(77, 'Rock Garden Homestay', 'homestay', 5, 77, 'Pantai Sundak, Tepus', 'Penginapan unik konsep taman batu alam tepi pantai Sundak suasana tropis santai.', 'Tempat peristirahatan pencinta laut menginginkan kedekatan total pantai.', 212427.00, 0.00, 0, 0, -8.14500000, 110.60850000, 'aktif', '2025-12-23 09:37:31'),
(78, 'Dea Lokha Hotel', 'hotel', 5, 78, 'Jl. KH Agus Salim No. 148, Wonosari', 'Hotel legendaris jantung Wonosari akses pusat administrasi bank mal.', 'Mitra terpercaya kunjungan dinas transit wisatawan kawasan pantai.', 297346.00, 0.00, 0, 0, -7.96800000, 110.60150000, 'aktif', '2025-12-23 09:37:31'),
(313, 'Simply Homy Malioboro', 'homestay', 1, 1, 'Danurejan', 'Dekat Stasiun Lempuyangan.', NULL, 450000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(314, 'Royal Malioboro by ASTON', 'hotel', 1, 2, 'Gedongtengen', 'Hotel mewah depan Stasiun Tugu.', NULL, 1474024.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(315, 'Hotel New Saphir', 'hotel', 1, 3, 'Gondokusuman', 'Hotel bintang 4 di pusat kota.', NULL, 750000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(316, 'Melia Purosani Yogyakarta', 'hotel', 1, 4, 'Gondomanan', 'Hotel bintang 5 dekat Malioboro.', NULL, 1600000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(317, 'Hotel Tentrem Yogyakarta', 'hotel', 1, 5, 'Jetis', 'Fasilitas mewah bintang 5.', NULL, 2415852.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(318, 'Putra Pandawa Homestay', 'homestay', 1, 6, 'Kotagede', 'Penginapan budget di area perak.', NULL, 300000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(319, 'Omah Sawo Guesthouse', 'homestay', 1, 7, 'Kraton', 'Dekat area Keraton Yogyakarta.', NULL, 350000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(320, 'Omah Jago Homestay', 'homestay', 1, 8, 'Mantrijeron', 'Bangunan Joglo dengan kolam renang.', NULL, 1500000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(321, 'Java Villas Hotel', 'hotel', 1, 9, 'Mergangsan', 'Area Prawirotaman yang eksotis.', NULL, 600000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(322, 'Ngampilan Residence', 'homestay', 1, 10, 'Ngampilan', 'Dekat pusat oleh-oleh Bakpia.', NULL, 250000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(323, '1O1 STYLE Malioboro', 'hotel', 1, 11, 'Pakualaman', 'Hotel modern dekat Puro Pakualaman.', NULL, 615503.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(324, 'Eze Villa', 'villa', 1, 12, 'Tegalrejo', 'Vila privat dekat pusat kota.', NULL, 1200000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(325, 'Ayra Homestay', 'homestay', 1, 13, 'Umbulharjo', 'Akses mudah ke Gembira Loka.', NULL, 350000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(326, 'YATS Colony', 'hotel', 1, 14, 'Wirobrajan', 'Hotel butik dengan desain unik.', NULL, 550000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(327, 'Calma Jatimas Klayar Villa', 'villa', 2, 15, 'Berbah', 'Vila dengan kolam renang pribadi.', NULL, 562747.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(328, 'The Cangkringan Villas', 'villa', 2, 16, 'Cangkringan', 'Resort lereng Gunung Merapi.', NULL, 950000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(329, 'Eastparc Hotel Yogyakarta', 'hotel', 2, 17, 'Depok', 'Hotel ramah anak terbaik.', NULL, 1200000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(330, 'Skava House', 'villa', 2, 18, 'Gamping', 'Vila modern fasilitas jacuzzi.', NULL, 1300000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(331, 'Omah Embun', 'homestay', 2, 19, 'Godean', 'Suasana pedesaan yang tenang.', NULL, 100000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(332, 'Villa Kamar Tamu Selomartani', 'villa', 2, 20, 'Kalasan', 'Vila di tengah sawah asri.', NULL, 400000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(333, 'Hotel O Pondok Inspirasi', 'hotel', 2, 21, 'Minggir', 'Penginapan budget di Sleman Barat.', NULL, 111171.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(334, 'The Rich Jogja Hotel', 'hotel', 2, 22, 'Mlati', 'Terhubung langsung dengan mall.', NULL, 800000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(335, 'Omah Kecebong', 'homestay', 2, 23, 'Moyudan', 'Konsep budaya dan alam.', NULL, 450000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(336, 'Sofia Boutique Residence', 'hotel', 2, 24, 'Ngaglik', 'Desain Eropa yang elegan.', NULL, 1100000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(337, 'JK Private Villa', 'villa', 2, 25, 'Ngemplak', 'Kapasitas besar untuk keluarga.', NULL, 2000000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(338, 'Villa Pakem Yogyakarta', 'villa', 2, 26, 'Pakem', 'Vila privat udara sejuk.', NULL, 1500000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(339, 'Hotel O Prambanan', 'hotel', 2, 27, 'Prambanan', 'Hanya 5 menit ke Candi Prambanan.', NULL, 200000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(340, 'Villa Samara', 'villa', 2, 28, 'Seyegan', 'Vila dengan kolam renang.', NULL, 600000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(341, 'Rumah Uti Paviliun', 'homestay', 2, 29, 'Sleman', 'Dekat pusat pemerintahan kabupaten.', NULL, 400000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(342, 'nDalem Eyang Dwijo', 'homestay', 2, 30, 'Tempel', 'Homestay tradisional Jawa.', NULL, 132231.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(343, 'Tenang Jiwa Villa', 'villa', 2, 31, 'Turi', 'Dikelilingi kebun salak pondoh.', NULL, 850000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(344, 'Wisma Sanggrahan', 'homestay', 3, 32, 'Bambanglipuro', 'Dekat Gereja Ganjuran.', NULL, 200000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(345, 'The Mangkoro Hotel', 'hotel', 3, 33, 'Banguntapan', 'Hotel butik area selatan kota.', NULL, 340164.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(346, 'Omah Putih Homestay', 'homestay', 3, 34, 'Bantul', 'Homestay luas untuk rombongan.', NULL, 1192500.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(347, 'Homestay Mangunan', 'homestay', 3, 35, 'Dlingo', 'Dekat Hutan Pinus Mangunan.', NULL, 250000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(348, 'Homestay Wukirsari', 'homestay', 3, 36, 'Imogiri', 'Dekat sentra batik dan makam raja.', NULL, 200000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(349, 'Omah Zunie', 'homestay', 3, 37, 'Jetis', 'Akses mudah ke Stadion Sultan Agung.', NULL, 200000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(350, 'Kamala House', 'homestay', 3, 38, 'Kasihan', 'Dekat sentra kerajinan Kasongan.', NULL, 316375.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(351, 'Queen of The South Resort', 'hotel', 3, 39, 'Kretek', 'Resort mewah tepi pantai.', NULL, 1282278.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(352, 'Amartha Indotama Villa', 'villa', 3, 40, 'Pajangan', 'Vila tenang dekat Goa Selarong.', NULL, 876197.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(353, 'Yukke Tembi Homestay', 'homestay', 3, 41, 'Pandak', 'Nuansa desa wisata budaya.', NULL, 318755.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(354, 'Joglo Ndalem Sabine', 'homestay', 3, 42, 'Piyungan', 'Homestay syariah asri.', NULL, 250000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(355, 'Omah Andini', 'homestay', 3, 43, 'Pleret', 'Dekat situs bersejarah Pleret.', NULL, 400000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(356, 'VillaCantik Yogyakarta', 'villa', 3, 44, 'Pundong', 'Dekat area kuliner Miedes.', NULL, 1299992.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(357, 'Hotel Puri Brata', 'hotel', 3, 45, 'Sanden', 'Dekat Pantai Goa Cemara.', NULL, 438160.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(358, 'Banbili Homestay', 'homestay', 3, 46, 'Sedayu', 'Homestay nyaman dekat UMY.', NULL, 400000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(359, 'Ros In Hotel', 'hotel', 3, 47, 'Sewon', 'Hotel bintang 4 di Ringroad Selatan.', NULL, 401850.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(360, 'Hotel Dewata YIA', 'hotel', 3, 48, 'Srandakan', 'Dekat jembatan ikonik Pandansimo.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(361, 'Yusman Homestay Syariah', 'homestay', 4, 49, 'Galur', 'Dekat area persawahan pesisir.', NULL, 132786.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(362, 'Omah Watu Blencong', 'homestay', 4, 50, 'Girimulyo', 'Pemandangan Perbukitan Menoreh.', NULL, 100000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(363, 'Homestay Kalibawang', 'homestay', 4, 51, 'Kalibawang', 'Area ekowisata Menoreh.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(364, 'Jemakir Homestay', 'homestay', 4, 52, 'Kokap', 'Dekat Waduk Sermo.', NULL, 115837.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(365, 'Homestay Galuh', 'homestay', 4, 53, 'Lendah', 'Dekat sentra batik Lendah.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(366, 'The Swantari by ARBA', 'hotel', 4, 54, 'Nanggulan', 'Hotel konsep kuliner dan alam.', NULL, 501552.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(367, 'Homestay Panjatan', 'homestay', 4, 55, 'Panjatan', 'Akses mudah ke pantai selatan.', NULL, 120000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(368, 'Griya Harja Homestay', 'homestay', 4, 56, 'Pengasih', 'Dekat pusat administrasi kabupaten.', NULL, 123967.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(369, 'Homestay Banyumili', 'homestay', 4, 57, 'Samigaluh', 'Dekat Kebun Teh Nglinggo.', NULL, 50000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(370, 'Homestay Sentolo', 'homestay', 4, 58, 'Sentolo', 'Dekat stasiun kereta api Sentolo.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(371, 'Cordia Hotel', 'hotel', 4, 59, 'Temon', 'Hotel di dalam terminal bandara YIA.', NULL, 800000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(372, 'King Hotel Wates', 'hotel', 4, 60, 'Wates', 'Dekat Alun-Alun Wates.', NULL, 300000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(373, 'Homestay Gedangsari', 'homestay', 5, 61, 'Gedangsari', 'Dekat Green Village Gedangsari.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(374, 'Jungwok Blue Ocean', 'hotel', 5, 62, 'Girisubo', 'Resort konsep Santorini.', NULL, 701361.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(375, 'Amini Guest House', 'homestay', 5, 63, 'Karangmojo', 'Dekat wisata Goa Pindul.', NULL, 137968.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(376, 'Homestay Ngawen', 'homestay', 5, 64, 'Ngawen', 'Dekat situs Candi Risan.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(377, 'Hotel Santika Gunungkidul', 'hotel', 5, 65, 'Nglipar', 'Fasilitas bintang 3 baru.', NULL, 600000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(378, 'Homestay Paliyan', 'homestay', 5, 66, 'Paliyan', 'Dekat hutan suaka margasatwa.', NULL, 120000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(379, 'HeHa Ocean Glamping', 'villa', 5, 67, 'Panggang', 'Glamping pemandangan laut.', NULL, 900000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(380, 'Maryam Homestay Syariah 2', 'homestay', 5, 68, 'Patuk', 'Dekat Gunung Api Purba.', NULL, 96340.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(381, 'Omah Anggur', 'homestay', 5, 69, 'Playen', 'Dekat Air Terjun Sri Gethuk.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(382, 'Homestay Alrisqi', 'homestay', 5, 70, 'Ponjong', 'Dekat area gua karst.', NULL, 150000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(383, 'Khayla Homestay Syariah', 'homestay', 5, 71, 'Purwosari', 'Area perbukitan menuju pantai.', NULL, 499993.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(384, 'PALEO Stone Age', 'hotel', 5, 72, 'Rongkop', 'Hotel dengan desain bebatuan unik.', NULL, 751314.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(385, 'Teras Kaca', 'villa', 5, 73, 'Saptosari', 'Spot foto kaca ikonik di tebing.', NULL, 685084.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(386, 'Arta Graha', 'homestay', 5, 74, 'Semanu', 'Area penghubung ke berbagai pantai.', NULL, 341303.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(387, 'Homestay Semin', 'homestay', 5, 75, 'Semin', 'Area perbatasan Jateng-DIY.', NULL, 120000.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(388, 'Hotel Kukup Indah', 'hotel', 5, 76, 'Tanjungsari', 'Dekat Pantai Baron dan Kukup.', NULL, 149862.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(389, 'Rock Garden Homestay', 'homestay', 5, 77, 'Tepus', 'Hanya 2 menit dari Pantai Sundak.', NULL, 212427.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(390, 'Dea Lokha Hotel', 'hotel', 5, 78, 'Wonosari', 'Dekat pusat administrasi kabupaten.', NULL, 297346.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-22 06:06:04'),
(401, 'vila paralaya', 'homestay', 3, 48, 'jalan bantul', 'adlaah', 'adalah', 0.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-25 05:17:11'),
(402, 'villa delunaAAA', 'homestay', 5, 78, 'jalan', 'adlald', 'adedddcf', 0.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-25 05:55:18'),
(403, 'villa delunaAAA', 'homestay', 3, 47, 'ahdknc', 'adalah pokoknya', 'dcjeewiojioej', 0.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-25 05:58:32'),
(404, 'villa delunaAAA', 'homestay', 1, 14, 'kjsdkjdsj', 'hallo', 'ajhdioa', 0.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-25 06:00:36'),
(405, 'vila paralaya', 'homestay', 5, 77, 'ashjdhsab', 'askjaksoq', 'ashjkxajks', 0.00, 0.00, 0, 0, NULL, NULL, 'aktif', '2025-12-25 06:04:35');

-- --------------------------------------------------------

--
-- Table structure for table `penginapan_fasilitas`
--

CREATE TABLE `penginapan_fasilitas` (
  `id_penginapan` int(11) NOT NULL,
  `id_fasilitas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penginapan_fasilitas`
--

INSERT INTO `penginapan_fasilitas` (`id_penginapan`, `id_fasilitas`) VALUES
(313, 1),
(313, 2),
(313, 4),
(313, 7),
(314, 1),
(314, 2),
(314, 3),
(314, 4),
(314, 5),
(314, 7),
(314, 8),
(314, 9),
(314, 14),
(314, 15),
(314, 16),
(315, 1),
(315, 2),
(315, 3),
(315, 4),
(315, 5),
(315, 7),
(315, 8),
(315, 9),
(315, 14),
(315, 15),
(315, 16),
(316, 1),
(316, 2),
(316, 3),
(316, 4),
(316, 5),
(316, 7),
(316, 8),
(316, 9),
(316, 14),
(316, 15),
(316, 16),
(317, 1),
(317, 2),
(317, 3),
(317, 4),
(317, 5),
(317, 7),
(317, 8),
(317, 9),
(317, 14),
(317, 15),
(317, 16),
(318, 1),
(318, 2),
(318, 4),
(318, 7),
(319, 1),
(319, 2),
(319, 4),
(319, 7),
(320, 1),
(320, 2),
(320, 4),
(320, 7),
(321, 1),
(321, 2),
(321, 3),
(321, 4),
(321, 5),
(321, 7),
(321, 8),
(321, 9),
(321, 14),
(321, 15),
(321, 16),
(322, 1),
(322, 2),
(322, 4),
(322, 7),
(323, 1),
(323, 2),
(323, 3),
(323, 4),
(323, 5),
(323, 7),
(323, 8),
(323, 9),
(323, 14),
(323, 15),
(323, 16),
(324, 1),
(324, 2),
(324, 4),
(324, 6),
(324, 7),
(324, 10),
(324, 11),
(324, 12),
(324, 13),
(325, 1),
(325, 2),
(325, 4),
(325, 7),
(326, 1),
(326, 2),
(326, 3),
(326, 4),
(326, 5),
(326, 7),
(326, 8),
(326, 9),
(326, 14),
(326, 15),
(326, 16),
(327, 1),
(327, 2),
(327, 4),
(327, 6),
(327, 7),
(327, 10),
(327, 11),
(327, 12),
(327, 13),
(328, 1),
(328, 2),
(328, 4),
(328, 6),
(328, 7),
(328, 10),
(328, 11),
(328, 12),
(328, 13),
(329, 1),
(329, 2),
(329, 3),
(329, 4),
(329, 5),
(329, 7),
(329, 8),
(329, 9),
(329, 14),
(329, 15),
(329, 16),
(330, 1),
(330, 2),
(330, 4),
(330, 6),
(330, 7),
(330, 10),
(330, 11),
(330, 12),
(330, 13),
(331, 1),
(331, 2),
(331, 4),
(331, 7),
(332, 1),
(332, 2),
(332, 4),
(332, 6),
(332, 7),
(332, 10),
(332, 11),
(332, 12),
(332, 13),
(333, 1),
(333, 2),
(333, 3),
(333, 4),
(333, 5),
(333, 7),
(333, 8),
(333, 9),
(333, 14),
(333, 15),
(333, 16),
(334, 1),
(334, 2),
(334, 3),
(334, 4),
(334, 5),
(334, 7),
(334, 8),
(334, 9),
(334, 14),
(334, 15),
(334, 16),
(335, 1),
(335, 2),
(335, 4),
(335, 7),
(336, 1),
(336, 2),
(336, 3),
(336, 4),
(336, 5),
(336, 7),
(336, 8),
(336, 9),
(336, 14),
(336, 15),
(336, 16),
(337, 1),
(337, 2),
(337, 4),
(337, 6),
(337, 7),
(337, 10),
(337, 11),
(337, 12),
(337, 13),
(338, 1),
(338, 2),
(338, 4),
(338, 6),
(338, 7),
(338, 10),
(338, 11),
(338, 12),
(338, 13),
(339, 1),
(339, 2),
(339, 3),
(339, 4),
(339, 5),
(339, 7),
(339, 8),
(339, 9),
(339, 14),
(339, 15),
(339, 16),
(340, 1),
(340, 2),
(340, 4),
(340, 6),
(340, 7),
(340, 10),
(340, 11),
(340, 12),
(340, 13),
(341, 1),
(341, 2),
(341, 4),
(341, 7),
(342, 1),
(342, 2),
(342, 4),
(342, 7),
(343, 1),
(343, 2),
(343, 4),
(343, 6),
(343, 7),
(343, 10),
(343, 11),
(343, 12),
(343, 13),
(344, 1),
(344, 2),
(344, 4),
(344, 7),
(345, 1),
(345, 2),
(345, 3),
(345, 4),
(345, 5),
(345, 7),
(345, 8),
(345, 9),
(345, 14),
(345, 15),
(345, 16),
(346, 1),
(346, 2),
(346, 4),
(346, 7),
(347, 1),
(347, 2),
(347, 4),
(347, 7),
(348, 1),
(348, 2),
(348, 4),
(348, 7),
(349, 1),
(349, 2),
(349, 4),
(349, 7),
(350, 1),
(350, 2),
(350, 4),
(350, 7),
(351, 1),
(351, 2),
(351, 3),
(351, 4),
(351, 5),
(351, 7),
(351, 8),
(351, 9),
(351, 14),
(351, 15),
(351, 16),
(352, 1),
(352, 2),
(352, 4),
(352, 6),
(352, 7),
(352, 10),
(352, 11),
(352, 12),
(352, 13),
(353, 1),
(353, 2),
(353, 4),
(353, 7),
(354, 1),
(354, 2),
(354, 4),
(354, 7),
(355, 1),
(355, 2),
(355, 4),
(355, 7),
(356, 1),
(356, 2),
(356, 4),
(356, 6),
(356, 7),
(356, 10),
(356, 11),
(356, 12),
(356, 13),
(357, 1),
(357, 2),
(357, 3),
(357, 4),
(357, 5),
(357, 7),
(357, 8),
(357, 9),
(357, 14),
(357, 15),
(357, 16),
(358, 1),
(358, 2),
(358, 4),
(358, 7),
(359, 1),
(359, 2),
(359, 3),
(359, 4),
(359, 5),
(359, 7),
(359, 8),
(359, 9),
(359, 14),
(359, 15),
(359, 16),
(360, 1),
(360, 2),
(360, 3),
(360, 4),
(360, 5),
(360, 7),
(360, 8),
(360, 9),
(360, 14),
(360, 15),
(360, 16),
(361, 1),
(361, 2),
(361, 4),
(361, 7),
(362, 1),
(362, 2),
(362, 4),
(362, 7),
(363, 1),
(363, 2),
(363, 4),
(363, 7),
(364, 1),
(364, 2),
(364, 4),
(364, 7),
(365, 1),
(365, 2),
(365, 4),
(365, 7),
(366, 1),
(366, 2),
(366, 3),
(366, 4),
(366, 5),
(366, 7),
(366, 8),
(366, 9),
(366, 14),
(366, 15),
(366, 16),
(367, 1),
(367, 2),
(367, 4),
(367, 7),
(368, 1),
(368, 2),
(368, 4),
(368, 7),
(369, 1),
(369, 2),
(369, 4),
(369, 7),
(370, 1),
(370, 2),
(370, 4),
(370, 7),
(371, 1),
(371, 2),
(371, 3),
(371, 4),
(371, 5),
(371, 7),
(371, 8),
(371, 9),
(371, 14),
(371, 15),
(371, 16),
(372, 1),
(372, 2),
(372, 3),
(372, 4),
(372, 5),
(372, 7),
(372, 8),
(372, 9),
(372, 14),
(372, 15),
(372, 16),
(373, 1),
(373, 2),
(373, 4),
(373, 7),
(374, 1),
(374, 2),
(374, 3),
(374, 4),
(374, 5),
(374, 7),
(374, 8),
(374, 9),
(374, 14),
(374, 15),
(374, 16),
(375, 1),
(375, 2),
(375, 4),
(375, 7),
(376, 1),
(376, 2),
(376, 4),
(376, 7),
(377, 1),
(377, 2),
(377, 3),
(377, 4),
(377, 5),
(377, 7),
(377, 8),
(377, 9),
(377, 14),
(377, 15),
(377, 16),
(378, 1),
(378, 2),
(378, 4),
(378, 7),
(379, 1),
(379, 2),
(379, 4),
(379, 6),
(379, 7),
(379, 10),
(379, 11),
(379, 12),
(379, 13),
(380, 1),
(380, 2),
(380, 4),
(380, 7),
(381, 1),
(381, 2),
(381, 4),
(381, 7),
(382, 1),
(382, 2),
(382, 4),
(382, 7),
(383, 1),
(383, 2),
(383, 4),
(383, 7),
(384, 1),
(384, 2),
(384, 3),
(384, 4),
(384, 5),
(384, 7),
(384, 8),
(384, 9),
(384, 14),
(384, 15),
(384, 16),
(385, 1),
(385, 2),
(385, 4),
(385, 6),
(385, 7),
(385, 10),
(385, 11),
(385, 12),
(385, 13),
(386, 1),
(386, 2),
(386, 4),
(386, 7),
(387, 1),
(387, 2),
(387, 4),
(387, 7),
(388, 1),
(388, 2),
(388, 3),
(388, 4),
(388, 5),
(388, 7),
(388, 8),
(388, 9),
(388, 14),
(388, 15),
(388, 16),
(389, 1),
(389, 2),
(389, 4),
(389, 7),
(390, 1),
(390, 2),
(390, 3),
(390, 4),
(390, 5),
(390, 7),
(390, 8),
(390, 9),
(390, 14),
(390, 15),
(390, 16),
(401, 1),
(401, 2),
(401, 3),
(402, 1),
(402, 2),
(403, 1),
(404, 1),
(405, 2);

-- --------------------------------------------------------

--
-- Table structure for table `refund`
--

CREATE TABLE `refund` (
  `id_refund` int(11) NOT NULL,
  `id_pembayaran` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `jumlah_refund` decimal(12,2) NOT NULL,
  `alasan_refund` text DEFAULT NULL,
  `status_refund` enum('diproses','selesai','ditolak') DEFAULT 'diproses',
  `tanggal_refund` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `riwayat_status`
--

CREATE TABLE `riwayat_status` (
  `id_riwayat` int(11) NOT NULL,
  `id_booking` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nama_role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tipe_kamar`
--

CREATE TABLE `tipe_kamar` (
  `id_tipe_kamar` int(11) NOT NULL,
  `id_penginapan` int(11) NOT NULL,
  `nama_tipe` varchar(100) NOT NULL,
  `harga_per_malam` decimal(12,2) NOT NULL,
  `kapasitas_orang` int(11) NOT NULL,
  `jumlah_kamar` int(11) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tipe_kamar`
--

INSERT INTO `tipe_kamar` (`id_tipe_kamar`, `id_penginapan`, `nama_tipe`, `harga_per_malam`, `kapasitas_orang`, `jumlah_kamar`, `deskripsi`) VALUES
(1, 313, 'Standard', 450000.00, 2, 5, 'Kamar standar homestay'),
(2, 318, 'Standard', 300000.00, 2, 5, 'Kamar standar homestay'),
(3, 319, 'Standard', 350000.00, 2, 5, 'Kamar standar homestay'),
(4, 320, 'Standard', 1500000.00, 2, 5, 'Kamar standar homestay'),
(5, 322, 'Standard', 250000.00, 2, 5, 'Kamar standar homestay'),
(6, 325, 'Standard', 350000.00, 2, 5, 'Kamar standar homestay'),
(7, 331, 'Standard', 100000.00, 2, 5, 'Kamar standar homestay'),
(8, 335, 'Standard', 450000.00, 2, 5, 'Kamar standar homestay'),
(9, 341, 'Standard', 400000.00, 2, 5, 'Kamar standar homestay'),
(10, 342, 'Standard', 132231.00, 2, 5, 'Kamar standar homestay'),
(11, 344, 'Standard', 200000.00, 2, 5, 'Kamar standar homestay'),
(12, 346, 'Standard', 1192500.00, 2, 5, 'Kamar standar homestay'),
(13, 347, 'Standard', 250000.00, 2, 5, 'Kamar standar homestay'),
(14, 348, 'Standard', 200000.00, 2, 5, 'Kamar standar homestay'),
(15, 349, 'Standard', 200000.00, 2, 5, 'Kamar standar homestay'),
(16, 350, 'Standard', 316375.00, 2, 5, 'Kamar standar homestay'),
(17, 353, 'Standard', 318755.00, 2, 5, 'Kamar standar homestay'),
(18, 354, 'Standard', 250000.00, 2, 5, 'Kamar standar homestay'),
(19, 355, 'Standard', 400000.00, 2, 5, 'Kamar standar homestay'),
(20, 358, 'Standard', 400000.00, 2, 5, 'Kamar standar homestay'),
(21, 361, 'Standard', 132786.00, 2, 5, 'Kamar standar homestay'),
(22, 362, 'Standard', 100000.00, 2, 5, 'Kamar standar homestay'),
(23, 363, 'Standard', 150000.00, 2, 5, 'Kamar standar homestay'),
(24, 364, 'Standard', 115837.00, 2, 5, 'Kamar standar homestay'),
(25, 365, 'Standard', 150000.00, 2, 5, 'Kamar standar homestay'),
(26, 367, 'Standard', 120000.00, 2, 5, 'Kamar standar homestay'),
(27, 368, 'Standard', 123967.00, 2, 5, 'Kamar standar homestay'),
(28, 369, 'Standard', 50000.00, 2, 5, 'Kamar standar homestay'),
(29, 370, 'Standard', 150000.00, 2, 5, 'Kamar standar homestay'),
(30, 373, 'Standard', 150000.00, 2, 5, 'Kamar standar homestay'),
(31, 375, 'Standard', 137968.00, 2, 5, 'Kamar standar homestay'),
(32, 376, 'Standard', 150000.00, 2, 5, 'Kamar standar homestay'),
(33, 378, 'Standard', 120000.00, 2, 5, 'Kamar standar homestay'),
(34, 380, 'Standard', 96340.00, 2, 5, 'Kamar standar homestay'),
(35, 381, 'Standard', 150000.00, 2, 5, 'Kamar standar homestay'),
(36, 382, 'Standard', 150000.00, 2, 5, 'Kamar standar homestay'),
(37, 383, 'Standard', 499993.00, 2, 5, 'Kamar standar homestay'),
(38, 386, 'Standard', 341303.00, 2, 5, 'Kamar standar homestay'),
(39, 387, 'Standard', 120000.00, 2, 5, 'Kamar standar homestay'),
(40, 389, 'Standard', 212427.00, 2, 5, 'Kamar standar homestay'),
(64, 313, 'Family', 600000.00, 4, 2, 'Kamar keluarga'),
(65, 318, 'Family', 450000.00, 4, 2, 'Kamar keluarga'),
(66, 319, 'Family', 500000.00, 4, 2, 'Kamar keluarga'),
(67, 320, 'Family', 1650000.00, 4, 2, 'Kamar keluarga'),
(68, 322, 'Family', 400000.00, 4, 2, 'Kamar keluarga'),
(69, 325, 'Family', 500000.00, 4, 2, 'Kamar keluarga'),
(70, 331, 'Family', 250000.00, 4, 2, 'Kamar keluarga'),
(71, 335, 'Family', 600000.00, 4, 2, 'Kamar keluarga'),
(72, 341, 'Family', 550000.00, 4, 2, 'Kamar keluarga'),
(73, 342, 'Family', 282231.00, 4, 2, 'Kamar keluarga'),
(74, 344, 'Family', 350000.00, 4, 2, 'Kamar keluarga'),
(75, 346, 'Family', 1342500.00, 4, 2, 'Kamar keluarga'),
(76, 347, 'Family', 400000.00, 4, 2, 'Kamar keluarga'),
(77, 348, 'Family', 350000.00, 4, 2, 'Kamar keluarga'),
(78, 349, 'Family', 350000.00, 4, 2, 'Kamar keluarga'),
(79, 350, 'Family', 466375.00, 4, 2, 'Kamar keluarga'),
(80, 353, 'Family', 468755.00, 4, 2, 'Kamar keluarga'),
(81, 354, 'Family', 400000.00, 4, 2, 'Kamar keluarga'),
(82, 355, 'Family', 550000.00, 4, 2, 'Kamar keluarga'),
(83, 358, 'Family', 550000.00, 4, 2, 'Kamar keluarga'),
(84, 361, 'Family', 282786.00, 4, 2, 'Kamar keluarga'),
(85, 362, 'Family', 250000.00, 4, 2, 'Kamar keluarga'),
(86, 363, 'Family', 300000.00, 4, 2, 'Kamar keluarga'),
(87, 364, 'Family', 265837.00, 4, 2, 'Kamar keluarga'),
(88, 365, 'Family', 300000.00, 4, 2, 'Kamar keluarga'),
(89, 367, 'Family', 270000.00, 4, 2, 'Kamar keluarga'),
(90, 368, 'Family', 273967.00, 4, 2, 'Kamar keluarga'),
(91, 369, 'Family', 200000.00, 4, 2, 'Kamar keluarga'),
(92, 370, 'Family', 300000.00, 4, 2, 'Kamar keluarga'),
(93, 373, 'Family', 300000.00, 4, 2, 'Kamar keluarga'),
(94, 375, 'Family', 287968.00, 4, 2, 'Kamar keluarga'),
(95, 376, 'Family', 300000.00, 4, 2, 'Kamar keluarga'),
(96, 378, 'Family', 270000.00, 4, 2, 'Kamar keluarga'),
(97, 380, 'Family', 246340.00, 4, 2, 'Kamar keluarga'),
(98, 381, 'Family', 300000.00, 4, 2, 'Kamar keluarga'),
(99, 382, 'Family', 300000.00, 4, 2, 'Kamar keluarga'),
(100, 383, 'Family', 649993.00, 4, 2, 'Kamar keluarga'),
(101, 386, 'Family', 491303.00, 4, 2, 'Kamar keluarga'),
(102, 387, 'Family', 270000.00, 4, 2, 'Kamar keluarga'),
(103, 389, 'Family', 362427.00, 4, 2, 'Kamar keluarga'),
(127, 314, 'Superior', 1474024.00, 2, 20, 'Kamar superior'),
(128, 315, 'Superior', 750000.00, 2, 20, 'Kamar superior'),
(129, 316, 'Superior', 1600000.00, 2, 20, 'Kamar superior'),
(130, 317, 'Superior', 2415852.00, 2, 20, 'Kamar superior'),
(131, 321, 'Superior', 600000.00, 2, 20, 'Kamar superior'),
(132, 323, 'Superior', 615503.00, 2, 20, 'Kamar superior'),
(133, 326, 'Superior', 550000.00, 2, 20, 'Kamar superior'),
(134, 329, 'Superior', 1200000.00, 2, 20, 'Kamar superior'),
(135, 333, 'Superior', 111171.00, 2, 20, 'Kamar superior'),
(136, 334, 'Superior', 800000.00, 2, 20, 'Kamar superior'),
(137, 336, 'Superior', 1100000.00, 2, 20, 'Kamar superior'),
(138, 339, 'Superior', 200000.00, 2, 20, 'Kamar superior'),
(139, 345, 'Superior', 340164.00, 2, 20, 'Kamar superior'),
(140, 351, 'Superior', 1282278.00, 2, 20, 'Kamar superior'),
(141, 357, 'Superior', 438160.00, 2, 20, 'Kamar superior'),
(142, 359, 'Superior', 401850.00, 2, 20, 'Kamar superior'),
(143, 360, 'Superior', 150000.00, 2, 20, 'Kamar superior'),
(144, 366, 'Superior', 501552.00, 2, 20, 'Kamar superior'),
(145, 371, 'Superior', 800000.00, 2, 20, 'Kamar superior'),
(146, 372, 'Superior', 300000.00, 2, 20, 'Kamar superior'),
(147, 374, 'Superior', 701361.00, 2, 20, 'Kamar superior'),
(148, 377, 'Superior', 600000.00, 2, 20, 'Kamar superior'),
(149, 384, 'Superior', 751314.00, 2, 20, 'Kamar superior'),
(150, 388, 'Superior', 149862.00, 2, 20, 'Kamar superior'),
(151, 390, 'Superior', 297346.00, 2, 20, 'Kamar superior'),
(158, 314, 'Deluxe', 1774024.00, 2, 15, 'Kamar deluxe'),
(159, 315, 'Deluxe', 1050000.00, 2, 15, 'Kamar deluxe'),
(160, 316, 'Deluxe', 1900000.00, 2, 15, 'Kamar deluxe'),
(161, 317, 'Deluxe', 2715852.00, 2, 15, 'Kamar deluxe'),
(162, 321, 'Deluxe', 900000.00, 2, 15, 'Kamar deluxe'),
(163, 323, 'Deluxe', 915503.00, 2, 15, 'Kamar deluxe'),
(164, 326, 'Deluxe', 850000.00, 2, 15, 'Kamar deluxe'),
(165, 329, 'Deluxe', 1500000.00, 2, 15, 'Kamar deluxe'),
(166, 333, 'Deluxe', 411171.00, 2, 15, 'Kamar deluxe'),
(167, 334, 'Deluxe', 1100000.00, 2, 15, 'Kamar deluxe'),
(168, 336, 'Deluxe', 1400000.00, 2, 15, 'Kamar deluxe'),
(169, 339, 'Deluxe', 500000.00, 2, 15, 'Kamar deluxe'),
(170, 345, 'Deluxe', 640164.00, 2, 15, 'Kamar deluxe'),
(171, 351, 'Deluxe', 1582278.00, 2, 15, 'Kamar deluxe'),
(172, 357, 'Deluxe', 738160.00, 2, 15, 'Kamar deluxe'),
(173, 359, 'Deluxe', 701850.00, 2, 15, 'Kamar deluxe'),
(174, 360, 'Deluxe', 450000.00, 2, 15, 'Kamar deluxe'),
(175, 366, 'Deluxe', 801552.00, 2, 15, 'Kamar deluxe'),
(176, 371, 'Deluxe', 1100000.00, 2, 15, 'Kamar deluxe'),
(177, 372, 'Deluxe', 600000.00, 2, 15, 'Kamar deluxe'),
(178, 374, 'Deluxe', 1001361.00, 2, 15, 'Kamar deluxe'),
(179, 377, 'Deluxe', 900000.00, 2, 15, 'Kamar deluxe'),
(180, 384, 'Deluxe', 1051314.00, 2, 15, 'Kamar deluxe'),
(181, 388, 'Deluxe', 449862.00, 2, 15, 'Kamar deluxe'),
(182, 390, 'Deluxe', 597346.00, 2, 15, 'Kamar deluxe'),
(189, 314, 'Suite', 2274024.00, 2, 5, 'Kamar suite mewah'),
(190, 315, 'Suite', 1550000.00, 2, 5, 'Kamar suite mewah'),
(191, 316, 'Suite', 2400000.00, 2, 5, 'Kamar suite mewah'),
(192, 317, 'Suite', 3215852.00, 2, 5, 'Kamar suite mewah'),
(193, 321, 'Suite', 1400000.00, 2, 5, 'Kamar suite mewah'),
(194, 323, 'Suite', 1415503.00, 2, 5, 'Kamar suite mewah'),
(195, 326, 'Suite', 1350000.00, 2, 5, 'Kamar suite mewah'),
(196, 329, 'Suite', 2000000.00, 2, 5, 'Kamar suite mewah'),
(197, 333, 'Suite', 911171.00, 2, 5, 'Kamar suite mewah'),
(198, 334, 'Suite', 1600000.00, 2, 5, 'Kamar suite mewah'),
(199, 336, 'Suite', 1900000.00, 2, 5, 'Kamar suite mewah'),
(200, 339, 'Suite', 1000000.00, 2, 5, 'Kamar suite mewah'),
(201, 345, 'Suite', 1140164.00, 2, 5, 'Kamar suite mewah'),
(202, 351, 'Suite', 2082278.00, 2, 5, 'Kamar suite mewah'),
(203, 357, 'Suite', 1238160.00, 2, 5, 'Kamar suite mewah'),
(204, 359, 'Suite', 1201850.00, 2, 5, 'Kamar suite mewah'),
(205, 360, 'Suite', 950000.00, 2, 5, 'Kamar suite mewah'),
(206, 366, 'Suite', 1301552.00, 2, 5, 'Kamar suite mewah'),
(207, 371, 'Suite', 1600000.00, 2, 5, 'Kamar suite mewah'),
(208, 372, 'Suite', 1100000.00, 2, 5, 'Kamar suite mewah'),
(209, 374, 'Suite', 1501361.00, 2, 5, 'Kamar suite mewah'),
(210, 377, 'Suite', 1400000.00, 2, 5, 'Kamar suite mewah'),
(211, 384, 'Suite', 1551314.00, 2, 5, 'Kamar suite mewah'),
(212, 388, 'Suite', 949862.00, 2, 5, 'Kamar suite mewah'),
(213, 390, 'Suite', 1097346.00, 2, 5, 'Kamar suite mewah'),
(220, 324, 'Private Villa', 1200000.00, 6, 1, 'Villa privat eksklusif'),
(221, 327, 'Private Villa', 562747.00, 6, 1, 'Villa privat eksklusif'),
(222, 328, 'Private Villa', 950000.00, 6, 1, 'Villa privat eksklusif'),
(223, 330, 'Private Villa', 1300000.00, 6, 1, 'Villa privat eksklusif'),
(224, 332, 'Private Villa', 400000.00, 6, 1, 'Villa privat eksklusif'),
(225, 337, 'Private Villa', 2000000.00, 6, 1, 'Villa privat eksklusif'),
(226, 338, 'Private Villa', 1500000.00, 6, 1, 'Villa privat eksklusif'),
(227, 340, 'Private Villa', 600000.00, 6, 1, 'Villa privat eksklusif'),
(228, 343, 'Private Villa', 850000.00, 6, 1, 'Villa privat eksklusif'),
(229, 352, 'Private Villa', 876197.00, 6, 1, 'Villa privat eksklusif'),
(230, 356, 'Private Villa', 1299992.00, 6, 1, 'Villa privat eksklusif'),
(231, 379, 'Private Villa', 900000.00, 6, 1, 'Villa privat eksklusif'),
(232, 385, 'Private Villa', 685084.00, 6, 1, 'Villa privat eksklusif');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `role_id` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `email`, `password`, `no_telepon`, `role_id`, `created_at`, `updated_at`, `status`) VALUES
(3, 'Admin Yogyastay', 'admin@yogyastay.com', 'admin123', '081122334455', 'admin', '2025-12-22 05:27:48', '2025-12-22 05:27:48', 'aktif'),
(4, 'Rania', 'rania@email.com', 'user123', '081298765432', 'user', '2025-12-22 05:27:48', '2025-12-22 05:27:48', 'aktif'),
(5, '', 'rania@gmail.com', '$2y$10$Q8/JKSVl5arzbkDe5vrCF.3pO8nRzELLAOPUmQorc6ryNCiJ5lm2K', NULL, 'user', '2025-12-22 05:58:09', '2025-12-22 05:58:09', 'aktif'),
(6, '', 'yogya@gmail.com', '$2y$10$HuAYz58qARl30o05Wk8PjOBj27Fit9zCoVNzIhifZ4dJaJmevJ4gG', NULL, 'admin', '2025-12-22 06:37:03', '2025-12-22 06:37:53', 'aktif'),
(7, 'cokicoki', 'coki@gmail.com', '$2y$10$VfNBpJ9drPozwPwuIo4kNu9BW3DL.qDdVovbQbLEQTCtBIb0SCF/a', NULL, 'user', '2025-12-25 01:55:58', '2025-12-25 02:45:20', 'aktif'),
(8, '', 'jhnnyr0623@gmail.com', '$2y$10$/E5pwctQXbLjlqc1Zqme2.CmZv/Np0cg8nUGTmuCOs4.qL3nW09US', NULL, 'user', '2025-12-25 23:44:49', '2025-12-25 23:44:49', 'aktif');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blog`
--
ALTER TABLE `blog`
  ADD PRIMARY KEY (`id_blog`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `booking`
--
ALTER TABLE `booking`
  ADD PRIMARY KEY (`id_booking`),
  ADD UNIQUE KEY `kode_booking` (`kode_booking`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_penginapan` (`id_penginapan`),
  ADD KEY `id_tipe_kamar` (`id_tipe_kamar`);

--
-- Indexes for table `checkin`
--
ALTER TABLE `checkin`
  ADD PRIMARY KEY (`id_checkin`),
  ADD KEY `id_booking` (`id_booking`);

--
-- Indexes for table `fasilitas`
--
ALTER TABLE `fasilitas`
  ADD PRIMARY KEY (`id_fasilitas`);

--
-- Indexes for table `gambar_penginapan`
--
ALTER TABLE `gambar_penginapan`
  ADD PRIMARY KEY (`id_gambar`),
  ADD KEY `id_penginapan` (`id_penginapan`);

--
-- Indexes for table `kabupaten`
--
ALTER TABLE `kabupaten`
  ADD PRIMARY KEY (`id_kabupaten`);

--
-- Indexes for table `kecamatan`
--
ALTER TABLE `kecamatan`
  ADD PRIMARY KEY (`id_kecamatan`),
  ADD KEY `id_kabupaten` (`id_kabupaten`);

--
-- Indexes for table `kontak_penginapan`
--
ALTER TABLE `kontak_penginapan`
  ADD PRIMARY KEY (`id_kontak`),
  ADD KEY `id_penginapan` (`id_penginapan`);

--
-- Indexes for table `log_aktivitas_admin`
--
ALTER TABLE `log_aktivitas_admin`
  ADD PRIMARY KEY (`id_log`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `pembatalan`
--
ALTER TABLE `pembatalan`
  ADD PRIMARY KEY (`id_pembatalan`),
  ADD KEY `id_booking` (`id_booking`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `id_booking` (`id_booking`);

--
-- Indexes for table `penginapan`
--
ALTER TABLE `penginapan`
  ADD PRIMARY KEY (`id_penginapan`),
  ADD KEY `id_kabupaten` (`id_kabupaten`),
  ADD KEY `id_kecamatan` (`id_kecamatan`),
  ADD KEY `idx_status` (`status`) USING BTREE,
  ADD KEY `idx_tipe` (`tipe_penginapan`) USING BTREE,
  ADD KEY `idx_rating` (`rating`) USING BTREE,
  ADD KEY `idx_harga` (`harga_mulai`) USING BTREE;

--
-- Indexes for table `penginapan_fasilitas`
--
ALTER TABLE `penginapan_fasilitas`
  ADD PRIMARY KEY (`id_penginapan`,`id_fasilitas`),
  ADD KEY `id_fasilitas` (`id_fasilitas`);

--
-- Indexes for table `refund`
--
ALTER TABLE `refund`
  ADD PRIMARY KEY (`id_refund`),
  ADD KEY `id_pembayaran` (`id_pembayaran`),
  ADD KEY `id_admin` (`id_admin`);

--
-- Indexes for table `riwayat_status`
--
ALTER TABLE `riwayat_status`
  ADD PRIMARY KEY (`id_riwayat`),
  ADD KEY `id_booking` (`id_booking`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tipe_kamar`
--
ALTER TABLE `tipe_kamar`
  ADD PRIMARY KEY (`id_tipe_kamar`),
  ADD KEY `id_penginapan` (`id_penginapan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blog`
--
ALTER TABLE `blog`
  MODIFY `id_blog` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `booking`
--
ALTER TABLE `booking`
  MODIFY `id_booking` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `checkin`
--
ALTER TABLE `checkin`
  MODIFY `id_checkin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fasilitas`
--
ALTER TABLE `fasilitas`
  MODIFY `id_fasilitas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `gambar_penginapan`
--
ALTER TABLE `gambar_penginapan`
  MODIFY `id_gambar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=382;

--
-- AUTO_INCREMENT for table `kabupaten`
--
ALTER TABLE `kabupaten`
  MODIFY `id_kabupaten` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `kontak_penginapan`
--
ALTER TABLE `kontak_penginapan`
  MODIFY `id_kontak` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `log_aktivitas_admin`
--
ALTER TABLE `log_aktivitas_admin`
  MODIFY `id_log` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `pembatalan`
--
ALTER TABLE `pembatalan`
  MODIFY `id_pembatalan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penginapan`
--
ALTER TABLE `penginapan`
  MODIFY `id_penginapan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=415;

--
-- AUTO_INCREMENT for table `refund`
--
ALTER TABLE `refund`
  MODIFY `id_refund` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `riwayat_status`
--
ALTER TABLE `riwayat_status`
  MODIFY `id_riwayat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tipe_kamar`
--
ALTER TABLE `tipe_kamar`
  MODIFY `id_tipe_kamar` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=281;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blog`
--
ALTER TABLE `blog`
  ADD CONSTRAINT `blog_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `booking`
--
ALTER TABLE `booking`
  ADD CONSTRAINT `booking_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `booking_ibfk_2` FOREIGN KEY (`id_penginapan`) REFERENCES `penginapan` (`id_penginapan`),
  ADD CONSTRAINT `booking_ibfk_3` FOREIGN KEY (`id_tipe_kamar`) REFERENCES `tipe_kamar` (`id_tipe_kamar`);

--
-- Constraints for table `checkin`
--
ALTER TABLE `checkin`
  ADD CONSTRAINT `checkin_ibfk_1` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`);

--
-- Constraints for table `kontak_penginapan`
--
ALTER TABLE `kontak_penginapan`
  ADD CONSTRAINT `kontak_penginapan_ibfk_1` FOREIGN KEY (`id_penginapan`) REFERENCES `penginapan` (`id_penginapan`);

--
-- Constraints for table `log_aktivitas_admin`
--
ALTER TABLE `log_aktivitas_admin`
  ADD CONSTRAINT `log_aktivitas_admin_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `pembatalan`
--
ALTER TABLE `pembatalan`
  ADD CONSTRAINT `pembatalan_ibfk_1` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`);

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`);

--
-- Constraints for table `penginapan`
--
ALTER TABLE `penginapan`
  ADD CONSTRAINT `penginapan_ibfk_1` FOREIGN KEY (`id_kabupaten`) REFERENCES `kabupaten` (`id_kabupaten`),
  ADD CONSTRAINT `penginapan_ibfk_2` FOREIGN KEY (`id_kecamatan`) REFERENCES `kecamatan` (`id_kecamatan`);

--
-- Constraints for table `penginapan_fasilitas`
--
ALTER TABLE `penginapan_fasilitas`
  ADD CONSTRAINT `penginapan_fasilitas_ibfk_1` FOREIGN KEY (`id_penginapan`) REFERENCES `penginapan` (`id_penginapan`),
  ADD CONSTRAINT `penginapan_fasilitas_ibfk_2` FOREIGN KEY (`id_fasilitas`) REFERENCES `fasilitas` (`id_fasilitas`);

--
-- Constraints for table `refund`
--
ALTER TABLE `refund`
  ADD CONSTRAINT `refund_ibfk_1` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran` (`id_pembayaran`),
  ADD CONSTRAINT `refund_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id_user`);

--
-- Constraints for table `riwayat_status`
--
ALTER TABLE `riwayat_status`
  ADD CONSTRAINT `riwayat_status_ibfk_1` FOREIGN KEY (`id_booking`) REFERENCES `booking` (`id_booking`);

--
-- Constraints for table `tipe_kamar`
--
ALTER TABLE `tipe_kamar`
  ADD CONSTRAINT `tipe_kamar_ibfk_1` FOREIGN KEY (`id_penginapan`) REFERENCES `penginapan` (`id_penginapan`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
