<?php
require_once '../admin/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $advertiser_id = $_POST['advertiser_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $target_interests = $_POST['target_interests'];
    $budget = $_POST['budget'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $stmt = $conn->prepare("INSERT INTO ads_campaigns (advertiser_id, title, description, target_interests, budget, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssdss", $advertiser_id, $title, $description, $target_interests, $budget, $start_date, $end_date);
    if ($stmt->execute()) {
        echo "تم إنشاء الحملة الإعلانية بنجاح.";
    } else {
        echo "حدث خطأ: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "طلب غير صالح.";
}
?>
