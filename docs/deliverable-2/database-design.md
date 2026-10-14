# Relational Database Design & Data Dictionary
**Project:** Zazen Matcha & Tea Co. — e-Commerce Platform  
**Course:** Eduvos ITECA3-34 Web Development and e-Commerce Technologies (Deliverable 2)  
**Document:** Chapter 3.6 — Database Design & Schema Architecture  
**Database System:** MariaDB 10.4.32 / MySQL 8.0 Compatible (XAMPP for Windows)  
**Database Name:** `zazen_matcha`  
**Storage Engine:** InnoDB (ACID compliant)  
**Default Collation:** `utf8mb4_unicode_ci` (UTF-8 4-Byte Multi-Language Support)  
**Source Baseline:** `database/zazen_matcha.sql`

---

## 1. Database Architecture Overview

The `zazen_matcha` database provides the relational data foundation for the Zazen Matcha e-Commerce platform. It comprises **seven core relational tables** normalized to Third Normal Form (3NF), engineered with strict foreign key constraints, explicit data typing, and indexing optimized for high-read e-commerce workloads.

### Key Architectural Characteristics:
* **Storage Engine:** All seven tables explicitly declare `ENGINE=InnoDB` to ensure full transaction safety (`BEGIN TRANSACTION`, `COMMIT`, `ROLLBACK`), row-level locking (`SELECT ... FOR UPDATE`), and foreign key enforcement.
* **Character Set & Collation:** Defined as `utf8mb4` with collation `utf8mb4_unicode_ci`. This guarantees lossless storage of Japanese tea terminology (e.g., 宇治 *Uji*, 抹茶 *Matcha*, 茶筅 *Chasen*, 茶碗 *Chawan*), special symbols, and modern client inputs.
* **Strict Referential Integrity:** All foreign key constraints utilize `ON DELETE RESTRICT` and `ON UPDATE CASCADE`. This structural rule prevents accidental or cascading deletion of historical customer purchase records, order items, or inventory data.

---

## 2. Complete Data Dictionary

### 2.1 Table: `admins`
* **Purpose:** Stores administrative personnel credentials for access to the management console (`/admin/*`). Kept strictly separate from customer accounts for security isolation.

