/**
 * Zazen Matcha & Tea Co. — Customer Frontend JavaScript Foundation
 * Vanilla JavaScript utilities for navigation and UI interactions.
 */

(function () {
    'use strict';

    /**
     * Initialises mobile navigation drawer toggle and keyboard support.
     */
    function initMobileNavigation() {
        const navToggle = document.getElementById('nav-toggle');
        const navWrapper = document.getElementById('primary-navigation');

        if (!navToggle || !navWrapper) {
            return;
        }

        function setMenuState(isOpen) {
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            navWrapper.classList.toggle('is-open', isOpen);
        }

        navToggle.addEventListener('click', function () {
            const isCurrentlyOpen = navToggle.getAttribute('aria-expanded') === 'true';
            setMenuState(!isCurrentlyOpen);
        });

        // Close mobile menu when pressing Escape
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && navToggle.getAttribute('aria-expanded') === 'true') {
                setMenuState(false);
                navToggle.focus();
            }
        });

        // Reset mobile menu state if viewport is resized above mobile breakpoint
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768 && navWrapper.classList.contains('is-open')) {
                setMenuState(false);
            }
        });
    }

    /**
     * Initialises dismissible flash notification banners.
     */
    function initFlashDismiss() {
        const dismissButtons = document.querySelectorAll('[data-flash-dismiss]');

        dismissButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                const flashBanner = button.closest('[data-flash-message]');
                const flashContainer = button.closest('.flash-container');

                if (flashContainer) {
                    flashContainer.remove();
                } else if (flashBanner) {
                    flashBanner.remove();
                }
            });
        });
    }

    /**
     * Initialises optional + / - quantity stepper controls on product/cart forms.
     */
    function initQuantitySteppers() {
        const steppers = document.querySelectorAll('.quantity-stepper');

        steppers.forEach(function (stepper) {
            const input = stepper.querySelector('.quantity-input');
            const buttons = stepper.querySelectorAll('[data-qty-step]');

            if (!input || input.disabled) {
                return;
            }

            function clampQuantity(val) {
                const min = parseInt(input.getAttribute('min'), 10) || 1;
                const max = parseInt(input.getAttribute('max'), 10) || 999;
                let parsed = parseInt(val, 10);
                if (isNaN(parsed) || parsed < min) {
                    parsed = min;
                } else if (parsed > max) {
                    parsed = max;
                }
                input.value = String(parsed);
            }

            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const step = parseInt(btn.getAttribute('data-qty-step'), 10) || 0;
                    const current = parseInt(input.value, 10) || 1;
                    clampQuantity(current + step);

                    // Auto-submit cart quantity update form when stepper is clicked in cart
                    const cartForm = stepper.closest('.cart-update-form');
                    if (cartForm && parseInt(input.value, 10) !== current) {
                        cartForm.submit();
                    }
                });
            });

            input.addEventListener('change', function () {
                clampQuantity(input.value);
            });
        });
    }

    /**
     * Initialises thumbnail switching on the Product Details gallery.
     */
    function initProductGalleryThumbs() {
        const mainImg = document.getElementById('product-main-image');
        const thumbButtons = document.querySelectorAll('[data-gallery-thumb]');

        if (!mainImg || thumbButtons.length === 0) {
            return;
        }

        const defaultSrc = mainImg.getAttribute('data-default-src') || mainImg.getAttribute('src');
        const defaultAlt = mainImg.getAttribute('data-default-alt') || mainImg.getAttribute('alt');
        const primaryBtn = thumbButtons[0];

        thumbButtons.forEach(function (btn, index) {
            btn.addEventListener('click', function () {
                const isAlreadyActive = btn.classList.contains('is-active');

                if (isAlreadyActive && index > 0 && defaultSrc) {
                    mainImg.setAttribute('src', defaultSrc);
                    if (defaultAlt) {
                        mainImg.setAttribute('alt', defaultAlt);
                    }
                    thumbButtons.forEach(function (other) {
                        other.classList.remove('is-active');
                        other.setAttribute('aria-pressed', 'false');
                    });
                    if (primaryBtn) {
                        primaryBtn.classList.add('is-active');
                        primaryBtn.setAttribute('aria-pressed', 'true');
                    }
                    return;
                }

                const newSrc = btn.getAttribute('data-gallery-thumb');
                const newAlt = btn.getAttribute('data-gallery-alt');

                if (newSrc) {
                    mainImg.setAttribute('src', newSrc);
                }
                if (newAlt) {
                    mainImg.setAttribute('alt', newAlt);
                }

                thumbButtons.forEach(function (other) {
                    other.classList.remove('is-active');
                    other.setAttribute('aria-pressed', 'false');
                });
                btn.classList.add('is-active');
                btn.setAttribute('aria-pressed', 'true');
            });
        });
    }

    /**
     * Formats a number into South African Rand (R) currency string.
     *
     * @param {number|string} amount
     * @returns {string}
     */
    function formatCurrencyZAR(amount) {
        const numeric = Number(amount) || 0;
        return 'R ' + numeric.toFixed(2);
    }

    // Expose simple reusable utilities on a namespace for future scripts
    window.ZazenUI = {
        formatCurrencyZAR: formatCurrencyZAR
    };

    document.addEventListener('DOMContentLoaded', function () {
        initMobileNavigation();
        initFlashDismiss();
        initQuantitySteppers();
        initProductGalleryThumbs();
    });
})();
