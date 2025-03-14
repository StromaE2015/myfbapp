<?php
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}
$user_id = $_SESSION['user_id'];

$post_id = intval($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

if ($post_id <= 0 || $content == '') {
    die("بيانات غير صالحة");
}

// إدخال التعليق
$stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, parent_id) VALUES (?,?,?,?)");
$stmt->bind_param("iisi", $post_id, $user_id, $content, $parent_id);
$stmt->execute();
$stmt->close();
$conn->close();

// إعادة التوجيه أو إرجاع JSON
header("Location: post_details.php?id=$post_id");
exit;
?>
