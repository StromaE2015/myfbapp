<?php
session_start();
require 'admin/config.php'; // ملف الإتصال بقاعدة البيانات

// إنشاء الاتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// استرجاع منتجات الأعضاء مع بيانات البائع (مثلاً الاسم الظاهر للبائع)
$query = "SELECT p.*, u.display_name AS seller_display FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC";
$result = $conn->query($query);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
$conn->close();
?>

<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ملفي الشخصي - Fakebook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f2f5;
    }
    .navbar {
      background-color: #1877f2;
    }
    .navbar-brand, .nav-link {
      color: white !important;
    }
    .sidebar {
      background: #fff;
      border-radius: 10px;
      padding: 15px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .profile-content {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .profile-picture {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
    }
    .product-card {
      border: 1px solid #ddd;
      border-radius: 5px;
      padding: 10px;
      background: #fff;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      margin-bottom: 20px;
    }
    .product-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 5px;
    }
  </style>
</head>
<body>
<?php
require 'productsnav.php'; // ملف القوائم أو شريط التنقل
?>

<!-- محتوى الصفحة -->
<div class="container mt-4">
  <div class="row">
    <!-- الشريط الجانبي الأيسر -->
    <?php require 'leftnav.php'; // ملف القوائم أو شريط التنقل?>

    <!-- المحتوى الرئيسي (المركز) -->
    <div class="col-md-6">
      <div class="container mt-4">
        <center><h3 class="mb-4">منتجات الأعضاء</h3></center>
        <div class="row">
          <?php if (count($products) > 0): ?>
            <?php foreach ($products as $product): ?>
              <div class="col-md-4">
                <div class="product-card">
                  <?php if (!empty($product['image'])): ?>
                    <img src="uploads/<?= htmlentities($product['image']) ?>" alt="<?= htmlentities($product['name']) ?>" class="product-image">
                  <?php else: ?>
                    <img src="uploads/default_product.jpg" alt="صورة افتراضية" class="product-image">
                  <?php endif; ?>
                  <h5 class="mt-2"><?= htmlentities($product['name']) ?></h5>
                  <p class="mb-1 text-muted">البائع: <?= htmlentities($product['seller_display']) ?></p>
                  <p><strong>السعر:</strong> <?= htmlentities($product['price']) ?> جنيه</p>
                  <a href="productdetails.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">عرض التفاصيل</a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12">
              <p class="text-center">لا توجد منتجات مضافة حتى الآن.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
