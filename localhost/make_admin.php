<?php
// Этот файл назначает тебя администратором
// Открой: http://localhost/make_admin.php
// После использования — УДАЛИ этот файл!

session_start();
require_once 'database/db.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    $stmt = $pdo->prepare("SELECT userId, fio, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE email = ?")->execute([$email]);
        $success = true;
        $message = 'Готово! Пользователь "' . htmlspecialchars($user['fio']) . '" теперь администратор. Войди заново на сайте.';
    } else {
        $message = 'Пользователь с таким email не найден. Сначала зарегистрируйся.';
    }
}

// Показать всех пользователей
$users = $pdo->query("SELECT userId, fio, email, role FROM users")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Назначить администратора</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 0 20px; background: #faf8f5; color: #2d1f14; }
    h1 { color: #8b5e3c; }
    .warn { background: #fff3cd; border: 1px solid #ffc107; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
    .success { background: #d4edda; border: 1px solid #28a745; padding: 12px; border-radius: 8px; color: #155724; margin-bottom: 20px; }
    .error { background: #f8d7da; border: 1px solid #dc3545; padding: 12px; border-radius: 8px; color: #721c24; margin-bottom: 20px; }
    input[type=email] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-size: 15px; margin-bottom: 10px; box-sizing: border-box; }
    button { background: #8b5e3c; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-size: 15px; cursor: pointer; }
    button:hover { background: #6e4a2e; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
    th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #e0d5cc; }
    th { background: #f0ece6; }
    .role-admin { color: #8b5e3c; font-weight: bold; }
    a { color: #8b5e3c; }
  </style>
</head>
<body>
  <h1>Назначить администратора</h1>

  <div class="warn">
    ⚠️ <strong>Удали этот файл после использования!</strong><br>
    Путь: <code>C:\OSPanel\domains\localhost\make_admin.php</code>
  </div>

  <?php if ($message): ?>
    <div class="<?php echo $success ? 'success' : 'error'; ?>"><?php echo $message; ?></div>
  <?php endif; ?>

  <form method="POST">
    <label style="font-weight:bold; display:block; margin-bottom:6px;">Email аккаунта которому дать права админа:</label>
    <input type="email" name="email" placeholder="твой@email.ru" required>
    <button type="submit">Назначить администратором</button>
  </form>

  <?php if (!empty($users)): ?>
    <h2 style="margin-top:30px; font-size:16px;">Все пользователи в базе:</h2>
    <table>
      <tr><th>#</th><th>ФИО</th><th>Email</th><th>Роль</th></tr>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?php echo $u['userId']; ?></td>
          <td><?php echo htmlspecialchars($u['fio']); ?></td>
          <td><?php echo htmlspecialchars($u['email']); ?></td>
          <td class="<?php echo $u['role'] === 'admin' ? 'role-admin' : ''; ?>"><?php echo $u['role']; ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p style="color:#999; margin-top:20px;">Пользователей пока нет. <a href="/authorization/regPage.php">Зарегистрируйся</a> сначала.</p>
  <?php endif; ?>

  <p style="margin-top:30px;"><a href="/index.php">← На главную</a></p>
</body>
</html>
