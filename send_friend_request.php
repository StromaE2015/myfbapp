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

// التأكد من تمرير معرف المتلقي عبر GET
if (!isset($_GET['receiver_id'])) {
    die("No receiver id provided.");
}

$sender_id = intval($_SESSION['user_id']);
$receiver_id = intval($_GET['receiver_id']);

// منع إرسال طلب لصديق لنفسك
if ($sender_id === $receiver_id) {
    die("Cannot send friend request to yourself.");
}

// التحقق مما إذا كان الطلب موجود بالفعل
$stmt = $conn->prepare("SELECT id FROM friend_requests WHERE sender_id = ? AND receiver_id = ?");
$stmt->bind_param("ii", $sender_id, $receiver_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo "تم إرسال طلب الصداقة مسبقًا.";
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// إدراج طلب الصداقة
$stmt = $conn->prepare("INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $sender_id, $receiver_id);
if ($stmt->execute()) {
    echo "تم إرسال طلب الصداقة بنجاح.";
} else {
    echo "حدث خطأ أثناء إرسال طلب الصداقة: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