| Column Name | Data Type | Nullable | Default | Key / Index | Description & Business Rules |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `admin_id` | `INT UNSIGNED` | No | *Auto* | **PK** | Unique surrogate primary key identifying the administrator. |
| `name` | `VARCHAR(100)` | No | *None* | — | Full display name of the administrator (e.g., "Zazen Admin"). |
| `email` | `VARCHAR(150)` | No | *None* | **UK** (`uq_admins_email`) | Unique login email address. Validated via `FILTER_VALIDATE_EMAIL`. |
| `password` | `VARCHAR(255)` | No | *None* | — | Secure cryptographic hash generated via PHP `password_hash($plain, PASSWORD_DEFAULT)` using Bcrypt. Plaintext passwords are never stored. |
| `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | — | Timestamp recording account creation date and time. |

---

### 2.2 Table: `customers`
* **Purpose:** Stores registered and guest customer profiles, delivery addresses, contact numbers, and authentication hashes.

| Column Name | Data Type | Nullable | Default | Key / Index | Description & Business Rules |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `customer_id` | `INT UNSIGNED` | No | *Auto* | **PK** | Unique surrogate primary key identifying the customer. |
| `first_name` | `VARCHAR(100)` | No | *None* | — | Customer's first / given name. Sanitized via `clean_input()`. |
| `last_name` | `VARCHAR(100)` | No | *None* | — | Customer's surname / family name. |
| `email` | `VARCHAR(150)` | No | *None* | **UK** (`uq_customers_email`) | Unique customer email address used as the primary login identifier. |
| `phone` | `VARCHAR(30)` | Yes | `NULL` | — | Contact telephone number. Validated against regex `^[0-9+\-\s()]{7,30}$`. |
| `password` | `VARCHAR(255)` | No | *None* | — | Bcrypt hash. Guest checkout rows are seeded with a randomized, cryptographically unguessable Bcrypt hash to prevent unauthorized login until the user claims the account. |
| `address` | `VARCHAR(255)` | Yes | `NULL` | — | Street delivery address line. |
| `city` | `VARCHAR(100)` | Yes | `NULL` | — | Town or metropolitan city. |
| `province` | `VARCHAR(100)` | Yes | `NULL` | — | South African province name (validated against allowlist of 9 provinces). |
| `postal_code` | `VARCHAR(20)` | Yes | `NULL` | — | Postal / ZIP delivery code. |
| `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | — | Account creation timestamp. |
| `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | — | Automatically updated timestamp reflecting the last profile modification. |

---

### 2.3 Table: `categories`
* **Purpose:** Classifies tea products and teaware into editorial taxonomies (e.g. *Matcha*, *Accessories*, *Gift Sets*).

| Column Name | Data Type | Nullable | Default | Key / Index | Description & Business Rules |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `category_id` | `INT UNSIGNED` | No | *Auto* | **PK** | Unique surrogate primary key identifying the category. |
| `name` | `VARCHAR(100)` | No | *None* | **UK** (`uq_categories_name`) | Unique display name of the category. Enforced case-insensitively during admin CRUD. |
| `description` | `TEXT` | Yes | `NULL` | — | Editorial description summarizing the category collection. |
| `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | — | Category creation timestamp. |
| `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | — | Category modification timestamp. |

---

### 2.4 Table: `products`
* **Purpose:** Stores the tea and teaware product catalog, pricing, current inventory stock levels, and publication status.

| Column Name | Data Type | Nullable | Default | Key / Index | Description & Business Rules |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `product_id` | `INT UNSIGNED` | No | *Auto* | **PK** | Unique surrogate primary key identifying the product. |
| `category_id` | `INT UNSIGNED` | No | *None* | **FK**, **IDX** (`idx_products_category`) | Foreign key linking to `categories.category_id`. Restricted on deletion. |
| `name` | `VARCHAR(150)` | No | *None* | — | Commercial product title (e.g. "Ceremonial Matcha"). |
| `description` | `TEXT` | Yes | `NULL` | — | In-depth product tasting notes, origin details, and preparation guide. |
| `price` | `DECIMAL(10,2)` | No | *None* | — | Retail price in South African Rand (ZAR). Fixed-point decimal prevents binary floating-point roundoff errors. |
| `stock_quantity` | `INT` | No | `0` | — | Available inventory count. Decremented atomically during checkout; flagged as low stock when $\le 10$. |
| `image` | `VARCHAR(255)` | Yes | `NULL` | — | Image asset filename located in `images/products/` (e.g. `ceremonial-matcha.jpg`). Falls back to `placeholder.jpg` if empty or missing. |
| `status` | `ENUM('active','inactive')` | No | `'active'` | **IDX** (`idx_products_status`) | Publication status. Inactive items are hidden from public catalog views. |
| `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | — | Product creation timestamp. |
| `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | — | Product modification timestamp. |

---

### 2.5 Table: `orders`
* **Purpose:** Represents customer purchase orders, financial transaction totals, payment methods, and delivery destinations.

| Column Name | Data Type | Nullable | Default | Key / Index | Description & Business Rules |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `order_id` | `INT UNSIGNED` | No | *Auto* | **PK** | Unique surrogate primary key identifying the purchase order. |
| `customer_id` | `INT UNSIGNED` | No | *None* | **FK**, **IDX** (`idx_orders_customer`) | Foreign key linking to `customers.customer_id`. Restricted on deletion. |
| `order_date` | `DATETIME` | No | `CURRENT_TIMESTAMP` | **IDX** (`idx_orders_date`) | Timestamp when the checkout transaction occurred. |
| `shipping_address` | `VARCHAR(255)` | No | *None* | — | Immutable delivery address snapshot captured at time of order. |
| `city` | `VARCHAR(100)` | No | *None* | — | Destination city snapshot. |
| `province` | `VARCHAR(100)` | No | *None* | — | Destination province snapshot. |
| `postal_code` | `VARCHAR(20)` | No | *None* | — | Destination postal code snapshot. |
| `subtotal` | `DECIMAL(10,2)` | No | *None* | — | Sum of all line items (`quantity * unit_price`) in ZAR. |
| `shipping_cost` | `DECIMAL(10,2)` | No | `0.00` | — | Standard shipping fee applied to the order (R 0.00 standard). |
| `total` | `DECIMAL(10,2)` | No | *None* | — | Grand order total (`subtotal + shipping_cost`). |
| `payment_method` | `VARCHAR(50)` | No | *None* | — | Tender method used (`'Simulated Card'` or `'Bank Transfer (EFT)'`). |
| `payment_status` | `ENUM('pending','paid','failed')` | No | `'pending'` | — | Payment settlement lifecycle status. |
| `order_status` | `ENUM('pending','processing','shipped','delivered','cancelled')` | No | `'pending'` | **IDX** (`idx_orders_status`) | Physical order fulfillment lifecycle status. |
| `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | — | Record creation timestamp. |
| `updated_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | — | Status update timestamp. |

---

### 2.6 Table: `order_items`
* **Purpose:** Normalizes line item details for each purchase order, recording product links, quantities, and historical unit prices.

| Column Name | Data Type | Nullable | Default | Key / Index | Description & Business Rules |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `order_item_id` | `INT UNSIGNED` | No | *Auto* | **PK** | Unique surrogate primary key identifying the line item. |
| `order_id` | `INT UNSIGNED` | No | *None* | **FK**, **IDX** (`idx_order_items_order`) | Foreign key linking to parent `orders.order_id`. |
| `product_id` | `INT UNSIGNED` | No | *None* | **FK**, **IDX** (`idx_order_items_product`) | Foreign key linking to purchased `products.product_id`. |
| `quantity` | `INT UNSIGNED` | No | `1` | — | Number of units purchased (must be $\ge 1$). |
| `unit_price` | `DECIMAL(10,2)` | No | *None* | — | **Historical Snapshot:** The unit price of the product at the instant of checkout. Never changes even if `products.price` is modified later. |
| `subtotal` | `DECIMAL(10,2)` | No | *None* | — | Calculated line subtotal (`quantity * unit_price`). |

---

### 2.7 Table: `contact_messages`
* **Purpose:** Stores incoming feedback, tea master advice inquiries, and wholesale requests submitted via the customer contact form.

| Column Name | Data Type | Nullable | Default | Key / Index | Description & Business Rules |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `message_id` | `INT UNSIGNED` | No | *Auto* | **PK** | Unique surrogate primary key identifying the message. |
| `name` | `VARCHAR(100)` | No | *None* | — | Sender full name. Sanitized via `clean_input()`. |
| `email` | `VARCHAR(150)` | No | *None* | **IDX** (`idx_contact_messages_email`) | Sender contact email address. Validated via `FILTER_VALIDATE_EMAIL`. |
| `subject` | `VARCHAR(200)` | No | *None* | — | Inquiry subject line. |
| `message` | `TEXT` | No | *None* | — | Complete inquiry message body (up to 5,000 characters). |
| `created_at` | `TIMESTAMP` | No | `CURRENT_TIMESTAMP` | — | Timestamp recording message receipt. |

---

## 3. Referential Integrity & Foreign Key Table

| Foreign Key Name | Child Table | Child Column | Parent Table | Parent Column | On Delete | On Update | Rationale |
| :--- | :--- | :--- | :--- | :--- | :---: | :---: | :--- |
| `fk_products_category` | `products` | `category_id` | `categories` | `category_id` | `RESTRICT` | `CASCADE` | Prevents deleting a category while products are assigned to it. |
| `fk_orders_customer` | `orders` | `customer_id` | `customers` | `customer_id` | `RESTRICT` | `CASCADE` | Prevents deleting customer accounts with transactional purchase history. |
| `fk_order_items_order` | `order_items` | `order_id` | `orders` | `order_id` | `RESTRICT` | `CASCADE` | Ensures line items cannot be orphaned from their parent order record. |
| `fk_order_items_product` | `order_items` | `product_id` | `products` | `product_id` | `RESTRICT` | `CASCADE` | Protects products from deletion once purchased; preserves sales history. |

---

## 4. Key Design Decisions & Security Architecture

1. **Fixed-Point Financial Arithmetic (`DECIMAL(10,2)`):**
   * Currency values are strictly typed as `DECIMAL(10,2)` rather than `FLOAT` or `DOUBLE`. Binary floating-point types introduce IEEE 754 precision inaccuracies during mathematical operations. `DECIMAL` guarantees exact cents precision required for e-commerce accounting.

2. **Immutable Snapshots for Financial Audit Compliance:**
   * E-commerce systems must maintain immutable audit trails. If product prices fluctuate or customers change physical residences, historical tax and delivery records must reflect conditions at the time of sale.
   * `order_items.unit_price` captures the unit price at purchase.
   * `orders.shipping_address` captures the physical street address at purchase.

3. **Concurrency Control via Pessimistic Row Locking:**
   * In [`customer/checkout.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/checkout.php), the database transaction invokes `SELECT product_id, stock_quantity, price FROM products WHERE product_id IN (...) FOR UPDATE`.
   * This locks the selected inventory rows against concurrent modifications. The stock decrement query subsequently checks `WHERE stock_quantity >= :qty` and validates `rowCount() === 1`, completely eliminating inventory overselling race conditions.

4. **Cryptographic Protection of Credentials:**
   * Passwords in `admins` and `customers` are hashed using standard PHP `password_hash()` with the Bcrypt algorithm (`PASSWORD_DEFAULT`), applying an adaptive work factor (cost 10).
   * Password hashes are excluded from general `SELECT` queries (e.g. `admin/customers.php`) to minimize exposure risks.
