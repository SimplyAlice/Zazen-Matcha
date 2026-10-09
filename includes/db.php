<?php
/**
 * Database Connection (PDO)
 * Zazen Matcha & Tea Co.
 *
 * Establishes a reusable UTF-8 PDO connection to the local XAMPP MySQL database.
 * Supports an optional git-ignored config.php override at the project root.
 */

// Load optional local configuration override if present (excluded via .gitignore)
$localConfigPath = dirname(__DIR__) . '/config.php';
if (file_exists($localConfigPath)) {
    require_once $localConfigPath;
}

// Default XAMPP MySQL connection settings
if (!defined('DB_HOST')) {
    define('DB_HOST', '127.0.0.1');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'zazen_matcha');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', 'utf8mb4');
}

/**
 * Returns a shared PDO database connection instance.
 *
 * @return PDO
 */
function get_db_connection(): PDO
{
    static $pdoInstance = null;

    if ($pdoInstance === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdoInstance = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log detailed error on the server without exposing credentials in the browser
            error_log('Database Connection Error: ' . $e->getMessage());
            http_response_code(500);
            exit('A database connection error occurred. Please try again later.');
        }
    }

    return $pdoInstance;
}

// Provide a default $pdo variable for files that include db.php directly
$pdo = get_db_connection();
