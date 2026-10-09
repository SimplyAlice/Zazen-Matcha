<?php
/**
 * Reusable Customer Header & Navigation
 * Zazen Matcha & Tea Co.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

ensure_session_started();

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$isCustomerLoggedIn = is_customer_logged_in();

// Calculate cart item count from session if present (defaults to 0)
$cartItemCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartItemCount += is_array($item) ? (int) ($item['quantity'] ?? 0) : (int) $item;
    }
}

$documentTitle = !empty($pageTitle)
    ? e($pageTitle) . ' — Zazen Matcha & Tea Co.'
    : 'Zazen Matcha & Tea Co. — Quiet Ritual, Modern Craft';

$flashMessage = get_flash_message();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $documentTitle; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('css/style.css')); ?>">
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="site-header">
        <div class="container header-inner">
            <!-- Brand / Logo -->
            <a href="<?php echo e(site_url('index.php')); ?>" class="brand-logo" aria-label="Zazen Matcha & Tea Co. Home">
                <span class="brand-name">Zazen Matcha</span>
                <span class="brand-subtitle">Tea Co.</span>
            </a>

            <!-- Mobile Menu Toggle -->
            <button
                type="button"
                class="nav-toggle"
                id="nav-toggle"
                aria-expanded="false"
                aria-controls="primary-navigation"
                aria-label="Toggle navigation menu"
            >
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>

            <!-- Primary & Utility Navigation -->
            <div class="nav-wrapper" id="primary-navigation">
                <nav class="primary-nav" aria-label="Main navigation">
                    <ul class="nav-list">
                        <li>
                            <a href="<?php echo e(site_url('index.php')); ?>"
                               class="nav-link <?php echo $currentPage === 'index.php' ? 'is-active' : ''; ?>"
                               <?php echo $currentPage === 'index.php' ? 'aria-current="page"' : ''; ?>>
                                Home
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('customer/products.php')); ?>"
                               class="nav-link <?php echo in_array($currentPage, ['products.php', 'product.php'], true) ? 'is-active' : ''; ?>"
                               <?php echo in_array($currentPage, ['products.php', 'product.php'], true) ? 'aria-current="page"' : ''; ?>>
                                Shop
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('customer/about.php')); ?>"
                               class="nav-link <?php echo $currentPage === 'about.php' ? 'is-active' : ''; ?>"
                               <?php echo $currentPage === 'about.php' ? 'aria-current="page"' : ''; ?>>
                                About
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('customer/contact.php')); ?>"
                               class="nav-link <?php echo $currentPage === 'contact.php' ? 'is-active' : ''; ?>"
                               <?php echo $currentPage === 'contact.php' ? 'aria-current="page"' : ''; ?>>
                                Contact
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="header-actions" aria-label="Customer account and cart">
                    <a href="<?php echo e(site_url('customer/cart.php')); ?>"
                       class="action-link cart-link <?php echo $currentPage === 'cart.php' ? 'is-active' : ''; ?>"
                       aria-label="Shopping cart (<?php echo (int) $cartItemCount; ?> items)">
                        <span>Cart</span>
                        <span class="cart-badge" id="header-cart-count"><?php echo (int) $cartItemCount; ?></span>
                    </a>

                    <?php if ($isCustomerLoggedIn): ?>
                        <a href="<?php echo e(site_url('customer/account.php')); ?>"
                           class="action-link <?php echo in_array($currentPage, ['account.php', 'orders.php'], true) ? 'is-active' : ''; ?>">
                            Account
                        </a>
                        <a href="<?php echo e(site_url('customer/logout.php')); ?>" class="action-link action-link-muted">
                            Log Out
                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(site_url('customer/login.php')); ?>"
                           class="action-link <?php echo in_array($currentPage, ['login.php', 'register.php'], true) ? 'is-active' : ''; ?>">
                            Account / Sign In
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main id="main-content" class="site-main">
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
