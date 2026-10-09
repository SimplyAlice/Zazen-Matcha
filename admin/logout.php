<?php
/**
 * Administrator Logout Endpoint
 * Zazen Matcha & Tea Co.
 *
 * Signs out the currently authenticated administrator by removing only
 * admin-specific session keys ($_SESSION['admin_id'], $_SESSION['admin_name'],
 * $_SESSION['admin_email']) and rotating the session ID, while preserving
 * customer session state and $_SESSION['cart'].
 */

require_once dirname(__DIR__) . '/includes/functions.php';

ensure_session_started();

$adminLoginUrl = site_url('admin/login.php');

if (is_admin_logged_in()) {
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_email']
    );

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    set_flash_message('info', 'You have been signed out.');
}

redirect($adminLoginUrl);
