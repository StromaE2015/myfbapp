<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'admin/config.php'; // ملف الاتصال بقاعدة البيانات

// إنشاء الاتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// حماية CSRF: إنشاء رمز إذا لم يكن موجودًا
if(empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$product = null;
if(!isset($_GET['id'])) {
    die("Invalid request: no product id.");
}
$product_id = intval($_GET['id']);

// استرجاع تفاصيل المنتج مع اسم البائع (مثلاً)
$stmt = $conn->prepare("SELECT p.*, u.display_name AS seller_name, u.id AS seller_id FROM products p JOIN users u ON p.user_id = u.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $product = $result->fetch_assoc();
} else {
    die("Product not found.");
}
$stmt->close();

// معالجة طلب الشراء عند تقديم النموذج
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['place_order'])) {
    // التحقق من رمز CSRF
    if(empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token.");
    }
    
    // تنظيف المدخلات
    $order_quantity = intval($_POST['quantity']);
    if($order_quantity <= 0) {
        $error = "Please enter a valid quantity.";
    } elseif($order_quantity > $product['stock_quantity']) {
        $error = "Ordered quantity exceeds available stock.";
    } else {
        $total_price = $order_quantity * $product['price'];
        $user_id = $_SESSION['user_id'];
        // إدراج الطلب في جدول orders
        $stmt = $conn->prepare("INSERT INTO orders (product_id, user_id, quantity, total_price, status, created_at) VALUES (?, ?, ?, ?, 'waiting', NOW())");
        $stmt->bind_param("iiid", $product_id, $user_id, $order_quantity, $total_price);
        if($stmt->execute()) {
            $message = "تم ارسال طلب الشراء الي التاجر و سيتم التواصل معك قريبا لاتمام عملية الشراء";
            
            // **إضافة سجل إشعار للبائع**
            $seller_id = $product['seller_id'];
            $notification_message = "تم طلب شراء منتج: " . $product['name'] . " بواسطة " . $_SESSION['display_name'];
            $stmt_notify = $conn->prepare("INSERT INTO notifications (user_id, message, status, created_at) VALUES (?, ?, 'unread', NOW())");
            if($stmt_notify) {
                $stmt_notify->bind_param("is", $seller_id, $notification_message);
                $stmt_notify->execute();
                $stmt_notify->close();
            }
        } else {
            $message = "Error placing order: " . $stmt->error;
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
        /* تنسيق بطاقة تفاصيل المنتج */
    .product-details {
      background: #fff;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .product-details h3 {
      font-size: 1.5rem;
      margin-bottom: 15px;
    }
    .product-details p {
      font-size: 1rem;
      margin-bottom: 10px;
    }
    /* تقليل حجم صورة المنتج */
    .product-image {
      width: 100%;
      max-height: 150px;
      object-fit: cover;
      border-radius: 5px;
      margin-bottom: 15px;
    }
    @media (max-width: 576px) {
      .product-image {
        max-height: 120px;
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
      <div class="card mb-4">
      <center><div class="card-header"><?= htmlentities($product['name']) ?></div></center>
      <div class="card-body">
          <center><p><strong>الوصف:</strong> <?= htmlentities($product['description']) ?></p></center>
          <center><p><strong>السعر:</strong> <?= htmlentities($product['price']) ?> جنيه</p></center>
          <center><p><strong>الكمية المتوفرة:</strong> <?= htmlentities($product['stock_quantity']) ?></p></center>
          <?php if(!empty($product['image'])): ?>
              <center><img src="uploads/<?= htmlentities($product['image']) ?>" alt="<?= htmlentities($product['name']) ?>" class="img-fluid mb-2"></center>
          <?php endif; ?>
          </center><?php if(!empty($product['video'])): ?>
              <center><video width="100%" controls></center>
                  <source src="uploads/<?= htmlentities($product['video']) ?>" type="video/mp4">
                  متصفحك لا يدعم تشغيل الفيديو.
              </video></center>
          <?php endif; ?>
      </div>

  
  <center><form method="POST">
      <!-- تضمين رمز CSRF -->
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
      <div class="mb-3">
          <label for="quantity" class="form-label">الكمية المطلوبة</label>
          <input type="number" name="quantity" id="quantity" class="form-control" required min="1" max="<?= htmlentities($product['stock_quantity']) ?>">
      </div>
      <button type="submit" name="place_order" class="btn btn-primary">أرسل طلب الشراء</button>
	</form></center>
	</div>
  </div>
    <!-- الشريط الجانبي الأيمن -->
    <?php require 'chat.php'; // ملف القوائم أو شريط التنقل?>

<!-- مودال الرسالة -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
         <h5 class="modal-title" id="messageModalLabel">رسالة</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
         <p id="messageModalText"></p>
      </div>
      <div class="modal-footer">
         <button type="button" class="btn btn-primary" data-bs-dismiss="modal">حسناً</button>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($message)) : ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("messageModalText").innerHTML = "<?= $message ?>";
    var msgModal = new bootstrap.Modal(document.getElementById("messageModal"));
    msgModal.show();
    setTimeout(function(){
    window.location.href = 'productdetails.php?id=<?= $product_id ?>';
    }, 3000);
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
