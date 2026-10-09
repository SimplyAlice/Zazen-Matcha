<?php
/**
 * Administrator Category Management Listing Page
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads all product categories from the
 * `categories` table along with their associated product counts via a
 * prepared PDO query (`LEFT JOIN products` + `COUNT(p.product_id)`),
 * ordered consistently by `category_id ASC`.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$addCategoryUrl = site_url('admin/category-add.php');
$dashboardUrl   = site_url('admin/dashboard.php');

$categories      = [];
$categoriesError = null;

// ----------------------------------------------------------------------------
// 1. Load All Categories with Product Counts (Ordered by category_id ASC)
// ----------------------------------------------------------------------------
try {
    $categoriesSql = "
        SELECT
            c.category_id,
            c.name,
            c.description,
            COUNT(p.product_id) AS product_count
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.category_id
        GROUP BY c.category_id, c.name, c.description
        ORDER BY c.category_id ASC
    ";

    $categories = db_fetch_all($pdo, $categoriesSql);
} catch (Throwable $exception) {
    error_log('Admin Categories Listing Error: ' . $exception->getMessage());
    $categoriesError = 'Unable to load product categories right now. Please refresh the page or check the database connection.';
}

// Compute summary totals for the panel header
$totalCategoriesCount = count($categories);
$totalProductsLinked  = 0;

foreach ($categories as $categoryItem) {
    $totalProductsLinked += (int) ($categoryItem['product_count'] ?? 0);
}

$pageTitle = 'Category Management';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Hero / Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-categories-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Catalogue Administration</span>
                <h1 id="admin-categories-heading" class="checkout-title">Category Management</h1>
                <p class="checkout-subtitle">
                    Review product categories and catalogue groupings across ceremonial matcha, preparation vessels, and curated gift sets.
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e($addCategoryUrl); ?>" class="btn btn-primary">
                    + Add Category
                </a>
                <a href="<?php echo e($dashboardUrl); ?>" class="btn btn-outline">
                    &larr; Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Categories Listing Section -->
<section class="section admin-dashboard-section admin-categories-section" aria-label="Product categories listing">
    <div class="container">
        <?php if ($categoriesError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Categories Notice</p>
                <p><?php echo e($categoriesError); ?></p>
            </div>
        <?php endif; ?>

        <div class="checkout-card admin-panel-card admin-categories-card">
            <!-- Panel Header & Summary Bar -->
            <div class="admin-panel-header admin-products-header">
                <div>
                    <span class="eyebrow">01 / Catalogue Taxonomy</span>
                    <h2 class="checkout-section-heading">
                        All Categories (<?php echo (int) $totalCategoriesCount; ?>)
                    </h2>
                    <p class="admin-products-summary">
                        <span><strong><?php echo (int) $totalCategoriesCount; ?></strong> Total Categories</span>
                        <span aria-hidden="true">&middot;</span>
                        <span><strong><?php echo (int) $totalProductsLinked; ?></strong> Assigned Products</span>
                    </p>
                </div>

                <a href="<?php echo e($addCategoryUrl); ?>" class="btn btn-primary admin-add-product-btn">
                    Add Category
                </a>
            </div>

            <?php if (empty($categories)): ?>
                <!-- Empty Categories State -->
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">No categories found.</p>
                    <p class="admin-empty-text">
                        There are currently no product categories stored in the database. Use the Add Category button to create your first category.
                    </p>
                    <div class="admin-empty-actions">
                        <a href="<?php echo e($addCategoryUrl); ?>" class="btn btn-primary">
                            Add Category
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Categories Table -->
                <div class="admin-table-wrapper">
                    <table class="admin-table admin-categories-table">
                        <thead>
                            <tr>
                                <th scope="col">Category ID</th>
                                <th scope="col">Category Name</th>
                                <th scope="col">Number of Products</th>
                                <th scope="col" class="admin-col-actions">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <?php
                                $categoryId          = (int) ($category['category_id'] ?? 0);
                                $categoryName        = trim((string) ($category['name'] ?? 'Untitled Category'));
                                $categoryDescription = trim((string) ($category['description'] ?? ''));
                                $productCount        = (int) ($category['product_count'] ?? 0);

                                $editUrl = site_url('admin/category-edit.php?id=' . rawurlencode((string) $categoryId));
                                ?>
                                <tr data-category-id="<?php echo $categoryId; ?>">
                                    <td class="admin-cell-id" data-label="Category ID">
                                        <strong>#<?php echo $categoryId; ?></strong>
                                    </td>

                                    <td data-label="Category Name">
                                        <div class="admin-product-name-cell">
                                            <span class="admin-product-name"><?php echo e($categoryName); ?></span>
                                            <?php if ($categoryDescription !== ''): ?>
                                                <span class="admin-customer-email"><?php echo e($categoryDescription); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td data-label="Number of Products">
                                        <span class="admin-stock-qty"><?php echo $productCount; ?></span>
                                        <span class="admin-customer-email">
                                            <?php echo $productCount === 1 ? 'product' : 'products'; ?>
                                        </span>
                                    </td>

                                    <td class="admin-cell-actions" data-label="Action">
                                        <a
                                            href="<?php echo e($editUrl); ?>"
                                            class="btn btn-outline admin-edit-btn"
                                            aria-label="Edit <?php echo e($categoryName); ?>"
                                        >
                                            Edit
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
