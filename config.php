<?php
$dbConfig = [
    'host'     => 'localhost',
    'name'     => 'books',
    'user'     => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['name'],
    $dbConfig['charset']
);

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], $pdoOptions);
} catch (PDOException $ex) {
    error_log('PDO connection error: ' . $ex->getMessage());
    throw new Exception('Unable to connect to database');
}
