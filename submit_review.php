<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'admin/config.php';

$conn = new mysqli($host, $username, $password, $db_name);
if($conn->connect_error){
    die("Connection failed: " . $conn->connect_error);
}

// حماية CSRF: إنشاء رمز إذا لم يكن موجودًا
if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$product_id = 0;
if(isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
} else {
    die("Product id not provided.");
}

// التحقق من وجود المنتج
$stmt = $conn->prepare("SELECT id, name FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows == 0) {
    die("Product not found.");
}
$product = $result->fetch_assoc();
$stmt->close();

$message = "";
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    // التحقق من رمز CSRF
    if(empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        die("Invalid CSRF token");
    }
    // تنظيف المدخلات
    $rating = intval($_POST['rating']);
    $comment = trim(htmlspecialchars($_POST['comment']));
    if($rating < 1 || $rating > 5) {
        $message = "Please select a rating between 1 and 5.";
    } else {
        $user_id = intval($_SESSION['user_id']);
        $stmt = $conn->prepare("INSERT INTO ratings (user_id, product_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);
        if($stmt->execute()){
            $message = "Review submitted successfully.";
        } else {
            $message = "Error submitting review: " . $stmt->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تقييم المنتج - <?= htmlentities($product['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body {
          background-color: #f0f2f5;
      }
      .review-container {
          max-width: 600px;
          margin: 20px auto;
          background: #fff;
          padding: 20px;
          border-radius: 10px;
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
  </style>
</head>
<body>
<?php include 'nav.php'; ?>
<div class="container review-container">
    <h3>تقييم المنتج: <?= htmlentities($product['name']) ?></h3>
    <?php if(!empty($message)): ?>
    <div class="alert alert-info"><?= htmlentities($message) ?></div>
    <?php endif; ?>
    <form method="POST">
      <!-- تضمين رمز CSRF -->
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <div class="mb-3">
         <label class="form-label">التقييم (1 إلى 5):</label>
         <select name="rating" class="form-control" required>
             <option value="">اختر التقييم</option>
             <?php for($i=1; $i<=5; $i++): ?>
             <option value="<?= $i ?>"><?= $i ?></option>
             <?php endfor; ?>
         </select>
      </div>
      <div class="mb-3">
         <label for="comment" class="form-label">التعليق:</label>
         <textarea name="comment" id="comment" rows="4" class="form-control" placeholder="اكتب تعليقك هنا..." required></textarea>
      </div>
      <button type="submit" name="submit_review" class="btn btn-primary">أرسل التقييم</button>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
