<?php
/**
 * Administrator Customer Management Listing Page (Read-Only)
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads all registered customer accounts
 * from the `customers` table via a prepared PDO query (explicitly excluding
 * password hashes), ordered newest registered first
 * (ORDER BY created_at DESC, customer_id DESC).
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$dashboardUrl = site_url('admin/dashboard.php');

$customers      = [];
$customersError = null;

// ----------------------------------------------------------------------------
// 1. Load All Customers (Newest Registered First; Never Select Password Hash)
// ----------------------------------------------------------------------------
try {
    $customersSql = "
        SELECT
            customer_id,
            first_name,
            last_name,
            email,
            phone,
            created_at
        FROM customers
        ORDER BY created_at DESC, customer_id DESC
    ";

    $customers = db_fetch_all($pdo, $customersSql);
} catch (Throwable $exception) {
    error_log('Admin Customers Listing Error: ' . $exception->getMessage());
    $customersError = 'Unable to load customer records right now. Please refresh the page or check the database connection.';
}

$totalCustomersCount = count($customers);

$pageTitle = 'Customer Management';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Hero / Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-customers-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Account Administration</span>
                <h1 id="admin-customers-heading" class="checkout-title">Customer Management</h1>
                <p class="checkout-subtitle">
                    Review registered customer profiles, contact details, and account registration records.
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

<!-- 2. Main Customers Listing Section -->
<section class="section admin-dashboard-section admin-customers-section" aria-label="Registered customers listing">
    <div class="container">
        <?php if ($customersError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Customers Notice</p>
                <p><?php echo e($customersError); ?></p>
            </div>
        <?php endif; ?>

        <div class="checkout-card admin-panel-card admin-customers-card">
            <div class="admin-panel-header admin-products-header">
                <div>
                    <span class="eyebrow">01 / Customer Directory</span>
                    <h2 class="checkout-section-heading">
                        Registered Customers (<?php echo (int) $totalCustomersCount; ?>)
                    </h2>
                    <p class="admin-products-summary">
                        <span><strong><?php echo (int) $totalCustomersCount; ?></strong> Total Registered Accounts</span>
                    </p>
                </div>
            </div>

            <?php if (empty($customers)): ?>
                <!-- Empty Customers State -->
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">No registered customers found.</p>
                    <p class="admin-empty-text">
                        New customer accounts created through the storefront registration or checkout pages will appear here automatically.
                    </p>
                </div>
            <?php else: ?>
                <!-- Customers Table -->
                <div class="admin-table-wrapper">
                    <table class="admin-table admin-customers-table">
                        <thead>
                            <tr>
                                <th scope="col">Customer ID</th>
                                <th scope="col">Customer</th>
                                <th scope="col">Email</th>
                                <th scope="col">Phone</th>
                                <th scope="col">Registration Date</th>
                                <th scope="col" class="admin-col-actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customerRow): ?>
                                <?php
                                $rowCustomerId = (int) ($customerRow['customer_id'] ?? 0);

                                $firstName = trim((string) ($customerRow['first_name'] ?? ''));
                                $lastName  = trim((string) ($customerRow['last_name'] ?? ''));
                                $fullName  = trim($firstName . ' ' . $lastName);
                                if ($fullName === '') {
                                    $fullName = 'Customer #' . $rowCustomerId;
                                }

                                $email = trim((string) ($customerRow['email'] ?? ''));
                                $phone = trim((string) ($customerRow['phone'] ?? ''));

                                $rawCreatedAt  = trim((string) ($customerRow['created_at'] ?? ''));
                                $timestamp     = $rawCreatedAt !== '' ? strtotime($rawCreatedAt) : false;
                                $formattedDate = $timestamp !== false
                                    ? date('d M Y, H:i', $timestamp)
                                    : $rawCreatedAt;

                                $detailsUrl = site_url('admin/customer-details.php?id=' . $rowCustomerId);
                                ?>
                                <tr data-customer-id="<?php echo $rowCustomerId; ?>">
                                    <td class="admin-cell-id" data-label="Customer ID">
                                        <strong>#<?php echo $rowCustomerId; ?></strong>
                                    </td>

                                    <td data-label="Customer">
                                        <span class="admin-customer-name"><?php echo e($fullName); ?></span>
                                    </td>

                                    <td data-label="Email">
                                        <span><?php echo e($email); ?></span>
                                    </td>

                                    <td data-label="Phone">
                                        <?php if ($phone !== ''): ?>
                                            <span><?php echo e($phone); ?></span>
                                        <?php else: ?>
                                            <span class="admin-customer-email" aria-label="Not provided">&mdash;</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="admin-cell-date" data-label="Registration Date">
                                        <?php echo e($formattedDate); ?>
                                    </td>

                                    <td class="admin-cell-actions" data-label="Action">
                                        <a
                                            href="<?php echo e($detailsUrl); ?>"
                                            class="btn btn-outline admin-edit-btn"
                                            aria-label="View details for <?php echo e($fullName); ?>"
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
