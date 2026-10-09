<?php
/**
 * Customer Authentication Protection Helpers
 * Zazen Matcha & Tea Co.
 *
 * Provides reusable session-based access control helpers to protect
 * customer-only pages and redirect unauthenticated visitors to login.php.
 */

require_once __DIR__ . '/functions.php';

ensure_session_started();

/**
 * Protects a page so that only a logged-in customer can access it.
 * If no customer is authenticated, stores the requested page URL,
 * sets a flash message, and redirects to the customer login page.
 *
 * @param string $loginUrl Relative path to the customer login page
 * @return void
 */
function require_customer_login(?string $loginUrl = null): void
{
    ensure_session_started();

    if (!is_customer_logged_in()) {
        // Remember the requested URL (basename + query string) for post-login redirect
        if (!empty($_SERVER['REQUEST_URI'])) {
            $requestUri = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
            $queryString = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            if ($requestUri !== '' && $requestUri !== 'login.php') {
                $_SESSION['redirect_after_login'] = $queryString
                    ? $requestUri . '?' . $queryString
                    : $requestUri;
            }
        }

        set_flash_message('info', 'Please sign in to access your account.');
        redirect($loginUrl ?? site_url('customer/login.php'));
    }
}

/**
 * Redirects an already-authenticated customer away from guest-only pages
 * (such as login.php or register.php).
 *
 * @param string|null $destinationUrl Target URL to redirect authenticated customers to
 * @return void
 */
function redirect_if_customer_logged_in(?string $destinationUrl = null): void
{
    ensure_session_started();

    if (is_customer_logged_in()) {
        redirect($destinationUrl ?? site_url('customer/account.php'));
    }
}

/**
 * Returns the currently logged-in customer's ID from the session, or null.
 *
 * @return int|null
 */
function get_logged_in_customer_id(): ?int
{
    ensure_session_started();

    if (!is_customer_logged_in()) {
        return null;
    }

    return (int) $_SESSION['customer_id'];
}

/**
 * Returns the currently logged-in customer's display name from the session, or null.
 *
 * @return string|null
 */
function get_logged_in_customer_name(): ?string
{
    ensure_session_started();

    if (!is_customer_logged_in()) {
        return null;
    }

    return isset($_SESSION['customer_name']) ? (string) $_SESSION['customer_name'] : null;
}
