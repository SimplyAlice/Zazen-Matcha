<?php
/**
 * Dedicated Prepared Drinks Page — Tea & Matcha Bar
 * Zazen Matcha & Tea Co.
 *
 * Dedicated shopping destination for prepared studio beverages (Category 5).
 * Allows customers to browse individual drinks, select quantities,
 * and add them directly to the cart.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Tea & Matcha Bar — Prepared Drinks Menu';

// 1. Fetch Category 5
$categorySql = "SELECT * FROM categories WHERE category_id = 5 LIMIT 1";
$drinksCategory = db_fetch_one($pdo, $categorySql);

// 2. Read filter parameters
$selectedFlight = isset($_GET['flight']) && in_array($_GET['flight'], ['all', 'matcha', 'iced'], true)
    ? (string) $_GET['flight']
    : 'all';

$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = clean_input((string) $_GET['search']);
}

$sortOption = 'featured';
$allowedSorts = [
    'featured'   => 'p.product_id ASC',
    'price_asc'  => 'p.price ASC, p.product_id ASC',
    'price_desc' => 'p.price DESC, p.product_id ASC',
    'name_asc'   => 'p.name ASC, p.product_id ASC',
];
if (isset($_GET['sort']) && isset($allowedSorts[(string) $_GET['sort']])) {
    $sortOption = (string) $_GET['sort'];
}
$orderBySql = $allowedSorts[$sortOption];

// 3. Query all prepared drinks (Category 5)
$whereClauses = ['p.status = :status', 'p.category_id = 5'];
$queryParams  = ['status' => 'active'];

if ($searchTerm !== '') {
    $whereClauses[] = '(p.name LIKE :s_name OR p.description LIKE :s_desc)';
    $like = '%' . $searchTerm . '%';
    $queryParams['s_name'] = $like;
    $queryParams['s_desc'] = $like;
}

$whereSql = implode(' AND ', $whereClauses);
$drinksSql = "
    SELECT
        p.product_id,
        p.category_id,
        p.name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        c.name AS category_name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.category_id
    WHERE {$whereSql}
    ORDER BY {$orderBySql}
";

$allDrinks = db_fetch_all($pdo, $drinksSql, $queryParams);

// Partition into flights if needed
// Flight 1: Matcha & Lattes (IDs 13 to 18)
// Flight 2: Iced Teas & Botanicals (IDs 19 to 22)
$flightMatchaIds = [13, 14, 15, 16, 17, 18];
$flightIcedIds   = [19, 20, 21, 22];

$drinks = [];
foreach ($allDrinks as $drink) {
    $pid = (int) $drink['product_id'];
    if ($selectedFlight === 'matcha' && !in_array($pid, $flightMatchaIds, true)) {
        continue;
    }
    if ($selectedFlight === 'iced' && !in_array($pid, $flightIcedIds, true)) {
        continue;
    }
    $drinks[] = $drink;
}

$drinkCount = count($drinks);

// Badge and metadata mapping
$drinkBadges = [
    13 => ['Pure Umami', 'No Added Sugar'],
    14 => ['Guest Favorite', 'Plant-Based Option'],
    15 => ['Signature Bar Drink', 'Three Distinct Layers'],
    16 => ['Natural Vanilla', 'Warm or Iced'],
    17 => ['Hydrating', 'Dairy-Free'],
    18 => ['Low Caffeine', 'Warm Toasted Notes'],
    19 => ['12h Cold Brew', 'Unsweetened'],
    20 => ['Semi-Oxidized', 'House Brew'],
    21 => ['Color-Shifting', 'Caffeine-Free'],
    22 => ['Botanical Blend', 'Caffeine-Free'],
];

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- 1. Dedicated Drinks Header Banner -->
<section class="section shop-hero drinks-hero" aria-labelledby="drinks-hero-heading">
    <div class="container">
        <div class="shop-hero-header">
            <span class="eyebrow">Cape Town Studio Tea Bar &bull; Crafted Fresh to Order</span>
            <h1 id="drinks-hero-heading" class="shop-hero-title">Tea &amp; Matcha Bar Drinks</h1>
            <p class="shop-hero-lead">
                Every beverage is crafted to order using single-origin Japanese ceremonial matcha, 
                artisan loose-leaf harvests, and house-infused botanical extracts. Select any beverage below to order.
            </p>
        </div>

        <div class="drinks-studio-meta-bar" role="note">
            <div class="drinks-meta-pill">
                <span class="drinks-meta-dot" aria-hidden="true"></span>
                <span><strong>Studio Counter:</strong> Tue &ndash; Sun: 08:30 &ndash; 16:30</span>
            </div>
            <div class="drinks-meta-pill">
                <span><strong>Milk Choices:</strong> Organic Oat, Almond, Fresh Jersey Milk</span>
            </div>
            <div class="drinks-meta-pill">
                <span><strong>Service:</strong> Enjoy in studio or takeaway</span>
            </div>
        </div>
    </div>
</section>

<!-- 2. Interactive Flight Filter & Search Bar -->
<section class="drinks-toolbar-section">
    <div class="container">
        <div class="drinks-toolbar-layout">
            <!-- Flight Filter Pills -->
            <nav class="drinks-filter-nav" aria-label="Drinks menu flights">
                <a href="<?php echo e(site_url('customer/drinks.php?flight=all' . ($searchTerm ? '&search=' . urlencode($searchTerm) : '') . ($sortOption !== 'featured' ? '&sort=' . urlencode($sortOption) : ''))); ?>"
                   class="drinks-filter-pill <?php echo $selectedFlight === 'all' ? 'is-active' : ''; ?>">
                    All Drinks (<?php echo count($allDrinks); ?>)
                </a>
                <a href="<?php echo e(site_url('customer/drinks.php?flight=matcha' . ($searchTerm ? '&search=' . urlencode($searchTerm) : '') . ($sortOption !== 'featured' ? '&sort=' . urlencode($sortOption) : ''))); ?>"
                   class="drinks-filter-pill <?php echo $selectedFlight === 'matcha' ? 'is-active' : ''; ?>">
                    Whisked Matcha &amp; Lattes (6)
                </a>
                <a href="<?php echo e(site_url('customer/drinks.php?flight=iced' . ($searchTerm ? '&search=' . urlencode($searchTerm) : '') . ($sortOption !== 'featured' ? '&sort=' . urlencode($sortOption) : ''))); ?>"
                   class="drinks-filter-pill <?php echo $selectedFlight === 'iced' ? 'is-active' : ''; ?>">
                    Iced Teas &amp; Botanicals (4)
                </a>
            </nav>

            <!-- Search & Sort Controls -->
            <div class="drinks-controls-group">
                <form action="<?php echo e(site_url('customer/drinks.php')); ?>" method="get" class="drinks-search-form" role="search">
                    <input type="hidden" name="flight" value="<?php echo e($selectedFlight); ?>">
                    <?php if ($sortOption !== 'featured'): ?>
                        <input type="hidden" name="sort" value="<?php echo e($sortOption); ?>">
                    <?php endif; ?>
                    <label for="drinks-search-input" class="sr-only">Search drinks</label>
                    <input
                        type="search"
                        id="drinks-search-input"
                        name="search"
                        value="<?php echo e($searchTerm); ?>"
                        placeholder="Search drinks..."
                        class="drinks-search-input"
                    >
                    <button type="submit" class="btn btn-outline drinks-search-btn">
                        Search
                    </button>
                    <?php if ($searchTerm !== ''): ?>
                        <a href="<?php echo e(site_url('customer/drinks.php?flight=' . urlencode($selectedFlight))); ?>" class="drinks-clear-search">
                            &times; Clear
                        </a>
                    <?php endif; ?>
                </form>

                <form action="<?php echo e(site_url('customer/drinks.php')); ?>" method="get" class="drinks-sort-form">
                    <input type="hidden" name="flight" value="<?php echo e($selectedFlight); ?>">
                    <?php if ($searchTerm !== ''): ?>
                        <input type="hidden" name="search" value="<?php echo e($searchTerm); ?>">
                    <?php endif; ?>
                    <label for="drinks-sort-select" class="sr-only">Sort drinks</label>
                    <select id="drinks-sort-select" name="sort" class="drinks-sort-select" onchange="this.form.submit()">
                        <option value="featured" <?php echo $sortOption === 'featured' ? 'selected' : ''; ?>>Sort by: Featured</option>
                        <option value="price_asc" <?php echo $sortOption === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sortOption === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo $sortOption === 'name_asc' ? 'selected' : ''; ?>>Name: A &ndash; Z</option>
                    </select>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- 3. Drinks Products Grid -->
<main class="section drinks-grid-section" id="drinks-catalog" aria-label="Drinks catalogue">
    <div class="container">
        <?php if (!empty($drinks)): ?>
            <div class="drinks-cards-grid">
                <?php foreach ($drinks as $drink): ?>
                    <?php
                    $pid       = (int) $drink['product_id'];
                    $name      = $drink['name'];
                    $price     = (float) $drink['price'];
                    $stock     = (int) $drink['stock_quantity'];
                    $inStock   = ($stock > 0);
                    $imageUrl  = product_image_url($drink['image']);
                    $detailUrl = site_url('customer/product.php?id=' . $pid);
                    $badges    = $drinkBadges[$pid] ?? ['Artisan Preparation'];
                    $isSig     = ($pid === 15 || $pid === 21);
                    ?>
                    <article class="drink-card <?php echo $isSig ? 'drink-card-signature' : ''; ?>" id="drink-<?php echo $pid; ?>">
                        <a href="<?php echo e($detailUrl); ?>" class="drink-card-media" aria-label="View details for <?php echo e($name); ?>">
                            <img
                                src="<?php echo e($imageUrl); ?>"
                                alt="<?php echo e($name); ?>"
                                width="600"
                                height="600"
                                loading="lazy"
                            >
                            <?php if ($isSig): ?>
                                <span class="drink-card-ribbon">Signature Drink</span>
                            <?php endif; ?>
                        </a>

                        <div class="drink-card-body">
                            <div class="drink-card-badges">
                                <?php foreach ($badges as $badge): ?>
                                    <span class="drink-badge-pill"><?php echo e($badge); ?></span>
                                <?php endforeach; ?>
                            </div>

                            <h2 class="drink-card-title">
                                <a href="<?php echo e($detailUrl); ?>">
                                    <?php echo e($name); ?>
                                </a>
                            </h2>

                            <p class="drink-card-desc">
                                <?php echo e($drink['description']); ?>
                            </p>

                            <div class="drink-card-price-row">
                                <span class="drink-card-price"><?php echo e(format_price($price)); ?></span>
                                <span class="drink-stock-label <?php echo $inStock ? 'in-stock' : 'out-of-stock'; ?>">
                                    <?php echo $inStock ? 'Available Fresh' : 'Currently Unavailable'; ?>
                                </span>
                            </div>

                            <!-- Individual Drink Add-to-Cart Form -->
                            <div class="drink-card-actions">
                                <?php if ($inStock): ?>
                                    <form action="<?php echo e(site_url('customer/cart.php')); ?>" method="post" class="drink-add-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                        <input type="hidden" name="return_url" value="<?php echo e(site_url('customer/cart.php')); ?>">

                                        <div class="drink-qty-row">
                                            <div class="drink-stepper">
                                                <button type="button" class="btn-step-minus" aria-label="Decrease quantity">&minus;</button>
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    value="1"
                                                    min="1"
                                                    max="<?php echo min(10, $stock); ?>"
                                                    class="drink-qty-field"
                                                    aria-label="Quantity for <?php echo e($name); ?>"
                                                >
                                                <button type="button" class="btn-step-plus" aria-label="Increase quantity">&plus;</button>
                                            </div>
                                            <button type="submit" class="btn btn-primary btn-drink-add">
                                                Add to Cart
                                            </button>
                                        </div>
                                    </form>
                                <?php else: ?>
                                    <button type="button" class="btn btn-outline btn-drink-disabled" disabled>
                                        Sold Out
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <span class="eyebrow">No Matching Beverages</span>
                <h2 class="empty-state-title">No drinks match your search.</h2>
                <p class="empty-state-text">
                    Try clearing your search term or exploring all studio drinks.
                </p>
                <div class="empty-state-actions">
                    <a href="<?php echo e(site_url('customer/drinks.php')); ?>" class="btn btn-primary">
                        View All Drinks
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- 4. Studio Retail & Take-Home Connection Section -->
<section class="section drinks-retail-bridge-section">
    <div class="container">
        <div class="drinks-bridge-card">
            <div class="drinks-bridge-content">
                <span class="eyebrow">Studio to Home Ritual</span>
                <h2 class="drinks-bridge-title">Love what you taste at our bar?</h2>
                <p class="drinks-bridge-text">
                    Every drink on our menu is prepared using the exact stone-ground ceremonial matcha tins, 
                    single-estate specialty loose leaves, and handcrafted stoneware ceramics available in our retail collection.
                </p>
                <div class="drinks-bridge-actions">
                    <a href="<?php echo e(site_url('customer/tea.php')); ?>" class="btn btn-primary">
                        Shop Packaged Teas &amp; Matcha
                    </a>
                    <a href="<?php echo e(site_url('customer/products.php?category=2')); ?>" class="btn btn-outline">
                        Explore Chawan Bowls &amp; Whisks
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Quantity steppers for drink cards
    var forms = document.querySelectorAll('.drink-add-form');
    forms.forEach(function (form) {
        var input = form.querySelector('.drink-qty-field');
        var btnMinus = form.querySelector('.btn-step-minus');
        var btnPlus = form.querySelector('.btn-step-plus');

        if (!input || !btnMinus || !btnPlus) return;

        btnMinus.addEventListener('click', function () {
            var val = parseInt(input.value, 10) || 1;
            var min = parseInt(input.min, 10) || 1;
            if (val > min) {
                input.value = val - 1;
            }
        });

        btnPlus.addEventListener('click', function () {
            var val = parseInt(input.value, 10) || 1;
            var max = parseInt(input.max, 10) || 10;
            if (val < max) {
                input.value = val + 1;
            }
        });
    });
});
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
