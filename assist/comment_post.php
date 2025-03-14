<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","msg"=>"Not logged in"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

if ($post_id <= 0 || $content === '') {
    echo json_encode(["status"=>"error","msg"=>"Invalid post_id or content"]);
    exit;
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["status"=>"error","msg"=>"DB connect error"]);
    exit;
}

// إدخال التعليق
$sql = "INSERT INTO comments (post_id, user_id, content, parent_id, status) 
        VALUES (?,?,?,?, 'visible')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisi", $post_id, $user_id, $content, $parent_id);
if ($stmt->execute()) {
    echo json_encode(["status"=>"ok"]);
} else {
    echo json_encode(["status"=>"error","msg"=>"DB insert failed"]);
}
$stmt->close();
$conn->close();
?>
