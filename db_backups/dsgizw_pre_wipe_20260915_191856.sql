-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: dsgizw
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.4

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
-- Table structure for table `academic_progress`
--

DROP TABLE IF EXISTS `academic_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` enum('term1','term2','term3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `assessment_type` enum('weekly_test','monthly_test','assignment','exam','project') COLLATE utf8mb4_unicode_ci NOT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `total_marks` decimal(5,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `academic_progress_recorded_by_foreign` (`recorded_by`),
  KEY `academic_progress_student_id_index` (`student_id`),
  KEY `academic_progress_class_id_index` (`class_id`),
  KEY `academic_progress_subject_id_index` (`subject_id`),
  KEY `academic_progress_term_index` (`term`),
  CONSTRAINT `academic_progress_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_progress_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_progress_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `academic_progress_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_progress`
--

LOCK TABLES `academic_progress` WRITE;
/*!40000 ALTER TABLE `academic_progress` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_years`
--

DROP TABLE IF EXISTS `academic_years`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_years` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `academic_years_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_years`
--

LOCK TABLES `academic_years` WRITE;
/*!40000 ALTER TABLE `academic_years` DISABLE KEYS */;
INSERT INTO `academic_years` VALUES (1,'2026','2026-01-01','2026-12-31',1,'2026-08-06 08:20:54','2026-09-06 18:41:57');
/*!40000 ALTER TABLE `academic_years` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` enum('all','parents','students','staff','specific_class') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `target_class_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  KEY `announcements_audience_index` (`audience`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'Welcome Back to School!','We welcome all students and parents to the new academic year. Please ensure all fees are paid by the due date.','all',NULL,2,1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(2,'Term 2 Fee Reminder','Term 2 fees are due by May 1st. Please contact the bursar for payment arrangements.','parents',NULL,2,1,'2026-08-06 08:20:54','2026-08-06 08:20:54');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_deposits`
--

DROP TABLE IF EXISTS `application_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_deposits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','ecocash','visa','mastercard','omari','innbucks','bank_transfer','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_by` bigint unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_deposits_received_by_foreign` (`received_by`),
  KEY `application_deposits_application_id_index` (`application_id`),
  KEY `application_deposits_payment_date_index` (`payment_date`),
  CONSTRAINT `application_deposits_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `application_deposits_received_by_foreign` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_deposits`
--

LOCK TABLES `application_deposits` WRITE;
/*!40000 ALTER TABLE `application_deposits` DISABLE KEYS */;
/*!40000 ALTER TABLE `application_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_document_requests`
--

DROP TABLE IF EXISTS `application_document_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_document_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `application_id` bigint unsigned NOT NULL,
  `document_key` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `requested_by` bigint unsigned DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT NULL,
  `fulfilled_at` timestamp NULL DEFAULT NULL,
  `old_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `application_document_requests_application_id_status_index` (`application_id`,`status`),
  KEY `application_document_requests_document_key_status_index` (`document_key`,`status`),
  CONSTRAINT `application_document_requests_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_document_requests`
--

LOCK TABLES `application_document_requests` WRITE;
/*!40000 ALTER TABLE `application_document_requests` DISABLE KEYS */;
INSERT INTO `application_document_requests` VALUES (1,1,'doc_parent_id_path','pending',NULL,1,'2026-08-09 15:02:31',NULL,'applications/APP-2026-0001/x8toI8efYMxbfKQ9QdnaU38c849pAfprqIOP5Zi0.jpg',NULL,'2026-08-09 15:02:31','2026-08-09 15:02:31');
/*!40000 ALTER TABLE `application_document_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  `term_id` bigint unsigned DEFAULT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `application_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `id_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `previous_school` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `former_grade` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason_for_joining` text COLLATE utf8mb4_unicode_ci,
  `doc_student_id_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_results_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_parent_id_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `doc_transfer_letter_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_email` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian2_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian2_email` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian2_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian3_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian3_email` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian3_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intended_class` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','offered','waiting_list','rejected','enrolled','approved','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `offer_letter_token` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offer_letter_version` smallint unsigned NOT NULL DEFAULT '1',
  `offer_letter_expires_at` timestamp NULL DEFAULT NULL,
  `offer_accepted_at` timestamp NULL DEFAULT NULL,
  `is_draft` tinyint(1) NOT NULL DEFAULT '0',
  `last_saved_step` tinyint unsigned DEFAULT NULL,
  `resume_token` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `processed_by` bigint unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `enrolled_student_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applications_application_number_unique` (`application_number`),
  UNIQUE KEY `applications_resume_token_unique` (`resume_token`),
  UNIQUE KEY `applications_offer_letter_token_unique` (`offer_letter_token`),
  KEY `applications_processed_by_foreign` (`processed_by`),
  KEY `applications_enrolled_student_id_foreign` (`enrolled_student_id`),
  KEY `applications_status_index` (`status`),
  KEY `applications_academic_year_index` (`academic_year`),
  CONSTRAINT `applications_enrolled_student_id_foreign` FOREIGN KEY (`enrolled_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applications_processed_by_foreign` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
INSERT INTO `applications` VALUES (1,1,1,2,2,'APP-2026-0001','Samuel','m','Ndlovu','samuelmndlovu27@gmail.com','0778776133','2000-07-30','22-321136D54','14397 Umvundla St\r\nSelborne Park 1','Willo Crest','Form 2','ghhjjjk','applications/APP-2026-0001/eE3rAX6fCwEudNdcJvcdHZT9ZOnll9CdUarXft4W.jpg','applications/APP-2026-0001/Q6olRQcXhoJVdYIZOQJKLPzBUESbagJmhDPgUdbB.jpg','applications/APP-2026-0001/x8toI8efYMxbfKQ9QdnaU38c849pAfprqIOP5Zi0.jpg','applications/APP-2026-0001/tJrSGlJbaiYFvHTMTx0P2zcgaZxFBuveVSUWda5c.jpg','Samuel M Ndlovu','samuelmndlovu27@gmail.com','0776261387',NULL,NULL,NULL,NULL,NULL,NULL,'Form 2 | Term 1 | Commercials','2026','offered','TPSUZOMGALLIR21W',1,'2026-08-30 15:03:11','2026-08-09 15:06:09',0,NULL,'BWVW72WYUWGQ',NULL,1,'2026-08-09 15:03:11',NULL,'2026-08-09 14:48:08','2026-08-09 15:06:09');
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_marks`
--

DROP TABLE IF EXISTS `assessment_marks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_marks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `mark_obtained` decimal(8,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `teacher_comment` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','entered','absent','excused') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `entered_by` bigint unsigned DEFAULT NULL,
  `entered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_marks_assessment_id_student_id_unique` (`assessment_id`,`student_id`),
  KEY `assessment_marks_assessment_id_index` (`assessment_id`),
  KEY `assessment_marks_student_id_index` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_marks`
--

LOCK TABLES `assessment_marks` WRITE;
/*!40000 ALTER TABLE `assessment_marks` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessment_marks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_types`
--

DROP TABLE IF EXISTS `assessment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `weight_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessment_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_types`
--

LOCK TABLES `assessment_types` WRITE;
/*!40000 ALTER TABLE `assessment_types` DISABLE KEYS */;
INSERT INTO `assessment_types` VALUES (1,'Weekly Test',20.00,NULL,1,'2026-08-09 15:11:07','2026-08-09 15:11:07');
/*!40000 ALTER TABLE `assessment_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assessment_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `assessment_type_id` bigint unsigned NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_marks` decimal(8,2) NOT NULL DEFAULT '100.00',
  `assessment_date` date NOT NULL,
  `created_by` bigint unsigned NOT NULL,
  `status` enum('draft','open','submitted','approved','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assessments_assessment_number_unique` (`assessment_number`),
  KEY `ass_main_idx` (`academic_year_id`,`term_id`,`stream_id`,`subject_id`),
  KEY `assessments_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `status` enum('present','absent','late','excused','sick','early_departure') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_recorded_by_foreign` (`recorded_by`),
  KEY `attendance_student_id_index` (`student_id`),
  KEY `attendance_class_id_index` (`class_id`),
  KEY `attendance_date_index` (`date`),
  CONSTRAINT `attendance_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_sessions`
--

DROP TABLE IF EXISTS `attendance_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `attendance_date` date NOT NULL,
  `session_type` enum('morning','afternoon','lesson') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `taken_by` bigint unsigned NOT NULL,
  `status` enum('draft','submitted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_session_unique` (`stream_id`,`attendance_date`,`session_type`,`subject_id`),
  KEY `attendance_sessions_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `attendance_sessions_attendance_date_index` (`attendance_date`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_sessions`
--

LOCK TABLES `attendance_sessions` WRITE;
/*!40000 ALTER TABLE `attendance_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `behaviour_categories`
--

DROP TABLE IF EXISTS `behaviour_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `behaviour_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('positive','negative') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'negative',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `behaviour_categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `behaviour_categories`
--

LOCK TABLES `behaviour_categories` WRITE;
/*!40000 ALTER TABLE `behaviour_categories` DISABLE KEYS */;
INSERT INTO `behaviour_categories` VALUES (1,'Late Coming','negative','Arriving after the expected reporting time.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(2,'Absenteeism','negative','Unexplained absence from school or lessons.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(3,'Homework Not Done','negative','Repeated failure to complete assigned homework.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(4,'Fighting','negative','Physical confrontation or aggression.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(5,'Bullying','negative','Bullying, intimidation, or harassment.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(6,'Disrespect','negative','Disrespect toward staff, students, or school rules.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(7,'Uniform Violation','negative','Failure to follow uniform expectations.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(8,'Absconding Lessons','negative','Skipping lessons while on campus.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(9,'Vandalism','negative','Damage to school property.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(10,'Good Conduct','positive','Positive behaviour, helpfulness, or exemplary conduct.',1,'2026-08-06 08:20:54','2026-08-06 08:20:54');
/*!40000 ALTER TABLE `behaviour_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `behaviour_incidents`
--

DROP TABLE IF EXISTS `behaviour_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `behaviour_incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `incident_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `behaviour_category_id` bigint unsigned NOT NULL,
  `incident_date` date NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'low',
  `reported_by` bigint unsigned NOT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `review_status` enum('pending','reviewed','escalated','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `parent_notified` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `behaviour_incidents_incident_number_unique` (`incident_number`),
  KEY `behaviour_incidents_student_id_index` (`student_id`),
  KEY `behaviour_incidents_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `behaviour_incidents_severity_index` (`severity`),
  KEY `behaviour_incidents_review_status_index` (`review_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `behaviour_incidents`
--

LOCK TABLES `behaviour_incidents` WRITE;
/*!40000 ALTER TABLE `behaviour_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `behaviour_incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `behaviour_records`
--

DROP TABLE IF EXISTS `behaviour_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `behaviour_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `issue_date` date NOT NULL,
  `issue_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('minor','moderate','severe') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_meeting_scheduled` tinyint(1) NOT NULL DEFAULT '0',
  `parent_meeting_date` date DEFAULT NULL,
  `parent_meeting_notes` text COLLATE utf8mb4_unicode_ci,
  `headmaster_review` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `behaviour_records_class_id_foreign` (`class_id`),
  KEY `behaviour_records_recorded_by_foreign` (`recorded_by`),
  KEY `behaviour_records_student_id_index` (`student_id`),
  KEY `behaviour_records_issue_date_index` (`issue_date`),
  CONSTRAINT `behaviour_records_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `behaviour_records_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `behaviour_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `behaviour_records`
--

LOCK TABLES `behaviour_records` WRITE;
/*!40000 ALTER TABLE `behaviour_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `behaviour_records` ENABLE KEYS */;
UNLOCK TABLES;

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
INSERT INTO `cache` VALUES ('destinygate-institute-cache-203d1f7675c4f9da44202525786a985aaa52af10','i:1;',1788772415),('destinygate-institute-cache-203d1f7675c4f9da44202525786a985aaa52af10:timer','i:1788772415;',1788772415),('destinygate-institute-cache-45e39033951cf51b9b62485c24d35a320a5a2766','i:1;',1788772202),('destinygate-institute-cache-45e39033951cf51b9b62485c24d35a320a5a2766:timer','i:1788772202;',1788772202),('destinygate-institute-cache-57996bc50f5513daa1f8cba20adb4fd36c95b8c2','i:1;',1788813058),('destinygate-institute-cache-57996bc50f5513daa1f8cba20adb4fd36c95b8c2:timer','i:1788813058;',1788813058),('destinygate-institute-cache-6de9bc692b3753a9e3a48ede129e28410d9c050b','i:1;',1788879448),('destinygate-institute-cache-6de9bc692b3753a9e3a48ede129e28410d9c050b:timer','i:1788879447;',1788879447),('destinygate-institute-cache-ce1ff55fc051e85877e0216f90785b45f94408a9','i:1;',1788813713),('destinygate-institute-cache-ce1ff55fc051e85877e0216f90785b45f94408a9:timer','i:1788813713;',1788813713),('destinygate-institute-cache-d726feb3d0902098702b54bc1d798b8d4234cf0a','i:1;',1788813056),('destinygate-institute-cache-d726feb3d0902098702b54bc1d798b8d4234cf0a:timer','i:1788813056;',1788813056),('destinygate-institute-cache-spatie.permission.cache','a:3:{s:5:\"alias\";a:4:{s:1:\"a\";s:2:\"id\";s:1:\"b\";s:4:\"name\";s:1:\"c\";s:10:\"guard_name\";s:1:\"r\";s:5:\"roles\";}s:11:\"permissions\";a:151:{i:0;a:4:{s:1:\"a\";i:1;s:1:\"b\";s:14:\"dashboard-view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:8:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;i:7;i:8;}}i:1;a:4:{s:1:\"a\";i:2;s:1:\"b\";s:19:\"academic-years-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:2;a:4:{s:1:\"a\";i:3;s:1:\"b\";s:21:\"academic-years-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:3;a:4:{s:1:\"a\";i:4;s:1:\"b\";s:19:\"academic-years-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:4;a:4:{s:1:\"a\";i:5;s:1:\"b\";s:21:\"academic-years-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:5;a:4:{s:1:\"a\";i:6;s:1:\"b\";s:23:\"academic-years-activate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:6;a:4:{s:1:\"a\";i:7;s:1:\"b\";s:10:\"terms-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:7;a:4:{s:1:\"a\";i:8;s:1:\"b\";s:12:\"terms-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:8;a:4:{s:1:\"a\";i:9;s:1:\"b\";s:10:\"terms-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:9;a:4:{s:1:\"a\";i:10;s:1:\"b\";s:12:\"terms-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:10;a:4:{s:1:\"a\";i:11;s:1:\"b\";s:17:\"terms-set-current\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:11;a:4:{s:1:\"a\";i:12;s:1:\"b\";s:10:\"forms-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:12;a:4:{s:1:\"a\";i:13;s:1:\"b\";s:10:\"forms-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:13;a:4:{s:1:\"a\";i:14;s:1:\"b\";s:12:\"streams-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:14;a:4:{s:1:\"a\";i:15;s:1:\"b\";s:14:\"streams-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:15;a:4:{s:1:\"a\";i:16;s:1:\"b\";s:12:\"streams-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:16;a:4:{s:1:\"a\";i:17;s:1:\"b\";s:14:\"streams-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:17;a:4:{s:1:\"a\";i:18;s:1:\"b\";s:19:\"subject-groups-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:18;a:4:{s:1:\"a\";i:19;s:1:\"b\";s:21:\"subject-groups-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:19;a:4:{s:1:\"a\";i:20;s:1:\"b\";s:19:\"subject-groups-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:20;a:4:{s:1:\"a\";i:21;s:1:\"b\";s:21:\"subject-groups-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:21;a:4:{s:1:\"a\";i:22;s:1:\"b\";s:13:\"subjects-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:22;a:4:{s:1:\"a\";i:23;s:1:\"b\";s:15:\"subjects-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:23;a:4:{s:1:\"a\";i:24;s:1:\"b\";s:13:\"subjects-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:24;a:4:{s:1:\"a\";i:25;s:1:\"b\";s:15:\"subjects-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:25;a:4:{s:1:\"a\";i:26;s:1:\"b\";s:23:\"teacher-allocation-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:26;a:4:{s:1:\"a\";i:27;s:1:\"b\";s:25:\"teacher-allocation-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:27;a:4:{s:1:\"a\";i:28;s:1:\"b\";s:25:\"teacher-allocation-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:28;a:4:{s:1:\"a\";i:29;s:1:\"b\";s:16:\"departments-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:29;a:4:{s:1:\"a\";i:30;s:1:\"b\";s:18:\"departments-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:30;a:4:{s:1:\"a\";i:31;s:1:\"b\";s:16:\"departments-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:31;a:4:{s:1:\"a\";i:32;s:1:\"b\";s:18:\"departments-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:32;a:4:{s:1:\"a\";i:33;s:1:\"b\";s:10:\"staff-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:33;a:4:{s:1:\"a\";i:34;s:1:\"b\";s:12:\"staff-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:34;a:4:{s:1:\"a\";i:35;s:1:\"b\";s:10:\"staff-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:35;a:4:{s:1:\"a\";i:36;s:1:\"b\";s:10:\"staff-show\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:36;a:4:{s:1:\"a\";i:37;s:1:\"b\";s:12:\"staff-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:37;a:4:{s:1:\"a\";i:38;s:1:\"b\";s:14:\"staff-activate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:38;a:4:{s:1:\"a\";i:39;s:1:\"b\";s:13:\"staff-suspend\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:39;a:4:{s:1:\"a\";i:40;s:1:\"b\";s:12:\"staff-resign\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:40;a:4:{s:1:\"a\";i:41;s:1:\"b\";s:17:\"staff-assign-role\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:41;a:4:{s:1:\"a\";i:42;s:1:\"b\";s:21:\"teacher-profiles-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:42;a:4:{s:1:\"a\";i:43;s:1:\"b\";s:23:\"teacher-profiles-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:43;a:4:{s:1:\"a\";i:44;s:1:\"b\";s:21:\"teacher-profiles-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:44;a:4:{s:1:\"a\";i:45;s:1:\"b\";s:13:\"students-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:45;a:4:{s:1:\"a\";i:46;s:1:\"b\";s:15:\"students-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:46;a:4:{s:1:\"a\";i:47;s:1:\"b\";s:13:\"students-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:47;a:4:{s:1:\"a\";i:48;s:1:\"b\";s:13:\"students-show\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;}}i:48;a:4:{s:1:\"a\";i:49;s:1:\"b\";s:15:\"students-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:49;a:4:{s:1:\"a\";i:50;s:1:\"b\";s:14:\"guardians-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:50;a:4:{s:1:\"a\";i:51;s:1:\"b\";s:16:\"guardians-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:51;a:4:{s:1:\"a\";i:52;s:1:\"b\";s:14:\"guardians-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:52;a:4:{s:1:\"a\";i:53;s:1:\"b\";s:16:\"guardians-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:53;a:4:{s:1:\"a\";i:54;s:1:\"b\";s:9:\"fees-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:54;a:4:{s:1:\"a\";i:55;s:1:\"b\";s:11:\"fees-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:55;a:4:{s:1:\"a\";i:56;s:1:\"b\";s:9:\"fees-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:56;a:4:{s:1:\"a\";i:57;s:1:\"b\";s:13:\"payments-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:57;a:4:{s:1:\"a\";i:58;s:1:\"b\";s:15:\"payments-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:58;a:4:{s:1:\"a\";i:59;s:1:\"b\";s:19:\"fee-structures-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:59;a:4:{s:1:\"a\";i:60;s:1:\"b\";s:21:\"fee-structures-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:60;a:4:{s:1:\"a\";i:61;s:1:\"b\";s:19:\"fee-structures-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:61;a:4:{s:1:\"a\";i:62;s:1:\"b\";s:21:\"fee-structures-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:62;a:4:{s:1:\"a\";i:63;s:1:\"b\";s:20:\"finance-reports-view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:63;a:4:{s:1:\"a\";i:64;s:1:\"b\";s:23:\"finance.reverse-payment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:64;a:4:{s:1:\"a\";i:65;s:1:\"b\";s:9:\"shop.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:65;a:4:{s:1:\"a\";i:66;s:1:\"b\";s:22:\"shop.manage_categories\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:66;a:4:{s:1:\"a\";i:67;s:1:\"b\";s:17:\"shop.manage_items\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:5;}}i:67;a:4:{s:1:\"a\";i:68;s:1:\"b\";s:20:\"shop.record_purchase\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:4;i:2;i:5;}}i:68;a:4:{s:1:\"a\";i:69;s:1:\"b\";s:19:\"shop.record_payment\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:4;}}i:69;a:4:{s:1:\"a\";i:70;s:1:\"b\";s:20:\"shop.cancel_purchase\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:70;a:4:{s:1:\"a\";i:71;s:1:\"b\";s:17:\"shop.view_reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:4;}}i:71;a:4:{s:1:\"a\";i:72;s:1:\"b\";s:16:\"shop.parent_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:72;a:4:{s:1:\"a\";i:73;s:1:\"b\";s:15:\"attendance.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:73;a:4:{s:1:\"a\";i:74;s:1:\"b\";s:15:\"attendance.mark\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:74;a:4:{s:1:\"a\";i:75;s:1:\"b\";s:17:\"attendance.submit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:75;a:4:{s:1:\"a\";i:76;s:1:\"b\";s:25:\"attendance.edit_submitted\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:76;a:4:{s:1:\"a\";i:77;s:1:\"b\";s:18:\"attendance.reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:77;a:4:{s:1:\"a\";i:78;s:1:\"b\";s:14:\"behaviour.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:78;a:4:{s:1:\"a\";i:79;s:1:\"b\";s:27:\"behaviour.manage_categories\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:79;a:4:{s:1:\"a\";i:80;s:1:\"b\";s:25:\"behaviour.record_incident\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:80;a:4:{s:1:\"a\";i:81;s:1:\"b\";s:25:\"behaviour.review_incident\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:81;a:4:{s:1:\"a\";i:82;s:1:\"b\";s:24:\"discipline.create_action\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:82;a:4:{s:1:\"a\";i:83;s:1:\"b\";s:25:\"discipline.manage_actions\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:83;a:4:{s:1:\"a\";i:84;s:1:\"b\";s:18:\"discipline.reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:84;a:4:{s:1:\"a\";i:85;s:1:\"b\";s:18:\"notifications.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:4:{i:0;i:1;i:1;i:2;i:2;i:6;i:3;i:7;}}i:85;a:4:{s:1:\"a\";i:86;s:1:\"b\";s:22:\"parent.attendance_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:86;a:4:{s:1:\"a\";i:87;s:1:\"b\";s:21:\"parent.behaviour_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:87;a:4:{s:1:\"a\";i:88;s:1:\"b\";s:23:\"student.attendance_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:7;}}i:88;a:4:{s:1:\"a\";i:89;s:1:\"b\";s:22:\"student.behaviour_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:7;}}i:89;a:4:{s:1:\"a\";i:90;s:1:\"b\";s:32:\"academics.assign_stream_subjects\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:90;a:4:{s:1:\"a\";i:91;s:1:\"b\";s:27:\"academics.allocate_teachers\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:91;a:4:{s:1:\"a\";i:92;s:1:\"b\";s:32:\"academics.enrol_student_subjects\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:92;a:4:{s:1:\"a\";i:93;s:1:\"b\";s:23:\"academics.drop_subjects\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:93;a:4:{s:1:\"a\";i:94;s:1:\"b\";s:30:\"academics.view_subject_reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:5:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:6;i:4;i:7;}}i:94;a:4:{s:1:\"a\";i:95;s:1:\"b\";s:16:\"assessments.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:95;a:4:{s:1:\"a\";i:96;s:1:\"b\";s:24:\"assessments.manage_types\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:96;a:4:{s:1:\"a\";i:97;s:1:\"b\";s:18:\"assessments.create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:97;a:4:{s:1:\"a\";i:98;s:1:\"b\";s:23:\"assessments.enter_marks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:98;a:4:{s:1:\"a\";i:99;s:1:\"b\";s:24:\"assessments.submit_marks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:99;a:4:{s:1:\"a\";i:100;s:1:\"b\";s:25:\"assessments.approve_marks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:100;a:4:{s:1:\"a\";i:101;s:1:\"b\";s:24:\"assessments.reopen_marks\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:101;a:4:{s:1:\"a\";i:102;s:1:\"b\";s:19:\"assessments.reports\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:102;a:4:{s:1:\"a\";i:103;s:1:\"b\";s:22:\"parent.assessment_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:103;a:4:{s:1:\"a\";i:104;s:1:\"b\";s:23:\"student.assessment_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:7;}}i:104;a:4:{s:1:\"a\";i:105;s:1:\"b\";s:16:\"reports.generate\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:105;a:4:{s:1:\"a\";i:106;s:1:\"b\";s:15:\"reports.publish\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:106;a:4:{s:1:\"a\";i:107;s:1:\"b\";s:15:\"reports.approve\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:107;a:4:{s:1:\"a\";i:108;s:1:\"b\";s:12:\"reports.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:108;a:4:{s:1:\"a\";i:109;s:1:\"b\";s:16:\"reports.download\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:109;a:4:{s:1:\"a\";i:110;s:1:\"b\";s:19:\"reports.batch_print\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:110;a:4:{s:1:\"a\";i:111;s:1:\"b\";s:18:\"parent.report_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:111;a:4:{s:1:\"a\";i:112;s:1:\"b\";s:19:\"student.report_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:7;}}i:112;a:4:{s:1:\"a\";i:113;s:1:\"b\";s:24:\"timetable.manage_periods\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:113;a:4:{s:1:\"a\";i:114;s:1:\"b\";s:22:\"timetable.manage_rooms\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:114;a:4:{s:1:\"a\";i:115;s:1:\"b\";s:16:\"timetable.manage\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:115;a:4:{s:1:\"a\";i:116;s:1:\"b\";s:14:\"timetable.view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:116;a:4:{s:1:\"a\";i:117;s:1:\"b\";s:16:\"timetable.export\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:117;a:4:{s:1:\"a\";i:118;s:1:\"b\";s:22:\"teacher.timetable_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:118;a:4:{s:1:\"a\";i:119;s:1:\"b\";s:22:\"student.timetable_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:7;}}i:119;a:4:{s:1:\"a\";i:120;s:1:\"b\";s:21:\"parent.timetable_view\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:6;}}i:120;a:4:{s:1:\"a\";i:121;s:1:\"b\";s:10:\"marks-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:121;a:4:{s:1:\"a\";i:122;s:1:\"b\";s:12:\"marks-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:122;a:4:{s:1:\"a\";i:123;s:1:\"b\";s:10:\"marks-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:123;a:4:{s:1:\"a\";i:124;s:1:\"b\";s:15:\"attendance-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:124;a:4:{s:1:\"a\";i:125;s:1:\"b\";s:17:\"attendance-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:125;a:4:{s:1:\"a\";i:126;s:1:\"b\";s:15:\"attendance-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:126;a:4:{s:1:\"a\";i:127;s:1:\"b\";s:14:\"behaviour-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:127;a:4:{s:1:\"a\";i:128;s:1:\"b\";s:16:\"behaviour-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:128;a:4:{s:1:\"a\";i:129;s:1:\"b\";s:14:\"behaviour-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:129;a:4:{s:1:\"a\";i:130;s:1:\"b\";s:16:\"behaviour-review\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:130;a:4:{s:1:\"a\";i:131;s:1:\"b\";s:21:\"teacher-comments-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}}i:131;a:4:{s:1:\"a\";i:132;s:1:\"b\";s:23:\"teacher-comments-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:132;a:4:{s:1:\"a\";i:133;s:1:\"b\";s:21:\"teacher-comments-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:3;}}i:133;a:4:{s:1:\"a\";i:134;s:1:\"b\";s:16:\"reports-academic\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:134;a:4:{s:1:\"a\";i:135;s:1:\"b\";s:17:\"reports-financial\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:135;a:4:{s:1:\"a\";i:136;s:1:\"b\";s:18:\"reports-attendance\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:136;a:4:{s:1:\"a\";i:137;s:1:\"b\";s:17:\"reports-behaviour\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:137;a:4:{s:1:\"a\";i:138;s:1:\"b\";s:18:\"announcements-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:7:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:6;i:6;i:7;}}i:138;a:4:{s:1:\"a\";i:139;s:1:\"b\";s:20:\"announcements-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:139;a:4:{s:1:\"a\";i:140;s:1:\"b\";s:18:\"announcements-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:140;a:4:{s:1:\"a\";i:141;s:1:\"b\";s:20:\"announcements-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:2:{i:0;i:1;i:1;i:2;}}i:141;a:4:{s:1:\"a\";i:142;s:1:\"b\";s:10:\"users-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:142;a:4:{s:1:\"a\";i:143;s:1:\"b\";s:12:\"users-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:143;a:4:{s:1:\"a\";i:144;s:1:\"b\";s:10:\"users-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:144;a:4:{s:1:\"a\";i:145;s:1:\"b\";s:10:\"users-show\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:145;a:4:{s:1:\"a\";i:146;s:1:\"b\";s:10:\"roles-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:146;a:4:{s:1:\"a\";i:147;s:1:\"b\";s:12:\"roles-create\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:147;a:4:{s:1:\"a\";i:148;s:1:\"b\";s:10:\"roles-edit\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:148;a:4:{s:1:\"a\";i:149;s:1:\"b\";s:12:\"roles-delete\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:149;a:4:{s:1:\"a\";i:150;s:1:\"b\";s:16:\"permissions-list\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}i:150;a:4:{s:1:\"a\";i:151;s:1:\"b\";s:18:\"permissions-assign\";s:1:\"c\";s:3:\"web\";s:1:\"r\";a:1:{i:0;i:1;}}}s:5:\"roles\";a:8:{i:0;a:3:{s:1:\"a\";i:1;s:1:\"b\";s:5:\"admin\";s:1:\"c\";s:3:\"web\";}i:1;a:3:{s:1:\"a\";i:2;s:1:\"b\";s:10:\"headmaster\";s:1:\"c\";s:3:\"web\";}i:2;a:3:{s:1:\"a\";i:3;s:1:\"b\";s:7:\"teacher\";s:1:\"c\";s:3:\"web\";}i:3;a:3:{s:1:\"a\";i:4;s:1:\"b\";s:6:\"bursar\";s:1:\"c\";s:3:\"web\";}i:4;a:3:{s:1:\"a\";i:5;s:1:\"b\";s:11:\"storekeeper\";s:1:\"c\";s:3:\"web\";}i:5;a:3:{s:1:\"a\";i:6;s:1:\"b\";s:6:\"parent\";s:1:\"c\";s:3:\"web\";}i:6;a:3:{s:1:\"a\";i:7;s:1:\"b\";s:7:\"student\";s:1:\"c\";s:3:\"web\";}i:7;a:3:{s:1:\"a\";i:8;s:1:\"b\";s:4:\"user\";s:1:\"c\";s:3:\"web\";}}}',1788599215);
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
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_name_unique` (`name`),
  UNIQUE KEY `categories_code_unique` (`code`),
  KEY `categories_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Sciences','SCI','Science-focused academic pathway.',1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(2,'Arts','ART','Arts and humanities academic pathway.',1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(3,'Commercials','COM','Commercial and business academic pathway.',1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(4,'General','GEN','General academic pathway.',1,'2026-08-06 08:20:53','2026-08-06 08:20:53');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `classes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stream` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_teacher_id` bigint unsigned DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classes_class_name_index` (`class_name`),
  KEY `classes_academic_year_index` (`academic_year`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
INSERT INTO `classes` VALUES (1,'Form 1','A',3,'2026/2027',35,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(2,'Form 2','A',4,'2026/2027',35,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(3,'Form 3','B',NULL,'2026/2027',30,'2026-08-06 08:20:53','2026-08-06 08:20:53');
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `discipline_actions`
--

DROP TABLE IF EXISTS `discipline_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `discipline_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `behaviour_incident_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `action_type` enum('verbal_warning','written_warning','parent_meeting','detention','suspension','headmaster_review','counselling','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `issued_by` bigint unsigned NOT NULL,
  `parent_required` tinyint(1) NOT NULL DEFAULT '0',
  `parent_notified` tinyint(1) NOT NULL DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `status` enum('open','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discipline_actions_action_number_unique` (`action_number`),
  KEY `discipline_actions_behaviour_incident_id_index` (`behaviour_incident_id`),
  KEY `discipline_actions_student_id_index` (`student_id`),
  KEY `discipline_actions_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `discipline_actions`
--

LOCK TABLES `discipline_actions` WRITE;
/*!40000 ALTER TABLE `discipline_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `discipline_actions` ENABLE KEYS */;
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
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
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
-- Table structure for table `fee_categories`
--

DROP TABLE IF EXISTS `fee_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `frequency` enum('per_term','once_off','as_applicable','per_event','per_project') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fee_categories_name_unique` (`name`),
  UNIQUE KEY `fee_categories_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_categories`
--

LOCK TABLES `fee_categories` WRITE;
/*!40000 ALTER TABLE `fee_categories` DISABLE KEYS */;
INSERT INTO `fee_categories` VALUES (1,'FEE-001','School Fees / Tuition','Seeded for browser demo','per_term',1,'2026-09-03 11:26:02','2026-09-08 05:29:59'),(2,'FEE-003','Examination Fees','Separate charge, as applicable.','as_applicable',1,'2026-09-03 11:38:42','2026-09-04 07:07:10'),(3,'FEE-006','Sports & Activities','Activity-specific charge.','as_applicable',1,'2026-09-03 11:38:42','2026-09-04 07:07:10'),(4,'FEE-002','Registration / Enrolment','Once-off / management-configured charge.','once_off',1,'2026-09-04 07:07:10','2026-09-04 07:07:10'),(5,'FEE-004','Project Fees','Charge by subject/project.','per_project',1,'2026-09-04 07:07:10','2026-09-04 07:07:10'),(6,'FEE-005','Practical / Skills Programme','Programme-dependent separate charge.','as_applicable',1,'2026-09-04 07:07:10','2026-09-04 07:07:10'),(7,'FEE-007','Educational Trips','Event-specific charge, per activity.','per_event',1,'2026-09-04 07:07:10','2026-09-04 07:07:10'),(8,'FEE-008','Other Approved Charges','Management-configured, as applicable.','as_applicable',1,'2026-09-04 07:07:10','2026-09-04 07:07:10');
/*!40000 ALTER TABLE `fee_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_structures`
--

DROP TABLE IF EXISTS `fee_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `class_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` enum('term1','term2','term3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fee_structures_academic_year_index` (`academic_year`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structures`
--

LOCK TABLES `fee_structures` WRITE;
/*!40000 ALTER TABLE `fee_structures` DISABLE KEYS */;
INSERT INTO `fee_structures` VALUES (1,'2026/2027','Form 1','term1',350.00,'2026-02-01','School fees for Form 1 - TERM1','2026-08-06 08:20:53','2026-08-06 08:20:53'),(2,'2026/2027','Form 1','term2',350.00,'2026-05-01','School fees for Form 1 - TERM2','2026-08-06 08:20:53','2026-08-06 08:20:53'),(3,'2026/2027','Form 1','term3',350.00,'2026-08-01','School fees for Form 1 - TERM3','2026-08-06 08:20:53','2026-08-06 08:20:53'),(4,'2026/2027','Form 2','term1',350.00,'2026-02-01','School fees for Form 2 - TERM1','2026-08-06 08:20:53','2026-08-06 08:20:53'),(5,'2026/2027','Form 2','term2',350.00,'2026-05-01','School fees for Form 2 - TERM2','2026-08-06 08:20:53','2026-08-06 08:20:53'),(6,'2026/2027','Form 2','term3',350.00,'2026-08-01','School fees for Form 2 - TERM3','2026-08-06 08:20:53','2026-08-06 08:20:53'),(7,'2026/2027','Form 3','term1',350.00,'2026-02-01','School fees for Form 3 - TERM1','2026-08-06 08:20:53','2026-08-06 08:20:53'),(8,'2026/2027','Form 3','term2',350.00,'2026-05-01','School fees for Form 3 - TERM2','2026-08-06 08:20:54','2026-08-06 08:20:54'),(9,'2026/2027','Form 3','term3',350.00,'2026-08-01','School fees for Form 3 - TERM3','2026-08-06 08:20:54','2026-08-06 08:20:54');
/*!40000 ALTER TABLE `fee_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_fee_structures`
--

DROP TABLE IF EXISTS `finance_fee_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_fee_structures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `stream_id` bigint unsigned DEFAULT NULL,
  `fee_category_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ffs_main_idx` (`academic_year_id`,`term_id`,`form_id`,`fee_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_fee_structures`
--

LOCK TABLES `finance_fee_structures` WRITE;
/*!40000 ALTER TABLE `finance_fee_structures` DISABLE KEYS */;
INSERT INTO `finance_fee_structures` VALUES (1,1,2,NULL,NULL,1,'Term 2 Tuition (Demo)',500.00,'2026-09-17',1,1,'2026-09-03 11:26:02','2026-09-03 11:26:02'),(2,1,2,1,NULL,1,'Term 2 Tuition - Form 1',450.00,'2026-09-24',1,1,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(3,1,2,2,NULL,1,'Term 2 Tuition - Form 2',500.00,'2026-09-24',1,1,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(4,1,2,3,NULL,1,'Term 2 Tuition - Form 3',550.00,'2026-09-24',1,1,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(5,1,2,NULL,NULL,2,'Term 2 Examination Fees',40.00,'2026-09-24',1,1,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(6,1,2,NULL,NULL,3,'Term 2 Sports Levy',25.00,'2026-09-24',0,1,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(7,1,3,NULL,NULL,1,'Term 3 Tuition (Demo)',500.00,'2026-09-22',1,1,'2026-09-07 16:27:34','2026-09-07 16:27:34'),(8,1,3,1,NULL,1,'Term 3 Tuition - Form 1',450.00,'2026-09-29',1,1,'2026-09-07 16:27:34','2026-09-07 16:27:34'),(9,1,3,2,NULL,1,'Term 3 Tuition - Form 2',500.00,'2026-09-29',1,1,'2026-09-07 16:27:34','2026-09-07 16:27:34'),(10,1,3,3,NULL,1,'Term 3 Tuition - Form 3',550.00,'2026-09-29',1,1,'2026-09-07 16:27:34','2026-09-07 16:27:34'),(11,1,3,NULL,NULL,2,'Term 3 Examination Fees',40.00,'2026-09-29',1,1,'2026-09-07 16:27:34','2026-09-07 16:27:34'),(12,1,3,NULL,NULL,3,'Term 3 Sports Levy',25.00,'2026-09-29',0,1,'2026-09-07 16:27:34','2026-09-07 16:27:34');
/*!40000 ALTER TABLE `finance_fee_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `finance_payments`
--

DROP TABLE IF EXISTS `finance_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finance_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_reference` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `guardian_id` bigint unsigned DEFAULT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','ecocash','bank_transfer','swipe','online','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payer_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_by` bigint unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','reversed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `finance_payments_receipt_number_unique` (`receipt_number`),
  UNIQUE KEY `finance_payments_payment_reference_unique` (`payment_reference`),
  KEY `finance_payments_student_id_index` (`student_id`),
  KEY `finance_payments_payment_date_index` (`payment_date`),
  KEY `finance_payments_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `finance_payments_guardian_id_foreign` (`guardian_id`),
  CONSTRAINT `finance_payments_guardian_id_foreign` FOREIGN KEY (`guardian_id`) REFERENCES `guardians` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `finance_payments`
--

LOCK TABLES `finance_payments` WRITE;
/*!40000 ALTER TABLE `finance_payments` DISABLE KEYS */;
INSERT INTO `finance_payments` VALUES (1,'RCPT-2026-00001','DGS-PAY-20260903-132602-LBXBXC',1,NULL,1,2,300.00,'ecocash','DEMO-ECO-0001','Mrs. Dube (Parent)','+263771234567',3,'2026-09-03','Demo payment seeded for browser walkthrough','active',NULL,'2026-09-03 11:26:02','2026-09-03 11:26:02'),(2,'RCPT-2026-00002','DGS-PAY-20260903-133842-AP9SYK',2,NULL,1,2,310.75,'ecocash','DEMO-ECOCASH-0001','Parent/Guardian',NULL,3,'2026-09-02','Bulk demo data','active',NULL,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(3,'RCPT-2026-00003','DGS-PAY-20260903-133842-SQEGRT',3,NULL,1,2,369.00,'bank_transfer','DEMO-BANK_TRANSFER-0002','Parent/Guardian',NULL,3,'2026-09-01','Bulk demo data','active',NULL,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(4,'RCPT-2026-00004','DGS-PAY-20260903-133842-QJENW3',4,NULL,1,2,515.00,'swipe','DEMO-SWIPE-0003','Parent/Guardian',NULL,3,'2026-08-31','Bulk demo data','active',NULL,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(5,'RCPT-2026-00005','DGS-PAY-20260903-133842-Q4ACFD',5,NULL,1,2,565.00,'cash','DEMO-CASH-0004','Parent/Guardian',NULL,3,'2026-08-30','Bulk demo data','active',NULL,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(6,'RCPT-2026-00006','DGS-PAY-20260903-133843-ZNGTRK',7,NULL,1,2,283.25,'bank_transfer','DEMO-BANK_TRANSFER-0006','Parent/Guardian',NULL,3,'2026-08-28','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(7,'RCPT-2026-00007','DGS-PAY-20260903-133843-Q4RGXG',8,NULL,1,2,339.00,'swipe','DEMO-SWIPE-0007','Parent/Guardian',NULL,3,'2026-08-27','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(8,'RCPT-2026-00008','DGS-PAY-20260903-133843-CV62PU',9,NULL,1,2,615.00,'cash','DEMO-CASH-0008','Parent/Guardian',NULL,3,'2026-08-26','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(9,'RCPT-2026-00009','DGS-PAY-20260903-133843-L6E47A',10,NULL,1,2,515.00,'ecocash','DEMO-ECOCASH-0009','Parent/Guardian',NULL,3,'2026-08-25','Bulk demo data','reversed','2026-09-07 06:45:30','2026-09-03 11:38:43','2026-09-04 10:40:39'),(10,'RCPT-2026-00010','DGS-PAY-20260903-133843-ZD96XN',12,NULL,1,2,338.25,'swipe','DEMO-SWIPE-0011','Parent/Guardian',NULL,3,'2026-09-02','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(11,'RCPT-2026-00011','DGS-PAY-20260903-133843-YW8P52',13,NULL,1,2,309.00,'cash','DEMO-CASH-0012','Parent/Guardian',NULL,3,'2026-09-01','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(12,'RCPT-2026-00012','DGS-PAY-20260903-133843-SUCDHS',14,NULL,1,2,565.00,'ecocash','DEMO-ECOCASH-0013','Parent/Guardian',NULL,3,'2026-08-31','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(13,'RCPT-2026-00013','DGS-PAY-20260903-133843-8GM3UP',15,NULL,1,2,615.00,'bank_transfer','DEMO-BANK_TRANSFER-0014','Parent/Guardian',NULL,3,'2026-08-30','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(14,'RCPT-2026-00014','DGS-PAY-20260903-133843-VPDTA3',17,NULL,1,2,310.75,'cash','DEMO-CASH-0016','Parent/Guardian',NULL,3,'2026-08-28','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(15,'RCPT-2026-00015','DGS-PAY-20260903-133843-T7UVDV',18,NULL,1,2,369.00,'ecocash','DEMO-ECOCASH-0017','Parent/Guardian',NULL,3,'2026-08-27','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(16,'RCPT-2026-00016','DGS-PAY-20260903-133843-QUEJA2',19,NULL,1,2,515.00,'bank_transfer','DEMO-BANK_TRANSFER-0018','Parent/Guardian',NULL,3,'2026-08-26','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(17,'RCPT-2026-00017','DGS-PAY-20260903-133843-CLU9SZ',20,NULL,1,2,565.00,'swipe','DEMO-SWIPE-0019','Parent/Guardian',NULL,3,'2026-08-25','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(18,'RCPT-2026-00018','DGS-PAY-20260903-133843-ZEQSX2',22,NULL,1,2,283.25,'ecocash','DEMO-ECOCASH-0021','Parent/Guardian',NULL,3,'2026-09-02','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(19,'RCPT-2026-00019','DGS-PAY-20260903-133843-BVVD3C',23,NULL,1,2,339.00,'bank_transfer','DEMO-BANK_TRANSFER-0022','Parent/Guardian',NULL,3,'2026-09-01','Bulk demo data','active',NULL,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(20,'RCPT-2026-00020','DGS-PAY-20260904-091303-JMV7EY',1,1,1,2,10.00,'cash',NULL,NULL,NULL,3,'2026-09-04',NULL,'active',NULL,'2026-09-04 07:13:03','2026-09-04 07:13:03'),(21,'RCPT-2026-00021','DGS-PAY-20260904-091330-HA3A92',1,1,1,2,10.00,'cash',NULL,NULL,NULL,3,'2026-09-04',NULL,'reversed',NULL,'2026-09-04 07:13:30','2026-09-04 07:13:30'),(22,'RCPT-2026-00022','DGS-PAY-20260906-211035-AM7JPS',6,NULL,1,1,12.00,'cash',NULL,'sam',NULL,1,'2026-09-06',NULL,'active',NULL,'2026-09-06 19:10:35','2026-09-06 19:10:35'),(23,'RCPT-2026-00023','DGS-PAY-20260906-212250-Z2YW58',3,NULL,1,2,346.00,'cash',NULL,NULL,NULL,3,'2026-09-06',NULL,'reversed',NULL,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(24,'RCPT-2026-00024','DGS-PAY-20260906-212304-MTHEJA',2,NULL,1,2,329.25,'cash',NULL,NULL,NULL,3,'2026-09-06',NULL,'active',NULL,'2026-09-06 19:23:04','2026-09-06 19:23:04'),(25,'RCPT-2026-00025','DGS-PAY-20260906-212713-LWARRQ',6,NULL,1,3,1222.00,'cash',NULL,'sam',NULL,1,'2026-09-06',NULL,'active',NULL,'2026-09-06 19:27:13','2026-09-06 19:27:13'),(26,'RCPT-2026-00026','DGS-PAY-20260906-213453-UCFH3K',4,NULL,1,2,200.00,'ecocash',NULL,NULL,NULL,3,'2026-09-06',NULL,'active','2026-09-07 06:45:25','2026-09-06 19:34:53','2026-09-06 19:34:53'),(27,'DGS-RCP-2026-00000001','DGS-PAY-20260907-084729-TQVA8Z',5,NULL,1,2,10.00,'cash',NULL,NULL,NULL,3,'2026-09-07',NULL,'active','2026-09-07 09:00:54','2026-09-07 06:47:29','2026-09-07 06:47:29'),(28,'DGS-RCP-2026-00000002','DGS-PAY-20260907-113005-LPXUYF',5,NULL,1,2,233.00,'ecocash','saddwwd324','sam','987887',1,'2026-09-07','for next term','active',NULL,'2026-09-07 09:30:05','2026-09-07 09:30:05'),(31,'DGS-RCP-2026-00000003','DGS-PAY-20260907-202616-7LEZGX',6,NULL,1,3,234.00,'cash',NULL,'SAM','0776261387',1,'2026-09-07',NULL,'active','2026-09-08 12:56:28','2026-09-07 18:26:16','2026-09-07 18:26:16');
/*!40000 ALTER TABLE `finance_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `forms`
--

DROP TABLE IF EXISTS `forms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` tinyint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `forms_level_unique` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `forms`
--

LOCK TABLES `forms` WRITE;
/*!40000 ALTER TABLE `forms` DISABLE KEYS */;
INSERT INTO `forms` VALUES (1,'Form 1',1,'Junior Secondary - Year 1','2026-08-06 08:20:53','2026-08-06 08:20:53'),(2,'Form 2',2,'Junior Secondary - Year 2','2026-08-06 08:20:53','2026-08-06 08:20:53'),(3,'Form 3',3,'Junior Secondary - Year 3','2026-08-06 08:20:53','2026-08-06 08:20:53'),(4,'Form 4',4,'Senior Secondary - Year 1','2026-08-06 08:20:53','2026-08-06 08:20:53'),(5,'Form 5',5,'Senior Secondary - Year 2','2026-08-06 08:20:53','2026-08-06 08:20:53'),(6,'Form 6',6,'Advanced Level','2026-08-06 08:20:53','2026-08-06 08:20:53');
/*!40000 ALTER TABLE `forms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grading_scales`
--

DROP TABLE IF EXISTS `grading_scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_scales` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remark` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grading_scales`
--

LOCK TABLES `grading_scales` WRITE;
/*!40000 ALTER TABLE `grading_scales` DISABLE KEYS */;
/*!40000 ALTER TABLE `grading_scales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `graduation_readiness`
--

DROP TABLE IF EXISTS `graduation_readiness`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `graduation_readiness` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `stream_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `status` enum('unknown','not_ready','ready') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unknown',
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `graduation_readiness_unique_student_year` (`student_id`,`academic_year_id`),
  KEY `graduation_readiness_academic_year_id_index` (`academic_year_id`),
  KEY `graduation_readiness_stream_id_form_id_index` (`stream_id`,`form_id`),
  KEY `graduation_readiness_category_id_index` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `graduation_readiness`
--

LOCK TABLES `graduation_readiness` WRITE;
/*!40000 ALTER TABLE `graduation_readiness` DISABLE KEYS */;
/*!40000 ALTER TABLE `graduation_readiness` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `guardians`
--

DROP TABLE IF EXISTS `guardians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `guardians` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occupation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_primary_contact` tinyint(1) NOT NULL DEFAULT '0',
  `can_receive_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guardians_student_id_index` (`student_id`),
  KEY `guardians_user_id_index` (`user_id`),
  CONSTRAINT `guardians_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `guardians_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `guardians`
--

LOCK TABLES `guardians` WRITE;
/*!40000 ALTER TABLE `guardians` DISABLE KEYS */;
INSERT INTO `guardians` VALUES (1,6,1,'John','Dube','jdube@gmail.com','0771234567',NULL,'Father',NULL,NULL,NULL,NULL,1,1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(2,7,4,'Grace','Ncube','grace.ncube0@gmail.com','0771000000',NULL,'Father',NULL,NULL,NULL,NULL,1,1,'2026-09-03 11:38:40','2026-09-03 11:38:40'),(3,8,7,'Peter','Gumbo','peter.gumbo3@gmail.com','0771000411',NULL,'Mother',NULL,NULL,NULL,NULL,1,1,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(4,9,10,'Mary','Marufu','mary.marufu6@gmail.com','0771000822',NULL,'Father',NULL,NULL,NULL,NULL,1,1,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(5,10,13,'Joseph','Mangwana','joseph.mangwana9@gmail.com','0771001233',NULL,'Mother',NULL,NULL,NULL,NULL,1,1,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(6,11,16,'Ruth','Muleya','ruth.muleya12@gmail.com','0771001644',NULL,'Father',NULL,NULL,NULL,NULL,1,1,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(7,12,19,'Simon','Mabika','simon.mabika15@gmail.com','0771002055',NULL,'Mother',NULL,NULL,NULL,NULL,1,1,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(8,13,22,'Agnes','Dziva','agnes.dziva18@gmail.com','0771002466',NULL,'Father',NULL,NULL,NULL,NULL,1,1,'2026-09-03 11:38:42','2026-09-03 11:38:42');
/*!40000 ALTER TABLE `guardians` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2024_01_01_000010_create_staff_table',1),(5,'2024_01_01_000020_create_classes_table',1),(6,'2024_01_01_000030_create_subjects_table',1),(7,'2024_01_01_000031_create_subjects_full_table',1),(8,'2024_01_01_000040_create_students_table',1),(9,'2024_01_01_000050_create_guardians_table',1),(10,'2024_01_01_000060_create_teacher_subjects_table',1),(11,'2024_01_01_000070_create_applications_table',1),(12,'2024_01_01_000080_create_fee_structures_table',1),(13,'2024_01_01_000090_create_student_fees_table',1),(14,'2024_01_01_000100_create_payments_table',1),(15,'2024_01_01_000110_create_academic_progress_table',1),(16,'2024_01_01_000120_create_teacher_comments_table',1),(17,'2024_01_01_000130_create_attendance_table',1),(18,'2024_01_01_000140_create_behaviour_records_table',1),(19,'2024_01_01_000150_create_student_purchases_table',1),(20,'2024_01_01_000160_create_notifications_table',1),(21,'2024_01_01_000170_create_announcements_table',1),(22,'2024_01_01_000180_create_student_documents_table',1),(23,'2024_01_01_000190_create_timetables_table',1),(24,'2024_02_01_000010_create_academic_years_table',1),(25,'2024_02_01_000020_create_terms_table',1),(26,'2024_02_01_000030_create_forms_table',1),(27,'2024_02_01_000040_create_streams_table',1),(28,'2024_02_01_000050_create_subject_groups_table',1),(29,'2024_02_01_000070_create_teacher_allocations_table',1),(30,'2024_03_01_000010_create_departments_table',1),(31,'2024_03_01_000020_create_staff_members_table',1),(32,'2024_03_01_000030_create_teacher_profiles_table',1),(33,'2024_04_01_000010_add_username_to_users_table',1),(34,'2024_04_01_000020_add_student_number_to_students_table',1),(35,'2024_04_01_000030_add_national_id_to_guardians_table',1),(36,'2024_05_01_000010_create_fee_categories_table',1),(37,'2024_05_01_000020_create_finance_fee_structures_table',1),(38,'2024_05_01_000030_create_student_bills_table',1),(39,'2024_05_01_000040_create_finance_payments_table',1),(40,'2024_05_01_000050_create_payment_allocations_table',1),(41,'2024_05_01_000060_create_student_account_transactions_table',1),(42,'2024_06_01_000010_create_shop_categories_table',1),(43,'2024_06_01_000020_create_shop_items_table',1),(44,'2024_06_01_000030_create_shop_purchases_table',1),(45,'2024_06_01_000040_create_shop_purchase_items_table',1),(46,'2024_06_01_000050_create_shop_purchase_payments_table',1),(47,'2024_07_01_000010_create_assessment_types_table',1),(48,'2024_07_01_000020_create_grading_scales_table',1),(49,'2024_07_01_000030_create_assessments_table',1),(50,'2024_07_01_000040_create_assessment_marks_table',1),(51,'2026_05_07_202449_create_personal_access_tokens_table',1),(52,'2026_05_10_000001_align_school_shop_purchase_tables',1),(53,'2026_05_10_000002_add_storekeeper_role_to_users_enum',1),(54,'2026_05_10_000003_relax_legacy_student_purchase_columns',1),(55,'2026_05_10_000004_create_attendance_behaviour_discipline_module',1),(56,'2026_05_10_000005_create_academic_progress_foundation_tables',1),(57,'2026_05_10_153244_create_permission_tables',1),(58,'2026_05_11_000010_create_report_card_module_tables',1),(59,'2026_05_11_000011_add_financial_clearance_status_to_report_cards',1),(60,'2026_05_11_000020_create_timetable_scheduling_module_tables',1),(61,'2026_05_11_131437_create_timetable_periods_table',1),(62,'2026_05_11_131438_create_timetable_rooms_table',1),(63,'2026_05_11_131439_create_school_timetables_table',1),(64,'2026_05_22_000001_create_categories_table',1),(65,'2026_05_22_000002_add_category_id_to_streams_table',1),(66,'2026_05_22_000003_add_stream_compatibility_columns_to_students_table',1),(67,'2026_05_25_000100_create_stream_native_results_engine_tables',1),(68,'2026_06_07_000001_add_fields_to_applications_table',1),(69,'2026_06_07_000002_add_application_academic_history_and_documents',1),(70,'2026_06_07_000003_add_application_draft_and_tracking_fields',1),(71,'2026_06_07_000004_make_application_fields_nullable_for_drafts',1),(72,'2026_06_07_000005_create_application_document_requests_table',1),(73,'2026_06_08_000001_update_applications_status_enum_for_admissions',1),(74,'2026_06_08_000002_add_offer_letter_fields_to_applications_table',1),(75,'2026_06_08_000003_add_offer_acceptance_to_applications_table',1),(76,'2026_08_05_000001_create_application_deposits_table',1),(77,'2026_08_05_000002_add_verification_fields_to_students_table',1),(78,'2026_08_05_000003_add_expired_to_applications_status_enum',1),(79,'2026_08_05_000004_add_deceased_to_students_status_enum',1),(80,'2026_09_04_000001_add_code_and_frequency_to_fee_categories_table',2),(81,'2026_09_04_000002_add_guardian_and_status_to_finance_payments_table',2),(82,'2026_09_04_000003_create_payment_adjustments_table',2),(83,'2026_09_06_000001_add_account_credit_to_shop_payment_method',3),(84,'2026_09_07_000001_add_payment_reference_to_finance_payments_table',4),(85,'2026_09_07_000002_add_purchase_reference_to_student_purchases_table',5),(86,'2026_09_08_000001_add_collection_tracking_to_student_purchases_table',6),(87,'2026_09_08_000002_add_size_and_preorder_support',7);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES (1,'App\\Models\\User',1),(2,'App\\Models\\User',2),(4,'App\\Models\\User',3),(3,'App\\Models\\User',4),(3,'App\\Models\\User',5),(6,'App\\Models\\User',6),(6,'App\\Models\\User',7),(6,'App\\Models\\User',8),(6,'App\\Models\\User',9),(6,'App\\Models\\User',10),(6,'App\\Models\\User',11),(6,'App\\Models\\User',12),(6,'App\\Models\\User',13);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
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
-- Table structure for table `payment_adjustments`
--

DROP TABLE IF EXISTS `payment_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_adjustments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `finance_payment_id` bigint unsigned NOT NULL,
  `type` enum('reversal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reversal',
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `adjusted_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_adjustments_finance_payment_id_foreign` (`finance_payment_id`),
  KEY `payment_adjustments_adjusted_by_foreign` (`adjusted_by`),
  CONSTRAINT `payment_adjustments_adjusted_by_foreign` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`),
  CONSTRAINT `payment_adjustments_finance_payment_id_foreign` FOREIGN KEY (`finance_payment_id`) REFERENCES `finance_payments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_adjustments`
--

LOCK TABLES `payment_adjustments` WRITE;
/*!40000 ALTER TABLE `payment_adjustments` DISABLE KEYS */;
INSERT INTO `payment_adjustments` VALUES (1,21,'reversal','Smoke test reversal',1,'2026-09-04 07:13:30','2026-09-04 07:13:30'),(2,9,'reversal','k',1,'2026-09-04 10:40:39','2026-09-04 10:40:39'),(3,23,'reversal','Smoke test: undo overpayment',3,'2026-09-06 19:22:50','2026-09-06 19:22:50');
/*!40000 ALTER TABLE `payment_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_allocations`
--

DROP TABLE IF EXISTS `payment_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_id` bigint unsigned NOT NULL,
  `student_bill_id` bigint unsigned NOT NULL,
  `amount_allocated` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_allocations_payment_id_index` (`payment_id`),
  KEY `payment_allocations_student_bill_id_index` (`student_bill_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_allocations`
--

LOCK TABLES `payment_allocations` WRITE;
/*!40000 ALTER TABLE `payment_allocations` DISABLE KEYS */;
INSERT INTO `payment_allocations` VALUES (1,1,1,300.00,'2026-09-03 11:26:02','2026-09-03 11:26:02'),(2,2,2,310.75,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(3,3,5,369.00,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(4,4,8,450.00,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(5,4,9,40.00,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(6,4,10,25.00,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(7,5,11,500.00,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(8,5,12,40.00,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(9,5,13,25.00,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(10,6,17,283.25,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(11,7,20,339.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(12,8,23,550.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(13,8,24,40.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(14,8,25,25.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(15,9,26,450.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(16,9,27,40.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(17,9,28,25.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(18,10,32,338.25,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(19,11,35,309.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(20,12,38,500.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(21,12,39,40.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(22,12,40,25.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(23,13,41,550.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(24,13,42,40.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(25,13,43,25.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(26,14,47,310.75,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(27,15,50,369.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(28,16,53,450.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(29,16,54,40.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(30,16,55,25.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(31,17,56,500.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(32,17,57,40.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(33,17,58,25.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(34,18,62,283.25,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(35,19,65,339.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(36,20,1,5.00,'2026-09-04 07:13:03','2026-09-04 07:13:03'),(37,20,68,5.00,'2026-09-04 07:13:03','2026-09-04 07:13:03'),(38,21,1,5.00,'2026-09-04 07:13:30','2026-09-04 07:13:30'),(39,21,68,5.00,'2026-09-04 07:13:30','2026-09-04 07:13:30'),(40,22,14,12.00,'2026-09-06 19:10:35','2026-09-06 19:10:35'),(41,23,5,181.00,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(42,23,6,40.00,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(43,23,7,25.00,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(44,24,2,189.25,'2026-09-06 19:23:04','2026-09-06 19:23:04'),(45,24,3,40.00,'2026-09-06 19:23:04','2026-09-06 19:23:04'),(46,24,4,25.00,'2026-09-06 19:23:04','2026-09-06 19:23:04'),(47,25,14,538.00,'2026-09-06 19:27:13','2026-09-06 19:27:13'),(48,25,15,40.00,'2026-09-06 19:27:13','2026-09-06 19:27:13'),(49,25,16,25.00,'2026-09-06 19:27:13','2026-09-06 19:27:13'),(52,31,101,234.00,'2026-09-07 18:26:16','2026-09-07 18:26:16');
/*!40000 ALTER TABLE `payment_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `student_fee_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','bank_transfer','check','online') COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_date` date NOT NULL,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_payment_number_unique` (`payment_number`),
  UNIQUE KEY `payments_receipt_number_unique` (`receipt_number`),
  KEY `payments_student_fee_id_foreign` (`student_fee_id`),
  KEY `payments_recorded_by_foreign` (`recorded_by`),
  KEY `payments_student_id_index` (`student_id`),
  KEY `payments_payment_date_index` (`payment_date`),
  CONSTRAINT `payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_student_fee_id_foreign` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fees` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,'PAY-20260806-0001',1,1,350.00,'cash','2026-01-25','RCP-20260806-0001',NULL,3,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(2,'PAY-20260806-0002',1,2,150.00,'bank_transfer','2026-05-10','RCP-20260806-0002',NULL,3,'2026-08-06 08:20:54','2026-08-06 08:20:54');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard-view','web','2026-09-04 07:06:46','2026-09-04 07:06:46'),(2,'academic-years-list','web','2026-09-04 07:06:46','2026-09-04 07:06:46'),(3,'academic-years-create','web','2026-09-04 07:06:46','2026-09-04 07:06:46'),(4,'academic-years-edit','web','2026-09-04 07:06:46','2026-09-04 07:06:46'),(5,'academic-years-delete','web','2026-09-04 07:06:46','2026-09-04 07:06:46'),(6,'academic-years-activate','web','2026-09-04 07:06:46','2026-09-04 07:06:46'),(7,'terms-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(8,'terms-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(9,'terms-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(10,'terms-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(11,'terms-set-current','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(12,'forms-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(13,'forms-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(14,'streams-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(15,'streams-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(16,'streams-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(17,'streams-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(18,'subject-groups-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(19,'subject-groups-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(20,'subject-groups-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(21,'subject-groups-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(22,'subjects-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(23,'subjects-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(24,'subjects-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(25,'subjects-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(26,'teacher-allocation-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(27,'teacher-allocation-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(28,'teacher-allocation-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(29,'departments-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(30,'departments-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(31,'departments-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(32,'departments-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(33,'staff-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(34,'staff-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(35,'staff-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(36,'staff-show','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(37,'staff-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(38,'staff-activate','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(39,'staff-suspend','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(40,'staff-resign','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(41,'staff-assign-role','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(42,'teacher-profiles-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(43,'teacher-profiles-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(44,'teacher-profiles-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(45,'students-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(46,'students-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(47,'students-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(48,'students-show','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(49,'students-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(50,'guardians-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(51,'guardians-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(52,'guardians-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(53,'guardians-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(54,'fees-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(55,'fees-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(56,'fees-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(57,'payments-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(58,'payments-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(59,'fee-structures-list','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(60,'fee-structures-create','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(61,'fee-structures-edit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(62,'fee-structures-delete','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(63,'finance-reports-view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(64,'finance.reverse-payment','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(65,'shop.view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(66,'shop.manage_categories','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(67,'shop.manage_items','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(68,'shop.record_purchase','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(69,'shop.record_payment','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(70,'shop.cancel_purchase','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(71,'shop.view_reports','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(72,'shop.parent_view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(73,'attendance.view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(74,'attendance.mark','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(75,'attendance.submit','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(76,'attendance.edit_submitted','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(77,'attendance.reports','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(78,'behaviour.view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(79,'behaviour.manage_categories','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(80,'behaviour.record_incident','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(81,'behaviour.review_incident','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(82,'discipline.create_action','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(83,'discipline.manage_actions','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(84,'discipline.reports','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(85,'notifications.view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(86,'parent.attendance_view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(87,'parent.behaviour_view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(88,'student.attendance_view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(89,'student.behaviour_view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(90,'academics.assign_stream_subjects','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(91,'academics.allocate_teachers','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(92,'academics.enrol_student_subjects','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(93,'academics.drop_subjects','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(94,'academics.view_subject_reports','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(95,'assessments.view','web','2026-09-04 07:06:47','2026-09-04 07:06:47'),(96,'assessments.manage_types','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(97,'assessments.create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(98,'assessments.enter_marks','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(99,'assessments.submit_marks','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(100,'assessments.approve_marks','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(101,'assessments.reopen_marks','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(102,'assessments.reports','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(103,'parent.assessment_view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(104,'student.assessment_view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(105,'reports.generate','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(106,'reports.publish','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(107,'reports.approve','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(108,'reports.view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(109,'reports.download','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(110,'reports.batch_print','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(111,'parent.report_view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(112,'student.report_view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(113,'timetable.manage_periods','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(114,'timetable.manage_rooms','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(115,'timetable.manage','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(116,'timetable.view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(117,'timetable.export','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(118,'teacher.timetable_view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(119,'student.timetable_view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(120,'parent.timetable_view','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(121,'marks-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(122,'marks-create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(123,'marks-edit','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(124,'attendance-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(125,'attendance-create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(126,'attendance-edit','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(127,'behaviour-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(128,'behaviour-create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(129,'behaviour-edit','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(130,'behaviour-review','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(131,'teacher-comments-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(132,'teacher-comments-create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(133,'teacher-comments-edit','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(134,'reports-academic','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(135,'reports-financial','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(136,'reports-attendance','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(137,'reports-behaviour','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(138,'announcements-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(139,'announcements-create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(140,'announcements-edit','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(141,'announcements-delete','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(142,'users-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(143,'users-create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(144,'users-edit','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(145,'users-show','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(146,'roles-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(147,'roles-create','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(148,'roles-edit','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(149,'roles-delete','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(150,'permissions-list','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(151,'permissions-assign','web','2026-09-04 07:06:48','2026-09-04 07:06:48');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (13,'App\\Models\\User',1,'spa-token','9fd109a5e75d962f10ccd6c29fdd89420bbd6cfa93c716ec7c4af670dcfe10e9','[\"*\"]','2026-09-14 15:51:14',NULL,'2026-09-03 10:45:29','2026-09-14 15:51:14'),(14,'App\\Models\\User',1,'spa-token','85d7b1458daaf3c7ec266bfc1fe3528488498a662ee139381906178343d79412','[\"*\"]','2026-09-13 11:21:15',NULL,'2026-09-03 15:31:38','2026-09-13 11:21:15');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `progression_rules`
--

DROP TABLE IF EXISTS `progression_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `progression_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `minimum_pass_mark` decimal(5,2) NOT NULL DEFAULT '50.00',
  `promotion_min_average` decimal(5,2) DEFAULT NULL,
  `promotion_max_failed_subjects` int unsigned DEFAULT NULL,
  `repeat_below_average` decimal(5,2) DEFAULT NULL,
  `supplementary_below_average` decimal(5,2) DEFAULT NULL,
  `required_subject_ids_json` text COLLATE utf8mb4_unicode_ci,
  `withhold_results_on_balance` tinyint(1) NOT NULL DEFAULT '1',
  `withhold_balance_threshold` decimal(12,2) NOT NULL DEFAULT '0.00',
  `gpa_scale_json` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `progression_rules_unique_scope` (`academic_year_id`,`form_id`,`category_id`),
  KEY `progression_rules_academic_year_id_index` (`academic_year_id`),
  KEY `progression_rules_form_id_category_id_index` (`form_id`,`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `progression_rules`
--

LOCK TABLES `progression_rules` WRITE;
/*!40000 ALTER TABLE `progression_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `progression_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_card_signatures`
--

DROP TABLE IF EXISTS `report_card_signatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_card_signatures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_card_id` bigint unsigned NOT NULL,
  `class_teacher_signed` tinyint(1) NOT NULL DEFAULT '0',
  `headmaster_signed` tinyint(1) NOT NULL DEFAULT '0',
  `class_teacher_signed_at` timestamp NULL DEFAULT NULL,
  `headmaster_signed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_card_signatures_report_card_id_unique` (`report_card_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_card_signatures`
--

LOCK TABLES `report_card_signatures` WRITE;
/*!40000 ALTER TABLE `report_card_signatures` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_card_signatures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_card_skill_assessments`
--

DROP TABLE IF EXISTS `report_card_skill_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_card_skill_assessments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_card_id` bigint unsigned NOT NULL,
  `skill_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_card_skill_assessments`
--

LOCK TABLES `report_card_skill_assessments` WRITE;
/*!40000 ALTER TABLE `report_card_skill_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_card_skill_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_card_subjects`
--

DROP TABLE IF EXISTS `report_card_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_card_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_card_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `subject_average` decimal(5,2) DEFAULT NULL,
  `subject_grade` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_points` int unsigned DEFAULT NULL,
  `class_average` decimal(5,2) DEFAULT NULL,
  `subject_position` int unsigned DEFAULT NULL,
  `teacher_comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_report_subject` (`report_card_id`,`subject_id`),
  KEY `report_card_subjects_subject_id_index` (`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_card_subjects`
--

LOCK TABLES `report_card_subjects` WRITE;
/*!40000 ALTER TABLE `report_card_subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_card_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `report_cards`
--

DROP TABLE IF EXISTS `report_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `overall_average` decimal(5,2) DEFAULT NULL,
  `overall_grade` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overall_points` int unsigned DEFAULT NULL,
  `class_position` int unsigned DEFAULT NULL,
  `stream_total_students` int unsigned DEFAULT NULL,
  `attendance_percentage` decimal(5,2) DEFAULT NULL,
  `conduct_grade` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `performance_trend` enum('improved','declined','maintained') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'maintained',
  `teacher_comment` text COLLATE utf8mb4_unicode_ci,
  `headmaster_comment` text COLLATE utf8mb4_unicode_ci,
  `recommendation` text COLLATE utf8mb4_unicode_ci,
  `promotion_status` enum('promoted','probation','repeat','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `fees_balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `financial_clearance_status` enum('cleared','pending') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `generated_by` bigint unsigned DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `status` enum('draft','generated','approved','published') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_term_report` (`student_id`,`academic_year_id`,`term_id`),
  UNIQUE KEY `report_cards_report_number_unique` (`report_number`),
  KEY `report_cards_academic_year_id_term_id_stream_id_index` (`academic_year_id`,`term_id`,`stream_id`),
  KEY `report_cards_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `report_cards`
--

LOCK TABLES `report_cards` WRITE;
/*!40000 ALTER TABLE `report_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `report_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results_aggregates`
--

DROP TABLE IF EXISTS `results_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `stream_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `term_average` decimal(6,2) DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `subjects_total` int unsigned NOT NULL DEFAULT '0',
  `subjects_entered` int unsigned NOT NULL DEFAULT '0',
  `subjects_passed` int unsigned NOT NULL DEFAULT '0',
  `is_withheld` tinyint(1) NOT NULL DEFAULT '0',
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `results_aggregates_unique_student_term` (`student_id`,`academic_year_id`,`term_id`),
  KEY `results_aggregates_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `results_aggregates_stream_id_form_id_index` (`stream_id`,`form_id`),
  KEY `results_aggregates_category_id_index` (`category_id`),
  KEY `results_aggregates_is_withheld_index` (`is_withheld`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results_aggregates`
--

LOCK TABLES `results_aggregates` WRITE;
/*!40000 ALTER TABLE `results_aggregates` DISABLE KEYS */;
/*!40000 ALTER TABLE `results_aggregates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results_group_aggregates`
--

DROP TABLE IF EXISTS `results_group_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results_group_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `group_type` enum('stream','form','category','subject_stream','subject_form','subject_category') COLLATE utf8mb4_unicode_ci NOT NULL,
  `group_id` bigint unsigned DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `average` decimal(6,2) DEFAULT NULL,
  `student_count` int unsigned NOT NULL DEFAULT '0',
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `results_group_aggregates_unique` (`academic_year_id`,`term_id`,`group_type`,`group_id`,`subject_id`),
  KEY `results_group_aggregates_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `results_group_aggregates_group_type_group_id_index` (`group_type`,`group_id`),
  KEY `results_group_aggregates_subject_id_index` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results_group_aggregates`
--

LOCK TABLES `results_group_aggregates` WRITE;
/*!40000 ALTER TABLE `results_group_aggregates` DISABLE KEYS */;
/*!40000 ALTER TABLE `results_group_aggregates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `results_rankings`
--

DROP TABLE IF EXISTS `results_rankings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `results_rankings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `ranking_type` enum('stream','form','category','subject_stream','subject_form','subject_category') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ranking_id` bigint unsigned DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `score` decimal(6,2) DEFAULT NULL,
  `rank` int unsigned DEFAULT NULL,
  `is_withheld` tinyint(1) NOT NULL DEFAULT '0',
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `results_rankings_unique` (`academic_year_id`,`term_id`,`ranking_type`,`ranking_id`,`subject_id`,`student_id`),
  KEY `results_rankings_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `results_rankings_ranking_type_ranking_id_index` (`ranking_type`,`ranking_id`),
  KEY `results_rankings_subject_id_index` (`subject_id`),
  KEY `results_rankings_student_id_index` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `results_rankings`
--

LOCK TABLES `results_rankings` WRITE;
/*!40000 ALTER TABLE `results_rankings` DISABLE KEYS */;
/*!40000 ALTER TABLE `results_rankings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES (1,1),(2,1),(3,1),(4,1),(5,1),(6,1),(7,1),(8,1),(9,1),(10,1),(11,1),(12,1),(13,1),(14,1),(15,1),(16,1),(17,1),(18,1),(19,1),(20,1),(21,1),(22,1),(23,1),(24,1),(25,1),(26,1),(27,1),(28,1),(29,1),(30,1),(31,1),(32,1),(33,1),(34,1),(35,1),(36,1),(37,1),(38,1),(39,1),(40,1),(41,1),(42,1),(43,1),(44,1),(45,1),(46,1),(47,1),(48,1),(49,1),(50,1),(51,1),(52,1),(53,1),(54,1),(55,1),(56,1),(57,1),(58,1),(59,1),(60,1),(61,1),(62,1),(63,1),(64,1),(65,1),(66,1),(67,1),(68,1),(69,1),(70,1),(71,1),(72,1),(73,1),(74,1),(75,1),(76,1),(77,1),(78,1),(79,1),(80,1),(81,1),(82,1),(83,1),(84,1),(85,1),(86,1),(87,1),(88,1),(89,1),(90,1),(91,1),(92,1),(93,1),(94,1),(95,1),(96,1),(97,1),(98,1),(99,1),(100,1),(101,1),(102,1),(103,1),(104,1),(105,1),(106,1),(107,1),(108,1),(109,1),(110,1),(111,1),(112,1),(113,1),(114,1),(115,1),(116,1),(117,1),(118,1),(119,1),(120,1),(121,1),(122,1),(123,1),(124,1),(125,1),(126,1),(127,1),(128,1),(129,1),(130,1),(131,1),(132,1),(133,1),(134,1),(135,1),(136,1),(137,1),(138,1),(139,1),(140,1),(141,1),(142,1),(143,1),(144,1),(145,1),(146,1),(147,1),(148,1),(149,1),(150,1),(151,1),(1,2),(2,2),(7,2),(12,2),(14,2),(18,2),(22,2),(26,2),(29,2),(33,2),(36,2),(42,2),(45,2),(46,2),(47,2),(48,2),(50,2),(51,2),(54,2),(57,2),(59,2),(63,2),(71,2),(77,2),(81,2),(83,2),(84,2),(85,2),(94,2),(95,2),(100,2),(101,2),(102,2),(105,2),(106,2),(107,2),(108,2),(109,2),(110,2),(113,2),(114,2),(115,2),(116,2),(117,2),(121,2),(124,2),(127,2),(130,2),(131,2),(134,2),(135,2),(136,2),(137,2),(138,2),(139,2),(140,2),(141,2),(1,3),(45,3),(48,3),(73,3),(74,3),(75,3),(78,3),(80,3),(94,3),(95,3),(97,3),(98,3),(99,3),(108,3),(118,3),(121,3),(122,3),(123,3),(124,3),(125,3),(126,3),(127,3),(128,3),(131,3),(132,3),(133,3),(138,3),(1,4),(45,4),(48,4),(54,4),(55,4),(56,4),(57,4),(58,4),(59,4),(60,4),(61,4),(63,4),(65,4),(68,4),(69,4),(71,4),(138,4),(1,5),(45,5),(48,5),(65,5),(67,5),(68,5),(138,5),(1,6),(72,6),(85,6),(86,6),(87,6),(94,6),(103,6),(111,6),(120,6),(138,6),(1,7),(85,7),(88,7),(89,7),(94,7),(104,7),(112,7),(119,7),(138,7),(1,8);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'admin','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(2,'headmaster','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(3,'teacher','web','2026-09-04 07:06:48','2026-09-04 07:06:48'),(4,'bursar','web','2026-09-04 07:06:49','2026-09-04 07:06:49'),(5,'storekeeper','web','2026-09-04 07:06:49','2026-09-04 07:06:49'),(6,'parent','web','2026-09-04 07:06:49','2026-09-04 07:06:49'),(7,'student','web','2026-09-04 07:06:49','2026-09-04 07:06:49'),(8,'user','web','2026-09-04 07:06:49','2026-09-04 07:06:49');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_notifications`
--

DROP TABLE IF EXISTS `school_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` enum('fee_reminder','fee_overdue','attendance_alert','behaviour_warning','academic_progress','announcement') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `related_student_id` bigint unsigned DEFAULT NULL,
  `related_record_id` bigint unsigned DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `email_sent` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_notifications_user_id_index` (`user_id`),
  KEY `school_notifications_type_index` (`type`),
  CONSTRAINT `school_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_notifications`
--

LOCK TABLES `school_notifications` WRITE;
/*!40000 ALTER TABLE `school_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `school_timetables`
--

DROP TABLE IF EXISTS `school_timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `school_timetables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `room_id` bigint unsigned DEFAULT NULL,
  `period_id` bigint unsigned NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `timetable_type` enum('class','exam','remedial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'class',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `school_timetables_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `school_timetables_form_id_index` (`form_id`),
  KEY `school_timetables_stream_id_index` (`stream_id`),
  KEY `school_timetables_teacher_id_index` (`teacher_id`),
  KEY `school_timetables_room_id_index` (`room_id`),
  KEY `school_timetables_period_id_index` (`period_id`),
  KEY `school_timetables_day_of_week_index` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `school_timetables`
--

LOCK TABLES `school_timetables` WRITE;
/*!40000 ALTER TABLE `school_timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `school_timetables` ENABLE KEYS */;
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
INSERT INTO `sessions` VALUES ('0aHjaozx2z5832PI3UnxYk42Ps6NZ8xTnm9EQTxl',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJDQ1U1VDE2SFIxbkJ4MGZVU3VvMVVQQXhQamo0aktOQURLWnp1cWJvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLz9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369666),('12DWZFChv91Ww6mvjkqLDtTa4MLuVrLExDBK1DBU',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJiYkJNNlg1cnZrRmhWY2VUWjc0QWZ6bG5pYnhqNHJvckl2Y2dxSXhwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvcG9zdHNcLzk5OTk5OCIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369829),('1m8PLw01aG1uQ4VjL4PAKRbhsaiiwACWMwgdndxh',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI1djc2VkJsTTRmYWlvTWFhcGZzclZ2cmJrOFhrZzA1ZVB2UXFibW1UIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGcG9zdHMlMkY5OTk5OTgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369829),('2IILtEyjge4bnGZmy6EMByMwQDz5AYBl0HbACQDj',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJENG9KaTFQS2R1SHBSYXVQT1B4NjBFZHRGQlFRQnZtcllqMm5ocmxwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL1wvd3AtanNvblwvYmF0Y2hcL3YxIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369672),('2KWOVuXfDBkMt1lKTDaLKAJ1UwsKqhYXi8qKmmcT',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJWUmxHVUhlNHFyZURBa0MwWGRNZTdaeTNKM0hwSFdaNkxmMEJRSWxvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL21lZGlhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369757),('2p8uGe5TwXvf2RmcFfmmSPy1hZAVNqhNP26bVtkr',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJDSkVnQm1jUG54Y3N6ckNKeFl4eDM5bDNvNmx1YkpKNXI2TFQzNTVMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL2NhdGVnb3JpZXMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369757),('2tNBXI0LXcw06zBnShScWiOCFO6FvenFBIwfay2G',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJEUVkwTGpEVFZ3eUxSZE1wbWxXUkNld2VFdjNKaXpHM1ZQa2JDbDBMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLz9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzJnJlc3Rfcm91dGU9JTJGZ3Jhdml0eXNtdHAlMkZ2MSUyRnRlc3RzJTJGbW9jay1kYXRhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314269),('38DClURsxFIwKnFvVL3nUs3mForq9XOgn5I9OCig',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJrUTZaQVVEcklXY2IyckpNWTRpa01iTXhuWTh3VnFNQTR0SDd3QjlpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369685),('3E8A0X4ZqtAzH4Dy1x4qaN2Jmk1U8GHKKSLkZHx1',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI1aUVJelRTcmdyS25tZTBLSjcyeXc2M0FYZGQ2RWVGZE03b2NucHc3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC90YWdzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369793),('5J8C8rUTWvAfuHsfC4cvmde4Vz1djQ3Eux1cbmOA',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJaeUhDVmlvMnpqMHNtR1RWcUlkMU9GM2F1aXlFZWVMOExVaFFrNkdaIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwLWpzb25cL2JhdGNoXC92MSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369674),('5pJuZtxEt4caWwb8wIzu3tZjmKhEFnTrhNoBVkto',NULL,'62.169.18.30','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJRVGFJQlZGc05aaldsd1J4a2NaVUhuSUlmWjZLVTdhVEw2MUU0UDN6IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789314298),('6Wt0bNLvUAsa2niDudNL9kGiTsWVVm79f52xo7By',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJaYkc1dnZmMDA5cEh0YnVic3JuS1dqdDFsUFZQVEZjbjJnVWRKZTNtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL3BhZ2VzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369757),('7EHoZ4xJjt3s8mtFAhpobqUvvhrLmK0hD5Yy3biL',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJZWklwdERFaEc2NUh4QktReFBpQno3Y1h2MFNuYlNhSjd6a0FscTRUIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGcG9zdHMlMkY5OTk5OSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369830),('87I94FdmLoUelFjZLklsW2BEj071MyvA3lSf6ZuB',NULL,'62.169.18.30','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJDV2xtazZnbEJZWVo0d3BWOVRVQ0ZOV3F1VXZVOUNUbEd2QmV2dWxxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzJnJlc3Rfcm91dGU9JTJGZ3Jhdml0eXNtdHAlMkZ2MSUyRnRlc3RzJTJGbW9jay1kYXRhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314276),('99JC2yqL1AQaxx2DB1BAcemhh8evkcbiIbU6BHIY',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJMZFUyZnlwQW5yYnlZZ2FGUkRpN0ZMTzJuRjF4eFloWUlwWUQ0OXJHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC9wb3N0c1wvOTk5OTk4Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369794),('AHp2ofLsLLruyWezUAmeRySrh1lUV5UV4EeHblvB',NULL,'62.169.18.30','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI3WWlDblJvdjNBOVNpRnJNazV4UWtLM210RU1DZzNCdzE2SURIeWp2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789314232),('B5lMffPDQY3nfT49an2LI0OV9wqEjHEHpTozerf3',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJsMUNwNnFLTDBxODlvZzI1ZFdTVWM1UjhaRVY2OGxobHFIbG9QM1ZxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvY2F0ZWdvcmllcyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369828),('BlcKHGpIC4eXu9ECQiiwal3f9izW0LKuTCPOfRbA',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI0OUc2elcxS1BRbkg2b0pGOHN1aWdDU2JhVmJLWVB5OURiajhzWTJ4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC9wb3N0c1wvOTk5OTkiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369795),('BYHxYim0YzZE34cuqoI0urMYvPGdWKay1YBFpepi',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJOSGJTQ2Z4RUg4R1hNWnhvR2l1YXp5QzZUbDRhbmFXWnhqYmdhZkIxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRnRhZ3MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369758),('cNO5FM5SZPLeahVPe3aPudDuMxdh4X0PYBB4LN66',NULL,'173.214.163.142','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI2eVJIZnZicUVXM1hlVjJkb1pHSXh4OTAzMmpNbDZxVVRHYm95ZWdjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789466728),('doSD3rufsbYmmvpf8H4oqnxIQ4GX792JLAkys3ao',NULL,'62.169.18.30','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ6MVNTUkdjbDBnSFF1VVBLQ0VMS0gybTVOWXFMMnlRUVVUOU9FZUJtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzJnJlc3Rfcm91dGU9JTJGZ3Jhdml0eXNtdHAlMkZ2MSUyRnRlc3RzJTJGbW9jay1kYXRhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314211),('DtnFYrJUBc46Gs39FGif3ZIHt54SgCW1wHKdB5Sk',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI3MTBZWEJSS2tZcEowUUU3eGMyYm5WbUdMRDV2ZktSbVc1Z1RNS215IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL3Bvc3RzXC85OTk5OTk5Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369759),('Enl3o3u5DNScHWzANqpZKCQELEcimTNBAgVISlml',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ4dFo0MnVLMWNwVllEVDJrWDM0a3pCUVM2UFhuVFFHajNKM21aZVU0IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRnBvc3RzJTJGOTk5OTk5Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369752),('F6ROXJoGw9nFyeFBnPbscS2eqbkEADhbpMj0KRIa',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ6WllYR000UUE0VlBQdEtndkN2MXZXOEhlTjJWQjFPZktOZWIzZDVkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369654),('F8Xbjw7Ii3n29SvsILDneMsNqHZOYF1teaP6yeSD',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJmdm5MUzVyWDRaMEtuaXBOdmdPTnpORGFYdlREaXpKRU1yd2NPYkxCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvbWVkaWEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369828),('FLI1CvUbLnlnuPfaZb0QmeFdkoQxPCVFFY9Decy0',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJNTW8wZHN6VDh5c0hHVG83MG4xbDRWTzM2aExvQnBwUFNROHdUamNsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3AtanNvblwvYmF0Y2hcL3YxIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369675),('fne2zXrJqYZ3fVi523smsSxVJ0BJceYL6czJuUOP',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJQYnJ4eTN6UWtQTEk0NHo5Vms1VkFJMzRJNllpdXlTYlpKWTR6Z28yIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZ1c2VycyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369797),('FuIMdnfoBuUvtEoTSeryfa7cUTIuixi32VmqPYNn',NULL,'62.169.18.30','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJRT1BIb0NYT0w0M1F2NHE5UUdKcjB6Rm5pQmNFWWhJNkxZN3lvUUVVIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YT9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314248),('fUUuJVCHTpFgMqdInIytKTJOLQm3clufsar4disl',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjdzR0Z0hKTFViQlY1N1pRbnVkeXVyekNuWWxxWTRNbEZTQWZrNmQzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvcG9zdHNcLzk5OTk5OSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369827),('fwtSlEuN5RIhQ0Iuuzv59l55dVYNQinUFO6gUrDQ',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqVUNVdlF1dzhadDNzd2Q2N0E4cUZId21yRVFxcnFVMnBuQ3lKTjdCIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL3VzZXJzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369771),('FyGb40QscSQDLC2fueOzlJMzyfbfVS6PNqLsz3hD',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJmU2VOakdHVU1KN3hqOUk5Z2V1QlFQZlJSZjRya2I0aGtsNEt5VXhYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC91c2VycyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369796),('Fzm9Xp3ShTmODHC4qJsxySe7sFdAb0hiqu1o5uQN',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJEZzNJM3hEMmJvZG9ISW5RZUpEVktwbUhQZTJkRkVXc1NEaU90dE00IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRnBhZ2VzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369757),('gOQsazLPkLu7zAHbYdawQHZz8OK0i4Bp7cMf7B8n',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJIN1dtb3pyQUFoeG9UV3AzMVhzQlR2ZGJzUzh3SURhR0FQc1lNSGVIIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvdXNlcnMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369830),('GS67txYhf15DvTprA5vm3mnYJK6yfeXggfkdKbK4',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjUXBpcFJ2a2pUUnRrTU1xQUpCaXlvb0NLWWxmeFRFVGJCcVVpVW5JIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789314266),('HaM8Ub31QiZnhvk8EtcjG6eyaxS5FhP9CjSGtK9A',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJpU2Mzd1pNVnVrcmo3dm1VSmV5Y2lVanJsRDZNeE1rOE10VTNIWGtRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvcG9zdHNcLzk5OTk5Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369830),('HHDR74LMVjw2wYtvRGdnprHtA1sE8mhMK0YhDkuj',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJaQVFPcnhITDFYc0lVc3JjQTNWaXNNV256QWRqWjVIUVBTN3I5Rm44IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRnBvc3RzJTJGOTk5OTk5OSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369759),('HiJ5qOUPStxWpbxCILdMEXWfeCyj2Z9QqtUMFXKi',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI3Q2FDMHhYZTUwTDd0cUtxMFdwTHRyY1Q5cFRVeXFLNHBQN0xoQlZFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369675),('HKzpurctfc9HgrDDB33PlSvJQYnvp5xaZHKaZmkN',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI4MXp0dmtiZjJ6bWxqTzZSRWliVGJZVDlvTzFBRHlXQWpHOTBrRTB2IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGbWVkaWEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369828),('i7ojhkSIrmKWzNsutgsxZGuZYwR7NH8k82qOTrcO',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJXR3JNNkFhSEZ4OXdja2dDeDdhTUdRVnlacTZqek5wcFpMOEtwNlVRIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRmNhdGVnb3JpZXMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369758),('IHKsBhIhM1IRsHwNj46SwrBfVElsl0e8JQ142zRM',NULL,'190.251.207.95','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJESGVxelhrZmtyODJqY3pLT214M0doSmQ2dzhMNEdVSVV1cmNOdHlDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789359070),('iNGw941RSf76pFKanCUeUDqnEhgiRv05PDivxLUx',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJhNkhNOE5zZ0ZUUkJYVWEwSkhlWXJCeXhoaHVmc0lhYVVMN3FBNzhvIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLz9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369653),('IOF4LEVKoBYByl31m4B3XSd8oxNr9zwoTPkmwyLp',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJlU3lXcVZDS2t6UGpiQVZhS2dLcUlDU3NDMHdNRXhxMTBvZjYzdENmIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZwb3N0cyUyRjk5OTk5OTkiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369796),('izZXrRUBAdIKaar3Vo5aWamaWSFOV31tGKo4gyVv',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJyc1JhbldKUFJwbHR0Mk5XeVllZzZydHBQVEU1Z3BLRk44aE5wdW44IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZtZWRpYSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369791),('J6PRSMhLOoL3qxKLO3wCPoKTg3k3S314c5f63Qzk',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJyUVpCRFVtVExWN1dVSTk4TEl0U1doNHdPR2Y3OG9QeUlrbU9ldUFHIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZwb3N0cyUyRjk5OTk5OSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369790),('klqP7fNgAmINV6iJmyxAZb9BbFK05uL8WwBccojN',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJWODB1UGs5TW90ZkhZQ2JWOHYzeEZtQ2tOT1hlMVR5TndaU2Y4cllXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZwb3N0cyUyRjk5OTk5Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369795),('KLwrUCr8G4jlfK0jc8zqlHlFwl2H9LDa81ygGKU2',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqTDJHNFd3empWTDY5U01maWNaNXlxMVh5emh5TE90cnJWUUZ0SENpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YT9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314263),('KVRUe7lHWLBTU29rDS35A3OLr8SzBcF0HRwWTE6f',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJadE4yMmVySXYxU09HQUN6Z1hzWDRTbjRGeGgzYko3TUEzRlN3SGVSIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRnVzZXJzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369771),('KvxvdMIiKGyT6QOo4l01D0SMOCjjENBUBrWBCTUj',NULL,'199.45.155.45','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','eyJfdG9rZW4iOiJ3UmVadk40S0FSZXpvTjZZdm1Nd09pb2VlRFR3dmsxZlFMS3J0WjA1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789421487),('KYu324Kj5CxPrkqmYs6EKoHEPkSY6mE3XxRDRPtG',1,'66.9.171.142','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Mobile Safari/537.36','eyJfdG9rZW4iOiJidEh3aFIwYXpLS25FWVZ1NnBhdjhkTHJrN0hhTW55Q3N3cW1kTE0zIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2FwaVwvY2F0ZWdvcmllcyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789305675),('l11JVlHrIbaU51yTJsw8DEEFrRna5pCz6SV5Sfw2',NULL,'62.169.18.30','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJpVDlpajYxaWtmTzRYUWszSUliRkJmT1p0SzhXZ3dxWnBOajRxbkkwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLz9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzJnJlc3Rfcm91dGU9JTJGZ3Jhdml0eXNtdHAlMkZ2MSUyRnRlc3RzJTJGbW9jay1kYXRhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314201),('Ld0NUvcTtOSiqcEfRVgu2ZaRjPQXYOhEZ942KfyP',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqRG1SWlp6bGJyTlJPN3JkSVk1V200RVZ6N0JPd2V3dDMxSjM4ZWEyIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZ0YWdzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369794),('LLnEDZdpuJZYHVkiocRQWBNVzN3R8oyDW0pO6Frg',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJLdXI3SjVGVjVNQ3YxNDNpRXNpbWNLTkZJU0JNNlNHMVB3MDhQaXBEIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2JhdGNoXC92MSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369671),('lurzCSHxx2R2mPYXvzWb1PPh3gh9ui9wv60hMtEC',NULL,'136.70.154.217','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/95.0.4638.69 Safari/537.36','eyJfdG9rZW4iOiJHMm5tR1N2N3lmb2VHblk0TnZKbHBpRzc0RVJHYmc5SnppenFSSFVDIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL1wvY21zXC93cC1pbmNsdWRlc1wvd2x3bWFuaWZlc3QueG1sIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789311884),('MbOia5HUzCRoAmpN1Mx8yp2eAFJ5Qgzds12GWy0j',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJaN2VoU1RSTGs5RVZkMktadld4RUJtZDFwb1NteXVVeXlTZDNVMVFOIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvcGFnZXMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369828),('n15cwkIwZaKra439HTWDkFhPRc3C2JoDlQuHEDsr',NULL,'74.7.243.201','Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; GPTBot/1.4; +https://openai.com/gptbot)','eyJfdG9rZW4iOiJnOVpiaHdLUlNaTG1QSWpTeGw2WktKelZiaG9ZaFQyWmsxME1CTVp1IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789431290),('NkttN4ovWFzNymvupUKik8DEE2ebUSM0JwjxlGdx',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJucUd0YkRISERCNlFrdW9lb1NMSDhDcGpGQ3lnSFBiblEwam13aERzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzJnJlc3Rfcm91dGU9JTJGZ3Jhdml0eXNtdHAlMkZ2MSUyRnRlc3RzJTJGbW9jay1kYXRhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314270),('nm62MzX896sDlZv7YrJ99QMNBzs5xX818TJ2b1bU',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJZeHExSlBVZG9JbkZCb2JrVHdYY0RFVkhZU2JmakQ1ekJ3blNTbUZNIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789314272),('NtoknVyywFohWKuvD7LIGvxpXrer5u9ljwJRglmO',NULL,'103.215.75.19','Mozilla/5.0','eyJfdG9rZW4iOiJQajFkSU9WcGtoMnVQUm1oUTc4Qklha0JYR0VJdlRKUlBuSUNDN2FjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2xpdmV3aXJlXC91cGRhdGUiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369692),('o3sP0ck0cOWdv2O7EhVZQF4BfeT45m8ynwLf8E1p',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJvOHNzVnlyb3hBTVVTYXRaNGdqMlhHSjFjdlpzbFNkM1ZmeEpIZm5aIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL3RhZ3MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369758),('O8iZyvcw9fXchAO8PzQurBsqJLtSXevCTrEs0SOI',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJrUnFCVHRMRGNkQ0FwRGlFbzhsclhNVzRzT3RmYjBWSGl2OHBMaEhtIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLz9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369665),('oekpTwGWkNKgIjApIlm1SC2rJCsp0KXRmV3S3JqZ',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqVkZoNWJpT2xjOUFPYXlMNU9qNkM5dmxQWDM4OThsamx5aEJhQ010IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZjYXRlZ29yaWVzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369793),('OWroOBchs716DTmX1ci0LXbcMLhPGYinUUn4xJYm',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI4ak9BOTVYZDA4eVd5Tlo4QUhua1RWdWtycVpjTDdtYVd5ZzdiZkZMIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YT9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314267),('ox9tvgVziKjU4NGAS1CrIJMLhu3LCvOQXvyc1wpr',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqY25vckNrMG00dDg2ZTFMMEpRaDZEbjVyY1FxeThFbDZ2OFAwY3lLIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGY2F0ZWdvcmllcyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369828),('OzHaiN9uNkIzJLWGnA5OxVQU33Xq1ICt9jyt0DHh',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJleDlDNnZCbW4yQzg5cDBjd3VmVUJJRzZpOGJ5TlZRYkFpUnd5TDRiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL3Bvc3RzXC85OTk5OTkiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369751),('PTCq9hZkMyM5OCXhKcOeGa9iYF2FEUJ136Rgz2SA',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJkSkc0SlFYdFFuY2tXVXlBd3RCT2ZaS0M5UHN6WTBhUlpDc1pOUXVrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvdGFncyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369829),('Q9iFRPHkQLoQBfZDGjSDqtCgN71Ukc7qlTRroNcZ',NULL,'104.30.167.164','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36 (compatible; +https://developers.cloudflare.com/security-center/)','eyJfdG9rZW4iOiJPY01XeTZUZzBYTFhUWEgzbUVDbjZQQkxNcER4UHNIWlQ0bzRMUjAwIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789470728),('qB8Cw7fjlaPmw5ssIb4unwG7LUpcwa9js2VoSOSm',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI0UHExcGp2OFZsQkRVU0xOZ0ljQVpJOVgzbzRNaVFtWWs1RTFTcDNFIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGcG9zdHMlMkY5OTk5OTkiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369827),('QhSRE7he0VhCtJMtDwUt8EQLsTLTvRm7900xp7Ym',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJSVWJWcFpBemp6Mm0wZWxsN1VsRmFRcDZ6TTFjcVpiZ0lDekJvd1lpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC9tZWRpYSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369791),('qJ9uCPml62AQDah4uQwYe6p6PedkiGH3D80lsjeN',NULL,'202.61.233.150','metabase-cve-2026-72898-detect/1.0 (benign detection probes only)','eyJfdG9rZW4iOiJLb3ROU2dHU0I4cjFYcldRZXdmanVpQVFjZFRuZVZQUVdkdGljM0ZjIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2FwaVwvc2Vzc2lvblwvcHJvcGVydGllcyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789349182),('Qk5WWRdVehlnNo3ch64TpxlMsMPlE5ZJBUstIToj',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJmelhVZDVWcjJ1ZlNDUUdNTEt0aExwdHZaVnd4OW1VSUw1M1dMcEhUIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC9wYWdlcyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369790),('QpJfdPGF5PYxEt5vMPTbOM6Tcq9XyA1E3gsDY7EU',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJlMDliUTRFUDkzQnRsbXUyc2JKUERqd2s0RDV3akFSQk1uT2Z3T3Z5IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGdGFncyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369829),('rd5sOM3sCYXnxHgzGeS0FTIW6QJ36sFAe6XePkQ0',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJqT29LN2xTRWhGQXcwUUFQSW81U0Z0WDJYeTlFWkR3UElSSWlHcmNkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL0JhdGNoXC92MSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369671),('rIdNxDDnBKiDMqFzXr8Yrbo33oI2K9qIhwuPAhTb',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJQTThSZTRvWlpYanpXNjlPbTJicnVDWTc3UnRHTHROQ0J2TmtSTUZkIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369675),('rNXpx8ulwL5dFtgZGewCDZFoh4btREIdgg85FdUe',NULL,'202.61.233.150','metabase-cve-2026-72898-detect/1.0 (benign detection probes only)','eyJfdG9rZW4iOiJkMzNndzR5RmxmQ0pRMDN3eklrV0ROZ0dEREl0ek1wOUU0MjBrYUJqIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2FwaVwvc2Vzc2lvblwvcHJvcGVydGllcyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789349182),('rpXvMNIZTa5TFIrRaznIz569DRJAYWjlsElzOOoC',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI5UWVXdFJqaFh1ODZkS0ZERDZXelVlZm1DTndmOHhIZkt1YnNMYTZJIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGcGFnZXMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369828),('RSP4HrLYSXOCPweZc4i4cHjIvpIDJyUkM1bMZQPq',NULL,'62.169.18.30','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI5dDRkd2p4am9lc3Zlb2Z5OVAyN3Vna21LcDFqNDQ5QUFLWEN0ZEVwIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2dyYXZpdHlzbXRwXC92MVwvdGVzdHNcL21vY2stZGF0YT9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314180),('rtF5dvfntLpz0LVcjas1uwCiYv2g0vYN8fSoRAoa',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJyTW9KcTFLSGNiVEFOaVVYZzZjM0ZOcGNRa2U5UkpydndZdlVUcEFYIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC9wb3N0c1wvOTk5OTk5OSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369795),('rw1WK45wDBE4woTNaVbBFOJsx5ofyUaxxlcHYIHL',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJmWUtEUHNGYlhlOWo4bXR2NFI4clRJYXVuY0xqeXB0UHNlMHo1UHNpIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369654),('s4v1nNIUOnrFja0OSQvCQfTfLpZYMevuRRXCvL3S',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJBR3NWenhQQk55UkdpTjV4Q2cya2J5Sk5HSk1Mb3p2SlVnSGpIUUdiIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLz9yZXN0X3JvdXRlPSUyRmJhdGNoJTJGdjEiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369664),('SBJPsYKpFAfzIbXroMQ3W8i8bT3PWggPclgrVjXe',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJER1M2SExBT2NuQjBvbXNNMXprSkpucEhHbEZ1bEZ0VE9sdldtek85IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL3Bvc3RzXC85OTk5OTgiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369758),('SJhbfNJVOCtBxqbg0cn0ZYpym92tWMXfdWvecGbP',NULL,'3.139.242.79','visionheight.com/scan Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJUNFJnd2p3S2pReTV0ODBPT3RlQ29ITHlFdGl5SFVMWGQwTVdWRENsIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789330312),('sLMRHlHDZP9J1F0WaHh0RU3b3xvVNpyZEMX7pZjE',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJIOEd4cGRmVmgydzlNWlM0NWdZZ09KOVhRcTdrcjBMUk1LVXhZMVJlIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGdXNlcnMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369830),('SPufwhMobjOPSaeN1khNcIBvFDVR8BI9sYIYDBxO',NULL,'190.251.207.95','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJMc3FUYnBpU3d5U2I4bTRUV3J0NDE2SE5YM0N4RUt4cUR2Z095d3djIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789486469),('tlFRASvQewf7uCynzQpXCj557xbin5DbbEsROkbB',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJtZDBSakhlMHBabFVNa2Jlank4NUZwbmE5eFFIVVJoQkpja3RLZ0VqIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2luZGV4LnBocD9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzJnJlc3Rfcm91dGU9JTJGZ3Jhdml0eXNtdHAlMkZ2MSUyRnRlc3RzJTJGbW9jay1kYXRhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314265),('tzWPc0OFOjXLbc1pgibGhsWlnjBT6on6u0RXWreD',1,'66.9.171.142','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJ2M0s2RXV0Tk5mdG9jaWJZMTVUYlJ6SWJVMXdLSzNQcm9DYktoTVQ4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2FwaVwvdGVybXMiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789408274),('U3k0UIuFKC4QjpxSNZIqW5aokffEw4um0Wsi76GU',NULL,'190.251.207.95','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJhTXhCdWNneHJHbXlUMVBjczNWNlJXSTE2aWFjdHNjazZpRzlIMGgzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789445419),('UwxdxRNxeAf2HuGTpxNBq3epL224mevvU98M7vKc',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJabE5COVl1OU1JQVdVcElkQkpqUGt6NjdCamoxa3ZMUUxTRnV2aTlzIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRnBvc3RzJTJGOTk5OTk4Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369758),('VstWfuOqJwqPWzOw25iIL6ORrlTpmljryJnlh3Ds',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjVFJhd2RlV3hMQ1RWUkZrQUQwY3JHZFpleXpDQ2RtV1ExRm1MbGVQIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC9jYXRlZ29yaWVzIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369791),('VtblIa6FFiwE1Pex5ZonvOnEHtrm6qv1S09geEKe',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJFeHFIZVhKS2xkSVRuc3I5VmdWQWw4a3MxYlA4MjgxWDBjekJxT0pUIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2dcL3dwXC92MlwvcG9zdHNcLzk5OTk5OTkiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369830),('VWjlmeiz9HToHfhRffjhWkc6p0qJrzQm4w1YlXFk',NULL,'190.251.207.95','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJYRnBQU0d5WXpaNmRHakVoS0NSUjdtYTRKWWNoRlA2a1ROejhGV1ZhIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLzhmM2ExYzdlIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789445420),('VYZRkWVrrTLLIATJNaOvYhvhWprJTwNM7YKDglqR',NULL,'38.211.128.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJLWGFQVzhPWU5ZQXFscjZvVDBUaEJZeGhLeEFzWHNXQ2VHUnFMNnZXIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLz9wYWdlPWdyYXZpdHlzbXRwLXNldHRpbmdzJnJlc3Rfcm91dGU9JTJGZ3Jhdml0eXNtdHAlMkZ2MSUyRnRlc3RzJTJGbW9jay1kYXRhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789314264),('WHH9J3sxtcld9C0JhFCbv22SNZanheWIdLQPPlM8',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJublF5NVFIVU9HWllDaldBdjZ2TGI3Zm1YU0luSlM5NDVMZENWVFA3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRm1lZGlhIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369757),('WJ7y58mSJrdFocZm9MOD126ITN7104QJOIb6o5XR',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJnVzBuZ3FnaklCNUMwUEN4MHh0ZUl2S3g0eWdWdWdRdkxkSk9WNjAxIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZwb3N0cyUyRjk5OTk5OCIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369794),('xtmQ5J8f3Z5d6Ruu82bjA7EAieXmmAiuSfzNhIBl',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiI5Y0NjQzZEV0hGT0Z4Qkd3MzRBNXJFWDdDVkZpUjVUMDhpeUFzNW5PIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzc1wvd3BcL3YyXC9wb3N0c1wvOTk5OTk5Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369790),('y8MyLuu4IAUxEHZRva2XQKkzcwEzDHRRBs4WH5Gg',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJjRXRuaFpTSDlZV2F1Rnk2Q3l1Ykk4M3l5aE43MjJJandLcEhBeG9zIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dvcmRwcmVzcz9yZXN0X3JvdXRlPSUyRndwJTJGdjIlMkZwYWdlcyIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369790),('YNoHdBcWo5IsbfjQx95cadabOBV7K3yiHmhmr8Jg',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJmWTV0Z01VTzBCa3J5bXQyU1d5cnJJRE1Oc05ZRDBWZ1FreE5tWGU4IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwXC93cFwvdjJcL3Bvc3RzXC85OTk5OSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369759),('ywy8K1dvnXwYW6sAxi6Kzi2PLNAP0sXAGcOiV3vp',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJablF6cDFMTWFVUUVqbWlZbWRLck5DODk5a0ZGNkREd1c5V3VsZ3RWIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL2Jsb2c/cmVzdF9yb3V0ZT0lMkZ3cCUyRnYyJTJGcG9zdHMlMkY5OTk5OTk5Iiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789369830),('yydmONZrvZIwrjQ5qCyIei4yD1GjWxfmB7gQ2PIf',NULL,'190.251.207.95','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJmRFZvTEcwZVRJNkliNkRINnVSblRMRXdRcFpIMGJuUkRVQWZucktrIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcLzhmM2ExYzdlIiwicm91dGUiOm51bGx9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX19',1789359071),('ZhVEQwVODgiuzxJOc8hXMee6cMVsy6bg8ZorGdfY',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJUbkJtWjVuUXFXbkpkUFRGeDNCUWpIOXdRYTRnZmMyVVNCT1pSMUs3IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwLWpzb25cL2JhdGNoXC92MSIsInJvdXRlIjpudWxsfSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119fQ==',1789369672),('zjnk1bgY6UDuiokP8KN1kfPZnBaSf9VH5aZtboLk',NULL,'103.215.75.19','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJuUDRzNVFIQ0dzQ29mZEtSV2tkbTZwbDZIYkZPYUxWSlczcUlyZmNZIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2NcL3dwP3Jlc3Rfcm91dGU9JTJGd3AlMkZ2MiUyRnBvc3RzJTJGOTk5OTkiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789369759),('zS5niO094Sr0ngsHJ5oDxskMJxTA1ebAlD27EYZY',NULL,'209.38.143.218','Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0','eyJfdG9rZW4iOiJmQ0RlckhUSklrQnFzOURvU1RSYUZHMEpVOWVyT1NYRkhwUDJWMEtKIiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHBzOlwvXC9kZ2kuYmVpdGJyaWRnZWNvbm5lY3QuY2MiLCJyb3V0ZSI6bnVsbH0sIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfX0=',1789313992);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_categories`
--

DROP TABLE IF EXISTS `shop_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shop_categories_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_categories`
--

LOCK TABLES `shop_categories` WRITE;
/*!40000 ALTER TABLE `shop_categories` DISABLE KEYS */;
INSERT INTO `shop_categories` VALUES (1,'Uniforms','School uniform items',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(2,'Books','Textbooks and reading materials',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(3,'Stationery','Exercise books, pens, and classroom supplies',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(4,'Sportswear','Tracksuits and sports clothing',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(5,'Trips','School trips and excursions',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(6,'Examination Materials','Exam stationery and materials',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(7,'Other','Other school shop items',1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(8,'Skills / Practical',NULL,1,'2026-09-08 07:12:34','2026-09-08 07:12:34');
/*!40000 ALTER TABLE `shop_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shop_items`
--

DROP TABLE IF EXISTS `shop_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shop_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shop_category_id` bigint unsigned NOT NULL,
  `item_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `unit_price` decimal(12,2) NOT NULL,
  `quantity_in_stock` int NOT NULL DEFAULT '0',
  `reorder_level` int NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shop_items_item_code_unique` (`item_code`),
  KEY `shop_items_shop_category_id_index` (`shop_category_id`),
  KEY `shop_items_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shop_items`
--

LOCK TABLES `shop_items` WRITE;
/*!40000 ALTER TABLE `shop_items` DISABLE KEYS */;
INSERT INTO `shop_items` VALUES (1,1,'School Shirt (Boys)',NULL,'UNI-001',NULL,12.50,42,10,1,'2026-08-06 08:20:54','2026-09-06 19:35:07'),(2,1,'School Shirt (Girls)',NULL,'UNI-002',NULL,12.50,45,10,1,'2026-08-06 08:20:54','2026-09-03 11:38:43'),(3,1,'School Trousers',NULL,'UNI-003',NULL,15.00,40,8,1,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(4,2,'Mathematics Textbook',NULL,'BK-001',NULL,8.00,18,5,1,'2026-08-06 08:20:54','2026-09-03 11:38:43'),(5,2,'English Textbook',NULL,'BK-002',NULL,8.00,26,5,1,'2026-08-06 08:20:54','2026-09-07 05:27:22'),(6,3,'Exercise Book (48 pages)',NULL,'ST-001',NULL,0.50,158,50,1,'2026-08-06 08:20:54','2026-09-07 06:54:26'),(7,3,'Pen (Blue)',NULL,'ST-002',NULL,0.30,0,20,1,'2026-08-06 08:20:54','2026-09-03 11:38:43'),(8,4,'Tracksuit',NULL,'SP-001',NULL,25.00,16,5,1,'2026-08-06 08:20:54','2026-09-07 05:01:31'),(9,6,'Answer Booklet',NULL,'EX-001',NULL,0.20,499,100,1,'2026-08-06 08:20:54','2026-09-07 05:27:22'),(10,1,'School Blazer',NULL,'UNI-CU-001','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',35.00,25,5,1,'2026-09-08 07:13:06','2026-09-08 07:13:06'),(11,1,'School Skirt',NULL,'UNI-CU-004','Girls · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',15.00,25,5,1,'2026-09-08 07:13:06','2026-09-08 07:13:06'),(12,1,'School Tie','Standard','UNI-CU-005','All learners · Standard — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',5.00,25,5,1,'2026-09-08 07:13:06','2026-09-08 07:13:06'),(13,1,'School Socks',NULL,'UNI-CU-006','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',3.00,25,5,1,'2026-09-08 07:13:07','2026-09-08 07:13:07'),(14,1,'School Badge','Standard','UNI-CU-007','All learners · Standard — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',2.00,25,5,1,'2026-09-08 07:13:07','2026-09-08 07:13:07'),(15,1,'Approved School Shoes',NULL,'UNI-CU-008','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',20.00,25,5,1,'2026-09-08 07:13:07','2026-09-08 07:13:07'),(16,1,'DestinyGate Half Puff Jacket',NULL,'UNI-WW-001','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',30.00,25,5,1,'2026-09-08 07:13:07','2026-09-08 07:13:07'),(17,1,'School Jersey',NULL,'UNI-WW-002','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',18.00,25,5,1,'2026-09-08 07:13:07','2026-09-08 07:13:07'),(18,1,'Woollen Hat',NULL,'UNI-WW-003','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',6.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(19,1,'School Scarf',NULL,'UNI-WW-004','All learners · Standard/Various — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',6.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(20,1,'Winter Trousers',NULL,'UNI-WW-005','As approved · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',15.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(21,4,'Sports Jersey',NULL,'UNI-SW-001','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',15.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(22,4,'Sports Shorts',NULL,'UNI-SW-002','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',10.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(23,4,'Track Pants',NULL,'UNI-SW-003','Where approved · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',15.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(24,4,'Sports Socks',NULL,'UNI-SW-004','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',3.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(25,4,'Approved Sports Shoes',NULL,'UNI-SW-005','All learners · Various sizes — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',20.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(26,8,'Baking Apron',NULL,'UNI-SK-001','Baking programme — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',8.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(27,8,'Hairnet',NULL,'UNI-SK-002','Baking programme — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',2.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(28,8,'Hairstyling Protective Apron',NULL,'UNI-SK-003','Hairstyling / Barbering programme — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',8.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08'),(29,8,'Electricals Practical/Safety Wear',NULL,'UNI-SK-004','Electricals programme — PLACEHOLDER price/stock, set the real amounts in Shop > Items & Stock.',15.00,25,5,1,'2026-09-08 07:13:08','2026-09-08 07:13:08');
/*!40000 ALTER TABLE `shop_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `staff_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `roles` json DEFAULT NULL,
  `qualifications` text COLLATE utf8mb4_unicode_ci,
  `employment_date` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_staff_id_unique` (`staff_id`),
  KEY `staff_user_id_index` (`user_id`),
  KEY `staff_staff_id_index` (`staff_id`),
  CONSTRAINT `staff_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,2,'STF-0001','Emmanuel','Chikwanda','headmaster@destinygate.ac.zw',NULL,'Administration','Headmaster','[\"headmaster\"]',NULL,'2018-01-15',1,'2026-08-06 08:20:52','2026-08-06 08:20:52'),(2,3,'STF-0002','Grace','Moyo','bursar@destinygate.ac.zw',NULL,'Finance','Bursar','[\"bursar\"]',NULL,NULL,1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(3,4,'STF-0003','Tendai','Mutasa','tmutasa@destinygate.ac.zw',NULL,'Sciences','Science Teacher','[\"teacher\"]',NULL,NULL,1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(4,5,'STF-0004','Rudo','Ncube','rncube@destinygate.ac.zw',NULL,'Humanities','English Teacher','[\"teacher\"]',NULL,NULL,1,'2026-08-06 08:20:53','2026-08-06 08:20:53');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_members`
--

DROP TABLE IF EXISTS `staff_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_members` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `staff_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned NOT NULL,
  `job_title` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `employment_type` enum('full_time','part_time','contract') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full_time',
  `employment_date` date NOT NULL,
  `profile_photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','on_leave','suspended','resigned') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_members_staff_number_unique` (`staff_number`),
  UNIQUE KEY `staff_members_email_unique` (`email`),
  UNIQUE KEY `staff_members_user_id_unique` (`user_id`),
  KEY `staff_members_department_id_index` (`department_id`),
  KEY `staff_members_status_index` (`status`),
  KEY `staff_members_staff_number_index` (`staff_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_members`
--

LOCK TABLES `staff_members` WRITE;
/*!40000 ALTER TABLE `staff_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stream_subjects`
--

DROP TABLE IF EXISTS `stream_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stream_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `is_compulsory` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_stream_subject` (`academic_year_id`,`term_id`,`stream_id`,`subject_id`),
  KEY `stream_subjects_form_id_stream_id_index` (`form_id`,`stream_id`),
  KEY `stream_subjects_subject_id_index` (`subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stream_subjects`
--

LOCK TABLES `stream_subjects` WRITE;
/*!40000 ALTER TABLE `stream_subjects` DISABLE KEYS */;
INSERT INTO `stream_subjects` VALUES (1,1,3,1,1,1,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(2,1,3,1,1,2,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(3,1,3,1,1,3,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(4,1,3,1,1,4,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(5,1,3,1,1,5,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(6,1,3,2,2,1,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(7,1,3,2,2,2,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(8,1,3,2,2,3,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(9,1,3,2,2,4,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(10,1,3,2,2,5,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(11,1,3,3,3,1,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(12,1,3,3,3,2,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(13,1,3,3,3,3,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(14,1,3,3,3,4,1,1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(15,1,3,3,3,5,1,1,'2026-09-07 17:12:35','2026-09-07 17:12:35');
/*!40000 ALTER TABLE `stream_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `streams`
--

DROP TABLE IF EXISTS `streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `streams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` smallint unsigned NOT NULL DEFAULT '40',
  `class_teacher_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `streams_form_id_name_unique` (`form_id`,`name`),
  KEY `streams_category_id_index` (`category_id`),
  CONSTRAINT `streams_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `streams_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `streams`
--

LOCK TABLES `streams` WRITE;
/*!40000 ALTER TABLE `streams` DISABLE KEYS */;
INSERT INTO `streams` VALUES (1,1,NULL,'A',35,NULL,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(2,2,NULL,'A',35,NULL,'2026-08-06 08:20:54','2026-08-06 08:20:54'),(3,3,NULL,'B',30,NULL,'2026-08-06 08:20:54','2026-08-06 08:20:54');
/*!40000 ALTER TABLE `streams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_account_transactions`
--

DROP TABLE IF EXISTS `student_account_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_account_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `transaction_type` enum('bill','payment','adjustment','cancellation') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `debit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance_after` decimal(12,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_account_transactions_student_id_index` (`student_id`),
  KEY `student_account_transactions_academic_year_id_term_id_index` (`academic_year_id`,`term_id`)
) ENGINE=InnoDB AUTO_INCREMENT=223 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_account_transactions`
--

LOCK TABLES `student_account_transactions` WRITE;
/*!40000 ALTER TABLE `student_account_transactions` DISABLE KEYS */;
INSERT INTO `student_account_transactions` VALUES (1,1,1,2,'bill','student_bills',1,'Term 2 Tuition (Demo)',500.00,0.00,500.00,3,'2026-09-03 11:26:02','2026-09-03 11:26:02'),(2,1,1,2,'payment','finance_payments',1,'Payment received - RCPT-2026-00001',0.00,300.00,200.00,3,'2026-09-03 11:26:02','2026-09-03 11:26:02'),(3,2,1,2,'bill','student_bills',2,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(4,2,1,2,'bill','student_bills',3,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(5,2,1,2,'bill','student_bills',4,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(6,3,1,2,'bill','student_bills',5,'Term 2 Tuition - Form 3',550.00,0.00,550.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(7,3,1,2,'bill','student_bills',6,'Term 2 Examination Fees',40.00,0.00,590.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(8,3,1,2,'bill','student_bills',7,'Term 2 Sports Levy',25.00,0.00,615.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(9,4,1,2,'bill','student_bills',8,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(10,4,1,2,'bill','student_bills',9,'Term 2 Examination Fees',40.00,0.00,490.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(11,4,1,2,'bill','student_bills',10,'Term 2 Sports Levy',25.00,0.00,515.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(12,5,1,2,'bill','student_bills',11,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(13,5,1,2,'bill','student_bills',12,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(14,5,1,2,'bill','student_bills',13,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(15,6,1,2,'bill','student_bills',14,'Term 2 Tuition - Form 3',550.00,0.00,550.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(16,6,1,2,'bill','student_bills',15,'Term 2 Examination Fees',40.00,0.00,590.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(17,6,1,2,'bill','student_bills',16,'Term 2 Sports Levy',25.00,0.00,615.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(18,7,1,2,'bill','student_bills',17,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(19,7,1,2,'bill','student_bills',18,'Term 2 Examination Fees',40.00,0.00,490.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(20,7,1,2,'bill','student_bills',19,'Term 2 Sports Levy',25.00,0.00,515.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(21,8,1,2,'bill','student_bills',20,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(22,8,1,2,'bill','student_bills',21,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(23,8,1,2,'bill','student_bills',22,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(24,9,1,2,'bill','student_bills',23,'Term 2 Tuition - Form 3',550.00,0.00,550.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(25,9,1,2,'bill','student_bills',24,'Term 2 Examination Fees',40.00,0.00,590.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(26,9,1,2,'bill','student_bills',25,'Term 2 Sports Levy',25.00,0.00,615.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(27,10,1,2,'bill','student_bills',26,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(28,10,1,2,'bill','student_bills',27,'Term 2 Examination Fees',40.00,0.00,490.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(29,10,1,2,'bill','student_bills',28,'Term 2 Sports Levy',25.00,0.00,515.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(30,11,1,2,'bill','student_bills',29,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(31,11,1,2,'bill','student_bills',30,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(32,11,1,2,'bill','student_bills',31,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(33,12,1,2,'bill','student_bills',32,'Term 2 Tuition - Form 3',550.00,0.00,550.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(34,12,1,2,'bill','student_bills',33,'Term 2 Examination Fees',40.00,0.00,590.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(35,12,1,2,'bill','student_bills',34,'Term 2 Sports Levy',25.00,0.00,615.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(36,13,1,2,'bill','student_bills',35,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(37,13,1,2,'bill','student_bills',36,'Term 2 Examination Fees',40.00,0.00,490.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(38,13,1,2,'bill','student_bills',37,'Term 2 Sports Levy',25.00,0.00,515.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(39,14,1,2,'bill','student_bills',38,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(40,14,1,2,'bill','student_bills',39,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(41,14,1,2,'bill','student_bills',40,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(42,15,1,2,'bill','student_bills',41,'Term 2 Tuition - Form 3',550.00,0.00,550.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(43,15,1,2,'bill','student_bills',42,'Term 2 Examination Fees',40.00,0.00,590.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(44,15,1,2,'bill','student_bills',43,'Term 2 Sports Levy',25.00,0.00,615.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(45,16,1,2,'bill','student_bills',44,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(46,16,1,2,'bill','student_bills',45,'Term 2 Examination Fees',40.00,0.00,490.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(47,16,1,2,'bill','student_bills',46,'Term 2 Sports Levy',25.00,0.00,515.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(48,17,1,2,'bill','student_bills',47,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(49,17,1,2,'bill','student_bills',48,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(50,17,1,2,'bill','student_bills',49,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(51,18,1,2,'bill','student_bills',50,'Term 2 Tuition - Form 3',550.00,0.00,550.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(52,18,1,2,'bill','student_bills',51,'Term 2 Examination Fees',40.00,0.00,590.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(53,18,1,2,'bill','student_bills',52,'Term 2 Sports Levy',25.00,0.00,615.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(54,19,1,2,'bill','student_bills',53,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(55,19,1,2,'bill','student_bills',54,'Term 2 Examination Fees',40.00,0.00,490.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(56,19,1,2,'bill','student_bills',55,'Term 2 Sports Levy',25.00,0.00,515.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(57,20,1,2,'bill','student_bills',56,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(58,20,1,2,'bill','student_bills',57,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(59,20,1,2,'bill','student_bills',58,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(60,21,1,2,'bill','student_bills',59,'Term 2 Tuition - Form 3',550.00,0.00,550.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(61,21,1,2,'bill','student_bills',60,'Term 2 Examination Fees',40.00,0.00,590.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(62,21,1,2,'bill','student_bills',61,'Term 2 Sports Levy',25.00,0.00,615.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(63,22,1,2,'bill','student_bills',62,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(64,22,1,2,'bill','student_bills',63,'Term 2 Examination Fees',40.00,0.00,490.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(65,22,1,2,'bill','student_bills',64,'Term 2 Sports Levy',25.00,0.00,515.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(66,23,1,2,'bill','student_bills',65,'Term 2 Tuition - Form 2',500.00,0.00,500.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(67,23,1,2,'bill','student_bills',66,'Term 2 Examination Fees',40.00,0.00,540.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(68,23,1,2,'bill','student_bills',67,'Term 2 Sports Levy',25.00,0.00,565.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(69,1,1,2,'bill','student_bills',68,'Term 2 Examination Fees',40.00,0.00,240.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(70,1,1,2,'bill','student_bills',69,'Term 2 Sports Levy',25.00,0.00,265.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(71,2,1,2,'payment','finance_payments',2,'Payment received - RCPT-2026-00002',0.00,310.75,254.25,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(72,3,1,2,'payment','finance_payments',3,'Payment received - RCPT-2026-00003',0.00,369.00,246.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(73,4,1,2,'payment','finance_payments',4,'Payment received - RCPT-2026-00004',0.00,515.00,0.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(74,5,1,2,'payment','finance_payments',5,'Payment received - RCPT-2026-00005',0.00,565.00,0.00,3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(75,7,1,2,'payment','finance_payments',6,'Payment received - RCPT-2026-00006',0.00,283.25,231.75,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(76,8,1,2,'payment','finance_payments',7,'Payment received - RCPT-2026-00007',0.00,339.00,226.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(77,9,1,2,'payment','finance_payments',8,'Payment received - RCPT-2026-00008',0.00,615.00,0.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(78,10,1,2,'payment','finance_payments',9,'Payment received - RCPT-2026-00009',0.00,515.00,0.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(79,12,1,2,'payment','finance_payments',10,'Payment received - RCPT-2026-00010',0.00,338.25,276.75,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(80,13,1,2,'payment','finance_payments',11,'Payment received - RCPT-2026-00011',0.00,309.00,206.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(81,14,1,2,'payment','finance_payments',12,'Payment received - RCPT-2026-00012',0.00,565.00,0.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(82,15,1,2,'payment','finance_payments',13,'Payment received - RCPT-2026-00013',0.00,615.00,0.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(83,17,1,2,'payment','finance_payments',14,'Payment received - RCPT-2026-00014',0.00,310.75,254.25,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(84,18,1,2,'payment','finance_payments',15,'Payment received - RCPT-2026-00015',0.00,369.00,246.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(85,19,1,2,'payment','finance_payments',16,'Payment received - RCPT-2026-00016',0.00,515.00,0.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(86,20,1,2,'payment','finance_payments',17,'Payment received - RCPT-2026-00017',0.00,565.00,0.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(87,22,1,2,'payment','finance_payments',18,'Payment received - RCPT-2026-00018',0.00,283.25,231.75,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(88,23,1,2,'payment','finance_payments',19,'Payment received - RCPT-2026-00019',0.00,339.00,226.00,3,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(89,1,1,2,'payment','finance_payments',20,'Payment received - RCPT-2026-00020',0.00,10.00,255.00,3,'2026-09-04 07:13:03','2026-09-04 07:13:03'),(90,1,1,2,'payment','finance_payments',21,'Payment received - RCPT-2026-00021',0.00,10.00,245.00,3,'2026-09-04 07:13:30','2026-09-04 07:13:30'),(91,1,1,2,'adjustment','finance_payments',21,'Payment reversed - RCPT-2026-00021',10.00,0.00,255.00,1,'2026-09-04 07:13:30','2026-09-04 07:13:30'),(92,10,1,2,'adjustment','finance_payments',9,'Payment reversed - RCPT-2026-00009',515.00,0.00,515.00,1,'2026-09-04 10:40:39','2026-09-04 10:40:39'),(93,6,1,1,'payment','finance_payments',22,'Payment received - RCPT-2026-00022',0.00,12.00,603.00,1,'2026-09-06 19:10:35','2026-09-06 19:10:35'),(94,3,1,2,'payment','finance_payments',23,'Payment received - RCPT-2026-00023',0.00,346.00,-100.00,3,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(95,3,1,2,'bill','student_bills',70,'Term 2 Tuition (Demo)',500.00,0.00,400.00,3,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(96,3,1,2,'adjustment','finance_payments',23,'Payment reversed - RCPT-2026-00023',346.00,0.00,746.00,3,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(97,2,1,2,'payment','finance_payments',24,'Payment received - RCPT-2026-00024',0.00,329.25,-75.00,3,'2026-09-06 19:23:04','2026-09-06 19:23:04'),(98,6,1,3,'payment','finance_payments',25,'Payment received - RCPT-2026-00025',0.00,1222.00,-619.00,1,'2026-09-06 19:27:13','2026-09-06 19:27:13'),(99,4,1,2,'payment','finance_payments',26,'Payment received - RCPT-2026-00026',0.00,200.00,-200.00,3,'2026-09-06 19:34:53','2026-09-06 19:34:53'),(100,4,1,2,'adjustment','student_purchases',14,'Fee credit applied to Shop purchase PUR-2026-0014',12.50,0.00,-187.50,3,'2026-09-06 19:34:54','2026-09-06 19:34:54'),(101,4,1,2,'adjustment','student_purchases',15,'Fee credit applied to Shop purchase PUR-2026-0015',12.50,0.00,-175.00,3,'2026-09-06 19:34:54','2026-09-06 19:34:54'),(102,4,1,2,'adjustment','student_purchases',14,'Shop purchase PUR-2026-0014 cancelled — fee credit restored',0.00,12.50,-187.50,3,'2026-09-06 19:35:07','2026-09-06 19:35:07'),(103,4,1,2,'adjustment','student_purchases',16,'Fee credit applied to Shop purchase PUR-2026-0016',50.00,0.00,-137.50,3,'2026-09-07 05:01:31','2026-09-07 05:01:31'),(104,6,1,2,'adjustment','student_purchases',17,'Fee credit applied to Shop purchase PUR-2026-0017',16.20,0.00,-602.80,1,'2026-09-07 05:27:22','2026-09-07 05:27:22'),(105,5,1,2,'payment','finance_payments',27,'Payment received - DGS-RCP-2026-00000001',0.00,10.00,-10.00,3,'2026-09-07 06:47:29','2026-09-07 06:47:29'),(106,5,1,2,'payment','finance_payments',28,'Payment received - DGS-RCP-2026-00000002',0.00,233.00,-243.00,1,'2026-09-07 09:30:05','2026-09-07 09:30:05'),(107,24,1,2,'bill','student_bills',71,'Term 2 Tuition - Form 1',450.00,0.00,450.00,3,'2026-09-07 09:54:02','2026-09-07 09:54:02'),(108,25,1,2,'bill','student_bills',72,'Term 2 Tuition (Demo)',500.00,0.00,500.00,NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(109,25,1,2,'bill','student_bills',73,'Term 2 Examination Fees',40.00,0.00,540.00,NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(110,25,1,2,'bill','student_bills',74,'Term 2 Sports Levy',25.00,0.00,565.00,NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(111,25,1,2,'bill','student_bills',75,'Term 2 Tuition - Form 1',450.00,0.00,1015.00,NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(113,27,1,3,'bill','student_bills',77,'Term 3 Tuition (Demo)',500.00,0.00,500.00,1,'2026-09-07 16:33:54','2026-09-07 16:33:54'),(114,27,1,3,'bill','student_bills',78,'Term 3 Examination Fees',40.00,0.00,540.00,1,'2026-09-07 16:33:54','2026-09-07 16:33:54'),(115,27,1,3,'bill','student_bills',79,'Term 3 Sports Levy',25.00,0.00,565.00,1,'2026-09-07 16:33:54','2026-09-07 16:33:54'),(116,24,1,3,'bill','student_bills',80,'Term 3 Tuition - Form 1',450.00,0.00,900.00,1,'2026-09-07 16:39:18','2026-09-07 16:39:18'),(117,1,1,3,'bill','student_bills',81,'Term 3 Tuition (Demo)',500.00,0.00,755.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(118,1,1,3,'bill','student_bills',82,'Term 3 Examination Fees',40.00,0.00,795.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(119,1,1,3,'bill','student_bills',83,'Term 3 Sports Levy',25.00,0.00,820.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(120,1,1,3,'bill','student_bills',84,'Term 3 Tuition - Form 1',450.00,0.00,1270.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(121,2,1,3,'bill','student_bills',85,'Term 3 Tuition (Demo)',500.00,0.00,425.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(122,2,1,3,'bill','student_bills',86,'Term 3 Examination Fees',40.00,0.00,465.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(123,2,1,3,'bill','student_bills',87,'Term 3 Sports Levy',25.00,0.00,490.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(124,2,1,3,'bill','student_bills',88,'Term 3 Tuition - Form 2',500.00,0.00,990.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(125,3,1,3,'bill','student_bills',89,'Term 3 Tuition (Demo)',500.00,0.00,1246.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(126,3,1,3,'bill','student_bills',90,'Term 3 Examination Fees',40.00,0.00,1286.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(127,3,1,3,'bill','student_bills',91,'Term 3 Sports Levy',25.00,0.00,1311.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(128,3,1,3,'bill','student_bills',92,'Term 3 Tuition - Form 3',550.00,0.00,1861.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(129,4,1,3,'bill','student_bills',93,'Term 3 Tuition (Demo)',500.00,0.00,362.50,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(130,4,1,3,'bill','student_bills',94,'Term 3 Examination Fees',40.00,0.00,402.50,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(131,4,1,3,'bill','student_bills',95,'Term 3 Sports Levy',25.00,0.00,427.50,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(132,4,1,3,'bill','student_bills',96,'Term 3 Tuition - Form 1',450.00,0.00,877.50,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(133,5,1,3,'bill','student_bills',97,'Term 3 Tuition (Demo)',500.00,0.00,257.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(134,5,1,3,'bill','student_bills',98,'Term 3 Examination Fees',40.00,0.00,297.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(135,5,1,3,'bill','student_bills',99,'Term 3 Sports Levy',25.00,0.00,322.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(136,5,1,3,'bill','student_bills',100,'Term 3 Tuition - Form 2',500.00,0.00,822.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(137,6,1,3,'bill','student_bills',101,'Term 3 Tuition (Demo)',500.00,0.00,-102.80,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(138,6,1,3,'bill','student_bills',102,'Term 3 Examination Fees',40.00,0.00,-62.80,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(139,6,1,3,'bill','student_bills',103,'Term 3 Sports Levy',25.00,0.00,-37.80,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(140,6,1,3,'bill','student_bills',104,'Term 3 Tuition - Form 3',550.00,0.00,512.20,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(141,7,1,3,'bill','student_bills',105,'Term 3 Tuition (Demo)',500.00,0.00,731.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(142,7,1,3,'bill','student_bills',106,'Term 3 Examination Fees',40.00,0.00,771.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(143,7,1,3,'bill','student_bills',107,'Term 3 Sports Levy',25.00,0.00,796.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(144,7,1,3,'bill','student_bills',108,'Term 3 Tuition - Form 1',450.00,0.00,1246.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(145,8,1,3,'bill','student_bills',109,'Term 3 Tuition (Demo)',500.00,0.00,726.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(146,8,1,3,'bill','student_bills',110,'Term 3 Examination Fees',40.00,0.00,766.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(147,8,1,3,'bill','student_bills',111,'Term 3 Sports Levy',25.00,0.00,791.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(148,8,1,3,'bill','student_bills',112,'Term 3 Tuition - Form 2',500.00,0.00,1291.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(149,9,1,3,'bill','student_bills',113,'Term 3 Tuition (Demo)',500.00,0.00,500.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(150,9,1,3,'bill','student_bills',114,'Term 3 Examination Fees',40.00,0.00,540.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(151,9,1,3,'bill','student_bills',115,'Term 3 Sports Levy',25.00,0.00,565.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(152,9,1,3,'bill','student_bills',116,'Term 3 Tuition - Form 3',550.00,0.00,1115.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(153,10,1,3,'bill','student_bills',117,'Term 3 Tuition (Demo)',500.00,0.00,1015.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(154,10,1,3,'bill','student_bills',118,'Term 3 Examination Fees',40.00,0.00,1055.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(155,10,1,3,'bill','student_bills',119,'Term 3 Sports Levy',25.00,0.00,1080.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(156,10,1,3,'bill','student_bills',120,'Term 3 Tuition - Form 1',450.00,0.00,1530.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(157,11,1,3,'bill','student_bills',121,'Term 3 Tuition (Demo)',500.00,0.00,1065.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(158,11,1,3,'bill','student_bills',122,'Term 3 Examination Fees',40.00,0.00,1105.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(159,11,1,3,'bill','student_bills',123,'Term 3 Sports Levy',25.00,0.00,1130.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(160,11,1,3,'bill','student_bills',124,'Term 3 Tuition - Form 2',500.00,0.00,1630.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(161,12,1,3,'bill','student_bills',125,'Term 3 Tuition (Demo)',500.00,0.00,776.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(162,12,1,3,'bill','student_bills',126,'Term 3 Examination Fees',40.00,0.00,816.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(163,12,1,3,'bill','student_bills',127,'Term 3 Sports Levy',25.00,0.00,841.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(164,12,1,3,'bill','student_bills',128,'Term 3 Tuition - Form 3',550.00,0.00,1391.75,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(165,13,1,3,'bill','student_bills',129,'Term 3 Tuition (Demo)',500.00,0.00,706.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(166,13,1,3,'bill','student_bills',130,'Term 3 Examination Fees',40.00,0.00,746.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(167,13,1,3,'bill','student_bills',131,'Term 3 Sports Levy',25.00,0.00,771.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(168,13,1,3,'bill','student_bills',132,'Term 3 Tuition - Form 1',450.00,0.00,1221.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(169,14,1,3,'bill','student_bills',133,'Term 3 Tuition (Demo)',500.00,0.00,500.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(170,14,1,3,'bill','student_bills',134,'Term 3 Examination Fees',40.00,0.00,540.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(171,14,1,3,'bill','student_bills',135,'Term 3 Sports Levy',25.00,0.00,565.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(172,14,1,3,'bill','student_bills',136,'Term 3 Tuition - Form 2',500.00,0.00,1065.00,NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(173,15,1,3,'bill','student_bills',137,'Term 3 Tuition (Demo)',500.00,0.00,500.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(174,15,1,3,'bill','student_bills',138,'Term 3 Examination Fees',40.00,0.00,540.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(175,15,1,3,'bill','student_bills',139,'Term 3 Sports Levy',25.00,0.00,565.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(176,15,1,3,'bill','student_bills',140,'Term 3 Tuition - Form 3',550.00,0.00,1115.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(177,16,1,3,'bill','student_bills',141,'Term 3 Tuition (Demo)',500.00,0.00,1015.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(178,16,1,3,'bill','student_bills',142,'Term 3 Examination Fees',40.00,0.00,1055.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(179,16,1,3,'bill','student_bills',143,'Term 3 Sports Levy',25.00,0.00,1080.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(180,16,1,3,'bill','student_bills',144,'Term 3 Tuition - Form 1',450.00,0.00,1530.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(181,17,1,3,'bill','student_bills',145,'Term 3 Tuition (Demo)',500.00,0.00,754.25,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(182,17,1,3,'bill','student_bills',146,'Term 3 Examination Fees',40.00,0.00,794.25,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(183,17,1,3,'bill','student_bills',147,'Term 3 Sports Levy',25.00,0.00,819.25,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(184,17,1,3,'bill','student_bills',148,'Term 3 Tuition - Form 2',500.00,0.00,1319.25,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(185,18,1,3,'bill','student_bills',149,'Term 3 Tuition (Demo)',500.00,0.00,746.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(186,18,1,3,'bill','student_bills',150,'Term 3 Examination Fees',40.00,0.00,786.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(187,18,1,3,'bill','student_bills',151,'Term 3 Sports Levy',25.00,0.00,811.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(188,18,1,3,'bill','student_bills',152,'Term 3 Tuition - Form 3',550.00,0.00,1361.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(189,19,1,3,'bill','student_bills',153,'Term 3 Tuition (Demo)',500.00,0.00,500.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(190,19,1,3,'bill','student_bills',154,'Term 3 Examination Fees',40.00,0.00,540.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(191,19,1,3,'bill','student_bills',155,'Term 3 Sports Levy',25.00,0.00,565.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(192,19,1,3,'bill','student_bills',156,'Term 3 Tuition - Form 1',450.00,0.00,1015.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(193,20,1,3,'bill','student_bills',157,'Term 3 Tuition (Demo)',500.00,0.00,500.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(194,20,1,3,'bill','student_bills',158,'Term 3 Examination Fees',40.00,0.00,540.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(195,20,1,3,'bill','student_bills',159,'Term 3 Sports Levy',25.00,0.00,565.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(196,20,1,3,'bill','student_bills',160,'Term 3 Tuition - Form 2',500.00,0.00,1065.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(197,21,1,3,'bill','student_bills',161,'Term 3 Tuition (Demo)',500.00,0.00,1115.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(198,21,1,3,'bill','student_bills',162,'Term 3 Examination Fees',40.00,0.00,1155.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(199,21,1,3,'bill','student_bills',163,'Term 3 Sports Levy',25.00,0.00,1180.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(200,21,1,3,'bill','student_bills',164,'Term 3 Tuition - Form 3',550.00,0.00,1730.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(201,22,1,3,'bill','student_bills',165,'Term 3 Tuition (Demo)',500.00,0.00,731.75,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(202,22,1,3,'bill','student_bills',166,'Term 3 Examination Fees',40.00,0.00,771.75,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(203,22,1,3,'bill','student_bills',167,'Term 3 Sports Levy',25.00,0.00,796.75,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(204,22,1,3,'bill','student_bills',168,'Term 3 Tuition - Form 1',450.00,0.00,1246.75,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(205,23,1,3,'bill','student_bills',169,'Term 3 Tuition (Demo)',500.00,0.00,726.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(206,23,1,3,'bill','student_bills',170,'Term 3 Examination Fees',40.00,0.00,766.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(207,23,1,3,'bill','student_bills',171,'Term 3 Sports Levy',25.00,0.00,791.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(208,23,1,3,'bill','student_bills',172,'Term 3 Tuition - Form 2',500.00,0.00,1291.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(209,25,1,3,'bill','student_bills',173,'Term 3 Tuition (Demo)',500.00,0.00,1515.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(210,25,1,3,'bill','student_bills',174,'Term 3 Examination Fees',40.00,0.00,1555.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(211,25,1,3,'bill','student_bills',175,'Term 3 Sports Levy',25.00,0.00,1580.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(212,25,1,3,'bill','student_bills',176,'Term 3 Tuition - Form 2',500.00,0.00,2080.00,NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(217,6,1,3,'payment','finance_payments',31,'Payment received - DGS-RCP-2026-00000003',0.00,234.00,278.20,1,'2026-09-07 18:26:16','2026-09-07 18:26:16');
/*!40000 ALTER TABLE `student_account_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_attendance_records`
--

DROP TABLE IF EXISTS `student_attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_attendance_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `status` enum('present','absent','late','excused','sick','early_departure') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'present',
  `arrival_time` time DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_record_unique` (`attendance_session_id`,`student_id`),
  KEY `student_attendance_records_student_id_index` (`student_id`),
  KEY `student_attendance_records_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_attendance_records`
--

LOCK TABLES `student_attendance_records` WRITE;
/*!40000 ALTER TABLE `student_attendance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_attendance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_bills`
--

DROP TABLE IF EXISTS `student_bills`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_bills` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bill_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `fee_structure_id` bigint unsigned DEFAULT NULL,
  `fee_category_id` bigint unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL,
  `status` enum('unpaid','partial','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_bills_bill_number_unique` (`bill_number`),
  KEY `student_bills_student_id_index` (`student_id`),
  KEY `student_bills_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `student_bills_status_index` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_bills`
--

LOCK TABLES `student_bills` WRITE;
/*!40000 ALTER TABLE `student_bills` DISABLE KEYS */;
INSERT INTO `student_bills` VALUES (1,'BILL-2026-00001',1,1,2,1,1,'Term 2 Tuition (Demo)',500.00,305.00,195.00,'partial','2026-09-17',3,'2026-09-03 11:26:02','2026-09-07 19:59:22'),(2,'BILL-2026-00002',2,1,2,3,1,'Term 2 Tuition - Form 2',500.00,500.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:23:04'),(3,'BILL-2026-00003',2,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:23:04'),(4,'BILL-2026-00004',2,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:23:04'),(5,'BILL-2026-00005',3,1,2,4,1,'Term 2 Tuition - Form 3',550.00,369.00,181.00,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:22:50'),(6,'BILL-2026-00006',3,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:22:50'),(7,'BILL-2026-00007',3,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:22:50'),(8,'BILL-2026-00008',4,1,2,2,1,'Term 2 Tuition - Form 1',450.00,450.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(9,'BILL-2026-00009',4,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(10,'BILL-2026-00010',4,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(11,'BILL-2026-00011',5,1,2,3,1,'Term 2 Tuition - Form 2',500.00,500.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(12,'BILL-2026-00012',5,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(13,'BILL-2026-00013',5,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(14,'BILL-2026-00014',6,1,2,4,1,'Term 2 Tuition - Form 3',550.00,550.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:27:13'),(15,'BILL-2026-00015',6,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:27:13'),(16,'BILL-2026-00016',6,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-06 19:27:13'),(17,'BILL-2026-00017',7,1,2,2,1,'Term 2 Tuition - Form 1',450.00,283.25,166.75,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(18,'BILL-2026-00018',7,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(19,'BILL-2026-00019',7,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(20,'BILL-2026-00020',8,1,2,3,1,'Term 2 Tuition - Form 2',500.00,339.00,161.00,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(21,'BILL-2026-00021',8,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(22,'BILL-2026-00022',8,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(23,'BILL-2026-00023',9,1,2,4,1,'Term 2 Tuition - Form 3',550.00,550.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(24,'BILL-2026-00024',9,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(25,'BILL-2026-00025',9,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(26,'BILL-2026-00026',10,1,2,2,1,'Term 2 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-04 10:40:39'),(27,'BILL-2026-00027',10,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-04 10:40:39'),(28,'BILL-2026-00028',10,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-04 10:40:39'),(29,'BILL-2026-00029',11,1,2,3,1,'Term 2 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(30,'BILL-2026-00030',11,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(31,'BILL-2026-00031',11,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(32,'BILL-2026-00032',12,1,2,4,1,'Term 2 Tuition - Form 3',550.00,338.25,211.75,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(33,'BILL-2026-00033',12,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(34,'BILL-2026-00034',12,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(35,'BILL-2026-00035',13,1,2,2,1,'Term 2 Tuition - Form 1',450.00,309.00,141.00,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(36,'BILL-2026-00036',13,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(37,'BILL-2026-00037',13,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(38,'BILL-2026-00038',14,1,2,3,1,'Term 2 Tuition - Form 2',500.00,500.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(39,'BILL-2026-00039',14,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(40,'BILL-2026-00040',14,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(41,'BILL-2026-00041',15,1,2,4,1,'Term 2 Tuition - Form 3',550.00,550.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(42,'BILL-2026-00042',15,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(43,'BILL-2026-00043',15,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(44,'BILL-2026-00044',16,1,2,2,1,'Term 2 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(45,'BILL-2026-00045',16,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(46,'BILL-2026-00046',16,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(47,'BILL-2026-00047',17,1,2,3,1,'Term 2 Tuition - Form 2',500.00,310.75,189.25,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(48,'BILL-2026-00048',17,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(49,'BILL-2026-00049',17,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(50,'BILL-2026-00050',18,1,2,4,1,'Term 2 Tuition - Form 3',550.00,369.00,181.00,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(51,'BILL-2026-00051',18,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(52,'BILL-2026-00052',18,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(53,'BILL-2026-00053',19,1,2,2,1,'Term 2 Tuition - Form 1',450.00,450.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(54,'BILL-2026-00054',19,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(55,'BILL-2026-00055',19,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(56,'BILL-2026-00056',20,1,2,3,1,'Term 2 Tuition - Form 2',500.00,500.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(57,'BILL-2026-00057',20,1,2,5,2,'Term 2 Examination Fees',40.00,40.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(58,'BILL-2026-00058',20,1,2,6,3,'Term 2 Sports Levy',25.00,25.00,0.00,'paid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(59,'BILL-2026-00059',21,1,2,4,1,'Term 2 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(60,'BILL-2026-00060',21,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(61,'BILL-2026-00061',21,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(62,'BILL-2026-00062',22,1,2,2,1,'Term 2 Tuition - Form 1',450.00,283.25,166.75,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(63,'BILL-2026-00063',22,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(64,'BILL-2026-00064',22,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(65,'BILL-2026-00065',23,1,2,3,1,'Term 2 Tuition - Form 2',500.00,339.00,161.00,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:43'),(66,'BILL-2026-00066',23,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(67,'BILL-2026-00067',23,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(68,'BILL-2026-00068',1,1,2,5,2,'Term 2 Examination Fees',40.00,5.00,35.00,'partial','2026-09-24',3,'2026-09-03 11:38:42','2026-09-04 07:13:30'),(69,'BILL-2026-00069',1,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',3,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(70,'BILL-2026-00070',3,1,2,1,1,'Term 2 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-17',3,'2026-09-06 19:22:50','2026-09-06 19:22:50'),(71,'BILL-2026-00071',24,1,2,2,1,'Term 2 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-24',3,'2026-09-07 09:54:02','2026-09-07 09:54:02'),(72,'BILL-2026-00072',25,1,2,1,1,'Term 2 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-17',NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(73,'BILL-2026-00073',25,1,2,5,2,'Term 2 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-24',NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(74,'BILL-2026-00074',25,1,2,6,3,'Term 2 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-24',NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(75,'BILL-2026-00075',25,1,2,2,1,'Term 2 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-24',NULL,'2026-09-07 10:49:06','2026-09-07 10:49:06'),(77,'BILL-2026-00076',27,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',1,'2026-09-07 16:33:54','2026-09-07 16:33:54'),(78,'BILL-2026-00077',27,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',1,'2026-09-07 16:33:54','2026-09-07 16:33:54'),(79,'BILL-2026-00078',27,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',1,'2026-09-07 16:33:54','2026-09-07 16:33:54'),(80,'BILL-2026-00079',24,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',1,'2026-09-07 16:39:18','2026-09-07 16:39:18'),(81,'BILL-2026-00080',1,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(82,'BILL-2026-00081',1,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(83,'BILL-2026-00082',1,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(84,'BILL-2026-00083',1,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(85,'BILL-2026-00084',2,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(86,'BILL-2026-00085',2,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(87,'BILL-2026-00086',2,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(88,'BILL-2026-00087',2,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(89,'BILL-2026-00088',3,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(90,'BILL-2026-00089',3,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(91,'BILL-2026-00090',3,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(92,'BILL-2026-00091',3,1,3,10,1,'Term 3 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(93,'BILL-2026-00092',4,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(94,'BILL-2026-00093',4,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(95,'BILL-2026-00094',4,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(96,'BILL-2026-00095',4,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(97,'BILL-2026-00096',5,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(98,'BILL-2026-00097',5,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(99,'BILL-2026-00098',5,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(100,'BILL-2026-00099',5,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(101,'BILL-2026-00100',6,1,3,7,1,'Term 3 Tuition (Demo)',500.00,234.00,266.00,'partial','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 18:26:16'),(102,'BILL-2026-00101',6,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(103,'BILL-2026-00102',6,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(104,'BILL-2026-00103',6,1,3,10,1,'Term 3 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(105,'BILL-2026-00104',7,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(106,'BILL-2026-00105',7,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(107,'BILL-2026-00106',7,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(108,'BILL-2026-00107',7,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(109,'BILL-2026-00108',8,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(110,'BILL-2026-00109',8,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(111,'BILL-2026-00110',8,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(112,'BILL-2026-00111',8,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(113,'BILL-2026-00112',9,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(114,'BILL-2026-00113',9,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(115,'BILL-2026-00114',9,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(116,'BILL-2026-00115',9,1,3,10,1,'Term 3 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(117,'BILL-2026-00116',10,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(118,'BILL-2026-00117',10,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(119,'BILL-2026-00118',10,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(120,'BILL-2026-00119',10,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(121,'BILL-2026-00120',11,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(122,'BILL-2026-00121',11,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(123,'BILL-2026-00122',11,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(124,'BILL-2026-00123',11,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(125,'BILL-2026-00124',12,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(126,'BILL-2026-00125',12,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(127,'BILL-2026-00126',12,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(128,'BILL-2026-00127',12,1,3,10,1,'Term 3 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(129,'BILL-2026-00128',13,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(130,'BILL-2026-00129',13,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(131,'BILL-2026-00130',13,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(132,'BILL-2026-00131',13,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(133,'BILL-2026-00132',14,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(134,'BILL-2026-00133',14,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(135,'BILL-2026-00134',14,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(136,'BILL-2026-00135',14,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:18','2026-09-07 16:59:18'),(137,'BILL-2026-00136',15,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(138,'BILL-2026-00137',15,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(139,'BILL-2026-00138',15,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(140,'BILL-2026-00139',15,1,3,10,1,'Term 3 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(141,'BILL-2026-00140',16,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(142,'BILL-2026-00141',16,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(143,'BILL-2026-00142',16,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(144,'BILL-2026-00143',16,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(145,'BILL-2026-00144',17,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(146,'BILL-2026-00145',17,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(147,'BILL-2026-00146',17,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(148,'BILL-2026-00147',17,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(149,'BILL-2026-00148',18,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(150,'BILL-2026-00149',18,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(151,'BILL-2026-00150',18,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(152,'BILL-2026-00151',18,1,3,10,1,'Term 3 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(153,'BILL-2026-00152',19,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(154,'BILL-2026-00153',19,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(155,'BILL-2026-00154',19,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(156,'BILL-2026-00155',19,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(157,'BILL-2026-00156',20,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(158,'BILL-2026-00157',20,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(159,'BILL-2026-00158',20,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(160,'BILL-2026-00159',20,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(161,'BILL-2026-00160',21,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(162,'BILL-2026-00161',21,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(163,'BILL-2026-00162',21,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(164,'BILL-2026-00163',21,1,3,10,1,'Term 3 Tuition - Form 3',550.00,0.00,550.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(165,'BILL-2026-00164',22,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(166,'BILL-2026-00165',22,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(167,'BILL-2026-00166',22,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(168,'BILL-2026-00167',22,1,3,8,1,'Term 3 Tuition - Form 1',450.00,0.00,450.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(169,'BILL-2026-00168',23,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(170,'BILL-2026-00169',23,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(171,'BILL-2026-00170',23,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(172,'BILL-2026-00171',23,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(173,'BILL-2026-00172',25,1,3,7,1,'Term 3 Tuition (Demo)',500.00,0.00,500.00,'unpaid','2026-09-22',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(174,'BILL-2026-00173',25,1,3,11,2,'Term 3 Examination Fees',40.00,0.00,40.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(175,'BILL-2026-00174',25,1,3,12,3,'Term 3 Sports Levy',25.00,0.00,25.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19'),(176,'BILL-2026-00175',25,1,3,9,1,'Term 3 Tuition - Form 2',500.00,0.00,500.00,'unpaid','2026-09-29',NULL,'2026-09-07 16:59:19','2026-09-07 16:59:19');
/*!40000 ALTER TABLE `student_bills` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_documents`
--

DROP TABLE IF EXISTS `student_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_key` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_documents_student_id_index` (`student_id`),
  CONSTRAINT `student_documents_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_documents`
--

LOCK TABLES `student_documents` WRITE;
/*!40000 ALTER TABLE `student_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_enrollments`
--

DROP TABLE IF EXISTS `student_enrollments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_enrollments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `enrollment_status` enum('enrolled','completed','withdrawn','transferred','supplementary','repeated','graduated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `promoted_from_stream_id` bigint unsigned DEFAULT NULL,
  `promoted_to_stream_id` bigint unsigned DEFAULT NULL,
  `repeated` tinyint(1) NOT NULL DEFAULT '0',
  `graduated` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_enrollments_unique_student_term` (`student_id`,`academic_year_id`,`term_id`),
  KEY `student_enrollments_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `student_enrollments_stream_id_form_id_index` (`stream_id`,`form_id`),
  KEY `student_enrollments_category_id_index` (`category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_enrollments`
--

LOCK TABLES `student_enrollments` WRITE;
/*!40000 ALTER TABLE `student_enrollments` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_enrollments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fees`
--

DROP TABLE IF EXISTS `student_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_fees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` enum('term1','term2','term3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('pending','partial','paid','overdue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_fees_student_id_index` (`student_id`),
  KEY `student_fees_academic_year_index` (`academic_year`),
  KEY `student_fees_status_index` (`status`),
  CONSTRAINT `student_fees_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_fees`
--

LOCK TABLES `student_fees` WRITE;
/*!40000 ALTER TABLE `student_fees` DISABLE KEYS */;
INSERT INTO `student_fees` VALUES (1,1,'2026/2027','term1',350.00,350.00,0.00,'2026-02-01','paid','2026-08-06 08:20:54','2026-08-06 08:20:54'),(2,1,'2026/2027','term2',350.00,150.00,200.00,'2026-05-01','partial','2026-08-06 08:20:54','2026-08-06 08:20:54'),(3,2,'2026/2027','term1',350.00,0.00,350.00,'2026-02-01','overdue','2026-08-06 08:20:54','2026-08-06 08:20:54');
/*!40000 ALTER TABLE `student_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_notifications`
--

DROP TABLE IF EXISTS `student_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `guardian_id` bigint unsigned DEFAULT NULL,
  `notification_type` enum('attendance','behaviour','discipline','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('portal','sms','email','whatsapp') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'portal',
  `status` enum('pending','sent','failed','read') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'sent',
  `related_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_notifications_student_id_index` (`student_id`),
  KEY `student_notifications_guardian_id_index` (`guardian_id`),
  KEY `student_notifications_related_type_related_id_index` (`related_type`,`related_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_notifications`
--

LOCK TABLES `student_notifications` WRITE;
/*!40000 ALTER TABLE `student_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_purchase_items`
--

DROP TABLE IF EXISTS `student_purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_purchase_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_purchase_id` bigint unsigned NOT NULL,
  `shop_item_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `line_total` decimal(12,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_purchase_items_student_purchase_id_index` (`student_purchase_id`),
  KEY `student_purchase_items_shop_item_id_index` (`shop_item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_purchase_items`
--

LOCK TABLES `student_purchase_items` WRITE;
/*!40000 ALTER TABLE `student_purchase_items` DISABLE KEYS */;
INSERT INTO `student_purchase_items` VALUES (1,1,1,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(2,1,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(3,1,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(4,1,7,1,0.30,0.30,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(5,2,2,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(6,2,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(7,2,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(8,2,7,1,0.30,0.30,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(9,3,1,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(10,3,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(11,3,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(12,3,7,1,0.30,0.30,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(13,4,1,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(14,4,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(15,4,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(16,5,2,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(17,5,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(18,5,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(19,6,1,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(20,6,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(21,6,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(22,7,2,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(23,7,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(24,7,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(25,8,1,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(26,8,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(27,8,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(28,9,2,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(29,9,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(30,9,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(31,10,1,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(32,10,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(33,10,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(34,11,2,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(35,11,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(36,11,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(37,12,1,1,12.50,12.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(38,12,6,3,0.50,1.50,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(39,12,4,1,8.00,8.00,'2026-09-03 11:38:43','2026-09-03 11:38:43'),(40,13,5,2,8.00,16.00,'2026-09-06 18:47:12','2026-09-06 18:47:12'),(41,13,6,1,0.50,0.50,'2026-09-06 18:47:12','2026-09-06 18:47:12'),(42,13,8,1,25.00,25.00,'2026-09-06 18:47:12','2026-09-06 18:47:12'),(43,14,1,1,12.50,12.50,'2026-09-06 19:34:54','2026-09-06 19:34:54'),(44,15,1,1,12.50,12.50,'2026-09-06 19:34:54','2026-09-06 19:34:54'),(45,16,8,3,25.00,75.00,'2026-09-07 05:01:31','2026-09-07 05:01:31'),(46,17,5,2,8.00,16.00,'2026-09-07 05:27:22','2026-09-07 05:27:22'),(47,17,9,1,0.20,0.20,'2026-09-07 05:27:22','2026-09-07 05:27:22'),(48,18,6,2,0.50,1.00,'2026-09-07 05:32:59','2026-09-07 05:32:59'),(49,19,6,3,0.50,1.50,'2026-09-07 06:54:26','2026-09-07 06:54:26');
/*!40000 ALTER TABLE `student_purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_purchase_payments`
--

DROP TABLE IF EXISTS `student_purchase_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_purchase_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_purchase_id` bigint unsigned NOT NULL,
  `finance_payment_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','ecocash','bank_transfer','swipe','online','other','account_credit') COLLATE utf8mb4_unicode_ci DEFAULT 'cash',
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `received_by` bigint unsigned NOT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_purchase_payments_student_purchase_id_index` (`student_purchase_id`),
  KEY `student_purchase_payments_payment_date_index` (`payment_date`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_purchase_payments`
--

LOCK TABLES `student_purchase_payments` WRITE;
/*!40000 ALTER TABLE `student_purchase_payments` DISABLE KEYS */;
INSERT INTO `student_purchase_payments` VALUES (1,1,NULL,11.15,'cash','SHOP-DEMO-0000',3,'2026-09-03','2026-09-03 11:38:43','2026-09-03 11:38:43'),(2,2,NULL,22.30,'ecocash','SHOP-DEMO-0001',3,'2026-09-02','2026-09-03 11:38:43','2026-09-03 11:38:43'),(3,3,NULL,22.30,'bank_transfer','SHOP-DEMO-0002',3,'2026-09-01','2026-09-03 11:38:43','2026-09-03 11:38:43'),(4,4,NULL,11.00,'swipe','SHOP-DEMO-0003',3,'2026-08-31','2026-09-03 11:38:43','2026-09-03 11:38:43'),(5,5,NULL,22.00,'cash','SHOP-DEMO-0004',3,'2026-08-30','2026-09-03 11:38:43','2026-09-03 11:38:43'),(6,6,NULL,22.00,'ecocash','SHOP-DEMO-0005',3,'2026-08-29','2026-09-03 11:38:43','2026-09-03 11:38:43'),(7,7,NULL,11.00,'bank_transfer','SHOP-DEMO-0006',3,'2026-08-28','2026-09-03 11:38:43','2026-09-03 11:38:43'),(8,8,NULL,22.00,'swipe','SHOP-DEMO-0007',3,'2026-09-03','2026-09-03 11:38:43','2026-09-03 11:38:43'),(9,9,NULL,22.00,'cash','SHOP-DEMO-0008',3,'2026-09-02','2026-09-03 11:38:43','2026-09-03 11:38:43'),(10,10,NULL,11.00,'ecocash','SHOP-DEMO-0009',3,'2026-09-01','2026-09-03 11:38:43','2026-09-03 11:38:43'),(11,11,NULL,22.00,'bank_transfer','SHOP-DEMO-0010',3,'2026-08-31','2026-09-03 11:38:43','2026-09-03 11:38:43'),(12,12,NULL,22.00,'swipe','SHOP-DEMO-0011',3,'2026-08-30','2026-09-03 11:38:43','2026-09-03 11:38:43'),(13,13,NULL,6.00,'cash',NULL,1,'2026-09-06','2026-09-06 18:47:12','2026-09-06 18:47:12'),(14,14,NULL,12.50,'account_credit',NULL,3,'2026-09-06','2026-09-06 19:34:54','2026-09-06 19:34:54'),(15,15,NULL,12.50,'account_credit',NULL,3,'2026-09-06','2026-09-06 19:34:54','2026-09-06 19:34:54'),(16,16,NULL,25.00,'cash',NULL,3,'2026-09-07','2026-09-07 05:01:31','2026-09-07 05:01:31'),(17,16,NULL,50.00,'account_credit',NULL,3,'2026-09-07','2026-09-07 05:01:31','2026-09-07 05:01:31'),(18,17,NULL,16.20,'account_credit',NULL,1,'2026-09-07','2026-09-07 05:27:22','2026-09-07 05:27:22'),(19,18,NULL,1.00,'ecocash','ECO-TEST-9988',3,'2026-09-07','2026-09-07 05:32:59','2026-09-07 05:32:59'),(20,19,NULL,1.50,'cash',NULL,3,'2026-09-07','2026-09-07 06:54:26','2026-09-07 06:54:26');
/*!40000 ALTER TABLE `student_purchase_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_purchases`
--

DROP TABLE IF EXISTS `student_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_purchases` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `purchase_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purchase_reference` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  `term_id` bigint unsigned DEFAULT NULL,
  `purchased_by` bigint unsigned DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(12,2) NOT NULL DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` enum('unpaid','partial','paid','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `verified_at` timestamp NULL DEFAULT NULL,
  `payment_status` enum('unpaid','partial','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `collected_at` timestamp NULL DEFAULT NULL,
  `collected_by` bigint unsigned DEFAULT NULL,
  `collection_notes` text COLLATE utf8mb4_unicode_ci,
  `is_preorder` tinyint(1) NOT NULL DEFAULT '0',
  `fulfilled_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` bigint unsigned NOT NULL,
  `purchase_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_purchases_purchase_number_unique` (`purchase_number`),
  UNIQUE KEY `student_purchases_purchase_reference_unique` (`purchase_reference`),
  KEY `student_purchases_purchase_number_index` (`purchase_number`),
  KEY `student_purchases_student_id_index` (`student_id`),
  KEY `student_purchases_academic_year_id_index` (`academic_year_id`),
  KEY `student_purchases_term_id_index` (`term_id`),
  KEY `student_purchases_status_index` (`status`),
  KEY `student_purchases_payment_status_index` (`payment_status`),
  KEY `student_purchases_purchase_date_index` (`purchase_date`),
  KEY `student_purchases_collected_by_foreign` (`collected_by`),
  CONSTRAINT `student_purchases_collected_by_foreign` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_purchases`
--

LOCK TABLES `student_purchases` WRITE;
/*!40000 ALTER TABLE `student_purchases` DISABLE KEYS */;
INSERT INTO `student_purchases` VALUES (1,'PUR-2026-0001','DGS-SHP-20260903-133843-B6ZXAQ',1,1,2,NULL,22.30,11.15,11.15,'partial',NULL,'partial',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-09-03','2026-09-03 11:38:43','2026-09-03 11:38:43'),(2,'PUR-2026-0002','DGS-SHP-20260903-133843-2DRQL3',2,1,2,NULL,22.30,22.30,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-09-02','2026-09-03 11:38:43','2026-09-03 11:38:43'),(3,'PUR-2026-0003','DGS-SHP-20260903-133843-4BS9PB',3,1,2,NULL,22.30,22.30,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-09-01','2026-09-03 11:38:43','2026-09-03 11:38:43'),(4,'PUR-2026-0004','DGS-SHP-20260903-133843-25UZWT',4,1,2,NULL,22.00,11.00,11.00,'partial',NULL,'partial',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-08-31','2026-09-03 11:38:43','2026-09-03 11:38:43'),(5,'PUR-2026-0005','DGS-SHP-20260903-133843-77Z6DN',5,1,2,NULL,22.00,22.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-08-30','2026-09-03 11:38:43','2026-09-03 11:38:43'),(6,'PUR-2026-0006','DGS-SHP-20260903-133843-9DU294',6,1,2,NULL,22.00,22.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-08-29','2026-09-03 11:38:43','2026-09-03 11:38:43'),(7,'PUR-2026-0007','DGS-SHP-20260903-133843-GA25GY',7,1,2,NULL,22.00,11.00,11.00,'partial',NULL,'partial',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-08-28','2026-09-03 11:38:43','2026-09-03 11:38:43'),(8,'PUR-2026-0008','DGS-SHP-20260903-133843-AJK8G2',8,1,2,NULL,22.00,22.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-09-03','2026-09-03 11:38:43','2026-09-03 11:38:43'),(9,'PUR-2026-0009','DGS-SHP-20260903-133843-AURYJJ',9,1,2,NULL,22.00,22.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-09-02','2026-09-03 11:38:43','2026-09-03 11:38:43'),(10,'PUR-2026-0010','DGS-SHP-20260903-133843-2V83DJ',10,1,2,NULL,22.00,11.00,11.00,'partial',NULL,'partial',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-09-01','2026-09-03 11:38:43','2026-09-03 11:38:43'),(11,'PUR-2026-0011','DGS-SHP-20260903-133843-EUHUUF',11,1,2,NULL,22.00,22.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-08-31','2026-09-03 11:38:43','2026-09-03 11:38:43'),(12,'PUR-2026-0012','DGS-SHP-20260903-133843-4HGN6R',12,1,2,NULL,22.00,22.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-03 11:38:43','Bulk demo data',3,'2026-08-30','2026-09-03 11:38:43','2026-09-03 11:38:43'),(13,'PUR-2026-0013','DGS-SHP-20260906-204712-YFKVUV',6,NULL,NULL,NULL,41.50,6.00,35.50,'partial',NULL,'partial',NULL,NULL,NULL,0,'2026-09-06 18:47:12',NULL,1,'2026-09-06','2026-09-06 18:47:12','2026-09-06 18:47:12'),(14,'PUR-2026-0014','DGS-SHP-20260906-213454-LKG5DN',4,NULL,NULL,NULL,12.50,12.50,0.00,'cancelled',NULL,'paid',NULL,NULL,NULL,0,'2026-09-06 19:34:54','Cancelled: Smoke test cancellation',3,'2026-09-06','2026-09-06 19:34:54','2026-09-06 19:35:07'),(15,'PUR-2026-0015','DGS-SHP-20260906-213454-VYH2NR',4,NULL,NULL,NULL,12.50,12.50,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-06 19:34:54',NULL,3,'2026-09-06','2026-09-06 19:34:54','2026-09-06 19:34:54'),(16,'PUR-2026-0016','DGS-SHP-20260907-070131-8QNESA',4,NULL,NULL,NULL,75.00,75.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-07 05:01:31',NULL,3,'2026-09-07','2026-09-07 05:01:31','2026-09-07 05:01:31'),(17,'PUR-2026-0017','DGS-SHP-20260907-072722-Y7USR4',6,NULL,NULL,NULL,16.20,16.20,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-07 05:27:22',NULL,1,'2026-09-07','2026-09-07 05:27:22','2026-09-07 05:27:22'),(18,'PUR-2026-0018','DGS-SHP-20260907-073259-6VRQQN',5,NULL,NULL,NULL,1.00,1.00,0.00,'paid',NULL,'paid',NULL,NULL,NULL,0,'2026-09-07 05:32:59',NULL,3,'2026-09-07','2026-09-07 05:32:59','2026-09-07 05:32:59'),(19,'PUR-2026-0019','DGS-SHP-20260907-085426-LTH8FV',6,NULL,NULL,NULL,1.50,1.50,0.00,'paid','2026-09-07 09:00:54','paid',NULL,NULL,NULL,0,'2026-09-07 06:54:26',NULL,3,'2026-09-07','2026-09-07 06:54:26','2026-09-07 06:54:26');
/*!40000 ALTER TABLE `student_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_subjects`
--

DROP TABLE IF EXISTS `student_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `stream_subject_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `is_compulsory` tinyint(1) NOT NULL DEFAULT '0',
  `enrollment_status` enum('active','dropped') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_subject` (`student_id`,`subject_id`,`academic_year_id`,`term_id`),
  KEY `student_subjects_student_id_enrollment_status_index` (`student_id`,`enrollment_status`),
  KEY `student_subjects_stream_subject_id_index` (`stream_subject_id`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_subjects`
--

LOCK TABLES `student_subjects` WRITE;
/*!40000 ALTER TABLE `student_subjects` DISABLE KEYS */;
INSERT INTO `student_subjects` VALUES (1,24,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(2,1,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(3,4,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(4,7,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(5,10,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(6,13,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(7,16,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(8,19,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(9,22,1,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(10,24,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(11,1,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(12,4,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(13,7,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(14,10,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(15,13,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(16,16,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(17,19,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(18,22,2,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(19,24,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(20,1,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(21,4,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(22,7,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(23,10,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(24,13,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(25,16,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(26,19,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(27,22,3,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(28,24,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(29,1,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(30,4,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(31,7,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(32,10,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(33,13,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(34,16,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(35,19,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(36,22,4,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(37,24,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(38,1,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(39,4,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(40,7,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(41,10,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(42,13,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(43,16,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(44,19,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(45,22,5,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(46,25,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(47,2,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(48,5,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(49,8,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(50,11,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(51,14,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(52,17,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(53,20,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(54,23,6,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(55,25,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(56,2,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(57,5,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(58,8,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(59,11,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(60,14,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(61,17,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(62,20,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(63,23,7,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(64,25,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(65,2,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(66,5,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(67,8,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(68,11,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(69,14,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(70,17,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(71,20,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(72,23,8,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(73,25,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(74,2,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(75,5,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(76,8,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(77,11,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(78,14,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(79,17,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(80,20,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(81,23,9,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(82,25,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(83,2,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(84,5,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(85,8,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(86,11,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(87,14,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(88,17,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(89,20,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(90,23,10,5,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(91,3,11,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(92,6,11,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(93,9,11,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(94,12,11,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(95,15,11,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(96,18,11,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(97,21,11,1,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(98,3,12,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(99,6,12,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(100,9,12,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(101,12,12,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(102,15,12,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(103,18,12,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(104,21,12,2,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(105,3,13,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(106,6,13,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(107,9,13,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(108,12,13,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(109,15,13,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(110,18,13,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(111,21,13,3,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(112,3,14,4,1,3,1,'active',1,'2026-09-07 17:12:34','2026-09-07 17:12:34'),(113,6,14,4,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(114,9,14,4,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(115,12,14,4,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(116,15,14,4,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(117,18,14,4,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(118,21,14,4,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(119,3,15,5,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(120,6,15,5,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(121,9,15,5,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(122,12,15,5,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(123,15,15,5,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(124,18,15,5,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35'),(125,21,15,5,1,3,1,'active',1,'2026-09-07 17:12:35','2026-09-07 17:12:35');
/*!40000 ALTER TABLE `student_subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `application_id` bigint unsigned DEFAULT NULL,
  `admission_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `class_id` bigint unsigned DEFAULT NULL,
  `stream_id` bigint unsigned DEFAULT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  `admission_date` date NOT NULL,
  `status` enum('active','inactive','transferred','graduated','suspended','deceased') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `document_verified_at` timestamp NULL DEFAULT NULL,
  `document_verified_by` bigint unsigned DEFAULT NULL,
  `verification_due_at` date DEFAULT NULL,
  `blood_type` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allergies` text COLLATE utf8mb4_unicode_ci,
  `medical_conditions` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_admission_number_unique` (`admission_number`),
  UNIQUE KEY `students_student_number_unique` (`student_number`),
  UNIQUE KEY `students_application_id_unique` (`application_id`),
  KEY `students_user_id_foreign` (`user_id`),
  KEY `students_admission_number_index` (`admission_number`),
  KEY `students_class_id_index` (`class_id`),
  KEY `students_status_index` (`status`),
  KEY `students_stream_id_foreign` (`stream_id`),
  KEY `students_form_id_foreign` (`form_id`),
  KEY `students_category_id_foreign` (`category_id`),
  KEY `students_academic_year_id_foreign` (`academic_year_id`),
  KEY `students_document_verified_by_foreign` (`document_verified_by`),
  CONSTRAINT `students_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_application_id_foreign` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_document_verified_by_foreign` FOREIGN KEY (`document_verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_form_id_foreign` FOREIGN KEY (`form_id`) REFERENCES `forms` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_stream_id_foreign` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,NULL,NULL,'DGI-2026-0001','DGI-2026-0001',NULL,'Tatenda','Dube','tatenda.dube@student.destinygate.ac.zw','2010-03-15','male',1,NULL,NULL,NULL,NULL,'2026-01-10','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-06 08:20:53','2026-09-04 10:38:16'),(2,NULL,NULL,'DGI-2026-0002','DGI-2026-0002',NULL,'Chiedza','Moyo',NULL,'2009-07-22','female',2,NULL,NULL,NULL,NULL,'2026-01-10','active','2026-08-09 15:10:07',1,NULL,NULL,NULL,NULL,'2026-08-06 08:20:53','2026-09-04 10:38:16'),(3,NULL,NULL,'DGI-2026-0003','DGI-2026-0003',NULL,'Farai','Zimba',NULL,'2008-11-05','male',3,NULL,NULL,NULL,NULL,'2026-01-10','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-08-06 08:20:53','2026-09-04 10:38:16'),(4,NULL,NULL,'DGI-2026-0004','DGI-2026-0004',NULL,'Tanaka','Ncube','tanaka.ncube@student.destinygate.ac.zw','2012-01-01','male',1,NULL,NULL,NULL,NULL,'2026-01-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:40','2026-09-04 10:38:16'),(5,NULL,NULL,'DGI-2026-0005','DGI-2026-0005',NULL,'Nyasha','Sibanda','nyasha.sibanda@student.destinygate.ac.zw','2011-08-04','female',2,NULL,NULL,NULL,NULL,'2026-02-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:40','2026-09-04 10:38:16'),(6,NULL,NULL,'DGI-2026-0006','DGI-2026-0006',NULL,'Tafadzwa','Chikwanha','tafadzwa.chikwanha@student.destinygate.ac.zw','2010-03-07','male',3,NULL,NULL,NULL,NULL,'2026-03-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:40','2026-09-04 10:38:16'),(7,NULL,NULL,'DGI-2026-0007','DGI-2026-0007',NULL,'Kudzai','Gumbo','kudzai.gumbo@student.destinygate.ac.zw','2012-10-10','female',1,NULL,NULL,NULL,NULL,'2026-01-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:40','2026-09-04 10:38:16'),(8,NULL,NULL,'DGI-2026-0008','DGI-2026-0008',NULL,'Blessing','Mutasa','blessing.mutasa@student.destinygate.ac.zw','2011-05-13','male',2,NULL,NULL,NULL,NULL,'2026-02-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(9,NULL,NULL,'DGI-2026-0009','DGI-2026-0009',NULL,'Ropafadzo','Chirwa','ropafadzo.chirwa@student.destinygate.ac.zw','2010-12-16','female',3,NULL,NULL,NULL,NULL,'2026-03-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(10,NULL,NULL,'DGI-2026-0010','DGI-2026-0010',NULL,'Panashe','Marufu','panashe.marufu@student.destinygate.ac.zw','2012-07-19','male',1,NULL,NULL,NULL,NULL,'2026-01-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(11,NULL,NULL,'DGI-2026-0011','DGI-2026-0011',NULL,'Thandiwe','Chitando','thandiwe.chitando@student.destinygate.ac.zw','2011-02-22','female',2,NULL,NULL,NULL,NULL,'2026-02-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(12,NULL,NULL,'DGI-2026-0012','DGI-2026-0012',NULL,'Takudzwa','Zvobgo','takudzwa.zvobgo@student.destinygate.ac.zw','2010-09-25','male',3,NULL,NULL,NULL,NULL,'2026-03-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(13,NULL,NULL,'DGI-2026-0013','DGI-2026-0013',NULL,'Vimbai','Mangwana','vimbai.mangwana@student.destinygate.ac.zw','2012-04-01','female',1,NULL,NULL,NULL,NULL,'2026-01-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(14,NULL,NULL,'DGI-2026-0014','DGI-2026-0014',NULL,'Tanaka','Chigumba','tanaka.chigumba@student.destinygate.ac.zw','2011-11-04','male',2,NULL,NULL,NULL,NULL,'2026-02-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(15,NULL,NULL,'DGI-2026-0015','DGI-2026-0015',NULL,'Nyasha','Nyoni','nyasha.nyoni@student.destinygate.ac.zw','2010-06-07','female',3,NULL,NULL,NULL,NULL,'2026-03-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(16,NULL,NULL,'DGI-2026-0016','DGI-2026-0016',NULL,'Tafadzwa','Muleya','tafadzwa.muleya@student.destinygate.ac.zw','2012-01-10','male',1,NULL,NULL,NULL,NULL,'2026-01-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(17,NULL,NULL,'DGI-2026-0017','DGI-2026-0017',NULL,'Kudzai','Chinyama','kudzai.chinyama@student.destinygate.ac.zw','2011-08-13','female',2,NULL,NULL,NULL,NULL,'2026-02-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(18,NULL,NULL,'DGI-2026-0018','DGI-2026-0018',NULL,'Blessing','Chikafu','blessing.chikafu@student.destinygate.ac.zw','2010-03-16','male',3,NULL,NULL,NULL,NULL,'2026-03-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(19,NULL,NULL,'DGI-2026-0019','DGI-2026-0019',NULL,'Ropafadzo','Mabika','ropafadzo.mabika@student.destinygate.ac.zw','2012-10-19','female',1,NULL,NULL,NULL,NULL,'2026-01-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:41','2026-09-04 10:38:16'),(20,NULL,NULL,'DGI-2026-0020','DGI-2026-0020',NULL,'Panashe','Musonza','panashe.musonza@student.destinygate.ac.zw','2011-05-22','male',2,NULL,NULL,NULL,NULL,'2026-02-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:42','2026-09-04 10:38:16'),(21,NULL,NULL,'DGI-2026-0021','DGI-2026-0021',NULL,'Thandiwe','Chipunza','thandiwe.chipunza@student.destinygate.ac.zw','2010-12-25','female',3,NULL,NULL,NULL,NULL,'2026-03-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:42','2026-09-04 10:38:16'),(22,NULL,NULL,'DGI-2026-0022','DGI-2026-0022',NULL,'Takudzwa','Dziva','takudzwa.dziva@student.destinygate.ac.zw','2012-07-01','male',1,NULL,NULL,NULL,NULL,'2026-01-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:42','2026-09-04 10:38:16'),(23,NULL,NULL,'DGI-2026-0023','DGI-2026-0023',NULL,'Vimbai','Mavhunga','vimbai.mavhunga@student.destinygate.ac.zw','2011-02-04','female',2,NULL,NULL,NULL,NULL,'2026-02-15','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-03 11:38:42','2026-09-04 10:38:16'),(24,14,NULL,'D0260024T','D0260024T','22-321136D54','Ndlovu','Samuel','samndlovucrypto@gmail.com','2002-08-31','male',NULL,1,1,NULL,NULL,'2026-09-07','active','2026-09-07 09:42:48',1,NULL,NULL,NULL,NULL,'2026-09-07 09:39:36','2026-09-07 09:42:48'),(25,15,NULL,'D0260025B','D0260025B','67-89978O89','Jack','Kombo','samndlovucrypto@gmail.co','2026-07-14','male',NULL,2,2,NULL,NULL,'2026-09-07','active','2026-09-07 10:17:31',1,NULL,NULL,NULL,NULL,'2026-09-07 10:16:35','2026-09-07 11:53:11'),(27,17,NULL,'D0260026H','D0260026H','34-98765U78','Ndlovuss','Hendricks','samndlovucrypto@gmail.ZW','2000-09-01',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-07','active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-09-07 16:33:54','2026-09-07 16:33:54');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subject_groups`
--

DROP TABLE IF EXISTS `subject_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subject_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subject_groups_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subject_groups`
--

LOCK TABLES `subject_groups` WRITE;
/*!40000 ALTER TABLE `subject_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `subject_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subjects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subject_group_id` bigint unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pass_mark` tinyint unsigned NOT NULL DEFAULT '50',
  `is_compulsory` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subjects_code_unique` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
INSERT INTO `subjects` VALUES (1,NULL,'Mathematics','MATH',50,1,1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(2,NULL,'English Language','ENG',50,1,1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(3,NULL,'Combined Science','SCI',50,0,1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(4,NULL,'History','HIST',50,0,1,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(5,NULL,'Geography','GEO',50,0,1,'2026-08-06 08:20:53','2026-08-06 08:20:53');
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_allocations`
--

DROP TABLE IF EXISTS `teacher_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_allocation` (`teacher_id`,`subject_id`,`stream_id`,`academic_year_id`,`term_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_allocations`
--

LOCK TABLES `teacher_allocations` WRITE;
/*!40000 ALTER TABLE `teacher_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_comments`
--

DROP TABLE IF EXISTS `teacher_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `class_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `term` enum('term1','term2','term3') COLLATE utf8mb4_unicode_ci NOT NULL,
  `progress` text COLLATE utf8mb4_unicode_ci,
  `participation` text COLLATE utf8mb4_unicode_ci,
  `homework` text COLLATE utf8mb4_unicode_ci,
  `behaviour` text COLLATE utf8mb4_unicode_ci,
  `areas_for_improvement` text COLLATE utf8mb4_unicode_ci,
  `strengths` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `teacher_comments_teacher_id_foreign` (`teacher_id`),
  KEY `teacher_comments_student_id_index` (`student_id`),
  KEY `teacher_comments_class_id_index` (`class_id`),
  CONSTRAINT `teacher_comments_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_comments_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `teacher_comments_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_comments`
--

LOCK TABLES `teacher_comments` WRITE;
/*!40000 ALTER TABLE `teacher_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_profiles`
--

DROP TABLE IF EXISTS `teacher_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `staff_member_id` bigint unsigned NOT NULL,
  `teacher_code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `specialization` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teacher_profiles_staff_member_id_unique` (`staff_member_id`),
  UNIQUE KEY `teacher_profiles_teacher_code_unique` (`teacher_code`),
  KEY `teacher_profiles_staff_member_id_index` (`staff_member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_profiles`
--

LOCK TABLES `teacher_profiles` WRITE;
/*!40000 ALTER TABLE `teacher_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_subject_allocations`
--

DROP TABLE IF EXISTS `teacher_subject_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_subject_allocations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint unsigned NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `stream_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_subject_allocation` (`teacher_id`,`subject_id`,`stream_id`,`academic_year_id`,`term_id`),
  KEY `tsa_teacher_year_term_idx` (`teacher_id`,`academic_year_id`,`term_id`),
  KEY `tsa_stream_subject_idx` (`stream_id`,`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_subject_allocations`
--

LOCK TABLES `teacher_subject_allocations` WRITE;
/*!40000 ALTER TABLE `teacher_subject_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_subject_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `terms`
--

DROP TABLE IF EXISTS `terms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `terms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned NOT NULL,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `terms_academic_year_id_name_unique` (`academic_year_id`,`name`),
  CONSTRAINT `terms_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `terms`
--

LOCK TABLES `terms` WRITE;
/*!40000 ALTER TABLE `terms` DISABLE KEYS */;
INSERT INTO `terms` VALUES (3,1,'Term 3','2026-09-08','2026-12-02',1,'2026-08-06 08:20:54','2026-09-14 15:51:14');
/*!40000 ALTER TABLE `terms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_periods`
--

DROP TABLE IF EXISTS `timetable_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timetable_periods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_number` int unsigned NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `is_break` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `timetable_periods_period_number_unique` (`period_number`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_periods`
--

LOCK TABLES `timetable_periods` WRITE;
/*!40000 ALTER TABLE `timetable_periods` DISABLE KEYS */;
INSERT INTO `timetable_periods` VALUES (1,'Period 1',1,'08:00:00','08:40:00',0,1,'2026-08-06 08:17:25','2026-08-06 08:17:25'),(2,'Period 2',2,'08:40:00','09:20:00',0,1,'2026-08-06 08:17:25','2026-08-06 08:17:25'),(3,'Period 3',3,'09:20:00','10:00:00',0,1,'2026-08-06 08:17:25','2026-08-06 08:17:25'),(4,'Break',4,'10:00:00','10:20:00',1,1,'2026-08-06 08:17:25','2026-08-06 08:17:25'),(5,'Period 4',5,'10:20:00','11:00:00',0,1,'2026-08-06 08:17:25','2026-08-06 08:17:25'),(6,'Period 5',6,'11:00:00','11:40:00',0,1,'2026-08-06 08:17:25','2026-08-06 08:17:25');
/*!40000 ALTER TABLE `timetable_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetable_rooms`
--

DROP TABLE IF EXISTS `timetable_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timetable_rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `room_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_type` enum('classroom','laboratory','computer_lab','hall','sports') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'classroom',
  `capacity` int unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetable_rooms`
--

LOCK TABLES `timetable_rooms` WRITE;
/*!40000 ALTER TABLE `timetable_rooms` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetable_rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `timetables` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `academic_year_id` bigint unsigned DEFAULT NULL,
  `term_id` bigint unsigned DEFAULT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `stream_id` bigint unsigned DEFAULT NULL,
  `class_id` bigint unsigned NOT NULL,
  `day_of_week` enum('monday','tuesday','wednesday','thursday','friday','saturday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_number` int NOT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `teacher_id` bigint unsigned NOT NULL,
  `room_id` bigint unsigned DEFAULT NULL,
  `period_id` bigint unsigned DEFAULT NULL,
  `start_time` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `end_time` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timetable_type` enum('class','exam','remedial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'class',
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `timetables_subject_id_foreign` (`subject_id`),
  KEY `timetables_teacher_id_foreign` (`teacher_id`),
  KEY `timetables_class_id_index` (`class_id`),
  KEY `timetables_day_of_week_index` (`day_of_week`),
  KEY `timetables_period_number_index` (`period_number`),
  CONSTRAINT `timetables_class_id_foreign` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetables_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `timetables_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetables`
--

LOCK TABLES `timetables` WRITE;
/*!40000 ALTER TABLE `timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transcript_subject_history`
--

DROP TABLE IF EXISTS `transcript_subject_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transcript_subject_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `term_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `stream_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `subject_id` bigint unsigned NOT NULL,
  `subject_average` decimal(6,2) DEFAULT NULL,
  `grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gpa_points` decimal(4,2) DEFAULT NULL,
  `is_withheld` tinyint(1) NOT NULL DEFAULT '0',
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transcript_subject_unique_row` (`student_id`,`academic_year_id`,`term_id`,`subject_id`),
  KEY `transcript_subject_history_academic_year_id_term_id_index` (`academic_year_id`,`term_id`),
  KEY `transcript_subject_history_stream_id_form_id_index` (`stream_id`,`form_id`),
  KEY `transcript_subject_history_category_id_index` (`category_id`),
  KEY `transcript_subject_history_subject_id_index` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transcript_subject_history`
--

LOCK TABLES `transcript_subject_history` WRITE;
/*!40000 ALTER TABLE `transcript_subject_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `transcript_subject_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transcript_year_aggregates`
--

DROP TABLE IF EXISTS `transcript_year_aggregates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transcript_year_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `academic_year_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned DEFAULT NULL,
  `stream_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `year_average` decimal(6,2) DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `is_withheld` tinyint(1) NOT NULL DEFAULT '0',
  `computed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transcript_year_unique_student_year` (`student_id`,`academic_year_id`),
  KEY `transcript_year_aggregates_academic_year_id_index` (`academic_year_id`),
  KEY `transcript_year_aggregates_stream_id_form_id_index` (`stream_id`,`form_id`),
  KEY `transcript_year_aggregates_category_id_index` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transcript_year_aggregates`
--

LOCK TABLES `transcript_year_aggregates` WRITE;
/*!40000 ALTER TABLE `transcript_year_aggregates` DISABLE KEYS */;
/*!40000 ALTER TABLE `transcript_year_aggregates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(320) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_method` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','headmaster','teacher','bursar','storekeeper','parent','student','user') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `last_signed_in` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_email_index` (`email`),
  KEY `users_role_index` (`role`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Administrator','admin@destinygate.ac.zw',NULL,NULL,'$2y$12$1DEn4lWk6cN9lKUFU0flJORa7VYpmI.qwlGq/cwX9tpXCbQpen69C',NULL,NULL,'admin',1,0,'2026-09-03 15:31:38',NULL,'2026-08-06 08:20:52','2026-08-06 08:20:52'),(2,'Dr. Emmanuel Chikwanda','headmaster@destinygate.ac.zw',NULL,NULL,'$2y$12$wnWBNIozb8fg17mF/hEmm.O9XGYYBLcTjuc0uAuMu.C.a5vgBGoNa',NULL,NULL,'headmaster',1,0,'2026-08-09 14:57:27',NULL,'2026-08-06 08:20:52','2026-08-06 08:20:52'),(3,'Mrs. Grace Moyo','bursar@destinygate.ac.zw',NULL,NULL,'$2y$12$pa2rmBU38w4pcsjLwaeLP.SdLMPhATNNrWay6ymEcjeJRElySNrIW',NULL,NULL,'bursar',1,0,'2026-08-06 17:52:25',NULL,'2026-08-06 08:20:52','2026-08-06 08:20:52'),(4,'Mr. Tendai Mutasa','tmutasa@destinygate.ac.zw',NULL,NULL,'$2y$12$94XRXwdhKx/2IWhVX5PqaesZ96g8pVLsjk5L9r1Q3ray9Hid/QuOW',NULL,NULL,'teacher',1,0,'2026-08-06 10:20:53',NULL,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(5,'Ms. Rudo Ncube','rncube@destinygate.ac.zw',NULL,NULL,'$2y$12$mM68D0ucvt8AAUB7HXkGUeMfPd9qJ7ZZdHYzKfdq4eLwD/xbct2ZO',NULL,NULL,'teacher',1,0,'2026-08-06 10:20:53',NULL,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(6,'Mr. John Dube','jdube@gmail.com',NULL,NULL,'$2y$12$aTb4Bw4lDfLxR8ypkHLx2eil6e/AKZk3foIZGkvI5T0vizGbXJzMW',NULL,NULL,'parent',1,0,'2026-08-09 15:26:22',NULL,'2026-08-06 08:20:53','2026-08-06 08:20:53'),(7,'Mr/Mrs Grace Ncube','grace.ncube0@gmail.com',NULL,NULL,'$2y$12$m9Kee70.DXsXs0EOlm6oaOMA0ATOECdzNyH.YVg2BAlOPWmQn3jzC',NULL,NULL,'parent',1,0,'2026-09-03 13:38:40',NULL,'2026-09-03 11:38:40','2026-09-03 11:38:40'),(8,'Mr/Mrs Peter Gumbo','peter.gumbo3@gmail.com',NULL,NULL,'$2y$12$0pKgtctwW8xseZi20igcH.VMltBouWmNJ5BQjB.bbYBWDZ.4tf5ri',NULL,NULL,'parent',1,0,'2026-09-03 13:38:41',NULL,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(9,'Mr/Mrs Mary Marufu','mary.marufu6@gmail.com',NULL,NULL,'$2y$12$JxRIAXIcNhIDAQFtqG8ea.ye.2dMzn8x.fp0vojakaC/UcbIwgkC2',NULL,NULL,'parent',1,0,'2026-09-03 13:38:41',NULL,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(10,'Mr/Mrs Joseph Mangwana','joseph.mangwana9@gmail.com',NULL,NULL,'$2y$12$0ZpEVxse4mQiq7bbsB/P9esWWshZG2cx9IkJfi6dwkAKLmcv8aip.',NULL,NULL,'parent',1,0,'2026-09-03 13:38:41',NULL,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(11,'Mr/Mrs Ruth Muleya','ruth.muleya12@gmail.com',NULL,NULL,'$2y$12$U01BrBhtzgGQEOjSBPOFo.L2iJs3kFvQIvy6fEkhV7s6obgbGuBZO',NULL,NULL,'parent',1,0,'2026-09-03 13:38:41',NULL,'2026-09-03 11:38:41','2026-09-03 11:38:41'),(12,'Mr/Mrs Simon Mabika','simon.mabika15@gmail.com',NULL,NULL,'$2y$12$9t55YGyJOtWRwcobfhn3jutJFfM6ZaEC4eGvSGW0phrY9wYZ8UsNm',NULL,NULL,'parent',1,0,'2026-09-03 13:38:42',NULL,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(13,'Mr/Mrs Agnes Dziva','agnes.dziva18@gmail.com',NULL,NULL,'$2y$12$S0AiDFJ6/PrCJMp6AW.c3.tZs1r0tQr5cqhmL1LQFPMsF02ZmWGFG',NULL,NULL,'parent',1,0,'2026-09-03 13:38:42',NULL,'2026-09-03 11:38:42','2026-09-03 11:38:42'),(14,'Ndlovu Samuel','samndlovucrypto@gmail.com','D0260024T',NULL,'$2y$12$DnU8j67pOxhM4xfL86c9x.gLaXxQdDROacvsCHqg7jFKvwQCq0RNS',NULL,NULL,'student',1,1,'2026-09-07 09:39:36',NULL,'2026-09-07 09:39:36','2026-09-07 09:39:36'),(15,'Jack Kombo','samndlovucrypto@gmail.co','D0260025B',NULL,'$2y$12$8g82brScO3X5vleocvKUy.K60FkhHAiuoOLYW6JDiNVHTuozvPjtS',NULL,NULL,'student',1,1,'2026-09-07 10:16:35',NULL,'2026-09-07 10:16:35','2026-09-07 11:53:11'),(17,'Ndlovuss Hendricks','samndlovucrypto@gmail.ZW','D0260026H',NULL,'$2y$12$KGJbKIzHF9fAw6znAIagneEmpXm4mbK67NUEzeFcDZpzEhuUH.1ia',NULL,NULL,'student',1,1,'2026-09-07 16:33:54',NULL,'2026-09-07 16:33:54','2026-09-07 16:33:54');
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

-- Dump completed on 2026-09-15 19:18:58
