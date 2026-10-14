<?php
/**
 * Newsletter Subscription Handler
 * Zazen Matcha & Tea Co.
 *
 * Validates email, verifies CSRF token, and records subscription
 * in the `newsletter_subscribers` MySQL table with status feedback.
 */

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

ensure_session_started();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(site_url('index.php'));
}

$returnUrl = isset($_POST['return_url']) && is_string($_POST['return_url']) && !empty($_POST['return_url'])
    ? $_POST['return_url']
    : site_url('index.php');

// Sanitize returnUrl: ensure it doesn't perform open redirect off-domain
if (strpos($returnUrl, 'http://') === 0 || strpos($returnUrl, 'https://') === 0) {
    $parsedHost = parse_url($returnUrl, PHP_URL_HOST);
    $serverHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if ($parsedHost !== $serverHost) {
        $returnUrl = site_url('index.php');
    }
}

// 1. Verify CSRF Token
$submittedToken = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
    ? $_POST['csrf_token']
    : null;

if (!verify_csrf_token($submittedToken)) {
    set_flash_message('error', 'Invalid security token. Please try subscribing again.');
    redirect($returnUrl);
}

// 2. Validate Email
$rawEmail = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';
$cleanEmail = strtolower(clean_input($rawEmail));

if ($cleanEmail === '' || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL) || mb_strlen($cleanEmail) > 150) {
    set_flash_message('error', 'Please enter a valid email address.');
    redirect($returnUrl);
}

// 3. Insert or update subscription in `newsletter_subscribers`
$clientIp = $_SERVER['REMOTE_ADDR'] ?? null;
if ($clientIp !== null && mb_strlen($clientIp) > 45) {
    $clientIp = mb_substr($clientIp, 0, 45);
}

try {
    // Check if subscriber already exists
    $existing = db_fetch_one(
        $pdo,
        'SELECT subscriber_id, status FROM newsletter_subscribers WHERE email = :email LIMIT 1',
        ['email' => $cleanEmail]
    );

    if ($existing !== null) {
        if ($existing['status'] === 'subscribed') {
            set_flash_message('info', 'Thank you! You are already subscribed to the Zazen Matcha ritual.');
        } else {
            $stmt = $pdo->prepare('UPDATE newsletter_subscribers SET status = "subscribed", updated_at = NOW(), ip_address = :ip WHERE subscriber_id = :id');
            $stmt->execute([
                'ip' => $clientIp,
                'id' => $existing['subscriber_id'],
            ]);
            set_flash_message('success', 'Welcome back! Your newsletter subscription has been reactivated.');
        }
    } else {
        $stmt = $pdo->prepare('INSERT INTO newsletter_subscribers (email, status, ip_address) VALUES (:email, "subscribed", :ip)');
        $stmt->execute([
            'email' => $cleanEmail,
            'ip'    => $clientIp,
        ]);
        set_flash_message('success', 'Thank you for subscribing to the Zazen Matcha ritual.');
    }
} catch (Throwable $e) {
    error_log('Newsletter Subscription Error: ' . $e->getMessage());
    set_flash_message('error', 'We could not process your subscription at this time. Please try again.');
}

redirect($returnUrl);
