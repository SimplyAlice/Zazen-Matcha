<?php
/**
 * Customer Shopping Cart Page
 * Zazen Matcha & Tea Co.
 *
 * Displays and manages the session-based shopping cart ($_SESSION['cart'])
 * using MySQL as the single source of truth for product details, pricing,
 * status, and stock availability.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

ensure_session_started();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$cartPageUrl = site_url('customer/cart.php');

// ----------------------------------------------------------------------------
// 1. Handle POST Cart Actions (update, remove, clear)
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = isset($_POST['csrf_token']) && !is_array($_POST['csrf_token'])
        ? (string) $_POST['csrf_token']
        : null;

    if (!verify_csrf_token($submittedToken)) {
        set_flash_message('error', 'Invalid security token. Please try submitting the form again.');
        redirect($cartPageUrl);
    }

    $action = isset($_POST['action']) && !is_array($_POST['action'])
        ? trim((string) $_POST['action'])
        : '';

    // Action A: Clear Entire Cart
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        set_flash_message('success', 'Your shopping cart has been cleared.');
        redirect($cartPageUrl);
    }

    // Validate product_id for single-item actions (remove / update)
    $rawProductId = $_POST['product_id'] ?? '';
    $postProductId = (!is_array($rawProductId) && ctype_digit((string) $rawProductId) && (int) $rawProductId > 0)
        ? (int) $rawProductId
        : 0;

    if ($postProductId <= 0) {
        set_flash_message('error', 'Invalid product selected.');
        redirect($cartPageUrl);
    }

    // Action B: Remove Single Item
    if ($action === 'remove') {
        if (isset($_SESSION['cart'][$postProductId])) {
            unset($_SESSION['cart'][$postProductId]);
            set_flash_message('success', 'Item removed from your cart.');
        } else {
            set_flash_message('info', 'That item was already removed from your cart.');
        }
        redirect($cartPageUrl);
    }

    // Action C: Update Item Quantity
    if ($action === 'update') {
        $rawQty = $_POST['quantity'] ?? '';
        $requestedQty = (!is_array($rawQty) && filter_var($rawQty, FILTER_VALIDATE_INT) !== false)
            ? (int) $rawQty
            : 0;

        if ($requestedQty < 1) {
            set_flash_message('error', 'Please enter a valid quantity of at least 1.');
            redirect($cartPageUrl);
        }

        // Re-query the product from MySQL (never trust client-side price or stock)
        $productSql = "
            SELECT product_id, name, price, stock_quantity, status
            FROM products
            WHERE product_id = :product_id
              AND status = :status
            LIMIT 1
        ";
        $productRow = db_fetch_one($pdo, $productSql, [
            'product_id' => $postProductId,
            'status'     => 'active',
        ]);

        if ($productRow === null) {
            unset($_SESSION['cart'][$postProductId]);
            set_flash_message('error', 'This product is no longer available and has been removed from your cart.');
            redirect($cartPageUrl);
        }

        $availableStock = (int) $productRow['stock_quantity'];

        if ($availableStock <= 0) {
            set_flash_message('error', 'Sorry, "' . $productRow['name'] . '" is currently out of stock.');
            redirect($cartPageUrl);
        }

        if ($requestedQty > $availableStock) {
            set_flash_message(
                'error',
                sprintf(
                    'Cannot set quantity to %d for "%s". Only %d available in stock.',
                    $requestedQty,
                    $productRow['name'],
                    $availableStock
                )
            );
            redirect($cartPageUrl);
        }

        $_SESSION['cart'][$postProductId] = $requestedQty;
        set_flash_message(
            'success',
            sprintf('Updated quantity for "%s" to %d.', $productRow['name'], $requestedQty)
        );
        redirect($cartPageUrl);
    }

    set_flash_message('error', 'Unrecognised cart action.');
    redirect($cartPageUrl);
}

// ----------------------------------------------------------------------------
// 2. Load & Validate Current Session Cart Items Against MySQL
// ----------------------------------------------------------------------------
$cartNotices    = [];
$cleanCart      = [];

foreach ($_SESSION['cart'] as $rawId => $rawQty) {
    if (ctype_digit((string) $rawId) && (int) $rawId > 0 && filter_var($rawQty, FILTER_VALIDATE_INT) !== false && (int) $rawQty >= 1) {
        $cleanCart[(int) $rawId] = (int) $rawQty;
    } else {
        unset($_SESSION['cart'][$rawId]);
        $cartNotices[] = 'An invalid cart entry was removed automatically.';
    }
}

$productsById = [];
if (!empty($cleanCart)) {
    $productIds   = array_keys($cleanCart);
    $placeholders = implode(', ', array_fill(0, count($productIds), '?'));

    $batchSql = "
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
        WHERE p.status = 'active'
          AND p.product_id IN ({$placeholders})
        ORDER BY p.product_id ASC
    ";

    $stmt = $pdo->prepare($batchSql);
    $stmt->execute(array_values($productIds));
    $fetchedProducts = $stmt->fetchAll();

    foreach ($fetchedProducts as $row) {
        $productsById[(int) $row['product_id']] = $row;
    }
}

// Reconcile session cart with active database products
$cartItems     = [];
$cartSubtotal  = 0.0;
$hasCartIssues = false;

foreach ($cleanCart as $pid => $sessionQty) {
    // If product was deleted or set to inactive, remove it from session cart and notify customer
    if (!isset($productsById[$pid])) {
        unset($_SESSION['cart'][$pid]);
        $cartNotices[] = 'An item in your cart is no longer available in our active catalogue and has been removed.';
        continue;
    }

    $product        = $productsById[$pid];
    $unitPrice      = (float) $product['price'];
    $availableStock = (int) $product['stock_quantity'];
    $isOutOfStock   = ($availableStock <= 0);
    $exceedsStock   = (!$isOutOfStock && $sessionQty > $availableStock);

    if ($isOutOfStock) {
        $hasCartIssues = true;
        $cartNotices[] = sprintf(
            '"%s" is currently out of stock. Please remove it from your cart before proceeding to checkout.',
            $product['name']
        );
    } elseif ($exceedsStock) {
        $hasCartIssues = true;
        $cartNotices[] = sprintf(
            'Your cart has %d of "%s", but only %d %s currently in stock. Please update the quantity to proceed.',
            $sessionQty,
            $product['name'],
            $availableStock,
            $availableStock === 1 ? 'is' : 'are'
        );
    }

    $lineTotal = $isOutOfStock ? 0.0 : ($unitPrice * $sessionQty);
    $cartSubtotal += $lineTotal;

    $cartItems[] = [
        'product_id'      => $pid,
        'category_id'     => (int) $product['category_id'],
        'category_name'   => $product['category_name'],
        'name'            => $product['name'],
        'price'           => $unitPrice,
        'stock_quantity'  => $availableStock,
        'image'           => $product['image'],
        'quantity'        => $sessionQty,
        'line_total'      => $lineTotal,
        'is_out_of_stock' => $isOutOfStock,
        'exceeds_stock'   => $exceedsStock,
    ];
}

$cartNotices = array_values(array_unique($cartNotices));
$isCartEmpty = empty($cartItems);
$canCheckout = (!$isCartEmpty && !$hasCartIssues);

$pageTitle = 'Your Cart';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Cart Page Header -->
<section class="cart-hero" aria-labelledby="cart-heading">
    <div class="container">
        <div class="cart-hero-inner">
            <div>
                <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Selection</span>
                <h1 id="cart-heading" class="cart-title">Your Cart</h1>
                <p class="cart-subtitle">
                    Review your selected matcha powders, vessels, and ritual sets before proceeding to checkout.
                </p>
            </div>
            <a href="<?php echo e(site_url('customer/products.php')); ?>" class="editorial-link cart-continue-top">
                &larr; Continue Shopping
            </a>
        </div>
    </div>
</section>

<!-- Main Cart Content -->
<section class="section cart-section" aria-label="Shopping cart contents">
    <div class="container">
        <?php if (!empty($cartNotices)): ?>
            <div class="cart-warnings" role="alert">
                <p class="cart-warnings-title">Please review your cart:</p>
                <ul class="cart-warnings-list">
                    <?php foreach ($cartNotices as $notice): ?>
                        <li><?php echo e($notice); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($isCartEmpty): ?>
            <!-- Empty Cart State -->
            <div class="empty-state cart-empty-state">
                <span class="eyebrow">Empty Selection</span>
                <h2 class="empty-state-title">Your cart is quiet.</h2>
                <p class="empty-state-text">
                    You have not added any matcha powders, ceramic vessels, or gift sets to your cart yet.
                </p>
                <div class="empty-state-actions">
                    <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-primary">
                        Continue Shopping
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Left Column: Cart Items List -->
                <div class="cart-items-column">
                    <div class="cart-items-header">
                        <span class="cart-items-count">
                            <?php echo count($cartItems); ?>
                            <?php echo count($cartItems) === 1 ? 'Product' : 'Products'; ?> in Cart
                        </span>

                        <!-- Clear Cart POST Form -->
                        <form action="<?php echo e($cartPageUrl); ?>" method="post" class="clear-cart-form" data-clear-cart-form>
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="clear-cart-btn">
                                Clear Cart
                            </button>
                        </form>
                    </div>

                    <div class="cart-item-list">
                        <?php foreach ($cartItems as $item): ?>
                            <?php
                            $itemId         = (int) $item['product_id'];
                            $itemUrl        = site_url('customer/product.php?id=' . $itemId);
                            $itemImageUrl   = product_image_url($item['image']);
                            $itemStock      = (int) $item['stock_quantity'];
                            $itemQty        = (int) $item['quantity'];
                            $isOutOfStock   = $item['is_out_of_stock'];
                            $exceedsStock   = $item['exceeds_stock'];
                            ?>
                            <article class="cart-item <?php echo ($isOutOfStock || $exceedsStock) ? 'cart-item-invalid' : ''; ?>">
                                <a href="<?php echo e($itemUrl); ?>" class="cart-item-media" aria-label="View <?php echo e($item['name']); ?>">
                                    <img
                                        src="<?php echo e($itemImageUrl); ?>"
                                        alt="<?php echo e($item['name']); ?>"
                                        width="900"
                                        height="1100"
                                        loading="lazy"
                                    >
                                </a>

                                <div class="cart-item-details">
                                    <div class="cart-item-top">
                                        <div>
                                            <span class="product-card-category"><?php echo e($item['category_name']); ?></span>
                                            <h2 class="cart-item-title">
                                                <a href="<?php echo e($itemUrl); ?>">
                                                    <?php echo e($item['name']); ?>
                                                </a>
                                            </h2>
                                            <p class="cart-item-unit-price">
                                                <?php echo e(format_price($item['price'])); ?> each
                                            </p>
                                        </div>

                                        <div class="cart-item-line-total">
                                            <?php if ($isOutOfStock): ?>
                                                <span class="stock-badge stock-out">Unavailable</span>
                                            <?php else: ?>
                                                <span class="line-total-amount"><?php echo e(format_price($item['line_total'])); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Stock Status / Warning Feedback -->
                                    <div class="cart-item-status">
                                        <?php if ($isOutOfStock): ?>
                                            <p class="cart-stock-warning">
                                                Out of Stock &mdash; This item is currently unavailable and cannot be purchased.
                                            </p>
                                        <?php elseif ($exceedsStock): ?>
                                            <p class="cart-stock-warning">
                                                Stock Limit Reached &mdash; Only <strong><?php echo $itemStock; ?></strong> available in stock (you have <?php echo $itemQty; ?> in cart). Please update quantity to <?php echo $itemStock; ?> or fewer.
                                            </p>
                                        <?php elseif ($itemStock <= 10): ?>
                                            <span class="stock-badge stock-low">Low Stock (<?php echo $itemStock; ?> available)</span>
                                        <?php else: ?>
                                            <span class="stock-badge stock-in">In Stock</span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Item Actions: Update Quantity (POST) & Remove (POST) -->
                                    <div class="cart-item-actions">
                                        <?php if (!$isOutOfStock): ?>
                                            <form action="<?php echo e($cartPageUrl); ?>" method="post" class="cart-update-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?php echo $itemId; ?>">

                                                <label for="cart-qty-<?php echo $itemId; ?>" class="visually-hidden">
                                                    Quantity for <?php echo e($item['name']); ?>
                                                </label>
                                                <div class="quantity-stepper cart-qty-stepper">
                                                    <button
                                                        type="button"
                                                        class="qty-step-btn"
                                                        data-qty-step="-1"
                                                        aria-label="Decrease quantity for <?php echo e($item['name']); ?>"
                                                    >&minus;</button>
                                                    <input
                                                        type="number"
                                                        id="cart-qty-<?php echo $itemId; ?>"
                                                        name="quantity"
                                                        class="quantity-input"
                                                        value="<?php echo $itemQty; ?>"
                                                        min="1"
                                                        max="<?php echo $itemStock; ?>"
                                                        required
                                                    >
                                                    <button
                                                        type="button"
                                                        class="qty-step-btn"
                                                        data-qty-step="1"
                                                        aria-label="Increase quantity for <?php echo e($item['name']); ?>"
                                                    >&#43;</button>
                                                </div>

                                                <button type="submit" class="btn btn-outline cart-update-btn">
                                                    Update
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="<?php echo e($cartPageUrl); ?>" method="post" class="cart-remove-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo $itemId; ?>">
                                            <button
                                                type="submit"
                                                class="cart-remove-btn"
                                                aria-label="Remove <?php echo e($item['name']); ?> from cart"
                                            >
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <aside class="cart-summary-column" aria-labelledby="cart-summary-heading">
                    <div class="cart-summary-card">
                        <h2 id="cart-summary-heading" class="cart-summary-title">Order Summary</h2>

                        <dl class="cart-summary-rows">
                            <div class="cart-summary-row">
                                <dt>Subtotal</dt>
                                <dd><?php echo e(format_price($cartSubtotal)); ?></dd>
                            </div>
                            <div class="cart-summary-row cart-summary-row-muted">
                                <dt>Shipping</dt>
                                <dd>Calculated at next step</dd>
                            </div>
                            <div class="cart-summary-row cart-summary-total">
                                <dt>Total</dt>
                                <dd><?php echo e(format_price($cartSubtotal)); ?></dd>
                            </div>
                        </dl>

                        <p class="cart-summary-note">
                            Shipping and checkout details will be calculated at the next step.
                        </p>

                        <div class="cart-summary-actions">
                            <?php if ($canCheckout): ?>
                                <a href="<?php echo e(site_url('customer/checkout.php')); ?>" class="btn btn-primary cart-checkout-btn">
                                    Proceed to Checkout
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-primary cart-checkout-btn" disabled aria-disabled="true">
                                    Proceed to Checkout
                                </button>
                                <p class="cart-checkout-blocked-msg">
                                    Please resolve the stock or availability warnings in your cart before proceeding to checkout.
                                </p>
                            <?php endif; ?>

                            <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-outline cart-continue-btn">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
