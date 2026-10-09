<?php
/**
 * Administrator Authentication Protection Helpers
 * Zazen Matcha & Tea Co.
 *
 * Provides reusable session-based access control helpers to protect
 * administrator-only pages and redirect unauthenticated visitors to
 * admin/login.php without affecting customer sessions or shopping carts.
 */

require_once __DIR__ . '/functions.php';

ensure_session_started();

/**
 * Protects an administration page so that only an authenticated admin can access it.
 * If no administrator session is active, redirects immediately to admin/login.php
 * and stops script execution without altering customer cart or session data.
 *
 * @param string|null $loginUrl Optional override URL for the admin login page
 * @return void
 */
function require_admin_login(?string $loginUrl = null): void
{
    ensure_session_started();

    if (!is_admin_logged_in()) {
        redirect($loginUrl ?? site_url('admin/login.php'));
    }
}

/**
 * Returns the currently logged-in administrator's ID from the session, or null.
 *
 * @return int|null
 */
function get_logged_in_admin_id(): ?int
{
    ensure_session_started();

    if (!is_admin_logged_in()) {
        return null;
    }

    return (int) $_SESSION['admin_id'];
}

/**
 * Returns the currently logged-in administrator's display name from the session, or null.
 *
 * @return string|null
 */
function get_logged_in_admin_name(): ?string
{
    ensure_session_started();

    if (!is_admin_logged_in()) {
        return null;
    }

    $name = isset($_SESSION['admin_name']) ? trim((string) $_SESSION['admin_name']) : '';

    return $name !== '' ? $name : 'Administrator';
}

/**
 * Returns the currently logged-in administrator's email from the session, or null.
 *
 * @return string|null
 */
function get_logged_in_admin_email(): ?string
{
    ensure_session_started();

    if (!is_admin_logged_in()) {
        return null;
    }

    return isset($_SESSION['admin_email']) ? trim((string) $_SESSION['admin_email']) : null;
}
