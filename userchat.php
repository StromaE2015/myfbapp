<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>دردشة مشابهة لفيسبوك</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- تضمين FontAwesome للأيقونات -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* تصميم سطح المكتب */
    .chat-container {
      display: flex;
      height: 80vh;
      border: 1px solid #ddd;
      border-radius: 5px;
      overflow: hidden;
    }
    .chat-sidebar {
      width: 25%;
      border-right: 1px solid #ddd;
      overflow-y: auto;
      padding: 15px;
      background-color: #fff;
    }
    .chat-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      background-color: #f9f9f9;
    }
    .chat-header {
      background-color: #1877f2;
      color: #fff;
      padding: 10px 15px;
      font-size: 1rem;
    }
    .chat-messages {
      flex: 1;
      padding: 15px;
      overflow-y: auto;
      background-color: #f0f2f5;
    }
    .chat-message {
      margin-bottom: 10px;
      padding: 10px;
      border-radius: 10px;
      max-width: 70%;
      word-wrap: break-word;
    }
    .chat-message.sent {
      background-color: #e1ffc7;
      align-self: flex-end;
      text-align: right;
    }
    .chat-message.received {
      background-color: #fff;
      align-self: flex-start;
      text-align: left;
      border: 1px solid #ddd;
    }
    .chat-input {
      padding: 10px;
      border-top: 1px solid #ddd;
      background-color: #fff;
    }
    /* تصميم للهواتف */
    @media (max-width: 576px) {
      .chat-container {
        flex-direction: column;
        height: 100vh;
      }
      .chat-sidebar {
        display: none; /* على الهاتف، القائمة الجانبية تُخفي */
      }
      .chat-main {
        height: 100%;
      }
      .chat-header {
        font-size: 1.1rem;
      }
      .chat-input textarea {
        height: 50px;
      }
    }
  </style>
</head>
<body>
  <!-- مثال: شريط تنقل (يمكنك تضمينه مع الـ nav.php الخاص بك) -->
  <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #1877f2;">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Fakebook</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- باقي الروابط هنا -->
    </div>
  </nav>
  
  <div class="container-fluid mt-3">
    <div class="chat-container">
      <!-- قائمة المحادثات (لسطح المكتب) -->
      <div class="chat-sidebar">
        <h5>المحادثات</h5>
        <ul class="list-group">
          <li class="list-group-item">محادثة مع أحمد</li>
          <li class="list-group-item">محادثة مع منى</li>
          <li class="list-group-item">محادثة مع سامي</li>
          <!-- المزيد من المحادثات -->
        </ul>
      </div>
      <!-- نافذة الدردشة الرئيسية -->
      <div class="chat-main">
        <div class="chat-header">
          <i class="fas fa-arrow-left d-sm-none me-2"></i>
          <span>دردشة مع [اسم الشريك]</span>
        </div>
        <div class="chat-messages d-flex flex-column">
          <!-- مثال على رسالة مرسلة -->
          <div class="chat-message sent">
            <p><strong>أنت:</strong> مرحبًا، كيف حالك؟</p>
            <small>10:00 صباحاً</small>
          </div>
          <!-- مثال على رسالة مستلمة -->
          <div class="chat-message received">
            <p><strong>الشريك:</strong> أنا بخير، شكرًا! وأنت؟</p>
            <small>10:01 صباحاً</small>
          </div>
          <!-- المزيد من الرسائل -->
        </div>
        <div class="chat-input">
          <div class="input-group">
            <textarea class="form-control" placeholder="أدخل رسالتك هنا..." aria-label="رسالتك"></textarea>
            <button class="btn btn-primary" type="button"><i class="fas fa-paper-plane"></i></button>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
