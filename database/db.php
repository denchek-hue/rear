<?php
$host    = 'localhost';
$user    = 'root';
$pass    = '';
$dbname  = 'bags_shop';
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn_server = "mysql:host=$host;charset=$charset";
    $pdo = new PDO($dsn_server, $user, $pass, $options);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET $charset COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        userId    INT PRIMARY KEY AUTO_INCREMENT,
        email     VARCHAR(100),
        password  VARCHAR(255),
        role      VARCHAR(20) DEFAULT 'user'
    )");

    // Миграция: удалить старые поля если они ещё есть в БД
    $cols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    foreach (['fio', 'phone', 'dateBirth'] as $col) {
        if (in_array($col, $cols)) {
            $pdo->exec("ALTER TABLE users DROP COLUMN `$col`");
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS brand (
        id   INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bag (
        id       INT PRIMARY KEY AUTO_INCREMENT,
        name     VARCHAR(255),
        price    DECIMAL(10,2),
        rating   DECIMAL(3,2) DEFAULT 0,
        img      VARCHAR(255),
        brand_id INT,
        FOREIGN KEY (brand_id) REFERENCES brand(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS bag_info (
        id          INT PRIMARY KEY AUTO_INCREMENT,
        bag_id      INT,
        title       VARCHAR(100),
        description TEXT,
        FOREIGN KEY (bag_id) REFERENCES bag(id)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS basket (
        id      INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        FOREIGN KEY (user_id) REFERENCES users(userId)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS basket_bag (
        id        INT PRIMARY KEY AUTO_INCREMENT,
        basket_id INT,
        bag_id    INT,
        quantity  INT DEFAULT 1,
        FOREIGN KEY (basket_id) REFERENCES basket(id),
        FOREIGN KEY (bag_id)    REFERENCES bag(id)
    )");

    // Демо-данные
    $cnt = $pdo->query("SELECT COUNT(*) FROM brand")->fetchColumn();
    if ($cnt == 0) {
        $pdo->exec("INSERT INTO brand (name) VALUES
            ('ARNY PRAHT'), ('ASKENT'), ('EKONIKA'), ('DrunOwens'), ('Elegance')");

        $pdo->exec("INSERT INTO bag (name, price, rating, img, brand_id) VALUES
            ('Классическая кожаная сумка',  4990, 4.8, 'img/bag1.jpg', 4),
            ('Сумка-шоппер замша',          3490, 4.5, 'img/bag2.jpg', 4),
            ('Клатч вечерний',              2990, 4.7, 'img/bag3.jpg', 3),
            ('Сумка кросс-боди',            3990, 4.6, 'img/bag4.jpg', 1),
            ('Рюкзак городской',            4490, 4.4, 'img/bag5.jpg', 2),
            ('Сумка-тоут кожа',             5990, 4.9, 'img/bag6.jpg', 4),
            ('Клатч на цепочке',            2490, 4.3, 'img/bag7.jpg', 5),
            ('Сумка поясная',               1990, 4.2, 'img/bag8.jpg', 2),
            ('Деловая сумка',               6990, 4.8, 'img/bag9.jpg', 3)");
    }

} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}
