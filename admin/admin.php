<?php
session_start();
require_once '../database/db.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: /authorization/loginPage.php");
    exit;
}
$stmt = $pdo->prepare("SELECT role FROM users WHERE userId = ?");
$stmt->execute([$_SESSION['logged_id']]);
$role = $stmt->fetchColumn();
if ($role !== 'admin') { header("Location: /index.php"); exit; }

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Удаление товара
if (isset($_GET['delete_bag'])) {
    $id = (int)$_GET['delete_bag'];
    $imgRow = $pdo->prepare("SELECT img FROM bag WHERE id = ?");
    $imgRow->execute([$id]);
    $imgPath = $imgRow->fetchColumn();
    $pdo->prepare("DELETE FROM basket_bag WHERE bag_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM bag_info WHERE bag_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM bag WHERE id = ?")->execute([$id]);
    if ($imgPath && strpos($imgPath, 'img/') === 0) {
        $f = $_SERVER['DOCUMENT_ROOT'] . '/' . $imgPath;
        if (file_exists($f)) unlink($f);
    }
    $_SESSION['massage'] = "Товар удалён.";
    header("Location: /admin/admin.php"); exit;
}

// Удаление бренда
if (isset($_GET['delete_brand'])) {
    $id = (int)$_GET['delete_brand'];
    $used = $pdo->prepare("SELECT COUNT(*) FROM bag WHERE brand_id = ?");
    $used->execute([$id]);
    if ($used->fetchColumn() > 0) {
        $_SESSION['massage'] = "Нельзя удалить бренд — есть товары с этим брендом.";
    } else {
        $pdo->prepare("DELETE FROM brand WHERE id = ?")->execute([$id]);
        $_SESSION['massage'] = "Бренд удалён.";
    }
    header("Location: /admin/admin.php"); exit;
}

// Добавление бренда
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $brandName = trim($_POST['brand_name']);
    if ($brandName) {
        $exists = $pdo->prepare("SELECT COUNT(*) FROM brand WHERE name = ?");
        $exists->execute([$brandName]);
        if ($exists->fetchColumn() > 0) {
            $_SESSION['massage'] = "Такой бренд уже существует.";
        } else {
            $pdo->prepare("INSERT INTO brand (name) VALUES (?)")->execute([$brandName]);
            $_SESSION['massage'] = "Бренд добавлен!";
        }
    }
    header("Location: /admin/admin.php"); exit;
}

// Добавление товара с загрузкой фото
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_bag'])) {
    $name     = trim($_POST['name']);
    $price    = (float)$_POST['price'];
    $brand_id = (int)$_POST['brand_id'];
    $desc     = trim($_POST['description']);
    $imgPath  = '';

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['photo'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $_SESSION['massage'] = "Ошибка: допустимые форматы — jpg, png, webp, gif.";
            header("Location: /admin/admin.php"); exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['massage'] = "Ошибка: файл слишком большой (макс. 5 МБ).";
            header("Location: /admin/admin.php"); exit;
        }
        $filename = 'bag_' . time() . '_' . mt_rand(100,999) . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $imgPath = 'img/' . $filename;
        } else {
            $_SESSION['massage'] = "Ошибка загрузки файла. Проверь права на папку /img/.";
            header("Location: /admin/admin.php"); exit;
        }
    }

    $pdo->prepare("INSERT INTO bag (name, price, rating, img, brand_id) VALUES (?, ?, 0, ?, ?)")
        ->execute([$name, $price, $imgPath, $brand_id]);
    $bagId = $pdo->lastInsertId();
    if ($desc) {
        $pdo->prepare("INSERT INTO bag_info (bag_id, title, description) VALUES (?, ?, ?)")
            ->execute([$bagId, $name, $desc]);
    }
    $_SESSION['massage'] = "Товар добавлен!";
    header("Location: /admin/admin.php"); exit;
}

