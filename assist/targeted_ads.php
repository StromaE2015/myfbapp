<?php
// targeted_ads.php: صفحة عرض الإعلانات الموجهة للمستخدم
require_once 'admin/config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<p>يجب تسجيل الدخول لعرض الإعلانات.</p>";
    exit;
}
$user_id = $_SESSION['user_id'];

// استرداد اهتمامات المستخدم
$stmt = $conn->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
if (!$stmt) {
    echo "<p>خطأ في التحضير: " . $conn->error . "</p>";
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_interests = [];
while ($row = $result->fetch_assoc()) {
    $user_interests[] = $row['interest'];
}
$stmt->close();

// استرداد الحملات الإعلانية التي تتوافق مع اهتمامات المستخدم
$ads = [];
if (count($user_interests) > 0) {
    $like_conditions = [];
    $params = [];
    $types = "";
    foreach ($user_interests as $interest) {
        $like_conditions[] = "target_interests LIKE ?";
        $params[] = "%" . $interest . "%";
        $types .= "s";
    }
    $where_clause = implode(" OR ", $like_conditions);
    $query = "SELECT * FROM ads_campaigns WHERE status = 'active' AND ($where_clause)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ads[] = $row;
        }
        $stmt->close();
    } else {
        echo "<p>خطأ في التحضير: " . $conn->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>الإعلانات الموجهة</title>
    <style>
      .ad-card { border: 1px solid #ccc; padding: 10px; margin: 10px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>الإعلانات الموجهة لك</h1>
    <?php if (count($ads) > 0): ?>
        <?php foreach ($ads as $ad): ?>
            <div class="ad-card" 
                 data-ad-id="<?php echo $ad['id']; ?>" 
                 data-user-id="<?php echo $user_id; ?>" 
                 onclick="trackAdClick(<?php echo $ad['id']; ?>, <?php echo $user_id; ?>)">
                <h2><?php echo htmlspecialchars($ad['title']); ?></h2>
                <p><?php echo nl2br(htmlspecialchars($ad['description'])); ?></p>
                <p><strong>الميزانية:</strong> <?php echo htmlspecialchars($ad['budget']); ?></p>
                <p><strong>من:</strong> <?php echo htmlspecialchars($ad['start_date']); ?> <strong>إلى:</strong> <?php echo htmlspecialchars($ad['end_date']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>لا توجد إعلانات موجهة حالياً.</p>
    <?php endif; ?>
    <script src="assets/js/ads_tracking.js"></script>
</body>
</html>
