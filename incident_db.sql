-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 28, 2026 at 08:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.3.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `incident_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_table` varchar(50) NOT NULL,
  `target_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `target_table`, `target_id`, `description`, `ip_address`, `created_at`) VALUES
(6, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 12:37:31'),
(7, 2, 'User Login', 'users', 2, 'Admin User logged in.', '::1', '2026-03-28 12:38:57'),
(8, 2, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 3', '::1', '2026-03-28 12:39:39'),
(9, 2, 'Updated Incident', 'incidents', 1, 'Status: Open → Resolved | Priority: High → High', '::1', '2026-03-28 12:39:57'),
(10, 2, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 3', '::1', '2026-03-28 13:03:30'),
(11, 2, 'Exported Incidents', 'incidents', NULL, 'Format: csv | Status:  | Count: 3', '::1', '2026-03-28 13:03:31'),
(12, 3, 'User Logout', 'users', 3, 'Test User logged out.', '::1', '2026-03-28 13:14:53'),
(13, 2, 'User Logout', 'users', 2, 'Admin User logged out.', '::1', '2026-03-28 13:14:59'),
(14, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 13:48:24'),
(15, 3, 'Created Incident', 'incidents', 4, 'User Test User created incident: Malware Attack', '::1', '2026-03-28 13:50:08'),
(16, 1, 'User Login', 'users', 1, 'Super Admin logged in.', '::1', '2026-03-28 13:50:56'),
(17, 1, 'Updated Incident', 'incidents', 4, 'Status: Open → In Progress | Priority: Medium → Medium', '::1', '2026-03-28 13:51:22'),
(18, 1, 'User Login', 'users', 1, 'Super Admin logged in.', '::1', '2026-03-28 15:42:22'),
(19, 1, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 4', '::1', '2026-03-28 15:43:06'),
(20, 1, 'Updated Incident', 'incidents', 4, 'Status: In Progress → In Progress | Priority: Medium → Medium', '::1', '2026-03-28 15:43:38'),
(21, 1, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 4', '::1', '2026-03-28 15:44:24'),
(22, 1, 'Blocked User', 'users', 3, 'Test User blocked', '::1', '2026-03-28 15:44:43'),
(23, 1, 'User Logout', 'users', 1, 'Super Admin logged out.', '::1', '2026-03-28 15:44:53'),
(24, 1, 'User Login', 'users', 1, 'Super Admin logged in.', '::1', '2026-03-28 15:45:33'),
(25, 1, 'Unblocked User', 'users', 3, 'Test User unblocked', '::1', '2026-03-28 15:45:38'),
(26, 1, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 4', '::1', '2026-03-28 15:45:41'),
(27, 1, 'User Logout', 'users', 1, 'Super Admin logged out.', '::1', '2026-03-28 15:45:44'),
(28, 2, 'User Login', 'users', 2, 'Admin User logged in.', '::1', '2026-03-28 15:45:56'),
(29, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 15:46:11'),
(30, 2, 'Updated Incident', 'incidents', 2, 'Status: In Progress → Resolved | Priority: Critical → Critical', '::1', '2026-03-28 15:47:21'),
(31, 2, 'User Logout', 'users', 2, 'Admin User logged out.', '::1', '2026-03-28 15:52:11'),
(32, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 15:52:19'),
(33, 3, 'User Logout', 'users', 3, 'Test User logged out.', '::1', '2026-03-28 15:52:26'),
(34, 2, 'User Login', 'users', 2, 'Admin User logged in.', '::1', '2026-03-28 15:52:33'),
(35, 2, 'Exported Incidents', 'incidents', NULL, 'Format: pdf | Status:  | Count: 4', '::1', '2026-03-28 16:15:42'),
(36, 2, 'Exported Incidents', 'incidents', NULL, 'Format: csv | Status:  | Count: 4', '::1', '2026-03-28 16:15:52'),
(37, 2, 'Exported Incidents', 'incidents', NULL, 'Format: pdf | Status:  | Count: 4', '::1', '2026-03-28 16:16:35'),
(38, 2, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 4', '::1', '2026-03-28 16:16:59'),
(39, 2, 'User Login', 'users', 2, 'Admin User logged in.', '::1', '2026-03-28 16:21:11'),
(40, 2, 'User Logout', 'users', 2, 'Admin User logged out.', '::1', '2026-03-28 16:21:31'),
(41, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 16:21:39'),
(42, 3, 'User Logout', 'users', 3, 'Test User logged out.', '::1', '2026-03-28 16:22:08'),
(43, 1, 'User Login', 'users', 1, 'Super Admin logged in.', '::1', '2026-03-28 16:22:12'),
(44, 1, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 4', '::1', '2026-03-28 16:22:34'),
(45, 1, 'Created User', 'users', 4, 'Added user: Chirag Sompura (user)', '::1', '2026-03-28 16:25:56'),
(46, 4, 'User Login', 'users', 4, 'Chirag Sompura logged in.', '::1', '2026-03-28 16:26:12'),
(47, 4, 'User Logout', 'users', 4, 'Chirag Sompura logged out.', '::1', '2026-03-28 16:26:19'),
(48, 1, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 4', '::1', '2026-03-28 16:32:03'),
(49, 1, 'Exported Incidents', 'incidents', NULL, 'Format:  | Status:  | Count: 4', '::1', '2026-03-28 16:32:06'),
(50, 1, 'User Logout', 'users', 1, 'Super Admin logged out.', '::1', '2026-03-28 16:32:30'),
(51, NULL, 'Failed Login Attempt', 'users', NULL, 'Email tried: admin@system.com', '::1', '2026-03-28 16:32:38'),
(52, 2, 'User Login', 'users', 2, 'Admin User logged in.', '::1', '2026-03-28 16:32:48'),
(53, NULL, 'Failed Login Attempt', 'users', NULL, 'Email tried: user@system.com', '::1', '2026-03-28 16:33:27'),
(54, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 16:33:44'),
(55, 3, 'Created Incident', 'incidents', 5, 'User Test User created incident: Unauthorized Login Attempt', '::1', '2026-03-28 16:34:43'),
(56, 2, 'Bulk Assigned Incidents', 'incidents', NULL, 'Assigned 1 incidents to admin #2', '::1', '2026-03-28 16:35:19'),
(57, 2, 'Updated Incident', 'incidents', 4, 'Status: In Progress → Resolved | Priority: Medium → Medium', '::1', '2026-03-28 17:02:07'),
(58, 2, 'User Login', 'users', 2, 'Admin User logged in.', '::1', '2026-03-28 17:11:14'),
(59, 2, 'Updated Incident', 'incidents', 4, 'Status: Resolved → Resolved | Priority: Medium → Medium', '::1', '2026-03-28 17:11:27'),
(60, 2, 'Updated Incident', 'incidents', 4, 'Status: Resolved → In Progress | Priority: Medium → Medium', '::1', '2026-03-28 17:11:53'),
(61, 2, 'Updated Incident', 'incidents', 4, 'Status: In Progress → Resolved | Priority: Medium → Medium', '::1', '2026-03-28 17:15:57'),
(62, 2, 'Updated Incident', 'incidents', 4, 'Status: Resolved → Resolved | Priority: Medium → Medium', '::1', '2026-03-28 17:16:08'),
(63, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 17:16:22'),
(64, 2, 'Updated Incident', 'incidents', 4, 'Status: Resolved → Resolved | Priority: Medium → Medium', '::1', '2026-03-28 17:23:50'),
(65, 2, 'User Logout', 'users', 2, 'Admin User logged out.', '::1', '2026-03-28 17:24:04'),
(66, 1, 'User Login', 'users', 1, 'Super Admin logged in.', '::1', '2026-03-28 17:24:17'),
(67, 1, 'User Logout', 'users', 1, 'Super Admin logged out.', '::1', '2026-03-28 17:28:40'),
(68, 2, 'User Login', 'users', 2, 'Admin User logged in.', '::1', '2026-03-28 17:28:58'),
(69, 2, 'Updated Incident', 'incidents', 4, 'Status: Resolved → In Progress | Priority: Medium → Medium', '::1', '2026-03-28 17:29:05'),
(70, 2, 'Updated Incident', 'incidents', 2, 'Status: Resolved → In Progress | Priority: Critical → Critical', '::1', '2026-03-28 17:29:28'),
(71, 2, 'Updated Incident', 'incidents', 4, 'Status: In Progress → Open | Priority: Medium → Medium', '::1', '2026-03-28 17:35:33'),
(72, 3, 'User Login', 'users', 3, 'Test User logged in.', '::1', '2026-03-28 17:35:48'),
(73, 2, 'Updated Incident', 'incidents', 4, 'Status: Open → Closed | Priority: Medium → Medium', '::1', '2026-03-28 17:35:56'),
(74, 3, 'User Logout', 'users', 3, 'Test User logged out.', '::1', '2026-03-28 17:36:16'),
(75, 2, 'Updated Incident', 'incidents', 4, 'Status: Closed → Closed | Priority: Medium → Medium', '::1', '2026-03-28 17:39:00'),
(76, 2, 'User Logout', 'users', 2, 'Admin User logged out.', '::1', '2026-03-28 19:19:46');

-- --------------------------------------------------------

--
-- Table structure for table `incidents`
--

CREATE TABLE `incidents` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` enum('Phishing','Malware','Ransomware','Unauthorized Access','Data Breach','DDoS','Insider Threat','Other') NOT NULL,
  `priority` enum('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `evidence_path` varchar(500) DEFAULT NULL,
  `incident_date` date NOT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `incidents`
--

INSERT INTO `incidents` (`id`, `user_id`, `assigned_to`, `title`, `description`, `category`, `priority`, `status`, `evidence_path`, `incident_date`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 3, 2, 'Phishing Email Received', 'Received a suspicious email asking for credentials from hr@fakecompany.com', 'Phishing', 'High', 'Resolved', NULL, '2026-03-28', '2026-03-28 12:39:57', '2026-03-28 12:33:08', '2026-03-28 12:39:57'),
(2, 3, 2, 'Malware on Workstation', 'Antivirus detected Trojan on workstation PC-042 in Finance department.', 'Malware', 'Critical', 'In Progress', NULL, '2026-03-26', '2026-03-28 15:47:21', '2026-03-28 12:33:08', '2026-03-28 17:29:28'),
(3, 3, NULL, 'Unauthorized Login Attempt', 'Multiple failed login attempts from IP 192.168.1.101 after office hours.', 'Unauthorized Access', 'Medium', 'Resolved', NULL, '2026-03-23', '2026-03-28 12:33:08', '2026-03-28 12:33:08', '2026-03-28 12:33:08'),
(4, 3, 2, 'Malware Attack', 'There is a Malware attack.', 'Malware', 'Medium', 'Closed', 'ev_69c78f38226b83.14659329.pdf', '2026-03-28', '2026-03-28 17:15:57', '2026-03-28 13:50:08', '2026-03-28 17:39:00'),
(5, 3, 2, 'Unauthorized Login Attempt', 'Multiple failed login attempts from IP 192.168.1.101 after office hours.', 'Data Breach', 'High', 'Open', NULL, '2026-03-28', NULL, '2026-03-28 16:34:43', '2026-03-28 16:35:19');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `incident_id` int(10) UNSIGNED NOT NULL,
  `message` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `incident_id`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 1, 'Your incident \"Phishing Email Received\" status changed from Open to Resolved.', 1, '2026-03-28 12:39:57'),
