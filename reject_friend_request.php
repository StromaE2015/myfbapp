<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'admin/config.php';
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['id'])) {
    die("No friend request id provided.");
}

$request_id = intval($_GET['id']);
$user_id = intval($_SESSION['user_id']);

// تحديث حالة طلب الصداقة إلى "rejected" للمستخدم الحالي (المستقبل)
$stmt = $conn->prepare("UPDATE friend_requests SET status = 'rejected' WHERE id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $request_id, $user_id);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: friend_requests.php?msg=" . urlencode("تم رفض طلب الصداقة."));
    exit();
} else {
    die("Error updating friend request: " . $stmt->error);
}
?>
