-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql101.infinityfree.com
-- Generation Time: Aug 09, 2026 at 02:15 PM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_42323304_iqamat`
--

-- --------------------------------------------------------

--
-- Table structure for table `dars_areas`
--

CREATE TABLE `dars_areas` (
  `id` int(11) NOT NULL,
  `areaName` varchar(100) NOT NULL,
  `darsType` enum('Weekly','Monthly','Bi-weekly') NOT NULL,
  `dayTime` varchar(100) DEFAULT NULL,
  `startDate` date DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `mapLink` text DEFAULT NULL,
  `contactName` varchar(100) DEFAULT NULL,
  `contactPhone` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `supervisor_id` int(10) NOT NULL,
  `representative_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dars_areas`
--

INSERT INTO `dars_areas` (`id`, `areaName`, `darsType`, `dayTime`, `startDate`, `location`, `mapLink`, `contactName`, `contactPhone`, `created_at`, `supervisor_id`, `representative_id`) VALUES
(2, 'Satellite Town Circle', 'Weekly', 'Friday 7:00 PM', '2025-01-10', 'Satellite Town Block B, Rawalpindi', 'https://maps.google.com/?q=Satellite+Town+Rawalpindi', 'Usman Ali', '03001234567', '2026-04-20 06:32:59', 0, NULL),
(3, 'Bahria Phase 4 Group', 'Monthly', 'Sunday 5:00 PM', '2025-02-15', 'Bahria Town Phase 4 Civic Center', 'https://maps.google.com/?q=Bahria+Phase+4', 'Saad Hussain', '03111222333', '2026-04-20 06:32:59', 0, NULL),
(4, 'Chaklala Scheme Circle', 'Bi-weekly', 'Saturday 6:30 PM', '2025-03-01', 'Chaklala Scheme 3 Park Area', 'https://maps.google.com/?q=Chaklala+Scheme+3', 'Hamza Tariq', '03219876543', '2026-04-20 06:32:59', 0, NULL),
(5, 'PWD Housing Dars', 'Weekly', 'Thursday 8:00 PM', '2025-01-20', 'PWD Housing Society Main Markaz', 'https://maps.google.com/?q=PWD+Housing+Society', 'Ali Raza', '03335557788', '2026-04-20 06:32:59', 0, NULL),
(6, 'Gulraiz Community', 'Monthly', 'Saturday 7:30 PM', '2025-02-10', 'Gulraiz Housing Scheme Mosque', 'https://maps.google.com/?q=Gulraiz+Housing+Scheme', 'Zain Ahmed', '03005556666', '2026-04-20 06:32:59', 0, NULL),
(7, 'Adyala Road Circle', 'Bi-weekly', 'Sunday 6:00 PM', '2025-03-05', 'Adyala Road Near Jail Chowk', 'https://maps.google.com/?q=Adyala+Road', 'Fahad Ali', '03441234567', '2026-04-20 06:32:59', 0, NULL),
(8, 'Commercial Market Group', 'Weekly', 'Wednesday 7:15 PM', '2025-01-25', 'Commercial Market Satellite Town', 'https://maps.google.com/?q=Commercial+Market+Rawalpindi', 'Danish Khan', '03114567890', '2026-04-20 06:32:59', 0, NULL),
(9, 'Westridge Circle', 'Monthly', 'Friday 6:45 PM', '2025-02-18', 'Westridge 2 Masjid Area', 'https://maps.google.com/?q=Westridge+2', 'Kashif Ali', '03216789012', '2026-04-20 06:32:59', 0, NULL),
(10, 'Gulshan Colony2', 'Weekly', 'Sunday 2:00 PM', '2026-05-05', 'Haripur', 'https://maps.app.goo.gl/Q2mSJmFmfjMXotqh9', '0213456897', '03156498', '2026-05-05 07:32:28', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `dars_attendance`
--

CREATE TABLE `dars_attendance` (
  `id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL COMMENT 'FK → dars_areas.id',
  `user_id` int(11) NOT NULL COMMENT 'FK → users.id  (the member being marked)',
  `submitted_by` int(11) NOT NULL COMMENT 'FK → users.id  (who submitted this attendance)',
  `dateTime` date NOT NULL COMMENT 'Date of the Dars session',
  `attendance` enum('Present','Absent') NOT NULL DEFAULT 'Absent',
  `timing` enum('OnTime','Late') NOT NULL DEFAULT 'OnTime' COMMENT 'Only relevant when attendance = Present',
  `note` text DEFAULT NULL COMMENT 'Optional session note (shared across all rows of same session)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dawah_records`
