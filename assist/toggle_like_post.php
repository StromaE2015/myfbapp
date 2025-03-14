<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}
$user_id = $_SESSION['user_id'];

require '../admin/config.php'; // عدّل المسار حسب مشروعك
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$post_id = intval($_POST['post_id'] ?? 0);
if ($post_id <= 0) {
    die("Invalid post_id");
}

// فحص ما إذا كان المستخدم قد وضع إعجابًا مسبقًا
$sql = "SELECT id FROM likes WHERE user_id=? AND post_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    // الإعجاب موجود => إزالته (تبديل toggle)
    $del = $conn->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?");
    $del->bind_param("ii", $user_id, $post_id);
    $del->execute();
    $del->close();
    echo json_encode(["status" => "unliked"]);
} else {
    // الإعجاب غير موجود => إضافته
    $ins = $conn->prepare("INSERT INTO likes(user_id, post_id) VALUES (?, ?)");
    $ins->bind_param("ii", $user_id, $post_id);
    $ins->execute();
    $ins->close();
    echo json_encode(["status" => "liked"]);
}

$stmt->close();
$conn->close();
?>
