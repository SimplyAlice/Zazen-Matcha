<?php
/**
 * Administrator Settings Overview Page (Read-Only)
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page displaying Store Information, live read-only
 * Database Status verification, current signed-in Admin Account details,
 * Security Notes, and Quick Links to core administration modules.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$adminName  = get_logged_in_admin_name() ?? 'Administrator';
$adminEmail = get_logged_in_admin_email() ?? '';

$dashboardUrl  = site_url('admin/dashboard.php');
$productsUrl   = site_url('admin/products.php');
$ordersUrl     = site_url('admin/orders.php');
$customersUrl  = site_url('admin/customers.php');
$categoriesUrl = site_url('admin/categories.php');

// ----------------------------------------------------------------------------
// 1. Live Read-Only Database Connectivity Check
// ----------------------------------------------------------------------------
$dbConnected     = false;
$dbStatusLabel   = 'Unavailable';
$dbStatusMessage = 'Unable to verify database connectivity right now. Please try again shortly.';

try {
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Database connection instance is not available.');
    }

    $healthCheckRow = db_fetch_one($pdo, 'SELECT 1 AS connection_ok');

    if (is_array($healthCheckRow) && (int) ($healthCheckRow['connection_ok'] ?? 0) === 1) {
        $dbConnected     = true;
        $dbStatusLabel   = 'Connected';
        $dbStatusMessage = 'The store database connection is active and responding normally.';
    } else {
        throw new RuntimeException('Unexpected database health check response.');
    }
} catch (Throwable $exception) {
    error_log('Admin Settings Database Check Error: ' . $exception->getMessage());
    $dbConnected     = false;
    $dbStatusLabel   = 'Unavailable';
    $dbStatusMessage = 'Unable to verify database connectivity right now. Please try again shortly.';
}

$pageTitle = 'Settings';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Hero / Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-settings-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; System Overview</span>
                <h1 id="admin-settings-heading" class="checkout-title">Settings</h1>
                <p class="checkout-subtitle">
                    Read-only overview of store identity, database connectivity, signed-in administrator profile, and security standards.
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

<!-- 2. Main Settings Overview Content -->
<section class="section admin-dashboard-section admin-settings-section" aria-label="Store settings and system overview">
    <div class="container">
        <?php if (!$dbConnected): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Database Status Notice</p>
                <p><?php echo e($dbStatusMessage); ?></p>
            </div>
        <?php endif; ?>

        <div class="admin-dashboard-grid">
            <!-- Left / Main Column: Store Information, Database Status, Admin Account & Security Notes -->
            <div class="checkout-card admin-panel-card">
                <!-- Panel 1: Store Information -->
                <div class="admin-panel-header">
                    <div>
                        <span class="eyebrow">01 / Store Identity</span>
                        <h2 class="checkout-section-heading">Store Information</h2>
                    </div>
                </div>

                <div class="confirmation-info-grid">
                    <div class="confirmation-info-block">
                        <span class="confirmation-meta-label">Store Name</span>
                        <p class="confirmation-info-value">
                            <strong>Zazen Matcha &amp; Tea Co.</strong>
                        </p>
                        <p class="confirmation-info-sub">
                            Japanese Ceremonial Matcha, Preparation Vessels &amp; Curated Tea Gift Sets
                        </p>
                    </div>

                    <div class="confirmation-info-block">
                        <span class="confirmation-meta-label">Storefront Currency &amp; Region</span>
                        <p class="confirmation-info-value">
                            <strong>South African Rand (ZAR / R)</strong>
                        </p>
                        <p class="confirmation-info-sub">
                            University PHP &amp; MySQL e-Commerce Assignment Prototype
                        </p>
                    </div>
                </div>

                <p class="account-section-note">
                    Zazen Matcha is a minimalist Japanese matcha and tea e-commerce storefront providing ceremonial and everyday matcha powders, traditional bamboo whisks, handcrafted ceramic bowls, and gift sets.
                </p>

                <hr class="summary-divider">

                <!-- Panel 2: Database Status -->
                <div class="admin-panel-header">
                    <div>
                        <span class="eyebrow">02 / System Health</span>
                        <h2 class="checkout-section-heading">Database Status</h2>
                    </div>
                    <span class="status-badge <?php echo $dbConnected ? 'status-product-active' : 'status-payment-failed'; ?>">
                        <?php echo e($dbStatusLabel); ?>
                    </span>
                </div>

                <div class="confirmation-info-grid">
                    <div class="confirmation-info-block">
                        <span class="confirmation-meta-label">Connection State</span>
                        <p class="confirmation-info-value">
                            <strong><?php echo e($dbStatusLabel); ?></strong>
                        </p>
                        <p class="confirmation-info-sub">
                            <?php echo e($dbStatusMessage); ?>
                        </p>
                    </div>

                    <div class="confirmation-info-block">
                        <span class="confirmation-meta-label">Data Access Layer</span>
                        <p class="confirmation-info-value">
                            <strong>PDO Prepared Statements</strong>
                        </p>
                        <p class="confirmation-info-sub">
                            Read-only status verification completed via shared database connection.
                        </p>
                    </div>
                </div>

                <hr class="summary-divider">

                <!-- Panel 3: Admin Account -->
                <div class="admin-panel-header">
                    <div>
                        <span class="eyebrow">03 / Active Session</span>
                        <h2 class="checkout-section-heading">Admin Account</h2>
                    </div>
                </div>

                <div class="confirmation-info-grid">
                    <div class="confirmation-info-block">
                        <span class="confirmation-meta-label">Administrator Name</span>
                        <p class="confirmation-info-value">
                            <strong><?php echo e($adminName); ?></strong>
                        </p>
                    </div>

                    <div class="confirmation-info-block">
                        <span class="confirmation-meta-label">Administrator Email</span>
                        <p class="confirmation-info-value">
                            <?php if ($adminEmail !== ''): ?>
                                <strong><?php echo e($adminEmail); ?></strong>
                            <?php else: ?>
                                <span class="admin-customer-email">&mdash;</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <hr class="summary-divider">

                <!-- Panel 4: Security Notes -->
                <div class="admin-panel-header">
                    <div>
                        <span class="eyebrow">04 / Protection Standards</span>
                        <h2 class="checkout-section-heading">Security Notes</h2>
                    </div>
                </div>

                <dl class="auth-benefit-list">
                    <div class="auth-benefit-item">
                        <dt>Administrator Authentication</dt>
                        <dd>All administration pages enforce session-based access control via <code>require_admin_login()</code> and redirect unauthenticated visitors to the administrator sign-in page.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>CSRF Form Protection</dt>
                        <dd>All state-changing administration forms generate and verify synchroniser tokens via <code>csrf_token()</code> and <code>verify_csrf_token()</code> before processing POST requests.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>Prepared Queries &amp; Output Escaping</dt>
                        <dd>Database operations use parameterised PDO statements and dynamic HTML output is escaped with <code>e()</code>. Passwords, hashes, and connection credentials are never displayed.</dd>
                    </div>
                </dl>
            </div>

            <!-- Right / Sidebar Column: Quick Links -->
            <aside class="auth-editorial-card admin-quicklinks-card" aria-labelledby="settings-quick-links-heading">
                <span class="eyebrow">05 / Administration Navigation</span>
                <h2 id="settings-quick-links-heading" class="auth-editorial-title">
                    Quick Links
                </h2>
                <p class="auth-editorial-intro">
                    Jump directly to any administration module to review store metrics, inventory, categories, customer orders, or accounts.
                </p>

                <nav class="admin-quicklinks-nav" aria-label="Administration quick links">
                    <ul class="admin-quicklinks-list">
                        <li>
                            <a href="<?php echo e($dashboardUrl); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Dashboard</span>
                                    <span class="admin-quicklink-desc">View live store metrics and recent customer order activity</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e($productsUrl); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Products</span>
                                    <span class="admin-quicklink-desc">Manage ceremonial matcha powders, vessels, and stock levels</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e($ordersUrl); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Orders</span>
                                    <span class="admin-quicklink-desc">Inspect customer orders and update payment or fulfilment status</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e($customersUrl); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Customers</span>
                                    <span class="admin-quicklink-desc">Review registered customer accounts and order history</span>
                                </div>
                                <span class="admin-quicklink-arrow" aria-hidden="true">&rarr;</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e($categoriesUrl); ?>" class="admin-quicklink-item">
                                <div>
                                    <span class="admin-quicklink-title">Categories</span>
                                    <span class="admin-quicklink-desc">Organise product categories across Matcha, Accessories, and Gift Sets</span>
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
