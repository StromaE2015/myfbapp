<?php
session_start();
require 'admin/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed"]);
    exit();
}

$sender_id = intval($_SESSION['user_id']);
$receiver_id = intval($_POST['receiver_id']);
$message = trim($_POST['message']);

if ($receiver_id <= 0 || empty($message)) {
    echo json_encode(["error" => "Invalid input"]);
    exit();
}

// حفظ الرسالة بحالة sent
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, status) VALUES (?, ?, ?, 'sent')");
$stmt->bind_param("iis", $sender_id, $receiver_id, $message);
if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => "Failed to send"]);
}

$stmt->close();
$conn->close();
?>
