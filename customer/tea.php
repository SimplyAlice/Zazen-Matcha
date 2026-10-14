<?php
/**
 * Dedicated Tea Collection Page
 * Zazen Matcha & Tea Co.
 *
 * Dedicated destination showcasing Category 1 (Matcha) and
 * Category 4 (Specialty Tea) packaged products queried from MySQL.
 * Includes category pills, sorting, search, and working Add to Cart forms.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Artisan Tea & Matcha Collection';

// 1. Fetch categories 1 and 4 from MySQL
$teaCategoriesSql = "
    SELECT category_id, name, description
    FROM categories
    WHERE category_id IN (1, 4)
    ORDER BY category_id ASC
";
$teaCategories = db_fetch_all($pdo, $teaCategoriesSql);

// 2. Read and sanitize GET filter parameters
$selectedCategoryId = 0;
if (isset($_GET['category']) && ctype_digit((string) $_GET['category'])) {
    $rawCatId = (int) $_GET['category'];
    if (in_array($rawCatId, [1, 4], true)) {
        $selectedCategoryId = $rawCatId;
    }
}

$searchTerm = '';
if (isset($_GET['search'])) {
    $searchTerm = clean_input((string) $_GET['search']);
}

$sortOption = 'featured';
$allowedSorts = [
    'featured'   => 'p.category_id ASC, p.product_id ASC',
    'price_asc'  => 'p.price ASC, p.product_id ASC',
    'price_desc' => 'p.price DESC, p.product_id ASC',
    'name_asc'   => 'p.name ASC, p.product_id ASC',
];
if (isset($_GET['sort']) && !is_array($_GET['sort']) && isset($allowedSorts[(string) $_GET['sort']])) {
    $sortOption = (string) $_GET['sort'];
}
$orderBySql = $allowedSorts[$sortOption];

// 3. Count products in each category for pill badges
$countsSql = "
    SELECT category_id, COUNT(*) AS cnt
    FROM products
    WHERE status = 'active' AND category_id IN (1, 4)
    GROUP BY category_id
";
$countRows = db_fetch_all($pdo, $countsSql);
$catCounts = [1 => 0, 4 => 0];
$totalTeaCount = 0;
foreach ($countRows as $cRow) {
    $cId = (int) $cRow['category_id'];
    $cnt = (int) $cRow['cnt'];
    $catCounts[$cId] = $cnt;
    $totalTeaCount += $cnt;
}

// 4. Build prepared product query dynamically
$whereClauses = ['p.status = :status', 'p.category_id IN (1, 4)'];
$queryParams  = ['status' => 'active'];

if ($selectedCategoryId > 0) {
    $whereClauses[]             = 'p.category_id = :category_id';
    $queryParams['category_id'] = $selectedCategoryId;
}

if ($searchTerm !== '') {
    $whereClauses[]             = '(p.name LIKE :search_name OR p.description LIKE :search_desc)';
    $likePattern                = '%' . $searchTerm . '%';
    $queryParams['search_name'] = $likePattern;
    $queryParams['search_desc'] = $likePattern;
}

$whereSql = implode(' AND ', $whereClauses);

$productsSql = "
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

$products     = db_fetch_all($pdo, $productsSql, $queryParams);
$productCount = count($products);
$hasFilters   = ($selectedCategoryId > 0 || $searchTerm !== '' || $sortOption !== 'featured');

// Deterministic rating display map
$ratingMap = [
    1  => '4.9',
    2  => '4.8',
    7  => '4.9',
    8  => '4.8',
    9  => '5.0',
    10 => '4.8',
    11 => '4.9',
    12 => '5.0',
];

/**
 * Helper to build tea filter URLs
 */
function build_tea_filter_url(int $categoryId = 0, string $search = '', string $sort = 'featured'): string
{
    $params = [];
    if ($categoryId > 0) {
        $params['category'] = $categoryId;
    }
    if ($search !== '') {
        $params['search'] = $search;
    }
    if ($sort !== '' && $sort !== 'featured') {
        $params['sort'] = $sort;
    }

    $query = http_build_query($params);
    return site_url('customer/tea.php' . ($query !== '' ? '?' . $query : ''));
}

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Tea Page Header -->
<section class="shop-hero tea-page-hero" aria-labelledby="tea-heading">
    <div class="container">
        <div class="shop-hero-inner">
            <span class="eyebrow">Terroir &bull; Single-Origin Harvests &bull; Generational Craft</span>
            <h1 id="tea-heading" class="shop-title">The Artisan Tea &amp; Matcha Collection</h1>
            <p class="shop-subtitle">
                From stone-ground ceremonial tencha in Kyoto to high-mountain Formosa oolongs, cellar-aged Yunnan pu-erh, and botanical tisanes. Each leaf is cultivated for clarity, depth, and contemplative daily ritual.
            </p>
        </div>
    </div>
</section>

