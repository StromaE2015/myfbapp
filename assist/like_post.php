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

if ($post_id <= 0) {
    echo json_encode(["status"=>"error","msg"=>"Invalid post_id"]);
    exit;
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["status"=>"error","msg"=>"DB connect error"]);
    exit;
}

// هل المستخدم معجب بالمنشور مسبقًا؟
$sql = "SELECT id FROM likes WHERE user_id=? AND post_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$res = $stmt->get_result();

$response = [];

if ($res->num_rows > 0) {
    // حذف الإعجاب
    $del = $conn->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?");
    $del->bind_param("ii", $user_id, $post_id);
    $del->execute();
    $del->close();
    $response["status"] = "unliked";
} else {
    // إضافة إعجاب
    $ins = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    $ins->bind_param("ii", $user_id, $post_id);
    $ins->execute();
    $ins->close();
    $response["status"] = "liked";

    // جلب صاحب المنشور + مقتطف + اسم المستخدم الذي قام بالإعجاب
    $stmtData = $conn->prepare("
        SELECT 
            p.user_id AS owner_id,
            p.content AS post_content,
            u.display_name AS liker_name
        FROM posts p
        JOIN users u ON u.id = ?
        WHERE p.id = ?
        LIMIT 1
    ");
    $stmtData->bind_param("ii", $user_id, $post_id);
    $stmtData->execute();
    $resData = $stmtData->get_result();
    if ($rowData = $resData->fetch_assoc()) {
        $ownerId     = $rowData['owner_id'];    // صاحب المنشور
        $postContent = $rowData['post_content'];
        $likerName   = $rowData['liker_name'];

        if ($ownerId != $user_id) {
            // نقتطع أول 30 حرف من المحتوى
            $snippet = mb_substr($postContent, 0, 30, 'UTF-8');
            if (mb_strlen($postContent, 'UTF-8') > 30) {
                $snippet .= '...';
            }
            $message = "$likerName أعجب بمنشورك: $snippet";

            $notif = $conn->prepare("
                INSERT INTO notifications (user_id, message, status, created_at) 
                VALUES (?, ?, 'unread', NOW())
            ");
            $notif->bind_param("is", $ownerId, $message);
            $notif->execute();
            $notif->close();
        }
    }
    $stmtData->close();
}

$stmt->close();
$conn->close();

echo json_encode($response);
exit;
?>
