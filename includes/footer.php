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
                <!-- Newsletter / Subscribe Column (Prototype Left Column) -->
                <div class="footer-brand">
                    <h2 class="footer-subscribe-title">Subscribe to the Ritual</h2>
                    <p class="footer-statement">
                        Seasonal harvest notes, preparation guides, and studio updates from Uji to Cape Town.
                    </p>
                    <div class="footer-newsletter-form" role="group" aria-label="Newsletter sign-up prototype preview">
                        <label for="footer-newsletter-email" class="visually-hidden">Email address (prototype preview)</label>
                        <input
                            type="email"
                            id="footer-newsletter-email"
                            class="footer-newsletter-input"
                            placeholder="Newsletter (prototype preview)"
                            disabled
                            aria-disabled="true"
                        >
                        <button type="button" class="btn btn-primary footer-newsletter-btn" disabled aria-disabled="true">
                            Preview
                        </button>
                    </div>
                    <p class="footer-newsletter-note">
                        Questions or wholesale enquiries? <a href="<?php echo e(site_url('customer/contact.php')); ?>">Contact our tea studio &rarr;</a>
                    </p>
                </div>

                <!-- Navigation Column -->
                <div class="footer-col">
                    <h2 class="footer-heading">Navigation</h2>
                    <ul class="footer-links">
                        <li><a href="<?php echo e(site_url('index.php')); ?>">Home</a></li>
                        <li><a href="<?php echo e(site_url('customer/products.php')); ?>">Shop</a></li>
                        <li><a href="<?php echo e(site_url('customer/about.php')); ?>">About</a></li>
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
                            <li><a href="<?php echo e(site_url('customer/account.php#account-orders-heading')); ?>">Order History</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Social & Studio Contact -->
                <div class="footer-col">
                    <h2 class="footer-heading">Social</h2>
                    <div class="footer-social-icons" aria-label="Social media channels (prototype icons)">
                        <span class="social-icon-badge" role="img" aria-label="Facebook">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                            </svg>
                        </span>
                        <span class="social-icon-badge" role="img" aria-label="Instagram">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                            </svg>
                        </span>
                        <span class="social-icon-badge" role="img" aria-label="YouTube">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>
                                <polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02" fill="#222522"></polygon>
                            </svg>
                        </span>
                    </div>
                    <address class="footer-contact">
                        <p>18 Botanical Lane, Cape Town</p>
                        <p><a href="mailto:hello@zazenmatcha.test">hello@zazenmatcha.test</a></p>
                    </address>
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
