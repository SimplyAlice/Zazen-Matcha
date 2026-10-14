# Level 1 Data Flow Diagram (DFD)
**Project:** Zazen Matcha & Tea Co. — e-Commerce Platform  
**Course:** Eduvos ITECA3-34 Web Development and e-Commerce Technologies (Deliverable 2)  
**Document:** System Design Documentation — Level 1 DFD  
**Source Baseline:** `C:\xampp\htdocs\ZAZEN-MATCHA`

---

## 1. Process Decomposition & Architectural Scope

The Level 1 Data Flow Diagram decomposes the primary system boundary (Process 0.0) into seven concrete, functional subprocesses mapping directly to the actual PHP controllers and MariaDB data stores.

### Implemented Subprocesses:
1. **1.0 User Authentication & Profile Management:** Handles customer registration, credential verification, session creation, profile updating, and administrative login.
2. **2.0 Catalog Browsing & Search:** Serves active products, executes keyword search, filters by taxonomy category, and presents single product galleries.
3. **3.0 Shopping Cart Management:** Controls session-based shopping bag items, enforces real-time stock limits, and computes dynamic line subtotals.
4. **4.0 Order Checkout & Stock Allocation:** Collects shipping details, verifies stock via row locks (`FOR UPDATE`), creates orders and line items inside a PDO transaction, and decrements inventory.
5. **5.0 Product & Category Administration:** Provides catalog CRUD operations, category uniqueness checks, and stock level maintenance for store administrators.
6. **6.0 Order Oversight & Business Analytics:** Computes dashboard KPI sales metrics and renders detailed fulfillment records.
7. **7.0 Customer Inquiries & Studio Care:** Validates and stores incoming contact form messages.

---

## 2. Visual Level 1 DFD (Mermaid)

