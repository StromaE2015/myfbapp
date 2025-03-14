<?php
session_start();
require 'admin/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['friend_id'])) {
    echo json_encode([]);
    exit();
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

$myUserId = intval($_SESSION['user_id']);
$friendId = intval($_GET['friend_id']);

$stmt = $conn->prepare("
    SELECT * FROM messages
    WHERE (sender_id = ? AND receiver_id = ?)
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
");
$stmt->bind_param("iiii", $myUserId, $friendId, $friendId, $myUserId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

$stmt->close();
$conn->close();
echo json_encode($messages);
?>