--

CREATE TABLE `dawah_records` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `dawah_type` enum('Personal','Collective') NOT NULL DEFAULT 'Personal',
  `dawah_mode` enum('Physical','Online') NOT NULL DEFAULT 'Physical',
  `person_name` varchar(150) NOT NULL,
  `person_contact` varchar(120) DEFAULT NULL,
  `location` varchar(200) NOT NULL,
  `dawah_date` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dawah_records`
--

INSERT INTO `dawah_records` (`id`, `user_id`, `dawah_type`, `dawah_mode`, `person_name`, `person_contact`, `location`, `dawah_date`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 19, 'Personal', 'Physical', 'Usman', '0123468789', 'Haripur', '2026-05-05', 'Good', '2026-05-11 12:04:14', '2026-05-11 12:04:14'),
(2, 23, 'Personal', 'Physical', 'Usman', '0123468789', 'Haripur', '2026-05-13', 'Good', '2026-05-13 21:53:58', '2026-05-13 21:54:15'),
(3, 27, 'Personal', 'Online', 'Usman', '9784563', 'Pindi', '2026-06-26', 'Good', '2026-06-28 16:40:26', '2026-06-28 16:40:26');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `topic` varchar(50) NOT NULL,
  `dateTime` datetime(6) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `location` varchar(50) NOT NULL,
  `type` text NOT NULL,
  `organisier` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `topic`, `dateTime`, `phone`, `location`, `type`, `organisier`) VALUES
(24, 'Dawaah Workshop', '2026-07-11 19:03:00.000000', '854136', 'Pindi', 'Dars', 2),
(25, 'Quran Recitation', '2026-07-25 19:10:00.000000', '456456', 'Hall Z', 'Dars', 28),
(26, 'Quran Recitation', '2026-07-09 19:21:00.000000', '456465', 'RAWALPINDI', 'Workshop', 1),
(27, 'Dawaah Single', '2026-07-06 19:26:00.000000', '03063695755', 'Haripur', 'Dawah', 1),
(28, 'Dawaah Single', '2026-07-24 19:43:00.000000', '12345689', 'Hall Z', 'Dars', 27),
(29, 'Surah Inshirah', '2026-07-11 02:47:00.000000', '798465789', 'Haripur', 'Dars', 34),
(30, 'Character Building', '2026-07-22 02:47:00.000000', '78998', 'Hall Z', 'Workshop', 1),
(31, 'Dawaah Workshop', '2026-07-18 02:48:00.000000', '456', 'Ali Masjid', 'Dawah', 23),
(32, 'Dawaah Single', '2026-07-17 02:52:00.000000', '789465', 'RAWALPINDI', 'Dars', 0),
(33, 'Dawah', '2026-07-23 02:53:00.000000', '789456', 'Rawalpindi', 'Dars', 34),
(34, 'Worksop2', '2026-07-17 02:55:00.000000', '123564', 'RAWALPINDI', 'Workshop', 16),
(35, 'Dars1', '2026-08-01 02:58:00.000000', '456465', 'Pindi', 'Dars', 27),
(36, 'Surah Fatiha', '2026-07-11 02:39:00.000000', '', '', 'Dars', 43),
(37, 'Surah Ikhlas', '2026-07-22 16:47:00.000000', '789456', 'Haripur', 'Dars', 41);

-- --------------------------------------------------------

--
-- Table structure for table `jobs_internships`
--

CREATE TABLE `jobs_internships` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('Job','Internship') NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `location_type` enum('Online','Physical','Hybrid') DEFAULT 'Physical',
  `location_address` varchar(500) DEFAULT NULL,
  `salary` varchar(100) DEFAULT NULL,
  `timings` varchar(255) DEFAULT NULL,
  `company_contact` varchar(255) DEFAULT NULL,
  `referred_by_name` varchar(255) DEFAULT NULL,
  `referred_by_contact` varchar(255) DEFAULT NULL,
  `posted_date` date DEFAULT NULL,
  `status` enum('Active','Closed') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jobs_internships`
--

