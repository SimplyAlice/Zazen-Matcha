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

    // Action D: Add Item to Cart
    if ($action === 'add') {
        $rawQty = $_POST['quantity'] ?? 1;
        $requestedQty = (!is_array($rawQty) && filter_var($rawQty, FILTER_VALIDATE_INT) !== false)
            ? (int) $rawQty
            : 1;

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
            set_flash_message('error', 'This product is no longer available.');
            redirect($cartPageUrl);
        }

        $availableStock = (int) $productRow['stock_quantity'];

        if ($availableStock <= 0) {
            set_flash_message('error', 'Sorry, "' . $productRow['name'] . '" is currently out of stock.');
            redirect($cartPageUrl);
        }

        $existingCartQty = isset($_SESSION['cart'][$postProductId])
            ? (int) $_SESSION['cart'][$postProductId]
            : 0;

        $newCartQty = $existingCartQty + $requestedQty;

        if ($requestedQty > $availableStock || $newCartQty > $availableStock) {
            if ($existingCartQty > 0) {
                set_flash_message(
                    'error',
                    sprintf(
                        'Cannot add %d more of "%s". Only %d available in stock and you already have %d in your cart.',
                        $requestedQty,
                        $productRow['name'],
                        $availableStock,
                        $existingCartQty
                    )
                );
            } else {
                set_flash_message(
                    'error',
                    sprintf('Cannot add %d of "%s". Only %d available in stock.', $requestedQty, $productRow['name'], $availableStock)
                );
            }
            redirect($cartPageUrl);
        }

        $_SESSION['cart'][$postProductId] = $newCartQty;

        set_flash_message(
            'success',
            sprintf('Added %d × %s to your cart.', $requestedQty, $productRow['name'])
        );

        $destUrl = $cartPageUrl;
        if (!empty($_POST['return_url']) && !is_array($_POST['return_url'])) {
            $candidateReturn = trim((string) $_POST['return_url']);
            $parsed = parse_url($candidateReturn);
            $candidatePath = $parsed['path'] ?? $candidateReturn;
            if (strpos($candidatePath, BASE_URL) === 0 || (BASE_URL === '' && strpos($candidatePath, '/') === 0)) {
                $destUrl = $candidateReturn;
            } elseif (strpos($candidateReturn, 'customer/') === 0 || strpos($candidateReturn, 'index.php') === 0) {
                $destUrl = site_url($candidateReturn);
            }
        }

        redirect($destUrl);
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

$cartNotices       = array_values(array_unique($cartNotices));
$isCartEmpty       = empty($cartItems);
$canCheckout       = (!$isCartEmpty && !$hasCartIssues);
$estimatedShipping = ($cartSubtotal <= 0.0 || $cartSubtotal >= 500.00) ? 0.00 : 60.00;
$estimatedTotal    = $cartSubtotal + $estimatedShipping;

