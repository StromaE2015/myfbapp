<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}
$user_id = $_SESSION['user_id'];

require '../admin/config.php';
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$comment_id = intval($_POST['comment_id'] ?? 0);
if ($comment_id <= 0) {
    die("Invalid comment_id");
}

// فحص ما إذا كان المستخدم قد وضع إعجابًا مسبقًا على التعليق
$sql = "SELECT id FROM likes WHERE user_id=? AND comment_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $comment_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // موجود => إزالة
    $del = $conn->prepare("DELETE FROM likes WHERE user_id=? AND comment_id=?");
    $del->bind_param("ii", $user_id, $comment_id);
    $del->execute();
    $del->close();
    echo json_encode(["status" => "unliked"]);
} else {
    // غير موجود => إضافة
    $ins = $conn->prepare("INSERT INTO likes(user_id, comment_id) VALUES (?, ?)");
    $ins->bind_param("ii", $user_id, $comment_id);
    $ins->execute();
    $ins->close();
    echo json_encode(["status" => "liked"]);
}

$stmt->close();
$conn->close();
?>
