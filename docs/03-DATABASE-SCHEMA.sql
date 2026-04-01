-- SVdP Vouchers Furniture Expansion - Proposed Schema
-- Notes:
-- 1. Adapt charset/collation to existing plugin conventions.
-- 2. Keep current wp_svdp_vouchers table; alter rather than replace.
-- 3. dbDelta compatibility matters if implemented through WordPress.

-- -----------------------------------------------------
-- ALTER existing root vouchers table
-- -----------------------------------------------------
ALTER TABLE `wp_svdp_vouchers`
  ADD COLUMN `voucher_type` varchar(32) NOT NULL DEFAULT 'clothing' AFTER `created_by`,
  ADD COLUMN `workflow_status` varchar(32) NOT NULL DEFAULT 'submitted' AFTER `status`;

CREATE INDEX `idx_svdp_vouchers_type` ON `wp_svdp_vouchers` (`voucher_type`);
CREATE INDEX `idx_svdp_vouchers_workflow_status` ON `wp_svdp_vouchers` (`workflow_status`);

-- -----------------------------------------------------
-- Catalog items
-- -----------------------------------------------------
CREATE TABLE `wp_svdp_catalog_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `pricing_type` varchar(20) NOT NULL,
  `price_min` decimal(10,2) DEFAULT NULL,
  `price_max` decimal(10,2) DEFAULT NULL,
  `price_fixed` decimal(10,2) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `allow_substitution` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_svdp_catalog_category` (`category`),
  KEY `idx_svdp_catalog_active` (`active`),
  KEY `idx_svdp_catalog_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Furniture voucher meta
-- -----------------------------------------------------
CREATE TABLE `wp_svdp_furniture_voucher_meta` (
  `voucher_id` bigint(20) NOT NULL,
  `delivery_required` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_address_line_1` varchar(255) DEFAULT NULL,
  `delivery_address_line_2` varchar(255) DEFAULT NULL,
  `delivery_city` varchar(100) DEFAULT NULL,
  `delivery_state` varchar(50) DEFAULT NULL,
  `delivery_zip` varchar(20) DEFAULT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estimated_total_min` decimal(10,2) DEFAULT NULL,
  `estimated_total_max` decimal(10,2) DEFAULT NULL,
  `estimated_requestor_portion_min` decimal(10,2) DEFAULT NULL,
  `estimated_requestor_portion_max` decimal(10,2) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by_user_id` bigint(20) DEFAULT NULL,
  `receipt_file_path` varchar(500) DEFAULT NULL,
  `invoice_file_path` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`voucher_id`),
  KEY `idx_svdp_furniture_completed_at` (`completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Voucher items
-- -----------------------------------------------------
CREATE TABLE `wp_svdp_voucher_items` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint(20) NOT NULL,
  `catalog_item_id` bigint(20) DEFAULT NULL,
  `requested_item_name_snapshot` varchar(255) NOT NULL,
  `requested_category_snapshot` varchar(50) NOT NULL,
  `requested_pricing_type_snapshot` varchar(20) NOT NULL,
  `requested_price_min_snapshot` decimal(10,2) DEFAULT NULL,
  `requested_price_max_snapshot` decimal(10,2) DEFAULT NULL,
  `requested_price_fixed_snapshot` decimal(10,2) DEFAULT NULL,
  `requested_sort_order_snapshot` int(11) NOT NULL DEFAULT 0,
  `substitution_type` varchar(20) NOT NULL DEFAULT 'none',
  `substitute_catalog_item_id` bigint(20) DEFAULT NULL,
  `substitute_item_name` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'requested',
  `actual_price` decimal(10,2) DEFAULT NULL,
  `completion_notes` text DEFAULT NULL,
  `cancellation_reason_id` bigint(20) DEFAULT NULL,
  `cancellation_notes` text DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by_user_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_svdp_voucher_items_voucher` (`voucher_id`),
  KEY `idx_svdp_voucher_items_status` (`status`),
  KEY `idx_svdp_voucher_items_sort` (`requested_sort_order_snapshot`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Voucher item photos
-- -----------------------------------------------------
CREATE TABLE `wp_svdp_voucher_item_photos` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `voucher_item_id` bigint(20) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `image_width` int(11) DEFAULT NULL,
  `image_height` int(11) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `uploaded_by_user_id` bigint(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_svdp_item_photos_item` (`voucher_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Furniture cancellation reasons
-- -----------------------------------------------------
CREATE TABLE `wp_svdp_furniture_cancellation_reasons` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `reason_text` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_svdp_cancel_reasons_active` (`active`),
  KEY `idx_svdp_cancel_reasons_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Invoices
-- -----------------------------------------------------
CREATE TABLE `wp_svdp_invoices` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `voucher_id` bigint(20) NOT NULL,
  `conference_id` bigint(20) NOT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `delivery_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `items_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `conference_share_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `statement_id` bigint(20) DEFAULT NULL,
  `stored_file_path` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_svdp_invoice_number` (`invoice_number`),
  KEY `idx_svdp_invoice_voucher` (`voucher_id`),
  KEY `idx_svdp_invoice_conference` (`conference_id`),
  KEY `idx_svdp_invoice_statement` (`statement_id`),
  KEY `idx_svdp_invoice_date` (`invoice_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- Invoice statements
-- -----------------------------------------------------
CREATE TABLE `wp_svdp_invoice_statements` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `statement_number` varchar(100) NOT NULL,
  `conference_id` bigint(20) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `generated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by_user_id` bigint(20) DEFAULT NULL,
  `stored_file_path` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_svdp_statement_number` (`statement_number`),
  KEY `idx_svdp_statement_conference` (`conference_id`),
  KEY `idx_svdp_statement_period` (`period_start`, `period_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
