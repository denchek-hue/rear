<?php
$host    = getenv('MYSQLHOST')     ?: 'localhost';
$port    = getenv('MYSQLPORT')     ?: '3306';
$dbname  = getenv('MYSQLDATABASE') ?: 'bags_shop';
$user    = getenv('MYSQLUSER')     ?: 'root';
$pass    = getenv('MYSQLPASSWORD') ?: '';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
