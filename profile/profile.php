<?php

session_start();
require_once '../database/db.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: /authorization/loginPage.php");
    exit;
}

$userId = $_SESSION['logged_id'];

// Данные пользователя
$sql  = "SELECT email FROM users WHERE userId = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$userData = $stmt->fetch();

// Корзина пользователя
$basketHtml = '<p style="color:var(--text-muted);">Корзина пуста</p>';
$sql  = "SELECT bb.quantity, b.name, b.price, b.img
         FROM basket ba
         JOIN basket_bag bb ON ba.id = bb.basket_id
         JOIN bag b ON bb.bag_id = b.id
         WHERE ba.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$basketItems = $stmt->fetchAll();

$totalPrice = 0;
if (!empty($basketItems)) {
    $basketHtml = '';
    foreach ($basketItems as $item) {
        $subtotal    = $item['price'] * $item['quantity'];
        $totalPrice += $subtotal;
        $basketHtml .= '
        <div class="card">
          <div style="display:flex; align-items:center; gap:1rem;">
            <span style="font-size:2rem;">' . $item['img'] . '</span>
            <div>
              <h3>' . htmlspecialchars($item['name']) . '</h3>
              <span class="badge">' . number_format($item['price'], 0, '.', ' ') . ' ₽ × ' . $item['quantity'] . '</span>
            </div>
          </div>
          <div class="meta"><span>Итого: <strong>' . number_format($subtotal, 0, '.', ' ') . ' ₽</strong></span></div>
        </div>';
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль | DrunOwens</title>
    <link rel="stylesheet" href="/css/pages/profile.css">
</head>
<body>
<section class="hero">
  <div class="modal">
    <div class="modal-content">
      <a class="close-modal" href="/index.php">&times;</a>
      <h2>Мой профиль</h2>

      <div class="profile-info">
        <p><strong>Email:</strong> <?php echo htmlspecialchars($userData['email']); ?></p>
        <a href="/profile/editProfile.php" style="color:var(--primary); font-size:0.9rem;">Изменить данные</a>
      </div>

      <p class="section-label">Корзина</p>
      <?php echo $basketHtml; ?>
      <?php if (!empty($basketItems)): ?>
        <div style="text-align:right; margin-top:0.5rem; font-size:1.1rem; font-weight:700; color:var(--primary);">
          Итого: <?php echo number_format($totalPrice, 0, '.', ' '); ?> ₽
        </div>
        <a href="/basket/basket.php" class="btn btn-primary" style="margin-top:1rem; width:100%; justify-content:center;">
          Оформить заказ
        </a>
      <?php endif; ?>

      <form action="/profile/exit.php" method="post" style="margin-top:1.5rem;">
        <button type="submit" class="btn btn-outline" style="width:100%; justify-content:center;">
          Выйти из аккаунта
        </button>
      </form>
    </div>
  </div>
</section>
</body>
</html>
