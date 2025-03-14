<?php
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = intval($_SESSION['user_id']);

// جلب طلبات الصداقة المعلقة (مع جلب صورة المرسل)
$stmt = $conn->prepare("
    SELECT friend_requests.id, friend_requests.sender_id, users.name AS sender_name, users.profile_picture
    FROM friend_requests 
    JOIN users ON friend_requests.sender_id = users.id 
    WHERE friend_requests.receiver_id = ? AND friend_requests.status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$friend_requests = [];
while ($row = $result->fetch_assoc()) {
    $friend_requests[] = $row;
}
$stmt->close();

// جلب جميع الإشعارات مع حد 20
$stmt2 = $conn->prepare("
    SELECT id, message, status 
    FROM notifications 
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$notifications = [];
while ($notif = $result2->fetch_assoc()) {
    $notifications[] = $notif;
}
$stmt2->close();
$conn->close();
?>

<!-- تنسيق إضافي لتوحيد تصميم القائمة (إن رغبت) -->
<style>
.nav-dropdown-item {
    list-style: none; 
    border-bottom: 1px solid #ddd;
    padding: 10px 15px;
    font-size: 14px;
    display: flex;
    align-items: center;
    color: #050505;
    transition: background-color 0.2s;
}
.nav-dropdown-item:hover {
    background-color: #f5f6f7;
}
</style>

<!-- طلبات الصداقة -->
<?php if (count($friend_requests) > 0): ?>
    <li class="nav-friend-req">طلبات الصداقة</li>
    <?php foreach ($friend_requests as $request): ?>
        <li class="nav-dropdown-item" style="list-style:none;">
            <!-- صورة المرسل -->
            <?php
            // إن كانت profile_picture موجودة
            $profilePic = !empty($request['profile_picture']) ? $request['profile_picture'] : 'default_avatar.jpg';
            ?>
            <img src="uploads/<?= htmlentities($profilePic) ?>" 
                 alt="<?= htmlentities($request['sender_name']) ?>"
                 style="width:35px; height:35px; border-radius:50%; margin-right:10px;">
            
            <!-- نص الطلب -->
            <div style="flex:1;">
                <span style="font-weight:bold;"><?= htmlentities($request['sender_name']) ?></span><br>
                <span>أرسل لك طلب صداقة</span>
            </div>
            
            <!-- أزرار القبول والرفض -->
            <div style="margin-left:auto;">
                <a href="accept_friend_request.php?id=<?= $request['id'] ?>" class="btn btn-success btn-sm">✅</a>
                <a href="reject_friend_request.php?id=<?= $request['id'] ?>" class="btn btn-danger btn-sm">❌</a>
            </div>
        </li>
    <?php endforeach; ?>
<?php endif; ?>

<!-- الإشعارات -->
<?php if (count($notifications) > 0): ?>
    <li class="nav-friend-req">الإشعارات</li>
    <?php foreach ($notifications as $notif): ?>
        <?php 
            $notifClass = ($notif['status'] == 'read') 
                ? "nav-notif-item nav-notif-read" 
                : "nav-notif-item nav-notif-bold";
        ?>
        <li class="nav-dropdown-item <?= $notifClass ?>" style="list-style:none;">
            <!-- عند الضغط عليها، يوسم الإشعار كمقروء -->
            <a class="dropdown-item d-flex align-items-center notification-item" 
               href="mark_notification_read.php?id=<?= $notif['id'] ?>"
               data-id="<?= $notif['id'] ?>"
               style="text-decoration:none; color:inherit;">
               
               <i class="fas fa-info-circle me-2" style="margin-right:8px;"></i>
               <span><?= htmlentities($notif['message']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
<?php else: ?>
    <li class="nav-no-notifs">
        <span class="dropdown-item text-center">لا توجد إشعارات</span>
    </li>
<?php endif; ?>
