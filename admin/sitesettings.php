<?php
session_start();

// تأكد من أنه تم تسجيل الدخول والسوبر أدمن فقط
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'superadmin' || !isset($_SESSION['admin_id'])) {
    die("Access Denied!");
}

// الاتصال بقاعدة البيانات
$conn = new mysqli('localhost', 'root', '', 'store_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحديث إعدادات المتجر
    if (isset($_POST['update_store'])) {
        $store_name = $_POST['store_name'];
        $store_email = $_POST['store_email'];
        // إذا تم رفع الشعار قم بتحديثه، وإلا احتفظ بالقديم
        if (!empty($_FILES['store_logo']['name'])) {
            $store_logo = $_FILES['store_logo']['name'];
            move_uploaded_file($_FILES['store_logo']['tmp_name'], 'uploads/' . $store_logo);
            $logo_update = ", store_logo = '$store_logo'";
        } else {
            $logo_update = "";
        }
        
        $sql = "UPDATE store_settings SET store_name = '$store_name', store_email = '$store_email' $logo_update WHERE id = 1";
        if ($conn->query($sql) === TRUE) {
            $message .= "تم تحديث بيانات المتجر!<br>";
        } else {
            $message .= "خطأ في تحديث بيانات المتجر: " . $conn->error . "<br>";
        }
    }
    
    // تحديث بيانات السوبر أدمن
    if (isset($_POST['update_admin'])) {
        $admin_name = $_POST['admin_name'];
        $admin_email = $_POST['admin_email'];
        // تحديث كلمة المرور في حال تم إدخالها
        if (!empty($_POST['admin_password'])) {
            $admin_password = password_hash($_POST['admin_password'], PASSWORD_BCRYPT);
            $password_update = ", password = '$admin_password'";
        } else {
            $password_update = "";
        }
        $admin_id = $_SESSION['admin_id'];
        $sql_admin = "UPDATE admins SET name = '$admin_name', email = '$admin_email' $password_update WHERE id = $admin_id";
        if ($conn->query($sql_admin) === TRUE) {
            $message .= "تم تحديث بيانات السوبر أدمن!<br>";
            $_SESSION['admin_name'] = $admin_name;
        } else {
            $message .= "خطأ في تحديث بيانات السوبر أدمن: " . $conn->error . "<br>";
        }
    }
}

// استرجاع بيانات إعدادات المتجر
$result_store = $conn->query("SELECT * FROM store_settings WHERE id = 1");
$store = $result_store->fetch_assoc();

// استرجاع بيانات السوبر أدمن
$admin_id = $_SESSION['admin_id'];
$result_admin = $conn->query("SELECT * FROM admins WHERE id = $admin_id");
$admin = $result_admin->fetch_assoc();

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعدادات المتجر - Fakebook</title>
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
            max-width: 600px;
            margin: 30px auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<!-- النافا بار كما في باقي الصفحات -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Fakebook</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users"></i> Friends</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-users-cog"></i> Groups</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fas fa-bookmark"></i> Bookmark</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-bell"></i>اسم المستخدم
                        <span class="badge bg-danger" id="notificationCount">3</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown" id="notificationsList">
                        <li><a class="dropdown-item" href="#">إشعار 1</a></li>
                        <li><a class="dropdown-item" href="#">إشعار 2</a></li>
                        <li><a class="dropdown-item" href="#">إشعار 3</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center" href="#">تسجيل الخروج</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php
    if (!empty($message)) {
        echo '<div class="alert alert-info">' . $message . '</div>';
    }
    ?>
    <div class="row">
        <!-- نموذج تعديل بيانات المتجر -->
        <div class="col-md-6">
            <div class="form-container">
                <h4 class="mb-4">تحديث بيانات المتجر</h4>
                <form method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="store_name" class="form-label">اسم المتجر</label>
                        <input type="text" class="form-control" name="store_name" id="store_name" value="<?= $store['store_name'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="store_email" class="form-label">البريد الإلكتروني للمتجر</label>
                        <input type="email" class="form-control" name="store_email" id="store_email" value="<?= $store['store_email'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="store_logo" class="form-label">الشعار</label>
                        <input type="file" class="form-control" name="store_logo" id="store_logo">
                    </div>
                    <button type="submit" name="update_store" class="btn btn-primary w-100">تحديث بيانات المتجر</button>
                </form>
            </div>
        </div>
        <!-- نموذج تعديل بيانات السوبر أدمن -->
        <div class="col-md-6">
            <div class="form-container">
                <h4 class="mb-4">تحديث بيانات السوبر أدمن</h4>
                <form method="post">
                    <div class="mb-3">
                        <label for="admin_name" class="form-label">اسم السوبر أدمن</label>
                        <input type="text" class="form-control" name="admin_name" id="admin_name" value="<?= $admin['name'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">البريد الإلكتروني للسوبر أدمن</label>
                        <input type="email" class="form-control" name="admin_email" id="admin_email" value="<?= $admin['email'] ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="admin_password" class="form-label">كلمة المرور (اتركها فارغة إذا لم ترغب بالتعديل)</label>
                        <input type="password" class="form-control" name="admin_password" id="admin_password">
                    </div>
                    <button type="submit" name="update_admin" class="btn btn-primary w-100">تحديث بيانات السوبر أدمن</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
