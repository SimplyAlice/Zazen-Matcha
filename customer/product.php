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

    if (!empty($_POST['return_url']) && !is_array($_POST['return_url'])) {
        $candidateReturn = trim((string) $_POST['return_url']);
        $parsed = parse_url($candidateReturn);
        $candidatePath = $parsed['path'] ?? $candidateReturn;
        if (strpos($candidatePath, BASE_URL) === 0 || (BASE_URL === '' && strpos($candidatePath, '/') === 0)) {
            $returnUrl = $candidateReturn;
        } elseif (strpos($candidateReturn, 'customer/') === 0 || strpos($candidateReturn, 'index.php') === 0) {
            $returnUrl = site_url($candidateReturn);
        }
    }

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

    if (!empty($_POST['buy_now'])) {
        redirect(site_url('customer/cart.php'));
    }

    redirect($returnUrl);
}

// ----------------------------------------------------------------------------
// 2. Load Product From GET ID
// ----------------------------------------------------------------------------
$rawGetId = $_GET['id'] ?? '';
$productId = (!is_array($rawGetId) && ctype_digit((string) $rawGetId) && (int) $rawGetId > 0)
    ? (int) $rawGetId
    : 0;

$product         = null;
$relatedProducts = [];

if ($productId > 0) {
    $product = db_fetch_one($pdo, $productQuerySql, [
        'product_id' => $productId,
        'status'     => 'active',
    ]);

    if ($product !== null) {
        $relatedSql = "
            SELECT
                p.product_id,
                p.name,
                p.price,
                p.image
            FROM products p
            WHERE p.status = :status
              AND p.product_id != :current_id
            ORDER BY (p.category_id = :cat_id) DESC, p.product_id ASC
            LIMIT 2
        ";
        $relatedProducts = db_fetch_all($pdo, $relatedSql, [
            'status'     => 'active',
            'current_id' => (int) $product['product_id'],
            'cat_id'     => (int) $product['category_id'],
        ]);
    }
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
    $inCartQty       = isset($_SESSION['cart'][$productId]) ? (int) $_SESSION['cart'][$productId] : 0;
    // Determine category portal and back-link destination
    if ($categoryId === 5) {
        $parentShopUrl   = site_url('customer/drinks.php');
        $parentShopLabel = 'Drinks Menu';
        $categoryShopUrl = site_url('customer/drinks.php');
    } elseif ($categoryId === 1 || $categoryId === 4) {
        $parentShopUrl   = site_url('customer/tea.php');
        $parentShopLabel = 'Tea Collection';
        $categoryShopUrl = site_url('customer/tea.php?category=' . $categoryId);
    } else {
        $parentShopUrl   = site_url('customer/products.php');
        $parentShopLabel = 'Shop';
        $categoryShopUrl = site_url('customer/products.php?category=' . $categoryId);
    }

    // Contextual secondary thumbnails based on product category & individual item
    $galleryThumbs = get_product_gallery($product);
    ?>

    <!-- Breadcrumb Bar -->
    <div class="product-breadcrumb-bar">
        <div class="container product-breadcrumb-inner">
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb-list">
                    <li>
                        <a href="<?php echo e(site_url('index.php')); ?>">Home</a>
                    </li>
                    <li aria-hidden="true" class="breadcrumb-sep">/</li>
                    <li>
                        <a href="<?php echo e($parentShopUrl); ?>"><?php echo e($parentShopLabel); ?></a>
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

            <a href="<?php echo e($parentShopUrl); ?>" class="breadcrumb-back-link">
                &larr; Back to <?php echo e($parentShopLabel); ?>
            </a>
        </div>
    </div>

    <!-- Main Product Detail Area -->
    <section class="section product-detail-section" aria-labelledby="product-detail-heading">
        <div class="container">
            <div class="product-detail-grid">
                <!-- Left Column: Product Imagery & Thumbnail Strip -->
                <div class="product-detail-media">
                    <div class="product-detail-image-frame">
                        <img
                            src="<?php echo e($imageUrl); ?>"
                            alt="<?php echo e($product['name']); ?>"
                            id="product-main-image"
                            data-default-src="<?php echo e($imageUrl); ?>"
                            data-default-alt="<?php echo e($product['name']); ?>"
                            width="1000"
                            height="1000"
                        >
                    </div>

                    <?php if (count($galleryThumbs) > 1): ?>
                        <div class="product-gallery-thumbs" role="group" aria-label="Product image thumbnails">
                            <?php foreach ($galleryThumbs as $thumbIndex => $thumb): ?>
                                <button
                                    type="button"
                                    class="product-thumb-btn<?php echo $thumbIndex === 0 ? ' is-active' : ''; ?>"
                                    data-gallery-thumb="<?php echo e($thumb['src']); ?>"
                                    data-gallery-alt="<?php echo e($thumb['alt']); ?>"
                                    aria-label="Show <?php echo e($thumb['alt']); ?>"
                                    aria-pressed="<?php echo $thumbIndex === 0 ? 'true' : 'false'; ?>"
                                >
                                    <img
                                        src="<?php echo e($thumb['src']); ?>"
                                        alt="<?php echo e($thumb['alt']); ?>"
                                        width="320"
                                        height="240"
                                        loading="lazy"
                                    >
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Product Information, Purchase Row, Accordion Specs & Related Products -->
                <div class="product-detail-info">
                    <h1 id="product-detail-heading" class="product-detail-title">
                        <?php echo e($product['name']); ?>
                    </h1>

                    <div class="product-detail-price-row">
                        <div class="product-detail-rating-group">
                            <span class="product-detail-stars" aria-label="5 out of 5 stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
                            <span class="product-detail-reviews">124 reviews</span>

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

                        <span class="product-detail-price">
                            <?php echo e(format_price($product['price'])); ?>
                        </span>
                    </div>

                    <div class="product-detail-description">
                        <p><?php echo e($product['description']); ?></p>
                    </div>

                    <!-- Add to Cart / Buy Now POST Form -->
                    <form
                        action="<?php echo e(site_url('customer/product.php?id=' . $productId)); ?>"
                        method="post"
                        class="product-cart-form"
                    >
                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">

                        <div class="product-purchase-controls">
                            <div class="quantity-field">
                                <label for="quantity-input" class="visually-hidden">Quantity</label>
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
                                    class="btn btn-primary add-to-cart-btn btn-pill"
                                    <?php echo !$isInStock ? 'disabled' : ''; ?>
                                >
                                    <?php echo $isInStock ? 'Add to Cart' : 'Out of Stock'; ?>
                                </button>
                            </div>

                            <div class="buy-now-action">
                                <button
                                    type="submit"
                                    name="buy_now"
                                    value="1"
                                    class="btn btn-outline buy-now-btn btn-pill"
                                    <?php echo !$isInStock ? 'disabled' : ''; ?>
                                >
                                    Buy Now
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

                    <!-- Category-Aware Accordion Specification Sections -->
                    <?php
                    if (in_array($categoryId, [1, 4], true)) {
                        $ingredientsHeading = 'Ingredients &amp; Origin';
                        $prepHeading        = 'Brewing &amp; Ritual';
                    } elseif ($categoryId === 6) {
                        $ingredientsHeading = 'Botanical Specimen &amp; Potting';
                        $prepHeading        = 'Care, Light &amp; Watering';
                    } else {
                        $ingredientsHeading = 'Materials &amp; Craft';
                        $prepHeading        = 'Care &amp; Usage';
                    }
                    $ingredientsText    = '';
                    $prepText           = '';

                    if ($categoryId === 1) {
                        $ingredientsText = '100% Stone-Ground Japanese Green Tea (Tencha) from Uji, Kyoto. Single-origin, shade-cultivated first harvest with zero additives.';
                        $prepText        = 'Sift 1/2 tsp (1g) into a pre-warmed chawan bowl, add 2 oz (60ml) filtered water at 176&deg;F (80&deg;C), and whisk briskly with a bamboo chasen in a W-motion until a dense, vibrant emerald microfoam forms.';
                    } elseif ($categoryId === 4) {
                        switch ($productId) {
                            case 7:
                                $ingredientsText = 'Artisan herbal infusion blending hand-harvested saffron threads, whole Egyptian chamomile blossoms, sweet fennel seeds, and sun-dried orange peel. Naturally caffeine-free.';
                                $prepText        = 'Measure 2.5g in 250ml freshly boiled filtered water (95&deg;C / 203&deg;F). Steep for 5 to 7 minutes for a luminous golden-amber infusion. Enjoy pure or with a touch of raw honey.';
                                break;
                            case 8:
                                $ingredientsText = 'Pure whole butterfly pea blossoms (Clitoria ternatea), hand-cut organic lemongrass, and wild Moroccan spearmint leaves. Naturally caffeine-free.';
                                $prepText        = 'Infuse 2g (5&ndash;7 blossoms) in 250ml hot water (90&deg;C / 194&deg;F) for 4 to 5 minutes. Enjoy pure cobalt-blue or squeeze fresh lemon juice to watch it naturally transform to royal violet.';
                                break;
                            case 9:
                                $ingredientsText = 'Semi-oxidized high-mountain Formosa-style oolong tea leaves scented with fresh autumn golden osmanthus blossom petals (Osmanthus fragrans).';
                                $prepText        = 'Steep 4g in 200ml heated water (85&deg;C&ndash;90&deg;C / 185&deg;F&ndash;194&deg;F). Gongfu style: steep 45&ndash;60 seconds, re-steeping up to 5 times. Western style: steep 3 minutes.';
                                break;
                            case 10:
                                $ingredientsText = 'Spring-harvested Chinese white tea (Bai Mu Dan) comprising silvery downy terminal buds and tender young leaves. Minimally processed and naturally sun-wilted.';
                                $prepText        = 'Steep 3g in 250ml filtered water (80&deg;C / 176&deg;F) for 3 to 4 minutes. Leaves can be re-steeped up to 3 times to reveal delicate notes of honeydew melon and wild florals.';
                                break;
                            case 11:
                                $ingredientsText = 'Cellar-aged Yunnan Shou (ripe) pu-erh post-fermented broad-leaf Camellia sinensis. Naturally fermented with zero artificial flavourings.';
                                $prepText        = 'Use 5g in 150ml boiling water (100&deg;C / 212&deg;F). Perform a quick 5-second initial rinse to open the leaves, then steep 30 to 45 seconds across multiple rich infusions.';
                                break;
                            case 12:
                                $ingredientsText = 'Hand-rolled silver-tipped green tea shoots from Fujian, meticulously layered and scented overnight with fresh nocturnal night-blooming jasmine flowers.';
                                $prepText        = 'Drop 3g (~15 pearls) into 250ml filtered water at 75&deg;C&ndash;80&deg;C (167&deg;F&ndash;176&deg;F). Steep for 2 to 3 minutes as the pearls slowly unfurl in the cup. Re-steep up to 3 times.';
                                break;
                            default:
                                $ingredientsText = 'Single-origin loose-leaf specialty tea honoring traditional heritage cultivars and artisanal small-batch processing.';
                                $prepText        = 'Infuse 3g in 250ml filtered water at 80&deg;C&ndash;90&deg;C for 3 to 4 minutes. Re-steep as desired.';
                                break;
                        }
                    } elseif ($categoryId === 2) {
                        switch ($productId) {
                            case 23:
                                $ingredientsText = 'Handcrafted high-fired stoneware ceramic bowl. Authentic Kuro-Oribe style dark iron glaze with subtle forest-green pooling and wabi-sabi hand-pinched contouring.';
                                $prepText        = 'Warm with hot water before whisking. Hand wash with warm water only, without harsh abrasives or dishwashing detergent, and allow to air dry completely.';
                                break;
                            case 24:
                                $ingredientsText = 'Stoneware clay body layered with natural feldspathic Shino glaze exhibiting fine crackle lines and warm orange-red iron flashing (hi-iro).';
                                $prepText        = 'Pre-warm before tea preparation. Rinse thoroughly after use with warm water and soft cloth; dry well to preserve the glaze integrity.';
                                break;
                            case 25:
                                $ingredientsText = 'Pair of wheel-thrown ceramic yunomi tea cups with natural wood-ash glaze in muted sage and stone-grey tones, featuring tactile fluted side facets.';
                                $prepText        = 'Comfortable to cup in both hands. Hand wash gently; suitable for hot green teas, hojicha, and botanical infusions.';
                                break;
                            case 26:
                                $ingredientsText = 'Authentic Hagi-yaki porous stoneware chawan with soft blush-cream glaze, fine kan-nyu crazing, and traditional notched wari-kodai base.';
                                $prepText        = 'Soak in clean water for a few minutes before first use. The porous clay and crazing will gradually develop a cherished tea patina over years of use.';
                                break;
                            case 27:
                                $ingredientsText = 'Sustainable yakisugi charred cedar wood frame with a removable, precision-fitted matte cream ceramic slotted drainage tray.';
                                $prepText        = 'Empty catch tray after tea sessions. Wipe cedar wood dry with a soft cloth; wash ceramic plate with warm water.';
                                break;
                            case 33:
                                $ingredientsText = 'Moulded stoneware ceramic whisk keeper (chasen-naoshi) finished in a classic pale celadon jade-green crackle glaze.';
                                $prepText        = 'After rinsing your bamboo whisk with clear warm water, mount it upright onto the curved bell keeper to air-dry and preserve tine shape.';
                                break;
                            case 34:
                                $ingredientsText = '100% GOTS-certified unbleached natural flax organic linen cloth (chakin), woven without synthetic sizing, bleaches, or dyes.';
                                $prepText        = 'Dampen slightly with clean warm water to fold into traditional ritual shape for warming and wiping chawan bowls. Hand rinse and line dry.';
                                break;
                            case 35:
                                $ingredientsText = 'Single-stem hand-split natural golden bamboo, gently steam-curved at the tip to create a traditional chashaku matcha measure.';
                                $prepText        = 'Wipe clean with a dry linen cloth after measuring matcha. Never soak bamboo in water or wash with dishwashing detergents.';
                                break;
                            default:
                                $ingredientsText = 'Artisan Japanese Teaware: High-fired stoneware ceramic or natural hand-split bamboo crafted for mindful tea ritual.';
                                $prepText        = 'Warm ceramic bowl with hot water prior to whisking. Rinse bamboo whisk in warm water to soften the tines before use; hand-rinse without detergent and air dry on a chasen-naoshi holder.';
                                break;
                        }
                    } elseif ($categoryId === 3) {
                        switch ($productId) {
                            case 28:
                                $ingredientsText = 'Curated artisan ceramic ensemble: Hand-glazed spouted stoneware pouring pitcher (katakuchi), two matching yunomi cups, and unbleached linen wrap in a recyclable presentation box.';
                                $prepText        = 'Ideal for sharing whisked matcha or steeped specialty teas. Hand wash all ceramic pieces gently with warm water.';
                                break;
                            default:
                                $ingredientsText = 'Curated Ceremonial Gift Set: Complete ceremonial kit featuring stone-ground matcha powder, handcrafted stoneware bowl, bamboo whisk, and measuring scoop.';
                                $prepText        = 'Designed for complete ceremonial preparation. Pre-warm the ceramic chawan, measure matcha with the bamboo scoop, and whisk with the chasen in a brisk W-motion until velvety and frothy.';
                                break;
                        }
                    } elseif ($categoryId === 6) {
                        switch ($productId) {
                            case 29:
                                $ingredientsText = 'Living miniature dwarf flowering cherry shrub (Prunus cultivar) trained in classical bonsai habit, settled in an unglazed stoneware bonsai pot with living moss.';
                                $prepText        = 'Bright filtered sunlight. Water 2–3 times weekly when topsoil begins to dry. Flowering occurs seasonally in spring; keep away from artificial heaters and air conditioners.';
                                break;
                            case 30:
                                $ingredientsText = 'Living Japanese dwarf juniper (Juniperus procumbens nana) with sculpted curving trunk and emerald needle pads in a shallow glazed stoneware pot with moss.';
                                $prepText        = 'Requires bright indirect light or gentle morning sun with good airflow. Water thoroughly when soil starts drying; mist foliage weekly. Do not let roots sit in standing water.';
                                break;
                            case 31:
                                $ingredientsText = 'Living miniature maidenhair fern (Adiantum) bound in a sphere of natural peat, nutrient soil, and living sheet moss wrapped with biodegradable jute cord, with ceramic saucer.';
                                $prepText        = 'Soak the moss ball in water for 5 minutes once or twice weekly, squeeze gently, and place on saucer. Prefers medium to bright indirect indoor light.';
                                break;
                            case 32:
                                $ingredientsText = 'Living Satsuki azalea specimen with woody trunk and glossy foliage in an authentic terracotta bonsai basin with Kanuma acid-rich potting medium.';
                                $prepText        = 'Morning sun and afternoon shade. Keep soil consistently moist with filtered water. Protect from extreme winter frost; seasonal blooms appear in spring/summer.';
                                break;
                            default:
                                $ingredientsText = 'Living Japanese-inspired botanical specimen potted in artisan ceramic ware with natural moss dressing.';
                                $prepText        = 'Place in bright indirect light. Water when topsoil feels dry to touch. Care card included.';
                                break;
                        }
                    } else {
                        $ingredientsText = 'Carefully curated artisan tea and teaware by Zazen Matcha &amp; Tea Co.';
                        $prepText        = 'Prepare with filtered water at appropriate temperature for best aromatic expression.';
                    }
                    ?>
                    <div class="product-accordion-list">
                        <details class="product-accordion-item" open>
                            <summary class="product-accordion-summary">
                                <span><?php echo $ingredientsHeading; ?></span>
                                <span class="accordion-chevron" aria-hidden="true">&#8964;</span>
                            </summary>
                            <div class="product-accordion-body">
                                <p><?php echo $ingredientsText; ?></p>
                            </div>
                        </details>

                        <details class="product-accordion-item" open>
                            <summary class="product-accordion-summary">
                                <span><?php echo $prepHeading; ?></span>
                                <span class="accordion-chevron" aria-hidden="true">&#8964;</span>
                            </summary>
                            <div class="product-accordion-body">
                                <p><?php echo $prepText; ?></p>
                            </div>
                        </details>

                        <details class="product-accordion-item" open>
                            <summary class="product-accordion-summary">
                                <span>Shipping &amp; Packaging</span>
                                <span class="accordion-chevron" aria-hidden="true">&#8964;</span>
                            </summary>
                            <div class="product-accordion-body">
                                <p>
                                    Carefully packed in eco-friendly protective packaging to safeguard delicate ceramics and retain tea freshness (<?php echo $isInStock ? $stockQty . ' available in stock' : 'Currently unavailable'; ?>).
                                </p>
                            </div>
                        </details>
                    </div>

                    <!-- Related Products Section -->
                    <?php if (!empty($relatedProducts)): ?>
                        <div class="product-related-section" aria-labelledby="related-products-heading">
                            <h2 id="related-products-heading" class="product-related-heading">Related Products</h2>
                            <div class="product-related-grid">
                                <?php foreach ($relatedProducts as $rel): ?>
                                    <?php
                                    $relUrl   = site_url('customer/product.php?id=' . (int) $rel['product_id']);
                                    $relImage = product_image_url($rel['image']);
                                    ?>
                                    <a href="<?php echo e($relUrl); ?>" class="related-mini-card">
                                        <div class="related-mini-media">
                                            <img
                                                src="<?php echo e($relImage); ?>"
                                                alt="<?php echo e($rel['name']); ?>"
                                                width="300"
                                                height="240"
                                                loading="lazy"
                                            >
                                        </div>
                                        <div class="related-mini-body">
                                            <span class="related-mini-title"><?php echo e($rel['name']); ?></span>
                                            <span class="related-mini-price"><?php echo e(format_price($rel['price'])); ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
