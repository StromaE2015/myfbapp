<?php
session_start();
require 'admin/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

$user_id = intval($_SESSION['user_id']);

// نجلب الرسائل الجديدة التي حالتها sent
$stmt = $conn->prepare("
    SELECT m.id, m.sender_id, m.message, m.created_at, u.display_name 
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.receiver_id = ? AND m.status = 'sent'
    ORDER BY m.created_at ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

// تحديث حالة الرسائل إلى 'read' أو 'delivered' حتى لا تظهر مجددًا
if (!empty($messages)) {
    $ids = array_column($messages, 'id');
    $ids_str = implode(',', $ids);
    $update = $conn->query("UPDATE messages SET status = 'read' WHERE id IN ($ids_str)");
}

$conn->close();
echo json_encode($messages);
?>
