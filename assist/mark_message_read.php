<?php
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo "Error";
    exit;
}

$user_id = intval($_SESSION['user_id']);
$message_id = intval($_GET['id']);

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo "Error";
    exit;
}

// تحديث الرسالة لجعلها مقروءة
$stmt = $conn->prepare("
    UPDATE messages
    SET status = 'read'
    WHERE id = ? AND receiver_id = ?
");
$stmt->bind_param("ii", $message_id, $user_id);
$stmt->execute();
$stmt->close();
$conn->close();

echo "OK";
?>
