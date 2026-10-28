
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
DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `type_id` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `activity_at` datetime NOT NULL,
  `follow_up_date` date DEFAULT NULL,
  `document_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_act_lead` (`company_id`,`lead_id`,`activity_at`),
  KEY `idx_act_customer` (`company_id`,`customer_id`,`activity_at`),
  KEY `idx_act_company` (`company_id`,`activity_at`),
  KEY `fk_act_lead` (`lead_id`),
  KEY `fk_act_customer` (`customer_id`),
  KEY `fk_act_type` (`type_id`),
  KEY `fk_act_doc` (`document_id`),
  CONSTRAINT `fk_act_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_act_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_act_doc` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_act_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`),
  CONSTRAINT `fk_act_type` FOREIGN KEY (`type_id`) REFERENCES `master_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` bigint(20) unsigned DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_company` (`company_id`,`created_at`),
  KEY `idx_audit_user` (`user_id`,`created_at`),
  KEY `idx_audit_module` (`module`,`record_id`),
  KEY `idx_audit_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `companies` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `website` varchar(200) DEFAULT NULL,
  `tax_info` varchar(255) DEFAULT NULL,
  `logo` varchar(100) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'BDT',
  `currency_symbol` varchar(10) NOT NULL DEFAULT '৳',
  `timezone` varchar(64) NOT NULL DEFAULT 'Asia/Dhaka',
  `date_format` varchar(20) NOT NULL DEFAULT 'd M Y',
  `invoice_footer` text DEFAULT NULL,
  `receipt_footer` text DEFAULT NULL,
  `theme_color` char(7) NOT NULL DEFAULT '#0f766e',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_companies_code` (`code`),
  KEY `idx_companies_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `companies` WRITE;
/*!40000 ALTER TABLE `companies` DISABLE KEYS */;
INSERT INTO `companies` VALUES (1,'Lake View Iconic Tower',NULL,'House 3D, Road 2A, Block J, Baridhara',NULL,NULL,NULL,NULL,'0/2026/10/02020c024567a06ff0c75b672a761703.png','BDT','৳','Asia/Dhaka','d M Y',NULL,NULL,'#0f766e','active','2026-10-07 15:23:40',NULL,NULL);
/*!40000 ALTER TABLE `companies` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `customer_code` varchar(30) NOT NULL,
  `customer_type_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `mother_name` varchar(150) DEFAULT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `alternate_phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nid` varchar(30) DEFAULT NULL,
  `passport` varchar(30) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(80) DEFAULT NULL,
  `district` varchar(80) DEFAULT NULL,
  `country` varchar(80) DEFAULT 'Bangladesh',
  `photo` varchar(100) DEFAULT NULL,
  `nid_front` varchar(100) DEFAULT NULL,
  `nid_back` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `lead_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'future customer portal login',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_customers_code` (`company_id`,`customer_code`),
  KEY `idx_customers_company` (`company_id`,`status`,`deleted_at`),
  KEY `idx_customers_phone` (`company_id`,`phone`),
  KEY `idx_customers_email` (`company_id`,`email`),
  KEY `idx_customers_nid` (`company_id`,`nid`),
  KEY `idx_customers_created` (`company_id`,`created_at`),
  KEY `fk_customers_type` (`customer_type_id`),
  KEY `fk_customers_user` (`user_id`),
  CONSTRAINT `fk_customers_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_customers_type` FOREIGN KEY (`customer_type_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `documents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `document_type_id` int(10) unsigned DEFAULT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `original_name` varchar(200) NOT NULL,
  `stored_name` varchar(100) NOT NULL,
  `mime` varchar(100) NOT NULL,
  `size` int(10) unsigned NOT NULL,
  `is_shareable` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'may be shared via signed link',
  `uploaded_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_docs_company` (`company_id`,`deleted_at`,`created_at`),
  KEY `idx_docs_customer` (`company_id`,`customer_id`),
  KEY `idx_docs_lead` (`company_id`,`lead_id`),
  KEY `idx_docs_project` (`company_id`,`project_id`),
  KEY `fk_docs_type` (`document_type_id`),
  KEY `fk_docs_project` (`project_id`),
  KEY `fk_docs_customer` (`customer_id`),
  KEY `fk_docs_lead` (`lead_id`),
  CONSTRAINT `fk_docs_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_docs_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_docs_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`),
  CONSTRAINT `fk_docs_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_docs_type` FOREIGN KEY (`document_type_id`) REFERENCES `master_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `employee_salary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_salary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL,
  `component_id` int(10) unsigned NOT NULL,
  `value` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_es` (`employee_id`,`component_id`),
  KEY `fk_es_component` (`component_id`),
  CONSTRAINT `fk_es_component` FOREIGN KEY (`component_id`) REFERENCES `salary_components` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_es_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `employee_salary` WRITE;
/*!40000 ALTER TABLE `employee_salary` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_salary` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `employee_code` varchar(30) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `designation_id` int(10) unsigned DEFAULT NULL,
  `joining_date` date DEFAULT NULL,
  `salary` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'basic salary',
  `status` enum('active','inactive','resigned') NOT NULL DEFAULT 'active',
  `photo` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_employees_code` (`company_id`,`employee_code`),
  KEY `idx_employees_company` (`company_id`,`status`,`deleted_at`),
  KEY `idx_employees_dept` (`company_id`,`department_id`),
  KEY `idx_employees_phone` (`company_id`,`phone`),
  KEY `fk_emp_user` (`user_id`),
  KEY `fk_emp_dept` (`department_id`),
  KEY `fk_emp_desig` (`designation_id`),
  CONSTRAINT `fk_emp_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_emp_dept` FOREIGN KEY (`department_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_emp_desig` FOREIGN KEY (`designation_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_emp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `expense_number` varchar(30) NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `expense_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachment` varchar(100) DEFAULT NULL,
  `attachment_name` varchar(200) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','cancelled') NOT NULL DEFAULT 'approved',
  `created_by` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_expenses_number` (`company_id`,`expense_number`),
  KEY `idx_expenses_date` (`company_id`,`expense_date`,`status`),
  KEY `idx_expenses_category` (`company_id`,`category_id`),
  KEY `idx_expenses_project` (`company_id`,`project_id`),
  KEY `fk_exp_category` (`category_id`),
  KEY `fk_exp_project` (`project_id`),
  KEY `fk_exp_method` (`payment_method_id`),
  CONSTRAINT `fk_exp_category` FOREIGN KEY (`category_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_exp_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_exp_method` FOREIGN KEY (`payment_method_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_exp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `fund_contributions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fund_contributions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `fund_number` varchar(30) NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL COMMENT 'contributor when a director/user',
  `contributor_name` varchar(150) NOT NULL COMMENT 'snapshot or free text for outside contributors',
  `contributor_phone` varchar(30) DEFAULT NULL,
  `fund_type` enum('capital','loan','other') NOT NULL DEFAULT 'capital',
  `project_id` int(10) unsigned DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `contribution_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `attachment` varchar(100) DEFAULT NULL,
  `attachment_name` varchar(200) DEFAULT NULL,
  `attachment_mime` varchar(100) DEFAULT NULL,
  `status` enum('received','cancelled') NOT NULL DEFAULT 'received',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_funds_number` (`company_id`,`fund_number`),
  KEY `idx_funds_date` (`company_id`,`contribution_date`,`status`),
  KEY `idx_funds_user` (`company_id`,`user_id`),
  KEY `idx_funds_project` (`company_id`,`project_id`),
  KEY `fk_funds_user` (`user_id`),
  KEY `fk_funds_project` (`project_id`),
  KEY `fk_funds_method` (`payment_method_id`),
  CONSTRAINT `fk_funds_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_funds_method` FOREIGN KEY (`payment_method_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_funds_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_funds_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `fund_contributions` WRITE;
