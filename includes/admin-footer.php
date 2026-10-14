<?php
/**
 * Reusable Administrator Footer
 * Zazen Matcha & Tea Co.
 *
 * Closes the main content region cleanly, renders a minimal professional
 * administration footer with Zazen Matcha branding, and loads js/script.js
 * for mobile navigation drawer and flash notification dismissal.
 */
?>
    </main>

    <footer class="site-footer admin-footer">
        <div class="container">
            <div class="footer-bottom admin-footer-bottom">
                <p class="copyright">
                    &copy; <?php echo date('Y'); ?> Zazen Matcha &amp; Tea Co. &mdash; Administration Console
                </p>
                <p class="footer-note">
                    <a href="<?php echo e(site_url('index.php')); ?>">View Customer Storefront</a>
                    &middot; Cape Town Tea Studio &mdash; Internal Management
                </p>
            </div>
        </div>
    </footer>

    <script src="<?php echo e(site_url('js/script.js')); ?>" defer></script>
</body>
</html>
