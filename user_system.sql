-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 14, 2025 at 01:48 AM
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
-- Database: `user_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `fullName` varchar(100) NOT NULL,
  `adminID` varchar(30) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phoneNumber` varchar(30) DEFAULT NULL,
  `department` varchar(80) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `fullName`, `adminID`, `email`, `phoneNumber`, `department`, `password`, `gender`, `created_at`) VALUES
(1, 'Jose Reyes', '1237648392', 'josereyes@gmail.com', '09123456789', 'Engineering', '$2y$10$SqTpXCzYqD9v6G6WGKu73eW6vDQwMNdyWaGd8NIzMJ0nFWnHFpo1G', 'Male', '2025-10-06 08:59:02'),
(2, 'Maria Santos ', '1235829461', 'mariasantos@gmail.com', '09876543210', 'Architecture', '$2y$10$SPU.biTSpx.BqKTWDPgWkOEwyLSGl8xqE8NIYcdRUOiZkVkrgvnAS', 'Male', '2025-10-06 09:00:00'),
(3, 'Juan Dela Cruz', '1239014756', 'juandelacruz@gmail.com', '09234567891', 'Information Technology', '$2y$10$z/wOll2RyPuUY/b.Soyv/e3cd9nNYB1wbCSLWoBxVRumb75DjoQdq', 'Male', '2025-10-06 09:00:53'),
(4, 'Isabel Fernandez', '1238461729', 'isabelfernandez@gmail.com', '09543219876', 'Business Administration', '$2y$10$biF7scXnrcXuNVElkMI/VOy5CxDkftR0qX3cBIQSE.tY6ntTlDNiy', 'Male', '2025-10-06 09:01:48'),
(5, 'Miguel Ramos ', '1234762851', 'miguelramos@gmail.com', '09567891234', 'Engineering', '$2y$10$pYIZHwhCym7llMO8jrnJ3O/chlm00epmY4qTW/7bzWxt8BXDDv.Ya', 'Male', '2025-10-06 09:02:34'),
(15, 'Jake Paul', '1237857356', 'jakepaul@gmail.com', '09437645752', 'Engineering', '$2y$10$x.t7.VACF4eJJ8c8qH85yuyV0JHdHZ8X.mm3inOOMf0mrEu85BfWi', 'Male', '2025-11-13 18:12:00');

-- --------------------------------------------------------

--
-- Table structure for table `admin_students`
--

