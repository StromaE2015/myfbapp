<?php
header('Content-Type: application/json; charset=UTF-8');
require '../admin/config.php';

$post_id = intval($_GET['post_id'] ?? 0);
if ($post_id <= 0) {
    echo json_encode(["total_likes"=>0]);
    exit;
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["total_likes"=>0]);
    exit;
}

$sql = "SELECT COUNT(*) AS total_likes FROM likes WHERE post_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $post_id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();
$stmt->close();
$conn->close();

$total = $data ? intval($data['total_likes']) : 0;
echo json_encode(["total_likes"=>$total]);
?>
