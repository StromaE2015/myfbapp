<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'admin/config.php'; // ملف الاتصال بقاعدة البيانات

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_GET['partner_id'])) {
    die("No partner id provided.");
}

$partner_id = intval($_GET['partner_id']);
$user_id = intval($_SESSION['user_id']);

// استرجاع بيانات الشريك (اسم العرض والصورة)
$stmt = $conn->prepare("SELECT id, display_name, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $partner_id);
$stmt->execute();
$partner_result = $stmt->get_result();
if ($partner_result->num_rows == 0) {
    die("Partner not found.");
}
$partner = $partner_result->fetch_assoc();
$stmt->close();

// معالجة إرسال رسالة جديدة
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $new_message = trim($_POST['message']);
    if (!empty($new_message)) {
        // إدراج الرسالة في جدول chats
        $stmt = $conn->prepare("INSERT INTO chats (sender_id, receiver_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $user_id, $partner_id, $new_message);
        $stmt->execute();
        $stmt->close();
    }
    // إعادة تحميل الصفحة لتحديث المحادثة
    header("Location: chat.php?partner_id=" . $partner_id);
    exit();
}

// استرجاع جميع الرسائل بين المستخدم والشريك
$stmt = $conn->prepare("SELECT * FROM chats WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->bind_param("iiii", $user_id, $partner_id, $partner_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>دردشة مع <?= htmlentities($partner['display_name']) ?> - Fakebook</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
      body {
          background-color: #f0f2f5;
      }
      .chat-container {
          max-width: 800px;
          margin: 20px auto;
          background: #fff;
          border-radius: 10px;
          box-shadow: 0 4px 8px rgba(0,0,0,0.1);
          padding: 20px;
      }
      .message {
          padding: 10px;
          margin-bottom: 10px;
          border-radius: 5px;
      }
      .message.sent {
          background-color: #e1ffc7;
          text-align: right;
      }
      .message.received {
          background-color: #f1f0f0;
          text-align: left;
      }
      .message small {
          display: block;
          color: #666;
      }
  </style>
</head>
<body>
<?php include 'nav.php'; // تضمين النافا بار ?>
<div class="container chat-container">
  <h4>دردشة مع <?= htmlentities($partner['display_name']) ?></h4>
  <hr>
  <div class="chat-messages">
    <?php if(count($messages) > 0): ?>
      <?php foreach($messages as $msg): ?>
        <?php if($msg['sender_id'] == $user_id): ?>
          <div class="message sent">
            <p><?= htmlentities($msg['message']) ?></p>
            <small><?= htmlentities($msg['created_at']) ?></small>
          </div>
        <?php else: ?>
          <div class="message received">
            <p><?= htmlentities($msg['message']) ?></p>
            <small><?= htmlentities($msg['created_at']) ?></small>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php else: ?>
      <p>لا توجد رسائل بعد. ابدأ المحادثة الآن!</p>
    <?php endif; ?>
  </div>
  <hr>
  <form method="POST">
    <div class="mb-3">
      <textarea name="message" class="form-control" rows="3" placeholder="أدخل رسالتك هنا" required></textarea>
    </div>
    <button type="submit" name="send_message" class="btn btn-primary">إرسال</button>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
