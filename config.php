<?php
// config.php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Khalti Configuration - SANDBOX MODE
define('KHALTI_MOCK_MODE', false);
define('KHALTI_SECRET_KEY', 'b13d8e464e4446b4ac1c151d41b71b5f');
define('KHALTI_BASE_URL', 'https://dev.khalti.com/api/v2/');
define('KHALTI_RETURN_URL', 'http://localhost/myproject/khalti_callback.php');
define('KHALTI_WEBSITE_URL', 'http://localhost/myproject/');

// Payment Fees
define('UNLOCK_FEE_NPR', 10);      // Fee to unlock room details
define('OWNER_FEE_NPR', 50);       // Fee to become an owner

// Database connection
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $host = 'localhost';
    $port = '3306';
    $dbname = 'myapp';
    $user = 'root';
    $pass = '';

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        $options
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>