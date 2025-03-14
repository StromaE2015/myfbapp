<?php
session_start();
require '../admin/config.php';  // عدّل المسار حسب مشروعك

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}
$user_id = $_SESSION['user_id'];

// محتوى المنشور
$content = $_POST['content'] ?? '';
$content = trim($content);

// إنشاء اتصال
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1) إدخال المنشور
$stmt = $conn->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $content);
$stmt->execute();
$post_id = $stmt->insert_id;
$stmt->close();

// 2) رفع الملفات (لو لديك حقل `media[]` متعدد مثلاً)
if (isset($_FILES['media'])) {
    // مثال مختصر
    foreach ($_FILES['media']['tmp_name'] as $i => $tmpPath) {
        if ($_FILES['media']['error'][$i] === 0) {
            $type = mime_content_type($tmpPath);
            $ext  = pathinfo($_FILES['media']['name'][$i], PATHINFO_EXTENSION);

            // قرر هل هو صورة أم فيديو
            $media_type = 'image';
            if (strpos($type, 'video') !== false) {
                $media_type = 'video';
            }
            // اسم الملف
            $newName = time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
            $target = '../uploads/posts/' . $newName;  // مجلد رفع وسائط المنشورات

            move_uploaded_file($tmpPath, $target);

            // إدخال في جدول post_media
            $stm = $conn->prepare("INSERT INTO post_media (post_id, media_type, media_path) VALUES (?,?,?)");
            $stm->bind_param("iss", $post_id, $media_type, $newName);
            $stm->execute();
            $stm->close();
        }
    }
}

$conn->close();

// إعادة التوجيه أو إعادة JSON
echo "تم إنشاء المنشور بنجاح!";
// أو: header("Location: ../index.php"); exit;
