<?php
session_start();
require 'config.php'; // ملف الاتصال بقاعدة البيانات

// تسجيل مستخدم جديد
if (isset($_POST['register'])) {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);
    
    if ($stmt->execute()) {
        echo "تم التسجيل بنجاح!";
    } else {
        echo "حدث خطأ أثناء التسجيل.";
    }
}

// تسجيل الدخول
if (isset($_POST['login'])) {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            echo "تم تسجيل الدخول بنجاح!";
        } else {
            echo "كلمة المرور غير صحيحة.";
        }
    } else {
        echo "البريد الإلكتروني غير مسجل.";
    }
}
?>
