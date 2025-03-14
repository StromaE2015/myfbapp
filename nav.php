<?php
// بدء الجلسة
require 'admin/config.php';

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}

$user_id = intval($_SESSION['user_id']); // ID المستخدم الحالي
?>

<style>
/* نفس الأكواد السابقة + أكواد خاصة بإشعارات الرسائل */
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

/* حاوية الإشعارات العامة */
#notificationsList {
    display: none;
    position: absolute;
    right: 0;
    top: 3rem; /* المسافة تحت أيقونة الجرس */
    width: 320px;
    max-height: 400px;
    overflow-y: auto;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 0;
}

/* حاوية إشعارات الرسائل */
#messagesList {
    display: none;
    position: absolute;
    right: 0; 
    top: 3rem; /* المسافة تحت أيقونة الماسنجر */
    width: 320px;
    max-height: 400px;
    overflow-y: auto;
    background-color: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 9999;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 0;
}
</style>

<!-- مكتبة jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {

    /*===============================
     1) إشعارات عامة (الجرس)
    ===============================*/
    function fetchGeneralNotifications() {
        $.ajax({
            url: 'assist/notifications.php', // ملف يجلب طلبات الصداقة + إشعارات عامة
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                $("#notificationsList").html(response);

                // عدّ الإشعارات غير المقروءة
                let unreadCount = $("#notificationsList").find(".nav-notif-bold").length;
                if (unreadCount > 0) {
                    $("#notificationCount").text(unreadCount).show();
                } else {
                    $("#notificationCount").hide();
                }
            },
            error: function() {
                console.log("خطأ في جلب الإشعارات العامة!");
            }
        });
    }

    // عند الضغط على أي إشعار عام
    $(document).on("click", ".notification-item", function(e) {
        e.preventDefault();
        let notifLink = $(this);
        let notifId = notifLink.data("id");

        // تعليم الإشعار كمقروء
        $.ajax({
            url: "mark_notification_read.php?id=" + notifId,
            type: "GET",
            success: function() {
                notifLink.removeClass("nav-notif-bold").addClass("nav-notif-read");
                fetchGeneralNotifications();
            }
        });

        // توجيه المستخدم بعد تحديث الإشعار
        window.location.href = notifLink.attr("href");
    });

    // أيقونة الجرس
    $("#notificationBell").on("click", function(e) {
        e.stopPropagation();
        let notifList = $("#notificationsList");
        if (notifList.css("display") === "none") {
            notifList.show();
        } else {
            notifList.hide();
        }
    });

    /*===============================
     2) إشعارات الرسائل (الماسنجر)
    ===============================*/
    function fetchMessageNotifications() {
        $.ajax({
            url: 'assist/messages_notifications.php', // ملف يجلب الرسائل غير المقروءة
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                $("#messagesList").html(response);

                // عدّ الرسائل غير المقروءة
                let unreadMsgCount = $("#messagesList").find(".nav-notif-bold").length;
                if (unreadMsgCount > 0) {
                    $("#messageCount").text(unreadMsgCount).show();
                } else {
                    $("#messageCount").hide();
                }
            },
            error: function() {
                console.log("خطأ في جلب إشعارات الرسائل!");
            }
        });
    }

    // عند الضغط على رسالة جديدة
    $(document).on("click", ".message-item", function(e) {
        e.preventDefault();
        let msgLink = $(this);
        let msgId = msgLink.data("id");

        // تعليم الرسالة كمقروءة
        $.ajax({
            url: "mark_message_read.php?id=" + msgId,
            type: "GET",
            success: function() {
                msgLink.removeClass("nav-notif-bold").addClass("nav-notif-read");
                fetchMessageNotifications();
            }
        });

        // توجيه المستخدم لفتح الشات أو الصفحة المطلوبة
        window.location.href = msgLink.attr("href");
    });

    // أيقونة الماسنجر
    $("#messengerIcon").on("click", function(e) {
        e.stopPropagation();
        let msgList = $("#messagesList");
        if (msgList.css("display") === "none") {
            msgList.show();
        } else {
            msgList.hide();
        }
    });

    // إخفاء القوائم عند الضغط خارجها
    $(document).on("click", function(e) {
        if (!$(e.target).closest("#notificationsList, #notificationBell").length) {
            $("#notificationsList").hide();
        }
        if (!$(e.target).closest("#messagesList, #messengerIcon").length) {
            $("#messagesList").hide();
        }
    });

    // تحديث دوري كل 5 ثوانٍ
    setInterval(function() {
        fetchGeneralNotifications();
        fetchMessageNotifications();
    }, 5000);

    // أول جلب عند تحميل الصفحة
    fetchGeneralNotifications();
    fetchMessageNotifications();

});
</script>

