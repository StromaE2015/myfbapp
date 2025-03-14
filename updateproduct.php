<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'admin/config.php'; // ملف الإتصال بقاعدة البيانات

// إنشاء الاتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// حماية CSRF: إنشاء رمز إذا لم يكن موجودًا
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$product = null;
$product_id = 0;

// التحقق من وجود معرف المنتج في الرابط
if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    // استخدام عبارة تحضير لمنع SQL Injection
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        die("المنتج غير موجود أو ليس لديك صلاحية الوصول إليه.");
    }
}

// عملية تحديث المنتج عند إرسال النموذج
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    // التحقق من رمز CSRF
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Invalid CSRF token");
    }
    
    // تنظيف المدخلات
    $product_id   = intval($_POST['product_id']);
    $product_name = trim(htmlspecialchars($_POST['product_name']));
    $description  = trim(htmlspecialchars($_POST['description']));
    $price        = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    
    // استخدام عبارة تحضير لتحديث المنتج
    $stmt_update = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ? WHERE id = ? AND user_id = ?");
    if (!$stmt_update) {
        die("Error preparing update statement: " . $conn->error);
    }
    $stmt_update->bind_param("ssdiii", $product_name, $description, $price, $stock_quantity, $product_id, $_SESSION['user_id']);
    if ($stmt_update->execute()) {
        $message = "تم تحديث المنتج بنجاح!";
    } else {
        $message = "خطأ أثناء تحديث المنتج: " . $conn->error;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>تعديل المنتج - Fakebook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f0f2f5;
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
  </style>
</head>
<body>



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
    var msgModal = new bootstrap.Modal(document.getElementById("messageModal"));
    document.getElementById("messageModalText").innerHTML = "<?= htmlentities($message) ?>";
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
