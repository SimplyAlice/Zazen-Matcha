<?php
/**
 * Reusable Administrator Header & Navigation
 * Zazen Matcha & Tea Co.
 *
 * Enforces administrator authentication via require_admin_login() and renders
 * the shared HTML5 head, administration header, responsive navigation bar,
 * logged-in admin identity, logout link, and session flash notifications.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin-auth.php';

require_admin_login();

$currentAdminPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$adminDisplayName = get_logged_in_admin_name() ?? 'Administrator';

$documentTitle = !empty($pageTitle)
    ? e($pageTitle) . ' — Zazen Matcha Administration'
    : 'Administration — Zazen Matcha & Tea Co.';

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
<body class="admin-body">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <header class="site-header admin-header">
        <div class="container header-inner admin-header-inner">
            <!-- Brand & Administration Identifier -->
            <div class="admin-brand-group">
                <a href="<?php echo e(site_url('admin/dashboard.php')); ?>" class="brand-logo" aria-label="Zazen Matcha Administration Dashboard">
                    <span class="brand-name">Zazen Matcha</span>
                    <span class="brand-subtitle">Tea Co.</span>
                </a>
                <span class="admin-portal-badge">Administration</span>
            </div>

            <!-- Mobile Menu Toggle -->
            <button
                type="button"
                class="nav-toggle"
                id="nav-toggle"
                aria-expanded="false"
                aria-controls="primary-navigation"
                aria-label="Toggle administration navigation menu"
            >
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
                <span class="nav-toggle-bar"></span>
            </button>

            <!-- Primary Admin Navigation & Session Controls -->
            <div class="nav-wrapper admin-nav-wrapper" id="primary-navigation">
                <nav class="primary-nav admin-nav" aria-label="Administration navigation">
                    <ul class="nav-list admin-nav-list">
                        <li>
                            <a href="<?php echo e(site_url('admin/dashboard.php')); ?>"
                               class="nav-link <?php echo $currentAdminPage === 'dashboard.php' ? 'is-active' : ''; ?>"
                               <?php echo $currentAdminPage === 'dashboard.php' ? 'aria-current="page"' : ''; ?>>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/products.php')); ?>"
                               class="nav-link <?php echo in_array($currentAdminPage, ['products.php', 'product-add.php', 'product-edit.php'], true) ? 'is-active' : ''; ?>"
                               <?php echo in_array($currentAdminPage, ['products.php', 'product-add.php', 'product-edit.php'], true) ? 'aria-current="page"' : ''; ?>>
                                Products
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/orders.php')); ?>"
                               class="nav-link <?php echo in_array($currentAdminPage, ['orders.php', 'order-details.php'], true) ? 'is-active' : ''; ?>"
                               <?php echo in_array($currentAdminPage, ['orders.php', 'order-details.php'], true) ? 'aria-current="page"' : ''; ?>>
                                Orders
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/customers.php')); ?>"
                               class="nav-link <?php echo in_array($currentAdminPage, ['customers.php', 'customer-details.php'], true) ? 'is-active' : ''; ?>"
                               <?php echo in_array($currentAdminPage, ['customers.php', 'customer-details.php'], true) ? 'aria-current="page"' : ''; ?>>
                                Customers
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/categories.php')); ?>"
                               class="nav-link <?php echo in_array($currentAdminPage, ['categories.php', 'category-add.php', 'category-edit.php'], true) ? 'is-active' : ''; ?>"
                               <?php echo in_array($currentAdminPage, ['categories.php', 'category-add.php', 'category-edit.php'], true) ? 'aria-current="page"' : ''; ?>>
                                Categories
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo e(site_url('admin/settings.php')); ?>"
                               class="nav-link <?php echo $currentAdminPage === 'settings.php' ? 'is-active' : ''; ?>"
                               <?php echo $currentAdminPage === 'settings.php' ? 'aria-current="page"' : ''; ?>>
                                Settings
                            </a>
                        </li>
                    </ul>
                </nav>

                <div class="header-actions admin-header-user" aria-label="Administrator session controls">
                    <span class="admin-user-pill" title="Signed-in administrator">
                        <span class="admin-user-dot" aria-hidden="true"></span>
                        <span class="admin-user-name"><?php echo e($adminDisplayName); ?></span>
                    </span>
                    <a href="<?php echo e(site_url('admin/logout.php')); ?>" class="action-link action-link-muted admin-logout-link">
                        Log Out
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main id="main-content" class="site-main admin-main">
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