(2, 3, 4, 'Your incident \"Malware Attack\" status changed from Open to In Progress.', 1, '2026-03-28 13:51:22'),
(3, 3, 2, 'Your incident \"Malware on Workstation\" status changed from In Progress to Resolved.', 1, '2026-03-28 15:47:21'),
(4, 3, 4, 'Your incident \"Malware Attack\" status changed from In Progress to Resolved.', 1, '2026-03-28 17:02:07'),
(5, 3, 4, 'Your incident \"Malware Attack\" status changed from Resolved to In Progress.', 1, '2026-03-28 17:11:53'),
(6, 3, 4, 'Your incident \"Malware Attack\" status changed from In Progress to Resolved.', 1, '2026-03-28 17:15:57'),
(7, 3, 4, 'Your incident \"Malware Attack\" status changed from Resolved to In Progress.', 1, '2026-03-28 17:29:05'),
(8, 3, 2, 'Your incident \"Malware on Workstation\" status changed from Resolved to In Progress.', 1, '2026-03-28 17:29:28'),
(9, 3, 4, 'Your incident \"Malware Attack\" status changed from In Progress to Open.', 1, '2026-03-28 17:35:33'),
(10, 3, 4, 'Your incident \"Malware Attack\" status changed from Open to Closed.', 1, '2026-03-28 17:35:56');