CREATE TABLE `admin_students` (
  `admin_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_students`
--

INSERT INTO `admin_students` (`admin_id`, `student_id`, `assigned_at`) VALUES
(1, 1, '2025-11-13 19:42:17'),
(1, 2, '2025-11-13 19:42:33'),
(1, 3, '2025-11-13 19:42:35'),
(1, 4, '2025-11-13 19:42:38'),
(1, 5, '2025-11-13 19:34:09'),
(2, 1, '2025-11-11 12:48:45'),
(3, 1, '2025-11-13 23:43:05'),
(4, 1, '2025-11-11 12:50:17'),
(5, 1, '2025-11-11 12:51:22'),
(15, 1, '2025-11-13 18:27:06');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendanceID` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') DEFAULT 'Present',
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendanceID`, `admin_id`, `studentID`, `date`, `status`, `remarks`) VALUES
(12, 1, 5, '2025-10-01', 'Present', ''),
(13, 1, 5, '2025-10-02', 'Present', ''),
(14, 1, 5, '2025-10-03', 'Present', ''),
(15, 1, 5, '2025-10-06', 'Late', ''),
(16, 1, 5, '2025-10-07', 'Present', ''),
(18, 1, 5, '2025-10-08', 'Present', ''),
(19, 1, 5, '2025-10-09', 'Present', ''),
(20, 1, 5, '2025-10-10', 'Absent', ''),
(21, 1, 5, '2025-10-13', 'Present', ''),
(22, 1, 5, '2025-10-14', 'Late', ''),
(23, 1, 5, '2025-10-15', 'Present', ''),
(24, 1, 1, '2025-10-01', 'Present', ''),
(25, 1, 1, '2025-10-02', 'Present', ''),
(26, 1, 1, '2025-10-03', 'Present', ''),
(27, 1, 1, '2025-10-06', 'Present', ''),
(28, 1, 1, '2025-10-07', 'Late', ''),
(29, 1, 1, '2025-10-08', 'Present', ''),
(30, 1, 1, '2025-10-09', 'Absent', ''),
(31, 1, 1, '2025-10-10', 'Present', ''),
(32, 1, 1, '2025-10-13', 'Late', ''),
(33, 1, 1, '2025-10-14', 'Present', ''),
(34, 1, 1, '2025-10-15', 'Present', ''),
(35, 1, 2, '2025-10-01', 'Present', ''),
(36, 1, 2, '2025-10-02', 'Present', ''),
(37, 1, 2, '2025-10-03', 'Present', ''),
(38, 1, 2, '2025-10-06', 'Late', ''),
(42, 1, 2, '2025-10-07', 'Present', ''),
(43, 1, 2, '2025-10-08', 'Late', ''),
(44, 1, 2, '2025-10-09', 'Present', ''),
(45, 1, 2, '2025-10-10', 'Present', ''),
(46, 1, 2, '2025-10-13', 'Present', ''),
(47, 1, 2, '2025-10-14', 'Present', ''),
(48, 1, 2, '2025-10-15', 'Absent', ''),
(49, 1, 3, '2025-10-01', 'Present', ''),
(50, 1, 3, '2025-10-02', 'Present', ''),
(51, 1, 3, '2025-10-03', 'Present', ''),
(52, 1, 3, '2025-10-06', 'Late', ''),
(53, 1, 3, '2025-10-07', 'Present', ''),
(54, 1, 3, '2025-10-08', 'Present', ''),
(55, 1, 3, '2025-10-09', 'Absent', ''),
(56, 1, 3, '2025-10-10', 'Present', ''),
(57, 1, 3, '2025-10-13', 'Present', ''),
(58, 1, 3, '2025-10-14', 'Present', ''),
(59, 1, 3, '2025-10-15', 'Present', ''),
(60, 1, 4, '2025-10-01', 'Present', ''),
(61, 1, 4, '2025-10-02', 'Present', ''),
(62, 1, 4, '2025-10-03', 'Present', ''),
(63, 1, 4, '2025-10-06', 'Present', ''),
(64, 1, 4, '2025-10-07', 'Present', ''),
(65, 1, 4, '2025-10-08', 'Present', ''),
(66, 1, 4, '2025-10-09', 'Present', ''),
(67, 1, 4, '2025-10-10', 'Present', ''),
(68, 1, 4, '2025-10-13', 'Present', ''),
(69, 1, 4, '2025-10-14', 'Present', ''),
(70, 1, 4, '2025-10-15', 'Present', 'good job'),
(71, 2, 1, '2025-10-01', 'Present', '');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `gradeID` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `studentID` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `grade` decimal(5,2) NOT NULL,
  `date_recorded` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`gradeID`, `admin_id`, `studentID`, `subject`, `grade`, `date_recorded`) VALUES
