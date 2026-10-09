<?php
/**
 * Administrator Product Management Listing Page (Phase 1: Read-Only Listing)
 * Zazen Matcha & Tea Co.
 *
 * Protected administration page that loads all catalogue products (active and
 * inactive) joined with their category names via PDO prepared statements.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/admin-auth.php';

// Enforce administrator authentication immediately
require_admin_login();

$addProductUrl = site_url('admin/product-add.php');
$dashboardUrl  = site_url('admin/dashboard.php');

$products      = [];
$productsError = null;

// Low-stock threshold (units <= 10 are flagged as Low stock; <= 0 as Out of stock)
$lowStockThreshold = 10;

// ----------------------------------------------------------------------------
// 1. Load All Products Joined with Category Names (Active & Inactive)
// ----------------------------------------------------------------------------
try {
    $productsSql = "
        SELECT
            p.product_id,
            p.category_id,
            p.name,
            p.description,
            p.price,
            p.stock_quantity,
            p.image,
            p.status,
            c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.category_id = p.category_id
        ORDER BY p.product_id ASC
    ";

    $products = db_fetch_all($pdo, $productsSql);
} catch (Throwable $exception) {
    error_log('Admin Products Listing Error: ' . $exception->getMessage());
    $productsError = 'Unable to load the product catalogue at this moment. Please refresh the page or check the database connection.';
}

// Compute quick summary counts from the fetched dataset for the toolbar header
$totalProductCount    = count($products);
$activeProductCount   = 0;
$inactiveProductCount = 0;
$lowOrOutStockCount   = 0;

foreach ($products as $prodItem) {
    $itemStatus = strtolower(trim((string) ($prodItem['status'] ?? 'inactive')));
    if ($itemStatus === 'active') {
        $activeProductCount++;
    } else {
        $inactiveProductCount++;
    }

    if ((int) ($prodItem['stock_quantity'] ?? 0) <= $lowStockThreshold) {
        $lowOrOutStockCount++;
    }
}

$pageTitle = 'Products';

require_once dirname(__DIR__) . '/includes/admin-header.php';
?>

<!-- 1. Page Hero / Header Banner -->
<section class="checkout-hero admin-page-hero" aria-labelledby="admin-products-heading">
    <div class="container">
        <div class="checkout-hero-inner admin-page-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Catalogue Administration</span>
                <h1 id="admin-products-heading" class="checkout-title">Product Management</h1>
                <p class="checkout-subtitle">
                    Review all ceremonial matcha powders, preparation vessels, and gift sets across active and inactive listings.
                </p>
            </div>
            <div class="admin-hero-actions">
                <a href="<?php echo e($addProductUrl); ?>" class="btn btn-primary">
                    + Add Product
                </a>
                <a href="<?php echo e($dashboardUrl); ?>" class="btn btn-outline">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. Main Product Catalogue Table Section -->
<section class="section admin-dashboard-section admin-products-section" aria-label="Product catalogue listing">
    <div class="container">
        <?php if ($productsError !== null): ?>
            <div class="cart-warnings admin-dashboard-alert" role="alert">
                <p class="cart-warnings-title">Catalogue Notice</p>
                <p><?php echo e($productsError); ?></p>
            </div>
        <?php endif; ?>

        <div class="checkout-card admin-panel-card admin-products-card">
            <!-- Panel Header & Summary Bar -->
            <div class="admin-panel-header admin-products-header">
                <div>
                    <span class="eyebrow">01 / Catalogue Inventory</span>
                    <h2 class="checkout-section-heading">
                        All Products (<?php echo (int) $totalProductCount; ?>)
                    </h2>
                    <p class="admin-products-summary">
                        <span><strong><?php echo (int) $activeProductCount; ?></strong> Active</span>
                        <span aria-hidden="true">&middot;</span>
                        <span><strong><?php echo (int) $inactiveProductCount; ?></strong> Inactive</span>
                        <span aria-hidden="true">&middot;</span>
                        <span><strong><?php echo (int) $lowOrOutStockCount; ?></strong> Low / Out of Stock</span>
                    </p>
                </div>

                <a href="<?php echo e($addProductUrl); ?>" class="btn btn-primary admin-add-product-btn">
                    Add Product
                </a>
            </div>

            <?php if (empty($products)): ?>
                <!-- Empty Catalogue State -->
                <div class="admin-empty-state" role="status">
                    <p class="admin-empty-title">No products are currently in the catalogue.</p>
                    <p class="admin-empty-text">
                        There are zero products stored in the database. Use the Add Product button to create your first catalogue listing.
                    </p>
                    <div class="admin-empty-actions">
                        <a href="<?php echo e($addProductUrl); ?>" class="btn btn-primary">
                            Add Product
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Products Table -->
                <div class="admin-table-wrapper">
                    <table class="admin-table admin-products-table">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Image</th>
                                <th scope="col">Product Name</th>
                                <th scope="col">Category</th>
                                <th scope="col">Price</th>
                                <th scope="col">Stock</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="admin-col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <?php
                                $productId    = (int) ($product['product_id'] ?? 0);
                                $productName  = trim((string) ($product['name'] ?? 'Untitled Product'));
                                $categoryName = trim((string) ($product['category_name'] ?? ''));
                                if ($categoryName === '') {
                                    $categoryName = 'Uncategorised';
                                }

                                $priceValue   = (float) ($product['price'] ?? 0.00);
                                $stockQty     = (int) ($product['stock_quantity'] ?? 0);

                                // Stock state classification
                                if ($stockQty <= 0) {
                                    $stockLabel = 'Out of stock';
                                    $stockClass = 'admin-stock-out';
                                } elseif ($stockQty <= $lowStockThreshold) {
                                    $stockLabel = 'Low stock';
                                    $stockClass = 'admin-stock-low';
                                } else {
                                    $stockLabel = 'In stock';
                                    $stockClass = 'admin-stock-in';
                                }

                                // Status normalisation ('active' vs 'inactive')
                                $rawStatus   = strtolower(trim((string) ($product['status'] ?? 'inactive')));
                                $isActive    = ($rawStatus === 'active');
                                $statusLabel = $isActive ? 'Active' : 'Inactive';
                                $statusClass = $isActive ? 'status-product-active' : 'status-product-inactive';

                                // Product image check
                                $rawImageFilename = basename(trim((string) ($product['image'] ?? '')));
                                $hasImageFile     = (
                                    $rawImageFilename !== ''
                                    && is_file(dirname(__DIR__) . '/images/products/' . $rawImageFilename)
                                );
                                $imageUrl = $hasImageFile ? product_image_url($rawImageFilename) : null;

                                $editUrl = site_url('admin/product-edit.php?id=' . $productId);
                                ?>
                                <tr data-product-id="<?php echo $productId; ?>">
                                    <td class="admin-cell-id" data-label="ID">
                                        <strong>#<?php echo $productId; ?></strong>
                                    </td>

                                    <td class="admin-cell-thumb" data-label="Image">
                                        <?php if ($imageUrl !== null): ?>
                                            <img
                                                src="<?php echo e($imageUrl); ?>"
                                                alt="<?php echo e($productName); ?>"
                                                class="admin-product-thumb"
                                                width="52"
                                                height="52"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <span class="admin-product-no-image" aria-label="No image available">
                                                No image
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td data-label="Product Name">
                                        <div class="admin-product-name-cell">
                                            <span class="admin-product-name"><?php echo e($productName); ?></span>
                                            <?php if ($rawImageFilename !== ''): ?>
                                                <span class="admin-product-filename"><?php echo e($rawImageFilename); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td data-label="Category">
                                        <span class="admin-category-tag"><?php echo e($categoryName); ?></span>
                                    </td>

                                    <td class="admin-cell-total" data-label="Price">
                                        <?php echo e(format_price($priceValue)); ?>
                                    </td>

                                    <td data-label="Stock">
                                        <div class="admin-stock-cell">
                                            <span class="admin-stock-qty"><?php echo $stockQty; ?></span>
                                            <span class="admin-stock-badge <?php echo e($stockClass); ?>">
                                                <?php echo e($stockLabel); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <td data-label="Status">
                                        <span class="status-badge <?php echo e($statusClass); ?>">
                                            <?php echo e($statusLabel); ?>
                                        </span>
                                    </td>

                                    <td class="admin-cell-actions" data-label="Actions">
                                        <a
                                            href="<?php echo e($editUrl); ?>"
                                            class="btn btn-outline admin-edit-btn"
                                            aria-label="Edit <?php echo e($productName); ?>"
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
