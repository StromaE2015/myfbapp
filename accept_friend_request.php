<?php
session_start();
require 'admin/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    die("طلب غير صالح");
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}

$request_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

// جلب بيانات الطلب
$stmt = $conn->prepare("SELECT sender_id FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $request_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("طلب الصداقة غير موجود أو تم التعامل معه بالفعل.");
}
$request = $result->fetch_assoc();
$friend_id = $request['sender_id'];
$stmt->close();

// تحديث حالة الطلب إلى 'accepted'
$stmt = $conn->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->close();

// إضافة الأصدقاء إلى جدول `friends`
$stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)");
$stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);
$stmt->execute();
$stmt->close();

$conn->close();
header("Location: index.php");
exit();
?>