(30, 5, 1, 'Introduction to Computing', 91.00, '2025-11-11 13:02:04'),
(31, 5, 1, 'Purposive Communication', 95.00, '2025-11-11 13:39:11'),
(32, 5, 1, 'Understanding the Self', 96.00, '2025-11-11 13:39:27'),
(33, 5, 1, 'Mathematics in the Modern World', 92.00, '2025-11-11 13:40:06'),
(34, 5, 1, 'Ethics', 96.00, '2025-11-11 13:47:41'),
(35, 5, 1, 'Discrete Mathematics', 89.00, '2025-11-11 13:47:55'),
(36, 5, 1, 'Human-Computer Interaction', 93.00, '2025-11-11 13:48:07'),
(37, 5, 1, 'Data Structures and Algorithms', 91.00, '2025-11-11 13:48:24'),
(48, 15, 1, 'TEST', 90.00, '2025-11-13 18:27:21'),
(49, 1, 5, 'Computer Programming 1', 90.00, '2025-11-13 20:44:44'),
(50, 1, 5, 'Data Structures and Algorithms', 92.00, '2025-11-13 20:45:03'),
(51, 1, 5, 'Web Systems and Technologies', 93.00, '2025-11-13 20:45:16'),
(52, 1, 5, 'Systems Analysis and Design', 96.00, '2025-11-13 20:45:32'),
(53, 1, 5, 'Ethics', 96.00, '2025-11-13 20:45:42'),
(54, 1, 1, 'Computer Programming 1', 93.00, '2025-11-13 20:46:13'),
(55, 1, 1, 'Data Structures and Algorithms', 91.00, '2025-11-13 20:46:21'),
(56, 1, 1, 'Web Systems and Technologies', 93.00, '2025-11-13 20:47:01'),
(57, 1, 1, 'Systems Analysis and Design', 92.00, '2025-11-13 20:47:08'),
(58, 1, 1, 'Ethics', 96.00, '2025-11-13 20:47:18'),
(59, 1, 2, 'Computer Programming 1', 93.00, '2025-11-13 20:47:27'),
(60, 1, 2, 'Data Structures and Algorithms', 90.00, '2025-11-13 20:47:37'),
(61, 1, 2, 'Web Systems and Technologies', 93.00, '2025-11-13 20:47:48'),
(62, 1, 2, 'Systems Analysis and Design', 92.00, '2025-11-13 20:48:01'),
(63, 1, 2, 'Ethics', 93.00, '2025-11-13 20:48:12'),
(64, 1, 3, 'Computer Programming 1', 90.00, '2025-11-13 20:49:47'),
(65, 1, 3, 'Data Structures and Algorithms', 90.00, '2025-11-13 20:49:55'),
(66, 1, 3, 'Web Systems and Technologies', 93.00, '2025-11-13 20:51:01'),
(67, 1, 3, 'Systems Analysis and Design', 89.00, '2025-11-13 20:51:09'),
(68, 1, 3, 'Ethics', 92.00, '2025-11-13 20:51:17'),
(69, 1, 4, 'Computer Programming 1', 88.00, '2025-11-13 20:51:42'),
(70, 1, 4, 'Data Structures and Algorithms', 93.00, '2025-11-13 20:51:54'),
(71, 1, 4, 'Web Systems and Technologies', 92.00, '2025-11-13 20:52:05'),
(72, 1, 4, 'Systems Analysis and Design', 87.00, '2025-11-13 20:52:12'),
(73, 1, 4, 'Ethics', 92.00, '2025-11-13 20:52:20'),
(74, 2, 1, 'Ethics', 98.00, '2025-11-13 20:58:31');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `user_type` enum('admin','student') NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `user_type`, `expires_at`) VALUES
(7, 'josereyes@gmail.com', 'cc047801f264c170e514f777d7728fe7a5963df2ff90a54b005226bf009906dd', 'admin', '2025-10-12 10:15:08'),
(20, 'jardyyygaming@gmail.com', '29f9e6e1d69420e188ab357f0156cdf915fdd0424f723b5a0c02c64697d2cde9', 'admin', '2025-10-12 10:43:11');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `fullName` varchar(100) NOT NULL,
  `studentNumber` varchar(30) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phoneNumber` varchar(30) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `province` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(30) DEFAULT NULL,
  `has_grades_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `has_attendance_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `has_attendance_grade` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `admin_id`, `fullName`, `studentNumber`, `email`, `phoneNumber`, `city`, `province`, `password`, `gender`, `has_grades_enabled`, `has_attendance_enabled`, `has_attendance_grade`, `created_at`) VALUES
(1, 1, 'James Edward Valles', '3217849562', 'jamesedwardvalles04@gmail.com', '09987654321', 'Quezon City', 'Bohol', '$2y$10$CGBmcAmMHDJS89X3P4aWh.ySWwnMLyIdOqDgp8Wc7cr8ueQumrEDy', 'Male', 1, 1, 0, '2025-10-06 09:07:21'),
(2, 1, 'Kurt Flores', '3215630947', 'kurtflores@gmail.com', '09345678912', 'Manila', 'Davao', '$2y$10$AuqscAU5rF8cXNcfJ6bwr.u8UZO5gCOuSNx3Jkb2.2BM8wXsIYjRa', 'Male', 1, 1, 0, '2025-10-06 09:08:06'),
(3, 1, 'Sebastian Rivera', '3219482736', 'sebrivera@gmail.com', '09765432198', 'Manila', 'Manila', '$2y$10$AFfXEfDrLxjm20es6lpekOYww1gXq4x5uumSDmaxFwyf57DtiU32O', 'Male', 1, 1, 0, '2025-10-06 09:08:46'),
(4, 1, 'Sofia Mendoza', '3213076854', 'sofiamendoza@gmail.com', '09456789123', 'Quezon City', 'Cebu', '$2y$10$y6buXkQOHnLDuZ3HdXcje.auGjTpxoowpz1p8zpAS/6fntonNCgmu', 'Female', 1, 1, 0, '2025-10-06 09:09:43'),
(5, 1, 'Ana Garcia', '3218594621', 'anagarcia@gmail.com', '09654321987', 'Taguig', 'Pampanga', '$2y$10$o53h406sdQcMchVOeE5bLuPSJ//LiEB./2qeQ7lJsV7RdKtD8Vv0q', 'Female', 1, 1, 0, '2025-10-06 09:10:37');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `adminID` (`adminID`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_students`
--
ALTER TABLE `admin_students`
  ADD PRIMARY KEY (`admin_id`,`student_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendanceID`),
  ADD KEY `studentID` (`studentID`),
  ADD KEY `fk_attendance_admin` (`admin_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`gradeID`),
  ADD KEY `studentID` (`studentID`),
  ADD KEY `fk_grades_admin` (`admin_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `studentNumber` (`studentNumber`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_students_admin` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `gradeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attendance_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `fk_grades_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`studentID`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
