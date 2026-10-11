# Zazen Matcha & Tea Co. — e-Commerce Platform
**Eduvos ITECA3-34 Web Development and e-Commerce Technologies — Deliverable 2 Submission**

---

## 1. Project Overview & Architecture

**Zazen Matcha & Tea Co.** is a full-stack, bespoke e-commerce platform designed for premium single-origin Japanese ceremonial matcha, artisan loose-leaf specialty teas, handcrafted stoneware teaware, and curated starter kits. The platform blends an unhurried, minimalist Japanese aesthetic with robust web engineering and transactional security.

In strict adherence to assignment guidelines:
* **100% CMS Prohibition Compliant:** Built entirely from scratch using native **HTML5, CSS3, vanilla JavaScript (ES6+), PHP 8.0+**, and a normalized **MariaDB 10.4 / MySQL (InnoDB)** relational database. Zero CMS platforms (such as WordPress, WooCommerce, or Shopify) and zero monolithic frameworks (such as Laravel or Symfony) were utilized.
* **Dual-Interface Architecture:** Features a public customer storefront (storytelling homepage, multi-category catalogue, product details with image galleries, session shopping cart, and simulated checkout) and an authenticated back-office management console (real-time KPI analytics, product/category CRUD, order review, customer records, and system health diagnostics).
* **Defensive Engineering & Security:** Built against OWASP Top 10 vulnerabilities via PDO prepared statements (SQL injection defense), strict context-aware `htmlspecialchars` output escaping (XSS defense), cryptographically secure session CSRF tokens (CSRF defense), and adaptive Bcrypt password hashing (`password_hash`).

---

## 2. Core Features & Catalogue Scope

### Storefront & Retail Features:
1. **Four Curated Product Categories:**
   * **Matcha Tea:** Single-origin ceremonial and daily-harvest Japanese green tea tencha stone-ground in Uji, Kyoto.
   * **Specialty Tea:** Six artisan loose-leaf cultivars (Saffron Golden Infusion, Midnight Bloom butterfly pea, Golden Osmanthus Oolong, White Peony, Earth & Ember Pu-erh, and Jasmine Pearls).
   * **Kits & Starter Sets:** Complete gift boxes containing ceremonial tins, bamboo whisks, scoops, and stoneware bowls.
   * **Ware & Accessories:** Handcrafted ceramic chawan bowls, multi-prong bamboo whisks (*chasen*), and bamboo scoops (*chashaku*).
2. **Product Details & Brewing Guidance:** Interactive image galleries with thumbnail switching, live stock status badges, customer review counters, ingredient notes, and precise water temperature/steeping instructions.
3. **Session Shopping Bag:** Real-time quantity adjustments, price calculations in South African Rand (ZAR), and persistent guest cart storage.
4. **Prepared-Drinks Menu ("Tea & Matcha Bar"):** Dedicated in-studio demonstration menu featuring 10 handcrafted drinks across two flights (*Flight 01: Whisked Matcha & Artisan Lattes*, *Flight 02: Iced Teas & Cold-Brewed Botanicals*), complete with studio hours, ingredient sourcing, and clear distinction from packaged retail teas.
5. **Cinematic 3D Feature ("The Ceremonial Chawan"):** Signature hardware-accelerated WebGL 3D vessel study rendering a procedural ceramic chawan bowl with authentic *wabi-sabi* hand-thrown contours, dynamic specular stoneware glaze lighting, whisked matcha foam interior, pointer drag with inertial damping, scroll parallax, and `@media (prefers-reduced-motion: reduce)` accessibility fallback.

### Administrative Console Features:
* Executive sales and inventory dashboard with live order counts and revenue metrics.
* Product catalogue management (create, read, update, deactivate).
* Category taxonomy administration.
* Order inspection and itemized fulfillment tracking.
* Customer directory and contact inquiry log.

---

## 3. Project Structure

