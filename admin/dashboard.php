<?php
/**
 * Administrator Dashboard Page
 * Zazen Matcha & Tea Co.
 *
 * Protected administration overview displaying live store metrics from MySQL
 * (Total Products, Active Products, Total Customers, Total Orders, Pending Orders,
 * Total Sales), the latest 5 customer orders, and quick management navigation.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$adminName       = get_logged_in_admin_name() ?? 'Administrator';
$adminEmail      = get_logged_in_admin_email() ?? '';
$dashboardError  = null;

// Initialise summary metric defaults
$totalProducts   = 0;
$activeProducts  = 0;
$totalCustomers  = 0;
$totalOrders     = 0;
$pendingOrders   = 0;
$totalSales      = 0.00;
$paidSales       = 0.00;
$recentOrders    = [];

// Friendly status badge labels
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
// 1. Load Live Store Metrics & Recent Orders via PDO Prepared Statements
// ----------------------------------------------------------------------------
try {
    // 1a. Total Products
    $totalProductsRow = db_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS count_val FROM products'
    );
    $totalProducts = (int) ($totalProductsRow['count_val'] ?? 0);

    // 1b. Active Products (status = 'active')
    $activeProductsRow = db_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS count_val FROM products WHERE status = :status',
        ['status' => 'active']
    );
    $activeProducts = (int) ($activeProductsRow['count_val'] ?? 0);

    // 1c. Total Customers
    $totalCustomersRow = db_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS count_val FROM customers'
    );
    $totalCustomers = (int) ($totalCustomersRow['count_val'] ?? 0);

    // 1d. Total Orders
    $totalOrdersRow = db_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS count_val FROM orders'
    );
    $totalOrders = (int) ($totalOrdersRow['count_val'] ?? 0);

    // 1e. Pending Orders (order_status = 'pending')
    $pendingOrdersRow = db_fetch_one(
        $pdo,
        'SELECT COUNT(*) AS count_val FROM orders WHERE order_status = :order_status',
        ['order_status' => 'pending']
    );
    $pendingOrders = (int) ($pendingOrdersRow['count_val'] ?? 0);

    // 1f. Total Sales
    // Definition: Sum of `orders.total` for all valid orders where
    // `order_status <> 'cancelled'` AND `payment_status <> 'failed'`.
    // Also computes settled (`payment_status = 'paid'`) revenue for clarity.
    $salesRow = db_fetch_one(
        $pdo,
        'SELECT
            COALESCE(SUM(total), 0.00) AS valid_sales_total,
            COALESCE(SUM(CASE WHEN payment_status = :paid_status THEN total ELSE 0.00 END), 0.00) AS paid_sales_total
         FROM orders
         WHERE order_status <> :cancelled_status
           AND payment_status <> :failed_status',
        [
            'paid_status'      => 'paid',
            'cancelled_status' => 'cancelled',
            'failed_status'    => 'failed',
        ]
    );
    $totalSales = (float) ($salesRow['valid_sales_total'] ?? 0.00);
    $paidSales  = (float) ($salesRow['paid_sales_total'] ?? 0.00);

    // 1g. Recent Orders (Latest 5 Orders joined safely with customer identity)
    $recentOrdersSql = "
        SELECT
            o.order_id,
            o.customer_id,
            o.order_date,
            o.total,
            o.payment_status,
            o.order_status,
            c.first_name,
            c.last_name,
            c.email
        FROM orders o
        LEFT JOIN customers c ON c.customer_id = o.customer_id
        ORDER BY o.order_date DESC, o.order_id DESC
        LIMIT 5
    ";
    $recentOrders = db_fetch_all($pdo, $recentOrdersSql);
} catch (Throwable $exception) {
    error_log('Admin Dashboard Query Error: ' . $exception->getMessage());
    $dashboardError = 'We could not load all store metrics right now. Please refresh or check the database connection.';
}

$pageTitle = 'Dashboard';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Dashboard Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="dashboard-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Store Operations</span>
                <h1 id="dashboard-heading" class="checkout-title">Administration Dashboard</h1>
                <p class="checkout-subtitle">
                    Signed in as <strong><?php echo e($adminName); ?></strong><?php echo $adminEmail !== '' ? ' (' . e($adminEmail) . ')' : ''; ?>. Live overview of catalogue inventory, customer accounts, and order activity.
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e(site_url('admin/products.php')); ?>" class="btn btn-primary">
                    Manage Products
                </a>
                <a href="<?php echo e(site_url('admin/orders.php')); ?>" class="btn btn-outline">
                    Manage Orders
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Dashboard Content -->
<section class="section admin-dashboard-section" aria-label="Store metrics and recent activity">
    <div class="container">
        <?php if ($dashboardError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Dashboard Notice</p>
                <p><?php echo e($dashboardError); ?></p>
            </div>
        <?php endif; ?>

        <!-- 2a. Six Key Store Metrics Grid -->
        <div class="admin-section-header">
            <div>
                <span class="eyebrow">01 / Store Overview</span>
                <h2 class="admin-section-title">Key Performance Metrics</h2>
            </div>
        </div>

        <div class="admin-metrics-grid" role="region" aria-label="Store summary metrics">
            <!-- Metric 1: Total Products -->
            <article class="admin-metric-card">
                <span class="admin-metric-label">Total Products</span>
                <p class="admin-metric-value" data-metric="total-products"><?php echo (int) $totalProducts; ?></p>
                <p class="admin-metric-meta">All catalogue items in database</p>
            </article>

            <!-- Metric 2: Active Products -->
            <article class="admin-metric-card">
                <span class="admin-metric-label">Active Products</span>
                <p class="admin-metric-value" data-metric="active-products"><?php echo (int) $activeProducts; ?></p>
                <p class="admin-metric-meta">Visible in customer shop (`status = active`)</p>
            </article>

            <!-- Metric 3: Total Customers -->
            <article class="admin-metric-card">
                <span class="admin-metric-label">Total Customers</span>
                <p class="admin-metric-value" data-metric="total-customers"><?php echo (int) $totalCustomers; ?></p>
                <p class="admin-metric-meta">Registered customer profiles</p>
            </article>

            <!-- Metric 4: Total Orders -->
            <article class="admin-metric-card">
                <span class="admin-metric-label">Total Orders</span>
                <p class="admin-metric-value" data-metric="total-orders"><?php echo (int) $totalOrders; ?></p>
                <p class="admin-metric-meta">All recorded customer orders</p>
            </article>

            <!-- Metric 5: Pending Orders -->
            <article class="admin-metric-card">
                <span class="admin-metric-label">Pending Orders</span>
                <p class="admin-metric-value" data-metric="pending-orders"><?php echo (int) $pendingOrders; ?></p>
                <p class="admin-metric-meta">Awaiting fulfilment (`order_status = pending`)</p>
            </article>

            <!-- Metric 6: Total Sales -->
            <article class="admin-metric-card admin-metric-card-highlight">
                <span class="admin-metric-label">Total Sales</span>
                <p class="admin-metric-value" data-metric="total-sales"><?php echo e(format_price($totalSales)); ?></p>
                <p class="admin-metric-meta">
                    Valid orders (excl. cancelled/failed) &middot; Paid: <?php echo e(format_price($paidSales)); ?>
                </p>
            </article>
        </div>

        <!-- 2b. Recent Orders & Quick Management Layout -->
        <div class="admin-dashboard-grid">
            <!-- Left / Main Column: Recent Orders (Latest 5) -->
            <div class="checkout-card admin-panel-card">
                <div class="admin-panel-header">
                    <div>
                        <span class="eyebrow">02 / Latest Activity</span>
                        <h2 class="checkout-section-heading">Recent Orders</h2>
                    </div>
                    <a href="<?php echo e(site_url('admin/orders.php')); ?>" class="editorial-link admin-panel-link">
                        View All Orders &rarr;
                    </a>
                </div>

                <?php if (empty($recentOrders)): ?>
                    <div class="admin-empty-state" role="status">
                        <p class="admin-empty-title">No orders have been recorded yet.</p>
                        <p class="admin-empty-text">
                            New customer orders placed through the storefront checkout will appear here automatically.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Order #</th>
                                    <th scope="col">Customer</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Payment Status</th>
                                    <th scope="col">Order Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $orderRow): ?>
                                    <?php
                                    $rowOrderId = (int) ($orderRow['order_id'] ?? 0);
                                    $custFirst  = trim((string) ($orderRow['first_name'] ?? ''));
                                    $custLast   = trim((string) ($orderRow['last_name'] ?? ''));
                                    $custName   = trim($custFirst . ' ' . $custLast);
                                    if ($custName === '') {
                                        $custName = 'Customer #' . (int) ($orderRow['customer_id'] ?? 0);
                                    }
                                    $custEmail  = trim((string) ($orderRow['email'] ?? ''));

                                    $rawDate    = (string) ($orderRow['order_date'] ?? '');
                                    $timestamp  = $rawDate !== '' ? strtotime($rawDate) : false;
                                    $formattedDate = $timestamp !== false
                                        ? date('d M Y, H:i', $timestamp)
                                        : $rawDate;

                                    $payStatusKey   = strtolower((string) ($orderRow['payment_status'] ?? 'pending'));
                                    $payStatusLabel = $paymentStatusLabels[$payStatusKey] ?? ucfirst($payStatusKey);

                                    $ordStatusKey   = strtolower((string) ($orderRow['order_status'] ?? 'pending'));
                                    $ordStatusLabel = $orderStatusLabels[$ordStatusKey] ?? ucfirst($ordStatusKey);
                                    ?>
                                    <tr>
                                        <td class="admin-cell-id" data-label="Order #">
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
                                        <td class="admin-cell-date" data-label="Date">
                                            <?php echo e($formattedDate); ?>
                                        </td>
                                        <td class="admin-cell-total" data-label="Total">
                                            <?php echo e(format_price($orderRow['total'] ?? 0)); ?>
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
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right / Sidebar Column: Quick Management Links -->
            <aside class="auth-editorial-card admin-quicklinks-card" aria-labelledby="quick-links-heading">
                <span class="eyebrow">03 / Store Management</span>
                <h2 id="quick-links-heading" class="auth-editorial-title">
                    Quick Actions
                </h2>
                <p class="auth-editorial-intro">
                    Navigate directly to core administration modules to manage products, categories, customer orders, and accounts.
                </p>

                <nav class="admin-quicklinks-nav" aria-label="Quick management links">
                    <ul class="admin-quicklinks-list">
                        <li>
                            <a href="<?php echo e(site_url('admin/products.php')); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Manage Products</span>
                                    <span class="admin-quicklink-desc">Add, edit, price, and control stock for matcha and tea ware</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/orders.php')); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Manage Orders</span>
                                    <span class="admin-quicklink-desc">Review customer orders and update payment or fulfilment status</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/customers.php')); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Manage Customers</span>
                                    <span class="admin-quicklink-desc">Inspect registered customer profiles and order history</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/categories.php')); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Manage Categories</span>
                                    <span class="admin-quicklink-desc">Organise product groupings across powders, tools, and gift sets</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
