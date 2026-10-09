<?php
/**
 * Customer Homepage
 * Zazen Matcha & Tea Co.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Mindful Matcha. Every Day.';

// Fetch up to 4 active featured products along with their category names
$featuredSql = "
    SELECT
        p.product_id,
        p.name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        c.name AS category_name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.category_id
    WHERE p.status = :status
    ORDER BY p.product_id ASC
    LIMIT 4
";

$featuredProducts = db_fetch_all($pdo, $featuredSql, ['status' => 'active']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Section -->
<section class="hero-section" aria-labelledby="hero-heading">
    <div class="container">
        <div class="hero-banner">
            <img
                src="<?php echo e(site_url('images/hero-matcha.jpg')); ?>"
                alt="Traditional bamboo matcha whisk preparing ceremonial green tea in a ceramic chawan bowl"
                class="hero-banner-bg"
                width="1376"
                height="768"
                fetchpriority="high"
            >
            <div class="hero-banner-overlay"></div>

            <div class="hero-content">
                <h1 id="hero-heading" class="hero-title">
                    Mindful Matcha.<br>Every Day.
                </h1>
                <p class="hero-lead">
                    Experience the calming focus of high-grade, single-origin Japanese matcha sourced with intention and crafted for modern ritual.
                </p>
                <div class="hero-actions">
                    <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-primary hero-btn-primary">
                        Shop Matcha
                    </a>
                    <a href="<?php echo e(site_url('customer/about.php')); ?>" class="btn btn-outline-light hero-btn-secondary">
                        Explore Our Story
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Why Zazen Matcha? Section -->
<section class="section why-zazen-section" aria-labelledby="why-zazen-heading">
    <div class="container">
        <h2 id="why-zazen-heading" class="section-title-center">Why Zazen Matcha?</h2>

        <div class="why-zazen-grid">
            <!-- Feature 1: 100% Ceremonial Grade -->
            <article class="why-zazen-card">
                <div class="why-zazen-icon" aria-hidden="true">
                    <svg width="46" height="46" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M24 40V18"></path>
                        <path d="M24 28C15 28 10 21 10 12C19 12 24 18 24 28Z"></path>
                        <path d="M24 24C32 24 38 17 38 9C29 9 24 15 24 24Z"></path>
                        <path d="M16 18L24 26"></path>
                        <path d="M32 15L24 22"></path>
                    </svg>
                </div>
                <h3 class="why-zazen-title">100% Ceremonial Grade</h3>
                <p class="why-zazen-desc">
                    Natural shade-grown first-harvest leaves from Uji, Japan, stone-ground into an ultra-fine emerald powder.
                </p>
            </article>

            <!-- Feature 2: Mindfully Sourced -->
            <article class="why-zazen-card">
                <div class="why-zazen-icon" aria-hidden="true">
                    <svg width="46" height="46" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 30H28L27 41H21L20 30Z"></path>
                        <line x1="19" y1="30" x2="29" y2="30"></line>
                        <line x1="20" y1="34" x2="28" y2="34"></line>
                        <path d="M20 30L14 13C18 9 30 9 34 13L28 30"></path>
                        <line x1="22" y1="30" x2="18" y2="11"></line>
                        <line x1="24" y1="30" x2="24" y2="10"></line>
                        <line x1="26" y1="30" x2="30" y2="11"></line>
                    </svg>
                </div>
                <h3 class="why-zazen-title">Mindfully Sourced</h3>
                <p class="why-zazen-desc">
                    Crafted in small batches with heritage tea cultivars and traditional Japanese artisan preparation tools.
                </p>
            </article>

            <!-- Feature 3: Calm, Sustained Energy -->
            <article class="why-zazen-card">
                <div class="why-zazen-icon" aria-hidden="true">
                    <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                        <path d="M35.5 32.5C32.8 36.4 28.2 38.8 23 38.5C15 38 9 31.2 9.5 23C10 14.8 17 8.8 25 9.5C32.8 10.2 38.5 16.8 38 24.5C37.8 27 37 29.2 35.8 31" stroke="currentColor" stroke-width="3.2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <h3 class="why-zazen-title">Calm, Sustained Energy</h3>
                <p class="why-zazen-desc">
                    Balanced L-theanine and gentle botanical energy for hours of calm, sustained mental clarity and focus.
                </p>
            </article>
        </div>
    </div>
</section>

<!-- 3. Shop Our Favorites Section -->
<section class="section featured-section" aria-labelledby="featured-heading">
    <div class="container">
        <h2 id="featured-heading" class="section-title-center">Shop Our Favorites</h2>

        <!-- Prototype 4-Pillar Icon Strip -->
        <div class="favorites-icon-strip" aria-hidden="true">
            <div class="favorites-icon-tile">
                <svg width="32" height="32" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 36C14 22 24 12 38 10C36 24 26 34 12 36Z"></path>
                    <path d="M12 36L28 20"></path>
                </svg>
            </div>
            <div class="favorites-icon-tile">
                <svg width="32" height="32" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 32C14 22 20 18 24 18C28 18 34 22 38 32H10Z" fill="rgba(63, 90, 64, 0.25)"></path>
                    <ellipse cx="24" cy="32" rx="16" ry="4"></ellipse>
                </svg>
            </div>
            <div class="favorites-icon-tile">
                <svg width="32" height="32" viewBox="0 0 48 48" fill="none">
                    <path d="M34 31C31.5 34.5 27.5 36.5 23 36.2C16 35.8 11 30 11.5 23C12 16 18 11 25 11.5C31.8 12 36.8 17.8 36.2 24.5" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
            </div>
            <div class="favorites-icon-tile">
                <svg width="32" height="32" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 29H27L26 38H22L21 29Z"></path>
                    <path d="M21 29L16 14C19 11 29 11 32 14L27 29"></path>
                    <line x1="24" y1="29" x2="24" y2="12"></line>
                </svg>
            </div>
        </div>

        <?php if (!empty($featuredProducts)): ?>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $idx => $product): ?>
                    <?php
                    $productId  = (int) $product['product_id'];
                    $productUrl = site_url('customer/product.php?id=' . $productId);
                    $imageUrl   = product_image_url($product['image']);
                    $inStock    = ((int) $product['stock_quantity'] > 0);
                    ?>
                    <article class="product-card home-product-card">
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
                            <h3 class="product-card-title">
                                <a href="<?php echo e($productUrl); ?>">
                                    <?php echo e($product['name']); ?>
                                </a>
                            </h3>
                            <div class="product-card-stars" aria-label="Rated 5 out of 5 stars">
                                &#9733;&#9733;&#9733;&#9733;&#9733;
                            </div>
                            <div class="product-card-price"><?php echo e(format_price($product['price'])); ?></div>

                            <div class="product-card-actions">
                                <?php if ($inStock && $idx < 3): ?>
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
                                        View Product
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No featured products are currently available in the catalogue.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 4. Product Categories Section -->
<section class="section home-categories-section" aria-labelledby="home-categories-heading">
    <div class="container">
        <h2 id="home-categories-heading" class="section-title-center">Product Categories</h2>

        <div class="category-showcase-grid">
            <a href="<?php echo e(site_url('customer/products.php?category=1')); ?>" class="category-showcase-card">
                <img
                    src="<?php echo e(site_url('images/categories/category-matcha-tea.jpg')); ?>"
                    alt="Matcha Tea — Overhead view of whisked ceremonial matcha bowl, green tea powder, and pouring vessel"
                    width="1000"
                    height="750"
                    loading="lazy"
                >
                <span class="category-showcase-overlay"></span>
                <span class="category-showcase-label">Matcha Tea</span>
            </a>

            <a href="<?php echo e(site_url('customer/products.php?category=3')); ?>" class="category-showcase-card">
                <img
                    src="<?php echo e(site_url('images/categories/category-kits-sets.jpg')); ?>"
                    alt="Kits &amp; Starter Sets — Curated matcha starter box, brushed-silver tin, bamboo whisk, and scoop"
                    width="1000"
                    height="750"
                    loading="lazy"
                >
                <span class="category-showcase-overlay"></span>
                <span class="category-showcase-label">Kits &amp; Starter Sets</span>
            </a>

            <a href="<?php echo e(site_url('customer/products.php?category=2')); ?>" class="category-showcase-card">
                <img
                    src="<?php echo e(site_url('images/categories/category-ware-accessories.jpg')); ?>"
                    alt="Ware &amp; Accessories — Handcrafted Japanese stoneware tea bowls and accessories"
                    width="1000"
                    height="750"
                    loading="lazy"
                >
                <span class="category-showcase-overlay"></span>
                <span class="category-showcase-label">Ware &amp; Accessories</span>
            </a>
        </div>
    </div>
</section>

<!-- 5. From Uji to Your Cup Section -->
<section class="section ritual-section uji-story-section" aria-labelledby="ritual-heading">
    <div class="container ritual-grid">
        <figure class="ritual-visual">
            <div class="ritual-image-frame uji-offset-frame">
                <img
                    src="<?php echo e(site_url('images/ritual-preparation.jpg')); ?>"
                    alt="Lush terraced green tea fields in the misty hillsides of Uji, Japan"
                    width="1200"
                    height="896"
                    loading="lazy"
                >
            </div>
        </figure>

        <div class="ritual-content">
            <h2 id="ritual-heading" class="ritual-title">From Uji to Your Cup</h2>
            <p class="ritual-intro">
                Every tin of Zazen Matcha begins in the misty hillside tea gardens of Uji, Japan—where generations of growers shade-cultivate tencha leaves to deepen their natural sweetness and vibrant chlorophyll.
            </p>
            <p class="ritual-body-text">
                Harvested once a year in early spring, the leaves are steamed, air-dried, and slowly stone-milled into a silk-fine powder designed for contemporary home and studio ritual.
            </p>
            <p class="ritual-body-text">
                Paired with our wide-base ceramic chawan bowls and hand-split bamboo whisks, each cup invites a quiet pause between tasks.
            </p>
            <a href="<?php echo e(site_url('customer/about.php')); ?>" class="editorial-link">
                Read Our Full Story &rarr;
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
