<?php
$conn = new mysqli("localhost", "root", "", "ecommerce");

if ($conn->connect_error) {
    die(json_encode(["error" => "فشل الاتصال بقاعدة البيانات: " . $conn->connect_error]));
}

$sql = "SELECT id, content, type FROM notifications WHERE is_read = 'unread'";
$result = $conn->query($sql);

if (!$result) {
    die(json_encode(["error" => "خطأ في الاستعلام: " . $conn->error]));
}

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode(["count" => count($notifications), "notifications" => $notifications]);

$conn->close();
?>
