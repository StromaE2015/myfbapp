<?php
require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $connDB;

    public function __construct() {
        $this->clients = [];

        // فتح اتصال قاعدة البيانات مرة واحدة
        $this->connDB = new mysqli("localhost", "root", "", "store_db");
        if ($this->connDB->connect_error) {
            die("Database Connection Failed: " . $this->connDB->connect_error);
        }
    }

public function onOpen(ConnectionInterface $conn) {
    // استخراج user_id من رابط الاتصال
    $querystring = $conn->httpRequest->getUri()->getQuery();
    parse_str($querystring, $queryParams);
    
    $user_id = $queryParams['user_id'] ?? null;

    if (!$user_id) {
        echo "Connection rejected: Missing user_id\n";
        $conn->close();
        return;
    }

    // ربط المستخدم بالاتصال
    $this->clients[$user_id] = $conn;

    echo "User $user_id connected\n";
}

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Received: $msg\n";
        $data = json_decode($msg, true);
        $sender_id = $data['sender_id'] ?? 0;
        $receiver_id = $data['receiver_id'] ?? 0;
        $message = $data['message'] ?? '';

        // تجهيز الاستعلام
        $stmt = $this->connDB->prepare("INSERT INTO messages (sender_id, receiver_id, message, status) VALUES (?, ?, ?, 'sent')");
        if (!$stmt) {
            die("Prepare Failed: " . $this->connDB->error);
        }

        // ربط القيم
        if (!$stmt->bind_param("iis", $sender_id, $receiver_id, $message)) {
            die("Binding Parameters Failed: " . $stmt->error);
        }

        // تنفيذ الاستعلام
        if (!$stmt->execute()) {
            die("Execute Failed: " . $stmt->error);
        } else {
            echo "Message Saved Successfully\n";
            $stmt->close();
        }

        

        // إرسال الرسالة للمستقبل لو كان متصل
        if (isset($this->clients[$receiver_id])) {
            $this->clients[$receiver_id]->send($msg);
        }
    }

    public function onClose(ConnectionInterface $conn) {
        foreach ($this->clients as $uid => $clientConn) {
            if ($clientConn === $conn) {
                unset($this->clients[$uid]);
                echo "User {$uid} disconnected\n";
                break;
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

// تشغيل السيرفر
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "WebSocket server running on ws://localhost:8080\n";
$server->run();
