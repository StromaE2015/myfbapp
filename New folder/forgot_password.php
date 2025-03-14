<?php
require 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $_POST['login']; // البريد الإلكتروني أو رقم الهاتف

    // التحقق إذا كان المستخدم موجود في قاعدة البيانات
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email = ? OR phone_number = ?");
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $email = $user['email']; // نستخدم البريد الإلكتروني لإرسال الرابط

        $token = bin2hex(random_bytes(50)); // إنشاء توكن عشوائي
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour")); // انتهاء صلاحية التوكن خلال ساعة

        // حفظ التوكن في قاعدة البيانات
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
        $stmt->bind_param("sss", $token, $expires, $email);
        $stmt->execute();

        // إرسال رابط إعادة التعيين إلى البريد الإلكتروني
        $resetLink = "http://yourwebsite.com/reset_password.php?token=" . $token;
        $subject = "إعادة تعيين كلمة المرور";
        $message = "مرحبًا،\n\nاضغط على الرابط التالي لإعادة تعيين كلمة المرور:\n\n" . $resetLink;
        $headers = "From: no-reply@yourwebsite.com\r\nContent-Type: text/plain; charset=UTF-8";
        mail($email, $subject, $message, $headers);

        echo "<div class='alert alert-success text-center'>تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.</div>";
    } else {
        echo "<div class='alert alert-danger text-center'>البريد الإلكتروني أو رقم الهاتف غير مسجل.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>استعادة كلمة المرور</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .reset-container {
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

<div class="reset-container">
    <h2 class="mb-3 text-primary">إعادة تعيين كلمة المرور</h2>
    <p>أدخل بريدك الإلكتروني أو رقم هاتفك لاستعادة كلمة المرور.</p>
    <form method="POST">
        <div class="mb-3">
            <input type="text" name="login" class="form-control" placeholder="البريد الإلكتروني أو رقم الهاتف" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">إرسال الرابط</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
