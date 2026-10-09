<?php
/**
 * Customer Account & Profile Page
 * Zazen Matcha & Tea Co.
 *
 * Protected customer profile page allowing an authenticated customer to:
 * - View and update their profile & saved delivery details
 * - Change their account password using verify_password() and hash_password()
 * - View their 5 most recent orders linked to customer/order-confirmation.php
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/customer-auth.php';

ensure_session_started();

// ----------------------------------------------------------------------------
// 1. Require Customer Authentication
// ----------------------------------------------------------------------------
require_customer_login();

$customerId = get_logged_in_customer_id();
$accountUrl = site_url('customer/account.php');
$loginUrl   = site_url('customer/login.php');
$logoutUrl  = site_url('customer/logout.php');
$shopUrl    = site_url('customer/products.php');

// Allowed South African provinces for optional address validation
$allowedProvinces = [
    'Eastern Cape',
    'Free State',
    'Gauteng',
    'KwaZulu-Natal',
    'Limpopo',
    'Mpumalanga',
    'Northern Cape',
    'North West',
    'Western Cape',
];

// Friendly presentation labels for order history badges
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
// 2. Load Authenticated Customer Profile (excluding password hash)
// ----------------------------------------------------------------------------
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
            postal_code
        FROM customers
        WHERE customer_id = :customer_id
        LIMIT 1
    ";

    $customer = db_fetch_one($pdo, $customerSql, [
        'customer_id' => $customerId,
    ]);
} catch (Throwable $exception) {
    error_log('Customer Account Load Error: ' . $exception->getMessage());
    $customer = null;
}

if ($customer === null) {
    // Clear invalid customer session variables safely while keeping cart intact
    unset($_SESSION['customer_id'], $_SESSION['customer_name']);
    set_flash_message('error', 'Your account session could not be verified. Please sign in again.');
    redirect($loginUrl);
}

// Initialise profile form state from database record
$profileForm = [
    'first_name'  => (string) ($customer['first_name'] ?? ''),
    'last_name'   => (string) ($customer['last_name'] ?? ''),
    'phone'       => (string) ($customer['phone'] ?? ''),
    'address'     => (string) ($customer['address'] ?? ''),
    'city'        => (string) ($customer['city'] ?? ''),
    'province'    => (string) ($customer['province'] ?? ''),
    'postal_code' => (string) ($customer['postal_code'] ?? ''),
];

$profileErrors  = [];
$passwordErrors = [];

// ----------------------------------------------------------------------------
// 3. Handle POST Actions (update_profile | change_password)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) && !is_array($_POST['action'])
        ? trim((string) $_POST['action'])
        : '';

    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    $csrfValid = verify_csrf_token($submittedToken);

    // ------------------------------------------------------------------------
    // Action A: Update Profile & Delivery Information
    // ------------------------------------------------------------------------
    if ($action === 'update_profile') {
        $profileForm['first_name']  = clean_input($_POST['first_name'] ?? '');
        $profileForm['last_name']   = clean_input($_POST['last_name'] ?? '');
        $profileForm['phone']       = clean_input($_POST['phone'] ?? '');
        $profileForm['address']     = clean_input($_POST['address'] ?? '');
        $profileForm['city']        = clean_input($_POST['city'] ?? '');
        $profileForm['province']    = clean_input($_POST['province'] ?? '');
        $profileForm['postal_code'] = clean_input($_POST['postal_code'] ?? '');

        if (!$csrfValid) {
            $profileErrors['general'] = 'Invalid security token. Please review your details and try again.';
        }

        // First Name (required, <= 100 chars)
        if ($profileForm['first_name'] === '') {
            $profileErrors['first_name'] = 'First name is required.';
        } elseif (mb_strlen($profileForm['first_name']) > 100) {
            $profileErrors['first_name'] = 'First name must not exceed 100 characters.';
        }

        // Last Name (required, <= 100 chars)
        if ($profileForm['last_name'] === '') {
            $profileErrors['last_name'] = 'Last name is required.';
        } elseif (mb_strlen($profileForm['last_name']) > 100) {
            $profileErrors['last_name'] = 'Last name must not exceed 100 characters.';
        }

        // Phone (optional or required; if provided, validate format & max 30 chars)
        if ($profileForm['phone'] !== '') {
            if (mb_strlen($profileForm['phone']) > 30) {
                $profileErrors['phone'] = 'Phone number must not exceed 30 characters.';
            } elseif (
                !preg_match('/^[0-9+\-\s()]{7,30}$/', $profileForm['phone'])
                || preg_match_all('/[0-9]/', $profileForm['phone']) < 7
            ) {
                $profileErrors['phone'] = 'Please enter a valid phone number (e.g. 082 123 4567 or +27 82 123 4567).';
            }
        }

        // Address (optional, <= 255 chars)
        if ($profileForm['address'] !== '' && mb_strlen($profileForm['address']) > 255) {
            $profileErrors['address'] = 'Address must not exceed 255 characters.';
        }

        // City (optional, <= 100 chars)
        if ($profileForm['city'] !== '' && mb_strlen($profileForm['city']) > 100) {
            $profileErrors['city'] = 'City must not exceed 100 characters.';
        }

        // Province (optional, <= 100 chars, valid SA province if provided)
        if ($profileForm['province'] !== '') {
            if (mb_strlen($profileForm['province']) > 100 || !in_array($profileForm['province'], $allowedProvinces, true)) {
                $profileErrors['province'] = 'Please select a valid South African province.';
            }
        }

        // Postal Code (optional, <= 20 chars)
        if ($profileForm['postal_code'] !== '') {
            if (mb_strlen($profileForm['postal_code']) > 20 || !preg_match('/^[0-9A-Za-z\-\s]{3,20}$/', $profileForm['postal_code'])) {
                $profileErrors['postal_code'] = 'Please enter a valid postal code.';
            }
        }

        if ($csrfValid && empty($profileErrors)) {
            try {
                // Authority is strictly the authenticated session $customerId
                $updateProfileSql = "
                    UPDATE customers
                    SET first_name  = :first_name,
                        last_name   = :last_name,
                        phone       = :phone,
                        address     = :address,
                        city        = :city,
                        province    = :province,
                        postal_code = :postal_code
                    WHERE customer_id = :customer_id
                ";

                $stmt = $pdo->prepare($updateProfileSql);
                $stmt->execute([
                    'first_name'  => $profileForm['first_name'],
                    'last_name'   => $profileForm['last_name'],
                    'phone'       => $profileForm['phone'] !== '' ? $profileForm['phone'] : null,
                    'address'     => $profileForm['address'] !== '' ? $profileForm['address'] : null,
                    'city'        => $profileForm['city'] !== '' ? $profileForm['city'] : null,
                    'province'    => $profileForm['province'] !== '' ? $profileForm['province'] : null,
                    'postal_code' => $profileForm['postal_code'] !== '' ? $profileForm['postal_code'] : null,
                    'customer_id' => $customerId,
                ]);

                // Immediately update session customer name so header reflects changes
                $_SESSION['customer_name'] = trim($profileForm['first_name'] . ' ' . $profileForm['last_name']);

                set_flash_message('success', 'Your account and delivery details have been saved.');
                redirect($accountUrl);
            } catch (Throwable $exception) {
                error_log('Customer Profile Update Error: ' . $exception->getMessage());
                $profileErrors['general'] = 'We could not save your profile changes right now. Please try again shortly.';
            }
        }
    }

    // ------------------------------------------------------------------------
    // Action B: Change Password
    // ------------------------------------------------------------------------
    if ($action === 'change_password') {
        $rawCurrentPassword = isset($_POST['current_password']) && !is_array($_POST['current_password'])
            ? (string) $_POST['current_password']
            : '';
        $rawNewPassword = isset($_POST['new_password']) && !is_array($_POST['new_password'])
            ? (string) $_POST['new_password']
            : '';
        $rawConfirmPassword = isset($_POST['confirm_password']) && !is_array($_POST['confirm_password'])
            ? (string) $_POST['confirm_password']
            : '';

        if (!$csrfValid) {
            $passwordErrors['general'] = 'Invalid security token. Please try submitting the password form again.';
        }

        if ($rawCurrentPassword === '') {
            $passwordErrors['current_password'] = 'Current password is required.';
        }

        if ($rawNewPassword === '') {
            $passwordErrors['new_password'] = 'New password is required.';
        } elseif (mb_strlen($rawNewPassword) < 8) {
            $passwordErrors['new_password'] = 'New password must be at least 8 characters long.';
        } elseif (mb_strlen($rawNewPassword) > 255) {
            $passwordErrors['new_password'] = 'New password must not exceed 255 characters.';
        }

        if ($rawConfirmPassword === '') {
            $passwordErrors['confirm_password'] = 'Please confirm your new password.';
        } elseif ($rawNewPassword !== '' && $rawNewPassword !== $rawConfirmPassword) {
            $passwordErrors['confirm_password'] = 'New passwords do not match.';
        }

        if ($csrfValid && empty($passwordErrors)) {
            try {
                // Load password hash ONLY when verifying a password change request
                $pwRow = db_fetch_one(
                    $pdo,
                    'SELECT password FROM customers WHERE customer_id = :customer_id LIMIT 1',
                    ['customer_id' => $customerId]
                );

                if (
                    $pwRow === null
                    || !verify_password($rawCurrentPassword, (string) $pwRow['password'])
                ) {
                    $passwordErrors['current_password'] = 'Current password is incorrect.';
                } else {
                    $newPasswordHash = hash_password($rawNewPassword);

                    $updatePwStmt = $pdo->prepare(
                        'UPDATE customers SET password = :password WHERE customer_id = :customer_id'
                    );
                    $updatePwStmt->execute([
                        'password'    => $newPasswordHash,
                        'customer_id' => $customerId,
                    ]);

                    set_flash_message('success', 'Your password has been updated successfully.');
                    redirect($accountUrl);
                }
            } catch (Throwable $exception) {
                error_log('Customer Password Update Error: ' . $exception->getMessage());
                $passwordErrors['general'] = 'We could not update your password right now. Please try again shortly.';
            }
        }
    }
}

// ----------------------------------------------------------------------------
// 4. Load Recent Orders for the Authenticated Customer Only (Latest 5)
// ----------------------------------------------------------------------------
$recentOrders   = [];
$orderLoadError = null;

try {
    $ordersSql = "
        SELECT
            order_id,
            order_date,
            total,
            payment_method,
            payment_status,
            order_status
        FROM orders
        WHERE customer_id = :customer_id
        ORDER BY order_date DESC, order_id DESC
        LIMIT 5
    ";

    $recentOrders = db_fetch_all($pdo, $ordersSql, [
        'customer_id' => $customerId,
    ]);
} catch (Throwable $exception) {
    error_log('Customer Order History Load Error: ' . $exception->getMessage());
    $orderLoadError = 'We could not load your recent orders at this moment.';
}

$pageTitle = 'Your Account';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Account Page Hero -->
<section class="checkout-hero account-hero" aria-labelledby="account-heading">
    <div class="container">
        <div class="checkout-hero-inner">
            <div>
                <span class="eyebrow">Welcome back, <?php echo e($customer['first_name']); ?>.</span>
                <h1 id="account-heading" class="checkout-title">Your Account</h1>
                <p class="checkout-subtitle">
                    Manage your details, delivery information, and orders.
                </p>
            </div>
            <div class="account-hero-actions">
                <a href="<?php echo e($shopUrl); ?>" class="editorial-link">
                    Continue Shopping &rarr;
                </a>
                <a href="<?php echo e($logoutUrl); ?>" class="btn btn-outline account-logout-btn">
                    Log Out
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Main Account Content -->
<section class="section account-section" aria-label="Customer account management">
    <div class="container">
        <div class="account-layout">
            <!-- Left Column: Profile, Delivery Information & Password Change -->
            <div class="account-main-column">
                <!-- 1. Profile & Delivery Information Form -->
                <form action="<?php echo e($accountUrl); ?>" method="post" class="account-form-stack" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="action" value="update_profile">

                    <?php if (!empty($profileErrors)): ?>
                        <div class="cart-warnings auth-form-alert" role="alert">
                            <p class="cart-warnings-title">Please correct the highlighted profile fields:</p>
                            <?php if (!empty($profileErrors['general'])): ?>
                                <p><?php echo e($profileErrors['general']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Section Card -->
                    <div class="checkout-card account-card">
                        <h2 class="checkout-section-heading">01 / Profile Details</h2>

                        <div class="checkout-field-grid">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    maxlength="100"
                                    value="<?php echo e($profileForm['first_name']); ?>"
                                    required
                                    autocomplete="given-name"
                                    <?php echo isset($profileErrors['first_name']) ? 'aria-invalid="true" aria-describedby="error-first-name"' : ''; ?>
                                >
                                <?php if (isset($profileErrors['first_name'])): ?>
                                    <span id="error-first-name" class="form-error"><?php echo e($profileErrors['first_name']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    maxlength="100"
                                    value="<?php echo e($profileForm['last_name']); ?>"
                                    required
                                    autocomplete="family-name"
                                    <?php echo isset($profileErrors['last_name']) ? 'aria-invalid="true" aria-describedby="error-last-name"' : ''; ?>
                                >
                                <?php if (isset($profileErrors['last_name'])): ?>
                                    <span id="error-last-name" class="form-error"><?php echo e($profileErrors['last_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="checkout-field-grid">
                            <div class="form-group">
                                <span class="form-label">Account Email</span>
                                <div class="account-readonly-field" aria-label="Account email address (read-only)">
                                    <?php echo e($customer['email']); ?>
                                </div>
                                <span class="form-hint">Your registered account email cannot be changed here.</span>
                            </div>

                            <div class="form-group">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input
                                    type="tel"
                                    id="phone"
                                    name="phone"
                                    maxlength="30"
                                    placeholder="e.g. 082 123 4567"
                                    value="<?php echo e($profileForm['phone']); ?>"
                                    autocomplete="tel"
                                    <?php echo isset($profileErrors['phone']) ? 'aria-invalid="true" aria-describedby="error-phone"' : ''; ?>
                                >
                                <?php if (isset($profileErrors['phone'])): ?>
                                    <span id="error-phone" class="form-error"><?php echo e($profileErrors['phone']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="account-card-actions">
                            <button type="submit" class="btn btn-primary">
                                Save Details
                            </button>
                        </div>
                    </div>

                    <!-- Shipping / Delivery Information Card -->
                    <div class="checkout-card account-card">
                        <h2 class="checkout-section-heading">02 / Delivery Information</h2>
                        <p class="account-section-note">
                            Saved delivery details are used to pre-populate your shipping address during checkout.
                        </p>

                        <div class="form-group">
                            <label for="address" class="form-label">Street Address</label>
                            <input
                                type="text"
                                id="address"
                                name="address"
                                maxlength="255"
                                placeholder="Street number, street name, apartment or studio"
                                value="<?php echo e($profileForm['address']); ?>"
                                autocomplete="street-address"
                                <?php echo isset($profileErrors['address']) ? 'aria-invalid="true" aria-describedby="error-address"' : ''; ?>
                            >
                            <?php if (isset($profileErrors['address'])): ?>
                                <span id="error-address" class="form-error"><?php echo e($profileErrors['address']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="checkout-field-grid checkout-address-grid">
                            <div class="form-group">
                                <label for="city" class="form-label">City / Town</label>
                                <input
                                    type="text"
                                    id="city"
                                    name="city"
                                    maxlength="100"
                                    value="<?php echo e($profileForm['city']); ?>"
                                    autocomplete="address-level2"
                                    <?php echo isset($profileErrors['city']) ? 'aria-invalid="true" aria-describedby="error-city"' : ''; ?>
                                >
                                <?php if (isset($profileErrors['city'])): ?>
                                    <span id="error-city" class="form-error"><?php echo e($profileErrors['city']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="province" class="form-label">Province</label>
                                <select
                                    id="province"
                                    name="province"
                                    <?php echo isset($profileErrors['province']) ? 'aria-invalid="true" aria-describedby="error-province"' : ''; ?>
                                >
                                    <option value="">Select Province</option>
                                    <?php foreach ($allowedProvinces as $prov): ?>
                                        <option
                                            value="<?php echo e($prov); ?>"
                                            <?php echo $profileForm['province'] === $prov ? 'selected' : ''; ?>
                                        >
                                            <?php echo e($prov); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($profileErrors['province'])): ?>
                                    <span id="error-province" class="form-error"><?php echo e($profileErrors['province']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input
                                    type="text"
                                    id="postal_code"
                                    name="postal_code"
                                    maxlength="20"
                                    placeholder="e.g. 8001"
                                    value="<?php echo e($profileForm['postal_code']); ?>"
                                    autocomplete="postal-code"
                                    <?php echo isset($profileErrors['postal_code']) ? 'aria-invalid="true" aria-describedby="error-postal-code"' : ''; ?>
                                >
                                <?php if (isset($profileErrors['postal_code'])): ?>
                                    <span id="error-postal-code" class="form-error"><?php echo e($profileErrors['postal_code']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="account-card-actions">
                            <button type="submit" class="btn btn-primary">
                                Save Details
                            </button>
                        </div>
                    </div>
                </form>

                <!-- 2. Change Password Section -->
                <div class="checkout-card account-card">
                    <h2 class="checkout-section-heading">03 / Change Password</h2>

                    <?php if (!empty($passwordErrors)): ?>
                        <div class="cart-warnings auth-form-alert" role="alert">
                            <p class="cart-warnings-title">Unable to update password:</p>
                            <?php if (!empty($passwordErrors['general'])): ?>
                                <p><?php echo e($passwordErrors['general']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo e($accountUrl); ?>" method="post" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password" class="form-label">Current Password *</label>
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                maxlength="255"
                                required
                                autocomplete="current-password"
                                <?php echo isset($passwordErrors['current_password']) ? 'aria-invalid="true" aria-describedby="error-current-password"' : ''; ?>
                            >
                            <?php if (isset($passwordErrors['current_password'])): ?>
                                <span id="error-current-password" class="form-error"><?php echo e($passwordErrors['current_password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="checkout-field-grid">
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    minlength="8"
                                    maxlength="255"
                                    required
                                    autocomplete="new-password"
                                    aria-describedby="new-password-hint<?php echo isset($passwordErrors['new_password']) ? ' error-new-password' : ''; ?>"
                                    <?php echo isset($passwordErrors['new_password']) ? 'aria-invalid="true"' : ''; ?>
                                >
                                <span id="new-password-hint" class="form-hint">Minimum 8 characters.</span>
                                <?php if (isset($passwordErrors['new_password'])): ?>
                                    <span id="error-new-password" class="form-error"><?php echo e($passwordErrors['new_password']); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    minlength="8"
                                    maxlength="255"
                                    required
                                    autocomplete="new-password"
                                    <?php echo isset($passwordErrors['confirm_password']) ? 'aria-invalid="true" aria-describedby="error-confirm-password"' : ''; ?>
                                >
                                <?php if (isset($passwordErrors['confirm_password'])): ?>
                                    <span id="error-confirm-password" class="form-error"><?php echo e($passwordErrors['confirm_password']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="account-card-actions">
                            <button type="submit" class="btn btn-outline">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Recent Orders History -->
            <aside class="account-orders-column" aria-labelledby="account-orders-heading">
                <div class="checkout-card account-orders-card">
                    <div class="confirmation-card-header">
                        <h2 id="account-orders-heading" class="checkout-section-heading">Recent Orders</h2>
                        <span class="confirmation-item-count">Latest 5</span>
                    </div>

                    <?php if ($orderLoadError !== null): ?>
                        <div class="cart-warnings" role="alert">
                            <p><?php echo e($orderLoadError); ?></p>
                        </div>
                    <?php elseif (empty($recentOrders)): ?>
                        <div class="account-empty-orders">
                            <p class="account-empty-orders-title">No orders yet.</p>
                            <p class="account-empty-orders-text">
                                When you place an order with Zazen Matcha, your order references and statuses will appear here.
                            </p>
                            <a href="<?php echo e($shopUrl); ?>" class="editorial-link">
                                Explore the collection &rarr;
                            </a>
                        </div>
                    <?php else: ?>
                        <ul class="account-order-list" aria-label="Your recent orders">
                            <?php foreach ($recentOrders as $ord): ?>
                                <?php
                                $ordId       = (int) $ord['order_id'];
                                $rawOrdDate  = (string) ($ord['order_date'] ?? '');
                                $ordTime     = $rawOrdDate !== '' ? strtotime($rawOrdDate) : false;
                                $ordDateText = $ordTime !== false ? date('d M Y', $ordTime) : $rawOrdDate;

                                $payKey   = strtolower(trim((string) ($ord['payment_status'] ?? 'pending')));
                                $payLabel = $paymentStatusLabels[$payKey] ?? ucfirst($payKey);

                                $statusKey   = strtolower(trim((string) ($ord['order_status'] ?? 'pending')));
                                $statusLabel = $orderStatusLabels[$statusKey] ?? ucfirst($statusKey);

                                $confirmUrl = site_url('customer/order-confirmation.php?id=' . $ordId);
                                ?>
                                <li class="account-order-item">
                                    <div class="account-order-top">
                                        <div>
                                            <a href="<?php echo e($confirmUrl); ?>" class="account-order-number">
                                                Order #<?php echo $ordId; ?>
                                            </a>
                                            <span class="account-order-date"><?php echo e($ordDateText); ?></span>
                                        </div>
                                        <strong class="account-order-total">
                                            <?php echo e(format_price($ord['total'])); ?>
                                        </strong>
                                    </div>

                                    <div class="account-order-bottom">
                                        <div class="account-order-badges">
                                            <span class="status-pill status-pill-<?php echo e($payKey); ?>" title="Payment Status">
                                                Payment: <?php echo e($payLabel); ?>
                                            </span>
                                            <span class="status-pill status-pill-<?php echo e($statusKey); ?>" title="Order Status">
                                                Order: <?php echo e($statusLabel); ?>
                                            </span>
                                        </div>

                                        <a href="<?php echo e($confirmUrl); ?>" class="editorial-link account-order-link">
                                            View Order &rarr;
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
