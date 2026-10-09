<?php
/**
 * Customer Shop / Product Catalogue Page
 * Zazen Matcha & Tea Co.
 *
 * Displays active products from MySQL with dynamic category filtering
 * and keyword search using PDO prepared statements.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Shop Collection';

// 1. Load all categories dynamically from MySQL for the filter bar
$categoriesSql = "
    SELECT category_id, name, description
    FROM categories
    ORDER BY category_id ASC
";
$categories = db_fetch_all($pdo, $categoriesSql);

// 2. Read and sanitise GET filter parameters
$selectedCategoryId = 0;
if (isset($_GET['category']) && ctype_digit((string) $_GET['category'])) {
    $selectedCategoryId = (int) $_GET['category'];
}

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
if (isset($_GET['sort']) && !is_array($_GET['sort']) && isset($allowedSorts[(string) $_GET['sort']])) {
    $sortOption = (string) $_GET['sort'];
}
$orderBySql = $allowedSorts[$sortOption];

// Identify the selected category record (if a valid category ID was requested)
$activeCategory = null;
foreach ($categories as $cat) {
    if ((int) $cat['category_id'] === $selectedCategoryId) {
        $activeCategory = $cat;
        break;
    }
}

// 3. Build the prepared product query dynamically based on active filters
$whereClauses = ['p.status = :status'];
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

/**
 * Helper to build filter URLs while preserving or updating query parameters.
 *
 * @param int    $categoryId
 * @param string $search
 * @param string $sort
 * @return string
 */
function build_shop_filter_url(int $categoryId = 0, string $search = '', string $sort = 'featured'): string
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
    return site_url('customer/products.php' . ($query !== '' ? '?' . $query : ''));
}

// Deterministic rating display map for prototype visual fidelity
$ratingMap = [
    1 => '4.9',
    2 => '4.8',
    3 => '4.9',
    4 => '4.8',
    5 => '4.7',
    6 => '5.0',
];

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- Shop Page Header -->
<section class="shop-hero" aria-labelledby="shop-heading">
    <div class="container">
        <div class="shop-hero-inner">
            <h1 id="shop-heading" class="shop-title">
                <?php if ($activeCategory !== null): ?>
                    <?php echo e($activeCategory['name']); ?>
                <?php else: ?>
                    The Shop
                <?php endif; ?>
            </h1>
            <p class="shop-subtitle">
                <?php if ($activeCategory !== null && !empty($activeCategory['description'])): ?>
                    <?php echo e($activeCategory['description']); ?>
                <?php else: ?>
                    Explore our curated collection of single-origin matcha, handcrafted starter kits, and traditional Japanese teaware.
                <?php endif; ?>
            </p>
        </div>
    </div>
</section>

