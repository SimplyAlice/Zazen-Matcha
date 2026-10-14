<?php
/**
 * Customer About / Philosophy Page
 * Zazen Matcha & Tea Co.
 *
 * Editorial brand philosophy page introducing Zazen Matcha's approach to
 * intentional preparation, daily tea ritual, and thoughtful craft.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Our Philosophy';
$shopUrl   = site_url('customer/products.php');

require_once dirname(__DIR__) . '/includes/header.php';
?>

<!-- 1. Hero Section -->
<section class="hero-section about-hero-section" aria-labelledby="about-hero-heading">
    <div class="container hero-grid about-hero-grid">
        <div class="hero-content about-hero-content">
            <div class="about-eyebrow-wrap">
                <span class="eyebrow about-eyebrow">
                    <span class="eyebrow-accent">Vol. 01</span>
                    <span class="eyebrow-sep">/</span>
                    Philosophy of Quietude
                </span>
            </div>
            <h1 id="about-hero-heading" class="hero-title about-hero-title">A Ritual Worth Slowing Down For.</h1>
            <p class="hero-lead about-hero-lead">
                Zazen Matcha was created around a quiet conviction: that preparing a bowl of matcha is not merely a way to start the morning, but a deliberate pause within an otherwise hurried day.
            </p>
            <div class="hero-actions about-hero-actions">
                <a href="<?php echo e($shopUrl); ?>" class="btn btn-primary">
                    Shop Matcha
                </a>
                <a href="#our-approach" class="btn btn-outline">
                    Read Our Approach
                </a>
            </div>

            <dl class="hero-pillars about-hero-pillars" aria-label="Brand ethos">
                <div class="hero-pillar-item about-pillar-item">
                    <dt>01 / Preparation</dt>
                    <dd>Measured by hand, never rushed</dd>
                </div>
                <div class="hero-pillar-item about-pillar-item">
                    <dt>02 / Objects</dt>
                    <dd>Tactile vessels &amp; balanced tools</dd>
                </div>
                <div class="hero-pillar-item about-pillar-item">
                    <dt>03 / Rhythm</dt>
                    <dd>A quiet five-minute daily pause</dd>
                </div>
            </dl>
        </div>

        <figure class="hero-visual about-hero-visual">
            <div class="about-visual-frame-wrapper">
                <div class="hero-image-frame about-image-frame">
                    <img
                        src="<?php echo e(site_url('images/about-tea-ritual.jpg')); ?>"
                        alt="Authentic Japanese tea ceremony with handcrafted ceramic chawan bowl and fresh matcha on tatami"
                        width="1800"
                        height="1200"
                        fetchpriority="high"
                    >
                </div>
            </div>
            <figcaption class="editorial-caption about-editorial-caption">
                <span class="caption-figure">Fig. 01 &mdash; The Mindful Presentation &amp; Tatami Ritual</span>
                <span class="caption-provenance">Kobori Enshu Ryu &bull; Zazen Archive</span>
            </figcaption>
        </figure>
    </div>
</section>

<!-- 2. Our Approach Section -->
<section id="our-approach" class="section brand-intro-section about-approach-section" aria-labelledby="approach-heading">
    <div class="container">
        <div class="brand-intro-grid">
            <div class="brand-intro-header">
                <span class="eyebrow">01 / Our Approach</span>
                <h2 id="approach-heading" class="brand-intro-title">
                    Built Around the Pause.
                </h2>
            </div>

            <div class="brand-intro-body about-approach-body">
                <p class="brand-intro-lead">
                    Most modern routines are designed for speed. We wanted to build something for the five minutes when you choose to slow down instead.
                </p>
                <p>
                    At Zazen Matcha &amp; Tea Co., we approach matcha as a tactile, everyday practice. Whether prepared warm in a wide ceramic bowl at dawn or poured over ice in the middle of a focused afternoon, the act of measuring, whisking, and pausing creates a clear boundary between one task and the next.
                </p>
                <p>
                    Our collection brings together finely milled single-origin matcha, artisan loose-leaf specialty teas, and simple, well-proportioned tools—ceramic chawan bowls, bamboo whisks, and curated gift sets—designed to sit naturally on the contemporary South African table.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 3. The Ritual Section -->
<section class="section ritual-section about-ritual-section" aria-labelledby="about-ritual-heading">
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
                <span>Fig. 02 &mdash; Powder, Water, and Motion</span>
                <span>Measure &bull; Whisk &bull; Pause</span>
            </figcaption>
        </figure>

        <div class="ritual-content">
            <span class="eyebrow">02 / The Ritual</span>
            <h2 id="about-ritual-heading" class="ritual-title">Three Deliberate Gestures.</h2>
            <p class="ritual-intro">
                Matcha rewards unhurried attention. With a few simple tools, preparation becomes a calm, repeatable rhythm rather than a chore.
            </p>

            <ol class="ritual-steps">
                <li class="ritual-step">
                    <span class="ritual-step-num">01</span>
                    <div>
                        <h3 class="ritual-step-title">Measure</h3>
                        <p>
                            A considered amount, prepared with intention. Sifting fine green tea powder into a warmed bowl sets a calm, steady pace before water ever meets the vessel.
                        </p>
                    </div>
                </li>
                <li class="ritual-step">
                    <span class="ritual-step-num">02</span>
                    <div>
                        <h3 class="ritual-step-title">Whisk</h3>
                        <p>
                            A few deliberate movements transform powder and warm water into something alive—a smooth, jade-toned suspension with a fine, even surface.
                        </p>
                    </div>
                </li>
                <li class="ritual-step">
                    <span class="ritual-step-num">03</span>
                    <div>
                        <h3 class="ritual-step-title">Pause</h3>
                        <p>
                            The final step is simply to enjoy it. Cradling the wide-base ceramic bowl with both hands invites a quiet pause before returning to the day.
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</section>

<!-- 4. What We Value Section -->
<section class="section about-values-section" aria-labelledby="values-heading">
    <div class="container">
        <div class="section-header about-values-header">
            <div>
                <span class="eyebrow">03 / What We Value</span>
                <h2 id="values-heading">Principles of the Table.</h2>
            </div>
            <p class="about-values-lead">
                Four quiet commitments guide how we curate our matcha powders and preparation ware.
            </p>
        </div>

        <div class="about-values-grid">
            <article class="about-value-card">
                <span class="about-value-index">01 / Intention</span>
                <h3 class="about-value-title">Intention</h3>
                <p class="about-value-text">
                    Small rituals can change the pace of a day. How we prepare a cup matters just as much as the quiet minutes we spend drinking it.
                </p>
            </article>

            <article class="about-value-card">
                <span class="about-value-index">02 / Simplicity</span>
                <h3 class="about-value-title">Simplicity</h3>
                <p class="about-value-text">
                    Good things do not need unnecessary complication. A balanced ceramic bowl, a whisk, warm water, and fine matcha powder are enough.
                </p>
            </article>

            <article class="about-value-card">
                <span class="about-value-index">03 / Craft</span>
                <h3 class="about-value-title">Craft</h3>
                <p class="about-value-text">
                    Preparation deserves the same care as the finished cup. Every vessel and tool in our collection is chosen for tactile balance and lasting daily use.
                </p>
            </article>

            <article class="about-value-card">
                <span class="about-value-index">04 / Everyday Ritual</span>
                <h3 class="about-value-title">Everyday Ritual</h3>
                <p class="about-value-text">
                    Matcha belongs in ordinary mornings, focused afternoons, and quiet evenings—an approachable practice meant to be lived with every day.
                </p>
            </article>
        </div>
    </div>
</section>

<!-- 5. Closing Call-to-Action Section -->
<section class="section cta-section about-cta-section" aria-labelledby="about-cta-heading">
    <div class="container">
        <div class="cta-panel">
            <div class="cta-panel-content">
                <span class="eyebrow cta-eyebrow">Begin Your Ritual</span>
                <h2 id="about-cta-heading" class="cta-title">Make Space for the Ritual.</h2>
                <p class="cta-text">
                    Explore the collection and create a matcha ritual that belongs to your day.
                </p>
            </div>
            <div class="cta-panel-actions">
                <a href="<?php echo e($shopUrl); ?>" class="btn btn-light">
                    Shop Matcha
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
