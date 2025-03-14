<?php
// بدء الجلسة
require 'admin/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}

$user_id = intval($_SESSION['user_id']); // ID المستخدم الحالي
?>

<!-- هنا ممكن تضيف وسم <style> لتعريف بعض الكلاسات بدلاً من الـ inline-styles -->
<style>
/* مثال لتصميم الإشعارات وطلب الصداقة */
.nav-notif-item {
    padding: 10px 15px; 
    font-size: 14px; 
    display: flex; 
    align-items: center; 
    color: #050505; 
    border-bottom: 1px solid #ddd;
    text-decoration: none;
    transition: background-color 0.2s;
}

.nav-notif-item:hover {
    background-color: #f5f6f7;
}

.nav-notif-bold {
    background-color: #d1ecf1; 
    font-weight: bold;
}

.nav-notif-read {
    background-color: #f1f1f1; 
    font-weight: normal;
}

.nav-friend-req {
    list-style: none; 
    padding: 10px; 
    font-weight: bold; 
    background: #f8f9fa;
}

.nav-no-notifs {
    list-style: none;
    padding: 10px 15px;
    font-size: 14px; 
    color: #050505;
    text-align: center;
}
</style>

<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Fakebook</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="products.php"><i class="fas fa-users"></i> المتجر</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="editproduct.php"><i class="fas fa-users-cog"></i> عرض منتجاتك</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="userproducts.php"><i class="fas fa-bookmark"></i> إضافة منتج</a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" 
             style="position: relative;">
            <img src="img/Notification Bell.png" alt="Notifications" style="width:24px; height:auto;">
            <span class="badge bg-danger" id="notificationCount"
                  style="position: absolute; top:0; right:0; font-size:12px;">
              0
            </span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end" id="notificationsList" 
              style="width:320px; max-height:400px; overflow-y:auto; padding:0; border:none; background-color:#fff; box-shadow:0 4px 12px rgba(0,0,0,0.15);">
            <li style="list-style:none;">
              <span class="dropdown-item" id="loadingMessage"
                    style="padding:10px 15px; font-size:14px; color:#050505;">جارٍ تحميل الإشعارات...</span>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- مكتبة jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function fetchNotifications() {
        $.ajax({
            url: 'notifications.php',
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                $("#notificationsList").html(response);

                // عدّ الإشعارات غير المقروءة بناءً على خلفية / ستايل معين
                // هنا نبحث عن العناصر ذات class nav-notif-bold مثلاً
                let unreadCount = $("#notificationsList").find(".nav-notif-bold").length;

                if (unreadCount > 0) {
                    $("#notificationCount").text(unreadCount).show();
                } else {
                    $("#notificationCount").hide();
                }
            },
            error: function() {
                console.log("خطأ في جلب الإشعارات!");
            }
        });
    }

    // تحديث كل 5 ثوانٍ بدلاً من كل ثانية
    setInterval(fetchNotifications, 5000);

    // تحديث عند الضغط على أي إشعار
    $(document).on("click", ".notification-item", function(e) {
        e.preventDefault();
        let notifLink = $(this);
        let notifId = notifLink.data("id");

        $.ajax({
            url: "mark_notification_read.php?id=" + notifId,
            type: "GET",
            success: function() {
                // بدل notifLink.css({"background-color": "#f1f1f1", "font-weight": "normal"});
                // استخدمنا كلاس
                notifLink.removeClass("nav-notif-bold").addClass("nav-notif-read");
                fetchNotifications();
            }
        });

        // توجيه المستخدم بعد تحديث الإشعار
        window.location.href = notifLink.attr("href");
    });

    fetchNotifications();
});
</script>
