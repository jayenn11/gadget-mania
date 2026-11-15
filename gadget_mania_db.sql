-- MySQL dump 10.13  Distrib 26.10.0, for macos27.0 (arm64)
--
-- Host: 127.0.0.1    Database: gadget_mania_db
-- ------------------------------------------------------
-- Server version	26.10.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Smartphone','5G smartphones and flagship mobile devices across leading global brands.','2026-11-15 00:00:26','2026-11-15 00:00:26'),(2,'Laptop','Portable computers for enterprise productivity, creative workloads, and gaming.','2026-11-15 00:00:26','2026-11-15 00:00:26'),(3,'Tablet','Touchscreen tablets for mobile productivity, digital illustration, and entertainment.','2026-11-15 00:00:26','2026-11-15 00:00:26'),(4,'Accessories','Essential gadget peripherals including GaN chargers, wireless mice, keyboards, and audio.','2026-11-15 00:00:26','2026-11-15 00:00:26');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_11_15_000001_create_categories_table',1),(5,'2026_11_15_000002_create_suppliers_table',1),(6,'2026_11_15_000003_create_products_table',1),(7,'2026_11_15_000004_create_purchase_orders_table',1),(8,'2026_11_15_000005_create_purchase_order_items_table',1),(9,'2026_11_15_000006_create_stock_movements_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `supplier_id` bigint unsigned DEFAULT NULL,
  `sku` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_price` decimal(15,2) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `min_stock` int NOT NULL DEFAULT '5',
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unit',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_unique` (`sku`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_supplier_id_foreign` (`supplier_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `products_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,1,1,'GM-IP15-128','iPhone 15 128GB Black','Apple',12200000.00,13999000.00,14,5,'Unit','2026-11-15 00:00:26','2026-11-15 00:00:26'),(2,1,1,'GM-S24-256','Samsung Galaxy S24 256GB Onyx Black','Samsung',11800000.00,13499000.00,2,5,'Unit','2026-11-15 00:00:26','2026-11-15 03:34:35'),(3,1,3,'GM-XM14-256','Xiaomi 14 5G 12/256GB Jade Green','Xiaomi',10400000.00,11999000.00,10,4,'Unit','2026-11-15 00:00:26','2026-11-15 00:00:26'),(4,2,1,'GM-MBA-M3','MacBook Air 13\" M3 8/256GB Space Grey','Apple',15900000.00,17999000.00,8,4,'Unit','2026-11-15 00:00:26','2026-11-15 00:00:26'),(5,2,2,'GM-ROG-G14','ASUS ROG Zephyrus G14 OLED Ryzen 9 RTX4060','ASUS',25500000.00,28999000.00,4,3,'Unit','2026-11-15 00:00:26','2026-11-15 00:33:09'),(6,2,2,'GM-LEN-LOQ15','Lenovo LOQ 15 Core i5-13450HX RTX3050','Lenovo',11200000.00,12799000.00,9,4,'Unit','2026-11-15 00:00:26','2026-11-15 00:00:26'),(7,3,1,'GM-IPAD-M2','iPad Air 11\" M2 Wi-Fi 128GB Blue','Apple',9600000.00,10999000.00,0,5,'Unit','2026-11-15 00:00:26','2026-11-15 00:53:48'),(8,3,3,'GM-TABS9-FE','Samsung Galaxy Tab S9 FE Wi-Fi 6/128GB','Samsung',5400000.00,6499000.00,11,4,'Unit','2026-11-15 00:00:26','2026-11-15 00:00:26'),(9,3,3,'GM-MIPAD-6','Xiaomi Pad 6 8/256GB Gravity Gray','Xiaomi',4300000.00,4999000.00,12,5,'Unit','2026-11-15 00:00:26','2026-11-15 00:00:26'),(10,4,4,'GM-LOGI-MX3S','Logitech MX Master 3S Wireless Mouse','Logitech',1320000.00,1649000.00,18,6,'Pcs','2026-11-15 00:00:26','2026-11-15 00:00:26'),(11,4,4,'GM-ANK-65W','Anker Nano II 65W GaN Fast Charger','Anker',420000.00,599000.00,2,8,'Pcs','2026-11-15 00:00:26','2026-11-15 03:20:42'),(12,4,1,'GM-AIRPODS-P2','Apple AirPods Pro (2nd Gen) USB-C','Apple',3350000.00,3999000.00,5,5,'Unit','2026-11-15 00:00:26','2026-11-15 00:53:48');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  KEY `purchase_order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `purchase_order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (1,1,1,5,12200000.00,61000000.00,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(2,1,4,5,15960000.00,79800000.00,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(3,2,5,2,25500000.00,51000000.00,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(4,2,6,4,11200000.00,44800000.00,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(5,3,11,20,420000.00,8400000.00,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(6,3,10,5,1260000.00,6300000.00,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(7,4,5,5,25500000.00,127500000.00,'2026-11-15 00:18:20','2026-11-15 00:18:20'),(8,5,2,2,11800000.00,23600000.00,'2026-11-15 00:25:23','2026-11-15 00:25:23'),(9,5,7,5,9600000.00,48000000.00,'2026-11-15 00:25:23','2026-11-15 00:25:23'),(10,6,2,1,11800000.00,11800000.00,'2026-11-15 00:26:47','2026-11-15 00:26:47'),(11,7,2,1,11800000.00,11800000.00,'2026-11-15 00:27:09','2026-11-15 00:27:09'),(12,8,5,1,25500000.00,25500000.00,'2026-11-15 00:48:14','2026-11-15 00:48:14'),(13,9,2,2,11800000.00,23600000.00,'2026-11-15 03:34:22','2026-11-15 03:34:22');
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supplier_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `order_date` date NOT NULL,
  `status` enum('pending','processing','received','cancelled','diproses','diterima','dibatalkan') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  KEY `purchase_orders_user_id_foreign` (`user_id`),
  CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `purchase_orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-20261109-0001',1,2,'2026-11-09','received',140800000.00,'Monthly stock replenishment for iPhone 15 and MacBook Air M3.','2026-11-15 00:00:26','2026-11-15 00:00:26'),(2,'PO-20261112-0002',2,1,'2026-11-12','processing',95800000.00,'Restock gaming laptops ROG Zephyrus G14 and Lenovo LOQ 15.','2026-11-15 00:00:26','2026-11-15 00:00:26'),(3,'PO-20261114-0003',4,2,'2026-11-14','pending',14700000.00,'Additional stock order for Anker fast chargers and Logitech mice.','2026-11-15 00:00:26','2026-11-15 00:00:26'),(4,'PO-20261115-0001',2,1,'2026-11-15','received',127500000.00,'Urgent Stock, ASAP','2026-11-15 00:18:20','2026-11-15 00:20:53'),(5,'PO-20261115-0002',1,1,'2026-11-15','received',71600000.00,NULL,'2026-11-15 00:25:23','2026-11-15 00:25:29'),(6,'PO-20261115-0003',1,1,'2026-11-15','cancelled',11800000.00,NULL,'2026-11-15 00:26:47','2026-11-15 00:26:50'),(7,'PO-20261115-0004',1,1,'2026-11-15','received',11800000.00,NULL,'2026-11-15 00:27:09','2026-11-15 00:27:12'),(8,'PO-20261115-0005',3,1,'2026-11-01','processing',25500000.00,NULL,'2026-11-15 00:48:14','2026-11-15 00:48:57'),(9,'PO-20261115-0006',1,1,'2026-11-15','received',23600000.00,NULL,'2026-11-15 03:34:22','2026-11-15 03:34:35');
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('1r99ZpML8kPzrL2U4WcObguHyh23dIQ33fAxlK8o',1,'127.0.0.1','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJXS01NVTNaNE1uQUhtaENCcmZiOGhiR0tQaE96aUVCWVM3THhpUlRSIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL3JlcG9ydHM/dGFiPXB1cmNoYXNlcyIsInJvdXRlIjoicmVwb3J0cy5pbmRleCJ9LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MSwicGFzc3dvcmRfaGFzaF93ZWIiOiJhNjg0ZTAzYmM1MDNkYTI1M2JkMDQwMmE1NGY2YjY0ZWJjZWM3YTFkZDAwNWU5MjAyNWUwZTY1ZTJhNmI4MjhjIn0=',1794739534);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_movements`
--

DROP TABLE IF EXISTS `stock_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stock_movements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reference_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('in','out') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `notes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stock_movements_reference_code_unique` (`reference_code`),
  KEY `stock_movements_product_id_foreign` (`product_id`),
  KEY `stock_movements_user_id_foreign` (`user_id`),
  CONSTRAINT `stock_movements_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `stock_movements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_movements`
--

LOCK TABLES `stock_movements` WRITE;
/*!40000 ALTER TABLE `stock_movements` DISABLE KEYS */;
INSERT INTO `stock_movements` VALUES (1,'TRX-IN-20261101',1,2,'in',5,12200000.00,61000000.00,'PO Receipt #PO-202611-001 (iPhone 15 128GB)','2026-11-09 10:15:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(2,'TRX-IN-20261102',4,2,'in',5,15900000.00,79500000.00,'PO Receipt #PO-202611-001 (MacBook Air M3)','2026-11-09 10:20:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(3,'TRX-OUT-20261101',1,3,'out',2,13999000.00,27998000.00,'Retail store sale - Walk-in Customer','2026-11-10 14:30:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(4,'TRX-OUT-20261102',2,3,'out',2,13499000.00,26998000.00,'Retail store sale - Samsung Galaxy S24','2026-11-11 16:10:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(5,'TRX-IN-20261103',10,2,'in',10,1320000.00,13200000.00,'Regular stock-in for Logitech peripherals','2026-11-12 09:45:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(6,'TRX-OUT-20261103',4,3,'out',1,17999000.00,17999000.00,'Retail sale - MacBook Air M3 bundle','2026-11-12 13:20:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(7,'TRX-OUT-20261104',5,3,'out',1,28999000.00,28999000.00,'Retail sale - ASUS ROG Zephyrus G14 gaming laptop','2026-11-13 15:05:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(8,'TRX-OUT-20261105',7,3,'out',2,10999000.00,21998000.00,'Corporate order - 2 units iPad Air M2','2026-11-14 11:40:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(9,'TRX-OUT-20261106',12,3,'out',3,3999000.00,11997000.00,'Retail sale - AirPods Pro (2nd Gen)','2026-11-14 17:15:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(10,'TRX-OUT-20261107',11,3,'out',4,599000.00,2396000.00,'Retail sale - Anker 65W GaN Fast Charger','2026-11-15 10:30:00','2026-11-15 00:00:26','2026-11-15 00:00:26'),(11,'PO-20261115-0001',5,1,'in',5,25500000.00,127500000.00,'Goods received from PO #PO-20261115-0001','2026-11-15 07:20:53','2026-11-15 00:20:53','2026-11-15 00:20:53'),(12,'PO-20261115-0002-01',2,1,'in',2,11800000.00,23600000.00,'Goods received from PO #PO-20261115-0002','2026-11-15 07:25:29','2026-11-15 00:25:29','2026-11-15 00:25:29'),(13,'PO-20261115-0002-02',7,1,'in',5,9600000.00,48000000.00,'Goods received from PO #PO-20261115-0002','2026-11-15 07:25:29','2026-11-15 00:25:29','2026-11-15 00:25:29'),(14,'PO-20261115-0004',2,1,'in',1,11800000.00,11800000.00,'Goods received from PO #PO-20261115-0004','2026-11-15 07:27:12','2026-11-15 00:27:12','2026-11-15 00:27:12'),(15,'TRX-OUT-20261115-0001',11,1,'out',1,599000.00,599000.00,'Retail store sale - Walk-in Customer','2026-11-15 07:33:09','2026-11-15 00:33:09','2026-11-15 00:33:09'),(16,'TRX-OUT-20261115-0002',11,1,'out',1,599000.00,599000.00,'Retail store sale - Walk-in Customer','2026-11-15 07:33:09','2026-11-15 00:33:09','2026-11-15 00:33:09'),(17,'TRX-OUT-20261115-0003',5,1,'out',3,28999000.00,86997000.00,'Retail store sale - Walk-in Customer','2026-11-15 07:33:09','2026-11-15 00:33:09','2026-11-15 00:33:09'),(18,'TRX-OUT-20261115-0004',12,1,'out',10,3999000.00,39990000.00,'Retail store sale - Walk-in Customer','2026-11-15 07:53:48','2026-11-15 00:53:48','2026-11-15 00:53:48'),(19,'TRX-OUT-20261115-0005',2,1,'out',6,13499000.00,80994000.00,'Retail store sale - Walk-in Customer','2026-11-15 07:53:48','2026-11-15 00:53:48','2026-11-15 00:53:48'),(20,'TRX-OUT-20261115-0006',7,1,'out',9,10999000.00,98991000.00,'Retail store sale - Walk-in Customer','2026-11-15 07:53:48','2026-11-15 00:53:48','2026-11-15 00:53:48'),(21,'TRX-OUT-20261115-0007',11,1,'out',1,599000.00,599000.00,'Retail store sale / Stock out','2026-11-15 10:20:42','2026-11-15 03:20:42','2026-11-15 03:20:42'),(22,'PO-20261115-0006',2,1,'in',2,11800000.00,23600000.00,'Goods received from PO #PO-20261115-0006','2026-11-15 10:34:35','2026-11-15 03:34:35','2026-11-15 03:34:35');
/*!40000 ALTER TABLE `stock_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(25) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `suppliers_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'SUP-001','PT Erajaya Swasembada Distribusi','Budi Santoso','02155967890','distribusi@erajaya-supply.co.id','Jl. Gedong Panjang No. 29-31, Pekojan, Tambora, West Jakarta','2026-11-15 00:00:26','2026-11-15 00:00:26'),(2,'SUP-002','PT Metrodata Electronics','Hendra Wijaya','02129345800','sales@metrodata.co.id','APL Tower 37th Floor, Jl. Letjen S. Parman Kav. 28, West Jakarta','2026-11-15 00:00:26','2026-11-15 00:00:26'),(3,'SUP-003','CV Nusantara Gadget Supply','Rina Permatasari','0224231098','order@nusantaragadget.id','Jl. Purnawarman No. 13-15, Babakan Ciamis, Bandung','2026-11-15 00:00:26','2026-11-15 00:00:26'),(4,'SUP-004','PT Bintang Aksesoris Nusantara','Deni Kurniawan','0315349821','partnership@bintangaksesoris.co.id','Jl. Kayoon No. 42, Embong Kaliasin, Genteng, Surabaya','2026-11-15 00:00:26','2026-11-15 00:00:26');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','kasir','gudang') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kasir',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Store Administrator','admin@gadgetmania.test','admin',NULL,'$2y$12$mQrUajXxKkEt7.abTAa3muxyFvnZBMxsawhDxIlu0n1xkIrhJ/IX6',NULL,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(2,'Head Warehouse Staff','gudang@gadgetmania.test','gudang',NULL,'$2y$12$fXjLZWijF1nK5tPRFCU4XeiWv7L6gQ41HBXl/1DH.aAOmzvimWKWO',NULL,'2026-11-15 00:00:26','2026-11-15 00:00:26'),(3,'Retail Cashier 1','kasir@gadgetmania.test','kasir',NULL,'$2y$12$/UjvwoIG2jbhlbfb7uhie.w4xEp2OsmrvzPIVa3bPdqIdJXTKozEO',NULL,'2026-11-15 00:00:26','2026-11-15 00:00:26');
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

-- Dump completed on 2026-11-15 18:06:55
