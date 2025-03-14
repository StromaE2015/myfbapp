<?php
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}
$current_user_id = $_SESSION['user_id'];

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit  = 5; // مثلاً نجلب 5 قصص كل مرة

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// نجلب قصص الأعضاء الآخرين فقط
$sql = "
  SELECT s.id, s.user_id, s.media_path, s.media_type, s.created_at, s.expire_at,
         u.display_name AS user_name, u.profile_picture
  FROM stories s
  JOIN users u ON s.user_id = u.id
  WHERE s.expire_at > NOW()
    AND s.is_ad = 0
    AND s.user_id != $current_user_id
  ORDER BY s.created_at DESC
  LIMIT $offset, $limit
";
$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $stUserName  = htmlspecialchars($row['user_name']);
    $stMediaPath = htmlspecialchars($row['media_path']);
    $stMediaType = $row['media_type'];
    $stProfile   = !empty($row['profile_picture']) ? $row['profile_picture'] : 'default_avatar.jpg';
    ?>
    <div class="w-1/4 sm:w-1/6 h-44 rounded-xl overflow-hidden flex flex-col group cursor-pointer" style="width: 90%;">
      <div class="h-3/5 overflow-hidden relative">
        <?php if ($stMediaType === 'video'): ?>
          <video src="uploads/stories/<?= $stMediaPath ?>"
                 class="group-hover:scale-110 transition-all duration-700 w-full h-full object-cover"
                 autoplay muted loop></video>
        <?php else: ?>
          <img src="uploads/stories/<?= $stMediaPath ?>"
               alt="Story images"
               class="group-hover:transform group-hover:scale-110 transition-all duration-700 w-full h-full object-cover">
        <?php endif; ?>
        <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
      </div>
      <div class="flex-1 relative flex items-end justify-center pb-2 text-center leading-none">
        <span class="font-semibold">
          <?= $stUserName ?>
        </span>
        <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
          <img src="uploads/<?= htmlspecialchars($stProfile) ?>" alt="Profile picture" class="object-cover w-full h-full">
        </div>
      </div>
    </div>
    <?php
}

$conn->close();
