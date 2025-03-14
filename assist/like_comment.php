<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","msg"=>"Not logged in"]);
    exit;
}
$user_id = $_SESSION['user_id'];
$comment_id = intval($_POST['comment_id'] ?? 0);

if ($comment_id <= 0) {
    echo json_encode(["status"=>"error","msg"=>"Invalid comment_id"]);
    exit;
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["status"=>"error","msg"=>"DB connect error"]);
    exit;
}

// هل المستخدم معجب بالتعليق مسبقًا؟
$sql = "SELECT id FROM likes WHERE user_id=? AND comment_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $comment_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // حذف الإعجاب
    $del = $conn->prepare("DELETE FROM likes WHERE user_id=? AND comment_id=?");
    $del->bind_param("ii", $user_id, $comment_id);
    $del->execute();
    $del->close();
    echo json_encode(["status"=>"unliked"]);
} else {
    // إضافة إعجاب
    $ins = $conn->prepare("INSERT INTO likes (user_id, comment_id) VALUES (?,?)");
    $ins->bind_param("ii", $user_id, $comment_id);
    $ins->execute();
    $ins->close();
    echo json_encode(["status"=>"liked"]);
}

$stmt->close();
$conn->close();
exit;
?>
