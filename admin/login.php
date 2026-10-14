<?php
/**
 * Administrator Login Page
 * Zazen Matcha & Tea Co.
 *
 * Authenticates administrators against the `admins` table using PDO prepared
 * statements, CSRF protection, verify_password(), and session_regenerate_id(true).
 * Uses dedicated admin session keys ($_SESSION['admin_id'], $_SESSION['admin_name'],
 * $_SESSION['admin_email']) without altering customer auth state or $_SESSION['cart'].
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

ensure_session_started();

$adminLoginUrl     = site_url('admin/login.php');
$adminDashboardUrl = site_url('admin/dashboard.php');
$storefrontUrl     = site_url('index.php');

// ----------------------------------------------------------------------------
// 1. Redirect Already-Authenticated Administrators to Dashboard
// ----------------------------------------------------------------------------
if (is_admin_logged_in()) {
    redirect($adminDashboardUrl);
}

// ----------------------------------------------------------------------------
// 2. Initialise Form State & Error Containers
// ----------------------------------------------------------------------------
$email      = '';
$formErrors = [];

// ----------------------------------------------------------------------------
// 3. Handle POST Admin Login Submission
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean and normalise email; keep raw password untouched
    $email = strtolower(clean_input($_POST['email'] ?? ''));
    $rawPassword = isset($_POST['password']) && !is_array($_POST['password'])
        ? (string) $_POST['password']
        : '';

    // 3a. Verify CSRF Token first (do not query database if invalid)
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    $csrfValid = verify_csrf_token($submittedToken);
    if (!$csrfValid) {
        $formErrors['general'] = 'Invalid security token. Please try signing in again.';
    }

    // 3b. Server-Side Field Validation
    if ($email === '') {
        $formErrors['email'] = 'Email address is required.';
    } elseif (mb_strlen($email) > 150) {
        $formErrors['email'] = 'Email address must not exceed 150 characters.';
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $formErrors['email'] = 'Please enter a valid email address.';
    }

    if ($rawPassword === '') {
        $formErrors['password'] = 'Password is required.';
    }

    // ------------------------------------------------------------------------
    // 4. Authenticate Against `admins` Table (when CSRF & validation pass)
    // ------------------------------------------------------------------------
    if ($csrfValid && empty($formErrors)) {
        try {
            $adminSql = "
                SELECT
                    admin_id,
                    name,
                    email,
                    password
                FROM admins
                WHERE email = :email
                LIMIT 1
            ";

            $admin = db_fetch_one($pdo, $adminSql, [
                'email' => $email,
            ]);

            if (
                $admin === null
                || !verify_password($rawPassword, (string) $admin['password'])
            ) {
                // Single generic message to prevent account enumeration
                $formErrors['general'] = 'Invalid email or password.';
            } else {
                // 4a. Regenerate session ID to prevent session fixation
                // (preserves existing session data including $_SESSION['cart'] and customer session)
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                // 4b. Establish admin-specific session state
                $_SESSION['admin_id']    = (int) $admin['admin_id'];
                $_SESSION['admin_name']  = trim((string) $admin['name']);
                $_SESSION['admin_email'] = trim((string) $admin['email']);

                set_flash_message(
                    'success',
                    'Welcome back, ' . $_SESSION['admin_name'] . '. Administrator session active.'
                );

                redirect($adminDashboardUrl);
            }
        } catch (Throwable $exception) {
            error_log('Admin Login Error: ' . $exception->getMessage());
            $formErrors['general'] = 'Unable to process administrator sign in at this time. Please try again shortly.';
        }
    }
}

$flashMessage  = get_flash_message();
$documentTitle = 'Admin Sign In — Zazen Matcha & Tea Co.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($documentTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('css/style.css')); ?>">
</head>
<body class="admin-auth-body">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Minimal Admin Top Header -->
    <header class="site-header admin-auth-header">
        <div class="container header-inner">
            <div class="admin-brand-group">
                <a href="<?php echo e($storefrontUrl); ?>" class="brand-logo" aria-label="Zazen Matcha & Tea Co. Storefront">
                    <span class="brand-name">Zazen Matcha</span>
                    <span class="brand-subtitle">Tea Co.</span>
                </a>
                <span class="admin-portal-badge">Administration</span>
            </div>

            <div class="admin-header-actions">
                <a href="<?php echo e($storefrontUrl); ?>" class="editorial-link admin-storefront-link">
                    &larr; Return to Customer Storefront
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" class="site-main admin-auth-main">
        <?php if ($flashMessage !== null): ?>
            <div class="container flash-container">
                <div class="flash-message flash-<?php echo e($flashMessage['type']); ?>" role="status" data-flash-message>
                    <p class="flash-text"><?php echo e($flashMessage['message']); ?></p>
                    <button type="button" class="flash-close" data-flash-dismiss aria-label="Dismiss notification">
                        &times;
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Admin Hero Banner -->
        <section class="checkout-hero admin-auth-hero" aria-labelledby="admin-login-heading">
            <div class="container">
                <div class="checkout-hero-inner">
                    <div>
                        <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Management Console</span>
                        <h1 id="admin-login-heading" class="checkout-title">Administrator Sign In</h1>
                        <p class="checkout-subtitle">
                            Authorised access for catalogue management, inventory control, and customer order fulfilment.
                        </p>
                    </div>
                    <p class="admin-security-note">
                        Protected session &middot; Authorised personnel only
                    </p>
                </div>
            </div>
        </section>

        <!-- Admin Sign-In Section -->
        <section class="section auth-section admin-auth-section" aria-label="Administrator sign in">
            <div class="container">
                <div class="auth-layout admin-auth-layout">
                    <!-- Left Column: Admin Login Card -->
                    <div class="checkout-card auth-form-card admin-login-card">
                        <div class="admin-card-header">
                            <span class="eyebrow">01 / Credentials</span>
                            <h2 class="checkout-section-heading">Management Portal Access</h2>
                        </div>

                        <?php if (!empty($formErrors)): ?>
                            <div class="cart-warnings auth-form-alert" role="alert">
                                <p class="cart-warnings-title">Authentication notice:</p>
                                <?php if (!empty($formErrors['general'])): ?>
                                    <p><?php echo e($formErrors['general']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form action="<?php echo e($adminLoginUrl); ?>" method="post" class="auth-form admin-login-form" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                            <div class="form-group">
                                <label for="email" class="form-label">Administrator Email *</label>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    maxlength="150"
                                    placeholder="admin@zazenmatcha.test"
                                    value="<?php echo e($email); ?>"
                                    required
                                    autocomplete="email"
                                    <?php echo isset($formErrors['email']) ? 'aria-invalid="true" aria-describedby="error-email"' : ''; ?>
                                >
                                <?php if (isset($formErrors['email'])): ?>
                                    <span id="error-email" class="form-error"><?php echo e($formErrors['email']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="password" class="form-label">Password *</label>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    maxlength="255"
                                    placeholder="Enter administrator password"
                                    required
                                    autocomplete="current-password"
                                    <?php echo isset($formErrors['password']) ? 'aria-invalid="true" aria-describedby="error-password"' : ''; ?>
                                >
                                <?php if (isset($formErrors['password'])): ?>
                                    <span id="error-password" class="form-error"><?php echo e($formErrors['password']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="auth-form-actions">
                                <button type="submit" class="btn btn-primary auth-submit-btn">
                                    Sign In to Dashboard
                                </button>
                            </div>

                            <div class="auth-form-footer admin-form-footer">
                                <p>
                                    Looking for the customer shop?
                                    <a href="<?php echo e($storefrontUrl); ?>" class="editorial-link">Return to customer homepage &rarr;</a>
                                </p>
                            </div>
                        </form>
                    </div>

                    <!-- Right Column: Operational Scope & Security Panel -->
                    <aside class="auth-editorial-card admin-ops-card" aria-labelledby="admin-ops-heading">
                        <span class="eyebrow">02 / System Overview</span>
                        <h2 id="admin-ops-heading" class="auth-editorial-title">
                            Storefront Operations
                        </h2>
                        <p class="auth-editorial-intro">
                            This console connects directly to the Zazen Matcha &amp; Tea Co. MySQL database for live catalogue, stock, and order administration.
                        </p>

                        <dl class="auth-benefit-list">
                            <div class="auth-benefit-item">
                                <dt>Catalogue &amp; Stock Control</dt>
                                <dd>Manage ceremonial matcha, specialty teas, and teaware listings, pricing in ZAR, and available stock quantities.</dd>
                            </div>
                            <div class="auth-benefit-item">
                                <dt>Order &amp; Fulfilment Tracking</dt>
                                <dd>Review customer orders, shipping destinations, payment records, and fulfilment statuses.</dd>
                            </div>
                            <div class="auth-benefit-item">
                                <dt>Isolated Admin Session</dt>
                                <dd>Administrator authentication operates independently of customer accounts and active shopping carts.</dd>
                            </div>
                        </dl>

                        <div class="auth-editorial-footer">
                            <a href="<?php echo e($storefrontUrl); ?>" class="btn btn-outline auth-browse-btn">
                                View Customer Storefront
                            </a>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </main>

    <!-- Minimal Admin Footer -->
    <footer class="site-footer admin-auth-footer">
        <div class="container">
            <div class="footer-bottom admin-footer-bottom">
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> Zazen Matcha &amp; Tea Co. &mdash; Administration Console
                </p>
                <p class="footer-note">
                    <a href="<?php echo e($storefrontUrl); ?>">Customer Storefront</a>
                    &middot; University e-Commerce Project Prototype
                </p>
            </div>
        </div>
    </footer>

    <script src="<?php echo e(site_url('js/script.js')); ?>" defer></script>
</body>
</html>
