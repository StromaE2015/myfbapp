<?php
session_start();
require 'config.php';

// التأكد من أن المستخدم مسجل الدخول
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// جلب بيانات المستخدم الحالية
$stmt = $conn->prepare("SELECT name, email, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// تحديث المعلومات الشخصية
if (isset($_POST['update_profile'])) {
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $email, $user_id);

    if ($stmt->execute()) {
        echo "تم تحديث البيانات بنجاح!";
    } else {
        echo "حدث خطأ أثناء التحديث.";
    }
}

// تغيير كلمة المرور
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);

    // جلب كلمة المرور القديمة
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();

    if (password_verify($current_password, $user_data['password'])) {
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $new_password, $user_id);
        $stmt->execute();
        echo "تم تغيير كلمة المرور بنجاح!";
    } else {
        echo "كلمة المرور الحالية غير صحيحة!";
    }
}

// تحديث الصورة الشخصية
if (isset($_POST['update_image'])) {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["profile_image"]["name"]);

    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
        $stmt->bind_param("si", $target_file, $user_id);
        $stmt->execute();
        echo "تم تحديث الصورة الشخصية!";
    } else {
        echo "حدث خطأ أثناء رفع الصورة.";
    }
}
?>

<h2>إعدادات الحساب</h2>

<form method="POST">
    <input type="text" name="name" value="<?= $user['name'] ?>" required>
    <input type="email" name="email" value="<?= $user['email'] ?>" required>
    <button type="submit" name="update_profile">تحديث البيانات</button>
</form>

<h3>تغيير كلمة المرور</h3>
<form method="POST">
    <input type="password" name="current_password" placeholder="كلمة المرور الحالية" required>
    <input type="password" name="new_password" placeholder="كلمة المرور الجديدة" required>
    <button type="submit" name="update_password">تحديث كلمة المرور</button>
</form>

<h3>تحديث الصورة الشخصية</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="profile_image" required>
    <button type="submit" name="update_image">تحديث الصورة</button>
</form>