```text
zazen-matcha/
├── admin/                      # Back-office administration portal
│   ├── categories.php          # Category taxonomy management
│   ├── customers.php           # Customer directory
│   ├── dashboard.php           # Executive KPI dashboard
│   ├── login.php               # Admin authentication gate
│   ├── orders.php              # Order fulfillment ledger
│   ├── products.php            # Product catalog inventory CRUD
│   └── settings.php            # Database & system health checks
├── css/
│   ├── admin.css               # Admin management styling
│   └── style.css               # Storefront design system & responsive layout
├── customer/                   # Customer storefront pages
│   ├── about.php               # Brand story & heritage
│   ├── account.php             # Customer profile & order history
│   ├── cart.php                # Session shopping bag
│   ├── checkout.php            # Multi-step checkout simulation
│   ├── contact.php             # Studio contact form
│   ├── login.php               # Customer authentication
│   ├── product.php             # Product details & brewing guidance
│   ├── products.php            # Filterable product catalogue
│   └── register.php            # New customer registration
├── database/
│   └── zazen_matcha.sql        # Database schema & initial seed script
├── docs/                       # Academic documentation & deliverables
│   ├── deliverable-2/          # Deliverable 2 report (PDF, HTML, MD)
│   ├── diagrams/               # System design models (Mermaid source)
│   │   └── rendered/           # Vector SVG diagrams (EERD, DFD, Use Case, Context)
│   └── prototypes/             # UI mockups & client sign-off instrument
├── images/                     # Application visual assets
│   ├── categories/             # Category cover photography
│   ├── gallery/                # Detail and texture photography
│   ├── products/               # Packaged tea and accessory images
│   └── tea-bar/                # Prepared drinks photography
├── includes/                   # Reusable server-side modules
│   ├── admin-header.php        # Admin navigation & layout wrapper
│   ├── auth.php                # Session guards & access control
│   ├── db.php                  # PDO database connection
│   ├── footer.php              # Storefront footer & script inclusion
│   ├── functions.php           # Sanitization, CSRF, formatting utilities
│   └── header.php              # Storefront header & navigation
├── js/
│   ├── chawan-3d.js            # Vanilla WebGL 3D vessel renderer
│   └── script.js               # Frontend UI utilities & drawer navigation
├── index.php                   # Storefront landing page
└── README.md                   # Project documentation & setup guide
```

---

## 4. Deliverable 2 Academic Artefacts

| Document / Artefact | Location | Description |
| :--- | :--- | :--- |
| **Comprehensive Report (PDF)** | `docs/deliverable-2/ITECA3-34_Deliverable_2_Report.pdf` | **Official Deliverable 2 Report** covering Introduction, Prototypes, System Design, Code Evidence, Database Screenshots, Functionality Audit, and Conclusions. |
| **Print-Ready HTML Report** | `docs/deliverable-2/report.html` | High-fidelity HTML source document used for offline PDF generation. |
| **Markdown Report Source** | `docs/deliverable-2/report.md` | Narrative report source with comprehensive code references. |
| **Database Design & Dictionary** | `docs/deliverable-2/database-design.md` | Full relational data dictionary for all 7 tables in Third Normal Form (3NF). |
| **Database Structure Screenshots** | `docs/deliverable-2/screenshots/database/` | 7 phpMyAdmin Structure screenshots verifying table schema without exposing customer records. |
| **Rendered Vector Diagrams** | `docs/diagrams/rendered/` | 4 standalone vector SVG diagrams: `eerd.svg`, `context-diagram.svg`, `dfd-level-1.svg`, `use-case-diagram.svg`. |
| **Diagram Source Models** | `docs/diagrams/` | Version-controlled Mermaid specifications: `crc-cards.md`, `eerd.md`, `context-diagram.md`, `dfd-level-1.md`, `use-case-diagram.md`. |
| **Storefront Prototypes** | `docs/prototypes/customer/` | 4 authentic desktop UI mockups and design rationale. |
| **Admin Console Prototypes** | `docs/prototypes/admin/README.md` | Screen wireframe specifications. |
| **Client Sign-off Instrument** | `docs/prototypes/client-approval-template.md` | Formal, unpopulated client approval evaluation template. |

