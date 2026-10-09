<?php
/**
 * Customer Order Confirmation Page
 * Zazen Matcha & Tea Co.
 *
 * Read-only confirmation screen displayed after successful checkout.
 * Validates the order ID server-side, enforces session/customer access safety,
 * and loads the order header, customer delivery details, and historical
 * order items from MySQL using PDO prepared statements.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/customer-auth.php';

ensure_session_started();

$shopUrl = site_url('customer/products.php');
$homeUrl = site_url('index.php');

// ----------------------------------------------------------------------------
// 1. Validate Order ID from $_GET['id'] (Positive Integer Only)
// ----------------------------------------------------------------------------
$rawOrderId = $_GET['id'] ?? '';
$orderId    = (!is_array($rawOrderId) && ctype_digit(trim((string) $rawOrderId)) && (int) $rawOrderId > 0)
    ? (int) trim((string) $rawOrderId)
    : 0;

$order        = null;
$orderItems   = [];
$dbError      = null;
$orderFound   = false;
$isAuthorized = false;

// Friendly presentation label maps for payment_status and order_status
$paymentStatusLabels = [
    'pending' => 'Pending',
    'paid'    => 'Paid',
    'failed'  => 'Failed',
];

$orderStatusLabels = [
    'pending'    => 'Pending',
    'processing' => 'Processing',
    'shipped'    => 'Shipped',
    'delivered'  => 'Delivered',
    'cancelled'  => 'Cancelled',
];

// ----------------------------------------------------------------------------
// 2. Load Order & Historical Order Items from MySQL
// ----------------------------------------------------------------------------
if ($orderId > 0) {
    try {
        $orderSql = "
            SELECT
                o.order_id,
                o.customer_id,
                o.order_date,
                o.shipping_address,
                o.city,
                o.province,
                o.postal_code,
                o.subtotal,
                o.shipping_cost,
                o.total,
                o.payment_method,
                o.payment_status,
                o.order_status,
                c.first_name,
                c.last_name,
                c.email,
                c.phone
            FROM orders o
            LEFT JOIN customers c ON c.customer_id = o.customer_id
            WHERE o.order_id = :order_id
            LIMIT 1
        ";

        $fetchedOrder = db_fetch_one($pdo, $orderSql, [
            'order_id' => $orderId,
        ]);

        if ($fetchedOrder !== null) {
            // ----------------------------------------------------------------
            // 3. Enforce Access & Ownership Safety (Deny by Default)
            // ----------------------------------------------------------------
            $loggedInCustomerId = get_logged_in_customer_id();
            $sessionLastOrderId = isset($_SESSION['last_order_id']) ? (int) $_SESSION['last_order_id'] : null;

            $isAuthorized      = false;
            $fetchedOrderId    = (int) ($fetchedOrder['order_id'] ?? 0);
            $fetchedCustomerId = (int) ($fetchedOrder['customer_id'] ?? 0);

            if ($loggedInCustomerId !== null && $loggedInCustomerId > 0) {
                // Condition 1: Logged-in customer may only view an order they own
                $isAuthorized = ($fetchedOrderId === $orderId && $fetchedCustomerId === $loggedInCustomerId);
            } elseif ($sessionLastOrderId !== null && $sessionLastOrderId > 0) {
                // Condition 2: Guest checkout confirmation flow with matching last_order_id
                $isAuthorized = ($fetchedOrderId === $orderId && $sessionLastOrderId === $orderId);
            }

            if ($isAuthorized) {
                $order      = $fetchedOrder;
                $orderFound = true;

                // Load historical order items (using order_items.unit_price, NOT products.price)
                $itemsSql = "
                    SELECT
                        oi.order_item_id,
                        oi.order_id,
                        oi.product_id,
                        oi.quantity,
                        oi.unit_price,
                        oi.subtotal,
                        p.name AS product_name,
                        p.image AS product_image,
                        p.status AS product_status,
                        cat.name AS category_name
                    FROM order_items oi
                    LEFT JOIN products p ON p.product_id = oi.product_id
                    LEFT JOIN categories cat ON cat.category_id = p.category_id
                    WHERE oi.order_id = :order_id
                    ORDER BY oi.order_item_id ASC
                ";

                $orderItems = db_fetch_all($pdo, $itemsSql, [
                    'order_id' => $orderId,
                ]);
            }
        }
    } catch (Throwable $exception) {
        error_log('Order Confirmation Database Error: ' . $exception->getMessage());
        $dbError    = 'We encountered a temporary problem loading your order confirmation. Please refresh the page or try again shortly.';
        $order      = null;
        $orderItems = [];
        $orderFound = false;
    }
}

// Prepare formatted display values when a valid order is loaded
$formattedDate      = '';
$recipientName      = '';
$paymentStatusKey   = '';
$paymentStatusLabel = '';
$orderStatusKey     = '';
$orderStatusLabel   = '';

if ($orderFound && $order !== null) {
    $rawDate   = (string) ($order['order_date'] ?? '');
    $timestamp = $rawDate !== '' ? strtotime($rawDate) : false;
    $formattedDate = $timestamp !== false
        ? date('j F Y, H:i', $timestamp)
        : $rawDate;

    $firstName     = trim((string) ($order['first_name'] ?? ''));
    $lastName      = trim((string) ($order['last_name'] ?? ''));
    $recipientName = trim($firstName . ' ' . $lastName);

    $paymentStatusKey   = strtolower(trim((string) ($order['payment_status'] ?? 'pending')));
    $paymentStatusLabel = $paymentStatusLabels[$paymentStatusKey] ?? ucfirst($paymentStatusKey);

    $orderStatusKey     = strtolower(trim((string) ($order['order_status'] ?? 'pending')));
    $orderStatusLabel   = $orderStatusLabels[$orderStatusKey] ?? ucfirst($orderStatusKey);
}

$pageTitle = $orderFound
    ? 'Order #' . (int) $order['order_id'] . ' Confirmed'
    : 'Order Confirmation';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($dbError !== null): ?>
    <!-- Database Failure State -->
    <section class="confirmation-hero" aria-labelledby="confirmation-error-heading">
        <div class="container">
            <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Order Record</span>
            <h1 id="confirmation-error-heading" class="confirmation-title">Unable to Load Order</h1>
        </div>
    </section>

    <section class="section confirmation-section" aria-label="Order error notice">
        <div class="container">
            <div class="empty-state confirmation-empty-state" role="alert">
                <span class="eyebrow">Temporary Service Issue</span>
                <h2 class="empty-state-title">We Could Not Retrieve This Order</h2>
                <p class="empty-state-text">
                    <?php echo e($dbError); ?>
                </p>
                <div class="confirmation-empty-actions">
                    <a href="<?php echo e($shopUrl); ?>" class="btn btn-primary">
                        Continue Shopping
                    </a>
                    <a href="<?php echo e($homeUrl); ?>" class="btn btn-secondary">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php elseif (!$orderFound || $order === null): ?>
    <!-- Invalid ID or Nonexistent / Unauthorized Order State -->
    <section class="confirmation-hero" aria-labelledby="order-not-found-heading">
        <div class="container">
            <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Order Lookup</span>
            <h1 id="order-not-found-heading" class="confirmation-title">Order Not Found</h1>
            <p class="confirmation-subtitle">
                We could not locate an order matching the reference provided.
            </p>
        </div>
    </section>

    <section class="section confirmation-section" aria-label="Order not found">
        <div class="container">
            <div class="empty-state confirmation-empty-state">
                <span class="eyebrow">Order Reference Unavailable</span>
                <h2 class="empty-state-title">Order Not Found</h2>
                <p class="empty-state-text">
                    The order reference may be invalid, expired, or no longer accessible from this session.
                    Explore our ceremonial matcha and handcrafted tea ware collection below.
                </p>
                <div class="confirmation-empty-actions">
                    <a href="<?php echo e($shopUrl); ?>" class="btn btn-primary">
                        Continue Shopping
                    </a>
                    <a href="<?php echo e($homeUrl); ?>" class="btn btn-secondary">
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php else: ?>
    <!-- Valid Order Confirmation Hero -->
    <section class="confirmation-hero" aria-labelledby="confirmation-heading">
        <div class="container">
            <div class="confirmation-hero-inner">
                <div class="confirmation-hero-copy">
                    <div class="confirmation-mark" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 6L9 17l-5-5"></path>
                        </svg>
                    </div>
                    <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Order Confirmation</span>
                    <h1 id="confirmation-heading" class="confirmation-title">
                        Thank you. Your order is confirmed.
                    </h1>
                    <p class="confirmation-subtitle">
                        We have recorded your order and reserved your selection in our Cape Town studio.
                        A summary of your items, delivery details, and current status is below.
                    </p>
                </div>

                <!-- Order Reference & Status Strip -->
                <dl class="confirmation-meta-bar" aria-label="Order reference details">
                    <div class="confirmation-meta-item">
                        <dt>Order Number</dt>
                        <dd class="confirmation-order-number">#<?php echo (int) $order['order_id']; ?></dd>
                    </div>
                    <div class="confirmation-meta-item">
                        <dt>Order Date</dt>
                        <dd><?php echo e($formattedDate); ?></dd>
                    </div>
                    <div class="confirmation-meta-item">
                        <dt>Payment Status</dt>
                        <dd>
                            <span class="status-pill status-pill-<?php echo e($paymentStatusKey); ?>">
                                <?php echo e($paymentStatusLabel); ?>
                            </span>
                        </dd>
                    </div>
                    <div class="confirmation-meta-item">
                        <dt>Order Status</dt>
                        <dd>
                            <span class="status-pill status-pill-<?php echo e($orderStatusKey); ?>">
                                <?php echo e($orderStatusLabel); ?>
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <!-- Main Confirmation Content -->
    <section class="section confirmation-section" aria-label="Confirmed order details">
        <div class="container">
            <div class="confirmation-layout">
                <!-- Left Column: Ordered Items & Delivery / Payment Details -->
                <div class="confirmation-main-column">
                    <!-- 1. Ordered Items Card -->
                    <div class="checkout-card confirmation-card">
                        <div class="confirmation-card-header">
                            <h2 class="checkout-section-heading">Ordered Items</h2>
                            <span class="confirmation-item-count">
                                <?php
                                $totalUnits = 0;
                                foreach ($orderItems as $oi) {
                                    $totalUnits += (int) ($oi['quantity'] ?? 0);
                                }
                                echo (int) $totalUnits . ' ' . ($totalUnits === 1 ? 'item' : 'items');
                                ?>
                            </span>
                        </div>

                        <?php if (empty($orderItems)): ?>
                            <div class="confirmation-no-items" role="status">
                                <p>No individual line items were found for this order record.</p>
                            </div>
                        <?php else: ?>
                            <ul class="confirmation-item-list" aria-label="Items in order #<?php echo (int) $order['order_id']; ?>">
                                <?php foreach ($orderItems as $item): ?>
                                    <?php
                                    $itemProductId = (int) ($item['product_id'] ?? 0);
                                    $itemName      = !empty($item['product_name'])
                                        ? (string) $item['product_name']
                                        : 'Product #' . $itemProductId;
                                    $itemCategory  = (string) ($item['category_name'] ?? '');
                                    $itemQty       = (int) ($item['quantity'] ?? 1);
                                    // Historical unit price from order_items.unit_price (never products.price)
                                    $unitPrice     = (float) ($item['unit_price'] ?? 0.00);
                                    $lineSubtotal  = isset($item['subtotal'])
                                        ? (float) $item['subtotal']
                                        : round($unitPrice * $itemQty, 2);
                                    $imageUrl      = product_image_url($item['product_image'] ?? null);
                                    $isProductActive = (($item['product_status'] ?? '') === 'active' && $itemProductId > 0);
                                    ?>
                                    <li class="confirmation-item-row">
                                        <div class="confirmation-item-media">
                                            <img
                                                src="<?php echo e($imageUrl); ?>"
                                                alt="<?php echo e($itemName); ?>"
                                                width="72"
                                                height="88"
                                                loading="lazy"
                                            >
                                        </div>

                                        <div class="confirmation-item-info">
                                            <?php if ($itemCategory !== ''): ?>
                                                <span class="confirmation-item-category"><?php echo e($itemCategory); ?></span>
                                            <?php endif; ?>

                                            <h3 class="confirmation-item-name">
                                                <?php if ($isProductActive): ?>
                                                    <a href="<?php echo e(site_url('customer/product.php?id=' . $itemProductId)); ?>">
                                                        <?php echo e($itemName); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?php echo e($itemName); ?>
                                                <?php endif; ?>
                                            </h3>

                                            <p class="confirmation-item-pricing">
                                                <span>Qty: <?php echo (int) $itemQty; ?></span>
                                                <span aria-hidden="true">&middot;</span>
                                                <span>Unit Price: <?php echo e(format_price($unitPrice)); ?></span>
                                            </p>
                                        </div>

                                        <div class="confirmation-item-subtotal">
                                            <span class="confirmation-item-subtotal-label">Line Total</span>
                                            <strong class="confirmation-item-subtotal-value">
                                                <?php echo e(format_price($lineSubtotal)); ?>
                                            </strong>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <!-- 2. Delivery & Payment Information Grid -->
                    <div class="confirmation-info-grid">
                        <!-- Shipping / Delivery Card -->
                        <div class="checkout-card confirmation-card">
                            <h2 class="checkout-section-heading">Delivery Destination</h2>
                            <dl class="confirmation-detail-list">
                                <?php if ($recipientName !== ''): ?>
                                    <div class="confirmation-detail-row">
                                        <dt>Recipient</dt>
                                        <dd><?php echo e($recipientName); ?></dd>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($order['email'])): ?>
                                    <div class="confirmation-detail-row">
                                        <dt>Email</dt>
                                        <dd><?php echo e($order['email']); ?></dd>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($order['phone'])): ?>
                                    <div class="confirmation-detail-row">
                                        <dt>Phone</dt>
                                        <dd><?php echo e($order['phone']); ?></dd>
                                    </div>
                                <?php endif; ?>

                                <div class="confirmation-detail-row">
                                    <dt>Shipping Address</dt>
                                    <dd>
                                        <?php echo e($order['shipping_address']); ?><br>
                                        <?php echo e($order['city']); ?>, <?php echo e($order['province']); ?><br>
                                        <?php echo e($order['postal_code']); ?>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Payment & Fulfilment Status Card -->
                        <div class="checkout-card confirmation-card">
                            <h2 class="checkout-section-heading">Payment &amp; Status</h2>
                            <dl class="confirmation-detail-list">
                                <div class="confirmation-detail-row">
                                    <dt>Payment Method</dt>
                                    <dd><?php echo e($order['payment_method']); ?></dd>
                                </div>

                                <div class="confirmation-detail-row">
                                    <dt>Payment Status</dt>
                                    <dd>
                                        <span class="status-pill status-pill-<?php echo e($paymentStatusKey); ?>">
                                            <?php echo e($paymentStatusLabel); ?>
                                        </span>
                                    </dd>
                                </div>

                                <div class="confirmation-detail-row">
                                    <dt>Order Status</dt>
                                    <dd>
                                        <span class="status-pill status-pill-<?php echo e($orderStatusKey); ?>">
                                            <?php echo e($orderStatusLabel); ?>
                                        </span>
                                    </dd>
                                </div>
                            </dl>

                            <p class="confirmation-status-note">
                                <?php if ($paymentStatusKey === 'pending'): ?>
                                    This is a simulated checkout order. Payment status is currently recorded as
                                    <strong>Pending</strong> while awaiting studio verification.
                                <?php elseif ($paymentStatusKey === 'paid'): ?>
                                    Payment has been recorded as <strong>Paid</strong>. Our studio team is preparing your parcel.
                                <?php else: ?>
                                    Current payment status is <strong><?php echo e($paymentStatusLabel); ?></strong>.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Authoritative Order Summary & Actions -->
                <aside class="checkout-summary-card confirmation-summary-card" aria-labelledby="confirmation-summary-heading">
                    <h2 id="confirmation-summary-heading" class="cart-summary-title">Order Summary</h2>

                    <dl class="cart-summary-rows confirmation-totals">
                        <div class="cart-summary-row">
                            <dt>Order Reference</dt>
                            <dd>#<?php echo (int) $order['order_id']; ?></dd>
                        </div>
                        <div class="cart-summary-row">
                            <dt>Subtotal</dt>
                            <dd><?php echo e(format_price($order['subtotal'])); ?></dd>
                        </div>
                        <div class="cart-summary-row">
                            <dt>Shipping</dt>
                            <dd><?php echo e(format_price($order['shipping_cost'])); ?></dd>
                        </div>
                        <div class="cart-summary-row cart-summary-total">
                            <dt>Total</dt>
                            <dd><?php echo e(format_price($order['total'])); ?></dd>
                        </div>
                    </dl>

                    <p class="cart-summary-note">
                        All amounts are shown in South African Rand (ZAR) based on the prices recorded at the time your order was placed.
                    </p>

                    <div class="cart-summary-actions confirmation-actions">
                        <a href="<?php echo e($shopUrl); ?>" class="btn btn-primary confirmation-action-btn">
                            Continue Shopping
                        </a>
                        <?php if (is_customer_logged_in()): ?>
                            <a href="<?php echo e(site_url('customer/account.php')); ?>" class="btn btn-outline confirmation-action-btn">
                                View Order History
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo e($homeUrl); ?>" class="btn btn-secondary confirmation-action-btn">
                            Back to Home
                        </a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
