function fetchNotifications() {
    $.ajax({
        url: 'fetch_notifications.php', // ملف جلب الإشعارات
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let notificationsList = $("#notificationsList");
            let notificationCount = $("#notification_count");

            notificationsList.empty(); // مسح الإشعارات القديمة

            if (response.count > 0) {
                notificationCount.text(response.count).show();
                response.notifications.forEach(notification => {
                    notificationsList.append(
                        `<li><a class="dropdown-item notification-item" href="${notification.link}" data-id="${notification.id}">${notification.message}</a></li>`
                    );
                });
            } else {
                notificationCount.hide();
                notificationsList.append('<li><a class="dropdown-item text-center" href="#">لا يوجد إشعارات جديدة</a></li>');
            }
        }
    });
}

// تحديث حالة الإشعار عند الضغط عليه
$(document).on("click", ".notification-item", function() {
    let notificationId = $(this).data("id");

    $.post("mark_as_read.php", { id: notificationId }, function() {
        $("#notification_count").hide();
    });
});

// تحميل الإشعارات عند فتح الصفحة وتحديثها كل 10 ثوانٍ
$(document).ready(function() {
    fetchNotifications();
    setInterval(fetchNotifications, 10000);
});
