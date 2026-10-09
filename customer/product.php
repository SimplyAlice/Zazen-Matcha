<?php
/**
 * Customer Product Details Page
 * Zazen Matcha & Tea Co.
 *
 * Loads a single active product by GET id using PDO prepared statements
 * and handles CSRF-protected POST requests to add items to $_SESSION['cart'].
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

ensure_session_started();

// Reusable prepared query to fetch a single active product with its category name
$productQuerySql = "
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
    INNER JOIN categories c ON p.category_id = c.category_id
    WHERE p.product_id = :product_id
      AND p.status = :status
    LIMIT 1
";

// ----------------------------------------------------------------------------
// 1. Handle POST Add-to-Cart Submission
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawPostId = $_POST['product_id'] ?? '';
    $postProductId = (!is_array($rawPostId) && ctype_digit((string) $rawPostId))
        ? (int) $rawPostId
        : 0;

    $returnUrl = $postProductId > 0
        ? site_url('customer/product.php?id=' . $postProductId)
        : site_url('customer/products.php');

    // Verify CSRF token
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    if (!verify_csrf_token($submittedToken)) {
        set_flash_message('error', 'Invalid security token. Please try submitting the form again.');
        redirect($returnUrl);
    }

    // Validate product ID
    if ($postProductId <= 0) {
        set_flash_message('error', 'Invalid product selected.');
        redirect(site_url('customer/products.php'));
    }

    // Validate requested quantity (must be a positive integer >= 1)
    $rawQuantity = $_POST['quantity'] ?? '';
    $requestedQuantity = (!is_array($rawQuantity) && filter_var($rawQuantity, FILTER_VALIDATE_INT) !== false)
        ? (int) $rawQuantity
        : 0;

    if ($requestedQuantity < 1) {
        set_flash_message('error', 'Please select a valid quantity of at least 1.');
        redirect($returnUrl);
    }

    // Re-query the product from MySQL (never trust client-side price, name, or stock)
    $productToCart = db_fetch_one($pdo, $productQuerySql, [
        'product_id' => $postProductId,
        'status'     => 'active',
    ]);

    if ($productToCart === null) {
        set_flash_message('error', 'This product is no longer available.');
        redirect(site_url('customer/products.php'));
    }

    $availableStock = (int) $productToCart['stock_quantity'];

    if ($availableStock <= 0) {
        set_flash_message('error', 'Sorry, "' . $productToCart['name'] . '" is currently out of stock.');
        redirect($returnUrl);
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $existingCartQty = isset($_SESSION['cart'][$postProductId])
        ? (int) $_SESSION['cart'][$postProductId]
        : 0;

    $newCartQty = $existingCartQty + $requestedQuantity;

    if ($requestedQuantity > $availableStock || $newCartQty > $availableStock) {
        if ($existingCartQty > 0) {
            set_flash_message(
                'error',
                sprintf(
                    'Cannot add %d more of "%s". Only %d available in stock and you already have %d in your cart.',
                    $requestedQuantity,
                    $productToCart['name'],
                    $availableStock,
                    $existingCartQty
                )
            );
        } else {
            set_flash_message(
                'error',
                sprintf(
                    'Cannot add %d of "%s". Only %d available in stock.',
                    $requestedQuantity,
                    $productToCart['name'],
                    $availableStock
                )
            );
        }
        redirect($returnUrl);
    }

    // Store product_id => quantity in the session cart
    $_SESSION['cart'][$postProductId] = $newCartQty;

    set_flash_message(
        'success',
        sprintf('Added %d × %s to your cart.', $requestedQuantity, $productToCart['name'])
    );
    redirect($returnUrl);
}

// ----------------------------------------------------------------------------
// 2. Load Product From GET ID
// ----------------------------------------------------------------------------
$rawGetId = $_GET['id'] ?? '';
$productId = (!is_array($rawGetId) && ctype_digit((string) $rawGetId) && (int) $rawGetId > 0)
    ? (int) $rawGetId
    : 0;

$product = null;
if ($productId > 0) {
    $product = db_fetch_one($pdo, $productQuerySql, [
        'product_id' => $productId,
        'status'     => 'active',
    ]);
}

if ($product !== null) {
    $pageTitle = $product['name'];
} else {
    $pageTitle = 'Product Not Found';
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<?php if ($product === null): ?>
    <!-- Friendly Product Not Found / Unavailable State -->
    <section class="section product-not-found-section" aria-labelledby="not-found-heading">
        <div class="container">
            <div class="empty-state product-not-found-box">
                <span class="eyebrow">Catalogue Notice</span>
                <h1 id="not-found-heading" class="empty-state-title">Product Not Found</h1>
                <p class="empty-state-text">
                    The item you are looking for could not be found or may no longer be available in our active collection.
                </p>
                <div class="empty-state-actions">
                    <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-primary">
                        &larr; Back to Shop
                    </a>
                </div>
            </div>
        </div>
    </section>
<?php else: ?>
    <?php
    $productId       = (int) $product['product_id'];
    $categoryId      = (int) $product['category_id'];
    $stockQty        = (int) $product['stock_quantity'];
    $isInStock       = ($stockQty > 0);
    $isLowStock      = ($stockQty > 0 && $stockQty <= 10);
    $imageUrl        = product_image_url($product['image']);
    $categoryShopUrl = site_url('customer/products.php?category=' . $categoryId);
    $inCartQty       = isset($_SESSION['cart'][$productId]) ? (int) $_SESSION['cart'][$productId] : 0;
    ?>

    <!-- Breadcrumb & Back Link -->
    <div class="product-breadcrumb-bar">
        <div class="container product-breadcrumb-inner">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li>
                        <a href="<?php echo e(site_url('customer/products.php')); ?>">Shop</a>
                    </li>
                    <li aria-hidden="true" class="breadcrumb-sep">/</li>
                    <li>
                        <a href="<?php echo e($categoryShopUrl); ?>"><?php echo e($product['category_name']); ?></a>
                    </li>
                    <li aria-hidden="true" class="breadcrumb-sep">/</li>
                    <li aria-current="page" class="breadcrumb-current">
                        <?php echo e($product['name']); ?>
                    </li>
                </ol>
            </nav>

            <a href="<?php echo e(site_url('customer/products.php')); ?>" class="breadcrumb-back-link">
                &larr; Back to Shop
            </a>
        </div>
    </div>

    <!-- Main Product Detail Area -->
    <section class="section product-detail-section" aria-labelledby="product-detail-heading">
        <div class="container">
            <div class="product-detail-grid">
                <!-- Left Column: Product Imagery -->
                <div class="product-detail-media">
                    <div class="product-detail-image-frame">
                        <img
                            src="<?php echo e($imageUrl); ?>"
                            alt="<?php echo e($product['name']); ?>"
                            width="900"
                            height="1100"
                        >
                    </div>
                </div>

                <!-- Right Column: Product Information & Add to Cart -->
                <div class="product-detail-info">
                    <a href="<?php echo e($categoryShopUrl); ?>" class="eyebrow product-detail-category">
                        <?php echo e($product['category_name']); ?>
                    </a>

                    <h1 id="product-detail-heading" class="product-detail-title">
                        <?php echo e($product['name']); ?>
                    </h1>

                    <div class="product-detail-price-row">
                        <span class="product-detail-price">
                            <?php echo e(format_price($product['price'])); ?>
                        </span>

                        <?php if ($isLowStock): ?>
                            <span class="stock-badge stock-low">
                                Low Stock (<?php echo $stockQty; ?> left)
                            </span>
                        <?php elseif ($isInStock): ?>
                            <span class="stock-badge stock-in">
                                In Stock
                            </span>
                        <?php else: ?>
                            <span class="stock-badge stock-out">
                                Out of Stock
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="product-detail-description">
                        <p><?php echo e($product['description']); ?></p>
                    </div>

                    <!-- Add to Cart POST Form -->
                    <form
                        action="<?php echo e(site_url('customer/product.php?id=' . $productId)); ?>"
                        method="post"
                        class="product-cart-form"
                    >
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">

                        <div class="product-purchase-controls">
                            <div class="quantity-field">
                                <label for="quantity-input" class="form-label">Quantity</label>
                                <div class="quantity-stepper">
                                    <button
                                        type="button"
                                        class="qty-step-btn"
                                        data-qty-step="-1"
                                        aria-label="Decrease quantity"
                                        <?php echo !$isInStock ? 'disabled' : ''; ?>
                                    >&minus;</button>
                                    <input
                                        type="number"
                                        id="quantity-input"
                                        name="quantity"
                                        class="quantity-input"
                                        value="1"
                                        min="1"
                                        max="<?php echo $stockQty > 0 ? $stockQty : 1; ?>"
                                        <?php echo !$isInStock ? 'disabled' : 'required'; ?>
                                    >
                                    <button
                                        type="button"
                                        class="qty-step-btn"
                                        data-qty-step="1"
                                        aria-label="Increase quantity"
                                        <?php echo !$isInStock ? 'disabled' : ''; ?>
                                    >&#43;</button>
                                </div>
                            </div>

                            <div class="add-to-cart-action">
                                <button
                                    type="submit"
                                    class="btn btn-primary add-to-cart-btn"
                                    <?php echo !$isInStock ? 'disabled' : ''; ?>
                                >
                                    <?php echo $isInStock ? 'Add to Cart' : 'Out of Stock'; ?>
                                </button>
                            </div>
                        </div>

                        <?php if ($inCartQty > 0): ?>
                            <p class="in-cart-notice">
                                You currently have <strong><?php echo $inCartQty; ?></strong> of this item in your cart.
                                <a href="<?php echo e(site_url('customer/cart.php')); ?>" class="editorial-link in-cart-link">
                                    View Cart &rarr;
                                </a>
                            </p>
                        <?php endif; ?>
                    </form>

                    <!-- Editorial Specification Details -->
                    <dl class="product-meta-specs">
                        <div class="product-meta-row">
                            <dt>Category</dt>
                            <dd><?php echo e($product['category_name']); ?></dd>
                        </div>
                        <div class="product-meta-row">
                            <dt>Availability</dt>
                            <dd>
                                <?php if ($isLowStock): ?>
                                    Low Stock &mdash; <?php echo $stockQty; ?> available
                                <?php elseif ($isInStock): ?>
                                    In Stock &mdash; <?php echo $stockQty; ?> available
                                <?php else: ?>
                                    Currently unavailable
                                <?php endif; ?>
                            </dd>
                        </div>
                        <div class="product-meta-row">
                            <dt>Preparation</dt>
                            <dd>Curated for daily home and studio tea ritual</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
