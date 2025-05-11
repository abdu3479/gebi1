-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: aziyan
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Temporary table structure for view `active_leases`
--

DROP TABLE IF EXISTS `active_leases`;
/*!50001 DROP VIEW IF EXISTS `active_leases`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `active_leases` AS SELECT
 1 AS `id`,
  1 AS `asset_code`,
  1 AS `property_name`,
  1 AS `block`,
  1 AS `kebele`,
  1 AS `lot_number`,
  1 AS `owner_id`,
  1 AS `owner_name`,
  1 AS `property_type`,
  1 AS `size`,
  1 AS `annual_lease_rate`,
  1 AS `status`,
  1 AS `registration_date`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `asset_transfers`
--

DROP TABLE IF EXISTS `asset_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_transfers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `old_owner_id` int(11) DEFAULT NULL,
  `new_owner_id` int(11) NOT NULL,
  `reason_id` int(11) DEFAULT NULL,
  `transferred_by` int(11) NOT NULL,
  `transferred_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `old_owner_id` (`old_owner_id`),
  KEY `new_owner_id` (`new_owner_id`),
  KEY `reason_id` (`reason_id`),
  KEY `transferred_by` (`transferred_by`),
  CONSTRAINT `asset_transfers_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`),
  CONSTRAINT `asset_transfers_ibfk_2` FOREIGN KEY (`old_owner_id`) REFERENCES `owners` (`id`),
  CONSTRAINT `asset_transfers_ibfk_3` FOREIGN KEY (`new_owner_id`) REFERENCES `owners` (`id`),
  CONSTRAINT `asset_transfers_ibfk_4` FOREIGN KEY (`reason_id`) REFERENCES `reasons` (`id`),
  CONSTRAINT `asset_transfers_ibfk_5` FOREIGN KEY (`transferred_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_transfers`
--

LOCK TABLES `asset_transfers` WRITE;
/*!40000 ALTER TABLE `asset_transfers` DISABLE KEYS */;
INSERT INTO `asset_transfers` VALUES (1,1,1,61,2,1,'2025-04-17 16:00:34'),(2,1,61,44,1,1,'2025-04-17 16:01:33'),(3,2,2,41,2,1,'2025-04-17 16:54:54'),(4,1,44,42,3,1,'2025-04-17 16:57:13'),(5,3,3,4,2,1,'2025-04-17 17:04:51');
/*!40000 ALTER TABLE `asset_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `document_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `owner_id` (`owner_id`),
  KEY `status` (`status`),
  KEY `fk_asset_construction_type` (`construction_type_id`),
  KEY `property_type_id` (`property_type_id`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`),
  CONSTRAINT `fk_asset_construction_type` FOREIGN KEY (`construction_type_id`) REFERENCES `construction_types` (`id`),
  CONSTRAINT `fk_asset_property_type` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (1,'AST-001','Abebe Residence','Block A','Kebele 01','L001',42,'አቦበከር  ሸምሱ',1,1,'123 Main St, Addis Ababa',500.00,1500.00,'Leased','2023-01-15',NULL,'REG-001','TIN-001',1,1,0,'2025-04-11 02:37:36','2025-04-17 16:57:13','',NULL),(2,'AST-002','Mulugeta Shop','Block B','02','LOT-002',41,'ተማም ኢብራሃም',1,6,'456 Market Rd, Addis Ababa',200.00,1200.00,'Leased','2023-02-20',NULL,'REG-002','TIN-002',1,0,1,'2025-04-11 02:37:36','2025-04-17 16:54:54','',NULL),(3,'AST-003','Genet Farm','Block C','03','LOT-003',4,'ራህስ ተሾሙ',1,NULL,'789 Rural Area, Addis Ababa',500.00,1800.00,'Leased','2023-03-10',NULL,'REG-003','TIN-003',0,1,0,'2025-04-11 02:37:36','2025-04-17 17:04:51','',NULL),(4,'AST-004','Solomon Office','Block A','01','LOT-004',4,'Solomon Desta',1,2,'101 Business Park, Addis Ababa',180.75,1600.00,'Inactive','2023-04-05',NULL,'REG-004','TIN-004',1,1,1,'2025-04-11 02:37:36','2025-04-12 14:13:53','',NULL),(5,'AST-005','Tigist House','Block D','04','LOT-005',5,'Tigist Lemma',1,3,'202 Residential Area, Addis Ababa',120.25,1700.00,'Leased','2023-05-12',NULL,'REG-005','TIN-005',1,0,0,'2025-04-11 02:37:36','2025-04-11 21:26:56','',NULL),(10,'as12','asas','blo001','01','plo10',44,'',4,7,'duna',250.00,3000.00,'Active','2025-04-17','0','r121','121212',1,0,0,'2025-04-17 04:54:26','2025-04-17 04:54:26','',NULL),(12,'as23','RR','b34','02','pl19',3,'ሙሉቀን ወልደሚካኤል',4,7,'shdager',300.00,4000.00,'Active','2025-04-18','0','34','1000123',1,0,0,'2025-04-17 22:58:40','2025-04-17 22:58:40','','');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `error_message` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action_type` (`action_type`),
  KEY `table_name` (`table_name`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'create','owners',62,NULL,'{\"first_name\":\"abdu\",\"last_name\":\"seraj\",\"email\":\"mameserag@gmail.com\",\"phone\":\"+251916273479\",\"id_number\":\"0001\",\"address\":\"werabe\\r\\nb1\",\"status\":\"active\",\"is_active\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 02:04:32',NULL),(2,1,'create','owners',62,NULL,'{\"first_name\":\"abdu\",\"last_name\":\"seraj\",\"email\":\"mameserag@gmail.com\",\"phone\":\"+251916273479\",\"id_number\":\"0001\",\"address\":\"werabe\r\nb1\",\"status\":\"active\",\"is_active\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 02:04:32',NULL),(3,1,'create','assets',10,NULL,'{\"asset_code\":\"as12\",\"property_name\":\"asas\",\"block\":\"blo001\",\"kebele\":\"01\",\"lot_number\":\"plo10\",\"owner_id\":44,\"owner_name\":\"\",\"property_type_id\":4,\"construction_type_id\":7,\"address\":\"duna\",\"size\":250,\"annual_lease_rate\":3000,\"status\":\"Active\",\"registration_date\":\"2025-04-17\",\"construction_type\":\"stone\",\"registration_number\":\"r121\",\"tax_identification_number\":\"121212\",\"has_electricity\":1,\"has_water\":0,\"has_sewage\":0}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 04:54:26',NULL),(4,1,'transfer','assets',1,NULL,'{\"transfer_id\":1,\"new_owner_id\":61,\"reason_id\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 16:00:34',NULL),(5,1,'transfer','assets',1,NULL,'{\"transfer_id\":2,\"new_owner_id\":44,\"reason_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 16:01:34',NULL),(6,1,'transfer','assets',2,NULL,'{\"transfer_id\":3,\"new_owner_id\":41,\"reason_id\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 16:54:54',NULL),(7,1,'transfer','assets',1,NULL,'{\"transfer_id\":4,\"new_owner_id\":42,\"reason_id\":3}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 16:57:13',NULL),(8,1,'transfer','assets',3,NULL,'{\"transfer_id\":5,\"new_owner_id\":4,\"reason_id\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 17:04:51',NULL),(9,1,'create','assets',12,NULL,'{\"asset_code\":\"as23\",\"property_name\":\"RR\",\"block\":\"b34\",\"kebele\":\"02\",\"lot_number\":\"pl19\",\"owner_id\":3,\"owner_name\":\"ሙሉቀን ወልደሚካኤል\",\"property_type_id\":4,\"construction_type_id\":7,\"address\":\"shdager\",\"size\":300,\"annual_lease_rate\":4000,\"status\":\"Active\",\"registration_date\":\"2025-04-18\",\"construction_type\":\"ከከከከከከከከከከከከከከከ\",\"registration_number\":\"34\",\"tax_identification_number\":\"1000123\",\"document_path\":\"\",\"has_electricity\":1,\"has_water\":0,\"has_sewage\":0}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-17 22:58:40',NULL),(10,1,'create','bills',NULL,NULL,'{\"bill_number\":\"BILL-000007\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":500,\"total_amount\":4500.15,\"created_by\":1,\"status\":\"pending\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-19 03:54:44','Failed to create bill'),(11,1,'create','bills',NULL,NULL,'{\"bill_number\":\"BILL-000008\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":500,\"total_amount\":4500.15,\"created_by\":1,\"status\":\"pending\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-19 03:58:03','Failed to create bill'),(12,1,'create','bills',NULL,NULL,'{\"bill_number\":\"BILL-000009\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":0,\"total_amount\":4000.15,\"created_by\":1,\"status\":\"pending\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-19 04:02:09','Failed to create bill'),(13,1,'create','bills',NULL,NULL,'{\"bill_number\":\"BILL-000010\",\"lease_id\":2,\"billing_date\":\"2025-04-19\",\"due_date\":\"2025-05-19\",\"base_amount\":4000,\"tax_amount\":0.15,\"late_fee\":0,\"total_amount\":4000.15,\"created_by\":1,\"status\":\"pending\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/132.0.0.0 Safari/537.36 OPR/117.0.0.0','2025-04-19 04:07:45',NULL);
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bill_files`
--

DROP TABLE IF EXISTS `bill_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bill_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `bill_files_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bill_files`
--

LOCK TABLES `bill_files` WRITE;
/*!40000 ALTER TABLE `bill_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `bill_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `bill_history`
--

DROP TABLE IF EXISTS `bill_history`;
/*!50001 DROP VIEW IF EXISTS `bill_history`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `bill_history` AS SELECT
 1 AS `id`,
  1 AS `bill_number`,
  1 AS `total_amount`,
  1 AS `billing_date`,
  1 AS `due_date`,
  1 AS `status`,
  1 AS `owner_name`,
  1 AS `property_name` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `bills`
--

DROP TABLE IF EXISTS `bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lease_id` int(11) NOT NULL,
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
  PRIMARY KEY (`id`),
  UNIQUE KEY `bill_number` (`bill_number`),
  KEY `lease_id` (`lease_id`),
  CONSTRAINT `fk_bill_lease` FOREIGN KEY (`lease_id`) REFERENCES `lease_agreements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bills`
--

LOCK TABLES `bills` WRITE;
/*!40000 ALTER TABLE `bills` DISABLE KEYS */;
INSERT INTO `bills` VALUES (1,1,'BILL-001','2025-02-01','2025-03-01',1500.00,150.00,75.00,1725.00,'overdue','Annual lease payment',1,'2025-04-11 02:37:36','2025-04-13 17:10:10',0,0.00),(2,2,'BILL-002','2025-01-01','2025-02-01',1200.00,120.00,60.00,1380.00,'overdue','Annual lease payment',1,'2025-04-11 02:37:36','2025-04-11 02:37:36',0,0.00),(3,3,'BILL-003','2025-02-01','2025-03-01',1800.00,180.00,90.00,2070.00,'overdue','Annual lease payment',1,'2025-04-11 02:37:36','2025-04-13 17:10:10',0,0.00),(4,4,'BILL-004','2025-02-01','2025-03-01',1600.00,160.00,80.00,1840.00,'overdue','Annual lease payment',1,'2025-04-11 02:37:36','2025-04-13 17:10:10',0,0.00),(5,5,'BILL-005','2025-02-01','2025-03-01',1700.00,170.00,85.00,1955.00,'overdue','Annual lease payment',1,'2025-04-11 02:37:36','2025-04-13 17:10:10',0,0.00),(14,2,'BILL-000006','2025-04-19','2025-05-19',4000.00,500.00,0.15,4500.15,'pending','dddddddddd',1,'2025-04-19 03:49:33','2025-04-19 03:49:33',0,0.00),(16,2,'BILL-000007','2025-04-19','2025-05-19',4000.00,500.00,0.15,4500.15,'pending','dddddddddd',1,'2025-04-19 03:54:44','2025-04-19 03:54:44',0,0.00),(18,2,'BILL-000008','2025-04-19','2025-05-19',4000.00,500.00,0.15,4500.15,'pending','dddddddddd',1,'2025-04-19 03:58:02','2025-04-19 03:58:02',0,0.00),(20,2,'BILL-000009','2025-04-19','2025-05-19',4000.00,0.00,0.15,4000.15,'pending','dddddddddd',1,'2025-04-19 04:02:08','2025-04-19 04:02:08',0,0.00),(22,2,'BILL-000010','2025-04-19','2025-05-19',4000.00,0.00,0.15,4000.15,'pending','dddddddddd',1,'2025-04-19 04:07:45','2025-04-19 04:07:45',0,0.00);
/*!40000 ALTER TABLE `bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `charges`
--

DROP TABLE IF EXISTS `charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bill_id` int(11) NOT NULL,
  `charge_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `charges_ibfk_1` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `charges`
--

LOCK TABLES `charges` WRITE;
/*!40000 ALTER TABLE `charges` DISABLE KEYS */;
INSERT INTO `charges` VALUES (1,1,'penalty',100.00,'Late payment penalty','2025-04-17 19:33:47'),(2,1,'penalty',2000.00,'you are very late','2025-04-17 19:59:21');
/*!40000 ALTER TABLE `charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `construction_types`
--

DROP TABLE IF EXISTS `construction_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `construction_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `durability_rating` enum('Low','Medium','High','Very High') DEFAULT 'Medium',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `construction_types`
--

LOCK TABLES `construction_types` WRITE;
/*!40000 ALTER TABLE `construction_types` DISABLE KEYS */;
INSERT INTO `construction_types` VALUES (1,'Concrete','Reinforced concrete construction','High','2025-04-11 21:26:31','2025-04-11 21:26:31'),(2,'Steel','Steel frame construction','Very High','2025-04-11 21:26:31','2025-04-11 21:26:31'),(3,'Wood','Timber frame construction','Medium','2025-04-11 21:26:31','2025-04-11 21:26:31'),(4,'Mud','Traditional mud construction','Low','2025-04-11 21:26:31','2025-04-11 21:26:31'),(5,'Stone','Natural stone construction','High','2025-04-11 21:26:31','2025-04-11 21:26:31'),(6,'Brick','Brick masonry construction','Medium','2025-04-11 21:26:31','2025-04-11 21:26:31'),(7,'Composite','Mixed construction materials','High','2025-04-11 21:26:31','2025-04-11 21:26:31'),(8,'Prefabricated','Factory-made components','Medium','2025-04-11 21:26:31','2025-04-11 21:26:31');
/*!40000 ALTER TABLE `construction_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `late_fee_rules`
--

DROP TABLE IF EXISTS `late_fee_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `late_fee_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `grace_period_days` int(11) NOT NULL,
  `fee_type` enum('fixed','percentage') NOT NULL,
  `fee_value` decimal(10,2) NOT NULL,
  `max_fee` decimal(15,2) DEFAULT NULL,
  `sms_reminder_days` int(11) NOT NULL DEFAULT 3,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `late_fee_rules`
--

LOCK TABLES `late_fee_rules` WRITE;
/*!40000 ALTER TABLE `late_fee_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `late_fee_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lease_agreements`
--

DROP TABLE IF EXISTS `lease_agreements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lease_agreements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `lease_term_of_years` int(11) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `lease_number` (`lease_number`),
  KEY `asset_id` (`asset_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `fk_lease_asset` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lease_agreements`
--

LOCK TABLES `lease_agreements` WRITE;
/*!40000 ALTER TABLE `lease_agreements` DISABLE KEYS */;
INSERT INTO `lease_agreements` VALUES (1,1,1,'LEASE-001','2023-01-01','2025-12-31',125.00,1500.00,'monthly','active','Standard lease terms',1,'2025-04-11 02:37:36','2025-04-13 17:09:27',3),(2,2,2,'LEASE-002','2024-01-01','2025-12-31',100.00,1800.00,'monthly','active','Standard lease terms',1,'2025-04-11 02:37:36','2025-04-13 17:09:27',2),(3,3,3,'LEASE-003','2023-06-01','2024-06-01',150.00,1200.00,'monthly','active','Standard lease terms',1,'2025-04-11 02:37:36','2025-04-13 17:09:27',2),(4,4,4,'LEASE-004','2024-03-01','2025-03-01',133.33,1600.00,'monthly','terminated','Standard lease terms',1,'2025-04-11 02:37:36','2025-04-13 17:09:27',1),(5,5,5,'LEASE-005','2024-05-01','2025-05-01',141.67,1700.00,'monthly','expired','Standard lease terms',1,'2025-04-11 02:37:36','2025-04-13 17:09:27',1);
/*!40000 ALTER TABLE `lease_agreements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `outstanding_bills`
--

DROP TABLE IF EXISTS `outstanding_bills`;
/*!50001 DROP VIEW IF EXISTS `outstanding_bills`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `outstanding_bills` AS SELECT
 1 AS `id`,
  1 AS `bill_number`,
  1 AS `total_amount`,
  1 AS `late_fee`,
  1 AS `tax_amount`,
  1 AS `additional_charges` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `owners`
--

DROP TABLE IF EXISTS `owners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `owners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `id_number` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(201) GENERATED ALWAYS AS (concat(`first_name`,' ',`last_name`)) STORED,
  `photo` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `id_number` (`id_number`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `owners`
--

LOCK TABLES `owners` WRITE;
/*!40000 ALTER TABLE `owners` DISABLE KEYS */;
INSERT INTO `owners` VALUES (1,'የማነ','ተካልይ','yamane@example.com','ID-001','0911223344','','2025-04-09 19:24:33','የማነ ተካልይ',NULL,'active',1),(2,'ሰማይነህ','መኮንን','samay@example.com','ID-002','0922334455','','2025-04-09 19:24:33','ሰማይነህ መኮንን',NULL,'active',1),(3,'ሙሉቀን','ወልደሚካኤል','muluken@example.com','ID-003','0933445566','','2025-04-09 19:24:33','ሙሉቀን ወልደሚካኤል',NULL,'active',1),(4,'ራህስ','ተሾሙ','rahis@example.com','ID-004','0944556677','','2025-04-09 19:24:33','ራህስ ተሾሙ',NULL,'active',1),(5,'አስራስ','ገብረሥላሴ','asrat@example.com','ID-005','0955667788','','2025-04-09 19:24:33','አስራስ ገብረሥላሴ',NULL,'active',1),(6,'ብርሃኔ','ወልደሚካኤል','birhane@example.com','ID-006','0966778899','','2025-04-09 19:24:33','ብርሃኔ ወልደሚካኤል',NULL,'active',1),(7,'ሰለሞን','ተካልይ','selomon@example.com',NULL,'0977889900','','2025-04-09 19:24:33','ሰለሞን ተካልይ',NULL,'active',1),(8,'አዲስ','መኮንን','addis@example.com',NULL,'0988990011','','2025-04-09 19:24:33','አዲስ መኮንን',NULL,'active',1),(9,'ሀይለማርያም','ተሾሙ','hailu@example.com',NULL,'0999001122','','2025-04-09 19:24:33','ሀይለማርያም ተሾሙ',NULL,'active',1),(10,'ሚካኤል','ወልደሚካኤል','miki@example.com',NULL,'0910011223','','2025-04-09 19:24:33','ሚካኤል ወልደሚካኤል',NULL,'active',1),(41,'ተማም','ኢብራሃም','yamane@example1.com',NULL,'0911223344','','2025-04-09 20:24:00','ተማም ኢብራሃም',NULL,'active',1),(42,'አቦበከር',' ሸምሱ','samay@example2.com',NULL,'0922334455','','2025-04-09 20:24:00','አቦበከር  ሸምሱ',NULL,'active',1),(43,'ጀማል','ወልደሚካኤል','muluken@example3.com',NULL,'0933445566','','2025-04-09 20:24:00','ጀማል ወልደሚካኤል',NULL,'active',1),(44,' ለይላ','ተሾሙ','rahis@example3.com',NULL,'0944556677','','2025-04-09 20:24:00',' ለይላ ተሾሙ',NULL,'active',1),(45,'አስራስ','ዘይኒ','asrat@example6.com',NULL,'0955667788','','2025-04-09 20:24:00','አስራስ ዘይኒ',NULL,'active',1),(46,'ብርሃኔ','ወልደሚካኤል','birhane@example7.com',NULL,'0966778899','','2025-04-09 20:24:00','ብርሃኔ ወልደሚካኤል',NULL,'active',1),(47,' ኢማኖ','ተካልይ','selomon@example4.com',NULL,'0977889900','','2025-04-09 20:24:00',' ኢማኖ ተካልይ',NULL,'active',1),(48,'አዲስ','መኮንን','addis@example9.com',NULL,'0988990011','','2025-04-09 20:24:00','አዲስ መኮንን',NULL,'active',1),(49,'ሀይለማርያም','ተሾሙ','hailu@example6.com',NULL,'0999001122','','2025-04-09 20:24:00','ሀይለማርያም ተሾሙ',NULL,'active',1),(50,'ሚካኤል','ወልደሚካኤል','miki@example0.com',NULL,'0910011223','','2025-04-09 20:24:00','ሚካኤል ወልደሚካኤል',NULL,'active',1),(61,'abdu','seraje','abdu@gmail.com','004','0916273479','werabe','2025-04-13 20:22:11','abdu seraje',NULL,'active',1),(62,'abdu','seraj','mameserag@gmail.com','0001','+251916273479','werabe\r\nb1','2025-04-17 02:04:32','abdu seraj',NULL,'active',1),(69,'Abebe','Kebede',NULL,NULL,'+251912345678',NULL,'2025-04-18 01:08:45','Abebe Kebede',NULL,'active',1);
/*!40000 ALTER TABLE `owners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_files`
--

DROP TABLE IF EXISTS `payment_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `payment_id` (`payment_id`),
  CONSTRAINT `payment_files_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_files`
--

LOCK TABLES `payment_files` WRITE;
/*!40000 ALTER TABLE `payment_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `payment_history`
--

DROP TABLE IF EXISTS `payment_history`;
/*!50001 DROP VIEW IF EXISTS `payment_history`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `payment_history` AS SELECT
 1 AS `id`,
  1 AS `payment_number`,
  1 AS `owner_name`,
  1 AS `property_name`,
  1 AS `amount`,
  1 AS `payment_date`,
  1 AS `payment_method` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `bill_id` (`bill_id`),
  CONSTRAINT `fk_payment_bill` FOREIGN KEY (`bill_id`) REFERENCES `bills` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,0,1,'PAY-20250410-1234','2025-03-01','2025-03-01',1,500.00,'bank_transfer','pending','REF-001','TXN-12345',NULL,'Payment for lease renewal',1,'2025-04-11 02:37:36','2025-04-19 15:56:05',NULL),(2,0,2,'PAY-20250410-2345','2025-03-05','2025-02-01',1,400.00,'mobile_money','pending','REF-002',NULL,NULL,NULL,1,'2025-04-11 02:37:36','2025-04-19 15:56:05',NULL),(3,0,3,'PAY-20250410-3456','2025-03-10','2025-03-01',1,600.00,'telebirr','pending','REF-003',NULL,NULL,NULL,1,'2025-04-11 02:37:36','2025-04-19 15:56:05',NULL),(4,0,4,'PAY-20250410-4567','2025-03-15','2025-03-01',1,300.00,'cash','pending',NULL,NULL,NULL,NULL,1,'2025-04-11 02:37:36','2025-04-19 15:56:05',NULL),(5,0,5,'PAY-20250410-5678','2025-03-20','2025-03-01',1,200.00,'check','pending','REF-005',NULL,NULL,NULL,1,'2025-04-11 02:37:36','2025-04-19 15:56:05',NULL),(6,0,1,'PAY-001','2023-02-15','2025-03-01',1,125.00,'cash','pending',NULL,NULL,NULL,NULL,1,'2025-04-11 02:37:36','2025-04-13 12:28:21',NULL),(7,0,2,'PAY-002','2023-03-15','2025-02-01',1,125.00,'bank_transfer','pending','REF-001',NULL,NULL,NULL,1,'2025-04-11 02:37:36','2025-04-13 12:28:19',NULL),(8,0,3,'PAY-003','2023-03-15','2025-03-01',1,100.00,'mobile_money','pending','REF-002',NULL,NULL,NULL,1,'2025-04-11 02:37:36','2025-04-13 12:28:20',NULL);
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `properties`
--

DROP TABLE IF EXISTS `properties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `property_name` varchar(255) NOT NULL,
  `asset_code` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `property_type` varchar(100) NOT NULL,
  `size` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `kebele` varchar(100) DEFAULT NULL,
  `block` varchar(50) DEFAULT NULL,
  `lot_number` varchar(50) DEFAULT NULL,
  `annual_lease_rate` decimal(12,2) DEFAULT NULL COMMENT 'in ETB',
  `registration_date` date DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `zone` varchar(50) DEFAULT NULL,
  `sub_city` varchar(50) DEFAULT NULL,
  `woreda` varchar(50) DEFAULT NULL,
  `gps_coordinates` varchar(100) DEFAULT NULL,
  `construction_type` enum('Concrete','Wood','Mud','Stone','Steel','Mixed') DEFAULT NULL,
  `construction_year` year(4) DEFAULT NULL,
  `floor_count` tinyint(4) DEFAULT NULL,
  `room_count` smallint(6) DEFAULT NULL,
  `has_electricity` tinyint(1) DEFAULT 0,
  `has_water` tinyint(1) DEFAULT 0,
  `has_sewage` tinyint(1) DEFAULT 0,
  `status` enum('Active','Inactive','Under Maintenance','Leased','Sold','Confiscated') DEFAULT 'Active',
  `tax_identification_number` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_code` (`asset_code`),
  KEY `owner_id` (`owner_id`),
  KEY `property_type` (`property_type`),
  KEY `status` (`status`),
  KEY `idx_lot_number` (`lot_number`)
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `properties`
--

LOCK TABLES `properties` WRITE;
/*!40000 ALTER TABLE `properties` DISABLE KEYS */;
INSERT INTO `properties` VALUES (234,38,'','17','','',260.00,'2025-04-09 20:49:40','260.00','WC4','2730',2900.00,'2013-02-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(235,39,'','20','','',250.00,'2025-04-09 20:49:40','250.00','RU2','270',7917.14,'2015-02-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(236,40,'','15','','',260.00,'2025-04-09 20:49:40','260.00','B3','2046',7610.00,'2015-02-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(237,41,'','3','','',220.00,'2025-04-09 20:49:40','220.00','ZBH5','3',17621.87,'2016-02-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(238,42,'','8','','',260.00,'2025-04-09 20:49:40','260.00','B3','29',7611.85,'2015-03-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(239,43,'','5','','',260.00,'2025-04-09 20:49:40','260.00','B3','2006',7600.00,'2015-03-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(240,45,'','6','','',250.00,'2025-04-09 20:49:40','250.00','AU2','3',7298.20,'2015-03-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(241,36,'','1','','',18.00,'2025-04-09 20:49:40','18.00','B015','3009',17396.00,'2016-03-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(242,40,'','28','','',250.00,'2025-04-09 20:49:40','250.00','RU2','3',17250.00,'2016-03-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(243,41,'','14','','',210.00,'2025-04-09 20:49:40','210.00','BGBL2','29',7900.00,'2015-04-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(244,42,'','16','','',260.00,'2025-04-09 20:49:40','260.00','SB8','3',17110.00,'2016-04-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(245,43,'','7','','',219.80,'2025-04-09 20:49:40','219.80','BG16','3',17006.25,'2016-04-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(246,44,'','13','','',260.00,'2025-04-09 20:49:40','260.00','B3','266',8543.97,'2015-04-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(247,36,'','10','','',66.00,'2025-04-09 20:49:40','66.00','WG4','3',6850.00,'2015-05-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(248,37,'','12','','',220.00,'2025-04-09 20:49:40','220.00','ZBH10','3',16736.75,'2016-05-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(249,38,'','19','','',NULL,'2025-04-09 20:49:40',NULL,'RU3','29',4265.25,'2014-06-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(250,39,'','2','','',260.00,'2025-04-09 20:49:40','260.00','WB4','3',16131.25,'2016-06-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(251,40,'','61','','',282.00,'2025-04-09 20:49:40','282.00','ZBH3','3009',15958.89,'2016-06-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(252,41,'','4','','',210.00,'2025-04-09 20:49:40','210.00','BG14','3',15906.25,'2016-06-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(253,42,'','136','','',66.00,'2025-04-09 20:49:40','66.00','ZBH16','3',12663.26,'2016-06-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL),(254,43,'','22','','',260.00,'2025-04-09 20:49:40','260.00','B3','26',15687.00,'2016-06-01',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,0,'Active',NULL,1,NULL,'2025-04-09 20:49:40',NULL);
/*!40000 ALTER TABLE `properties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_files`
--

DROP TABLE IF EXISTS `property_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  CONSTRAINT `property_files_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_files`
--

LOCK TABLES `property_files` WRITE;
/*!40000 ALTER TABLE `property_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `property_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `property_types`
--

DROP TABLE IF EXISTS `property_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `property_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `property_types`
--

LOCK TABLES `property_types` WRITE;
/*!40000 ALTER TABLE `property_types` DISABLE KEYS */;
INSERT INTO `property_types` VALUES (1,'Residential','Properties used for living purposes','2025-04-11 21:26:02','2025-04-11 21:26:02'),(2,'Commercial','Properties used for business activities','2025-04-11 21:26:02','2025-04-11 21:26:02'),(3,'Agricultural','Properties used for farming and agriculture','2025-04-11 21:26:02','2025-04-11 21:26:02'),(4,'Industrial','Properties used for manufacturing and production','2025-04-11 21:26:02','2025-04-11 21:26:02'),(5,'Mixed-Use','Properties with both residential and commercial uses','2025-04-11 21:26:02','2025-04-11 21:26:02');
/*!40000 ALTER TABLE `property_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reasons`
--

DROP TABLE IF EXISTS `reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reasons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reasons`
--

LOCK TABLES `reasons` WRITE;
/*!40000 ALTER TABLE `reasons` DISABLE KEYS */;
INSERT INTO `reasons` VALUES (1,'Sale',1,'2025-04-17 05:06:12'),(2,'Inheritance',1,'2025-04-17 05:06:12'),(3,'Administrative Correction',1,'2025-04-17 05:06:12'),(4,'Gift',0,'2025-04-17 05:06:12');
/*!40000 ALTER TABLE `reasons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_delivery_logs`
--

DROP TABLE IF EXISTS `sms_delivery_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_delivery_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `status` enum('sent','delivered','failed') NOT NULL,
  `response` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notification` (`notification_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_delivery_logs`
--

LOCK TABLES `sms_delivery_logs` WRITE;
/*!40000 ALTER TABLE `sms_delivery_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_delivery_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_notifications`
--

DROP TABLE IF EXISTS `sms_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `recipient_type` enum('owner','tenant','other') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL COMMENT 'ID of owner/tenant',
  `phone_number` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `status` enum('pending','sent','failed','delivered') NOT NULL DEFAULT 'pending',
  `message_id` varchar(100) DEFAULT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `attempts` tinyint(3) NOT NULL DEFAULT 0,
  `send_date` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status_senddate` (`status`,`send_date`),
  KEY `idx_recipient` (`recipient_type`,`recipient_id`),
  KEY `idx_template` (`template_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_notifications`
--

LOCK TABLES `sms_notifications` WRITE;
/*!40000 ALTER TABLE `sms_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_templates`
--

DROP TABLE IF EXISTS `sms_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sms_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `template` text NOT NULL,
  `variables` varchar(255) NOT NULL COMMENT 'Comma-separated variable names',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_templates`
--

LOCK TABLES `sms_templates` WRITE;
/*!40000 ALTER TABLE `sms_templates` DISABLE KEYS */;
INSERT INTO `sms_templates` VALUES (1,'new_bill_en','New bill: ETB {{amount}} due on {{due_date}} for {{property}}. Bill# {{bill_number}}','amount,due_date,property,bill_number',1,'2025-04-18 18:24:00',NULL),(2,'new_bill_am','አዲስ ቢል: ETB {{amount}} በ{{due_date}} ለ{{property}} የሚከፈል። ቢል# {{bill_number}}','amount,due_date,property,bill_number',1,'2025-04-18 18:24:00',NULL),(3,'payment_reminder_en','Reminder: ETB {{amount}} due on {{due_date}} for {{property}}. Late fee: ETB {{late_fee}}','amount,due_date,property,late_fee',1,'2025-04-18 18:24:00',NULL),(4,'payment_reminder_am','አስገንዝብ: ETB {{amount}} በ{{due_date}} ለ{{property}} የሚከፈል። የተቆጣጠር ክፍያ: ETB {{late_fee}}','amount,due_date,property,late_fee',1,'2025-04-18 18:24:00',NULL);
/*!40000 ALTER TABLE `sms_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `updated_by` (`updated_by`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'company_name','Werabe Land Lease','The company name displayed in the system',1,NULL,'2025-04-09 14:37:37'),(2,'default_currency','ETB','Default currency for all monetary values',1,NULL,'2025-04-09 14:37:37'),(3,'tax_rate','0.15','Default tax rate (15%)',1,NULL,'2025-04-09 14:37:37'),(4,'late_fee_percentage','0.05','Late payment fee percentage (5%)',1,NULL,'2025-04-09 14:37:37');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unpaid_payments`
--

DROP TABLE IF EXISTS `unpaid_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unpaid_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `interest` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) GENERATED ALWAYS AS (`amount` + `interest`) STORED,
  `status` enum('pending','paid','overdue') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unpaid_payments`
--

LOCK TABLES `unpaid_payments` WRITE;
/*!40000 ALTER TABLE `unpaid_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `unpaid_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role` (`role`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin_user','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Admin User','admin@werabe.com','0912345678','admin',1,'2025-04-16 08:14:34','::1','2025-04-11 02:37:36','2025-04-16 16:14:34'),(2,'billing_user','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Billing Officer','billing@werabe.com','0923456789','billing_officer',1,NULL,NULL,'2025-04-11 02:37:36','2025-04-11 02:37:36'),(3,'clerk_user','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Clerk User','clerk@werabe.com','0934567890','clerk',1,NULL,NULL,'2025-04-11 02:37:36','2025-04-11 02:37:36'),(4,'viewer_user','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Viewer User','viewer@werabe.com','0945678901','viewer',1,NULL,NULL,'2025-04-11 02:37:36','2025-04-11 02:37:36');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Final view structure for view `active_leases`
--

/*!50001 DROP VIEW IF EXISTS `active_leases`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `active_leases` AS select `a`.`id` AS `id`,`a`.`asset_code` AS `asset_code`,`a`.`property_name` AS `property_name`,`a`.`block` AS `block`,`a`.`kebele` AS `kebele`,`a`.`lot_number` AS `lot_number`,`a`.`owner_id` AS `owner_id`,`a`.`owner_name` AS `owner_name`,`a`.`property_type` AS `property_type`,`a`.`size` AS `size`,`a`.`annual_lease_rate` AS `annual_lease_rate`,`a`.`status` AS `status`,`a`.`registration_date` AS `registration_date`,`a`.`created_at` AS `created_at`,`a`.`updated_at` AS `updated_at` from (`assets` `a` join `lease_agreements` `la` on(`a`.`id` = `la`.`asset_id`)) where `a`.`status` in ('active','leased') and `la`.`status` = 'active' */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `bill_history`
--

/*!50001 DROP VIEW IF EXISTS `bill_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `bill_history` AS select `b`.`id` AS `id`,`b`.`bill_number` AS `bill_number`,`b`.`total_amount` AS `total_amount`,`b`.`billing_date` AS `billing_date`,`b`.`due_date` AS `due_date`,`b`.`status` AS `status`,`o`.`full_name` AS `owner_name`,`a`.`property_name` AS `property_name` from (((`bills` `b` join `lease_agreements` `l` on(`b`.`lease_id` = `l`.`id`)) join `assets` `a` on(`l`.`asset_id` = `a`.`id`)) join `owners` `o` on(`a`.`owner_id` = `o`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `outstanding_bills`
--

/*!50001 DROP VIEW IF EXISTS `outstanding_bills`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_unicode_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `outstanding_bills` AS select `b`.`id` AS `id`,`b`.`bill_number` AS `bill_number`,`b`.`total_amount` + coalesce(sum(`c`.`amount`),0) AS `total_amount`,`b`.`late_fee` AS `late_fee`,`b`.`tax_amount` AS `tax_amount`,coalesce(sum(`c`.`amount`),0) AS `additional_charges` from (`bills` `b` left join `charges` `c` on(`b`.`id` = `c`.`bill_id`)) where `b`.`status` in ('pending','overdue') group by `b`.`id`,`b`.`bill_number`,`b`.`total_amount`,`b`.`late_fee`,`b`.`tax_amount` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `payment_history`
--

/*!50001 DROP VIEW IF EXISTS `payment_history`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `payment_history` AS select `p`.`id` AS `id`,`p`.`payment_number` AS `payment_number`,`o`.`full_name` AS `owner_name`,`a`.`asset_code` AS `property_name`,`p`.`amount` AS `amount`,`p`.`payment_date` AS `payment_date`,`p`.`payment_method` AS `payment_method` from ((((`payments` `p` join `bills` `b` on(`p`.`bill_id` = `b`.`id`)) join `lease_agreements` `la` on(`b`.`lease_id` = `la`.`id`)) join `assets` `a` on(`la`.`asset_id` = `a`.`id`)) join `owners` `o` on(`a`.`owner_id` = `o`.`id`)) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-04-19 12:39:56
