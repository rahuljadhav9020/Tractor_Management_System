-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2025 at 08:44 AM
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
-- Database: `tractor_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `maintenance_date` date NOT NULL,
  `maintenance_type` varchar(100) NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`id`, `vehicle_id`, `maintenance_date`, `maintenance_type`, `cost`, `description`, `created_at`, `quantity`) VALUES
(6, 1, '2025-03-25', 'diesel_fuel', 1200.00, '', '2025-03-28 07:35:37', 10);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `source_type` enum('trip','rental','maintenance') NOT NULL,
  `source_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `paid_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT NULL,
  `remaining_amount` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `source_type`, `source_id`, `amount`, `status`, `created_at`, `paid_date`, `notes`, `paid_amount`, `remaining_amount`) VALUES
(9, 'trip', 5, 1200.00, '', '2025-03-28 06:30:13', '2025-03-28 00:00:00', NULL, 500.00, 500.00),
(11, 'rental', 3, 43500.00, '', '2025-03-28 06:31:31', '2025-03-28 00:00:00', NULL, 20000.00, 23500.00),
(12, 'trip', 6, 1000.00, 'paid', '2025-03-28 06:38:44', '2025-03-28 00:00:00', NULL, 1000.00, NULL),
(13, 'rental', 4, 15000.00, 'paid', '2025-03-28 06:39:12', '2025-03-28 00:00:00', NULL, 15000.00, NULL),
(15, 'trip', 8, 2000.00, 'paid', '2025-03-28 06:46:47', '2025-03-28 00:00:00', NULL, 2000.00, NULL),
(19, 'trip', 10, 2000.00, 'pending', '2025-03-28 07:23:24', NULL, NULL, NULL, NULL),
(20, 'trip', 11, 5000.00, 'pending', '2025-03-28 07:23:47', NULL, NULL, NULL, NULL),
(21, 'trip', 12, 5000.00, 'paid', '2025-03-28 07:24:30', '2025-03-28 00:00:00', NULL, 5000.00, NULL),
(23, 'maintenance', 6, 1200.00, 'pending', '2025-03-28 07:35:37', NULL, NULL, NULL, NULL),
(24, 'rental', 6, 14000.00, 'pending', '2025-03-28 07:43:03', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `rentals`
--

CREATE TABLE `rentals` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rentals`
--

INSERT INTO `rentals` (`id`, `vehicle_id`, `customer_name`, `start_date`, `end_date`, `daily_rate`, `total_amount`, `status`, `notes`, `created_at`) VALUES
(3, 3, 'chetan desai', '2025-01-01', '2025-03-28', 500.00, 43500.00, 'active', '', '2025-03-28 06:31:31'),
(4, 2, 'Vijay', '2025-01-31', '2025-03-01', 500.00, 15000.00, 'active', '', '2025-03-28 06:39:12'),
(6, 3, 'chetan desai', '2025-03-01', '2025-03-28', 500.00, 14000.00, 'active', '', '2025-03-28 07:43:03');

-- --------------------------------------------------------

--
-- Table structure for table `trips`
--

CREATE TABLE `trips` (
  `id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `start_location` varchar(255) NOT NULL,
  `end_location` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `trips` int(10) DEFAULT NULL,
  `trip_cost` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trips`
--

INSERT INTO `trips` (`id`, `vehicle_id`, `customer_name`, `start_location`, `end_location`, `date`, `trips`, `trip_cost`, `notes`, `created_at`) VALUES
(5, 4, 'Rahul Jadhav', 'कोरडेवाडी ', 'मोरगिरी ', '2025-03-28 00:00:00', 1, 1200.00, '', '2025-03-28 06:30:13'),
(6, 4, 'Rahul', 'मोरेवाडी ', 'आंब्रग ', '2025-03-28 00:00:00', 1, 1000.00, '', '2025-03-28 06:38:44'),
(8, 3, 'Sarthak', 'कोरडेवाडी ', 'मोरगिरी ', '2025-02-01 00:00:00', 1, 2000.00, '', '2025-03-28 06:46:47'),
(10, 4, 'Rahul', 'कोरडेवाडी ', 'मोरगिरी ', '2025-03-08 00:00:00', 2, 2000.00, '', '2025-03-28 07:23:24'),
(11, 4, 'अशोक मोरे ', 'कोरडेवाडी ', 'मोरगिरी ', '2025-03-03 00:00:00', 5, 5000.00, '', '2025-03-28 07:23:47'),
(12, 1, 'Sarthak', 'मोरेवाडी ', 'आंब्रग ', '2025-03-25 00:00:00', 5, 5000.00, '', '2025-03-28 07:24:30');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('tractor','trolley','tanker') NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `name`, `type`, `registration_number`, `created_at`) VALUES
(1, 'New Holland', 'tractor', 'MH50C310', '2025-03-02 08:31:36'),
(2, 'Tanker Old', 'tanker', 'MH50C310', '2025-03-02 08:31:52'),
(3, 'Tanker New', 'tanker', 'MH50C310', '2025-03-02 08:32:00'),
(4, 'N.Holland Trolley', 'trolley', 'MH50C310', '2025-03-02 08:32:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rentals`
--
ALTER TABLE `rentals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `trips`
--
ALTER TABLE `trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `rentals`
--
ALTER TABLE `rentals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `trips`
--
ALTER TABLE `trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `rentals`
--
ALTER TABLE `rentals`
  ADD CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);

--
-- Constraints for table `trips`
--
ALTER TABLE `trips`
  ADD CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
