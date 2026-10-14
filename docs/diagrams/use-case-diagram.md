# UML Use Case Diagram & System Specifications
**Project:** Zazen Matcha & Tea Co. — e-Commerce Platform  
**Course:** Eduvos ITECA3-34 Web Development and e-Commerce Technologies (Deliverable 2)  
**Document:** System Design Documentation — Use Case Model  
**Source Baseline:** `C:\xampp\htdocs\ZAZEN-MATCHA`

---

## 1. Actor Hierarchy & System Scope

The Use Case Model articulates the functional interactions supported by the Zazen Matcha e-Commerce platform across three primary human actors:

```mermaid
classDiagram
    class GuestVisitor {
        <<Actor>>
        Unauthenticated Shopper
    }
    class RegisteredCustomer {
        <<Actor>>
        Authenticated Account Holder
    }
    class Administrator {
        <<Actor>>
        Store Manager / Fulfillment Admin
    }

    GuestVisitor <|-- RegisteredCustomer : Generalizes / Inherits
```

* **Guest Visitor:** An unauthenticated shopper who can freely explore the storefront, browse and filter products, manage a session shopping cart, submit contact inquiries, or proceed through guest checkout without prior registration.
* **Registered Customer:** An authenticated account holder who inherits all visitor capabilities and gains access to saved profile maintenance, pre-filled checkout, password management, and personal order history.
* **Administrator:** An authorized manager who authenticates via an independent portal to supervise sales metrics, oversee the product and category catalog, inspect customer orders, and view database diagnostics.

---

## 2. Visual UML Use Case Diagram (Mermaid)

```mermaid
flowchart LR
    %% Actors
    GUEST["👤 Guest Visitor"]
    CUST["👤 Registered Customer"]
    ADMIN["👤 Administrator"]

    %% System Boundary
    subgraph ZazenPlatform ["Zazen Matcha e-Commerce Platform Boundary"]
        %% Storefront & Discovery
        UC01(["UC-01: Browse Storefront & Story"])
        UC02(["UC-02: Browse & Search Catalog"])
        UC03(["UC-03: View Product Details & Gallery"])
        UC04(["UC-04: Manage Shopping Bag"])

        %% Checkout & Orders
        UC05(["UC-05: Guest Checkout"])
        UC06(["UC-06: Registered Checkout"])
        UC07(["UC-07: View Order Receipt"])
        UC_TX[["«include»\nAtomic Stock Deduction\n& Order Transaction"]]
        UC_CSRF[["«include»\nCSRF Token Validation"]]

        %% Customer Account
        UC08(["UC-08: Register Customer Account"])
        UC09(["UC-09: Authenticate / Login"])
        UC10(["UC-10: Manage Account & Address"])
        UC11(["UC-11: Change Password"])
        UC12(["UC-12: View Personal Order History"])
        UC13(["UC-13: Submit Studio Inquiry"])

        %% Administrator Use Cases
        UC14(["UC-14: Administrator Login"])
        UC15(["UC-15: View KPI Analytics Dashboard"])
        UC16(["UC-16: Manage Products (CRUD)"])
        UC17(["UC-17: Manage Categories (CRUD)"])
        UC18(["UC-18: View Orders & Line Items"])
        UC19(["UC-19: Oversee Customer Directory"])
        UC20(["UC-20: Inspect System Settings"])

        %% Proposed / Future Enhancements (Clearly Differentiated)
        subgraph ProposedFeatures ["«Proposed / Future Enhancements»"]
            UCP1(["UC-P1: Update Fulfillment Status"])
            UCP2(["UC-P2: Online Payment Gateway API"])
            UCP3(["UC-P3: Automated Email Invoices"])
        end
    end

    %% Guest Interactions
    GUEST --> UC01
    GUEST --> UC02
    GUEST --> UC03
    GUEST --> UC04
    GUEST --> UC05
    GUEST --> UC08
    GUEST --> UC13

    %% Customer Inherited & Specific Interactions
    CUST -.->|inherits| GUEST
    CUST --> UC06
    CUST --> UC07
    CUST --> UC09
    CUST --> UC10
    CUST --> UC11
    CUST --> UC12

    %% Administrator Interactions
    ADMIN --> UC14
    ADMIN --> UC15
    ADMIN --> UC16
    ADMIN --> UC17
    ADMIN --> UC18
    ADMIN --> UC19
    ADMIN --> UC20

    %% Include Relationships
    UC05 -.->|«include»| UC_TX
    UC05 -.->|«include»| UC_CSRF
    UC06 -.->|«include»| UC_TX
    UC06 -.->|«include»| UC_CSRF
    UC08 -.->|«include»| UC_CSRF
    UC10 -.->|«include»| UC_CSRF
    UC11 -.->|«include»| UC_CSRF
    UC16 -.->|«include»| UC_CSRF
    UC17 -.->|«include»| UC_CSRF

    %% Proposed Linkages
    ADMIN -.->|«proposed»| UCP1
    UC06 -.->|«proposed»| UCP2
    UC_TX -.->|«proposed»| UCP3

    %% Styling
    style ZazenPlatform fill:#faf9f7,stroke:#2d3a2e,stroke-width:2px
    style ProposedFeatures fill:#f4f1ea,stroke:#8c7355,stroke-dasharray: 5 5,stroke-width:1px
    style UC_TX fill:#dce6dc,stroke:#2d3a2e,stroke-width:1px
    style UC_CSRF fill:#dce6dc,stroke:#2d3a2e,stroke-width:1px
```

