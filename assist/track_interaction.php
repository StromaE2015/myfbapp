<?php
require_once "../admin/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = $_POST["item_id"];
    $item_type = $_POST["item_type"];
    $interaction_type = $_POST["interaction_type"];

    $stmt = $conn->prepare("INSERT INTO interactions (item_id, item_type, interaction_type, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $item_id, $item_type, $interaction_type);
    $stmt->execute();
    
    echo json_encode(["status" => "success"]);
}
?>
