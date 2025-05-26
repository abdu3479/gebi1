CREATE TABLE `tax_rate` (
  `rate_id` int(11) NOT NULL AUTO_INCREMENT,
  `rate_name` varchar(100) NOT NULL COMMENT 'Human-readable name of the tax rate',
  `rate_code` varchar(20) NOT NULL COMMENT 'Unique code for system reference (e.g., VAT-15)',
  `rate_description` text DEFAULT NULL COMMENT 'Detailed description of the tax',
  `rate_percentage` decimal(5,2) DEFAULT NULL COMMENT 'Percentage rate (NULL if fixed amount)',
  `fixed_amount` decimal(10,2) DEFAULT NULL COMMENT 'Fixed tax amount (NULL if percentage)',
  `minimum_amount` decimal(10,2) DEFAULT NULL COMMENT 'Minimum tax amount required',
  `maximum_amount` decimal(10,2) DEFAULT NULL COMMENT 'Maximum tax amount cap',
  `applicable_to` enum('property','building','rental','vehicle','business','other') NOT NULL COMMENT 'What entity this tax applies to',
  `category` enum('A','B','C','D') DEFAULT NULL COMMENT 'Taxpayer category for differentiated rates',
  `calculation_method` enum('percentage','fixed','tiered') NOT NULL DEFAULT 'percentage' COMMENT 'How tax is calculated',
  `effective_date` date NOT NULL COMMENT 'When this rate becomes active',
  `expiry_date` date DEFAULT NULL COMMENT 'When this rate expires (NULL for ongoing)',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=active, 0=inactive',
  `requires_approval` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'If application requires approval',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `created_by` int(11) DEFAULT NULL COMMENT 'User who created this rate',
  `updated_by` int(11) DEFAULT NULL COMMENT 'User who last updated this rate',
  PRIMARY KEY (`rate_id`),
  UNIQUE KEY `rate_code_unique` (`rate_code`),
  UNIQUE KEY `idx_unique_active_rate` (`applicable_to`, `category`, `effective_date`, `is_active`) 
    COMMENT 'Prevent duplicate active rates for same category',
  KEY `idx_applicable_to` (`applicable_to`),
  KEY `idx_effective_date` (`effective_date`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `fk_tax_rate_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_tax_rate_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `chk_rate_values` CHECK (
    (`rate_percentage` IS NOT NULL AND `fixed_amount` IS NULL) OR
    (`rate_percentage` IS NULL AND `fixed_amount` IS NOT NULL)
  )
  CONSTRAINT `chk_date_range` CHECK (`expiry_date` IS NULL OR `expiry_date` > `effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Master table for all tax rates in the system';