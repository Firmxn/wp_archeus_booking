-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 03, 2025 at 03:32 AM
-- Server version: 8.0.43
-- PHP Version: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_puskeswan`
--

-- --------------------------------------------------------

--
-- Table structure for table `wp_archeus_reservasi_puskeswan`
--

CREATE TABLE `wp_archeus_reservasi_puskeswan` (
  `id` int NOT NULL,
  `customer_name` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `customer_email` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `booking_time` varchar(50) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `service_type` varchar(255) COLLATE utf8mb4_unicode_520_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_520_ci NOT NULL DEFAULT 'submitted',
  `payload` longtext COLLATE utf8mb4_unicode_520_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `form_id` longtext COLLATE utf8mb4_unicode_520_ci,
  `additional_fields` longtext COLLATE utf8mb4_unicode_520_ci,
  `schedule_id` longtext COLLATE utf8mb4_unicode_520_ci,
  `time_slot` longtext COLLATE utf8mb4_unicode_520_ci,
  `type_gender_pet` longtext COLLATE utf8mb4_unicode_520_ci,
  `age_pet` longtext COLLATE utf8mb4_unicode_520_ci,
  `berat_badan_pet` longtext COLLATE utf8mb4_unicode_520_ci,
  `type_vaksinasi` longtext COLLATE utf8mb4_unicode_520_ci,
  `date_vaksinasi_terakhir` longtext COLLATE utf8mb4_unicode_520_ci,
  `email` longtext COLLATE utf8mb4_unicode_520_ci,
  `type_pet` longtext COLLATE utf8mb4_unicode_520_ci,
  `breed_pet` longtext COLLATE utf8mb4_unicode_520_ci,
  `name_pet` longtext COLLATE utf8mb4_unicode_520_ci,
  `flow_id` longtext COLLATE utf8mb4_unicode_520_ci,
  `flow_name` longtext COLLATE utf8mb4_unicode_520_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;

--
-- Dumping data for table `wp_archeus_reservasi_puskeswan`
--

INSERT INTO `wp_archeus_reservasi_puskeswan` (`id`, `customer_name`, `customer_email`, `booking_date`, `booking_time`, `service_type`, `status`, `payload`, `created_at`, `updated_at`, `form_id`, `additional_fields`, `schedule_id`, `time_slot`, `type_gender_pet`, `age_pet`, `berat_badan_pet`, `type_vaksinasi`, `date_vaksinasi_terakhir`, `email`, `type_pet`, `breed_pet`, `name_pet`, `flow_id`, `flow_name`) VALUES
(1, 'Firmansyah Pramudia Ariyanto', 'arcministrator@gmail.com', '2025-10-03', '11:00:00', 'Kebiri', 'submitted', '{\"form_id\":1,\"customer_name\":\"Firmansyah Pramudia Ariyanto\",\"customer_email\":\"arcministrator@gmail.com\",\"booking_date\":\"2025-10-03\",\"booking_time\":\"11:00:00\",\"service_type\":\"Kebiri\",\"special_requests\":\"\",\"additional_fields\":\"a:9:{s:15:\\\"type_gender_pet\\\";s:6:\\\"Betina\\\";s:7:\\\"age_pet\\\";s:1:\\\"3\\\";s:15:\\\"berat_badan_pet\\\";s:1:\\\"3\\\";s:14:\\\"type_vaksinasi\\\";s:5:\\\"23r23\\\";s:23:\\\"date_vaksinasi_terakhir\\\";s:10:\\\"2025-10-07\\\";s:5:\\\"email\\\";s:24:\\\"arcministrator@gmail.com\\\";s:8:\\\"type_pet\\\";s:6:\\\"ewfwef\\\";s:9:\\\"breed_pet\\\";s:4:\\\"wefw\\\";s:8:\\\"name_pet\\\";s:6:\\\"23r2fw\\\";}\",\"schedule_id\":\"22\",\"time_slot\":\"2025-10-03 11:00:00-12:00:00\",\"type_gender_pet\":\"Betina\",\"age_pet\":\"3\",\"berat_badan_pet\":\"3\",\"type_vaksinasi\":\"23r23\",\"date_vaksinasi_terakhir\":\"2025-10-07\",\"email\":\"arcministrator@gmail.com\",\"type_pet\":\"ewfwef\",\"breed_pet\":\"wefw\",\"name_pet\":\"23r2fw\",\"flow_id\":1,\"flow_name\":\"Reservasi Puskeswan\"}', '2025-10-03 09:41:10', '2025-10-03 09:41:10', '1', 'a:9:{s:15:\"type_gender_pet\";s:6:\"Betina\";s:7:\"age_pet\";s:1:\"3\";s:15:\"berat_badan_pet\";s:1:\"3\";s:14:\"type_vaksinasi\";s:5:\"23r23\";s:23:\"date_vaksinasi_terakhir\";s:10:\"2025-10-07\";s:5:\"email\";s:24:\"arcministrator@gmail.com\";s:8:\"type_pet\";s:6:\"ewfwef\";s:9:\"breed_pet\";s:4:\"wefw\";s:8:\"name_pet\";s:6:\"23r2fw\";}', '22', '2025-10-03 11:00:00-12:00:00', 'Betina', '3', '3', '23r23', '2025-10-07', 'arcministrator@gmail.com', 'ewfwef', 'wefw', '23r2fw', '1', 'Reservasi Puskeswan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `wp_archeus_reservasi_puskeswan`
--
ALTER TABLE `wp_archeus_reservasi_puskeswan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_date` (`booking_date`),
  ADD KEY `service_type` (`service_type`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `wp_archeus_reservasi_puskeswan`
--
ALTER TABLE `wp_archeus_reservasi_puskeswan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
