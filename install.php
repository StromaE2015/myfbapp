<?php
require 'admin/config.php';
$servername = $host;
$dbname = $db_name;
$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// إنشاء قاعدة البيانات إذا لم تكن موجودة
$sql = "CREATE DATABASE IF NOT EXISTS store_db";
if ($conn->query($sql) !== TRUE) {
    echo "Error creating database: " . $conn->error . "<br>";
}

// استخدام قاعدة البيانات
$conn->select_db($dbname);

// تعريف استعلامات إنشاء الجداول
$sql_users = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(15) NOT NULL,
    profile_picture VARCHAR(255),
    address TEXT,
    country VARCHAR(100),
	password VARCHAR(255) NOT NULL,
	last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql_admins = "CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'superadmin') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    video VARCHAR(255),
    stock_quantity INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$sql_orders = "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

$sql_chats = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)";

$sql_ratings = "CREATE TABLE IF NOT EXISTS ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
)";

$sql_notifications = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

// إنشاء جدول store_settings لحفظ بيانات المتجر
$sql_store_settings = "CREATE TABLE IF NOT EXISTS store_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    store_name VARCHAR(255) NOT NULL,
    store_email VARCHAR(255) NOT NULL,
    store_logo VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql_friend_requests = "CREATE TABLE IF NOT EXISTS friend_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    status ENUM('pending','accepted','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_request (sender_id, receiver_id)
)";

$sql_friends = "CREATE TABLE IF NOT EXISTS friends (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friendship (user_id, friend_id)
)";

$sql_posts = "CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  status ENUM('public','private') DEFAULT 'public'
)";

$sql_comments = "CREATE TABLE IF NOT EXISTS comments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  user_id INT NOT NULL,
  content TEXT NOT NULL,
  parent_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  status ENUM('visible','deleted') DEFAULT 'visible'
)";

$sql_likes = "CREATE TABLE IF NOT EXISTS likes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  post_id INT DEFAULT NULL,
  comment_id INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_like_post (user_id, post_id),
  UNIQUE KEY unique_like_comment (user_id, comment_id)

)";

$sql_stories = "CREATE TABLE IF NOT EXISTS stories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  media_path VARCHAR(255) NOT NULL,
  media_type ENUM('image','video') DEFAULT 'image',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expire_at TIMESTAMP NOT NULL,
  is_ad TINYINT DEFAULT 0  -- لو أردت تمييز القصة كإعلان
)";

$sql_posts_media = "CREATE TABLE IF NOT EXISTS post_media (
  id INT AUTO_INCREMENT PRIMARY KEY,
  post_id INT NOT NULL,
  media_type ENUM('image','video') DEFAULT 'image',
  media_path VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// متغير لتخزين مخرجات المودال في حال نجاح التثبيت
$modalOutput = "";

