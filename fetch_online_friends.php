<?php
session_start();
require 'admin/config.php';

// ✅ إزالة أي فراغات غير مرئية قد تسبب أخطاء
ob_clean();
header("Content-Type: application/json; charset=UTF-8");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed"]));
}

$user_id = intval($_SESSION['user_id']);
$time_limit = date("Y-m-d H:i:s", strtotime("-5 minutes"));

$stmt = $conn->prepare("
    SELECT u.id, u.display_name, u.profile_picture 
    FROM users u
    JOIN friend_requests f ON (f.sender_id = u.id OR f.receiver_id = u.id)
    WHERE (f.sender_id = ? OR f.receiver_id = ?) 
    AND f.status = 'accepted' 
    AND u.last_active >= ?
    AND u.id != ? -- ✅ استبعاد المستخدم نفسه
    GROUP BY u.id
");
$stmt->bind_param("iisi", $user_id, $user_id, $time_limit, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$friends = [];
while ($row = $result->fetch_assoc()) {
    $friends[] = $row;
}
$stmt->close();
$conn->close();

// ✅ تأكد أن الإخراج صحيح ولا يحتوي على فراغات زائدة
echo json_encode($friends, JSON_UNESCAPED_UNICODE);
exit();
