<?php
session_start();
require 'admin/config.php'; // استدعاء ملف الاتصال

// إنشاء الاتصال باستخدام المتغيرات المعرفة في config.php
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login'];
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // البحث في جدول الأعضاء باستخدام البريد الإلكتروني أو رقم الهاتف أو اسم المستخدم
    // تأكد من استخدام أسماء الأعمدة الصحيحة كما هي موجودة في قاعدة البيانات، 
    // على سبيل المثال هنا نستخدم "email" و "phone" و "name"
    $stmt = $conn->prepare("SELECT id, name, display_name, email, phone, password FROM users WHERE email = ? OR phone = ? OR name = ?");
    
    if (!$stmt) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorMessage').innerText = 'خطأ في الاستعلام: " . $conn->error . "';
                var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            });
        </script>";
        exit;
    }

    // ربط نفس قيمة $login لكل من الحقول الثلاثة
    $stmt->bind_param("sss", $login, $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['name'];
            $_SESSION['display_name'] = $user['display_name'];

            // ميزة "تذكرني" عبر الكوكيز
            if ($remember) {
                setcookie("user_id", $user['id'], time() + (86400 * 30), "/"); // لمدة 30 يوم
                setcookie("username", $user['name'], time() + (86400 * 30), "/");
            }

            // إرسال إشعار عبر الإيميل بتسجيل الدخول
            $to = $user['email'];
            $subject = "تسجيل دخول جديد";
            $message = "مرحبًا " . $user['display_name'] . "، لقد تم تسجيل الدخول إلى حسابك.";
            $headers = "From: no-reply@yourwebsite.com";
            mail($to, $subject, $message, $headers);

            header("Location: index.php");
            exit();
        } else {
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('errorMessage').innerText = 'كلمة المرور غير صحيحة.';
                    var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                    errorModal.show();
                });
            </script>";
        }
    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorMessage').innerText = 'البريد الإلكتروني أو رقم الهاتف أو اسم المستخدم غير مسجل.';
                var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - Fakebook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 400px;
        }
        .btn-primary {
            background-color: #1877f2;
            border: none;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2 class="mb-3 text-primary">تسجيل الدخول</h2>
    <form method="POST">
        <div class="mb-3">
            <input type="text" name="login" class="form-control" placeholder="البريد الإلكتروني أو رقم الهاتف أو اسم المستخدم" required>
        </div>
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="كلمة المرور" required>
        </div>
        <div class="mb-3 form-check text-start">
            <input type="checkbox" name="remember" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">تذكرني</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
    </form>
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="alert alert-primary text-center">
                    <p id="errorMessage">حدث خطأ غير متوقع.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
