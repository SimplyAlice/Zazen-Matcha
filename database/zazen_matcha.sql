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

DROP TABLE IF EXISTS `newsletter_subscribers`;
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

-- ----------------------------------------------------------------------------
-- 8. Table: newsletter_subscribers
-- ----------------------------------------------------------------------------
CREATE TABLE `newsletter_subscribers` (
    `subscriber_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(150) NOT NULL,
    `status` ENUM('subscribed', 'unsubscribed') NOT NULL DEFAULT 'subscribed',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`subscriber_id`),
    UNIQUE KEY `uq_newsletter_email` (`email`),
    KEY `idx_newsletter_status` (`status`)
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
(1, 'Matcha', 'Curated selection of single-origin stone-ground Uji green tea powders for traditional whisking and iced lattes.'),
(2, 'Accessories', 'Artisan Japanese tea preparation ware including handcrafted ceramic chawan bowls, bamboo whisks, and carved scoops.'),
(3, 'Gift Sets', 'Thoughtfully curated ceremonial gift boxes combining first-harvest matcha powders and tactile preparation ware.'),
(4, 'Specialty Tea', 'Single-origin loose-leaf green, white, oolong, pu-erh, and botanical herbal infusions.'),
(5, 'Prepared Drinks', 'Freshly prepared ceremonial matcha drinks, layered artisan lattes, slow cold brews, and botanical infusions crafted in our Cape Town studio tea bar.'),
(6, 'Living Botanicals', 'Hand-nurtured miniature bonsai trees, flowering tea garden specimens, and tabletop greenery inspired by Japanese wabi-sabi aesthetics.');

-- 4. Sample Products
INSERT INTO `products` (
    `product_id`, `category_id`, `name`, `description`, `price`, `stock_quantity`, `image`, `status`
) VALUES
(1, 1, 'Ceremonial Matcha', 'Single-origin Uji ceremonial fine green tea powder for traditional whisked usucha and koicha preparation (30g tin).', 280.00, 25, 'ceremonial-matcha.jpg', 'active'),
(2, 1, 'Everyday Matcha', 'Balanced stone-ground Uji green tea powder crafted for daily morning lattes, iced matcha, and smoothies (50g pouch).', 195.00, 40, 'everyday-matcha.jpg', 'active'),
(3, 3, 'Matcha Starter Kit', 'Curated ceremonial starter bundle containing one tin of stone-ground matcha powder, a handcrafted bamboo chasen, and a bamboo scoop.', 520.00, 15, 'matcha-starter-kit.jpg', 'active'),
(4, 2, 'Matcha Whisk', 'Traditional multi-prong bamboo chasen whisk handcrafted for aerating matcha into a rich, velvety microfoam froth.', 160.00, 30, 'matcha-whisk.jpg', 'active'),
(5, 2, 'Matcha Bowl', 'Handcrafted wide-base cream ceramic chawan bowl with organic wabi-sabi contours, shaped for tactile whisking and cradle comfort.', 240.00, 20, 'matcha-bowl.jpg', 'active'),
(6, 3, 'Matcha Gift Set', 'Luxury presentation gift box including first-harvest ceremonial matcha, a handcrafted ceramic chawan bowl, bamboo whisk, and carved scoop.', 690.00, 10, 'matcha-gift-set.jpg', 'active'),
(7, 4, 'Saffron Golden Infusion', 'Herbal infusion pairing vibrant saffron threads with whole chamomile blossoms, sweet fennel seeds, and sun-dried citrus peel. Naturally caffeine-free with a luminous golden-amber liquor.', 245.00, 30, 'saffron-golden-infusion.jpg', 'active'),
(8, 4, 'Midnight Bloom', 'Botanical infusion blending sun-dried whole butterfly pea flowers, organic lemongrass cuts, and wild spearmint leaves. Infuses an intense cobalt-blue liquor that naturally turns violet with citrus.', 190.00, 25, 'midnight-bloom.jpg', 'active'),
(9, 4, 'Golden Osmanthus Oolong', 'Semi-oxidized high-mountain oolong tea naturally scented with sweet golden autumn osmanthus blossom petals. Delivers honeyed stonefruit and delicate orchid notes across multiple steepings.', 260.00, 20, 'golden-osmanthus-oolong.jpg', 'active'),
(10, 4, 'White Peony', 'Spring-harvested white tea (Bai Mu Dan) comprising silvery down-covered terminal buds and tender young leaves. Gentle sun-withering yields a pale champagne liquor with notes of fresh melon and wild florals.', 210.00, 20, 'white-peony.jpg', 'active'),
(11, 4, 'Earth & Ember Pu-erh', 'Cellar-aged Shou (ripe) pu-erh tea from Yunnan. Naturally fermented to produce a velvety, dark-mahogany liquor with rich undertones of damp forest cedar, roasted cacao, and petrichor.', 275.00, 15, 'earth-ember-pu-erh.jpg', 'active'),
(12, 4, 'Jasmine Pearls', 'Hand-rolled tender green tea shoots scented with night-blooming jasmine flowers. Each silver-tipped pearl slowly unfurls in warm water, releasing an intoxicating floral aroma and fresh sweetness.', 230.00, 35, 'jasmine-pearls.jpg', 'active'),
(13, 5, 'Traditional Whisked Usucha', 'Single-origin ceremonial Uji matcha whisked with 80°C spring water into a thick, velvety emerald microfoam. Served in a warm handcrafted chawan bowl.', 50.00, 50, 'traditional-whisked-usucha.jpg', 'active'),
(14, 5, 'Classic Iced Matcha Latte', 'First-harvest ceremonial matcha hand-whisked and poured over cold organic oat milk and crystal clear ice. Balanced, creamy, and grounding.', 60.00, 50, 'classic-iced-matcha-latte.jpg', 'active'),
(15, 5, 'Strawberry Matcha Latte', 'House-simmered organic strawberry compote layered with silky cold oat milk and stone-ground ceremonial matcha foam.', 68.00, 50, 'strawberry-matcha-latte.jpg', 'active'),
(16, 5, 'Vanilla Bean Matcha Latte', 'Whole Madagascar vanilla bean infusion paired with gently steamed velvety microfoam milk and vibrant Japanese green tea.', 65.00, 50, 'vanilla-bean-matcha-latte.jpg', 'active'),
(17, 5, 'Iced Coconut Matcha', 'Ceremonial matcha floated delicately over pure coconut water, coconut blossom nectar, and a float of coconut cream over ice.', 68.00, 50, 'iced-coconut-matcha.jpg', 'active'),
(18, 5, 'Roasted Hojicha Latte', 'Slow charcoal-roasted Japanese green tea micro-powder steamed with silky milk. Rich nutty notes of roasted grain, cacao, and caramel.', 58.00, 50, 'roasted-hojicha-latte.jpg', 'active'),
(19, 5, 'Iced Jasmine Pearl Tea', 'Hand-rolled silver-tipped green tea pearls cold-steeped for 12 hours with fresh night-blooming jasmine flowers. Floral, delicate, and refreshing.', 52.00, 50, 'iced-jasmine-pearl-tea.jpg', 'active'),
(20, 5, 'White Peach Iced Oolong', 'High-mountain Formosa oolong cold-infused with white peach nectar and garden mint sprigs. Natural stonefruit sweetness with orchid aromatics.', 58.00, 50, 'white-peach-iced-oolong.jpg', 'active'),
(21, 5, 'Butterfly Pea Flower Lemonade', 'Deep cobalt-blue butterfly pea flower infusion paired with freshly squeezed lemon juice and sparkling water. Naturally shifts to royal violet.', 56.00, 50, 'butterfly-pea-lemonade.jpg', 'active'),
(22, 5, 'Golden Saffron Herbal Infusion', 'Pure saffron threads gently brewed with chamomile blossoms, citrus peel, and Cape honey. Luminous amber liquor served warm or chilled.', 62.00, 50, 'golden-saffron-herbal-infusion.jpg', 'active'),
(23, 2, 'Kuro-Oribe Ceramic Chawan', 'Handcrafted Japanese-style chawan matcha bowl in traditional Kuro-Oribe dark iron glaze with subtle forest-green pooling, organic wabi-sabi rim, and an earthy textured foot ring. Ideal for whisking ceremonial matcha.', 380.00, 20, 'kuro-oribe-chawan.jpg', 'active'),
(24, 2, 'Shino Glaze Tenmoku Matcha Bowl', 'Minimalist tea bowl featuring a creamy feldspathic Shino crackle glaze over iron-rich stoneware with warm burnt-orange accents and comfortable hand-cupping wabi-sabi indentations.', 340.00, 25, 'shino-glaze-bowl.jpg', 'active'),
(25, 2, 'Yunomi Hand-Thrown Ceramic Cups (Pair)', 'Pair of small, tactile ceramic sipping cups with fluted finger ridges and natural wood-ash glaze in subtle sage and stone-grey tones, sized for mindful green tea drinking.', 290.00, 30, 'yunomi-ceramic-cups.jpg', 'active'),
(26, 2, 'Hagi-Yaki Wabi Tea Bowl', 'Traditional Japanese Hagi-yaki stoneware chawan in soft pale blush-ivory glaze with fine spiderweb crazing (kan-nyu) and a classic wari-kodai notched foot, developing a natural tea patina with use.', 420.00, 15, 'hagi-yaki-tea-bowl.jpg', 'active'),
(27, 2, 'Carved Cedar & Ceramic Drip Tray', 'Minimalist dry tea tray combining water-resistant charred Japanese cedar wood (yakisugi) with a removable inset matte cream ceramic drainage plate for tranquil gongfu tea service.', 360.00, 18, 'cedar-ceramic-tea-tray.jpg', 'active'),
(28, 3, 'Wabi Ceramic Tasting Gift Set', 'Thoughtfully curated ceramic ensemble comprising an artisan-glazed pouring pitcher (katakuchi), two matching yunomi cups, and an unbleached linen wrap in a recyclable presentation box.', 580.00, 12, 'wabi-ceramic-tasting-set.jpg', 'active'),
(29, 6, 'Miniature Flowering Sakura Cherry Blossom (Living Shrub)', 'Compact living dwarf flowering shrub specimen trained in classical bonsai style with delicate pale pink seasonal blossoms in an unglazed ceramic pot. (Living plant: flowering occurs seasonally based on daylight and temperature; care card included).', 480.00, 10, 'sakura-bonsai-tree.jpg', 'active'),
(30, 6, 'Japanese Juniper Bonsai (Living Evergreen)', 'Living evergreen Japanese dwarf juniper (Juniperus procumbens nana) with sculpted curving trunk and dense emerald needle pads in a shallow stoneware pot with velvet moss dressing. (Living plant: requires bright filtered light and moderate moisture).', 450.00, 14, 'juniper-bonsai-plant.jpg', 'active'),
(31, 6, 'Kokedama Moss Ball Tabletop Fern (Living Botanical)', 'Traditional Japanese moss ball botanical specimen cradling a living ornamental micro-fern, bound in natural peat moss and twine, resting on a glazed dark ceramic saucer. Designed for tabletop tea spaces and desk corners.', 220.00, 22, 'kokedama-moss-ball-fern.jpg', 'active'),
(32, 6, 'Tea Corner Flowering Azalea Bonsai (Living Plant)', 'Compact living Satsuki azalea specimen exhibiting bright pink ruffled seasonal blooms against deep-green glossy foliage in an earthenware bonsai basin. (Living plant: seasonal bloom depends on ambient light and seasonal cycles).', 520.00, 8, 'azalea-bonsai-plant.jpg', 'active'),
(33, 2, 'Celadon Ceramic Whisk Holder (Chasen-Naoshi)', 'Glazed ceramic keeper designed to support the curved tulip silhouette of bamboo chasen tines after rinsing, promoting even air circulation and prolonging whisk lifespan.', 140.00, 35, 'celadon-whisk-holder.jpg', 'active'),
(34, 2, 'Pure Organic Linen Tea Cloth (Chakin)', 'Unbleached, quick-drying 100% natural organic flax linen ritual wiping cloth for warming and drying chawan bowls without synthetic lint or chemical finishes.', 95.00, 40, 'organic-linen-tea-cloth.jpg', 'active'),
(35, 2, 'Hand-Carved Golden Bamboo Tea Scoop (Chashaku)', 'Slender natural golden bamboo scoop gently steam-curved at the tip to measure precisely one gram of matcha powder, crafted without varnishes or synthetic coatings.', 85.00, 40, 'bamboo-tea-scoop-chashaku.jpg', 'active');

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

-- 8. Sample Newsletter Subscriber
INSERT INTO `newsletter_subscribers` (`subscriber_id`, `email`, `status`, `ip_address`) VALUES
(1, 'thabo@example.test', 'subscribed', '127.0.0.1');