INSERT INTO `jobs_internships` (`id`, `title`, `type`, `company_name`, `location_type`, `location_address`, `salary`, `timings`, `company_contact`, `referred_by_name`, `referred_by_contact`, `posted_date`, `status`, `created_at`) VALUES
(1, 'Frontend Developer', 'Job', 'TechNova Solutions', 'Hybrid', 'Islamabad, Pakistan', 'PKR 120,000/month', '9 AM - 5 PM', 'hr@technova.com', 'Ali Raza', '+92 300 1234567', '2026-04-17', 'Active', '2026-04-20 07:30:00'),
(2, 'Digital Marketing Intern', 'Internship', 'Bright Media Agency', 'Online', NULL, 'PKR 15,000/month', 'Flexible', 'contact@brightmedia.com', 'Sara Khan', '+92 301 7654321', '2026-04-12', 'Active', '2026-04-20 07:30:00'),
(3, 'Backend Developer (Laravel)', 'Job', 'CodeCrafters Pvt Ltd', 'Physical', 'Lahore, Pakistan', 'PKR 150,000/month', '10 AM - 6 PM', 'jobs@codecrafters.com', 'Usman Tariq', '+92 302 9988776', '2026-04-05', 'Active', '2026-04-20 07:30:00'),
(4, 'Graphic Designer Intern', 'Internship', 'Pixel Studio', 'Hybrid', 'Karachi, Pakistan', 'PKR 20,000/month', '11 AM - 4 PM', 'info@pixelstudio.com', 'Ayesha Malik', '+92 333 1122334', '2026-04-01', 'Closed', '2026-04-20 07:30:00'),
(5, 'Data Analyst', 'Job', 'Insight Analytics', 'Online', NULL, 'PKR 130,000/month', 'Flexible', 'careers@insight.com', 'Bilal Ahmed', '+92 345 5566778', '2026-03-28', 'Active', '2026-04-20 07:30:00'),
(7, 'Content Writing Intern', 'Internship', 'WriteSmart Co.', 'Online', NULL, 'PKR 10,000/month', 'Flexible', 'hello@writesmart.com', 'Fatima Noor', '+92 321 2233445', '2026-04-18', 'Active', '2026-04-20 07:30:00'),
(8, 'UI/UX Designer', 'Job', 'Creative Minds', 'Physical', 'Faisalabad, Pakistan', 'PKR 110,000/month', '10 AM - 5 PM', 'jobs@creativeminds.com', 'Zain Ali', '+92 334 6677889', '2026-04-08', 'Closed', '2026-04-20 07:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `namaz_attendance`
--

CREATE TABLE `namaz_attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL COMMENT 'FK → users.id',
  `area_id` int(11) DEFAULT NULL COMMENT 'FK → dars_areas.id',
  `attendance_date` date NOT NULL COMMENT 'Date of the prayer',
  `prayer_name` enum('fajr','dhuhr','asr','maghrib','isha') NOT NULL,
  `status` enum('with_jamaat','without_jamaat','missed') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `organisier_id` int(11) NOT NULL,
  `area` varchar(100) DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Dars',
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `event_id`, `user_id`, `organisier_id`, `area`, `type`, `message`, `is_read`, `created_at`) VALUES
(104, 37, 44, 41, '9', 'Dars', 'New Dars: \"Surah Ikhlas\" at Haripur', 0, '2026-07-09 14:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `quran_attendance`
--

CREATE TABLE `quran_attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `area_id` int(10) UNSIGNED NOT NULL,
  `attendance_date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL DEFAULT 'Absent',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quran_attendance`
--

INSERT INTO `quran_attendance` (`id`, `user_id`, `area_id`, `attendance_date`, `status`, `created_at`, `updated_at`) VALUES
(44, 20, 7, '2026-04-27', 'Present', '2026-05-03 15:25:02', '2026-05-03 15:25:25'),
(45, 20, 7, '2026-04-28', 'Present', '2026-05-03 15:25:02', '2026-05-03 15:25:25'),
(46, 20, 7, '2026-04-29', 'Present', '2026-05-03 15:25:03', '2026-05-03 15:25:26'),
(47, 20, 7, '2026-05-02', 'Present', '2026-05-03 15:25:04', '2026-05-03 15:25:30'),
(48, 20, 7, '2026-04-30', 'Present', '2026-05-03 15:25:09', '2026-05-03 15:25:28'),
(50, 20, 7, '2026-05-01', 'Present', '2026-05-03 15:25:13', '2026-05-03 15:25:30'),
(52, 20, 7, '2026-05-03', 'Present', '2026-05-03 15:25:15', '2026-05-03 15:25:31'),
(67, 21, 2, '2026-05-04', 'Present', '2026-05-07 05:57:37', '2026-05-07 05:57:37'),
(68, 21, 2, '2026-05-05', 'Present', '2026-05-07 05:57:38', '2026-05-07 06:30:36'),
(69, 21, 2, '2026-05-06', 'Present', '2026-05-07 05:57:53', '2026-05-09 06:51:39'),
(70, 21, 2, '2026-05-07', 'Present', '2026-05-07 06:29:59', '2026-05-09 06:51:39'),
(81, 20, 7, '2026-05-04', 'Present', '2026-05-09 06:52:36', '2026-05-09 06:52:36'),
(82, 20, 7, '2026-05-05', 'Present', '2026-05-09 06:52:36', '2026-05-09 06:52:36'),
(83, 20, 7, '2026-05-06', 'Present', '2026-05-09 06:52:37', '2026-05-09 06:52:37'),
(84, 20, 7, '2026-05-07', 'Present', '2026-05-09 06:52:38', '2026-05-09 06:52:38'),
(85, 22, 9, '2026-05-04', 'Present', '2026-05-09 06:54:21', '2026-05-09 06:54:21'),
(86, 22, 9, '2026-05-05', 'Present', '2026-05-09 06:54:22', '2026-05-09 06:54:22'),
(87, 22, 9, '2026-05-06', 'Present', '2026-05-09 06:54:22', '2026-05-09 06:54:22'),
(88, 22, 9, '2026-05-07', 'Present', '2026-05-09 06:54:23', '2026-05-09 06:54:23'),
(89, 22, 9, '2026-04-27', 'Present', '2026-05-09 06:56:10', '2026-05-09 06:56:10'),
(90, 22, 9, '2026-04-28', 'Present', '2026-05-09 06:56:11', '2026-05-09 06:56:11'),
(91, 22, 9, '2026-04-29', 'Present', '2026-05-09 06:56:12', '2026-05-09 06:56:12'),
(92, 22, 9, '2026-04-30', 'Present', '2026-05-09 06:56:13', '2026-05-09 06:56:13'),
(93, 19, 2, '2026-04-27', 'Present', '2026-05-09 06:58:42', '2026-05-09 06:58:42'),
(94, 19, 2, '2026-04-28', 'Present', '2026-05-09 06:58:43', '2026-05-09 06:58:43'),
(95, 19, 2, '2026-04-29', 'Present', '2026-05-09 06:58:44', '2026-05-09 06:58:44'),
(96, 19, 2, '2026-04-30', 'Present', '2026-05-09 06:58:44', '2026-05-09 06:58:44'),
(97, 23, 2, '2026-05-11', 'Present', '2026-05-13 16:52:20', '2026-05-13 16:52:20'),
(98, 23, 2, '2026-05-12', 'Present', '2026-05-13 16:52:22', '2026-05-13 16:52:42'),
(99, 23, 2, '2026-05-13', 'Present', '2026-05-13 16:52:23', '2026-05-13 16:52:23'),
(102, 23, 2, '2026-05-04', 'Present', '2026-05-13 16:53:02', '2026-05-13 16:53:02'),
(103, 23, 2, '2026-05-05', 'Present', '2026-05-13 16:53:02', '2026-05-13 16:53:02'),
(104, 23, 2, '2026-05-06', 'Present', '2026-05-13 16:53:03', '2026-05-13 16:53:03'),
(105, 23, 2, '2026-05-07', 'Present', '2026-05-13 16:53:03', '2026-05-13 16:53:03'),
(106, 23, 2, '2026-05-08', 'Present', '2026-05-13 16:53:03', '2026-05-13 16:53:03'),
(107, 23, 2, '2026-05-09', 'Present', '2026-05-13 16:53:04', '2026-05-13 16:53:04'),
(108, 23, 2, '2026-05-10', 'Present', '2026-05-13 16:53:04', '2026-05-13 16:53:14'),
(111, 27, 9, '2026-06-29', 'Present', '2026-06-30 13:21:32', '2026-06-30 13:21:32'),
(112, 27, 9, '2026-06-30', 'Present', '2026-06-30 13:21:33', '2026-06-30 13:21:33'),
(113, 33, 9, '2026-07-02', 'Present', '2026-07-02 20:54:12', '2026-07-02 20:54:12'),
(114, 33, 9, '2026-07-01', 'Present', '2026-07-02 20:54:14', '2026-07-02 20:54:14'),
(115, 33, 9, '2026-06-30', 'Present', '2026-07-02 20:54:15', '2026-07-02 20:54:15'),
(116, 46, 2, '2026-07-20', 'Absent', '2026-07-26 12:32:07', '2026-07-26 12:32:31');

-- --------------------------------------------------------

--
-- Table structure for table `research`
--

CREATE TABLE `research` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author_id` int(11) NOT NULL,
  `research_type` varchar(100) NOT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL COMMENT 'FK → users.id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `name`, `description`, `leader_id`, `created_at`) VALUES
(1, 'IT Team', 'Manages all technical systems.', NULL, '2026-05-04 06:02:42'),
(2, 'Research Team', 'Handles research & publications.', NULL, '2026-05-04 06:02:42');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `team_role` enum('member','trainee','lead') NOT NULL DEFAULT 'member',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `technical_workshops`
--

CREATE TABLE `technical_workshops` (
  `id` int(11) NOT NULL,
  `topic` varchar(200) NOT NULL,
  `skills` varchar(300) NOT NULL,
  `organisier` varchar(150) NOT NULL,
  `dateTime` datetime DEFAULT NULL,
  `durationValue` int(11) DEFAULT NULL COMMENT 'e.g. 2',
  `durationUnit` enum('Days','Weeks','Months') DEFAULT 'Months',
  `frequency` int(11) DEFAULT NULL COMMENT 'sessions per week',
  `phone` varchar(30) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `feeType` enum('Free','Paid') NOT NULL DEFAULT 'Free',
  `feeAmount` varchar(100) DEFAULT NULL COMMENT 'e.g. 5000 PKR, only used when feeType=Paid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technical_workshops`
--

INSERT INTO `technical_workshops` (`id`, `topic`, `skills`, `organisier`, `dateTime`, `durationValue`, `durationUnit`, `frequency`, `phone`, `location`, `feeType`, `feeAmount`, `created_at`) VALUES
(1, 'Python for Beginners', 'Python, OOP, File I/O', 'Ahmed Raza', '2026-05-10 09:00:00', 2, 'Months', 2, '+92-300-1234567', 'Hall A, Rawalpindi', 'Free', NULL, '2026-04-21 06:03:23'),
(2, 'Excel & Data Analysis', 'Excel, Pivot Tables, Charts', 'Sara Khan', '2026-06-01 10:00:00', 1, 'Months', 3, '+92-321-9876543', 'Room 3, Islamabad', 'Paid', '3000 PKR', '2026-04-21 06:03:23'),
(3, 'Networking Basics', 'TCP/IP, Subnetting, Cisco', 'Usman Ali', '2026-07-15 14:00:00', 6, 'Weeks', 4, '+92-333-5556677', 'Lab 2, Peshawar', 'Free', NULL, '2026-04-21 06:03:23');

-- --------------------------------------------------------

--
-- Table structure for table `time_daily`
--

CREATE TABLE `time_daily` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `log_date` date NOT NULL,
  `total_minutes` decimal(8,2) NOT NULL DEFAULT 0.00,
  `goal_achieved` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 if >= 60 min',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_daily`
--

INSERT INTO `time_daily` (`id`, `user_id`, `log_date`, `total_minutes`, `goal_achieved`, `updated_at`) VALUES
(1, 19, '2026-05-01', '0.64', 0, '2026-05-01 17:19:34'),
(4, 19, '2026-05-02', '1.15', 0, '2026-05-02 05:36:50'),
(5, 19, '2026-05-03', '1.00', 0, '2026-05-03 14:53:31'),
(6, 20, '2026-05-03', '0.20', 0, '2026-05-03 15:26:51'),
(7, 23, '2026-05-13', '0.88', 0, '2026-05-13 16:57:54');

-- --------------------------------------------------------

--
-- Table structure for table `time_sessions`
--

CREATE TABLE `time_sessions` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `area_id` int(10) UNSIGNED DEFAULT NULL,
  `session_date` date NOT NULL COMMENT 'YYYY-MM-DD of the session start',
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL COMMENT 'NULL while timer is running',
  `duration_minutes` decimal(8,2) DEFAULT 0.00 COMMENT 'Filled on Stop',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `time_sessions`
--

INSERT INTO `time_sessions` (`id`, `user_id`, `area_id`, `session_date`, `start_time`, `end_time`, `duration_minutes`, `created_at`) VALUES
(1, 19, 2, '2026-05-01', '2026-05-01 19:18:19', '2026-05-01 19:18:36', '0.28', '2026-05-01 17:18:19'),
(2, 19, 2, '2026-05-01', '2026-05-01 19:18:40', '2026-05-01 19:18:51', '0.18', '2026-05-01 17:18:40'),
(3, 19, 2, '2026-05-01', '2026-05-01 19:19:23', '2026-05-01 19:19:34', '0.18', '2026-05-01 17:19:23'),
(4, 19, 2, '2026-05-02', '2026-05-02 07:35:41', '2026-05-02 07:36:50', '1.15', '2026-05-02 05:35:41'),
(5, 19, 2, '2026-05-03', '2026-05-03 16:52:31', '2026-05-03 16:53:31', '1.00', '2026-05-03 14:52:31'),
(6, 20, 7, '2026-05-03', '2026-05-03 17:26:39', '2026-05-03 17:26:51', '0.20', '2026-05-03 15:26:39'),
(7, 23, 2, '2026-05-13', '2026-05-13 18:56:57', '2026-05-13 18:57:35', '0.63', '2026-05-13 16:56:57'),
(8, 23, 2, '2026-05-13', '2026-05-13 18:57:39', '2026-05-13 18:57:54', '0.25', '2026-05-13 16:57:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstName` text NOT NULL,
  `lastName` text NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(260) NOT NULL,
  `age` int(11) NOT NULL,
  `gender` text NOT NULL,
  `email` varchar(50) NOT NULL,
  `phone` int(20) NOT NULL,
  `cnic` varchar(20) NOT NULL,
  `role` text NOT NULL,
  `area` text NOT NULL,
  `supervisor_id` int(10) NOT NULL,
  `image` varchar(50) DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `activation_token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstName`, `lastName`, `username`, `password`, `age`, `gender`, `email`, `phone`, `cnic`, `role`, `area`, `supervisor_id`, `image`, `date_of_joining`, `is_active`, `approval_status`, `activation_token`) VALUES
(43, 'Ch', 'Osama', 'ChOsama', '$2y$10$f8gr26FIeXA7Gtu0qJzumuTlaeO1UkHlh.vzrgeoj7cLCJIPT0f/i', 32, 'Male', 'chosamabi7777@gmail.com', 2147483647, '', 'committee', '5', 0, NULL, NULL, 1, 'approved', NULL),
(44, 'Potato', 'Osama', 'PotatoOsama', '$2y$10$QNKX1Iu6ZReVJtGNGbkPzeNEWdTRPIGb4VuyIsfmTJKenm/epivm2', 35, 'Male', 'immrpotato77@gmail.com', 123456, '', 'trainee', '9', 0, NULL, NULL, 1, 'approved', NULL),
(45, 'Ammarah', 'Yaqub', 'AmmarahYaqub', '$2y$10$vWqvevauWB2ZO1r/Vw8EO.BjionAD/s/Z2w3JE0WtbUEN7YrX3mWm', 40, 'Female', 'ammarah.msc@gmail.com', 2147483647, '', 'member', '7', 0, NULL, NULL, 1, 'pending', NULL),
(46, 'Osama', 'Naeem', 'OsamaNaeem', '$2y$10$.G.yUuDBw1UXbk9uhqjFpeKSGCDlzF/fV1SPadiCsRNzpCpqwovBO', 23, 'Male', 'chosama7777@gmail.com', 98765, '', 'committee', '2', 0, NULL, NULL, 1, 'approved', NULL),
(47, 'Zain', 'Ul Abideen', 'ZainUl Abideen', '$2y$10$C6.D8J/bd6BSi1rYqZK5Wuc9N.TAIidcpvkfEKOw4acBm9XbfOHPW', 24, 'Male', 'uzain9661@gmail.com', 2147483647, '', 'member', '10', 0, 'uploads/profiles/user_6a6a332bcdeea0.13116163.jpg', NULL, 1, 'approved', NULL),
(48, 'Muhammad Abdullah', 'Siddique', 'Muhammad AbdullahSid', '$2y$10$eoUj32fM55dNENUIcu7DEO4lAOJVltUDTffKSJ84ytTe04zfdY.Ra', 20, 'Male', 'a24998113@gmail.com', 2147483647, '', 'member', '10', 0, 'uploads/profiles/user_6a789179bb0688.57642881.jpg', NULL, 1, 'pending', NULL),
(49, 'Muhammad', 'Siddique', 'MuhammadSiddique', '$2y$10$V48qjl3Z6v0HF/9OKVIcyOtpZrCCm.iEfn2Au7P7NaDxUgzsIsiLm', 20, 'Male', 'abdullahsiddique.dev.ai@gmail.com', 2147483647, '', 'member', '10', 0, 'uploads/profiles/user_6a78c250cf77f3.13449501.jpg', NULL, 0, 'pending', '5d2ae40fcdfac2612b50e2265847e638f5f9115bfe06c257e92ceaf3dadcb01c');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dars_areas`
--
ALTER TABLE `dars_areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_representative` (`representative_id`);

--
-- Indexes for table `dars_attendance`
--
ALTER TABLE `dars_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_session_member` (`area_id`,`user_id`,`dateTime`),
  ADD KEY `fk_att_user` (`user_id`),
  ADD KEY `fk_att_submitted` (`submitted_by`);

--
-- Indexes for table `dawah_records`
--
ALTER TABLE `dawah_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_date` (`dawah_date`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organisier` (`organisier`),
  ADD KEY `topic` (`topic`,`organisier`),
  ADD KEY `organisier_2` (`organisier`);

--
-- Indexes for table `jobs_internships`
--
ALTER TABLE `jobs_internships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `namaz_attendance`
--
ALTER TABLE `namaz_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_namaz` (`user_id`,`attendance_date`,`prayer_name`),
  ADD KEY `fk_namaz_area` (`area_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `idx_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `quran_attendance`
--
ALTER TABLE `quran_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_date` (`user_id`,`attendance_date`);

--
-- Indexes for table `research`
--
ALTER TABLE `research`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_team_name` (`name`),
  ADD KEY `idx_team_leader` (`leader_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_team_member` (`team_id`,`user_id`),
  ADD KEY `idx_tm_team` (`team_id`),
  ADD KEY `idx_tm_user` (`user_id`);

--
-- Indexes for table `technical_workshops`
--
ALTER TABLE `technical_workshops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_daily`
--
ALTER TABLE `time_daily`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_date` (`user_id`,`log_date`);

--
-- Indexes for table `time_sessions`
--
ALTER TABLE `time_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_date` (`user_id`,`session_date`),
  ADD KEY `idx_user_open` (`user_id`,`end_time`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dars_areas`
--
ALTER TABLE `dars_areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dars_attendance`
--
ALTER TABLE `dars_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `dawah_records`
--
ALTER TABLE `dawah_records`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `jobs_internships`
--
ALTER TABLE `jobs_internships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `namaz_attendance`
--
ALTER TABLE `namaz_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=449;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `quran_attendance`
--
ALTER TABLE `quran_attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;

--
-- AUTO_INCREMENT for table `research`
--
ALTER TABLE `research`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `technical_workshops`
--
ALTER TABLE `technical_workshops`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `time_daily`
--
ALTER TABLE `time_daily`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `time_sessions`
--
ALTER TABLE `time_sessions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dars_areas`
--
ALTER TABLE `dars_areas`
  ADD CONSTRAINT `fk_representative` FOREIGN KEY (`representative_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `dars_attendance`
--
ALTER TABLE `dars_attendance`
  ADD CONSTRAINT `fk_att_area` FOREIGN KEY (`area_id`) REFERENCES `dars_areas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_submitted` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `namaz_attendance`
--
ALTER TABLE `namaz_attendance`
  ADD CONSTRAINT `fk_namaz_area` FOREIGN KEY (`area_id`) REFERENCES `dars_areas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_namaz_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `research`
--
ALTER TABLE `research`
  ADD CONSTRAINT `research_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `fk_teams_leader` FOREIGN KEY (`leader_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `fk_tm_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
