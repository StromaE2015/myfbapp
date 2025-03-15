<?php
header('Content-Type: application/json; charset=UTF-8');
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "msg" => "Not logged in"]);
    exit;
}
$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    echo json_encode(["status" => "error", "msg" => "Invalid post_id"]);
    exit;
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "msg" => "DB connect error: " . $conn->connect_error]);
    exit;
}

// هل المستخدم معجب بالمنشور مسبقًا؟
$sql = "SELECT id FROM likes WHERE user_id=? AND post_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["status" => "error", "msg" => "Prepare SELECT error: " . $conn->error]);
    exit;
}
$stmt->bind_param("ii", $user_id, $post_id);
if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "msg" => "Execute SELECT error: " . $stmt->error]);
    exit;
}
$res = $stmt->get_result();

$response = [];

if ($res->num_rows > 0) {
    // حذف الإعجاب
    $del = $conn->prepare("DELETE FROM likes WHERE user_id=? AND post_id=?");
    if (!$del) {
        echo json_encode(["status" => "error", "msg" => "Prepare DELETE error: " . $conn->error]);
        exit;
    }
    $del->bind_param("ii", $user_id, $post_id);
    if (!$del->execute()) {
        echo json_encode(["status" => "error", "msg" => "Execute DELETE error: " . $del->error]);
        exit;
    }
    $del->close();
    $response["status"] = "unliked";
} else {
    // إضافة إعجاب
    $ins = $conn->prepare("INSERT INTO likes (user_id, post_id) VALUES (?, ?)");
    if (!$ins) {
        echo json_encode(["status" => "error", "msg" => "Prepare INSERT error: " . $conn->error]);
        exit;
    }
    $ins->bind_param("ii", $user_id, $post_id);
    if (!$ins->execute()) {
        echo json_encode(["status" => "error", "msg" => "Execute INSERT error: " . $ins->error]);
        exit;
    }
    $ins->close();
    $response["status"] = "liked";

    // استدعاء دالة recordInteraction لتسجيل التفاعل
    require_once '../assist/interactions.php';
    try {
        recordInteraction($user_id, 'post', $post_id, 'like');
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "msg" => "Interaction error: " . $e->getMessage()]);
        exit;
    }

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
    if (!$stmtData) {
        echo json_encode(["status" => "error", "msg" => "Prepare SELECT data error: " . $conn->error]);
        exit;
    }
    $stmtData->bind_param("ii", $user_id, $post_id);
    if (!$stmtData->execute()) {
        echo json_encode(["status" => "error", "msg" => "Execute SELECT data error: " . $stmtData->error]);
        exit;
    }
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
            if (!$notif) {
                echo json_encode(["status" => "error", "msg" => "Prepare NOTIFICATION error: " . $conn->error]);
                exit;
            }
            $notif->bind_param("is", $ownerId, $message);
            if (!$notif->execute()) {
                echo json_encode(["status" => "error", "msg" => "Execute NOTIFICATION error: " . $notif->error]);
                exit;
            }
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
