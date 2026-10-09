<?php
/**
 * Customer Logout Endpoint
 * Zazen Matcha & Tea Co.
 *
 * Signs out the currently authenticated customer by removing only
 * customer-specific session keys ($_SESSION['customer_id'], $_SESSION['customer_name'],
 * $_SESSION['customer_email'], $_SESSION['redirect_after_login']) and rotating
 * the session ID, while preserving $_SESSION['cart'] and any active administrator session.
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/customer-auth.php';

ensure_session_started();

$customerLoginUrl = site_url('customer/login.php');

if (function_exists('logout_customer')) {
    logout_customer();
}

unset(
    $_SESSION['customer_id'],
    $_SESSION['customer_name'],
    $_SESSION['customer_email'],
    $_SESSION['redirect_after_login']
);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
}

set_flash_message('info', 'You have been signed out.');

redirect($customerLoginUrl);