---

## 5. Required Software & Local Environment

* **Operating System:** Windows 10/11, macOS, or Linux
* **Web Server:** Apache 2.4+ (Standard XAMPP distribution recommended)
* **PHP Runtime:** PHP 8.0 to PHP 8.2 with `pdo_mysql` and `openssl` extensions enabled
* **Database Management System:** MariaDB 10.4+ or MySQL 8.0+ (InnoDB storage engine)
* **Browser:** Any modern standards-compliant web browser (Microsoft Edge, Google Chrome, Mozilla Firefox, Safari)

---

## 6. Local Setup & Installation Instructions

### Step 1 — Deploy Source Files
Place the repository into your local web server document root:
* **XAMPP Windows default:** `C:\xampp\htdocs\zazen-matcha`
* **macOS / Linux:** `/var/www/html/zazen-matcha` or your configured virtual host directory.

### Step 2 — Start Server Services
Launch the **XAMPP Control Panel** and start both **Apache** and **MySQL**.

### Step 3 — Database Creation & Seed Data Import
> [!WARNING]
> **Database Safety Notice:**
> Import `database/zazen_matcha.sql` ONLY into a fresh local development environment. The script includes `DROP TABLE IF EXISTS` statements. Do not execute this script against an existing database containing live customer orders.

1. Open **phpMyAdmin** at `http://localhost/phpmyadmin/` (or use the MySQL CLI).
2. Create or select the `zazen_matcha` database and import the seed script:
   ```bash
   mysql -u root -p < database/zazen_matcha.sql
   ```
3. This creates the database `zazen_matcha` and provisions seven normalized InnoDB tables along with fictional prototype seed records.

### Step 4 — Verify Database Configuration
Connection parameters are defined in `includes/db.php`:
* **Host:** `localhost`
* **Database Name:** `zazen_matcha`
* **Username:** `root`
* **Password:** `""` *(standard XAMPP default is blank)*
* **Charset:** `utf8mb4`

Adjust these parameters in `includes/db.php` if your local environment utilizes a MySQL root password or non-standard port.

---

## 7. Navigating the Application

### Customer Storefront:
* **URL:** `http://localhost/zazen-matcha/` (or `http://localhost/zazen-matcha/index.php`)
* **Key Sections:** Hero narrative, feature pillars, favorites showcase, category grid, in-studio Tea & Matcha Bar menu (`#tea-bar`), 3D Chawan vessel study (`#vessel-study`), and Uji heritage story.
* **Catalogue:** Filter by category (`customer/products.php?category=1` through `4`), search by keyword, sort by price/rating.
* **Fictional Test Customer Account:**
  * **Email:** `thabo@example.test`
  * **Password:** `Customer123!`

### Administrator Console:
* **URL:** `http://localhost/zazen-matcha/admin/login.php`
* **Key Sections:** Management dashboard (`admin/dashboard.php`), product catalogue & stock levels (`admin/products.php`), category taxonomy (`admin/categories.php`), orders ledger (`admin/orders.php`), and system diagnostics (`admin/settings.php`).
* **Fictional Test Administrator Account:**
  * **Email:** `admin@zazenmatcha.test`
  * **Password:** `Admin123!`

---

## 8. Simulated Payment Tender Clarification

In strict conformance with the academic Deliverable 2 assignment scope:
* **No External Payment Gateway:** The application features **zero connection** to live external payment gateways (such as PayFast, Stripe, or PayPal).
* **Simulated Checkout:** Checkout tender options (`Simulated Card` with instant transaction authorization, and manual `Bank Transfer (EFT)` instructions) execute entirely within the local PHP application boundary.
* **Transactional Integrity:** All orders are committed via atomic PDO transactions (`SELECT ... FOR UPDATE` pessimistic row locking) to prevent inventory overselling race conditions.
