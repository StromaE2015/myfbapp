<?php
$host = "localhost"; // اسم السيرفر
$db_name = "ecommerce"; // اسم قاعدة البيانات
$username = "root"; // اسم المستخدم
$password = ""; // كلمة المرور

$conn = new mysqli($host, $username, $password, $db_name);

// التأكد من نجاح الاتصال
if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}
?>
