<?php
/**
 * Customer Checkout Page
 * Zazen Matcha & Tea Co.
 *
 * Revalidates the session cart against MySQL, collects customer and delivery
 * details with server-side validation and CSRF protection, and creates the
 * order + order_items + stock updates inside an atomic PDO transaction.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/customer-auth.php';

ensure_session_started();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$checkoutUrl = site_url('customer/checkout.php');
$cartUrl     = site_url('customer/cart.php');
$shopUrl     = site_url('customer/products.php');

// Allowed South African provinces for server-side validation
$allowedProvinces = [
    'Eastern Cape',
    'Free State',
    'Gauteng',
    'KwaZulu-Natal',
    'Limpopo',
    'Mpumalanga',
    'Northern Cape',
    'North West',
    'Western Cape',
];

// Payment methods accepted by the tea studio
$allowedPaymentMethods = [
    'Simulated Card'      => 'Credit / Debit Card (Studio Processing)',
    'Bank Transfer (EFT)' => 'Direct EFT / Bank Transfer',
];

// ----------------------------------------------------------------------------
// 1. Load Logged-In Customer Profile (if authenticated) for Pre-filling
// ----------------------------------------------------------------------------
$loggedInCustomerId = get_logged_in_customer_id();
$loggedInCustomer   = null;

if ($loggedInCustomerId !== null) {
    $customerSql = "
        SELECT
            customer_id,
            first_name,
            last_name,
            email,
            phone,
            address,
            city,
            province,
            postal_code
        FROM customers
        WHERE customer_id = :customer_id
        LIMIT 1
    ";
    $loggedInCustomer = db_fetch_one($pdo, $customerSql, [
        'customer_id' => $loggedInCustomerId,
    ]);
}

// Default form field values (pre-filled from logged-in customer when available)
$formData = [
    'first_name'       => $loggedInCustomer['first_name'] ?? '',
    'last_name'        => $loggedInCustomer['last_name'] ?? '',
    'email'            => $loggedInCustomer['email'] ?? '',
    'phone'            => $loggedInCustomer['phone'] ?? '',
    'shipping_address' => $loggedInCustomer['address'] ?? '',
    'city'             => $loggedInCustomer['city'] ?? '',
    'province'         => $loggedInCustomer['province'] ?? '',
    'postal_code'      => $loggedInCustomer['postal_code'] ?? '',
    'payment_method'   => 'Simulated Card',
];

$formErrors = [];

// ----------------------------------------------------------------------------
// 2. Independently Revalidate Session Cart Against MySQL
// ----------------------------------------------------------------------------
$cartIssues = [];
$cleanCart  = [];

foreach ($_SESSION['cart'] as $rawProductId => $rawQuantity) {
    if (
        ctype_digit((string) $rawProductId)
        && (int) $rawProductId > 0
        && filter_var($rawQuantity, FILTER_VALIDATE_INT) !== false
        && (int) $rawQuantity > 0
    ) {
        $cleanCart[(int) $rawProductId] = (int) $rawQuantity;
    } else {
        $cartIssues[] = 'An invalid item quantity was detected in your cart.';
    }
}

$checkoutItems  = [];
$orderSubtotal  = 0.0;

if (!empty($cleanCart)) {
    $productIds   = array_keys($cleanCart);
    $placeholders = implode(', ', array_fill(0, count($productIds), '?'));

    $productsSql = "
        SELECT
            p.product_id,
            p.category_id,
            p.name,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            c.name AS category_name
        FROM products p
        INNER JOIN categories c ON p.category_id = c.category_id
        WHERE p.product_id IN ({$placeholders})
        ORDER BY p.product_id ASC
    ";

    $stmt = $pdo->prepare($productsSql);
    $stmt->execute(array_values($productIds));
    $dbProducts = $stmt->fetchAll();

    $productsById = [];
    foreach ($dbProducts as $row) {
        $productsById[(int) $row['product_id']] = $row;
    }

    foreach ($cleanCart as $pid => $qty) {
        if (!isset($productsById[$pid])) {
            $cartIssues[] = 'One of the products in your cart could not be found in our catalogue.';
            continue;
        }

        $prod  = $productsById[$pid];
        $price = (float) $prod['price'];
        $stock = (int) $prod['stock_quantity'];

        if ($prod['status'] !== 'active') {
            $cartIssues[] = sprintf('"%s" is no longer active and cannot be ordered.', $prod['name']);
            continue;
        }

        if ($stock <= 0) {
            $cartIssues[] = sprintf('"%s" is currently out of stock.', $prod['name']);
        } elseif ($qty > $stock) {
            $cartIssues[] = sprintf(
                'Requested quantity (%d) for "%s" exceeds available stock (%d).',
                $qty,
                $prod['name'],
                $stock
            );
        }

        $lineTotal = $price * $qty;
        $orderSubtotal += $lineTotal;

        $checkoutItems[] = [
            'product_id'     => $pid,
            'name'           => $prod['name'],
            'category_name'  => $prod['category_name'],
            'image'          => $prod['image'],
            'unit_price'     => $price,
            'quantity'       => $qty,
            'line_total'     => $lineTotal,
            'stock_quantity' => $stock,
        ];
    }
}

$shippingCost = 0.00;
$orderTotal   = $orderSubtotal;
$isCartEmpty  = empty($cleanCart);
$isCartValid  = (!$isCartEmpty && empty($cartIssues) && !empty($checkoutItems));

// ----------------------------------------------------------------------------
// 3. Handle POST Order Submission
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isCartEmpty) {
    // Populate & sanitise submitted form values so valid entries are preserved
    $formData['first_name']       = clean_input($_POST['first_name'] ?? '');
    $formData['last_name']        = clean_input($_POST['last_name'] ?? '');
    $formData['email']            = clean_input($_POST['email'] ?? '');
    $formData['phone']            = clean_input($_POST['phone'] ?? '');
    $formData['shipping_address'] = clean_input($_POST['shipping_address'] ?? '');
    $formData['city']             = clean_input($_POST['city'] ?? '');
    $formData['province']         = clean_input($_POST['province'] ?? '');
    $formData['postal_code']      = clean_input($_POST['postal_code'] ?? '');
    $formData['payment_method']   = clean_input($_POST['payment_method'] ?? '');

    // 3a. Verify CSRF Token
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    if (!verify_csrf_token($submittedToken)) {
        $formErrors['general'] = 'Invalid security token. Please review your details and try again.';
    }

    // 3b. Check initial cart validity
    if (!$isCartValid) {
        $formErrors['general'] = 'Your cart contains items that require attention before an order can be placed.';
    }

    // 3c. Validate Customer & Delivery Fields (Server-Side)
    if ($formData['first_name'] === '') {
        $formErrors['first_name'] = 'First name is required.';
    } elseif (mb_strlen($formData['first_name']) > 100) {
        $formErrors['first_name'] = 'First name must not exceed 100 characters.';
    }

    if ($formData['last_name'] === '') {
        $formErrors['last_name'] = 'Last name is required.';
    } elseif (mb_strlen($formData['last_name']) > 100) {
        $formErrors['last_name'] = 'Last name must not exceed 100 characters.';
    }

    if ($formData['email'] === '') {
        $formErrors['email'] = 'Email address is required.';
    } elseif (mb_strlen($formData['email']) > 150 || filter_var($formData['email'], FILTER_VALIDATE_EMAIL) === false) {
        $formErrors['email'] = 'Please enter a valid email address.';
    }

    if ($formData['phone'] === '') {
        $formErrors['phone'] = 'Contact phone number is required.';
    } elseif (mb_strlen($formData['phone']) > 30 || !preg_match('/^[0-9+\-\s()]{7,30}$/', $formData['phone'])) {
        $formErrors['phone'] = 'Please enter a valid phone number (7 to 30 digits).';
    }

    if ($formData['shipping_address'] === '') {
        $formErrors['shipping_address'] = 'Street shipping address is required.';
    } elseif (mb_strlen($formData['shipping_address']) < 5 || mb_strlen($formData['shipping_address']) > 255) {
        $formErrors['shipping_address'] = 'Shipping address must be between 5 and 255 characters.';
    }

    if ($formData['city'] === '') {
        $formErrors['city'] = 'City or town is required.';
    } elseif (mb_strlen($formData['city']) > 100) {
        $formErrors['city'] = 'City must not exceed 100 characters.';
    }

    if ($formData['province'] === '') {
        $formErrors['province'] = 'Please select a province.';
    } elseif (!in_array($formData['province'], $allowedProvinces, true)) {
        $formErrors['province'] = 'Please select a valid South African province.';
    }

    if ($formData['postal_code'] === '') {
        $formErrors['postal_code'] = 'Postal code is required.';
    } elseif (!preg_match('/^[0-9A-Za-z\-\s]{3,20}$/', $formData['postal_code'])) {
        $formErrors['postal_code'] = 'Please enter a valid postal code.';
    }

    if (!array_key_exists($formData['payment_method'], $allowedPaymentMethods)) {
        $formErrors['payment_method'] = 'Please select a valid payment method.';
    }

    // ------------------------------------------------------------------------
    // 4. Atomic Order Creation Transaction (if validation passed)
    // ------------------------------------------------------------------------
    if (empty($formErrors)) {
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

            $verifiedSubtotal = round($verifiedSubtotal, 2);
            $verifiedShipping = 0.00;
            $verifiedTotal    = $verifiedSubtotal;

            // 4c. Resolve or create customer record (orders.customer_id is NOT NULL FK)
            $orderCustomerId = null;

            if ($loggedInCustomer !== null) {
                $orderCustomerId = (int) $loggedInCustomer['customer_id'];

                $updateCustomerSql = "
                    UPDATE customers
                    SET first_name  = :first_name,
                        last_name   = :last_name,
                        phone       = :phone,
                        address     = :address,
                        city        = :city,
                        province    = :province,
                        postal_code = :postal_code
                    WHERE customer_id = :customer_id
                ";
                $updateCustStmt = $pdo->prepare($updateCustomerSql);
                $updateCustStmt->execute([
                    'first_name'  => $formData['first_name'],
                    'last_name'   => $formData['last_name'],
                    'phone'       => $formData['phone'],
                    'address'     => $formData['shipping_address'],
                    'city'        => $formData['city'],
                    'province'    => $formData['province'],
                    'postal_code' => $formData['postal_code'],
                    'customer_id' => $orderCustomerId,
                ]);
            } else {
                // Check if a customer record with this email already exists
                $existingCustomer = db_fetch_one(
                    $pdo,
                    'SELECT customer_id, password FROM customers WHERE email = :email LIMIT 1 FOR UPDATE',
                    ['email' => $formData['email']]
                );

                if ($existingCustomer !== null) {
                    $orderCustomerId  = (int) $existingCustomer['customer_id'];
                    $existingPassword = trim((string) ($existingCustomer['password'] ?? ''));

                    // Only update saved profile details if the existing record is an
                    // unregistered guest row (empty password). Do not overwrite a
                    // registered customer's saved profile during guest checkout.
                    if ($existingPassword === '') {
                        $updateGuestSql = "
                            UPDATE customers
                            SET first_name  = :first_name,
                                last_name   = :last_name,
                                phone       = :phone,
                                address     = :address,
                                city        = :city,
                                province    = :province,
                                postal_code = :postal_code
                            WHERE customer_id = :customer_id
                        ";
                        $updateGuestStmt = $pdo->prepare($updateGuestSql);
                        $updateGuestStmt->execute([
                            'first_name'  => $formData['first_name'],
                            'last_name'   => $formData['last_name'],
                            'phone'       => $formData['phone'],
                            'address'     => $formData['shipping_address'],
                            'city'        => $formData['city'],
                            'province'    => $formData['province'],
                            'postal_code' => $formData['postal_code'],
                            'customer_id' => $orderCustomerId,
                        ]);
                    }
                } else {
                    // Create a customer record for guest checkout using a random bcrypt password hash
                    $insertCustomerSql = "
                        INSERT INTO customers (
                            first_name,
                            last_name,
                            email,
                            phone,
                            password,
                            address,
                            city,
                            province,
                            postal_code
                        ) VALUES (
                            :first_name,
                            :last_name,
                            :email,
                            :phone,
                            :password,
                            :address,
                            :city,
                            :province,
                            :postal_code
                        )
                    ";
                    $insertCustStmt = $pdo->prepare($insertCustomerSql);
                    $insertCustStmt->execute([
                        'first_name'  => $formData['first_name'],
                        'last_name'   => $formData['last_name'],
                        'email'       => $formData['email'],
                        'phone'       => $formData['phone'],
                        'password'    => hash_password(bin2hex(random_bytes(16))),
                        'address'     => $formData['shipping_address'],
                        'city'        => $formData['city'],
                        'province'    => $formData['province'],
                        'postal_code' => $formData['postal_code'],
                    ]);

                    $orderCustomerId = (int) $pdo->lastInsertId();
                }
            }

            // 4d. Insert into orders table
            $insertOrderSql = "
                INSERT INTO orders (
                    customer_id,
                    order_date,
                    shipping_address,
                    city,
                    province,
                    postal_code,
                    subtotal,
                    shipping_cost,
                    total,
                    payment_method,
                    payment_status,
                    order_status
                ) VALUES (
                    :customer_id,
                    NOW(),
                    :shipping_address,
                    :city,
                    :province,
                    :postal_code,
                    :subtotal,
                    :shipping_cost,
                    :total,
                    :payment_method,
                    :payment_status,
                    :order_status
                )
            ";
            $orderStmt = $pdo->prepare($insertOrderSql);
            $orderStmt->execute([
                'customer_id'      => $orderCustomerId,
                'shipping_address' => $formData['shipping_address'],
                'city'             => $formData['city'],
                'province'         => $formData['province'],
                'postal_code'      => $formData['postal_code'],
                'subtotal'         => $verifiedSubtotal,
                'shipping_cost'    => $verifiedShipping,
                'total'            => $verifiedTotal,
                'payment_method'   => $formData['payment_method'],
                'payment_status'   => 'pending',
                'order_status'     => 'pending',
            ]);

            $newOrderId = (int) $pdo->lastInsertId();
            if ($newOrderId <= 0) {
                throw new RuntimeException('Failed to generate a valid order ID.');
            }

            // 4e. Insert each item into order_items & deduct stock from products
            $insertItemSql = "
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    quantity,
                    unit_price,
                    subtotal
                ) VALUES (
                    :order_id,
                    :product_id,
                    :quantity,
                    :unit_price,
                    :subtotal
                )
            ";
            $itemStmt = $pdo->prepare($insertItemSql);

            $deductStockSql = "
                UPDATE products
                SET stock_quantity = stock_quantity - :deduct_qty
                WHERE product_id = :product_id
                  AND status = 'active'
                  AND stock_quantity >= :min_qty
            ";
            $stockStmt = $pdo->prepare($deductStockSql);

            foreach ($verifiedItems as $vItem) {
                $itemStmt->execute([
                    'order_id'   => $newOrderId,
                    'product_id' => $vItem['product_id'],
                    'quantity'   => $vItem['quantity'],
                    'unit_price' => $vItem['unit_price'],
                    'subtotal'   => $vItem['subtotal'],
                ]);

                $stockStmt->execute([
                    'deduct_qty' => $vItem['quantity'],
                    'product_id' => $vItem['product_id'],
                    'min_qty'    => $vItem['quantity'],
                ]);

                if ($stockStmt->rowCount() !== 1) {
                    throw new RuntimeException('STOCK_ERROR:Stock update failed due to concurrent inventory change.');
                }
            }

            // 4f. Commit transaction
            $pdo->commit();

            // 4g. Clear session cart ONLY after transaction commit succeeds
            $_SESSION['cart']          = [];
            $_SESSION['last_order_id'] = $newOrderId;

            set_flash_message('success', 'Your order #' . $newOrderId . ' has been placed successfully.');
            redirect(site_url('customer/order-confirmation.php?id=' . $newOrderId));
        } catch (Throwable $txException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $msg = $txException->getMessage();

            // If stock changed after page load, inform the customer and return them to Cart
            if (strpos($msg, 'STOCK_ERROR:') === 0) {
                $friendlyStockMsg = substr($msg, strlen('STOCK_ERROR:'));
                set_flash_message('error', $friendlyStockMsg . ' Please review your cart.');
                redirect($cartUrl);
            }

            // Log technical details server-side without exposing SQL/stack traces to the user
            error_log('Checkout Order Creation Error: ' . $msg);
            $formErrors['general'] = 'We could not process your order right now. Your cart has been kept intact—please try again.';
        }
    }
}

$pageTitle = 'Checkout';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Checkout Page Header -->
<section class="checkout-hero" aria-labelledby="checkout-heading">
    <div class="container">
        <div class="checkout-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Order Completion</span>
                <h1 id="checkout-heading" class="checkout-title">Checkout</h1>
                <p class="checkout-subtitle">
                    Review your order and complete your details before placing it.
                </p>
            </div>
            <a href="<?php echo e($cartUrl); ?>" class="editorial-link checkout-back-link">
                &larr; Return to Cart
            </a>
        </div>
    </div>
</section>

<!-- Main Checkout Content -->
<section class="section checkout-section" aria-label="Checkout details and order summary">
    <div class="container">
        <?php if ($isCartEmpty): ?>
            <!-- 1. Empty Cart State -->
            <div class="empty-state checkout-empty-state">
                <span class="eyebrow">No Items to Checkout</span>
                <h2 class="empty-state-title">Your cart is empty.</h2>
                <p class="empty-state-text">
                    Add matcha powders, ceramic vessels, or gift sets to your cart before proceeding to checkout.
                </p>
                <div class="empty-state-actions">
                    <a href="<?php echo e($shopUrl); ?>" class="btn btn-primary">
                        Return to Shop
                    </a>
                </div>
            </div>
        <?php elseif (!$isCartValid): ?>
            <!-- 2. Invalid Cart State (stock or product availability changed) -->
            <div class="cart-warnings checkout-cart-warnings" role="alert">
                <p class="cart-warnings-title">Your cart requires attention before checkout:</p>
                <ul class="cart-warnings-list">
                    <?php foreach ($cartIssues as $issue): ?>
                        <li><?php echo e($issue); ?></li>
                    <?php endforeach; ?>
                </ul>
                <div class="checkout-warning-actions">
                    <a href="<?php echo e($cartUrl); ?>" class="btn btn-outline">
                        &larr; Return to Cart to Resolve
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($formErrors)): ?>
                <div class="cart-warnings checkout-form-alert" role="alert">
                    <p class="cart-warnings-title">Please correct the highlighted fields below:</p>
                    <?php if (!empty($formErrors['general'])): ?>
                        <p><?php echo e($formErrors['general']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo e($checkoutUrl); ?>" method="post" class="checkout-layout" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                <!-- Left Column: Customer & Delivery Details -->
                <div class="checkout-form-column">
                    <div class="checkout-card">
                        <h2 class="checkout-section-heading">01 / Contact Information</h2>
                        <div class="checkout-field-grid">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    maxlength="100"
                                    value="<?php echo e($formData['first_name']); ?>"
                                    required
                                    autocomplete="given-name"
                                    <?php echo isset($formErrors['first_name']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                <?php if (isset($formErrors['first_name'])): ?>
                                    <span class="form-error"><?php echo e($formErrors['first_name']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    maxlength="100"
                                    value="<?php echo e($formData['last_name']); ?>"
                                    required
                                    autocomplete="family-name"
                                    <?php echo isset($formErrors['last_name']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                <?php if (isset($formErrors['last_name'])): ?>
                                    <span class="form-error"><?php echo e($formErrors['last_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="checkout-field-grid">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    maxlength="150"
                                    value="<?php echo e($formData['email']); ?>"
                                    required
                                    autocomplete="email"
                                    <?php echo isset($formErrors['email']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                <?php if (isset($formErrors['email'])): ?>
                                    <span class="form-error"><?php echo e($formErrors['email']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    maxlength="30"
                                    placeholder="e.g. 082 123 4567"
                                    value="<?php echo e($formData['phone']); ?>"
                                    required
                                    autocomplete="tel"
                                    <?php echo isset($formErrors['phone']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                <?php if (isset($formErrors['phone'])): ?>
                                    <span class="form-error"><?php echo e($formErrors['phone']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-card">
                        <h2 class="checkout-section-heading">02 / Delivery Details</h2>

                        <div class="form-group">
                            <label for="shipping_address" class="form-label">Street Address *</label>
                            <input
                                type="text"
                                id="shipping_address"
                                name="shipping_address"
                                maxlength="255"
                                placeholder="Street number, street name, apartment or suite"
                                value="<?php echo e($formData['shipping_address']); ?>"
                                required
                                autocomplete="street-address"
                                <?php echo isset($formErrors['shipping_address']) ? 'aria-invalid="true"' : ''; ?>
                            >
                            <?php if (isset($formErrors['shipping_address'])): ?>
                                <span class="form-error"><?php echo e($formErrors['shipping_address']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="checkout-field-grid checkout-address-grid">
                            <div class="form-group">
                                <label for="city" class="form-label">City / Town *</label>
                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    maxlength="100"
                                    value="<?php echo e($formData['city']); ?>"
                                    required
                                    autocomplete="address-level2"
                                    <?php echo isset($formErrors['city']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                <?php if (isset($formErrors['city'])): ?>
                                    <span class="form-error"><?php echo e($formErrors['city']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="province" class="form-label">Province *</label>
                                <select
                                    id="province"
                                    name="province"
                                    required
                                    <?php echo isset($formErrors['province']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                    <option value="">Select Province</option>
                                    <?php foreach ($allowedProvinces as $prov): ?>
                                        <option
                                            value="<?php echo e($prov); ?>"
                                            <?php echo $formData['province'] === $prov ? 'selected' : ''; ?>
                                        >
                                            <?php echo e($prov); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($formErrors['province'])): ?>
                                    <span class="form-error"><?php echo e($formErrors['province']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="postal_code" class="form-label">Postal Code *</label>
                                <input
                                    type="text"
                                    id="postal_code"
                                    name="postal_code"
                                    maxlength="20"
                                    placeholder="e.g. 8001"
                                    value="<?php echo e($formData['postal_code']); ?>"
                                    required
                                    autocomplete="postal-code"
                                    <?php echo isset($formErrors['postal_code']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                <?php if (isset($formErrors['postal_code'])): ?>
                                    <span class="form-error"><?php echo e($formErrors['postal_code']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-card">
                        <h2 class="checkout-section-heading">03 / Payment Method</h2>
                        <p class="checkout-payment-note">
                            Select your preferred payment method to finalize your order.
                        </p>

                        <div class="payment-options" role="radiogroup" aria-label="Payment method">
                            <?php foreach ($allowedPaymentMethods as $methodKey => $methodLabel): ?>
                                <label class="payment-option-card">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="<?php echo e($methodKey); ?>"
                                        <?php echo $formData['payment_method'] === $methodKey ? 'checked' : ''; ?>
                                    >
                                    <span class="payment-option-label"><?php echo e($methodLabel); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php if (isset($formErrors['payment_method'])): ?>
                            <span class="form-error"><?php echo e($formErrors['payment_method']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: Your Order Summary -->
                <aside class="checkout-summary-column" aria-labelledby="checkout-order-heading">
                    <div class="checkout-summary-card">
                        <h2 id="checkout-order-heading" class="cart-summary-title">Your Order</h2>

                        <ul class="checkout-item-list">
                            <?php foreach ($checkoutItems as $item): ?>
                                <?php $imgUrl = product_image_url($item['image']); ?>
                                <li class="checkout-item-row">
                                    <div class="checkout-item-media">
                                        <img
                                            src="<?php echo e($imgUrl); ?>"
                                            alt="<?php echo e($item['name']); ?>"
                                            width="90"
                                            height="110"
                                            loading="lazy"
                                        >
                                    </div>
                                    <div class="checkout-item-info">
                                        <p class="checkout-item-name"><?php echo e($item['name']); ?></p>
                                        <p class="checkout-item-meta">
                                            Qty: <?php echo (int) $item['quantity']; ?> &times; <?php echo e(format_price($item['unit_price'])); ?>
                                        </p>
                                    </div>
                                    <div class="checkout-item-total">
                                        <?php echo e(format_price($item['line_total'])); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <dl class="cart-summary-rows checkout-totals">
                            <div class="cart-summary-row">
                                <dt>Subtotal</dt>
                                <dd><?php echo e(format_price($orderSubtotal)); ?></dd>
                            </div>
                            <div class="cart-summary-row cart-summary-row-muted">
                                <dt>Shipping</dt>
                                <dd><?php echo e(format_price($shippingCost)); ?></dd>
                            </div>
                            <div class="cart-summary-row cart-summary-total">
                                <dt>Total</dt>
                                <dd><?php echo e(format_price($orderTotal)); ?></dd>
                            </div>
                        </dl>

                        <div class="cart-summary-actions">
                            <button type="submit" class="btn btn-primary checkout-submit-btn">
                                Place Order
                            </button>
                            <a href="<?php echo e($cartUrl); ?>" class="btn btn-outline checkout-return-cart-btn">
                                Return to Cart
                            </a>
                        </div>
                    </div>
                </aside>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
