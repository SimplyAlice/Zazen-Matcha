<?php
/**
 * Administrator Order Management Listing Page (Read-Only)
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads all customer orders joined with
 * customer profile names/emails via a prepared PDO query, ordered newest first
 * (ORDER BY o.order_date DESC, o.order_id DESC).
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$dashboardUrl = site_url('admin/dashboard.php');

$orders      = [];
$ordersError = null;

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
// 1. Load All Orders Joined Safely with Customers (Newest First)
// ----------------------------------------------------------------------------
try {
    $ordersSql = "
        SELECT
            o.order_id,
            o.customer_id,
            o.order_date,
            o.total,
            o.payment_method,
            o.payment_status,
            o.order_status,
            c.first_name,
            c.last_name,
            c.email
        FROM orders o
        LEFT JOIN customers c ON c.customer_id = o.customer_id
        ORDER BY o.order_date DESC, o.order_id DESC
    ";

    $orders = db_fetch_all($pdo, $ordersSql);
} catch (Throwable $exception) {
    error_log('Admin Orders Listing Error: ' . $exception->getMessage());
    $ordersError = 'Unable to load customer orders right now. Please refresh the page or check the database connection.';
}

// Compute quick summary counts from the loaded orders dataset
$totalOrdersCount   = count($orders);
$pendingOrdersCount = 0;
$paidOrdersCount    = 0;

foreach ($orders as $item) {
    if (strtolower((string) ($item['order_status'] ?? '')) === 'pending') {
        $pendingOrdersCount++;
    }
    if (strtolower((string) ($item['payment_status'] ?? '')) === 'paid') {
        $paidOrdersCount++;
    }
}

$pageTitle = 'Order Management';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Hero / Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-orders-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Fulfilment Administration</span>
                <h1 id="admin-orders-heading" class="checkout-title">Order Management</h1>
                <p class="checkout-subtitle">
                    Review all customer orders, payment statuses, and fulfilment progress across the store.
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e($dashboardUrl); ?>" class="btn btn-outline">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Orders Listing Section -->
<section class="section admin-dashboard-section admin-orders-section" aria-label="Customer orders listing">
    <div class="container">
        <?php if ($ordersError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Orders Notice</p>
                <p><?php echo e($ordersError); ?></p>
            </div>
        <?php endif; ?>

        <div class="checkout-card admin-panel-card admin-orders-card">
            <div class="admin-panel-header admin-products-header">
                <div>
                    <span class="eyebrow">01 / Order Records</span>
                    <h2 class="checkout-section-heading">
                        Orders (<?php echo (int) $totalOrdersCount; ?>)
                    </h2>
                    <p class="admin-products-summary">
                        <span><strong><?php echo (int) $totalOrdersCount; ?></strong> Total</span>
                        <span aria-hidden="true">&middot;</span>
                        <span><strong><?php echo (int) $pendingOrdersCount; ?></strong> Pending Fulfilment</span>
                        <span aria-hidden="true">&middot;</span>
                        <span><strong><?php echo (int) $paidOrdersCount; ?></strong> Paid</span>
                    </p>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <!-- Empty Orders State -->
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">No orders have been recorded yet.</p>
                    <p class="admin-empty-text">
                        Customer orders placed through the storefront checkout will appear here automatically.
                    </p>
                </div>
            <?php else: ?>
                <!-- Orders Table -->
                <div class="admin-table-wrapper">
                    <table class="admin-table admin-orders-table">
                        <thead>
                            <tr>
                                <th scope="col">Order ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Order Date</th>
                                <th scope="col">Total</th>
                                <th scope="col">Payment Status</th>
                                <th scope="col">Order Status</th>
                                <th scope="col" class="admin-col-actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $orderRow): ?>
                                <?php
                                $rowOrderId = (int) ($orderRow['order_id'] ?? 0);

                                // Customer display name with safe 'Guest' fallback if missing/null
                                $custFirst = trim((string) ($orderRow['first_name'] ?? ''));
                                $custLast  = trim((string) ($orderRow['last_name'] ?? ''));
                                $custName  = trim($custFirst . ' ' . $custLast);
                                if ($custName === '') {
                                    $custName = 'Guest';
                                }
                                $custEmail = trim((string) ($orderRow['email'] ?? ''));

                                // Format order date
                                $rawDate       = trim((string) ($orderRow['order_date'] ?? ''));
                                $timestamp     = $rawDate !== '' ? strtotime($rawDate) : false;
                                $formattedDate = $timestamp !== false
                                    ? date('d M Y, H:i', $timestamp)
                                    : $rawDate;

                                // Payment status badge
                                $payStatusKey   = strtolower(trim((string) ($orderRow['payment_status'] ?? 'pending')));
                                $payStatusLabel = $paymentStatusLabels[$payStatusKey] ?? ucfirst($payStatusKey);

                                // Order status badge
                                $ordStatusKey   = strtolower(trim((string) ($orderRow['order_status'] ?? 'pending')));
                                $ordStatusLabel = $orderStatusLabels[$ordStatusKey] ?? ucfirst($ordStatusKey);

                                // Construct validated detail URL
                                $detailsUrl = site_url('admin/order-details.php?id=' . $rowOrderId);
                                ?>
                                <tr data-order-id="<?php echo $rowOrderId; ?>">
                                    <td class="admin-cell-id" data-label="Order ID">
                                        <strong>#<?php echo $rowOrderId; ?></strong>
                                    </td>

                                    <td data-label="Customer">
                                        <div class="admin-customer-cell">
                                            <span class="admin-customer-name"><?php echo e($custName); ?></span>
                                            <?php if ($custEmail !== ''): ?>
                                                <span class="admin-customer-email"><?php echo e($custEmail); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="admin-cell-date" data-label="Order Date">
                                        <?php echo e($formattedDate); ?>
                                    </td>

                                    <td class="admin-cell-total" data-label="Total">
                                        <?php echo e(format_price($orderRow['total'] ?? 0.00)); ?>
                                    </td>

                                    <td data-label="Payment Status">
                                        <span class="status-badge status-payment-<?php echo e($payStatusKey); ?>">
                                            <?php echo e($payStatusLabel); ?>
                                        </span>
                                    </td>

                                    <td data-label="Order Status">
                                        <span class="status-badge status-order-<?php echo e($ordStatusKey); ?>">
                                            <?php echo e($ordStatusLabel); ?>
                                        </span>
                                    </td>

                                    <td class="admin-cell-actions" data-label="Action">
                                        <a
                                            href="<?php echo e($detailsUrl); ?>"
                                            class="btn btn-outline admin-edit-btn"
                                            aria-label="View details for Order #<?php echo $rowOrderId; ?>"
                                        >
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
