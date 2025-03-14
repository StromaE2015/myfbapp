<?php
require 'config.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // التحقق من صلاحية التوكن
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        die("<div class='alert alert-danger text-center'>الرابط غير صالح أو انتهت صلاحيته.</div>");
    }
} else {
    die("<div class='alert alert-danger text-center'>طلب غير صالح.</div>");
}

// تحديث كلمة المرور
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "<div class='alert alert-danger text-center'>كلمتا المرور غير متطابقتين.</div>";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashed_password, $token);
        $stmt->execute();

        echo "<div class='alert alert-success text-center'>تم إعادة تعيين كلمة المرور بنجاح! يمكنك الآن <a href='login.php' class='text-success'>تسجيل الدخول</a>.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور</title>
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
    <p>يرجى إدخال كلمة المرور الجديدة.</p>
    <form method="POST">
        <div class="mb-3">
            <input type="password" name="password" class="form-control" placeholder="كلمة المرور الجديدة" required>
        </div>
        <div class="mb-3">
            <input type="password" name="confirm_password" class="form-control" placeholder="تأكيد كلمة المرور" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">إعادة تعيين</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
