<?php
// interactions.php: دوال تسجيل التفاعلات وتحديث الاهتمامات
require_once '../admin/config.php'; // تأكد من أن ملف الإعدادات يُعرف متغير الاتصال

/**
 * تسجل التفاعل في جدول user_interactions وتُحدث جدول user_interests
 *
 * @param int $userId معرف المستخدم
 * @param string $contentType نوع المحتوى (post, product, story)
 * @param int $contentId معرف المحتوى
 * @param string $interactionType نوع التفاعل (view, like, comment, search)
 */
function recordInteraction($userId, $contentType, $contentId, $interactionType) {
    global $conn; // افترضنا أن $conn هو متغير الاتصال من config.php

    // تسجيل التفاعل
    $stmt = $conn->prepare("INSERT INTO user_interactions (user_id, content_type, content_id, interaction_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $userId, $contentType, $contentId, $interactionType);
    $stmt->execute();
    $stmt->close();

    // مثال بسيط: إذا كان التفاعل من نوع like أو comment نقوم بتحديث اهتمام عام
    if ($interactionType == 'like' || $interactionType == 'comment') {
        // هنا نستخدم "general" كاهتمام افتراضي، ويمكنك تعديلها لتحديد اهتمام محدد بناءً على المحتوى
        updateUserInterest($userId, "general", 1);
    }
}

/**
 * تحديث أو إضافة سجل اهتمام للمستخدم
 *
 * @param int $userId معرف المستخدم
 * @param string $interest الاهتمام المراد تحديثه
 * @param int $increment قيمة الزيادة في الوزن (افتراضي 1)
 */
function updateUserInterest($userId, $interest, $increment = 1) {
    global $conn;

    // محاولة تحديث السجل إذا كان موجوداً
    $stmt = $conn->prepare("UPDATE user_interests SET weight = weight + ? WHERE user_id = ? AND interest = ?");
    $stmt->bind_param("iis", $increment, $userId, $interest);
    $stmt->execute();
    
    if ($stmt->affected_rows == 0) {
        // إذا لم يكن السجل موجوداً، قم بإدخاله
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO user_interests (user_id, interest, weight) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $userId, $interest, $increment);
        $stmt->execute();
    }
    $stmt->close();
}
?>