---

## 3. Use Case Specifications & Functional Status

### 3.1 Implemented Storefront & Customer Use Cases

| ID | Use Case Name | Primary Actor | Implementation Path | Verification Status |
| :--- | :--- | :--- | :--- | :--- |
| **UC-01** | Browse Storefront & Story | Guest / Customer | `index.php`, `customer/about.php` | **VERIFIED (Live HTTP 200)** |
| **UC-02** | Browse & Search Catalog | Guest / Customer | `customer/products.php` | **VERIFIED (Live HTTP 200)** |
| **UC-03** | View Product Details | Guest / Customer | `customer/product.php` | **VERIFIED (Source Audit)** |
| **UC-04** | Manage Shopping Bag | Guest / Customer | `customer/cart.php` | **VERIFIED (Source Audit)** |
| **UC-05** | Guest Checkout | Guest | `customer/checkout.php` | **VERIFIED (Source Audit)** |
| **UC-06** | Registered Checkout | Customer | `customer/checkout.php` | **VERIFIED (Source Audit)** |
| **UC-07** | View Order Receipt | Guest / Customer | `customer/order-confirmation.php` | **VERIFIED (Source Audit)** |
| **UC-08** | Register Account | Guest | `customer/register.php` | **VERIFIED (Live HTTP 200)** |
| **UC-09** | Authenticate / Login | Customer | `customer/login.php` | **VERIFIED (Live HTTP 200)** |
| **UC-10** | Manage Profile & Address | Customer | `customer/account.php` | **VERIFIED (Source Audit)** |
| **UC-11** | Change Password | Customer | `customer/account.php` | **VERIFIED (Source Audit)** |
| **UC-12** | View Personal Order History | Customer | `customer/account.php` | **VERIFIED (Source Audit)** |
| **UC-13** | Submit Studio Inquiry | Guest / Customer | `customer/contact.php` | **VERIFIED (Live HTTP 200)** |

### 3.2 Implemented Administrator Use Cases

| ID | Use Case Name | Primary Actor | Implementation Path | Verification Status |
| :--- | :--- | :--- | :--- | :--- |
| **UC-14** | Administrator Login | Administrator | `admin/login.php` | **VERIFIED (Live HTTP 200)** |
| **UC-15** | View KPI Analytics Dashboard | Administrator | `admin/dashboard.php` | **VERIFIED (Source Audit)** |
| **UC-16** | Manage Products (CRUD) | Administrator | `admin/products.php`, `product-add.php`, `product-edit.php` | **VERIFIED (Source Audit)** |
| **UC-17** | Manage Categories (CRUD) | Administrator | `admin/categories.php`, `category-add.php`, `category-edit.php` | **VERIFIED (Source Audit)** |
| **UC-18** | View Orders & Line Items | Administrator | `admin/orders.php`, `order-details.php` | **VERIFIED (Source Audit)** |
| **UC-19** | Oversee Customer Directory | Administrator | `admin/customers.php`, `customer-details.php` | **VERIFIED (Source Audit)** |
| **UC-20** | Inspect System Settings | Administrator | `admin/settings.php` | **VERIFIED (Source Audit)** |

### 3.3 Proposed / Future Enhancements (Distinct from Current Baseline)

| ID | Proposed Use Case | Target Actor | Architectural Description | Reason Not in Current Baseline |
| :--- | :--- | :--- | :--- | :--- |
| **UC-P1** | Update Order Fulfillment Status | Administrator | Allow administrators to update `orders.order_status` (`pending` $\to$ `processing` $\to$ `shipped` $\to$ `delivered`) and `orders.payment_status` (`pending` $\to$ `paid`). | Currently `admin/order-details.php` is strictly read-only; identified as the highest priority implementation advancement. |
| **UC-P2** | Online Payment Gateway | Customer | Connect checkout to an external PCI-compliant payment gateway (e.g. PayFast or Stripe). | Current prototype relies exclusively on simulated card and manual EFT instructions per academic assignment scope. |
| **UC-P3** | Automated Email Invoices | Customer / Admin | Trigger transactional SMTP dispatch of HTML order receipts and status alerts. | Current prototype displays web confirmations via `order-confirmation.php` without active local SMTP mailer configuration. |
