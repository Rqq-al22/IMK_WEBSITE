-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jan 05, 2026 at 12:32 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `money_tracker`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `user_id`, `name`, `type`, `created_at`) VALUES
(1, 2, 'Gaji', 'income', '2026-01-05 08:14:19'),
(2, 2, 'Bonus', 'income', '2026-01-05 08:14:19'),
(3, 2, 'Makan', 'expense', '2026-01-05 08:14:19'),
(4, 2, 'Transport', 'expense', '2026-01-05 08:14:19'),
(5, 2, 'Pulsa', 'expense', '2026-01-05 08:14:19'),
(6, 2, 'Belanja', 'expense', '2026-01-05 08:14:19'),
(7, 3, 'Gaji', 'income', '2026-01-05 08:14:52'),
(8, 3, 'Bonus', 'income', '2026-01-05 08:14:52'),
(9, 3, 'Makan', 'expense', '2026-01-05 08:14:52'),
(10, 3, 'Transport', 'expense', '2026-01-05 08:14:52'),
(11, 3, 'Pulsa', 'expense', '2026-01-05 08:14:52'),
(12, 3, 'Belanja', 'expense', '2026-01-05 08:14:52'),
(13, 5, 'Gaji', 'income', '2026-01-05 12:28:04'),
(14, 5, 'Hadiah', 'income', '2026-01-05 12:28:04'),
(15, 5, 'Freelance', 'income', '2026-01-05 12:28:04'),
(16, 5, 'Bonus', 'income', '2026-01-05 12:28:04'),
(17, 5, 'Lainnya', 'income', '2026-01-05 12:28:04'),
(18, 5, 'Makan', 'expense', '2026-01-05 12:28:04'),
(19, 5, 'Transport', 'expense', '2026-01-05 12:28:04'),
(20, 5, 'Pulsa/Internet', 'expense', '2026-01-05 12:28:04'),
(21, 5, 'Hiburan', 'expense', '2026-01-05 12:28:04'),
(22, 5, 'Kos/Tagihan', 'expense', '2026-01-05 12:28:04'),
(23, 5, 'Belanja', 'expense', '2026-01-05 12:28:04'),
(24, 5, 'Lainnya', 'expense', '2026-01-05 12:28:04');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED DEFAULT NULL,
  `type` enum('income','expense') NOT NULL,
  `amount` decimal(14,2) NOT NULL,
  `trx_date` date NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `created_at`) VALUES
(1, 'T001', 'rrrrr', 'rrr', '2026-01-05 07:45:16'),
(2, 'O08', 'rezkialya0000@gmail.com', '$2y$10$WHF69zlXi3/hpKzA4Mjds.Wnc5PgYo60jBumcZV/cQlDONLLOQOt.', '2026-01-05 08:14:19'),
(3, 'E1E124015', 'rezkialya0909@gmail.com', '$2y$10$IqOaOCQkepSpHheYa3Iqq.qPdWVn37u0So/1tPGvZWRGNnR5vMofi', '2026-01-05 08:14:52'),
(5, 'Raynara', 'admin@gmail.com', '$2y$10$9gMV2EZcCiAKTTyN9QCYM.KPKlZs73O6pkg/x5b.6GDswkvKkXxP2', '2026-01-05 12:28:04');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cat_user_name_type` (`user_id`,`name`,`type`),
  ADD KEY `idx_cat_user` (`user_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trx_category` (`category_id`),
  ADD KEY `idx_trx_user_date` (`user_id`,`trx_date`),
  ADD KEY `idx_trx_user_type` (`user_id`,`type`),
  ADD KEY `idx_trx_user_cat` (`user_id`,`category_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_trx_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_trx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
