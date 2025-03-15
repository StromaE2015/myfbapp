<?php
require_once "../admin/config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_id = $_POST["ad_id"];
    $user_id = $_POST["user_id"];
    $interaction_type = $_POST["interaction_type"];

    $stmt = $conn->prepare("INSERT INTO ad_interactions (user_id, ad_id, interaction_type, timestamp) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iis", $user_id, $ad_id, $interaction_type);
    $stmt->execute();

    echo json_encode(["status" => "success"]);
}
?>
