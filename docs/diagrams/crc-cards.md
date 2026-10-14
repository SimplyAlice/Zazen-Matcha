# Class Responsibility Collaborator (CRC) Cards
**Project:** Zazen Matcha & Tea Co. — e-Commerce Platform  
**Course:** Eduvos ITECA3-34 Web Development and e-Commerce Technologies (Deliverable 2)  
**Document:** System Design Documentation — CRC Architecture  
**Source Baseline:** `C:\xampp\htdocs\ZAZEN-MATCHA`

---

## 1. Architectural Context & Design Methodology

### 1.1 Procedural Implementation vs. Object-Oriented Domain Modelling
The Zazen Matcha application is developed in native PHP 8.0 without a third-party framework or Content Management System (CMS), conforming strictly to the assignment brief. The runtime implementation utilizes modular, procedural include libraries (`includes/db.php`, `includes/functions.php`, `includes/customer-auth.php`, `includes/admin-auth.php`) interfacing directly with an InnoDB relational database via PDO prepared statements.

To satisfy the system-modelling requirements of Deliverable 2, the CRC cards below document:
1. **Conceptual Domain Entities:** Core business concepts and data entities that represent the business domain and map directly to the persistent relational database schema.
2. **System Service Components:** Reusable architectural subsystems and service modules implemented across the codebase that collaborate with domain models to enforce security, data persistence, and session state.

---

## 2. Conceptual Domain Entities

### Card 1: Customer
* **Classification:** Conceptual Domain Entity  
* **Persistence Mapping:** Table `customers`

| Responsibilities | Collaborators |
| :--- | :--- |
| Maintain personal customer identity (first name, last name, email, phone). | `DatabaseManager` |
| Store delivery address details (street, city, province, postal code). | `Order` |
| Store secure credential hash generated via `password_hash()`. | `AuthenticationGuard` |
| Link and trace historical purchase orders. | `Order` |
| Track account creation and profile update timestamps. | `SecurityHelper` |

---

### Card 2: Administrator
* **Classification:** Conceptual Domain Entity  
* **Persistence Mapping:** Table `admins`

| Responsibilities | Collaborators |
| :--- | :--- |
| Maintain administrator identity credentials (name, email). | `DatabaseManager` |
| Store secure administrator credential hash (Bcrypt). | `AuthenticationGuard` |
| Enforce independent administrative session privileges. | `SecurityHelper` |
| Oversee catalog items, product categories, and inventory. | `Product`, `Category` |
| Review customer accounts and order fulfillment lifecycles. | `Customer`, `Order` |

---

### Card 3: Category
* **Classification:** Conceptual Domain Entity  
* **Persistence Mapping:** Table `categories`

| Responsibilities | Collaborators |
| :--- | :--- |
| Classify and group related tea and teaware products. | `Product` |
| Enforce unique naming constraint (`uq_categories_name`). | `DatabaseManager` |
| Provide editorial taxonomy descriptions for catalog filtering. | `Product` |
| Maintain timestamps for category creation and edits. | `Administrator` |

---

### Card 4: Product
* **Classification:** Conceptual Domain Entity  
* **Persistence Mapping:** Table `products`

| Responsibilities | Collaborators |
| :--- | :--- |
| Store catalog attributes (name, description, unit price in ZAR, image path). | `Category` |
| Maintain active stock quantity (`stock_quantity`) and availability status. | `ShoppingCart` |
| Provide authoritative price and stock figures during cart calculation. | `Order`, `OrderItem` |
| Support atomic stock decrement during transactional checkout. | `DatabaseManager` |
| Restrict category deletion when products are linked (`ON DELETE RESTRICT`). | `Category` |

---

### Card 5: Order
* **Classification:** Conceptual Domain Entity  
* **Persistence Mapping:** Table `orders`

