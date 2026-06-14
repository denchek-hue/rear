<?php

session_start();
require_once '../database/db.php';

$message = '';
if (isset($_SESSION['massage'])) {
    $message = $_SESSION['massage'];
    unset($_SESSION['massage']);
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | DrunOwens</title>
    <link rel="stylesheet" href="/css/pages/reg.css">
</head>
<body>
    <section class="hero">
      <div class="modal">
        <div class="modal-content">
          <a class="close-modal" href="/index.php">&times;</a>
          <h2>Вход в аккаунт</h2>
          <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">Добро пожаловать в DrunOwens</p>
          <p id="massage"><?php echo htmlspecialchars($message); ?></p>
          <form id="auth-form" action="/authorization/login.php" method="post">
            <div class="form-group">
              <label>Email или телефон</label>
              <input type="text" name="inputUser" placeholder="example@mail.ru" required>
            </div>
            <div class="form-group">
              <label>Пароль</label>
              <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Войти в аккаунт</button>
            <p style="text-align:center; margin-top:1rem; font-size:0.9rem; color:var(--text-muted);">
              Нет аккаунта?
              <a href="/authorization/regPage.php" style="color:var(--primary);">Зарегистрироваться</a>
            </p>
          </form>
        </div>
      </div>
    </section>
    <script src="/js/checkPass.js"></script>
</body>
</html>
