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

// التأكد من وجود معرف الطلب في الرابط
if (!isset($_GET['id'])) {
    header("Location: myorders.php");
    exit();
}

$order_id = intval($_GET['id']);
$user_id  = intval($_SESSION['user_id']);

// التحقق من أن الطلب يعود للمستخدم وحالته "pending"
$stmt = $conn->prepare("SELECT status FROM orders WHERE id = ? AND user_id = ?");
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found or you don't have permission to cancel this order.");
}

$order = $result->fetch_assoc();
if ($order['status'] !== 'waiting') {
    die("Only waiting orders can be cancelled.");
}
$stmt->close();

// تحديث حالة الطلب إلى 'cancelled'
$stmt_update = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ?");
if (!$stmt_update) {
    die("Error preparing update statement: " . $conn->error);
}
$stmt_update->bind_param("ii", $order_id, $user_id);
if ($stmt_update->execute()) {
    $stmt_update->close();
    $conn->close();
    $message = "تم إلغاء الطلب بنجاح.";
} else {
    $message = "Error cancelling order: ";
}
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