-- --------------------------------------------------------

--
-- Table structure for table `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(512) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','superadmin') NOT NULL DEFAULT 'user',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `is_blocked`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'superadmin@system.com', '$2y$10$szP9WroEyaHMm0hbAqXrx.PkJkcnshflT2ak6BxbB.zCmuKrJgxPa', 'superadmin', 0, '2026-03-28 12:33:08', '2026-03-28 12:33:08'),
(2, 'Admin User', 'admin@system.com', '$2y$10$szP9WroEyaHMm0hbAqXrx.PkJkcnshflT2ak6BxbB.zCmuKrJgxPa', 'admin', 0, '2026-03-28 12:33:08', '2026-03-28 12:33:08'),
(3, 'Test User', 'user@system.com', '$2y$10$szP9WroEyaHMm0hbAqXrx.PkJkcnshflT2ak6BxbB.zCmuKrJgxPa', 'user', 0, '2026-03-28 12:33:08', '2026-03-28 15:45:38'),
(4, 'Chirag Sompura', 'chiragsompura0881@gmail.com', '$2y$12$a0AeI2c8qFFaJQyc0svOyOV4tJElLyTLm5Yeo8bGbxJ79G.7sv9xm', 'user', 0, '2026-03-28 16:25:56', '2026-03-28 16:25:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_created` (`created_at`);

--
-- Indexes for table `incidents`
--
ALTER TABLE `incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_incidents_user` (`user_id`),
  ADD KEY `idx_incidents_status` (`status`),
  ADD KEY `idx_incidents_category` (`category`),
  ADD KEY `idx_incidents_priority` (`priority`),
  ADD KEY `idx_incidents_assigned` (`assigned_to`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `incident_id` (`incident_id`),
  ADD KEY `idx_notifications_user` (`user_id`);

--
-- Indexes for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `incidents`
--
ALTER TABLE `incidents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `incidents`
--
ALTER TABLE `incidents`
  ADD CONSTRAINT `incidents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `incidents_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`incident_id`) REFERENCES `incidents` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD CONSTRAINT `refresh_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