```mermaid
flowchart TD
    %% External Entities
    CUST["Customer / Guest Shopper"]
    ADMIN["Store Administrator"]

    %% Data Stores (InnoDB Tables & Session)
    D1[("D1: customers")]
    D2[("D2: products")]
    D3[("D3: categories")]
    D4[("D4: orders")]
    D5[("D5: order_items")]
    D6[("D6: admins")]
    D7[("D7: contact_messages")]
    D8[("D8: Session Cart State")]

    %% Process 1.0: Authentication & Profile
    subgraph P1 ["1.0 Authentication & Profile Management"]
        P1_AUTH(("1.0\nAuthenticate &\nManage Profiles"))
    end
    CUST -->|"Login / Register / Profile Details"| P1_AUTH
    P1_AUTH -->|"Customer Credentials & Addresses"| D1
    D1 -->|"Profile Data & Bcrypt Hash"| P1_AUTH
    ADMIN -->|"Admin Login Credentials"| P1_AUTH
    P1_AUTH -->|"Verify Admin Credentials"| D6
    D6 -->|"Admin Hash & Name"| P1_AUTH
    P1_AUTH -->|"Session Cookie & Flash Alerts"| CUST
    P1_AUTH -->|"Admin Session Cookie"| ADMIN

    %% Process 2.0: Catalog Browsing & Search
    subgraph P2 ["2.0 Catalog Browsing & Search"]
        P2_CAT(("2.0\nBrowse &\nSearch Catalog"))
    end
    CUST -->|"Search Terms & Category Filters"| P2_CAT
    D2 -->|"Active Products & Prices"| P2_CAT
    D3 -->|"Category Names & Descriptions"| P2_CAT
    P2_CAT -->|"Formatted Product Cards & Galleries"| CUST

    %% Process 3.0: Shopping Cart Management
    subgraph P3 ["3.0 Shopping Cart Management"]
        P3_CART(("3.0\nManage Session\nShopping Bag"))
    end
    CUST -->|"Add / Update / Remove Item"| P3_CART
    P3_CART -->|"Verify Product Price & Stock"| D2
    D2 -->|"Stock Quantity & Status"| P3_CART
    P3_CART <-->|"Read / Write Cart Array"| D8
    P3_CART -->|"Updated Cart View & Subtotals"| CUST

    %% Process 4.0: Order Checkout & Stock Allocation
    subgraph P4 ["4.0 Order Checkout & Stock Allocation"]
        P4_CHK(("4.0\nExecute Checkout &\nAtomic Transaction"))
    end
    CUST -->|"Shipping Address & Simulated Tender"| P4_CHK
    D8 -->|"Cart Items & Quantities"| P4_CHK
    P4_CHK -->|"Lock Rows FOR UPDATE & Deduct Stock"| D2
    P4_CHK -->|"Read / Provision Customer"| D1
    P4_CHK -->|"Insert Order Header"| D4
    P4_CHK -->|"Insert Order Items (Historical Unit Price)"| D5
    P4_CHK -->|"Clear Cart State"| D8
    P4_CHK -->|"Order Invoice & Tracking Reference"| CUST

    %% Process 5.0: Product & Category Administration
    subgraph P5 ["5.0 Product & Category Administration"]
        P5_ADM(("5.0\nManage Products\n& Categories"))
    end
    ADMIN -->|"Add / Edit Products & Categories"| P5_ADM
    P5_ADM -->|"Insert / Update Product Records"| D2
    P5_ADM -->|"Insert / Update Category Records"| D3
    D2 -->|"Product Catalog Listings"| P5_ADM
    D3 -->|"Category Taxonomy & Counts"| P5_ADM
    P5_ADM -->|"CRUD Status & Validation Feedback"| ADMIN

    %% Process 6.0: Order Oversight & Business Analytics
    subgraph P6 ["6.0 Order Oversight & Business Analytics"]
        P6_ORD(("6.0\nReview Orders &\nAggregate Metrics"))
    end
    ADMIN -->|"View Orders & Dashboard Request"| P6_ORD
    D4 -->|"Order Totals, Dates, Statuses"| P6_ORD
    D5 -->|"Order Items & Line Subtotals"| P6_ORD
    D1 -->|"Customer Shipping Names"| P6_ORD
    P6_ORD -->|"KPI Metrics & Order Breakdowns"| ADMIN

    %% Process 7.0: Customer Inquiries
    subgraph P7 ["7.0 Customer Inquiries & Studio Care"]
        P7_MSG(("7.0\nReceive Studio\nContact Inquiries"))
    end
    CUST -->|"Submit Studio Contact Form"| P7_MSG
    P7_MSG -->|"Insert Inquiry Record"| D7
    D7 -->|"Read Inquiry Records"| ADMIN
    P7_MSG -->|"Submission Confirmation Alert"| CUST

    %% Styling
    style P1_AUTH fill:#e2e8e2,stroke:#2d3a2e,stroke-width:2px,color:#181a17
    style P2_CAT fill:#e2e8e2,stroke:#2d3a2e,stroke-width:2px,color:#181a17
    style P3_CART fill:#e2e8e2,stroke:#2d3a2e,stroke-width:2px,color:#181a17
    style P4_CHK fill:#c8d8c8,stroke:#2d3a2e,stroke-width:2px,color:#181a17
    style P5_ADM fill:#e2e8e2,stroke:#2d3a2e,stroke-width:2px,color:#181a17
    style P6_ORD fill:#e2e8e2,stroke:#2d3a2e,stroke-width:2px,color:#181a17
    style P7_MSG fill:#e2e8e2,stroke:#2d3a2e,stroke-width:2px,color:#181a17
```

---

## 3. Data Store Mapping

| Data Store | Schema Table | Storage Mechanism | Access Mode | Description |
| :--- | :--- | :--- | :--- | :--- |
| **D1: Customers** | `customers` | MariaDB InnoDB | Read / Write | Stores customer profile records, delivery addresses, and Bcrypt password hashes. |
| **D2: Products** | `products` | MariaDB InnoDB | Read / Write | Stores catalog items, pricing in ZAR, inventory stock quantities, image filenames, and active status. |
| **D3: Categories** | `categories` | MariaDB InnoDB | Read / Write | Stores taxonomy classifications (`Matcha`, `Accessories`, `Gift Sets`). |
| **D4: Orders** | `orders` | MariaDB InnoDB | Read / Write | Stores transactional order headers, immutable shipping address snapshots, and simulated payment methods. |
| **D5: Order Items** | `order_items` | MariaDB InnoDB | Read / Write | Stores purchased line items, quantities, line subtotals, and frozen historical `unit_price`. |
| **D6: Admins** | `admins` | MariaDB InnoDB | Read-Only (Auth) | Stores isolated administrative credentials and Bcrypt password hashes (e.g. local development test fixture `admin@zazenmatcha.test`). |
| **D7: Contact Messages** | `contact_messages` | MariaDB InnoDB | Read / Write | Stores customer feedback and studio inquiries submitted through the contact form. |
| **D8: Session Cart** | `$_SESSION['cart']` | PHP Server RAM / File | Read / Write | Ephemeral session storage holding product IDs and requested quantities prior to checkout. |
