<?php
header('Content-Type: application/json');
session_start();
require_once '../admin/config.php';

$user_id = $_SESSION['user_id'] ?? 0; 
$post_id = $_GET['post_id'] ?? 0;

// نفترض أنك تستخدم MySQLi
$sql = "SELECT 
          c.id,
          c.parent_id,
          c.post_id,
          c.user_id,
          c.content,
          c.created_at,
          u.display_name,
          u.profile_picture,
          /* 
             إذا وجد صف في جدول likes يطابق:
             - likes.user_id = $user_id
             - likes.comment_id = c.id
             - likes.post_id IS NULL
             نرجع is_liked = 1
             وإلا 0
          */
          IF(l.id IS NOT NULL, 1, 0) AS is_liked
        FROM comments c
        JOIN users u ON u.id = c.user_id
        LEFT JOIN likes l 
               ON (l.comment_id = c.id AND l.post_id IS NULL AND l.user_id = ?)
        WHERE c.post_id = ?
        ORDER BY c.created_at ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$result = $stmt->get_result();
$comments = [];

while($row = $result->fetch_assoc()){
    // يمكنك حساب time_ago لو أحببت
    $row['time_ago'] = 'just now';
    $comments[] = $row;
}
echo json_encode($comments);
