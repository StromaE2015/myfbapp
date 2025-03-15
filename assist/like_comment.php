<?php
header('Content-Type: application/json');
session_start();
require_once '../admin/config.php';

// خلال التطوير
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_GET['comment_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing comment id']);
    exit;
}

$comment_id = intval($_GET['comment_id']);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(['status'=>'error','message'=>'DB connect error']);
    exit;
}

// هل معجب بالتعليق مسبقًا؟
$query = "SELECT id FROM likes WHERE comment_id = ? AND user_id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $comment_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$response = [];

if ($result->num_rows > 0) {
    // إزالة الإعجاب
    $query = "DELETE FROM likes WHERE comment_id = ? AND user_id = ?";
    $stmtDel = $conn->prepare($query);
    $stmtDel->bind_param("ii", $comment_id, $user_id);
    if ($stmtDel->execute()) {
        $response['status'] = 'unliked';
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to remove like';
    }
    $stmtDel->close();
} else {
    // إضافة إعجاب
    $query = "INSERT INTO likes (comment_id, user_id) VALUES (?, ?)";
    $stmtIns = $conn->prepare($query);
    if ($stmtIns->bind_param("ii", $comment_id, $user_id) && $stmtIns->execute()) {
        $response['status'] = 'liked';

        // جلب صاحب التعليق + مقتطف + اسم المعجب
        $stmtData = $conn->prepare("
            SELECT 
                c.user_id AS owner_id,
                c.content AS comment_text,
                u.display_name AS liker_name
            FROM comments c
            JOIN users u ON u.id = ?
            WHERE c.id = ?
            LIMIT 1
        ");
        $stmtData->bind_param("ii", $user_id, $comment_id);
        $stmtData->execute();
        $rData = $stmtData->get_result();
        if ($rowData = $rData->fetch_assoc()) {
            $ownerId      = $rowData['owner_id'];
            $commentText  = $rowData['comment_text'];
            $likerName    = $rowData['liker_name'];

            if ($ownerId != $user_id) {
                $snippet = mb_substr($commentText, 0, 30, 'UTF-8');
                if (mb_strlen($commentText, 'UTF-8') > 30) {
                    $snippet .= '...';
                }
                $message = "$likerName أعجب بتعليقك: $snippet";

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
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Failed to add like';
    }
    $stmtIns->close();
}

$stmt->close();
$conn->close();
echo json_encode($response);
exit;
?>
