<?php
/**
 * Customer Homepage
 * Zazen Matcha & Tea Co.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Quiet Ritual, Modern Craft';

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
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="eyebrow">Zazen Matcha &amp; Tea Co. &mdash; Collection 01</span>
            <h1 id="hero-heading" class="hero-title">Quiet Ritual, Modern Craft.</h1>
            <p class="hero-lead">
                Finely milled matcha powders and tactile ceramic vessels designed to bring a measured pause to the contemporary table.
            </p>
            <div class="hero-actions">
                <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-primary">
                    Explore the Collection
                </a>
                <a href="<?php echo e(site_url('customer/about.php')); ?>" class="btn btn-outline">
                    Our Philosophy
                </a>
            </div>

            <dl class="hero-pillars" aria-label="Collection highlights">
                <div class="hero-pillar-item">
                    <dt>01 / Powders</dt>
                    <dd>Ceremonial &amp; everyday blends</dd>
                </div>
                <div class="hero-pillar-item">
                    <dt>02 / Vessels</dt>
                    <dd>Wide-base bowls &amp; whisks</dd>
                </div>
                <div class="hero-pillar-item">
                    <dt>03 / Curated Sets</dt>
                    <dd>Complete preparation bundles</dd>
                </div>
            </dl>
        </div>

        <figure class="hero-visual">
            <div class="hero-image-frame">
                <img
                    src="<?php echo e(site_url('images/hero-matcha.jpg')); ?>"
                    alt="Zazen Matcha tin alongside a dark wide-base ceramic tea bowl"
                    width="1000"
                    height="1200"
                >
            </div>
            <figcaption class="editorial-caption">
                <span>Fig. 01 &mdash; Ceremonial Tin &amp; Wide-Base Chawan</span>
                <span>Cape Town Studio Prototype</span>
            </figcaption>
        </figure>
    </div>
</section>

<!-- 2. Brand Introduction Section -->
<section class="section brand-intro-section" aria-labelledby="brand-intro-heading">
    <div class="container">
        <div class="brand-intro-grid">
            <div class="brand-intro-header">
                <span class="eyebrow">01 / The Zazen Concept</span>
                <h2 id="brand-intro-heading" class="brand-intro-title">
                    Built Around the Pause Between Tasks.
                </h2>
            </div>
            <div class="brand-intro-body">
                <p class="brand-intro-lead">
                    Zazen Matcha &amp; Tea Co. was conceived around a simple observation: the objects we use every morning shape the pace of the hours that follow.
                </p>
                <p>
                    Rather than treating tea as a hurried convenience, our collection brings together finely milled green tea powders and balanced preparation tools—ceramic bowls, multi-prong whisks, and measured scoops—crafted for a calm, repeatable five-minute ritual at home or in the studio.
                </p>
                <a href="<?php echo e(site_url('customer/about.php')); ?>" class="editorial-link">
                    Read Our Full Philosophy &rarr;
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 3. Featured Products Section -->
<section class="section featured-section" aria-labelledby="featured-heading">
    <div class="container">
        <div class="section-header">
            <div>
                <span class="eyebrow">02 / Selected Objects</span>
                <h2 id="featured-heading">Featured Collection</h2>
            </div>
            <a href="<?php echo e(site_url('customer/products.php')); ?>" class="editorial-link">
                View Full Catalogue &rarr;
            </a>
        </div>

        <?php if (!empty($featuredProducts)): ?>
            <div class="product-grid">
                <?php foreach ($featuredProducts as $product): ?>
                    <?php
                    $productUrl = site_url('customer/product.php?id=' . (int) $product['product_id']);
                    $imageUrl   = product_image_url($product['image']);
                    ?>
                    <article class="product-card">
                        <a href="<?php echo e($productUrl); ?>" class="product-card-media" aria-label="View <?php echo e($product['name']); ?>">
                            <img
                                src="<?php echo e($imageUrl); ?>"
                                alt="<?php echo e($product['name']); ?>"
                                width="900"
                                height="1100"
                                loading="lazy"
                            >
                        </a>
                        <div class="product-card-body">
                            <span class="product-card-category"><?php echo e($product['category_name']); ?></span>
                            <h3 class="product-card-title">
                                <a href="<?php echo e($productUrl); ?>">
                                    <?php echo e($product['name']); ?>
                                </a>
                            </h3>
                            <p class="product-card-desc">
                                <?php echo e($product['description']); ?>
                            </p>
                            <div class="product-card-footer">
                                <span class="product-card-price"><?php echo e(format_price($product['price'])); ?></span>
                                <a href="<?php echo e($productUrl); ?>" class="product-card-link">
                                    View Details
                                </a>
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

<!-- 4. Brand / Value / Ritual Section -->
<section class="section ritual-section" aria-labelledby="ritual-heading">
    <div class="container ritual-grid">
        <figure class="ritual-visual">
            <div class="ritual-image-frame">
                <img
                    src="<?php echo e(site_url('images/ritual-preparation.jpg')); ?>"
                    alt="Wide-base ceramic matcha bowl prepared with whisked green tea"
                    width="900"
                    height="1050"
                    loading="lazy"
                >
            </div>
            <figcaption class="editorial-caption">
                <span>Fig. 02 &mdash; The Three Elements of Preparation</span>
                <span>Measure &bull; Whisk &bull; Vessel</span>
            </figcaption>
        </figure>

        <div class="ritual-content">
            <span class="eyebrow">03 / The Daily Ritual</span>
            <h2 id="ritual-heading" class="ritual-title">Three Deliberate Gestures.</h2>
            <p class="ritual-intro">
                Preparing matcha by hand replaces haste with tactile attention. Each item in the Zazen Matcha collection is proportioned to work in quiet harmony.
            </p>

            <ol class="ritual-steps">
                <li class="ritual-step">
                    <span class="ritual-step-num">I</span>
                    <div>
                        <h3 class="ritual-step-title">The Measure</h3>
                        <p>Sift a measured portion of fine matcha powder into a warmed bowl to create a smooth, uniform foundation.</p>
                    </div>
                </li>
                <li class="ritual-step">
                    <span class="ritual-step-num">II</span>
                    <div>
                        <h3 class="ritual-step-title">The Whisk</h3>
                        <p>Add warm water and whisk briskly in a linear motion until a fine, jade-toned suspension settles at the surface.</p>
                    </div>
                </li>
                <li class="ritual-step">
                    <span class="ritual-step-num">III</span>
                    <div>
                        <h3 class="ritual-step-title">The Vessel</h3>
                        <p>Cradle the wide-base ceramic bowl with both hands—appreciating the weight, warmth, and unhurried stillness of the moment.</p>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</section>

<!-- 5. Call-to-Action Section -->
<section class="section cta-section" aria-labelledby="cta-heading">
    <div class="container">
        <div class="cta-panel">
            <div class="cta-panel-content">
                <span class="eyebrow cta-eyebrow">Begin Your Ritual</span>
                <h2 id="cta-heading" class="cta-title">Assemble Your Daily Tea Table.</h2>
                <p class="cta-text">
                    Explore individual matcha tins, standalone ceramic bowls, multi-prong whisks, or curated starter sets prepared for everyday home ritual.
                </p>
            </div>
            <div class="cta-panel-actions">
                <a href="<?php echo e(site_url('customer/products.php')); ?>" class="btn btn-light">
                    Shop the Collection
                </a>
                <a href="<?php echo e(site_url('customer/contact.php')); ?>" class="btn btn-outline-light">
                    Speak With Our Studio
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
