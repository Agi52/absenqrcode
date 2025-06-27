-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2025 at 10:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `absensi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `status` enum('hadir','tidak_hadir','terlambat') DEFAULT 'hadir',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `jam_kerja` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absensi`
--

INSERT INTO `absensi` (`id`, `user_id`, `tanggal`, `jam_masuk`, `jam_keluar`, `status`, `created_at`, `updated_at`, `jam_kerja`) VALUES
(0, 6, '2025-06-25', '18:42:24', '18:43:23', 'terlambat', '2025-06-25 11:42:24', '2025-06-25 11:43:23', 0.00),
(2, 2, '2025-06-25', '13:17:53', NULL, 'terlambat', '2025-06-25 06:17:53', '2025-06-25 06:17:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jabatan`
--

CREATE TABLE `jabatan` (
  `id` int(11) NOT NULL,
  `nama_jabatan` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jabatan`
--

INSERT INTO `jabatan` (`id`, `nama_jabatan`, `created_at`, `deskripsi`) VALUES
(1, 'Guru Matematika', '2025-06-11 03:08:59', NULL),
(2, 'Guru Bahasa Indonesia', '2025-06-11 03:08:59', NULL),
(3, 'Guru IPA', '2025-06-11 03:08:59', NULL),
(4, 'Kepala Sekolah', '2025-06-11 03:08:59', NULL),
(6, 'Staff TU', '2025-06-11 03:08:59', NULL),
(7, 'Guru Seni Musik', '2025-06-24 01:45:18', NULL),
(8, 'Guru Informatika', '2025-06-24 01:45:31', NULL),
(9, 'Guru Ekonomi', '2025-06-24 01:45:40', NULL),
(10, 'Guru Bahasa Inggris', '2025-06-24 01:46:13', NULL),
(11, 'Guru Bahasa Jawa', '2025-06-24 01:46:22', NULL),
(12, 'Guru, Biologi, Fisika, Kimia', '2025-06-24 01:46:57', NULL),
(13, 'Guru Sejarah', '2025-06-24 02:05:22', NULL),
(14, 'Guru PAI & BP', '2025-06-24 02:35:12', NULL),
(15, 'Guru PJOK', '2025-06-24 02:36:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `jadwal_mengajar`
--

CREATE TABLE `jadwal_mengajar` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mata_pelajaran_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `hari` tinyint(4) NOT NULL COMMENT '0=Minggu, 1=Senin, 2=Selasa, 3=Rabu, 4=Kamis, 5=Jumat, 6=Sabtu',
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `ruangan` varchar(20) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal_mengajar`
--

INSERT INTO `jadwal_mengajar` (`id`, `user_id`, `mata_pelajaran_id`, `kelas_id`, `hari`, `jam_mulai`, `jam_selesai`, `ruangan`, `keterangan`, `status`, `created_at`, `updated_at`) VALUES
(31, 5, 11, 10, 1, '07:40:00', '08:20:00', 'Kelas X', 'Jam ke-1: Bahasa Inggris', 'aktif', '2025-06-26 02:38:50', '2025-06-26 02:38:50'),
(32, 5, 11, 10, 1, '08:20:00', '09:00:00', 'Kelas X', 'Jam ke-2: Bahasa Inggris', 'aktif', '2025-06-26 02:38:50', '2025-06-26 02:38:50'),
(33, 5, 11, 10, 1, '09:00:00', '09:45:00', 'Kelas X', 'Jam ke-3: Bahasa Inggris', 'aktif', '2025-06-26 02:38:50', '2025-06-26 02:38:50'),
(34, 7, 24, 10, 1, '10:00:00', '10:40:00', 'Kelas X', 'Jam ke-5: Fisika', 'aktif', '2025-06-26 02:38:50', '2025-06-26 02:38:50'),
(35, 7, 24, 10, 1, '10:40:00', '11:20:00', 'Kelas X', 'Jam ke-6: Fisika', 'aktif', '2025-06-26 02:38:50', '2025-06-26 02:38:50'),
(36, 9, 19, 10, 1, '11:20:00', '12:00:00', 'Kelas X', 'Jam ke-6: Sejarah', 'aktif', '2025-06-26 02:38:50', '2025-06-26 02:38:50'),
(37, 9, 19, 10, 1, '12:30:00', '13:10:00', 'Kelas X', 'Jam ke-7: Sejarah', 'aktif', '2025-06-26 02:38:50', '2025-06-26 02:38:50'),
(38, 7, 23, 11, 1, '07:40:00', '08:20:00', 'Kelas XI', '', 'aktif', '2025-06-26 03:11:05', '2025-06-26 03:11:05'),
(39, 6, 21, 11, 1, '12:30:00', '13:50:00', 'Lab Kom', '', 'aktif', '2025-06-26 03:39:16', '2025-06-26 03:39:16');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(20) NOT NULL,
  `tingkat` varchar(10) DEFAULT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT 0,
  `wali_kelas_id` int(11) DEFAULT NULL,
  `tahun_ajaran` varchar(20) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `tingkat`, `jurusan`, `kapasitas`, `wali_kelas_id`, `tahun_ajaran`, `status`, `created_at`, `updated_at`) VALUES
(10, 'X Fase E', '10', 'Umum', 32, NULL, '2024/2025', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:09:42'),
(11, 'XI Fase F', '11', 'Umum', 30, NULL, '2024/2025', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:02:10'),
(12, 'XII fase F', '12', 'Umum', 28, NULL, '2024/2025', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `mata_pelajaran`
--

CREATE TABLE `mata_pelajaran` (
  `id` int(11) NOT NULL,
  `kode_pelajaran` varchar(10) NOT NULL,
  `nama_pelajaran` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mata_pelajaran`
--

INSERT INTO `mata_pelajaran` (`id`, `kode_pelajaran`, `nama_pelajaran`, `deskripsi`, `kategori`, `status`, `created_at`, `updated_at`) VALUES
(9, 'EKO001', 'Ekonomi', 'Pengenalan konsep dasar ekonomi dan bisnis', 'Sosial', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(10, 'PKY001', 'Prakarya', 'Pembelajaran keterampilan praktis dan kreativitas', 'Keterampilan', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(11, 'ENG001', 'Bahasa Inggris', 'Pembelajaran bahasa Inggris dasar meliputi grammar, vocabulary, dan conversation', 'Bahasa', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(12, 'BHS001', 'Bahasa Indonesia', 'Pembelajaran bahasa Indonesia meliputi tata bahasa, sastra, dan keterampilan menulis', 'Bahasa', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(13, 'SOS001', 'Sosiologi', 'Pembelajaran tentang masyarakat dan interaksi sosial', 'Sosial', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(14, 'GEO001', 'Geografi', 'Pembelajaran geografi meliputi geografi fisik dan geografi manusia', 'Sosial', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(15, 'MTK001', 'Matematika', 'Pembelajaran matematika untuk kelas X, XI, XII', 'Eksak', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(16, 'ETL001', 'B Inggris TL', 'Bahasa Inggris Tingkat Lanjut untuk program khusus', 'Bahasa', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(17, 'AGM001', 'PAI BP', 'Pendidikan Agama Islam dan Budi Pekerti', 'Agama', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(18, 'MLK001', 'Mulok Keislaman', 'Muatan Lokal Keislaman dan nilai-nilai Islam', 'Agama', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(19, 'SEJ001', 'Sejarah', 'Pembelajaran sejarah Indonesia dari masa prasejarah hingga modern', 'Sosial', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(20, 'PKN001', 'Pendidikan Pancasila', 'Pembelajaran tentang nilai-nilai Pancasila dan kewarganegaraan', 'Sosial', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(21, 'TIK001', 'Informatika', 'Pengenalan dasar komputer dan teknologi informasi', 'Teknologi', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(22, 'SNM001', 'Seni Musik', 'Pembelajaran seni musik meliputi teori dan praktik bermusik', 'Seni', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(23, 'BIO001', 'Biologi', 'Pembelajaran biologi dasar meliputi sel, genetika, dan ekologi', 'Eksak', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(24, 'IPA001', 'Fisika', 'Pengenalan konsep-konsep dasar fisika meliputi mekanika, termodinamika, dan gelombang', 'Eksak', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(25, 'KIM001', 'Kimia', 'Pengenalan konsep dasar kimia meliputi struktur atom, ikatan kimia, dan reaksi kimia', 'Eksak', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(26, 'BJW001', 'Bahasa Jawa', 'Pembelajaran bahasa dan budaya Jawa', 'Bahasa', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32'),
(27, 'PJK001', 'PJOK', 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'Olahraga', 'aktif', '2025-06-26 02:14:32', '2025-06-26 02:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_cuti`
--

CREATE TABLE `pengajuan_cuti` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jenis_cuti` varchar(50) NOT NULL,
  `alasan` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `keterangan_admin` text DEFAULT NULL,
  `disetujui_oleh` int(11) DEFAULT NULL,
  `tanggal_ajuan` datetime DEFAULT current_timestamp(),
  `tanggal_disetujui` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengajuan_cuti`
--

INSERT INTO `pengajuan_cuti` (`id`, `user_id`, `tanggal_mulai`, `tanggal_selesai`, `jenis_cuti`, `alasan`, `status`, `keterangan_admin`, `disetujui_oleh`, `tanggal_ajuan`, `tanggal_disetujui`, `created_at`, `updated_at`) VALUES
(21, 14, '2025-06-29', '2025-06-30', 'Cuti Khusus', 'Acara Keluarga', 'rejected', 'Kurang jelas', 1, '2025-06-24 10:24:22', '2025-06-24 10:27:20', '2025-06-24 03:24:22', '2025-06-25 18:07:30'),
(22, 5, '2025-06-27', '2025-06-28', 'Cuti Khusus', 'Acara wisuda anak di Jogja', 'pending', NULL, NULL, '2025-06-24 10:25:17', NULL, '2025-06-24 03:25:17', '2025-06-25 18:07:36'),
(23, 10, '2025-06-28', '2025-06-29', 'Cuti Sakit', 'Haid hari pertama', 'approved', 'Pengajuan cuti disetujui', 1, '2025-06-24 10:26:05', '2025-06-24 10:27:02', '2025-06-24 03:26:05', '2025-06-25 18:07:41');

-- --------------------------------------------------------

--
-- Table structure for table `ruangan`
--

CREATE TABLE `ruangan` (
  `id` int(11) NOT NULL,
  `kode_ruangan` varchar(10) NOT NULL,
  `nama_ruangan` varchar(50) NOT NULL,
  `kapasitas` int(11) DEFAULT 0,
  `fasilitas` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ruangan`
--

INSERT INTO `ruangan` (`id`, `kode_ruangan`, `nama_ruangan`, `kapasitas`, `fasilitas`, `status`, `created_at`, `updated_at`) VALUES
(15, 'R1X', 'Ruang X', 35, 'Proyektor, AC, Papan Tulis', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:24:06'),
(16, 'R2XI', 'Ruang XI', 35, 'Proyektor, AC, Papan Tulis', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:24:24'),
(17, 'R3XII', 'Ruang XII', 35, 'Proyektor, AC, Papan Tulis', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:24:37'),
(18, 'LAB1', 'Lab IPA', 30, 'Alat Lab, Proyektor, AC', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:06:01'),
(19, 'LAB2', 'Lab Komputer', 40, 'Komputer, Proyektor, AC', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:06:07'),
(20, 'AULA', 'Aula', 200, 'Sound System, Proyektor, AC', 'aktif', '2025-06-25 17:42:44', '2025-06-25 18:06:13');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jabatan_id` int(11) DEFAULT NULL,
  `qr_code` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `nip` varchar(20) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `jabatan_id`, `qr_code`, `role`, `created_at`, `nip`, `deskripsi`, `status`, `updated_at`) VALUES
(1, 'Admin', 'admin@sekolah.com', '$2a$12$2dIi32fkHinTog1m0r.BKuJaS4yedUy6ngJGI4Tz4/naKINvyUgGy', 1, 'ADMIN001', 'admin', '2025-06-11 03:08:59', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(3, 'Dwi Nurul Hidayati, S.Pd.', 'dwinurulh19@gmail.com', '$2y$10$gK.8OC/tNKpnoGZl5SN4TOPuAKTJWCKnbPBeuGuTwUH9PxAApBFa2', 1, 'USER45610', 'user', '2025-06-24 01:52:19', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(4, 'Dwi Ayu Susilowati, S.Pd', 'susilowatidwiayu@gmail.com', '$2y$10$kCUZ6j91x5YkF2FqE6Jb1uxKytaUFlPcfHEQ1J0aSax0F3uUhSHw2', 2, 'USER59022', 'user', '2025-06-24 01:54:01', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(5, 'Santi Haryati, S.Pd', 'santiharyati85@gmail.com', '$2y$10$IhO5rmZrG5jPSy4YJgFgAuREJ.t2clY6nKr3OiY1779XmiaIFj.de', 10, 'USER44375', 'user', '2025-06-24 01:54:59', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(6, 'Subiono Ahmad, S.T', 'barkenciel@gmail.com', '$2y$10$Vljd/2XtPmxMPoV1.JPzyuj6sZZ12DoUg1m4E.CHa6Jvj/9MeLAj2', 8, 'USER03893', 'user', '2025-06-24 01:56:01', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(7, 'Fifit Fitriani, S.Pd', 'fifitfitriani793@gmail.com', '$2y$10$wQ1NNkiw/Njcwm/34KOWtuN1bzq1VZd7iRs3BJxE36kJrPbDtOYSG', 12, 'USER02663', 'user', '2025-06-24 02:02:34', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(8, 'Winarti, S.Pd', 'buwiwin023@gmail.com', '$2y$10$Ccmka2bj.KT1WL0MrRP5sOfQy7Euob9hvj3bHLmQas/oz8fG5W59C', 11, 'USER42682', 'user', '2025-06-24 02:03:49', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(9, 'Mila Triyana', 'milatriyana123@gmail.com', '$2y$10$Y..uud8KDyHPrO27VsVxa.8JFOPIbqn84YKQWDHg6D2du5BcRVojy', 13, 'USER03436', 'user', '2025-06-24 02:06:09', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(10, 'Yulia Rahmawati', 'yulirahmw@gmail.com', '$2y$10$WxAF4DevGUmdZow2i8WpYOoFGEo87.62AmgOdEznAFnpmBq3H2hPC', 6, 'USER64046', 'user', '2025-06-24 02:07:36', NULL, NULL, 'aktif', '2025-06-26 01:39:07'),
(11, 'Diva Arthamefia Paradisa', 'divaarthamefiap@gmail.com', '$2y$10$oOhPkzh8ssrA3VVDI5Brf.94HCRaBy/FjTtWSiFNYjw8VSrqHcJMK', 6, 'USER26561', 'user', '2025-06-24 02:08:44', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(12, 'Endah Agustiningsih, S.H.I', 'endahagustiningsih80@gmail.com', '$2y$10$8FF3LBLL6rawZxPP08R6ju27zAq5YJq.ZyyMsSZhlaDubfXmcfLIK', 14, 'USER48228', 'user', '2025-06-24 02:36:32', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(13, 'Aris Budi Prayitno', 'arisbudiprayitno31@gmail.com', '$2y$10$k82ECmDfMPT9DN6UdqC.K.hFWhDqoUcHfVKVLeh8ya7pska/W9w6K', 15, 'USER47504', 'user', '2025-06-24 02:37:42', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(14, 'Sri Astuti Purwaningrum, S.Pd', 'sriastutipurwaningrum@gmail.com', '$2y$10$u5g0c0MsRTgKQlW0Fg71heXvLJ/s.4Ue.aeDqIxLxX93bQ2r9qhSO', 9, 'USER07244', 'user', '2025-06-24 02:39:27', NULL, NULL, 'aktif', '2025-06-25 16:26:33'),
(15, 'Sifi Fadilatul Annisa', 'fadilatulannisa.sifi@gmail.com', '$2y$10$UxexbRJcKEaafp2Zeqqk5.NIdGZJV2.XmSHCjzZXK/egsusRVpJ3a', 6, 'USER51110', 'user', '2025-06-26 01:38:48', NULL, NULL, 'aktif', '2025-06-26 01:38:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jabatan`
--
ALTER TABLE `jabatan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jadwal_mengajar`
--
ALTER TABLE `jadwal_mengajar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_jadwal` (`user_id`,`hari`,`jam_mulai`,`jam_selesai`),
  ADD KEY `idx_hari_jam` (`hari`,`jam_mulai`),
  ADD KEY `idx_guru` (`user_id`),
  ADD KEY `idx_kelas` (`kelas_id`),
  ADD KEY `idx_mapel` (`mata_pelajaran_id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `wali_kelas_id` (`wali_kelas_id`);

--
-- Indexes for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_pelajaran` (`kode_pelajaran`);

--
-- Indexes for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disetujui_oleh` (`disetujui_oleh`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_tanggal_ajuan` (`tanggal_ajuan`);

--
-- Indexes for table `ruangan`
--
ALTER TABLE `ruangan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_ruangan` (`kode_ruangan`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `jabatan_id` (`jabatan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `jabatan`
--
ALTER TABLE `jabatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `jadwal_mengajar`
--
ALTER TABLE `jadwal_mengajar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `mata_pelajaran`
--
ALTER TABLE `mata_pelajaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `ruangan`
--
ALTER TABLE `ruangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `jadwal_mengajar`
--
ALTER TABLE `jadwal_mengajar`
  ADD CONSTRAINT `jadwal_mengajar_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_mengajar_ibfk_2` FOREIGN KEY (`mata_pelajaran_id`) REFERENCES `mata_pelajaran` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jadwal_mengajar_ibfk_3` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`wali_kelas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  ADD CONSTRAINT `pengajuan_cuti_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengajuan_cuti_ibfk_2` FOREIGN KEY (`disetujui_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`jabatan_id`) REFERENCES `jabatan` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