$pageTitle = 'Your Shopping Cart';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Cart Page Header -->
<section class="cart-hero" aria-labelledby="cart-heading">
    <div class="container">
        <div class="cart-hero-inner">
            <div>
                <h1 id="cart-heading" class="cart-title">Your Shopping Cart</h1>
                <nav class="cart-breadcrumb" aria-label="Breadcrumb">
                    <a href="<?php echo e(site_url('customer/products.php')); ?>">Products</a>
                    <span class="cart-breadcrumb-sep" aria-hidden="true">&gt;</span>
                    <span aria-current="page">Your Shopping Cart</span>
                </nav>
            </div>
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
                    You have not added any matcha powders, specialty teas, freshly prepared drinks, or ceramic vessels to your cart yet.
                </p>
                <div class="empty-state-actions">
                    <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-primary">
                        Shop Packaged Teas
                    </a>
                    <a href="<?php echo e(site_url('customer/drinks.php')); ?>" class="btn btn-outline" style="margin-left: 0.75rem;">
                        Order Fresh Drinks
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Left Column: Tabular Cart Items List -->
                <div class="cart-items-column">
                    <div class="cart-table-head" aria-hidden="true">
                        <span class="cart-col-product">Product</span>
                        <span class="cart-col-price">Price</span>
                        <span class="cart-col-qty">Quantity</span>
                        <span class="cart-col-total">Total</span>
                    </div>

                    <div class="cart-item-list">
                        <?php foreach ($cartItems as $rowIndex => $item): ?>
                            <?php
                            $itemNumber     = $rowIndex + 1;
                            $itemId         = (int) $item['product_id'];
                            $itemUrl        = site_url('customer/product.php?id=' . $itemId);
                            $itemImageUrl   = product_image_url($item['image']);
                            $itemStock      = (int) $item['stock_quantity'];
                            $itemQty        = (int) $item['quantity'];
                            $isOutOfStock   = $item['is_out_of_stock'];
                            $exceedsStock   = $item['exceeds_stock'];
                            $itemSublabel   = ((int) $item['category_id'] === 1)
                                ? 'Sourced from Uji'
                                : $item['category_name'];
                            ?>
                            <article class="cart-item <?php echo ($isOutOfStock || $exceedsStock) ? 'cart-item-invalid' : ''; ?>">
                                <!-- 1. Product Info Cell -->
                                <div class="cart-cell-product">
                                    <span class="cart-item-index"><?php echo $itemNumber; ?>.</span>

                                    <a href="<?php echo e($itemUrl); ?>" class="cart-item-media" aria-label="View <?php echo e($item['name']); ?>">
                                        <img
                                            src="<?php echo e($itemImageUrl); ?>"
                                            alt="<?php echo e($item['name']); ?>"
                                            width="160"
                                            height="160"
                                            loading="lazy"
                                        >
                                    </a>

                                    <div class="cart-item-info">
                                        <h2 class="cart-item-title">
                                            <a href="<?php echo e($itemUrl); ?>">
                                                <?php echo e($item['name']); ?>
                                            </a>
                                        </h2>
                                        <p class="cart-item-subtitle"><?php echo e($itemSublabel); ?></p>

                                        <!-- Stock Status / Warning Feedback -->
                                        <div class="cart-item-status">
                                            <?php if ($isOutOfStock): ?>
                                                <p class="cart-stock-warning">
                                                    Out of Stock &mdash; Unavailable for purchase.
                                                </p>
                                            <?php elseif ($exceedsStock): ?>
                                                <p class="cart-stock-warning">
                                                    Only <strong><?php echo $itemStock; ?></strong> in stock (you have <?php echo $itemQty; ?>).
                                                </p>
                                            <?php elseif ($itemStock <= 10): ?>
                                                <span class="stock-badge stock-low">Low Stock (<?php echo $itemStock; ?> left)</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Unit Price Cell -->
                                <div class="cart-cell-price">
                                    <span class="cart-item-unit-price">
                                        <?php echo e(format_price($item['price'])); ?>
                                    </span>
                                </div>

                                <!-- 3. Quantity Stepper Cell (POST Update Form) -->
                                <div class="cart-cell-qty">
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

                                            <button type="submit" class="cart-update-btn">
                                                Update
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="stock-badge stock-out">Out of Stock</span>
                                    <?php endif; ?>
                                </div>

                                <!-- 4. Line Total + Remove Action Cell -->
                                <div class="cart-cell-total">
                                    <div class="cart-item-line-total">
                                        <?php if ($isOutOfStock): ?>
                                            <span class="stock-badge stock-out">Unavailable</span>
                                        <?php else: ?>
                                            <span class="line-total-amount"><?php echo e(format_price($item['line_total'])); ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <form action="<?php echo e($cartPageUrl); ?>" method="post" class="cart-remove-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?php echo $itemId; ?>">
                                        <button
                                            type="submit"
                                            class="cart-remove-btn"
                                            aria-label="Remove <?php echo e($item['name']); ?> from cart"
                                        >
                                            <span>Remove</span>
                                            <span class="cart-remove-icon" aria-hidden="true">&times;</span>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Bottom Bar: Continue Shopping & Clear Cart -->
                    <div class="cart-footer-bar">
                        <div class="cart-continue-group">
                            <a href="<?php echo e(site_url('customer/products.php')); ?>" class="cart-continue-link">
                                &larr; Continue Shopping
                            </a>
                            <span class="cart-link-sep" style="margin: 0 0.5rem; color: var(--color-border);">&bull;</span>
                            <a href="<?php echo e(site_url('customer/drinks.php')); ?>" class="cart-continue-link">
                                Order Fresh Drinks &rarr;
                            </a>
                        </div>

                        <!-- Clear Cart POST Form -->
                        <form action="<?php echo e($cartPageUrl); ?>" method="post" class="clear-cart-form" data-clear-cart-form>
                            <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" class="clear-cart-btn">
                                Clear Cart
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <aside class="cart-summary-column" aria-labelledby="cart-summary-heading">
                    <div class="cart-summary-card">
                        <h2 id="cart-summary-heading" class="cart-summary-title">Order Summary</h2>

                        <dl class="cart-summary-rows">
                            <div class="cart-summary-row">
                                <dt>Subtotal:</dt>
                                <dd><?php echo e(format_price($cartSubtotal)); ?></dd>
                            </div>
                            <div class="cart-summary-row cart-summary-shipping-row">
                                <dt>
                                    Estimated Shipping:
                                    <span class="cart-shipping-subnote">(or free if qualifying)</span>
                                </dt>
                                <dd>
                                    <?php echo $estimatedShipping <= 0.0 ? 'Free' : e(format_price($estimatedShipping)); ?>
                                </dd>
                            </div>
                            <div class="cart-summary-row cart-summary-total">
                                <dt>Total:</dt>
                                <dd><?php echo e(format_price($estimatedTotal)); ?></dd>
                            </div>
                        </dl>

                        <!-- Promo Code Row -->
                        <div class="cart-promo-row" aria-label="Promotional code">
                            <label for="cart-promo-input" class="visually-hidden">Promo code</label>
                            <input
                                type="text"
                                id="cart-promo-input"
                                class="cart-promo-input"
                                placeholder="Promo code (optional)"
                                disabled
                                aria-disabled="true"
                            >
                            <button type="button" class="btn cart-promo-btn" disabled aria-disabled="true">
                                Apply
                            </button>
                        </div>

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
                        </div>

                        <!-- Trust Badges Row -->
                        <div class="cart-trust-badges" aria-label="Secure checkout and shipping assurances">
                            <span class="cart-trust-icon" title="Secure Checkout">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                    <polyline points="9 12 11 14 15 10"></polyline>
                                </svg>
                            </span>
                            <span class="cart-trust-icon" title="Carefully Packaged">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                </svg>
                            </span>
                            <span class="cart-trust-icon" title="Fast Dispatch">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="1" y="3" width="15" height="13"></rect>
                                    <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                                    <circle cx="5.5" cy="18.5" r="2.5"></circle>
                                    <circle cx="18.5" cy="18.5" r="2.5"></circle>
                                </svg>
                            </span>
                        </div>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
