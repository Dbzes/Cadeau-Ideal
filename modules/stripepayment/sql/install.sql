CREATE TABLE IF NOT EXISTS `PREFIX_stripe_payment` (
  `id_stripe_payment` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_cart` INT UNSIGNED NOT NULL,
  `id_order` INT UNSIGNED NULL,
  `id_customer` INT UNSIGNED NULL,
  `payment_intent_id` VARCHAR(255) NOT NULL,
  `client_secret` VARCHAR(255) NULL,
  `status` VARCHAR(50) NULL,
  `amount` DECIMAL(20,6) NULL,
  `currency` VARCHAR(3) NULL,
  `method` VARCHAR(20) NULL,
  `mode` VARCHAR(10) NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`id_cart`),
  INDEX (`id_order`),
  INDEX (`payment_intent_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_stripe_refund` (
  `id_stripe_refund` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `id_order` INT UNSIGNED NOT NULL,
  `id_employee` INT UNSIGNED NULL,
  `refund_id` VARCHAR(255) NOT NULL,
  `payment_intent_id` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(20,6) NOT NULL,
  `currency` VARCHAR(3) NOT NULL,
  `reason` VARCHAR(100) NULL,
  `status` VARCHAR(50) NULL,
  `mode` VARCHAR(20) NULL,
  `details_json` TEXT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (`id_order`),
  INDEX (`refund_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4;
