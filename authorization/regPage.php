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
    <title>Регистрация | DrunOwens</title>
    <link rel="stylesheet" href="/css/pages/reg.css">
</head>
<body>
    <section class="hero">
      <div class="modal">
        <div class="modal-content">
          <a class="close-modal" href="/index.php">&times;</a>
          <h2>Создать аккаунт</h2>
          <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">Присоединяйтесь к DrunOwens</p>
          <p id="massage"><?php echo htmlspecialchars($message); ?></p>
          <form id="auth-form" action="/authorization/reg.php" method="post">
            <div class="form-group">
              <label>Email</label>
              <input name="email" type="email" maxlength="100" placeholder="example@mail.ru" required>
            </div>
            <div class="form-group">
              <label>Пароль</label>
              <input name="password" type="password" minlength="6" id="pass" required>
            </div>
            <p id="massagePass"></p>
            <div class="form-group">
              <label>Повтор пароля</label>
              <input type="password" minlength="6" id="copyPass" required>
            </div>
            <button type="submit" class="btn btn-primary">Создать учётную запись</button>
            <p style="text-align:center; margin-top:1rem; font-size:0.9rem; color:var(--text-muted);">
              Есть аккаунт?
              <a href="/authorization/loginPage.php" style="color:var(--primary);">Войти</a>
            </p>
          </form>
        </div>
      </div>
    </section>
    <script src="/js/checkPass.js"></script>
</body>
</html>
