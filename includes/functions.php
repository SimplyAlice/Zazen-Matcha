<?php
/**
 * Shared Foundational Helper Functions
 * Zazen Matcha & Tea Co.
 *
 * Contains reusable helper functions for sessions, output escaping,
 * input cleaning, password hashing, redirects, flash messages, and
 * prepared PDO database queries.
 */

/**
 * Safely starts a PHP session if one is not already active.
 *
 * @return void
 */
function ensure_session_started(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'use_strict_mode' => true,
        ]);
    }
}

/**
 * Escapes a string for safe HTML output (prevents XSS).
 *
 * @param string|int|float|null $value
 * @return string
 */
function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Trims and cleans basic text input from forms.
 * Note: Database queries must still use PDO prepared statements,
 * and HTML output must still use e().
 *
 * @param string|null $input
 * @return string
 */
function clean_input(?string $input): string
{
    if ($input === null) {
        return '';
    }
    return trim(strip_tags($input));
}

/**
 * Formats a numeric amount into South African Rand (R) currency format.
 *
 * @param float|int|string $amount
 * @return string
 */
function format_price($amount): string
{
    return 'R ' . number_format((float) $amount, 2, '.', ',');
}

/**
 * Redirects the browser to a given URL and stops script execution.
 *
 * @param string $url
 * @return never
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Stores a one-time flash message in the session.
 *
 * @param string $type    Message type (e.g. 'success', 'error', 'info')
 * @param string $message Message text
 * @return void
 */
function set_flash_message(string $type, string $message): void
{
    ensure_session_started();
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieves and clears the current flash message from the session.
 *
 * @return array{type: string, message: string}|null
 */
function get_flash_message(): ?array
{
    ensure_session_started();
    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $flash = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);

    return $flash;
}

/**
 * Checks whether a customer is currently logged in via session.
 *
 * @return bool
 */
function is_customer_logged_in(): bool
{
    ensure_session_started();
    return !empty($_SESSION['customer_id']);
}

/**
 * Checks whether an administrator is currently logged in via session.
 *
 * @return bool
 */
function is_admin_logged_in(): bool
{
    ensure_session_started();
    return !empty($_SESSION['admin_id']);
}

/**
 * Hashes a plain-text password using PHP's default secure algorithm (bcrypt).
 *
 * @param string $plainPassword
 * @return string
 */
function hash_password(string $plainPassword): string
{
    return password_hash($plainPassword, PASSWORD_DEFAULT);
}

/**
 * Verifies a plain-text password against a stored password hash.
 *
 * @param string $plainPassword
 * @param string $passwordHash
 * @return bool
 */
function verify_password(string $plainPassword, string $passwordHash): bool
{
    return password_verify($plainPassword, $passwordHash);
}

/**
 * Generates or returns the active CSRF token stored in the session.
 *
 * @return string
 */
