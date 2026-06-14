<?php

session_start();
require_once '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $userInput = trim($_POST['inputUser'] ?? '');
    $password  = $_POST['password'];

    $sql  = "SELECT phone, email, password, userId FROM users WHERE phone = ? OR email = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userInput, $userInput]);

    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['logged_id'] = $user['userId'];
            header("Location: /index.php");
            exit;
        } else {
            $_SESSION['massage'] = "Неверный пароль. Попробуйте ещё раз.";
            header("Location: /authorization/loginPage.php");
            exit;
        }
    } else {
        $_SESSION['massage'] = "Аккаунт с такими данными не найден.";
        header("Location: /authorization/loginPage.php");
        exit;
    }
}