/*!40000 ALTER TABLE `fund_contributions` DISABLE KEYS */;
/*!40000 ALTER TABLE `fund_contributions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `gallery_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `type` enum('image','video','document') NOT NULL,
  `stored_name` varchar(100) DEFAULT NULL,
  `original_name` varchar(200) DEFAULT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `size` int(10) unsigned DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gallery_project` (`company_id`,`project_id`,`status`,`sort_order`),
  KEY `fk_gallery_project` (`project_id`),
  CONSTRAINT `fk_gallery_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_gallery_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `gallery_items` WRITE;
/*!40000 ALTER TABLE `gallery_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `gallery_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `invoice_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoice_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` int(10) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `unit_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_ii_invoice` (`invoice_id`),
  CONSTRAINT `fk_ii_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `invoice_items` WRITE;
/*!40000 ALTER TABLE `invoice_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_items` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `invoice_number` varchar(30) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `sale_id` int(10) unsigned DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_charge` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','unpaid','partial','paid','cancelled','overdue') NOT NULL DEFAULT 'unpaid',
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(10) unsigned DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoices_number` (`company_id`,`invoice_number`),
  KEY `idx_invoices_status` (`company_id`,`status`,`deleted_at`),
  KEY `idx_invoices_customer` (`company_id`,`customer_id`),
  KEY `idx_invoices_project` (`company_id`,`project_id`),
  KEY `idx_invoices_date` (`company_id`,`invoice_date`),
  KEY `idx_invoices_due` (`company_id`,`due_date`),
  KEY `idx_invoices_sale` (`sale_id`),
  KEY `fk_invoices_customer` (`customer_id`),
  KEY `fk_invoices_project` (`project_id`),
  CONSTRAINT `fk_invoices_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_invoices_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_invoices_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_invoices_sale` FOREIGN KEY (`sale_id`) REFERENCES `share_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `lead_code` varchar(30) NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `source_id` int(10) unsigned DEFAULT NULL,
  `status_id` int(10) unsigned NOT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `converted_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_leads_code` (`company_id`,`lead_code`),
  KEY `idx_leads_status` (`company_id`,`status_id`,`deleted_at`),
  KEY `idx_leads_assigned` (`company_id`,`assigned_to`),
  KEY `idx_leads_project` (`company_id`,`project_id`),
  KEY `idx_leads_phone` (`company_id`,`phone`),
  KEY `idx_leads_email` (`company_id`,`email`),
  KEY `idx_leads_followup` (`company_id`,`follow_up_date`),
  KEY `idx_leads_created` (`company_id`,`created_at`),
  KEY `fk_leads_project` (`project_id`),
  KEY `fk_leads_source` (`source_id`),
  KEY `fk_leads_status` (`status_id`),
  KEY `fk_leads_assigned` (`assigned_to`),
  KEY `fk_leads_customer` (`customer_id`),
  CONSTRAINT `fk_leads_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leads_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_leads_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_leads_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_leads_source` FOREIGN KEY (`source_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_leads_status` FOREIGN KEY (`status_id`) REFERENCES `master_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `identifier` varchar(150) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_la_ident` (`identifier`,`ip`,`created_at`),
  KEY `idx_la_ip` (`ip`,`created_at`),
  KEY `idx_la_user` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `master_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `master_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `type` varchar(40) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `color` char(7) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_md_name` (`company_id`,`type`,`name`),
  KEY `idx_md_type` (`company_id`,`type`,`is_active`,`sort_order`),
  KEY `idx_md_code` (`company_id`,`type`,`code`),
  CONSTRAINT `fk_md_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `master_data` WRITE;
/*!40000 ALTER TABLE `master_data` DISABLE KEYS */;
INSERT INTO `master_data` VALUES (1,1,'lead_status','New','new','#0d6efd',10,1,1,'2026-10-07 15:23:40',NULL),(2,1,'lead_status','Contacted','contacted','#6610f2',20,1,0,'2026-10-07 15:23:40',NULL),(3,1,'lead_status','Interested','interested','#0dcaf0',30,1,0,'2026-10-07 15:23:40',NULL),(4,1,'lead_status','Offer Sent','offer_sent','#6f42c1',40,1,1,'2026-10-07 15:23:40',NULL),(5,1,'lead_status','Follow Up','follow_up','#fd7e14',50,1,0,'2026-10-07 15:23:40',NULL),(6,1,'lead_status','Negotiation','negotiation','#ffc107',60,1,0,'2026-10-07 15:23:40',NULL),(7,1,'lead_status','Converted','converted','#198754',70,1,1,'2026-10-07 15:23:40',NULL),(8,1,'lead_status','Lost','lost','#dc3545',80,1,1,'2026-10-07 15:23:40',NULL),(9,1,'lead_source','Facebook',NULL,NULL,10,1,0,'2026-10-07 15:23:40',NULL),(10,1,'lead_source','Website',NULL,NULL,20,1,0,'2026-10-07 15:23:40',NULL),(11,1,'lead_source','Referral',NULL,NULL,30,1,0,'2026-10-07 15:23:40',NULL),(12,1,'lead_source','Walk-in',NULL,NULL,40,1,0,'2026-10-07 15:23:40',NULL),(13,1,'lead_source','Phone Call',NULL,NULL,50,1,0,'2026-10-07 15:23:40',NULL),(14,1,'lead_source','Event',NULL,NULL,60,1,0,'2026-10-07 15:23:40',NULL),(15,1,'lead_source','Other',NULL,NULL,70,1,0,'2026-10-07 15:23:40',NULL),(16,1,'customer_type','Individual',NULL,NULL,10,1,0,'2026-10-07 15:23:40',NULL),(17,1,'customer_type','Corporate',NULL,NULL,20,1,0,'2026-10-07 15:23:40',NULL),(18,1,'customer_type','NRB (Non-Resident)',NULL,NULL,30,1,0,'2026-10-07 15:23:40',NULL),(19,1,'project_status','Planning','planning','#6c757d',10,1,0,'2026-10-07 15:23:40',NULL),(20,1,'project_status','Ongoing','ongoing','#0d6efd',20,1,0,'2026-10-07 15:23:40',NULL),(21,1,'project_status','Completed','completed','#198754',30,1,0,'2026-10-07 15:23:40',NULL),(22,1,'project_status','On Hold','on_hold','#ffc107',40,1,0,'2026-10-07 15:23:40',NULL),(23,1,'share_type','Standard',NULL,NULL,10,1,0,'2026-10-07 15:23:40',NULL),(24,1,'share_type','Premium',NULL,NULL,20,1,0,'2026-10-07 15:23:40',NULL),(25,1,'share_type','Corner Plot',NULL,NULL,30,1,0,'2026-10-07 15:23:40',NULL),(26,1,'payment_method','Cash','cash',NULL,10,1,0,'2026-10-07 15:23:40',NULL),(27,1,'payment_method','Bank','bank',NULL,20,1,0,'2026-10-07 15:23:40',NULL),(28,1,'payment_method','bKash','bkash',NULL,30,1,0,'2026-10-07 15:23:40',NULL),(29,1,'payment_method','Nagad','nagad',NULL,40,1,0,'2026-10-07 15:23:40',NULL),(30,1,'payment_method','Rocket','rocket',NULL,50,1,0,'2026-10-07 15:23:40',NULL),(31,1,'payment_method','Cheque','cheque',NULL,60,1,0,'2026-10-07 15:23:40',NULL),(32,1,'expense_category','Office Setup',NULL,NULL,10,1,0,'2026-10-07 15:23:40','2026-10-13 16:51:46'),(33,1,'expense_category','Utilities',NULL,NULL,20,1,0,'2026-10-07 15:23:40',NULL),(34,1,'expense_category','Marketing',NULL,NULL,30,1,0,'2026-10-07 15:23:40',NULL),(35,1,'expense_category','Transport',NULL,NULL,40,1,0,'2026-10-07 15:23:40',NULL),(36,1,'expense_category','Salary',NULL,NULL,50,1,0,'2026-10-07 15:23:40',NULL),(37,1,'expense_category','Maintenance',NULL,NULL,60,1,0,'2026-10-07 15:23:40',NULL),(38,1,'expense_category','Other',NULL,NULL,80,1,0,'2026-10-07 15:23:40','2026-10-13 16:52:15'),(39,1,'department','Management',NULL,NULL,10,1,0,'2026-10-07 15:23:40',NULL),(40,1,'department','Sales',NULL,NULL,20,1,0,'2026-10-07 15:23:40',NULL),(41,1,'department','Accounts',NULL,NULL,30,1,0,'2026-10-07 15:23:40',NULL),(42,1,'department','Operations',NULL,NULL,40,1,0,'2026-10-07 15:23:40',NULL),(43,1,'designation','Managing Director',NULL,NULL,10,1,0,'2026-10-07 15:23:40',NULL),(44,1,'designation','Manager',NULL,NULL,20,1,0,'2026-10-07 15:23:40',NULL),(45,1,'designation','Sales Executive',NULL,NULL,30,1,0,'2026-10-07 15:23:40',NULL),(46,1,'designation','Accountant',NULL,NULL,40,1,0,'2026-10-07 15:23:40',NULL),(47,1,'designation','Office Assistant',NULL,NULL,50,1,0,'2026-10-07 15:23:40',NULL),(48,1,'document_type','Land Document',NULL,NULL,10,1,0,'2026-10-07 15:23:40',NULL),(49,1,'document_type','Deed / Dolil',NULL,NULL,20,1,0,'2026-10-07 15:23:40',NULL),(50,1,'document_type','Project Brochure',NULL,NULL,30,1,0,'2026-10-07 15:23:40',NULL),(51,1,'document_type','Agreement',NULL,NULL,40,1,0,'2026-10-07 15:23:40',NULL),(52,1,'document_type','Payment Receipt',NULL,NULL,50,1,0,'2026-10-07 15:23:40',NULL),(53,1,'document_type','Customer NID',NULL,NULL,60,1,0,'2026-10-07 15:23:40',NULL),(54,1,'document_type','Other',NULL,NULL,70,1,0,'2026-10-07 15:23:40',NULL),(55,1,'activity_type','Call','call','#0d6efd',10,1,1,'2026-10-07 15:23:40',NULL),(56,1,'activity_type','Meeting','meeting','#6610f2',20,1,1,'2026-10-07 15:23:40',NULL),(57,1,'activity_type','SMS','sms','#20c997',30,1,1,'2026-10-07 15:23:40',NULL),(58,1,'activity_type','Email','email','#0dcaf0',40,1,1,'2026-10-07 15:23:40',NULL),(59,1,'activity_type','WhatsApp','whatsapp','#25d366',50,1,1,'2026-10-07 15:23:40',NULL),(60,1,'activity_type','Follow-up','follow_up','#fd7e14',60,1,1,'2026-10-07 15:23:40',NULL),(61,1,'activity_type','Note','note','#6c757d',70,1,1,'2026-10-07 15:23:40',NULL),(62,1,'activity_type','Offer Sent','offer_sent','#6f42c1',80,1,1,'2026-10-07 15:23:40',NULL),(63,1,'activity_type','Payment','payment','#198754',90,1,1,'2026-10-07 15:23:40',NULL),(64,1,'activity_type','Document Shared','document_shared','#795548',100,1,1,'2026-10-07 15:23:40',NULL),(65,1,'expense_category','Director Meeting',NULL,NULL,70,1,0,'2026-10-13 16:52:31','2026-10-13 16:53:23');
/*!40000 ALTER TABLE `master_data` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `channel` varchar(20) NOT NULL DEFAULT 'system',
  `recipient` varchar(150) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `body` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `status` enum('queued','sent','failed','read') NOT NULL DEFAULT 'queued',
  `error` varchar(255) DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`,`status`,`created_at`),
  KEY `idx_notif_company` (`company_id`,`created_at`),
  CONSTRAINT `fk_notif_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `number_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `number_sequences` (
  `company_id` int(10) unsigned NOT NULL,
  `seq_key` varchar(30) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `next_number` int(10) unsigned NOT NULL DEFAULT 1,
  `number_length` tinyint(3) unsigned NOT NULL DEFAULT 6,
  `yearly_reset` tinyint(1) NOT NULL DEFAULT 0,
  `current_year` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`company_id`,`seq_key`),
  CONSTRAINT `fk_seq_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `number_sequences` WRITE;
/*!40000 ALTER TABLE `number_sequences` DISABLE KEYS */;
INSERT INTO `number_sequences` VALUES (1,'customer','CUS',1,6,0,2026),(1,'employee','EMP',1,6,0,2026),(1,'expense','EXP',1,6,1,2026),(1,'fund','FND',1,6,1,2026),(1,'invoice','INV',1,6,1,2026),(1,'lead','LEAD',1,6,0,2026),(1,'offer','OFF',1,6,1,2026),(1,'payment','PAY',1,6,1,2026),(1,'project','PRJ',1,6,0,2026),(1,'salary','SAL',1,6,1,2026),(1,'sale','SALE',1,6,1,2026);
/*!40000 ALTER TABLE `number_sequences` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `offers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `offer_number` varchar(30) NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `lead_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `package_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `shares` int(10) unsigned NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `registration_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `development_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `valid_until` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `terms` text DEFAULT NULL,
  `status` enum('draft','sent','viewed','accepted','rejected','expired','converted') NOT NULL DEFAULT 'draft',
  `sent_at` datetime DEFAULT NULL,
  `viewed_at` datetime DEFAULT NULL,
  `sale_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_offers_number` (`company_id`,`offer_number`),
  KEY `idx_offers_status` (`company_id`,`status`,`deleted_at`),
  KEY `idx_offers_lead` (`company_id`,`lead_id`),
  KEY `idx_offers_customer` (`company_id`,`customer_id`),
  KEY `idx_offers_created` (`company_id`,`created_at`),
  KEY `fk_offers_project` (`project_id`),
  KEY `fk_offers_lead` (`lead_id`),
  KEY `fk_offers_customer` (`customer_id`),
  KEY `fk_offers_package` (`package_id`),
  KEY `fk_offers_sale` (`sale_id`),
  CONSTRAINT `fk_offers_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_offers_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_offers_lead` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`),
  CONSTRAINT `fk_offers_package` FOREIGN KEY (`package_id`) REFERENCES `share_packages` (`id`),
  CONSTRAINT `fk_offers_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_offers_sale` FOREIGN KEY (`sale_id`) REFERENCES `share_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `offers` WRITE;
/*!40000 ALTER TABLE `offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `offers` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pr_token` (`token_hash`),
  KEY `idx_pr_user` (`user_id`),
  CONSTRAINT `fk_pr_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `payment_number` varchar(30) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `invoice_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method_id` int(10) unsigned NOT NULL,
  `reference` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `status` enum('confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
  `idempotency_key` char(32) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(10) unsigned DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_number` (`company_id`,`payment_number`),
  UNIQUE KEY `uq_payments_idem` (`company_id`,`idempotency_key`),
  KEY `idx_payments_invoice` (`invoice_id`,`status`),
  KEY `idx_payments_customer` (`company_id`,`customer_id`),
  KEY `idx_payments_project` (`company_id`,`project_id`),
  KEY `idx_payments_date` (`company_id`,`payment_date`,`status`),
  KEY `fk_payments_customer` (`customer_id`),
  KEY `fk_payments_project` (`project_id`),
  KEY `fk_payments_method` (`payment_method_id`),
  KEY `fk_payments_receiver` (`received_by`),
  CONSTRAINT `fk_payments_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_payments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `fk_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `master_data` (`id`),
  CONSTRAINT `fk_payments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_payments_receiver` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `label` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_name` (`name`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','dashboard','View dashboard'),(2,'companies.view','companies','View companies'),(3,'companies.manage','companies','Create/edit companies (multi-company)'),(4,'users.view','users','View users'),(5,'users.create','users','Create users'),(6,'users.edit','users','Edit users / reset password'),(7,'users.delete','users','Deactivate/delete users'),(8,'roles.view','roles','View roles'),(9,'roles.create','roles','Create roles'),(10,'roles.edit','roles','Edit roles & permissions'),(11,'roles.delete','roles','Delete roles'),(12,'leads.view','leads','View leads (own/assigned)'),(13,'leads.view_all','leads','View all leads'),(14,'leads.create','leads','Create leads'),(15,'leads.edit','leads','Edit leads'),(16,'leads.assign','leads','Assign leads'),(17,'leads.delete','leads','Delete leads'),(18,'leads.convert','leads','Convert lead to customer'),(19,'activities.view','activities','View activities'),(20,'activities.create','activities','Log activities'),(21,'activities.delete','activities','Delete activities'),(22,'customers.view','customers','View customers'),(23,'customers.create','customers','Create customers'),(24,'customers.edit','customers','Edit customers'),(25,'customers.delete','customers','Delete customers'),(26,'customers.statement','customers','View customer statement'),(27,'documents.view','documents','View/download documents'),(28,'documents.upload','documents','Upload documents'),(29,'documents.delete','documents','Delete documents'),(30,'documents.share','documents','Share documents by link'),(31,'gallery.view','gallery','View gallery'),(32,'gallery.manage','gallery','Manage gallery'),(33,'projects.view','projects','View projects'),(34,'projects.create','projects','Create projects'),(35,'projects.edit','projects','Edit projects'),(36,'projects.delete','projects','Delete projects'),(37,'shares.view','shares','View share inventory & packages'),(38,'shares.create','shares','Create share packages'),(39,'shares.edit','shares','Edit share packages'),(40,'shares.delete','shares','Delete share packages'),(41,'sales.view','sales','View share sales'),(42,'sales.create','sales','Create share sales'),(43,'sales.cancel','sales','Cancel share sales'),(44,'sales.discount','sales','Give discount above company limit'),(45,'offers.view','offers','View offers'),(46,'offers.create','offers','Create offers'),(47,'offers.edit','offers','Edit offers / change status'),(48,'offers.send','offers','Send offers'),(49,'offers.delete','offers','Delete offers'),(50,'invoices.view','invoices','View invoices'),(51,'invoices.create','invoices','Create invoices'),(52,'invoices.edit','invoices','Edit draft invoices'),(53,'invoices.delete','invoices','Cancel invoices'),(54,'invoices.print','invoices','Print invoices'),(55,'payments.view','payments','View payments & receipts'),(56,'payments.create','payments','Receive payments'),(57,'payments.edit','payments','Edit payment notes/reference'),(58,'payments.delete','payments','Cancel (reverse) payments'),(59,'employees.view','employees','View employees'),(60,'employees.create','employees','Create employees'),(61,'employees.edit','employees','Edit employees'),(62,'employees.delete','employees','Delete employees'),(63,'salary.view','salary','View salary'),(64,'salary.manage','salary','Manage salary structure & components'),(65,'salary.pay','salary','Pay / cancel salary'),(66,'expenses.view','expenses','View expenses'),(67,'expenses.create','expenses','Create expenses'),(68,'expenses.edit','expenses','Edit expenses'),(69,'expenses.delete','expenses','Cancel expenses'),(70,'expenses.approve','expenses','Approve expenses'),(71,'reports.view','reports','View reports'),(72,'reports.export','reports','Export reports (CSV)'),(73,'settings.view','settings','View settings'),(74,'settings.edit','settings','Edit settings'),(75,'master_data.manage','settings','Manage master data'),(76,'audit_logs.view','system','View audit logs'),(77,'backup.manage','system','Create/download database backups'),(78,'funds.view','funds','View fund contributions'),(79,'funds.create','funds','Record fund contributions'),(80,'funds.edit','funds','Edit fund contributions'),(81,'funds.delete','funds','Cancel fund contributions');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `project_code` varchar(30) NOT NULL,
  `project_name` varchar(150) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `total_land_area` decimal(15,4) DEFAULT NULL,
  `land_unit` varchar(30) DEFAULT NULL,
  `total_shares` int(10) unsigned NOT NULL DEFAULT 0,
  `share_unit` varchar(150) DEFAULT NULL COMMENT 'e.g. 1 share = 0.5 Katha',
  `share_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `registration_fee` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'per share',
  `development_fee` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'per share',
  `status_id` int(10) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `expected_completion_date` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_projects_code` (`company_id`,`project_code`),
  KEY `idx_projects_company` (`company_id`,`deleted_at`),
  KEY `fk_projects_status` (`status_id`),
  CONSTRAINT `fk_projects_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_projects_status` FOREIGN KEY (`status_id`) REFERENCES `master_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_perm` (`permission_id`),
  CONSTRAINT `fk_rp_perm` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30),(1,31),(1,32),(1,33),(1,34),(1,35),(1,36),(1,37),(1,38),(1,39),(1,40),(1,41),(1,42),(1,43),(1,44),(1,45),(1,46),(1,47),(1,48),(1,49),(1,50),(1,51),(1,52),(1,53),(1,54),(1,55),(1,56),(1,57),(1,58),(1,59),(1,60),(1,61),(1,62),(1,63),(1,64),(1,65),(1,66),(1,67),(1,68),(1,69),(1,70),(1,71),(1,72),(1,73),(1,74),(1,75),(1,76),(1,77),(2,1),(2,2),(2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(2,13),(2,14),(2,15),(2,16),(2,17),(2,18),(2,19),(2,20),(2,21),(2,22),(2,23),(2,24),(2,25),(2,26),(2,27),(2,28),(2,29),(2,30),(2,31),(2,32),(2,33),(2,34),(2,35),(2,36),(2,37),(2,38),(2,39),(2,40),(2,41),(2,42),(2,43),(2,44),(2,45),(2,46),(2,47),(2,48),(2,49),(2,50),(2,51),(2,52),(2,53),(2,54),(2,55),(2,56),(2,57),(2,58),(2,59),(2,60),(2,61),(2,62),(2,63),(2,64),(2,65),(2,66),(2,67),(2,68),(2,69),(2,70),(2,71),(2,72),(2,73),(2,74),(2,75),(2,76),(2,78),(2,79),(2,80),(2,81),(3,1),(3,4),(3,12),(3,13),(3,14),(3,15),(3,16),(3,17),(3,18),(3,19),(3,20),(3,21),(3,22),(3,23),(3,24),(3,25),(3,26),(3,27),(3,28),(3,29),(3,30),(3,31),(3,32),(3,33),(3,34),(3,35),(3,37),(3,38),(3,39),(3,40),(3,41),(3,42),(3,43),(3,44),(3,45),(3,46),(3,47),(3,48),(3,49),(3,50),(3,51),(3,54),(3,55),(3,56),(3,59),(3,66),(3,67),(3,71),(3,72),(4,1),(4,2),(4,4),(4,8),(4,12),(4,13),(4,19),(4,22),(4,26),(4,27),(4,31),(4,33),(4,37),(4,41),(4,45),(4,50),(4,54),(4,55),(4,59),(4,63),(4,66),(4,70),(4,71),(4,72),(4,73),(4,76),(4,78),(5,1),(5,31),(5,33),(5,37),(6,1),(6,12),(6,14),(6,15),(6,18),(6,19),(6,20),(6,22),(6,23),(6,24),(6,27),(6,28),(6,30),(6,31),(6,33),(6,37),(6,41),(6,42),(6,45),(6,46),(6,47),(6,48),(6,50),(6,54),(7,1),(7,22),(7,26),(7,33),(7,37),(7,41),(7,50),(7,51),(7,52),(7,53),(7,54),(7,55),(7,56),(7,57),(7,58),(7,59),(7,63),(7,64),(7,65),(7,66),(7,67),(7,68),(7,69),(7,70),(7,71),(7,72),(7,78),(7,79),(7,80),(7,81),(8,1),(8,12),(8,19),(8,20),(8,31),(8,33);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `slug` varchar(80) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_company_slug` (`company_id`,`slug`),
  CONSTRAINT `fk_roles_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,NULL,'Super Admin','super_admin','Full system access across all companies',1,'2026-10-07 15:23:40',NULL),(2,NULL,'Admin','admin','Full access within the company',1,'2026-10-07 15:23:40',NULL),(3,NULL,'Manager','manager',NULL,1,'2026-10-07 15:23:40',NULL),(4,NULL,'Director','director',NULL,1,'2026-10-07 15:23:40',NULL),(5,NULL,'Share Holder','share_holder',NULL,1,'2026-10-07 15:23:40',NULL),(6,NULL,'Sales Executive','sales_executive',NULL,1,'2026-10-07 15:23:40',NULL),(7,NULL,'Accountant','accountant',NULL,1,'2026-10-07 15:23:40',NULL),(8,NULL,'Employee','employee',NULL,1,'2026-10-07 15:23:40',NULL),(9,NULL,'Customer','customer','Reserved for the future customer portal',1,'2026-10-07 15:23:40',NULL);
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `salary_components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_components` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('allowance','deduction','bonus','commission') NOT NULL,
  `calc_type` enum('fixed','percent') NOT NULL DEFAULT 'fixed' COMMENT 'percent = % of basic',
  `default_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sc_name` (`company_id`,`name`),
  CONSTRAINT `fk_sc_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `salary_components` WRITE;
/*!40000 ALTER TABLE `salary_components` DISABLE KEYS */;
INSERT INTO `salary_components` VALUES (1,1,'House Rent','allowance','percent',40.00,1,10,'2026-10-07 15:23:40',NULL),(2,1,'Transport','allowance','fixed',1500.00,1,20,'2026-10-07 15:23:40',NULL),(3,1,'Medical','allowance','fixed',1000.00,1,30,'2026-10-07 15:23:40',NULL),(4,1,'Mobile Bill','allowance','fixed',500.00,1,40,'2026-10-07 15:23:40',NULL),(5,1,'Festival Bonus','bonus','fixed',0.00,1,50,'2026-10-07 15:23:40',NULL),(6,1,'Sales Commission','commission','fixed',0.00,1,60,'2026-10-07 15:23:40',NULL),(7,1,'Tax','deduction','fixed',0.00,1,70,'2026-10-07 15:23:40',NULL),(8,1,'Other Deduction','deduction','fixed',0.00,1,80,'2026-10-07 15:23:40',NULL);
/*!40000 ALTER TABLE `salary_components` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `salary_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salary_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `payment_number` varchar(30) NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `salary_month` char(7) NOT NULL COMMENT 'YYYY-MM',
  `basic` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(15,2) NOT NULL DEFAULT 0.00,
  `bonus` decimal(15,2) NOT NULL DEFAULT 0.00,
  `commission` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(15,2) NOT NULL DEFAULT 0.00,
  `details` longtext DEFAULT NULL COMMENT 'JSON component breakdown',
  `payment_date` date NOT NULL,
  `payment_method_id` int(10) unsigned DEFAULT NULL,
  `reference` varchar(150) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('paid','cancelled') NOT NULL DEFAULT 'paid',
  `created_by` int(10) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_salpay_number` (`company_id`,`payment_number`),
  KEY `idx_salpay_emp` (`company_id`,`employee_id`,`salary_month`,`status`),
  KEY `idx_salpay_date` (`company_id`,`payment_date`,`status`),
  KEY `fk_salpay_emp` (`employee_id`),
  KEY `fk_salpay_method` (`payment_method_id`),
  CONSTRAINT `fk_salpay_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_salpay_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `fk_salpay_method` FOREIGN KEY (`payment_method_id`) REFERENCES `master_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `salary_payments` WRITE;
/*!40000 ALTER TABLE `salary_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `schema_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schema_migrations` (
  `migration` varchar(100) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `schema_migrations` WRITE;
/*!40000 ALTER TABLE `schema_migrations` DISABLE KEYS */;
INSERT INTO `schema_migrations` VALUES ('001_core.sql','2026-10-07 15:23:39'),('002_projects_crm.sql','2026-10-07 15:23:40'),('003_sales_finance.sql','2026-10-07 15:23:40'),('004_hr_expenses.sql','2026-10-07 15:23:40'),('005_fund_contributions.sql','2026-10-13 17:11:03');
/*!40000 ALTER TABLE `schema_migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `company_id` int(10) unsigned NOT NULL,
  `skey` varchar(100) NOT NULL,
  `svalue` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`company_id`,`skey`),
  CONSTRAINT `fk_settings_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `share_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `share_packages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `share_type_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `shares_count` int(10) unsigned NOT NULL DEFAULT 1 COMMENT 'shares per package',
  `price` decimal(15,2) NOT NULL COMMENT 'price per package',
  `description` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sp_project` (`company_id`,`project_id`,`status`),
  KEY `fk_sp_project` (`project_id`),
  KEY `fk_sp_type` (`share_type_id`),
  CONSTRAINT `fk_sp_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_sp_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_sp_type` FOREIGN KEY (`share_type_id`) REFERENCES `master_data` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `share_packages` WRITE;
/*!40000 ALTER TABLE `share_packages` DISABLE KEYS */;
/*!40000 ALTER TABLE `share_packages` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `share_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `share_sales` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `sale_number` varchar(30) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `package_id` int(10) unsigned DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL COMMENT 'packages, or shares when no package',
  `shares` int(10) unsigned NOT NULL COMMENT 'total shares',
  `unit_price` decimal(15,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `registration_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `development_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `other_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `due_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sale_date` date NOT NULL,
  `sales_person_id` int(10) unsigned DEFAULT NULL,
  `offer_id` int(10) unsigned DEFAULT NULL,
  `invoice_id` int(10) unsigned DEFAULT NULL,
  `status` enum('reserved','confirmed','cancelled') NOT NULL DEFAULT 'confirmed',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(10) unsigned DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sales_number` (`company_id`,`sale_number`),
  KEY `idx_sales_project` (`company_id`,`project_id`,`status`),
  KEY `idx_sales_customer` (`company_id`,`customer_id`),
  KEY `idx_sales_person` (`company_id`,`sales_person_id`),
  KEY `idx_sales_date` (`company_id`,`sale_date`),
  KEY `fk_sales_customer` (`customer_id`),
  KEY `fk_sales_project` (`project_id`),
  KEY `fk_sales_package` (`package_id`),
  KEY `fk_sales_person` (`sales_person_id`),
  KEY `fk_sales_offer` (`offer_id`),
  KEY `fk_sales_invoice` (`invoice_id`),
  CONSTRAINT `fk_sales_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_sales_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  CONSTRAINT `fk_sales_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`),
  CONSTRAINT `fk_sales_package` FOREIGN KEY (`package_id`) REFERENCES `share_packages` (`id`),
  CONSTRAINT `fk_sales_person` FOREIGN KEY (`sales_person_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `share_sales` WRITE;
/*!40000 ALTER TABLE `share_sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `share_sales` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `share_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `share_transactions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `project_id` int(10) unsigned NOT NULL,
  `sale_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `type` enum('reserve','sale','confirm','release','cancel','transfer_in','transfer_out','adjust') NOT NULL,
  `shares` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_st_project` (`company_id`,`project_id`,`created_at`),
  KEY `idx_st_sale` (`sale_id`),
  KEY `fk_st_project` (`project_id`),
  KEY `fk_st_customer` (`customer_id`),
  CONSTRAINT `fk_st_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`),
  CONSTRAINT `fk_st_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_st_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`),
  CONSTRAINT `fk_st_sale` FOREIGN KEY (`sale_id`) REFERENCES `share_sales` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `share_transactions` WRITE;
/*!40000 ALTER TABLE `share_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `share_transactions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_roles` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`user_id`,`role_id`),
  KEY `idx_ur_role` (`role_id`),
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `user_roles` WRITE;
/*!40000 ALTER TABLE `user_roles` DISABLE KEYS */;
INSERT INTO `user_roles` VALUES (1,1);
/*!40000 ALTER TABLE `user_roles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `avatar` varchar(100) DEFAULT NULL,
  `session_version` int(10) unsigned NOT NULL DEFAULT 1,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  UNIQUE KEY `uq_users_phone` (`phone`),
  KEY `idx_users_company` (`company_id`,`status`),
  CONSTRAINT `fk_users_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Md. Mahfuzur Rahman','mahfuzak08@gmail.com','01719455709','superadmin','$2y$12$UTlpfeDhKGz5yswWFCEBK.vVgNT78OTJqSO55qXBfmaNxD1CQH59e','active',NULL,1,NULL,NULL,'2026-10-07 15:23:40',NULL,'2026-10-07 15:23:40','2026-10-24 08:43:15',NULL);
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

