-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 22, 2026 at 02:58 PM
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
-- Database: `privatehire`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `pickup` varchar(255) DEFAULT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `journey_date` date DEFAULT NULL,
  `journey_time` time DEFAULT NULL,
  `passengers` int(11) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Booked',
  `driver_id` int(11) DEFAULT NULL,
  `notification_preference` enum('sms','email','both') DEFAULT 'sms',
  `confirmation_sent` tinyint(1) DEFAULT 0,
  `reminder_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `pickup`, `destination`, `journey_date`, `journey_time`, `passengers`, `vehicle_id`, `status`, `driver_id`, `notification_preference`, `confirmation_sent`, `reminder_sent`) VALUES
(1, 8, 'Westlands', 'Kileleshwa', '2026-03-08', '05:20:00', 3, 1, 'Cancelled', NULL, 'sms', 0, 0),
(2, 8, 'Nairobi', 'Mombasa', '2026-03-20', '06:45:00', 5, 2, 'Cancelled', 0, 'sms', 0, 0),
(3, 8, 'Naivasha', 'Malindi', '2026-03-01', '21:10:00', 2, 3, 'Booked', 3, 'sms', 0, 0),
(4, 9, 'Westlands', 'Mombasa', '2026-03-13', '13:30:00', 6, 2, 'Cancelled', 2, 'sms', 0, 0),
(5, 10, 'Westlands', 'Kileleshwa', '2026-03-21', '16:07:00', 5, 2, 'Cancelled', 5, 'sms', 0, 0),
(6, 12, 'Westlands', 'Home', '2026-03-20', '11:07:00', 2, 1, 'Cancelled', 6, 'sms', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`id`, `name`, `phone`, `vehicle_id`) VALUES
(1, 'John Brown', '0700000001', 1),
(2, 'David Clark', '0700000002', 2),
(3, 'Michael White', '0700000003', 3),
(4, 'James Green', '0700000004', 1),
(5, 'Daniel Hill', '0700000005', 2),
(6, 'Jet Jefferson', '0734567821', 1);

-- --------------------------------------------------------

--
-- Table structure for table `enquiries`
--

CREATE TABLE `enquiries` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enquiries`
--

INSERT INTO `enquiries` (`id`, `name`, `email`, `message`) VALUES
(1, 'test123', 'test@email.com', 'This is a test'),
(3, 'keith', 'keith@email.com', 'hgfxe22uygxe2iuhli2ehj');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` varchar(20) DEFAULT 'customer',
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `reset_expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `phone`, `role`, `reset_token`, `reset_expires`, `reset_expires_at`) VALUES
(7, 'admin', 'admin@email.com', '$2y$10$7QYIo4zpVRZ94uo3HobEAeKy8hYa/22Ms898n2UOoFmmhiKf4hTvK', NULL, 'admin', NULL, NULL, NULL),
(8, 'test', 'test@email.com', '$2y$10$ERYQWasHofu3yw2TnHLBKuw9LTicnLHodZtNs6ED5z6ckWCpOQ1L2', NULL, 'customer', NULL, NULL, NULL),
(9, 'test1', 'test1@gmail.com', '$2y$10$rgxdOCDjSSNDqWvJ7FGf/OxOt6f2DsFLRwCikUTOPwBMWD4o.PzI.', NULL, 'customer', NULL, NULL, NULL),
(10, 'keith', 'keith@email.com', '$2y$10$hw0ixzaQVDARQyNdGQCr2u7u4CGeiolBzL5nCPrZlQNezXEE1zRLy', NULL, 'customer', NULL, NULL, NULL),
(11, 'test2', 'test2@gmail.com', '$2y$10$F9w3lJhjrmcczf4Aa4S.luA75PsSSv.9M/dUviC5hIBBWU6HXVJcS', NULL, 'customer', NULL, NULL, NULL),
(12, 'keith2', 'keith2@mail.com', '$2y$10$aRmb929fXqb4jPwGE/WlPOJOx4Kb9Vhc1bOmASCdMTjq9m0QPy3Ka', NULL, 'customer', NULL, NULL, NULL),
(13, 'k123', 'k@email.com', '$2y$10$Ens5q/9CiMJjOyvAgeZI8OUwuHjBJ8CifwpKWECmEQJIWfga/mGmi', NULL, 'customer', NULL, NULL, NULL),
(14, 'New', 'karungokeith@gmail.com', '$2y$10$4dUnm929vlQVQP0THRz6IePzW8sUdcirzTieDC096ugfUerhCDG6.', NULL, 'customer', NULL, NULL, NULL),
(15, 'bomber', 'bomberalive@gmail.com', '$2y$10$KUQim1Zs86OMmaIbA57zQOrZrUMIi6Q6K/d8J6kXdlYG.6j3xzF5y', NULL, 'customer', '2f039d41a4aabb0d6aca43794d8dff1960dfb4cbdc39c0f47bca3adccf7f4f85', NULL, '2026-03-22 14:50:30');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `seats` int(11) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `price_per_km` decimal(10,2) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `colour` varchar(50) DEFAULT NULL,
  `make` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `name`, `seats`, `price`, `price_per_km`, `registration_number`, `colour`, `make`, `model`) VALUES
(1, 'Toyota Prius', 4, 12.00, 1.50, NULL, NULL, NULL, NULL),
(2, 'Mercedes Vito', 7, 20.00, 2.50, NULL, NULL, NULL, NULL),
(3, 'BMW Executive', 4, 25.00, 3.00, NULL, NULL, NULL, NULL),
(4, 'Cadillac escalade', 4, NULL, NULL, NULL, NULL, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `enquiries`
--
ALTER TABLE `enquiries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `enquiries`
--
ALTER TABLE `enquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

-- --------------------------------------------------------
-- Sprint 2 migration additions
-- --------------------------------------------------------

ALTER TABLE `bookings`
  ADD COLUMN IF NOT EXISTS `booking_channel` enum('online','phone') DEFAULT 'online',
  ADD COLUMN IF NOT EXISTS `total_cost` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `discount_percent` decimal(5,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `discount_amount` decimal(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `final_cost` decimal(10,2) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_method` enum('paypal','card') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  ADD COLUMN IF NOT EXISTS `payment_reference` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `card_brand` enum('visa','mastercard','amex') DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `card_last4` varchar(4) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `cancelled_at` datetime DEFAULT NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
