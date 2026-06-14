<?php

session_start();
require_once 'database/db.php';

// Навигационная кнопка
$btn = '<li><a class="btn btn-outline" href="/authorization/loginPage.php">Войти / Регистрация</a></li>';

if (isset($_SESSION['logged_in']) === true) {
    $btn = '<li><a class="btn btn-outline" href="/profile/profile.php">Профиль</a></li>';
    $btn .= '<li><a class="btn btn-primary" href="/basket/basket.php">Корзина</a></li>';

    $sql  = "SELECT role FROM users WHERE userId = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['logged_id']]);
    $role = $stmt->fetchColumn();

    if ($role === 'admin') {
        $btn .= '<li><a class="btn btn-outline" href="/admin/admin.php">Админка</a></li>';
    }
}

// Новинки (последние 3 сумки)
$newBags = 'Товары скоро появятся';
$sql     = "SELECT b.id, b.name, b.price, b.rating, b.img, br.name AS brand FROM bag b
            LEFT JOIN brand br ON b.brand_id = br.id
            ORDER BY b.id DESC LIMIT 3";
$stmt    = $pdo->query($sql);
$data    = $stmt->fetchAll();

if (!empty($data)) {
    $newBags = '';
    foreach ($data as $bag) {
        $newBags .= '
        <div class="card product-card fade-up">
            <div class="product-img"><img src="/' . htmlspecialchars($bag['img']) . '" alt=""></div>
            <div class="product-info">
                <div class="product-brand">' . htmlspecialchars($bag['brand']) . '</div>
                <h3>' . htmlspecialchars($bag['name']) . '</h3>
                <div class="product-price">' . number_format($bag['price'], 0, '.', ' ') . ' ₽</div>
                <div style="font-size:0.85rem; color:var(--accent); margin-bottom:0.8rem;">★ ' . $bag['rating'] . '</div>
                <a href="/catalog/catalog.php?add_to_cart=' . $bag['id'] . '" class="btn btn-primary" style="width:100%; justify-content:center;">В корзину</a>
            </div>
        </div>';
    }
}

// Популярные сумки (с наилучшим рейтингом)
$topBags = 'Товары скоро появятся';
$sql     = "SELECT b.id, b.name, b.price, b.rating, b.img, br.name AS brand FROM bag b
            LEFT JOIN brand br ON b.brand_id = br.id
            ORDER BY b.rating DESC LIMIT 3";
$stmt    = $pdo->query($sql);
$data    = $stmt->fetchAll();

if (!empty($data)) {
    $topBags = '';
    foreach ($data as $bag) {
        $topBags .= '
        <div class="card product-card fade-up">
            <div class="product-img"><img src="/' . htmlspecialchars($bag['img']) . '" alt=""></div>
            <div class="product-info">
                <div class="product-brand">' . htmlspecialchars($bag['brand']) . '</div>
                <h3>' . htmlspecialchars($bag['name']) . '</h3>
                <div class="product-price">' . number_format($bag['price'], 0, '.', ' ') . ' ₽</div>
                <div style="font-size:0.85rem; color:var(--accent); margin-bottom:0.8rem;">★ ' . $bag['rating'] . '</div>
                <a href="/catalog/catalog.php?add_to_cart=' . $bag['id'] . '" class="btn btn-primary" style="width:100%; justify-content:center;">В корзину</a>
            </div>
        </div>';
    }
}

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
  <title>DrunOwens | Интернет-магазин женских сумок</title>
  <meta name="description" content="DrunOwens — интернет-магазин женских сумок. Кожаные сумки, клатчи, шоперы от ведущих брендов.">
  <link rel="stylesheet" href="/css/style.css">
