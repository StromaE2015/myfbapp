<?php
session_start();
session_destroy();

// مسح الكوكيز عند تسجيل الخروج
setcookie("user_id", "", time() - 3600, "/");
setcookie("user_name", "", time() - 3600, "/");

header("Location: login.php");
exit();
?>
