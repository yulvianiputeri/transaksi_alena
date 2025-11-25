-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Nov 25, 2025 at 07:13 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `transaksi_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `harga_referensi`
--

CREATE TABLE `harga_referensi` (
  `id` int(11) NOT NULL,
  `jenis` varchar(255) DEFAULT NULL,
  `harga_beli` decimal(10,2) DEFAULT NULL,
  `harga_jual` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `harga_referensi`
--

INSERT INTO `harga_referensi` (`id`, `jenis`, `harga_beli`, `harga_jual`) VALUES
(4, 'Bahbir', 295.00, 305.00),
(5, 'Jolong', 310.00, 325.00),
(6, 'Kayu Log', 310.00, 325.00),
(7, 'Kayu Bakar', 310.00, 325.00),
(8, 'Tatalan', 310.00, 325.00),
(9, 'Kawung', 310.00, 325.00),
(10, 'Woodchip', 435.00, 450.00),
(11, 'Serbuk', 435.00, 450.00);

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `tanggal` date DEFAULT NULL,
  `nama` varchar(255) DEFAULT NULL,
  `jenis` varchar(255) DEFAULT NULL,
  `berat` decimal(10,2) DEFAULT NULL,
  `beli` decimal(10,2) DEFAULT NULL,
  `jual` decimal(10,2) DEFAULT NULL,
  `bongkar` varchar(255) DEFAULT NULL,
  `laba` decimal(10,2) DEFAULT NULL,
  `nomor_transaksi` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `tanggal`, `nama`, `jenis`, `berat`, `beli`, `jual`, `bongkar`, `laba`, `nomor_transaksi`) VALUES
(4, '2025-11-10', 'Anang', 'Woodchip', 3816.00, 1659960.00, 1717200.00, '19960', 57240.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$DwNpS6KrcuqcxGZZGAgz7.ZEBid2dmFzT4SUyzlOQRcDUvg3BxWmq', 'admin', '2025-11-25 16:16:57');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `harga_referensi`
--
ALTER TABLE `harga_referensi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `harga_referensi`
--
ALTER TABLE `harga_referensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
