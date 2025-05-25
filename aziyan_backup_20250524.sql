-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2025 at 08:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `aziyan`
--

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_leases`
-- (See below for the actual view)
--
CREATE TABLE `active_leases` (
`id` int(11)
,`asset_code` varchar(50)
,`property_name` varchar(100)
,`block` varchar(50)
,`kebele` varchar(50)
,`lot_number` varchar(50)
,`owner_id` int(11)
,`owner_name` varchar(100)
,`property_type` varchar(100)
,`size` decimal(10,2)
,`annual_lease_rate` decimal(10,2)
,`status` enum('Active','Inactive','Under Maintenance','Leased','Sold','Confiscated')
,`registration_date` date
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_code` varchar(50) NOT NULL,
  `property_name` varchar(100) DEFAULT NULL,
  `block` varchar(50) DEFAULT NULL,
  `kebele` varchar(50) DEFAULT NULL,
  `lot_number` varchar(50) DEFAULT NULL,
  `owner_id` int(11) DEFAULT NULL,
  `owner_name` varchar(100) DEFAULT NULL,
  `property_type_id` int(11) NOT NULL,
  `construction_type_id` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `size` decimal(10,2) NOT NULL,
  `annual_lease_rate` decimal(10,2) NOT NULL,
  `status` enum('Active','Inactive','Under Maintenance','Leased','Sold','Confiscated') NOT NULL DEFAULT 'Active',
  `registration_date` date NOT NULL,
  `construction_type` varchar(100) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `tax_identification_number` varchar(50) DEFAULT NULL,
  `has_electricity` tinyint(1) DEFAULT 0,
  `has_water` tinyint(1) DEFAULT 0,
  `has_sewage` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `property_type` varchar(100) NOT NULL,
  `document_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assets`
--

INSERT INTO `assets` (`id`, `asset_code`, `property_name`, `block`, `kebele`, `lot_number`, `owner_id`, `owner_name`, `property_type_id`, `construction_type_id`, `address`, `size`, `annual_lease_rate`, `status`, `registration_date`, `construction_type`, `registration_number`, `tax_identification_number`, `has_electricity`, `has_water`, `has_sewage`, `created_at`, `updated_at`, `property_type`, `document_path`) VALUES
(1, 'AST-001', 'Abebe Residence', 'Block A', 'Kebele 01', 'L001', 42, 'አቦበከር  ሸምሱ', 1, 1, '123 Main St, Addis Ababa', 500.00, 1500.00, 'Leased', '2023-01-15', NULL, 'REG-001', 'TIN-001', 1, 1, 0, '2025-04-11 02:37:36', '2025-04-17 16:57:13', '', NULL),
(2, 'AST-002', 'Mulugeta Shop', 'Block B', '02', 'LOT-002', 41, 'ተማም ኢብራሃም', 1, 6, '456 Market Rd, Addis Ababa', 200.00, 1200.00, 'Leased', '2023-02-20', NULL, 'REG-002', 'TIN-002', 1, 0, 1, '2025-04-11 02:37:36', '2025-04-17 16:54:54', '', NULL),
(3, 'AST-003', 'Genet Farm', 'Block C', '03', 'LOT-003', 4, 'ራህስ ተሾሙ', 1, NULL, '789 Rural Area, Addis Ababa', 500.00, 1800.00, 'Leased', '2023-03-10', NULL, 'REG-003', 'TIN-003', 0, 1, 0, '2025-04-11 02:37:36', '2025-04-17 17:04:51', '', NULL),
(4, 'AST-004', 'Solomon Office', 'Block A', '01', 'LOT-004', 4, 'Solomon Desta', 1, 2, '101 Business Park, Addis Ababa', 180.75, 1600.00, 'Inactive', '2023-04-05', NULL, 'REG-004', 'TIN-004', 1, 1, 1, '2025-04-11 02:37:36', '2025-04-12 14:13:53', '', NULL),
(5, 'AST-005', 'Tigist House', 'Block D', '04', 'LOT-005', 5, 'Tigist Lemma', 1, 3, '202 Residential Area, Addis Ababa', 120.25, 1700.00, 'Leased', '2023-05-12', NULL, 'REG-005', 'TIN-005', 1, 0, 0, '2025-04-11 02:37:36', '2025-04-11 21:26:56', '', NULL),
(10, 'as12', 'asas', 'blo001', '01', 'plo10', 44, ' ለይላ ተሾሙ', 4, 7, 'duna', 250.00, 3000.00, 'Active', '2025-04-17', '0', 'r121', '121212', 1, 0, 0, '2025-04-17 04:54:26', '2025-04-24 00:27:03', '', ''),
(12, 'as23', 'RR', 'b34', '02', 'pl19', 3, 'ሙሉቀን ወልደሚካኤል', 4, 7, 'shdager', 300.00, 4000.00, 'Active', '2025-04-18', '0', '34', '1000123', 1, 0, 0, '2025-04-17 22:58:40', '2025-04-17 22:58:40', '', ''),
(15, 'aa1', 'RR', 'b13', '02', 'p18', 72, 'abdulhafize seraje', 4, 6, '', 250.00, 2700.00, 'Active', '2025-04-21', '0', '', '', 1, 1, 0, '2025-04-22 00:56:52', '2025-04-22 00:56:52', '', ''),
(18, 'q1', 'RR', 'wg1', '02', '18', 76, 'kemal hasen', 5, 6, 'werabe', 500.00, 2500.00, 'Active', '2025-04-23', '0', '', '', 0, 1, 1, '2025-04-23 23:51:07', '2025-04-23 23:51:07', '', ''),
(20, 'q12', 'RR', 'wg12', '02', '110', 82, NULL, 3, 7, 'werabe', 500.00, 2500.00, 'Active', '0000-00-00', '', 'r21', '000009', 0, 1, 1, '2025-04-24 21:53:07', '2025-04-24 21:53:07', '', ''),
(21, 'b54', 'RR', 'mk1', '01', 'k09', 83, NULL, 5, 3, 'mk', 300.00, 12000.00, 'Active', '0000-00-00', NULL, 'R32', '10000012', 0, 1, 0, '2025-04-26 00:41:51', '2025-04-26 00:41:51', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `asset_transfers`
--

CREATE TABLE `asset_transfers` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `old_owner_id` int(11) DEFAULT NULL,
  `new_owner_id` int(11) NOT NULL,
  `reason_id` int(11) DEFAULT NULL,
  `transferred_by` int(11) NOT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asset_transfers`
--

INSERT INTO `asset_transfers` (`id`, `asset_id`, `old_owner_id`, `new_owner_id`, `reason_id`, `transferred_by`, `transferred_at`) VALUES
(1, 1, 1, 61, 2, 1, '2025-04-17 16:00:34'),
(2, 1, 61, 44, 1, 1, '2025-04-17 16:01:33'),
(3, 2, 2, 41, 2, 1, '2025-04-17 16:54:54'),
(4, 1, 44, 42, 3, 1, '2025-04-17 16:57:13'),
(5, 3, 3, 4, 2, 1, '2025-04-17 17:04:51');

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `error_message` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action_type`, `table_name`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`, `error_message`) VALUES
(1, 1, 'create', 'owners', 62, NULL, '{\"first_name\":\"abdu\",\"last_name\":\"seraj\",\"email\":\"mameserag@gmail.com\",\"phone\":\"+251916273479\",\"id_number\":\"0001\",\"address\":\"werabe\\r\\nb1\",\"status\":\"active\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 02:04:32', NULL),
(2, 1, 'create', 'owners', 62, NULL, '{\"first_name\":\"abdu\",\"last_name\":\"seraj\",\"email\":\"mameserag@gmail.com\",\"phone\":\"+251916273479\",\"id_number\":\"0001\",\"address\":\"werabe\r\nb1\",\"status\":\"active\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 02:04:32', NULL),
(3, 1, 'create', 'assets', 10, NULL, '{\"asset_code\":\"as12\",\"property_name\":\"asas\",\"block\":\"blo001\",\"kebele\":\"01\",\"lot_number\":\"plo10\",\"owner_id\":44,\"owner_name\":\"\",\"property_type_id\":4,\"construction_type_id\":7,\"address\":\"duna\",\"size\":250,\"annual_lease_rate\":3000,\"status\":\"Active\",\"registration_date\":\"2025-04-17\",\"construction_type\":\"stone\",\"registration_number\":\"r121\",\"tax_identification_number\":\"121212\",\"has_electricity\":1,\"has_water\":0,\"has_sewage\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 04:54:26', NULL),
(4, 1, 'transfer', 'assets', 1, NULL, '{\"transfer_id\":1,\"new_owner_id\":61,\"reason_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 16:00:34', NULL),
(5, 1, 'transfer', 'assets', 1, NULL, '{\"transfer_id\":2,\"new_owner_id\":44,\"reason_id\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 16:01:34', NULL),
(6, 1, 'transfer', 'assets', 2, NULL, '{\"transfer_id\":3,\"new_owner_id\":41,\"reason_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 16:54:54', NULL),
(7, 1, 'transfer', 'assets', 1, NULL, '{\"transfer_id\":4,\"new_owner_id\":42,\"reason_id\":3}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 16:57:13', NULL),
(8, 1, 'transfer', 'assets', 3, NULL, '{\"transfer_id\":5,\"new_owner_id\":4,\"reason_id\":2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 17:04:51', NULL),
(9, 1, 'create', 'assets', 12, NULL, '{\"asset_code\":\"as23\",\"property_name\":\"RR\",\"block\":\"b34\",\"kebele\":\"02\",\"lot_number\":\"pl19\",\"owner_id\":3,\"owner_name\":\"ሙሉቀን ወልደሚካኤል\",\"property_type_id\":4,\"construction_type_id\":7,\"address\":\"shdager\",\"size\":300,\"annual_lease_rate\":4000,\"status\":\"Active\",\"registration_date\":\"2025-04-18\",\"construction_type\":\"ከከከከከከከከከከከከከከከ\",\"registration_number\":\"34\",\"tax_identification_number\":\"1000123\",\"document_path\":\"\",\"has_electricity\":1,\"has_water\":0,\"has_sewage\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-17 22:58:40', NULL),
(10, 1, 'create', 'bills', NULL, NULL, '{\"bill_number\":\"BILL-000007\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":500,\"total_amount\":4500.15,\"created_by\":1,\"status\":\"pending\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-19 03:54:44', 'Failed to create bill'),
(11, 1, 'create', 'bills', NULL, NULL, '{\"bill_number\":\"BILL-000008\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":500,\"total_amount\":4500.15,\"created_by\":1,\"status\":\"pending\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-19 03:58:03', 'Failed to create bill'),
(12, 1, 'create', 'bills', NULL, NULL, '{\"bill_number\":\"BILL-000009\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":0,\"total_amount\":4000.15,\"created_by\":1,\"status\":\"pending\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-19 04:02:09', 'Failed to create bill'),
(13, 1, 'create', 'bills', NULL, NULL, '{\"bill_number\":\"BILL-000010\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":0,\"total_amount\":4000.15,\"created_by\":1,\"status\":\"pending\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-19 04:07:45', NULL),
(14, 1, 'create', 'bills', 24, '[]', '{\"bill_number\":\"BILL-000004\",\"lease_id\":2,\"billing_date\":\"2025-04-20\",\"due_date\":\"2025-07-20\",\"base_amount\":4000,\"tax_amount\":100,\"late_fee\":0,\"total_amount\":4100,\"notes\":\"bbbbbbbbbbbbbbbbbbb\",\"created_by\":1,\"status\":\"pending\",\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 01:02:57', NULL),
(15, 1, 'create', 'bills', NULL, NULL, '{\"bill_number\":\"BILL-000004\",\"lease_id\":2,\"billing_date\":\"2025-04-20\",\"due_date\":\"2025-07-20\",\"base_amount\":4000,\"tax_amount\":100,\"late_fee\":0,\"total_amount\":4100,\"created_by\":1,\"status\":\"pending\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 01:02:57', NULL),
(16, 1, 'create', 'bills', NULL, NULL, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 01:05:48', 'Invalid CSRF token'),
(17, 1, 'create', 'lease_agreements', 1, NULL, '{\"asset_id\":12,\"owner_id\":3,\"start_date\":\"2025-04-19\",\"end_date\":\"2026-02-01\",\"monthly_rent\":500,\"payment_terms\":\"annually\",\"status\":\"active\",\"terms_conditions\":\"ajjajajajajajajajajajajajajajajajajajajajajjajajajajaj\",\"lease_term_of_years\":5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 01:31:34', NULL),
(18, 1, 'create', 'lease_agreements', 1, NULL, '{\"asset_id\":12,\"owner_id\":3,\"start_date\":\"2025-04-19\",\"end_date\":\"2026-02-01\",\"monthly_rent\":500,\"payment_terms\":\"annually\",\"status\":\"active\",\"terms_conditions\":\"ajjajajajajajajajajajajajajajajajajajajajajjajajajajaj\",\"lease_term_of_years\":5}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 01:35:08', NULL),
(19, 1, 'create', 'lease_agreements', 6, '[]', '{\"lease_number\":\"LEASE-000001\",\"asset_id\":12,\"owner_id\":3,\"start_date\":\"2025-04-19\",\"end_date\":\"2026-02-01\",\"yearly_rent_rate\":6000,\"payment_terms\":\"annually\",\"status\":\"active\",\"terms_conditions\":\"ajjajajajajajajajajajajajajajajajajajajajajjajajajajaj\",\"lease_term_of_years\":5,\"created_by\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 01:39:17', NULL),
(20, 1, 'create', 'lease_agreements', 6, NULL, '{\"lease_number\":\"LEASE-000001\",\"asset_id\":12,\"owner_id\":3,\"start_date\":\"2025-04-19\",\"end_date\":\"2026-02-01\",\"yearly_rent_rate\":6000,\"payment_terms\":\"annually\",\"status\":\"active\",\"terms_conditions\":\"ajjajajajajajajajajajajajajajajajajajajajajjajajajajaj\",\"lease_term_of_years\":5,\"created_by\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 01:39:18', NULL),
(21, 1, 'delete', 'owners', 43, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 16:44:55', NULL),
(22, 1, 'delete', 'owners', 10, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 16:45:35', NULL),
(23, 1, 'delete', 'owners', 69, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 16:45:44', NULL),
(24, 1, 'delete', 'owners', 49, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-20 16:45:54', NULL),
(25, 1, 'update', 'lease_agreements', 5, '{\"status\":\"expired\"}', '{\"status\":\"active\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-21 20:15:29', NULL),
(26, 1, 'update', 'lease_agreements', 3, '{\"status\":\"active\"}', '{\"status\":\"expired\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-21 20:15:29', NULL),
(27, 1, 'create', 'payments', 22, NULL, '{\"payment_number\":\"PAY-2025-0006\",\"status\":\"completed\",\"notes\":\"\",\"payment_date\":\"2025-04-21\",\"received_by\":1,\"transaction_reference\":null,\"lease_id\":1,\"bill_id\":1,\"amount\":1725,\"payment_method\":\"cash\",\"out_trade_no\":\"PAY_1745272762_4837\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-21 21:59:22', NULL),
(28, 1, 'create', 'payments', 22, NULL, '{\"lease_id\":1,\"bill_id\":1,\"amount\":1725,\"payment_method\":\"cash\",\"payment_date\":\"2025-04-21\",\"status\":\"completed\",\"notes\":\"\",\"received_by\":1,\"out_trade_no\":\"PAY_1745272762_4837\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-21 21:59:23', NULL),
(29, 1, 'create', 'owners', 72, NULL, '{\"first_name\":\"abdulhafize\",\"last_name\":\"seraje\",\"phone\":\"+251916273479\",\"id_number\":null,\"address\":\"werabe\",\"status\":\"active\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 00:56:52', NULL),
(30, 1, 'create', 'assets', 15, NULL, '{\"asset_code\":\"aa1\",\"property_name\":\"RR\",\"block\":\"b13\",\"kebele\":\"02\",\"lot_number\":\"p18\",\"owner_id\":72,\"owner_name\":\"abdulhafize seraje\",\"property_type_id\":4,\"construction_type_id\":6,\"address\":\"\",\"size\":250,\"annual_lease_rate\":2700,\"status\":\"Active\",\"registration_date\":\"2025-04-21\",\"construction_type\":\"\",\"registration_number\":\"\",\"tax_identification_number\":\"\",\"document_path\":\"\",\"has_electricity\":1,\"has_water\":1,\"has_sewage\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 00:56:52', NULL),
(31, 1, 'create', 'owners', 73, NULL, '{\"first_name\":\"abdulhafize\",\"last_name\":\"seraje\",\"phone\":\"+251916273479\",\"id_number\":null,\"address\":\"werabe\",\"status\":\"active\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 00:58:13', NULL),
(32, 1, 'create', 'owners', 74, NULL, '{\"first_name\":\"abdulhafize\",\"last_name\":\"seraje\",\"phone\":\"+251916273479\",\"id_number\":null,\"address\":\"werabe\",\"status\":\"active\",\"is_active\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 01:03:22', NULL),
(33, 1, 'delete', 'owners', 74, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 01:51:51', NULL),
(34, 1, 'delete', 'owners', 62, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 01:52:32', NULL),
(35, 1, 'delete', 'owners', 73, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 01:53:11', NULL),
(36, 1, 'delete', 'owners', 72, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-22 01:53:32', NULL),
(37, 1, 'create', 'owners', 76, NULL, '{\"first_name\":\"kemal\",\"last_name\":\"hasen\",\"address\":\"wegrcha\",\"phone\":\"+251916273479\",\"full_name\":\"kemal hasen\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-23 23:51:07', NULL),
(38, 1, 'create', 'assets', 18, NULL, '{\"asset_code\":\"q1\",\"property_name\":\"RR\",\"block\":\"wg1\",\"kebele\":\"02\",\"lot_number\":\"18\",\"owner_id\":76,\"owner_name\":\"kemal hasen\",\"property_type_id\":5,\"construction_type_id\":6,\"address\":\"werabe\",\"size\":500,\"annual_lease_rate\":2500,\"status\":\"Active\",\"registration_date\":\"2025-04-23\",\"construction_type\":\"\",\"registration_number\":\"\",\"tax_identification_number\":\"\",\"document_path\":\"\",\"has_electricity\":0,\"has_water\":1,\"has_sewage\":1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-23 23:51:08', NULL),
(39, 1, 'create', 'owners', 77, NULL, '{\"first_name\":\"kemal\",\"last_name\":\"hasen\",\"address\":\"wegrcha\",\"phone\":\"+251916273479\",\"full_name\":\"kemal hasen\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-23 23:57:03', NULL),
(40, 1, 'delete', 'owners', 77, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-24 00:09:37', NULL),
(41, 1, 'delete', 'owners', 75, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-24 00:11:59', NULL),
(42, 1, 'delete', 'owners', 47, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-24 00:13:11', NULL),
(43, 1, 'delete', 'owners', 7, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-24 00:13:34', NULL),
(44, 1, 'delete', 'owners', 48, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-24 00:13:55', NULL),
(45, 1, 'delete', 'owners', 45, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-24 00:16:23', NULL),
(46, 1, 'update', 'assets', 10, NULL, '{\"asset_code\":\"as12\",\"property_name\":\"asas\",\"block\":\"blo001\",\"kebele\":\"01\",\"lot_number\":\"plo10\",\"owner_id\":44,\"owner_name\":\" ለይላ ተሾሙ\",\"property_type_id\":4,\"construction_type_id\":7,\"address\":\"duna\",\"size\":250,\"annual_lease_rate\":3000,\"status\":\"Active\",\"registration_date\":\"2025-04-17\",\"construction_type\":\"0\",\"registration_number\":\"r121\",\"tax_identification_number\":\"121212\",\"document_path\":\"\",\"has_electricity\":1,\"has_water\":0,\"has_sewage\":0,\"property_type\":\"Industrial\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-24 00:27:04', NULL),
(47, 1, 'create', 'bills', 26, '[]', '{\"bill_number\":\"BILL-000032\",\"lease_id\":7,\"billing_date\":\"2025-04-25\",\"due_date\":\"2025-05-26\",\"base_amount\":1800,\"tax_amount\":200,\"notes\":\"hhhhhhhhhhhhhhhh\",\"created_by\":1,\"total_amount\":2000,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0,\"owner_id\":82}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-25 04:31:21', NULL),
(48, 1, 'create', 'bills', 27, '[]', '{\"bill_number\":\"BILL-000033\",\"lease_id\":7,\"billing_date\":\"2025-04-25\",\"due_date\":\"2025-05-26\",\"base_amount\":1800,\"tax_amount\":200,\"notes\":\"hhhhhhhhhhhhhhhh\",\"created_by\":1,\"total_amount\":2000,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0,\"owner_id\":82}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-25 04:40:16', NULL),
(49, 3, 'create', 'bills', NULL, '[]', '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-25 16:51:40', NULL),
(50, 1, 'create', 'bills', 28, '[]', '{\"bill_number\":\"BILL-000036\",\"lease_id\":7,\"billing_date\":\"2025-04-25\",\"due_date\":\"2025-05-26\",\"base_amount\":1800,\"tax_amount\":200,\"notes\":\"hhhhhhhhhhhhhhhh\",\"created_by\":3,\"total_amount\":2000,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0,\"owner_id\":82}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-25 16:52:08', NULL),
(51, 1, 'create', 'bills', 29, '[]', '{\"bill_number\":\"BILL-000037\",\"lease_id\":7,\"billing_date\":\"2025-04-25\",\"due_date\":\"2025-05-26\",\"base_amount\":1800,\"tax_amount\":200,\"notes\":\"hhhhhhhhhhhhhhhh\",\"created_by\":3,\"total_amount\":2000,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0,\"owner_id\":82}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-25 16:58:59', NULL),
(52, 3, 'create', 'bills', NULL, '[]', '{\"bill_number\":\"BILL-000041\",\"lease_id\":8,\"billing_date\":\"2025-04-26\",\"due_date\":\"2025-06-28\",\"base_amount\":12000,\"tax_amount\":200,\"notes\":\"bdbbdbddddddddddddd\",\"created_by\":3,\"total_amount\":12200,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-26 01:07:13', NULL),
(53, 3, 'create_failed', 'bills', NULL, '[]', '{\"bill_number\":\"BILL-000042\",\"lease_id\":8,\"billing_date\":\"2025-04-26\",\"due_date\":\"2025-06-28\",\"base_amount\":12000,\"tax_amount\":200,\"notes\":\"bdbbdbddddddddddddd\",\"created_by\":3,\"total_amount\":12200,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-26 01:23:51', NULL),
(54, 3, 'create_failed', 'bills', NULL, '[]', '{\"bill_number\":\"BILL-000043\",\"lease_id\":8,\"billing_date\":\"2025-04-26\",\"due_date\":\"2025-06-28\",\"base_amount\":12000,\"tax_amount\":200,\"notes\":\"bdbbdbddddddddddddd\",\"created_by\":3,\"total_amount\":12200,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-26 01:25:09', NULL),
(55, 3, 'create_failed', 'bills', NULL, '[]', '{\"bill_number\":\"BILL-000044\",\"lease_id\":8,\"billing_date\":\"2025-04-26\",\"due_date\":\"2025-06-28\",\"base_amount\":12000,\"tax_amount\":200,\"notes\":\"bdbbdbddddddddddddd\",\"created_by\":3,\"total_amount\":12200,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-26 01:25:12', NULL),
(56, 3, 'create_failed', 'bills', NULL, '[]', '{\"bill_number\":\"BILL-000045\",\"lease_id\":8,\"billing_date\":\"2025-04-26\",\"due_date\":\"2025-06-28\",\"base_amount\":12000,\"tax_amount\":200,\"notes\":\"bdbbdbddddddddddddd\",\"created_by\":3,\"total_amount\":12200,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-26 01:25:14', NULL),
(57, 3, 'create_failed', 'bills', NULL, '[]', '{\"bill_number\":\"BILL-000046\",\"lease_id\":8,\"billing_date\":\"2025-04-26\",\"due_date\":\"2025-06-28\",\"base_amount\":12000,\"tax_amount\":200,\"notes\":\"bdbbdbddddddddddddd\",\"created_by\":3,\"total_amount\":12200,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-26 01:26:59', NULL),
(58, 3, 'create', 'bills', 30, '[]', '{\"bill_number\":\"BILL-000047\",\"lease_id\":8,\"billing_date\":\"2025-04-26\",\"due_date\":\"2025-06-28\",\"base_amount\":12000,\"tax_amount\":200,\"additional_charges\":0,\"notes\":\"bdbbdbddddddddddddd\",\"created_by\":3,\"total_amount\":12200,\"status\":\"pending\",\"late_fee\":0,\"late_fee_applied\":0,\"late_fee_amount\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0', '2025-04-26 01:39:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bills`
--

CREATE TABLE `bills` (
  `id` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `bill_number` varchar(20) NOT NULL,
  `billing_date` date NOT NULL,
  `due_date` date NOT NULL,
  `base_amount` decimal(15,2) NOT NULL,
  `late_fee` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `late_fee_applied` tinyint(1) DEFAULT 0,
  `late_fee_amount` decimal(10,2) DEFAULT 0.00,
  `additional_charges` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bills`
--

INSERT INTO `bills` (`id`, `lease_id`, `owner_id`, `bill_number`, `billing_date`, `due_date`, `base_amount`, `late_fee`, `tax_amount`, `total_amount`, `status`, `notes`, `created_by`, `created_at`, `updated_at`, `late_fee_applied`, `late_fee_amount`, `additional_charges`) VALUES
(1, 1, 1, 'BILL-001', '2025-02-01', '2025-03-01', 1500.00, 150.00, 75.00, 1725.00, 'paid', 'Annual lease payment', 1, '2025-04-11 02:37:36', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(2, 2, 2, 'BILL-002', '2025-01-01', '2025-02-01', 1200.00, 120.00, 60.00, 1380.00, 'overdue', 'Annual lease payment', 1, '2025-04-11 02:37:36', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(3, 3, 3, 'BILL-003', '2025-02-01', '2025-03-01', 1800.00, 180.00, 90.00, 2070.00, 'overdue', 'Annual lease payment', 1, '2025-04-11 02:37:36', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(4, 4, 4, 'BILL-004', '2025-02-01', '2025-03-01', 1600.00, 160.00, 80.00, 1840.00, 'overdue', 'Annual lease payment', 1, '2025-04-11 02:37:36', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(5, 5, 5, 'BILL-005', '2025-02-01', '2025-03-01', 1700.00, 170.00, 85.00, 1955.00, 'overdue', 'Annual lease payment', 1, '2025-04-11 02:37:36', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(14, 2, 2, 'BILL-000006', '2025-04-19', '2025-04-20', 4000.00, 500.00, 0.15, 4500.15, 'pending', 'dddddddddd', 1, '2025-04-19 03:49:33', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(16, 2, 2, 'BILL-000007', '2025-04-19', '2025-05-19', 4000.00, 500.00, 0.15, 4500.15, 'pending', 'dddddddddd', 1, '2025-04-19 03:54:44', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(18, 2, 2, 'BILL-000008', '2025-04-19', '2025-05-19', 4000.00, 500.00, 0.15, 4500.15, 'pending', 'dddddddddd', 1, '2025-04-19 03:58:02', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(20, 2, 2, 'BILL-000009', '2025-04-19', '2025-05-19', 4000.00, 0.00, 0.15, 4000.15, 'pending', 'dddddddddd', 1, '2025-04-19 04:02:08', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(22, 2, 2, 'BILL-000010', '2025-04-19', '2025-05-19', 4000.00, 0.00, 0.15, 4000.15, 'pending', 'dddddddddd', 1, '2025-04-19 04:07:45', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(24, 2, 2, 'BILL-000004', '2025-04-20', '2025-07-20', 4000.00, 0.00, 100.00, 4100.00, 'pending', 'bbbbbbbbbbbbbbbbbbb', 1, '2025-04-20 01:02:57', '2025-04-23 20:55:49', 0, 0.00, 0.00),
(26, 7, 82, 'BILL-000032', '2025-04-25', '2025-05-26', 1800.00, 0.00, 200.00, 2000.00, 'pending', 'hhhhhhhhhhhhhhhh', 1, '2025-04-25 04:31:21', '2025-04-25 04:31:21', 0, 0.00, 0.00),
(27, 7, 82, 'BILL-000033', '2025-04-25', '2025-05-26', 1800.00, 0.00, 200.00, 2000.00, 'pending', 'hhhhhhhhhhhhhhhh', 1, '2025-04-25 04:40:16', '2025-04-25 04:40:16', 0, 0.00, 0.00),
(28, 7, 82, 'BILL-000036', '2025-04-25', '2025-05-26', 1800.00, 0.00, 200.00, 2000.00, 'pending', 'hhhhhhhhhhhhhhhh', 3, '2025-04-25 16:52:08', '2025-04-25 16:52:08', 0, 0.00, 0.00),
(29, 7, 82, 'BILL-000037', '2025-04-25', '2025-05-26', 1800.00, 0.00, 200.00, 2000.00, 'pending', 'hhhhhhhhhhhhhhhh', 3, '2025-04-25 16:58:59', '2025-04-25 16:58:59', 0, 0.00, 0.00),
(30, 8, 0, 'BILL-000047', '2025-04-26', '2025-06-28', 12000.00, 0.00, 200.00, 12200.00, '', 'bdbbdbddddddddddddd', 3, '2025-04-26 01:39:01', '2025-04-26 01:39:01', 0, 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `bill_files`
--

CREATE TABLE `bill_files` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `bill_history`
-- (See below for the actual view)
--
CREATE TABLE `bill_history` (
`id` int(11)
,`bill_number` varchar(20)
,`total_amount` decimal(15,2)
,`billing_date` date
,`due_date` date
,`status` enum('pending','paid','overdue','cancelled')
,`owner_name` varchar(201)
,`property_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `bill_sequence`
--

CREATE TABLE `bill_sequence` (
  `id` int(11) NOT NULL,
  `last_number` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bill_sequence`
--

INSERT INTO `bill_sequence` (`id`, `last_number`) VALUES
(1, 52);

-- --------------------------------------------------------

--
-- Table structure for table `buildings`
--

CREATE TABLE `buildings` (
  `building_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `building_name` varchar(100) NOT NULL,
  `floor_count` int(11) DEFAULT 1,
  `room_count` int(11) DEFAULT NULL,
  `building_area` decimal(10,2) NOT NULL,
  `construction_type` enum('wood','concrete','mixed') NOT NULL,
  `construction_year` year(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `building_type` enum('Residential','Commercial','Mixed','Other') NOT NULL DEFAULT 'Residential'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buildings`
--

INSERT INTO `buildings` (`building_id`, `property_id`, `building_name`, `floor_count`, `room_count`, `building_area`, `construction_type`, `construction_year`, `description`, `address`, `building_type`) VALUES
(1, 1, 'seniya molle', 10, 20, 50.00, 'wood', '2007', 'rrdrdrrdrdr', '0', 'Residential'),
(2, 2, 'selam', 4, 20, 500.00, 'concrete', '2009', 'hhhhhhhhhhh', '0', 'Commercial');

-- --------------------------------------------------------

--
-- Table structure for table `charges`
--

CREATE TABLE `charges` (
  `id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `charge_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charges`
--

INSERT INTO `charges` (`id`, `bill_id`, `charge_type`, `amount`, `description`, `created_at`) VALUES
(1, 1, 'penalty', 100.00, 'Late payment penalty', '2025-04-17 19:33:47'),
(2, 1, 'penalty', 2000.00, 'you are very late', '2025-04-17 19:59:21'),
(3, 24, 'other', 300.00, 'hhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhhh', '2025-04-21 17:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `construction_types`
--

CREATE TABLE `construction_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `durability_rating` enum('Low','Medium','High','Very High') DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `construction_types`
--

INSERT INTO `construction_types` (`id`, `name`, `description`, `durability_rating`, `created_at`, `updated_at`) VALUES
(1, 'Concrete', 'Reinforced concrete construction', 'High', '2025-04-11 21:26:31', '2025-04-11 21:26:31'),
(2, 'Steel', 'Steel frame construction', 'Very High', '2025-04-11 21:26:31', '2025-04-11 21:26:31'),
(3, 'Wood', 'Timber frame construction', 'Medium', '2025-04-11 21:26:31', '2025-04-11 21:26:31'),
(4, 'Mud', 'Traditional mud construction', 'Low', '2025-04-11 21:26:31', '2025-04-11 21:26:31'),
(5, 'Stone', 'Natural stone construction', 'High', '2025-04-11 21:26:31', '2025-04-11 21:26:31'),
(6, 'Brick', 'Brick masonry construction', 'Medium', '2025-04-11 21:26:31', '2025-04-11 21:26:31'),
(7, 'Composite', 'Mixed construction materials', 'High', '2025-04-11 21:26:31', '2025-04-11 21:26:31'),
(8, 'Prefabricated', 'Factory-made components', 'Medium', '2025-04-11 21:26:31', '2025-04-11 21:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `government_agencies`
--

CREATE TABLE `government_agencies` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `api_endpoint` varchar(255) DEFAULT NULL,
  `reporting_frequency` enum('monthly','quarterly','annually') DEFAULT 'monthly',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `government_reports`
--

CREATE TABLE `government_reports` (
  `report_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `period_month` tinyint(4) NOT NULL,
  `period_year` year(4) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `submission_date` datetime DEFAULT NULL,
  `submission_method` enum('api','manual','email') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`report_data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `government_reports`
--

INSERT INTO `government_reports` (`report_id`, `report_date`, `period_month`, `period_year`, `submitted_by`, `submission_date`, `submission_method`, `reference_number`, `report_data`) VALUES
(1, '2025-04-30', 4, '2025', 1, NULL, 'manual', 'VR-202504-0001', '[{\"room_id\": 2, \"room_number\": \"102\", \"days_vacant\": 45, \"building_name\": \"Main Tower\", \"property_name\": \"Downtown Plaza\", \"address\": \"123 Main St\", \"owner_name\": \"John Doe\", \"owner_id\": \"ID123456\"}]');

-- --------------------------------------------------------

--
-- Table structure for table `late_fee_rules`
--

CREATE TABLE `late_fee_rules` (
  `id` int(11) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `grace_period_days` int(11) NOT NULL,
  `fee_type` enum('fixed','percentage') NOT NULL,
  `fee_value` decimal(10,2) NOT NULL,
  `max_fee` decimal(15,2) DEFAULT NULL,
  `sms_reminder_days` int(11) NOT NULL DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lease_agreements`
--

CREATE TABLE `lease_agreements` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `lease_number` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_rent` decimal(15,2) NOT NULL,
  `yearly_rent_rate` decimal(15,2) DEFAULT NULL,
  `payment_terms` enum('monthly','quarterly','annually') DEFAULT 'monthly',
  `status` enum('active','terminated','expired') DEFAULT 'active',
  `terms_conditions` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lease_term_of_years` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lease_agreements`
--

INSERT INTO `lease_agreements` (`id`, `asset_id`, `owner_id`, `lease_number`, `start_date`, `end_date`, `monthly_rent`, `yearly_rent_rate`, `payment_terms`, `status`, `terms_conditions`, `created_by`, `created_at`, `updated_at`, `lease_term_of_years`) VALUES
(1, 1, 1, 'LEASE-001', '2023-01-01', '2025-12-31', 125.00, 1500.00, '', 'active', 'Standard lease terms', 1, '2025-04-11 02:37:36', '2025-04-21 21:23:25', 3),
(2, 2, 2, 'LEASE-002', '2024-01-01', '2025-12-31', 100.00, 1800.00, '', 'active', 'Standard lease terms', 1, '2025-04-11 02:37:36', '2025-04-21 21:23:25', 2),
(3, 3, 3, 'LEASE-003', '2023-06-01', '2024-06-01', 150.00, 1200.00, '', 'expired', 'Standard lease terms', 1, '2025-04-11 02:37:36', '2025-04-21 21:23:25', 2),
(4, 4, 4, 'LEASE-004', '2024-03-01', '2025-03-01', 133.33, 1600.00, '', 'terminated', 'Standard lease terms', 1, '2025-04-11 02:37:36', '2025-04-21 21:23:25', 1),
(5, 5, 5, 'LEASE-005', '2024-05-01', '2025-05-01', 141.67, 1700.00, '', 'active', 'Standard lease terms', 1, '2025-04-11 02:37:36', '2025-04-21 21:23:25', 1),
(6, 12, 3, 'LEASE-000001', '2025-04-19', '2026-02-01', 0.00, 6000.00, '', 'active', 'ajjajajajajajajajajajajajajajajajajajajajajjajajajajaj', 1, '2025-04-20 01:39:17', '2025-04-21 21:23:25', 5),
(7, 20, 82, 'LSE-2025-000000', '2025-04-23', '2025-04-25', 150.00, 2500.00, 'annually', 'active', 'we can rate', 1, '2025-04-24 21:53:08', '2025-04-24 21:53:08', 8),
(8, 21, 83, 'LSE-2025-000003', '2025-04-06', '2025-04-26', 1000.00, 12000.00, 'annually', 'active', 'ppppppppppppppppppppppp', 3, '2025-04-26 00:41:51', '2025-04-26 00:41:51', 5);

-- --------------------------------------------------------

--
-- Table structure for table `lease_sequence`
--

CREATE TABLE `lease_sequence` (
  `id` int(11) NOT NULL,
  `last_number` int(11) DEFAULT NULL,
  `stub` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lease_sequence`
--

INSERT INTO `lease_sequence` (`id`, `last_number`, `stub`) VALUES
(1, 14, 1),
(2, NULL, 1),
(3, NULL, 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `outstanding_bills`
-- (See below for the actual view)
--
CREATE TABLE `outstanding_bills` (
`id` int(11)
,`bill_number` varchar(20)
,`total_amount` decimal(33,2)
,`late_fee` decimal(15,2)
,`tax_amount` decimal(15,2)
,`additional_charges` decimal(32,2)
,`due_date` date
,`status` enum('pending','paid','overdue','cancelled')
,`lease_id` int(11)
,`owner_id` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `owners`
--

CREATE TABLE `owners` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(201) GENERATED ALWAYS AS (concat(`first_name`,' ',`last_name`)) STORED,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_active` tinyint(1) DEFAULT 1,
  `government_id` varchar(50) DEFAULT NULL,
  `is_registered_landlord` tinyint(1) DEFAULT 0,
  `email` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `owners`
--

INSERT INTO `owners` (`id`, `first_name`, `last_name`, `id_number`, `phone`, `address`, `created_at`, `photo`, `status`, `is_active`, `government_id`, `is_registered_landlord`, `email`) VALUES
(1, 'የማነ', 'ተካልይ', 'ID-001', '+251911223344', '', '2025-04-09 19:24:33', NULL, 'active', 1, NULL, 0, NULL),
(2, 'ሰማይነህ', 'መኮንን', 'ID-002', '+251916273479', '', '2025-04-09 19:24:33', NULL, 'active', 1, NULL, 0, NULL),
(3, 'ሙሉቀን', 'ወልደሚካኤል', 'ID-003', '+251912345678', '', '2025-04-09 19:24:33', NULL, 'active', 1, NULL, 0, NULL),
(4, 'ራህስ', 'ተሾሙ', 'ID-004', '+251944556677', '', '2025-04-09 19:24:33', NULL, 'active', 1, NULL, 0, NULL),
(5, 'አስራስ', 'ገብረሥላሴ', 'ID-005', '+251955667788', '', '2025-04-09 19:24:33', NULL, 'active', 1, NULL, 0, NULL),
(6, 'ብርሃኔ', 'ወልደሚካኤል', 'ID-006', '+251966778899', '', '2025-04-09 19:24:33', NULL, 'active', 1, NULL, 0, NULL),
(41, 'ተማም', 'ኢብራሃም', NULL, '+251911223344', '', '2025-04-09 20:24:00', NULL, 'active', 1, NULL, 0, NULL),
(42, 'አቦበከር', ' ሸምሱ', NULL, '+251922334455', '', '2025-04-09 20:24:00', NULL, 'active', 1, NULL, 0, NULL),
(44, ' ለይላ', 'ተሾሙ', NULL, '+251944556677', '', '2025-04-09 20:24:00', NULL, 'active', 1, NULL, 0, NULL),
(61, 'abdu', 'seraje', '004', '+251916273479', 'werabe', '2025-04-13 20:22:11', NULL, 'active', 1, NULL, 0, NULL),
(82, 'ahmed', 'hakimi', '00024', '+251916273470', 'wegrcha', '2025-04-24 21:53:07', '', 'active', 1, NULL, 0, NULL),
(83, 'hhhhhh', 'llllll', '000092', '+251793456789', 'geme', '2025-04-26 00:41:51', '', 'active', 1, NULL, 0, NULL),
(84, '', '', '0091', '+251916273479', 'duna', '2025-05-25 06:20:11', NULL, 'active', 1, NULL, 0, 'mamama@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `tax_assessment_id` varchar(50) DEFAULT NULL,
  `assessment_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank','mobile') NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `received_by` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment`
--

INSERT INTO `payment` (`payment_id`, `tax_assessment_id`, `assessment_id`, `payment_date`, `amount`, `payment_method`, `receipt_number`, `received_by`, `notes`) VALUES
(1, 'TAX-3', 3, '2025-05-25', 75000.00, 'cash', 'REC-3-1748148404458', 'admin_user', 'bbbbbbbbbbbb');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `lease_id` int(11) NOT NULL,
  `bill_id` int(11) NOT NULL,
  `payment_number` varchar(50) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_expiration_date` date DEFAULT NULL,
  `payment_overdue` tinyint(1) DEFAULT 0,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','check','mobile_money','telebirr','cbe_birr') NOT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `reference_number` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `payment_details` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `lease_id`, `bill_id`, `payment_number`, `payment_date`, `payment_expiration_date`, `payment_overdue`, `amount`, `payment_method`, `status`, `reference_number`, `transaction_reference`, `payment_details`, `notes`, `received_by`, `created_at`, `updated_at`, `completed_at`) VALUES
(1, 0, 1, 'PAY-20250410-1234', '2025-03-01', '2025-03-01', 1, 500.00, 'bank_transfer', 'pending', 'REF-001', 'TXN-12345', NULL, 'Payment for lease renewal', 1, '2025-04-11 02:37:36', '2025-04-20 02:40:20', NULL),
(2, 0, 2, 'PAY-20250410-2345', '2025-03-05', '2025-02-01', 1, 400.00, 'mobile_money', 'pending', 'REF-002', NULL, NULL, NULL, 1, '2025-04-11 02:37:36', '2025-04-20 02:40:20', NULL),
(3, 0, 3, 'PAY-20250410-3456', '2025-03-10', '2025-03-01', 1, 600.00, 'telebirr', 'pending', 'REF-003', NULL, NULL, NULL, 1, '2025-04-11 02:37:36', '2025-04-20 02:40:20', NULL),
(4, 0, 4, 'PAY-20250410-4567', '2025-03-15', '2025-03-01', 1, 300.00, 'cash', 'pending', NULL, NULL, NULL, NULL, 1, '2025-04-11 02:37:36', '2025-04-20 02:40:20', NULL),
(5, 0, 5, 'PAY-20250410-5678', '2025-03-20', '2025-03-01', 1, 200.00, 'check', 'pending', 'REF-005', NULL, NULL, NULL, 1, '2025-04-11 02:37:36', '2025-04-20 02:40:20', NULL),
(6, 0, 1, 'PAY-001', '2023-02-15', '2025-03-01', 1, 125.00, 'cash', 'pending', NULL, NULL, NULL, NULL, 1, '2025-04-11 02:37:36', '2025-04-13 12:28:21', NULL),
(7, 0, 2, 'PAY-002', '2023-03-15', '2025-02-01', 1, 125.00, 'bank_transfer', 'pending', 'REF-001', NULL, NULL, NULL, 1, '2025-04-11 02:37:36', '2025-04-13 12:28:19', NULL),
(8, 0, 3, 'PAY-003', '2023-03-15', '2025-03-01', 1, 100.00, 'mobile_money', 'pending', 'REF-002', NULL, NULL, NULL, 1, '2025-04-11 02:37:36', '2025-04-13 12:28:20', NULL),
(22, 1, 1, 'PAY-2025-0006', '2025-04-21', NULL, 0, 1725.00, 'cash', 'completed', NULL, NULL, NULL, '', 1, '2025-04-21 21:59:22', '2025-04-21 21:59:22', '2025-04-21 13:59:22');

-- --------------------------------------------------------

--
-- Table structure for table `payment_files`
--

CREATE TABLE `payment_files` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `payment_history`
-- (See below for the actual view)
--
CREATE TABLE `payment_history` (
`id` int(11)
,`payment_number` varchar(50)
,`owner_name` varchar(201)
,`property_name` varchar(50)
,`amount` decimal(15,2)
,`payment_date` date
,`payment_method` enum('cash','bank_transfer','check','mobile_money','telebirr','cbe_birr')
);

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `property_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `property_name` varchar(100) NOT NULL,
  `address` text NOT NULL,
  `area` decimal(10,2) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `construction_year` year(4) DEFAULT NULL,
  `valuation` decimal(12,2) DEFAULT NULL,
  `zoning` varchar(100) DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `tax_status` enum('Active','Exempt','Delinquent') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`property_id`, `owner_id`, `type_id`, `property_name`, `address`, `area`, `registration_number`, `construction_year`, `valuation`, `zoning`, `registration_date`, `tax_status`) VALUES
(1, 61, 4, 'helal molle', 'duna', 300.00, '13', '2008', 10000000.00, '1st', '2025-05-23', 'Active'),
(2, 82, 2, 'selam pension', 'duna', 250.00, '12', '2010', 900000.00, '2nd', '2025-05-04', 'Delinquent');

-- --------------------------------------------------------

--
-- Table structure for table `property_files`
--

CREATE TABLE `property_files` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_types`
--

CREATE TABLE `property_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `property_types`
--

INSERT INTO `property_types` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Residential', 'Properties used for living purposes', '2025-04-11 21:26:02', '2025-04-11 21:26:02'),
(2, 'Commercial', 'Properties used for business activities', '2025-04-11 21:26:02', '2025-04-11 21:26:02'),
(3, 'Agricultural', 'Properties used for farming and agriculture', '2025-04-11 21:26:02', '2025-04-11 21:26:02'),
(4, 'Industrial', 'Properties used for manufacturing and production', '2025-04-11 21:26:02', '2025-04-11 21:26:02'),
(5, 'Mixed-Use', 'Properties with both residential and commercial uses', '2025-04-11 21:26:02', '2025-04-11 21:26:02');

-- --------------------------------------------------------

--
-- Table structure for table `reasons`
--

CREATE TABLE `reasons` (
  `id` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reasons`
--

INSERT INTO `reasons` (`id`, `reason`, `is_active`, `created_at`) VALUES
(1, 'Sale', 1, '2025-04-17 05:06:12'),
(2, 'Inheritance', 1, '2025-04-17 05:06:12'),
(3, 'Administrative Correction', 1, '2025-04-17 05:06:12'),
(4, 'Gift', 0, '2025-04-17 05:06:12');

-- --------------------------------------------------------

--
-- Table structure for table `rental_payments`
--

CREATE TABLE `rental_payments` (
  `payment_id` int(11) NOT NULL,
  `contract_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `period_month` tinyint(4) NOT NULL,
  `period_year` year(4) NOT NULL,
  `payment_method` enum('cash','bank','mobile') NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `received_by` varchar(100) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rent_contracts`
--

CREATE TABLE `rent_contracts` (
  `contract_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `tenant_name` varchar(100) NOT NULL,
  `tenant_phone` varchar(20) DEFAULT NULL,
  `tenant_id_number` varchar(50) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','active','terminated','completed') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rent_contracts`
--

INSERT INTO `rent_contracts` (`contract_id`, `room_id`, `tenant_name`, `tenant_phone`, `tenant_id_number`, `start_date`, `end_date`, `monthly_rent`, `deposit_amount`, `status`, `notes`) VALUES
(1, 2, 'kemal', '09123456', '00000012', '2025-05-25', '2026-05-30', 900.00, 500.00, 'active', 'as we ----------');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `room_id` int(11) NOT NULL,
  `building_id` int(11) NOT NULL,
  `type_id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `floor_number` int(11) NOT NULL,
  `area` decimal(10,2) NOT NULL,
  `monthly_rent` decimal(10,2) NOT NULL,
  `is_occupied` tinyint(1) DEFAULT 0,
  `last_occupied_date` date DEFAULT NULL,
  `days_vacant` int(11) DEFAULT 0,
  `last_reported_date` date DEFAULT NULL,
  `reporting_status` enum('reported','pending','non-compliant') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`room_id`, `building_id`, `type_id`, `room_number`, `floor_number`, `area`, `monthly_rent`, `is_occupied`, `last_occupied_date`, `days_vacant`, `last_reported_date`, `reporting_status`) VALUES
(1, 1, 2, '20', 4, 20.00, 1000.00, 1, NULL, 0, NULL, 'pending'),
(2, 1, 1, '19', 4, 15.00, 800.00, 1, NULL, 0, NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `room_types`
--

CREATE TABLE `room_types` (
  `type_id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_types`
--

INSERT INTO `room_types` (`type_id`, `type_name`, `description`) VALUES
(1, 'Single', 'Single occupancy room with basic amenities'),
(2, 'Double', 'Double occupancy room with ensuite bathroom'),
(3, 'Studio', 'Self-contained studio apartment');

-- --------------------------------------------------------

--
-- Table structure for table `signatures_stamps`
--

CREATE TABLE `signatures_stamps` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('stamp','signature') NOT NULL,
  `image` blob NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) NOT NULL,
  `upload_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signatures_stamps`
--

INSERT INTO `signatures_stamps` (`id`, `user_id`, `type`, `image`, `file_name`, `file_size`, `upload_date`) VALUES
(1, 1, 'stamp', 0xffd8ffe000104a46494600010101004800480000ffe139264578696600004d4d002a000000080006000b000200000022000008620112000300000001000100000131000200000022000008840132000200000014000008a68769000400000001000008baea1c00070000080c000000560000113e1cea00000008000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000057696e646f77732050686f746f20456469746f722031302e302e31303031312e300057696e646f77732050686f746f20456469746f722031302e302e31303031312e3000323032353a30353a32342031353a33393a3335000006900300020000001400001114900400020000001400001128929100020000000338300000929200020000000338300000a00100030000000100010000ea1c00070000080c00000908000000001cea000000080000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000323032353a30353a32342031353a33373a353100323032353a30353a32342031353a33373a35310000000006010300030000000100060000011a0005000000010000118c011b0005000000010000119401280003000000010002000002010004000000010000119c0202000400000001000027820000000000000060000000010000006000000001ffd8ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432ffdb0043010909090c0b0c180d0d1832211c213232323232323232323232323232323232323232323232323232323232323232323232323232323232323232323232323232ffc000110800f7010003012100021101031101ffc4001f0000010501010101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b51100020102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f0156272d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00eb3b51d2bd63cf133452017345002d25001cd3a98098ef45218bf8d18cd001cd1ce680141a280179a5a00297f1a00427d68cd0056b7b3b5b3327d9ade387cc3b9f62e326a7a00a90699636da84d7f0dbaa5d4ffeb241d5a92cf4ab2b0b9b9b8b5844725cb6e94827e63cff0089a02e3068d6435a6d5bcb26f19366f2c4803d876a2df46b2b6d5ee75489185d5c2ec918b1c11c76fc281dd95e0f0c69b069f7d651ac821be62d37ce7249f43daab9f0aa66cad45e49fd936d1943644644a7939639e7e98a03999b99cd253244cd2e41a0614b9a0419a2800a5a061da992cb1c11f992c8b1a8eacc7028049bd8aabaa4132836f1cd70a73f3a2617f36c0a864bfbfc911da5b45e866b804fe4a3fad3b1b468b7b90bea17262de351b05d80891506ef9bdb2c3a0aaeda879ab1bc7ae87790958d2189406f5c03d714246b1a4bb0ff00ed210429249a9ccf1c8bb52458038cff00c079cfe152cbadc166e96cfa8c266f94fef6160483eb8e868b0de1db7b1a11dfb344d288d268c2e55a0903163dc638a24d5ece09e386767819d720ca8557e84f4cd2b18ba32be85c565750c84303d083906969195ac2514084a2800a29805148051ef450222a42714c62679a3ad0014b40c3b520fad03173514f75e48385dc40c9ec31f53c505461ccec674d7d73b9954efc8e238074fab1ef50f94f3cfbe02f8546f99d4925b3d327a60fa551d90a7186e2c967777823777922dbc95c93cf63529d1e37bd8ee5de41c1fdded182718e7f9d171ba89688a53f86aca698dc7da7c9b82c1cb67824679c1ab4f6f0ec55952ddc03b5480a48c8fbded45c3da392b0ebab3b291107ee811954c11ce473c0fe754e3f0ad9cc8924fe619822e5d73f7ba9ebfca8b94aa38a2a5cc652d0d95842fbd245629b7e5eb8392467b75a6243a935bbc80ce932ca47d999f72bae33900f5eb8fc299b269ad45d1b51bd92ebfb364b2fb2485b787830b8ebd57a5764048070db8e3bd43471e269a8c88bedaa92ac73c6f0b313b72321bf1156782320e41ee28b1c928d84a322912264669d4c00514805a314088e98698094503168a0607e94d2c13ef50345196ed8c85133b47538cf3518b79da3733b9943b74c1f97db0299d718c62bcc90d94711dcb249193c94073b8ff3a921530ab678dcc5be6e31dba5329cae8433a00115fa6788c6280d2b0f960738fef1348951ee27ef41df208e151c05c0258d355678d93257e738e9d3bf3e94685de2897cb791b2ab0cbc9078070475a89d0631f651f31c314e2804d3ea34c68ea036f491570ac70c57e99a60b78d5c047fe1da50371f91a6689912a1b72ad1b0c2f44c60e3dbd7e953c9ab35bbc71bc4f976c06231d68b5c254d542ddbdf24cdb1ca7983b13489662de3db66c2105b7118c8ebcd2b58e59c5c1f2b228f55d93082f216b773c0918623273d013deb438a5631946c3b8ed470291028fa52e45021dc76a2810c2b518426801de59f4a5f2cd002143e949823b531903ce395404b038aa8cf279abf330047ccc07141d108db7265711b109173d49cf1f9d3249b712aceed9fe18f207e7d699a28eb7644c9347b5515625638e3ad35215fb4c5e64923654b6d3c67ff00ad4cd3992d89563b88d94c2b16d73ce0007f3fff005d3a4f31537cb93b86d68d5c920fd691174d904b629792b4ac15e16da406c9dacbce7afb54d0416ef70e21b744de7cd6703396c6067de81741668d2ce6373b1dc0e4932703e829d748f205b859e2454c90c230cc47a67b501ae8c8a5f366b60e6e5f0c090eb18cae7dbdaabc104914404eed36e6c2cdb76e41f5038a66b1762478e6c2f94eb3c449054fcc292390ece5bcae71839619fc7a5334ba6ae569ac880ef1c844aca4ecda369e7a8f73d2b3c4daa411c72b4a8635c6e8a376ddf9e79edf9d3358da5ba23b6f1634322c1776acf1e58bf1f77d31ed5d3d94907938b56fdda8e62ee9e83daa5a39b1341c5732d8b91bac8bb94fd47707ae0d3c7a549c0d587504134890e680450334fec60f7a4fb227614ae481b4147d8f23ae28b8c69b3f7aa1791485bc9881e7ef37a0a68d29c6f21a2ccc7842a42a8dcce7bfa5325b33cfc9852738eedf5a7736ea35ac642a1b6ec51dba502df6308e31b781838c9cfbd1706fa223788b1491e37cab1c646307a527d99a58d5e5621a36c30028b82d351b0ef4f3622c70840048e69193718b70918a49d42f5f4a637a3236b4b9412bc18329242aee3b40279c8f5a56b5960800795d82fce0200093f51da8b94a6ac36ea67368ca2540d91bd9860915249185689c6d16f1293d7f5a10d5924408ef1da7ef0843212c583e31fe4542a0cf1292ef1c6ebbb1bcf41ec7839a66a9257654dd636d739175334c101280970393c81ebc8e6a58b5145b6226df2c7b9806441d0739e94ca519096f7d1cd2cb1c3209171868e403bd52bdd264b8b758ed7c947c8660d1e0919e808390314169f2b31ef6ca4b121f7431cc13e6018e07ae33daa3d27519ac6e96eff74a47123303920fa0fea699d0ed5216676765796fa8bc9716394b953fbd89f3f3703b74e98e6b46d2ea1bc88c911ce18ab03d4107159b3c7ab4dc6e9f42ce2971c5239c30290a81405cdba426a405a33c50056b998ae224525dbbfa5363511a6f2e09ce5895ea3d299b415a23659429c91927eeae381ef4b1c6aafe64cc0be7819a0bbd912092365c921802793ce0d0e543298c6fdc38f9b83413663271181246cc154ae724e4d447ee606d0eb8dcdc61bfad3296c492465369f2c3e78217818f539a6440cb2190b2c6aabf736f4f7cd03b69723324519db1904bf0327bf727bd52b81765115248c2b1272aa72707a67de84694a0b7913cf0806388843f748f357764ff00f5aaa4d64350b840c4b18416454c8527b6e19e69a65c62ad7213a4ac223b68e5db2ed2fb2404a924938241eddaaa6a361246b1b3940c082aa6e085771d54606475e94ee5c65aea54923b98ec9ae6decadf1300ca21dc4e09c1cf43d853bfb320b9b710895d7cb50a63604ab29ea5467ae4e39f4a7735bd95d0db6b196448c5b34414125c140bb428043608eb9ff22af5a4125d44cd338765f95a4db801bf3f5a09993359c1748166092ab01df861d45666a5a7c56f399fece1a20854a819e00c01ff00d7a131539b4caba6c02c6fa5963942aa10d861f3302bd38fa5749a7dbdac760b359458419deb924fa9a244e320dae634562dca1941c119a3c86f4accf2de82794d834823e6811ab9a4a4317b53249022e4e7f2e940d14a16f9d98ab152dc311c9ffeb548f32e3795f901f93fda3dcd33a2dd04f9e31e714dcc4f0b515ccaf0466e161c1209c6739e33cfa74a07bb1cb225c49f238031b93fda34a76192362b8c9c6cdc383eb9a636ac2dc7f12ed0f21c839071cf6a730759a3f2f087cbe41e41f6a010db73bc492893cc623053a7e751b24f6fb4c716f3b09285863340d357b31647548a332c7182dc9dc3217e950b5f33c0aa11629482563917ef6338ce3a03c73486b5d08c5eb1fb44a65cba90a15e3ced19e71b7afb53ede5926b791a290057978f90a36d3db9ef4ec68d5892248a390858245f2f2325b93f877aa528b286cae24958c6f6e1c099d73b4163dbf1a057915e1b1b958629e2909b264da10672a0a8c303f5cf069d2a28b5448621f69f30630c016232791e9c53dcdf75a10a2dd3c24c3122cc82450011b5c1e0824f3c66a9dab4f269f2218e38e658fccdac3218839cfe94cb4a2d1aa976e504732c236954250639ce3241e82adc72978d634d84856079e180ed48c9c5230e6b373a8cc64872c62050f9808381d2a7d36e174fbd533c83e750b202f927a00700629ee8e89253a6e27569180c48fbadd314ff002c566786d58044a4f23348604f4c5210514c02a9b4de64a06e5d818a91ce78a0b82d473b744030ac37138c10bef50a7fa4cfc83e520e07f21546f1dae483323c80919418041ea4fb76a5655c3a34ed9033b0f3c7d291287a4419779543c82a40f4a8b1e54ac161c6f6e18bfcb9a069df71c8a1da40ec525c7cea0f0dee291882a9e7637a1c8c3f38a0090c88804b84cb101b07fce4d5417d0deb340921477898a8752bf88f5a0145bd47c4b7290212433ed5463b4819ee79ed8a1137965991fe57ca90339c1c8e7fa505dcaf731148dd959dd1616900c8049151c32d97d99942aa06c3bab12d82dd299a733689a5be659046a77155f998a955c8ec09ea4e7a54325a5bdc46eb711c444f132c85a418e3a71dcff2a01268ac5a34fb1c80092d432c3b04cc464f703a1ed5661b9b43b6dedde19a756727bb205ec7d3ad068dbb5915c5a47293961c3b602606e0d83c0079ede9492430ac71af96cec59900042e0fab1e00c67f1c53b82932a5d24b1c79746949f946d5244847bffbddb8357f4d9d6e620a2116e6221517fa67b9ff00393415277572ddcdbee956e1befae7202e71918cd665de92d2b06c8f33003ed7c11eff00a534c74aa72bd4e8ade065d3c425ce4ae01f4a9addfcc8864fcebc3718c1fa566cf32a6b264a29691991514c06be369183c8ed502b460ab1030780d4234815da4568d99707cc3c63d0718a9a2cc6918099c8f9b1d41fa551b3f845766f340f33ca4627042f7f7a704791b12a2103fe5a03c9a44e96118b794ffbe0aca77123071ed5107ca32c81f739e573d3dc5038dac4c1098f69662e38dec30714ac0b26c38ce0e71d07a504df5297d8037df770465491c641e79c7f5a48d600e22b78fcd230a646fbb81d813d4fd28b9a295d589ae24983a87f2c0041e0d207454cc7336e2727238f7a6528e89a12270ac033c6880607ca39a734b02395721ba6190e31e946a538bbe832382d992e08906d6e5d76e141f5c1aa0f630a79c0cc7cb521d57677ebc7a8e7e945c149df5160030f0adabb5b2e177bb15c1248240c74e0558b7b08a3be93c98215c70f2eef98f1c640fad172db122845adde64039e519533b4679cfa0e955ee165160ec8f149390c73b4649c1c1fc284289520bf42e2e2675549a301637560c1b3dfd33fad5878a34911c4c1c91b2355c065c707be3834cd1c6c6994331565dc51b8241ebeb5247011267cb21b182c53afa739a9b9cf29a45c8432261f1c53c637161de91caf563a92910474530209e5daca98e0f5c75a6b10e8a81186f38e47e74cda2b41aa3ceb8538f917a0fa539a391848ea70c41185e0f6a0a6f51a24112159629369385de33d7b54abe5c116400a075c7ad029790c49e2983c68bf3608dac319a70dae4284c05180cc3f4a07669d88cb3baba82981d4eeebff00eaa172abe633e500e083c9a0761b15bb1de6624ab91b632dedde8f35437eed8b94046c030abf5340b5638db8925df2eddc0e70831faf7a04706ed817731e786edeb45ca536b4437ece8e4fee88207ebda8fb2ba8f9586eebb48ce29dcb557b8d779560319450ca3866f4a16eccd09f3bf7457183d5727fad03e54d5ee2b6e8a48d64557f310872c78207afe751911aef0913469b86022637f6e4fd78a40ae12f97b99ae102aa2e1816e73c11c0355afae1d2ca502430c81061b66594fb0ee29a2e9ddb31a14bbb29f6deca922a1219480198638600f3c9c568da4334314905ca82ef331128218293c818fe7f5aa66d27a1bd6d22f90a580538e40a995c30cae08f5159b3cda89dd8aca1970c383da90c61a3dbc8e723069198e19da3279ef4ee2811176a5a605578cef3ce412093d31f4a24750cdea8bd3dcd33788b060405881f31eb8a787dcea769c7d2813dc8c194965e40539dd9fbc291d833989c16e8dc7decd03424ad15c6632763e723239cd3937c912860ca0f039c503d8a91da4915f49236e28c8aac58f0719edeb4f0646188140507118e8a07ad32ee9ea240b29de8488e332124eec93f4a9cc588d912244c1e06719f7fad2149abe840e8f22b42ccdb244c970d8c374c03ebd3ad5a81642e59fe538e80839f7fad027b10892621b74a0919cae0714e46656dd19c8c7dd3da82b955859584d2a81955e8dc7351c916d8dede5412472f507a6298d3b2e52316ff00680a81de3922e57273f88cf4eb534d0c8b1c5182c51796d806491f9520bd9d98ed888b8853cd72ebbc3372a3b9e7d2aabc2de77d9e3895803c331242f7cf34ca84aef5316f34c98432dcdebac9247b15431ce17383c9e72456984985acced0c88ccaaff363271d01ff00ebd3b9b39268b307d9ee2292d8b462541b98038c13eb5a311d90a8623238e3a54b392adf624386079a419c735260083048cf5e453e8111d358951914c48842c850e188624e33db9a89b3895b00ee9303be4014d1bc4b3182a8a08e31ce7bd572b23dcbe095c30db86c600f6a016e4e15bcce80e0727a66a311c65dd44ac4e7767772948484cb3a16594480f032bd0d3d2301f7093e5620e314ca63260b126cddb43b753cf1df34af3205090b2172db401d8fff00aa8124da046551e58270bc641c9ce69aee25df12c8a5931b80eb83d3dc501d4484c518c8d9f3360f6cb7f8f150319bce0ace1595cb2838f9876fcbd28296a3a389a564ba648ccd82ac14e323fcf6a73af3be356ddfdded4cd2e48e40dacab819fcaac17575231f4348ce451722389b90b329ca1cf5f6a5b871042479b8f331e50cf2d8e585058d966f263def3444636e01e704fb52c91c170a41202afcaea4703d3fa532ed6d4aa649469ec9345b249970016dcbb8f039efdaaeb48a6358d977176c718231ea28636ba90c2239a312471a8724efda3be31f8f23bd2456cd16e75015b8623241279ce4671d31d2815fa129bd9b2d8519db918e99abf13ef404f19eb49a30ab4d456849900804f5e8297bd49811d3589c703bd305b8c51e6125d4615be5c544abba28b8038e3f3a11ac4904bbd9f68f954e371f5f6a8f6095c32b211bb27d41f6a04b7272c1594f3f31c0e3a7155e38e39095704139c6063bf634d0e2ec392211a3461f7b139f9bad0b8924571c85c01cd036eeee0cb991871f2ae3040c727d288a2559b0557e55ce42e324d017d04f25172c3e53bb3f788cfd6a527cbdcc14648e540feb484466585d158052739c15c9a8a5961d85f0c0f72299a462ee42d711a105a22e198046080f5a9cb924e2374c1c1247247b532dab1291090517702474ed5119e688aa88c6d4601c9f4a44257d075ca472938da412324f634e0a163657525a3e15fb807b8a02fa58ab7114e61c5b5c2c5b7e6dcd186247d4d55b40fb658f1c03b813eabd71edc5346f169c4b58478c1091e51b71deb93d3b71d6a14659add80478c8764619e060f1fd28121e25569155369664f341594ee3f51f4356ca44bca80ceaa406272467ff00d5419cdb4c80c1b1d8f2e14838fae690eae20de66421546770ef45ae5387b5d117ace71776f1ce17008cafb559a839271e5934474c932626dbd71c53256e5613f9572d08552080786391fd3f5a97604182c5bb05e98a66bb122aaa2aaae70bc73cd3311c92326ccb0f6c0fce912858d3ca2546f604ff0013676fd29b000f99bcbdadc80334c600060b2b1d9824601eb4e3caed5182bd8f4a0646effbf65c1fe1391e9446d9971861855e739fff005d0160b81bc80bc30e99e8695404407764e0f2579028020102839e991c85e39a7eccae59896078c7141adee3d40fe10bc02781de99e6af9bb036e72bb87a62810bb9236dee71e8c3b5249e5ca8480436e209e99a040c22447e59b7738f4a4818ca1e376240db9dddc73c532ba0d788ef70efb5107ca383c678fa8aae1a2227569ceeced6298cb0f4c76e69a2d3d0b10a9f2915a52304962c7939391f8d39218231b115c75dcec07cc71824fbf148872b11c16e914cb244a141c2e48cfcb8c0c7a76ab271bc33ed551dcaf3f85214a4d88e3699064f0bd7d79aced52269e060a1880c378c0c11fd69adcba2ed345cd11f769ea047e5a824015a552f739ebff11919a6be3610727e9419adc890a305c8c60639ed4d76dc88c707df14cd091fab31395dbd81ce2a308ecf850c2323392dcfd28044cb90c57610074627ad336a48ec02918ea4f00d2175230e0c4aae07dee8bea3dea128eb37c8bf79883838efd7da996ac4cc9b98ed6c6e418c741834ec0dca1be52ca4601a04de80d1ab811eedccbc9a7840a3087236e003489b90afcd129240da72540c0a1b380ea403fcf9a6683c6006e8083c9e94be59c060a38e82815c6129bc8ef9e9ef4d721623b0027381919c505215957cb50a08f9701bd68c8965202fccb84248cfa9cfe828019340c0b399178ecd1673f4e690b2185772a198957184e41a65277448627c847dc01c1271bb27de9a6726ec6d490a3a753c81ff0001feb485b9320c22e53ea4f18fc29a77b3e199fdc71ebe9412388fdeb77241fcaa0bc8cc9136d00301c64f538183422a2ecc9ec1425a85002f27201ce0d5aa4cc6a3bc991f7a424639200ef9a0cd1596542ac7702a0fdecf3ef9a55c342bb70412483ea299b139c8030093ed51288d90b14232700e719a4492a461071bb9eb939a63ab00e4150adc1dc3fc2805b8d8b60dd18914b30e8a3a549e5fcccc72738c7b5036319582ab96076f723b50cbba3f970f221ce41eff00feaa60488c245dea4f23a5206080aed200f41489202ecac4b74079c0ebe95279649e58ee031cd3346271bb3d71c53924db26d3920fe94084931e60655cb01da923401594f201cbb7afb503be83d143c419fa139c1eded51428ae8ccabb4bb6ece3ae3a502b8b2b32c8c2250cdeee320fd2a1048c4ae36b260b0e87ff00d5d682d6c4a936f382ff00396c8183d29a582854690ab64baaae4f19ef405ac0b334d103117906e2a5810307df9f5a8837952895d1be72376474c81c7ebfce819776e646c9e71d3d2aa5e958ed649157f79b47deef8e83f9d04c772c58307b6ced60771ce57193edea2ad714998cfe264751cac1546491ce3819cd025b90a912b3201b021c6077fa558c0048c76a668caf25bbceeaeb3bc7b78c2f7a7ba383c1ca019f539140ee8525d76b2e5cb3004138c0a59118b06d80e3b66913d457dd1a970aa4fe58a783b8654e73c83da80137edea0904f6151aa9889e98eaa42f34008a763b00727197001efdc54a3047dedc08ebeb4088d94ac8a40e1ba9a5dcb9071df934160d87238e94a43020938e2980c5064270c3dc83da9f825b6a616353cf1d4d02641b15ee32b26e6039dc7ee8ff0039a9bcf8f0c7761578caf3403d510fc92cc029cee3921d4f18ef52cee124440bcb1c1e70282bad80a3a6d6c965eea46481ed51c82352e40d8ca412ec30bf4a069ea3561108de25421b9009c0e7bfd6ac18cb100aa8fee9eb404a435dc79cc3e5dc1093cf3d6a1b9959ed88f2982e71823a8a023ba2d5b0c5ac63615f97ee9ed53523196ec8e90e08c7634128aebf2caab9e9eb5219300123f8b1d28341a9f2b953df8cd4bcb6403b48ef40318636e034ef93e8719a9073f2f3c773408acec65711a29754fbdb4f5f6a910cacc7726c00fcb839e3de829a561ca9825998924f534e654384381819141231c08f6a80b8ce7e6fe94c72d16d31f2cdfc23a7ff005a818e628cdb245742d8391d3f3a4c98ced591483c004f4a068159464920b7ae68deccd803e51de980ee1142b64463b019cfd699b995b6c7b72fc8cf503e9400e8d194b163f291f313dc8eff00ca9ad2678575dac4fde1d7d466804490c6cacc492463001ed4c68a299c2b75500b21e7a501cdef5c51118c298959b9e03374a5916362ad9fbdc2103239a434f511104854edf96304004548ac7382a50f607bf5a626471af2cec013f7464738ef51ce7cc65fbfb4bed38231ff00eae45034cba06051d2918b23a5ef400c6c83c75a5db952b9e48e682d1037fcb36cfcd9c123d6ac6e07f2cd03642ec7ce182178c06c6734f5531272e598f763d4d01d052c780aa327b13f9d0c3a6188e7d3f4a04347fac6fde6e19c81fddf6a5dbb9bd491c71400a131957e41ed8a67964021486c8c1cf50281dc5de9f2ab0c1182370a5de9f2e00396c1c1e94007c8b9c22a9cfb73486652d80430f45e69808b1be72a4a061c963cfe54f8c048f715d9dce4e690116e599990020b03839ff38a238d583721918fcca47434c7aa448f2a2feec83d380075fa547e4e06d1f3a0e761ea3e86815ba8f59016299dca0720e73501523050ab270a06480abf4a0a88c8c18eef7344f9284eec019f41c7d4d5ee43313f748cf3d8d012dc8613f32a9e08f9cf1ebc0a77324ea3f841c9f4a04cb14b48c88a8a006c8098db1d71c719aa9229b98f63315db82403820fd45346902407e45dc7696f94e3a034f89be731e7183c50c6c959822b36338a825208df210cd8f9147634090be58c461b21c1ea0e71dcf269e596304838c7500649a42151a2772508248c923bd383ab004739e8680b5b70fb83000da01c9f4a4055806078a04019188f995b078a468e1621591393900ad031552319728a33c9c8e948b3216d9d0838c5301921323344546391b8f3c8e9c521567d8cac033afcdc707148ab92a208f80bce3ef014c1b3cc65539c9cb0cf7a09bb60f18672c59810b851e9ef4b0aba8dadc8c67703de98efa113e13e6e0eff004381c5491c67696523e651b491c81e940efa0f1b99860ec39390475a5d8cf1bac8f9ddd0818c504b6060428c87eeb63233490c02267231963dba7e5481c89e90d040ca4ef400544e0ab9200c11c9f5a0a8b07d9e4956e9838a8cfcd182bc3ae37669964aa4c89924a9e87de902af202aaf6e0d02d8574e32b92d918c9ce29c06e0486c1ec7d2815cae96a239060b6c503054e33f5ab2a1725b0093dc50c7295caee92fda4b87668982811f185209c9fe54eda5d482c51bcc2781d690262ac416e4100f033cf427a5200ce5707a963bb1d3d2983771ecc5e020afcd8e78a1a33b9b18192181f7a043bcb199327866ddc718e05282990a0f2074a008d99c4997c7ca3240a1a1422368c0054820fb7a520d891c0209e871d475a300e1439cf073eb4088915be68bf8558e011c30f4a78ddb3018648ee698d89bfc941bc903a0dc7f4a02bae707966cfd077a4326420ae7bd3a83362f6a28022a4a00514d939438ebda804570ccce07c8570491df3da967956184cae40db8c9033c77a66c8737ee4ab2f2b4f4649143f6c823f0a04d0adf740c6707a9a460f91b4e0e73db9f6a0925f63e99e0531171b980c6e3923f0c7f4a4210b1f300c103d3d69412dc81f4cd0011c8240dc60a9c114c92509862a76838273c734c76d6c39e623211771e0f5a6bc8c15380ace707be281d8468e43104f303104ee2476a52ac8ea5467823f95021ed1ab3066ea3de9d9555f402824634843f05428e1b3dbd0d3503b387380470dc75fa522894b052b90793d68da18e7d2811048e9248080084e4b13d054bf78648ce4640f4a65740881cb13d49f4c0a9a910c2973408868a005a2802bcf1311f210a49e4e39a688d238cef3cb0c1f56a66d17a58647950ce4bf96546d8cf3d0f18f7a7219372918dfc175e8307fad0364e5d5834641c9e30453ce0f02919b1a8fb8639fae3ad2f39247414083cc054b7cca01c1e3aff00f5a9461941420ae32083c50057582440a5594bed218e4e09352481e484a9da84fdeee314ca6efa8187795dc7046338f6a919038218fd0fa500d89f220ca9000a16542c007ceee45048cf34efc10c07f780c8ea69fc31c82718ed400dd9855f3096c7078ebf5a978c5200eb907a1a86590eef263e5c8ea7b50822aec688d4c5804e1739207535223b39e0e318ea3afb8a65326edd28c523316928023eb45002d1400a79aab2a6268c8230339dcb9cff00873422a1b8d8cca563f9e3da33bb62f53ede94e60b1282cdf3118f98e33546822662567704367a2739a78904b09601d4918e57047bd206872a2c61497c91c024d279a04c54e7eee7f9d04d84574076a9cb1c9c75a7c7b429555c6d38c628130078c2773d48e2953732e1d7d3af7a04347cd236ec820e0738e291d55d9f3cedc7340c76d5fb98e41cf0280844848e06ec8cfeb400a5011d4f5cf14e5f970bf95210150cc09cf19e3348b9e548e01e09a0435e601d635f998fa76a8a28f9db9dd91f33fafd2996b444ebdd002029c7d6a45014003b5225b168cf14121450047450014b400a29a403c1e940158c6d0c8c54288c8fc8e69a50cce19ba20e3eb4cda2fa8f52ae46d90ee04e4d0db8b8c31563c01d8fe340d081a5ddfbc09b546739e6a5014c2a5c1ce32491d2814b410c68554a1e7191cfdea7eec20278f526827700c0b91b8641e46691190128a49c7524f4a0561d9504648f6268deaa482c38ed4084dc02ee04907a6282d96503a30273400ccb6131d3041c73cff9cd29889937670320fd08a0090b8538ef50b4be60cafcaa09cb1efc76f5a006c716538caa9eedf79beb5301901001b0a8c1a06d922aed51839fad3a912c28a041450047450014b400514009d78a8258a45c188af5e41f4a68b83b308b66c3bf1b8138e7ad31d255002287079dbe94177d440526b9c26c6653871e9570818e7a5029ee5672460b21da06413fc34d8dda7456dc30327207f9f6a6525a0f29be7de1d4aee07f2a5f2df321e06e208c5020580858c36d3b492735279619b77f4a44b1046026cc9c039e0d38050a0039dbef9a091be6003a714c62ceadce1738c9e38a06246aae49077fa1fe13fe35285e0173b987208fe94031f8dc3b8a5181d29137168a042d19a004cd2d004745002d140094b40094b400dda0b92517d8f734fe9da81dc68503a0032727155e67cb81b80403e70475fa7e94c71d58c7c3467eceec180c61ba1fa8a1222909291a6e61c00703e94cdaf6561a85a4b6ff00488c42ca7181f363de901cb10664f4009c6680d073c6b3147dea761cfdee29f9383fbc4c74e1b8a01d850413cbab13d4004e28278c2a3b7a13c5222c39622a0fc814faf534ec21249f9987af6a096c7206ce1b8029eaa1540c938e324d2258b4502168a00296800a28022a5a003345001476a00514b400525002d452c6cd22b0238ed8a11517664055e33911ee207073d6885f82a0938ead8e2a8d1b4c8d65fb3dcac04ee6705c8f615379b183b33f37bf340da02f095c855e99e9e9481c1fba176b7230b485a8a32247766cab005063ee8c0c8fd2942b19475dbd7e61fca825b24119dc493d7de9fb4039c738c6691171d45020a5e2800a5a0028a0028a008a8a6007a50280168a402d140099a506800a01a004349b5739c0068b85c608230e5f68dc460b11934e3147cfca39f6a65733104318555d830060714e000e000001485ccc5a281053a801296800a5a0028a0028a0028a008c8f7a3bd300a5a003b52d20133450014a280034940052d002668cf340051cd002d140051408297bd030a3b5001d696800a2801296803fffd9ffe131e4687474703a2f2f6e732e61646f62652e636f6d2f7861702f312e302f003c3f787061636b657420626567696e3d27efbbbf272069643d2757354d304d7043656869487a7265537a4e54637a6b633964273f3e0d0a3c783a786d706d65746120786d6c6e733a783d2261646f62653a6e733a6d6574612f223e3c7264663a52444620786d6c6e733a7264663d22687474703a2f2f7777772e77332e6f72672f313939392f30322f32322d7264662d73796e7461782d6e7323223e3c7264663a4465736372697074696f6e207264663a61626f75743d22757569643a66616635626464352d626133642d313164612d616433312d6433336437353138326631622220786d6c6e733a786d703d22687474703a2f2f6e732e61646f62652e636f6d2f7861702f312e302f223e3c786d703a43726561746f72546f6f6c3e57696e646f77732050686f746f20456469746f722031302e302e31303031312e303c2f786d703a43726561746f72546f6f6c3e3c786d703a437265617465446174653e323032352d30352d32345431353a33373a35312e3830333c2f786d703a437265617465446174653e3c2f7264663a4465736372697074696f6e3e3c2f7264663a5244463e3c2f783a786d706d6574613e0d0a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020203c3f787061636b657420656e643d2777273f3effdb0043000302020302020303030304030304050805050404050a070706080c0a0c0c0b0a0b0b0d0e12100d0e110e0b0b1016101113141515150c0f171816141812141514ffdb00430103040405040509050509140d0b0d1414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414ffc00011080226023803012200021101031101ffc4001f0000010501010101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b51100020102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f0156272d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00f75c6e6f7a7b73802907de1c7e34a7039afd00f8f11b23041a5127a8cd2673c5364f96a807337b629b9cd47927b629ff00c35202f5ef4d348adf8d1bb27a50512799d38a5dcbb4e7ad471b7cd4e63b8e3b53245dc36f07268553939e829bb474a76d0a0b5201428c123834e53b69bb972003d694e3b1cd000adbb349f74e285e2918f3400e7ec693751b89c7149b72dd79f4a07615586791c53fcc1d054617771da90285e6a82c4d43714d04b11c52b30fc6a404ddbb3c54614e4f3c7a54a800e49e28e87206450511f391dcd3f0de9485b1c81cd3fcc39031c50033271ef4a18b30cf4a02fcd9cd3f6d002312dd4d0a4862452ed1eb49f4e6815d0de412c4e69e640168553dfa53590e481400bbb728ed4f522a2e5783c52e0f5ed40c76edad8eb4b9ea2a22a4b669e036e24d021ea4ee1cd49b8019350ee3e94bbb140c7f980f414d6ce320d26ecf7fc29776d5a9b00a3e6e4f5a5f348e29a14b73d05382eda003cc269e18f1838349d452afcb48070f97a36c18e8a3afd69378c8cfe63e53fa629080c7afe94bb46314d00f2dbb193f4ef4c3d79e57b8f5a36fa9cd3810bd69004172ca1d0a2aab70571c11f4343306c0c2a28e8aa30051261d7e5fbddaa3dbb796a63727d05daa41e734bd8638a6f1d851b8e3a7eb408716c8c633ce78af22f8b9fb2ef827e31788175dd526d4b4dd5fcb113dc58cc06f51c2821c30e327a62bd6f7534966073c0aa1c5b8bba394f857f0df4ef84be0ab5f0c69977717b656af2c8935d637fcefb88e38ea4fe75d5f1d08c8a45da31c9245058763f850127cc26d25483f35496f208e54dc70a0f34cdc7b71498ef408f91bc79fb0ef89fc57e36d5b5e8bc67a6ac5797a6f112e2272c497042903d00c7f857d7d6be7dbdbd9ace57ce4541279630818607cb9fc698adb7956653ecc6943039c7cbce4e0f53f8e68bf914e6dab1f227c68fd8e7c6be38f899aaf89b46d6b44365a85e8b9ff004c6916487183d9486e9ed5f58d8c33dbe9f6715dcab2dd471a09e68c6d1238525d941ec7d2ad336e5201ebc1e48fe4699fc44001437a64f6f7a01c9c9599c97c5bb1f156b3f0f6f34ef05a59a6b379ba1924bd7318f2987507b1c66be75fd95ff662d77c19e3abff0010f8e3c3f159c764863d3d5e7597cc90b02d20c1391d7938afaedb6b06c8dc18e707b718e3daa351f37a7181800003d30074a5cda6c546a38ab2473ff12349d43c4df0dfc57a4e948b26ad7da7cd6d6eaedb01661dce2be52fd957e02fc41f871f1863d67c41e179ac34afb1cf6bf693346543301b4819c9ce0f6ef5f67a3b06241da7b52f99f778ca83f778e83a0e99f5fce85b094dab9e55fb54f84fc41e35f82fa968fe18d3e6d46fa7bd80bdac4c033c60824027dc67f0af2cfd8a7e1278b7e1ceabe2cbff0013787e4d1e0ba54b780ceca18b8f4009ed9afa97795c03d0607191ebfe35231324614e4a0ce33ce3de90d546958f8fbf6e1f86be33f1af8f74ad4742f0cdf6ada75be962033daaee0242c588c0f4e066bbff00867e23b9fd9aff00643d1b52f1268af6ba9d8bcb1c5a7cce03ccef3c8c071bb1c10791dabe8886430b651995771254311dc71d7d8fe75cdf8f3e1ef877e26e9f158f8a34d4d5ad213bd2391d9007e46ef948cf5a6bb0fda3d99f27695e26f147eddd34fe1ed41a0f09787747db77e75ac2d334931054866661ce0f41e9457d67e09f01787be1d6932e97e1ad2a1d26c19fcd648b2599fa64b1e68a1efa12eb3e88d3fe1f434cebef4c597726036e34e556ea2aac6561c46dc73cd35be6e69af2823a72290d160b0ee283f769a41c7ad0a370e0d1601cabde9714c59431da3934e56ce73c5031027bd2b03b081c9a5ebf4a672bd0d310ab9c7231ef4e0c0f19cd0a33d4e6936ed3c2d2b122a28e78a5e1580c50ac17bf348c771041c5161926e1f8d1c545ee4d3968b00acd8e94bb78cf7a6b0cf7a5ddc628b143d70abc75a62f7a4c9dded4ab4580566c29c522aee24d2e33c53d485cd1601abf329a53f77149b76e4d140c50075fd29070338eb48bc303d47a5398818520839f4cd02b82b77a5ddb8536353b82b0da5812bc8c9c7b66ab6b5ac69fe19b392f355bdb7b0b48d829967955473f53ef458b5172d916583761934fda5319eb5c55e7c6cf04c36fe6da6aafad1ddb7cbd32dda73f98ae5b50fda03536dafe1df877ae6ab016d9e6344101f7c1e69f2391b470d527f0a3d8d771e8b91ebd07eb49b1a46f950962768031c9fc7b57885e78b7e3678aad1d6c3c25a3f85e23ff002f17d71be545f5da07a56df80f40f8bd0dd4975e2bf1569b7566630912c5086f28672083c1cfe147b3b6ecdd6127d647aafd9d46fdcca194edfbdd4fa0a5688430acb2bac5131dbb9d940cfe26bc23fe143f88b49bbd575ad43e2a6a874d60f30c00d2c609c93e80019a9349fd9ef45f1468f0ddff00c279e27d7f4cba1e6e7ed1807df040239a3923dcdfeab0e5d667a9dff8f7c2fa5f89a3f0f5eebd676dabba8616ed275cf2391c74e7f0a92e7c6de17b2bc82d26f10582dd4ee638a35973b9bd335c549fb3978163d3c5e5fe912eb72a26c7ba92e089dd47f70e7a81c5721e1df837f08f5048ef74fd1afae258df0b0cd7a77c4727208c939155c91b6e6d0c14248f78fb7e98b3a41fdad626e19bcbf27cf50c5bd053350bab5d16213ea7796da7c2491bae2655c91d40f538af1cd53e11fc1fb3f10412ea9bb46d6a740c36dfca8146400720fdefcb15d16aff0005fc09e30874c1a86ad75ab5bd89cc56eda9b48a70720b0cf3f5a9e48f739a5854b53d1ed57edd12cd6e44d0b72244fba464e083f419a996d5dd41d8db4e70769ec6b9387c1373a3ebcbae5a78927fb14908b56d32e24516e1400018d73d475ac993c1fe37d2bc68753d1fc676fa9e8afba41a1ea92104ab6321187a7d2a7957722183e66ec7a2797b49553bb1e80e314bb78ce38f5ae0eff5cf16785b4759ed3c32dad9899e49e0b4b8da523ce4e370f988ec38cd6bd8f8fb4fb8f0649e27bcb1bad26ce38fccb8b69a33e6c2323823d7be28dcc678694343a4dbed81eb48b9539c562784fc79e1cf1c69363a8e89aac37305e7faa569155fae3952722b42cbc41a66a9757569677b0dc5cda12b3c6922928738f5e6972b31f653ec5b7cf1de9a78fad3f6491b60a3608dc0e09e29db70791c8eb907fc2a4cecfa9152edcae7b52ee5ce7b5377339c67029d807703907069acc36f2734152bc1a6b2e3148072ed6e338a4650a323934602f6e69334c04dc579a404b707a1a7b60e0535b8e9d2a803cb50452941da9bc9a3a7bd002e31cd252b52502131cf5c0a5dbb7be450cb81eb4ddc49c76a7610edbc7140ee3bd19e2955739f534ac50d3cf7c53a3c6d393cd2600eb48ff37418a9017a679a553d0530669e06d1baa840d8ce08a786dcb81c547cb1f4a545dbea4fa62a6c03b071d69c381cf342e7b8a6364b600a0076e3b8e3a1a293ee8e7ad154414db6f978c01f41491b2eedbcd2b48370c2e2919b7f19c1fa551a08c3e560075a36823af3e94adc2820d461875cf1dcd003d81c543cf638a7aca1b39523d3d29bbb7718a602347fc4876b52a86fbcfd052f99b7e9eb4a1c3742707b5003167c9c0e2a5f3d147cc6a20830685557e194d004aedd71c7a5344a7a13cd342b161856c1f5ed4ef2d7b9e681f28e75e84b534480286cfe14d690719fba3bd386189c0054d03b215a41b4e7834ab711e4464e1c8c8cf4a6f961b93c11da9bb77e41009ce4503e5240c173b9867b734ecee518ef5024059b2ca38ef4e0a4606381407292b4cb1f0793ed4f5915978c83f4a895923539e6812e7eea311ed4059136eda79e07ad3c61b9041aaed719502356663db1cfe554f54f115868aa9fda13c36cafc8f31c0cfe19ebed48a8d372d91a6cc63277230f41eb4ef2d994657cb53fc4dd01f4e335e7daa7c499aeb747e0dd26e75fbefe2692368e21e9cb01fa66a9d8af8badf52b9baf1778a347d22cfc9cbe9d631b294ca9fbd26719071e955cacec860672b368ec35af1c786fc3699d4b58b7b72c711ab1cb483a640ce7af6ac45f89d2eadab58d9f86fc397d7f0c8374da95ca7916ca7b2827963d38c0acdf0759e83aa5abdb68b75637d750c8658eea45598c687a9dcc0f39ed5cd78f1bc4de31f122e99a6ead67622c65491c5a9319753f2b1624e01c13d2a923d4a38085eefa17b5af0878b7c55e747ae7c40834189e4758ed345656d833c0661f36ef6152f86bc0be04d2616b3bcbabaf13dec726e92e353f9d5481c9218e3a67b551d7ad6cfe1f789f458869b0456f9c4f7d248159f2093264b75cfe62a4f1342de36d066b9b24532da5d9cdbdbb81f6b5e841603a722a9247a5f5551d6fa1b5e19f897a2df7da2dfc3fa75ad8e9f14cf00960b258a29597b0707927fce6adf883e206b5a0bdbc6d6f6b0692f0b4c24b89d16e8800e44487a9cfbf35c369bf0ef5356d26e3564ff847e546dd0e8ba5befb70bc912c9c0f9b38aed7c59f0f20f164fa56a90496b797da6302b7b214944233c8dbebf855688525469bd19ce68ff15aff00c551ca74eb29b50b75510cd6e881a6b7739237e0f030335c4fc72f89de29f87f7da4eb363ad5c6a16e6dff00d234f0a1a14238c1c0f4e3ad7aa7c37f82f17c3a4d5ee61d76e7543ac4c67b99e4511b0e0fca003eb818a7e97e138f5abbd5d66b5b8d3bc37716cd67f63bd8d43cce7ac8b9cf1e94af4fb12ea61d743c81bf69dd5af6f3416d1e382e3fb521115cd8c8b88a07618da33919ef56a6f0bf8faebc4369abc5e344b9b56ba5826b7d3c81f6785b8c707190327a718ae9f5cfd99ed6fb41b1b0d33589acf4db6388ac762a38933c485c739abbe12f0ddc7c20f0ceb88969fda7aa5ab0d90c2be6798323696c9e48c925bd33c557344e9e6c3ca1eead4e5be2c7857e234cda4c3e099ef25b2b3425eea79b1248ccdd4f1c8f4e2ad6bfa5f8ef5cb5b3b8f0f6810f85ee2cef635ba698a46d784a8ded807273cf38af4bb5d43c4b258ff69db4e9ac3dcac61e20fb52cf032cb8f4c647d6a7d3f47d3a3d7aeb5b796e27bb68555f926dd7f31c1fa1a872ec8e78d5e4d91c6fc4ad6e3d0fc2134fe2cd2ac51bed8b05888532f77214fb8ddc027bd798e8de19b2f87b7da56afe31d22f348bed4ae644d3e0b0b964b58a30320b923af3822be8fbab7d36e2f96f6f043768a9bd1ae230e88c070cb9e01af2cf889a2eabe1ff0002cf16897925e5e5deaeb23fdaa1fb40f2a46018056cec1cf6a6a4bb1d14abae5b4cf1ff008c7f11a2f1949617ba64934f691ced6f23a163102bd76edc83f5ac3f0678a2f2dfc51a1ce977286d3d26685a507e7da09faf4cf7afa8754f847a6df69fa5e8f62b068f6d6b731dc85b55540cc57f78a738073cd5fd77e03699a9eb5a66a45ad34bb8b39b708e35e275c825081c72011d7bd0dc4e9588a11d2c713f05fe2f78d7c53a9b116b2f8834f2ccb7374c1425ae46e041239eb8fae2bd0bc69f10353f0ee930cb65a369fe22b1bd7d97125c5caa60e71caf7e38e95cbdbda69babb5bcda2c53f86acb48bd7b616e76c71ca491b9b8623a8fe2af36fda5b4cd3f47d4bc2da9db5c79b6935d3f9d72b923e51fec9c139c714e314f5467ec6857926958f52b5f889f0c745863b0b8b4b2d22ea40cb2c767080533cb152471c67a57167e17f815b50fb6f80fe224fa6ebd7936db749ae43c772c58ed565c67a8f435c6ea5f14b42d7aeb4e37364220adb1ae24836b853c061c7e3d4d4ff133e0f69ff0e749d0b55d3a6b8d56ef53d496e9350b89d898d492c1b68236a8cf5cf6a9b599d33c1c22d252d4f4ebbf167c66f01e9426bdf0bda78bed607f9ef34f94b3900e0fc981db3f957a868fe38b3d7b478f594b39b4db56844938badd194c0e720f4c1e2be4fd4be347887e165e4d69a06bc2ef4dfb46e63765a4c923276e4f0bd4673ce6bbdf0efed4de1cf1c6997165e26d3e184caff67b836ec5633095e72327a9a4d5fa1e562b2c727ac6e7d211dc4379690cf03a84902b2c990c841ee18714f9a30ac3761c7f794f5af2af875f0d747f0f6a86ebc2fe20bb9fc3cf0945d21ae0b5baf23e75cf20e0f4aefa1d2e7b1ba58ec2e36db6fcbadc1cb1278007e26b9dc2cf43e5eb5054db4ba1adb954633b8f6a5386232768aca5d69e0bd8e296c644836b996ee43b551973c631c83eb4ba578a346f10ce61d3b51b6bc97697090ca1f201c1e9d0d16673fb393d51a7c3743919c526dc669b0a95f300f98672181cfd69ff00786472a475fe945cc5ae5d191b352e495381934acb4d6057041a6035586ea76e06420529555e4f1eb48aabb8b0a0414aab415f4a543c8e2801a5fe6c62855c9a188dc467148adf35500bdf14819ba63f1a5fe2069fbb0a78cd0047c96e9934bb4fa52b36dda40ce69db8f7a9023e453d73b7a529e78c52ff000e08e2815845ce7a7eb4ff00318291da9b85ea050393e9400d3b98f5c51b88e3bd48c7a6293cbf981a040abb873452ede68a44d8a0dd38e69369c640f9a9f81f4142b0607230699a11b2edc29191de9930555e16a4672323ad47212c38e0fd2980cfbc339c503014d0546df56a6a901b06a863b7761c9a7ac836ecdb83eb46d42c7230077a696dbc0e450007e45273434a19149e9dc528c3751c7a52850d2640c0feed002ab00cd8e0e3818a456dcb9db4a641b8e473da953e55c50509b8e318e3e948cc5b381834e674c633cd2a3af4c71eb40c60911b033861ed4d56dc338e3bd39b6e718c8fa52498c9e300d0506ff4e2943950734c46e701727eb4f662a40c7cdd4503b0d5e19b193df38a47bb489b69ce71919155af6ea6b5df2798abdb611f374cf4ae7e3f105f6a96b15d416abb14904cae107071de9a573be9e167515d23a0bf92e6f34d9ededae5b4a9e452b15d30e11bb1c11fd6b989f4fb04b5b1b6f139b6f145fd9a971797255123971c31c76f6ae23c41e22f13ea9acddea379aacba2e890a1861b6840777900e58606315d6eb379e1e5d1d65beb7bed485d2c6919822059d8af24f4aae53d4a741d35ef32b47f10a7d3747b2b858ad5835c112c9613078e300e0139cfe559d2784efeea6b8fb56a92dce9f75ba6b8b7917ccde1b90148c6011ec6ba087e1ef872f6cc4165642c206c0d9c0192bc861c7354bfe107f13d8df59c3617fbb4c407ce5320c85e806726a91e8c6a412b19dacf81a2f06dadddf784e7b5d2ef6e15256b26cf95295c727d06dc8c7ae0d6d58783e3d526835ab9bb79646854c90c242c2ac472578fe74e5d35b47b19a49d2e2fddd5a01159a87ebddb3e95afe0db1934cf0d5959ce8d70c4b03b98065c9c8cfd28319ce4af690cf16787f4dd623867bf92311400331b94120da38da38e6ac68fa469ab07da2ce185ad2400a4889e5f39e70a3f0fcaacea1ab1b58d952286f44642b47180c539f7a7c37f79756abf65b15b68d8edc4a41fc40c0c567738a55276b5c93fb219eccac37ad06f932f71b776e1fdce7a0ed5674dd22cb47133c052df7f209c0566f7ac49b47babe8caea1a93dba07ca7d9be4feb4e5d3f46b78fcb374f3fb239e4fa9a5a9925265ab35874f9a4f32f2196577f31a357cb2927b1f4fc2a1bcd7164ba2d217be9083b767dd8c0e83dea18f52d26de4da964c0a8e77f24fe38a9ff00e121b2b58e668ad218e38632f803f9fbd3b32d41cb4b115bf8b67863f2db4ad42f5c93992db1851ef914d93c4cd24b3a5ce84d6c645c6e9e41ba44c636903bfbe6b2f49f16eabac4eed6c8b244e32bf29ce09c7a545abebf2dbea416e7696831bd5a3ddfae2af959d51a16d916eee49f56f0dcba62f8724b7b4995a2296b2147d87a36477a93c3f2dfe83a3dbe9506877125a4317940dd31de540eb9ee7deb3b4ff1647009eefed04991c82324a85ed8e98e715b96be22b996301ef1844a371dc7391e828e565ca0ec55b9f152d8599b74f0fdecb73126e36ef1ee8bd8e7eb8ac5d37c69a84c9e64f35c6957f21d8ec6ccbc6a33d947b77adf7f14ea51b339915a19c7969ea16af43e343e58858ee78c6cf918038fcb35228b515f099573ae691ae42fa75f6b36ab2348bb6e1d086dd91ce0e319f4ad9d5357b048ee15f56b11696e54add2b1c4240c7cdc8c9cf6c55799f4bbe859efb49865dc432be06e0410739c75aa3a968fa1eb533c2b04314463dce6e08da4819e9ebef5495f7239632fb2715f113c13e2cf107873c50f657da6cfa0b488e915b30499e22a37c9c01f5e6b06cfc1da0f8aa1d27498754ba96ced824e74b750595c2e0b13dd700e7f3af4eb5f0bd9c365736161e20c5b3004ab1055fbed1ec3d2adeb9a2f88ad74fb8b7d1974f86e9e058d6f618d77a0c718207f5abbdb63d0854e5b451e69e34f84be11f19eab35bb5cde5a5f69e524fb3c520fb3ac6b83b80c0e0e31c9ef5da5d3c7e229ac2d6eec626b4811634f3994c5246178c6dce3e9eb54745d0ac23d2e7d375ad26f61bcbb8cc3a86a523966b8246376e0063e82a0f0f78497c3b75796516b725d7851ade34b485c832c2e0e7a8033d3bd1b9ab9b6ef7381f157c29f04c3e20bb8575295f5530194e91226ff002d4b71b5b1803d062b90b5f82d776de209cc1a2c96f0c8d1b34b2840813827df38ed8af76d6b4bb946927b869b4d4fb3f9306ad611299665ce7e6c83d2b22fbc450c0ef7dadea0a343454823d5a5429b980eac077ce07e34cefa5896959a3d4bc3fa769b63a7c1f67b7855a34c0555c0c607a77ad5525ba9da719da39c5707a26bab25aa1d34fdb6cf00996338c13d187a8c574367e22b46ca5c4b27da14ec92118dc3b8ac395dd9f1f89c3ce552524b737e4b782f2365951678dc70b264fe9f5ae72f3e1a7876e67b5960b56d366b625a296c7119473cb676819079eb53cfaeaa6f9a1900b700ee6208278e9823f9669da3f896d35cb6fb6c32ef8d711fca080083d08eff005a95a238953a917771396f06c7e3eb4f323d4b53b5bb86199ca24d181249116f970470a7a7506b5b49f8b9e16d6f57bad252fe3b4d4eda5f2a4b572776ee9e9d2ba88af21b599a2742ebb777b726b90baf877a05d5e5d5fdb5a45a6ea17057ce9e35e65c1c8cfa1ce3f2a77e6dca5184efcc76b246219769704fb1eb51b483a74607915c2f8b356f13f83f4f96f747d1878aa61185fb1ee11b2b647cdbcfb7b56958fc4ad227d7a1f0e5ddc47178945bfda67b383e611a7724f4e2a6cce2a94795fba753e62b2f238a679cbb78152346f1aef232a7200c724f5fcb1ce6a28dbce538c2aff78d230e5ee395c6fdadf29a8a69446e086279e98a53895882c09f6a745b15be5f9877c8a62b218e37336791eb9a7bee55423d71b69366e0714acaccf93f8521f292bb08f23ae291640ce57daa28e3c37cc496ee0d49f21623382681590d858eec15e07bd48f2286e78a67eed541c914ec29ebc934122ab2b2f073ed46e44619c81526e1b708067e942e3b8c9a00037a1c8fa51f7b8c62a4fa8c5201f2938a042ed01723ad47925864d3b760f5c52e075ef4120a371c5149cf6eb454d8762a281df9148d86e40c538038a6f383c558881bef535d4af4c91eb9a9245dbce6a1f9b246722980bc0ebf9d06346e879a5da5a8d9b3af068288d77331033f8d3b6c8b8c804548abb54f5346d3b876a6047b4ee1c53995bd29fb4ee1c714f6cf1486451375c8e6959b2781c5298cee3c631de94b2f41d68288c47f31f97a77a56de3daa456f9bae050c72701a98ec4277f7146379c1e0d4add48ddc0ef8accd5b588b498599de347380849c82738a0d69c5cb62d49325be77fc98192cc718f4accbad5e5b75cc856dcbf119987073df8ac8d6358b6f0fe9a6fefa463732c81628a342ec4f5e9d87bd5cd3b5abbd49619a6b24f2e71b55940217ea0f4a763ba342da9cfeb0eda86b0da7ea3ac4b04bf2c91bdba71f43f852dbdbea50b1b55b05bcb6da764c4f2dcfa6315d4d8f872df4bbfbbbb123ddc8c32eb230200ebc56ad8ec9e38de191fcac1cab2e30734fd0f43eb2a9c796262697a7c36b3fd96e34d8e281e3fddc8ac492d8e46dc60549ab5aeb36f6b6aba1e95637aaa4accd792ec083d578e4d5ad5bc5567a66a0b0a48f7d330012de15df96fa5519aefc45ad28163671e9c993be69f048fa28e3f335571294e7b9a967a4cc373dc90d0b618a82308d8e704d55fedeb3d32d648ed647b9950952d1a02393d0d61c9e15b78e6f3359d72e3579320f96cc5501cf00283eb4f6d7ac609ade1b485a32ce54b1ee47b7ebf851666f1a7293d0bf0c1e20b88dda0482c2275dd9d9f3119ee73d4d3f51d374a5d897f7325c4d1b29ca31e3bf6ebe95caea3e2e9bcc11a4f33cac4308c0c8624f4acbd59b5b335bdab411d8c92f119b87da5f27afaf14ec6d1a0e6eccf419358b3d3a46f274d595586e595c649fc38ac8bff00184b7b0a450111957cba20ff00ebd4e7c32967a335ceb9ab5c456b0a6dc42541761c9001e7f5ace8757f0c68d6b26a9a86992e9da7db26e8b54ba20a5c923eea2824b1c1f4eb53a12e9c22b72599aeefd45bc71ca4751211c7d2a4b7f0beab74d93043691a7dd5de370239c915af63e2ab6d67495bed08ac96e70d14254acac31dd4f2293c517f73a3da6a17f7d7d35c24968a2db458a3500c840390c3e6ea7d7b7e1458c7da38e89102f836e2e239649ef1609ce1636cf1eb9c5575f87b6b6fe43c97b712899c0937384567cf2707b7b54f2c77be22d02d56cef0e99abf94922c375f344c31dce3ae78ebefed50f8d343bfbdf879776b71ac5b58eaf046278eea4388d19581ddc67b8e9dc52f217b5a97b1a1a96bba1780e29a292ee3b66e8b6ecc049237b63a0a8b43d2ee15751bcd7ac74d86c51c325cc57070dc6487cfa647d6bc96ead3c29f187e1dbde4fe2d55bfb7daf71aaaa0003c6406d8bd7191803debbab8f863a4fc42f8576ba243aadfdce9f3ac6ff6d9242933aae0607a646473eb4ecbb8dcadd4daf16068b4137be1b9346b8791c08e4bb4f31194f50b8239c66af5ccda4e87e137d4b54d96d0d981f6a95636da3233900027ae07e358d6fe01d0fc17f0ff004ef0e2db5dde5a599cc51aca5a6077039240ad3b1d2da7fb46956935bb42b06f9adef7717391c6e24e3dbbd2dba994a52bdae598d74ad6b43d3b54d360f3edee4831dc48c230548ea01e6a69bc236115bc92e0bc8c5be687fbff00ddc7f5ae1a5f00f8cfc411eaf05f6a563a4e9f948ec2d6c1b7aaa82096e83d3a57a6e93a7cb696fe5cdcb6d5e73f2f4033f5c8cd0f41d4a93a6925239587433776e37ceda7f932f979bdf954f19f5acc8fc1b3477334a2fad7518d9fef412e78f4c574b7575a0f8bb43d41350944b63138927566db226df4ce39240fad799f80fe2e785afaeae74df0f786353b75b4bd16f34b3c5851b8e03649ef9a71bd8de9e227cba9d549a0cba4c012e206960743858864804f04ff0076addade8b183ecf69762391c0c12db882067152c3f14b4e93c45a9585f69b71a4ac127d9127ba8cf977248e02e33deb0f5cd7b50d3b43d4b5593c331fdb2d9bcab5b18e32ed37cc00c91d320fa1aa34551c9d99bf26b57762a9f6d916e005dc9b86493dff004aa3f6ed2f56bab083ec7269f1211e63b0c0279193f9d79269bf1e20d5f5ab3b2f11f87ee3c253ddcc2d2ddb63ecde47520f519e3f1af565d2da3b73243ac5beaab148619fc83bf69fa7a8eb4ceb5156d59349f6fd3fec16ba6de9d5a051248ca8bc47f370327d7a525d7886ca2b7369ace90123c6eff488410ee587278c607d2b2eea3bcb1b785e369496e5180230338c6703eb53de78eb51b5847db6c45edb01b3cbb850493df9fa53358c1cbe122b7b3d7efbc476b71a0ea3657fe1500fda34e851566f30a919523f8467a7b75ac5f13695ad431dcecb3b9984e49090afcdc1e32dfa57410e99e1cd524b7bed26f4e837aac3cc8a66291b71c8c7e7ce6b4246d5b43660f73fda766f9c4c8fb9541fa74a65426e8cf5573ccbe185edd5c49a9c734579a55bdab6c0b78ec58ca7d01cf1f4ed5d7af89068baa242d730c32386705768c81c17db9e9cd6a5c78974eba8e4863b666bd5f9cc770842b803f5ae17c456335bade6ade1ed222b8d40a90f63c32b6483b03376c8e955a3d0ea528d6bb9ad0f57d0fc59a7eb36f2c8b771bcaa31b8ae37fd39e2b661922924210ee2b83b874ce335e1375e255b1d37483ab5a5c786753d66131c70b404450b8ea493e9f4a21f88565e0ed3920d4fc60f773c837c4b658659b00e71c74e3159ca1d8f22a653ccdce8cac8f7c50cc8ccaa64f3305f68cf04139c1edc570ff10bc0faaeb16a2e7c3365a7d9de5e1fb3ea1a9cc81664b5200611e3193c13dbad79fe91f1a2fbc5936cd1f5686c2684ee65bb4cc8e883a05c8eb5d06b9e3cb9d3dc7d8ef96eb5d9190f9539da0a1e7711edd71df153c8d6a62b2caf0f79330211e3ff00841e3cd4f57d62fdb52f8796d6bbde59a5334b0205006d00649f6f7c57ae783fc71a17c40d2a2d4b42be59f4e9091f3a3210c3aae180c9e4714fb2f11e97ab2da5acf2c314d77191f65997efe07ef085ee37639ac8f117c2bb1d66fb4cd5ada5bad3afb4d90cd6b0dbc9b2027703929c024b01f854ca48f3b114d4ddaaad51d66e0a878da46d2463900e7fc294cc159776769fe202b074ff00104fa74320d69648af249301586405e4919ef93cfb57461c190b28c21070a474e9fe35079528db6077c11b4647634e56dd8e79f4a872638d463927ad2aaf20938a833b12c9222ae4fccc4e0f6a636557213229154636bae4eee0e6a7dd83803e5c53208b68f99b1c63b8a72a85e14e370a58837dd6fbbdda95a3f9f728ca818ce68201be5652bcfcbb4ff8d3bcc0ab92a4522fa101b348db5d8009c77ed400a936c3890e49e80549bcb723e51ef55e36f2db04724e01352ccdb6450c487f6e9f9d22ac8795071f302c6841952c4e17f9d44acdb9937fef053d91b72eff00b9fc245326c4a30bc9e94532453bd0a9e01c628a00605ddc62858b238e4d6a369e3ee9e3349f65589768233537641ceb47279873d2a68edc9c74ad58f499998b95f97e9527f65be7eee07ad324cafb2edce7a0a0d9ab2ee07915b22c082c31f8f6a77d802f604fe5417631961655e578f5a72db963f778ad46b76cedeb41b561c638fa52bb032e4b72bd0714cf28b74ad66806d3c1fca98b6e579da029e873d699464b46c58fbd27d9f9e9cd6b2dbfcdbb1c535e1fa2d31332248c8e2ab5dcdf65b72fb7737403b67d2b5352916c6d2495d37151f28ce327b7eb5c5ff6b7f6f5c5cc444f0496f8c4063c79ac7b835676d1a0e6b9996cf8826b9d3e46bb83ecf321216146dc587af415956f6b1452452dd1ff00489b2608a75257ff00d7e9ef8ae9e5d0edbc3f62af34825bf936b059392b9a71b79a4b45692392400ee45c02037f8507746d0d12391f175ef882ce4b16b1d31a4dcdfbe99a21218d429c71f5c77abfa108f56867852ec5b5e94c978d76b2b1e09da7815a5a5e96fe1fb7bbb992eaed51b32949a4dc818f5da3fa550974797c497866502c57682278fe4661d726aaf7364c8750bd9fc326de2b49db52bdc6d9633b43b7b8e29cd63a86a56e64d5ef26d3ed55c30887c92007b36323f5abc106931c8147da6623634cea09faf354bed535e090b6f9ca60ede703e83341aab3d6c5837da7683209b4fb26ba8cfcde731f9863be7ad644de286badc25965092062b1afca467bf039fc6b434fd0669bcdb9bd97ecf0919545c9257a74ad75b5b3b35478c5bc912af1bbef62996eac6292472f6da1dcde436f2a48c3079f378c8aabaef85d74eb8d3e69e6579e5942c69e66c50339c67bd77d7174b25bc4d6856452c33b460af3dab93f1c5bde4d7fa73dadbc0f6ab28329ba84c83f0c118e69dc958a7164b1b1d26f6c6dacb4980c84812cb3303b70739071cd677882eb4cbbf12686faddaa3eab891628981c9e4e083db8f6aebe48adecd62630867e8045c609fa9ae77c75a7e9f796316a170263716cfb92e616ded1e3f84714ae385794a46a6a1ff1349d5eeac3643090a9d300f6eb517fc2276d70d05eb471aea36b0bc16de611e54393bb7b291827b66b2355d4ee6eb43b6bcb848eee15742b160867391827dfdaba9b1beb4d463114f347e6491e444e78071f733eb49932e68c74dce5743f00df2fda25d475b8f51bcba4646bbb7411eccf7400e06056af84fc1b0f83f44b7d22f2e6e35c998c8d1dd5fbf9af82c48c7f7700e3bf4adabcb8d3f438628a4b71171c4718ef556c5edbc41b6e629a233db360618e0739c11f4a9bb31f6b36b5672fae4f77a2491695a55e5e410cf9cc720f338270d86c702b46ca6b6f11c6d6174f0cbb804930ac3732f183c74c66a9d9df5c5f78f6579dda5b58d7622a7ca8beb49af69ffd9fe2bd32780b4112caa6455254b2b671df939c55d8ebd251b75372d7437d3ef2dadececb4c834990334f0c362b8dc01039c71fd6992cd27882f0e976f2ff0064e996e773b42c1323d3e94cf0978c353d6f52d69aff00419b46d26d58c76d733c8a5a7c8392006e807ad737aa6a3a55bf88a2f3668e6b5922dd74d1924c3cf43f8734d2ee674a939c9a65bf0e4b71ff000975d4161757371a7c676ab39f9538ea3ebd3f1af42b9822953cb9f67ccdb9bcb006703919eb5ca4de23d2b4ad0649b43beb19990e4c2833263b0c7bd49e1bf17dbeada5dacfac40ba649752f976e66f959dfd36d4493162a9b935cbb21de21d0b56bcbab6b6b2b9bab6b3913cbc44c14609ea6b074cd42efc33e34b5d0e1d54dfdbae3cd13386206791bbdfa577f6d0cb3de7fa4bf97fc24725460d79bf8a20d1f55d79acb4e862fb43858e49adc9dd9279e7db1d688fbd7b9be17df6d496963bcd4bc27a76a4678a78943493a4f3aab797e6153f2700f2071f5aabe32d5adf4dd1de28914492ca2490f4cb0e17bfd29c9676be13d17ed24c931b68fc9134edb89ae73c376bff000945e3dcea6bfbb66de888376f5fe5c75fc2851b6ccc68d34db9fd946c78775afed8d05aea0b2f2afdbaac851b2c0e39273fc85646a1af27846f75b9e3bdb84bc9ca4fe5cb6cf3411301f36d2a7d3349a1cc34ef16496d690ecb50a5e48a350c0f3c13f5fd2ba7d635a7d1f4dbdb96b5fb6dbf091d9f7762c0e7a8e0554b47a1a548a8d4514b739d9f548bc6d3c474abab19751b568e6956f6d449b908c9d9c7cad9f7ae7f5cd6350f04f85ee2effe11d82d6ee5d4002b71f246eace1779c724f3e95bcbacea1e536af0e91696b0a0f2ca81b5f7753f74f3819eb4cd4be215c5de928f77e1b9962b85ff0047ba06364ca9c86c373d4551d9ece7a44ddba4d32c6dde1bbba80191c37cd36e54257f84678ce7a573de2ef09db2d8add4626b88e3758c956e0838e40f6fad70dab6a96faa7db34f8629b500e914d71a95e47e5431cb91b403807e6f41e98ef49a7fc45f194de26d43c1561e1bb6b3996d9665d5065a1404854720f52c09fa7bd3b1718ce94af736b51f0b30d2585ec6de5bbf98acec0103b018e6b4bc3f08b6b75f2e33e5dbf60fc1e3a73d6afeb9af0b3d3351d2eff5bb3d32f618a179af7504c79838e9818e707bf5c55ed1af3c2fa8470a5b86499d14833824487682587b1c914ee754aac9abc990d9f88348d7db2e87edeade50c85dbcf18073ef49a75e5e7815af24b8b34b9d3a59fe49a227cc538f41e9d739ed54758d02d239e555184cef7f2136a2739a6af8b961952170f71641361d832a7b66999463cdf0b19a9e9716ade1dba7d30bea825dfb23bac2ba93ce1463924fad7977837e14787bc1da7adcea4b2ddcf78c6d5a2d617fe3dd483958f69f9724e075af6b3a5d96afa5b5d68fa9369d7912308b7302bbb1f78ae39af2d87c33e20b46b6d1cf88a1be9fed4b77aa4f76cad1dba104ec880e77703a9efd2a8eda32b269b333c65f07f48f0ddbe87a8c1e1d6b1fb05c4d0c92f9ad2cb2291b918907a7d45798f88351bfd27c4de1ed7ae0cd7d746efcd486e2365f3235e89f43d2bd03e221baf02e95a95c47e33bdf115fc3a8465ece675da001b914e074238cf35972c1e326b39f55d47fb2ef00916786367c3c113ae41039c719c7b815573d5a3aa3cef54f1b78a2fbc5f6be2a6429aadabc85608f2cb023120a6063d41c7b57be7c37f8e5aeebda849a25e69105fddc3079ff00685dea23cf52d9e071dabc4f45b55b0d48ea17b36c8af67c13bc0181c9ddcf073fceba95f1458e931dcc3a2dc2c5f6c63e748b962c07453c8c8cd4d94b745d6c150c42b545af73eb18ee2d7c496692db4b6fa8dba8584b40dbb6cbd4e78e31595af6b37fe1c8e1961b37bef39cab9ced5846475383e9e95f2e7836efc4da478a3c39abf87f5a9bec57371e45dd9a81e5f07e66c67b0c9fc2bec4b2bcb5d5237920db2165ced8df712c7a823b74fd6b9e51b1f0d8fc0fd567a6c2ac6394de1e441b98f1b7180460fe3fa5092a310062453ce54f02bcd35ad3bc4fe03d53c47adf876da1d617549adda3d2a627318030ec492428ebf957a0e93aba6b16f2153035d5b048eea18f9f264233b7818fc6a79743c69d37f64b463259ba96539dbdbf3a743e63b213c67b67a529070067e63d587434ab1fcc73d2a0e27b5c71756ce410738ce695a3fde607cdf8e2855daa4039e73d28dbbb77de04f7c74a440aa8be6286a1f7333b019e385a5da76e339fc2a453b707a5022258cc4a1e45dc4f18cd3594b74e429c81e9526d2d1904f342a8ee7ad228468f12164392c31bb1d69159155177700f734ad1968c73b235a8e18c2c6d85c36770dc339aa0245cf196e58e78e68a8d781b82905bf868a09b1e8074d8d970464fd285d221ea539abeac32323f1a0b7cc0673585c0a7f650a300f1f4a635b83c6dc8abc573ed4dda17dcd170b228fd8558925783da9bfd96a4105735a41bda8dc3d28b819a74a50870bcfd6963d255970ca4fe357d57e6a78186c8a2e067368b1463255bf13517f64c0cdf3a1c7f08cd6b49213c1e4547d7b64d3b88cb93498dbef1e3b76aa1abdbc1a758cb73f32ac6a49651923f0ef5bb79711dac7990aae4e06ee9581a86b16ec91cc5de442db1561eec3b1156b5d4eaa349cf57b1e6fe20fb46ad3d95cdb2b6a282400428ac1cf1c123d8e2ba7d2fc3971670892e0c773a885dd242e7680b9cf0707902ba5d0747b983fd32fad332cc1903c0a4346a7a679e3d2af697a7be910b411235dc133b07924604ae41efd4d5f323d9738a828c4e0246b9d735c8e24b065b5b78d8b331043fa7eb8ad691adf4d48c488d1bc8bf2c654fdeec3f3ae81bec5a1ce0a4092de1f942313c7e1de934ad25ec7ed77da9dc2c85cb3ed1cec18e001517661bb38bb5d0af352bb69352ba58506708a318183c0aab756c4cb2cb6fb9e252172fd0015d45feb0fa81b69d6ddc5846492ac0166e08c9f4ab9a1e9116ab1b5cca9e4db924ac79eab83c9ab4d246df0abb38c5b4b9d5976db418298662dc0c13fad48de1d9ad51e763845cef8917e661df15e936ab04d6aad692c2618cf961a3e78f435913e9777a95c2342c12de393f7a6e3e5c8f61de97318badd11cc485ae740375a6c6e8366d115c0e7e98aced2eded6eb48b792f4dbda5e4d953148c37673f5ef5dedde8cf7d328b4bffb35993b1c20197f5033d33d3bf5acdb3f0be9da859db5c451c3a90b299845f6840360079e47707bd1cc2dd6a7133dfe97a6ebf06897470f7085e3923c8d857920f1e80f39a9b54bc6d24462087cd87712d21f9b2315deea51e909a85919502dd4c19418e2ca827d4f6a64de138ee2c6eed19245b9c129347d3a67834f98956bea79678934f4bfbdb0bd8242d142c19d724751ce7d6ba98f4db29ec44b6c8b1c322090c519e0e41e79fa568786b4f8354b1b9b17921fb5ae51f78c3f4c7157345d06f341d2e2b4bd963b3087cb8a4620924038183f9d55ceba9251b451cdc961653466c5e252547da0dbabaee0318cfb7d2b97d07fb393c5b22c16de42c78da93f2727827eb8cd7a3bf87d74ab959a7fb39bdb960a6799769704f41d0fe59acf97e1be9fa3f8a16edbed1e75e74ded94c8e4e3db8a1495b5269d4d1a919be4ceb34f2b69ef396753133b8cb02718e9c60735059ac563aa5cc70c096c3ef4c0e38f538ef5d15e78141d403477934ab2a2e17cef9460f6f4a72f806c3fd26e6ead8f4daef24a5430faf6a5cc8e77d8e26cf5ed2ac75cbeb4bab8b286ed5808c952a4eee983d3a526ade15b8d4ae4ce97523f9654f98c460800f039aefed7c27652de89a782d65b5054c2ca81cee0320138f4ef54afe29eebc4c967a2dec6520849b9f3ad898f731e81b03e9d28e635556519a68e23c6fa3cb3f8560b68f4d7d4eeb72c4ab1fc81063ef3631fad45e06f86b3e83a1dc8bcb686fb52ba903ca9b81f947451d7b57aad9e86d0452a4929925272eb16718edd474acf91ad2deea411ce44a1953cb45f9727a678f5a3998e189a9083a37dddeffa1ca59e83a525db326976f6b3c7ccdf2e371fe1038edeb5996f7f6daeeb935a6ad6d6b74fa7cbbad44637bc4d8ce7238fcebd0f58f0d1ba49a6d5004d3628c1d91e73b8739c8e4f4ae385ae8734d0dcc7a92d919e5521eda2d8091c8463c7271ce69dcd2129ca37687c16575a958dfc2970d1caee63fb4c4e08563ce07e1906b8ff0008e86de1bf17cef77728b33968c6e5c1c1e983ebefef5dd59fc33b4d3f569358d1ee2789de3689adc4c5add989dc588ecd91d6b9dd37e1d78de6f106a17fe26d66c65b157cd82a29791077e78c71914e0ed73a2954e48c92ea6b788b47b9d6b4b9e08a44ba903731e38c7d4903f1f6acff000bc77ba3784a75d49160f2f72158bef2af6c1c631f8d755e20d3edbc3ba23dfdcdd88a2890bbbe42a9e3a1cf5af1db7f8ad0fc65bebcf0d78756ea17b39d2496e235fdde31d33d0827b55c5361414a4b952d0d6f00dadd4571ab6a0668c192455b556e588f4233deb7ae5759bebe86dcc76c74a58099e6120f33cccf2a179c62b13c17f12352d7b5cbdd1d92c85e69720f362f2d518460edddc75e6a3bcd46cd756593c3be23759a7bc686ef4e8e2560d21ce7be4537bea774a32735ccb523fed8f33c413e8f637a44195245c8c47e983c773c75ef5b2de19d7750d5e0b6beb8d35b4c588ac51db056656cf418ce3eb9acbf0fd8e99e016d4354d485feb01cc96d242b6acf26f39206d00f00f704d73fe26f889a37c2c5d175fb44bed3b499f725e59f92d9e73f7777439c67db347a1d128b935c87a1dd69f25e5f246c74f4f0ad9463cd8988790ccac02f39e00fa557d5b5ef0dea5a86b166eb1476b369ead77751b8dcb127036853bb8f6ae17508f5abcd2d75af07e851be9b7d73fbe86e4b2bcf0bae7ccef8c13589e09f83d7379e30d2b5e5bab8d2ed3cf31cfa6dbb6e79e304ee0c36e769e9c1a718f764c70fcd172933acb4d5fc15e3af0ff88746d235082fa76b2640851dde554c127e6e9c85fa552f02f891e3f09dfebd1dc03a55a69f1c16da6c117fa536c3b8b10c3a6702b90f1cdae9be19d074e9fc3f0c9a4eac9aa334d7488e1f6b39054f03820f4aea67baf05780f4fd38ea7733ea1a9dcb7d9ed0c0c5a52580461d71d49e08e300d68747b14e3636acfe3968379a7e9ff6785c4da92b2adadd8db2391d73c7073daa8ea1e2ef0beacaba2a09fc3da85f47b626b853b59f70055703a9e47e35e77e30d6359b1fecfb4f0df85ee2e6f23f3937dd5a64c48e70496e9bb9ea0d5fd5fe0ff89351d4edad3c55ac25b2a88ef45e4726c7b575c150beff008d1b1ac68460b73d121b6beb382d6d581b67822d89318481280718cfae71d68f0ce99a659ff6e4cfa28ba7bb944d3bac855e6948e1779edc6de9de9f6f6be2cb1d6f414fed7d36f3c2f9d934f7d74167993ae49248639c702ba4d4349b45b98ad6c75049249d59adedd46338c919edd47ad45ce69fbaf43c63c5dfb3ee93e28d26e574eb39acb5ac9b97856e8c862058679ee474fa566f83fe0feade1cd4350d76d3c5702592a436cd693c4d207f6707fbbcfd7dabd634eb8bcd22397ecdf306225585881927dc0cf51d33dab5ae2decbc59a644ba80922b8c00f1da923738e41e29dcb58871491e17e20f87faa429a949a443a76b914d73f6b956589615b66919768452c4f201e6bccfc4de13d4b409219b50bcb1b07490e6dde605b9feee057bfeb5f08c6a3e20d7f5ebdd6eeb4db216c0ae9ea312651485e06738c935e71e00f83e9af78cacaf3528355934cb8b469609258195257dd81b8b0f97d7f0aae647ad4f15071d59cb7fc23fe20f18585badacf6eba3c6e215b8b4ecc7a961fd6bd1bc03e25b0f84b1ea0b637ab73716b78c26db83e66147cabc67f1ae6a6f069f09eafe32b38a5bc9ac12e37335a382a8060f19239c671ef576fbe195f782fc40fae5adc343a35cc51cee6f82ef62e07ca49054b7b62aac9ad4eba94e962209495d33e8cf86ff0016bc3bf12b75be917cdfda96f1acb7561e53a845fee82c83393dea96b9e0c1e0f7d67c55a19bb93589499a6b149094b8207dd09eb8c8cfe38af9cfc3f6ba9e9be32d6758b0b78b4fd4bcb1b6ead41e148e030ce3915f5b7817c4c9e28d22de6b5945fdfc70a79c4a9dbe66d1fa64f3f4ac254ec7c6633012c2be7a5f0943c23f10340f1a5e5f58e977a24d42c541bab37465921ce305b23bf35d26d0a00ea7d457cd9f0bee3c55e26f117883c41a4cfa759dfa5fc96faa2b5aba958e33f2ec5fe2ce08ddfed74afa2f4dbefed5b186ebca6884830372edc9ee7079aca6b94f9ec5528d3778eccb19dbcd3d7e6f5fce831301ebf4a9117b631591e58c0b86e94a48e86a465da3a5336d00227cacc5bf8ba7b52ed56e36fe39a4da7bd3d476a64dd88631b703f5a561f2f2734adf2d47249b8600e6a82ec8d82e7249c514e5859fef0e28a41767a2d3c63838e69b46ec8c56058acdc5464d2914d6e9400f5e94b5187c76a78f985000adcd3c1c9a887cad4bbb1402571ce3dea369047ced2e7fba38cd2b303c64fe02b1f58d5eda1d966bba4ba990b055ea9cf19a68d21072958cad6fc4eb7a214b049239965c6264c16ec481dc54d6be1896c6459ee2e144f2482538e899f4f7f6aafa0e9f6965746faf2fbed17c5bca58dbee44be98f5cf7ad4b1b14b559921692fcb4fe6b367fd5e7ebdab4f43d7d29c6d12c6b3a6bde5d5bdcc5ad5ce9f323f2c0fcb27b15ab1ac6a32c36421b75325d4c76e2351b97dcf1c555d43548b45598b4af757454b47030ce78e00355ec20934fb37bed4656927760c96a83013233c7d29108961b58f45b659ae5a479d46ecb7ccf9f41589a96a136b0aaedba42f93e5a71c76cd36f3fe26570cde74c15fac2e7815b7a1685f633e7cb719cae16351b401e87ae6a8db48eac8746d1f3e4cb727632823cb5e4743d4d6e49711da5bc43c82e99dbfbb19e2b9f87c587505bc1085860b697c9599860eeefc771f8d695bc3ad5c348b3c912c407ee648f8ddc64122a5994e4e44b1edd2635fb35b6d8669479a91c7d32719ebc63afe156c3b5d424c5148225720f9abefd47b5737a12eb6f0eaf6da9dc466781b724b1821190fae40a2e6f2469247d38ccf7876a990484246a074c628b3279343a8581238c15d8847cdcf2a0f6fd6b9b1e20b1d3acaf05bdbcae6094a916e991b8f27f3ab7a47882f92631eabe5a01f7250bb4935951eb1069baa5dbff65b4715dcca0bcbcf98c7804003d79aa1aa6ec75563789a869315d476cd0394c88a54f9c63db159ba44b797ab249736925bcc1bee33672bd9811dab52690c36eb70b134c36f2801038ed9ae4b4cd42f1afee20b5be5bf859fccf2afce1a1627a2918e3d8d22795bd8964d026d3b5e82f55b659cae7cc455c9ce0f3576d351d2fc74d7568aa6e63b39b6cde7290430e8c39158be1bd5ef5f58d62d99e59efa160cd6f7720312ae782981c56a5a4da8bee4d461b09269a4cc3e53ed23ea00e78a0e895db57ec50f0cda6b9750cc4858208ae196286e144a4a838077372b9f6ad892def1e1bb9758450218c8856dbf8867a63d7deace9da5ea16faa5ccd35caed998058d7e50a00fc693fe121b15bc36bfda31cb313b4c0dc90738fc68216e60e93e2ab082d638ad34fb89a685199d6400aaf7fbc6a29a6b9f1f6997302dfdbda86263fb2ed219b23b1ce2b5f54b1b5f0de977a6d46c33b64ee191bdb8279ed8278ac3b78edb5482d92ca126e5194492c3f285c7f153b1d54d424aea26de85e137d1f4b683cc66976089559f214fd38fe75666b6ff004554b952eb25bb23cd0c813e61c60127ad58b8be2b6ade43bdc3c7f201c12ec78cfeb5ccdac3791eba2e2f3569af6e8466d61b158559217c6777a1a47238f34ec3d756b7d03c2b04820956e64630a47732191cae70391d79e6b0fc41ff0009347670cd25cc7686e182c70b28121edc1ed50ea12eb1637d6971ab16b899599576458079ec07038adb6fed1f146af66cd6460b6b7612ee7e19b1d31ebcd347ab4a9c68ae67a9c578c2cbc491df695a7db6a37178f75f2363e42808c127ad6afc3cf09be91a949a15fdbdb5e279a6422e1cb12e3f8b3eb8aeb7c4da15d6a1a943769aac7a6169004dcbb9b1dd7a8eb4783b4b82cb509a5844d35cdbc87cd9261b5813dfbe462ab9958ea962297b171b6a676b56afa86acba2e8edfd9b048d99645f41d581cf1cf159b65a5df49a96af6b25f3369f66b849b04976039ea6b57c636634bb8592ddfecf7778ec8b6f01dccebb8727d013ce6ac5b687acdad8c96eef0b0986c66ce7cbcff7863f5a5133838462ae71de20b5d5b58f0cbb5e34375a3ab80b13479ce3b1f5e7156a6d2b4ed27c27f6ff000e5c43e16bc9301e410298e4217ba8ef9ed9a58f4fd43c4de65ad96b30cb67a7dc08ee115b25181e40e076cd7a15be8fa7e93a42a4f6f1fd9a3524864243367393ef8e6ab9b9744554a90845463bb3ce3c37e19d321d0351d4e39ae2d352bc5124bab4768bbcb0ea067f849ed55d7e1b5ed8b4fad784f53b76bdbf7596596ea046cb85e48207cb5d0ea9e3cd3bc416173a5787ede4bd671e54a631b42f3dbdbf0acdf875a28f0dfdb123f11c71c3230cd8c2fe6056eea7dff2a399eec5ef28fb496e729e176f17e9f677f79e28d0175cd42c7533b63b5919c4b115397403001c1e8735cf5ef87e4d73c29ae49a2f865af9acb59174ba7dfc4b24a7237151d48ee315ed97be34787c47a46816f66f7da8ca4c92ca89e5c691f38248279f6af2bf1f78db5bd635ed4bc35f09b437b6d4a4b90da86a117dc3202377cc4fa66abda4b7b174aa4d3d51ced97c4bf88f75fda7a4587826e6defadd94c70a6dda14903674e38278a7596b5e23b6bad72cf5a906932e9ad15ce1edc94b7565e57208fe2c719eb8ad0f1725eafc74f0d5bdf5f5d696f676bf6dd41a27606721704f5f98e6bd53c5170751d5e5b8bf8a47f094fa6a48ebe502ee49c1ed907073ce78157cdaa3d2728c64a2a3a33e74f87fe22f137c46f122d98d56ce2166df6c5696d632c5036de011df39ea6bd5744d0fc23f10b5ab9b1b08eeceada35eb5c79d2c0a09665c1dbc74e01e9dab1ae3e0fe8fe0bfed8d73c3a8da9cf040b75636f249b7319c9da99233d334c5b4bad63c59a64fa3e91ff08df88efad12ff5399666549215c643104807afe354e4ac3a8d72e852b8f85ba8dbf87f58875ff10de5c410eaef7510599bccb7848e54918db93d8e78cd49e1bf87d7b71ac59d8788e28f56b122636d79693e64008ca9914b670381cf5cd75fab4363e2ebdf175bea9a25ee926ead23377bcb3453c814ed915d0f07007d735c97807c2737876cf4fd460b6d474ed4b4db17b99eee593e6d4199b223dac3ee8500f5ed53196867097bbb9c67803c2baa6b5a86bda5ff0064daf89ed74fbb908be5979b13ce014e9c0e78ad6f00f84efe6f0cdaaffc247770ebb06a7318aed4832c239daa0371b7b63d0d7b1689af4ba8fc2ed43567d053c3172f75beea3962112cec483e602a338c67d6bcb3c44f26b1adc93cd7b6f00b3d722162fa73ef8a450a33b8000f393d6ab9afb917725aa3bad0fecdac3436d15d4375730ca6de7913f76c1d549cb0c639e78f7a65be9979a548eb07991c82201a47c6370c827f106b6be2178dbc31e16d0ef7518fc9babd8b50821ba86388878cb32867e0740334db1d4fc47a95c78aeedb4e4d5f468a28a5d15ac514195d87dcc93ce3be45667049e9722b7d3e09268ec4584929700b4c40640e7f8b939fc2af7da88d36f2ce48638dfc874f376e0ee1c000e3d79e29f73a5dfc766b73230d3ae005796399790c5793c7a13d2a5867fed08e3b296369da65282566dae848ea9f2ff914aec4949ec78e78db41bfb36f0c590d2e368b5a84c7737318dcf114563bd877ce3a9a8f5ad4b479adfc2de1fd66d1358b7d42365170b9c2cca48048cf5c03f9d7b26a1e0b975bd7b4db6bcbe4b74b1b368242ccc0cb19e060e061b24579bde7c0dd63c17aad847a2eb50cfa38bd6753aa005edc49c1da49e7afb56dccac91ead0c469cb7d8f37d1fc357ba47c4fd46d7406763a842e93477037421631c381ebed5e87e09d26f7e1c6872dcb6b126b1236eb89599711c609638001ce074c560f892d5fc13e3db5d40dfc73dbc12bdbb6d7da1f7738c039e4fa54d7dabc7ac69f6f626eaff45b778242ead16d52493f367a91839c6474ad25aa48f5ab515884a9f467b9786ef34ed774d4bed345bb472aacade42e3e63d41c638fad457924fa5ea50c86e3165705b72ca032a10a4e47191d2bcdfc1faf5c7c36d4b4b9350d5d756d1f5088c111b850aa9b7f8805eb9f7af7086cf4df1169af3c33dbddd84caea1e31f21ca9040e7835c5523caf53e0b1f819616777f0b31ed6482fad12e6dcee8e40086c1dad9cf4e3da8f25b774c557f07e82fe18d260d3afd266065db6c884bec8b9c12735d3ff67a6de857a1da7d0e6b3ba3c1a9149e873f242c3b6693ecedb78193e95d17f6722f2064d235a2af6c51746273df6576e318a45b5766edc56ec967bd4e0e3f0a22d34c619f7023e945d0ac6049092d81cfad28876ff0f35a6d18924215197df19a6bdaca814ecce4f4a2e0525438c5157fecafcfcb819a29927514dddb68f33da9bbab2341de67b5377518e3349400b4f0714cfbb416f6a00777cd21e8734abf37b5457333c30ef8c7cfdb8cd05c574197525c471936c825931944231922b917853c3faa4f7f78892dcc713c92dc49286646c7dd1e9d718abba84971a435d4d05e1bad4254c9898e427fba31d6b3f4ff000ec536ae75abe924df7306d93499141476e0eefaf1e95a2b1e8d08a8eace8f4b44bfb5b7bc82de368ae00912580862588e723b55c6be82c74d9afa50c1003bf000070718ac28f4e98df457097373616cb118cda420242de9c638a863865f185fc114502c3a5e9e08f265627cf7ea49e9f5fc299ac99a3a6b7f695ba6a8b034218eef2e620011fa8a8b5cd6a394a1490c8a012a9b72afe951788af02b8b4894ac4179f2db85f6fa557f0f592ea0a5a560a9110b1a8faf6a66aa0d2e67b16208cc7a7c97f2c5ba445de61c84181d793ed5a0faf69578a6ddeebecd71e5890471a962148eb90319f6a5d5bcab8b19ed6da547baf2d89ee17b608acbb7f0dcb25f477d1c72ac7340b1caeac14ee1e8b4887a8ed567d3b58b8bad22ca559afd6dc4e1553087d327fbddc8f6aa67c486e3c4565e1abe8cadd3d9095e7b57da131db233f9d74d79a345268f7160a8d199e331f9abb55f9ee5b3d6bc8f52b39fc17a968307da166d62ded6402dd5b324b1124052fcf3cfa538a5d4d694633d0f44f166a6b1dbd95bc61ee4ca426f2f9c638c7ff5e92eb4bbcd06e9afed2492e2108ad2412104ae0738fc327f0ae72e16feff00c39a4cd7960b6fa9b485d6cda405fc907af6e86ba8d27c5b65abb456920313952a6dbca66624753c551aca1caac8b973258ebda7b35b4d0cd3e048b13364e0e3f2accd274910df29d74ac6ead9b659a6c127fd95ea6a082fbc3fa0de59451998b5fcad02f9a784933d39c638ed4cd434f71e22b7d42e7c3ed757d0cbe4a5d6a376bf2274cc6a3a1c1a467f0ad4d9bedf25ddb6f89eded63ddb4f9a796e9861ebed45f3c56372f2c16d1b4a53e58d060b1f7ab7ab4c349d3fed08a9bd5f86939e49ea7deb063d2f53d473a889da6b9ce5428da0fb62a4ce02f86355b0d4afae2ec5aa58eaff0072558ce0b01eb9eb5178b2cd9bc416d242512e186edd9c1f63ed5aba3e8ab048d773a21bd63f36d1d0fa1aa3e228fecbe22b4bb9d0331e3767e52be9ed8a6689a731bfdb1ade4d94cf025e6720bf0597daa8d8f87efb549a3825b1b7b516f2176bddd891b3cf5fad6d78af4f9356d3de7b08a17b98089048d203f2823818c9cd677877c66fa930d3af74f96cefd812b3326e8d940f507d077a19af368ec8ddd6b508349d35fedf89e0db92ae370e3bfbd73d0e96b73a136a493bc56f867099c1c638e95d15ee8f67ab59c50dd7fa45ba9c15e99fafa54b7163043a64d104792d44441862c7dd03b7b8149339e9d7e48d8e3741f135bf86fc3373797bf380ee5238f99703b9f4ce6b6b41d71bc5490df45650c114855c156f9cae39278eb5423f03f87eeb45338b6bc53721d921b87dafcf63edc55dd1f46d374fd012cace79f4c8ae10beedf92a41c1c3638fca9bb33a27284d5e25bd53c61068f335a7d92e2f2e04821468e2dd1066230a4f51c7538e99a67fc250f0eaaf637d7da5899a3060b1809599b903bf51ef543528bc469e24f3b4c86df50b616aa8de75c6c69187476c2904fb8eb5c86b1f0e358f1bea565e24d72ca1d1356d1ee57ec0f6326e7ba841cb0233d3b6306a546dd48d1ab1dd6b5a86af7567756ba5d8db5c5d5bb60e6456dbc679f4ac38fc75a97882eb5ad0f4e16fa3eb5a4bc6f325fae216053efa3719e7b563788bc41aa5ff00896f2c3c31613e92b045f6cd4350b88b0aee4fdd0dfa1f634ff1340be30d32ff00ce8e495b52d3d564b8849cac8180255b1d31576ea7651a2a5b96fc2da5788e6f195e4facdedb3c70a822585810646c1001fe15c76ad0b3f185df8c3c697ba68d3350d3ad3489406d463c2c572c4720123e618ae57c0ba8eb5a3c31e986cdac74d86c95ff00b51222771570a7767396db9e699e3abef185feb9a858e99e2e7b448628ee6249511770246371c60faf6a763a2ad2729a51d8dbf87fe28d6ee3c4baad96b1e1e5b2b36bb6fb3dddac43329c1da5fbf3d32477a2f66bcf1478924d2358d4648f4f8e739b78d8a166c1ee3b62a5f01de6afff000936a06e7c516f7d13c30b9b1842b32c854e487ee3d8d6ade6b9e187f171d3efa295b52c6e372846ccfa67fbd4b639d274ea36fb1a16fa768de0f8e48e1786dd5c7963ecf8de54f183df3ef9aafe13d17c2da1cb796da2aa4b2f9be6cf1a8de4b9ee58f7cd656bde1192d7528e4b10d7715c3fcf91c463b739ada8ec43dc5dd9e983ec53dbc8a25c28025caf7fa52266e4d692d4c2d63c377f6be35d035bb2960b485a4905c472364e02b1233dff002aeb6dfc3ba3a9b8bcd2acededee24491d2581182f98c080cd83cf26b81f1b69d059c5a25ecba8cc89a75dee731cbf2efcf7cf6f6ad3d33c543c1fe0cd7759bad560bfd312476b392d72cd0ee39daf8efb883d7b629c93b5915355270538cb4d8e1f42f0bde49e2ab86f1b5cc7aaeb724170b64c9bb625b8ea436073eddabce27d43c51e06fec1f116a76975a9787a39bc995239d9c3c1b8a9c8f5c1ad8f807f1335ff00887e3cbed3e7d492e6c6d6392e6492ea30db2394f0839e3383dcfd2be91b8f08e8f0f84db499963b7b05076191d4ab704e5726afda72cb53d0f6ce972c1a3cafc43e13b38bc55a6eb96d01bdd0eff4d01ccd3906d171f2ed5ce01c7afad763a54da07f65e97a65fcad73717d612588b585725d06589670382001c67fc2b9ff00ecdd2ed6e1ac7cf69fcd954b956050201c6393c7b629fa3f88934a11d8de5823a27992417b1a1561ce0e40ea0f143573aa54f9a1cc8e32d754b1f0478a2e74d7d5ef7c45e17d5d248e0d266255edd4020ec20e4edf535a1f0b7c71a32c10692d15e5ee95a82bc760d7d206684c791b7731ce2af7c2ed2744d7a2b5f105e8175776e2eedadde3520479dd90ca47381ce7dba562fc35d2fc3fe31b896c05ebc975e1dd425f2558aaf96ac704f4c10777434d0a168a68a573aff883e18d9eae9e27f13daea716ab116d3ec150cb1c5f37f7fa0c01c8ac2f857e3ab1bab4f1489a0d3f4ad6dadc324d00c8b990760318048af57d02c7c37aa7847fb4b56b957d3a392e2068f52849c485b20ae38038c7be6bc93c27e0bf0f5d3b6aba45d35bea76ad3c77b69329093b29257c907db1f519aa474519526b5477edaf7886c7c2fe26bad2f4ab7d4e6fb3a3c51cd0a3149307e739ebc7bd51f863f176fad2c67bdbdf0fcc3478e0f3bfb5ad36b6c2bb1a4464c8031c9e3d31df357fe16f8821bbd16dd751863d362164e248ef94c6d2489903667a93d706a1f0f5a5ce9fa2e851d925a4c409607d3cec5fb44b824919382718c8c77a0ce5084958ebac7c643c75f6e9a0b648b4b9b4e8ef6d750572ed26ec9601077007a7f8d5492fdae21d37512f26e7f2e58265046c503073d39f6ae7f43d534ed4b4ed3ad9b4fbcd1b52b692e34f686d73b94124e18a0c01cf5078a8bc3fe34b6b3f867a7e993585fea97b6f712da46b6c8667daadf7d8f5f6cf7a461ecf974e87a5eb9ad4571e1bb679e06b8beb86d8be51f9c64f0d9fe95cdf89356b21a4dbd96bed3a5c59dcc4214488bc9292ebc1007231d7db346b1a8c324d1dc08ded8840e3e460222178047d7f535d4ea1b3c516fa75cdadacd7b7b1ed66789c280d8eb93ed5261cb083ba47cede3cb3f0e6a1e37ba96f6fccda8596ab1ddac32c889e5c4840f2c671c639c6323debaff1325ae8b6fe2593fb4a4d4ae2fc2a5b868f296f09423e538232777e957fe257c37d0fc55777b2ff00614064b7492e2eae1be59f78039c91ea3b67ad3e38f4bf1a7c286b60f2dd6856c91ecba88e2e2464e19001ef8fcab5e6d11ee46b6b16b638bd1b4db6bcd16cedb5bd69af63b29084b711aab4191908719cfae78fa57a7fc0af10699268a3446977c96f2bb2aa4676e0b6473eb5c7ea9af683a2adbb58683771cd190cd712c7912020724fb0047e358f1fc44f13cba5a5f697a65bc9670cbba38acd55247c9e4b7b6334e71e7418ea2b1987e54b53e9ad5501b4621da2c676ba8c9e463f2e6a1d26cfcbb1821337da248e301a4eb9c77fd69de1fd421d4b4f4db242675553208e505d490090476e4e2af7d95d662e9852460aaad7034d3b1f9856a3eca6e2c8c419f6a4fb2ae79e6ad161b8a819dbfc43a521207348e66ac566b652b8e82a35b146605b2dedd2ae7dea00c3522412dc46bc2281f4a864b750cc513048c0abc73b719a8e8b819ffd9a597e63ce3d28abccc7f0a28b8591469ebf37b5205a4fe2a60498da3ae693a8a4a33400adda9b4e6ed49b7228010fde00566ebfaf5b68769e6ce5fcb932be646bbd508ebbb078ad204428eee1982f384fbc7e95c6f84eff004fbbb2d41d2d1b4c5ba9e446b4bccfefb8209c1ec4669d8eba31e635ac19b529619e12a2d1a3f9a4420eecf420fe557e292cedeea181a467b87076f9c77371d7f4ac4f089b3b0d3ee34cb38a2b6bbb790c8b668e72e3b609ed5af14b3cfa4fdb750d3e2b3bd8c30586460580f5cfd2a8ead6f6454d63589e602c2d4c4d23bed66c7dc5f5a7dd3a785ac63b5811a5f30900bf2c5b1cb7d319aa5e15b59459de6ab74776ec91196006cac4d435296f07daa6292c39db1ef3d149c018faf7ab3b2953e77a8b1c773a8df1b5444937ff00acda09dabd726ba7bed42d7c31fd9ba725b5e48d3fdc6b387cc41cf566cf150f83f4f9ece1b8b89d9d2493a1272027e5c55efb5ad9dbc62195d23dc7320383b7a9c1343f222b4d5f9119f79a7acfaa5a323ccc37303716b8c29ec241f5c547fd90669ae9ad35190dca63cc5693e607d547a541e028f4dd36d6feeecae2e2f835cbb31ba07773d87b66b76ced6392f25bc6b511493200f8e4e05230b9930dc788b50b7cdb4ea85495733c4bf36071fad2a6a97d6b34726a7a5c7238e0dc98955947a86c74ab3ff09269da45e0b3bc963b7323fc8246c123d454fad6b93d94e3ccc7d9bf8a2986fe0f4c7d682d5ef7454926d0ef358b5bc592482fa01b63655fbea7b60e7bd68cd670dbf8812f44285a48f1248dd9bb6074cd3b4db3b6bab78af7ec222771b937afcca338fc2a5bc84cdb01609b5b76ecd2b99f3caf6b983ad785ac758ba591f548e1916e1668566810147e9f290b939ad29bc3c9f65f33574b7bcfb392de6ec3966ec78355358d05353bbdd15ecc6f1577a5a96014fa60d6149a723de5aa6ae5ac51890db1dc798d83c1e699d0ad28ee742750b6f106eb15bb5f33eeb4324472a31c15355f49b0d5f4a77b14ba77b40f8592418619ef49aee87f66b617da75c6d9216564ebf74763cf3c56b68de2687588d7cd9563bb61cae3a71d6817a189676b3f87350667b879e2b86f999cff0016715b3ab69f1f882db0cad1488a4a4840c67fad52d734bb4d5aee746bc95678cac8d0e32a07f5e6a0bab8d0e1b51a4dcddc98484bb2863bb1d4b03f85045f5b963c35a0c7e0db16f3ef49795f32198fcbbcf40315a53e971de5e453c6d2406339fdc90a19bbf18e463d6b9e92eadaf3ec1009d64d2608bce6799f86907ddce79e2ba9b39e3d4210c8db8b0de768383d815ffebd233937ba1331cde6046599f3f3796c188f6fad55d4354834bb169ca35d4272998d7761bb0619e39ae523d420f0fe89af7fc22d68d77ab43725e582e0fccf27a0e78cf4ae520f889e218741b8bcb3f0bde6a325c3f9771148c8a6de43f7b039c81424d9a53c2cea2b9def89629b52f01ead3097fd356d9847241f7938c80067ad72bf077c69a6f8f3c07225a5c492259c2d0186e976c9bc021c139e0135d1fc35f1568de34f0ccb35821431661bc8e5c8293824156cf63ffd6f7a7787f4db9d22c65b7d2742b0d1ed4ce4bc2b1fc8f9fe2f5e4f34ef6dcdadecf4657f0a78a2de6f094b74f6f0599b195ede411678553eac7ad47757d0cd2699abd93cbb0b3410b4c3956ce4e39e84679ad5f137871358b082dda24b5b2f35a4ba1b015627df23d3dfad57f1078762f146990c0ba8be9d1420c502c3b5149380ac31cf1468691b6e5cb892fb55b7d5ac26822f2e58cc6b3799b46e65e7381e991f53593fd83a9e8f61a5693a5d9a1863b5304973b8feef3c93c9e78abba2c9e26b5d274fb44d3a0f3226f2e79ee9fefa8380ebc7cdfa5682dfde7daad926b9b7b68657650b2261e4719e073d3fa51760aab8cb4389f106ada9e9f269be1c7bf6d3bedd198ed621890381c9763d81c55eb8d1756bcd76c34dbd7b14b7bad34c77b2a2aef720606d27bf3fa56af8cf4d91b56d24cd75058da98da3f3a4881b8690f18438e1475eb5c07c7eb3b8b3b8f03ac1781614d4238bcc901cc8c5fb907a723f2aa8f91db0acdedb9d27c3ef86fe1df87f6773fd922eafaf635fde5c4d925803f7719c66afeb1a6e85ac68f2dc19962523ed42c65401dd93e627839ea2b035af88f6ba678b2c34a8aeee226be6f2915603f2b903e6cf4c1e463b55ef1078f34bf0ff008c348d08e89a9cf2df45b1a582d83479c12c19bea3dbad1677b84d54776d9d2c3e2c86e7c3466b09d56f62804d25bdc0e123ff001c5453eb53df4934d1c56cf2dbd92caf3c1201bf38c0dbd4f1dea96b5e28d5b4fb84b5d1347b289e5858c76d310240ea385f97d71eb5c478416d26d174bd47c747fb2fc63a9c33456da7ef9516342c461c062339e41edd68b3dcc952715ccceca47b7f154d7b6d73e40b4b875f2ad502f99bb6e0b7279eb9e9c62a2f007c34d3bc1abe27b2580dd5b5f4a275b39e4ff59185f9be4e8327d33557c35e1c862934dbdd5a7b73e24b2b66b5b78b4f915d25519c16e73b8838cd5db5d1747baf899a76bb7dad492f88a3d3fcbb7d2167056dd7925b683c9e31f8d3932e778c3963b1c3783fe0fe8fa1f8e3c4b796ed0787ec2ebc99469aaaca303a639ce064f3ef49f12bc2d757dad5cd9f88bc76f61e1fb764962b7b58ff00d5a9e8bc0dc7eb5bfe28f12783a6f1b5a4e6f6fdb5ad434f9ec6dedd7e68880431c91d0f20d56d5bc2be1df11f84eff5abeb4b96d5574ff2fed0f3305dc3e50719ea288ebb9dd195dc5c8dcd67e1fd9dbf83dee743b8b8bbb98234719010f97818241e7915b7e0b86cee340f32fe0898c5c16643bd578c8c8fad79afc15f115c5e7ecffaa6b57c67bc36fbc19a466f3255438201c7f7738e6a66f8f9e0fd43e176a32e917cda5eaf35bbb7f675d9c4a9db271d781918f5a4ef7b213a9565170b9ddcfe26d12de19a18bc3971662d4a9824784a094b36328738cf39e7b5707e179fc3f378c3c57a4cda06a3a1c96c1af656403cabb66c7ccaf8e587a575b32a2f83b4ad6af0dc5f69705ba5c2e9f6e0c92c9205072a09f9877c60631deabcfa879de2db3d45bc570c3a0ea3a7c8d0683771aa3bcd8ce477c0c723d453a72e5ba30a72945d8e0b44f877a9ae966092dee52f6db504bcb5b196e0ac3736e7906419c0193e87a5765e1ad16f2fbc593c1e26b2d063b7b5612598d3e37530cbd50bbe7040e9ef915b13325c6a93ea1a6cd0ddebada29f2e22498848a785cf4edd2b2bcb96ebc6363e1fd4ade24492d92f352bc9f78226183b632081807191e99ab53bab1a7b57b2336cfc0f79e34f1149378b4d9decd61a996805906cedd8594bf62071dbb56043a99d3c6b10b429ae5e683aa092cec62f2c5c40b21f9db07921bb0c7f8d6c7c449b59d7fc4faa689617b67e1cf0ccb047770788d65dc37a9543182a7faf7c63bd4be27f0de9779043e1bd2229a5d7f55b318f18dac4bc320e1b7fd7dfda8bdb73a6151c56a6249797fa67887c4ba7682b2cda9c330967b0b870aacd2804b2639014120f3d78a9bc1be0ef07ea1e248e68358d6b46f11e99247713dacd23471236465095f95949e7bf1537c46d3f5db1b7bcd1eeed65b9d27fb2f0de23d26558b516d88acec415e72c3a0273d3bd79eeada0ebbe20d364f126a0d70fe12b9d3a0cd9c85e2bdbe9146173b063380381824f5abb5d16a5ed373e836d734ef115f6b36f05a5d2cf608d10bad9882e031dc421cfce723ae2ab786ef24d255229cf971f9bbfcd0bbb2369e315c7fc18bcd72d343bbbfbbb48b4ed11517ec1a1e4fdae34e8cf22b9e076ce6bd16ceded6fad526b195668a55e7cb7562a4b0241fe5c67ad652386afbaed732f5a85a7bcbeba4b56b5f3643682357dc6e438ea49fbbc5723e034d3bc35f0a459e8ba75c78726379706cbedb0b386915bab103eeeeaf4fb771a6ab5a48a8619b82fdda43c0c75c102b9fbcb8d6b445b6b3b582ca2f3ee95ddee1d9808c1c1da338191ce69df4428d492564ce3b5fb3f116b5e17d2eea5b9d2524855ed6e523dce26c82cc54e060e47435e73ab783526b18edb4eb86b3d4268d618fc894a81cf5615eec3c3ba1f84e1b95591a382fee3cdba669c80dbb2411bb207200c7a1cd655d781b4db6d45aee199248ccdb12766ce54a13d7d33c56aa563dbc363a34d5a667fc27d327f0d422d6e649755d5b6e66bd4c328248c28fcb19f7af695dd3308e50d13151bd50f23d403f4ae5bc0fa745069b6df66fb1dc44a9e4b9850e43a93d49ebffd6aea795270bdf38cf7ae497c573e2732a91ad59c91c1fc3cd5ec749d7359f071d46f350d4ad676b9f3258cec1139dca371f6cfe55ddf0fb876071581e23b9d4f4fd52d6fa096c6d7488a326f649a2ccce7f870c07419c63deb634fd4a0d634f82fad8e609c064e319ce79fd2b267932d5684ebc7d297f8b347ddfc68eb52644acf8514cdc18d3b238cf0297865c8231d8d0044796c514acbdf3450053a4f7a514633c55800e696940dbcf5a0b6474a003ef1028f6a178e6909cb0fae28033358fed3f3a19609a18ace3c190b2ee63938c6323f3a866d122d4ae84a272f2ed654661c2804741daa7bcba2b208a5531161f2363e50d9c727e950dd5c496fe7de18e490468d13c719fbd923e61eb8eb568efa778dac59b5d3635c992d62fb630f2cccddd7d6b135cb246b9d3b4b6b80cea5a4f2d09f941e3039ee2b634fd4049a7acae1caf90cfbd7a71db3eb595a4a8d42ef50d56481c9076c6ce47ca00e9c7d69a5a9d505add92eb8b0c56f069e219028eb86c0dbd7159ba4e9b1df6a6b1a41b4467908377ca3903db9c51777d25d6d9080e472580e98e6ae78355ac63b878da2b97b9937e6324edf73fe156ced97b9137d6191a6965070a3e52ca41c0f4e0f1497d65a75c69ac6f2359608cef05b8e6b9fba4bed2754bb76d42e75032a868ec555540e792300678cf5ad2bebcb3bdd3becb1e65940019002003d4835279bca9caec5b8f1359e9f6b6ed045ba160c1630a33c1038fcea0b1f1c584934b15dacda7487006f52c1b9f5c7159f756b63a2eada6b5c46be4aab1dca7785dc3bd74d75a5d9ead6fba039dcbc329e0fa1a46c9537b22beb1e19d27c41e44da858c774e982ad20ea072318aa96ba65cdd6a4a52ea2114677326c2dd3a6493c60567ff60f8863b88e1179e5411386c2b7de19ce2ba9b8b51716b71027df957a283f313c60e39eb48ca568bd199fa7ebb6fa86a9776515d2df436c3265553c313c8ddd2a8ea1e36b1d27c411585c27930b609b9c6307b7eb8abb67663c3fa5a24369f65954ec920b5f999989fbdcf5a4d7e1b49b479e4d46ce099d50e188f9bdb3e9cfd68142ce5a94b58d5aeb4ad6a08f4fb2b3be8e76deef0a7ce41fe2ce78fa55bbcb79bc53a061d0dac80b4a16420ed656e2b9dd0eebfe1159ad5ee50c76d741555e41955ee066b435cd52fe098dc5acaa6cb7e25b758c12571d78ed9c74aa3add351d98cd17c537b6e3ec37fa65c35d21ceee36b2f4dc3dab6e1d16cadefa5d48821dd32e378555039c73505e5cdbb2db5e4360d733a955239c853dfe83d2af5cb41ab068e587cc50795997a77e941cf3919da5dc5e6a125cea17767f6342db21692457578cf03257ebdeb46710642cf1db24bfeac2ca07cd9edd2a95d7876daea38a2963cc71b798806514639e8300fe39a9f5399e1fb2edb4fb5db938691790871d48eb48c6e737e22696dee6d0de8d262bc9607f2636fbdbb9550831839cd73170f6ba968361abdac77da378a087859937ac6eca7037024291f957a26a9a4c5ab4c923c76b77e52e638674c14f707391cd43a78bf9a6bcb3bdb480e9f1ff00a95ceeda319239f7a67642692d4f38d5ae3c47ad680d6761a62e8de20ba5cdcea400304a41c024e7bff3adcff842755d6bc3161a334efa4c8853cdbcb5c106404138fa91d4fad763a5f93791c7f67bb61671068840c814e79ce78ac6f1178bf42f07de14f3e4babd11eefb15902eb8f53e869b9bd91d31af27a41097de13d376c3686c1dee599099a2728fbc64ee3b783d3a915d8ae61b68964740d1e23690f4cfaf5ae1344b7d7bc417afabdb5d2e8b14f02afd9b7e58a83d4a9e86b464f05e525b8379717176cb8dacc5411edd7f3a56bee6128272f79936bcba8df5b4115bdde9d6e826df34b7120653183d87193fc8d67f88b528754862d3ed2eef1047207f36da00c6500838c8ed91dab27fe1178e08fed1796be625afeec020b1c9edd7f5ae9345d5a0b765b7245a46db81973b444a39eb5563a9d1e58fba578fc416af090f16ab67f67fbd32c27208e78cf6a835bbed1bc546d669e4d42d4c52aca8d18009edc8f7adc9fc556b716ac61916706365538272071cf3deb116d629238dccbfe94cbb9f6f1c7600548469a7f116f50d43c330cd6930845fea4a4ac6bb99997d5b04e32067b55d9a6d3b5eb48d218adee6f616cc693383e5b1e0bf4e38cf1fad6045a6c725e248912ca633be464c671dff004aeb236d135446db144cec3ef15da40e8065467ad1e839d3e5f84cfd434cb0bcd7ed2490a4d77007685123cadbbf3b9ba77c8efc63de96eacf5b8f58b1be8ae02dac2cfbe1b58416753d8e49ef55b54b78bc37a85b2e99706199b79955f2ea73c8193f4a92cbc652497935bdd5bc9146170cea393ea57e9d68f789b546b4387f1c78666f0b5f5bf88b4dd4eeaecdc6a008b3b560554918299e483cf3ed9aeafc47a6c1e30d2743fed0b7532fdad44ab1af319c118dd9c81826a7b1d53c2da75da69f6aea971733f9b9932598e3a9f7ae994471b487c858d56407746a4f1ea7de9b7a21cab4d249a3e7fd7974ff000fde691e22d123bcd06087553a6dcac2322743905b0d9271f5a9d2e2c3c75e1f36be0bb98e0bdb7bd974dbbbdbc4d9711ae497f2ce32739e39ef5ea3e23bbd32dfc373df35bbfd9ec65f3712460e083f7c0fc699a7b69573a40d50db431db59ccb79bad6d8879176123200e5b935a29f747546b2696879069ba2d9ea96be1fbdf05490dbea5a1dec963247aa067797002c9d873903e6f7af55b1d335bd6ac61d3a4d365f0cb452e2e5a4db28901ce42f6c367bf6358ba9fc47d2b47d1eef5e9ed92ea192e42dac7656bb278b71059e45c820ff0085737a1af8d75ed53c55e21d3fc4d0240c62bbb2d3a40ce42ede5580c0071934735cd9cdc925b1b5ae78522d37c4fa26ed46eacfc3b7314b049a75867ca71820965c6339ae6f45fd99bc3567aa699e20b6b9b866b3f35c58df057ccbd2327a71ec7d6bd6be1bea1a8eb9a7dfcdaabc7712452e1238a20a106391df9cd72377e1df14788b52d4245bf8eda090a9449fe565e7f8973e9c7e35319493dc9854936d37a1d2e9fa6df697a5d95deb4ebfda9f66685a55202458272073f79720f03b5705a4e83a769767a5e9d7453c5cfe20ba9a193589265792c65739545033b46074f5e6bd2fc3be0fb3b3d3acceab772dfdf88ca19a6625412707683c053c707f3ae4353f863a7ea1a6b3db433784bec1a81bd79b4e603cf71fc44760466a13d4e6753dff0074d2f877a0e93e17fb3685617d757f2db1955e4ba3921c3741c648cfbd64d86bba0fc5bd72ee21a85fc7169b2cd04d6b202a2e64208c2f438233f95747e1df881a36b1ad24735bcf6d3443641765382a782c481c9279ac7d3fc29a436a535bea76f617f0fda5a50d672babc6b9f94b118e7e87bd0b4b951beb720bad1fc29fd9563a5ea9a1b436362244b0d12fd36adc383bc30009eb83f7ba62a1f09f89b48d534ad075b87504f0f68d6b2cd649a2c70a00f704e0fcf8c851f4f7ad0f1368b716daa5b5fa46be24d62e2f1a186f14126c617183b86ee7007a57897c3ef13e91a4ebda8782752d397c40eda9b32b46c53c8de4e5f919eb5bc63cd1d4eda54fda2d1ec7a7f809aea5d6ee23f156a11a5cdadf4834c934fbaf359e30c5bcb70b9e083d78e956f59b3b7d37c4da5ea3a8e9175af78814491595c5aa3f916433c0901e3be73d7031cd737e1bf0cf847e09fc44f146a567aa5d6a5aa2c1f6896c256f92da26603780a0f427ad76be1dd792d2f3525b0b8fb7595c6a25ae2ef509cf9603af254f60091c7f2a9b7662941c5e8725abdc1f167c42b130c50eaba9ff00674b6575269f27970c1ce497dec33c67b5749e15953c293785344b8312d9c91cca9b595a1120c90db973dc6304d59b8f077863c290bdce9b632da2b5e28bebae1bed8194ee00b1fbb8f4aa1e1dbaf2a5834e83c0f75a5787f4fbc6f22e8a82b22b65b783e94ae652d55d1d2eab6fa9fd96e6de636eb1ac8255681f39f98636f154b52b8d535b92cf4dd909b5b5899a6b83c48011dbd6b5f506b8d584d77a644ff65824f27cb2a37383d587a01fd29349b79ed2e2e2410bf968eaad29e4480f61ebd7a545d1cbce969732b56d06dfc69a1fd87c4522dc6976ef1acde4c810bcebf70641e9b793f4ad5d35747916da3b2ba80da5b2342510120a05383e99071ce699e741a1c93bd97862665dc433431e7cdcf53824807deb4b4d4864d419e1d11ac6128148b85d8178cedda383cf7a9e6319564b5b8fd3e5c5e32db5c48f14847951281b40039e9deb723c6dc83bbe6c7cdfdef4c5625be822d7cc30858f2fe629562369ff000ad8b65f2e3231963f331eb93ebed50cf1eb494ddd1647cdf23126361860c01ebd7a8acbd3156cf7db654221d912a26d000cfbd5efb429b8312fcce06ee07f0fafd2858d03000739c9348e525c16c6280a7d299e66785f5fd3d69ca4e793f85419c871e94830a3006076a0b738a461f2f5a0426ef9ba5148ac338cf345005466f97a548bcafbd45d81a0b7c86ac07b2b2f51fad281ef4d8fe6e4b7e749b7e6c039fc2801dbbb62a2bc711dbb124ee230028c9a78ddd81cfb5666b9717b198a2b6d91c2e30f231f98678e053b1ac637d106a53ee5b6b0b986496def1b0581e54019fe63ad4fa4d9ff0061c324064fb547bb11973f717d0d6769fabd9ff66437ba9ddada6e90db44d3385dc54f400f3f8d5efed8b1f3a08b2266972dbe120aa81dcfad59e8463649187a9696fa6e9e905b4b7334974ccdb25972a993d471d3daacd9f8674df07da4ed6d1cc25bcf9e55121219ce32707a7d2a5b19adbc41e2665b792496ded5446481804e7ad57d46e1b51beb9529214c2ac6a0e79ce0551d115a99ba7da9bad612d901f280cb0638247a63ff00af5d4ea964f25ba436d2476615941906080a082471edfceb3bc26bbfed37520caabf928179248ebd454fab5adc6a3aa4301bd78ad948710c4aa030cf20d26c9ad535e543ec3504ba6be75945d416fc6f04638aa3178e21bb9881a73c5049d25240c90393d2af268296506ad0d9c71c69700950fd8d61e9faf585be8674bb84dfa8a7c8a446704e78fa50284798e8ee2d6df54b58571fba6019b70c6e5cd64de685aae96ce74bb82f04876ac6bc6c5fad4fa37886c62b386d668e649a3199594865073fdee31f955afed68752d4228f4dbc76b880ee921dbf2b29e00ce7d71524b4e0c9343b7d421d2c2dd4fe74ef9dbbbaaf3fad5e6dcdf2919982ed2cb8c7e5493425be4e7cd5e59508e0e738cd55bcd42dad1226bb66b319c17761927e80d0733bcde8524f0f44ad1de8b6492fd243ba565c1dbf81ab5e24d1e7d634c9440d99f6e52366001fc4914d93c4da56c8b1767f780938e475c62a4d69665b0616374b14e02949ce0a807d460f6f6fca8d8bb38ead069b6f3dc6922cf54b358a54509e4c8c1c671d78accd2fc3f716b70f1cf70b0c70f3b41f97cbedb49ffebf15a771aa269ba7b5e37ef2e3ca04c88842cad8c6381dea0b58eebc41a4bfdae05577c7c8af9c2f5c7e74cd54a4cb1e7dcda5b90cf0decd19f924550b843d33cfe1561644b4591a668cce30cd10719c11cf7ac4d5ac2eec6dafae61c22c106e48e01976da3a60ff003aaacda86bfa05beada2b42f78c8bbedf51880121feee474fae290f96fab25b5f10346d79a74d233ea09289512ee504884f42b8fe5535c5f1d174fb9ba89e4ba7f394066539c1232147d3355ee2081b51b99248d575db8816481ae54328006182900743dbb8a9ec74abf8f5417b717acf2c91287b48dc796587f10cf4aa1f2a2dea9ae45636f6b7d1e9f34ef73f2958d72c8beb8ac5b7f1758d9789f52b3bd8efada71124eb26c251c120003dfdab57c45adfd86dd637493ed93c80411c386937fae01fbbebed9ac9d0741babaf12dd6adac5f3cd701045f67518895bd87ae2805a0b797bad78b142e9e5346d27761a678f32ca7bf1c6dfaf356f45d0343f07dbdd498462df3dcdd5cb8720752738e07b56dea178d676be63e3cb4182cc70a147af155ed749d3add67bd31c3045709897e62639148cf71c9c76a90553a22ae832457919be8d6de78e57cc1756e73ba3f4f6ab9ae69f7b796882daf24b20ec449205dec57b01d3183588babda68b673e93e1ab03752a3060ac84428c79c6ec7a6691bc2fae6b96714dabeb9269ecadbbc9b1e8bec6aae1add499cf6a96f75677af05c5eb5c60f0cb9dcc31d71eb572e231328db693c52042c0043d0f1cd75b0e9f0e8881eeda393cb60ab7922fcdb8f45ff00ebd5bbcbb0235b9f3e4d911dd27940337231c8f4e68e63b1e31c559239382c6ee452cb612afc83002e06076feb4d8f45d43ce12088e7192c3a8cf18aea2ffc4b05869b1ea3397f265dab1aa825f0413923031d2aaeadaa28d2e3bc449955b69c5b1dae0161cf7a7cfe41f5b976397bbb7bc0b3c6609d1b6ed73121c119ef8aa8963e5476e669e6825d876a46db38e9c8ea6bb6d62fefa496c2ef4b912ea3b7399e095809245da49e9e9d7f0a4d7b50b8325a49636f1de31954496985cf9679c92464628e7368e2d6d24729676b6bab4d1162cf7683eecce72bce33d79abb1e8307daa57bb9a41248a562446e73ea2b666f09e9da839f2e16b5943ee38272bce703f1accb9f0cdf417d6f34537da5632704a11cfa7269dd1b2c4424ed127d0fc3da56b16117dae2517b192b1dcc7f2c831dbbf3ef572fe3b9b1d3dd7cc9aeac6556864b81f2bc7c1c313dce71cd605bdf3e9f26e4964b793cc3be3c6086ec7f3abbaccdadf89ace0860bcb7b5b428c6edbeeb1c7703eb8a9b18d4a752a49599b1e1df0ae93a5e851d945ff13181c9795ddd9849820e0e49ff0022ab6a9a5c0baa3c7a769c8b79736c64796391d41038518071c0f6ae3f47f106a9e1d9268a2cde69d09679847f3068c8c13ec6bbaf0bea9a4ebf62b73a7efb6959360495be75527af5cfe14dab1352855a5ab7a189696ba1e87ad3e9935b886f7500b741994ba4bb08cf2471f4ab7a46a4fa178a6eacaef4f516da86d920b8b087695201015f9c62aa69de11b8d163b08eda36bb4b79a679ee2f67cb2ef6dc403cf0476f7a4d735ad6b4abfb17826b6b1d1ddc89564c6163fef296ef9c7148cd49b76b8fd4fc33e20b5d796eb43bcf26deea4324eac003bb23a73e82a9e8fe0dd5b4ff00135feabab6a56af04d18312cb2124b29c8c9e31cd74da0df5e3235dea1a83ac084ac48c10295ea1b3dc639ae634b96cfc4dab5d45a9ea2658416d90ab0542a0e73ee7fad247446a4acd345eb9f163ea1a81b669ada2d3e28f71993272e4f23e953eb9f6ab8d0f54bad30bebf24e8a61b781950796b80dc9eb8e4fe154efbc41a3786b4f8e3d134a8eecc8490d2f38c1c1cf1d7daa7bcf1309357d3f428996d352d42c9e5b799140481475014e39cfb1feb45999b4e16918b7965e238fc3b63696da7a8b6602495530640a79c061efdab56cb41d3f48b85d324592eefe7613ab94645b542a7f88e33cfd6acf84638b47f0ec963a96b536a3776fe62de5e49f7d581c91c600038c563e8baa58ae802c9ee25bcbcf358c534d2b397c9ca824f418aabb66f1a92a9ad8b5a968b69e19b34d56e2fa285a2b98ee7cc672405cfcc49040c633c57965d6bde16f176b1addbf87cae976cd6e6f2e75bb3da259f6b7ce3730254633802bbfd5bc3d22f83eff44d64b47a65da156b9dea36e739009e0706bc17c41f08748f01f93e29d23c5573aa6937cb2d94f653005a63955011978e0678fc7dab78457567a787d1ebd4efa693c1fe2ad72eafc6bab6b69e20d396c25d415cade623c0daa71b7240aafe25f0e68bf0f7e16ebde198354ba89a5b56beb24ba576699830dcccf8c6f2768c5719ab780e6f087837c0f7d25b4d6d0aebea24b77065728c432e00049c0f6e2bd2fe294dadf8fac751d23c23a7c3a96bf09318fb746cb1792e30464918273d71c629bd19d32e54ec8d9d1f4ab4f8d1f0e609a7590e9f1da471c51b12a63914005b20f2723f5ab5a358476f2c7a05d6bc88f1c29b2de49b32cab90385dd9381ed5cff00877c6107c3dd0fc21e1397c3972f2c2a23bb7d3d8ba2bf258af2327760649aec75486c2fe6b1f14e996c25d461dd6ff689238ccd19cf0ac41ea3daa1cb57a1e749b49ab1da697a3c9a659b4312b329da5773630b83c11eb5763b78e37322060d90c4f4c902a9e86d3fd995af2512dd18d59c0396ddea7d3835a0b731346ee1ff0076876fcc3041c57333e5b11ccaa324691f696382cfd4f3f85398193ef3124e0e4fae2abb4c5243093b981271df1c73fad29b8f3633b01e0e066a0e26e5dc91c8550339cf5069af2882232eddc23c3ed03ae0e7148b92d923f5a7ee65f9836d1ed408c5d1a1beb7877cb3c974666691a69880ca09c8451d9456d286f21982fcfb091cf7c715164348ae17793fc7e9ed53866c9ec0d3031fc2fab49aa68f6e264916e215547675db92091ebef5b780bc93df1546fb4f8eeafadae8958a6b7055597e5520f623d6aeb7cca7d33ce6a096270e78348d9a558f6f438a5f6a09116304e7183453f8db8a28033fa8c7a52a92845387414360ae6ac00e1baf228ced5e0714d8d76f534efc33f5a008aea648e32ac5893d760248ae76ead46b512a00ad3ac98552e578f52474fa57453433baa882e9addbbf0181ae69e14f08d86a52f9b25f5d484c8011fc44e07af1cd5a3ba8daf76269d636735c359dc456f753e9ef8f9d03a827aedcf7c56dabc367a7cf2344a235040558c7355347b2895e490c7b6e9f63cad8da3711c8ab3ac3a4805b4415b272555b3f9d51d2dea52d03fe253a1c978d148924ce4a99873f45c74acf82dee2dde1b78e2669e65e6423017a9cf5ad7d5644b3d2e086469208b3c7d9c739fa1aaba489e5d5a3925937228c8671f3918fad06db2b9bc21b8b7b3485223712c436ed38fc48a866b569244923416d70a01f9949279e98152ea31c525bca1de4f231bc85ceee39edcf6aaed7d66d004174c8180543bf6b74cfaf152705dca57659fb73cb78f0fd924500022638d9bbd2a1bcb8d3ec7ca7b9b626791f6af971e727bf6f4aaaab34d7d68b7373e4246a592de160525ed973ce4d47ad49716f70f7f6935dddca919ff405c346e00e8063afbd06893e8c76a1e1fd2ee6ce74f2bece8ca58b2643fae7dfe94683a6dbe93a7b28bb8654098fb46d11b05c670496e08aa0baca78b7499a1b469b4bd500fdeabc443a719daa7a645588ac927b3689a10f74f088dde5c36fc773c633f8551a5ddb53434982dad526f277cb6ec7266670e1b3c9208f4ae634fb887c41aedd0d4a78a646568a05d9ced1dd7d4fe55af71ad5f6977d069f65a742f0a46b8dadb413d3a01c56934365f6c5925486df51893126d5cb0c9e9db3fa54974f9a1aa3985f87c91df5f34ad2fd9e5502dd91b050e3fbbffd7a9b4df11cfa22c961aad9bcbe50f96445c332e383ef9ae964ba9f836e8b71193b5a40df707ad64ebbe1f5d48aceb2b7dae3ff00546427691eebdff3aaf534f68e7fc4d44b1d274f9a48b5649ee2ce2187f28cc563fa1539abf709696f7525fc0595dd0891add49561d776df6ac9b1d75aeaeed6de47b785369fb4412444ac8475c7a1c7357a258aeaf9eeed6492de18a236e102e108dd9cf5a931d99977daf66deeafec6e12e2cede0795d150bb39504907a63a550f875f1013c63e15b6b8fb45a5adfcd1c8cb648db64400f1918e0fe752dff87a3b6fed0bbb7582d92ea3649e5cb6f65c73f2e79e2b93f0269da6f86edf4e1a359cbad5b1919bedccab88493e99cafd39abb1da94790eb64d4f5c8756d38a4763f6092061711b926e778cf2bc74ae96e2f2db4db38ee4bab2c8e1238e41c96c64823d6986fe2b1b69ee268fcec9c3b30e319cf071c7d2b9af0caa3f93aeeaed737ad2cad0dbc5360ed8f38508323fc7de918d9d8d4b5d2934d9e6d6353915af3c96db1be000b9ce00ec71de9fe17f160f1258cd39b692de48e52890483790b83f3f007f5ae4344f1241717ba81d4347d422b692e1d0c97bf3200380b9fe9527877c497d6eb7c6d7495b36926d905bce731ed0015607a8e7eb41ac70fceae76171ac43a458aa5f6ed42699cb2410c6493c70181e95ca5be8be21f145c5a36b224b2d106f736e8db191b9c74ed8e2b67c21a1cf24efac6aacb36a8643970c5a353d95467b7ae2afc9e2ab0b5bd3a6dc4925d5d5c1649523ce307b74e05498dd53f7522bea6b6fa5d8dc248160d09235b837b0ca5a48dd481b980ea3b75ef576f2f1f5ad174cfb048baad95f2e67bef376ac70f76ec73db1efd6a8ea1a847a0c96761068925e44d1344218ff00d52af5c1ce739aa56fe1783c55a4a3ead65f65557655b3b59c811283d06d03f2a0cfe237b53d421f0fe9b20711cf6f05b34c15773b7cbf747391922b3bc37e26b5f1768a75ed035092ead258095b3f2d40571d40f7cf18fc6b2fc29e245f1058b5869964c62577b5b8320202a03b7a927b550b1bfd0fc0ba3d8db6831efb58af1acdad63243ee66cb3138e9d79ab2e305d4ea34b8b509356796e7518ae249e141fd9b73b59a2c03923ff00d55d135a8ea46176e37a11b40f4ac2b3d0ec6ebc4d2eb31a4697820fb39955f2581eca3b1c77a8bc48b716d6d6765a6badbacacc0cd3139ce0923f207f3a8336bde5134ec741d3ec6f24bab5856d67bb6cfccf8660075033c53ae2e9b4d95de2b4f3e431ee668c8cb73f8f6ae3b4fd1da34b9fb44d347387299c1c9031d09ad5b186e76d979f2ca891ab61580064e69d99b7b04ba9a726ac6eed6592c1ed85e85dc61981ce7d3822b57ecff68b7b733c5b9b01983313b5f193b79ae7353d1d350b84bbb57fb3cdc02b19da4e3da9b6fac4da4c56f15e16bb8b9df703ef4677771ce38a444e9595e2cdbd4ac629addb1146d22b06ca8c13cf435cebc890daf9aefb25dc50a8e70335d4df48058c9359af9e5e1f351b3805723bfafe15c859c3fda97514576426e6dce8839cf5dbf977aa5737c3ca497bc50b6b51a7dbccf14e2dcbb166641c953f9d74175e1a171e19b23a7dcbc1a847999678d46e97d5738e32323f1aadace932e9b7133c7162d3eefcdce548fd29f6fad49e1fd3b6422e3514e364718c707f873ce2aa57b23ae72e74b5393f85ff0012ade4b5d6e1d66092c752b3bffb2c90a2b348ec7ee92bd31db83deb73e204c759d3a1bcb3b3b7d7a3d3a7db3e9f70769248c6dc772339c74e3ad59d5b54b686fad6eff7565320df70aa832cd8e39c75038f7f6a5d53c27a278faca4bf86e268e59a5593cd858a32b2918ce0f404671df18a933bda7ccf6239354fed1b795e730dc5ddda46174591c2fd9b8c05071dbd066b46dfc1967fd91690dfe9e82f21ce648db2ca09e071d08ae58fc35b55d649beb8bcd57748b762fb761d5c118518e00c8eddb35d6789a59e28e24b7ba648d9bcc9caff000f1d49f6eb43d1e844a77694198d67a9693a3f8ab569b7c76f62912a5b2b47bba0c337d7352dd78b1b59f12da8d125b7b26b5959dae2e205937c6304aa93823279e0fb51e17f0cfeee4b89358b5bf86e987931c817080e7208ce4f35d3a68b656b6f14d2dbdafdadba4e8bb5464e30bef546f5670bebab393f14687a8788bcab59667d36d849e7c925aaf37318e4063eb9ea39ac99fc4be1bd5adb518ef6c6fa286c4c702dc436eca6427a15e9d0f5f6cfd2ba7d6be21687a3f8fb47f0bdcea4f2ebb7f117b6b15525554024bb91d0f069de20bed4f41be31f9c8609adb3b8a02c6553d0e783c7b5244d1ace4f96270de2af0fea967a3df69f6e2e35d8a150c96b6f212e032eedc4920fddcf4ef5a3e19b3f03785f4183c3b77f67babfd06186f25b5b845692de595490dce78048cf349ff0b0534f516e6c2e6c3c49790b5bc376b097424302bb81f7c74c7a5705e2a5d634af164ba3adbc7a85eea9a407bbd5e6b7004442b162edec47033e82ad5cf579aa4ad193b18c7e2478d3c33e20f155b68ccbe25d26d766a1123a2c8638dfef14230463dabb1f823ae4b15f6b763e6dd2ea37489a9fdbee558a98dc12ca0907eee0f19aa7f0d7e195cf877e166afa85d6a13cbaaea96c6dd2788ae7cb190163c0fe2c839edef543e195cff6d78263fed1824816285f4b37af2b25eca237c9f93a77c6735add743aafcd1b44f4bf877ad5edd49acf87efaca4820d324da97d71002976921ce54819ef543c336c9a3c7e2dd091225b7b3befb5470a911ed538cb658e79cfbd5ef08ea389a0b7d2f64e92d8949adaea53e7c857a3139e981591ae46741f16413cf78adadeb5652c1069fb04891156057e63d0f1d4e6b338acefef1d7787e7bc9b4dbbba934f9b4d910b240598179801c1c8ce3e95475af1acb6f66f6a2dd6e02cd19f318ed3db712debdb18ae83c3f7177a5cab15e0566dbbbf76411e66dc9e9e8463a556f12e8c9269128d3b4d866bd9879b18909f965ce739c7af359bd0f22a3829fbc6ae976b7ef3b5cdcdc3885db7a1640a0a91f7472738fc2b5182e309c2e7358de1b96f24b08cdf490fda446a2e4479ff005b81f2f3db9ede95b1c2a8382a3fda18e7d2b168f1abdb9f42448d55738e6a1999957721fbbc918cd4c5971b43724526c564653ce6a4e7b11c7324d08917e45ce029e94ede40ce0b7d0552921fb3cb18116e818f5419c5585df23b34bf2a670833d7f0aa245ba95955191546e237349f754679a21ba867f34c6e1b07180c1b8f5e3a54b845620f0982187aa9183fa66b3f47b6160d756aba5ae9d6d1c9ba1656056453dfa920d408d25dcdd4629a326465ec3bd196fef6453f70da38c1a006c9f2a1a29eadb79a2824a7d5690e57de856e2973ce6ac0457f514bbbf2a465e738cfb0a6fef3f85338ed9e681ad4590aa8dcce1074cb74aa974be4b45b915cb7cd961f7bd00aa3ac4efaa68c61827fb0cef92b248990083d319f4cd598ede58ae165b897ed120f2f3bff00d58e31c0fd69a3ae1a1616292e6ea55914c502e30d8fbe4ff8573f777135c78c2dad630a2184e7743c9e411f31ab3677127fc25b327daa630cd6e4797d537039c8f4e0556d06e237f146a72a799b51369da7bfaf4ad0eb8abee2f882e1e5d506f79045b4a98a45c8cf41fe3573c376e21f36e676d9fc2b8e4554bdbc92196e8bcad716a4f9abe60f99001c81eb5b1a3c3f65d2d0ef56136248c3704a9a0d2a3b46c3ee219e169ef18b4fe58dd1c50e771f6aa56892dfdaaddf906cad7cb669acde21b9dfaee39c9ad686e848c03641c8cae7f2e6b2fc6d75e4e89709f6a3034bf21919b1dc6467e9507147de76245b3b65be4b85d58186389a336b8511e7ae7d455ab884430b2dd4328b72312bc44e7047418e7a5727369ba77f60cb2457b187b78cbec0e03392beb5bba5ff0069daa69e5375ec774a4cf233e3cb0b8008c67a838c6299d13828a56637c3fa3da683018eda5926b6991991a4666940cf439ec2b4ad6331dd5aceb2888676ed9080cdec2b2a6d4b49b9d72da14b8b88aea00dba3689c075e6b4eeb4fb4d6ac8852d11dc1a39990ee42083c7e54ae67739b6fb636a377710465a6b7debc9c3127918abfa4e8165abd9477515eddc934a0b4ae5fe6dfdc63b0068f0de977de1f9b51b8d46fa3bbb791cb46c17e6f639cf14b75e17bab2beb8b8d2ee3c9576cecdd8eb8c9fd6aae8eae65648ac744d674d95e4b1b8527f8d79e9fe353e8fa95f8b1b84ba6659fcddb14f22e767b8aafa849e271f67589b62ab00c5080597b9cff4abbad7883ced2d026e82e2597cb8c4cb866603938f4eb409cbc86e8b656b6b7125a4da9349a85fb9b889e6c096423b29f4f6abf712adb3222c6f70caff00bc6fe7fad6378ab47bdd534989a2b78227b7963682584e258c0c6ee7ebdaafdc2dcdddd4563bda094461cb2f4753d727d6919b2cead1e96b366e86e60e36c6339e7db238aafa4e8363a2318f49b68ac56e1cb48635cfdeefd78a7eb9a6dac7249a9bc2f77711c6b1889640a368c0ce4f1eff00854ba9dd368be1fb8d42dd1049e49640b873c8f63ef4ee66a4d3b26737ae5e5e5d5d476481de2b77cb221c82776016e2afdd787626fb3cb75e4bd9210442c5b709739c8c74c55af0bc730d2fedd2c1fe9774aaec1c63e5c75fceb5de42ab10661193db8e3f4a66f3a8edca8aba8cd0d8e8970f25a24b6db772c2e7866ec7eb9ef58da06826246d47cb59ae65c32c5249c4409240e9effa547ac487c51aa4fa7cf3ad941665498c9c2cbc83cf3cd7402eade35485a6b782691731468c06f038e39e7d6912a734ac16ff006afed2f366b34b52e36bbabeec8c7d00a558628e637b885e757da648d0676f61ff00d7a8e4b26668f33b21ee59b827d29ba6df4eb76f15c5b222eec42f1b81bc8e4e47d0523257bdc963d49acfcc9aeed830525d645236aae7a935ccdd78834bf06ea49a75c2dd85beb9063312f98acf20e0923a2f34fd53cfd66e752b4f10e9cafe1d4884f1cd6d2956760c329b40c915269d750dbaa5ecf6a74fd3a18034507df98738424f604718e7ad06a85d3b4d97c171eb373650c7785e7c2db45d4b1ec7af1ce73ed5b31796d0dc99ec6d76472052a89825f009edcf7fca974f962b7be96e2dbf74b21cc908182fd324e7bf35cb7c50d4e1d334eb527539b4e1b94ac9193b1483c1738e33c0aa34df43a75beb1d3a692e278e0b42c07efb761d97b0229daddbc5aa69a0c67cd73fbd81d5b0473c907e99ae5fc5f3788f52874893c369a2dee95736ff00e9975a8c7bb71c67729c8e08079aad71f10e2f02e976d05c692f7362afb1afa1da61897192e0139c034acc51a6ef7ea26a5f116cbc37a8691a3eb96d35cdc6a12379774a0955f6cf7381df15afaf58ab9b22892c86324a966c7cb9ce7ffad59fe2cb3d3b5fb5b1d52d4bdde9fb96e9648d86edc39cfd3bf15bfa4dc69f7567992569a22be6a34a7a313d0d5bd91e84adcabb94e6d467b768ae2d72a01cb4ae36a15e9c7bd5c8e2b28563b8b68fcc49065e4e776f279c922aaea9e187be9a29ee6769ac910ba5af9bd1b0704631c66a9c333e9b716d1202a1c736f927f11cf35267a0e6d399750b6bab6be9710b1665693fd667f871d8558752d7497914989d94a3ee3805b3c301db038ad6bc589550c36fbadd46e91d539040ce2b22f1ad6dcaf9677195d5b0dc6075a11377b58ea1952e2dda2b91bfcd00bfa702b9ad4b42946a4675918458c471c6768ce31cfad7452cbe5da35cee5485137b6ee30a3a9fcaa7f2e1beb65756474650c8e0f033c834ae79f19ba537a9cacd6f1ccd219963dcc002bdcb01d0715434bd423f0ccc6e668e38ec66902485ce3cbc90338fc6ae6bc59a63048ae891f2ac0e031f5e95a9a5d969fa8e87214b613c610ef5917712d8ebd69f43d2e75386a69497b6da2dba4b2dc7936e4ed12e382588da07d734cd54c735aadaddda4d24171f2ef8d71db3cfb5715a1cf79adc9ac7876e3578e7bfb79a2b94b5dbb5a15046d009ea303f0ad5d2a6b7b1f1ddc69a9777b7379e52cd70d9fdc927f8707fa54f2f5b9cfec947a8ebdf07787adee34cd52f62862b8d366696dd6d98c61c6380464eea9350d4354d3649e736d15cd9c7b2e36b1c2267a607af3d2b9cd4bcab2f17de5aeabe6aacafbedd864a6dee07a7bd695af8b0b69bfd96cbfdab25c87449235da593a292083820e07bfb559a2a4dab9d1fd9f49babab6d6df4d85750750915c4814cb86ebcf5c7d2b335abdb5f1068f757d6fbe67b5b9f29640a429c1e719ebcd64c7ad58c9a5db586a305d4371013b2e91332458ec303935d2d9dac5ad69b2456b7cf711b465639641b093d4ee181ce452db71c69ba525239ab7bad3f499135ed6754944d6a1e6c420108bd79ff00f556b5ceb5a3788b435bf1771dc68f70813c8207cf904ed1d0fcde86b1c69f727cbd14e96679ee0f9524807cbb49e49fc2b12fa6f0fc3e243e15b5b8497c49122cc96ebf2866e8abce07e35692dee77c7de77b9d6e9ba79d4344b78eef4f3a65a44cd3dbd9dbb79619002150f3d73838e3a57977c37f07d9f8dbc437baaf89f49d574cbdd33539a3b2d3c33087ca3ff2d081c1f5e4f6af5db7f2ad418ef6f73756b1e5a3854947623233d4673c75ae4743d6ae758f1af8aacadee67b4b8b8821ca8dc0228186ebc0c827a51aab911738a96a74a9e0fd222f12497b6518b731d9359c52ab13b118e493c7273deb9cd434bd1e6f1a461b4eb9d62ead4111eacac330b95ec323bd77e96ab1daf9cf3f99671a011321f970a304311c9e7dab99d4b53b28ec351d6bca4d26cb4e7fb43b4c361955472c339247a7f2a98b328d4926db3cfb4ef1c6bda8fc404d3bc3ba44d67a2d982ba96b5328d92be791c9e79c74af5a5f164736c48375c0e86541904e71dba53e4b11ad5bdc4d26c4d266b5125bc8401e648cb925b0070322b9ad318c7a1abe9da7b237cbbe48f2a8e09c2ed27a64d0f51b846aeacd35f1f690b2431cd7b1899a43186b8f9151fe638ce39207f2ad0bad5d64d4a38ed6e3cd7902878fa93fed0f6af34f1147a468de1fb8d73c61e1d8efecec6f628ad7ec0c6476f98067600e78279e3a66ba3bdb586d2f9b51b4b79edad646cdba4cfb5dbe4cfddfeef2001458c2585a32d96a77cec4ab6f046d6c6ec739f4a972ca4ae33b7ae3e99ae623f18dbb2cb6f183f68861dee02963bb1fa7a52e95ac4daa42c784925019959b803b0cfae2a7919e6fd52a5f44749b666b825a558e353c46a3af19eb4ed824cb71b33904d60f867c4235eb39e48807b7593e468e50f8c641c8eb5b22568e2558d16707f841231fa566f4dce69c5c3490fcef63b4e0e28b8b7924882c53183e70e7cb03278e94e36e8f32b9f959074078a4e7cc661c67a0a83024de3b8dcd8eadd690f1c9e0538280d9eb49260b004706810b8f968a48e4ddc63001c5141254db8148bf353b7522f06ac43bfcf150dd42d736f2c21ca1653865e0838e0e6a62c0546cca54f7e33417132eeb4f17d6e9039784a2826653c938c74ab5242925bac72b35cc4170b9182303bd4e8c926d6670148e189c01f5aab70b1eada55cc56b73b83e6332c2c38f5c7341d312eac905ac725c36e4800555200c6718c71585e17b5169657d23ce917da676dacc30c05171a3dae9fe1e36d13c96d6f02891df793961d49ab6c049e1f0c02ceb32aed239e091f37d2aceb84b430dede76708a7786c8076e7bfd7b8ae9e3d3615863322b2950194337dde2b212dd6064457f2d1dd79273d3b0adebb12f09146ac380c5ba0fc698aa3ba22f322765750779e060706b23c61a6c7a969f1c12c693c5bb718d9b0411ce47af4e95b3712796b1e176a31c280307a7359fadc2750d258c302dcc96ffbc09bb9dc3903df9a85b9853d2656b3d3742bbb852f0c7673c71ab2232ed27b77e0d6d8db6f1e1a550bd8e30031e3b7e15c9ea4f63e22b586f92e8c37a144725ab707cc1cf1ec315d4c0ecba7db79b1e64d80600cf278cd5b37a9a91e9a9731c616ea312ce4b2798a777cbd6ac05b5d2e169259192d949f337316c0efdea045668da27b66570d963bf9fceab78985a3694d6d712b7de5c41f7449c838350671d59971f89b4cd6a3bfb3b8827d3e28b9598f00ae7e535adac6a4ba3e94b71196bb0e411b7e6dca715933247a9c36f6125ab5bf01a42cb92e3b60fd2975dd075ab7996e346ba8bc9876b243372b81d462aec754611ba57347c3fe23935590c1756aa8cbf30c9fe1f423d692486e9a6ba37296d766362f6cd8c3a0c70a3dfdeb23c2f34dfdbb7f7172b199665059101c2b77c56d5d416f33ce82097ed9b0b2301f29e38c1a964558f2bb264f637b25c47249728b6f0160635e4b631f36efc6a75bc96e1a268960304885848ec43e01c7031c8fc6abdc43731e832c7018ccfb42b2492003a72a4f6fad645969970faae9ba97991a18edbecd240b23381ce70bdbf1a665767431c915c264491b472653e6c60fe0735ce78ae6be7d72c2cb4fb68c430279f32371b957f8703ad6bcd1da49a8616cb0ea770707bd65e92cf73e2ad4afd8b3436f17921a4e0e7a9007d280a695ef21d6fe36d2afedc5d97086346deb9db8607eee2aade78f6c756f0a5cea369940aad1aac830fbba7cbea39a89af347ba920b61a5178ef8b46db63ef9c93edd2a5d434ed1ef352b0d3ad56dde28d3618a10418c8e793ebc551d0bd9def62df83f4bb6b3d1a29a68c1bab940f2cc1773631d39cd67d8df693a9b69d71712c6fb0c890b5c45b594eec6d040eb5d55f5b32c6ab004411e319525781d080466b9bd27c25f66d3864db5ccd1ddb5e2a07388f20e73cfbf4a4473ddb35ede59e3bef324665b690154b66db8dc3a107393938ed5cbf8efe20e93e11d3f4ad4b59d2ee24b892e8c31476c85995fa64e3b60f5adbd02c6fdae275d5f5882e2e725e3b35da1a053d39c67a7d2b4b54b60b6d1a9d3d6fdba2b4b82a09e324e38a46236ea3b6b8ba8ae67c19248b680b901e3201dac3a66b1dbc41358ea967a34fa53b25e46ce92c6372467eeaf27fa9aea5648a4b795a3f2c056daea39546c648fc3150c7109b6866262c64267a83c7ebe948398c2d63c4167a3b5bacaf1cb24f234323acc3e46c600c0f7f7abb7d611ea1a6886e50dd69d347e448bf291cf1939ebd699a8697a3436f6d6f716902468e5a18420e5faf1dff001cd5a7b0b7bab306d59a143960bd33c73c7d699a464ae7061745f0edbe8fe1495ee2e742821729334c0b2387ca2123b0e9f8d6beb30db49796e61bcb47d17eca619aca48448676ea0138e39c56db6806de3636ad6a5a36dc126815b71c7f8d518d2f57ce92ef4bb68d963668e48fa2b60e3815a1dd1b35a1a5a4de69d069d6d07fa3c4889ca346309e8a071c0aab2786f4fb88e4bdb091086724aa9382dec3d2a9da5adb6b963697175631b48418e5da4a9cf438c1f4ab969a269ba74f8b7b692ddc02b1af9e4f63d4522651b752086199f33c38258796c5db85c7a541a8787e6d42e20b880c68f10e5a4c820fa8ad56d3ada7863595648c2f2594f04fad50, 'stamp.jpg', 106678, '2025-05-24 16:04:41');
INSERT INTO `signatures_stamps` (`id`, `user_id`, `type`, `image`, `file_name`, `file_size`, `upload_date`) VALUES
(2, 1, 'signature', 0xffd8ffe000104a46494600010101004800480000ffe1334c4578696600004d4d002a000000080006000b000200000022000008620112000300000001000100000131000200000022000008840132000200000014000008a68769000400000001000008baea1c00070000080c000000560000113e1cea00000008000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000057696e646f77732050686f746f20456469746f722031302e302e31303031312e300057696e646f77732050686f746f20456469746f722031302e302e31303031312e3000323032353a30353a32342031353a34313a3232000006900300020000001400001114900400020000001400001128929100020000000338300000929200020000000338300000a00100030000000100010000ea1c00070000080c00000908000000001cea000000080000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000323032353a30353a32342031353a33373a353100323032353a30353a32342031353a33373a35310000000006010300030000000100060000011a0005000000010000118c011b0005000000010000119401280003000000010002000002010004000000010000119c0202000400000001000021a70000000000000060000000010000006000000001ffd8ffdb004300080606070605080707070909080a0c140d0c0b0b0c1912130f141d1a1f1e1d1a1c1c20242e2720222c231c1c2837292c30313434341f27393d38323c2e333432ffdb0043010909090c0b0c180d0d1832211c213232323232323232323232323232323232323232323232323232323232323232323232323232323232323232323232323232ffc0001108010000cb03012100021101031101ffc4001f0000010501010101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b51100020102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f0156272d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00f4ea4af48e00a434c028a0028a002928018d346a32645c7d6a0fed0b4ce3ce5fca8b14a0d8f17b6a7a4e9f9d3d6785feeca87fe0428b038343f208e0834502b0b4940829298051400521a004a5c9a044b452189450014500252d00253244122156ce0fa1a00884734600428e9e8c3047e2297ed18e2489d3df191fa5334bdf61e1a293a146a0c317431a7fdf345c4dc90c36b06388c0f71c52fd9d07dd675fa39a039d8794e3a4ef8f7c1a705907fcb407eab40ae84fdf7fb07f31416907fcb307e8d40837f1ca30fd68debebfa50214303d083f8d140052d0049d68a40145002528a004ef4500140a00292801af146ff79149f522982dd57ee3bafb06c8fd68b8ee1b665fbb22b7fbcb8fe54bbe41f7a23ff016cd31e8279f18386253fde18a94302320e47a8a0560a4a041494008403d8526c51d063e940001ee68c37afe940130e28a4014940052d0025140051d0d00569ef16de45578e53bba32a12298752b65cef665c7f79714ec691a5292ba15751b3719132d4a2f2d88e264fce8e5637466ba0ff3136eedeb8f5cd2ab061956047a834ac438b5a8633d699e4c79e17693fdde29937104722e76cbbbd038a3cc75fbd19faaf340f71cb22b9f95813e94502b0514005250226a290c2931400514008cca832ec147b9a84df5a838371167d370a2c528c9ec868beb4ddb45c45bbd3754cb2249f71d5be8734581c24875215561f32823dc5024dad889ad6de4fbd0464faec1555f48b566c9ddf8607f4aa5266d0c44e2473e8768f03aa2befda76fef0f5a9f4b8208aca330c6132b86f5247ad17d0a9d57386a5cef45239828a008a509b0b3ae71ce475a68dfb0346de629ecdc1fce81ad50a2652db4fcac7a06e3352502b00a28112d14861513ce919c64b31fe1519340d2b8c2d70c3e4444f77393f90ff1a416ccdfeb6795bd81da3f4a65dd4451696ebcf94a7fde193522c68a30a8a07b0a5725ce4c52aa7aa83f8544d6b6ec73e5203ea060d1760a6c88f9f6a491ba6849fbbd5d7e9ea2a78a68e65dd1b023b8ee3eb4c7249aba24a86e24d8985237b1c28f7a44a576451dfc45cc52e61957aab8c03ee0f4350b4a9637658b0fb34ed9241cec6f5fa1aab1bc69bbb5dc8ed2ebecb3cb6f73742452c5e294f4c13f749e9915664b90b28688f9aa17e64439fc451614a9fbc2bde452405e1b840c318c919fa629ad7ca3746cae937f082a4827d8f4a2c4aa7a6a3bed4acb246ff248abcab719f714c512c1fbc406489802c83aa9f51ebf4a02d6d18af7114c9b5559dbb2942307f1e95179d2dbb6c7ce71f2873f2b7d1bb7d0d0351d2ccb50cab347b9783fc4a7a83e952d2316acec49416c29627007269015d58ddc6af14bb616190cbd5be87d31524714712e1102ff005a0a6ecac3e8a0903466800cd068013dea196da399b2ca437675383f9d31c5b4c4f2251d2e5f1ee01a7c702a36f24b39fe263cd05392e887b22bae19430f423350358da3ae1ade320f18db45d846aca3b1042896522d9b2036effea890383fdd3fd2aeaa2a7dc50bec0629b2aa49bd7b8d3146cdb8c685bd4a8cd3ff000a5733726c63c692637a2b63a64669dda80bb1290807820107b1a05762246918c222a83d70315262806efab1f4c950491942719a404692ac416275116000303e5fc2a6ed9140d8514084ed4b4001a4a60029690076a4a6019a2802aea38fb13b7f1290571d8f6ab3ce3268e85bf810514198526280129280178a28025cd148621008c1fcaa26b75ea8cd19ff0064e07e540d3b09e5cca3e59f3fefa83fca8cdc85e56273ec48a657bac4f3651d6dcfe0c28fb43f7b7907e5fe345814509f69ff00a6337fdf347da47fcf19bfef8a2c1c9e606e7d2198ff00c068fb4ffd30987fc068b07279886e9bb5bcc7fe0349f6a73d2d66fd07f5a761f279809e63d2d987d5852efba6e9146bfef3ff00f5a9072c5750f21a465699f76d390a06066a7a0993be81494102d21a004a2800a28024a290c283400525001d28a004a5ed4c4251d6818514005140051da800a2810868a004a2800ef45004945218514005250021a281052f6a60276a2800a3b500146681876a28105140094500251400514012514861450027141e680128a620a280133ed450029e292800cd250028a5a4014da602d21a00293b50004fa514012d14863594329539c1e0e0e2b9e10ac7afc86ea79d6289774619c8523007e3c93f8d544da8f53a0dc37eddc376338cf38aab71aa595a4be55c5ca46e1776d39e9492b911a7293b22b5d788b4bb2892496e815917721452d91f854a758b56d15b558cb496c10bfcabf371d4629f2b2dd09a49b4321d6ad67d15b548f7184293b71c8238c7e7547c37e254d73cf8dd1639e3248519e578fd79a2da0d517cadf62beade2c7d37509ecd2c4c8d1e30c5f19cf7ad77d517fb13fb413cbc98c32ab371bbfbb9faf14f9762de1da8c65dc669bab7f695abe2309791ae5a16ce33db07b8acd8fc417b6d2c326a96be4db48b83b636cab67afb8a144a5865cd28bdfa1d048be7dab8439f310ed3923391c7bd61e992c91b5bbdcdcacaf296843b7de43fddcf7e7da85b114609c64990432df4ee636bcb9627cc899578dae0e579038abfa6cf20d53ca69a57592d91b6c87a3038279f5a6ec69384395d885ee2e23b89af0c8a2582558e688670d1762467af39cd6fd4b39eac52b584ef452310ef45003734645004d452185739e25bd9ed644482dfcc69a268c16525739047f5fd2aa3b9b508f34ec6ad82196082eee2255bb3084761fcbe99ae5fc6b6e61bfd3f52688496e1bca9411c73d3fafe54e3f11a52972d5b197ae686da4e950cb7136e8ddc8fdd8fbb9191827f1fcea2d22ceef54d0aeada3d53ecb6f0b6409170af9f539e2adbbab9dd2ab19c6e5cf05e976f78f2f99296fb311e64039572c0f27f235a56d676da6f8f365b288a39a2cec030338ffeb54b7ba309caf3947c84f12c305b6bb6d7331d91dcc6d133e3a1c119fd41fc2aa691a4c17da5dcdc199da78832f961b233d41c7ad34ec8d61554682915acb5696cd20be8237790a089815255f1d71f9035d56aa1b56f0c492a40c6468c4a9130e410738fe743b5ee6788b29c66997ac9e53a55bb042b2f9230b20c61b1dc76ac4b4b2d422b09af8403edc58b35ab81b0907aafa71d2a5347342a28b9799d2c672818aec6600b03d41acfbdb4be7be8eeacdedf2b195db2a743db04524f53284927a934d68b7b64f1dcc68b2cd104919467ff00d601a96da2786d628a497cd7450a5f6e371f5c76a57e84ca575625a3a504099a43400945004c68a4332fc4124f0e92d35b82658e58d940cf3f30e38ae335ab5d4f528252eb34a091e58c8c0e082463e9dfdeb48dac7a1858c79398eab42bdf31deca3b2962b78514c72bff001f1fcfd6af6ada645ac6992d94ac555f0430ec474352f477396a7bb52e88a6d120bad0e3d2ee5de448d1409070d91dea4d434a8350d25f4f24c71b2a80ca395c74a5717b4645a3e8369a2232db9919e40a1ddce738cfe5d6adbd95abdea5e3c2a6e231b5643d40a2e2954939730f9ede0b98f65c4292202080e33c8a7ac69167622ae4e4ed18cd2d49e676b065234278451c93d00a5c8db9c8c7ad315db2359a377da9223363380dce29b71776f68aad71324419b682e7193458395dec2db5ddbde2b35bcc922a9da4a9e8699677d0df897c92d986431b865c1047f4a2c3706af7e8544d6a26d4cda9422266f2e298721dc0e57dbffad5a59a6d58270e5b0668a440521e9400945004d452194b5604e93758382232dc7b73fd2bcf7564d4a08a0d42112456aa88a2546203647058761927239ad216b6a77e15ae4773b3f0e6ad3eab621e6882c9180b230e85b2723ebc7eb5b43dea1ee725556958e1fc5baaea9a6eacc96d7462865806012319e471e86aa1f1b5dc1676a96fb18c30859f7f24b0e339ad124d1db0a119c14994ff00e132d72499984e91a9e00d8b83ef5bfafebd771f85eca78098e6bc00348870508e4e31f4a1a57413a115cb62f784f589353b278677df3418cbff00781cfebc5733af7dbedef7548927b83007f3368739c71cfd3e6a168c29538aad28b1d71e329afb47fb0cd65b677c248e5b2a57b9c763d2b5fc37a84b2cb36897d2210b1621c704af71f91a1ab2095151a6d897da43f87601a8d85dfcd127965648c1dd93ebf956a6bb1fdbbc3124ca76c8a8b3a907a11cff002cd2bdeccc9c94b967e6334d918de4b0a3bac779024f14b8c907033db151416e3fe121b8b3175708d1aa4eaca7058e4e777f7bad3bee54e4af2f42e6b31a426c25458d02dda93c6396cf3ee6b5bbd43d8e59bbc109de8a464149400868fc68027a43486417aa1ac2e14f431303f957986ad2dcc776ba7090bd9ac88c55b38246067bf071c0ad21b1db85d5347a6d8422dec218d502e1470063fc9ab1dea19c937ef33cebc5f7ada96bd169b1da1678cf95c9c1624fafa74a97c29a6e9fa8433d85ed86e9e09773b1254818c007f11d2b47a23bd4b968ab1b9aa785b4c5d1ee05ad9959510ba1424b64738e7e9d2b02c255f11d8c7a04a8f6f2400c8931191c1e847d0fe9493d2e4c2af341b7d0d0d0748bdd07c4ff006764696d6684fefd538e39e7d2ac789342bfbebf13d908ca4b108e40cc14820f5f714732bdc955d2abcfe44da6f87a65d027b3bc5b75ba70ca9328dc40241193f51599ff000845f3bc7236a11ac839ca83953ec7f2a14ac358a4ae9f53a7bed35b50d14d84d31dec8a0cb8cfcc31ce2a786cd23d3d2ce43e622c6236278dc318a9b9c8e7a5909a7d8c7a658a5a44f23c699dbe61c900f6fa557d3f46b3d34abc4aed2852be63b6588273cf6a2e1ed1ebe65e9638e650b222ba82080c32323a1a76734880a28105250025273eb4013d1486477009b694038250e3f2af2fd54bd87f62dc2334ee61495c86cb647607db0063dab486c75e19ee8f4db6b85bab38ae13056540e3f11528cd41cb256645f66b73742e4c119b803024da3701f5a1becf6fe65c308a2dc32f21c2e7ea68d47ccde8392e2192431a4a8ce064a861903b1a5d89bf7841bb18dd8e68d8354305c4244c448a444489307ee9032734db7bdb6bb04dbdc45280013b1c1c67e94585664df5a0d021091c64e33c0f7a8e39d2784cb048920390a54e46681d8c8b6d6ae1f4b82ea6b51e6bdcf91246a71b7923233f856a4b776f0a33c92aaaa384724fdd271807f314da2e54ec57b4d44dcf9a0dbc8ac970d0600cf4e8c7da9d16a704d1c6d187264576518eebd47d69d85ecc922bc599b688dc3f94242ac30403dbebc558c82323bf4a443560a4a420cf347140137f5a4a0620753900f4eb583a5e956325ab0312b08ee2455e32400e783ed9e69a7646d4e4e29d8d76da20f255df94203771f88e86b9ad275efecfd0676d426335c5bcac8137659bd067f3a695d170a6e69f733efbe223c312791a71121fbc24270bf9563ea7e3dbbd4b4fb8b536912472c7b5b04923dea9451a2c3a8b287873599347d4cdcceecf1ecf2cee248c638fcb02bb3f0af8aee359bfba82f3c950177c7b0630076e7af07f4a1aea3ab49495cb964a53c51acdabbb6cb985255ce31d0a9c571eba3788b428d8db492817127943ca6073cf1c76cfad09f72616bd99ada1cde258eda4bf9649268edb723dace486603ae38e4d58835ad76fecae3c8b47659642b13018921cf3e98231de968caf654e4ee3aed67d474f99ed85fa5e45e5cb2ab47b57cc53d87a9e4f1ed5b3a389eda5bf8ee2258e369f7c58206e040cf1ec687b5899b5cad2238f4e6163a9daaed8de4b8792062d9c13c83c74c1a70d34cb677b04d70ce972abbb0832240002c09f5c0e3da8b99b91662b28d04abe6c81649565edc118c8cf70715661b548b1b0be048d20071d5ba8fa7349b2272b930da650d8f9b18fc334ee070052327a8dcfcd8c1fad19e79a4213271d3f5a5e6818fca96c6738e7f3a56ea39a0ab5888b0c94122f999c609aceb04458f52230a1ee243807b74c9fc41a7d0d22b463d258e786616d29dc8a503f503fc48eb5870783f6693711cd787ed53c9e6f9cb92073c714d3b1bc2a7b324b9f0e35fda5ac7a85da34c8c49748828618c63ebc5538fc09a7797ba4ba99f0e7732803e4ec39efef47314eb0a3c1ba5b6232970c0bb7ef3ccfc8700fa75e3ad5ed3fc3969a64c6e2d6d6532203b4b1ce41e08c71cd171fb456d4db92cd1a713c70aa4cabb43e39c75c7e752cb0b92be58418e4127a1a573979d0c486e10a80e847f11f5e9ffd7a9043890b6e27273f4a0529ae83ca8f53ef50bdaa4872e4920e410718a4429d89760393d0f1418c64e38cf5a62e615630338efd69d8e2909b13df14b408434940098e38a5a0657b9b3b89d94a4d1c7b07042927bf519e474ab0b13024bca5bb1e000699aca69ab0c168a2457324a4a8c7dec53bece9b4a92e417de416ef9cfe5ed4ae4fb46430697656c434508560776ec9c93cf27d4f26ac08a3e0ed1c003f2a2e0e6dee35eda0931be18db0c1c6547de1d0fd6a6edc504b93133c519a04251400514084349400b46680168a0043476cd00266938a0005140131a4a0619a406800cd25002e690e7d681076a2800a28189484e280137a923e6193ef48c42f2c4019c734c2c292abc920738e690baab2ab30058e1413d6804ae3e8a42128ed4009487a74a041f4a28193668a00c7d4f589ac2e658d2d95d62804e58be32bbb0703d696e75eb5b4d421b79dd1239630c1b772a4e3008ed9cf5f6aae5d0ea542f14d750bad6d2daef66c66811c473381cc6cdf77ea0d3a4d663495a3104accb3fd9fa81f3e323f0228e512c3b65cb2ba17b68b38431e490c8c465483822b19f56bc86fded6420b477888709ff2c5c7ca7f3a12153a77938b268ee350d3e68cdf9334328542c98f9642c40f4c8c62a7d55e58a6b32b2ba43248629369c7de0769f6e451a5c7cb1e64d6cca66f75116b661508b858d9dcbff1ede1948fd73572e5de758a68158acb0b1072460e015ef4ec572c6e88a6bb32c56b7309224303499ea08006411deb4495b9b40ea010ea18647e352d19ce2924caefb6360e228fe5941f4c6eea6a6b904c4542ab6791bbdb914136d511c920b9b6cc2cac0f5c76239c536e952ead8121c82a4ae0e0a9fe79a638ab16a17f320470d9ca8e7d6a4a464d59852521094868105140c9a928031b5ab412de58cc63678cb986751920c6dea3d3205677882c2796e6ed21b6678a5b11b76af5746040fc89ab4ceba552c9263afad2fe796f556d99e1bb823753c0daea33cf7ce463f1ab13595e3cf34d1db93b9a09d03301f32e030fae28ba35f6915d4b1a743a85adfcf1ba2fd8da491d4ee07a9047bfad3db4e69355bb924506d678d32436183a9ff0c52bea6129a536e258d42ccdec11c61b6ec9524ce7fba73fd29da85bb5dd8cb0a101c80549ec41c8fd452b9929dac48d0a4c11a545f3141008fe1c8c1c556b4b39608ada3794308a328c36fdef43ed8a2e0a62269e2111ac78088ee40c74560723f3a9ed2136f691c2493b176e4fa50d8e73bab0d116dbc924218aba28ebc0233dbf11f95492209170738c83c5045f51ab0aed65d802eec8029624085976e3e6cfd682b998eb78fca8bcbdb80a4e31e952f14886eec4ef4134086d1400514012d277a005a8d268e47744705a338603b1a0a49bd848e78e6804f1b868c8c822a8c7aee9d31509700ee2a07ca7f88903f514d4596a9c9ec5afb643f6e5b424899937a823823be0d57d56f9f4e8239921f3b74823280e18e738c7be684b5b308c1f324fa9269b766fac56e0e016660540236e0f439ee28b9d46ded64f2e52c18004e109ea703f5a2da87b36e6e288a3d5a19da58e15732a2b101d4a8257a8aa2356bf36fe6fd9e25194c6096c865c8f4efc5528f7358e1f5b48b6da930d105f2a077d818a7239ef5a24fc848f4a9b18ca3666545a9dc4aaf88601fbaf314f984f43839e2a690f94f2b7993ec23795ce401eabdff0a669ecf958b71348a328cdb4c5bd4af7208f5f5cd5892405d06e640739edda825c45b590bdb21272d8c13eb838a9e9194959862987ae29121da9280168a064940a0064a1da2711b6d72a769c7435ce5b1b8b4d7e68ae35208c7ca723ca004bc1047b7ffaaaa274d0b59ab15629a4b3bf554792348f5130c9196ca147048fd4d58d3ec63b2d523d36ea0b79f28f2c726df99543e40fccd533793d1fa15ef1162beb864204b1dfa36e5fbc8b22e3afa679abf75697f1e971da3b9bab87983473738420823777c70686d0a528d95c974abc8ad6eae6c2ea42972f3970ac7218b72769c0e334dd7ad2e679637b785e6cc4c842bedc1043027f118fc696ccc94b96af33d993c5a74dfda22f24c0cb31db9e42b28c8f4ea29ba6d92b9bc79612a8f36d48d8600543c11fce95c752aa7b126a0b2426dc41017899d96445c743dff3ab76f349299449104556c290d9dc31fa7a51d0ca5671b99b1e97242e312aec5128c15e4ab738cfd6af180b5b2ab92cc212a71d0f145cb9554c16091921470bb426d233920f6ffebd3bca610c4b1b9f958105867f0a2e439ab85946523932e4a9918a823a0cf4ab5de919c9dddc29bde8242929083b52d0324a2800aa775a5d9dddc2cf3c5ba450003b88e8723a7bd08b8c9c5dd0e7d3ece4695a4b74632b2bbee1d48e86a4682179d2631a995010af8e467a8a771b9c98be4c4ccc5a242580dc4a8e71d334e2c011938cd04eac69589d86e542c39190091ef4d3756eb2889a78c484e36ee19cd16b8ed26225e5bcb3bc093234a99dc80f231d69c268da568838322805973c807a1a560716ba15d353b592558d1998b398c1da71b87519a75c5ec3684ac81f3b1a4f9573c0eb55cacaf672bd81eef6dcc29b7314aa70e39c1ea01fa8cfe54b7172b6ff00795986dc90be9903fad04f2b42bc8220b84ca807041e94c92f1556060836cadb06f6c60f614ac350b886e6558237310477072add8819fe94efb51c2392bb5997f26ffebd162b910e94b2120331dddbd07b542d24fe52bc58900db9e3ef0eff008d340a299621956781254c85619191cd3e918b56760a5a4062e9fa94efa8ac334c1b32bc2536e08206e07e95369fac7da1e585d4971bcc6e1485703ebdeadc7b1dd2a1177b0db3d5249240cd8f2eea3696dc13c82bd54d51ff00848ae5937ac713036ff680307380db5969f2a1ac3c6e745bfceb4122e4078f70c7b8ae46caf6f6f7ec96f2dc490b4d1cb034839cb2f461efd294519d18ab4afd0d7b6fb6e9daa436924c27b7b9270c73b930bcfb609aabafda997538e3541bee612913b1e1645208fa7142b5ee5c2ca6a5dd116a7713dbeaaf74b362585a34289de363ce78e68d4a3587599be64024db307c64a15e71c7ad51bc146e9aec58d56d960d3ffb4f4e95a391732673f7f77ad56ba59a2d71af7cd6df198f8eca8dc11f4a22453b4f72dc31c963aaa6f8a3c4d336c3ce4023f2ec2a5d6add9ae6d670f8453b1c671c1e3fad4f5b99f32f6a98cbf2f6b756d0c4014688a0049e48c6de7d6b42d2e63bdb7495594c800f3173f74e3241149ec673f8548cc92edbfb3bce2d2c3279d222b28c907240e3bf6a6df4f7575648ed6ee2481a3959421cb0e09c7bf5e9ed54ac6918ad19a51f9972d224c8c554831395c704647e3d453ae6dc2a4223dc02b0e9fa7eb5265cc94ac2ed99e085e48d7ccc9deabc03dbff00af4b6f0ba5b609c6d62539cf1412dab0eb1468eca256c060bce3a54f49994dde4c5a291255b7b6316a578fb005976ba3633838c1acf874db982fa06084c6934a18838f95b9071f5ab4cec8564af720b4d2f5282eed0491c6f05b4ae14efe763020ff003fd6963f0e4ea631e6441764d0b1c93f23e4823f1ed4f9914ebc6fa17f45b1bcd3ad8dbdd4e92a00bb3049c71c8e7b53b4ed2a3b157562b28f3da68895e533ef52df639e75136edd4b335989ef6daeb79060dd851d0e46296e6d63ba309624343207423d452b8bda3d3c84bcb286f6dde194603632cbd78f7a626996cb70f7050b4922057ddc86c77c7ad17638d59455912cf6905c5ab5b4880c2c3050703151cda75a4a58bc592ca118e7aafa7e945c98d494495a18d9919941d9f772338a73207c06008ee08ce6826ec6bc28cc59bdb1ed8a54882ab640dcdd580c6681f33b584f2d1e355ebb78cd200aca1d6404062d91c8f7a07ccc7429e5c61436e5c920fb1e69eebb971d2821bd400e003cf1d693680bb474028108060600e9476a4014b408928a0625048c73c629814edf508ee5ee638c1df036d2bea719e29b69aa5b5f421a37559083fba6601b23da9d99bfb091991f88e6731b7f67ca6273f7914b639c1e83b5487c4910d552dca116ec4c665231b5c76fa55729bbc26b64c975cbb9934d49eca453fbc00babf0074a76853bc96d3c32b879a094a3b83f7bbe695bdd33704a8dfadc4d53559f4f9d512dd5d190b6f24f51daa08357b9b89fca68954194c6c46780572a68e556b970c3a74d4ccb5b8ba649e3135cb4d085664666e0ab107eb915a92dd49169de48f3d66925290b11c939caf27b5365d4847643b582a52d6e265dd042dfbe0a73c1041cfd0d5db31326911a6e56942611c1c83e87f2c54f439e492822aaa4b0cac91dc11f68562c36fdd9303906a5b790c71cb327cc922072b9c0ddc86a0a6958b16cf1c291c0643924850c31ef8ab748e79a698521a448d345020a4cfd28026a4340c29934493c2f13e76b820e0e29827677393f22d2c3c492c72c6de5e23689c31054e719f7ed9a48e1962baddb72d6f7e093b79d8ffd3ad6973d453d2efaa36608e4b1d5a3b58bcd36d2891ce790add6a86a1a6bdc5edd8fb3b3a878e54c8383d9aa53d6e654eaa52e6f22e5e688aba6b59d9158e279034a1d8b7cbc6719ce28b386e74a95a31135cdb9da1650143fa738eb45eeac67ed54e2e2cb7a969c350f28190a6c273c75041047eb5141a2c30bc72348eec8aa060e0657a1c52be962638871872225b5b010dedd5c390fe711b463a01da9f7d6cd3db8116165460c8c4640228bea43ab79a914c69b70b09844aa56497cc90edc614fde51f5ad48e3486258e350a8830aa3b0a4d8aa4d3d8a90d8b2babcd33bba39656ce383d8ff009ed53b5ba170f8e46463b1cd1713a9ae844d65149731b3c64f9443a36e3d7a55ccf1413395c33c5252246d14084a5fc28024a2818945001819ce06477c52e681f330cfbd25020cd2500078a43e9400668a0028340052d0014500141a006d25020ef462803fffd900ffe131e4687474703a2f2f6e732e61646f62652e636f6d2f7861702f312e302f003c3f787061636b657420626567696e3d27efbbbf272069643d2757354d304d7043656869487a7265537a4e54637a6b633964273f3e0d0a3c783a786d706d65746120786d6c6e733a783d2261646f62653a6e733a6d6574612f223e3c7264663a52444620786d6c6e733a7264663d22687474703a2f2f7777772e77332e6f72672f313939392f30322f32322d7264662d73796e7461782d6e7323223e3c7264663a4465736372697074696f6e207264663a61626f75743d22757569643a66616635626464352d626133642d313164612d616433312d6433336437353138326631622220786d6c6e733a786d703d22687474703a2f2f6e732e61646f62652e636f6d2f7861702f312e302f223e3c786d703a43726561746f72546f6f6c3e57696e646f77732050686f746f20456469746f722031302e302e31303031312e303c2f786d703a43726561746f72546f6f6c3e3c786d703a437265617465446174653e323032352d30352d32345431353a33373a35312e3830333c2f786d703a437265617465446174653e3c2f7264663a4465736372697074696f6e3e3c2f7264663a5244463e3c2f783a786d706d6574613e0d0a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020200a202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020202020203c3f787061636b657420656e643d2777273f3effdb0043000302020302020303030304030304050805050404050a070706080c0a0c0c0b0a0b0b0d0e12100d0e110e0b0b1016101113141515150c0f171816141812141514ffdb00430103040405040509050509140d0b0d1414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414141414ffc0001108029d021103012200021101031101ffc4001f0000010501010101010100000000000000000102030405060708090a0bffc400b5100002010303020403050504040000017d01020300041105122131410613516107227114328191a1082342b1c11552d1f02433627282090a161718191a25262728292a3435363738393a434445464748494a535455565758595a636465666768696a737475767778797a838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae1e2e3e4e5e6e7e8e9eaf1f2f3f4f5f6f7f8f9faffc4001f0100030101010101010101010000000000000102030405060708090a0bffc400b51100020102040403040705040400010277000102031104052131061241510761711322328108144291a1b1c109233352f0156272d10a162434e125f11718191a262728292a35363738393a434445464748494a535455565758595a636465666768696a737475767778797a82838485868788898a92939495969798999aa2a3a4a5a6a7a8a9aab2b3b4b5b6b7b8b9bac2c3c4c5c6c7c8c9cad2d3d4d5d6d7d8d9dae2e3e4e5e6e7e8e9eaf2f3f4f5f6f7f8f9faffda000c03010002110311003f00fb4301bb7349d0e2856e683d735f6c7c90374a69f6a52dc5229a402707a9a5dbe946da6e18b6074a6805dc3f1a5e7d283f2d1bb354021245341cf34f6a674a006824353b77cd8a685cf34bfc59a00752370d8a39fa5364cf51400fdd49b8d0a7762976ede73400522d2f03ad22fdeeb4009b793494f381c839a601cd003f39f6a4a290b6deb400134828fbdd052e28012939229d8dd4cf51fce801d9e38a06181069554e7180091c0cd3550b7dd071d37638a0036a9c0f4a38fc291d920dc65711e02e77719ce381434d0a31569e3438ce77023f1f4a766559bd90e0a36f5e4d342e38a700197e574933d0c4c181a72fcd95568d8804e378cd1663e4975408286603bd3bc99393b0edf5ec3de98d19d80e0f3d0f634ecc871b740ddef49cf2d4898662b8248ea0751f8520203153c1eb907b5487c833c134abd2902ed1f360fa629db42edcf04d003240376338a4e9ce3f1a7300d9ddc9f6a15b6f18e2818f5653818e4d34a8563eb411b88c7068e8093d68102f4e78a5a6724629fd00a005e29bbbdb8a3760d27f1734009ea7b534e5f8650e3fda1c52ff0017b53b269c42e3989987cc493dfde9aad8f989e4526edb4d652cbed556dc05da0018e08ef49f749c5397e55c13cd33eb47401de616e0f4a053474cf6a51400ea6e4678a4dc4d1b466801db8e7149b8e69768dd48ca1791408053a9a29339340c730dd4c6eb4ea63fcd408383c6698d186e093f850bf2f4a5c6ea091ca4280053bccf5a8f691d68c734012f9828a8b6d1401a0cb8c629540349bb775a5db5058d2b8e05038a293752b00b4e0db4533752f1dfbd300670dd38a0535885e0f5a457cf1400bbb7521a5dbb78a46a00169067d38a03114acdf2e6801e39a46e178eb4dc7d68c7a50011f5e68918f414abf2d21f5a004fbc39a45fbd4b9a4e873400bb41a5e074a14e68f5a0015b8a51990734d5fbb4aa7ad00014ad2b1e29809e99a71f43400c3f74b7a0c9aced535e874fba36a885ee00195f73e9dbf3c5688c2e00e9d6b3b54f0fd9eb6acb711c85db932c590c31d33cf4a68b85b9b529c775e24bdc3c70a5bc0ac4339c1623d80cff003ab17fa6ea13db388ee2386665033b4e03070738cfa03591fd8fac68c219f4fb8f3d232ccc83201ec0628b4f1f18ef96cf57b56d3e546da58a9c1e09ddfa1fceb4b23be5156f707dcf85f5ad43eccf36a8acfe648ef3c2870a011b4609aa1ff0acefee22314de229ae1305411160f527d7debadb1d6ac35440d6d70b20e154b1db927aedc8e6afc919f2cee51201d1b006e34b9da146b54a7f64f3bb7f85b7367711cb17882e87943fd5a2e33faf5a9ffe102d557e78f5f70a4ee28c9c91e879aee4a151c0c6300fca3bfe146e1d42e48f6c51ed197f5b9f589c347e0fd7956296db556db9e60c90a7df92691b48f15471c98bc8ee7ca05d123f93078f979aee94eec641e391c9c52b13d7be6ab9fc8878a7d8e1eda1f1b8522e618f731cab161b80a9a1d5bc571c6d23c4ad129da88f839fad75e1086c966249e7269fbb6e40f947a628e65d887888f589c749e30d5ad76bdde99379aac09558b2a57d063b52afc46cc4eb2587ef5dc85550dbb1f4c5760df770a59727f8588feb4a30c7955dc0e4315c9e7dcd2d3a223db527bc4e6a3f1e58c88898daedc2b13d7d7156edfc51a6ccb21136d8946407e1bf2ab7368f60de614b48959bfbaa2aaffc227a491186b38c280415e79cd2d07fb97a9663d52c1ee4c4b72bbf19f98e07e757c2ab6c659164127dd08c093584fe0cd3248e5061750dd064e71517fc2150dbdc46d697f35aa85fba49228b2138d27b33a22bb58a93d3a32e083f8d0db97ef2f02b998fc2777668cb6f7f23203bc296efed522daf88a2912482f23310e1d1d7247eb4ac88f671ee6ffccc7e55c83e946d2d824f1dab15975955f2d4898c9920a8e47ebd2921d435868407b2569547cdf375391c5223d9a36be63c638a3bf1cd66cdabdec6bc6932b9ce08465c93f8e2aac3e318773a5c595c5abc7f79594b1fe5544fb3377834878c563378ab4f8d89964648cf2aeca40c7bfa55cb6d62c6e795b85653c865395fce993c85c6524647340276f3447342ebba3963957bed61c7d69cc46ed8197775014e47e748355d08fdb3c52d2b4606d6e3938eb42afca33f2f1d3ad2109d39a28eb81df2050bcafb8eb40080fbd296cf7a677a3bd003b70e05291b6a3fe2cd4a4eeefcd0026ea6335233534366810b4adf28cd2529e70281584560dde9ca0527dded481891401263de8a6668a065f1c53e999e3341936ae715030a6d22cb9ed8a76da004a775c526df7a37668011b9a4f7a7114d34003534e69c6936f7a0000cd2ed3dc714cfa1a76efc6810ea5e05465fa6695811c75a0639bd4521144603e73da9d80bd2810c6f945233155c8eb4375f5a5fba704500314fcc0d49fc42998e7da9f9e86818927dec53395a9186ee699b6801fd85373b9bda8563d3b5235002f1bb8a632e7ea0e4726957347f174a04391b1bb24f273d6a2bab382f1d5a68c4ac063e61d78c549b690a95ef4eecb8c9c5dd3306f7c03a45d063023da5c1fbacaec429fa1e055587c3fade8b22ae9fa88bbb27287c89cfdd18c3107f2e3deba62a4823737e74e5c29ce33d3afb55731a7b67d4e521f136b5a6a8fb769523654b0651b83124e3e82b4acfc65657047da43580214095be642c7a8adaf31963080e0741df1ce6ab5c69b677ea62b8b68a44c3f51b71b87b517345529c95992db5f5b5f4598260cac78dc40c7f9c1a943775c104e315cecbe04b69238c417535b3e140db9c1dbd327d793517f64ebd6ad2bc17ca519731c7211b811818fc69d85c9096cce99980e08c1f4a616cb720e2b995d6f5fb1b6617ba72cee8c09d9d4ae706a7b4f1a5b5d4c903c2619dddff0076dd954673934b5ee66e9b8bd0df5704fb52b75e3a1aa10eb3a7dd2c612e957cedbb49031c8ce0e0fb55e819668d0c3993b71f5a2c2b31dc8e714990ca78a7b7cb805583648208e98a68ddbba0c74ce68b11adc76e0d8ef8f7a6f7a5fe1240fc4534b6319e8680bd8186eee41ec73d297ebdfa9f5a686cb6294d01761260a8193c74c1a7a145076fc99e78ed4c53ebcd2a8ebc501763f79dd9072739e403fd2a366ea4aa9cf5c8a55650dc75a46f9bad03bb2092c6d9f71fb3c797fbdf2e73f9d45fd9163f2e6da3007f081807f015707a526d1de826e65ffc22fa532b816db0b36e0559860fe751af87d63b89654ba9919c6dce7240f6ad9e9d291b1f4fa50526635be8b7713068b509498f211186771efbbd6ac2aea48d333491b2673183c7e957b9dc0fa77c9a7290148340ee65cda96a36f6a929d3d6575705fcb6c123daa55d591a473f679c4606770191f4abebfbb5c83cf638a6ab056214054fee8e9410ca1fdad06d0e4f96a4ff0011e40a95752b4375e47da6356c81c9eb9a9cc319c828a41ea0815135940c173126e5e8db466801eb347248c81b0e0fe7ef5285db824e3fdee2a93e930b5c79e030936e386e29cd66f1dd473c73b03d194f22802cafccd9dbc67039eb436368380a7aedcf355fcabb6918fdaf0b9c82cb90bf9532d16ea1b565b8916e5f276c91aedc8a00b3e94ab55d6e6445669eddb81d53bd3e1b8f3304ab2823232280266a4a8fed51e0e4e1bfbbde9fbb6e38c06e86800cfb514bbdbfbcb45005dddda9caddaa3ef4f5f5a80168a29375000dd29a3e5e69776690b2af2dd2801c4d34f346e1d474a50dc5000291b2697cce78a4663cd000abf2e7a526df7a546f979a4dc3b5048defcd3875a4c7345031eb1ed048ef46698bd7ad3e818d652d9c53155a3182770f7a5673da9c3e6ebc8a0069ebf2f34ecf1e94c5f9785a539ddcf3400f04fa518a4cd1934003fddcd355832934edf81834c5e0d002ad3f82299f769e3a500274a47eb4e6a4cd00330692a4cd376f7a006b03c714a169727ad21a007f057073c74e4d32463d4e5b3d738cd14bbcf4a077632363f743613ba9a592d2091409638a45ce70c83ad2e370e949e5f3cd01ccccab8f09e913199a3b4585e45d85a2c8c0cf41f95547f08986154b7d4ee2029f7016e00cd74181e99a00c55a65fb491cfdad86b96d2616f05ca9258f1f7cf3c1c9e99a967f106a5a7aa0bdd270c00dcd6e43abfa915baadce49e7d683b7e4240250b15f419ed415cc9ee6143e34b19a332c893db029b846eb8c72075fa915a50ea7673adbecba88b487680ee01247a0ee7dbeb53cb04171feb61497d980c5675c78574db932996d51a365c6d071b4ff797d0fbd2168cd52a371c1391c10473f88a68cf707f2ae6c784e4b1646b4d52e90ff089589007d7bd396e35fb352d26cd432c5554f0303bd50b911d2edebc520f971cf35ce49e2d36b218efece7b5391fbc442547d6b42cfc4363a82a98ae13731c61be520fa629d8390d26e68cd1cb160a37608e57045341dc4f1d3f3fcaa496ac03ef50452f07a71f5a6b365476a09b0152bd693e94e196e0f34bb7140ac271b698abba9fb4d271d3bd032324038a0f4f4a7f979ed51eeed400abd2969a0e29dc6dce680157e9cd3598f237123d28539e691a80173961dbe94e750ed96ebdbda98beb4ad8614008df748cf07af34d518c63b518142f1c5020685376ec7cdeb4d92256c64e3b66a5c8ce0d26171d40fad032b7f650ff009eed4558e3fbe3f2a2802d12dbb81914f8d89c823142b01d68cf24e7e5a801fc5378a418348d196e9c5003a8651f5a6e19714e66f5e6800033c52300b49b8f6a5ddbb83400c5c13c52b734ae02a923eb4d56dca0d00397eee2936ae69320f5a063771400e2b8e050548a7090e694b739a006a8f5a5a432678a6b31068013ee939a7a83d7b5479f5e6a45a0036d04669325bee900d2e4fafd6801b9e4d14bb40e69372af38a00197e5a4ea29c5cb0f6a45f9738a0046a7f414d9182e293cc56e319a0076ee9ee718a6eeeb9ce7d31515ec8d0da4b2a4265745dc8839cb76e3bd79e6adf1b2d3c3f35bdb6ac22d2e69fe48e3b942a4c83a81cf4a6a2d9b53a32aba44f480c09c519ae2edfe28e9f365644f9b381e4b0653fe1f4ab3ff0b0ac1a6d8aac7e5dc49382bd38c5572346bf54acb4b1d57f0fb5183e95cfdbf8e34d6b7f39c3c7b87ca3239ff2714b0f8f34e6c0759101c0ce41e4f5a39593f55afd6274057033428accb7f1369b74885266576e3695ef4f8fc45a634065fb56e5e00c2f72718a39589e1eac7789a4b4374a823beb4919425d407716500be0e47ad3c5cc5f36668d154edcb1effd2972b33f6735d056e31499e718a7b32b0251c4898e59466a35caa9c8391cd1662d568d0ec527b9a780eebefdb8a68572aa76e430c8e29136633df34239dc4300569db4f7ebe9de8dbed8a41ea237cb9dbc52293c0fba324f1ef428f9b9a790074ab241f0f90769c8c1dc3ad655c786b4bb8b869becdb276182ea7f5ad4fc33f5a3713c74a2e5f33301bc21b6355b4bc960911b7292e7afbd35afbc476490c4d0437ce158ee471bc81db18ae8b8c522a856dcbf23faa819aad06a662d9f8a21ba52d35acd032a82eac3d738fe46b4adafacef1824777148ccbbc107a8f4fad4df678d9b7346a49eb9fa607e95953784ec1a4df6a86d4f07119e0e39a342ae99aeaa4638e581c01cf4ebcd0c718efea6b023d3b55d25b75add3dcc06469d2dcf27fda5cf6a6378aa6b5f31752d2e6b30a570cbf3285278c9c75a2c0e2afa1d16ec77a8cb61b8aaf6fa85add83e5caa1f272ac40c7a67f0ab21598162bf2632adebeb419b426e3cf34c6e39a70e7d8d270cbc8a44f2b1839a361ee69eabb7a505874a4026ec0a50734de3f1a4dc56818e2a290a0a5dc7bd29f4a00601b45205cf34b9cd28e28109b686e5718cd2d264fe141247b7d851526ea2802e95078a368fc297de835058c1d6a4ed4c039a5e82801771cf5a461de84193e94e65e2801a39a6eec1a72d214a001c865c5347cbc0e942afad2ed19f4a005f2f72f148b1ed1926973c11f853776d6c75140876714ee36d3411274a561c50026076a1be6a4e94adf2d03136d2b7dda2838dbd6801553b838a00db9ef46efca94b06518ef400d634cc7ad39bf4a028a006a8e6a40db734d6c2f348cc36d02065279269b85f4a02e31c9a1a8188d819e32bdfad51d5f48d37c410ac1ab69b6b7d09fb9e6c2acc3e848c8fc2af2e77e29fb40183549d8b84a50778bb1823c17a2796631a65bc23a2792bb76fa532fbc0ba75f2aeedf14e176878f1cfd78ae85b07a51ce30091f8d3e693ea7547195a3f68f3dd4be16dccf1986d357540c46e5b88b780073818c63b5652fc23d6ccd316d62c4a367c98fc96561f8e6bd5c286e0ff00334d6e5c9e87a8209e2ad5468ea8e675627922f80bc556388236b5b80a701cce54e09e474a87fe119f13adf4919b416f11603724e361c7719c73f8d7b18623396386ebcd4335aa5c462365de0364163b88fceabdb791d74f3796d289e372586af0c2cd308590c7bd479c3713bbbfe5556e75ed5a0f35e72de65c105f6329dd8e074271c57a6dd7c33d0efb0678e72571f76e1d4719ec081de98bf0cf408d7063b9997fe79c972c47e44d6aaac7a9e94732c335efc753cd23f135f69b3c8a61695080c417231f5c1ad3ff0085d9358ac4971679e0e0460b0e07738af40b7f00f87e1470b60d087eaa2526b46dbc3fa459c2f08b28d95863f78bbbf9d4b9a6f6339e3702f5703c5ae3e3b5d5b5ddcc0229e459a1df14641caeee983f9d41e15f8bdad6b1a8476fa769d792ccbb905b0c90defbbb57ad78bbc37a7dc7876f258acede39214dca4201d381cfd3f9562fc21f2a3d16e60d912482407cb0a32ca54739fae68e68b5b16a7859e1e55210d8e87c25ac6afa8dbc8356d2fec2d19c2b6fc96fad741bf9c1c67da9aadbb181c9e4f14adc0cf7f7ae7d1ec7c8d59294db5b0a31b86698ec55f1fad3982edff6a9aac718a464381c7519a690739ed4e662a314c5258e2980b4b8db480d2fdea04206e719a79c6dc0245478e68a062aaed2d838c907777a733092378db0caff007958673e9f9534f2314d140f9ac67ea1e1fb2bc59c797e434e30cf1f04f20fe7c0aa51e9baae9bf30bf5bb455ff56f957931d327e9ed5b8c7760900e2857f9baf3dbda8b8d4ae645bf8961373f64bc1f63fdd8905c367611edc7a9ad58d8b44ac98901e7e520d12dbc5711ec92359130c02b73c0e3a7e358571e118fcb3f61be9ac8c4bfb9466ca29cff002cd32f737581dccb824af7c63f0a455f5ac56d7ef748568f57b3676503fd2edc6e461ebf5ad4b3d42d751557b6b88e70c37615c6e51ee2821a266419a1b1b693775c1c01c648a463d8f5ef4891de94bbbbd357e650692801c0629699450263b148bde9cb405ea68244e3d28a28a00bf81da9a41a5e9834e6e98a82c8fa1a5278a461cf4a5ea2801569703bd0b430cd003194af4e949934f63c534f6a0419a5dbce69bbb6f34aac1ba500230c7e26908f9b343312db41e7b52ff0966e9d33834d0d790a38a4ddbb814923a5bc66495c2459fbec40fd2b9dd43c79a3d899a313b3cd1b6d2aa0fa13fd2ab96e6b1a7525a451d0f23181cfa53b6966e57f3ae21be225f5f24bfd95a4cd2aa27df9232bb8919e0e6a175f16ea86165582dc49c36643f20c771472a3a7ea73de6d1df152abb8300b8c9e9c7eb59f75ade9b6520492f2369588558d5b258935ccdaf80a5452daadebdc3118f2e176c107bf5ab3a6fc3bd2f4f9a62e5a6c67ca0e4e572a475fc4d1ca87ec6845fbd22d4df10b41b5c892fe3071bb1d88e839fa83542e3e2e6816e92307966921fbcb1267767d2b420f01f872d3caf2b4b87746a17e7191c56b47a5d9c6a822b3b740be912ff8555d22bfd923d1b39ab5f89da4dc27c905cb249f32bede2a193e2ce990b9cdadc14ea0a91fc8d75a34fb5552915b431a139c22015334312aed10c4076c20cd1cd1ec1cf85fe567156ff193c397b1feea693cdce3c9246fcfd056bc7e3bd0e69022dcc878e5b60c74fad6ab699652c9e74d676d34c3eec8f0a92bf4e31556ebc2da5dd4722b5840378c0da8073dfa51ee6e1cd8696c9a1d1ebd61711ab5bdf424b2a95c9f53cff3ad35c33614a919f976b06c8ce335caff00c2b5d196174b633d9c8d8c98e424023d0106a8a7802ff498561d3b59128e4e2e49c86241eb9e3a7a77a3962f6647b2a32d22f53b5c7cc7d7afa1fca9c4fad70771e3cd53c337d0daf89ac5e2865e23bd8d7cc4e3b961d2bb5d2ef20d72c92eaca55b8848cee53d6a5c7a99cf0f386ab625dc296936e18f7eb8ff000a07522a51cbea3b38a8d98eecd3db914ddbda9d805ceeeb4aaa437069aac15b613f353877a9015a36f5c546ecc9927eefae29ec7a669b249f29624041d73540270ca32df438a7718e4eef5aa96b7b6f79713431485de1e245618d87deadac7e5f4c1f520e68b9567bb1cbe5c8ad1c814c2eacaeac32082315e5ba2fda7c2fe38934f92302d18ed057b28624123fe058fc2bd39b2f19d836fd7a0ae2fe2a7855f52d3e3d6b4e4921beb15059e16c332e79fc01209f61554e57763d4c0d45193a12da6773261646f4ec41ea3b71436181c0dc00c9f6f7ae63e1d78a3fe12df0ddbcb393f6f85764db576862091b87b1c56d6a970f6ba54f342ad3caabb9117392476181d694972bb1c7568ba755d3ea59750a7032dc677629a9eb5e73f0bfe3047e3cb8bcb4bab516f3c570618995b25c8fe1e7b8c60d7a415d9f29e79233ebeb56e3622bd0a987b73f50c6eef8a17e539ce4521f5c6e1ed591a9789934bd5ad6ce7b66486738131e84f381fa52b76308de4ec8d8ce7af5a55a62af46cf5e8053b0075a9b02f3176fa527b5417d742c6d5ee5be5893196f4e79a8b4dd6acf58674b47cb464868cf5a68a845c9368b748b86eb423accbb90e47f2a64922dba1791b646bcb33740299166c7b05e94c65f948ced5e33f9d10cb15d461e095654600864391cd3b77cb8cfd7f3a5a024d6e733e2e678f50d3a484b27925964f2c9c364f00d6d691a91d52dc33616e4315618e320f26a9f89e1796d6dd049f2348c1b68e71d4ff215cb6b7ab3780fc496b25c4cb1e97701831652caa55721bea4ff003ad6cac7a74a9c650f33d01be7705b0dd8e7a1ac7bcf0cda7992cb6ac6ce56e774581cd69dbcd1dc5bc32c526f8e440d9ee0919c1a5560c78e6b3b1c6d4a2ecce7a4d6355d05a38350b55bf80f4b90e0151efc56be9baad96b4cc2d2652f1f1246c7053eb575983290caaca7aab2820d73baa786adb735e5b5d3584f9c9da386391d4532e31533a05caa8cfe14e0be841ae3d7c51aae830c52ea102ea5a6bb95fb641f33c63dc0eb5d0d8eb961ad2b3d9cdb8f5f2dbe5603df3d0f4fce95899d1940beebc5474aea63608490dd7041a31ef9a93987a81de978030299ba8ce4d02168a28a2c05ea5cd4793464d41649c8a5069ab9ee78a55ea680169091eb4add2a2cf3400a4e4505772f5c1a455dcdb7d4fe9de997d709a7d9b4f3b2a2a82c371c6e039e2a921a8b96c3d41240231fecf73515d5c43639f3d847b86555b835ca4df1123bebef2747b26bc91a1de26dc405cf183c53ad7c2779aa0b76d76ea3b82b92b1c2cd8ce7229dac74aa2d2bc8b171e3cb311cb0d8446faf36a958c03fc470093e83a9aab35e78b3592a2ce28f4b524c46693fd9e0b81e9c8fd6ba6b1d36db4f876c36f044480ade5a724027824d5a2d9c9cfcc7a9fa74fe668ba2956a71d123893f0ea5bc8cbeabaddcde4abfdd3843f402ba0d3bc2fa3e9f6e889651cb281cc92a9249c63d6b4bef364f207e54bf5a5cc4cb1127a21e8e5176a7eed71b4052463aff008d3bcd3d3712339f98e7b547bb8c76a5dbdfb527a9cce4fab1eaab90071ea475a46185383ce79a69e3045216a9245f971d28c0a455e68db8cd3017ee702909cd19ddcd2500283b4e697777a43c52350306f9bbe3e8714d62e7233c1fc6936f34746a008ee2dd6eadde0b8852eade4055a3906463e95e79a8780751f095c2ea1e17bc55b10acd369d2b9dabdfe5f4e9d3debd2777208e0fb5337bb3f5e3b8f5e0f5ab475d1c44e9ab4b54737e16f889a5788ace3f34bd95f6409ade752ac0e7195040c8ff000ae9ca95c1cab0c6edcbf535cdeb5f0ff4ad78b4ac8d6d7658379c8e473cf35856f77e25f02c98d4637d6749848437112ee942f241c67e63ec3d6a9a4f634953a75b5a6f53d0bb67d7a527506b0749f1e68dad46a62964494c82268644c3a31e40c67d013f856ea92c85e36595413ca9f4e39f4e6b3b35b1cb3a3523f1808d449bbbd3fad2286dc377007f176a4daecdb42b641c13b7af19a7ea608490654f3daa1b8b84b3d3669dc2aa46a49f37eee692f350874f506791232c0b2a33004815c76a3a85c78b164d3e197cbb76954991109c70783f98aa4ba9d34e9bbdd943fb526d0dc6ac2321256e4852449cf7e7b0aebfc3de36d2bc596bf6cb0be42bb7e78723723648c55eb6b54d3ed60b4f9278e350a4bae73dfa579f6bdf0374b96f2eaefc3b77368975310cd0c64f96cdd735ada2f43d38ba15bdc7a33d3258cf19e15ba027ad3971f2c6e77c6782a0f55ee2bccedf50f1b787615b3ba885e4318004ea724fe001ab96ff0014a6597cbbdd22ed767057ecae8ea7a0ce4743eb51c9caef121e0e74da7091cdea90ea3f0afc4905edb0f3745b97dcab82422e7ee9e781c9af5ed2f57b5d52c61beb098189b38553823d739e9ed5e71e24f1ce99e22d32eb4dd534c9a7d2a68d932b90d1b8ee0fd6bceac7e2549f0def259937c96932c6163119903e385207af6357ecf9b57b9eccb093c4534ee9491bde36f87ba7fc3fd61f5cd19a5b48a494ddcc246db12c87ef6c393c9f5f6af61f0feb567e24d22dafac6549a3963538539dac4723f1ae1ceb3a97c40d3a1b396c3ccb098ab5c42d10195231c64f0467b7a5662fc2fd7fc017105c786af66b8b149189b26619c0e83af5fc2aacad66635a97b5a6a151fbc8f5a18254eec03d89ebed583e32b04d434d8d047baea16f32319c11807a7bd727a5f8c3c67e73c13e8177661b959990305fcaafc3a7f88bc4574b3dda7d8a18db9790f328e7a7a5676e53cb8e1a745f349a1fa5f8d63d16f134dbf675567c2b4830cb900e3debb90c9342b2c3224f13746439ac8d53c27a7ebb6b1c5769e632821243f794e3d7ad70dff086f8c7c17137f615f9d4ed186f16f71f2bc67d3dea928cba84e9d1aafdd7667a7c90457b6f25bcc85e2914a98f19cf7fe95e7f6b6f2f85efa7fb24a2e9a3632b123682a7a0fad453fc40f14e956e0cda2cd34e000638e2c924fa1cd6af82975a9a6bab9d5ed1ed8c877ed9f696e790b81918c7bd2b72951a5ec22dc9dd1a5e1ef19e9fad468a8be43e70c0918dd9e7fad6aea535b4da7dc0de92c6518950c320007b572daefc2dd275e32cb0c92d8cae770784edc37aff003aa763f0cae20be8da4d7e69a0fe3409d70318a6ec2e5c334a570d2bc456fe1e0ac7cdfece7051328461b030335db5add437f6ab3dac89346ebb81570483c707f5a8a4d16d6e74b5d3e606584b6e5ec476fc2bcfe1d03c43f0f35079b4e94ea9a44858bdb12331927f5e3f9d2b221d3a35b48bd4ebfc4cceb6f1242c237972ac0e7b9e4fe9fad5b934fb5d5b4b16d756a935bc91b0da573d460fd2b9193c453f8b350b78ed2d24b475c8991b24035dcdba8550c060718e7a63ad4ec63252a1a2679dc8baa7c2950d099753d0d9cc8eaca5da3c900007d0576fa3f88b4ed6e3df05cc7b9988319382ade95a24830bc4ca1e37186561906b95d67e1de89a92974f3b4fb8ddb84b6cdb4ee3ebc1a7cd7d0bf694eb2b4f467532285e19957b8dc71fad739e24f11411c315a5acc25b898e4ae39da3ae0f71d3f3aa49f0fe789816d66e2511fdd00f5ff7bd6b634cf0bd8e8f279e881af197064233807ae33d3b7e542b2d4cf969d3774ce6a7bed57c176705e9b1377a5dc3349716ab8631267f8bbe6aee9c7c39e3a55bed325feced414e0b0e0ed1d9973eb8e6bb00546fde8b22c802bab8e0e0633f5e6b8bd63e1cdb5d5c25d69773369b791fddd8b857f663dc74aabdceba788a7356ea67eb1e3ed57c03abd859eb3a2dc5f69d74e234d46d54c81013805b1ef5e86cd1c837c2eaea3860a73b4fa570b77ae6bba0c6b69aad9c9796ddf6a6e4c7a64547e12d5a68f5a8e3b3b29bfb3254662841db11cf6cfd694ad622b6195b9d33bdc52ad2be1320020678f4c7b53738ed9aca3aa3c8f263a8a4cfb514c2c5b270714f514d65e7a669586d1c56650f2d8e94cdc69bcd2afb9c7d6801db8b71fad34a9dd81fde55e78ea71c7e74a50b2908a5dba818c67f1ae63fe1205f126b4f6ba75c8921861cba8c031499c1ddcf5aa4ae6b4e0e49b25d73c5d1da5c4fa75846d717c2416e491945620039fa64d66da784753d5a713eaba937d882aa2da1539c293c67dfe95d559e970d9b23045f3befc8f8077b962c5b3f5356e672723279eb93d695cdd54518d915ed6cadf4e5f2eced92d1724811aedc67b66a552071d7ea49c520f45f947a52f00ed3d7ad1739a537262f0bef41008eb8a4eb46d18c1e69102606383cd2336da5da17a0a7150704d00369cadd8f4a69eb4a141a0052334d23152291d315136030e7ad00394e291982f349d188ef48c3d79a00728f97ad22b734d0db69ace17140133525206f968a000f6a6b2866a7f6c53376d5c532436fa5342b2b64f4a0b134e3965c1a6313775a491c8c31f9c8e9bb9c7d2936d234648e38a2c526d6c626b9e09d13c499792d56d2f4fdcb9b71b5d7f11fd6b2a1f05eb7a4c9249a6eb26611ff00a8b69d7e56f50c47e1c9aec150edc1fcc75a936e49ce7903b9ad399ae875c31338ab3d4e21d3c6b67147b208ee42ef0f1f981483edea297ed1e27961821b985e391532ee8dc163c8391d80e2bb82c5483939c9c74e3347cd1aa2ee20a8c0a2e997f5a6beca39487c273ea0de76bb742e5f1848a3180063bf5ae8ed2ceded2358a087cb4000dabc600a9803d18fbd387cb9c54dcc275652dc66c656001ca0e452aa9591994ed2c7279a5550a73ce694fcd9a461cd62392391d00f90aff10da0e714c548374824855925187e3afa73f954c540e94cddb58631f4a3d0a55651d99143a7c011964821cfdd2bb723a7bfad52d6fc23a66b9a5c9613dac3145e5948e48a3c3c5c70571d79ad2621b19eb4bb7763ae7d41c1a6a4d335862aac6f2523cbb489f59f865abda68da8ca93e9132ee5bb11ee2e30492c49f971815ea30cd15c5bc12c2cad1b22b0da43020f7cfad64f89fc2f6fe2ad29ad275fdeaffab937636f39fc4715c5fc3af135e687a83f86f553e790fba0976e3009231f4ad1c7995d1e9cd2c652e78fc48f4d66de9839507a852466a36ceec7f0ff0074f4a749f231008f94e3029031acba6a78adbd9b1c3033938c8e7dea2c74ee78ce49e714e650dc919c537702c314097ba4a5bccc6ff9b1cfcd51b48edbb69181ec064fe14adf7a93a6714eec7ed1dacddc5dd12b05276bfd68ddf306ce7f1a8cc2ad2ef232d4e65db8f4a42d056c11c7af3ef4dc02003f301d011c5252ad205a6c0b1a42ae23458f7f5daa0526df418a5269377bd31b937b8cde791d28fbca47ad48d82b8a8f85e94122b6307031cf4cd371f3138c53c28eb47ad0035bee8c1e69aac4386e41c7a9a7fddeb4d620d3b8d0be732a81bb8f43cd2c51c708c46a10679038cd46ddaa418dbef45d94a524ad70396fa53c2fa52aedc63a50aa0367393488128a7ee3450058dc4e282c699b8f3c528f9aa063da91496ed9f6a37522e3a138a00cbf126a4d676d144e934715c4a9079c1010a58faf6e33cd727f0abe11d97c33d4f5fd522d4efb52bad76459a5fb58185c0e31f9f5efc576da969b6faad9882e10c9e5c8b2c7b8e47983eefe1cfe9589a66ad2682cf63aadb3c7187dab7ac7292118181e9c7f2ab8b3aa12b42c8e9936af1927b034375a8f28db9636f336e09653c60f23f4a7ed207ff5ea2d6309681470b9a3a8a6b73410283c52e3de98aa33cd48bb6810cf6a52d436037148680b85231a283400aa4d46e019067b5482a2917736698c79c6eceec5073f5a431865ce3269570a314c014739229dc30e45252fad0037683cd1c0a1a902e7ad0262ab669add714ec6da42db72719a64883029fd45431b6ee4a953ef52eea4509b69465baf14b9f6a3a8f4a5a8c02fa51b83703ad07eb4d55f9b3de985876d0dc1a6c8bb8753d69d9db48c73d39a62b08abb79ce697752514085dd4aa7351b1e79a5561da81d87b546cbb8eeef4e66f6a14f3405845a76ef5a0f7a66e19c503b595871c6ee993d73e95c0fc4af0de6fad7c476f3ba5cc482364031b88c91fd6bbd11866f7edcd6578ba389b419bcf4668f8560a7a1f5fc2b4a7f1247760ea3854496cf41fe1bd5e2d7746b79d4b0971b64571cee1d79ad3650bc83915c57c2d92393409e08998a4570e4e4e4ae4fddfaf7fc6bb223e4001271eb53356934462e0a15e490f18a4dabdbad3540a77635271b1bd5b14522f0734b4122838a18eea4a0d050521e946694d0031ba5376eea5c1dd4b41246c483e829de582334e61b874a6ee38a007a8dd4d6f9691188cd2331f4a00189a8f763ad3c6077a19431e9400ab91cf6a3a74a5ed421f518a006f20e6a45f5cf347ad301a00933ef45377514580b8abd874a7797b6986957eef5a82c76da8a5c85240c91d054aadc734de3278cd002265955812b91ce0f7a8afacadb52b736f79089edd815da4f4c82323df9a936f24e78f4a77f0e7033da98e3a3b9cbaf87753d1d8ff65cc92da2205d923e1c01d067bd3ec7c532dac7e56a96b24170bb98e067e50401f53922ba22a1b86f5cfe3eb4dbcb786fd76dcc6b2f5cb11ce0fbd346fcea4acc20bab6b8f2d12706460bf21186c919c63e80fe5533c4571856e71b4b0c6e07a62b9cb8f07c30ef974b95ed6568cc2599cb707b8cf43ef55ae34bf10e9fe525b5d79f02aa0f99b3b0af43cff9e68e54c7ecd4b66750df2eec306c123e5f6a4dc38e3f1ae4a6f16eb70ac7249a5b111c8cafe4f0ac077c7d3153a7c41b28d5a5bdb4b8b48fa92cb472f607426b5474fbb77439a5e3b9ac5b3f1968b7d1f9915ec610f42d907f2abd6fab69f787f757d0b007921a95998ba53ea5a3cd37af04edf7a7ecdca5a32244ecca686b7f988c0639f5a69333e592e827dde3ad2360f7a8f72aa96cfcaa39ee7a8a976e338e48c671cf5a6166c371146da0a9edc9ce3a1a5561d0f5f6cff8521f287dd1481b34addb0299fc2c7d2801cd42fad377679f6a15b703d867bd003bef714bb4af27a547f788c1eb4ec32f4a005ead93cd21217b52676fbd237cc78191400fdc36d27e349c63de95be45048c7b50019a338e69b92dd0528c8ce7a5002ee0c29bf77814bfc249a5dbe628c0e6988073494edbdbfa50df28f7f7e280e518c36f3d68ed9ef470ebf7862923900e8474a02cfa0f6ed42a8ce4d26e38dad80c064fad2edf5215707e6271d28b008fed4d5055b26977c4aa1a499230403cb0e9d33f9e2a096fada18e679255448880ff0038e09e9fd7f2a7a96a9cba227dc58e2b9cf88774134036c2e16da69807567e85430054fa6454d79e36b1b39268638dae658ce386caff002ae7a2d22e7c65a909efe274b42d9d84f18e78ab86f7677e1e9aa7514a5d0d8f877a6ad8f87c395092cd2b33b2b655bd08f7c63f2aea38c5450ac6228a141b6389762ae00000fa0a795dbd2a1bbb6ce5af53da546c4da77714eda554e7814052dd3ad3391b81a47331570cb90693752a7cab8cd23018a00031fa0a6efde714ac9f2f0d82685fddfde21bfdd14085028627346451c5002f55a6eda52c00a4dc0f140029c13c66a366ebc53b927838149e59dd92722801178146ef9a9c471eb4dda33cd0024918a178e29ec01a68502801bb8f6a5396eb46dc7bd1fa5301cbcf7a4e9c50bc1a33cd301db68a37514016b34bbb8c51b685c77ac8b101a4e7753b6834bb46df7a004e7a639a55c8e0f4a178348cc6801cd83de936fe34cdd8a50d407a8e50178c647a5347cbc0f5feb4ea6c9f2fbd002962df78eee4f61d0f6a8e55591577a2c817b1407fa53b93d0e298d9914a93d7d2834536b6286a5e19d335224bd9c49383f2ba2edc8f438acbff00857da5c133a0699233c9f2df853edc5747b7700324e29deb8e33e956a469ede491cccfe099ede1c596b12213f71642791efef502e95e26d3e4568efedee02b022351b73db9eb9aeb33d38e45212554040b9cf248c9a7cc3f6edf439efb47889703ecc8f25bca048aa3f76e083d3e9513df789a4b79c4564ad246e30c1f03919cfb818c5751e67cbb47007a74a438914af418fe1e29dd09545d51cb4de28d77cc62ba348db8a80ca30ab81c9f7a8e1f1b6a2d6693369b2679059622403e98cd75d26597924b6319a02ee8e48feeac9fc229e869ed29f5472f378c2eedf7efd32491a145775cedfbde9512f8e9d566dfa74cc5177b22a64a8fad751e5a728a016c0562e339c537c91f7582e3d851a0fda51ec7356ff11ad2e94347693615b0dbc6d207720639ed4c9fe245b25bbdc1b19440ade4b07e1cf203151debac68612ac1a28db3dca0cd471c30ae41891c15dbf3203fd28ba0e7c3bfb273727c48b26baf252d2658d4b6642b83b7a838ff00ebfa533fe16769d2a1992095adf00e41f994fb8c5754d6d1c8aaa628fca5e02ed18fa7d3fc051f66b787223b681430008118c1a2e87cd87fe5673507c48b09a112c70cd32329236af3f954371f12a08610a2c26499ff00d5bbaee5cfbe315d3ad9c0ad948228f8230a800c1a72db411ae04481739da541145d07361ff959caafc4a8c468e2c1a462858a8e0ae3db14abf132d620bf6ab3955a450ead9e99190318e4f15d4c9145231262889273f70539ad6dd936fd9a161c7541d87ff5cd1741cd87fe567267e2559798a3ecd72a36824b4254f345c7c438ec5a75b8b39f11a16063424b70481f9e2ba8686068c46f04722050a159738029d1c68100d8871c64a8ce3d28ba0e6c3ff2b391b8f8891c2a4c7657126e9fcb551113b54aee04ff002aacbf112e1a7610d88f94721c1c57751a242b811ae4f562064ff9cd34c71ac6cab122a91c80319a7788fda61edac19c3ffc2ca9d6cccd1e9f22dc671242f1310a338ce7b5127c40d6e46536fa1968cb9076866c803839e2bb851998cbce08c15dc719fce9cade5b33afde6209e4e3f2a39a3d87eda8ada271bff0966bb3346ad64d1bb0de23d980703a5413789bc456f3472b58b2c52a011954ce49ea315dbfcac54900b29e1a9fd7033c2f41e94b99760fac52b7c271eb2789a48d4ed5b7dcb8f3e5c061ff0001eff9d3a3d37c4f34a0cb739df19512b305c83fecd756ce790a07fc08671f9d18ef8e7df9a5ccbb11edd5f489c6ff00c223addeb235d6a50c6563283e5ce464605595f01bc9725aef51f3a16e5d51705981e0fd315d62b1d801ec7228ee09249f5cd1ccc4f12f628e9fa25969cccd04037f20b951cfe75757e55f9428fa0a6f1b8f14ec9e2973339e5394ba8ce5bad3b9a53d6978352663771c75c5251d38a7019a64b1b8a461e94e3c51bb6f340838dbc9c5202bdc714483284d34265450029fa5260d3d571c50c769a006afdee9c52950df4a46738a37656801b20f978348ac714bb7775a4236f4a0075376ee6a5539a3bd00211b69053c80d401b6801bbb6d07e6c52b2e691b22a80063393498f4a419342fdec5003a8a7514016b776a6d2b0e3229dcaa935916339a06775064e01a50c1a8014529e949f768dd400d228e0538afbd0a280115816148cdc9c8a71503069bbbaf1400b1b07e314d640878a5560bd3ad2ee07ad003031e94ad42fde3475c8a004a46e185236e53c0a72fcdc9eb4136b91ab31988fe1c54838a36855e299bbe6e94c62f98734be61f6a6739a51d68b80f3cf6151e403cd49f8d47fc58ea2985c704dc339e295707de82a1695703a714c5762eda1b0d4add2a1cfcd40b51fc2d34fcd43727148a39a062aa806a46fbbc544dc1a0376a0639547a52fdda6eec53bf86800a38ce691b0d4dc5031c406ef4dc727bd1f77a52aaf5a004db49d2949c5275a090cf39a71e948cb8a7a9c8a076f31178143350569aaa3bd000bd68e734e5017a1a4a0046cd2a52ee14a31da80233f7aa451f2e7bd47de9e1b8c500c41211d8534b16a72a8ef46d0bd282417eed19c014b9a063ad031030c8a1b939a19b03a814ddc4f4a06230a5c629aadb9b1de9d412206a1972383405c52fd28019c0eb4a17824535bad2ae05002ab67ad0587ad2328c7148abdcf1400e56ddf5a1fa527bad0cdc63bd000bcd1b029cd09413baa807f1e94533345005cec39a66d2ae4eecad3b9c74a03718359141fca9140ddd2834f502818d348d4ac290d0014a0d25140039c8a3a823da83cd0a6801be5e3a1a5da3a6ea52d81d05363e5b38a007918a677a7b5328000c7383d295982f34c23bd3971c64668106e0c2a3c6e6f4a95b19e9518eb9a09141f9b1484e5b1484e0e7bd3b68e0f7a00186da6a83ba9fd5b9a5385aa006236fbd33cc1d314bfc4681f7a980ecd3597bd3d946323ad30b71eb4009d4e685fbd488dcd2eddc6800650dcd26d0bc8a070693775a007632b9ef4dc9ef4b1b7182295fda801bbb6d38306e94832b46ea005e68f3081cd285a1802a6801a3079a5c5357e50697271c8e68290adcd0bf2d264fa529e2818bba938dbcd27dee0535bd3bd021467b52f3e94e03038a56228246903140f97de999f9bda9d9a005dbf352b29028e1bbd2fddef9a004ddc51ba9a4e29734009b734bc2f1474a1b0df5a0a06dac339e698b8cf14aabb5b9e94ad8cf028060c769f7a696e286f9a914734122839a4dc79a56e28e050033ef1e68a56c1e947e340077cd2fdee293f1a3215b1400dc6de9d681b88a7f1f5a5241191d6801b1f5a3a7534bdb34dfbd9a100bc7ad14ca2a80bca6a2e739a971c539b1b47159163371ed49e61ef4efa527d6801776ea08c52034a4e45001db3494aac54628dd4ec020349fc54a4ed19a62f3480735247eb4bc350140a043b39a60f9a94814ec8db403236fa669ab267181814f207af34cc60f5a648f634ca73522f14c061a93a0a6b61ba52751400e0031eb4489c01483e5a71e6980dfbb4e5e94d65a15b8a0071f5cd31ba714adf3520f96801bce3da9eac197228c8a4c0fa500373f3514fe3b53681d85da1b9269dc5329c3eee68101a4dbcf5a42db464d2a32b1a00706fe74331e9da90e17f3a69cb1f4a0055038a47ceee29b92bd39a72927ad00395ba0ef4c6cf34ee17914dfbd9a0055f5a4665419f7a406933bb834012f98bb7d699d79a58f938c714375c77a0052bf2d276a77f0d333c5003978a56e734ca76ec2f34002a86e693a53a36ca9a6fbd00251452336d193400a5bb52f1481871f4a52dba8188c76f2685ee694a6e18a6b26def4084661de9a1c38c53b39e0d21c2e001400c07e6a731e2831e0f1464fa50022e7752b7decd23352b74a0045903640eb4b8ef4a9ec306959b6f0393400a32dc76a6b615b14cf34eec7434f621973de9a01b4537f1a2981a0de946edc28619ef49b702b22c55a56a6fd697ad002018a551b8e2939ed46590fa5002e3e6c52679c535a4fde28c7278cd72fe28f889a67831a417f6f784afdd9638498dfa9c06fc3f3c5520eb63a96231c9e29abf97e15c078a7e38695e1bf0fa6af3e9f7d18b811f91018c2c9f3e41dc39c6303f3ae3743fda71bc5de20fb0685e1a9351905b19f87da51470727a1391fad6beca4d5d1ddf53acd5d2d0f71df82063f1a7938accd1358feddd3e3b936935939e1a19472a70335a0cded9ac5a6b738a51717662921b8ef48df780a68fbd91406f31bbd0843b2a0f349b7bf6a52cbcf209a4539524d5085fbd4857f5a236daa4b7273c0a7f98b20e841a09230bb38ef4538ae58d23295ce6829210d3b7719a8f39a5663c01d280e8389a4a4c8c71d68fe22319c607e741361690f2d8a56f948cae3fcf348d186c1ce3b91414308dad814993e949e6856c1c8fc29eac18139cd0305c67af34833ce7d69368f30633c73f74d39b9618ef40acc72ad2a7a52336d3b78ddee69c3e56c0e7a0e87be7fc290728922fcb8c7151afcad8a7c8c39c1e335180c5f390abdc934f61f4246c0e9cd355f6d2ae6455da41dd9c16e01c50d13ae7729538c956e0d16b8ad26b60ddba91ba50df2d20917bd16121ad308f1bb383c74a7e70471c9ec39acdf11ead71a0e8b77a85b69f3ea8d021736d6cc15dd40c9ebed5cf784fe30784bc61a2a5fd9ea31d961d6396dae885789ce721973ed54a17d4da9d1a9513e5573b16c052c194e3af27fc29a06e50c181fa579ca78c7c6dadf8fade0d0f4eb79fc2b0ca52f6f5c7083b6de6bd1efaee0d3e3b8b8bb95618e3f98c87853e98a728f62a74274f962f764899e074a731da7deb1afbc65a0e9ba3beab3eab6dfd9f1c82392649036c3ee0555bef891e16b5f0dde6bd16b105ee9b6681e492d9b737271c8ec6a79644ca8d4eb13a427e5a6a28db5c7784be30f847c756f28d0f564bbba8adbed2d6f8c395cf419ea697c1ff173c2fe38d6aef45d32fcff006a5a80d25bcc02b1cfa0cf3cf1f953e4914f0f3bec762460f1d29a58f4c53b71da0b00a7b0271c56678b26bcb3f0cdf5e69ea1aeed9448aac4057c1e4126a52e676338c79a4a28d20db72081f4ef48df2fd2bcd3e10fc6c87e2647269faa69eda1788e095d3ec32023ed08bfc719e8df857a64836f1c11d720638ff1aa7071dc75694a9bd50c66db49bb239e9437a51b415cf7a93114e318c73428cd22aedfad24721c3171cf6a007e691ba52862cbc8a0e71c5004601dd9a31939a1988a371e38a007ee348cdc525039a008f926a414a319a56a008d7ef669c4d0ab4b200169a0206505b3de9fb4ede6917ad3bb62980cc8a29760a2803417d691a4147069028cd64589b8374e68da453a8c81d7a5001f7b8ef49b413cf342b0ddc538fad3b084e99a49156ea0104a91cb1e36a2cc81d413d38fae294d0adb5b9e3f9f507fa0a16838b6bde47cf1f17bc337ba87c47d32fa4d4152c235082092038014aef2a33c93d79cf4aecbe13f83fc377d717be22d2edb6cd1caf6c970c853cc5ef918c75156be2e7c2f3e2d36be23b19a68b5bd253880484a48a0313f2f72738acef843ae6b1a7f84f5cd4b53b2923d3edc13158c916c9637c1c8c7739fcf757637cd05a9f610a8ea60af17a9eacc55642c1720f4a5dad2329da719c13e9ef5ccfc35f1b27c44f07dbeb51d94da789a5755b69976c8003b4123f035675af1f68fe19bed2ac6f1e796fafbe686de18f71ea4649cf4ae5b3bd8f969425ccf9b736e15dca4a9dcbcfcd523300caa782ca4ee0091c5014c7b9d995875561cfd41fa5656bfaa7fc22de1bbbd5a40d731db29936e4f23a6063dcd2eb61429ca72e58ee692aaee38032467e603dfd0e7f4a3cb65017b9cfdec2f03be324d7ce9a5fed25ab378a21b493ec9726e81411794519000481bc7d3d6ab6bbfb526b3e1dd4b0b6fa718d383014dc481f7ff00795d0e8c923d8fec7c4daf63e938cbcaaad12eee702a4793e6200dc57821464839c007f1af31f1afc5a834df02e9fa8e98aab7b7e11a378ce572480c47a8191cd7cefabfc6af887a725cdb5a6bb2c7b8349b4a292464e32685424f5b8e9e4f88a8b99e87db2c8ca24c61fc9711c9b08215bd38a899b6c815b1bba6dce777b8af87fe0ffc62f12f857e21e9e6e35596e74bbc9162bf599b72fce30580f5c915f72c6a61645dc00c2a2b31032d8f97f3acea41c59c58bc0d4c3b4a4676ada9d8683009efef61b389ba34ce1477c01ea738e2a7122dc5ac5711e64824fb928e84773c67dbf3af923f6b6b6bcd27c7b64e6e0886e631225ac92110c6eb9dc0f071938c60679f7ad6fd91fe2b2dbde6a5e14d635687174a2f34f17b2147059b0f0a13d40f97ae3a5692a5ee73237797fee94cfa9e28dd94860b80719cfb819fd6bc6be37fc768fc0aaba56991996ee493f7970c76f96a9cb1c7a741f535eb1aab791a3ddf93a9456d74f039b4914e58301e9dc66bf3fbc7c356d4bcc96fa0335c49279f2dc29dde630192707ae79c81da9d185f736cbf06ab4f9eaad23d0faf7e047c648fe29e9f79677eab6fafdacef9877f33478cef518e98c7eb5ea0b223fcebf3af2415efe8315f9dff000cbc6dab69f7125f59cf1dbeb5a5913c7b53975273b0e31e8062befdf0cf883fe126f0ce8dac2c3e4497b6eb2bc7c0f2df1f329c7f9e6a6b4391dd0b32c1470ed54a7b32b78cbc6363e07d0e7d475168cac6aa523ce1a524e084f5238e2bccec7f6aaf08cd6b24f776f716f228da9185ddbdb278ed839addfda33c3d0f883e16ea93ae3ed3a794963da79452d96fa74fe75f1c68fa38d7e0367f664fb4dabef88c727ccf925b18eff5f6c56d46119c6ecebcb32ea58c836f73e95d3ff6b6d3ee7c4da7d94fa4b45637328b4f303e595dce11be99c7e75ef7f3ed50c0176032c381f87e55f005af87ee34ebb325de93234f6b2099768c167ce401edc715f697c3bf895a6f8fbc32ba989adec6e53f76f6f39d92236002b827d8d2ab4d2f84accf2a587a6a7495cd9f156a92e8fe18d4aee1b769e4823dc06339c7a578ef81ff0069c9b59f1259e9daada4305a4cde5099148dad9e18f3c74fd6bbcf8b9f1334bf87fa1dab5c192e5f50568e2581772ed3c1279e78feb5f3758f82746f13e9b7779a1ea804b74ad35b8881ddd4961fa7e1f8d552a7cd4df32d4d32bcb7eb34dba8ac7d98506e571b823f2bbb9e3b735c8fc52f18dcf80bc1b7bacc1009dadd954a94dc46e3b738fa915e55f067e25eb763ac59e85e20d493540e7ecca41dacac307273d401c7e35ee9e26d053c4da0dfe94e142dd46e034a70aae0820fe601ae671f6728a7b33cac4e07ea75d2a9f09f3c68ff001afc67e32d22e2286786c2e638dbcc8cdb72067eee73ee0fe06aefc09f8b1acdbf8aa2f0cf886fa7d53fb4949b79b663c99037ccbf31e463a56c7823f6699f45b8bbb8d675a696f190edfb302012c18039e7919ebedd2bcf3e257c3bf13f866dee27b82d1c36ade4c57b1e30aac31b8301f7b8ed8aecfddc9b513e9e9d2cbf150f670f88fadae21685986d6e39c63a0eb93dc7e550be17b861ed5f3cfc07f1f78897c59a7f876ffc4a9ad69371014816e17f788d195fe3ea7a9eb5f44b2fcdf776e3b0e9f85714a2e2eccf8dc760ea60eaf24995750927b6d27509a088dc4cb6ee63840cef6c1c0c7d48af9297c2f75e385bdb9b5d3ad749f12457be54cab8459c2be4f1d338c73ed5f6146c17a9299fe207a578278cb5cb9f00fc44bf8adb4d8aeed24d970d8407680bf3673dc819ada8bd2c8f5327934e71dd9a9717de24f873e24f20951a5476eb706212a60703767d7935dc7c42d387883c0f35ddac0fb3c9f3963dc41c601cb1efc67b573ff15ae2cc6936576f6a97134f66fb91501d90940d82477c95ad0f84fe2ab7f889f0d847036d783fd0ae158f2876e1770fa714df73bf1508fbb5a4b589e5df0dfc05a3788bc2fe27d3974eb81b4b5efdabccdc0ca464051e98078ad2fd9c7c3fa558de6af650c493dbea918964b59a3cc6197a8c1ef9238ac9f07f89350f84ff00139bc3379a7dc2dbdddc79036c659103fdd62dd318fe75d85a780fc5de0df895a6dfe9d04175e1f9a76fb4aacbb5e10dbb771ec718ab72566ee7554ad4ecdab6a54f85965e13d27e226a9143128d4cb35bb2496c024583c8071d4e0e0562f8b3c3c9e15f8f3e1bd574786cc4f2bedb88d82c5ba3738e0fb75fa8aef7c4be10f14e9be3392fbc2eba7496d77b5af3ed436b92a0e369cf53d09ef5e7bf1b55a2f1c5ac93dbc0d3a4514a60566d9191824648e831eb4e2dc99953942bd4e4f23e91daa268c310c00c75078c915e0fe2ff001778cf45f126bd66229af7499360850c2186d2483c1f4f5af68f0fde0bdd26c2f11959a4895f6c6723a71cf4f5af2bf8f16b73e19b9b5f165ae99717c225559d6294e368c9c6d1d6b0859499e1e0631a7886a5d0a3f173e1cbc5e0bd0b56d0ef64d1fc47a6ed96111824c8c472a4f18c826bb8f841e3e9be24780edf55b9b56b4bc89dede789f82b221c371e87ad5bf0bf89f4ff00897e018f518dc496d302a77fde42382303d39af3bfd9f645d17c5de30f0cbdd79a4ceb776f805815390d8fc71556e78b36acbdb426fb1ed4b96c52f6fc6957e65e3ebe949bb1c0ae6e963e700939cd251b893cd25021f9ef49ba83d29a28017686eb4bb42d34363b529607a50031bef669cb8ef41507ad21c01ed40d0fc63e941a6282dd0ee140cab74c629a18a580eb5148c48e29d236fa4c8a6488bc1e7a53b70a69f4a6b36d5ce68024dd4543e68f5a28034d78eb46e14aea36d336d668b241cd1c743d29178a6b824714c03ee9e29fc95a6fde18c7348c7231de98876ec7d290481a4c039f6a03055c6323ad11c2ace187cb9e2805d8492dccd6d3dbacdf662f1b289401f212386c91d8e2bcebc0779e1fb7f02dcc726b171acdb895a2d47527041494121b78c71d5706bb8d5af0e97a7cf73fb93228db1fda66f2a36620e14b60e33c8ce3b8af8dec7e2159dddd6a7e124b5bad274b79e4bc6b5b393314170c72d993396fba4f39e4815bd38f3687d065741d6528a67d15f093c49a0af8835ff0e68dabbea5f67315ca1b86552dbf24a443b85c7fe3d57be22fc19d27e226b9a6eb173797563a858e040f6d26c5e0e71d3f035f32fc0cd0f53b7f8b1693e98e358b4b793f7da894dbe50704ed7feef00fe42becd8ee96f361585e358c8550d900e3bafae73d6954f765a118fa4f0f35664caa16348be5db1ff0aaed07239a6de59c1aa69b75637087c8b88cc6c8b9cf3ce78f702a52001839c8e8734d56f2a4122921c8237671d7ff00d5585f5d0f263525cd74cfcdef881aa4ba7f8825b9850c73595d98de2490ac98566cf18f4c71f5af60f1b7c35f0ddaf86ed353b49350bb9f500b245777cca628a260acdc0ef9e391d0d51fdae7c0d7fa6f8f350d5cdb29b0d43cb9209a0000dc07cc0fa938fe7567e0469f79f137c333f8406a4b6890b89d5a53ba43175213be01ed5e9a93e5bb3f45c2629d48aa8de8b7399f06ea863d36f34eb885aeedede379a22ccdb618d3391f8fcbd3d05606bc7ceb78f50d2c3c969711f12c64ecc638539e84f35f60781fe00e85e15596392693529660f1c92dc1c7980afdcc63a64fe95f286a1770fc3ff1078afc3d796125b46d7f22dbacc49489410463d07a5546a29e88e8a38fa3886e9c0e1fc3774cbaf88a64d81c1555604fcdd01faf248fc2be94d37f69c92dfe1f8d1a5b7b89bc491c66cd6eb7065d89c248cd8fbfd3f5f4e7c27c49af69dad5b5bde4a163d4e46f2e4444dbb40fbb8c7723f91af5efd9bfc07e16f88969ab5af8934873a8426330cd1b955950e41e9dfa73d7ad6553952bbe863898d1941ca7ad8f16f17fc42d7bc45207d535392fefade7ca34aa43b1cf1820671c0a8fc1be0b9af1975496e846a1f05b782c1f19623b83902be8af8c7fb26e816ba2eb5ac7855ef74f9ed515e2b4f3f7228e0123773cfd6bc1ec6697c37a3e3528ad4d99b88b3f31f31581fbc0803af39a71946a2ba32c3558d58f325a23aaf87fae6b7e36f889a3e8515ecea893145b8476211467713cfa647e35f46de7ecc3e0cbebaf34cb75e5ac63c9b6572549030dc633ce3d6bbdf0ff00853c39a1d8c573a6d85ac2920dff00688506e7c819e40ae82d641e6c0500c3380a59b19e7802b9e75b5f74f9cc5662d556a9ab23f38eeb415f0df8c35cd3a2866d32e74f94ecb6b88cef6899c88f078cf404673debed4fd9f7548b55f85b60d1dc79d380c970db811e667939fc057877ed1133e9ff001924bb68d374b6d1c4272dbbe65e307f0e95ecff00b3af94be0b78921861920b936eed08c239dbbb763d7919ad2b7bd0573d0c545d6cbd54bea775e28d1535ef0eeaba74a5596e2d1c300386c29c0fd457c25aa7879ac15cd9b1b3d42dae0af9b923cb2bc60fb6703f1afbf9559a5311cb23f5da383c74ff003e95f217c68d26d340f1e6b4b965b6b997ce16ec7037301f778f61c7b9a9c3bd2c46435a30ab283ea6bfc665b9d3f5bd1eee357b17bdd32dee24897e6d8dd08fae307f1af35f3b53fb05d5e7d8ee5eee4758da68559a36901e73b47f74fe75e93e22d5adbc5ff00746d5159a7d6b4a97cbf2224632290386c70483819e6ba5fd99eeb504d7750b4be96331dc4292adbed014ca093b9476ea335d4e5cb1d4fac9d4f6787939eb63cebc0fe15b9f14784f5bd5e5d567b9fec3bb48058cc18b465baf047b56a7c21bdb2d77c7171e1d6bdfec937703b5ac90c215a3950e0aaf1d0f7fc6beaa9f47b5bab1d4ad95114de8692e1e250a257009078f7c7e75f15c9ad4fe13f8956daf69715ac9abe9723c71dbcd27cb20c9055bd33fd2b3a737522d23c9c0e3feb309421a1af35beaba4f89b5082fcc37f79a05d33078f11bb846e59587a9ea08e98afad7c2fe2cd37c61e19b0d56d350b79a3b94527c9607938dc3d7823a7bd7cd1aaf8bb4fd4351d4351bbd21623a847e74918941f2a42bf30071c82706bcd2d75d9f429da5d1aea64b95d8f0c51fc899cf2b81c513a5ed22bb9ae3b02f174e29bd51f7c485d1a41b597e63d0915c9fc52f0e8f167c3ed56c183190aef8c06c00403fd09aa5f06be214df123c1715f5e5b4b69a9c2c63ba8a4209dc3bfe35da4d62fa869b79661d5649a278d598e3048201ae28a74e67c1da585afcafa33e4cf83b35be89f11b4084e249543247091938e8483ebd2beb579191c17cfcc33cd7c47e04bc1e0af8b59d72e79b1bd10abc7ce1b9518cf5fbd5f6d46c27851c49e646c010dd33c7a56f88b5d347b19d4e353d949760550cc72723b8ce2bc2be3bdf691ac6a515de957897573701ad6736efe600ca08da40f6cd7b7de5bfdaad2ea042c2492264560707246063d6be21b5835ff83bf1516e754d10dc412dda24b0962382c02ba8e467079a28da3791c19549529ba8d9ea1f0abc7963a4ea775a36b9f6bbbb3f230f13c4d215c600f71c1fd057b8f81ef3c25249341e1958ad669009e58d460cb903271df0074ae85b4db192e1ae0d9dba49711b23931056391dc8ebdbf2af91bc451f89be0efc77d24ac77375a449388e358e22632ae71c30f734695ae7a35f114b18a6b667d733450dc6cf3a18e42acbb03a02c076e7af6a9f71000ce46d0a73839c7afbf34c2c64917745e59d8a0f39208ce41a77e95cbaad0f969736cd86ff009b93c67272c47bd78ffc6cd62c17546b0d5745fed2b6b8b673e642ecb2018c601cd7affd0e0f6e2b81f8a5f07b4df8990dbcde7cfa5eaf6ecbe5dd40dc15fe2523deb5a52b3d4f47015e346b29cf62ef81fc41e19d07c0fa3182e96cec16048d1669b730600e41c9aec1248ae6d081e55dd9cca7e560191d48c73ffd6af08d7bf65b825f07df58586bd7bf6d2cd342d70414dc3ef00062bd17e16f87b59f0af84edb4cd5a48ae6685411329392be98aba914b58b34c42a5ccea52975367c27e05d17c13a45d69da2da7d92d277791a157246f739e33eff00cabe6dd5758d0be05fc65b1bfd667bd94cce6d7cfdedf3e4e07b60122beaa3205ee41f6ac5f127833c3fe328618f5cd2e0d41a160f19910650839e0fd403534e7cb7b9cf87c53a7cd197535619566b7495410ae8369f51eb4ece69ec07451b500c2a8e8a318c0a6eddb58f53cfee2631d28faf029686f9a8245e69154e69e3eed26eeb40051c537771cd1bb774a004e052060add01fad35b8a07ad34341bb04ed1b7e94a1bd4d1b473ebed4cdd96c679a631be602c463149b392734e6217ad355949205021ddb8a4396fbd8228193c76a3cbfc6810bb57d28a3f0a2803455b34840fa50ab4c9e3f3100248c1cf159143a36dcb9a7e7151ab67b629cdf7698c5623af4a4ebd3ad2052d819a6a865ce78e6a8095632cc7240c024e4e38033c7e5ed581e11f1a693e34b0bab9d36f44a96cc12e06dc1878cf3ebf866b6b7c2de64570b988a1dc1ba37b71eb8af92fe12fc42ff847ee7c7be1ab1b68ed9e79e4bbb48413219fe60aea38e001dab7a74fda267a983c1ac55d267d2fe28bfd22e23b3b2d5374965262e5b69f91829caf39e7a0e2be50f8c1e27d37c3ff00122f5e1d1e194f96adb214f292372324b64770c38c7539cd45f14a6d5aeb4bf0f4ba7df4f63a5da8df1b33b05421b2ca0119ea31cfad64f8f20b5d2edee64d71ee0deeab6d1dd5b5c39cbcc0019058f703b57453828a3ebf0780faa46f73b9f85bf1b34cd69adf44d13c3b25a5e3485e4b5b44e6465e37313c91827ae6bea0d175cb7d5ec526446b5961c24f6bb49f2db0303dabe55fd92fc11a6e8ba7ea7f116e6ee47fb3dccf64769256d80c65b6fb8f7afa37c35f12fc37e28d71adf4a6737d3a6fdcc0059c00307d8e335cf53de7648f1f328cab3ba5b1d5c9b881bca963fdd1c63b520015b27b5341548f259b6eec233753f852b30ddcb8c1f6ae53e51eecf32fda5fc2b178abe0dea8bb1da5b23f6b82453828cbc927db19e2be5bfd9e3c7507c3ff00893a635d5b3c96b327d8dee870516466d871f5039fa57dd7aad8c3aae9775a7dd402eacaf236b79620db72ac083cfe46be32f12fecb9e29f0d78c16cb4ab47b9b467f32d6e61972be5eec9524e7919cfe02bb694972f2c8fa0cbeb28c1c64cfb621582158994b3234424f339208008cf3f4af8d3f6a8f0bdedafc52b99e22a6dee4473e5c6776f1838f4c715f606950b5ae8ba7c0ce6531dbc71b3b127715183f4ae33e2cfc24d3be2a69b13f9df60d62dd5841740643020fcac3b81dab0a72e59b39f095950c437d0f94fe05fecf77df17bc3da8ead2df49a55b2c820b4b9237c52ca8ccae7d7d07e7eb5f4c7c24f829ff0ae04e6ef50591e3558d5a303cb3ce724119c9e9d7d2b6be0ff00c368be177816db488ef0dd066333b29da03b1f9b0bdb26bb657d926ed99e083df3c77aa9556ee6f5f30a8f9a9c7667c8bf13bf68df1749a96b1e1eba316950a16865811558950721b7e07040f4af0ef124126a762ba8bdd20b598fce832c801538e9df38afa33e377ecd7e24f1478f2f357f0eb583e9f73fbd0d23f28c400430ee00ad9d07f63bd1ed7499a1d6f569ef1ee91311dbf091328eab8e9d4d6fcf08c558f570f8ca1468f2bdda3d5be0fdf34ff0a3c2c668e4122d8c71bacaa55b72aaa92548ef5d5dc430de5ab24885738daca3956cf04555d3ece3d0ed2dad6de592482188441a46258e0f539ab99c608e3d2b8fadcf95ace3ceda3e4afdab6ca4d1fc5f6179105681a2092c92740eb9efdb39c7e35e91fb2bcd7375e1bbad4640ab697539e410cad201861907a8e07e02bd96fec6cf56b636fa8d8dadfc47195b8855c7073dfde9ba7e9b65a443e4d8595bd9401cca22b78c2206239381dcd744aaf346c7a5fda1feccf0f62dab1dd946e39ef8039c0cd78a7ed09f04ef7e224316b1a30f2efec8ed96d47df954670c39f71eb5ecf188e3ddfbb1f3365b3de9e803f2c59d9790db8e7f3ac2353d9bb9e761ebcf0f35389f1a7c29f02f8efc47a8471416d2db58c6ed03dc5d64233a63202f070003dabd0fc0ff0004fc73e13f895697ff006cb78f4d86579a4911f76e465e063b7415f43b111c8aea76e5b2e138ce7ae69cbb554fa03903dbd2b49577256b1ecd6ce2b55a6e1cba305675b88887456c216c80173d481f5c7eb5f3dfc4ff00d98f51f1178b2e757f0ceaf61676b7f309e6b7ba4643137f17cc3a9f6c57d05bbe6393c7f3a661989ded90dd7e623e9deb38c9c568789471152849ca9b3cb63fd9d7496f0cc36315dcd657aa32b3c67cc049fbfbb7763f8573f67fb29e9662371a86b9712dd0662f25ac40215c8da31cff003af75651c37df71fc4dc9a6799b557605ddc03f28e4007fc6b4f693ee7a5fdad8a6ad739cf87bf0eec3e1de957367605e56b893cd9679382491e99ae9f795652493b7a0c9c7e94de7824e5bfbd8141fcea1c9bdcf26ad49549f34dea709a87c13f066a9e208b59bad2565bc8e73703748c50c87a1233ce2bb60a225555e115428fc29c73bb1da8fbdc114afcdab14ea4aa5b99ec2fb8e0fa8eb55b52d3ad7588d16fada1bb29f75e640ccb839ebd6a5326deb5229c8cd2d498c9ad858d8ae7d38ebce0d3648d2e36acf125c246c1d12550c14839c8f4a733ed51ce3348b2053d79a3540a4fa324505573ed48cdc71cd0ac594e48a50368ce38a09495c6e2a45f97ebdce6a3072c00e49e83d68dfbf200ff001fca95842f0ca40ee4f73de95f3dc0e7d3ad37695e7a566f88fc4361e13d1e6d575395a2b387ef3052714d5de88ab49e88d063818c67bd0beb8e7d6b2bc37e2bd2bc65a4a6a3a25d25e5bb121995b1b38e86b5d64e9f3641fd7de9b4e2ecc251947492d4466c7bd26edc691b6b526076a46485a28dde949ba818f0df2e69285fbb42e1bbf3400d65e29b8c629ec0e2984f14d0037b76a55195069a3a629dc6dc53018d9240e9f4a19406dc3ad39707a0e69ac40e6801adcf5a8f2aa7247e552820e79a36f7a005eaa368c7d6915cafd68673f852799b9723e9400ede68a6d1401a5f769adf329a1b9ef471eb590d888bf2d3b34c56edda9772d031cddb1da82dd7de981b76714bd57354864f139551c808082fbb38c6475c57c5dff084ea57bf12b55bb91edf4c82cf5430dbf95392cdc9663c0ced3f2f06becb291dc058265df0c836baf3c83f4e7b57cebf1af5cd5618b5eb5d3346b78f4bd2278d46a30e62952e1955b7e3ab0c328ef5d1464d3763e8f25a96aae24771f0ab5cf1935cdf36af23e8b70e6168b60296b20c82cc3b28e09fceb99f197c2dd76dc785ac35fb79bc436773bace0d434bfdf8b59307e627ba32f39ed8adff001b473e8fe031a5e9265923b989b50bb79b96ba98800a1238da41381c1e0d709e18fda8756f036a1656b1e8f6b2e91358a466dd170619532030ea01c120f4c8c7e3d3effd93eb2aca7ab89a1f0defaf3e0cebcbe1f7d56d62d37c59712d9b7c8efe4ca14aa311b48f9c903b73deb9cf0bfc26f1c45f15352b6ba5b8d334dd36f2279efa772914a990c9b587186391c7f74d7d1bf0b7c6fa5fc4af0cbfdbcb47a91321132c481a3933940a477fc3b57a75c4d6b78220f223c5396312b2850d8c0e571dbf9935cd2ab28b69a3e77178a74df2b5b92c131b982395d70cc73e5b73b3e87b83d41f7a636cda3715ce7a53d025baaaaa0451c614fa74a648b1bb64fe95cc7ca4dde4d8e03e538e07ad3bcc630eccf19f95476e3b53370076827141665200200e493f85166257d8731dcc72df3375cff151bb6f38fbbce73b4f4af3df89df186c3e1a6ab67a7dcd949753cf1acad205c80a7ae39eb52e9bf1dfc13a959cd76bae4702c20eeb6bb011fb1c0193c900d69ece4d5d1d8b0b5651535b1db990b1054e7b1c8353fdd60738c775eb5e2ff017e3ede7c5af1278aad6e2c96db4fb1656b7b855e42b13b4311d72013f81af60bcbfb3d3c79777709036d054b1c6fcfa0ebc512835a19ca8ce2ecd122b6d2ece8d1b3600cf43fa54ebc1008c01d81e955daea0b6863925ba8cc7b4b798e70187b561f88bc7da1784f4f1a8dfea76ab612a9fdf0cb156ec0d1cb26ad61fb1a92d3959b88de68190073c53e4c85f9464f435c659fc64f035fc6ac9e21b540a031f9b9cfa103a56b786fc7ba0f8be49adb49bf5b89fa9464209008048fcc53f6724b543787ab6d626c3655493c9eb8cd2e47cfcf2a3a0a6318c302c3a71d093f4fcf1f9d79a7c68f8e5a67c1bb8b3b4bad226d4ef6f544816171b530406dd8390791d47e35308b93b231a74e551f2c4f4e01c7514a80464b66bcefe197c6dd33e246a52d808174bbe58bcdf225c9dc3b1073cf51c7bd7a19c37527cb618ddd0eee4114384a2eccbab87a987d262c326d9194a6d75019cf51cd2cc5032e46031c6e155ae2e0dbdbbcdb6498c311291affcb523a2fb92702be61b7fda9bc55a97882ebc88ace1b6b6cc92594aa6370a0b02a493d46d3dbb8ad69d394d3b1b61b0b5312ed03ea4506450570eb9c6e5238a5dacbf3370075538c8fd6be70f893fb4dcf736ba7c9e1c816c44d6cc651229deae0025769fbc4f6f7aedbf671f89ba87c42f0bea916ad790df6a7a6dc98cc9120dd2445432b37bf51f850e8ca31f78deb65b5a941ca47abab6e058671dc629d1eddd820ab17d803704f1db3d686952240ec9b91482579fc7f1af9b3f6ad9356935dd222b3d4ef6c2dd2091d6381ca65b70c367f3ace9d3e7672e1f0ef1128c56e7d2aceaa9966dbce003d4d1cafa03f9d78cfecf7f1523d63c3b1e85e23d4e3fedbb7664b5fb51da5d46dc65bb9c9af648f7b93e64581db9c86fa11d453941c19588c24f0f3e59751e55b701f292d8daa5c02df4154ef353b3b1fb3ade5d456725c9610c770c119b1d723b57cc1fb494dae699e3681dafef74ab462bb278a4db1edddd6b83f15ddff6c5a5b3ea9ab5d9bb92d0b422490f273804127e87f3ae8545492699eb50ca1d5829dcfb8180dc413eff005cf4fce9e54aa9e33c715e69f05fe2268bab780f46b7bff105a4dabdbc5f679966942c8c570013cf27915e96dba3f2cfccca71cfd7f98ae794250934cf171142a61e6e3246478a3c57a5782fc3f2eb3ad4cd6d610902470a5b6d72b63f1f3c057da4b6a2bacc71aab00b0cdf2c8e0f4205749e38f0cc1e35f066b5a2dd45bc4f036d1fed0191fa8af917e1beb1a443e38d1746d77c311dc1b667804ea37f9fc7b77c81f956d4a11926d9e8e03074f149ca4f63e9af0cfc74f0ef8afc40349b20fe6c84ac121c15908eb8af4456e9f302bdd88c0af97b5aff0084734df8c8b158696ba54da748a624456401580cb75ea735f4c8bab786c63b999c476822de5d8fcbd327ad454846c9c4d330c0c684632a7b33cd3f686f1d7883c0fe12b79bc3b085bcb8902b4cca4888739e878e95a3f05be27cff0012bc3324fab88e1d7ad24f26e23418040030d8f7ae63e287c54d2f58f07cfa4d869f79a9cd74c112e618c48b1b31da4b907a609358fe01d52d3c0d75aa2e8625d42faf6dccc6d76b31578c7cc78e8a4a8233e86b7f66f92dd4da8e079b0f2fe63e80e0af0013f5e3f1af15f8e5e361ac32f83f4c9156e6497f7fe729c38c1d9b0fd4d4df087f68fb7f885e216d1351b45d3750dc56d9f2312b2e0b0e4fd6ba6bff00837a4df7c41b7f174579756f771463ccb4c031487070c47273c9e8476aca2bd9bd4f3e8c3eaf57f7bd8f13875cd77f65db8b396e5adb51d27506c1b1857eeb92338e38239fcebe9db1bd4d4b4cb4bd8b2a9711acaaa7b023a578cfed45a7cdab2e92b692406ecb332c72f3d319383d2bd3fc057cd7fe0dd2a49563493ca08447f77238a751a94548ebc7463568471091b5db9eb4edbf2e475a1b95a723638ae63e7460f98127ad1903a539d4533a5003d5a9aca467079a16867dbcf6a0015be5c375a42ca78a3cce98a4ceeed5403c00b48cb9e690e0505b2b8a0055c2f7a69c1a6b7cbd29d9e2802368fe6cd2ee602972334ec81400c563d0d2b6303151b13c90334b13b32e4a953ef400edc3dff2a29777b51401a240e72339a8f6851c548dc0a69f9ba73590c55031d2933bbb538631d79a68a0621e338e297f8314b805739a6eeed40c7c25964421b6638273818f7af9b3e3d69b716de36bdd2db559edbc3fac5b4570d0ac98d8546d6258e76f001cff00b38afa3f01b8201ee2be79fdad2de5b8d43c2f0db4af6b717704d1c9315caed19c86f6c66ba28bf7d23dac9e5cb8a4717f12bc57af693e22b5f03da5baea10595b45225e27c866468f0aee171c903af5af0ab85b3d03c4883c40f2c11e72e91659971db38c735f68f827c33a3eb9e09f0f69fa8cd6326b16d65120be8241bee1029f949ea7049ef5e71fb557c39f0a785fc1f6bf6495e2d667b95678e67c931856c96aec753964e363eb6ae223f0b5a9f3be87e3abff000df8ef486b1bbbad12da6bfb7bb83cd5e186fc03e846339ff7abeedb7f8a96f73f11357f0aeb168b6b7b04714ba46a0a018678dd1492c47dd39ec6be2a8a1b8f167c21682eb4f8f50b9f0f32c96f25b0733bc0589e005c9038efd715f54fecff00e0dd52e6d62f17eb0e6d24d4ace1852ce74dedb106159988eb8ebc0ac6aa5bb3c5c6469b8f33dcf63b65bb5cfdae5848c031c6a9f38cff0078e79a93ccfde11b0ed5e8d533166924cff1705971dba536404be485c1f6ae1dcf9496e3566f30f0314f9305554ae72dfcb9fe94d6902f18c52336720672411d7dbffaf549936d4f9d7f6c8b596f63f0ca476ca66613ed9b1d178da18f6fbdc57cc0d6b7735846f71035aa6023895065981c0f9b1ce4915f69fed41a1c5ab7c384b901dae2dee221f2ff000a67e6c0039e00af8f2f3c49368f653d93aadd58b5e2ef91ce5981001520fdde4fe18af46949f258fd0329e5a985e59743b0fd9afc743e16f8f6f6fefa129a2df2476b7d2b0c2c4573cfebd7dcfad6dfed05ae5ecdf122760af7d124514b6f3a48593cb7194c007078ea7b5793dfeb915c69ec232c9b661d58302b9c7cc31cf415634cf12ea3aa6ab1cd7acf69a5aaada894619626cf3f37403eef1f5abb26eed9df2c3d3535235e1f1f4f1c935a4ead34d9f291000c89c76e2b92d7bc477125f7d8658e41130e2064c2e720eee31db3f9d3b5cd4921d5cb22217591904f19ce483d491d78cf6a95b44b3bfdfa8df3caacae8caa198af964e4067e8a4e3d29b95b61cb96feea25d357640cf6a7c833333b32819c76cfd315dcfc20f165df84fe26786257b89fcabeb85b7e72d953ce7f315caf9c42cae2dfc8b62be6c713a03ce4f1bb8e081e95b3a1ccf7d7fa75ec4b24135b323431b104020f63dbff00af4a5792369535529495b73ef2f116a31e83a6ddead22068ede3776546c12d83b723ebfcabe00f887e36d57e227889efb59ba5bcb911bc28b1c6142424f7c77181c9afd039a1373e1c7578d2766b3662170c253e5938fae4d7e6e4da441a7dcdcdfbc8cb7b3cec1e120e11431c8cfe5dab1a36d59f29975251a924f74759e09b8d5747921d5e37688d9c88636073caf0093dff00faf5f6e7c3ff001743e37f09dbea08fe6dd2b18eeb9c2a4a31918ec30457c1335f5fcd1932ceb0db2aee685485de707000f5cd7d3bfb25f892d354d1fc416d113e74571f6a7b793862c540ce3d3814eb2bc53ea7ab9ad08d6a1ceb747bfae448912a9058839db92bf30e47bd7c51f1b3c2b237c5ed761d12c71751ab4d34aa7681b8124f1d72067eb5f6b5bcc5d6372db772eefa1da7ff00ad5f37fc76b1b2f08fc4ad3f5b8d9a24bab358fc92f812b9c8c93df96c7e358d0959b3c4c92a7262395f53c3efafac5743b7698cd7d219c2cc572c07002b6e2383eb8c5667823e2e6b1f0a7c5c6f344086291825cc41b16f22e4e0647a11dcf7eb5e8fadf84f4ad77e18f886612be9bab584e97503c6fb639a320fc873dc10727e95e556be1b4974a0f6be62dc46fe5dc5b819f9c65b7290705587eb5d6ddf43ed7154fdab74da3e98d07f6ced02ef4391ae348b9b6bd1666e5218c878e424e170d9e3904d784cdf103c47e3fd42e1352d51e4f3433411c8c3f7418e40dde83fad62cde1bbfd7b56d0746b5d25b4ebebeb8fb0c0f776ed1a01b4901d80c01dc715b377e15d4bc25e2c9f4bd46c17fb5acc7d9e4719f264048daca700118079a984631d4f2b0986a54eb2e47aa1be28f0eeb5a55dd9d8deb16d42641340613f38dc319cfa57d51fb32f8b2e3c5bf0bd04d3e2fb4d9a4b57f31b73000d790fc54bcd3f58b7f045fd9248751b7b36b4bc2ac3cdde08c64fe1c7b5735f007e263fc23f1deab0ea5230f0d6a4c5a51232e61703e6231d7e6c71ef5138b927a1a66543dbd269ee7d79e2cf01f877e2059c16fae588d41611f2b16200ae73c7df02f41f1d785acf468121d32e6d0aadbddac218aaf236b13f5aee348d72c35eb78a7d2e68ee6d5a2597cc858104302471dbff00af569adcccdb7041fe2da073ce4571734a0f53e1157c461df2b76b1f18fc43f84917c3dd41ac1248ef6692d832dc2858e404139248f715f43fecf5ae4dae7c2dd2fed5334f2dba794f248d96dd9391f901f9d7977ed4d7ef63e2fd1c32c5044d0346a70159c938cfbf26bb6fd98f568350f00dec71ab1105eb3f99819c10475efcad764ed28267d1e2651af80f68f73da2001661920f38dbfdef6af8eb5fd1ee3c17f1ee36587fe256b7a2754e1442ce718cf5c618d7d7be6058db037927b57ca7fb57473d9f8db4ebe2eff64456122c2718c63923d79acb0dade279995cf92a38773bcfda4ec6e65d4b4dd4f47b2df717568e77c6992cc982173ee067f0af42f85bae45f11be1a5ab5ddaf928f17933dbb82181030739ac0d2f4c83e2f7c1bd2d74eb96b0ba16e1adaee4ddb848a0aee619ce0938269df02fc0fe2cf06ff6baf88af2d4c7204548a07c8565cee627b93c554e56a6e3d51e862ab42545d293d6275de17f877a0783e0bcfecab1489ae4af99131257a81903b1af08f09ea13785be3969f21bf5305c3cb6d35ab70141276f23ae006183eb5ef9e20f889e1cf06cca9abea7059dcb28c412be198904803f235f20f8e51b4ff8d96dade9172ed6d35e445ad6f06d3b7209600f4e09fc39aaa316d7bdd4cb0152a5484d4fb687d8373e05f0cb6b29aa9d0aca3d404be70ba8e10af9e467238e87d2b5c8dbb989c81cfa67834c8e54bab582646f3229631227b29ed4febc63b119fc2b91bd4f9dab2f7ad2678ffed2b04d67a1e93a94167f68904c636917ef2ab002ba3f81eb22fc3fb55959e31b8e2193efae4fff005aa3f8fd79716bf0c2f5ad62f39c32bf4e07233938e381583fb32f893fe126f014b3986e202972d198ee23656001ec48e4649e6ba2c9d249ee7a7ed94b00e9b7d4f5ee1714d63b4d0c368ebd29ab863935cdb1e1f51e7b5336866a492511ae71c522c9bb9c5021db76f23ad0096ebd295583528c0a6806903d28560ad431069bb475a602c9eb40a146eeb463d28010fad34d0ce3a51bbda800dbde91813d3ad29c8fa52aed6ebd68018ceab8ce734eda19720fe748d186ed415e302801be5c9ea28a5c1f5345006948722851b7914aca1852564585286c514ddc2801ad9f3370fca9557279e280c0fd69775002b05c65b840465bd2bc0bf6be8c4da5e8b3b4b2c290c6e0c91c6cde492c3e6623b63231ef5ef9bb1f310081c907a5715f10758bad275af09dac72dac9a3ea575269fa8c172a1849bd498cf3df2056d4a4e32563bb03374ebc5a3e53f88db3e11f8abc1de2294b6a9a44f08bcb796dd8c519cee3b739c0e08edcd607c68f8a4bf11f5cd66f4406469a585ade490ffaa450320e4e149cfbe6baef8ed1b6a1afea3a75cef8344b29fecb650f961d20d8a147b007e95e33368fbed16f11404131876bfccb26dfe2fe58af41eaefd4fbe9d352f7ac7d27f006c6c3c2375e1ff15deaadc437b6e21b69639581691be5688c63e5e0e4e4fa57d4f22b2c8eaccc3f842eec818f4c715f37fecc7a645a7e8fa6dee9bac58ea7a7dee3ed3a65cbee6b3b80cd87407ee923391f4afa2e66f25a564197277608c649ea6b86a3bb3e473597ef1242927b609f5a8f6b6ee5b1f8549ce78f94e33cd3558b13ce71d7158d8f09e8c6e7e6fbbc7ad48c3e524019fae00e7d6a21feb38dc41f5a96358ee3f72cca7278041c67b6684b51475dce43e325fc1a7fc29d79ee446d03816fc8c92ce762e0f183920f1e95f135e59c3aa59da59e9f776e90cc4e431190fbfa313ce727f4ae9be33fc52d6fc55e20d76c358b99a2b2b1bb6860b38c8541b3237301d707045791e8f67a86ade22d3ed74e8a16fb54c162f31fcb56727839c7a9fd6bd2a74fd9c7567dce591f614352fcba7dd682d325d08e330c8d0bf20b2b7afb8e7d2becbf03fc13f057883e069d1d648aeac3537375fda483eeccc06587d0e462be52f1c784754f07ebf368bad26cd6add9651701b7472230278240f9948c1fad7b0fecf3f12756d73c1ede01b516b69aac7079f633bf237066dc4815135a68746279a54d4e9b3add33f637d25c14d5b5a9a78dc10a6de10accabd5b20f1dab4b45fd913c3d613c2d73aadf5f5bc327986ddc0c381d1581fbd8fa57b46971dc43a6db477ad1b5dac6a2668cfcacf8e4818e324d4cb0876438e5323a0ae4e77b1f2ef31c45ed73c43e207ecc7a46a5a26ad3787e29e2d61edf75b59c9208e27619ef8f4cd7cd5a1c379a7da4505d24b69756ac629a0c647070483fc42bf413f7b1f98b191bbcb21170339c76afcfff0088b792daf8c358b3956e2d0c174e0a15192b8cee2a7eef27e95d345f3e8d9efe518f7566e3519f73f80ee2d750f0ae97369f2bed9221b5588eb801b1ce71ed5f0cfc6af0f5e693f1035a69a3fb234922b46b202abc939c0e9c802b9e6f1d6afa558c56d6324ab3dba936be5dc6dc16ef8079ed5f75fc3ebbd37e237c39d125d7acadb55be921486edae2dc39dd803193e98f5aabfb26ec454ff61ab2adba67c176b2892eed4e7e6ba188ede761b94860093ed826bd9bf66df18d8e99f1420b28d1206be89a050873e694e7afd14fe55f41fc48f82ba078dbc1b7ba6d869965a66a90c5becaee055475604e10b63382057c4fe1fb6d6bc13f16749b77d39a0d6ed6f5520b374c1707218a1fe20431e6853538ea378e8e268c923f46a6c14651823673e8578fd6bc37f6b1c68fe08d36f3ec8b758b968de5099310da4a1cf619c7e55ee5f681730a5c18bcb322f298fb99182bf856278cfc21a7f8f3c317fa0eae3ccd3af5792b9df1b01f2918f435cb0972c8f9ac34de1eba9f63e2f6d6e2d73c2f22aa79573e5665691c6ddc080c31d083915c75ef88b51b7b38a2863b7b06d3e3e244184979ddc01df0315ec727ec9fe2bb3d4e3b1b3b9b47d395b1fda3e6f6c8392beb81d2b62e3f631bc92e6551e284974f986d7568ca48a3a9c7e35dceac753eda79a5395a573df7c197ba4f8dbc3fe1fd502f9b24291dc2874e776c00678e79cd7897ed692c5a3eb9a0ea9b64d97109b62021c6e53fe04d7b27c30f008f85fe0db1d021d4e5d512d86d335c677f5c81cfa66b5fc4fe16d2fc79a1dc691ad5afda6d5f98980cb44ffde539e3a9ae08c9a95cf94863a54b14aa27a5cf8575af105e1b6b2d4165f3228599a61147bd917a0de3ea57f3ae7f5bf2b5a5b0b9b0944b2c7b835b49180518e376738ce7835f6c7837e00785fc24ccd1acb7cd244f13a5e7ccad920fe3d054d75f007c16dac5aea36ba62d93c4b87b75198e5cf5241cd767d6123df9e73466f547cd5f0075fbff0008fc5eb4b48e79214d494452d8bbee0c00fbc0678ec3f1afb5191d7025fbd9cee0783cff008573765f0c3c1da4ebd6dadd9e836f6daa5ba958ee1148619f4c9ae9063767fce6b967253773e631b88857973451e03fb62e977379e18d02fd2d5aeaded67749658e3dcc9b89dbd0640a5fd93bfb557c33a84b3d8470d97da1946d7e5fa738c0c7ff005ebdea7b78ee20313a868ce32adc8fae29b1dbc56abb628d111b92aaa141fc80a7ed7dde53258a6b0ee88f6c153b5b606c8000e4579b7c6ef852ff0011b446974d9238757b7c948e41913291ce7d7b77ed5e95b8e319e3a1f7f6a15915c00838e07b67d2b252717cc8e6a55a74e4a71dcf1cfd9afc21e39f02e997da4788add22d291964b476903be4f51f4af696511f01410a3ee81d78fe64f39a8b705da31923006ea63b1ddd703eb4393a92e61d6a8ea49c9eecf9fbf68cf84de27f1f7c42d0f54d06c6194dbc256eeea490671e9b48ebdf3ed5d2f893f665f0e78ba3d0ef35092e20d4ac563779229376f6041e739e3e51f99af5a8c98dd9978760416a451e4aedc9e98adbda3b591bd3c655a30e480b0c09676f143191b2340838c703daa5ce7a74a84b6edb9e48e297710de958db56cf3e4dc9de5b8d936b46f1322bc4c0868dc02ac0fa83d68b38a2b58fca86248971fc2319fc3a7e948cdcf34329519e869dc7adad72665f97151edec0d2abfca39cf14a781c5048df949c1a78c0cf151b53b6865c72280177718028dc69b8c714a401d79a0037134d2694e17a514008bc521cb74a73138c76a4e01c0a0063271ef4e18ef4b9a6e2800918638a8ff8b34e3f4a45f95b245003b76d14d698ae30334bb433649c52498cf140079cde945191e94500698c6df7a4dc69474c521214d4162ee1eb4dfa546cade667f86a6071c5003463f1a0f149b4e49a7a9dc2a4422fcc0e0f620fd0835e57fb492db45f0e06a721fb3cba7dfc4e8c46097e0023df048fa915ea25771c7ae7f9578b7ed856725c7c19fb4452482482ed1b6af2a7208cb7b0fbd9edb6b6a3f1a3b30af96bc59e5ff163c45e1af883a06851e952dc1d43c40e92dc3c519f9663b57675ebbb3903d7db9f27f165adc59dddff0085350892cf55b29766db004c4ccaa0865cfae727d335f4159fc3e91ff649b48ae2e2cf4dd4edc8d523be4ee19b72bab8e5492722be5bd6350bab8f1dc32ea1a9b25e3010cf78abb9f214062091c82b8e7dabd052e6b9f710c473a49773eeff0081fe13f0de8fe01d3e5d2ac6dfcf9a28cdcccabf334b8c93cf43927f3af46698b29e7a0fad791fece7e32b1d57419348d23fe267a7d9fef24bec85db9180a78f635eb2146edc0751918e45704f73e47308ca38895de826f2ac19577679614c590091b00aeef5a4bcbc874eb1b9b899fca8d1725b1ee07f5a659b2dc5bc52a481d19430da723f3accf2badc99032b3e4e769a9a1748d8b80a0af39efd474fc71559576bbfde39f5a763e53bba771485d2e7ce7fb447ecf771ad6ba7c45e1989ae2e2e636fb6e9cc76ef9320074e3939edef55fe0bfeccf70ba95aeb7e2e8534eb7b6903ae96cd96771fc4d838c8241033dabe96f331caee2739ebde9ccdbce49e7a63fcf7f7ad5d595ac7b11c7d48d3f671386f8bbf06f4ff008b1a6dbee75b4d5ede51225e6d259b00ed46ed8248cfd2bcd3f678f807acfc3bf1b6a3ab6bb711c771041e4c315b1051f7b64927ae38e99ef5f41bb1da71962719e48ce0fb5032157b91fddc0279a95376b18bc65751e56c456558caae473918391d297715418e1bbd376ee6cef0a4745f7f4a556dd238ce4a900d4f53cf77bea3c3118607e6f5f4ae2bc75f07bc23f112e96ef58b293ed09bb325acad13302318254e4f3cf24d764bee297cc8b7a8c9ddd7038a7195b634a552549de0cf9d35cfd8cf48696da5f0f78867b744e1a2d499a5da3fd9c63f339af6af05f84edfc17e17b0d1a39649d2df2ed2371be42739fa7f8d742d20564ea4e0927d691a447c061923bd53937b9d1531752a2b486f9c616474192bf74e3a7f8f5aa979a3e9ba86a569aacda7db4da9d9645bdc49102f1839ce0faf3571e40bc8a6c326f603a6e3deb3b347245b8ecc5663e714ea0281cd3b76d6047ca47a521e58e0f273d064d18655e46085c91d6993abd0618c6d917aab9c914ff318be4e5c8e3e624d351b767349b8ae4d037e62bbb6c50067b12724d2617774c8e982053c36e51c6334940bc81b18c1036ff740c0a60f91401d076cd48718f7a89f2dc0da3eb4809721faf269b2b055e07e54c8d5d79638f6a749f2a923ad318d590118a731f9454638c6475a7fde5c023e948424992bc1e7ad491c41d98e73b072bdea28e1e5b24fb571bf18bc773fc37f07cfe2082c85e344510c7ec4e0b7e1d6ae29ca4922945c8ed59f2770e5077a6336e63915e75f067e2f1f8ada24ef2dbc76d756ce5728d90eb80430e3a1cf15a3e35f8b5a1fc3df13691a26b4658bfb4d7305c7f0eec8183f9fe956e9be670359509464a1d59d9ef1dbad00e7af34d8de29d7cc8644923232194f514e3f2a93f9e78acad6d0e7945a6e2013b8a4cf5cf14abb9801c6e3d867f9d72da8fc49d2f4bf1f699e12b80c6fb508d9e2753f2e54676fd7ad546f2d10462d9d58c32f3d3b535be6538a468d635959db604c70dc67348b346bb57cc4fde709f370fec0f7ff00eb534df61f2b152365e4f4a7e78a6fdd525b851f7bd17eb4aac2652d0bac8179e0f5c6723eb4f5172bec2fea283f29e0d56b1d4adb54b3173637115d43bb69911c615bba1f706a45ba8269a48a3b88da48f6ee407e619ce38fc295fa0f958edd96c77a73f6a6bdc471c91472b6d790e157b934e6c64fb12291234e78a76ee943305514dda646c8e940876e1c8a6afcb48ca41e7ad0a78a005fa53778a19be5a02f1ed4009cf6a1b2471d69785a36fcd9a00366e1f3f4a1946de0d377ee6c76a78028022e68a9a8a00b9f5a52064e79348cc761f53d2923caae1f935258f5fbbeb485b2782298c0f00714fda38c0c1f5a000292dcf4a705db4c1f29eb4a189cf35221db4005bae2bcc7f695b3bad4be0deb16b6d1092dd955ae2557e634072723fcf04d7a61e71593e2ed321d6fc23ade9b72f1882f2d648583103ef2119fad694df2c948e8c3d956849ec79d78775ed33c4dfb3adbdc69aeb6515b5a2dadc452fccb0887aaedc7a723ea2be25d52c46a9389f6cd26a3785b0ebb958ec70771e3032b8e076afa53f671f05ea2b278bbc317b3c73426da3d9673314122baa83230f5f9719fa574b77f097c37e1ed4b4ef0ea6917d3da6a5966d43cd2b15832a9c1de475ce063dfdabbb48b3ebe9ca9d1938c9f99e4ff00b28eadaaf80fe27be8a6dde2835ab52cd0a02c23111623771c654e6bed394ed030723a020607aff5af2ff0ae9f27c3fd72df43b8d1afafeeda3172fae2206545daa9b3763ae2bd39b13792aa59f70ddb88c13ef8ff003d2b926eeee785994e33aa9c06c9e5cf19865e44830558654f4ebf950db55408d42220c044c617d3a560f8f354d67c3fe0dd6350d0a18a6bf82d99a269b928c39cecefc66bc07e0bfed1dacea5afc5a5f888dbea06ea501eee38c4580df74e33d01e3f11453a7cd17238e8e16a565271e87d3c1b9524f07a535f1b8e4fb714eb88c09531c96190a296140cd818249c75e73f4fcea12d0e27da5b91e4aa911fdeed9a22cc8074dfdf3eb4b8612344c8d1b2a839c646ee73cfd314b2a7920eedaab8c86670bcf6a56e8528dfa02c8de66c3c71c10334c69b6ab929c2b6deb83d7e952dbc42e247556691e2628c178dac06719f4aa2bae597da25b75ba85e78f0bf671200d2bb0380bea7afe54b9742d516d6c733e2af8bde15f06f894681a95f7d9f54655913cd52b18046412d8e2b43c51e3ff0ff00822c6daff58bcfb35bdeb910346a4994e060818f5fd2be48fdb62f2ceebe2466d8dc3dcc76aa9399095c30ce140cfa73f857906adf133c57e27d2f4cd2b54d4669b4fd258359066dc50e41c162724019aecf671b47ccf5e9e021512933f473c1fe36d27e20690750d166335bab146593e59011c1cafa56e3286dbd09c64e2be47fd8e7e205b5c78b2ebc377b325bcd710235bc7393ba570edb803dc9fe95f57c92b595add5e4a161b58c96691f85541d492718c63a56128da4d44e5c5e1dd19250d9960fef30be808a6f92064838e3273f9d60784fe26785fc71a95c69fa1eaf6da8de44bbda384e0019f5efc9adddbe64655c3206cab2b0c7a8acdc5c7738651a94f592393f899f1274cf86367a7dcea90cf34777291fb95fb8a07535d1693abd9f88347b5d534b93ed165708248e4e8083fe1c7e75e23fb68dd3d8fc2ad29e042a4ea8a8fc9dc40041c1ab3fb24f8d9fc4ff000ee5b016cea749bb6892e1948595186e381edc574fb34e9731d6a8c65479d9d8fc54f8c9a5fc21d5347fed8b5ba96c6e032bc96eb9033ebfad67b7ed21f0d6c6d610be245ddb941b61133cbf31c01efd4579e7edb6d0af8734901d5659a52b957e36e1b83f8e2be4ad2fc6fa8e8b7d9b3b5b592e635c2cad18246410a41fceb48528ca9f31db47074ab5352ea7ea3acf0cfe4bc4c5e39577a328ed8c8e295b684666380095c9c0c74af937c35fb605d69be0ad3acaff004857d56da2103dc6405ce0e48fc2b1b4dfdb0bc550f89cbdf5a59dc693732a298110028a48191d727dab1f632673bcb6a5fdd7a1f63ac82352c4965519c019c8f5e29f23157c2aef5e3e6cf4cd7cb9a97ed9d3a78961b5b0d1ad934e8af0413899b0fe5e79650075c638f635f49f877c43a6f8cb4d8b52d1aea3bbb0907cafc86ddce411ed8c567384a3b9cf3c2d5a5ab45e19e0e7073cafa52c83a0ea54e7819af3c9fe3878774bf88171e1bbcf36d242cb14334d80a6420f07db35e83232d8db99ef0810c5192f2c672b9039231d474fce938bd158c7d8cd5aeb71650cc430c16ee33827d85126448aa0798181257772bec40cd789fc3dfda4a1f127c4cbdf0ddf69c96fa55c068ec2e77fcd230f5e38ae5f5ef117897e05f8f1b57d3165f11f856f27ff004a064323c4a580c01ce3bf34d52dee76d3c04a5be87d12bac5836a274efb5c2b7cbb316cee1646dc78c2ff005ae2af3e37784f4ef191f0bde5e358ea4938819e642103f276e7dfa03f4af993e3978c753d0fe320f1169e64b77b79629123624318198120647385fe42bd57e3c69fe1dd4dfc3fe22256e2eb54b7498b95c6fc60ab9ff6873935d11a3156bbdcee865baa4dee7d0cc4ab75c0c654f639e807bd73de3cd067f10f81f57d2e12925d4f6ec2357fba5bae0fb706a5f03ea126b1e10d26eda6593ccb75ceee483cf3f5e2b5aead85c432c4efb62950c6e57ae18638ae54f967a1e4ca1ec6ab8f667cfbfb2ae9b2e8379ae6977b17d8e595925b78f7ee0500380b8cf1906acfed65a1d9f88adfc3b69771a47a9198c56ec1c87576e41ce3d47eb5cd7c12d02f2cfe3d6b3692ea773776da7abb9466c05ce4051df03d3dcd7b97c5ef01dbfc42f07dd5b999ad2fadd4dc5adce7e689d70c08e3ae01fcebae5a544fb9ecd77155e9ccbfe0fb39bc3be07b18ef99ccb6b6bfbeca92728bcfe14cf02f8eb4ff00885657177a6a48d6b14cd0f9c57e572076af35f857f156f3c71a4eb7e16d46e2197588ad8ac52c7f2993200e416ea4673f4e950fecafe22b45b1d73c2e2dcd96a5697324ab6fe672df300587b723f3a89535ab39eae155e53b9d4eb5f1797c19f17acfc25acdbedd3b528d5ed2f882ab1b1fe135e6df1d3c2f7767f1b3c33a9c3892179d1e29012360e54f7f43fce97f6ba3616babf852fe78a5f33cf3179a80b14c30233f91abff001ab588edec7c01ab5e8952d6742c2e48236951b806fcbfce6b4828c526830b4e128a6cf58f8a9e0dd47e20786d74fd235a9340bd6996437118cee504663fa63f95735f19ace7d2be1195b1b931dce9b24663b846190e32a4e41e320b575fe35d7ae345f87ba8eb76216478ed3ce50a0b705700e3f115c9fc3db7b5f14fc14bd9e2866b8b8bdb566b88e55e4c8bd700f6e78aca2b5d7b9ca94549beccb7e1df146a7e3cf8417379a6e24d5dad1c286e1a42a3afb9e29bfb3f78813c43e018e0bb7920d56d65920b98ee54a488f8cf20fe86a9feccb75f6af866209209eda6b1b892dfc999361e18e307f1ad8f01eacd71e30f14dadce9cb6f22dd2b99946525503ae7d7a55ca694a490559c5395b6317e07f80f59f07eb5e227bd83c8b2beba3325bbc990096fbca3b67155be1cea977a87c5ef15a5e23222c102c1e67a0243638f715ebd1b3090004e47f164f6e7fad60ea5a08bcf10daea504862755659b69fbdc838ac5493d598c2a2b3e62d6b5a67daa4b6bb12188da9f30b03d000473f9fe95a08de6c28f90db803b877cf7a7c91f9c92447fd5b8dac0f714c8edc5ba80a7e518017d00acce3f850d605c014f5518e0e294280323ad308dbd4f5a081d8c77a14763c53171939cfb50cddcf5a00565fca819ed4be60db92714dddd3d0d002ee3de9af200bd32691be6e338fa8a779657e8067340117cc8c383834e6c1e3914afba45e1b8fa5353038e4e3b9a061b7fda3f9d14bb968a06696e5e38cafad296040c039ef5144c8dfbb4e42f0cd8200aafa85d7d964488472991b9dd1e3083207cd93dea465ecff002a6ee27b66879012541f9b008c919fcb34c398d5771cb31c7ca3f9d01662beef2c955f9bd69a24263e3e62aa49c7e14d326fda37ed2b26d6cf4fcea1922b8db74b04b189994f940739e3bd05a896a3b88ee21692270eb93cfb678a5665750be4ace36e0a3ae7b838fc7bd50d3edcd9c31c0119196340723a9c0e6a693cd92583645bcb12b265b68418396cfa7f8d1d2c5c627947c18d1e4baf1a78cb5f7ba8a68a2ba6d3d2dbf8a350db886ee464f1e9815eb13adbf926d2ed95add983ed90e431ce47e3dff0af9cbe1578b07863e27f8ff40ba61e6c9a91b8296ff3978f04641f5e95f484d6f6f71e5892359d72a519b963919c81dce09adab369a3d0c52774df62c190c588c92777214b67db354f52b85d36dccd792c50c19ff5923edda3dbdfdbdeb1cf882def6fa68ed50a5c5a2f98541dccf18edec7a71f5a8f53bc4d42dbecd7713a5c6a285a186e10120260bfca4f3f29ec7359f2b399536b566e3795751a4092a83711b245b88cc80839c67db35f03c3a0dce93f15ed20fb2b244358589a161b58a2ca4ae7dba7e9e95f7325b41690ac56cd88f6b1862dac4950b90391953f8d7c79f1f3c43068ff0012e5974f6922942c1293b8155971c91dff008471ee6baa83b45a3decb23ccdc7b9f6db4ca625bbb911461630f3aa49f2a28ce79f618e2be58f8a1fb546a765af6a1a66851ada6879d892ca856493b960739ec47e346a3e26d53c59f0f6e2e6e75ab84664513476e42ef03b0c74af36f0af8025f88adae4e776cb2daaaaa4b3b2efc120672781fad5c69a5ab3d0a795429c9ca7a9e850fed5d749e09922b6d34dc6a30c9e6bde19885c37000cf5e05788789be3978bf57d61a7b9d62f5ade4dc9f678df6ab291d38f4c835e8de20fd9a7c436f6305ac72c096a20fb7b4ccec02c7c952f93d8715e23a968b2cd1ba8b213c96ec499addcb8ef820fe1d31551696c8a950a4be146e699f18bc65a129161afdfda26d2d26d94c9e664630724f6aad27c40b9bf96d658352985e59fefd64123071203c1fc89ac2b7d26192f1e55b8911a3dacb6f30daad9fbc3d8d43651cb69ac15dc8005fbbb31e606cf1f50053e6f2335051e84baf788b51f13eacefa96a135e4e58307b87dc79c05e6a9ea167736891ca60fdc48cd192a7856030430f7ab9369ff0063d42091c08ed80555423e623d4fbff855db8b145653e733248bba42a092d9c9fcf00d2bdca513a5f0af8824f0db7876fa28e0458e513a4b0b1fb40c3862bc1ce48047e35defed03e2af13f8c3c4f77247addd41e1cbe8a3b9b7805ce62851972c8e0742381cfad790eaa902c20449e432a29539c1e71b4f4ebc7506b22dee2eaf24686696631cd9428ef953820e40f7c7726856bdd94e319ad56c751e0cd565f0f6b16da869b7a62bbb59199807da303b64019afd23d23528f5ad26cb558fccf2e6b659558f3f2b46304feb5f983e1eb7916ebcc9dff77e66e2db32cc070571ea6bee9f82fe36d4b5ef837a2dfc309596169a00ca320a0ce323d5475ac6b3b9c38ea3ed29a9256b1d17c6ed0edfc45f08fc4ad7110bef2adc5cc2bbc30429fc40f6e98fc6bc77f628f1235d43e23d3028955a7599ce31e4e570b9f6c27eb5ee369aa5beb4b6ba05cc734d6dab5ab442e4c78491b1f3a11dbbfe95f387ecf771ff000817c6ef11e86246b4b7983acc973110176333440fb1231f8d107fbb71670508a54a506ef7d8f76f8f7e013f11be17ea3a3c71c71dfc205e42fb4650c677301c74201af8c7c1ff000b355d496d751b5d35b57b08ef1eda78d331bef5391d57a0ddeb5fa09637535fd94ac926cb894310f2a6e0b90015238c7718ff0068554f08cd048b37fa2c76f79101e759aa1531c9c83919c7207e95953a8e2ac451acf0f1b58fcecf1158dd58eb979a66ada62e9b35acd22b2bb0ca86c146e3f870339f6f7ac49278ec666b38a48ef16207cdddb94ee270194f18041c835f6c7c43f83f69f12be23c773796ff00d94d6de53c979b770754e446c3b8c647d2b47e2f7c09f07fc4482df10ae8b7b6fe5b7daec5047ba3da14230c72b8191e95bf3de4933d7f6f16a2fb9e0df0d7f67bd53c7de0db6d72ce4b4816e8af931dd4c5a463939c9eb9f7ff001a7ead79f11bf67fbd82c219e4b5b352de59662606e77100fbe0fe75f4f7c2df0d59f837c2ba769ba55bdd4d6b69198e39a423f7877e33cf2474a93e2bfc3f8fe29786974995fecd7104aad6f701772a480fcc08f4e4d671a979b4f6462f1979f2cd7ba7c6dae6b73fc7af1f4578af69a6eab751c20a97221ce7191e9c0fd6ba39bc5df10fe16cba8f8735ed54cb652ab450aabb3ae0f3953e9803bd7a543fb24699a2dedb09b5d69649a6f996193632a29cf61d33fcebd73c49e1ad0b5a034fd4ac6deef4eb5da6dee197e70ca02ed2d9e46335a4ab27d0e996230d1d523e4ff00f8b34ff000ef8c74abed6ecd85a40df684b8f9be755dd9f63d457d3dae7c64f0468fe078b5c8aeed6f348d493fd1a08d423cb212728aa3f8979ed5b3e24f85fe15f15787a2d2af74d85a0b74409e4a00460003071edcfad79de87fb32786b41d654dc4726a16cb3f9f6892ca0c76efdcedc0c64544a7192d59c589c5d1aebdd76392fda9747b7d4a3d07c6715ac86ca7816dee4cc08688e32991d0719a83e20e8377ac7ecd9a05f59a7efb453bf687c318589ff00d9483f857d13e28d12c7c45a75cf87ef2dfcfd3af41578e541b23c00339cfb71f5a89744d297434b68ece33650a2c290e3230a0a8c8efd6aa356ce3e4610c6f2d3515ba3ceff0064ad46f754f82da48be8d9a68cbaac8aa5b700cc00c57ad59dcac8e533e5cc8cb9561d3915c6dc6bd3e9f6b67f608c41616eed14b12c5e4b1700e3206320e09cd6af86f5c1a95f4f711dd2058d36cf66f82509c723bf6ae692f7b98e2ad4a536eaa3c8f4db6f1068dfb4eeaf76fa6bff00635ca60dca01d36e39c7b9af7d7649639ad9cb2868bcb7723bb0fd0715cceb51eaf637c977686dee6291c6647f94aae3380707dab4a41705a19650bb2642d3b36dc1381eb9fe55a49f334c9ab51492679c7c27f81e3e186b5ad6b735fa5e4daa4a6458e68c1680e41508d9e07d7d7ad74fa6f81bc3379e3c9bc6b6514d69aea235adcc1136221d371d8077c0e6b73fb3249ae2ce4b796486ea2cac8aca3cb950f7271ce0e318c545a2cb1c3713bb5a325d6ff9ce4ee6faf3cd2bb25d6948778925f0fdd4767fdb3025d26f0f10317985180c6714cbb8ac3c5da3b586aba624f64eec908953a0ec40edff00d6abb71098e4dd2b603f2ab8184faf7edeb59f757d322dbcb3461610ff002c91e480c7a2b0f4c679ff001a2f656223271d8d5d2e3861b48ec22686e2d608c4463dc18eddb8dac3e98ab104d0e9cd15aaaac43015638d391ef81deaa45a2dbcb225ddb2a5ace465829f95b8eb8ffebd24324cb23bb28b95fbca65ea0fd462a0c64f7f32e42d045348906d8e4dc5d9475c9ee462a411469bca471c65b825540cff009c53233b9bcd09b5d8609a9432f3918f4e2830726c8de491595793cf2d8a6f9f14534a10894646e23a8f6c7e349756e6e4c67734655b231c555ba8659f54491184518c6fe3ef1a449a4adc0cf3919cd37cc56cf6228c0dc48040cf5cf14d6556e3d6801cae7b0cd42f2eec82c17db14e2b80002463d29248c498e3a1a0457b9bc4b79111831278e3afe5562175917eeb0ff7aa19aca09a6591d4865e983563e551902802368c6e231d693cc50c23dedbff00bb8a773bf38fcaa3b86742ac00639e462824959f72329f9862ab09a68d46d5ca2f1cd582e4296e8bef4c493736fce074f62281a1b35c188027a3f031446d22c6438c8ce78a7965db8201fc2a45019415e0639f7a0a2bf9d1d1563e5fee8fca8a009e3f3fedd71e636d48f9f2d7a3d4b2422ea446246d91086538e70723350dfdf258e98d7974a4ac09bd9633f7b9000cfe351dab2cb1fdb2393cd803e63641c15c03cfe7526bcafb171621b9080a0f42703fc292451bb0db719f5ed42cab2ed2390d93f78703e951b08edd1a42199064b77fca824ab034702ca8916e5918c8818f04fa67b557fb418668ae21896ee3328595636c345f29ad18a44e2452bb1c02dbb1f9536eae20d36ce59e4091c56ff34ca08181ebd39a762e2476b0186dcc92b4af28055b27208cf159f1c8fab6b5756e23dd05a8540f9c001864e7d6afc3aad8ddd9aca6e9561990b2cac410c87bd3b4eb5d396e1a58555bcbc79cd1b6781ea33cf193f81a5d0d7924b53c2ad3fb12d3f6bcd5addedfecaf2d8164bc520047e9c0ef9dc6bdead2d5ecdb135cb4e55784200ce1720e47ae3b7ad78a7c3db1d226f8b5e3ff00195d6a50ddcf6b2b456d002196de2e573bbbe4915ecb7779b6e0325bb185b2b0ed3f364f1903b8adab743bf137764ce6f42d0458eabadeb040885c6d8962894a84541b81c92793d3f1a597488b52d2ec7532b25cdc5a798d0f9cc5a4491f2304e3a73fa52ea1aabe9a7c491b48279ed8348d692364142990ca7d462b13c07e32d475cbbf0ccb672c1368779148b2c8e312bb0041e3d8e2972bb5cd23094a0e496c3be30788f51f0d7815e7d36e56c3578e4fb3bfcdb82ab60640f5e7ad7c41a7e8971e30f8810e9ed7bf6bd46ea768c34fb8ed6e73c63a77afd02d76e2db4dbabe6fb249a9ac91a2b2431accc65c803af00f3fa533c39e01d1f4ed606bafa2d8c5ac6046268002c3039ce380dcfe95a46a72c6c74e1f14b0f1bd8f399be029b0f85cfa2d85fc36fe20fb395926cb142e4e40c63fce6be5a92ebc4ff06fc7021f324d275ab66024846f3130e5892718209c1fc2bf412459a759ed6f51ee1d7913460aef04fca722b135e9a3d4edcdccba1c57a8239104ee44811914e371c70338e4e692aaed66694f32936d48f31f83ff00133c57f1ba68ae751b582cfc31f677b6b99238894bb91460004fdd1963ed5e07aa7c35d7b40f10ded9c7a35d492da5c34ca9a7465d5d0b67e539c602f6afb6ac161f0de8b6f0a59ac36eb1ac8d6f6a7e5285724a01d4e6a8785e792e61d56d52036ae5d9a3693e7de8ff0037cc463663142994b19ae88f8c7e327c1cd63c27ac695776d6734d61a986c662225dfb4348ae327a03c1e2bb03fb296b6bf0fe2f105bdaacdae49bae24d3f3991a3c0dabcf4f9467dff1afa8352d652111dbc9e56a51468d1cb2300509604121bae7e9e82b521ba9f58b2b57b19dace6865524121b23014237fb38a9f68ee4cf192b688f92fc07fb23f887c5d63f6ebeb93a0436ad88edeea1f31e5206492370d80640c1c934ed0bf65dd48f8ab4f82e9e1d47c3ed2ed9e4b7631c8b86c10477e4f635f522eb1716ecf737fadda488f782ded9215c46460b1507bb103be7a5731a45f585943e2044f15b48df6977b4631ef31e4f390318e403f8d3e7614b1139deecf24d6bf647d1afb543ff0008e78a2decace597e7b6bfdde7c441f982027938c91dbad44dfb1ae9b6ba52dc45e2f5b9324c1e3b89231b5e32d8c0c671c0eb9aefb567b2f85dae693aa6bb6771e27d4ee1a3125cd992546f6c0dca41ec4e715d96a16b37856cfecde1fd39af2c1a57df6ec488d1393b47a1cb75a7766eeb72daccf24d1ff66fd1dedee6eac6f5b5592275496d9a3da99cf1b48e878f7af41f0ae9da57c2dd2ffb06de10b1c9389e5b459b2b1339f98838ee474f6a957c33ae69fe45e6830fd884851dec9263207009cf3ebcd69ebde1bd5e3bdbdb8d3b4fb3d40cb6fb229a4703e62a4938f50ddea65aee153111aab964f43ac3bee16ccc61265327de0b8da320fe04573fe20d274b6d416fadede117714bb6e64116e62472ac4f702ade9b69ae27832d6caf6ea3975a8e24f3a68c623dc33faf3cd5eb0d3e4863b7124999630439fef31c66a763e7fda7b3968cc3f0bdd0d5356bc4f3ca492b25d2a23f2181ea4f4c1c56ecd2051713b471db30930d2bff0011e79ce7ea07ff005a920b08aceead16248e181232a762e0ee273d6a5d4b4db7d72c961b92ed12c81c1538271eb4b4339d7e677311a5b66ba9f51f31205640ecf2be30a320e79ef583a3f8823f106bd34da7dd96b56c2451b459d8a083800fde1c0fd6bad6f0c699e7a38b557c000ee39ce3d4743566ef49d3eeade5805bac064c65e01b1863a608e94f98da389a76f78e52fbc5cba63db5db1b8b54370d1cf12441595791d3b027073ed5a163a95c9d52f1f4f95ae6de68a36da9f36d760727f4ad79b41b092d563951e43b83798edb98e0e7ad490e85636f7dfda1145e55dedd85e362a08ebc8ce3ad3ba0956a6d5d195aa69173ab456f346c82eed9d58bcbc6e5e77291df827f2a75969f0db48f6c240125667539ddf2e40039efcfe95bcccbbd5d86f607233fcaa158c6794523b1c74f6a839beb0ed6206b591a26b7777dc8e1d26420703b1a9cc3fe8edf3aba10080c39247ad48bc6403c9a245dcb83d318a939f98a9f63f3448ce8a2495b71f4aa7756378ba837d8ae0c48d1959636c153db38f515a80ed0a3390b49b4727bd55c7ed5ad8c84d0cdcdac915eccf300002c792c402013efcf5f6aa7a1f836df47bc6b8525ae586d91cf75ec2ba3dc768fad3648c6d0064773cf5a2e6bf5a9b8f2897908b8b7788f31f4183c8a8ae34d5bab34858e42a6d1d338fad4df77152751cd339f9db21b788c5691c2589741856249ff003da905ac99621f1331fbc475fad4e169ccbf9d05739911c13ea9a7ccb7256de657203ab75c7b62a7bcb1965d11ad8cacd204c3b8c02fd3ad5eda0740076ce39a55e7231db0680e722b7b7f2618e318501402076c538c255a470383c019c54addfb669acbe62856e83d2913cc28c841dbdaa261b9b9ed5374503a8e947278a08191eedc4b803f1a6c8c41183fe1f5a6c91bf981b3ed526d23fad0217e5c0c10475c8a52a000739a8bc948d72a307352eedca050046c69377a53f1f35267b5003793db34c2ac5b24fcb52b67b1c535b1400de7385e94bb978cf5a41c74a41d7914011ed6937ee3f2e78a70f99020e0814f2b9c638a1549618e28288c0f2d4ef6cfb511cc655c6dd84700fad2b46d248476a55561953f7578140c5d8f4526d3fde3450225d36de46d2445736ec9b8306b7ddf779c75f4e2bcfb57f1a49f0fe64fb7c53c1612f9916d48cbc7b9f00038c7a5761796be258f4ed463b4bdb67be5327d8e49fee9c0f9439e2b9e8fc3fe33d73418c6b77364755173e5dd46e80c5e5601263e707a63f1ab515bb3dbc3f2464dd47a1d1683ab2dc5bdbc935c79f6f3c6be44d1aaac1807046eec7240e7d6ac5deb10e9bad58457f792d889098d119774726483f7bd4571fa2fc1b9746d435bb63e23b87f0fdfb79b6d62e0836cc704803d030047d2bac93c3f1ea91c716b2c35158a38e35e0aee207df383f78f72314a56e8615d53e7d0bbb57ed2a8ca1a3d9bc3b8c03827918eb5991deff68c5a8585f4d136a56a845cc28a0af94c0957f75233cf18da6b434ed02cb49b24b68232638fee077662bf424fbd4567e1ad234df125e6bb6d69e5ea57d1a457326e243a28215307a0193c7bd45ce5e68459e6ba8f893c3f79736d6fa7b8bad1ed60dad326443b8100a0603ae4e7f0aee7c21aee9f71a7d8ada208d2f9d955e55387c0e49247236eee6b56d741d2aca168adaca28a0662e515001bb39ce318ab662842224712208f3b005e17230703b0c718a727a591d75315070b451e69a4dd7857fb6bc5d16a1a6c76375a94ed66f796f16d825870023647008c9e83d2ba7f157882dfc03a0c9144de641696e9b5436e2edd137376c961d3d2ba2b8b4b5bbb536d736f1496e79f2ca0c0e9e83da8921b7b8531c96f14919014aba020804601cfd0536d3b19cf10a4eecf02f899e1df11eb50ff0063e8fa491e2af1769f1cd7d73e7ec8ecd6238da0f4f9830e98ad5f86bf0f7c65f0ff00ecba7cb6f6373a6c50178984a4c90cac0ed39cfad7b5b2a492233c685906d46da32a38e07b71d2957e5937601e3072073e99ab552cac744330718b825a1c4fc3b4f182e9b3dbf8bac53fb42d642d04d6f285339607b8f4e3ae6b5746d3f51d37c26f0b2e2fcbbb796f2659379ea4f73cf5ae876aaa05c7cbe95108d5880df315e959b7738e559c8ce864d634b68fcc30de88631092a482548c647a9ef51e8fa09d2f4cb8b47b8f31a40d96e47deea4f627f0ed5b8b9e4e72339552385fa53598051c671c73537b19fb56b631acf46d4edeea0126b45acede358c45e48cc807a9ff000c572fe23f006bb75e2db4d5744f141b0b2b5944f3e96c8409803f701ee0fa1cd7a01e9c93cfb9a171b48f5c83d79e955ccca588922841a0d86eb9792cf1e74c67685ce5518f5000e83d055bb1b582c548b7896325816f7c54aaaabd3a52ed0a38145d994ab3919d3681a5c96f1c3258452c71cdf6848f18092631b87bf27f3a7d9e8ba7d8c92c90d85bc5e612cd84ea4fff00a87e5567856dc4e0773e949e60326064e790dd8d2bbee47b495ad727f957044719dbf7772038efdc7ad3437ceec4905f93862327b9fc693a0e7ad35ba521733b6e2f0aa546719f522824960c4e7078e0714ddd4bba96a177dc0e48e4e69571ea7348795a66d2b93d4d048ec0f979248e7f1f5a5e7017b0a8c6f6edcd3f767d8d3001f2d2772452b29229a14d02ea3b8f5c9a1b2c698a3e6e69e45021bc6e19a46f9988c714ec0e28f5f7a02e2050bf5a1bb51d3346ef5a008dd7bd03d295b9e293d2a804e3a03d0d3fbd30280c4e29ca79a006b75a7af4a4d81a97ee9c5002d381a6529f9466801ede94c1c1a77bd2151400e5c31e694e3a0a8f6ed6e29dfc54007a52303f4a5a691ba801573de838eb488a10d2e7ad0037, 'sig.jpg', 93023, '2025-05-24 16:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `sms_delivery_logs`
--

CREATE TABLE `sms_delivery_logs` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `status` enum('sent','delivered','failed') NOT NULL,
  `response` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_delivery_logs`
--

INSERT INTO `sms_delivery_logs` (`id`, `notification_id`, `phone_number`, `status`, `response`, `created_at`) VALUES
(3, 5, '+251912345678', 'failed', 'API error: HTTP 401 - Invalid API Key or Token', '2025-04-20 11:06:50'),
(4, 6, '+251912345678', 'failed', 'Twilio client not initialized', '2025-04-23 11:28:00'),
(5, 7, '+251955667788', 'failed', 'Twilio client not initialized', '2025-04-23 11:28:45'),
(6, 8, '+251916273479', 'sent', '{\"bill_id\":\"14\",\"owner_id\":\"2\",\"phone\":\"+251916273479\",\"message\":\"አስገንዝብ: ETB 4,500.15 በ2025-04-20 ለMulugeta Shop የሚከፈል። የተቆጣጠር ክፍያ: ETB 500.00 በቴሌብር (*127#) ይክፈሉ።\",\"send_date\":\"2025-04-23 23:48:49\",\"status\":\"sent\",\"message_id\":\"SM851d44a9f3c3981ea7b349dfcd38d3ef\",\"error_message\":null,\"template_id\":4}', '2025-04-23 13:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `sms_notifications`
--

CREATE TABLE `sms_notifications` (
  `id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `recipient_type` enum('owner','tenant','other') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL COMMENT 'ID of owner/tenant',
  `bill_id` int(11) DEFAULT NULL COMMENT 'ID of the associated bill',
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed','delivered') NOT NULL DEFAULT 'pending',
  `message_id` varchar(100) DEFAULT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `attempts` tinyint(3) NOT NULL DEFAULT 0,
  `send_date` datetime NOT NULL,
  `type` enum('automatic','manual') NOT NULL DEFAULT 'manual' COMMENT 'Type of notification',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_notifications`
--

INSERT INTO `sms_notifications` (`id`, `template_id`, `recipient_type`, `recipient_id`, `bill_id`, `phone_number`, `message`, `status`, `message_id`, `error_message`, `attempts`, `send_date`, `type`, `created_at`, `updated_at`, `created_by`) VALUES
(1, 1, 'owner', 2, NULL, '0911223344', 'New bill: ETB 4100 due on 2025-07-20 for {{property}}. Bill# BILL-000004', 'pending', NULL, NULL, 0, '2025-04-20 03:02:57', 'manual', '2025-04-19 17:02:57', NULL, 1),
(2, 2, 'owner', 12, NULL, '0933445566', 'New lease LEASE-000001 for Kebele 02 starting 2025-04-19. Annual rent: 6,000.00 ETB. Term: 5 years.', 'pending', NULL, NULL, 0, '2025-04-20 03:39:18', 'manual', '2025-04-19 17:39:18', NULL, 1),
(3, 4, 'owner', 3, 3, '+251912345678', 'አስገንዝብ: ETB 2,070.00 በ2025-03-01 ለGenet Farm የሚከፈል። የተቆጣጠር ክፍያ: ETB 180.00 Pay via Telebirr (*127#).', 'failed', NULL, 'API error: HTTP 401 - Invalid API Key or Token', 1, '2025-04-20 20:53:29', '', '2025-04-20 10:53:30', NULL, 1),
(4, 4, 'owner', 3, 3, '+251912345678', 'አስገንዝብ: ETB 2,070.00 በ2025-03-01 ለGenet Farm የሚከፈል። የተቆጣጠር ክፍያ: ETB 180.00 Pay via Telebirr (*127#).', 'failed', NULL, 'API error: HTTP 401 - Invalid API Key or Token', 1, '2025-04-20 21:05:58', '', '2025-04-20 11:05:59', NULL, 1),
(5, 4, 'owner', 3, 3, '+251912345678', 'አስገንዝብ: ETB 2,070.00 በ2025-03-01 ለGenet Farm የሚከፈል። የተቆጣጠር ክፍያ: ETB 180.00 Pay via Telebirr (*127#).', 'failed', NULL, 'API error: HTTP 401 - Invalid API Key or Token', 1, '2025-04-20 21:06:49', '', '2025-04-20 11:06:49', NULL, 1),
(6, 4, 'owner', 2, 14, '+251912345678', 'አስገንዝብ: ETB 4,500.15 በ2025-04-20 ለMulugeta Shop የሚከፈል። የተቆጣጠር ክፍያ: ETB 500.00 በቴሌብር (*127#) ይክፈሉ።', 'failed', NULL, 'Twilio client not initialized', 1, '0000-00-00 00:00:00', 'manual', '2025-04-23 11:28:00', NULL, 1),
(7, 4, 'owner', 5, 5, '+251955667788', 'አስገንዝብ: ETB 1,955.00 በ2025-03-01 ለTigist House የሚከፈል። የተቆጣጠር ክፍያ: ETB 170.00 በቴሌብር (*127#) ይክፈሉ።', 'failed', NULL, 'Twilio client not initialized', 1, '0000-00-00 00:00:00', 'manual', '2025-04-23 11:28:45', NULL, 1),
(8, 4, 'owner', 2, 14, '+251916273479', 'አስገንዝብ: ETB 4,500.15 በ2025-04-20 ለMulugeta Shop የሚከፈል። የተቆጣጠር ክፍያ: ETB 500.00 በቴሌብር (*127#) ይክፈሉ።', 'sent', 'SM851d44a9f3c3981ea7b349dfcd38d3ef', NULL, 1, '0000-00-00 00:00:00', 'manual', '2025-04-23 13:48:51', NULL, 1),
(9, 1, 'owner', 82, NULL, '+251916273470', 'New bill: ETB 2000 due on 2025-05-26 for {{property}}. Bill# BILL-000032', 'pending', NULL, NULL, 0, '2025-04-25 07:31:22', 'manual', '2025-04-24 20:31:22', NULL, 1),
(10, 1, 'owner', 82, NULL, '+251916273470', 'New bill: ETB 2000 due on 2025-05-26 for {{property}}. Bill# BILL-000033', 'pending', NULL, NULL, 0, '2025-04-25 07:40:16', 'manual', '2025-04-24 20:40:16', NULL, 1),
(11, 1, 'owner', 82, NULL, '+251916273470', 'New bill: ETB 2000 due on 2025-05-26 for {{property}}. Bill# BILL-000036', 'pending', NULL, NULL, 0, '2025-04-25 19:52:08', 'manual', '2025-04-25 08:52:08', NULL, 3),
(12, 1, 'owner', 82, NULL, '+251916273470', 'New bill: ETB 2000 due on 2025-05-26 for {{property}}. Bill# BILL-000037', 'pending', NULL, NULL, 0, '2025-04-25 19:58:59', 'manual', '2025-04-25 08:58:59', NULL, 3),
(13, 1, 'owner', 83, NULL, '+251793456789', 'New bill: ETB 12200 due on 2025-06-28 for {{property}}. Bill# BILL-000047', 'pending', NULL, NULL, 0, '2025-04-26 04:39:01', 'manual', '2025-04-25 17:39:01', NULL, 3);

-- --------------------------------------------------------

--
-- Table structure for table `sms_templates`
--

CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `template` text NOT NULL,
  `variables` varchar(255) NOT NULL COMMENT 'Comma-separated variable names',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sms_templates`
--

INSERT INTO `sms_templates` (`id`, `name`, `template`, `variables`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'new_bill_en', 'New bill: ETB {{amount}} due on {{due_date}} for {{property}}. Bill# {{bill_number}}', 'amount,due_date,property,bill_number', 1, '2025-04-18 18:24:00', NULL),
(2, 'new_bill_am', 'አዲስ ቢል: ETB {{amount}} በ{{due_date}} ለ{{property}} የሚከፈል። ቢል# {{bill_number}}', 'amount,due_date,property,bill_number', 1, '2025-04-18 18:24:00', NULL),
(3, 'payment_reminder_en', 'Reminder: ETB {{amount}} due on {{due_date}} for {{property}}. Late fee: ETB {{late_fee}}', 'amount,due_date,property,late_fee', 1, '2025-04-18 18:24:00', NULL),
(4, 'payment_reminder_am', 'አስገንዝብ: ETB {{amount}} በ{{due_date}} ለ{{property}} የሚከፈል። የተቆጣጠር ክፍያ: ETB {{late_fee}}', 'amount,due_date,property,late_fee', 1, '2025-04-18 18:24:00', NULL),
(9, 'payment_confirmation_am', 'ውድ {{full_name}}፣ ለቢል ቁጥር {{bill_number}} ክፍያዎ {{amount}} ብር ተከፍሏል። እናመሰግናለን! ለተጨማሪ ክፍያዎች በቴሌብር (*127#) ይጠቀሙ።', 'full_name,bill_number,amount', 1, '2025-04-20 11:57:18', '2025-04-20 11:57:18'),
(10, 'payment_confirmation_en', 'Dear {{full_name}}, your payment of {{amount}} ETB for Bill {{bill_number}} has been received. Thank you! Use Telebirr (*127#) for future payments.', 'full_name,bill_number,amount', 1, '2025-04-20 11:57:18', '2025-04-20 11:57:18'),
(11, 'thank_you_am', 'ክፍያዎ ተቀባይነት አግኝቷል። ለወራቤ መሬት ሊዝ እናመሰግናለን!', 'none', 1, '2025-04-20 18:00:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `action_date` datetime NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `record_type` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `action_date`, `ip_address`, `user_agent`, `record_id`, `record_type`) VALUES
(1, 1, 'Updated assessment ID 2 status to paid', '2025-05-24 15:08:46', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 OPR/118.0.0.0', 2, 'assessment'),
(2, 1, 'Updated assessment ID 2 status to overdue', '2025-05-24 19:17:30', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 OPR/118.0.0.0', 2, 'assessment'),
(3, 1, 'Updated assessment ID 2 status to paid', '2025-05-24 19:25:40', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 OPR/118.0.0.0', 2, 'assessment'),
(4, 1, 'Created assessment ID 3', '2025-05-24 20:15:02', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 OPR/118.0.0.0', 3, 'tax_assessments'),
(5, 1, 'Recorded payment ID 1 for assessment ID 3', '2025-05-24 21:15:44', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 OPR/118.0.0.0', 1, 'payment'),
(6, 1, 'Auto-updated assessment ID 3 to paid after payment', '2025-05-24 21:15:44', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/133.0.0.0 Safari/537.36 OPR/118.0.0.0', 3, 'tax_assessments');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `is_public`, `updated_by`, `updated_at`) VALUES
(1, 'company_name', 'Werabe Land Lease', 'The company name displayed in the system', 1, NULL, '2025-04-09 14:37:37'),
(2, 'default_currency', 'ETB', 'Default currency for all monetary values', 1, NULL, '2025-04-09 14:37:37'),
(3, 'tax_rate', '0.15', 'Default tax rate (15%)', 1, NULL, '2025-04-09 14:37:37'),
(4, 'late_fee_percentage', '0.05', 'Late payment fee percentage (5%)', 1, NULL, '2025-04-09 14:37:37');

-- --------------------------------------------------------

--
-- Table structure for table `tax_assessments`
--

CREATE TABLE `tax_assessments` (
  `assessment_id` int(11) NOT NULL,
  `contract_id` int(11) DEFAULT NULL,
  `property_id` int(11) DEFAULT NULL,
  `building_id` int(11) DEFAULT NULL,
  `rate_id` int(11) NOT NULL,
  `assessment_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `period_month` tinyint(4) NOT NULL,
  `assessed_value` decimal(12,2) NOT NULL,
  `tax_amount` decimal(12,2) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `is_rental` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tax_assessments`
--

INSERT INTO `tax_assessments` (`assessment_id`, `contract_id`, `property_id`, `building_id`, `rate_id`, `assessment_date`, `due_date`, `period_month`, `assessed_value`, `tax_amount`, `fiscal_year`, `status`, `is_rental`, `notes`) VALUES
(2, NULL, 1, 1, 4, '2025-05-24', NULL, 0, 70000.00, 1050.00, '2025', 'paid', 0, 'tttttttttttt'),
(3, NULL, 2, 2, 4, '2025-05-25', '2025-05-31', 0, 5000000.00, 75000.00, '2025', 'paid', 0, 'ggggggg');

-- --------------------------------------------------------

--
-- Table structure for table `tax_rates`
--

CREATE TABLE `tax_rates` (
  `rate_id` int(11) NOT NULL,
  `rate_name` varchar(100) NOT NULL,
  `rate_description` text DEFAULT NULL,
  `rate_percentage` decimal(5,2) NOT NULL,
  `applicable_to` enum('property','building','rental') NOT NULL,
  `effective_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tax_rates`
--

INSERT INTO `tax_rates` (`rate_id`, `rate_name`, `rate_description`, `rate_percentage`, `applicable_to`, `effective_date`, `expiry_date`) VALUES
(1, 'የአፍር ግብር - Residential', 'Residential property tax rate', 0.50, 'property', '2023-01-01', NULL),
(2, 'የአፍር ግብር - Commercial', 'Commercial property tax rate', 1.00, 'property', '2023-01-01', NULL),
(3, 'የህንጻ ግብር - Residential', 'Residential building tax rate', 0.75, 'building', '2023-01-01', NULL),
(4, 'የህንጻ ግብር - Commercial', 'Commercial building tax rate', 1.50, 'building', '2023-01-01', NULL),
(5, 'የአከራይተከራይ ግብር - Individual', 'Individual rental income tax', 15.00, 'rental', '2023-01-01', NULL),
(6, 'የአከራይተከራይ ግብር - Business', 'Business rental income tax', 30.00, 'rental', '2023-01-01', NULL),
(7, 'የባለቤት የመጀመሪያ ቤት ነፃነት', 'Primary residence exemption', 0.00, 'property', '2023-01-01', NULL),
(8, 'የታሪካዊ ህንጻዎች ተቀናሽ', 'Historical buildings discount', 0.25, 'building', '2023-01-01', '2025-12-31');

-- --------------------------------------------------------

--
-- Table structure for table `unpaid_payments`
--

CREATE TABLE `unpaid_payments` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `interest` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) GENERATED ALWAYS AS (`amount` + `interest`) STORED,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','billing_officer','clerk','viewer') NOT NULL DEFAULT 'clerk',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `login_ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `is_active`, `last_login`, `login_ip`, `created_at`, `updated_at`) VALUES
(1, 'admin_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin@werabe.com', '0912345678', 'admin', 1, '2025-04-16 08:14:34', '::1', '2025-04-11 02:37:36', '2025-04-16 16:14:34'),
(2, 'billing_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Billing Officer', 'billing@werabe.com', '0923456789', 'billing_officer', 1, NULL, NULL, '2025-04-11 02:37:36', '2025-04-11 02:37:36'),
(3, 'clerk_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Clerk User', 'clerk@werabe.com', '0934567890', 'clerk', 1, NULL, NULL, '2025-04-11 02:37:36', '2025-04-11 02:37:36'),
(4, 'viewer_user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Viewer User', 'viewer@werabe.com', '0945678901', 'viewer', 1, NULL, NULL, '2025-04-11 02:37:36', '2025-04-11 02:37:36');

-- --------------------------------------------------------

--
-- Table structure for table `vacancy_reports`
--

CREATE TABLE `vacancy_reports` (
  `id` int(11) NOT NULL,
  `agency_id` int(11) NOT NULL,
  `report_period` date NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `submission_date` datetime NOT NULL,
  `submission_method` enum('api','email','manual') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `status` enum('pending','submitted','failed','verified') DEFAULT 'pending',
  `report_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`report_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `active_leases`
--
DROP TABLE IF EXISTS `active_leases`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_leases`  AS SELECT `a`.`id` AS `id`, `a`.`asset_code` AS `asset_code`, `a`.`property_name` AS `property_name`, `a`.`block` AS `block`, `a`.`kebele` AS `kebele`, `a`.`lot_number` AS `lot_number`, `a`.`owner_id` AS `owner_id`, `a`.`owner_name` AS `owner_name`, `a`.`property_type` AS `property_type`, `a`.`size` AS `size`, `a`.`annual_lease_rate` AS `annual_lease_rate`, `a`.`status` AS `status`, `a`.`registration_date` AS `registration_date`, `a`.`created_at` AS `created_at`, `a`.`updated_at` AS `updated_at` FROM (`assets` `a` join `lease_agreements` `la` on(`a`.`id` = `la`.`asset_id`)) WHERE `a`.`status` in ('active','leased') AND `la`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `bill_history`
--
DROP TABLE IF EXISTS `bill_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `bill_history`  AS SELECT `b`.`id` AS `id`, `b`.`bill_number` AS `bill_number`, `b`.`total_amount` AS `total_amount`, `b`.`billing_date` AS `billing_date`, `b`.`due_date` AS `due_date`, `b`.`status` AS `status`, `o`.`full_name` AS `owner_name`, `a`.`property_name` AS `property_name` FROM (((`bills` `b` join `lease_agreements` `l` on(`b`.`lease_id` = `l`.`id`)) join `assets` `a` on(`l`.`asset_id` = `a`.`id`)) join `owners` `o` on(`a`.`owner_id` = `o`.`id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `outstanding_bills`
--
DROP TABLE IF EXISTS `outstanding_bills`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `outstanding_bills`  AS SELECT `b`.`id` AS `id`, `b`.`bill_number` AS `bill_number`, `b`.`total_amount`+ coalesce(sum(`c`.`amount`),0) AS `total_amount`, `b`.`late_fee` AS `late_fee`, `b`.`tax_amount` AS `tax_amount`, coalesce(sum(`c`.`amount`),0) AS `additional_charges`, `b`.`due_date` AS `due_date`, `b`.`status` AS `status`, `b`.`lease_id` AS `lease_id`, `b`.`owner_id` AS `owner_id` FROM (`bills` `b` left join `charges` `c` on(`b`.`id` = `c`.`bill_id`)) WHERE `b`.`status` in ('pending','overdue') GROUP BY `b`.`id`, `b`.`bill_number`, `b`.`total_amount`, `b`.`late_fee`, `b`.`tax_amount`, `b`.`due_date`, `b`.`status`, `b`.`lease_id`, `b`.`owner_id` ;

-- --------------------------------------------------------

--
-- Structure for view `payment_history`
--
DROP TABLE IF EXISTS `payment_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `payment_history`  AS SELECT `p`.`id` AS `id`, `p`.`payment_number` AS `payment_number`, `o`.`full_name` AS `owner_name`, `a`.`asset_code` AS `property_name`, `p`.`amount` AS `amount`, `p`.`payment_date` AS `payment_date`, `p`.`payment_method` AS `payment_method` FROM ((((`payments` `p` join `bills` `b` on(`p`.`bill_id` = `b`.`id`)) join `lease_agreements` `la` on(`b`.`lease_id` = `la`.`id`)) join `assets` `a` on(`la`.`asset_id` = `a`.`id`)) join `owners` `o` on(`a`.`owner_id` = `o`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_code` (`asset_code`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `status` (`status`),
  ADD KEY `fk_asset_construction_type` (`construction_type_id`),
  ADD KEY `property_type_id` (`property_type_id`);

--
-- Indexes for table `asset_transfers`
--
ALTER TABLE `asset_transfers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `old_owner_id` (`old_owner_id`),
  ADD KEY `new_owner_id` (`new_owner_id`),
  ADD KEY `reason_id` (`reason_id`),
  ADD KEY `transferred_by` (`transferred_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action_type` (`action_type`),
  ADD KEY `table_name` (`table_name`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `bills`
--
ALTER TABLE `bills`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bill_number` (`bill_number`),
  ADD KEY `lease_id` (`lease_id`);

--
-- Indexes for table `bill_files`
--
ALTER TABLE `bill_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `bill_sequence`
--
ALTER TABLE `bill_sequence`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buildings`
--
ALTER TABLE `buildings`
  ADD PRIMARY KEY (`building_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `charges`
--
ALTER TABLE `charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `construction_types`
--
ALTER TABLE `construction_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `government_agencies`
--
ALTER TABLE `government_agencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `government_reports`
--
ALTER TABLE `government_reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `late_fee_rules`
--
ALTER TABLE `late_fee_rules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lease_agreements`
--
ALTER TABLE `lease_agreements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `lease_number` (`lease_number`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `lease_sequence`
--
ALTER TABLE `lease_sequence`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `owners`
--
ALTER TABLE `owners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `id_number` (`id_number`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `assessment_id` (`assessment_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_number` (`payment_number`),
  ADD KEY `bill_id` (`bill_id`);

--
-- Indexes for table `payment_files`
--
ALTER TABLE `payment_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`property_id`),
  ADD UNIQUE KEY `registration_number` (`registration_number`);

--
-- Indexes for table `property_files`
--
ALTER TABLE `property_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`);

--
-- Indexes for table `property_types`
--
ALTER TABLE `property_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `reasons`
--
ALTER TABLE `reasons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `rental_payments`
--
ALTER TABLE `rental_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `contract_id` (`contract_id`);

--
-- Indexes for table `rent_contracts`
--
ALTER TABLE `rent_contracts`
  ADD PRIMARY KEY (`contract_id`),
  ADD KEY `room_id` (`room_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `building_id` (`building_id`),
  ADD KEY `type_id` (`type_id`);

--
-- Indexes for table `room_types`
--
ALTER TABLE `room_types`
  ADD PRIMARY KEY (`type_id`);

--
-- Indexes for table `signatures_stamps`
--
ALTER TABLE `signatures_stamps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `sms_delivery_logs`
--
ALTER TABLE `sms_delivery_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notification` (`notification_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status_senddate` (`status`,`send_date`),
  ADD KEY `idx_recipient` (`recipient_type`,`recipient_id`),
  ADD KEY `idx_template` (`template_id`),
  ADD KEY `idx_bill_id` (`bill_id`),
  ADD KEY `idx_send_date` (`send_date`);

--
-- Indexes for table `sms_templates`
--
ALTER TABLE `sms_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_name` (`name`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action_date` (`action_date`),
  ADD KEY `record` (`record_type`,`record_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `tax_assessments`
--
ALTER TABLE `tax_assessments`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `property_id` (`property_id`),
  ADD KEY `building_id` (`building_id`),
  ADD KEY `rate_id` (`rate_id`),
  ADD KEY `contract_id` (`contract_id`),
  ADD KEY `idx_status_due_date` (`status`,`due_date`);

--
-- Indexes for table `tax_rates`
--
ALTER TABLE `tax_rates`
  ADD PRIMARY KEY (`rate_id`);

--
-- Indexes for table `unpaid_payments`
--
ALTER TABLE `unpaid_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role` (`role`,`is_active`);

--
-- Indexes for table `vacancy_reports`
--
ALTER TABLE `vacancy_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agency_id` (`agency_id`),
  ADD KEY `submitted_by` (`submitted_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `asset_transfers`
--
ALTER TABLE `asset_transfers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `bills`
--
ALTER TABLE `bills`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `bill_files`
--
ALTER TABLE `bill_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `buildings`
--
ALTER TABLE `buildings`
  MODIFY `building_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `charges`
--
ALTER TABLE `charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `construction_types`
--
ALTER TABLE `construction_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `government_agencies`
--
ALTER TABLE `government_agencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `government_reports`
--
ALTER TABLE `government_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `late_fee_rules`
--
ALTER TABLE `late_fee_rules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lease_agreements`
--
ALTER TABLE `lease_agreements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lease_sequence`
--
ALTER TABLE `lease_sequence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `owners`
--
ALTER TABLE `owners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `payment_files`
--
ALTER TABLE `payment_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `property_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `property_files`
--
ALTER TABLE `property_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_types`
--
ALTER TABLE `property_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reasons`
--
ALTER TABLE `reasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `rental_payments`
--
ALTER TABLE `rental_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rent_contracts`
--
ALTER TABLE `rent_contracts`
  MODIFY `contract_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `room_types`
--
ALTER TABLE `room_types`
  MODIFY `type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `signatures_stamps`
--
ALTER TABLE `signatures_stamps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sms_delivery_logs`
--
ALTER TABLE `sms_delivery_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sms_notifications`
--
ALTER TABLE `sms_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `sms_templates`
--
ALTER TABLE `sms_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tax_assessments`
--
ALTER TABLE `tax_assessments`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tax_rates`
--
ALTER TABLE `tax_rates`
  MODIFY `rate_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `unpaid_payments`
--
ALTER TABLE `unpaid_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `vacancy_reports`
--
ALTER TABLE `vacancy_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`),
  ADD CONSTRAINT `fk_asset_construction_type` FOREIGN KEY (`construction_type_id`) REFERENCES `construction_types` (`id`),
  ADD CONSTRAINT `fk_asset_property_type` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`);

--
-- Constraints for table `asset_transfers`
--
ALTER TABLE `asset_transfers`
  ADD CONSTRAINT `asset_transfers_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  ADD CONSTRAINT `asset_transfers_ibfk_2` FOREIGN KEY (`old_owner_id`) REFERENCES `owners` (`id`),
  ADD CONSTRAINT `asset_transfers_ibfk_3` FOREIGN KEY (`new_owner_id`) REFERENCES `owners` (`id`),
  ADD CONSTRAINT `asset_transfers_ibfk_4` FOREIGN KEY (`reason_id`) REFERENCES `reasons` (`id`),
  ADD CONSTRAINT `asset_transfers_ibfk_5` FOREIGN KEY (`transferred_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `bills`
--
ALTER TABLE `bills`
  ADD CONSTRAINT `fk_bill_lease` FOREIGN KEY (`lease_id`) REFERENCES `lease_agreements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bill_files`
--
ALTER TABLE `bill_files`
  ADD CONSTRAINT `bill_files_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `buildings`
--
ALTER TABLE `buildings`
  ADD CONSTRAINT `buildings_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`);

--
-- Constraints for table `charges`
--
ALTER TABLE `charges`
  ADD CONSTRAINT `charges_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lease_agreements`
--
ALTER TABLE `lease_agreements`
  ADD CONSTRAINT `fk_lease_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `tax_assessments` (`assessment_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_files`
--
ALTER TABLE `payment_files`
  ADD CONSTRAINT `payment_files_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `property_files`
--
ALTER TABLE `property_files`
  ADD CONSTRAINT `property_files_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `rental_payments`
--
ALTER TABLE `rental_payments`
  ADD CONSTRAINT `rental_payments_ibfk_1` FOREIGN KEY (`contract_id`) REFERENCES `rent_contracts` (`contract_id`);

--
-- Constraints for table `rent_contracts`
--
ALTER TABLE `rent_contracts`
  ADD CONSTRAINT `rent_contracts_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`);

--
-- Constraints for table `rooms`
--
ALTER TABLE `rooms`
  ADD CONSTRAINT `rooms_ibfk_1` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`),
  ADD CONSTRAINT `rooms_ibfk_2` FOREIGN KEY (`type_id`) REFERENCES `room_types` (`type_id`);

--
-- Constraints for table `tax_assessments`
--
ALTER TABLE `tax_assessments`
  ADD CONSTRAINT `tax_assessments_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`property_id`),
  ADD CONSTRAINT `tax_assessments_ibfk_2` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`building_id`),
  ADD CONSTRAINT `tax_assessments_ibfk_3` FOREIGN KEY (`rate_id`) REFERENCES `tax_rates` (`rate_id`),
  ADD CONSTRAINT `tax_assessments_ibfk_4` FOREIGN KEY (`contract_id`) REFERENCES `rent_contracts` (`contract_id`) ON DELETE CASCADE;

--
-- Constraints for table `vacancy_reports`
--
ALTER TABLE `vacancy_reports`
  ADD CONSTRAINT `vacancy_reports_ibfk_1` FOREIGN KEY (`agency_id`) REFERENCES `government_agencies` (`id`),
  ADD CONSTRAINT `vacancy_reports_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
