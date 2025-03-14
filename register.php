<?php
session_start();
require 'admin/config.php'; // استدعاء ملف الاتصال

// إنشاء الاتصال باستخدام المتغيرات المعرفة في config.php
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// دالة تصحيح اسم الملف
function sanitizeFileName($filename) {
    $filename = basename($filename);
    $filename = strtolower($filename);
    $filename = preg_replace("/[^a-z0-9.\-_]/", "", $filename);
    return $filename;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // الحصول على البيانات المُدخلة مع تصفيتها
    $name         = htmlspecialchars($_POST['username']);
    $display_name = htmlspecialchars($_POST['display_name']);
    $email        = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone        = htmlspecialchars($_POST['phone']);
    $address      = htmlspecialchars($_POST['address']);
    $country      = htmlspecialchars($_POST['country']);
    $password     = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $created_at   = date("Y-m-d H:i:s");

    // رفع الصورة الشخصية
    $profile_picture = "default.png"; // الصورة الافتراضية
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $cleanFileName = sanitizeFileName($_FILES["profile_image"]["name"]);
        $image_name = time() . "_" . $cleanFileName;
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // التحقق من نوع الملف
        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_picture = $image_name;
            } else {
                echo "<script>alert('خطأ أثناء نقل الملف! تأكد من الصلاحيات.');</script>";
            }
        } else {
            echo "<script>alert('نوع الملف غير مدعوم!');</script>";
        }
    }
    
    // إدخال البيانات إلى قاعدة البيانات حسب بنية جدول البيانات
    $stmt = $conn->prepare("INSERT INTO users (name, display_name, email, phone, profile_picture, address, country, password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "Prepare failed: " . $conn->error;
        exit;
    }
    $stmt->bind_param("sssssssss", $name, $display_name, $email, $phone, $profile_picture, $address, $country, $password, $created_at);

    if ($stmt->execute()) {
        // إرسال إشعار بالبريد الإلكتروني عند التسجيل
        $to      = $email;
        $subject = "مرحبًا بك في Fakebook!";
        $message = "مرحبًا " . $display_name . "، شكرًا لتسجيلك في Fakebook!";
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

<!DOCTYPE html>
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
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
    </style>
</head>
<body>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <form class="register-form text-center" method="POST" enctype="multipart/form-data">
            <h2 class="mb-4 text-primary">إنشاء حساب</h2>
            <input type="text" name="username" class="form-control mb-3" placeholder="اسم المستخدم" required>
            <input type="text" name="display_name" class="form-control mb-3" placeholder="الاسم الظاهر للأعضاء" required>
            <input type="email" name="email" class="form-control mb-3" placeholder="البريد الإلكتروني" required>
            <input type="text" name="phone" class="form-control mb-3" placeholder="رقم الهاتف" required>
            <input type="text" name="address" class="form-control mb-3" placeholder="العنوان" required>
            <input type="text" name="country" id="country" class="form-control mb-3" placeholder="الدولة" readonly required>
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
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        fetch('https://ipapi.co/json/')
            .then(response => response.json())
            .then(data => {
                if(data.country_name) {
                    document.getElementById('country').value = data.country_name;
                }
            })
            .catch(error => console.error('Error fetching country:', error));
    });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