// تنفيذ استعلامات إنشاء الجداول والجدول الخاص بإعدادات المتجر فقط عند إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        $conn->query($sql_users) === TRUE &&
        $conn->query($sql_admins) === TRUE &&
        $conn->query($sql_products) === TRUE &&
        $conn->query($sql_orders) === TRUE &&
        $conn->query($sql_chats) === TRUE &&
        $conn->query($sql_ratings) === TRUE &&
        $conn->query($sql_notifications) === TRUE &&
        $conn->query($sql_friend_requests) === TRUE &&
        $conn->query($sql_friends) === TRUE &&
		$conn->query($sql_posts) === TRUE &&
        $conn->query($sql_comments) === TRUE &&
        $conn->query($sql_likes) === TRUE &&
        $conn->query($sql_stories) === TRUE &&
        $conn->query($sql_store_settings) === TRUE
    ) {
        // لا طباعة رسالة هنا لتجنب ظهورها عند تحميل الصفحة
    } else {
        echo "Error creating tables: " . $conn->error . "<br>";
    }

    // التحقق من وجود مجلد 'uploads' وإذا لم يكن موجودًا يتم إنشاؤه
    $uploadDir = 'uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // التحقق من عملية التثبيت وإدخال بيانات المتجر
    if (isset($_POST['store_name']) && isset($_POST['store_email']) && isset($_FILES['store_logo'])) {
        $store_name = $_POST['store_name'];
        $store_email = $_POST['store_email'];
        $store_logo = $_FILES['store_logo']['name'];

        // تحميل الشعار إلى مجلد uploads
        move_uploaded_file($_FILES['store_logo']['tmp_name'], $uploadDir . '/' . $store_logo);

        // إدخال بيانات المتجر في جدول store_settings
        $sql_store = "INSERT INTO store_settings (store_name, store_email, store_logo)
                      VALUES ('$store_name', '$store_email', '$store_logo')";

        if ($conn->query($sql_store) === TRUE) {
            // إضافة بيانات السوبر أدمن عند التثبيت
            if (
                isset($_POST['admin_name']) &&
                isset($_POST['admin_email']) &&
                isset($_POST['admin_password'])
            ) {
                $admin_name = $_POST['admin_name'];
                $admin_email = $_POST['admin_email'];
                // تشفير كلمة المرور
                $admin_password = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);

                $sql_admin = "INSERT INTO admins (name, email, password, role)
                              VALUES ('$admin_name', '$admin_email', '$admin_password', 'superadmin')";

                if ($conn->query($sql_admin) === TRUE) {
                    $modalOutput = '
                    <div class="modal fade" id="exampleModalToggle" aria-hidden="true" aria-labelledby="exampleModalToggleLabel" tabindex="-1">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalToggleLabel">رسالة</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                          </div>
                          <div class="modal-body">
                            تم تثبيت الموقع بنجاح. الرجاء تسجيل الدخول باستخدام بيانات السوبر أدمن.
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-primary" onclick="window.location.href=\'admin/login.php\'">الانتقال لتسجيل الدخول</button>  
                          </div>
                        </div>
                      </div>
                    </div>';
                } else {
                    echo "خطأ في إنشاء السوبر أدمن: " . $conn->error;
                }
            }
        } else {
            echo "خطأ في إدخال بيانات المتجر: " . $conn->error;
        }
    }
}

$conn->close();
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fakebook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <style>
        body {
            background-color: #f0f2f5;
        }
        .sidebar, .profile-sidebar, .main-content {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            margin-left: 5px;
        }
        .navbar {
            background-color: #1877f2;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .navbar .nav-link:hover {
            color: #dfe3ee !important;
        }
        .sidebar h2, .profile-sidebar h4 {
            color: #1877f2;
        }
        .list-group-item {
            cursor: pointer;
        }
        .list-group-item:hover {
            background-color: #e4e6eb;
        }
        .btn-primary { background-color: #1877f2; border: none; }
        .btn-success { background-color: #42b72a; border: none; }
        .btn-warning { background-color: #f7b928; border: none; }
        .form-control {
            margin: 10px 0;
            padding: 10px;
        }
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            .sidebar, .profile-sidebar, .main-content {
                padding: 15px;
            }
        }
        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Fakebook</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-home"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users"></i> Friends</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users-cog"></i> Groups</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-bookmark"></i> Bookmark</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-pen"></i> Write</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>
                        <span class="badge bg-danger" id="notificationCount">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" id="notificationsList">
                        <li><a class="dropdown-item" href="#">إشعار 1</a></li>
                        <li><a class="dropdown-item" href="#">إشعار 2</a></li>
                        <li><a class="dropdown-item" href="#">إشعار 3</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">عرض كل الإشعارات</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="form-container">
                <form method="post" enctype="multipart/form-data">
                    <!-- بيانات المتجر -->
                    <div class="mb-3">
                        <label for="store_name" class="form-label">اسم المتجر</label>
                        <input type="text" class="form-control" name="store_name" id="store_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="store_email" class="form-label">البريد الإلكتروني للمتجر</label>
                        <input type="email" class="form-control" name="store_email" id="store_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="store_logo" class="form-label">الشعار</label>
                        <input type="file" class="form-control" name="store_logo" id="store_logo" required>
                    </div>
                    <hr>
                    <!-- بيانات السوبر أدمن -->
                    <h4>بيانات السوبر أدمن</h4>
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">اسم السوبر أدمن</label>
                        <input type="text" class="form-control" name="admin_name" id="admin_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">البريد الإلكتروني للسوبر أدمن</label>
                        <input type="email" class="form-control" name="admin_email" id="admin_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">كلمة المرور</label>
                        <input type="password" class="form-control" name="admin_password" id="admin_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">تثبيت الموقع</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
// عرض المودال في حال تعيينه بعد تحميل الصفحة
if (!empty($modalOutput)) {
    echo $modalOutput;
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var myModal = new bootstrap.Modal(document.getElementById("exampleModalToggle"));
            myModal.show();
        });
    </script>';
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