| Responsibilities | Collaborators |
| :--- | :--- |
| Record transactional order header (customer ID, order date, order total). | `Customer` |
| Preserve immutable snapshot of shipping destination at purchase time. | `OrderItem` |
| Store chosen payment method (`Simulated Card`, `Bank Transfer (EFT)`). | `ShoppingCart` |
| Maintain payment status (`pending`, `paid`, `failed`). | `DatabaseManager` |
| Maintain fulfillment status (`pending`, `processing`, `shipped`, `delivered`, `cancelled`). | `Administrator` |
| Enforce referential integrity with customer account (`ON DELETE RESTRICT`). | `Customer` |

---

### Card 6: OrderItem
* **Classification:** Conceptual Domain Entity  
* **Persistence Mapping:** Table `order_items`

| Responsibilities | Collaborators |
| :--- | :--- |
| Associate a line item with its parent purchase order (`order_id`). | `Order` |
| Link to catalog product reference (`product_id`). | `Product` |
| Store historical unit price (`unit_price`) at the exact time of order placement. | `Order` |
| Store purchased line quantity and calculate line subtotal. | `Product` |
| Prevent product deletion when sales history exists (`ON DELETE RESTRICT`). | `DatabaseManager` |

---

### Card 7: ShoppingCart
* **Classification:** Ephemeral Domain Concept  
* **Persistence Mapping:** Runtime Session (`$_SESSION['cart']`)

| Responsibilities | Collaborators |
| :--- | :--- |
| Hold active customer item selections and requested quantities in session. | `Product` |
| Add new products or increment quantities via user action. | `SecurityHelper` |
| Revalidate requested quantities against real-time MySQL inventory. | `DatabaseManager` |
| Calculate aggregate subtotal, taxes, and final order balance. | `Order` |
| Flush / clear session contents upon successful checkout completion. | `Order` |

---

### Card 8: ContactMessage
* **Classification:** Conceptual Domain Entity  
* **Persistence Mapping:** Table `contact_messages`

| Responsibilities | Collaborators |
| :--- | :--- |
| Capture customer / visitor studio inquiry details (name, email, subject, body). | `SecurityHelper` |
| Record reception timestamp for studio tracking. | `DatabaseManager` |
| Provide read-only logging of customer communication. | `Administrator` |

---

## 3. System Service Components (Codebase Helpers)

### Card 9: DatabaseManager
* **Classification:** Architectural Service Component  
* **Source Implementation:** `includes/db.php`, `includes/functions.php`

| Responsibilities | Collaborators |
| :--- | :--- |
| Establish and maintain shared UTF-8 PDO database connection instance. | All Domain Entities |
| Execute prepared statements with parameter binding to prevent SQL injection. | All Domain Entities |
| Provide helper wrappers: `db_fetch_one()` and `db_fetch_all()`. | All Domain Entities |
| Manage atomic transactions (`beginTransaction()`, `commit()`, `rollBack()`). | `Order`, `Product` |
| Enforce pessimistic row-level locking (`FOR UPDATE`) during checkout. | `Product`, `Order` |

---

### Card 10: AuthenticationGuard
* **Classification:** Architectural Service Component  
* **Source Implementation:** `includes/customer-auth.php`, `includes/admin-auth.php`

| Responsibilities | Collaborators |
| :--- | :--- |
| Enforce customer route authentication (`require_customer_login()`). | `Customer` |
| Enforce administrator route authentication (`require_admin_login()`). | `Administrator` |
| Isolate administrator sessions from customer sessions and cart state. | `ShoppingCart` |
| Handle post-login redirection targets (`$_SESSION['redirect_after_login']`). | `Customer` |
| Destroy session tokens and clear credentials upon logout. | `SecurityHelper` |

---

### Card 11: SecurityHelper
* **Classification:** Architectural Service Component  
* **Source Implementation:** `includes/functions.php`

| Responsibilities | Collaborators |
| :--- | :--- |
| Generate cryptographically secure random CSRF tokens (`csrf_token()`). | All Form Handlers |
| Validate submitted tokens with timing-safe string comparison (`hash_equals()`). | All Form Handlers |
| Sanitize HTML output to prevent Cross-Site Scripting (`e()` via `htmlspecialchars`). | View Templates |
| Hash and verify credentials via native `password_hash()` and `password_verify()`. | `Customer`, `Administrator` |
| Clean form input strings (`clean_input()`). | Form Controllers |