$bags   = $pdo->query("SELECT b.id, b.name, b.price, b.rating, b.img, br.name AS brand FROM bag b LEFT JOIN brand br ON b.brand_id = br.id ORDER BY b.id DESC")->fetchAll();
$brands = $pdo->query("SELECT id, name, (SELECT COUNT(*) FROM bag WHERE brand_id = brand.id) AS cnt FROM brand ORDER BY name")->fetchAll();
$users  = $pdo->query("SELECT userId, email, role FROM users ORDER BY userId")->fetchAll();

$message = '';
if (isset($_SESSION['massage'])) { $message = $_SESSION['massage']; unset($_SESSION['massage']); }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Админка | DrunOwens</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    body{padding-top:80px;background:var(--bg);}
    .admin-wrap{max-width:1200px;margin:0 auto;padding:2rem 1.5rem;}
    .tabs{display:flex;gap:.5rem;margin-bottom:2rem;flex-wrap:wrap;}
    .tab-btn{padding:.6rem 1.2rem;border:1px solid rgba(139,94,60,.2);border-radius:8px;background:var(--card);color:var(--text-muted);cursor:pointer;font-size:14px;font-weight:500;font-family:inherit;transition:.2s;}
    .tab-btn.active{background:var(--primary);color:#fff;border-color:var(--primary);}
    .tab-btn:hover:not(.active){border-color:var(--primary);color:var(--primary);}
    .tab-panel{display:none;}.tab-panel.active{display:block;}
    .two-col{display:grid;grid-template-columns:380px 1fr;gap:2rem;align-items:start;}
    .section-card{background:var(--card);border-radius:var(--radius);padding:1.5rem;box-shadow:var(--shadow);border:1px solid rgba(139,94,60,.08);}
    .section-card h2{font-size:1.1rem;font-weight:600;margin-bottom:1.2rem;color:var(--text);}
    .fg{margin-bottom:.9rem;}
    .fg label{display:block;font-size:13px;font-weight:500;margin-bottom:5px;color:var(--text-muted);}
    .fg input,.fg select,.fg textarea{width:100%;padding:.65rem .8rem;background:var(--surface);border:1px solid rgba(139,94,60,.18);border-radius:8px;color:var(--text);font-family:inherit;font-size:14px;outline:none;transition:.2s;box-sizing:border-box;}
    .fg input:focus,.fg select:focus,.fg textarea:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(139,94,60,.1);}
    .upload-zone{border:2px dashed rgba(139,94,60,.3);border-radius:10px;padding:1.5rem;text-align:center;cursor:pointer;transition:.2s;background:var(--surface);position:relative;}
    .upload-zone:hover,.upload-zone.dragover{border-color:var(--primary);background:rgba(139,94,60,.05);}
    .upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
    .upload-icon{font-size:2rem;margin-bottom:.5rem;}
    .upload-zone p{font-size:13px;color:var(--text-muted);margin:0;}
    .upload-hint{font-size:11px;color:var(--text-muted);margin-top:.3rem;}
    #preview-wrap{margin-top:.8rem;display:none;}
    #preview-wrap img{max-height:140px;border-radius:8px;border:1px solid rgba(139,94,60,.15);}
    #preview-name{font-size:12px;color:var(--text-muted);margin-top:.3rem;}
    .dt{width:100%;border-collapse:collapse;font-size:14px;}
    .dt th{text-align:left;padding:10px 12px;background:var(--surface);font-weight:600;font-size:12px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);border-bottom:2px solid rgba(139,94,60,.1);}
    .dt td{padding:10px 12px;border-bottom:1px solid rgba(139,94,60,.07);vertical-align:middle;}
    .dt tr:hover td{background:rgba(139,94,60,.03);}
    .bag-thumb{width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid rgba(139,94,60,.1);}
    .bag-ph{width:48px;height:48px;border-radius:6px;background:var(--surface);display:inline-flex;align-items:center;justify-content:center;font-size:1.5rem;}
    .del-btn{color:#dc2626;text-decoration:none;font-size:13px;padding:4px 8px;border-radius:6px;transition:.15s;}
    .del-btn:hover{background:rgba(220,38,38,.1);}
    .bc{display:inline-block;background:rgba(139,94,60,.12);color:var(--primary);font-size:11px;font-weight:600;padding:2px 7px;border-radius:10px;}
    .role-admin{color:var(--primary);font-weight:600;}
    .msg{padding:10px 16px;border-radius:8px;margin-bottom:1.5rem;font-size:14px;}
    .msg.ok{background:#d4edda;border:1px solid #28a745;color:#155724;}
    .msg.err{background:#f8d7da;border:1px solid #dc3545;color:#721c24;}
    @media(max-width:900px){.two-col{grid-template-columns:1fr;}}
  </style>
</head>
<body>
  <header id="header" class="scrolled">
    <div class="container">
      <nav>
        <a href="/index.php" class="logo">DrunOwens</a>
        <ul class="nav-links">
          <li><a href="/index.php">Главная</a></li>
          <li><a href="/catalog/catalog.php">Каталог</a></li>
          <li><a href="/profile/profile.php">Профиль</a></li>
          <li><a href="/profile/exit.php" class="btn btn-outline">Выйти</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <div class="admin-wrap">
    <h1 style="font-size:2rem;color:var(--text);margin-bottom:.3rem;">Панель администратора</h1>
    <p style="color:var(--text-muted);margin-bottom:2rem;">DrunOwens — управление магазином</p>

    <?php if ($message):
      $isErr = mb_strpos($message,'Ошибка')!==false || mb_strpos($message,'Нельзя')!==false || mb_strpos($message,'существует')!==false; ?>
      <div class="msg <?php echo $isErr?'err':'ok'; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <div class="tabs">
      <button class="tab-btn active" onclick="showTab('bags',this)">Товары (<?php echo count($bags); ?>)</button>
      <button class="tab-btn" onclick="showTab('brands',this)">Бренды (<?php echo count($brands); ?>)</button>
      <button class="tab-btn" onclick="showTab('users',this)">Пользователи (<?php echo count($users); ?>)</button>
    </div>

    <!-- ТОВАРЫ -->
    <div class="tab-panel active" id="tab-bags">
      <div class="two-col">
        <div class="section-card">
          <h2>Добавить сумку</h2>
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="add_bag" value="1">
            <div class="fg">
              <label>Фотография товара</label>
              <div class="upload-zone" id="uploadZone">
                <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewPhoto(this)">
                <p>Нажми или перетащи фото сюда</p>
                <p class="upload-hint">JPG, PNG, WEBP &mdash; макс. 5 МБ</p>
              </div>
              <div id="preview-wrap">
                <img id="preview-img" src="" alt="Превью">
                <div id="preview-name"></div>
              </div>
            </div>
            <div class="fg"><label>Название сумки</label><input name="name" type="text" placeholder="Классическая кожаная сумка" required></div>
            <div class="fg"><label>Цена (руб.)</label><input name="price" type="number" placeholder="3990" required></div>
            <div class="fg">
              <label>Бренд</label>
              <select name="brand_id">
                <?php foreach ($brands as $b): ?>
                  <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="fg"><label>Описание (необязательно)</label><textarea name="description" rows="3" placeholder="Краткое описание..."></textarea></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Добавить товар</button>
          </form>
        </div>

        <div class="section-card">
          <h2>Все товары</h2>
          <?php if (empty($bags)): ?>
            <p style="color:var(--text-muted);text-align:center;padding:2rem 0;">Товаров пока нет</p>
          <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="dt">
              <tr><th>Фото</th><th>Название</th><th>Бренд</th><th>Цена</th><th>&#9733;</th><th></th></tr>
              <?php foreach ($bags as $bag): ?>
                <tr>
                  <td>
                    <?php $f = $_SERVER['DOCUMENT_ROOT'].'/'.$bag['img'];
                    if ($bag['img'] && file_exists($f)): ?>
                      <img class="bag-thumb" src="/<?php echo htmlspecialchars($bag['img']); ?>" alt="">
                    <?php else: ?>
                      <div class="bag-ph">&#128084;</div>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($bag['name']); ?></td>
                  <td><?php echo htmlspecialchars($bag['brand']); ?></td>
                  <td><?php echo number_format($bag['price'],0,'.',' '); ?> руб.</td>
                  <td><?php echo $bag['rating']; ?></td>
                  <td><a href="/admin/admin.php?delete_bag=<?php echo $bag['id']; ?>" class="del-btn" onclick="return confirm('Удалить?')">Удалить</a></td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- БРЕНДЫ -->
    <div class="tab-panel" id="tab-brands">
      <div class="two-col">
        <div class="section-card">
          <h2>Добавить бренд</h2>
          <form method="POST">
            <input type="hidden" name="add_brand" value="1">
            <div class="fg"><label>Название бренда</label><input name="brand_name" type="text" placeholder="Например: FURLA" required></div>
            <button type="submit" class="btn btn-primary" style="width:100%;">Добавить бренд</button>
          </form>
        </div>
        <div class="section-card">
          <h2>Все бренды</h2>
          <?php if (empty($brands)): ?>
            <p style="color:var(--text-muted);">Брендов пока нет</p>
          <?php else: ?>
          <table class="dt">
            <tr><th>#</th><th>Название</th><th>Товаров</th><th></th></tr>
            <?php foreach ($brands as $b): ?>
              <tr>
                <td><?php echo $b['id']; ?></td>
                <td><?php echo htmlspecialchars($b['name']); ?></td>
                <td><span class="bc"><?php echo $b['cnt']; ?></span></td>
                <td>
                  <?php if ($b['cnt'] == 0): ?>
                    <a href="/admin/admin.php?delete_brand=<?php echo $b['id']; ?>" class="del-btn" onclick="return confirm('Удалить бренд?')">Удалить</a>
                  <?php else: ?>
                    <span style="font-size:12px;color:var(--text-muted);">есть товары</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </table>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- ПОЛЬЗОВАТЕЛИ -->
    <div class="tab-panel" id="tab-users">
      <div class="section-card">
        <h2>Все пользователи</h2>
        <div style="overflow-x:auto;">
          <table class="dt">
            <tr><th>#</th><th>Email</th><th>Роль</th></tr>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?php echo $u['userId']; ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td class="<?php echo $u['role']==='admin'?'role-admin':''; ?>"><?php echo $u['role']; ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="/js/index.js"></script>
  <script>
    function showTab(name, btn) {
      document.querySelectorAll('.tab-panel').forEach(function(p){p.classList.remove('active');});
      document.querySelectorAll('.tab-btn').forEach(function(b){b.classList.remove('active');});
      document.getElementById('tab-'+name).classList.add('active');
      btn.classList.add('active');
    }
    function previewPhoto(input) {
      var wrap=document.getElementById('preview-wrap');
      var img=document.getElementById('preview-img');
      var name=document.getElementById('preview-name');
      if (input.files && input.files[0]) {
        var reader=new FileReader();
        reader.onload=function(e){img.src=e.target.result;name.textContent=input.files[0].name+' ('+(input.files[0].size/1024).toFixed(0)+' КБ)';wrap.style.display='block';};
        reader.readAsDataURL(input.files[0]);
      }
    }
    var zone=document.getElementById('uploadZone');
    if(zone){
      zone.addEventListener('dragover',function(e){e.preventDefault();zone.classList.add('dragover');});
      zone.addEventListener('dragleave',function(){zone.classList.remove('dragover');});
      zone.addEventListener('drop',function(e){e.preventDefault();zone.classList.remove('dragover');var fi=document.getElementById('photoInput');fi.files=e.dataTransfer.files;previewPhoto(fi);});
    }
  </script>
</body>
</html>
