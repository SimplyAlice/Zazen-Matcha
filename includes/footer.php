<?php
/**
 * Reusable Customer Footer
 * Zazen Matcha & Tea Co.
 */
?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Brand Column -->
                <div class="footer-brand">
                    <a href="<?php echo e(site_url('index.php')); ?>" class="brand-logo footer-logo">
                        <span class="brand-name">Zazen Matcha</span>
                        <span class="brand-subtitle">Tea Co.</span>
                    </a>
                    <p class="footer-statement">
                        Thoughtfully prepared matcha powders and contemporary tea ware designed for quiet daily ritual and modern living.
                    </p>
                </div>

                <!-- Explore Navigation -->
                <div class="footer-col">
                    <h2 class="footer-heading">Explore</h2>
                    <ul class="footer-links">
                        <li><a href="<?php echo e(site_url('index.php')); ?>">Home</a></li>
                        <li><a href="<?php echo e(site_url('customer/products.php')); ?>">Shop Collection</a></li>
                        <li><a href="<?php echo e(site_url('customer/about.php')); ?>">Our Philosophy</a></li>
                        <li><a href="<?php echo e(site_url('customer/contact.php')); ?>">Contact</a></li>
                    </ul>
                </div>

                <!-- Customer Account -->
                <div class="footer-col">
                    <h2 class="footer-heading">Customer Care</h2>
                    <ul class="footer-links">
                        <li><a href="<?php echo e(site_url('customer/cart.php')); ?>">Shopping Cart</a></li>
                        <?php if (function_exists('is_customer_logged_in') && is_customer_logged_in()): ?>
                            <li><a href="<?php echo e(site_url('customer/account.php')); ?>">My Account</a></li>
                            <li><a href="<?php echo e(site_url('customer/account.php#account-orders-heading')); ?>">Order History</a></li>
                            <li><a href="<?php echo e(site_url('customer/logout.php')); ?>">Log Out</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo e(site_url('customer/login.php')); ?>">Customer Sign In</a></li>
                            <li><a href="<?php echo e(site_url('customer/register.php')); ?>">Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Contact Placeholders -->
                <div class="footer-col">
                    <h2 class="footer-heading">Studio &amp; Enquiries</h2>
                    <address class="footer-contact">
                        <p>18 Botanical Lane, Gardens</p>
                        <p>Cape Town, 8001, South Africa</p>
                        <p><a href="mailto:hello@zazenmatcha.test">hello@zazenmatcha.test</a></p>
                        <p>+27 (0) 21 555 0194</p>
                    </address>
                    <div class="footer-social" aria-label="Social media placeholders">
                        <a href="#instagram" class="social-link" aria-label="Instagram (placeholder)">Instagram</a>
                        <a href="#journal" class="social-link" aria-label="Journal (placeholder)">Journal</a>
                        <a href="#pinterest" class="social-link" aria-label="Pinterest (placeholder)">Pinterest</a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> Zazen Matcha &amp; Tea Co. All rights reserved.
                </p>
                <p class="footer-note">
                    University e-Commerce Project Prototype
                </p>
            </div>
        </div>
    </footer>

    <script src="<?php echo e(site_url('js/script.js')); ?>" defer></script>
</body>
</html>
