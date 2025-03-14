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

if(isset($_GET['id'])){
    $notif_id = intval($_GET['id']);
    $user_id = intval($_SESSION['user_id']);
    
    // تحديث حالة الإشعار إلى "read" فقط إذا كان ينتمي للمستخدم الحالي
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notif_id, $user_id);
    if($stmt->execute()){
        $stmt->close();
        $conn->close();
        header("Location: notifications.php");
        exit();
    } else {
        die("Error updating notification: " . $stmt->error);
    }
} else {
    die("No notification id provided.");
}
?>
