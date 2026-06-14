<?php
session_start();
require_once '../database/db.php';

$brand_filter = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$sort         = isset($_GET['sort']) ? $_GET['sort'] : 'id_desc';

// Получаем максимальную цену из БД
$maxPriceRow = $pdo->query("SELECT CEIL(MAX(price) / 1000) * 1000 AS max_p FROM bag")->fetch();
$dbMaxPrice  = $maxPriceRow ? (int)$maxPriceRow['max_p'] : 10000;
if ($dbMaxPrice < 1000) $dbMaxPrice = 1000;

$price_max = isset($_GET['price_max']) ? (int)$_GET['price_max'] : $dbMaxPrice;

$navBtn = '<li><a class="btn btn-outline" href="/authorization/loginPage.php">Войти / Регистрация</a></li>';
if (isset($_SESSION['logged_in'])) {
    $navBtn = '<li><a class="btn btn-outline" href="/profile/profile.php">Профиль</a></li>';
    $navBtn .= '<li><a class="btn btn-primary" href="/basket/basket.php">Корзина</a></li>';
    $stmt = $pdo->prepare("SELECT role FROM users WHERE userId = ?");
    $stmt->execute([$_SESSION['logged_id']]);
    if ($stmt->fetchColumn() === 'admin') {
        $navBtn .= '<li><a class="btn btn-outline" href="/admin/admin.php">Админка</a></li>';
    }
}

$brands = $pdo->query("SELECT * FROM brand")->fetchAll();

if ($sort === 'price_asc') $orderBy = 'b.price ASC';
elseif ($sort === 'price_desc') $orderBy = 'b.price DESC';
elseif ($sort === 'rating') $orderBy = 'b.rating DESC';
else $orderBy = 'b.id DESC';

$where  = "WHERE b.price <= ?";
$params = [$price_max];
if ($brand_filter > 0) { $where .= " AND b.brand_id = ?"; $params[] = $brand_filter; }

$sql  = "SELECT b.id, b.name, b.price, b.rating, b.img, br.name AS brand, bi.description FROM bag b LEFT JOIN brand br ON b.brand_id = br.id LEFT JOIN bag_info bi ON bi.bag_id=b.id $where ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bags = $stmt->fetchAll();

if (isset($_GET['add_to_cart'])) {
    if (!isset($_SESSION['logged_in'])) { header("Location: /authorization/loginPage.php"); exit; }
    $bagId  = (int)$_GET['add_to_cart'];
    $userId = $_SESSION['logged_id'];
    $basket = $pdo->prepare("SELECT id FROM basket WHERE user_id = ?");
    $basket->execute([$userId]);
    $basketRow = $basket->fetch();
    if (!$basketRow) {
        $pdo->prepare("INSERT INTO basket (user_id) VALUES (?)")->execute([$userId]);
        $basketId = $pdo->lastInsertId();
    } else { $basketId = $basketRow['id']; }
    $exists = $pdo->prepare("SELECT id FROM basket_bag WHERE basket_id = ? AND bag_id = ?");
    $exists->execute([$basketId, $bagId]);
    if ($exists->fetch()) {
        $pdo->prepare("UPDATE basket_bag SET quantity = quantity + 1 WHERE basket_id = ? AND bag_id = ?")->execute([$basketId, $bagId]);
    } else {
        $pdo->prepare("INSERT INTO basket_bag (basket_id, bag_id) VALUES (?, ?)")->execute([$basketId, $bagId]);
    }
    $_SESSION['massage'] = "Товар добавлен в корзину!";
    header("Location: /catalog/catalog.php"); exit;
}

