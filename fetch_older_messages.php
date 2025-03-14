<?php
session_start();
require 'admin/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['friend_id'])) {
    echo json_encode([]);
    exit;
}

$myUserId  = intval($_SESSION['user_id']);
$friendId  = intval($_GET['friend_id']);
// إذا لم يُرسل before_id، نستخدم رقم كبير
$beforeId  = isset($_GET['before_id']) ? intval($_GET['before_id']) : 999999999;

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

// جلب 20 رسالة أقدم من before_id
$stmt = $conn->prepare("
    SELECT * FROM messages
    WHERE (
           (sender_id = ? AND receiver_id = ?)
        OR (sender_id = ? AND receiver_id = ?)
    )
    AND id < ?
    ORDER BY id DESC
    LIMIT 20
");
$stmt->bind_param("iiiii", $myUserId, $friendId, $friendId, $myUserId, $beforeId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
$conn->close();

// عكس المصفوفة لجعلها تصاعدية عند العرض
$messages = array_reverse($messages);

echo json_encode($messages);
?>
