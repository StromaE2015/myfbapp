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
            sender_name: "MyName", // أو اسحب الاسم من مكان آخر
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
        <div id="chatBox_${friendId}" class="chat-box fixed bottom-0 bg-white shadow-lg border border-gray-300 rounded-lg w-80">
            <div class="chat-header p-2 bg-blue-500 text-white flex justify-between">
                <span>${friendName}</span>
                <div>
                    <button onclick="minimizeChat(${friendId})" class="text-white mr-2">🗕</button>
                    <button onclick="closeChat(${friendId})" class="text-white">✖</button>
                </div>
            </div>
            <div class="chat-messages p-2 h-60 overflow-y-auto" id="messages_${friendId}">
                <!-- يمكن تركها فارغة أو تضع "لا توجد رسائل بعد" -->
            </div>
            <div class="chat-input p-2 flex">
                <input type="text" id="messageInput_${friendId}" class="flex-1 border p-1 rounded-lg" placeholder="اكتب رسالة..." onkeypress="handleKeyPress(event, ${friendId}, '${friendName}')">
                <button onclick="sendMessage(${friendId}, '${friendName}')" class="ml-2 bg-blue-500 text-white px-3 py-1 rounded-lg">إرسال</button>
            </div>
        </div>
    `;
    $("body").append(chatBox);
    chatWindows.push(`#chatBox_${friendId}`);
    positionChatWindows();
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

    // عرض الرسالة في واجهة الدردشة
    let messageElement = `<div class="sent-message bg-blue-500 text-white p-1 rounded-lg mt-1">${messageText}</div>`;
    $(`#messages_${friendId}`).append(messageElement).scrollTop($(`#messages_${friendId}`)[0].scrollHeight);

    // إرسال عبر WebSocket
    sendMessageWS(friendId, friendName, messageText);

    // مسح النص
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
    msgBox.append(messageElement).scrollTop(msgBox[0].scrollHeight);
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
