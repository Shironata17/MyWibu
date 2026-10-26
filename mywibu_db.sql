-- phpMyAdmin SQL Dump
-- version 6.0.0-dev+20261024.6eba7ba9ad
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 26, 2026 at 01:35 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mywibu_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `anime_library`
--
CREATE TABLE `anime_library` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `title` varchar(160) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Plan to Watch',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--
CREATE TABLE `comments` (
  `id` int UNSIGNED NOT NULL,
  `video_id` int UNSIGNED NOT NULL,
  `user_id` int NOT NULL,
  `body` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `email` varchar(25) NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `tanggal_lahir`, `role`) VALUES
(1, 'admin1', 'admin1@gmail.com', '$2y$10$ms3Y0xSIRiJZJC/O60epTOR1yZguOiu0yNfJ6e53QBXnmtFkMh7JC', '2016-10-20', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--
CREATE TABLE `videos` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `description` text NOT NULL,
  `synopsis` text NOT NULL,
  `category` varchar(20) NOT NULL DEFAULT 'anime',
  `genre` varchar(100) NOT NULL DEFAULT '',
  `video_file` varchar(80) NOT NULL,
  `cover_file` varchar(80) DEFAULT NULL,
  `uploaded_by` int UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anime_library`
--
ALTER TABLE `anime_library`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_library` (`user_id`,`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comments_video_date` (`video_id`,`created_at`),
  ADD KEY `comments_user_fk` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `video_category_date` (`category`,`created_at`),
  ADD KEY `video_uploaded_by` (`uploaded_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anime_library`
--
ALTER TABLE `anime_library`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_video_fk` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
