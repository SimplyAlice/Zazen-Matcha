# MySQL Database Table Screenshots Guide & Checklist
**Project:** Zazen Matcha & Tea Co. — e-Commerce Platform  
**Course:** Eduvos ITECA3-34 Web Development and e-Commerce Technologies (Deliverable 2)  
**Deliverable Component:** MySQL Table Screenshots (2 Marks)  
**Target Directory for Captured Images:** `docs/deliverable-2/screenshots/database/`  
**Database Name:** `zazen_matcha`  
**Host Tool:** phpMyAdmin (`http://localhost/phpmyadmin/`) or MySQL Workbench

---

## 1. Rubric Requirement & Ethical Guidelines

The Eduvos Deliverable 2 rubric allocates **2 marks** for visual evidence demonstrating the live relational database tables.

### Privacy & Academic Ethics Rules:
* **Capture Table Structure Views, Not Personal Data:** The rubric requires evidence that the tables, primary keys, data types, and relationships exist. To uphold POPIA (Protection of Personal Information Act) compliance and academic assessment integrity, screenshots should primarily capture the **Structure tab (`tbl_structure.php`)** in phpMyAdmin rather than browsing live customer email addresses, physical addresses, or password hashes.
* **No Fabricated Screenshots:** In strict accordance with the assessment instructions, placeholder or fake screenshots must never be fabricated. The checklist below defines the exact seven real captures to take directly from the local MariaDB/phpMyAdmin environment.

---

## 2. Seven-Table Capture Checklist

To obtain the allocated 2 marks, capture each of the seven tables in phpMyAdmin under the `zazen_matcha` database and save the image files with the standardized filenames listed below:

| # | Table Name | Recommended phpMyAdmin View | Target Filename | Required Visible Elements |
| :---: | :--- | :--- | :--- | :--- |
| **1** | `admins` | Table **Structure** View | `01-table-admins-structure.png` | Columns (`admin_id`, `name`, `email`, `password`, `created_at`), Types (`INT`, `VARCHAR`, `TIMESTAMP`), Primary Key icon on `admin_id`, Unique Key icon on `email`. |
| **2** | `customers` | Table **Structure** View | `02-table-customers-structure.png` | Columns (`customer_id`, `first_name`, `last_name`, `email`, `phone`, `password`, `address`, `city`, `province`, `postal_code`, timestamps), Primary Key on `customer_id`, Unique Key on `email`. |
| **3** | `categories` | Table **Structure** View | `03-table-categories-structure.png` | Columns (`category_id`, `name`, `description`, timestamps), Primary Key on `category_id`, Unique Key on `name`. |
| **4** | `products` | Table **Structure** View (with Relation View) | `04-table-products-structure.png` | Columns (`product_id`, `category_id`, `name`, `price`, `stock_quantity`, `image`, `status`), Primary Key on `product_id`, Index on `category_id`, Foreign Key indicator linking to `categories`. |
| **5** | `orders` | Table **Structure** View (with Relation View) | `05-table-orders-structure.png` | Columns (`order_id`, `customer_id`, `order_date`, `shipping_address`, `subtotal`, `total`, `payment_method`, `payment_status`, `order_status`), Primary Key on `order_id`, FK linking to `customers`. |
| **6** | `order_items` | Table **Structure** View (with Relation View) | `06-table-order-items-structure.png` | Columns (`order_item_id`, `order_id`, `product_id`, `quantity`, `unit_price`, `subtotal`), Primary Key on `order_item_id`, FKs linking to `orders` and `products`. |
| **7** | `contact_messages` | Table **Structure** View | `07-table-contact-messages-structure.png` | Columns (`message_id`, `name`, `email`, `subject`, `message`, `created_at`), Primary Key on `message_id`, Index on `email`. |

---

## 3. Recommended Step-by-Step Capture Procedure

1. **Ensure Local Services are Active:**
   * Apache and MySQL/MariaDB must be running in XAMPP Control Panel.
2. **Open phpMyAdmin:**
   * Navigate to `http://localhost/phpmyadmin/` in Google Chrome or Microsoft Edge.
3. **Select Database:**
   * In the left sidebar, click `zazen_matcha`.
4. **Capture Each Table:**
   * Click the table name (e.g. `products`).
   * Select the **Structure** tab at the top.
   * Use Windows Snipping Tool (`Win + Shift + S`) to capture the table column definition box clearly.
   * Save the image in `docs/deliverable-2/screenshots/database/` using the exact filename specified above (e.g. `04-table-products-structure.png`).
5. **Capture Database Designer / Relation View (Bonus Evidence):**
   * While in `zazen_matcha`, click **More $\to$ Designer** in phpMyAdmin to show the visual relationship canvas displaying all foreign key connecting lines.
   * Save this capture as `08-database-designer-relations.png`.

---

## 4. Current Screenshot Status

* **Status:** **PENDING MANUAL CAPTURE VIA PHPMYADMIN**
* **Verification Note:** Because automated background processes in the local environment cannot interactively drive the Windows display subsystem, automated screenshots cannot be generated without user interaction.
* **Action Required:** Capture the 7 views listed above and place them into `docs/deliverable-2/screenshots/database/` prior to compiling the final Deliverable 2 submission PDF.
