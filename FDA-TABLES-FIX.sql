-- ZASK Age-Gate FDA Tables Fix
-- Run this in phpMyAdmin or via wp-cli to fix FDA tables
-- Replace 'wp_' with your actual table prefix if different

-- Drop old FDA alerts table if it exists with wrong structure
DROP TABLE IF EXISTS `wp_zask_fda_alerts`;

-- Create FDA alerts table with correct structure
CREATE TABLE IF NOT EXISTS `wp_zask_fda_alerts` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `warning_details` longtext NOT NULL,
  `fda_url` varchar(500) DEFAULT NULL,
  `detected_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` varchar(20) DEFAULT 'active',
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create FDA products table
CREATE TABLE IF NOT EXISTS `wp_zask_fda_products` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `last_scanned_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_name` (`product_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify tables were created
SELECT 'FDA Tables Created Successfully!' as status;
SELECT COUNT(*) as fda_products_count FROM `wp_zask_fda_products`;
SELECT COUNT(*) as fda_alerts_count FROM `wp_zask_fda_alerts`;
