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

                <div class="header-actions" aria-label="Search, customer account, and shopping cart">
                    <a href="<?php echo e(site_url('customer/products.php#shop-search-input')); ?>"
                       class="header-icon-link"
                       aria-label="Search products"
                       title="Search products">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="20" y1="20" x2="16.35" y2="16.35"></line>
                        </svg>
                    </a>

                    <?php if ($isCustomerLoggedIn): ?>
                        <a href="<?php echo e(site_url('customer/account.php')); ?>"
                           class="header-icon-link <?php echo in_array($currentPage, ['account.php', 'orders.php'], true) ? 'is-active' : ''; ?>"
                           aria-label="My Account"
                           title="My Account">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="header-action-label">Account</span>
                        </a>
                        <a href="<?php echo e(site_url('customer/logout.php')); ?>" class="action-link action-link-muted">
                            Log Out
                        </a>
                    <?php else: ?>
                        <a href="<?php echo e(site_url('customer/login.php')); ?>"
                           class="header-icon-link <?php echo in_array($currentPage, ['login.php', 'register.php'], true) ? 'is-active' : ''; ?>"
                           aria-label="Account / Sign In"
                           title="Account / Sign In">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span class="visually-hidden">Account / Sign In</span>
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo e(site_url('customer/cart.php')); ?>"
                       class="header-icon-link cart-link <?php echo $currentPage === 'cart.php' ? 'is-active' : ''; ?>"
                       aria-label="Shopping cart (<?php echo (int) $cartItemCount; ?> items)"
                       title="Shopping Cart">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="9" cy="21" r="1.5"></circle>
                            <circle cx="19" cy="21" r="1.5"></circle>
                            <path d="M2.5 3h2.8l2.4 11.5a2 2 0 0 0 2 1.5h9.8a2 2 0 0 0 1.9-1.4L23 6H6.2"></path>
                        </svg>
                        <span class="visually-hidden">Cart</span>
                        <span class="cart-badge" id="header-cart-count"><?php echo (int) $cartItemCount; ?></span>
                    </a>
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
