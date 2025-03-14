<!DOCTYPE html>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'admin/config.php'; // ملف الاتصال بقاعدة البيانات

// إنشاء الاتصال
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// حماية CSRF: إنشاء رمز إذا لم يكن موجودًا
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// استرجاع المنتجات الخاصة بالمستخدم باستخدام عبارة تحضير
$user_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("SELECT id, name, description, price, stock_quantity, image, video, created_at FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
  </style>
</head>
<body>
<?php
require 'productsnav.php'; // ملف القوائم أو شريط التنقل
?>

<!-- محتوى الصفحة -->
<div class="container mt-4">
  <div class="row">
    <?php require 'leftnav.php'; // ملف القوائم أو شريط التنقل?>

    <!-- المحتوى الرئيسي (المركز) -->
	<div class="col-md-6">
      <div class="card mb-4">
      <center><div class="card-header">طلبات الشراء</div></center>
      <div class="card-body">
    <table class="table table-bordered table-striped">
       <thead>
         <tr>
            <th>اسم المنتج</th>
            <th>السعر</th>
            <th>الكمية</th>
            <th>الإجراءات</th>
         </tr>
       </thead>
       <tbody>
         <?php if(count($products) > 0): ?>
             <?php foreach($products as $product): ?>
                 <tr>
                   <td><?= htmlentities($product['name']) ?></td>
                   <td><?= htmlentities($product['price']) ?></td>
                   <td><?= htmlentities($product['stock_quantity']) ?></td>
                   <td>
                     <!-- زر التعديل الذي يحمل بيانات المنتج في صفاته data- -->
                     <button class="btn btn-sm btn-primary edit-btn" 
                        data-id="<?= $product['id'] ?>" 
                        data-name="<?= htmlentities($product['name']) ?>"
                        data-description="<?= htmlentities($product['description']) ?>"
                        data-price="<?= $product['price'] ?>"
                        data-stock="<?= $product['stock_quantity'] ?>"
                        >
                        <i class="fas fa-edit"></i> تعديل
                     </button>
                     <!-- زر الحذف -->
                     <a href="deleteproduct.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من حذف المنتج؟');">
                        <i class="fas fa-trash"></i> حذف
                     </a>
                   </td>
                 </tr>
             <?php endforeach; ?>
         <?php else: ?>
            <tr><td colspan="5" class="text-center">لا توجد منتجات</td></tr>
         <?php endif; ?>
       </tbody>
    </table>
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
<!-- مودال تعديل المنتج -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <form method="POST" action="updateproduct.php">
           <div class="modal-header">
             <h5 class="modal-title" id="editProductModalLabel">تعديل المنتج</h5>
             <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
           </div>
           <div class="modal-body">
             <input type="hidden" name="product_id" id="modal_product_id">
             <!-- تضمين رمز CSRF -->
             <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
             <div class="mb-3">
                <label for="modal_product_name" class="form-label">اسم المنتج</label>
                <input type="text" name="product_name" id="modal_product_name" class="form-control" required>
             </div>
             <div class="mb-3">
                <label for="modal_description" class="form-label">وصف المنتج</label>
                <textarea name="description" id="modal_description" class="form-control" rows="3" required></textarea>
             </div>
             <div class="mb-3">
                <label for="modal_price" class="form-label">السعر</label>
                <input type="number" name="price" id="modal_price" class="form-control" step="0.01" required>
             </div>
             <div class="mb-3">
                <label for="modal_stock" class="form-label">الكمية المتوفرة</label>
                <input type="number" name="stock_quantity" id="modal_stock" class="form-control" required>
             </div>
           </div>
           <div class="modal-footer">
             <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
             <button type="submit" name="update_product" class="btn btn-primary">تحديث المنتج</button>
           </div>
         </form>
      </div>
   </div>
</div>
<?php if (!empty($message)) : ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("messageModalText").innerHTML = "<?= $message ?>";
    var msgModal = new bootstrap.Modal(document.getElementById("messageModal"));
    msgModal.show();
});
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// استخدام JavaScript لالتقاط حدث النقر على أزرار التعديل وملء المودال بالبيانات
document.querySelectorAll('.edit-btn').forEach(function(button) {
    button.addEventListener('click', function() {
        var productId = this.getAttribute('data-id');
        var productName = this.getAttribute('data-name');
        var description = this.getAttribute('data-description');
        var price = this.getAttribute('data-price');
        var stock = this.getAttribute('data-stock');

        document.getElementById('modal_product_id').value = productId;
        document.getElementById('modal_product_name').value = productName;
        document.getElementById('modal_description').value = description;
        document.getElementById('modal_price').value = price;
        document.getElementById('modal_stock').value = stock;

        var editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
        editModal.show();
    });
});
</script>

</body>
</html>
