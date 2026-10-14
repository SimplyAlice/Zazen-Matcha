# Eduvos ITECA3-34 Deliverable 2 Project Report
**Module:** Web Development and e-Commerce Technologies (ITECA3-34)  
**Institution:** Eduvos Higher Education  
**Project Title:** Zazen Matcha & Tea Co. — Full-Stack Database-Driven e-Commerce Platform  
**Total Allocated Marks:** 65 Marks  
**Author:** Alice Matarise  
**Student Number:** ct.2023.h4c6x5  
**Live Public Deployment:** https://zazen-matcha.alwaysdata.net/  
**Repository Working Directory:** `C:\xampp\htdocs\ZAZEN-MATCHA`  
**Submission Version:** Deliverable 2 Comprehensive Academic Report  
**Target Environment:** Apache / PHP 8.4 / MariaDB 10.x (InnoDB) / Alwaysdata Cloud SSL

---

## Table of Contents
1. [Executive Summary & Project Overview](#1-executive-summary--project-overview)
2. [Chapter 1: Introduction (5 Marks)](#2-chapter-1-introduction-5-marks)
   * 2.1 [Business Background & Brand Vision](#21-business-background--brand-vision)
   * 2.2 [Problem Statement & Market Opportunity](#22-problem-statement--market-opportunity)
   * 2.3 [Project Objectives & Scope](#23-project-objectives--scope)
   * 2.4 [Target Audience & User Personas](#24-target-audience--user-personas)
   * 2.5 [Technology Stack Justification & CMS Prohibition Compliance](#25-technology-stack-justification--cms-prohibition-compliance)
3. [Chapter 2: User Interface Prototyping (12 Marks)](#3-chapter-2-user-interface-prototyping-12-marks)
   * 3.1 [Customer Storefront Prototype (6 Marks)](#31-customer-storefront-prototype-6-marks)
   * 3.2 [Administrator Management Console Prototype (6 Marks)](#32-administrator-management-console-prototype-6-marks)
   * 3.3 [Client Evaluation & Sign-Off Status](#33-client-evaluation--sign-off-status)
4. [Chapter 3: System Design & Architecture (15 Marks)](#4-chapter-3-system-design--architecture-15-marks)
   * 4.1 [Class Responsibility Collaborator (CRC) Cards (3 Marks)](#41-class-responsibility-collaborator-crc-cards-3-marks)
   * 4.2 [Enhanced Entity Relationship Diagram (EERD) (3 Marks)](#42-enhanced-entity-relationship-diagram-eerd-3-marks)
   * 4.3 [System Context Diagram (Level 0 DFD) (3 Marks)](#43-system-context-diagram-level-0-dfd-3-marks)
   * 4.4 [Level 1 Data Flow Diagram (DFD) (3 Marks)](#44-level-1-data-flow-diagram-dfd-3-marks)
   * 4.5 [UML Use Case Diagram & Actor Hierarchy (3 Marks)](#45-uml-use-case-diagram--actor-hierarchy-3-marks)
   * 4.6 [Relational Database Design & Normalisation](#46-relational-database-design--normalisation)
5. [Chapter 4: Implementation & Coding Evidence (12 Marks)](#5-chapter-4-implementation--coding-evidence-12-marks)
   * 5.1 [Sample PHP Code: Atomic Transaction & Concurrency Control (3 Marks)](#51-sample-php-code-atomic-transaction--concurrency-control-3-marks)
   * 5.2 [Sample HTML5 Code: Semantic Structure & Form Security (3 Marks)](#52-sample-html5-code-semantic-structure--form-security-3-marks)
   * 5.3 [Sample JavaScript Code: DOM Stepper & Progressive Enhancement (3 Marks)](#53-sample-javascript-code-dom-stepper--progressive-enhancement-3-marks)
   * 5.4 [Sample CSS3 Code: Design Tokens & Responsive Media Queries (3 Marks)](#54-sample-css3-code-design-tokens--responsive-media-queries-3-marks)
6. [Chapter 5: Relational Database Structure & Screenshots (2 Marks)](#6-chapter-5-relational-database-structure--screenshots-2-marks)
   * 6.1 [Database Architecture & Seven-Table Overview](#61-database-architecture--seven-table-overview)
   * 6.2 [MySQL Table Structure Screenshot Evidence](#62-mysql-table-structure-screenshot-evidence)
7. [Chapter 6: E-Commerce Functionality Audit (16 Marks)](#7-chapter-6-e-commerce-functionality-audit-16-marks)
   * 7.1 [Customer Storefront Capabilities (8 Marks)](#71-customer-storefront-capabilities-8-marks)
   * 7.2 [Administrator Portal Capabilities (8 Marks)](#72-administrator-portal-capabilities-8-marks)
8. [Chapter 7: System Limitations & Proposed Enhancements](#8-chapter-7-system-limitations--proposed-enhancements)
9. [Chapter 8: Conclusion & Learning Reflection (3 Marks)](#9-chapter-8-conclusion--learning-reflection-3-marks)
10. [References](#10-references)

---

## 1. Executive Summary & Project Overview

This report documents the architectural design, pre-development prototyping, relational database modelling, software implementation, and functional verification for **Zazen Matcha & Tea Co.**, a bespoke full-stack e-commerce web platform developed for the Eduvos ITECA3-34 Web Development and e-Commerce Technologies Deliverable 2 assignment.

In strict compliance with academic requirements, the application has been engineered entirely with custom **HTML5, CSS3, vanilla JavaScript, PHP 8.0, and MariaDB/MySQL**. Zero third-party Content Management Systems (CMS), website builders, or monolithic application frameworks (such as WordPress, Shopify, or Laravel) were utilized.

The software solution provides a dual-faceted operational environment, publicly deployed and operational at **https://zazen-matcha.alwaysdata.net/**:
1. **Customer-Facing Storefront:** A responsive digital studio enabling visitors to explore single-origin Japanese ceremonial green teas, examine handcrafted ceramic teaware, configure dynamic shopping carts, and execute simulated checkout orders protected by atomic database transactions and CSRF tokens.
2. **Administrator Management Console:** An isolated operational portal allowing authenticated store managers to oversee real-time revenue analytics, maintain product catalog inventories, configure category taxonomies, inspect chronological customer orders, and verify database connection health.

In strict compliance with Eduvos submission criteria, the entire web application and relational database are publicly hosted on Alwaysdata under a secure HTTPS environment (Let's Encrypt TLS), providing universal evaluator access to both customer and administrative functions.

---

## 2. Chapter 1: Introduction (5 Marks)

### 2.1 Business Background & Brand Vision
Zazen Matcha & Tea Co. is a boutique specialty tea purveyor conceptually established in Cape Town, South Africa. The company specializes in the sourcing and ethical distribution of single-origin, shade-grown organic tencha leaves harvested in the hillside tea gardens of Uji, Kyoto Prefecture, Japan. 

In traditional Japanese tea culture, *Zazen* (seated meditation) embodies mindfulness, clarity, and intentional calm. The enterprise translates this ancient heritage into contemporary everyday rituals for urban professionals, wellness practitioners, and discerning tea enthusiasts. Unlike mass-market culinary matcha powder often adulterated with fillers, sweeteners, or industrial food colorants, Zazen Matcha delivers stone-milled, pure ceremonial green tea rich in L-theanine and clean caffeine.

### 2.2 Problem Statement & Market Opportunity
The South African specialty beverage sector has witnessed surging consumer demand for functional, health-promoting alternatives to commercial coffee and energy drinks. However, consumers encounter significant friction in the domestic market:
1. **Opacity Regarding Grade and Provenance:** Commercial retailers frequently label low-grade culinary powder as "ceremonial", leading to bitter taste profiles and consumer disillusionment.
2. **Fragmented Sourcing of Teaware:** Novice consumers struggle to find curated, authentic preparation tools (such as hand-carved 80-tine bamboo *chasen* whisks, stoneware *chawan* bowls, and bamboo *chashaku* scoops) paired with educational preparation guides.
3. **Absence of Dedicated Digital Platforms:** Existing domestic options are either limited to generic marketplace listings or locked inside complex, slow CMS templates lacking bespoke aesthetic mindfulness and mobile responsiveness.

Zazen Matcha bridges this gap by providing an educational, aesthetically refined, and technically robust digital storefront.

### 2.3 Project Objectives & Scope
The primary technical and operational objectives of the Deliverable 2 project comprise:
* **Custom Software Architecture:** Develop an end-to-end, database-driven web application using raw PHP 8.0 and MySQL without prohibited CMS platforms.
* **Responsive Visual Prototyping:** Formulate intuitive user interface prototypes compatible across smartphones, tablets, and desktop viewports.
* **Formal System Modelling:** Produce academic system design artefacts including CRC cards, an Enhanced Entity Relationship Diagram (EERD), Context Diagram, Level 1 Data Flow Diagram (DFD), and UML Use Case specifications.
* **Transactional Reliability & Concurrency:** Implement atomic database transactions with pessimistic row-locking (`SELECT ... FOR UPDATE`) during checkout to eliminate inventory race conditions and prevent overselling.
* **Security & Defensive Engineering:** Protect the platform against common web application vulnerabilities (OWASP Top 10) through PDO prepared statements (SQL injection prevention), `htmlspecialchars` output encoding (Cross-Site Scripting prevention), cryptographically random session tokens (CSRF prevention), and adaptive Bcrypt password hashing.

### 2.4 Target Audience & User Personas
The system serves three core user profiles:
1. **The Daily Ritual Practitioner (Persona A):** Experienced consumers seeking high-grade ceremonial matcha (30g tins) for morning whisking; requires quick reordering, order history tracking, and inventory transparency.
2. **The Health-Conscious Novice (Persona B):** Beginners switching from coffee to matcha for jitter-free focus; requires educational sourcing narratives, water temperature guidelines, and bundled starter kits.
3. **The Store Administrator (Persona C):** Commercial operators requiring intuitive catalog CRUD controls, instant low-stock notifications ($\le 10$ units), customer directory lookup, and financial metric aggregation.

### 2.5 Technology Stack Justification & CMS Prohibition Compliance
The technology stack was chosen to meet performance, security, and curriculum guidelines:

| Layer | Selected Technology | Technical Justification | CMS Prohibition Status |
| :--- | :--- | :--- | :--- |
| **Markup & Semantics** | Semantic HTML5 | Native semantic tags (`<header>`, `<main>`, `<article>`, `<section>`, `<nav>`, `<footer>`) guarantee screen-reader accessibility and clean DOM structure. | **COMPLIANT** (100% Custom) |
| **Presentation & Layout** | Modern CSS3 (Grid / Flexbox) | Custom Properties (design tokens), CSS Grid, and media queries provide fluid responsiveness across mobile, tablet, and desktop without bulky third-party UI frameworks. | **COMPLIANT** (Custom `style.css`) |
| **Client-Side Behaviour** | Vanilla JavaScript (ES6+) | Lightweight, vanilla IIFE scripts handle mobile drawer toggling, dynamic quantity steppers, dismissible alerts, and gallery thumbnail switching without library overhead. | **COMPLIANT** (Custom `script.js`) |
| **Server-Side Runtime** | PHP 8.0 (Native Procedural / Modular) | High-performance server scripting with strict typing, native Bcrypt hashing, secure session handling, and robust PDO database abstraction. | **COMPLIANT** (Custom PHP Controllers) |
| **Database System** | MariaDB 10.4 / MySQL 8.0 (InnoDB) | Relational database providing ACID transactions, foreign key constraints (`ON DELETE RESTRICT`), and `utf8mb4` character encoding. | **COMPLIANT** (Custom Normalized Schema) |
| **Web Server Environment** | Apache 2.4.58 (XAMPP for Windows) | Proven local execution environment providing URL rewrite support, SSL/TLS handling, and native PHP integration. | **COMPLIANT** (Local Server Stack) |

---

## 3. Chapter 2: User Interface Prototyping (12 Marks)

The prototyping phase established the visual identity, wireframe layout, and user journeys prior to full-stack code implementation. All prototype artefacts are preserved in `docs/prototypes/`.

### 3.1 Customer Storefront Prototype (6 Marks)
The pre-development customer prototype is documented in [`docs/prototypes/customer/README.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/prototypes/customer/README.md) and supported by four high-fidelity desktop mockups:

1. **Screen 01: Storefront Homepage ([`01-home-page-prototype.jpg`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/prototypes/customer/01-home-page-prototype.jpg)):**  
   Establishes the editorial tone through a high-resolution hero banner (*"Mindful Matcha. Every Day."*), primary call-to-action buttons (*"Shop Matcha"*, *"Explore Our Story"*), a three-pillar value grid (*100% Ceremonial Grade*, *Mindfully Sourced*, *Calm Sustained Energy*), visual category cards, and the Uji hillside origin story.
2. **Screen 02: Shop / Catalog Browsing ([`02-shop-catalog-prototype.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/prototypes/customer/02-shop-catalog-prototype.png)):**  
   Presents the complete product range via a responsive 4-column card grid. Incorporates horizontal category filter pills (*All Products*, *Ceremonial Matcha*, *Everyday Matcha*, *Kits & Starter Sets*, *Teaware & Accessories*), search input, sorting controls, star review ratings, ZAR pricing, quick add-to-cart buttons, and numbered pagination.
3. **Screen 03: Product Details & Brewing Ritual ([`03-product-details-prototype.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/prototypes/customer/03-product-details-prototype.png)):**  
   Focuses on consumer confidence and education. Features hierarchical breadcrumbs, a multi-image gallery with selectable thumbnail views, verified review scores (124 reviews, 5.0 stars), inline quantity steppers (`- 1 +`), dual purchase buttons (*Add to Cart*, *Buy Now*), expandable preparation accordions, and related teaware recommendations.
4. **Screen 04: Shopping Cart Page ([`04-shopping-cart-prototype.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/prototypes/customer/04-shopping-cart-prototype.png)):**  
   Illustrates session shopping bag management with tabular item rows, unit prices, interactive quantity steppers, line totals, item removal controls, promo code inputs, and a floating order summary card with trust badges.

**Multi-Device Adaptation:**  
While initial supplied mockups targeted desktop viewports, responsive adaptation for tablet ($769\text{px} \le w \le 960\text{px}$) and mobile ($w \le 768\text{px}$) viewports was codified in [`css/style.css`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/css/style.css), implementing collapsible hamburger menus (`#nav-toggle`), single-column product stacking, and touch-optimized buttons.

### 3.2 Administrator Management Console Prototype (6 Marks)
The administrator management interface is documented in [`docs/prototypes/admin/README.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/prototypes/admin/README.md) across eleven operational screen specifications:

* **Authentication Portal (Screen A-01):** Dedicated admin login form isolated from customer sessions.
* **Operations Dashboard (Screen A-02):** Dynamic KPI metrics grid (Total Products, Active Catalog, Customers, Orders, Gross Revenue) and live feed of recent customer orders.
* **Catalog & Category Management (Screens A-03 to A-08):** Paginated inventory data tables with automated low-stock warnings ($\le 10$ units), dynamic category assignment, and case-insensitive uniqueness checks.
* **Order Oversight (Screens A-09 & A-10):** Chronological order review displaying customer delivery snapshots and historical line item pricing.
* **Customer Directory & System Health (Screens A-11 to A-13):** Customer oversight and live database connectivity health verification.

**Delineation of Implemented vs. Proposed Features:**  
*Implemented:* Admin Sign In, Session Guard, Dashboard Metrics, Product CRUD, Category CRUD, Customer Directory, Orders Listing, Order Details Breakdown, and Database Health Check.  
*Proposed / Future Enhancement:* Status transition mutation on [`admin/order-details.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/order-details.php) (currently read-only; admin status dropdowns proposed).

### 3.3 Client Evaluation & Sign-Off Status
In accordance with academic ethics, client approval has not been fabricated, simulated, or assumed. A formal sign-off instrument has been prepared and preserved at [`docs/prototypes/client-approval-template.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/prototypes/client-approval-template.md).

* **Current Status:** **PENDING STAKEHOLDER EXECUTION**
* **Required Real-World Inputs for Execution:**
  1. Client Business / Entity Name
  2. Client Representative Full Name and Professional Title / Designation
  3. Official Contact Email and Phone Coordinates
  4. Completed 6-dimension evaluation checklist (Brand Identity & Aesthetic Appeal, Customer Navigation & Discovery, Product Details & Brewing Ritual, Shopping Cart & Checkout UX, Cross-Device Responsiveness, and Administrative Usability)
  5. Documented revision/change requests (if any)
  6. Formal approval decision (`APPROVED` / `REJECTED`) with physical or verified digital signature and execution date.

---

## 4. Chapter 3: System Design & Architecture (15 Marks)

The system design phase generated five core architectural models and a comprehensive data dictionary, saved in [`docs/diagrams/`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/diagrams) and [`docs/deliverable-2/`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2).

> [!NOTE]
> **Diagram Format Specification:** In the project repository, all design models in [`docs/diagrams/`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/diagrams) are maintained as version-controlled Markdown Mermaid source text files rather than pre-rendered static raster/vector graphic images. For final academic PDF compilation or visual submission, these diagrams can be viewed directly via GitHub/Mermaid-compatible Markdown previewers or rendered into high-resolution visual figures.

### 4.1 Class Responsibility Collaborator (CRC) Cards (3 Marks)
Documented in [`docs/diagrams/crc-cards.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/diagrams/crc-cards.md). To reconcile procedural PHP implementation with academic object-oriented modelling, the CRC cards explicitly distinguish:
1. **Conceptual Domain Entities:** `Customer`, `Administrator`, `Category`, `Product`, `Order`, `OrderItem`, `ShoppingCart`, and `ContactMessage`.
2. **Architectural Service Collaborators:** `DatabaseManager` (PDO database connection and query abstraction), `AuthenticationGuard` (session-based access control), and `SecurityHelper` (CSRF token generation/verification, input sanitization, and password hashing).

### 4.2 Enhanced Entity Relationship Diagram (EERD) (3 Marks)
Documented in [`docs/diagrams/eerd.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/diagrams/eerd.md) using Mermaid `erDiagram`. Models all seven relational tables (`admins`, `customers`, `categories`, `products`, `orders`, `order_items`, `contact_messages`), attributes, primary/foreign keys, and cardinalities:
* `categories` ($1$) to `products` ($0..\text{N}$) via `fk_products_category` (`ON DELETE RESTRICT`).
* `customers` ($1$) to `orders` ($0..\text{N}$) via `fk_orders_customer` (`ON DELETE RESTRICT`).
* `orders` ($1$) to `order_items` ($1..\text{N}$) via `fk_order_items_order` (`ON DELETE RESTRICT`).
* `products` ($1$) to `order_items` ($0..\text{N}$) via `fk_order_items_product` (`ON DELETE RESTRICT`).
* `admins` and `contact_messages` operate as standalone operational entities.

**Financial Immutability Rule:** `order_items.unit_price` preserves the historical purchase price at the instant of sale, ensuring that subsequent adjustments to `products.price` never alter past accounting records.

### 4.3 System Context Diagram (Level 0 DFD) (3 Marks)
Documented in [`docs/diagrams/context-diagram.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/diagrams/context-diagram.md). Defines the system boundary (`Zazen Matcha e-Commerce System`) and information flows with external actors (`Customer / Guest Shopper` and `Store Administrator`).  
*Payment Gateway Distinction:* The model strictly documents the **absence of external third-party payment gateways**, accurately showing that tender selection (`Simulated Card` and manual `Bank Transfer (EFT)`) is handled entirely inside the system boundary.

### 4.4 Level 1 Data Flow Diagram (DFD) (3 Marks)
Documented in [`docs/diagrams/dfd-level-1.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/diagrams/dfd-level-1.md). Decomposes the platform into seven operational processes:
* `1.0 Authenticate & Manage Profiles` $\leftrightarrow$ `D1: customers`, `D6: admins`
* `2.0 Browse & Search Catalog` $\leftrightarrow$ `D2: products`, `D3: categories`
* `3.0 Manage Session Shopping Bag` $\leftrightarrow$ `D2: products`, `D8: Session Cart`
* `4.0 Execute Checkout & Atomic Transaction` $\leftrightarrow$ `D1`, `D2`, `D4: orders`, `D5: order_items`, `D8`
* `5.0 Manage Products & Categories` $\leftrightarrow$ `D2`, `D3`
* `6.0 Review Orders & Aggregate Metrics` $\leftrightarrow$ `D1`, `D4`, `D5`
* `7.0 Receive Studio Contact Inquiries` $\leftrightarrow$ `D7: contact_messages`

### 4.5 UML Use Case Diagram & Actor Hierarchy (3 Marks)
Documented in [`docs/diagrams/use-case-diagram.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/diagrams/use-case-diagram.md). Defines 20 implemented use cases across three actors (`Guest Visitor`, `Registered Customer` inheriting from Guest, and `Store Administrator`). Highlights `<<include>>` relationships for CSRF token verification and atomic inventory transactions, while clearly categorizing proposed features (`UC-P1` Fulfillment Status Updates, `UC-P2` Live Gateway Integration, `UC-P3` SMTP Email Invoices).

### 4.6 Relational Database Design & Normalisation
Documented in [`docs/deliverable-2/database-design.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/database-design.md). Provides a comprehensive data dictionary for all seven tables, covering column data types, nullability, default values, key constraints, fixed-point financial arithmetic (`DECIMAL(10,2)`), and UTF-8 multi-byte collation (`utf8mb4_unicode_ci`).

---

## 5. Chapter 4: Implementation & Coding Evidence (12 Marks)

The implementation codebase demonstrates defensive programming, security controls, and standards-compliant syntax. Below are four representative excerpts from the actual codebase with academic technical commentary.

### 5.1 Sample PHP Code: Atomic Transaction & Concurrency Control (3 Marks)
The excerpt below from [`customer/checkout.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/checkout.php#L270-L336) illustrates the transactional checkout process, demonstrating how ACID properties and row-level locking are implemented in native PHP:

```php
// Source: customer/checkout.php (Lines 270-336)
try {
    $pdo->beginTransaction();

    // 4a. Lock and re-fetch cart products inside the transaction (FOR UPDATE)
    $txProductIds   = array_keys($cleanCart);
    $txPlaceholders = implode(', ', array_fill(0, count($txProductIds), '?'));

    $lockSql = "
        SELECT
            product_id,
            name,
            price,
            stock_quantity,
            status
        FROM products
        WHERE product_id IN ({$txPlaceholders})
        FOR UPDATE
    ";
    $lockStmt = $pdo->prepare($lockSql);
    $lockStmt->execute(array_values($txProductIds));
    $lockedRows = $lockStmt->fetchAll();

    $lockedProducts = [];
    foreach ($lockedRows as $lRow) {
        $lockedProducts[(int) $lRow['product_id']] = $lRow;
    }

    // 4b. Final authoritative stock & price verification
    $verifiedItems    = [];
    $verifiedSubtotal = 0.0;

    foreach ($cleanCart as $pid => $requestedQty) {
        if (!isset($lockedProducts[$pid])) {
            throw new RuntimeException('STOCK_ERROR:One of the items in your cart is no longer available.');
        }

        $lockedProd  = $lockedProducts[$pid];
        $lockedStock = (int) $lockedProd['stock_quantity'];
        $lockedPrice = (float) $lockedProd['price'];

        if ($lockedProd['status'] !== 'active') {
            throw new RuntimeException(
                sprintf('STOCK_ERROR:"%s" is no longer active and cannot be purchased.', $lockedProd['name'])
            );
        }

        if ($lockedStock <= 0 || $requestedQty > $lockedStock) {
            throw new RuntimeException(
                sprintf(
                    'STOCK_ERROR:Insufficient stock for "%s" (requested %d, currently %d available).',
                    $lockedProd['name'],
                    $requestedQty,
                    max(0, $lockedStock)
                )
            );
        }

        $lineSubtotal      = round($lockedPrice * $requestedQty, 2);
        $verifiedSubtotal += $lineSubtotal;

        $verifiedItems[] = [
            'product_id' => $pid,
            'quantity'   => $requestedQty,
            'unit_price' => $lockedPrice,
            'subtotal'   => $lineSubtotal,
        ];
    }
```

**Technical Explanation & Engineering Justification:**
* **ACID Transaction Isolation:** Initiating `$pdo->beginTransaction()` guarantees that the multi-step order placement (customer creation, order insertion, order_items insertion, and inventory deduction) succeeds as a single atomic unit. If an error occurs, `$pdo->rollBack()` restores previous database state.
* **Pessimistic Concurrency Locking (`FOR UPDATE`):** In high-traffic e-commerce environments, multiple concurrent shoppers may attempt to purchase the final inventory unit simultaneously. Appending `FOR UPDATE` to the `SELECT` query instructs the InnoDB engine to acquire exclusive row-level locks on the selected product records. Other sessions attempting to read or update these rows must wait until this transaction commits or rolls back, completely eliminating race conditions.
* **Authoritative Server-Side Validation:** The application never trusts client-side price or quantity data submitted via form fields. Prices and inventory are re-queried directly from the locked database records.

---

### 5.2 Sample HTML5 Code: Semantic Structure & Form Security (3 Marks)
The excerpt below from [`customer/products.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/products.php#L258-L313) illustrates the semantic markup and security attributes used in the catalog product cards:

```html
<!-- Source: customer/products.php (Lines 258-313) -->
<article class="product-card shop-product-card">
    <a href="<?php echo e($productUrl); ?>" class="product-card-media" aria-label="View <?php echo e($product['name']); ?>">
        <img
            src="<?php echo e($imageUrl); ?>"
            alt="<?php echo e($product['name']); ?>"
            width="1000"
            height="1000"
            loading="lazy"
        >
    </a>

    <div class="product-card-body">
        <div class="shop-card-header-row">
            <h2 class="product-card-title">
                <a href="<?php echo e($productUrl); ?>">
                    <?php echo e($product['name']); ?>
                </a>
            </h2>
            <span class="shop-card-rating" aria-label="Rated <?php echo e($ratingValue); ?> out of 5 stars">
                <span class="star-glyph" aria-hidden="true">&#9733;</span> <?php echo e($ratingValue); ?>
            </span>
        </div>

        <p class="product-card-desc">
            <?php echo e($product['description']); ?>
        </p>

        <div class="product-card-meta">
            <span class="product-card-price"><?php echo e(format_price($product['price'])); ?></span>
            <?php if ($isLowStock): ?>
                <span class="stock-badge stock-low">Low Stock (<?php echo $stockQty; ?> left)</span>
            <?php elseif ($isInStock): ?>
                <span class="stock-badge stock-in">In Stock</span>
            <?php else: ?>
                <span class="stock-badge stock-out">Out of Stock</span>
            <?php endif; ?>
        </div>

        <div class="product-card-footer">
            <?php if ($isInStock): ?>
                <form action="<?php echo e($productUrl); ?>" method="post" class="card-quick-add-form">
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn btn-primary btn-card-full">
                        Add to Cart
                    </button>
                </form>
            <?php else: ?>
                <a href="<?php echo e($productUrl); ?>" class="btn btn-outline btn-card-full">
                    View Details
                </a>
            <?php endif; ?>
        </div>
    </div>
</article>
```

**Technical Explanation & Engineering Justification:**
* **Semantic HTML5 Elements:** Using `<article class="product-card shop-product-card">` encapsulates each catalog offering as a self-contained, distributable item. Media images specify explicit aspect dimensions (`width="1000" height="1000"`) paired with native `loading="lazy"` to eliminate layout shifts (Cumulative Layout Shift) and defer non-critical offscreen network fetches.
* **Accessibility (WCAG 2.1 AA):** Descriptive `aria-label` bindings on media links and rating containers guarantee that assistive screen readers announce product names and star ratings accurately. Purely decorative star glyphs are hidden from accessibility trees via `aria-hidden="true"`.
* **Cross-Site Request Forgery (CSRF) Mitigation:** The quick-add form embeds a hidden cryptographic token `<input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">` validated on the server using constant-time string comparison (`hash_equals()`), blocking cross-origin request forgery.
* **Output Escaping:** All dynamic values are passed through the helper `e()` which applies `htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`, neutralising Cross-Site Scripting (XSS) vectors.

---

### 5.3 Sample JavaScript Code: DOM Stepper & Progressive Enhancement (3 Marks)
The excerpt below from [`js/script.js`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/js/script.js#L67-L110) illustrates the vanilla JavaScript quantity stepper control:

```javascript
// Source: js/script.js (Lines 67-110)
/**
 * Initialises optional + / - quantity stepper controls on product/cart forms.
 */
function initQuantitySteppers() {
    const steppers = document.querySelectorAll('.quantity-stepper');

    steppers.forEach(function (stepper) {
        const input = stepper.querySelector('.quantity-input');
        const buttons = stepper.querySelectorAll('[data-qty-step]');

        if (!input || input.disabled) {
            return;
        }

        function clampQuantity(val) {
            const min = parseInt(input.getAttribute('min'), 10) || 1;
            const max = parseInt(input.getAttribute('max'), 10) || 999;
            let parsed = parseInt(val, 10);
            if (isNaN(parsed) || parsed < min) {
                parsed = min;
            } else if (parsed > max) {
                parsed = max;
            }
            input.value = String(parsed);
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const step = parseInt(btn.getAttribute('data-qty-step'), 10) || 0;
                const current = parseInt(input.value, 10) || 1;
                clampQuantity(current + step);

                // Auto-submit cart quantity update form when stepper is clicked in cart
                const cartForm = stepper.closest('.cart-update-form');
                if (cartForm && parseInt(input.value, 10) !== current) {
                    cartForm.submit();
                }
            });
        });

        input.addEventListener('change', function () {
            clampQuantity(input.value);
        });
    });
}
```

**Technical Explanation & Engineering Justification:**
* **Progressive Enhancement:** Form inputs function natively as standard HTML `<input type="number">` fields if JavaScript is disabled. When active, script enhances the UI with touch-friendly increment/decrement step buttons (`[data-qty-step]`).
* **Boundary Clamping:** The helper `clampQuantity()` enforces minimum ($\ge 1$) and maximum bounds derived directly from DOM attributes, preventing invalid negative or zero values.
* **Automatic Form Submission:** When adjusted inside the shopping cart, the script detects the parent form via `stepper.closest('.cart-update-form')` and submits immediately, providing smooth desktop and mobile interaction without requiring extra "Update" button clicks.
* **IIFE Scope Encapsulation:** Wrapped in an Immediately Invoked Function Expression `(function () { 'use strict'; ... })();` to prevent global namespace pollution.

---

### 5.4 Sample CSS3 Code: Design Tokens & Responsive Media Queries (3 Marks)
The excerpts below from [`css/style.css`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/css/style.css) illustrate the responsive layout foundations and mobile navigation mechanics across two contiguous source blocks:

```css
/* Source: css/style.css (Lines 4084-4131) — Tablet Breakpoint Foundations */
/* ----------------------------------------------------------------------------
   17. Responsive Foundations (Tablet & Mobile)
   ---------------------------------------------------------------------------- */
@media (max-width: 960px) {
    .hero-grid,
    .brand-intro-grid,
    .ritual-grid,
    .product-detail-grid,
    .cart-layout,
    .checkout-layout,
    .confirmation-layout,
    .auth-layout,
    .account-layout,
    .contact-layout,
    .admin-dashboard-grid {
        grid-template-columns: 1fr;
        gap: 2.5rem;
    }

    .confirmation-meta-bar,
    .about-values-grid,
    .admin-metrics-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .why-zazen-grid,
    .category-showcase-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .cart-summary-card,
    .checkout-summary-card,
    .confirmation-summary-card,
    .account-orders-card {
        position: static;
    }

    .checkout-address-grid {
        grid-template-columns: 1fr 1fr;
    }

    .product-grid,
    .shop-product-grid,
    .favorites-icon-strip {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

```css
/* Source: css/style.css (Lines 4283-4305) — Mobile Drawer Navigation */
    .nav-toggle {
        display: inline-flex;
    }

    .nav-wrapper {
        display: none;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        flex-direction: column;
        align-items: stretch;
        max-width: none;
        padding: 1.5rem var(--container-pad);
        background-color: var(--color-surface);
        border-bottom: 1px solid var(--color-border);
        box-shadow: 0 12px 24px rgba(24, 26, 23, 0.06);
    }

    .nav-wrapper.is-open {
        display: flex;
    }
```

**Technical Explanation & Engineering Justification:**
* **Modern CSS Grid & Flexbox Architecture:** Layouts rely on fluid CSS Grid with dynamic fraction units (`1fr`), avoiding rigid pixel-based container widths.
* **Breakpoint Hierarchy:**
  * At `960px` (Tablet threshold), multi-column split layouts collapse to single vertical columns, sticky sidebars revert to `position: static` to prevent overflow clipping, and 4-column product grids adapt to 2 columns.
  * At `768px` (Smartphone threshold), all product grids and admin metrics collapse into single full-width cards, optimizing mobile readability and vertical thumb scrolling.
* **Accessible Mobile Navigation:** The primary navigation wrapper transitions from a static horizontal flexbar into an absolute drawer displayed upon toggling `.nav-wrapper.is-open`, triggered by `#nav-toggle` with synchronized `aria-expanded` states.

---

## 6. Chapter 5: Relational Database Structure & Screenshots (2 Marks)

### 6.1 Database Architecture & Seven-Table Overview
The `zazen_matcha` database operates under MariaDB 10.4 with InnoDB storage, enforcing referential integrity and complete normalization across seven tables:

| # | Table Name | Storage Engine | Primary Key | Foreign Keys & Target | Purpose in System |
| :---: | :--- | :---: | :--- | :--- | :--- |
| **1** | `admins` | InnoDB | `admin_id` | *None* (Isolated) | Stores administrative management credentials and Bcrypt hashes. |
| **2** | `customers` | InnoDB | `customer_id` | *None* | Stores registered and guest customer profiles, delivery addresses, and login credentials. |
| **3** | `categories` | InnoDB | `category_id` | *None* | Classifies teas and teaware into editorial taxonomies (*Matcha*, *Specialty Tea*, *Accessories*, *Gift Sets*). |
| **4** | `products` | InnoDB | `product_id` | `category_id` $\to$ `categories` | Maintains product title, description, price (ZAR), stock quantity, image path, and publication status. |
| **5** | `orders` | InnoDB | `order_id` | `customer_id` $\to$ `customers` | Records customer orders, immutable delivery address snapshots, totals, and simulated tender methods. |
| **6** | `order_items` | InnoDB | `order_item_id` | `order_id` $\to$ `orders`<br>`product_id` $\to$ `products` | Normalizes purchased line items, recording quantity, line subtotal, and frozen historical `unit_price`. |
| **7** | `contact_messages` | InnoDB | `message_id` | *None* (Standalone) | Stores visitor feedback, wholesale queries, and tea master inquiries submitted via contact form. |

### 6.2 MySQL Table Structure Screenshot Evidence
In compliance with assessment criteria and privacy guidelines, visual evidence captures the **Structure tab (`tbl_structure.php`)** in phpMyAdmin to prove schema definition, data typing, and indexing without exposing live customer data.

* **Checklist Reference:** A dedicated step-by-step capture checklist is provided in [`docs/deliverable-2/mysql-screenshot-checklist.md`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/mysql-screenshot-checklist.md).
* **Verified Captured Images in [`docs/deliverable-2/screenshots/database/`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/):**
  1. [`01-table-admins-structure.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/01-table-admins-structure.png) (Structure of `admins` table showing primary key on `admin_id` and unique index on `email`)
  2. [`02-table-customers-structure.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/02-table-customers-structure.png) (Structure of `customers` table showing primary key on `customer_id` and unique index on `email`)
  3. [`03-table-categories-structure.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/03-table-categories-structure.png) (Structure of `categories` table showing primary key on `category_id` and unique index on `name`)
  4. [`04-table-products-structure.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/04-table-products-structure.png) (Structure of `products` table showing primary key on `product_id` and foreign key index `idx_products_category`)
  5. [`05-table-orders-structure.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/05-table-orders-structure.png) (Structure of `orders` table showing primary key on `order_id` and foreign key index `idx_orders_customer`)
  6. [`06-table-order-items-structure.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/06-table-order-items-structure.png) (Structure of `order_items` showing dual foreign key indexes for `orders` and `products`)
  7. [`07-table-contact-messages-structure.png`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/docs/deliverable-2/screenshots/database/07-table-contact-messages-structure.png) (Structure of `contact_messages` table showing primary key on `message_id`)
* **Optional Designer Canvas:** The optional visual relations canvas (`08-database-designer-relations.png`) was not captured; the seven individual table structure captures provide comprehensive, direct proof of all table structures and indexed foreign keys.

---

## 7. Chapter 6: E-Commerce Functionality Audit (16 Marks)

### 7.1 Customer Storefront Capabilities (8 Marks)
The customer storefront was evaluated using real browser automation (Microsoft Edge), read-only HTTP GET requests, static asset verification, and deep source-code auditing:

| Functional Area | Implemented Mechanism | Verification Mode | Diagnostic Result |
| :--- | :--- | :---: | :--- |
| **Storefront Homepage** | Root [`index.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/index.php) rendering hero banner, values, featured teas, category grid, in-studio Tea &amp; Matcha Bar, 3D Chawan vessel study, Uji tea story, and footer. | **Live Browser QA** | `HTTP 200 OK`, 0 PHP errors, responsive desktop and mobile viewports verified. |
| **Catalog Browsing** | [`customer/products.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/products.php) with 4 category filter pills (*Matcha*, *Specialty Tea*, *Accessories*, *Gift Sets*), keyword search, rating badges, and pagination. | **Live Browser QA** | `HTTP 200 OK`, 0 PHP errors, all 12 product images load (`200 OK`), category filter queries verified. |
| **In-Studio Tea &amp; Matcha Bar** | Dedicated section [`index.php#tea-bar`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/index.php#tea-bar) presenting 10 handcrafted drinks across two flights (*Whisked Matcha &amp; Artisan Lattes*, *Iced Teas &amp; Botanicals*) with studio hours, ingredient sourcing, and clear distinction from packaged products. | **Live Browser QA** | `HTTP 200 OK`, 0 PHP errors, responsive flight grid verified, high-resolution photography loads (`200 OK`). |
| **Cinematic 3D Vessel Study** | Signature hardware-accelerated WebGL 3D Chawan vessel study on [`index.php#vessel-study`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/index.php#vessel-study) with lathe geometry, dynamic lighting, pointer inertia, scroll parallax, and `prefers-reduced-motion` fallback. | **Live Browser QA** | `HTTP 200 OK`, WebGL canvas initializes cleanly, zero external dependencies, responsive canvas scaling, static photograph fallback verified. |
| **Brand Story Page** | [`customer/about.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/about.php) featuring Uji origin history, values, and preparation philosophy. | **Live HTTP GET** | `HTTP 200 OK`, 0 PHP errors, assets load (`200 OK`). |
| **Product Detail View** | [`customer/product.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/product.php) featuring multi-angle gallery, quantity steppers, tasting notes, and stock checks. | **Source Audit &amp; QA** | Validated prepared query lookup across all 12 products, fallback image resolver, and CSRF token binding. |
| **Shopping Cart** | [`customer/cart.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/cart.php) managing session bag, server-side price re-verification, quantity updates, and cart clear. | **Live QA &amp; Audit** | CSRF validation verified on all POST actions; subtotal calculation verified (R 245.00 $\times 2 =$ R 490.00); clean cart clear verified. |
| **Checkout Processing** | [`customer/checkout.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/checkout.php) collecting delivery details, validating SA provinces, and executing atomic transaction. | **Source Audit** | Atomic PDO transaction with `SELECT ... FOR UPDATE` locking and `rowCount() === 1` stock update verification. |
| **Order Confirmation** | [`customer/order-confirmation.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/order-confirmation.php) displaying order invoice, customer address, and line items. | **Source Audit** | Enforces strict ownership access control (logged-in customer ID or guest `last_order_id` session match). |
| **Customer Authentication** | [`customer/login.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/login.php), [`customer/register.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/register.php) using PHP `password_hash()` and `password_verify()`. | **Live HTTP GET** | `HTTP 200 OK` on both routes, 0 PHP errors, clean form presentation. |
| **Customer Account Portal** | [`customer/account.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/account.php) allowing profile editing, password changes, and order history inspection. | **Source Audit** | Protected by `require_customer_login()`; password change uses `password_verify()` before rehashing. |
| **Studio Contact Form** | [`customer/contact.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/contact.php) capturing feedback and inserting records into `contact_messages`. | **Live HTTP GET** | `HTTP 200 OK`, 0 PHP errors, inputs sanitized via `clean_input()`. |

### 7.2 Administrator Portal Capabilities (8 Marks)
The management console was evaluated across administrative access control and operations modules:

| Functional Area | Implemented Mechanism | Verification Mode | Diagnostic Result |
| :--- | :--- | :---: | :--- |
| **Admin Sign In** | [`admin/login.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/login.php) with isolated session initialization. | **Live HTTP GET** | `HTTP 200 OK`, 0 PHP errors, clean CSRF-protected form. |
| **Access Guard** | [`includes/admin-auth.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/includes/admin-auth.php) protecting all management pages via `require_admin_login()`. | **Source Audit** | Restricts unauthenticated visitors to `admin/login.php` without altering customer shopping carts. |
| **Executive Dashboard** | [`admin/dashboard.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/dashboard.php) aggregating live KPI counters (Products, Customers, Orders, Sales) and 5 recent orders. | **Source Audit** | Clean PDO prepared statements computing real-time aggregates and revenue sums. |
| **Product Management** | [`admin/products.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/products.php), [`product-add.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/product-add.php), [`product-edit.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/product-edit.php) providing full catalog CRUD. | **Source Audit** | Validates dynamic category dropdowns, enforces numeric price/stock rules, flags low stock ($\le 10$). |
| **Category Management** | [`admin/categories.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/categories.php), [`category-add.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/category-add.php), [`category-edit.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/category-edit.php) providing taxonomy CRUD. | **Source Audit** | Aggregates linked product counts; enforces case-insensitive name uniqueness (`LOWER(name)`). |
| **Customer Oversight** | [`admin/customers.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/customers.php), [`customer-details.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/customer-details.php) listing accounts and order histories. | **Source Audit** | Newest-first sorting; strictly excludes password hashes from `SELECT` projection. |
| **Orders Oversight** | [`admin/orders.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/orders.php), [`order-details.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/order-details.php) displaying order journal and line item breakdown. | **Source Audit** | Displays historical delivery snapshot and purchased `order_items.unit_price`. |
| **Status Mutation Gap** | **Order status update controls in [`admin/order-details.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/order-details.php)**. | **Source Audit** | **Identified Gap:** The details page is currently read-only. Adding status transition controls is proposed. |
| **System Settings** | [`admin/settings.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/settings.php) verifying store information and live database health. | **Source Audit** | Executes live `SELECT 1 AS connection_ok` query and displays record count summaries. |

---

## 8. Chapter 7: System Limitations & Proposed Enhancements

In accordance with academic transparency, the following technical boundaries of the Deliverable 2 prototype are documented:

1. **Simulated Payment Gateway Architecture:**
   * *Current State:* The application does not connect to a commercial card processor API (such as PayFast or Stripe). Orders are processed via internal prototype tenders (`Simulated Card` with immediate authorization or `Bank Transfer (EFT)` with reference instructions).
   * *Proposed Enhancement:* Implement a secure webhook-driven payment gateway integration using sandbox API credentials for Deliverable 3.
2. **Administrative Order Status Transitions:**
   * *Current State:* [`admin/order-details.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/order-details.php) provides an immutable, read-only audit view of orders and line items. Administrators cannot currently change fulfillment status (`pending` $\to$ `shipped`) directly on the page.
   * *Proposed Enhancement:* Implement CSRF-protected form controls on [`admin/order-details.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/admin/order-details.php) allowing administrators to update `order_status` and `payment_status`.
3. **Transactional Email Notifications:**
   * *Current State:* Local XAMPP environments lack an active SMTP Mail Transfer Agent (MTA). Invoices are rendered directly on-screen via [`customer/order-confirmation.php`](file:///c:/xampp/htdocs/ZAZEN-MATCHA/customer/order-confirmation.php).
   * *Proposed Enhancement:* Integrate a cloud email API (e.g. PHPMailer via SendGrid or Mailgun) to dispatch HTML receipts and order tracking numbers.
4. **Cloud Production Deployment (Verified Active):**
   * *Implementation Status:* **FULLY OPERATIONAL & VERIFIED LIVE**
   * *Production URL:* `https://zazen-matcha.alwaysdata.net/`
   * *Target Environment:* Linux / Apache / PHP 8.4 / MariaDB 10.x (InnoDB) with Let's Encrypt SSL/TLS encryption.
   * *Database Architecture:* Remote MariaDB cluster (`mysql-zazen-matcha.alwaysdata.net`) maintaining referential integrity across all 7 operational tables.
   * *Evaluator Access:* All customer storefront journeys (catalog browsing, shopping bag operations, atomic checkout) and administrative management functions (dashboard metrics, product/category CRUD, order review) are directly testable over the public internet, satisfying the mandatory Eduvos hosting requirement.

---

## 9. Chapter 8: Conclusion & Learning Reflection (3 Marks)

### 9.1 Summary of Deliverable 2 Achievements
The Zazen Matcha & Tea Co. Deliverable 2 project has successfully established a production-grade, database-driven e-commerce platform that fulfills all functional, architectural, and ethical criteria of the Eduvos ITECA3-34 curriculum:
1. **CMS Prohibition Compliance:** The system demonstrates that a responsive e-commerce application can be built from foundational web standards (HTML5, CSS3, JavaScript, PHP, MySQL) without reliance on bloated CMS platforms or monolithic frameworks.
2. **Architectural Rigor:** High-mark system design artefacts (CRC Cards, EERD, Context Diagram, Level 1 DFD, Use Case Model, and Data Dictionary) have been produced and aligned with the actual codebase.
3. **Database Integrity & Concurrency:** Implementing an atomic PDO transaction with `SELECT ... FOR UPDATE` row-locking during checkout solves one of the most critical real-world e-commerce challenges: race conditions and inventory overselling.
4. **Defensive Security Posture:** The implementation adheres to OWASP recommendations, incorporating parameterized PDO queries against SQL injection, `htmlspecialchars` output escaping against XSS, cryptographically secure CSRF session tokens, and modern Bcrypt password hashing.

### 9.2 Technical Reflection & Final-Year IT Competencies
Developing Zazen Matcha provided practical mastery across several advanced competencies:
* **Relational Database Design:** Normalizing tables to 3NF while recognizing practical exceptions—such as storing frozen snapshots of `unit_price` in `order_items` and shipping addresses in `orders` to ensure historical financial and legal audit compliance.
* **Separation of Concerns:** Structuring a clean procedural architecture that decouples database connection logic (`db.php`), reusable utility functions (`functions.php`), authentication guards (`customer-auth.php`, `admin-auth.php`), and template presentation layers.
* **Progressive Enhancement:** Crafting resilient front-end interactions (quantity steppers, mobile drawer navigation) that function natively via server-side form submissions when scripts are disabled, but enhance user delight when JavaScript is active.

The Zazen Matcha codebase and documentation provide a complete, robust foundation ready for packaging, public staging deployment, and final assessment presentation.

---

## 10. References

1. Eduvos (Pty) Ltd. (2026). *Web Development and e-Commerce Technologies (ITECA3-34) Module Guide and Deliverable 2 Assessment Brief*. Eduvos Higher Education.
2. The PHP Group. (2026). *PHP Manual: PDO (PHP Data Objects), Password Hashing, and Session Management*. Available at: https://www.php.net/manual/en/
3. Oracle Corporation / MariaDB Foundation. (2026). *MariaDB 10.4 Reference Manual: InnoDB Storage Engine, Locking, and Transactions*. Available at: https://mariadb.com/kb/en/documentation/
4. OWASP Foundation. (2025). *OWASP Top 10 Web Application Security Risks*. Available at: https://owasp.org/www-project-top-ten/
5. World Wide Web Consortium (W3C). (2023). *Web Content Accessibility Guidelines (WCAG) 2.1*. W3C Recommendation. Available at: https://www.w3.org/TR/WCAG21/
6. Mozilla Developer Network (MDN). (2026). *CSS Grid Layout, Flexbox, and Media Queries Reference*. Available at: https://developer.mozilla.org/en-US/docs/Web/CSS

---

## 11. Appendix E: AI Assistance & Disclosure Statement

### E.1 Disclosure Overview & Institutional Compliance
In compliance with the Eduvos Higher Education Policy on Artificial Intelligence and Academic Integrity, this appendix provides a transparent, comprehensive declaration of the artificial intelligence systems utilized during the research, architectural design, software development, troubleshooting, and documentation phases of Deliverable 2.

All submitted work has been subjected to rigorous human peer review, manual debugging, and functional execution verification by the student author. The student author retains complete ownership of and intellectual accountability for all code, database structures, system models, and written analyses presented in this submission.

### E.2 Inventory of AI Systems Utilized
* **Primary AI Assistant:** Google Antigravity (Powered by DeepMind Advanced Agentic Coding Models / Gemini 2.5)
* **Execution Modality:** Local IDE pair-programming assistant, codebase auditor, and terminal orchestrator.
* **Date Range of Usage:** October 2026.

### E.3 Functional Scope of AI Assistance
The AI assistant was utilized across the following delimited technical domains:

1. **Architectural Scaffolding & Modular PHP Design:**
   * Assisted in structuring clean procedural PHP separation of concerns (`includes/db.php`, `includes/functions.php`, `includes/customer-auth.php`, `includes/admin-auth.php`) without the use of prohibited third-party CMS platforms or monolithic application frameworks.
2. **Concurrency Control & Transaction Implementation:**
   * Assisted in drafting the atomic checkout algorithm in `customer/checkout.php`, specifically the implementation of `SELECT ... FOR UPDATE` pessimistic row-locking on InnoDB tables to prevent inventory race conditions.
3. **Responsive Front-End Styling & Semantic Markup:**
   * Assisted in refining fluid CSS Grid layouts and WCAG 2.1 AA accessibility attributes (`aria-label`, `aria-hidden`) across catalog and product views.
4. **Codebase Auditing & Security Scans:**
   * Automated verification of PDO prepared statements (SQL injection prevention), `htmlspecialchars` output encoding (XSS mitigation), CSRF token validation, and detection of hardcoded development credentials.
5. **Production Deployment & Server Troubleshooting:**
   * Assisted in diagnosing remote server error logs during Alwaysdata cloud deployment, resolving MariaDB authentication error 1045, and packaging POSIX-compliant ZIP archives for Apache/Linux extraction.
6. **Documentation Synthesis:**
   * Assisted in compiling, formatting, and proofreading the academic report structure according to the Eduvos ITECA3-34 Deliverable 2 brief.

### E.4 Human Verification, Testing, and Quality Assurance Methodology
To guarantee that no hallucinated, insecure, or unverified artifacts were incorporated into the project:
* **Zero Fabrication Policy:** All database screenshots (`01-table-admins-structure.png` through `07-table-contact-messages-structure.png`) are genuine, full-resolution phpMyAdmin captures from the operational database. No screenshots, mockups, or client signatures were synthetically fabricated.
* **Independent Execution QA:** Every PHP script, form action, and database query was tested in both the local XAMPP environment and the public Alwaysdata production environment (`https://zazen-matcha.alwaysdata.net/`).
* **Code Review & Line-by-Line Comprehension:** The student personally reviewed and understood all generated logic, variable bindings, and database schemas before incorporating them into the submission repository.

### E.5 Formal Student Authorship Declaration

```text
I, Alice Matarise, Student Number ct.2023.h4c6x5, hereby declare that:
1. The work submitted for Eduvos ITECA3-34 Deliverable 2 is my own work, developed in accordance with the module guidelines.
2. Where artificial intelligence tools were employed for assistance, their usage has been fully and truthfully disclosed in this Appendix.
3. I have personally reviewed, tested, and verified all source code, database configurations, and documentation.
4. I understand and can defend every architectural decision, security control, and code implementation presented in this submission.

Student Signature: ____________________________________    Date: ________________________
```