$message = '';
if (isset($_SESSION['massage'])) { $message = $_SESSION['massage']; unset($_SESSION['massage']); }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Каталог сумок | DrunOwens</title>
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="/css/pages/catalog.css">
</head>
<body>
  <header id="header">
    <div class="container">
      <nav>
        <a href="/index.php" class="logo">DrunOwens</a>
        <ul class="nav-links">
          <li><a href="/index.php#new">Новинки</a></li>
          <li><a href="/index.php#popular">Популярное</a></li>
          <li><a href="/catalog/catalog.php">Каталог</a></li>
          <li><a href="/index.php#contact">Контакты</a></li>
          <?php echo $navBtn; ?>
        </ul>
        <button class="menu-toggle" onclick="toggleMenu()">☰</button>
      </nav>
    </div>
  </header>

  <div class="page-header">
    <div class="container">
      <h1>Каталог сумок</h1>
      <p><?php echo count($bags); ?> товаров в наличии</p>
      <?php if ($message): ?><p style="color:#16a34a;margin-top:0.5rem;"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
    </div>
  </div>

  <div class="container">
    <div class="catalog-layout">
      <aside class="filters">
        <h3>Фильтры</h3>
        <form method="GET" action="/catalog/catalog.php">
          <div class="filter-group">
            <label>Бренд</label>
            <select name="brand">
              <option value="0">Все бренды</option>
              <?php foreach ($brands as $b): ?>
                <option value="<?php echo $b['id']; ?>" <?php echo $brand_filter==$b['id']?'selected':''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="filter-group">
            <label>Макс. цена: <span id="priceVal"><?php echo ($price_max>=$dbMaxPrice ? number_format($dbMaxPrice,0,'.',' ') : number_format($price_max,0,'.',' ')); ?> руб.</span></label>
            <input type="range" name="price_max" min="0" max="<?php echo $dbMaxPrice; ?>" step="<?php echo max(100, (int)($dbMaxPrice/20)); ?>" value="<?php echo min($price_max,$dbMaxPrice); ?>" oninput="document.getElementById('priceVal').textContent=Number(this.value).toLocaleString('ru-RU')+' руб.'">
          </div>
          <div class="filter-group">
            <label>Сортировка</label>
            <select name="sort">
              <option value="id_desc"    <?php echo $sort==='id_desc'   ?'selected':''; ?>>Сначала новые</option>
              <option value="price_asc"  <?php echo $sort==='price_asc' ?'selected':''; ?>>Сначала дешевые</option>
              <option value="price_desc" <?php echo $sort==='price_desc'?'selected':''; ?>>Сначала дорогие</option>
              <option value="rating"     <?php echo $sort==='rating'    ?'selected':''; ?>>По рейтингу</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%">Применить</button>
          <a href="/catalog/catalog.php" style="display:block;text-align:center;margin-top:0.8rem;font-size:0.9rem;color:var(--text-muted);">Сбросить</a>
        </form>
      </aside>

      <div class="bags-grid">
        <?php if (empty($bags)): ?>
          <div class="empty-msg"><p style="font-size:3rem;">&#128269;</p><p>Ничего не найдено</p></div>
        <?php else: ?>
          <?php foreach ($bags as $bag): ?>
            <div class="bag-card"
              data-name="<?php echo htmlspecialchars($bag['name']); ?>"
              data-brand="<?php echo htmlspecialchars($bag['brand']); ?>"
              data-price="<?php echo number_format($bag['price'],0,'.',' '); ?> руб."
              data-description="<?php echo htmlspecialchars($bag['description'] ?? ''); ?>"
              data-img="/<?php echo htmlspecialchars($bag['img']); ?>"
              data-id="<?php echo $bag['id']; ?>"
              data-rating="<?php echo $bag['rating']; ?>">
              <div class="bag-img" onclick="openBagModal(this.closest('.bag-card'))" style="cursor:pointer;">
                <?php $imgFull = $_SERVER['DOCUMENT_ROOT'].'/'.$bag['img'];
                if ($bag['img'] && file_exists($imgFull)): ?>
                  <img src="/<?php echo htmlspecialchars($bag['img']); ?>" alt="<?php echo htmlspecialchars($bag['name']); ?>">
                <?php else: ?>
                  <span style="font-size:3.5rem;">&#128084;</span>
                <?php endif; ?>
              </div>
              <div class="bag-info">
                <div class="bag-brand"><?php echo htmlspecialchars($bag['brand']); ?></div>
                <h3 onclick="openBagModal(this.closest('.bag-card'))" style="cursor:pointer;"><?php echo htmlspecialchars($bag['name']); ?></h3>
                <div class="bag-price"><?php echo number_format($bag['price'],0,'.',' '); ?> &#8381;</div>
                <div class="bag-rating">&#9733; <?php echo $bag['rating']; ?></div>
                <a href="/catalog/catalog.php?add_to_cart=<?php echo $bag['id']; ?>" class="btn btn-primary" style="width:100%;justify-content:center;" onclick="event.stopPropagation();">В корзину</a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <footer>
    <div class="container">
      <div class="footer-links"><a href="#">Конфиденциальность</a><a href="/index.php">Главная</a><a href="#">Доставка и оплата</a></div>
      <p style="color:#a89080;margin-top:0.5rem;">&#169; 2026 DrunOwens. Все права защищены.</p>
    </div>
  </footer>
  <script>
    const header=document.getElementById('header');
    window.addEventListener('scroll',function(){header.classList.toggle('scrolled',window.scrollY>50);});
    function toggleMenu(){var nav=document.querySelector('.nav-links');if(nav)nav.classList.toggle('open');}
  </script>