<nav class="bg-white dark:bg-dark-second h-max md:h-14 w-full shadow flex flex-col md:flex-row items-center justify-center md:justify-between fixed top-0 z-50 border-b dark:border-dark-third">

    <!-- LEFT NAV ... (كما في تصميمك) -->
    <div class="flex items-center justify-between w-full md:w-max px-4 py-2">
        <a href="#" class="mr-2 hidden md:inline-block">
            <img src="./images/fb-logo.png" alt="Facebook logo" class="w-24 sm:w-20 lg:w-10 h-auto">
        </a>
        <a href="#" class="inline-block md:hidden">
            <img src="./images/fb-logo-mb.png" alt="" class="w-32 h-auto">
        </a>
        <div class="flex items-center justify-between space-x-1">
            <!-- ... باقي عناصر السيرش ... -->
        </div>
    </div>
    <!-- END LEFT NAV -->

    <!-- MAIN NAV ... (كما في تصميمك) -->
            <!-- MAIN NAV -->
        <ul class="flex w-full lg:w-max items-center justify-center">
            <li class="w-1/5 md:w-max text-center">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block text-blue-500 border-b-4 border-blue-500">
                    <i class='bx bxs-home'></i>
                </a>
            </li>
            <li class="w-1/5 md:w-max text-center">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block rounded text-gray-600 hover:bg-gray-100 dark:hover:bg-dark-third dark:text-dark-txt relative">
                    <i class='bx bxl-messenger'></i>
                    <span class="text-xs absolute top-0 right-1/4 bg-red-500 text-white font-semibold rounded-full px-1 text-center">9+</span>
                </a>
            </li>
            <li class="w-1/5 md:w-max text-center">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block rounded text-gray-600 hover:bg-gray-100 dark:hover:bg-dark-third dark:text-dark-txt relative">
                    <i class='bx bx-store'></i>
                </a>
            </li>
            <li class="w-1/5 md:w-max text-center">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block rounded text-gray-600 hover:bg-gray-100 dark:hover:bg-dark-third dark:text-dark-txt relative">
                    <i class='bx bx-group'></i>
                </a>
            </li>
            <li class="w-1/5 md:w-max text-center hidden md:inline-block">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block rounded text-gray-600 hover:bg-gray-100 dark:hover:bg-dark-third dark:text-dark-txt relative">
                    <i class='bx bx-layout'></i>
                    <span class="text-xs absolute top-0 right-1/4 bg-red-500 text-white font-semibold rounded-full px-1 text-center">9+</span>
                </a>
            </li>
            <li class="w-1/5 md:w-max text-center inline-block md:hidden">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block rounded text-gray-600 hover:bg-gray-100 dark:hover:bg-dark-third dark:text-dark-txt relative">
                    <i class='bx bx-menu'></i>
                </a>
            </li>
        </ul>
        <!-- END MAIN NAV -->


    <!-- RIGHT NAV -->
    <ul class="hidden md:flex mx-4 items-center justify-center relative">
        <li class="h-full hidden xl:flex">
            <a href="#" class="inline-flex items-center justify-center p-1 rounded-full hover:bg-gray-200 dark:hover:bg-dark-third mx-1">
                <img src="./images/tuat.jpg" alt="Profile picture" class="rounded-full h-7 w-7">
                <span class="mx-2 font-semibold dark:text-dark-txt">Tuat</span>
            </a>
        </li>
        <li>
            <div class="text-xl hidden xl:grid place-items-center bg-gray-200 dark:bg-dark-third dark:text-dark-txt rounded-full mx-1 p-3 cursor-pointer hover:bg-gray-300 relative">
                <i class='bx bx-plus'></i>
            </div>
        </li>
        <!-- أيقونة الماسنجر مع عداد الرسائل -->
        <li class="relative">
            <div id="messengerIcon" class="text-xl hidden xl:grid place-items-center bg-gray-200 dark:bg-dark-third dark:text-dark-txt rounded-full mx-1 p-3 cursor-pointer hover:bg-gray-300 relative">
                <i class='bx bxl-messenger'></i>
                <span id="messageCount" class="text-xs absolute top-0 right-0 bg-red-500 text-white font-semibold rounded-full px-1 text-center" style="display:none;">0</span>
            </div>
            <div id="messagesList"></div>
        </li>
        <!-- أيقونة الإشعارات العامة (الجرس) -->
        <li class="relative">
            <div id="notificationBell" class="text-xl grid place-items-center bg-gray-200 dark:bg-dark-third dark:text-dark-txt rounded-full mx-1 p-3 cursor-pointer hover:bg-gray-300 relative">
                <i class='bx bxs-bell'></i>
                <!-- عداد الإشعارات -->
                <span id="notificationCount" class="text-xs absolute top-0 right-0 bg-red-500 text-white font-semibold rounded-full px-1 text-center" style="display:none;">0</span>
            </div>
            <div id="notificationsList">
                <span id="loadingMessage" class="dropdown-item p-2 text-gray-700 block">جارٍ تحميل الإشعارات...</span>
            </div>
        </li>
        <li>
            <div class="text-xl grid place-items-center bg-gray-200 dark:bg-dark-third dark:text-dark-txt rounded-full mx-1 p-3 cursor-pointer hover:bg-gray-300 relative" id="dark-mode-toggle">
                <i class='bx bxs-moon'></i>
            </div>
        </li>
    </ul>
    <!-- END RIGHT NAV -->
</nav>
