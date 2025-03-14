<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'admin/config.php';

// اتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// استرجاع المنشورات (افتراضيًا جدول posts)
$posts = [];
$post_query = "SELECT p.*, u.display_name 
               FROM posts p 
               JOIN users u ON p.user_id = u.id 
               ORDER BY p.created_at DESC";
$result = $conn->query($post_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['type'] = 'post';
        $posts[] = $row;
    }
}

// استرجاع المنتجات الحديثة (من جدول products)
$products = [];
$product_query = "SELECT p.*, u.display_name AS seller_display 
                  FROM products p 
                  JOIN users u ON p.user_id = u.id 
                  ORDER BY p.created_at DESC 
                  LIMIT 10";
$result2 = $conn->query($product_query);
if ($result2) {
    while ($row = $result2->fetch_assoc()) {
        $row['type'] = 'product';
        $products[] = $row;
    }
}

// دمج المنشورات والمنتجات في موجز واحد
$feed = array_merge($posts, $products);
// ترتيب الموجز بحسب created_at تنازلياً
usort($feed, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// جلب أحدث 5 طلبات صداقة
$user_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("
    SELECT friend_requests.id, friend_requests.sender_id, friend_requests.created_at, 
           users.display_name, users.profile_picture 
    FROM friend_requests 
    JOIN users ON friend_requests.sender_id = users.id 
    WHERE friend_requests.receiver_id = ? 
      AND friend_requests.status = 'pending'
    ORDER BY friend_requests.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$resultFR = $stmt->get_result();
$friend_requests = [];
while ($rowFR = $resultFR->fetch_assoc()) {
    $friend_requests[] = $rowFR;
}
$stmt->close();

/** 
 * جلب قصة المستخدم (إن وجدت) 
 * + أول 10 قصص للأعضاء الآخرين
 */
$sql_my = "
  SELECT id, media_path, media_type
  FROM stories
  WHERE user_id = ?
    AND expire_at > NOW()
  ORDER BY created_at DESC
  LIMIT 1
";
$stmt_my = $conn->prepare($sql_my);
$stmt_my->bind_param("i", $user_id);
$stmt_my->execute();
$res_my = $stmt_my->get_result();
$my_story = $res_my->fetch_assoc();
$stmt_my->close();

$hasMyStory = ($my_story != null);

$sql_others = "
  SELECT s.id, s.user_id, s.media_path, s.media_type, s.created_at, s.expire_at,
         u.display_name AS user_name, u.profile_picture
  FROM stories s
  JOIN users u ON s.user_id = u.id
  WHERE s.expire_at > NOW()
    AND s.is_ad = 0
    AND s.user_id != $user_id
  ORDER BY s.created_at DESC
  LIMIT 10
";
$res_others = $conn->query($sql_others);
$other_stories = [];
if ($res_others) {
    while ($st = $res_others->fetch_assoc()) {
        $other_stories[] = $st;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        Facebook - TailwindCSS
    </title>
    <link rel="shortcut icon" href="./images/fb-logo.png" type="image/png">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <link rel="stylesheet" href="./tailwind/tailwind.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
		<script src="js/likes_comments.js"></script>
		<script src="js/ajax.js"></script> 
		<script src="js/media_slider.js"></script>



	<script>
	  var myUserId = <?php echo $_SESSION['user_id']; ?>;
	  var myUserName = "<?php echo $_SESSION['display_name']; ?>";
	</script>
	<script>
	
	/**********************************/
/*        قائمة الأصدقاء         */
/**********************************/
function fetchOnlineFriends() {
    // إن أردت الإبقاء على هذا لعرض الأصدقاء الأونلاين عبر AJAX
    $.ajax({
        url: 'fetch_online_friends.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            let friendsList = $("#onlineFriendsList");
            friendsList.empty();

            if (Array.isArray(response) && response.length > 0) {
                response.forEach(friend => {
                    let friendItem = `
                        <li onclick="openChat(${friend.id}, '${friend.display_name}')"
                            class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="uploads/${friend.profile_picture ? friend.profile_picture : 'default_avatar.jpg'}"
                                     alt="${friend.display_name}"
                                     class="w-10 h-10 rounded-full border border-gray-300 shadow-sm">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 bottom-0 border-white border-2"></span>
                            </div>
                            <div class="flex-1">
                                <span class="font-semibold text-sm text-gray-800 dark:text-dark-txt">${friend.display_name}</span>
                            </div>
                        </li>
                    `;
                    friendsList.append(friendItem);
                });
            } else {
                friendsList.html('<p class="text-gray-500 text-center">لا يوجد أصدقاء متصلون</p>');
            }
        },
        error: function() {
            console.log("فشل في تحميل الأصدقاء المتصلين!");
        }
    });
}
// تحديث قائمة الأصدقاء المتصلين كل 10 ثوانٍ
setInterval(fetchOnlineFriends, 10000);
fetchOnlineFriends(); // عند فتح الصفحة

/**********************************/
/*   واجهة الدردشة + WebSocket    */
/**********************************/
// مصفوفة تحتفظ بمراجع نوافذ الدردشة المفتوحة
let chatWindows = [];

// الاتصال بـ WebSocket
let ws = null;
var myUserId = <?php echo $_SESSION['user_id']; ?>; // تأكد من وجود session_start() قبلها

function connectWebSocket() {
    // شغل سيرفر Ratchet على بورت 8080 مثلاً:
    // php server.php
    // ثم ws = new WebSocket("ws://localhost:8080?user_id=" + myUserId);
    ws = new WebSocket("ws://localhost:8080?user_id=" + myUserId);

    ws.onopen = function() {
        console.log("WebSocket connected!");
    };

    ws.onmessage = function(event) {
        console.log("Message from server:", event.data);
        // غالبًا ما يكون event.data بصيغة JSON
        let data = JSON.parse(event.data);

        // نتوقع data يحتوي على:
        // { "type": "chat", "sender_id": 2, "receiver_id": 1, "message": "Hello", ... }
        if (data.type === "chat") {
            let friendId = data.sender_id;
            let friendName = data.sender_name || ("User" + friendId);
            let message = data.message;

            // إذا لم تكن النافذة مفتوحة، نفتحها
            if (!$(`#chatBox_${friendId}`).length) {
                openChat(friendId, friendName);
            }
            // نعرض الرسالة
            receiveMessage(friendId, friendName, message);
        }
    };

    ws.onclose = function() {
        console.log("WebSocket connection closed");
        // يمكنك إعادة المحاولة بعد وقت
        // setTimeout(connectWebSocket, 5000);
    };

    ws.onerror = function(error) {
        console.error("WebSocket error:", error);
    };
}

// إرسال رسالة عبر WebSocket
function sendMessageWS(friendId, friendName, message) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        let data = {
            type: "chat",
            sender_id: myUserId,
            receiver_id: friendId,
            sender_name: myUserName, // أو اسحب الاسم من مكان آخر
            message: message
        };
        ws.send(JSON.stringify(data));
    } else {
        console.log("WebSocket not connected!");
    }
}

/**********************************/
/*      دوال واجهة الدردشة        */
/**********************************/

 
function openChat(friendId, friendName) {
    if ($(`#chatBox_${friendId}`).length) return;

    let chatBox = `
    <div id="chatBox_${friendId}" 
         class="chat-box fixed bottom-0 right-0 bg-white shadow-lg border border-gray-300 rounded-lg 
                w-80 flex flex-col" 
         style="height: 450px;">
        <!-- شريط العنوان -->
        <div class="chat-header flex items-center justify-between bg-blue-600 text-white px-3 py-2 rounded-t-lg">
            <span class="font-semibold">${friendName}</span>
            <div class="space-x-2">
                <button onclick="minimizeChat(${friendId})" class="hover:text-gray-200">🗕</button>
                <button onclick="closeChat(${friendId})" class="hover:text-gray-200">✖</button>
            </div>
        </div>

        <!-- منطقة الرسائل -->
        <div id="messages_${friendId}" 
             class="chat-messages flex-1 overflow-y-auto p-2 bg-gray-100">
            <!-- الرسائل تظهر هنا -->
        </div>

        <!-- شريط الإدخال -->
        <div class="chat-input border-t bg-white p-2 flex items-center space-x-2">
            <button class="text-gray-500 hover:text-gray-700" 
                    onclick="openFileDialog(${friendId})" 
                    title="إرفاق وسائط">
                <i class="fas fa-paperclip"></i>
            </button>
            <input type="file" id="fileInput_${friendId}" 
                   style="display:none" 
                   accept="image/*,video/*" 
                   onchange="uploadFile(${friendId})">

            <input type="text" id="messageInput_${friendId}" 
                   class="flex-1 border p-1 rounded-lg" 
                   placeholder="اكتب رسالة..."
                   onkeypress="handleKeyPress(event, ${friendId})">

            <button onclick="sendMessage(${friendId}, '${friendName}')" 
                    class="bg-blue-500 text-white px-3 py-1 rounded-lg">
                إرسال
            </button>
        </div>
    </div>
    `;
    $("body").append(chatBox);
    chatWindows.push(`#chatBox_${friendId}`);
    positionChatWindows();

    // 1) جلب أحدث 20 رسالة
    fetchInitialMessages(friendId);

    // 2) تفعيل Infinite Scroll
    let msgContainer = $(`#messages_${friendId}`);
    msgContainer.off("scroll").on("scroll", function() {
        if ($(this).scrollTop() === 0) {
            // المستخدم وصل لأعلى => جلب رسائل أقدم
            loadOlderMessages(friendId);
        }
    });
}


// ترتيب النوافذ
function positionChatWindows() {
    let offset = 10;
    chatWindows.forEach((selector) => {
        $(selector).css("right", `${offset}px`).css("bottom", "0");
        offset += 290;
    });
}

// إرسال رسالة بالضغط على زر الإرسال
function sendMessage(friendId, friendName) {
    let messageInput = $(`#messageInput_${friendId}`);
    let messageText = messageInput.val().trim();
    if (messageText === "") return;

    let msgBox = $(`#messages_${friendId}`);
    let messageElement = `<div class="sent-message bg-blue-500 text-white p-1 rounded-lg mt-1">${messageText}</div>`;

    msgBox.append(messageElement);
    msgBox.scrollTop(msgBox[0].scrollHeight);

    // إرسال عبر WebSocket
    sendMessageWS(friendId, friendName, messageText);

    messageInput.val("");
}


// إرسال عند الضغط على زر "Enter"
function handleKeyPress(event, friendId, friendName) {
    if (event.key === "Enter") {
        sendMessage(friendId, friendName);
    }
}

// استقبال رسالة من الطرف الآخر
function receiveMessage(friendId, friendName, message) {
    let msgBox = $(`#messages_${friendId}`);
    let messageElement = `<div class="received-message bg-gray-300 text-black p-1 rounded-lg mt-1">${message}</div>`;

    // إضافة الرسالة الجديدة
    msgBox.append(messageElement);

    // تمرير الحاوية للأسفل
    msgBox.scrollTop(msgBox[0].scrollHeight);
}


// إغلاق النافذة
function closeChat(friendId) {
    $(`#chatBox_${friendId}`).remove();
    chatWindows = chatWindows.filter(selector => selector !== `#chatBox_${friendId}`);
    positionChatWindows();
}

// تصغير النافذة
function minimizeChat(friendId) {
    let chatBox = $(`#chatBox_${friendId}`);
    if (chatBox.hasClass("minimized")) {
        chatBox.removeClass("minimized").css("height", "auto");
    } else {
        chatBox.addClass("minimized").css("height", "40px");
    }
}

/**********************************/
/*   تحديث النشاط (اختياري)      */
/**********************************/
function updateLastActive() {
    $.ajax({
        url: "update_activity.php",
        type: "GET",
        success: function(response) {
            console.log("تم تحديث وقت آخر نشاط:", response);
        },
        error: function(xhr, status, error) {
            console.error("خطأ أثناء تحديث وقت آخر نشاط:", error);
        }
    });
}
setInterval(updateLastActive, 30000);
updateLastActive();

/**********************************/
/*       تشغيل WebSocket          */
/**********************************/
$(document).ready(function() {
    connectWebSocket(); // اتصل بالسيرفر ws://localhost:8080
});

	function openFileDialog(friendId) {
    document.getElementById(`fileInput_${friendId}`).click();
}

function uploadFile(friendId) {
    let fileInput = document.getElementById(`fileInput_${friendId}`);
    let file = fileInput.files[0];
    if (!file) return;

    // رفع الملف عبر AJAX إلى السيرفر
    let formData = new FormData();
    formData.append("file", file);

    $.ajax({
        url: "upload_media.php", // سكربت PHP لحفظ الملف
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        success: function(response){
            // نفترض أن الاستجابة: { success: true, url: "uploads/..."}
            let data = JSON.parse(response);
            if(data.success){
                // أرسل رابط الصورة/الفيديو عبر WebSocket
                // مثلاً نرسل <img> أو <video> حسب نوع الملف
                let isImage = file.type.startsWith("image/");
                let msg = isImage 
                    ? `<img src="${data.url}" alt="image" style="max-width:200px;">`
                    : `<video src="${data.url}" controls style="max-width:200px;"></video>`;

                // sendMessageWS أو أي دالة لإرسال عبر WebSocket
                sendMessageWS(friendId, /*friendName*/ "Friend", msg);
            }
        }
    });
}

// نخزن أصغر id للرسائل المعروضة في كل محادثة
let oldestIdMap = {};

function loadOlderMessages(friendId) {
    // لو لم نحدد بعد، نستخدم رقم كبير
    let beforeId = oldestIdMap[friendId] || 999999999;

    $.ajax({
        url: 'fetch_older_messages.php',
        type: 'GET',
        data: {
            friend_id: friendId,
            before_id: beforeId
        },
        dataType: 'json',
        success: function(response) {
            console.log("Older messages response:", response);
            if (response.length === 0) {
                console.log("لا مزيد من الرسائل الأقدم.");
                return;
            }

            let container = $(`#messages_${friendId}`);
            let oldScrollHeight = container[0].scrollHeight;

            // إضافة الرسائل في الأعلى
            response.forEach(msg => {
                let msgClass = (msg.sender_id == myUserId)
                  ? "sent-message bg-blue-500 text-white p-1 rounded-lg mt-1 max-w-[70%] ml-auto"
                  : "received-message bg-gray-300 text-black p-1 rounded-lg mt-1 max-w-[70%] mr-auto";

                let messageElement = `<div class="${msgClass}">${msg.message}</div>`;
                container.prepend(messageElement);
            });

            // الحفاظ على موضع التمرير
            let newScrollHeight = container[0].scrollHeight;
            container[0].scrollTop = newScrollHeight - oldScrollHeight;

            // حدّث أصغر id => أول عنصر في response هو الأقدم
            oldestIdMap[friendId] = response[0].id;
        },
        error: function(xhr, status, error) {
            console.log("فشل في جلب الرسائل الأقدم!", error, xhr.responseText);
        }
    });
}


function fetchInitialMessages(friendId) {
    // هنا نستخدم نفس سكربت fetch_older_messages.php مع before_id كبير
    // لجلب أحدث 20 رسالة
    $.ajax({
        url: 'fetch_older_messages.php',
        type: 'GET',
        data: {
            friend_id: friendId,
            before_id: 999999999 // قيمة كبيرة
        },
        dataType: 'json',
        success: function(response) {
            let container = $(`#messages_${friendId}`);
            container.empty(); // مسح أي محتوى قديم

            if (response.length === 0) {
                console.log("لا توجد رسائل بين المستخدم والصديق بعد.");
                return;
            }

            // عرض الرسائل
            response.forEach(msg => {
                let msgClass = (msg.sender_id == myUserId)
                    ? "sent-message bg-blue-500 text-white p-1 rounded-lg mt-1 max-w-[70%] ml-auto"
                    : "received-message bg-gray-300 text-black p-1 rounded-lg mt-1 max-w-[70%] mr-auto";

                let messageElement = `<div class="${msgClass}">${msg.message}</div>`;
                container.append(messageElement);
            });

            // تمرير للأسفل لرؤية آخر رسالة
            container[0].scrollTop = container[0].scrollHeight;

            // حفظ أقدم id (الرسالة الأولى في المصفوفة هي الأقدم بعد array_reverse)
            oldestIdMap[friendId] = response[0].id;
        },
        error: function() {
            console.log("فشل في جلب أحدث الرسائل!");
        }
    });
}

	</script>


<script>
// دالة فتح نافذة اختيار الملف
function triggerHiddenButton() {
    const fileInput = document.getElementById("hiddenBtn");
    if (fileInput) {
        fileInput.value = ""; // إعادة التهيئة
        fileInput.click();    // فتح نافذة الاختيار
    }
}

// ربط حدث التغيير
document.addEventListener("DOMContentLoaded", function() {
    const fileInput = document.getElementById("hiddenBtn");
    if (!fileInput) return;

    // عند اختيار الملف
    fileInput.addEventListener("change", function(e) {
        const file = e.target.files[0];
        if (!file) return;
        // رفع الملف
        uploadStoryFile(file);
    });
});

// دالة رفع الملف عبر AJAX
function uploadStoryFile(file) {
    let formData = new FormData();
    formData.append("story_media", file);

    fetch("assist/create_story.php", {
        method: "POST",
        body: formData
    })
    .then(resp => resp.text())
    .then(data => {
        console.log("Story uploaded:", data);
        // لو لديك دالة fetchStories() لتحديث عرض القصص
        // if (typeof fetchStories === "function") {
        //   fetchStories();
        // }
    })
    .catch(err => {
        console.error("خطأ في رفع القصة:", err);
    });
}

let storyOffset = 10;

function loadMoreStories() {
  fetch(`assist/fetch_stories.php?offset=${storyOffset}`)
    .then(resp => resp.text())
    .then(html => {
      const container = document.getElementById("story");
      container.insertAdjacentHTML("beforeend", html);
      storyOffset += 10;
    })
    .catch(err => console.error("خطأ في جلب المزيد من القصص:", err));
}


</script>


</head>

<body class="bg-gray-100 dark:bg-dark-main">
    <!-- NAV -->

		<?php require 'nav.php'; ?>

    <!-- END NAV -->

    <!-- MAIN -->
    <div class="flex justify-center h-screen">
        <!-- LEFT MENU -->
        <div class="w-1/5 pt-16 h-full hidden xl:flex flex-col fixed top-0 left-0">
            <ul class="p-4">
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/tuat.jpg" alt="Profile picture" class="w-10 h-10 rounded-full">
                        <span class="font-semibold">Tran Anh Tuat</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/friends.png" alt="Profile picture" class="w-10 h-10 rounded-full">
                        <span class="font-semibold">Friends</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/page.png" alt="Profile picture" class="w-10 h-10 rounded-full">
                        <span class="font-semibold">Pages</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/memory.png" alt="Profile picture" class="w-10 h-10 rounded-full">
                        <span class="font-semibold">Memories</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/group.png" alt="Profile picture" class="w-10 h-10 rounded-full">
                        <span class="font-semibold">Groups</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <span class="w-10 h-10 rounded-full grid place-items-center bg-gray-300 dark:bg-dark-second">
                            <i class='bx bx-chevron-down'></i>
                        </span>
                        <span class="font-semibold">See more</span>
                    </a>
                </li>
                <li class="border-b border-gray-200 dark:border-dark-third mt-6"></li>
            </ul>
            <div class="flex justify-between items-center px-4 h-4 group">
                <span class="font-semibold text-gray-500 text-lg dark:text-dark-txt">Your shortcuts</span>
                <span class="text-blue-500 cursor-pointer hover:bg-gray-200 dark:hover:bg-dark-third p-2 rounded-md hidden group-hover:inline-block">Edit</span>
            </div>
            <ul class="p-4">
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/group-img-1.jpg" alt="Profile picture" class="w-10 h-10 rounded-lg">
                        <span class="font-semibold">Cộng đồng Front-end(HTML/CSS/JS) Việt Nam</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/group-img-2.jpg" alt="Profile picture" class="w-10 h-10 rounded-lg">
                        <span class="font-semibold">CNPM08_UIT_Group học tập</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/group-img-3.jpg" alt="Profile picture" class="w-10 h-10 rounded-lg">
                        <span class="font-semibold">Cộng đồng UI/UX Design vietnam</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <img src="./images/group-img-4.jpg" alt="Profile picture" class="w-10 h-10 rounded-lg">
                        <span class="font-semibold">Nihon Koi</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center space-x-2 p-2 hover:bg-gray-200 rounded-lg transition-all dark:text-dark-txt dark:hover:bg-dark-third">
                        <span class="w-10 h-10 rounded-full grid place-items-center bg-gray-300 dark:bg-dark-second">
                            <i class='bx bx-chevron-down'></i>
                        </span>
                        <span class="font-semibold">See more</span>
                    </a>
                </li>
            </ul>
            <div class="mt-auto p-6 text-sm text-gray-500 dark:text-dark-txt">
                <a href="#">Privacy</a>
                <span>.</span>
                <a href="#">Terms</a>
                <span>.</span>
                <a href="#">Advertising</a>
                <span>.</span>
                <a href="#">Cookies</a>
                <span>.</span>
                <a href="#">Ad choices</a>
                <span>.</span>
                <a href="#">More</a>
                <span>.</span>
                <span>Facebook © 2021</span>
            </div>
        </div>
        <!-- END LEFT MENU -->

        <!-- MAIN CONTENT -->
        <div class="w-full lg:w-2/3 xl:w-2/5 pt-32 lg:pt-16 px-2">
            <!-- STORY -->

<div class="relative flex flex-nowrap overflow-x-auto space-x-2 pt-4" 
     style="border:1px solid #ccc; padding:10px;">

  <!-- زر Create Story -->
  <div onclick="triggerHiddenButton()" 
       class="w-1/4 sm:w-1/6 h-44 rounded-xl shadow overflow-hidden flex-none flex flex-col group cursor-pointer">
    <div class="h-3/5 overflow-hidden">
      <img src="./images/profile.jpg" alt="Create Story" 
           class="group-hover:transform group-hover:scale-110 transition-all duration-700">
    </div>
    <div class="flex-1 relative flex items-end justify-center pb-2 text-center leading-none">
      <span class="font-semibold">Create a <br> Story</span>
      <div class="w-10 h-10 rounded-full bg-blue-500 text-white grid place-items-center text-2xl 
                  border-4 border-white absolute -top-5 left-1/2 transform -translate-x-1/2">
        <i class='bx bx-plus'></i>
      </div>
    </div>
  </div>

  <!-- قصة المستخدم إن وجدت -->
  <?php if ($hasMyStory): ?>
    <?php 
      $myMediaPath = htmlspecialchars($my_story['media_path']);
      $myMediaType = $my_story['media_type'];
      $myProfilePic = !empty($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : 'default_avatar.jpg';
    ?>
    <div class="w-1/4 sm:w-1/6 h-44 rounded-xl shadow overflow-hidden flex-none">
      <div class="relative h-full group cursor-pointer" onclick="openStoryViewer(<?= $user_id ?>)">
        <?php if ($myMediaType === 'video'): ?>
          <video src="uploads/stories/<?= $myMediaPath ?>"
                 class="group-hover:scale-110 transition-all duration-700 h-full w-full object-cover"
                 autoplay muted loop></video>
        <?php else: ?>
          <img src="uploads/stories/<?= $myMediaPath ?>"
               alt="My Story"
               class="group-hover:transform group-hover:scale-110 transition-all duration-700 h-full w-full object-cover">
        <?php endif; ?>
        <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
        <span class="absolute bottom-0 left-2 pb-2 font-semibold text-white text-sm sm:text-base">
          Your Story
        </span>
        <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
          <img src="uploads/<?= htmlspecialchars($myProfilePic) ?>" alt="Profile" class="object-cover w-full h-full">
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- عرض أول 10 قصص لأعضاء آخرين -->
  <div id="storiesSection" class="flex flex-nowrap space-x-2">
    <?php foreach ($other_stories as $st): 
      $stUserName  = htmlspecialchars($st['user_name']);
      $stMediaPath = htmlspecialchars($st['media_path']);
      $stMediaType = $st['media_type'];
      $stProfile   = !empty($st['profile_picture']) ? $st['profile_picture'] : 'default_avatar.jpg';
    ?>
      <div class="w-1/4 sm:w-1/6 h-44 rounded-xl shadow overflow-hidden flex-none" style="width: 100%">
        <div class="relative h-full group cursor-pointer" onclick="openStoryViewer(<?= $st['user_id'] ?>)">
          <?php if ($stMediaType === 'video'): ?>
            <video src="uploads/stories/<?= $stMediaPath ?>"
                   class="group-hover:scale-110 transition-all duration-700 h-full w-full object-cover"
                   autoplay muted loop></video>
          <?php else: ?>
            <img src="uploads/stories/<?= $stMediaPath ?>"
                 alt="Story"
                 class="group-hover:transform group-hover:scale-110 transition-all duration-700 h-full w-full object-cover">
          <?php endif; ?>
          <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
          <span class="absolute bottom-0 left-2 pb-2 font-semibold text-white text-sm sm:text-base">
            <?= $stUserName ?>
          </span>
          <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
            <img src="uploads/<?= htmlspecialchars($stProfile) ?>" alt="Profile" class="object-cover w-full h-full">
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- زر "Load More" -->
  <div class="w-12 h-12 rounded-full hidden lg:grid place-items-center text-2xl bg-white 
              absolute -right-6 top-1/2 transform -translate-y-1/2 border border-gray-200 
              cursor-pointer hover:bg-gray-100 shadow text-gray-500"
       onclick="loadMoreStories()">
    <i class='bx bx-right-arrow-alt'></i>
  </div>
</div>
<!-- END STORY -->

<!-- جزء بسيط: الحاوية الظاهرة (Collapsed) -->
<div class="px-4 mt-4 shadow rounded-lg bg-white dark:bg-dark-second">
  <!-- هذا السطر يمثل الحقل المصغّر للمنشور -->
  <div class="p-2 border-b border-gray-300 dark:border-dark-third flex space-x-4" 
       data-bs-toggle="modal" data-bs-target="#createPostModal"
       style="cursor:pointer;">
    <img src="./images/tuat.jpg" alt="Profile picture" class="w-10 h-10 rounded-full">
    <div class="flex-1 bg-gray-100 rounded-full flex items-center justify-start pl-4 dark:bg-dark-third text-gray-500 text-lg dark:text-dark-txt">
      <span>What's on your mind, Tuat?</span>
    </div>
  </div>

  <!-- اختصارات سريعة (Live video / Photo / Feeling) -->
  <div class="p-2 flex">
    <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl sm:text-3xl py-2 rounded-lg cursor-pointer text-red-500">
      <i class='bx bxs-video-plus'></i>
      <span class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-dark-txt">Live video</span>
    </div>
    <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl sm:text-3xl py-2 rounded-lg cursor-pointer text-green-500">
      <i class='bx bx-images'></i>
      <span class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-dark-txt">Photo/Video</span>
    </div>
    <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl sm:text-3xl py-2 rounded-lg cursor-pointer text-yellow-500">
      <i class='bx bx-smile'></i>
      <span class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-dark-txt">Feeling/Activity</span>
    </div>


<!-- END POST "collapsed" FORM -->

<!-- مودال إنشاء المنشور (Bootstrap 5) -->

  </div>
  <!-- باقي اختصارات مثل Live video ... الخ -->
</div>

<!-- مودال إنشاء المنشور (Bootstrap 5) -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <!-- عنوان المودال -->
      <div class="modal-header">
        <h5 class="modal-title" id="createPostModalLabel">Create Post</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- نموذج إضافة المنشور -->
      <form action="assist/create_post.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="d-flex align-items-center mb-3">
            <img src="./images/tuat.jpg" alt="Profile" style="width:40px;height:40px;" class="rounded-full">
            <span class="ms-2 fw-bold">UserName</span>
          </div>
          <!-- حقل النص -->
          <div class="mb-3">
            <textarea name="content" rows="3" class="form-control" placeholder="What's on your mind?"></textarea>
          </div>
          <!-- حقل رفع الملفات (متعددة) -->
          <div class="mb-3">
            <label for="postMedia" class="form-label">Upload Images/Videos</label>
            <input type="file" name="media[]" id="postMedia" class="form-control" accept="image/*,video/*" multiple>
            <!-- multiple يسمح برفع عدة ملفات -->
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Post</button>
        </div>
      </form>
      <!-- نهاية النموذج -->
    </div>
  </div>
</div>
<!-- END MODAL -->


            <!-- ROOM -->
            <div class="p-4 mt-4 shadow rounded-lg bg-white dark:bg-dark-second overflow-hidden">
                <div class="flex space-x-4 relative">
                    <div class="w-1/2 lg:w-3/12 flex space-x-2 items-center justify-center border-2 border-blue-200 dark:border-blue-700 rounded-full cursor-pointer">
                        <i class='bx bxs-video-plus text-2xl text-purple-500'></i>
                        <span class="text-sm font-semibold text-blue-500">Create Room</span>
                    </div>
                    <div class="relative cursor-pointer">
                        <img src="./images/avt-3.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="relative cursor-pointer">
                        <img src="./images/avt-4.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>

                    <div class="w-12 h-12 rounded-full hidden lg:grid place-items-center text-2xl text-gray-500 bg-white absolute right-0 top-1/2 transform -translate-y-1/2 border border-gray-200 cursor-pointer hover:bg-gray-100 shadow dark:bg-dark-third dark:border-dark-third dark:text-dark-txt">
                        <i class='bx bxs-chevron-right'></i>
                    </div>
                </div>
            </div>




            <!-- LIST POST -->

            <div>



<!-- ثم عرف عنصرنا المخصص -->
  <div id="postsSection"></div>



        </div>
        <!-- END MAIN CONTENT -->

<!-- RIGHT MENU -->
<div class="w-1/5 pt-16 h-full hidden xl:block px-4 fixed top-0 right-0">
    <div class="h-full overflow-y-auto">
        
        <!-- طلبات الصداقة -->
        <div class="px-4 pt-4">
            <div class="flex justify-between items-center">
                <center><span class="font-semibold text-gray-500 text-lg dark:text-dark-txt">طلبات الصداقة</span></center>
                <?php if (count($friend_requests) > 4): ?>
                    <a href="friend_requests.php" class="text-blue-500 cursor-pointer hover:bg-gray-200 dark:hover:bg-dark-third p-2 rounded-md">
                        عرض الكل
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-2">
            <?php if (!empty($friend_requests)): ?>
                <?php foreach (array_slice($friend_requests, 0, 4) as $request): ?>
                    <div class="flex items-center space-x-4 p-2 bg-white dark:bg-dark-second shadow-md rounded-lg transition-all">
                        <img src="uploads/<?= !empty($request['profile_picture']) ? $request['profile_picture'] : 'default_avatar.jpg' ?>" 
                             alt="<?= htmlentities($request['display_name']) ?>" 
                             class="w-12 h-12 rounded-full border border-gray-300">
                        <div class="flex-1">
                            <div class="dark:text-dark-txt">
                                <span class="font-semibold text-gray-800 dark:text-white"><?= htmlentities($request['display_name']) ?></span>
                                <span class="float-right text-sm text-gray-500"><?= date("d M", strtotime($request['created_at'])) ?></span>
                            </div>
                            <div class="flex space-x-2 mt-2">
                                <a href="accept_friend_request.php?id=<?= $request['id'] ?>" 
                                   class="w-1/2 bg-blue-500 cursor-pointer py-1 text-center font-semibold text-white rounded-lg">
                                    تأكيد
                                </a>
                                <a href="reject_friend_request.php?id=<?= $request['id'] ?>" 
                                   class="w-1/2 bg-gray-300 cursor-pointer py-1 text-center font-semibold text-black rounded-lg">
                                    رفض
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <center><p class="text-gray-500 text-center py-2">لا توجد طلبات صداقة</p></center>
            <?php endif; ?>
        </div>

        <div class="border-b border-gray-200 dark:border-dark-third mt-6"></div>

        <!-- قائمة الأصدقاء -->
       <div class="flex justify-between items-center px-4 pt-4 text-gray-500 dark:text-dark-txt">
    <span class="font-semibold text-lg">الأصدقاء المتصلون</span>
</div>
<ul id="onlineFriendsList" class="p-2">
</ul>


    </div>

</div>






</div>
        </div>
        <!-- END RIGHT MENU -->
            <div style="height: 450px;" id="chatBox" class="fixed bottom-0 right-5 w-80 bg-white border border-gray-300 shadow-lg rounded-lg hidden">
  </div>
    </div>
    <!-- END MAIN -->

    <script src="./static/app.js"></script>


<input type="file" id="hiddenBtn" name="story_media" 
       style="display:none" accept="image/*,video/*">


</body>
</html>
