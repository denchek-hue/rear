<?php

session_start();
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'];

    $hashPass = password_hash($password, PASSWORD_DEFAULT);

    $sql  = "SELECT email FROM users WHERE email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['massage'] = "Этот Email уже зарегистрирован. Используйте другой.";
        header("Location: /authorization/regPage.php");
        exit;
    }

    $sql  = "INSERT INTO users (email, password, role) VALUES (?, ?, 'user')";
    $stmt = $pdo->prepare($sql);

    try {
        $stmt->execute([$email, $hashPass]);
        $_SESSION['massage'] = "Регистрация прошла успешно! Войдите в аккаунт.";
        header("Location: /authorization/loginPage.php");
        exit;
    } catch (PDOException $e) {
        error_log("Ошибка регистрации: " . $e->getMessage() . PHP_EOL, 3, "errors.log");
        die("Ошибка при регистрации. Попробуйте позже.");
    }
}
