<?php
require_once __DIR__ . '/config.php';

// Check if all required constants are defined
$required_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'];
foreach ($required_constants as $constant) {
    if (!defined($constant)) {
        die("Database configuration error: $constant is not defined");
    }
}

$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Return arrays with column names as keys
    PDO::ATTR_EMULATE_PREPARES => false,                // Use real prepared statements
];

try {
    return new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    error_log('Database connection error: ' . $e->getMessage());
    die('Не удалось подключиться к базе данных. Пожалуйста, попробуйте позже.');
}
