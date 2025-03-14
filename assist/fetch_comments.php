<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require '../admin/config.php';

$post_id = intval($_GET['post_id'] ?? 0);
if ($post_id <= 0) {
    echo json_encode([]);
    exit;
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

// نجلب التعليقات المرئية فقط
$sql = "SELECT c.*, u.display_name, u.profile_picture
        FROM comments c
        JOIN users u ON c.user_id = u.id
        WHERE c.post_id=? AND c.status='visible'
        ORDER BY c.created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$res = $stmt->get_result();
$comments = [];
while ($row = $res->fetch_assoc()) {
    // ممكن إضافة حساب time_ago
    $row['time_ago'] = 'just now'; 
    $comments[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode($comments);
?>
