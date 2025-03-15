<?php
header('Content-Type: application/json');
session_start();
require_once '../admin/config.php';

// عرض الأخطاء أثناء التطوير (احذفها أو عطّلها في الإنتاج)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// التحقق من المعاملات
if (!isset($_GET['parent_comment_id']) || !isset($_GET['content'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$parent_comment_id = intval($_GET['parent_comment_id']);
$content = trim($_GET['content']);

// التحقق من أن المحتوى غير فارغ
if (empty($content)) {
    echo json_encode(['status' => 'error', 'message' => 'Content cannot be empty']);
    exit;
}

// التحقق من session['user_id']
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

// الاتصال بقاعدة البيانات (MySQLi)
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB connect error: '.$conn->connect_error]);
    exit;
}

// 1) ابحث عن التعليق الأب لجلب post_id + صاحب التعليق
$query = "
    SELECT c.post_id, c.user_id AS parent_owner
    FROM comments c
    WHERE c.id = ?
    LIMIT 1
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $parent_comment_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$row = $res->fetch_assoc()) {
    // لو لم نجد التعليق الأب
    echo json_encode(['status' => 'error', 'message' => 'Parent comment not found']);
    exit;
}
$post_id     = $row['post_id'];
$parentOwner = $row['parent_owner'];

// 2) أضف الرد في نفس post_id
$insertSql = "
    INSERT INTO comments (parent_id, post_id, user_id, content, created_at) 
    VALUES (?, ?, ?, ?, NOW())
";
$stmtIns = $conn->prepare($insertSql);
if (!$stmtIns) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmtIns->bind_param("iiis", $parent_comment_id, $post_id, $user_id, $content);

if ($stmtIns->execute()) {
    // الرد تمت إضافته بنجاح
    // 3) إضافة إشعار لصاحب التعليق الأب، إن لم يكن هو نفس المستخدم
    if ($parentOwner != $user_id) {
        // جلب اسم المستخدم الرادّ
        $sqlUser = "SELECT display_name FROM users WHERE id=? LIMIT 1";
        $stmtUser = $conn->prepare($sqlUser);
        $stmtUser->bind_param("i", $user_id);
        $stmtUser->execute();
        $rUser = $stmtUser->get_result();
        $replierName = "مستخدم مجهول";
        if ($uRow = $rUser->fetch_assoc()) {
            $replierName = $uRow['display_name'];
        }
        $stmtUser->close();

        // إنشاء مقتطف من الرد (30 حرف فقط)
        $snippet = mb_substr($content, 0, 30, 'UTF-8');
        if (mb_strlen($content, 'UTF-8') > 30) {
            $snippet .= '...';
        }

        // تكوين رسالة الإشعار
        $message = "$replierName رد على تعليقك: $snippet";

        // إدراج الإشعار في notifications
        $notifSql = "
            INSERT INTO notifications (user_id, message, status, created_at)
            VALUES (?, ?, 'unread', NOW())
        ";
        $stmtNotif = $conn->prepare($notifSql);
        $stmtNotif->bind_param("is", $parentOwner, $message);
        $stmtNotif->execute();
        $stmtNotif->close();
    }

    echo json_encode(['status' => 'ok', 'message' => 'Reply added successfully']);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to add reply: ' . $stmtIns->error
    ]);
}
$stmtIns->close();
$conn->close();
exit;
?>
