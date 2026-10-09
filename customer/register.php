<?php
/**
 * Customer Registration Page
 * Zazen Matcha & Tea Co.
 *
 * Allows a new customer to register an account in the `customers` table
 * using server-side validation, CSRF protection, PDO prepared statements,
 * bcrypt password hashing via hash_password(), and race-safe duplicate-email
 * handling. Preserves existing session state (including $_SESSION['cart']).
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/customer-auth.php';

ensure_session_started();

$registerUrl = site_url('customer/register.php');
$loginUrl    = site_url('customer/login.php');
$shopUrl     = site_url('customer/products.php');

// Choose the safest existing destination for authenticated redirects:
// Prefer customer/account.php once built; fallback to customer/products.php currently.
$postAuthRedirectUrl = is_file(__DIR__ . '/account.php')
    ? site_url('customer/account.php')
    : $shopUrl;

// ----------------------------------------------------------------------------
// 1. Prevent Already-Authenticated Customers from Registering Again
// ----------------------------------------------------------------------------
redirect_if_customer_logged_in($postAuthRedirectUrl);

// ----------------------------------------------------------------------------
// 2. Initialise Form State & Error Containers
// ----------------------------------------------------------------------------
$formData = [
    'first_name' => '',
    'last_name'  => '',
    'email'      => '',
    'phone'      => '',
];

$formErrors       = [];
$isDuplicateEmail = false;

// ----------------------------------------------------------------------------
// 3. Handle POST Registration Submission
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean and preserve non-password inputs
    $formData['first_name'] = clean_input($_POST['first_name'] ?? '');
    $formData['last_name']  = clean_input($_POST['last_name'] ?? '');
    $formData['email']      = strtolower(clean_input($_POST['email'] ?? ''));
    $formData['phone']      = clean_input($_POST['phone'] ?? '');

    // Read raw password inputs without transforming user-typed characters
    $rawPassword = isset($_POST['password']) && !is_array($_POST['password'])
        ? (string) $_POST['password']
        : '';
    $rawConfirmPassword = isset($_POST['confirm_password']) && !is_array($_POST['confirm_password'])
        ? (string) $_POST['confirm_password']
        : '';

    // 3a. Verify CSRF Token
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    if (!verify_csrf_token($submittedToken)) {
        $formErrors['general'] = 'Invalid security token. Please review your details and submit the form again.';
    }

    // 3b. Validate First Name
    if ($formData['first_name'] === '') {
        $formErrors['first_name'] = 'First name is required.';
    } elseif (mb_strlen($formData['first_name']) > 100) {
        $formErrors['first_name'] = 'First name must not exceed 100 characters.';
    }

    // 3c. Validate Last Name
    if ($formData['last_name'] === '') {
        $formErrors['last_name'] = 'Last name is required.';
    } elseif (mb_strlen($formData['last_name']) > 100) {
        $formErrors['last_name'] = 'Last name must not exceed 100 characters.';
    }

    // 3d. Validate Email Address
    if ($formData['email'] === '') {
        $formErrors['email'] = 'Email address is required.';
    } elseif (mb_strlen($formData['email']) > 150) {
        $formErrors['email'] = 'Email address must not exceed 150 characters.';
    } elseif (filter_var($formData['email'], FILTER_VALIDATE_EMAIL) === false) {
        $formErrors['email'] = 'Please enter a valid email address.';
    }

    // 3e. Validate Phone Number (compatible with checkout validation & SA formats)
    if ($formData['phone'] === '') {
        $formErrors['phone'] = 'Phone number is required.';
    } elseif (mb_strlen($formData['phone']) > 30) {
        $formErrors['phone'] = 'Phone number must not exceed 30 characters.';
    } elseif (
        !preg_match('/^[0-9+\-\s()]{7,30}$/', $formData['phone'])
        || preg_match_all('/[0-9]/', $formData['phone']) < 7
    ) {
        $formErrors['phone'] = 'Please enter a valid phone number (e.g. 082 123 4567 or +27 82 123 4567).';
    }

    // 3f. Validate Password & Confirmation
    if ($rawPassword === '') {
        $formErrors['password'] = 'Password is required.';
    } elseif (mb_strlen($rawPassword) < 8) {
        $formErrors['password'] = 'Password must be at least 8 characters long.';
    } elseif (mb_strlen($rawPassword) > 255) {
        $formErrors['password'] = 'Password must not exceed 255 characters.';
    }

    if ($rawConfirmPassword === '') {
        $formErrors['confirm_password'] = 'Please confirm your password.';
    } elseif ($rawPassword !== '' && $rawPassword !== $rawConfirmPassword) {
        $formErrors['confirm_password'] = 'Passwords do not match.';
    }

    // ------------------------------------------------------------------------
    // 4. Check for Duplicate Email & Insert New Customer Record
    // ------------------------------------------------------------------------
    if (empty($formErrors)) {
        try {
            // 4a. Check if the email is already registered
            $existingCustomer = db_fetch_one(
                $pdo,
                'SELECT customer_id FROM customers WHERE email = :email LIMIT 1',
                ['email' => $formData['email']]
            );

            if ($existingCustomer !== null) {
                $isDuplicateEmail    = true;
                $formErrors['email'] = 'This email is already registered. Please sign in instead.';
            } else {
                // 4b. Hash password securely using the existing helper
                $passwordHash = hash_password($rawPassword);

                // 4c. Insert new customer row via PDO prepared statement
                $insertSql = "
                    INSERT INTO customers (
                        first_name,
                        last_name,
                        email,
                        phone,
                        password
                    ) VALUES (
                        :first_name,
                        :last_name,
                        :email,
                        :phone,
                        :password
                    )
                ";

                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    'first_name' => $formData['first_name'],
                    'last_name'  => $formData['last_name'],
                    'email'      => $formData['email'],
                    'phone'      => $formData['phone'],
                    'password'   => $passwordHash,
                ]);

                $newCustomerId = (int) $pdo->lastInsertId();
                if ($newCustomerId <= 0) {
                    throw new RuntimeException('Failed to retrieve newly created customer ID.');
                }

                // 4d. Regenerate session ID safely while preserving $_SESSION['cart']
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                // 4e. Establish customer login session using keys expected by customer-auth.php
                $_SESSION['customer_id']   = $newCustomerId;
                $_SESSION['customer_name'] = trim($formData['first_name'] . ' ' . $formData['last_name']);

                set_flash_message(
                    'success',
                    'Welcome to Zazen Matcha, ' . $formData['first_name'] . '. Your account has been created.'
                );
                redirect($postAuthRedirectUrl);
            }
        } catch (PDOException $pdoException) {
            // Race-safe duplicate email detection (SQLSTATE 23000 / MySQL error 1062)
            $sqlState   = (string) $pdoException->getCode();
            $driverCode = isset($pdoException->errorInfo[1]) ? (int) $pdoException->errorInfo[1] : 0;

            if ($sqlState === '23000' || $driverCode === 1062) {
                $isDuplicateEmail    = true;
                $formErrors['email'] = 'This email is already registered. Please sign in instead.';
            } else {
                error_log('Customer Registration PDO Error: ' . $pdoException->getMessage());
                $formErrors['general'] = 'We could not complete your registration at this moment. Please try again shortly.';
            }
        } catch (Throwable $exception) {
            error_log('Customer Registration Error: ' . $exception->getMessage());
            $formErrors['general'] = 'We could not complete your registration at this moment. Please try again shortly.';
        }
    }
}

$pageTitle = 'Create Account';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Registration Page Hero -->
<section class="checkout-hero auth-hero" aria-labelledby="register-heading">
    <div class="container">
        <div class="checkout-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Customer Account</span>
                <h1 id="register-heading" class="checkout-title">Create an Account</h1>
                <p class="checkout-subtitle">
                    Join our Cape Town tea studio for streamlined ordering and access to your personal order history.
                </p>
            </div>
            <a href="<?php echo e($loginUrl); ?>" class="editorial-link checkout-back-link">
                Already registered? Sign In &rarr;
            </a>
        </div>
    </div>
</section>

<!-- Main Registration Section -->
<section class="section auth-section" aria-label="Customer account registration">
    <div class="container">
        <div class="auth-layout">
            <!-- Left Column: Registration Form Card -->
            <div class="checkout-card auth-form-card">
                <h2 class="checkout-section-heading">Account Details</h2>

                <?php if (!empty($formErrors)): ?>
                    <div class="cart-warnings auth-form-alert" role="alert">
                        <p class="cart-warnings-title">Please review your registration details:</p>
                        <?php if (!empty($formErrors['general'])): ?>
                            <p><?php echo e($formErrors['general']); ?></p>
                        <?php endif; ?>
                        <?php if ($isDuplicateEmail): ?>
                            <p class="auth-duplicate-notice">
                                <?php echo e($formErrors['email']); ?>
                                <a href="<?php echo e($loginUrl); ?>" class="auth-alert-link">Sign in to your account &rarr;</a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e($registerUrl); ?>" method="post" class="auth-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">

                    <!-- Name Fields -->
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
                                <?php echo isset($formErrors['first_name']) ? 'aria-invalid="true" aria-describedby="error-first-name"' : ''; ?>
                            >
                            <?php if (isset($formErrors['first_name'])): ?>
                                <span id="error-first-name" class="form-error"><?php echo e($formErrors['first_name']); ?></span>
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
                                <?php echo isset($formErrors['last_name']) ? 'aria-invalid="true" aria-describedby="error-last-name"' : ''; ?>
                            >
                            <?php if (isset($formErrors['last_name'])): ?>
                                <span id="error-last-name" class="form-error"><?php echo e($formErrors['last_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contact Fields -->
                    <div class="checkout-field-grid">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                maxlength="150"
                                placeholder="name@example.com"
                                value="<?php echo e($formData['email']); ?>"
                                required
                                autocomplete="email"
                                <?php echo isset($formErrors['email']) ? 'aria-invalid="true" aria-describedby="error-email"' : ''; ?>
                            >
                            <?php if (isset($formErrors['email'])): ?>
                                <span id="error-email" class="form-error">
                                    <?php echo e($formErrors['email']); ?>
                                    <?php if ($isDuplicateEmail): ?>
                                        <a href="<?php echo e($loginUrl); ?>" class="auth-inline-link">Sign in instead</a>
                                    <?php endif; ?>
                                </span>
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
                                <?php echo isset($formErrors['phone']) ? 'aria-invalid="true" aria-describedby="error-phone"' : ''; ?>
                            >
                            <?php if (isset($formErrors['phone'])): ?>
                                <span id="error-phone" class="form-error"><?php echo e($formErrors['phone']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Password Fields (never redisplayed after validation failure) -->
                    <div class="checkout-field-grid">
                        <div class="form-group">
                            <label for="password" class="form-label">Password *</label>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                minlength="8"
                                maxlength="255"
                                required
                                autocomplete="new-password"
                                aria-describedby="password-hint<?php echo isset($formErrors['password']) ? ' error-password' : ''; ?>"
                                <?php echo isset($formErrors['password']) ? 'aria-invalid="true"' : ''; ?>
                            >
                            <span id="password-hint" class="form-hint">Minimum 8 characters.</span>
                            <?php if (isset($formErrors['password'])): ?>
                                <span id="error-password" class="form-error"><?php echo e($formErrors['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                minlength="8"
                                maxlength="255"
                                required
                                autocomplete="new-password"
                                <?php echo isset($formErrors['confirm_password']) ? 'aria-invalid="true" aria-describedby="error-confirm-password"' : ''; ?>
                            >
                            <?php if (isset($formErrors['confirm_password'])): ?>
                                <span id="error-confirm-password" class="form-error"><?php echo e($formErrors['confirm_password']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="auth-form-actions">
                        <button type="submit" class="btn btn-primary auth-submit-btn">
                            Create Account
                        </button>
                    </div>

                    <div class="auth-form-footer">
                        <p>
                            Already have a Zazen Matcha account?
                            <a href="<?php echo e($loginUrl); ?>" class="editorial-link">Sign in instead &rarr;</a>
                        </p>
                    </div>
                </form>
            </div>

            <!-- Right Column: Editorial Account Benefits Panel -->
            <aside class="auth-editorial-card" aria-labelledby="membership-benefits-heading">
                <span class="eyebrow">Quiet Ritual, Personalised</span>
                <h2 id="membership-benefits-heading" class="auth-editorial-title">
                    A Considered Experience
                </h2>
                <p class="auth-editorial-intro">
                    Creating a customer profile connects your tea preferences and order records across every visit.
                </p>

                <dl class="auth-benefit-list">
                    <div class="auth-benefit-item">
                        <dt>01 / Streamlined Checkout</dt>
                        <dd>Your contact details are pre-filled automatically whenever you place an order.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>02 / Complete Order Archive</dt>
                        <dd>Review past ceremonial matcha selections, quantities, and delivery statuses.</dd>
                    </div>
                    <div class="auth-benefit-item">
                        <dt>03 / Cart Continuity</dt>
                        <dd>Any items currently in your shopping bag remain preserved when you register.</dd>
                    </div>
                </dl>

                <div class="auth-editorial-footer">
                    <a href="<?php echo e($shopUrl); ?>" class="btn btn-outline auth-browse-btn">
                        Explore the Collection
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
