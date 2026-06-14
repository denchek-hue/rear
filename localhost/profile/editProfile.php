<?php

session_start();
require_once '../database/db.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: /authorization/loginPage.php");
    exit;
}

$userId = $_SESSION['logged_id'];

$message = '';
if (isset($_SESSION['massage'])) {
    $message = $_SESSION['massage'];
    unset($_SESSION['massage']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE userId = ?");
    $stmt->execute([$email, $userId]);

    $_SESSION['massage'] = "Данные успешно обновлены.";
    header("Location: /profile/profile.php");
    exit;
}

$stmt = $pdo->prepare("SELECT email FROM users WHERE userId = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Редактировать профиль | DrunOwens</title>
  <link rel="stylesheet" href="/css/reg.css">
</head>
<body>
<section class="hero">
  <div class="modal">
    <div class="modal-content">
      <a class="close-modal" href="/profile/profile.php">&times;</a>
      <h2>Редактирование</h2>
      <p id="massage"><?php echo htmlspecialchars($message); ?></p>
      <form method="post">
        <div class="form-group">
          <label>Email</label>
          <input name="email" type="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
      </form>
    </div>
  </div>
</section>
</body>
</html>
