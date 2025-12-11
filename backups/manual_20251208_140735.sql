-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: essentia_hr
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
-- Table structure for table `01`
--

DROP TABLE IF EXISTS `01`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `01` (
  `1` int(11) NOT NULL,
  `2` int(11) NOT NULL,
  `3` int(11) NOT NULL,
  `4` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `01`
--

LOCK TABLES `01` WRITE;
/*!40000 ALTER TABLE `01` DISABLE KEYS */;
/*!40000 ALTER TABLE `01` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `can_undo` tinyint(1) NOT NULL DEFAULT 0,
  `undone_at` timestamp NULL DEFAULT NULL,
  `undone_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'2025-12-04 09:37:24',1,'import_sales_csv','Imported sales CSV for 12/2025','{\"month\":12,\"year\":2025,\"changes\":[{\"emp_id\":2,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"164\",\"emp_id\":\"2\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"1.00\",\"bonus\":\"0.00\",\"gross_salary\":\"29000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"29000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":5,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"165\",\"emp_id\":\"5\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"1.00\",\"bonus\":\"0.00\",\"gross_salary\":\"25000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"25000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":4,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"166\",\"emp_id\":\"4\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"1.00\",\"bonus\":\"0.00\",\"gross_salary\":\"25000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"25000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":3,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"167\",\"emp_id\":\"3\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"1.00\",\"bonus\":\"0.00\",\"gross_salary\":\"22000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"22000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":6,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"168\",\"emp_id\":\"6\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"0.00\",\"bonus\":\"0.00\",\"gross_salary\":\"40000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"40000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":7,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"169\",\"emp_id\":\"7\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"0.00\",\"bonus\":\"0.00\",\"gross_salary\":\"25000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"25000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":8,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"170\",\"emp_id\":\"8\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"0.00\",\"bonus\":\"0.00\",\"gross_salary\":\"24000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"24000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":9,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"171\",\"emp_id\":\"9\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"0.00\",\"bonus\":\"0.00\",\"gross_salary\":\"23000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"23000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":10,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"172\",\"emp_id\":\"10\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"0.00\",\"bonus\":\"0.00\",\"gross_salary\":\"24000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"24000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":11,\"month\":12,\"year\":2025,\"previous_payroll\":{\"id\":\"173\",\"emp_id\":\"11\",\"month\":\"12\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"30\",\"sales\":\"0.00\",\"commission_percent\":\"0.00\",\"bonus\":\"0.00\",\"gross_salary\":\"24000.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"24000.00\",\"generated_at\":\"2025-12-04 14:18:19\"},\"previous_hold_balance\":\"0.00\"}]}',0,'2025-12-04 09:37:46',1),(2,'2025-12-04 10:54:13',1,'db_backup','Daily backup created (20251204_115412)','{\"type\":\"daily\",\"file\":\"daily_20251204_115412.sql\"}',0,NULL,NULL),(3,'2025-12-04 10:54:13',1,'db_backup','Weekly backup created (20251204_115413)','{\"type\":\"weekly\",\"file\":\"weekly_20251204_115413.sql\"}',0,NULL,NULL),(4,'2025-12-04 12:09:30',1,'delete_targets_group','Deleted targets for Pine Square (Sales Man) 11/2025','{\"rows\":[{\"id\":30,\"branch_id\":3,\"designation\":\"Sales Man\",\"month\":11,\"year\":2025,\"sales_target\":\"7100000.00\",\"bonus_amount\":\"8000.00\",\"created_at\":\"2025-12-04 14:05:00\"},{\"id\":31,\"branch_id\":3,\"designation\":\"Sales Man\",\"month\":11,\"year\":2025,\"sales_target\":\"7600000.00\",\"bonus_amount\":\"16000.00\",\"created_at\":\"2025-12-04 14:05:00\"}]}',1,NULL,NULL),(5,'2025-12-04 12:11:19',1,'delete_targets_group','Deleted targets for Pine Square (Cashier) 11/2025','{\"rows\":[{\"id\":28,\"branch_id\":3,\"designation\":\"Cashier\",\"month\":11,\"year\":2025,\"sales_target\":\"7100000.00\",\"bonus_amount\":\"10000.00\",\"created_at\":\"2025-12-04 14:05:00\"},{\"id\":29,\"branch_id\":3,\"designation\":\"Cashier\",\"month\":11,\"year\":2025,\"sales_target\":\"7600000.00\",\"bonus_amount\":\"16000.00\",\"created_at\":\"2025-12-04 14:05:00\"}]}',1,NULL,NULL),(6,'2025-12-04 12:13:51',1,'import_sales_csv','Imported sales CSV for 11/2025','{\"month\":11,\"year\":2025,\"changes\":[{\"emp_id\":2,\"month\":11,\"year\":2025,\"previous_payroll\":{\"id\":\"142\",\"emp_id\":\"2\",\"month\":\"11\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"29\",\"sales\":\"2548412.29\",\"commission_percent\":\"1.00\",\"bonus\":\"16000.00\",\"gross_salary\":\"28033.33\",\"commission_amount\":\"25484.12\",\"net_salary\":\"69517.46\",\"generated_at\":\"2025-12-04 14:15:26\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":5,\"month\":11,\"year\":2025,\"previous_payroll\":{\"id\":\"143\",\"emp_id\":\"5\",\"month\":\"11\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"28\",\"sales\":\"2237992.38\",\"commission_percent\":\"1.00\",\"bonus\":\"16000.00\",\"gross_salary\":\"23333.33\",\"commission_amount\":\"22379.92\",\"net_salary\":\"61713.26\",\"generated_at\":\"2025-12-04 14:15:26\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":4,\"month\":11,\"year\":2025,\"previous_payroll\":{\"id\":\"144\",\"emp_id\":\"4\",\"month\":\"11\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"19\",\"sales\":\"1543679.89\",\"commission_percent\":\"1.00\",\"bonus\":\"16000.00\",\"gross_salary\":\"15833.33\",\"commission_amount\":\"15436.80\",\"net_salary\":\"47270.13\",\"generated_at\":\"2025-12-04 14:15:26\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":3,\"month\":11,\"year\":2025,\"previous_payroll\":{\"id\":\"145\",\"emp_id\":\"3\",\"month\":\"11\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"13\",\"sales\":\"1798339.39\",\"commission_percent\":\"1.00\",\"bonus\":\"16000.00\",\"gross_salary\":\"9533.33\",\"commission_amount\":\"17983.39\",\"net_salary\":\"43516.73\",\"generated_at\":\"2025-12-04 14:15:26\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":6,\"month\":11,\"year\":2025,\"previous_payroll\":{\"id\":\"146\",\"emp_id\":\"6\",\"month\":\"11\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"28\",\"sales\":\"8128423.96\",\"commission_percent\":\"0.00\",\"bonus\":\"30000.00\",\"gross_salary\":\"37333.33\",\"commission_amount\":\"40642.12\",\"net_salary\":\"107975.45\",\"generated_at\":\"2025-12-04 14:15:26\"},\"previous_hold_balance\":\"0.00\"},{\"emp_id\":8,\"month\":11,\"year\":2025,\"previous_payroll\":{\"id\":\"148\",\"emp_id\":\"8\",\"month\":\"11\",\"year\":\"2025\",\"total_days\":\"30\",\"earned_days\":\"33\",\"sales\":\"0.00\",\"commission_percent\":\"0.00\",\"bonus\":\"0.00\",\"gross_salary\":\"26400.00\",\"commission_amount\":\"0.00\",\"net_salary\":\"26400.00\",\"generated_at\":\"2025-12-04 14:15:26\"},\"previous_hold_balance\":\"0.00\"}]}',1,NULL,NULL),(7,'2025-12-08 13:05:55',1,'db_backup','Daily backup created (20251208_140554)','{\"type\":\"daily\",\"file\":\"daily_20251208_140554.sql\"}',0,NULL,NULL),(8,'2025-12-08 13:06:01',1,'db_backup','Manual backup created (20251208_140601)','{\"type\":\"manual\",\"file\":\"manual_20251208_140601.sql\"}',0,NULL,NULL),(9,'2025-12-08 13:07:22',1,'db_backup','Manual backup created (20251208_140721)','{\"type\":\"manual\",\"file\":\"manual_20251208_140721.sql\"}',0,NULL,NULL),(10,'2025-12-08 13:07:29',1,'db_backup','Manual backup created (20251208_140728)','{\"type\":\"manual\",\"file\":\"manual_20251208_140728.sql\"}',0,NULL,NULL);
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` char(1) NOT NULL DEFAULT 'P',
  `remarks` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_emp_date` (`emp_id`,`date`),
  CONSTRAINT `fk_att_emp` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,1,'2025-12-03','P',''),(2,1,'2025-12-01','P','');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_targets`
--

DROP TABLE IF EXISTS `branch_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `branch_id` int(11) NOT NULL,
  `designation` varchar(100) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `sales_target` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bonus_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_branch_designation` (`branch_id`,`designation`,`month`,`year`,`sales_target`),
  CONSTRAINT `fk_bt_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_targets`
--

LOCK TABLES `branch_targets` WRITE;
/*!40000 ALTER TABLE `branch_targets` DISABLE KEYS */;
INSERT INTO `branch_targets` VALUES (6,1,'Branch Manager',12,2025,11604000.00,16000.00,'2025-12-03 12:51:36'),(7,1,'Branch Manager',12,2025,12000000.00,30000.00,'2025-12-03 12:51:36'),(10,1,'Sales Man',12,2025,11604000.00,8000.00,'2025-12-03 12:55:24'),(11,1,'Sales Man',12,2025,12000000.00,15000.00,'2025-12-03 12:55:24'),(12,1,'Cashier',12,2025,11604000.00,10000.00,'2025-12-03 12:55:38'),(13,1,'Cashier',12,2025,12000000.00,16000.00,'2025-12-03 12:55:38'),(26,3,'Branch Manager',11,2025,7100000.00,16000.00,'2025-12-04 09:05:00'),(27,3,'Branch Manager',11,2025,7600000.00,30000.00,'2025-12-04 09:05:00'),(32,3,'Sales Man',11,2025,7100000.00,8000.00,'2025-12-04 12:10:21'),(33,3,'Sales Man',11,2025,7600000.00,15000.00,'2025-12-04 12:10:21'),(38,3,'Branch Manager',12,2025,7100000.00,15000.00,'2025-12-04 12:12:59'),(39,3,'Branch Manager',12,2025,7600000.00,30000.00,'2025-12-04 12:12:59'),(44,3,'Cashier',11,2025,7100000.00,10000.00,'2025-12-04 12:38:23'),(45,3,'Cashier',11,2025,7600000.00,16000.00,'2025-12-04 12:38:23'),(48,3,'Sales Man',12,2025,7100000.00,8000.00,'2025-12-04 12:40:53'),(49,3,'Sales Man',12,2025,7600000.00,15000.00,'2025-12-04 12:40:53'),(50,3,'Cashier',12,2025,7100000.00,10000.00,'2025-12-04 12:41:04'),(51,3,'Cashier',12,2025,7600000.00,16000.00,'2025-12-04 12:41:04');
/*!40000 ALTER TABLE `branch_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `manager_contact` varchar(50) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Sattelite','Mohsin Ali','03213013012','active'),(2,'Karachi','ali','03222255222','active'),(3,'Pine Square','Shoaib','03008357964','active');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `designations`
--

DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designations`
--

LOCK TABLES `designations` WRITE;
/*!40000 ALTER TABLE `designations` DISABLE KEYS */;
INSERT INTO `designations` VALUES (1,'Branch Manager','active'),(2,'Cashier','active'),(3,'Sales Man','active'),(4,'Sweeper','active'),(5,'Tailor','active'),(6,'Security Gaurd','active'),(7,'Internee','active');
/*!40000 ALTER TABLE `designations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `branch_id` int(11) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `commission` decimal(12,2) NOT NULL DEFAULT 0.00,
  `joining_date` date DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `hold_salary` tinyint(1) NOT NULL DEFAULT 0,
  `hold_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_code` (`emp_code`),
  KEY `fk_emp_branch` (`branch_id`),
  CONSTRAINT `fk_emp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'EL1','Ali',1,'Branch Manager','03225225555',NULL,20000.00,1.00,'2025-01-01','inactive',0,0.00,'2025-12-03 09:33:53'),(2,'1','Abdullah',3,'Sales Man','3700380401','',29000.00,1.00,'0000-00-00','active',0,0.00,'2025-12-03 15:22:19'),(3,'3','Umer',3,'Sales Man','3244094123','',22000.00,1.00,'0000-00-00','active',0,0.00,'2025-12-03 15:22:19'),(4,'9','Muzamil',3,'Sales Man','3499639100','',25000.00,1.00,'0000-00-00','active',0,0.00,'2025-12-03 15:22:19'),(5,'8','Adnan Tanveer',3,'Sales Man','3174261040','',25000.00,1.00,'0000-00-00','active',0,0.00,'2025-12-03 15:22:19'),(6,'2','Shoaib',3,'Branch Manager','3008357964','',40000.00,0.50,'0000-00-00','active',0,0.00,'2025-12-03 15:24:39'),(7,'4','Uzair Shabbir',3,'Sales Man','3279401978','',25000.00,1.00,'2025-12-01','active',0,0.00,'2025-12-03 15:24:39'),(8,'5','Liaqat',3,'Sweeper','3287841632','',24000.00,0.00,'0000-00-00','active',0,0.00,'2025-12-03 15:24:39'),(9,'6','ALI RAZA',3,'Sales Man','','',23000.00,1.00,'2025-12-01','active',0,0.00,'2025-12-03 15:24:39'),(10,'7','USMAN HUSSAIN',3,'Sales Man','3047826430','',24000.00,0.00,'2025-12-01','active',0,0.00,'2025-12-03 15:24:39'),(11,'10','IMRAN',3,'Sales Man','3260066404','',24000.00,1.00,'2025-12-01','active',0,0.00,'2025-12-03 15:24:39');
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `total_days` int(11) NOT NULL DEFAULT 30,
  `earned_days` int(11) NOT NULL DEFAULT 0,
  `sales` decimal(14,2) NOT NULL DEFAULT 0.00,
  `commission_percent` decimal(6,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
  `commission_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(14,2) NOT NULL DEFAULT 0.00,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_emp_month` (`emp_id`,`month`,`year`),
  CONSTRAINT `fk_pay_emp` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
INSERT INTO `payroll` VALUES (23,1,12,2025,30,31,11604000.00,1.00,16000.00,20666.67,116040.00,152706.67,'2025-12-03 12:57:45'),(147,7,11,2025,30,30,0.00,0.00,16000.00,25000.00,0.00,41000.00,'2025-12-04 09:15:26'),(149,9,11,2025,30,30,0.00,0.00,16000.00,23000.00,0.00,39000.00,'2025-12-04 09:15:26'),(150,10,11,2025,30,23,0.00,0.00,16000.00,18400.00,0.00,34400.00,'2025-12-04 09:15:26'),(151,11,11,2025,30,28,0.00,0.00,16000.00,22400.00,0.00,38400.00,'2025-12-04 09:15:26'),(184,2,12,2025,30,30,0.00,1.00,0.00,29000.00,0.00,29000.00,'2025-12-04 09:37:46'),(185,5,12,2025,30,30,0.00,1.00,0.00,25000.00,0.00,25000.00,'2025-12-04 09:37:46'),(186,4,12,2025,30,30,0.00,1.00,0.00,25000.00,0.00,25000.00,'2025-12-04 09:37:46'),(187,3,12,2025,30,30,0.00,1.00,0.00,22000.00,0.00,22000.00,'2025-12-04 09:37:46'),(188,6,12,2025,30,30,0.00,0.00,0.00,40000.00,0.00,40000.00,'2025-12-04 09:37:46'),(189,7,12,2025,30,30,0.00,0.00,0.00,25000.00,0.00,25000.00,'2025-12-04 09:37:46'),(190,8,12,2025,30,30,0.00,0.00,0.00,24000.00,0.00,24000.00,'2025-12-04 09:37:46'),(191,9,12,2025,30,30,0.00,0.00,0.00,23000.00,0.00,23000.00,'2025-12-04 09:37:46'),(192,10,12,2025,30,30,0.00,0.00,0.00,24000.00,0.00,24000.00,'2025-12-04 09:37:46'),(193,11,12,2025,30,30,0.00,0.00,0.00,24000.00,0.00,24000.00,'2025-12-04 09:37:46'),(195,5,11,2025,30,28,2237992.38,1.00,15000.00,23333.33,22379.92,60713.26,'2025-12-04 12:13:51'),(196,4,11,2025,30,19,1543679.89,1.00,15000.00,15833.33,15436.80,46270.13,'2025-12-04 12:13:51'),(197,3,11,2025,30,13,1798339.39,1.00,15000.00,9533.33,17983.39,42516.73,'2025-12-04 12:13:51'),(198,6,11,2025,30,28,8128423.96,0.00,30000.00,37333.33,40642.12,107975.45,'2025-12-04 12:13:51'),(199,8,11,2025,30,33,0.00,0.00,0.00,26400.00,0.00,26400.00,'2025-12-04 12:13:51'),(203,2,11,2025,30,29,2548412.29,1.00,15000.00,28033.33,25484.12,68517.46,'2025-12-08 08:54:29');
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_targets`
--

DROP TABLE IF EXISTS `sales_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `sales_target` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bonus_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_month_year` (`emp_id`,`month`,`year`),
  CONSTRAINT `fk_target_emp` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_targets`
--

LOCK TABLES `sales_targets` WRITE;
/*!40000 ALTER TABLE `sales_targets` DISABLE KEYS */;
INSERT INTO `sales_targets` VALUES (1,1,12,2025,300000.00,10000.00,'2025-12-03 11:35:14');
/*!40000 ALTER TABLE `sales_targets` ENABLE KEYS */;
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
  `role` enum('admin','branch') NOT NULL DEFAULT 'admin',
  `branch_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$MPrJeEC5pMBNq7DoP1r2yeV/tgBau9se0BwC26D48kc3VhPILuXtG','admin',NULL,'2025-12-03 09:32:23');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-08 18:07:35
