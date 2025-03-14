<?php
// File: my_orders.php
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

$user_id = intval($_SESSION['user_id']);

// استرجاع الطلبات الخاصة بالمستخدم مع بيانات المنتج
$query = "SELECT o.id, o.quantity, o.total_price, o.status, o.created_at, p.name AS product_name 
          FROM orders o 
          JOIN products p ON o.product_id = p.id 
          WHERE o.user_id = ? 
          ORDER BY o.created_at DESC";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
$stmt->close();
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
      <center><div class="card-header">طلبات الشراء</div></center>
      <div class="card-body">
		   <?php if(count($orders) > 0): ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>اسم المنتج</th>
                    <th>الكمية</th>
                    <th>السعر الإجمالي</th>
                    <th>الحالة</th>
                    <th>تاريخ الطلب</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $order): ?>
                    <tr>
                        <td><?= htmlentities($order['product_name']) ?></td>
                        <td><?= htmlentities($order['quantity']) ?></td>
                        <td><?= htmlentities($order['total_price']) ?> جنيه</td>
                        <td><?= htmlentities($order['status']) ?></td>
                        <td><?= htmlentities($order['created_at']) ?></td>
                        <td>
                            <!-- يمكنك إضافة أزرار إضافية كعرض التفاصيل أو إلغاء الطلب إن كانت الحالة "pending" -->
                            <?php if($order['status'] === 'waiting'): ?>
                                <a href="cancelorder.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-warning" onclick="return confirm('هل أنت متأكد من إلغاء الطلب؟');">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">لا توجد طلبات حتى الآن.</p>
    <?php endif; ?>
      </div>
	</div>
  </div>
    <!-- الشريط الجانبي الأيمن -->
    <?php require 'chat.php'; // ملف القوائم أو شريط التنقل?>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
