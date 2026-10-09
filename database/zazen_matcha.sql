-- ============================================================================
-- Zazen Matcha & Tea Co. - Database Schema & Seed Data
-- Database Name: zazen_matcha
-- Target Environment: XAMPP (MySQL / MariaDB, InnoDB, utf8mb4)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `zazen_matcha`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `zazen_matcha`;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `contact_messages`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------------------------------------------------------
-- 1. Table: admins
-- ----------------------------------------------------------------------------
CREATE TABLE `admins` (
    `admin_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hash generated via PHP password_hash()',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`admin_id`),
    UNIQUE KEY `uq_admins_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: customers
-- ----------------------------------------------------------------------------
CREATE TABLE `customers` (
    `customer_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hash generated via PHP password_hash()',
    `address` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `province` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`customer_id`),
    UNIQUE KEY `uq_customers_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table: categories
-- ----------------------------------------------------------------------------
CREATE TABLE `categories` (
    `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `uq_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Table: products
-- Relationship: categories (1) -> (many) products
-- ----------------------------------------------------------------------------
CREATE TABLE `products` (
    `product_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `stock_quantity` INT NOT NULL DEFAULT 0,
    `image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`product_id`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_status` (`status`),
    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`category_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Table: orders
-- Relationship: customers (1) -> (many) orders
-- ----------------------------------------------------------------------------
CREATE TABLE `orders` (
    `order_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `customer_id` INT UNSIGNED NOT NULL,
    `order_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `shipping_address` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `province` VARCHAR(100) NOT NULL,
    `postal_code` VARCHAR(20) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    `shipping_cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `total` DECIMAL(10, 2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `payment_status` ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
    `order_status` ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`order_id`),
    KEY `idx_orders_customer` (`customer_id`),
    KEY `idx_orders_status` (`order_status`),
    KEY `idx_orders_date` (`order_date`),
    CONSTRAINT `fk_orders_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `customers` (`customer_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Table: order_items
-- Relationships:
--   orders (1)   -> (many) order_items
--   products (1) -> (many) order_items
-- Note: unit_price stores the historical price at the time of purchase.
-- ----------------------------------------------------------------------------
CREATE TABLE `order_items` (
    `order_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (`order_item_id`),
    KEY `idx_order_items_order` (`order_id`),
    KEY `idx_order_items_product` (`product_id`),
    CONSTRAINT `fk_order_items_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,
    CONSTRAINT `fk_order_items_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `products` (`product_id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 7. Table: contact_messages
-- ----------------------------------------------------------------------------
CREATE TABLE `contact_messages` (
    `message_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`message_id`),
    KEY `idx_contact_messages_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SAMPLE SEED DATA (Prototype / Fictional Data for Testing)
-- ============================================================================

-- 1. Sample Admin Account
-- Email: admin@zazenmatcha.test | Password: Admin123!
INSERT INTO `admins` (`admin_id`, `name`, `email`, `password`) VALUES
(1, 'Zazen Admin', 'admin@zazenmatcha.test', '$2y$10$/6oPiEkPtQl4TIhZgGBhHeMQIej6srwCa66WqVY/Gb0HOGRYbFRMK');

-- 2. Sample Customer Account
-- Email: thabo@example.test | Password: Customer123!
INSERT INTO `customers` (
    `customer_id`, `first_name`, `last_name`, `email`, `phone`, `password`,
    `address`, `city`, `province`, `postal_code`
) VALUES (
    1, 'Thabo', 'Mokoena', 'thabo@example.test', '0821234567',
    '$2y$10$6ImimmkSXusvIG.20JoxPODZ4ueMYHJTIBr9THipRXvx.tSnIA78u',
    '42 Botanical Avenue', 'Cape Town', 'Western Cape', '8001'
);

-- 3. Sample Categories
INSERT INTO `categories` (`category_id`, `name`, `description`) VALUES
(1, 'Matcha', 'Fictional prototype selection of finely milled green tea powders for hot and iced preparation.'),
(2, 'Accessories', 'Fictional prototype tea preparation tools including bowls, whisks, and scoops.'),
(3, 'Gift Sets', 'Fictional prototype curated bundles combining matcha powders and preparation accessories.');

-- 4. Sample Products (Fictional prototype items and prices)
INSERT INTO `products` (
    `product_id`, `category_id`, `name`, `description`, `price`, `stock_quantity`, `image`, `status`
) VALUES
(1, 1, 'Ceremonial Matcha', 'Fictional prototype fine green tea powder intended for traditional whisked tea preparation (30g tin).', 280.00, 25, 'ceremonial-matcha.jpg', 'active'),
(2, 1, 'Everyday Matcha', 'Fictional prototype green tea powder blended for everyday lattes, smoothies, and iced drinks (50g pouch).', 195.00, 40, 'everyday-matcha.jpg', 'active'),
(3, 3, 'Matcha Starter Kit', 'Fictional prototype starter bundle containing one tin of matcha powder, a whisk, and a scoop.', 520.00, 15, 'matcha-starter-kit.jpg', 'active'),
(4, 2, 'Matcha Whisk', 'Fictional prototype multi-prong bamboo-style whisk designed for frothing matcha in a tea bowl.', 160.00, 30, 'matcha-whisk.jpg', 'active'),
(5, 2, 'Matcha Bowl', 'Fictional prototype wide-base ceramic bowl shaped for comfortable whisking and pouring.', 240.00, 20, 'matcha-bowl.jpg', 'active'),
(6, 3, 'Matcha Gift Set', 'Fictional prototype presentation box including ceremonial matcha, a ceramic bowl, whisk, and scoop.', 690.00, 10, 'matcha-gift-set.jpg', 'active');

-- 5. Sample Order (Demonstrates customers -> orders relationship)
INSERT INTO `orders` (
    `order_id`, `customer_id`, `order_date`, `shipping_address`, `city`, `province`, `postal_code`,
    `subtotal`, `shipping_cost`, `total`, `payment_method`, `payment_status`, `order_status`
) VALUES (
    1, 1, '2026-10-08 10:30:00', '42 Botanical Avenue', 'Cape Town', 'Western Cape', '8001',
    440.00, 60.00, 500.00, 'Simulated Card', 'paid', 'processing'
);

-- 6. Sample Order Items (Demonstrates orders -> order_items and products -> order_items)
INSERT INTO `order_items` (
    `order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`
) VALUES
(1, 1, 1, 1, 280.00, 280.00),
(2, 1, 4, 1, 160.00, 160.00);

-- 7. Sample Contact Message
INSERT INTO `contact_messages` (`message_id`, `name`, `email`, `subject`, `message`) VALUES
(1, 'Lerato Ndlovu', 'lerato@example.test', 'Prototype Product Question', 'Hello, this is a sample contact message used to test the contact_messages table.');
