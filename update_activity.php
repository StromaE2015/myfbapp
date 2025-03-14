<?php
session_start();
require 'admin/config.php';

// التأكد من تسجيل دخول المستخدم
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = intval($_SESSION['user_id']);

// تحديث وقت آخر نشاط
$stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$conn->close();
echo "Updated";
?>
