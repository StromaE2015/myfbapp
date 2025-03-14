<?php
session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}
$user_id = $_SESSION['user_id'];

// التحقق من الملف
if (!isset($_FILES['story_media']) || $_FILES['story_media']['error'] != 0) {
    die("لم يتم رفع أي ملف!");
}

$fileType = $_FILES['story_media']['type'];
$mediaType = 'image';
if (strpos($fileType, 'video/') === 0) {
    $mediaType = 'video';
}

// تأكد من المجلد الصحيح
// نفترض أن uploads/stories موجود في جذر المشروع (C:\xampp\htdocs\power\uploads\stories\)
$targetDir = __DIR__ . "/../uploads/stories/"; 
// لو المجلد غير موجود أنشئه
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$filename = time() . "_" . basename($_FILES["story_media"]["name"]);
$targetFile = $targetDir . $filename;

if (!move_uploaded_file($_FILES["story_media"]["tmp_name"], $targetFile)) {
    die("فشل رفع الملف");
}

// وقت انتهاء بعد 24 ساعة
$expireAt = date("Y-m-d H:i:s", strtotime("+24 hours"));

$conn = new mysqli($host, $username, $password, $db_name);
$stmt = $conn->prepare("
    INSERT INTO stories (user_id, media_path, media_type, expire_at)
    VALUES (?,?,?,?)
");
$stmt->bind_param("isss", $user_id, $filename, $mediaType, $expireAt);
$stmt->execute();
$stmt->close();
$conn->close();

echo "Story uploaded successfully!";
