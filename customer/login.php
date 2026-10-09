<?php
/**
 * Customer Login Page
 * Zazen Matcha & Tea Co.
 *
 * Authenticates existing customers against the `customers` table using
 * PDO prepared statements, CSRF protection, verify_password(), and
 * session_regenerate_id(true). Preserves $_SESSION['cart'] across sign-in.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/customer-auth.php';

ensure_session_started();

$loginUrl    = site_url('customer/login.php');
$registerUrl = site_url('customer/register.php');
$shopUrl     = site_url('customer/products.php');

// Choose the safest existing destination for authenticated redirects:
// Prefer customer/account.php once built; fallback to customer/products.php currently.
$defaultRedirectUrl = is_file(__DIR__ . '/account.php')
    ? site_url('customer/account.php')
    : $shopUrl;

// ----------------------------------------------------------------------------
// 1. Prevent Already-Authenticated Customers from Accessing Login Page
// ----------------------------------------------------------------------------
redirect_if_customer_logged_in($defaultRedirectUrl);

// ----------------------------------------------------------------------------
// 2. Initialise Form State & Error Containers
// ----------------------------------------------------------------------------
$email      = '';
$formErrors = [];

// ----------------------------------------------------------------------------
// 3. Handle POST Login Submission
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean and normalise email; keep raw password untouched
    $email = strtolower(clean_input($_POST['email'] ?? ''));
    $rawPassword = isset($_POST['password']) && !is_array($_POST['password'])
        ? (string) $_POST['password']
        : '';

    // 3a. Verify CSRF Token first (do not query for authentication if invalid)
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
    // 4. Authenticate Against MySQL (only when CSRF & field validation pass)
    // ------------------------------------------------------------------------
    if ($csrfValid && empty($formErrors)) {
        try {
            $customerSql = "
                SELECT
                    customer_id,
                    first_name,
                    last_name,
                    email,
                    password
                FROM customers
                WHERE email = :email
                LIMIT 1
            ";

            $customer = db_fetch_one($pdo, $customerSql, [
                'email' => $email,
            ]);

            if (
                $customer === null
                || !verify_password($rawPassword, (string) $customer['password'])
            ) {
                // Generic failure message to prevent account enumeration
                $formErrors['general'] = 'Invalid email or password.';
            } else {
                // 4a. Regenerate session ID to prevent session fixation
                // (preserves existing session data including $_SESSION['cart'])
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                // 4b. Establish authenticated customer session
                $customerId   = (int) $customer['customer_id'];
                $firstName    = trim((string) $customer['first_name']);
                $lastName     = trim((string) $customer['last_name']);
                $customerName = trim($firstName . ' ' . $lastName);

                $_SESSION['customer_id']   = $customerId;
                $_SESSION['customer_name'] = $customerName;

                // 4c. Determine safe post-login redirect destination
                $targetUrl = $defaultRedirectUrl;
                if (!empty($_SESSION['redirect_after_login']) && is_string($_SESSION['redirect_after_login'])) {
                    $savedTarget = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);

                    $targetFile = basename(parse_url($savedTarget, PHP_URL_PATH) ?? '');
                    if (
                        $targetFile !== ''
                        && !in_array($targetFile, ['login.php', 'register.php'], true)
                        && is_file(__DIR__ . '/' . $targetFile)
                    ) {
                        $targetUrl = site_url('customer/' . ltrim($savedTarget, '/'));
                    }
                }

                set_flash_message(
                    'success',
                    'Welcome back, ' . $firstName . '. You are now signed in.'
                );
                redirect($targetUrl);
            }
        } catch (Throwable $exception) {
            error_log('Customer Login Error: ' . $exception->getMessage());
            $formErrors['general'] = 'We could not sign you in right now. Please try again shortly.';
        }
    }
}

$pageTitle = 'Customer Sign In';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Login Page Hero -->
<section class="checkout-hero auth-hero" aria-labelledby="login-heading">
    <div class="container">
        <div class="checkout-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Customer Account</span>
                <h1 id="login-heading" class="checkout-title">Sign In</h1>
                <p class="checkout-subtitle">
                    Sign in to access your saved details and continue your tea ritual.
                </p>
            </div>
            <a href="<?php echo e($registerUrl); ?>" class="editorial-link checkout-back-link">
                New to Zazen Matcha? Create an Account &rarr;
            </a>
        </div>
    </div>
</section>

<!-- Main Login Section -->
<section class="section auth-section" aria-label="Customer sign in">
    <div class="container">
        <div class="auth-layout">
            <!-- Left Column: Login Form Card -->
            <div class="checkout-card auth-form-card">
                <h2 class="checkout-section-heading">Account Sign In</h2>

                <?php if (!empty($formErrors)): ?>
                    <div class="cart-warnings auth-form-alert" role="alert">
                        <p class="cart-warnings-title">Unable to sign in:</p>
                        <?php if (!empty($formErrors['general'])): ?>
                            <p><?php echo e($formErrors['general']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e($loginUrl); ?>" method="post" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            placeholder="name@example.com"
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
                            Sign In
                        </button>
                    </div>

                    <div class="auth-form-footer">
                        <p>
                            Don&rsquo;t have a Zazen Matcha account yet?
                            <a href="<?php echo e($registerUrl); ?>" class="editorial-link">Create an account &rarr;</a>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Right Column: Editorial Panel -->
            <aside class="auth-editorial-card" aria-labelledby="signin-editorial-heading">
                <span class="eyebrow">Return to the Studio</span>
                <h2 id="signin-editorial-heading" class="auth-editorial-title">
                    Seamless Continuation
                </h2>
                <p class="auth-editorial-intro">
                    Sign in to connect your active shopping bag with your saved delivery details and order history.
                </p>

                <dl class="auth-benefit-list">
                    <div class="auth-benefit-item">
                        <dt>01 / Preserved Shopping Bag</dt>
                        <dd>Any matcha powders or vessels already in your cart remain untouched when you sign in.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>02 / Faster Checkout</dt>
                        <dd>Your saved contact and Cape Town or nationwide delivery details pre-populate automatically.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>03 / Order Records</dt>
                        <dd>Keep your purchases linked to your personal customer profile.</dd>
                    </div>
                </dl>

                <div class="auth-editorial-footer">
                    <a href="<?php echo e($registerUrl); ?>" class="btn btn-outline auth-browse-btn">
                        Create New Account
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
