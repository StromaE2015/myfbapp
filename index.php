<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'admin/config.php';

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// استرجاع المنشورات (نفترض وجود جدول posts يحتوي على عمود content وcreated_at)
$posts = [];
$post_query = "SELECT p.*, u.display_name FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC";
$result = $conn->query($post_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['type'] = 'post';
        $posts[] = $row;
    }
}

// استرجاع المنتجات الحديثة (من جدول products)
$products = [];
$product_query = "SELECT p.*, u.display_name AS seller_display FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 10";
$result2 = $conn->query($product_query);
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        $row['type'] = 'product';
        $products[] = $row;
    }
}

// دمج المنشورات والمنتجات في موجز واحد
$feed = array_merge($posts, $products);
// ترتيب الموجز بحسب created_at تنازلياً
usort($feed, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>الرئيسية - Fakebook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- تضمين FontAwesome للأيقونات -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
      body {
          background-color: #f0f2f5;
      }
      .navbar {
          background-color: #1877f2;
      }
      .main-content {
          margin-top: 20px;
      }
      .feed-item {
          background: #fff;
          border-radius: 10px;
          padding: 15px;
          margin-bottom: 20px;
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
      .feed-item img {
          width: 100%;
          height: auto;
          border-radius: 5px;
      }
      .feed-item video {
          width: 100%;
          border-radius: 5px;
      }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container main-content">
  <?php if(count($feed) > 0): ?>
    <?php foreach($feed as $item): ?>
      <div class="feed-item">
        <?php if($item['type'] === 'post'): ?>
          <!-- عرض المنشور -->
          <p><strong><?= htmlentities($item['display_name']) ?>:</strong> <?= htmlentities($item['content']) ?></p>
          <small class="text-muted"><?= htmlentities($item['created_at']) ?></small>
        <?php elseif($item['type'] === 'product'): ?>
          <!-- عرض المنتج -->
          <h5><?= htmlentities($item['name']) ?></h5>
          <p><?= htmlentities($item['description']) ?></p>
          <p><strong>السعر:</strong> <?= htmlentities($item['price']) ?> ريال</p>
          <?php if(!empty($item['image'])): ?>
            <img src="uploads/<?= htmlentities($item['image']) ?>" alt="<?= htmlentities($item['name']) ?>">
          <?php endif; ?>
          <?php if(!empty($item['video'])): ?>
            <video controls>
              <source src="uploads/<?= htmlentities($item['video']) ?>" type="video/mp4">
              متصفحك لا يدعم تشغيل الفيديو.
            </video>
          <?php endif; ?>
          <small class="text-muted"><?= htmlentities($item['created_at']) ?></small>
          <br>
          <a href="productdetails.php?id=<?= $item['id'] ?>" class="btn btn-primary btn-sm mt-2">عرض التفاصيل</a>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
