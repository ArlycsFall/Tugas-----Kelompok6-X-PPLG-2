-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2025 at 06:51 PM
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
-- Database: `mandis`
--

-- --------------------------------------------------------

--
-- Table structure for table `akun`
--

CREATE TABLE `akun` (
  `role` enum('siswa','guru','admin') NOT NULL,
  `password` varchar(40) NOT NULL,
  `username` varchar(40) NOT NULL,
  `email` varchar(40) NOT NULL,
  `id_akun` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `akun`
--

INSERT INTO `akun` (`role`, `password`, `username`, `email`, `id_akun`) VALUES
('siswa', 'pler', 'FLARE', 'flare@gmail.com', 11),
('siswa', '123', 'qwe', 'qwer@gmail.com', 14),
('siswa', '16', 'aku16', '16@gmail.com', 17),
('guru', '$2y$10$xFfym04x0Z/1HytL7UnRlerLeytaA4Smr', 'sensei', 'WDu@gmail.com', 18),
('admin', '123', 'akuraja', 'admin@gmail.cpm', 23),
('guru', '123', 'gru', 'guru@guru', 25),
('guru', '123', 'popo', 'amba@amba', 26);

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id_dokumen` int(11) NOT NULL,
  `tanggal_upload` date NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `jenis_dokumen` varchar(40) NOT NULL,
  `id_siswa` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id_dokumen`, `tanggal_upload`, `file_path`, `jenis_dokumen`, `id_siswa`) VALUES
(8, '2025-06-07', 'uploads/dokumen_1749462633_2471.png', 'Foto', 4),
(11, '0000-00-00', 'uploads/dokumen/1749308570_logos.png', 'blakutakblakutik', 6),
(12, '0000-00-00', 'uploads/dokumen/1', 'jawa', 6);

-- --------------------------------------------------------

--
-- Table structure for table `guru`
--

CREATE TABLE `guru` (
  `nama` varchar(40) NOT NULL,
  `jabatan` varchar(30) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `id_akun` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `guru`
--

INSERT INTO `guru` (`nama`, `jabatan`, `id_guru`, `id_akun`) VALUES
('Urawa Hanako', 'blue.archieve@gmail.com', 1, 18),
('AKUGURU', 'PNS', 2, 25),
('', '', 3, 26);

-- --------------------------------------------------------

--
-- Table structure for table `orangtua`
--

CREATE TABLE `orangtua` (
  `nama_ayah` varchar(40) NOT NULL,
  `nama_ibu` varchar(40) NOT NULL,
  `pekerjaan_ayah` varchar(40) NOT NULL,
  `pekerjaan_ibu` varchar(40) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_orangtua` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orangtua`
--

INSERT INTO `orangtua` (`nama_ayah`, `nama_ibu`, `pekerjaan_ayah`, `pekerjaan_ibu`, `id_siswa`, `id_orangtua`) VALUES
('-', '-', '-', '-', 5, 2);

-- --------------------------------------------------------

--
-- Table structure for table `prestasi`
--

CREATE TABLE `prestasi` (
  `id_prestasi` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `nama_prestasi` varchar(255) NOT NULL,
  `jenis` varchar(255) NOT NULL,
  `tingkat` enum('kecamatan','kota','provinsi','nasional','internasional') NOT NULL,
  `penyelenggara` varchar(255) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(1000) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE `siswa` (
  `nis` varchar(100) NOT NULL,
  `nisn` varchar(100) NOT NULL,
  `nama` varchar(40) NOT NULL,
  `kelas` varchar(100) NOT NULL,
  `alamat` varchar(60) NOT NULL,
  `email` varchar(100) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
  `asal_sekolah` varchar(30) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `id_akun` int(11) NOT NULL,
  `status` enum('aktif','lulus','keluar') NOT NULL,
  `telepon` varchar(40) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`nis`, `nisn`, `nama`, `kelas`, `alamat`, `email`, `foto`, `tanggal_lahir`, `jenis_kelamin`, `asal_sekolah`, `id_siswa`, `id_akun`, `status`, `telepon`) VALUES
('12345678', '1234567', 'qwertyuikwsdfgh', 'wertyuio', 'ytrhtayr3edscxtgdf', 'flare@gmail.com', '', '2025-06-06', 'Laki-laki', 'dftgfghbhjnbjnmjk', 4, 11, 'lulus', '8745654235562'),
('112312313', '-12313123', 'Bbla bla bla bli bli blu blu blu', 'sakaksok', '-', 'qwer@gmail.com', '', '1221-12-12', '', 'simba', 5, 14, 'aktif', '-0000001'),
('123123123', '32123123123', 'AKURAJAWA', 'basbas', 'JALAN-JALAN', '', '', '0000-00-00', 'Laki-laki', '', 6, 17, 'aktif', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id_akun`);

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id_dokumen`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `guru`
--
ALTER TABLE `guru`
  ADD PRIMARY KEY (`id_guru`),
  ADD KEY `id_akun` (`id_akun`);

--
-- Indexes for table `orangtua`
--
ALTER TABLE `orangtua`
  ADD PRIMARY KEY (`id_orangtua`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `prestasi`
--
ALTER TABLE `prestasi`
  ADD PRIMARY KEY (`id_prestasi`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `siswa`
--
ALTER TABLE `siswa`
  ADD PRIMARY KEY (`id_siswa`),
  ADD KEY `id_akun` (`id_akun`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akun`
--
ALTER TABLE `akun`
  MODIFY `id_akun` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id_dokumen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `guru`
--
ALTER TABLE `guru`
  MODIFY `id_guru` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orangtua`
--
ALTER TABLE `orangtua`
  MODIFY `id_orangtua` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `prestasi`
--
ALTER TABLE `prestasi`
  MODIFY `id_prestasi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `siswa`
--
ALTER TABLE `siswa`
  MODIFY `id_siswa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `guru`
--
ALTER TABLE `guru`
  ADD CONSTRAINT `guru_ibfk_1` FOREIGN KEY (`id_akun`) REFERENCES `akun` (`id_akun`);

--
-- Constraints for table `orangtua`
--
ALTER TABLE `orangtua`
  ADD CONSTRAINT `orangtua_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `prestasi`
--
ALTER TABLE `prestasi`
  ADD CONSTRAINT `prestasi_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id_siswa`);

--
-- Constraints for table `siswa`
--
ALTER TABLE `siswa`
  ADD CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_akun`) REFERENCES `akun` (`id_akun`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