function csrf_token(): string
{
    ensure_session_started();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Alias for csrf_token() for compatibility with forms expecting generate_csrf_token().
 *
 * @return string
 */
function generate_csrf_token(): string
{
    return csrf_token();
}

/**
 * Validates a submitted CSRF token against the session token.
 *
 * @param string|null $submittedToken
 * @return bool
 */
function verify_csrf_token(?string $submittedToken): bool
{
    ensure_session_started();
    if (empty($_SESSION['csrf_token']) || empty($submittedToken)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}

/**
 * Helper to fetch a single row using a PDO prepared statement.
 *
 * @param PDO    $pdo
 * @param string $sql
 * @param array  $params
 * @return array|null
 */
function db_fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

/**
 * Helper to fetch all matching rows using a PDO prepared statement.
 *
 * @param PDO    $pdo
 * @param string $sql
 * @param array  $params
 * @return array
 */
function db_fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**
 * Determine the base web path of the project (e.g. '/ZAZEN-MATCHA')
 * so that asset and page URLs work consistently from both root (index.php)
 * and subfolder pages (/customer/* and /admin/*) without relying on '../'.
 */
if (!defined('BASE_URL')) {
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot     = isset($_SERVER['DOCUMENT_ROOT'])
        ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/')
        : '';

    if ($docRoot !== '' && stripos($projectRoot, $docRoot) === 0) {
        $subPath = trim(substr($projectRoot, strlen($docRoot)), '/');
        define('BASE_URL', $subPath !== '' ? '/' . $subPath : '');
    } else {
        define('BASE_URL', '/ZAZEN-MATCHA');
    }
}

/**
 * Builds a consistent root-relative URL for any internal page or asset.
 *
 * @param string $path Path relative to the project root (e.g. 'css/style.css' or 'customer/products.php')
 * @return string
 */
function site_url(string $path = ''): string
{
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return BASE_URL . '/';
    }

    return BASE_URL . '/' . $cleanPath;
}

/**
 * Resolves the local image URL for a product image filename stored in the database,
 * falling back to images/products/placeholder.jpg if the file is missing.
 *
 * @param string|null $imageFilename
 * @return string
 */
function product_image_url(?string $imageFilename): string
{
    $filename = basename(trim((string) $imageFilename));
    if ($filename !== '') {
        $filePath = dirname(__DIR__) . '/images/products/' . $filename;
        if (is_file($filePath)) {
            return site_url('images/products/' . $filename);
        }
    }

    return site_url('images/products/placeholder.jpg');
}

/**
 * Returns contextual gallery thumbnails for a product based on its category
 * and specific product identity.
 *
 * Each item in the returned array contains:
 * - 'src': Fully qualified URL to the high-resolution image asset
 * - 'alt': Accessible, editorial description for the image
 *
 * @param array $product Associative array containing product_id, category_id, name, and image
 * @return array<int, array{src: string, alt: string}>
 */
function get_product_gallery(array $product): array
{
    $productId   = (int) ($product['product_id'] ?? 0);
    $productName = (string) ($product['name'] ?? 'Product');
    $imageUrl    = product_image_url($product['image'] ?? null);

    // Primary image is always index 0
    $primary = [
        'src' => $imageUrl,
        'alt' => $productName . ' — Primary product presentation',
    ];

    switch ($productId) {
        // --- Category 1: Packaged Matcha Powders ---
        case 1:
            // Ceremonial Matcha
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/matcha-powder-detail.jpg'),
                    'alt' => $productName . ' — Silk-fine stone-ground Uji tencha ceremonial powder detail',
                ],
                [
                    'src' => site_url('images/gallery/whisked-matcha-bowl.jpg'),
                    'alt' => $productName . ' — Frothy emerald microfoam usucha bowl preparation',
                ],
            ];

        case 2:
            // Everyday Matcha
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/matcha-powder-detail.jpg'),
                    'alt' => $productName . ' — Finely milled stone-ground green tea powder detail',
                ],
                [
                    'src' => site_url('images/gallery/whisked-matcha-bowl.jpg'),
                    'alt' => $productName . ' — Freshly whisked everyday morning matcha bowl',
                ],
            ];

        // --- Category 4: Specialty Loose-Leaf Teas ---
        case 7:
            // Saffron Golden Infusion
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/golden-saffron-botanicals.jpg'),
                    'alt' => $productName . ' — Hand-picked botanical saffron threads, chamomile, and dried citrus peel',
                ],
                [
                    'src' => site_url('images/gallery/golden-saffron-steep.jpg'),
                    'alt' => $productName . ' — Luminous golden-amber herbal liquor slow steep in glass teapot',
                ],
            ];

        case 8:
            // Midnight Bloom
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/butterfly-pea-botanicals.jpg'),
                    'alt' => $productName . ' — Sun-dried Clitoria ternatea blue butterfly pea flower blossoms on slate',
                ],
                [
                    'src' => site_url('images/gallery/butterfly-pea-color-shift.jpg'),
                    'alt' => $productName . ' — Pure indigo botanical infusion liquor and citrus color-shift',
                ],
            ];

        case 12:
            // Jasmine Pearls
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/iced-jasmine-pearls-detail.jpg'),
                    'alt' => $productName . ' — Hand-rolled tender green tea pearls with fresh jasmine blossoms',
                ],
            ];

        // --- Category 5: Prepared Drinks (Tea Bar) ---
        case 13:
            // Traditional Whisked Usucha
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/whisked-matcha-bowl.jpg'),
                    'alt' => $productName . ' — Handcrafted ceramic chawan with frothy usucha microfoam',
                ],
                [
                    'src' => site_url('images/gallery/matcha-powder-detail.jpg'),
                    'alt' => $productName . ' — Silk-fine single-origin ceremonial matcha base',
                ],
            ];

        case 14:
            // Classic Iced Matcha Latte
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/classic-iced-matcha-prep.jpg'),
                    'alt' => $productName . ' — Ceremonial matcha whisked over organic oat milk and ice',
                ],
                [
                    'src' => site_url('images/gallery/matcha-powder-detail.jpg'),
                    'alt' => $productName . ' — Vibrant stone-ground ceremonial matcha powder base',
                ],
                [
                    'src' => site_url('images/tea-bar/matcha-bar-feature.jpg'),
                    'alt' => $productName . ' — Cape Town tea bar counter service presentation',
                ],
            ];

        case 15:
            // Strawberry Matcha Latte (STRICTLY NO PLAIN MATCHA LATTE PREP)
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/strawberry-matcha-layer-detail.jpg'),
                    'alt' => $productName . ' — House-made ripe strawberry puree and ceremonial matcha gradient layering',
                ],
            ];

        case 16:
            // Vanilla Bean Matcha Latte
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/vanilla-bean-matcha-detail.jpg'),
                    'alt' => $productName . ' — Madagascar vanilla bean caviar infusion with whole vanilla pods',
                ],
            ];

        case 17:
            // Iced Coconut Matcha
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/iced-coconut-matcha-detail.jpg'),
                    'alt' => $productName . ' — Pure young coconut water topped with vibrant green matcha swirl',
                ],
            ];

        case 18:
            // Roasted Hojicha Latte
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/roasted-hojicha-detail.jpg'),
                    'alt' => $productName . ' — Charcoal-roasted Kyoto green tea micro-mill and warm layered latte',
                ],
            ];

        case 19:
            // Iced Jasmine Pearl Tea
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/iced-jasmine-pearls-detail.jpg'),
                    'alt' => $productName . ' — Hand-rolled jasmine green tea pearls cold extraction and steep',
                ],
            ];

        case 20:
            // White Peach Iced Oolong
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/white-peach-oolong-prep.jpg'),
                    'alt' => $productName . ' — High-mountain oolong cold-steeped with fresh white peach slices and ice',
                ],
            ];

        case 21:
            // Butterfly Pea Flower Lemonade
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/butterfly-pea-color-shift.jpg'),
                    'alt' => $productName . ' — Luminous violet and indigo natural citrus color-shift infusion',
                ],
                [
                    'src' => site_url('images/gallery/butterfly-pea-botanicals.jpg'),
                    'alt' => $productName . ' — Sun-dried Clitoria ternatea blue butterfly pea flower blossoms on slate',
                ],
            ];

        case 22:
            // Golden Saffron Herbal Infusion
            return [
                $primary,
                [
                    'src' => site_url('images/gallery/golden-saffron-steep.jpg'),
                    'alt' => $productName . ' — Luminous golden-amber slow herbal steep in glass teapot',
                ],
                [
                    'src' => site_url('images/gallery/golden-saffron-botanicals.jpg'),
                    'alt' => $productName . ' — Hand-picked botanical saffron threads and dried citrus peel',
                ],
            ];

        // --- All other products (Accessories, Living Botanicals, Teaware, Sets without extra angles) ---
        // Retain verified primary product image and omit unrelated supplementary imagery
        default:
            return [
                $primary,
            ];
    }
}
