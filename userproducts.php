<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'admin/config.php'; // ملف الإتصال بقاعدة البيانات

// إنشاء الاتصال
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $user_id = $_SESSION['user_id'];
    $product_name = htmlspecialchars($_POST['product_name']);
    $description = htmlspecialchars($_POST['description']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $created_at = date("Y-m-d H:i:s");

    // رفع صورة المنتج
    $product_image = "default_product.jpg";
    if (!empty($_FILES['product_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . "_" . basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                $product_image = $image_name;
            } else {
                $message .= "Error uploading image.<br>";
            }
        } else {
            $message .= "Invalid image file type.<br>";
        }
    }

    // رفع فيديو المنتج (اختياري)
    $product_video = "";
    if (!empty($_FILES['product_video']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $video_name = time() . "_" . basename($_FILES["product_video"]["name"]);
        $target_file = $target_dir . $video_name;
        $videoFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_video_types = ["mp4", "webm", "ogg"];
        if (in_array($videoFileType, $allowed_video_types)) {
            if (move_uploaded_file($_FILES["product_video"]["tmp_name"], $target_file)) {
                $product_video = $video_name;
            } else {
                $message .= "Error uploading video.<br>";
            }
        } else {
            $message .= "Invalid video file type.<br>";
        }
    }

    $stmt = $conn->prepare("INSERT INTO products (user_id, name, description, price, image, video, stock_quantity, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $message .= "Error preparing statement: " . $conn->error;
    } else {
        $stmt->bind_param("issdssis", $user_id, $product_name, $description, $price, $product_image, $product_video, $stock_quantity, $created_at);
        if ($stmt->execute()) {
            $message .= "تم إضافة المنتج بنجاح!<br>";
        } else {
            $message .= "Error adding product: " . $stmt->error . "<br>";
        }
    }
}

// استرجاع المنتجات التي أضافها المستخدم
$user_id = $_SESSION['user_id'];
$result_products = $conn->query("SELECT * FROM products WHERE user_id = $user_id ORDER BY created_at DESC");

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
        <div class="card-header">إضافة منتج جديد</div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="product_name" class="form-label">اسم المنتج</label>
              <input type="text" name="product_name" id="product_name" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="description" class="form-label">وصف المنتج</label>
              <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
            </div>
            <div class="mb-3">
              <label for="price" class="form-label">السعر</label>
              <input type="number" name="price" id="price" class="form-control" step="0.01" required>
            </div>
            <div class="mb-3">
              <label for="stock_quantity" class="form-label">الكمية المتوفرة</label>
              <input type="number" name="stock_quantity" id="stock_quantity" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="product_image" class="form-label">صورة المنتج</label>
              <input type="file" name="product_image" id="product_image" class="form-control" accept="image/*" required>
            </div>
            <div class="mb-3">
              <label for="product_video" class="form-label">فيديو المنتج (اختياري، لا يتجاوز دقيقة)</label>
              <input type="file" name="product_video" id="product_video" class="form-control" accept="video/*">
            </div>
            <button type="submit" name="add_product" class="btn btn-primary">أضف المنتج</button>
          </form>
        </div>
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
    window.location.href = 'editproduct.php';
    }, 3000);
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
