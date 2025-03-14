<?php
session_start();

// لو المستخدم عنده كوكيز وملوش سيشن، نحوله للسيشن تلقائيًا
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id'])) {
    $_SESSION['user_id'] = $_COOKIE['user_id'];
    $_SESSION['user_name'] = $_COOKIE['user_name'];
}

// لو مفيش تسجيل دخول، نحوله لصفحة اللوجين
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<h1>مرحبًا، <?php echo $_SESSION['user_name']; ?>!</h1>
<a href="logout.php">تسجيل الخروج</a>
