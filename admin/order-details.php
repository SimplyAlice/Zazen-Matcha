<?php
/**
 * Administrator Order Details Page (Read-Only)
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads a single order by ?id=<order_id>,
 * its related customer profile (with 'Guest' fallback), its stored historical
 * shipping address snapshot, and its line items from `order_items` using
 * historical `order_items.unit_price`.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$ordersListUrl = site_url('admin/orders.php');

// ----------------------------------------------------------------------------
// 1. Validate Order ID from $_GET['id'] (Positive Integer Only)
// ----------------------------------------------------------------------------
$rawOrderId = $_GET['id'] ?? '';
$orderId    = (
    !is_array($rawOrderId)
    && ctype_digit(trim((string) $rawOrderId))
    && (int) trim((string) $rawOrderId) > 0
)
    ? (int) trim((string) $rawOrderId)
    : 0;

$order      = null;
$orderItems = [];
$orderFound = false;
$dbError    = null;

// Status presentation label maps matching actual database ENUM values
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
// 2. Load Order Header, Customer Details & Historical Order Items via PDO
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
            $order      = $fetchedOrder;
            $orderFound = true;

            // Load line items using historical order_items.unit_price (never products.price)
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
                    c.name AS category_name
                FROM order_items oi
                LEFT JOIN products p ON p.product_id = oi.product_id
                LEFT JOIN categories c ON c.category_id = p.category_id
                WHERE oi.order_id = :order_id
                ORDER BY oi.order_item_id ASC
            ";

            $orderItems = db_fetch_all($pdo, $itemsSql, [
                'order_id' => $orderId,
            ]);
        }
    } catch (Throwable $exception) {
        error_log('Admin Order Details Load Error: ' . $exception->getMessage());
        $dbError = 'Unable to load order details right now. Please refresh the page or check the database connection.';
    }
}

$pageTitle = $orderFound ? 'Order #' . $orderId : 'Order Not Found';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Hero / Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-order-details-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Fulfilment Administration</span>
                <h1 id="admin-order-details-heading" class="checkout-title">
                    <?php echo $orderFound ? 'Order #' . (int) $orderId : 'Order Not Found'; ?>
                </h1>
                <p class="checkout-subtitle">
                    <?php if ($orderFound): ?>
                        Complete read-only record of customer details, shipping destination, historical item pricing, and order totals.
                    <?php else: ?>
                        The requested order record could not be located in the Zazen Matcha database.
                    <?php endif; ?>
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e($ordersListUrl); ?>" class="btn btn-outline">
                    &larr; Back to Orders
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Order Details Section -->
<section class="section admin-dashboard-section admin-order-details-section" aria-label="Order details">
    <div class="container">
        <?php if ($dbError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Database Notice</p>
                <p><?php echo e($dbError); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$orderFound): ?>
            <!-- Clean Order Not Found State -->
            <div class="checkout-card admin-panel-card">
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">Order Not Found</p>
                    <p class="admin-empty-text">
                        The order ID provided is missing, invalid, or does not match an existing order in the database.
                    </p>
                    <div class="admin-empty-actions">
                        <a href="<?php echo e($ordersListUrl); ?>" class="btn btn-primary">
                            &larr; Back to Orders
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php
            // Prepare formatted order header & customer fields
            $displayOrderId = (int) ($order['order_id'] ?? 0);
            $rawOrderDate   = trim((string) ($order['order_date'] ?? ''));
            $orderTimestamp = $rawOrderDate !== '' ? strtotime($rawOrderDate) : false;
            $formattedDate  = $orderTimestamp !== false
                ? date('d M Y, H:i', $orderTimestamp)
                : $rawOrderDate;

            $custFirst = trim((string) ($order['first_name'] ?? ''));
            $custLast  = trim((string) ($order['last_name'] ?? ''));
            $custName  = trim($custFirst . ' ' . $custLast);
            if ($custName === '') {
                $custName = 'Guest';
            }
            $custEmail = trim((string) ($order['email'] ?? ''));
            $custPhone = trim((string) ($order['phone'] ?? ''));

            $paymentMethod  = trim((string) ($order['payment_method'] ?? 'Not specified'));
            $payStatusKey   = strtolower(trim((string) ($order['payment_status'] ?? 'pending')));
            $payStatusLabel = $paymentStatusLabels[$payStatusKey] ?? ucfirst($payStatusKey);

            $ordStatusKey   = strtolower(trim((string) ($order['order_status'] ?? 'pending')));
            $ordStatusLabel = $orderStatusLabels[$ordStatusKey] ?? ucfirst($ordStatusKey);

            // Historical shipping address snapshot stored in `orders`
            $shippingStreet   = trim((string) ($order['shipping_address'] ?? ''));
            $shippingCity     = trim((string) ($order['city'] ?? ''));
            $shippingProvince = trim((string) ($order['province'] ?? ''));
            $shippingPostal   = trim((string) ($order['postal_code'] ?? ''));

            // Stored financial values on the order record
            $storedSubtotal = (float) ($order['subtotal'] ?? 0.00);
            $storedShipping = (float) ($order['shipping_cost'] ?? 0.00);
            $storedTotal    = (float) ($order['total'] ?? 0.00);

            $calculatedItemsSubtotal = 0.00;
            $totalUnitsCount         = 0;
            ?>

            <!-- Top Order Meta Bar -->
            <div class="confirmation-meta-bar" aria-label="Order summary metadata">
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Order ID</span>
                    <span class="confirmation-meta-value">#<?php echo $displayOrderId; ?></span>
                </div>
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Order Date</span>
                    <span class="confirmation-meta-value"><?php echo e($formattedDate); ?></span>
                </div>
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Payment Status</span>
                    <span class="confirmation-meta-value">
                        <span class="status-badge status-payment-<?php echo e($payStatusKey); ?>">
                            <?php echo e($payStatusLabel); ?>
                        </span>
                    </span>
                </div>
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Order Status</span>
                    <span class="confirmation-meta-value">
                        <span class="status-badge status-order-<?php echo e($ordStatusKey); ?>">
                            <?php echo e($ordStatusLabel); ?>
                        </span>
                    </span>
                </div>
            </div>

            <!-- Main Two-Column Details Layout -->
            <div class="admin-dashboard-grid">
                <!-- Left / Main Column: Order Items & Customer / Address Cards -->
                <div class="confirmation-main-column">
                    <!-- Card 1: Order Items Table -->
                    <div class="checkout-card admin-panel-card">
                        <div class="admin-panel-header">
                            <div>
                                <span class="eyebrow">01 / Purchased Products</span>
                                <h2 class="checkout-section-heading">Order Items</h2>
                            </div>
                        </div>

                        <?php if (empty($orderItems)): ?>
                            <div class="admin-empty-state" role="status">
                                <p class="admin-empty-title">No items recorded for this order.</p>
                            </div>
                        <?php else: ?>
                            <div class="admin-table-wrapper">
                                <table class="admin-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Product</th>
                                            <th scope="col">Quantity</th>
                                            <th scope="col">Unit Price</th>
                                            <th scope="col" class="admin-col-actions">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderItems as $item): ?>
                                            <?php
                                            $itemProductId = (int) ($item['product_id'] ?? 0);
                                            $rawProdName   = trim((string) ($item['product_name'] ?? ''));
                                            $displayProdName = $rawProdName !== '' ? $rawProdName : 'Product unavailable';
                                            $categoryName  = trim((string) ($item['category_name'] ?? ''));

                                            $itemQuantity  = (int) ($item['quantity'] ?? 0);
                                            // Strictly use historical order_items.unit_price
                                            $itemUnitPrice = (float) ($item['unit_price'] ?? 0.00);
                                            // Calculate item subtotal as quantity * unit_price
                                            $itemSubtotal  = round($itemQuantity * $itemUnitPrice, 2);

                                            $calculatedItemsSubtotal += $itemSubtotal;
                                            $totalUnitsCount         += $itemQuantity;
                                            ?>
                                            <tr data-order-item-id="<?php echo (int) ($item['order_item_id'] ?? 0); ?>">
                                                <td data-label="Product">
                                                    <div class="admin-product-name-cell">
                                                        <span class="admin-product-name"><?php echo e($displayProdName); ?></span>
                                                        <span class="admin-product-filename">
                                                            Product ID #<?php echo $itemProductId; ?>
                                                            <?php if ($categoryName !== ''): ?>
                                                                &middot; <?php echo e($categoryName); ?>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                </td>

                                                <td data-label="Quantity">
                                                    <strong><?php echo $itemQuantity; ?></strong>
                                                </td>

                                                <td class="admin-cell-total" data-label="Unit Price">
                                                    <?php echo e(format_price($itemUnitPrice)); ?>
                                                </td>

                                                <td class="admin-cell-total admin-cell-actions" data-label="Subtotal">
                                                    <?php echo e(format_price($itemSubtotal)); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Card 2: Customer Information & Historical Shipping Address -->
                    <div class="confirmation-info-grid">
                        <!-- Customer Profile Details -->
                        <div class="checkout-card admin-panel-card">
                            <span class="eyebrow">02 / Customer Information</span>
                            <h2 class="checkout-section-heading">Customer Details</h2>

                            <dl class="confirmation-detail-list">
                                <div class="confirmation-detail-row">
                                    <dt>Full Name</dt>
                                    <dd><?php echo e($custName); ?></dd>
                                </div>
                                <div class="confirmation-detail-row">
                                    <dt>Email Address</dt>
                                    <dd><?php echo $custEmail !== '' ? e($custEmail) : 'Not available'; ?></dd>
                                </div>
                                <?php if ($custPhone !== ''): ?>
                                    <div class="confirmation-detail-row">
                                        <dt>Phone Number</dt>
                                        <dd><?php echo e($custPhone); ?></dd>
                                    </div>
                                <?php endif; ?>
                                <div class="confirmation-detail-row">
                                    <dt>Payment Method</dt>
                                    <dd><?php echo e($paymentMethod); ?></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Stored Order Shipping Address Snapshot -->
                        <div class="checkout-card admin-panel-card">
                            <span class="eyebrow">03 / Delivery Destination</span>
                            <h2 class="checkout-section-heading">Shipping Address</h2>

                            <dl class="confirmation-detail-list">
                                <div class="confirmation-detail-row">
                                    <dt>Street Address</dt>
                                    <dd><?php echo $shippingStreet !== '' ? e($shippingStreet) : 'Not recorded'; ?></dd>
                                </div>
                                <div class="confirmation-detail-row">
                                    <dt>City</dt>
                                    <dd><?php echo $shippingCity !== '' ? e($shippingCity) : 'Not recorded'; ?></dd>
                                </div>
                                <div class="confirmation-detail-row">
                                    <dt>Province</dt>
                                    <dd><?php echo $shippingProvince !== '' ? e($shippingProvince) : 'Not recorded'; ?></dd>
                                </div>
                                <div class="confirmation-detail-row">
                                    <dt>Postal Code</dt>
                                    <dd><?php echo $shippingPostal !== '' ? e($shippingPostal) : 'Not recorded'; ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Right / Sidebar Column: Stored Financial Totals Summary -->
                <aside class="cart-summary-card confirmation-summary-card" aria-labelledby="admin-order-totals-heading">
                    <h2 id="admin-order-totals-heading" class="cart-summary-title">Order Summary</h2>

                    <div class="cart-summary-rows">
                        <div class="cart-summary-row">
                            <span>Order ID</span>
                            <span>#<?php echo $displayOrderId; ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Total Units</span>
                            <span><?php echo (int) $totalUnitsCount; ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Items Subtotal</span>
                            <span><?php echo e(format_price($storedSubtotal)); ?></span>
                        </div>
                        <div class="cart-summary-row">
                            <span>Shipping Cost</span>
                            <span><?php echo e(format_price($storedShipping)); ?></span>
                        </div>
                    </div>

                    <div class="cart-summary-total">
                        <span class="cart-summary-total-label">Order Total</span>
                        <span class="cart-summary-total-amount" data-order-total="<?php echo e(number_format($storedTotal, 2, '.', '')); ?>">
                            <?php echo e(format_price($storedTotal)); ?>
                        </span>
                    </div>

                    <p class="confirmation-status-note">
                        Unit prices reflect the historical price recorded in <code>order_items.unit_price</code> at the time the order was placed.
                    </p>

                    <div class="cart-summary-actions">
                        <a href="<?php echo e($ordersListUrl); ?>" class="btn btn-outline confirmation-action-btn">
                            &larr; Back to Orders
                        </a>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