<!-- Catalogue Filter & Search Controls -->
<section class="shop-controls-section" aria-label="Catalogue filters and search">
    <div class="container">
        <div class="shop-toolbar">
            <!-- Dynamic Category Filter Navigation -->
            <nav class="category-filter-nav" aria-label="Filter products by category">
                <ul class="category-filter-list">
                    <li>
                        <a href="<?php echo e(build_shop_filter_url(0, $searchTerm, $sortOption)); ?>"
                           class="category-pill <?php echo $selectedCategoryId === 0 ? 'is-active' : ''; ?>"
                           <?php echo $selectedCategoryId === 0 ? 'aria-current="true"' : ''; ?>>
                            All Products
                        </a>
                    </li>
                    <?php foreach ($categories as $category): ?>
                        <?php
                        $catId      = (int) $category['category_id'];
                        $isSelected = ($selectedCategoryId === $catId);
                        ?>
                        <li>
                            <a href="<?php echo e(build_shop_filter_url($catId, $searchTerm, $sortOption)); ?>"
                               class="category-pill <?php echo $isSelected ? 'is-active' : ''; ?>"
                               <?php echo $isSelected ? 'aria-current="true"' : ''; ?>>
                                <?php echo e($category['name']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Sort & Search Form (GET) -->
            <form action="<?php echo e(site_url('customer/products.php')); ?>" method="get" class="shop-search-form" role="search">
                <?php if ($selectedCategoryId > 0): ?>
                    <input type="hidden" name="category" value="<?php echo (int) $selectedCategoryId; ?>">
                <?php endif; ?>

                <div class="shop-sort-group">
                    <label for="shop-sort-select" class="shop-sort-label">Sort by:</label>
                    <select id="shop-sort-select" name="sort" class="shop-sort-select" onchange="this.form.submit()">
                        <option value="featured" <?php echo $sortOption === 'featured' ? 'selected' : ''; ?>>Featured</option>
                        <option value="price_asc" <?php echo $sortOption === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo $sortOption === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="name_asc" <?php echo $sortOption === 'name_asc' ? 'selected' : ''; ?>>Name: A&ndash;Z</option>
                    </select>
                </div>

                <label for="shop-search-input" class="visually-hidden">Search products</label>
                <div class="search-input-group">
                    <span class="search-input-icon" aria-hidden="true">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"></circle>
                            <line x1="20" y1="20" x2="16.35" y2="16.35"></line>
                        </svg>
                    </span>
                    <input
                        type="search"
                        id="shop-search-input"
                        name="search"
                        value="<?php echo e($searchTerm); ?>"
                        placeholder="Search products..."
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
                    <?php echo $productCount === 1 ? 'product' : 'products'; ?>
                    <?php if ($activeCategory !== null): ?>
                        in <span><?php echo e($activeCategory['name']); ?></span>
                    <?php endif; ?>
                    <?php if ($searchTerm !== ''): ?>
                        matching &ldquo;<span><?php echo e($searchTerm); ?></span>&rdquo;
                    <?php endif; ?>
                </p>

                <a href="<?php echo e(site_url('customer/products.php')); ?>" class="clear-filters-link">
                    Clear Filters &times;
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Product Catalogue Grid -->
<section class="section shop-catalogue-section" aria-label="Product listing">
    <div class="container">
        <?php if (!empty($products)): ?>
            <div class="shop-product-grid">
                <?php foreach ($products as $idx => $product): ?>
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
                                        <button type="submit" class="btn btn-primary btn-card-full">
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

            <!-- Prototype Bottom Count & Pagination Bar -->
            <div class="shop-pagination-bar">
                <p class="shop-result-count" aria-live="polite">
                    Showing 1&ndash;<?php echo (int) $productCount; ?> of <?php echo (int) $productCount; ?>
                    <?php echo $productCount === 1 ? 'product' : 'products'; ?>
                </p>

                <nav class="shop-pagination" aria-label="Pagination">
                    <span class="pagination-pill is-active" aria-current="page">1</span>
                    <a href="<?php echo e(build_shop_filter_url($selectedCategoryId, $searchTerm, $sortOption)); ?>" class="pagination-pill" aria-label="Next page">&rarr;</a>
                </nav>
            </div>
        <?php else: ?>
            <div class="empty-state shop-empty-state">
                <span class="eyebrow">No Matching Objects</span>
                <h2 class="empty-state-title">No products matched your current selection.</h2>
                <p class="empty-state-text">
                    <?php if ($searchTerm !== '' && $activeCategory !== null): ?>
                        We could not find any items in <strong><?php echo e($activeCategory['name']); ?></strong> matching &ldquo;<strong><?php echo e($searchTerm); ?></strong>&rdquo;.
                    <?php elseif ($searchTerm !== ''): ?>
                        We could not find any items matching &ldquo;<strong><?php echo e($searchTerm); ?></strong>&rdquo;.
                    <?php else: ?>
                        There are currently no active products in this category.
                    <?php endif; ?>
                </p>
                <div class="empty-state-actions">
                    <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-outline">
                        Clear All Filters
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
