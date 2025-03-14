<?php
session_start();
require 'admin/config.php';

// تأكد من أن المستخدم مسجّل الدخول
if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = intval($_SESSION['user_id']); // ID المستخدم الحالي

// جلب طلبات الصداقة المعلقة لهذا المستخدم
$stmt = $conn->prepare("
    SELECT friend_requests.id, friend_requests.sender_id, users.display_name, users.profile_picture, friend_requests.created_at 
    FROM friend_requests 
    JOIN users ON friend_requests.sender_id = users.id 
    WHERE friend_requests.receiver_id = ? AND friend_requests.status = 'pending'
    ORDER BY friend_requests.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$friend_requests = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>طلبات الصداقة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h3 class="mb-4">طلبات الصداقة</h3>
    
    <?php if (!empty($friend_requests)): ?>
        <?php foreach ($friend_requests as $request): ?>
            <div class="card mb-3 p-3">
                <div class="d-flex align-items-center">
                    <img src="uploads/<?= !empty($request['profile_picture']) ? $request['profile_picture'] : 'default_avatar.jpg' ?>" alt="Profile" class="rounded-circle me-3" style="width: 50px; height: 50px;">
                    <div class="flex-grow-1">
                        <strong><?= htmlentities($request['display_name']) ?></strong>
                        <p class="text-muted mb-0"><?= date("d M Y - H:i", strtotime($request['created_at'])) ?></p>
                    </div>
                    <div class="d-flex">
                        <a href="accept_friend_request.php?id=<?= $request['id'] ?>" class="btn btn-success btn-sm me-2">تأكيد</a>
                        <a href="reject_friend_request.php?id=<?= $request['id'] ?>" class="btn btn-danger btn-sm">رفض</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-center text-muted">لا توجد طلبات صداقة جديدة.</p>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
