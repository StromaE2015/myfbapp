<?php
header('Content-Type: application/json');
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status"=>"error","msg"=>"Not logged in"]);
    exit;
}

$user_id   = $_SESSION['user_id'];
$post_id   = intval($_POST['post_id'] ?? 0);
$content   = trim($_POST['content'] ?? '');
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
$sql = "INSERT INTO comments (post_id, user_id, content, parent_id, status, created_at) 
        VALUES (?,?,?,?, 'visible', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iisi", $post_id, $user_id, $content, $parent_id);
if ($stmt->execute()) {
    // التعليق الجديد تم إدخاله
    // نأتي بمقتطف من التعليق الذي تمت إضافته (وهو نفسه $content)
    $snippet = mb_substr($content, 0, 30, 'UTF-8');
    if (mb_strlen($content, 'UTF-8') > 30) {
        $snippet .= '...';
    }

    // هل هذا تعليق على منشور أم رد على تعليق؟
    if (empty($parent_id) || $parent_id == 0) {
        // تعليق على منشور
        $stmtOwner = $conn->prepare("SELECT user_id FROM posts WHERE id=? LIMIT 1");
        $stmtOwner->bind_param("i", $post_id);
        $stmtOwner->execute();
        $resOwner = $stmtOwner->get_result();
        if ($rowOwner = $resOwner->fetch_assoc()) {
            $ownerId = $rowOwner['user_id'];
            if ($ownerId != $user_id) {
                // مثال: "فلان علق على منشورك: ... "
                // نفترض أن اسم المعلق موجود في جدول users
                $stmtName = $conn->prepare("SELECT display_name FROM users WHERE id=? LIMIT 1");
                $stmtName->bind_param("i", $user_id);
                $stmtName->execute();
                $resName = $stmtName->get_result();
                $mName   = "مستخدم مجهول";
                if ($rowN = $resName->fetch_assoc()) {
                    $mName = $rowN['display_name'];
                }
                $stmtName->close();

                $message = "$mName علق على منشورك: $snippet";

                $notif = $conn->prepare("
                    INSERT INTO notifications (user_id, message, status, created_at) 
                    VALUES (?, ?, 'unread', NOW())
                ");
                $notif->bind_param("is", $ownerId, $message);
                $notif->execute();
                $notif->close();
            }
        }
        $stmtOwner->close();
    } else {
        // رد على تعليق آخر
        // نرسل إشعار لصاحب التعليق الأب
        $stmtOwner = $conn->prepare("SELECT user_id FROM comments WHERE id=? LIMIT 1");
        $stmtOwner->bind_param("i", $parent_id);
        $stmtOwner->execute();
        $resOwner = $stmtOwner->get_result();
        if ($rowOwner = $resOwner->fetch_assoc()) {
            $ownerId = $rowOwner['user_id'];
            if ($ownerId != $user_id) {
                // "فلان رد على تعليقك: ..."
                $stmtName = $conn->prepare("SELECT display_name FROM users WHERE id=? LIMIT 1");
                $stmtName->bind_param("i", $user_id);
                $stmtName->execute();
                $resName = $stmtName->get_result();
                $mName   = "مستخدم مجهول";
                if ($rowN = $resName->fetch_assoc()) {
                    $mName = $rowN['display_name'];
                }
                $stmtName->close();

                $message = "$mName رد على تعليقك: $snippet";

                $notif = $conn->prepare("
                    INSERT INTO notifications (user_id, message, status, created_at) 
                    VALUES (?, ?, 'unread', NOW())
                ");
                $notif->bind_param("is", $ownerId, $message);
                $notif->execute();
                $notif->close();
            }
        }
        $stmtOwner->close();
    }

    echo json_encode(["status"=>"ok"]);
} else {
    echo json_encode(["status"=>"error","msg"=>"DB insert failed"]);
}
$stmt->close();
$conn->close();
?>