</head>
<body>

  <header id="header">
    <div class="container">
      <nav>
        <a href="/index.php" class="logo">DrunOwens</a>
        <ul class="nav-links">
          <li><a href="#new">Новинки</a></li>
          <li><a href="#popular">Популярное</a></li>
          <li><a href="/catalog/catalog.php">Каталог</a></li>
          <li><a href="#contact">Контакты</a></li>
          <?php echo $btn; ?>
        </ul>
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <h1 class="fade-up">Стиль начинается<br><span>с правильной сумки</span></h1>
      <p class="fade-up">Откройте мир эксклюзивных женских сумок от независимых дизайнеров и ведущих брендов. Кожа, замша, текстиль — найдите свой образ.</p>
      <div class="hero-actions fade-up">
        <a href="/catalog/catalog.php" class="btn btn-primary">Смотреть каталог</a>
        <a href="#new" class="btn btn-outline">Новинки сезона</a>
      </div>
    </div>
  </section>

  <!-- FEATURES -->
  <section id="features" style="background: var(--surface);">
    <div class="container">
      <div class="section-title fade-up">
        <h2>Почему выбирают нас</h2>
        <p>Уникальный ассортимент и высокий сервис</p>
      </div>
      <div class="flex-row">
        <div class="card feature-card fade-up">
          <div class="feature-icon">🏅</div>
          <h3>Качественные материалы</h3>
          <p>Только натуральная кожа, замша и качественный текстиль</p>
        </div>
        <div class="card feature-card fade-up">
          <div class="feature-icon">🚚</div>
          <h3>Быстрая доставка</h3>
          <p>Доставка по всей России за 2–5 рабочих дней</p>
        </div>
        <div class="card feature-card fade-up">
          <div class="feature-icon">🔄</div>
          <h3>Лёгкий возврат</h3>
          <p>Возврат товара в течение 14 дней без объяснений</p>
        </div>
        <div class="card feature-card fade-up">
          <div class="feature-icon">💎</div>
          <h3>Эксклюзивные модели</h3>
          <p>Уникальные сумки от независимых дизайнеров</p>
        </div>
      </div>
    </div>
  </section>

  <!-- NEW ARRIVALS -->
  <section id="new">
    <div class="container">
      <div class="section-title fade-up">
        <h2>Новинки сезона</h2>
        <p>Свежие поступления от наших партнёров</p>
      </div>
      <div class="flex-row flex-row-3">
        <?php echo $newBags; ?>
      </div>
      <div style="text-align:center; margin-top:2rem;">
        <a href="/catalog/catalog.php" class="btn btn-primary">Смотреть все новинки</a>
      </div>
    </div>
  </section>

  <section id="popular" style="background: var(--surface);">
    <div class="container">
      <div class="section-title fade-up">
        <h2>Хиты продаж</h2>
        <p>Самые популярные модели по оценкам покупательниц</p>
      </div>
      <div class="flex-row flex-row-3">
        <?php echo $topBags; ?>
      </div>
      <div style="text-align:center; margin-top:2rem;">
        <a href="/catalog/catalog.php" class="btn btn-primary">Весь каталог</a>
      </div>
    </div>
  </section>

  <!-- CONTACT -->
  <section id="contact">
    <div class="container">
      <div class="contact-grid">
        <div class="fade-up">
          <h2>Остались вопросы?</h2>
          <p style="color:var(--text-muted); margin-bottom:2rem;">Напишите нам — ответим в течение часа и поможем с выбором идеальной сумки.</p>
          <div style="margin-top:2rem; line-height:2;">
            <p>+7 (999) 123-45-67</p>
            <p>info@drunowens.ru</p>
            <p>г. Ижевск, ул. Модная, 8</p>
            <p>Пн–Пт: 9:00 – 21:00</p>
          </div>
        </div>
        <form class="card fade-up" id="contact-form">
          <div class="form-group"><label>Ваше имя</label><input type="text" required></div>
          <div class="form-group"><label>Телефон или Email</label><input type="text" required></div>
          <div class="form-group"><label>Ваш вопрос</label><textarea rows="3"></textarea></div>
          <button type="submit" class="btn btn-primary" style="width:100%">Отправить</button>
        </form>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <div class="footer-links">
        <a href="#">Политика конфиденциальности</a>
        <a href="/catalog/catalog.php">Каталог</a>
        <a href="/basket/basket.php">Доставка и оплата</a>
      </div>
      <p style="color:#a89080;">© 2026 DrunOwens. Все права защищены.</p>
    </div>
  </footer>

  <script src="/js/index.js"></script>
</body>
</html>