<!-- Terroir & Craft Highlights Strip -->
<section class="tea-terroir-strip" aria-label="Terroir and craftsmanship highlights">
    <div class="container">
        <div class="tea-terroir-grid">
            <div class="tea-terroir-item">
                <span class="terroir-num">01</span>
                <h3 class="terroir-title">Uji Terroir Matcha</h3>
                <p class="terroir-desc">Shade-grown first harvest tencha from Kyoto Prefecture, stone-ground into vibrant emerald powder.</p>
            </div>
            <div class="tea-terroir-item">
                <span class="terroir-num">02</span>
                <h3 class="terroir-title">Single-Origin Whites</h3>
                <p class="terroir-desc">Silvery Bai Mu Dan buds naturally sun-withered to yield a champagne liquor with wild floral notes.</p>
            </div>
            <div class="tea-terroir-item">
                <span class="terroir-num">03</span>
                <h3 class="terroir-title">High-Mountain Oolong</h3>
                <p class="terroir-desc">Semi-oxidized Formosa loose leaves scented with autumn osmanthus blossom for rich stonefruit aromatics.</p>
            </div>
            <div class="tea-terroir-item">
                <span class="terroir-num">04</span>
                <h3 class="terroir-title">Aged Pu-erh &amp; Botanicals</h3>
                <p class="terroir-desc">Cellar-fermented Shou vintage leaves and whole botanical blossoms blended with pure saffron threads.</p>
            </div>
        </div>
    </div>
</section>

