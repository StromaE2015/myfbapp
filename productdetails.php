<?php
session_start();
if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit();
}

require 'admin/config.php';
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$product_id = intval($_GET['id']);

// استرجاع بيانات المنتج مع اسم البائع
$stmt = $conn->prepare("SELECT p.*, u.display_name AS seller_name, u.id AS seller_id FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
if ($stmt === false) {
    die("Error in SQL Query (Product): " . $conn->error);
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    die("المنتج غير موجود.");
}
$stmt->close();

$product_owner_id = $product['user_id'];
$current_user_id = $_SESSION['user_id'] ?? null;
$seller_name = $product['seller_name'];
$seller_id = $product['seller_id'];

// استرجاع بيانات التقييمات
$stmt_rating = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM ratings WHERE product_id = ?");
if ($stmt_rating === false) {
    die("Error in SQL Query (Rating): " . $conn->error);
}
$stmt_rating->bind_param("i", $product_id);
$stmt_rating->execute();
$result_rating = $stmt_rating->get_result();
$rating_data = $result_rating->fetch_assoc();
$stmt_rating->close();

// استرجاع جميع التقييمات
$stmt_reviews = $conn->prepare("SELECT r.rating, r.comment, u.display_name FROM ratings r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
if ($stmt_reviews === false) {
    die("Error in SQL Query (Reviews): " . $conn->error);
}
$stmt_reviews->bind_param("i", $product_id);
$stmt_reviews->execute();
$reviews = $stmt_reviews->get_result();
$stmt_reviews->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تفاصيل المنتج - Fakebook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { background-color: #f0f2f5; }
    .navbar { background-color: #1877f2; }
    .navbar-brand, .nav-link { color: white !important; }
    .product-details, .reviews-section {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .product-image { width: 100%; max-height: 150px; object-fit: cover; border-radius: 5px; margin-bottom: 15px; cursor: pointer; }
    .review { border-bottom: 1px solid #ddd; padding: 10px 0; }
    .review:last-child { border-bottom: none; }
    .star { color: #ffc107; }
  </style>
</head>
<body>
<?php require 'productsnav.php'; ?>

<div class="container mt-4">
  <div class="row">
    <?php require 'leftnav.php'; ?>

    <div class="col-md-6">
      <div class="card mb-4">
        <div class="product-details">
          <center><h3><?= htmlentities($product['name']) ?></h3></center>
          <center><p><strong>وصف السلعة:</strong> <?= htmlentities($product['description']) ?></p></center>
          <center><p><strong>السعر:</strong> <?= htmlentities($product['price']) ?> جنيه</p></center>
          <center><p><strong>الكمية:</strong> <?= htmlentities($product['stock_quantity']) ?> <strong>قطعة</strong></p></center>
          <center><p><strong>البائع:</strong> <a href="profile.php?id=<?= $seller_id ?>" class="text-primary"> <?= htmlentities($seller_name) ?> </a></p></center>
          <?php if (!empty($product['image'])): ?>
            <center>
              <img src="uploads/<?= htmlentities($product['image']) ?>" alt="<?= htmlentities($product['name']) ?>" class="product-image" data-bs-toggle="modal" data-bs-target="#imageModal">
            </center>
          <?php else: ?>
            <center><img src="uploads/default_product.jpg" alt="صورة افتراضية" class="product-image"></center>
          <?php endif; ?>
          <?php if (!empty($product['video'])): ?>
            <video width="100%" controls>
              <source src="uploads/<?= htmlentities($product['video']) ?>" type="video/mp4">
              متصفحك لا يدعم تشغيل الفيديو.
            </video>
          <?php endif; ?>
          <hr>
          <?php if ($current_user_id !== $product_owner_id): ?>
          <center><a href="placeorder.php?id=<?= $product_id ?>" class="btn btn-primary">طلب المنتج</a></center>
          <hr>
          <?php endif; ?>
          <?php if ($current_user_id !== $product_owner_id): ?>
          <center>
            <a href="submit_review.php?id=<?= $product_id ?>" class="btn btn-warning">
              <i class="fas fa-star"></i> تقييم المنتج
            </a>
          </center>
          <hr>
          <?php endif; ?>
          <center>
            <p><strong>متوسط التقييم:</strong> <?= round($rating_data['avg_rating'], 1) ?> 
              (<?= $rating_data['review_count'] ?> تقييم)
            </p>
          </center>
        </div>
      </div>
      <div class="reviews-section">
        <center><h4>التقييمات</h4></center>
        <?php if ($reviews->num_rows > 0): ?>
          <?php while ($review = $reviews->fetch_assoc()): ?>
            <div class="review">
              <strong><?= htmlentities($review['display_name']) ?>:</strong>
              <span class="text-warning">
                <?php for ($i = 0; $i < $review['rating']; $i++): ?>
                  <i class="fas fa-star star"></i>
                <?php endfor; ?>
              </span>
              <p><?= nl2br(htmlentities($review['comment'])) ?></p>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="text-muted">لا توجد تقييمات حتى الآن.</p>
        <?php endif; ?>
      </div>
    </div>
    <?php require 'chat.php'; ?>
  </div>
</div>

<!-- مودال الصورة -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center">
        <img src="uploads/<?= htmlentities($product['image']) ?>" class="img-fluid" alt="صورة المنتج">
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
