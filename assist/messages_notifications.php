<?php
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}

$user_id = intval($_SESSION['user_id']);

// الاتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/**
 * الفكرة: نجلب آخر رسالة (أعلى id) غير مقروءة لكل مرسل.
 * نستعمل استعلام فرعي:
 *
 *   SELECT m.id, m.sender_id, m.message, m.status, m.created_at, u.name, u.profile_picture
 *   FROM messages m
 *   JOIN (
 *       SELECT sender_id, MAX(id) as max_id
 *       FROM messages
 *       WHERE receiver_id=? AND status='unread'
 *       GROUP BY sender_id
 *   ) sub ON m.id = sub.max_id
 *   JOIN users u ON u.id = m.sender_id
 *   ORDER BY m.created_at DESC
 *   LIMIT 20
 */

$stmt = $conn->prepare("
    SELECT m.id AS msg_id,
           m.sender_id,
           m.message,
           m.status,
           m.created_at,
           u.name AS sender_name,
           u.profile_picture
    FROM messages m
    JOIN (
        SELECT sender_id, MAX(id) AS max_id
        FROM messages
        WHERE receiver_id = ? AND status = 'sent'
        GROUP BY sender_id
    ) sub ON m.id = sub.max_id
    JOIN users u ON u.id = m.sender_id
    ORDER BY m.created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$last_unread_msgs = [];
while ($row = $result->fetch_assoc()) {
    $last_unread_msgs[] = $row;
}
$stmt->close();
$conn->close();
?>

<?php if (count($last_unread_msgs) > 0): ?>
    <li class="nav-friend-req">رسائل جديدة</li>
    <?php foreach ($last_unread_msgs as $msg): ?>
        <?php 
            // لو أردت تمييز غير المقروء بكلاس مختلف
            // لكننا نعلم أن كلها غير مقروءة لأننا نبحث عن status='unread'
            // ممكن تستعمل nav-notif-bold مثلاً
            $msgClass = "nav-notif-item nav-notif-bold";

            // لو أردت اقتصاص النص ليكون أقصر
            $shortMessage = (mb_strlen($msg['message']) > 50) 
                ? mb_substr($msg['message'], 0, 50) . '...' 
                : $msg['message'];
        ?>
        <li style="list-style:none;">
            <!-- عند الضغط عليها، استدعِ mark_message_read.php?id=XX -->
            <!-- ثم وجِّه المستخدم لصفحة open_chat.php?sender=XX -->
            <a class="dropdown-item d-flex align-items-center message-item <?= $msgClass ?>"
               href="open_chat.php?sender=<?= $msg['sender_id'] ?>"
               data-id="<?= $msg['msg_id'] ?>">
               
               <!-- صورة المرسل -->
               <img src="uploads/<?= !empty($msg['profile_picture']) ? $msg['profile_picture'] : 'default_avatar.jpg' ?>"
                    alt="<?= htmlentities($msg['sender_name']) ?>"
                    style="width:35px; height:35px; border-radius:50%; margin-right:10px;">
               
               <div>
                   <!-- اسم المرسل -->
                   <span style="font-weight:bold; color:#050505;"><?= htmlentities($msg['sender_name']) ?></span><br>
                   <!-- آخر رسالة -->
                   <span><?= htmlentities($shortMessage) ?></span>
               </div>
            </a>
        </li>
    <?php endforeach; ?>
<?php else: ?>
    <li class="nav-no-notifs">
        <span class="dropdown-item text-center">لا توجد رسائل جديدة</span>
    </li>
<?php endif; ?>
