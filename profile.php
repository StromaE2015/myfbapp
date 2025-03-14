<?php
session_start();
require 'admin/config.php'; // ملف الاتصال بقاعدة البيانات

// التأكد من تمرير معرف المستخدم في الرابط
if (!isset($_GET['id'])) {
    die("لا يوجد معرف مستخدم.");
}

$profile_id = intval($_GET['id']);

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// استرجاع بيانات العضو الذي تمت زيارته
$stmt = $conn->prepare("SELECT id, name, display_name, email, phone, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    die("المستخدم غير موجود.");
}
$profile = $result->fetch_assoc();
$stmt->close();

// التحقق إذا كان الزائر هو صاحب الملف الشخصي
$isOwner = (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $profile_id);

// استعلام التحقق من وجود علاقة صداقة أو طلب صداقة قائم بين المستخدم الحالي والعضو المعروض
$friendship_exists = false;
if (isset($_SESSION['user_id']) && !$isOwner) {
    $current_user = intval($_SESSION['user_id']);
    // افترضنا أن جدول friend_requests يحمل حالات "accepted" للعلاقات القائمة
    $stmt2 = $conn->prepare("SELECT id FROM friend_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'accepted'");
    $stmt2->bind_param("iiii", $current_user, $profile_id, $profile_id, $current_user);
    $stmt2->execute();
    $stmt2->store_result();
    if ($stmt2->num_rows > 0) {
        $friendship_exists = true;
    }
    $stmt2->close();
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
  </style>
</head>
<body>
<?php
require 'nav.php'; // ملف الإتصال بقاعدة البيانات
?>



<!-- محتوى الصفحة -->
<div class="container mt-4">
  <div class="row">
    <!-- الشريط الجانبي الأيسر -->
    <div class="col-md-3">
      <div class="sidebar">
        <h5>روابط حسابي</h5>
        <ul class="list-unstyled">
          <li><a href="profile.php">ملفي الشخصي</a></li>
          <li><a href="settings.php">إعدادات الحساب</a></li>
          <li><a href="#">طلب صداقة</a></li>
          <!-- روابط إضافية -->
        </ul>
        <hr>
        <h5>إعلانات</h5>
        <p>مكان الإعلانات</p>
      </div>
    </div>
    <!-- المحتوى الرئيسي (المركز) -->
    <div class="col-md-6">
      <div class="profile-content">
        <div class="text-center">
<img src="uploads/<?= htmlentities($profile['profile_picture']) ?>" alt="<?= htmlentities($profile['display_name']) ?>" class="profile-picture mb-3">
      <h3><?= htmlentities($profile['display_name']) ?></h3>
      <p><strong>اسم المستخدم:</strong> <?= htmlentities($profile['name']) ?></p>
      <p><strong>البريد الإلكتروني:</strong> <?= htmlentities($profile['email']) ?></p>
      <p><strong>رقم الهاتف:</strong> <?= htmlentities($profile['phone']) ?></p>
      
      <?php if(isset($_SESSION['user_id']) && !$isOwner): ?>
          <?php if(!$friendship_exists): ?>
              <a href="send_friend_request.php?receiver_id=<?= $profile['id'] ?>" class="btn btn-primary add-friend-btn">
                  <i class="fas fa-user-plus"></i> إضافة صديق
              </a>
          <?php else: ?>
              <button class="btn btn-success add-friend-btn" disabled>
                  <i class="fas fa-check"></i> صديق بالفعل
              </button>
          <?php endif; ?>
      <?php endif; ?>
        </div>
        <hr>
        <h4>منشوراتي</h4>
        <p>هنا ستُعرض منشورات المستخدم الخاصة به (يمكنك ربطها من قاعدة البيانات لاحقًا)</p>
      </div>
    </div>
    <!-- الشريط الجانبي الأيمن -->
    <div class="col-md-3">
      <div class="sidebar">
        <h5>قائمة الأصدقاء</h5>
        <ul class="list-unstyled">
          <?php foreach($friends as $friend): ?>
            <li><?= $friend['display_name'] ?></li>
          <?php endforeach; ?>
        </ul>
        <hr>
        <h5>الدردشة</h5>
        <p>قائمة الدردشات الأخيرة (يمكن تطويرها لاحقًا)</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
