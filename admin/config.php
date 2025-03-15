<?php
$host = "localhost"; // اسم السيرفر
$db_name = "store_db"; // اسم قاعدة البيانات
$username = "root"; // اسم المستخدم
$password = ""; // كلمة المرور

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

