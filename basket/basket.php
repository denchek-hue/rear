<?php

session_start();
require_once '../database/db.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: /authorization/loginPage.php");
    exit;
}

$userId = $_SESSION['logged_id'];

function getBasketId($pdo, $userId) {
    $s = $pdo->prepare("SELECT id FROM basket WHERE user_id = ?");
    $s->execute([$userId]);
    $r = $s->fetch();
    return $r ? $r['id'] : null;
}

if (isset($_GET['remove'])) {
    $bagId = (int)$_GET['remove'];
    $bid   = getBasketId($pdo, $userId);
    if ($bid) $pdo->prepare("DELETE FROM basket_bag WHERE basket_id = ? AND bag_id = ?")->execute([$bid, $bagId]);
    header("Location: /basket/basket.php"); exit;
}
if (isset($_GET['minus'])) {
    $bagId = (int)$_GET['minus'];
    $bid   = getBasketId($pdo, $userId);
    if ($bid) {
        $s = $pdo->prepare("SELECT quantity FROM basket_bag WHERE basket_id = ? AND bag_id = ?");
        $s->execute([$bid, $bagId]);
        $row = $s->fetch();
        if ($row) {
            if ($row['quantity'] <= 1) $pdo->prepare("DELETE FROM basket_bag WHERE basket_id = ? AND bag_id = ?")->execute([$bid, $bagId]);
            else $pdo->prepare("UPDATE basket_bag SET quantity = quantity - 1 WHERE basket_id = ? AND bag_id = ?")->execute([$bid, $bagId]);
        }
    }
    header("Location: /basket/basket.php"); exit;
}
if (isset($_GET['plus'])) {
    $bagId = (int)$_GET['plus'];
    $bid   = getBasketId($pdo, $userId);
    if ($bid) $pdo->prepare("UPDATE basket_bag SET quantity = quantity + 1 WHERE basket_id = ? AND bag_id = ?")->execute([$bid, $bagId]);
    header("Location: /basket/basket.php"); exit;
}

// Получение корзины
$sql  = "SELECT bb.id AS bb_id, bb.quantity, b.id AS bag_id, b.name, b.price, b.img, br.name AS brand
         FROM basket ba
         JOIN basket_bag bb ON ba.id = bb.basket_id
         JOIN bag b ON bb.bag_id = b.id
         LEFT JOIN brand br ON b.brand_id = br.id
         WHERE ba.user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$items = $stmt->fetchAll();

$total = 0;
foreach ($items as $_i) { $total += $_i['price'] * $_i['quantity']; }

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
  <title>Корзина | DrunOwens</title>
  <link rel="stylesheet" href="/css/profile.css">
  <style>
    .basket-item { display:flex; align-items:center; gap:1rem; background:var(--surface); padding:1rem; border-radius:10px; margin-bottom:1rem; }
    .basket-item img-placeholder { font-size:2.5rem; flex-shrink:0; }
    .basket-item-info { flex:1; }
    .basket-item-info h3 { font-size:1rem; margin-bottom:0.2rem; }
    .basket-item-info .brand { font-size:0.8rem; color:var(--text-muted); }
    .basket-item-price { font-size:1.1rem; font-weight:700; color:var(--primary); }
    .remove-btn { background:none; border:none; cursor:pointer; font-size:1.2rem; color:var(--text-muted); text-decoration:none; }
    .remove-btn:hover { color:#dc2626; }
    .total-row { display:flex; justify-content:space-between; align-items:center; padding:1rem 0; border-top:2px solid rgba(139,94,60,0.15); margin-top:1rem; }
  </style>
</head>
<body>
<section class="hero">
  <div class="modal">
    <div class="modal-content" style="max-width:560px;">
      <a class="close-modal" href="/index.php">&times;</a>
      <h2>Моя корзина</h2>
      <?php if ($message): ?>
        <p style="color:#16a34a; margin-bottom:1rem;"><?php echo htmlspecialchars($message); ?></p>
      <?php endif; ?>

      <?php if (empty($items)): ?>
        <p style="color:var(--text-muted); text-align:center; padding:2rem 0;">Корзина пуста. <a href="/catalog/catalog.php" style="color:var(--primary);">Перейти в каталог</a></p>
      <?php else: ?>
        <?php foreach ($items as $item): ?>
          <div class="basket-item">
            <?php $bImg = $_SERVER['DOCUMENT_ROOT'].'/'.$item['img'];
            if ($item['img'] && file_exists($bImg)): ?>
              <img src="/<?php echo htmlspecialchars($item['img']); ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:8px;flex-shrink:0;">
            <?php else: ?>
              <div style="width:72px;height:72px;border-radius:8px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:1.8rem;flex-shrink:0;">&#128084;</div>
            <?php endif; ?>
            <div class="basket-item-info">
              <div class="brand"><?php echo htmlspecialchars($item['brand']); ?></div>
              <h3><?php echo htmlspecialchars($item['name']); ?></h3>
              <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                <a href="/basket/basket.php?minus=<?php echo $item['bag_id']; ?>" style="width:26px;height:26px;border-radius:6px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);text-decoration:none;border:1px solid rgba(139,94,60,0.2);">&#8722;</a>
                <span style="font-weight:600;min-width:18px;text-align:center;"><?php echo $item['quantity']; ?></span>
                <a href="/basket/basket.php?plus=<?php echo $item['bag_id']; ?>" style="width:26px;height:26px;border-radius:6px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--primary);text-decoration:none;border:1px solid rgba(139,94,60,0.2);">&#43;</a>
              </div>
            </div>
            <div class="basket-item-price"><?php echo number_format($item['price'] * $item['quantity'], 0, '.', ' '); ?> ₽</div>
            <a href="/basket/basket.php?remove=<?php echo $item['bag_id']; ?>" class="remove-btn" title="Удалить">✕</a>
          </div>
        <?php endforeach; ?>

        <div class="total-row">
          <span style="font-size:1.1rem; font-weight:600;">Итого:</span>
          <span style="font-size:1.4rem; font-weight:800; color:var(--primary);"><?php echo number_format($total, 0, '.', ' '); ?> ₽</span>
        </div>

        <button class="btn btn-primary" style="width:100%; justify-content:center; font-size:1rem; padding:1rem;"
          onclick="alert('Заказ оформлен! Мы свяжемся с вами.')">
          Оформить заказ
        </button>
      <?php endif; ?>

      <a href="/catalog/catalog.php" style="display:block; text-align:center; margin-top:1rem; color:var(--text-muted); font-size:0.9rem;">
        ← Продолжить покупки
      </a>
    </div>
  </div>
</section>
</body>
</html>
