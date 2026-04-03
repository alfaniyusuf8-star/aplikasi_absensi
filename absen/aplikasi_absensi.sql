-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 02, 2026 at 11:11 AM
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
-- Database: `aplikasi_absensi`
--

-- --------------------------------------------------------

--
-- Table structure for table `biodata_jamaah`
--

CREATE TABLE `biodata_jamaah` (
  `id_biodata` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `jenjang` enum('Umum','Muda/i','Remaja','Pra Remaja','Caberawit') NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `nama_panggilan` varchar(50) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `status_pernikahan` enum('Belum Menikah','Menikah','Pernah Menikah') DEFAULT NULL,
  `is_siap_nikah` tinyint(1) DEFAULT 0,
  `target_menikah` varchar(50) DEFAULT NULL,
  `kriteria_idaman` text DEFAULT NULL,
  `id_kepala_keluarga` int(11) DEFAULT NULL,
  `status_keluarga` enum('Kepala Keluarga','Istri','Anak','Lainnya') DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat_asal` text DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat_surabaya` text DEFAULT NULL,
  `nama_ortu` varchar(150) DEFAULT NULL,
  `hp_ortu` varchar(20) DEFAULT NULL,
  `status_ortu` enum('Jamaah','Belum') DEFAULT NULL,
  `tempat_sambung_ortu` varchar(150) DEFAULT NULL,
  `darurat_nama` varchar(150) DEFAULT NULL,
  `darurat_hubungan` varchar(50) DEFAULT NULL,
  `darurat_hp` varchar(20) DEFAULT NULL,
  `status_mubaligh` enum('MT','Non MT') DEFAULT NULL,
  `kegiatan_surabaya` enum('Bekerja','Kuliah','Lainnya') DEFAULT NULL,
  `tempat_kerja` varchar(150) DEFAULT NULL,
  `alamat_kerja` text DEFAULT NULL,
  `universitas` varchar(150) DEFAULT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `angkatan` varchar(10) DEFAULT NULL,
  `hobi` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `biodata_jamaah`
--

INSERT INTO `biodata_jamaah` (`id_biodata`, `id_user`, `jenjang`, `nama_lengkap`, `nama_panggilan`, `jenis_kelamin`, `status_pernikahan`, `is_siap_nikah`, `target_menikah`, `kriteria_idaman`, `id_kepala_keluarga`, `status_keluarga`, `no_hp`, `alamat_asal`, `foto`, `tempat_lahir`, `tanggal_lahir`, `alamat_surabaya`, `nama_ortu`, `hp_ortu`, `status_ortu`, `tempat_sambung_ortu`, `darurat_nama`, `darurat_hubungan`, `darurat_hp`, `status_mubaligh`, `kegiatan_surabaya`, `tempat_kerja`, `alamat_kerja`, `universitas`, `jurusan`, `angkatan`, `hobi`, `created_at`) VALUES
(1, 7, 'Remaja', 'yusuf', 'alfan', 'P', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '01234', 'bjn', NULL, 'bjn', '2026-03-19', 'ashabul', 'ii', '092183832', 'Jamaah', 'ahs', 'iewo', 'owie', '', 'MT', 'Kuliah', '', '', 'wpoe', 'ska', '2020', 'jaksa', '2026-03-12 12:05:55'),
(2, 12, 'Muda/i', 'yusuf alfani', 'ask', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '767', 'dj', 'foto_1773317781_69b2ae95be01d.jpg', 'wkls', '2026-03-17', 'skl;', 'ls', '7943', 'Jamaah', 'jk', 'lkds', ',mds', '732', 'Non MT', 'Bekerja', ';l', 'l', 'lkew', 'wl', '', 'l', '2026-03-12 12:16:21'),
(3, 13, 'Muda/i', 'yusuf alfani', 'alfan', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '01234', 'aa', 'foto_1773350030_69b32c8e337c5.jpg', 'Bojonegoro', '2014-02-04', '', 'aaaaaa', '21', 'Jamaah', '', '', '', '', '', '', '', '', '', '', '', 'jaksa', '2026-03-12 21:13:50'),
(5, 16, 'Muda/i', 'asd', 'a', 'P', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '01234', 'aa', 'foto_1773387313_69b3be31159ab.jpeg', 'sds', '2021-06-24', 'ssd', 'sd', '134', 'Jamaah', 'sd', 'ds', 'dss', '12', 'MT', 'Kuliah', '', '', 'as', 'ds', '2020', 'as', '2026-03-13 07:35:13'),
(6, 18, 'Muda/i', 'aldi al', 'yaya', 'P', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '930302', 'sdlsa', 'foto_1773405790_69b4065e7fb92.jpeg', 'jzxzk', '2000-03-14', 'asad', 'lkas', '3244', 'Jamaah', 'wasasa', 'add', 'adddda', '245664', 'Non MT', 'Kuliah', 'akjslsa', 'assa', 'ass', 'ass', '2122', 'assas', '2026-03-13 12:43:10'),
(7, 19, 'Umum', 'Mamad', 'maad', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '12929932', 'akslkdalkda', 'foto_1773415130_69b42adab6514.jpeg', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2026-03-13 15:18:50'),
(8, 20, 'Pra Remaja', 'ali ala', 'ali', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '085733006215', 'ql;as', 'foto_1773420819_69b44113ef8cf.png', 'as', '2013-02-21', '', 'ax', '218921129', 'Jamaah', '', '', '', '', '', '', '', '', '', '', '', 'ksal', '2026-03-13 16:53:39'),
(12, 26, 'Umum', 'SUPER ADMIN PUSAT', NULL, '', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-14 07:31:41'),
(14, 9, 'Umum', 'Musdiq', 'mus', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '12345678', 'asdfghjkl;', 'foto_1773476461_69b51a6dcd0eb.jpeg', '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2026-03-14 08:21:01'),
(15, 8, 'Umum', 'asdfssd', 'df', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '123', 'qwedfg', NULL, '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2026-03-14 21:01:15'),
(16, 14, 'Muda/i', 'oki', 'asdf', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '134567890', 'sdfghjk', 'foto_1773548622_69b6344e20872.png', 'dfghjkl', '2009-02-04', 'asdfghj', 'SDFGHJKL;', '12345678', 'Jamaah', 'asdfgh', 'asdfgh', 'asdfghj', '123456789', 'MT', 'Kuliah', '', '', 'qwer', 'ASDFGHJ', '12345', '123456', '2026-03-15 04:23:42'),
(17, 6, 'Umum', 'ass', 'asdfg', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '15', 'eg', NULL, '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2026-03-15 05:17:50'),
(19, 4, 'Umum', 'asd', 'ASD', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '123456', 'adfghj', NULL, '', NULL, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '2026-03-15 06:08:59'),
(22, 31, 'Umum', 'Musdiq', 'Musdiq', 'L', 'Belum Menikah', 1, '', '', NULL, 'Kepala Keluarga', '1234567890', 'asdfghjkl;\'\r\n', 'foto_1774975858_69cbfb7216236.jpg', '', NULL, '', '', '', '', '', '', '', '', 'MT', '', '', '', '', '', '', '', '2026-03-15 14:24:01'),
(23, 32, 'Umum', 'latipun', 'latip', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '123456789', 'asl;\'\r\n;khfds', 'foto_1773585687_69b6c5176f3e8.jpg', '', NULL, '', '', '', '', '', '', '', '', 'MT', '', '', '', '', '', '', '', '2026-03-15 14:41:27'),
(24, 33, 'Umum', 'juhari e', 'juh', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, 'Kepala Keluarga', '1234567890', 'aghjkl', NULL, '', NULL, '', '', '', '', '', '', '', '', 'MT', '', '', '', '', '', '', '', '2026-03-15 14:44:34'),
(25, 34, 'Muda/i', 'yusuf alfani', 'alfan', 'L', 'Belum Menikah', 1, 'Tahun Ini', 'ad', NULL, NULL, '081315813556', 'Ds. Talok Kec.Kalitidu Kab.Bojonegoro', 'foto_1773589868_69b6d56c54e2f.png', 'Bojonegoro', '2004-01-04', 'Ds. Talok Kec.Kalitidu Kab.Bojonegoro', 'imron', '081315813556', 'Jamaah', 'bjn', 'aaaa', 'asdfghj', '081315813556', 'MT', 'Kuliah', '', '', 'pens', 'meka', '2023', 'baca', '2026-03-15 15:51:08'),
(26, 35, 'Caberawit', 'budi s', 'budi', 'P', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '1234567890', 'Ds. Talok Kec.Kalitidu Kab.Bojonegoro', 'foto_1773596575_69b6ef9f14c53.jpeg', 'Bojonegoro', '2020-02-18', '', 'aaaaaa', '081315813556', 'Jamaah', '', '', '', '', '', '', '', '', '', '', '', 'baca', '2026-03-15 17:42:55'),
(27, 37, 'Muda/i', 'akbar maul', 'maul', 'P', 'Belum Menikah', 1, 'Tahun Ini', 'aa', NULL, NULL, '123456788', 'qwertyu', 'foto_1773599149_69b6f9ad11a36.jpeg', 'qwserty', '2009-02-11', 'qwerty', 'hewes', '123456789', 'Jamaah', 'sda', 'asdf', 'ASDF', '12345678', 'MT', 'Kuliah', '', '', 'UI', 'Parkir', '2025', 'solat', '2026-03-15 18:25:49'),
(28, 40, 'Umum', 'sdfg', 'asdf', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '23456', 'asdfg', NULL, '', NULL, '', '', '', '', '', '', '', '', 'MT', '', '', '', '', '', '', '', '2026-03-16 05:02:54'),
(29, 42, 'Muda/i', 'yusuf alfani', 'alfan', 'L', 'Belum Menikah', 0, NULL, NULL, NULL, NULL, '081315813556', 'Ds. Talok Kec.Kalitidu Kab.Bojonegoro', 'foto_1773729198_69b8f5aec946b.jpeg', 'Bojonegoro', '2004-05-05', 'Ds. Talok Kec.Kalitidu Kab.Bojonegoro', 'imron', '081315813556', 'Jamaah', 'bjn barat', 'iewo', 'owie', '081315813556', 'MT', 'Kuliah', '', '', 'pens', 'meka', '2023', 'baca', '2026-03-17 06:33:18'),
(31, 44, 'Remaja', 'vian a', 'vian', 'L', 'Belum Menikah', 0, NULL, NULL, 33, 'Anak', '1345678', 'Ds. Talok Kec.Kalitidu Kab.Bojonegoro', 'foto_1773732778_69b903aa0e4b1.jpeg', 'Bojonegoro', '2021-06-16', '', 'Juhari e', '2345', 'Jamaah', '', '', '', '', '', '', '', '', '', '', '', 'baca', '2026-03-17 07:32:58'),
(37, 52, 'Umum', 'Siti Aminah', NULL, 'P', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-31 15:42:56'),
(38, 53, 'Remaja', 'Ahmad Fauzi', NULL, 'L', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-31 15:42:56'),
(39, 54, '', 'Nisa Nurhaliza', NULL, 'P', '', 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-31 15:42:56'),
(44, 59, 'Muda/i', 'Siti Aisyah', 'Aisyah', 'P', '', 0, NULL, NULL, NULL, NULL, '08554433221', 'Sidoarjo', NULL, 'Sidoarjo', '1995-10-20', 'Jl. Utara No 2', 'Bapak Ali', '811223344', 'Belum', 'Sidoarjo', 'Suami Rio', 'Suami', '899112233', 'Non MT', '', 'PT. Barokah', 'Jl. Juanda', '', '', '', 'Memasak', '2026-04-01 07:35:48'),
(47, 62, 'Umum', 'Muhammad ahmad', 'Yusuf', 'L', '', 0, NULL, NULL, NULL, NULL, '08123456789', 'Malang', NULL, '', '0000-00-00', '', '', '', '', '', '', '', '', 'MT', '', '', '', '', '', '', '', '2026-04-01 08:02:22'),
(48, 63, 'Umum', 'Siti ropiah', 'Aisyah', 'P', '', 0, NULL, NULL, NULL, NULL, '08554433221', 'Sidoarjo', NULL, '', '0000-00-00', '', '', '', '', '', '', '', '', 'Non MT', '', '', '', '', '', '', '', '2026-04-01 08:02:22');

-- --------------------------------------------------------

--
-- Table structure for table `data_dhuafa`
--

CREATE TABLE `data_dhuafa` (
  `id_user` int(11) NOT NULL,
  `bantuan_pusat` varchar(150) DEFAULT NULL,
  `bantuan_daerah` varchar(150) DEFAULT NULL,
  `bantuan_desa` varchar(150) DEFAULT NULL,
  `bantuan_kelompok` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `data_dhuafa`
--

INSERT INTO `data_dhuafa` (`id_user`, `bantuan_pusat`, `bantuan_daerah`, `bantuan_desa`, `bantuan_kelompok`, `updated_at`) VALUES
(31, 'Ya', 'Ya', 'Ya', 'Ya', '2026-03-31 15:15:55'),
(33, 'Ya', 'Ya', 'Ya', 'Ya', '2026-03-31 15:16:06'),
(44, 'Ya', 'Ya', 'Ya', 'Ya', '2026-03-31 15:16:06');

-- --------------------------------------------------------

--
-- Table structure for table `galeri_usaha`
--

CREATE TABLE `galeri_usaha` (
  `id_galeri` int(11) NOT NULL,
  `id_usaha` int(11) NOT NULL,
  `nama_foto` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jabatan_rangkap`
--

CREATE TABLE `jabatan_rangkap` (
  `id_jabatan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `level` varchar(50) DEFAULT NULL,
  `kelompok` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jabatan_rangkap`
--

INSERT INTO `jabatan_rangkap` (`id_jabatan`, `id_user`, `level`, `kelompok`) VALUES
(1, 18, 'admin_caberawit', 'Semampir'),
(2, 7, 'keimaman_desa', 'Semampir'),
(3, 31, 'keimaman_desa', 'Praja'),
(4, 32, 'admin', 'Semampir'),
(5, 33, 'keimaman', 'Semampir'),
(6, 36, 'admin_caberawit', 'Semampir'),
(7, 40, 'admin_desa', 'Keputih'),
(8, 42, 'tim_dhuafa_desa', 'Semampir'),
(9, 45, 'tim_pnkb', 'Semampir'),
(10, 46, 'ketua_mudai', 'Semampir'),
(11, 43, 'admin_mudai_desa', 'Semampir'),
(12, 34, 'admin_mudai', 'Keputih');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan`
--

CREATE TABLE `kegiatan` (
  `id_kegiatan` int(11) NOT NULL,
  `judul_pengajian` varchar(255) DEFAULT NULL,
  `tempat_pengajian` enum('Tempat 1','Tempat 2','Tempat 3','Gabungan') DEFAULT NULL,
  `materi` text DEFAULT NULL,
  `link_zoom` varchar(255) DEFAULT NULL,
  `lat_pusat` varchar(255) DEFAULT NULL,
  `lng_pusat` varchar(255) DEFAULT NULL,
  `target_kelompok` varchar(255) DEFAULT NULL,
  `target_jenjang` varchar(50) DEFAULT 'Semua',
  `status_buka` tinyint(1) DEFAULT 1,
  `status_izin` int(11) DEFAULT 0,
  `waktu_buka_absen` datetime DEFAULT NULL,
  `waktu_tutup_absen` datetime DEFAULT NULL,
  `waktu_buka_izin` datetime DEFAULT NULL,
  `waktu_tutup_izin` datetime DEFAULT NULL,
  `is_selesai` tinyint(1) DEFAULT 0,
  `tgl_tutup` datetime DEFAULT NULL,
  `tgl_buat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kegiatan`
--

INSERT INTO `kegiatan` (`id_kegiatan`, `judul_pengajian`, `tempat_pengajian`, `materi`, `link_zoom`, `lat_pusat`, `lng_pusat`, `target_kelompok`, `target_jenjang`, `status_buka`, `status_izin`, `waktu_buka_absen`, `waktu_tutup_absen`, `waktu_buka_izin`, `waktu_tutup_izin`, `is_selesai`, `tgl_tutup`, `tgl_buat`) VALUES
(41, 'ass', '', NULL, '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 0, 0, '2026-03-15 21:46:04', NULL, NULL, NULL, 1, NULL, '2026-03-15 14:45:58'),
(42, 'aaa', '', NULL, 'https://us06web.zoom.us/j/84143102298?pwd=YQOhUqAznSvbeGmNC3aZOb4bZd1EKr.1', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 0, 0, '2026-03-15 21:58:30', NULL, NULL, NULL, 1, NULL, '2026-03-15 14:56:25'),
(43, 'aaa', '', NULL, 'https://us06web.zoom.us/j/84143102298?pwd=YQOhUqAznSvbeGmNC3aZOb4bZd1EKr.1', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 0, 0, '2026-03-15 22:24:19', NULL, NULL, NULL, 1, NULL, '2026-03-15 15:24:12'),
(44, 'as', '', NULL, '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Caberawit', 0, 0, '2026-03-16 00:45:10', NULL, NULL, NULL, 1, NULL, '2026-03-15 17:44:47'),
(45, 'sambung', '', NULL, 'https://us06web.zoom.us/j/88353769376?pwd=zOHc6IGC6KPdVdBWQtwirNB1GDW92a.1', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 0, 0, '2026-03-16 01:27:18', NULL, NULL, NULL, 1, NULL, '2026-03-15 18:27:11'),
(46, 'aa', '', 'aaaaaaaa', 'https://us06web.zoom.us/j/84143102298?pwd=YQOhUqAznSvbeGmNC3aZOb4bZd1EKr.1', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-03-16 02:10:22', NULL, NULL, NULL, 0, NULL, '2026-03-15 19:08:58'),
(47, 'aaaaa', '', 'aaaaaaa', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-16 04:59:16'),
(48, 'aaaaa', '', 'aaaaaaa', '', '-7.308543354342562', '112.78272324131338', 'Keputih', 'Semua', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-16 04:59:16'),
(49, 'aaaaa', '', 'aaaaaaa', '', '-7.308543354342562', '112.78272324131338', 'Praja', 'Semua', 0, 0, '2026-03-16 11:59:27', NULL, NULL, NULL, 1, NULL, '2026-03-16 04:59:16'),
(50, 'ae', '', 'aa', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 0, '2026-03-16 12:08:56', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:02:03'),
(51, 'ae', '', 'aa', '', '-7.308543354342562', '112.78272324131338', 'Keputih', 'Semua', 1, 0, '2026-03-16 12:08:43', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:02:03'),
(52, 'ae', '', 'aa', '', '-7.308543354342562', '112.78272324131338', 'Praja', 'Semua', 1, 1, '2026-03-16 12:02:11', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:02:03'),
(53, 'alaa', '', 'a', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-03-16 12:09:21', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:08:23'),
(54, 'alaa', '', 'a', '', '-7.308543354342562', '112.78272324131338', 'Keputih', 'Semua', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '2026-03-16 05:08:23'),
(55, 'alaa', '', 'a', '', '-7.308543354342562', '112.78272324131338', 'Praja', 'Semua', 1, 0, '2026-03-16 12:09:44', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:08:23'),
(56, 'sal', '', '', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 0, '2026-03-16 12:15:28', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:14:50'),
(57, 'sal', '', '', '', '-7.308543354342562', '112.78272324131338', 'Keputih', 'Semua', 1, 0, '2026-03-16 12:15:28', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:14:50'),
(58, 'sal', '', '', '', '-7.308543354342562', '112.78272324131338', 'Praja', 'Semua', 1, 0, '2026-03-16 12:15:28', NULL, NULL, NULL, 0, NULL, '2026-03-16 05:14:50'),
(59, 'sambung kelompok', '', 'makna', 'https://us06web.zoom.us/j/88353769376?pwd=zOHc6IGC6KPdVdBWQtwirNB1GDW92a.1', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 0, 0, '2026-03-16 21:11:23', NULL, NULL, NULL, 1, NULL, '2026-03-16 14:10:05'),
(60, 'suajka', '', 'skja', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-03-16 22:11:14', NULL, NULL, NULL, 0, NULL, '2026-03-16 15:11:06'),
(61, 'zz', '', 'zzz', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-03-17 13:25:54', NULL, NULL, NULL, 0, NULL, '2026-03-17 06:25:46'),
(62, 'aaaa', '', 'zzzzz', '', '-7.292671', '112.777931', 'Semampir', 'Semua', 1, 1, '2026-03-17 13:35:41', NULL, NULL, NULL, 0, NULL, '2026-03-17 06:35:33'),
(63, 'aaaa', '', 'zzzzz', '', '-7.292671', '112.777931', 'Keputih', 'Semua', 1, 1, '2026-03-17 13:35:41', NULL, NULL, NULL, 0, NULL, '2026-03-17 06:35:33'),
(64, 'aaaa', '', 'zzzzz', '', '-7.292671', '112.777931', 'Praja', 'Semua', 1, 1, '2026-03-17 13:35:41', NULL, NULL, NULL, 0, NULL, '2026-03-17 06:35:33'),
(65, 'ss', '', 'a', 'https://us06web.zoom.us/j/88353769376?pwd=zOHc6IGC6KPdVdBWQtwirNB1GDW92a.1', '-7.292671', '112.777931', 'Semampir', 'Semua', 0, 0, '2026-03-17 13:39:36', NULL, NULL, NULL, 1, NULL, '2026-03-17 06:39:30'),
(66, 'ss', '', 'a', 'https://us06web.zoom.us/j/88353769376?pwd=zOHc6IGC6KPdVdBWQtwirNB1GDW92a.1', '-7.292671', '112.777931', 'Keputih', 'Semua', 0, 0, '2026-03-17 13:39:36', NULL, NULL, NULL, 1, NULL, '2026-03-17 06:39:30'),
(67, 'ss', '', 'a', 'https://us06web.zoom.us/j/88353769376?pwd=zOHc6IGC6KPdVdBWQtwirNB1GDW92a.1', '-7.292671', '112.777931', 'Praja', 'Semua', 0, 0, '2026-03-17 13:39:36', NULL, NULL, NULL, 1, NULL, '2026-03-17 06:39:30'),
(68, 'aaaaa', '', 'aaa', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-03-17 13:52:47', NULL, NULL, NULL, 0, NULL, '2026-03-17 06:52:34'),
(69, 'sambung kelompok', '', 'alquran', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-03-17 23:08:45', NULL, NULL, NULL, 0, NULL, '2026-03-17 16:08:39'),
(70, 'sambung kelompok', '', 'makna', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-03-17 23:32:17', NULL, NULL, NULL, 0, NULL, '2026-03-17 16:32:07'),
(71, 'desa', '', 'aaa', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 0, 0, '2026-03-31 22:19:32', NULL, NULL, NULL, 1, NULL, '2026-03-31 15:19:26'),
(72, 'desa', '', 'aaa', '', '-7.308543354342562', '112.78272324131338', 'Keputih', 'Semua', 0, 0, '2026-03-31 22:19:32', NULL, NULL, NULL, 1, NULL, '2026-03-31 15:19:26'),
(73, 'desa', '', 'aaa', '', '-7.308543354342562', '112.78272324131338', 'Praja', 'Semua', 0, 0, '2026-03-31 22:19:32', NULL, NULL, NULL, 1, NULL, '2026-03-31 15:19:26'),
(74, 'sambung desa', '', 'maknaa', '', '-7.308543354342562', '112.78272324131338', 'Semampir', 'Semua', 1, 1, '2026-04-01 19:00:44', NULL, NULL, NULL, 0, NULL, '2026-04-01 12:00:02'),
(75, 'sambung desa', '', 'maknaa', '', '-7.308543354342562', '112.78272324131338', 'Keputih', 'Semua', 1, 1, '2026-04-01 19:00:44', NULL, NULL, NULL, 0, NULL, '2026-04-01 12:00:02'),
(76, 'sambung desa', '', 'maknaa', '', '-7.308543354342562', '112.78272324131338', 'Praja', 'Semua', 1, 1, '2026-04-01 19:00:44', NULL, NULL, NULL, 0, NULL, '2026-04-01 12:00:02'),
(77, 'desa', '', 'al', '', '-7.308634088092277', '112.78273792488537', 'Semampir', 'Semua', 0, 0, '2026-04-02 09:43:03', NULL, NULL, NULL, 1, NULL, '2026-04-02 02:42:23'),
(78, 'desa', '', 'al', '', '-7.308634088092277', '112.78273792488537', 'Keputih', 'Semua', 0, 0, '2026-04-02 09:43:03', NULL, NULL, NULL, 1, NULL, '2026-04-02 02:42:23'),
(79, 'desa', '', 'al', '', '-7.308634088092277', '112.78273792488537', 'Praja', 'Semua', 0, 0, '2026-04-02 09:43:03', NULL, NULL, NULL, 1, NULL, '2026-04-02 02:42:23'),
(80, 'desa s', '', 'aa', '', '-7.308634088092277', '112.78273792488537', 'Semampir', 'Semua', 1, 1, '2026-04-02 10:20:45', NULL, NULL, NULL, 0, NULL, '2026-04-02 03:07:05'),
(81, 'desa s', '', 'aa', '', '-7.308634088092277', '112.78273792488537', 'Keputih', 'Semua', 1, 1, '2026-04-02 10:20:45', NULL, NULL, NULL, 0, NULL, '2026-04-02 03:07:05'),
(82, 'desa s', '', 'aa', '', '-7.308634088092277', '112.78273792488537', 'Praja', 'Semua', 1, 1, '2026-04-02 10:20:45', NULL, NULL, NULL, 0, NULL, '2026-04-02 03:07:05'),
(83, 'kak', '', 'jaj', '', '-7.308634088092277', '112.78273792488537', 'Semampir', 'Semua', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-02 03:58:18'),
(84, 'kak', '', 'jaj', '', '-7.308634088092277', '112.78273792488537', 'Keputih', 'Semua', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-02 03:58:18'),
(85, 'kak', '', 'jaj', '', '-7.308634088092277', '112.78273792488537', 'Praja', 'Semua', 0, 0, NULL, NULL, NULL, NULL, 0, NULL, '2026-04-02 03:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id_notif` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `judul` varchar(100) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi`
--

INSERT INTO `notifikasi` (`id_notif`, `id_user`, `judul`, `pesan`, `link`, `is_read`, `created_at`) VALUES
(1, 37, 'Update Status Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Dibatalkan', 'pantau_taaruf.php', 1, '2026-04-02 02:18:40'),
(2, 37, 'Update Status Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Diproses PNKB', 'pantau_taaruf.php', 1, '2026-04-02 02:19:35'),
(3, 37, 'Update Status Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Dibatalkan', 'pantau_taaruf.php', 1, '2026-04-02 02:20:44'),
(4, 37, 'Update Status Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Dibatalkan', 'pantau_taaruf.php', 1, '2026-04-02 02:20:55'),
(5, 37, 'Update Status Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Diproses PNKB', 'pantau_taaruf.php', 1, '2026-04-02 02:22:55'),
(6, 37, 'Update Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Proses SL', 'pantau_taaruf.php', 1, '2026-04-02 02:27:39'),
(7, 42, 'Kabar Gembira 💍', 'Ada proses ta\'aruf untuk Anda yang telah memasuki tahap Proses SL. Cek sekarang!', 'pantau_taaruf.php', 0, '2026-04-02 02:27:39'),
(8, 37, 'Update Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Diproses PNKB', 'pantau_taaruf.php', 1, '2026-04-02 02:27:55'),
(9, 37, 'Update Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Proses ND', 'pantau_taaruf.php', 1, '2026-04-02 02:28:03'),
(10, 42, 'Kabar Gembira 💍', 'Ada proses ta\'aruf untuk Anda yang telah memasuki tahap Proses ND. Cek sekarang!', 'pantau_taaruf.php', 0, '2026-04-02 02:28:03'),
(11, 37, 'Update Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Proses SL', 'pantau_taaruf.php', 1, '2026-04-02 02:28:45'),
(12, 42, 'Kabar Gembira 💍', 'Ada proses ta\'aruf untuk Anda yang telah memasuki tahap Proses SL. Cek sekarang!', 'pantau_taaruf.php', 0, '2026-04-02 02:28:45'),
(13, 31, 'Update Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Proses ND', 'pantau_taaruf.php', 1, '2026-04-02 02:28:54'),
(14, 35, 'Kabar Gembira 💍', 'Ada proses ta\'aruf untuk Anda yang telah memasuki tahap Proses ND. Cek sekarang!', 'pantau_taaruf.php', 0, '2026-04-02 02:28:54'),
(15, 37, 'Update Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Proses SL', 'pantau_taaruf.php', 1, '2026-04-02 02:31:33'),
(16, 42, 'Kabar Gembira 💍', 'Ada proses ta\'aruf untuk Anda yang telah memasuki tahap Proses SL. Cek sekarang!', 'pantau_taaruf.php', 0, '2026-04-02 02:31:33'),
(17, 37, 'Update Ta\'aruf 💍', 'Status pengajuan Anda telah diperbarui menjadi: Dibatalkan', 'pantau_taaruf.php', 1, '2026-04-02 02:32:02'),
(18, 38, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(19, 40, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 02:42:23'),
(20, 53, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(21, 58, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(22, 37, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 02:42:23'),
(23, 42, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(24, 34, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(25, 43, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 02:42:23'),
(26, 44, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(27, 35, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(28, 51, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(29, 33, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(30, 36, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(31, 32, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(32, 39, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(33, 62, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(34, 46, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 02:42:23'),
(35, 31, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 02:42:23'),
(36, 54, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(37, 45, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 02:42:23'),
(38, 63, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(39, 59, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(40, 52, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(41, 26, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(42, 41, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(43, 57, 'Pengajian Baru: desa 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 02:42:23'),
(44, 38, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(45, 40, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:07:05'),
(46, 53, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(47, 58, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(48, 37, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:07:05'),
(49, 42, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(50, 34, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(51, 43, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:07:05'),
(52, 44, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(53, 35, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(54, 51, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(55, 33, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(56, 36, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(57, 32, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(58, 39, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(59, 62, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(60, 46, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:07:05'),
(61, 31, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:07:05'),
(62, 54, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(63, 45, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:07:05'),
(64, 63, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(65, 59, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(66, 52, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(67, 26, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(68, 41, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(69, 57, 'Pengajian Baru: desa s 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:07:05'),
(70, 38, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(71, 40, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 1, '2026-04-02 03:20:45'),
(72, 53, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(73, 58, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(74, 37, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 1, '2026-04-02 03:20:45'),
(75, 42, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(76, 34, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(77, 43, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 1, '2026-04-02 03:20:45'),
(78, 44, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(79, 35, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(80, 51, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(81, 33, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(82, 36, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(83, 32, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(84, 39, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(85, 62, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(86, 46, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 1, '2026-04-02 03:20:45'),
(87, 31, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 1, '2026-04-02 03:20:45'),
(88, 54, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(89, 45, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 1, '2026-04-02 03:20:45'),
(90, 63, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(91, 59, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(92, 52, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(93, 26, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(94, 41, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(95, 57, 'Absen Dibuka! 🕌', 'Absensi untuk pengajian DESA S telah dibuka. Silakan siapkan QR Code Anda dan absen sekarang.', 'dashboard.php', 0, '2026-04-02 03:20:45'),
(96, 38, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(97, 40, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 1, '2026-04-02 03:20:47'),
(98, 53, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(99, 58, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(100, 37, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 1, '2026-04-02 03:20:47'),
(101, 42, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(102, 34, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(103, 43, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 1, '2026-04-02 03:20:47'),
(104, 44, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(105, 35, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(106, 51, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(107, 33, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(108, 36, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(109, 32, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(110, 39, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(111, 62, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(112, 46, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 1, '2026-04-02 03:20:47'),
(113, 31, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 1, '2026-04-02 03:20:47'),
(114, 54, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(115, 45, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 1, '2026-04-02 03:20:47'),
(116, 63, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(117, 59, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(118, 52, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(119, 26, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(120, 41, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(121, 57, 'Penerimaan Izin Dibuka 📝', 'Bagi jamaah yang berhalangan hadir pada acara DESA S, form pengajuan izin sudah bisa diakses.', 'dashboard.php', 0, '2026-04-02 03:20:47'),
(122, 38, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(123, 40, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(124, 53, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(125, 58, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(126, 37, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(127, 42, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(128, 34, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(129, 43, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:58:18'),
(130, 44, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(131, 35, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(132, 51, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(133, 33, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(134, 36, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(135, 32, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(136, 39, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(137, 62, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(138, 46, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:58:18'),
(139, 31, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 1, '2026-04-02 03:58:18'),
(140, 54, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(141, 45, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(142, 63, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(143, 59, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(144, 52, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(145, 26, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(146, 41, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18'),
(147, 57, 'Pengajian Baru: kak 🕌', 'Jadwal pengajian baru untuk jenjang Semua telah ditambahkan. Silakan cek aplikasi untuk informasi lebih lanjut.', 'dashboard.php', 0, '2026-04-02 03:58:18');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_taaruf`
--

CREATE TABLE `pengajuan_taaruf` (
  `id_pengajuan` int(11) NOT NULL,
  `id_pengaju` int(11) NOT NULL,
  `id_kandidat` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` varchar(50) DEFAULT 'Menunggu',
  `catatan_pnkb` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_taaruf`
--

INSERT INTO `pengajuan_taaruf` (`id_pengajuan`, `id_pengaju`, `id_kandidat`, `tanggal`, `status`, `catatan_pnkb`) VALUES
(1, 37, 34, '2026-03-31', 'Diproses PNKB', ''),
(2, 31, 35, '2026-03-31', '', ''),
(3, 31, 37, '2026-03-31', 'Diproses PNKB', 'qqw'),
(4, 37, 42, '2026-04-01', 'Dibatalkan', '');

-- --------------------------------------------------------

--
-- Table structure for table `perizinan`
--

CREATE TABLE `perizinan` (
  `id_izin` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_kegiatan` int(11) DEFAULT NULL,
  `jenis_izin` enum('Tidak Hadir','Online') DEFAULT 'Tidak Hadir',
  `alasan` text DEFAULT NULL,
  `status_izin` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `status_konfirmasi` enum('Belum','Menunggu','Disetujui','Ditolak') DEFAULT 'Belum',
  `catatan_admin` text DEFAULT NULL,
  `tgl_pengajuan` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `perizinan`
--

INSERT INTO `perizinan` (`id_izin`, `id_user`, `id_kegiatan`, `jenis_izin`, `alasan`, `status_izin`, `status_konfirmasi`, `catatan_admin`, `tgl_pengajuan`) VALUES
(15, 32, 41, 'Online', 'asgf', 'disetujui', 'Disetujui', NULL, '2026-03-15 21:47:12'),
(16, 32, 42, 'Online', 'as', 'disetujui', 'Disetujui', NULL, '2026-03-15 21:58:43'),
(17, 37, 56, 'Tidak Hadir', 'Diinput manual oleh Admin / Pengurus', 'disetujui', 'Disetujui', NULL, '2026-03-16 17:21:36'),
(18, 32, 59, 'Online', 'sakit', 'disetujui', 'Disetujui', NULL, '2026-03-16 21:10:47'),
(19, 32, 61, 'Online', 'ss', 'disetujui', 'Disetujui', NULL, '2026-03-17 13:26:10'),
(20, 33, 61, 'Online', 'aa', 'ditolak', 'Ditolak', NULL, '2026-03-17 13:30:22'),
(21, 33, 62, 'Online', 'a', 'disetujui', 'Disetujui', NULL, '2026-03-17 13:36:02'),
(22, 40, 63, 'Tidak Hadir', 'aa', 'disetujui', 'Disetujui', NULL, '2026-03-17 13:37:26'),
(23, 31, 67, 'Online', 'd', 'disetujui', 'Disetujui', NULL, '2026-03-17 13:39:48'),
(24, 32, 68, 'Tidak Hadir', 'aaaa', 'disetujui', 'Disetujui', 'Alhamdulillah izin sudah kami ACC.\r\n\r\nSemoga urusan Latipun diberikan kelancaran, kemudahan, dan kebarokahan oleh Allah SWT. Semoga Allah SWT mengampuni kita semua. Aamiin... Alhamdulillah jaza kumullahu khoiro.', '2026-03-17 13:52:58'),
(25, 32, 69, 'Tidak Hadir', 'sakit', 'disetujui', 'Disetujui', 'Alhamdulillah izin sudah kami ACC.\r\n\r\nSyafakallah laa ba\'sa thohurun insyaAllah. Semoga Allah SWT memberikan kesembuhan yang paripurna untuk Latipun, diangkat penyakitnya, dan bisa segera berkumpul aktif mengaji kembali bersama jamaah lainnya. Amiin. Jangan lupa istirahat yang cukup ya.', '2026-03-17 23:09:27'),
(26, 33, 69, 'Tidak Hadir', 'sibuj', 'disetujui', 'Disetujui', 'Alhamdulillah izin sudah kami ACC.\r\n\r\nSemoga urusan Juhari E diberikan kelancaran, kemudahan, dan kebarokahan oleh Allah SWT. Kami tunggu kehadirannya kembali di pengajian berikutnya ya. Alhamdulillah jaza kumullahu khoiro.', '2026-03-17 23:22:03'),
(27, 32, 70, 'Tidak Hadir', 'sakit', 'disetujui', 'Disetujui', 'Alhamdulillah izin sudah kami ACC.\r\n\r\nSemoga Allah SWT memberikan kesembuhan dan kesehatan yang barokah untuk Latipun, diangkat penyakitnya, dan bisa beribadah kembali. Amiin. Jangan lupa istirahat yang cukup ya.', '2026-03-17 23:32:30'),
(28, 57, 71, 'Tidak Hadir', 'm', 'disetujui', 'Disetujui', 'Alhamdulillah izin sudah kami ACC.\r\n\r\nSemoga urusan Muhammad Yusuf diberikan kelancaran, kemudahan, dan kebarokahan oleh Allah SWT. Semoga Allah SWT mengampuni kita semua. Aamiin... Alhamdulillah jaza kumullahu khoiro.', '2026-03-31 22:59:59'),
(29, 33, 74, 'Tidak Hadir', 'a', 'disetujui', 'Disetujui', 'Alhamdulillah izin sudah kami ACC.\r\n\r\nSemoga urusan Juhari E diberikan kelancaran, kemudahan, dan kebarokahan oleh Allah SWT. Semoga Allah SWT mengampuni kita semua. Aamiin... Alhamdulillah jaza kumullahu khoiro.', '2026-04-01 19:01:44');

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id_presensi` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_kegiatan` int(11) DEFAULT NULL,
  `tgl_presensi` date DEFAULT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `lokasi_lat` varchar(50) DEFAULT NULL,
  `lokasi_long` varchar(50) DEFAULT NULL,
  `lokasi_gps` varchar(255) DEFAULT NULL,
  `foto_selfie` varchar(255) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status_absen` enum('tepat waktu','terlambat','izin','sakit','alpa') NOT NULL DEFAULT 'alpa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `presensi`
--

INSERT INTO `presensi` (`id_presensi`, `id_user`, `id_kegiatan`, `tgl_presensi`, `tanggal`, `jam_masuk`, `jam_keluar`, `lokasi_lat`, `lokasi_long`, `lokasi_gps`, `foto_selfie`, `keterangan`, `status_absen`) VALUES
(34, 32, 40, '2026-03-15', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(35, 33, 40, '2026-03-15', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(37, 32, 42, '2026-03-15', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, ''),
(38, 32, 43, '2026-03-15', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(39, 35, 44, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(40, 37, 45, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(41, 33, 46, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(42, 31, 49, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(43, 33, 53, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(44, 33, 56, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(45, 31, 58, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(46, 40, 57, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'terlambat'),
(47, 32, 59, '2026-03-16', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, ''),
(49, 37, 69, '2026-03-17', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'tepat waktu'),
(50, 44, 69, '2026-03-17', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'terlambat'),
(51, 42, 70, '2026-03-27', '0000-00-00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'terlambat');

-- --------------------------------------------------------

--
-- Table structure for table `usaha_jamaah`
--

CREATE TABLE `usaha_jamaah` (
  `id_usaha` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `nama_usaha` varchar(100) NOT NULL,
  `deskripsi` text NOT NULL,
  `no_wa` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `level` varchar(50) DEFAULT 'karyawan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kelompok` enum('Semampir','Keputih','Praja','Semua') DEFAULT 'Semua',
  `tgl_buat_akun` datetime NOT NULL DEFAULT current_timestamp(),
  `tgl_daftar` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `password`, `nama_lengkap`, `jabatan`, `level`, `created_at`, `kelompok`, `tgl_buat_akun`, `tgl_daftar`) VALUES
(26, 'superadmin', 'superadmin123', '', NULL, 'superadmin', '2026-03-14 07:31:40', 'Semua', '2026-03-14 14:31:40', '2026-03-14 14:31:40'),
(31, 'musdiq', '$2y$10$g9NRmHan0HEOJDPanRxwzOElC1inGBcoJw3oG2v8vuFVSm5fRf5DG', '', NULL, 'karyawan', '2026-03-15 14:22:24', 'Praja', '2026-03-15 21:22:24', '2026-03-15 21:22:24'),
(32, 'latip', '$2y$10$bv8kbFvvgHj6LLeGinARZe7Cp2m9LvuEBiTuzDzpXtEtjwYPDkCz.', '', NULL, 'karyawan', '2026-03-15 14:40:35', 'Semampir', '2026-03-15 21:40:35', '2026-03-15 21:40:35'),
(33, 'juhari', '$2y$10$tZJMcqGLuENFkNl0l0cPQOC4hLb4hZSoUVx8sgsqyXBSPYg.6nAE.', '', NULL, 'karyawan', '2026-03-15 14:44:06', 'Semampir', '2026-03-15 21:44:06', '2026-03-15 21:44:06'),
(34, 'alpan', '$2y$10$RS0/crVp2u0h5xbiNIMig.MQEG2KlpednFrNNIPkai4iIyQBB5OU.', '', NULL, 'karyawan', '2026-03-15 15:49:41', 'Keputih', '2026-03-15 22:49:41', '2026-03-15 22:49:41'),
(35, 'budi', '$2y$10$4TFB5DDIxoqLBGKqE94vrONMThqfTAP.EdlUif5fwdHjy8aA3JSTG', '', NULL, 'karyawan', '2026-03-15 17:42:02', 'Semampir', '2026-03-16 00:42:02', '2026-03-16 00:42:02'),
(36, 'lala', '$2y$10$BX3ZjfgEcauaxwlij3MlG..aydwIZqxBu8mevcHQdFBT7JltTxrMm', '', NULL, 'karyawan', '2026-03-15 17:43:26', 'Semampir', '2026-03-16 00:43:26', '2026-03-16 00:43:26'),
(37, 'akbar', '$2y$10$a5FUTb4HgnrNDkZlihT0beNqq8NQN9Rkkfgn4C1qqfixJ1U40/8Um', '', NULL, 'karyawan', '2026-03-15 18:23:23', 'Semampir', '2026-03-16 01:23:23', '2026-03-16 01:23:23'),
(38, 'aaa', '$2y$10$RIfyt9hbfB9S2u53Za4MdOfjhlV8w8NZeSjhOS/eTvMCfMsbpl4lq', '', NULL, 'karyawan', '2026-03-15 18:33:22', 'Keputih', '2026-03-16 01:33:22', '2026-03-16 01:33:22'),
(39, 'lll', '$2y$10$z2/fGPM0O8vgVM6SFfW3qeh0VswyrraCCsRyYm77m0VZhPmxLlEyO', '', NULL, 'karyawan', '2026-03-15 18:34:01', 'Semampir', '2026-03-16 01:34:01', '2026-03-16 01:34:01'),
(40, 'admin_desa', '$2y$10$CwJOE99pDjQzWQZ90t6SXuF2VmQovWIUD2Bh/4bk1Eqc6msvFk4Z.', '', NULL, 'karyawan', '2026-03-16 04:57:25', 'Keputih', '2026-03-16 11:57:25', '2026-03-16 11:57:25'),
(41, 'yusuf', '$2y$10$74DMwP2/ODbcxlAqNSYI7egsjzSaTLW61nk8gaH25Y.JDXG55RO9K', '', NULL, 'karyawan', '2026-03-16 13:47:43', 'Semampir', '2026-03-16 20:47:43', '2026-03-16 20:47:43'),
(42, 'alfani', '$2y$10$s8cp8vP6McHlZ1cCH9dDMOAPuf1rC9unJ8tQkgscsB2hf9vdIFlIa', '', NULL, 'karyawan', '2026-03-16 14:55:10', 'Semampir', '2026-03-16 21:55:10', '2026-03-16 21:55:10'),
(43, 'amir', '$2y$10$shJ.fzEktgQIcNIrYkg/VOqWETIo210GPhmsNB4uM6O/zZSzpB9uC', '', NULL, 'karyawan', '2026-03-17 07:15:10', 'Semampir', '2026-03-17 14:15:10', '2026-03-17 14:15:10'),
(44, 'anak', '$2y$10$MWq0JlE1JZpJWhvug/wZPuwsmDpwcgdezGI.x6Wz9g1Shne9b9X2S', '', NULL, 'karyawan', '2026-03-17 07:26:56', 'Semampir', '2026-03-17 14:26:56', '2026-03-17 14:26:56'),
(45, 'pnkb', '$2y$10$8SroO0YWhxYARi/mIABZDOZm5bE6GG3yDa3tL34.69iQ/txzLSyB.', '', NULL, 'karyawan', '2026-03-30 06:46:13', 'Semampir', '2026-03-30 13:46:13', '2026-03-30 13:46:13'),
(46, 'mm', '$2y$10$t.Q5j7BToOXyz/UTLWzZrOJNEbcqRN6AnY41wGg3sjyd7YH.9Gy/S', '', NULL, 'karyawan', '2026-03-30 07:06:53', 'Semampir', '2026-03-30 14:06:53', '2026-03-30 14:06:53'),
(51, 'budi123', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-03-31 15:42:56', 'Semampir', '2026-03-31 22:42:56', '2026-03-31 22:42:56'),
(52, 'siti456', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-03-31 15:42:56', 'Keputih', '2026-03-31 22:42:56', '2026-03-31 22:42:56'),
(53, 'ahmad789', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-03-31 15:42:56', 'Praja', '2026-03-31 22:42:56', '2026-03-31 22:42:56'),
(54, 'nisa01', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-03-31 15:42:56', 'Keputih', '2026-03-31 22:42:56', '2026-03-31 22:42:56'),
(57, 'yusuf123', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-03-31 15:53:47', 'Semampir', '2026-03-31 22:53:47', '2026-03-31 22:53:47'),
(58, 'aisyah456', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-03-31 15:53:47', 'Keputih', '2026-03-31 22:53:47', '2026-03-31 22:53:47'),
(59, 'siti', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-04-01 07:35:48', 'Keputih', '2026-04-01 14:35:48', '2026-04-01 14:35:48'),
(62, 'mhmd', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-04-01 08:02:22', 'Semampir', '2026-04-01 15:02:22', '2026-04-01 15:02:22'),
(63, 'ropi', 'e10adc3949ba59abbe56e057f20f883e', '', NULL, 'karyawan', '2026-04-01 08:02:22', 'Keputih', '2026-04-01 15:02:22', '2026-04-01 15:02:22');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `biodata_jamaah`
--
ALTER TABLE `biodata_jamaah`
  ADD PRIMARY KEY (`id_biodata`);

--
-- Indexes for table `data_dhuafa`
--
ALTER TABLE `data_dhuafa`
  ADD PRIMARY KEY (`id_user`);

--
-- Indexes for table `galeri_usaha`
--
ALTER TABLE `galeri_usaha`
  ADD PRIMARY KEY (`id_galeri`);

--
-- Indexes for table `jabatan_rangkap`
--
ALTER TABLE `jabatan_rangkap`
  ADD PRIMARY KEY (`id_jabatan`);

--
-- Indexes for table `kegiatan`
--
ALTER TABLE `kegiatan`
  ADD PRIMARY KEY (`id_kegiatan`);

--
-- Indexes for table `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id_notif`);

--
-- Indexes for table `pengajuan_taaruf`
--
ALTER TABLE `pengajuan_taaruf`
  ADD PRIMARY KEY (`id_pengajuan`);

--
-- Indexes for table `perizinan`
--
ALTER TABLE `perizinan`
  ADD PRIMARY KEY (`id_izin`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_kegiatan` (`id_kegiatan`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id_presensi`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `usaha_jamaah`
--
ALTER TABLE `usaha_jamaah`
  ADD PRIMARY KEY (`id_usaha`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `biodata_jamaah`
--
ALTER TABLE `biodata_jamaah`
  MODIFY `id_biodata` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `galeri_usaha`
--
ALTER TABLE `galeri_usaha`
  MODIFY `id_galeri` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `jabatan_rangkap`
--
ALTER TABLE `jabatan_rangkap`
  MODIFY `id_jabatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `kegiatan`
--
ALTER TABLE `kegiatan`
  MODIFY `id_kegiatan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id_notif` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `pengajuan_taaruf`
--
ALTER TABLE `pengajuan_taaruf`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `perizinan`
--
ALTER TABLE `perizinan`
  MODIFY `id_izin` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id_presensi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `usaha_jamaah`
--
ALTER TABLE `usaha_jamaah`
  MODIFY `id_usaha` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `perizinan`
--
ALTER TABLE `perizinan`
  ADD CONSTRAINT `perizinan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`),
  ADD CONSTRAINT `perizinan_ibfk_2` FOREIGN KEY (`id_kegiatan`) REFERENCES `kegiatan` (`id_kegiatan`);

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
