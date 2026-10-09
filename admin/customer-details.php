<?php
/**
 * Administrator Customer Details Page (Read-Only)
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads a single customer profile by
 * ?id=<customer_id> (explicitly excluding password hashes) along with the
 * customer's complete order history ordered newest first.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$customersListUrl = site_url('admin/customers.php');

// ----------------------------------------------------------------------------
// 1. Validate Customer ID from $_GET['id'] (Positive Integer Only)
// ----------------------------------------------------------------------------
$rawCustomerId = $_GET['id'] ?? '';
$customerId    = (
    !is_array($rawCustomerId)
    && ctype_digit(trim((string) $rawCustomerId))
    && (int) trim((string) $rawCustomerId) > 0
)
    ? (int) trim((string) $rawCustomerId)
    : 0;

$customer       = null;
$customerOrders = [];
$customerFound  = false;
$dbError        = null;

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
// 2. Load Customer Profile (Excluding Password) & Related Orders via PDO
// ----------------------------------------------------------------------------
if ($customerId > 0) {
    try {
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
                postal_code,
                created_at
            FROM customers
            WHERE customer_id = :customer_id
            LIMIT 1
        ";

        $fetchedCustomer = db_fetch_one($pdo, $customerSql, [
            'customer_id' => $customerId,
        ]);

        if ($fetchedCustomer !== null) {
            $customer      = $fetchedCustomer;
            $customerFound = true;

            $ordersSql = "
                SELECT
                    order_id,
                    customer_id,
                    order_date,
                    total,
                    payment_status,
                    order_status
                FROM orders
                WHERE customer_id = :customer_id
                ORDER BY order_date DESC, order_id DESC
            ";

            $customerOrders = db_fetch_all($pdo, $ordersSql, [
                'customer_id' => $customerId,
            ]);
        }
    } catch (Throwable $exception) {
        error_log('Admin Customer Details Load Error: ' . $exception->getMessage());
        $dbError = 'Unable to load customer details right now. Please refresh the page or check the database connection.';
    }
}

$pageTitle = $customerFound ? 'Customer #' . $customerId : 'Customer Not Found';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Hero / Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-customer-details-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Account Administration</span>
                <h1 id="admin-customer-details-heading" class="checkout-title">
                    <?php echo $customerFound ? 'Customer #' . (int) $customerId : 'Customer Not Found'; ?>
                </h1>
                <p class="checkout-subtitle">
                    <?php if ($customerFound): ?>
                        Read-only customer profile, contact and delivery information, and complete order history.
                    <?php else: ?>
                        The requested customer record could not be located in the Zazen Matcha database.
                    <?php endif; ?>
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e($customersListUrl); ?>" class="btn btn-outline">
                    &larr; Back to Customers
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Customer Details Section -->
<section class="section admin-dashboard-section admin-customer-details-section" aria-label="Customer details">
    <div class="container">
        <?php if ($dbError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Database Notice</p>
                <p><?php echo e($dbError); ?></p>
            </div>
        <?php endif; ?>

        <?php if (!$customerFound): ?>
            <!-- Clean Customer Not Found State -->
            <div class="checkout-card admin-panel-card">
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">Customer Not Found</p>
                    <p class="admin-empty-text">
                        The customer ID provided is missing, invalid, or does not match an existing customer in the database.
                    </p>
                    <div class="admin-empty-actions">
                        <a href="<?php echo e($customersListUrl); ?>" class="btn btn-primary">
                            &larr; Back to Customers
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php
            $displayCustomerId = (int) ($customer['customer_id'] ?? 0);

            $firstName = trim((string) ($customer['first_name'] ?? ''));
            $lastName  = trim((string) ($customer['last_name'] ?? ''));
            $fullName  = trim($firstName . ' ' . $lastName);
            if ($fullName === '') {
                $fullName = 'Customer #' . $displayCustomerId;
            }

            $email      = trim((string) ($customer['email'] ?? ''));
            $phone      = trim((string) ($customer['phone'] ?? ''));
            $address    = trim((string) ($customer['address'] ?? ''));
            $city       = trim((string) ($customer['city'] ?? ''));
            $province   = trim((string) ($customer['province'] ?? ''));
            $postalCode = trim((string) ($customer['postal_code'] ?? ''));

            $rawCreatedAt  = trim((string) ($customer['created_at'] ?? ''));
            $createdTs     = $rawCreatedAt !== '' ? strtotime($rawCreatedAt) : false;
            $formattedDate = $createdTs !== false
                ? date('d M Y, H:i', $createdTs)
                : $rawCreatedAt;

            $totalOrdersCount = count($customerOrders);
            ?>

            <!-- Top Customer Meta Bar -->
            <div class="confirmation-meta-bar" aria-label="Customer summary metadata">
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Customer ID</span>
                    <span class="confirmation-meta-value">#<?php echo $displayCustomerId; ?></span>
                </div>
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Full Name</span>
                    <span class="confirmation-meta-value"><?php echo e($fullName); ?></span>
                </div>
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Registration Date</span>
                    <span class="confirmation-meta-value"><?php echo e($formattedDate); ?></span>
                </div>
                <div class="confirmation-meta-item">
                    <span class="confirmation-meta-label">Total Orders</span>
                    <span class="confirmation-meta-value"><?php echo (int) $totalOrdersCount; ?></span>
                </div>
            </div>

            <!-- Customer Profile & Location Information Grid -->
            <div class="confirmation-info-grid">
                <!-- Card 1: Contact & Account Details -->
                <div class="checkout-card admin-panel-card">
                    <span class="eyebrow">01 / Customer Profile</span>
                    <h2 class="checkout-section-heading">Account &amp; Contact Information</h2>

                    <dl class="confirmation-detail-list">
                        <div class="confirmation-detail-row">
                            <dt>Customer ID</dt>
                            <dd>#<?php echo $displayCustomerId; ?></dd>
                        </div>
                        <div class="confirmation-detail-row">
                            <dt>Full Name</dt>
                            <dd><?php echo e($fullName); ?></dd>
                        </div>
                        <div class="confirmation-detail-row">
                            <dt>Email</dt>
                            <dd><?php echo $email !== '' ? e($email) : '&mdash;'; ?></dd>
                        </div>
                        <div class="confirmation-detail-row">
                            <dt>Phone</dt>
                            <dd><?php echo $phone !== '' ? e($phone) : '&mdash;'; ?></dd>
                        </div>
                        <div class="confirmation-detail-row">
                            <dt>Registration Date</dt>
                            <dd><?php echo $formattedDate !== '' ? e($formattedDate) : '&mdash;'; ?></dd>
                        </div>
                    </dl>
                </div>

                <!-- Card 2: Saved Address & Location Details -->
                <div class="checkout-card admin-panel-card">
                    <span class="eyebrow">02 / Saved Location</span>
                    <h2 class="checkout-section-heading">Address &amp; Region</h2>

                    <dl class="confirmation-detail-list">
                        <div class="confirmation-detail-row">
                            <dt>Street Address</dt>
                            <dd><?php echo $address !== '' ? e($address) : '&mdash;'; ?></dd>
                        </div>
                        <div class="confirmation-detail-row">
                            <dt>City</dt>
                            <dd><?php echo $city !== '' ? e($city) : '&mdash;'; ?></dd>
                        </div>
                        <div class="confirmation-detail-row">
                            <dt>Province</dt>
                            <dd><?php echo $province !== '' ? e($province) : '&mdash;'; ?></dd>
                        </div>
                        <div class="confirmation-detail-row">
                            <dt>Postal Code</dt>
                            <dd><?php echo $postalCode !== '' ? e($postalCode) : '&mdash;'; ?></dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Card 3: Customer Order History Table -->
            <div class="checkout-card admin-panel-card" style="margin-top: 2rem;">
                <div class="admin-panel-header admin-products-header">
                    <div>
                        <span class="eyebrow">03 / Purchase Records</span>
                        <h2 class="checkout-section-heading">
                            Customer Order History (<?php echo (int) $totalOrdersCount; ?>)
                        </h2>
                    </div>
                    <a href="<?php echo e($customersListUrl); ?>" class="btn btn-outline">
                        &larr; Back to Customers
                    </a>
                </div>

                <?php if (empty($customerOrders)): ?>
                    <div class="admin-empty-state" role="status">
                        <p class="admin-empty-title">No orders found for this customer.</p>
                        <p class="admin-empty-text">
                            This customer account has not placed any orders yet.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="admin-table-wrapper">
                        <table class="admin-table admin-customer-orders-table">
                            <thead>
                                <tr>
                                    <th scope="col">Order ID</th>
                                    <th scope="col">Order Date</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Payment Status</th>
                                    <th scope="col">Order Status</th>
                                    <th scope="col" class="admin-col-actions">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerOrders as $orderRow): ?>
                                    <?php
                                    $rowOrderId = (int) ($orderRow['order_id'] ?? 0);

                                    $rawOrderDate   = trim((string) ($orderRow['order_date'] ?? ''));
                                    $orderTs        = $rawOrderDate !== '' ? strtotime($rawOrderDate) : false;
                                    $orderDateLabel = $orderTs !== false
                                        ? date('d M Y, H:i', $orderTs)
                                        : $rawOrderDate;

                                    $payStatusKey   = strtolower(trim((string) ($orderRow['payment_status'] ?? 'pending')));
                                    $payStatusLabel = $paymentStatusLabels[$payStatusKey] ?? ucfirst($payStatusKey);

                                    $ordStatusKey   = strtolower(trim((string) ($orderRow['order_status'] ?? 'pending')));
                                    $ordStatusLabel = $orderStatusLabels[$ordStatusKey] ?? ucfirst($ordStatusKey);

                                    $orderDetailsUrl = site_url('admin/order-details.php?id=' . $rowOrderId);
                                    ?>
                                    <tr data-order-id="<?php echo $rowOrderId; ?>">
                                        <td class="admin-cell-id" data-label="Order ID">
                                            <strong>#<?php echo $rowOrderId; ?></strong>
                                        </td>

                                        <td class="admin-cell-date" data-label="Order Date">
                                            <?php echo e($orderDateLabel); ?>
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
                                                href="<?php echo e($orderDetailsUrl); ?>"
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
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/admin-footer.php'; ?>