<!-- МОДАЛЬНОЕ ОКНО ТОВАРА -->
<div id="bagModal" onclick="if(event.target===this)closeBagModal()"
  style="display:none;position:fixed;inset:0;background:rgba(45,31,20,0.6);z-index:9999;overflow-y:auto;padding:2rem 1rem;">
  <div style="max-width:860px;margin:0 auto;background:#fff;border-radius:16px;overflow:hidden;position:relative;display:flex;flex-wrap:wrap;">
    <button onclick="closeBagModal()"
      style="position:absolute;right:14px;top:12px;font-size:26px;border:none;background:none;cursor:pointer;color:#7a6655;line-height:1;z-index:2;">&#215;</button>
    <!-- фото -->
    <div style="width:45%;min-height:380px;background:#f0ece6;flex-shrink:0;">
      <img id="mImg" src="" alt=""
        style="width:100%;height:100%;min-height:380px;object-fit:cover;display:block;">
    </div>
    <!-- инфо -->
    <div style="flex:1;padding:2rem;display:flex;flex-direction:column;gap:0.6rem;min-width:260px;">
      <div id="mBrand" style="font-size:0.85rem;color:#7a6655;text-transform:uppercase;letter-spacing:0.05em;"></div>
      <h2 id="mName" style="font-size:1.6rem;font-weight:800;color:#2d1f14;margin:0;"></h2>
      <div id="mPrice" style="font-size:1.5rem;font-weight:700;color:#8b5e3c;"></div>
      <div id="mRating" style="font-size:0.9rem;color:#c9956a;"></div>
      <hr style="border:none;border-top:1px solid rgba(139,94,60,0.12);margin:0.5rem 0;">
      <p style="font-size:0.9rem;font-weight:600;color:#2d1f14;margin:0;">Описание</p>
      <p id="mDesc" style="font-size:0.9rem;color:#7a6655;line-height:1.6;margin:0;"></p>
      <div style="margin-top:auto;padding-top:1rem;">
        <a id="mCartBtn" href="#"
          style="display:block;text-align:center;padding:0.9rem;background:#8b5e3c;color:#fff;border-radius:10px;font-weight:600;font-size:1rem;text-decoration:none;">
          В корзину
        </a>
      </div>
    </div>
  </div>
</div>

<style>
  @media(max-width:600px){
    #bagModal > div { flex-direction:column; }
    #bagModal > div > div:first-child { width:100%; min-height:220px; }
  }
</style>

<script>
function openBagModal(el) {
  var modal = document.getElementById('bagModal');
  var img   = document.getElementById('mImg');
  var imgSrc = el.dataset.img;
  if (imgSrc && imgSrc !== '/') {
    img.src = imgSrc;
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }
  document.getElementById('mBrand').textContent  = el.dataset.brand || '';
  document.getElementById('mName').textContent   = el.dataset.name  || '';
  document.getElementById('mPrice').textContent  = el.dataset.price || '';
  document.getElementById('mRating').textContent = el.dataset.rating ? ('★ ' + el.dataset.rating) : '';
  document.getElementById('mDesc').textContent   = el.dataset.description || 'Описание отсутствует.';
  document.getElementById('mCartBtn').href       = '/catalog/catalog.php?add_to_cart=' + el.dataset.id;
  modal.style.display = 'block';
  document.body.style.overflow = 'hidden';
}
function closeBagModal() {
  document.getElementById('bagModal').style.display = 'none';
  document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeBagModal(); });
</script>
</body>
</html>