<!-- Catalogue Filter & Search Controls -->
<section class="shop-controls-section" aria-label="Tea collection filters and search">
    <div class="container">
        <div class="shop-toolbar">
            <!-- Category Filter Pills -->
            <nav class="category-filter-nav" aria-label="Filter teas by category">
                <ul class="category-filter-list">
                    <li>
                        <a href="<?php echo e(build_tea_filter_url(0, $searchTerm, $sortOption)); ?>"
                           class="category-pill <?php echo $selectedCategoryId === 0 ? 'is-active' : ''; ?>"
                           <?php echo $selectedCategoryId === 0 ? 'aria-current="true"' : ''; ?>>
                            All Teas (<?php echo (int) $totalTeaCount; ?>)
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo e(build_tea_filter_url(1, $searchTerm, $sortOption)); ?>"
                           class="category-pill <?php echo $selectedCategoryId === 1 ? 'is-active' : ''; ?>"
                           <?php echo $selectedCategoryId === 1 ? 'aria-current="true"' : ''; ?>>
                            Ceremonial &amp; Daily Matcha (<?php echo (int) ($catCounts[1] ?? 0); ?>)
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo e(build_tea_filter_url(4, $searchTerm, $sortOption)); ?>"
                           class="category-pill <?php echo $selectedCategoryId === 4 ? 'is-active' : ''; ?>"
                           <?php echo $selectedCategoryId === 4 ? 'aria-current="true"' : ''; ?>>
                            Specialty Teas &amp; Botanicals (<?php echo (int) ($catCounts[4] ?? 0); ?>)
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- Sort & Search Form (GET) -->
            <form action="<?php echo e(site_url('customer/tea.php')); ?>" method="get" class="shop-search-form" role="search">
                <?php if ($selectedCategoryId > 0): ?>
                    <input type="hidden" name="category" value="<?php echo (int) $selectedCategoryId; ?>">
                <?php endif; ?>

                <div class="shop-sort-group">
                    <label for="tea-sort-select" class="shop-sort-label">Sort by:</label>
                    <select id="tea-sort-select" name="sort" class="shop-sort-select" onchange="this.form.submit()">
                        <option value="featured" <?php echo $sortOption === 'featured' ? 'selected' : ''; ?>>Featured</option>
                        <option value="price_asc" <?php echo $sortOption === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sortOption === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo $sortOption === 'name_asc' ? 'selected' : ''; ?>>Name: A&ndash;Z</option>
                    </select>
                </div>

                <label for="tea-search-input" class="visually-hidden">Search teas</label>
                <div class="search-input-group">
                    <span class="search-input-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="20" y1="20" x2="16.35" y2="16.35"></line>
                        </svg>
                    </span>
                    <input
                        type="search"
                        id="tea-search-input"
                        name="search"
                        value="<?php echo e($searchTerm); ?>"
                        placeholder="Search teas &amp; botanicals..."
                        class="shop-search-input"
                    >
                    <button type="submit" class="btn btn-primary shop-search-btn">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <?php if ($hasFilters): ?>
            <div class="shop-meta-bar">
                <p class="shop-result-count" aria-live="polite">
                    Showing <strong><?php echo (int) $productCount; ?></strong>
                    <?php echo $productCount === 1 ? 'tea selection' : 'tea selections'; ?>
                    <?php if ($selectedCategoryId === 1): ?>
                        in <span>Matcha Powders</span>
                    <?php elseif ($selectedCategoryId === 4): ?>
                        in <span>Specialty Teas &amp; Botanicals</span>
                    <?php endif; ?>
                    <?php if ($searchTerm !== ''): ?>
                        matching &ldquo;<span><?php echo e($searchTerm); ?></span>&rdquo;
                    <?php endif; ?>
                </p>

                <a href="<?php echo e(site_url('customer/tea.php')); ?>" class="clear-filters-link">
                    Clear Filters &times;
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Product Catalogue Grid -->
<section class="section shop-catalogue-section" aria-label="Tea catalogue listing">
    <div class="container">
        <?php if (!empty($products)): ?>
            <div class="shop-product-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $productId   = (int) $product['product_id'];
                    $productUrl  = site_url('customer/product.php?id=' . $productId);
                    $imageUrl    = product_image_url($product['image']);
                    $stockQty    = (int) $product['stock_quantity'];
                    $isInStock   = ($stockQty > 0);
                    $isLowStock  = ($stockQty > 0 && $stockQty <= 10);
                    $ratingValue = $ratingMap[$productId] ?? '4.9';
                    ?>
                    <article class="product-card shop-product-card">
                        <a href="<?php echo e($productUrl); ?>" class="product-card-media" aria-label="View <?php echo e($product['name']); ?>">
                            <img
                                src="<?php echo e($imageUrl); ?>"
                                alt="<?php echo e($product['name']); ?>"
                                width="1000"
                                height="1000"
                                loading="lazy"
                            >
                        </a>

                        <div class="product-card-body">
                            <div class="shop-card-header-row">
                                <h2 class="product-card-title">
                                    <a href="<?php echo e($productUrl); ?>">
                                        <?php echo e($product['name']); ?>
                                    </a>
                                </h2>
                                <span class="shop-card-rating" aria-label="Rated <?php echo e($ratingValue); ?> out of 5 stars">
                                    <span class="star-glyph" aria-hidden="true">&#9733;</span> <?php echo e($ratingValue); ?>
                                </span>
                            </div>

                            <p class="product-card-desc">
                                <?php echo e($product['description']); ?>
                            </p>

                            <div class="product-card-meta">
                                <span class="product-card-price"><?php echo e(format_price($product['price'])); ?></span>
                                <?php if ($isLowStock): ?>
                                    <span class="stock-badge stock-low">Low Stock (<?php echo $stockQty; ?> left)</span>
                                <?php elseif ($isInStock): ?>
                                    <span class="stock-badge stock-in">In Stock</span>
                                <?php else: ?>
                                    <span class="stock-badge stock-out">Out of Stock</span>
                                <?php endif; ?>
                            </div>

                            <div class="product-card-footer">
                                <?php if ($isInStock): ?>
                                    <form action="<?php echo e($productUrl); ?>" method="post" class="card-quick-add-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo e(csrf_token()); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="return_url" value="<?php echo e($_SERVER['REQUEST_URI'] ?? site_url('customer/tea.php')); ?>">
                                        <button type="submit" class="btn btn-primary btn-card-full" aria-label="Add <?php echo e($product['name']); ?> to cart">
                                            Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo e($productUrl); ?>" class="btn btn-outline btn-card-full">
                                        View Details
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Bottom Count & Quick Pagination -->
            <div class="shop-pagination-bar">
                <p class="shop-result-count" aria-live="polite">
                    Showing 1&ndash;<?php echo (int) $productCount; ?> of <?php echo (int) $productCount; ?>
                    <?php echo $productCount === 1 ? 'tea' : 'teas'; ?>
                </p>

                <nav class="shop-pagination" aria-label="Pagination">
                    <span class="pagination-pill is-active" aria-current="page">1</span>
                    <a href="<?php echo e(build_tea_filter_url($selectedCategoryId, $searchTerm, $sortOption)); ?>" class="pagination-pill" aria-label="Next page">&rarr;</a>
                </nav>
            </div>
        <?php else: ?>
            <div class="empty-state shop-empty-state">
                <span class="eyebrow">No Matching Leaves</span>
                <h2 class="empty-state-title">No teas matched your current selection.</h2>
                <p class="empty-state-text">
                    Try refining your search keyword or clearing the active category filters to view our full collection.
                </p>
                <a href="<?php echo e(site_url('customer/tea.php')); ?>" class="btn btn-primary">
                    View All Teas
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Studio Tea Bar Cross-Link Banner -->
<section class="section tea-studio-banner-section" aria-labelledby="studio-banner-heading">
    <div class="container">
        <div class="tea-studio-banner">
            <div class="tea-studio-banner-content">
                <span class="eyebrow">Cape Town Tea Studio &bull; Fresh Preparations</span>
                <h2 id="studio-banner-heading" class="tea-studio-banner-title">
                    Taste every harvest fresh at our Kloof Street Tea Bar
                </h2>
                <p class="tea-studio-banner-text">
                    All packaged teas and ceremonial matcha powders showcased above are served daily at our tea bar. Experience 12-hour cold-brewed jasmine pearls, velvety charcoal-roasted hojicha lattes, and hand-whisked ceremonial usucha prepared by our studio team.
                </p>
                <div class="tea-studio-banner-actions">
                    <a href="<?php echo e(site_url('index.php#tea-bar')); ?>" class="btn btn-primary btn-pill">
                        Explore Tea Bar Drinks Menu &rarr;
                    </a>
                    <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-outline btn-pill">
                        Browse Teaware &amp; Accessories &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
