
<!DOCTYPE html>
<?php
session_start();
require 'config.php'; // استدعاء ملف الاتصال

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $created_at = date("Y-m-d H:i:s");

    // رفع الصورة
    $profile_image = "default.png"; // الصورة الافتراضية
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // إنشاء المجلد لو مش موجود
        }
        
        $image_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // التحقق من نوع الملف
        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image = $image_name;
            } else {
                echo "<script>alert('خطأ أثناء نقل الملف! تأكد من الصلاحيات.');</script>";
            }
        } else {
            echo "<script>alert('نوع الملف غير مدعوم!');</script>";
        }
    }

    // إدخال البيانات إلى قاعدة البيانات
    $stmt = $conn->prepare("INSERT INTO users (name, email, PhoneNumber, password, profile_image, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $email, $phone, $password, $profile_image, $created_at);

    if ($stmt->execute()) {
        // إرسال إشعار بالإيميل
        $to = $email;
        $subject = "مرحبًا بك في Fakebook!";
        $message = "مرحبًا " . $name . "، شكرًا لتسجيلك في Fakebook!";
        $headers = "From: no-reply@fakebook.com";
        mail($to, $subject, $message, $headers);

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('successMessage').innerText = 'تم التسجيل بنجاح!';
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            });
        </script>";
    } else {
        $error_message = "حدث خطأ أثناء التسجيل.";
        if ($conn->errno == 1062) {
            $error_message = "هذا البريد الإلكتروني مسجل بالفعل!";
        } else {
            $error_message .= "\\n" . $conn->error;
        }
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('errorMessage').innerText = '$error_message';
                var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
                errorModal.show();
            });
        </script>";
    }
}
?>


<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل Fakebook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .register-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <form class="register-form text-center" method="POST" enctype="multipart/form-data">
            <h2 class="mb-4 text-primary">إنشاء حساب</h2>
            <input type="text" name="name" class="form-control mb-3" placeholder="الاسم الكامل" required>
            <input type="email" name="email" class="form-control mb-3" placeholder="البريد الإلكتروني" required>
            <input type="text" name="phone" class="form-control mb-3" placeholder="رقم الهاتف" required>
            <input type="password" name="password" class="form-control mb-3" placeholder="كلمة المرور" required>
            <input type="file" name="profile_image" class="form-control mb-3" accept="image/*">
            <button type="submit" class="btn btn-primary w-100">تسجيل</button>
        </form>
    </div>
    <!-- مودال النجاح -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
            <div class="alert alert-success text-center">
                <p id="successMessage">تم التسجيل بنجاح!</p>
            </div>
        </div>
    </div>
</div>

<!-- مودال الخطأ -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="alert alert-danger text-center">
                <p id="errorMessage">حدث خطأ أثناء التسجيل.</p>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
