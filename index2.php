<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require 'admin/config.php';
$user_id = intval($_SESSION['user_id']); // ID المستخدم الحالي

$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// استرجاع المنشورات (نفترض وجود جدول posts يحتوي على عمود content وcreated_at)
$posts = [];
$post_query = "SELECT p.*, u.display_name FROM posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC";
$result = $conn->query($post_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['type'] = 'post';
        $posts[] = $row;
    }
}

// استرجاع المنتجات الحديثة (من جدول products)
$products = [];
$product_query = "SELECT p.*, u.display_name AS seller_display FROM products p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 10";
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
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="./tailwind/tailwind.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-gray-100 dark:bg-dark-main">
    <!-- NAV -->
    <nav class="bg-white dark:bg-dark-second h-max md:h-14 w-full shadow flex flex-col md:flex-row items-center justify-center md:justify-between fixed top-0 z-50 border-b dark:border-dark-third">

        <!-- LEFT NAV -->
        <div class="flex items-center justify-between w-full md:w-max px-4 py-2">
            <a href="#" class="mr-2 hidden md:inline-block">
                <img src="./images/fb-logo.png" alt="Facebook logo" class="w-24 sm:w-20 lg:w-10 h-auto">
            </a>
            <a href="#" class="inline-block md:hidden">
                <img src="./images/fb-logo-mb.png" alt="" class="w-32 h-auto">
            </a>
            <div class="flex items-center justify-between space-x-1">
                <div class="relative bg-gray-100 dark:bg-dark-third px-2 py-2 w-10 h-10 sm:w-11 sm:h-11 lg:h-10 lg:w-10 xl:w-max xl:pl-3 xl:pr-8 rounded-full flex items-center justify-center cursor-pointer">
                    <i class='bx bx-search-alt-2 text-xl xl:mr-2 dark:text-dark-txt'></i>
                    <input type="text" placeholder="Search Facebook" class="outline-none bg-transparent hidden xl:inline-block">
                </div>
                <div class="text-2xl grid place-items-center md:hidden bg-gray-200 dark:bg-dark-third rounded-full w-10 h-10 cursor-pointer hover:bg-gray-300 dark:text-dark-txt">
                    <i class='bx bxl-messenger'></i>
                </div>
                <div class="text-2xl grid place-items-center md:hidden bg-gray-200 dark:bg-dark-third rounded-full w-10 h-10 cursor-pointer hover:bg-gray-300 dark:text-dark-txt" id="dark-mode-toggle-mb">
                    <i class='bx bxs-moon'></i>
                </div>
            </div>
        </div>
        <!-- END LEFT NAV -->

        <!-- MAIN NAV -->
        <ul class="flex w-full lg:w-max items-center justify-center">
            <li class="w-1/5 md:w-max text-center">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block text-blue-500 border-b-4 border-blue-500">
                    <i class='bx bxs-home'></i>
                </a>
            </li>
            <li class="w-1/5 md:w-max text-center">
                <a href="#" class="w-full text-3xl py-2 px-3 xl:px-12 cursor-pointer text-center inline-block rounded text-gray-600 hover:bg-gray-100 dark:hover:bg-dark-third dark:text-dark-txt relative">
                    <i class='bx bx-movie-play'></i>
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
        <ul class="hidden md:flex mx-4 items-center justify-center">
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
            <li>
                <div class="text-xl hidden xl:grid place-items-center bg-gray-200 dark:bg-dark-third dark:text-dark-txt rounded-full mx-1 p-3 cursor-pointer hover:bg-gray-300 relative">
                    <i class='bx bxl-messenger'></i>
                </div>
            </li>
            <li>
                <div class="text-xl grid place-items-center bg-gray-200 dark:bg-dark-third dark:text-dark-txt rounded-full mx-1 p-3 cursor-pointer hover:bg-gray-300 relative">
                    <i class='bx bxs-bell'></i>
                    <span class="text-xs absolute top-0 right-0 bg-red-500 text-white font-semibold rounded-full px-1 text-center">9</span>
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
            <div class="relative flex space-x-2 pt-4">
                <div class="w-1/4 sm:w-1/6 h-44 rounded-xl shadow overflow-hidden flex flex-col group cursor-pointer">
                    <div class="h-3/5 overflow-hidden">
                        <img src="./images/profile.jpg" alt="picture" class="group-hover:transform group-hover:scale-110 transition-all duration-700">
                    </div>
                    <div class="flex-1 relative flex items-end justify-center pb-2 text-center leading-none dark:bg-dark-second dark:text-dark-txt">
                        <span class="font-semibold">
                            Create a <br> Story
                        </span>
                        <div class="w-10 h-10 rounded-full bg-blue-500 text-white grid place-items-center text-2xl border-4 border-white dark:border-dark-second absolute -top-5 left-1/2 transform -translate-x-1/2">
                            <i class='bx bx-plus'></i>
                        </div>
                    </div>
                </div>
                <div class="w-1/4 sm:w-1/6 h-44 rounded-xl overflow-hidden">
                    <div class="relative h-full group cursor-pointer">
                        <img src="./images/story.jpg" alt="Story images" class="group-hover:transform group-hover:scale-110 transition-all duration-700 h-full w-full">
                        <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
                        <span class="absolute bottom-0 left-2 pb-2 font-semibold text-white">
                            Your story
                        </span>
                        <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
                            <img src="./images/tuat.jpg" alt="Profile picture">
                        </div>
                    </div>
                </div>
                <div class="w-1/4 sm:w-1/6 h-44 rounded-xl overflow-hidden">
                    <div class="relative h-full group cursor-pointer">
                        <img src="./images/story-1.jpg" alt="Story images" class="group-hover:transform group-hover:scale-110 transition-all duration-700 h-full w-full">
                        <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
                        <span class="absolute bottom-0 left-2 pb-2 font-semibold text-white">
                            Lorem
                        </span>
                        <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
                            <img src="./images/avt-7.jpg" alt="Profile picture">
                        </div>
                    </div>
                </div>
                <div class="w-1/4 sm:w-1/6 h-44 rounded-xl overflow-hidden">
                    <div class="relative h-full group cursor-pointer">
                        <img src="./images/story-2.jpg" alt="Story images" class="group-hover:transform group-hover:scale-110 transition-all duration-700 h-full w-full">
                        <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
                        <span class="absolute bottom-0 left-2 pb-2 font-semibold text-white">
                            John Doe
                        </span>
                        <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
                            <img src="./images/avt-6.png" alt="Profile picture">
                        </div>
                    </div>
                </div>
                <div class="hidden sm:inline-block w-1/4 sm:w-1/6 h-44 rounded-xl overflow-hidden">
                    <div class="relative h-full group cursor-pointer">
                        <img src="./images/story-3.jpg" alt="Story images" class="group-hover:transform group-hover:scale-110 transition-all duration-700 h-full w-full">
                        <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
                        <span class="absolute bottom-0 left-2 pb-2 font-semibold text-white">
                            John Doe
                        </span>
                        <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
                            <img src="./images/avt-6.png" alt="Profile picture">
                        </div>
                    </div>
                </div>
                <div class="hidden sm:inline-block w-1/4 sm:w-1/6 h-44 rounded-xl overflow-hidden">
                    <div class="relative h-full group cursor-pointer">
                        <img src="./images/story-4.jpg" alt="Story images" class="group-hover:transform group-hover:scale-110 transition-all duration-700 h-full w-full">
                        <div class="w-full h-full bg-black absolute top-0 left-0 bg-opacity-10"></div>
                        <span class="absolute bottom-0 left-2 pb-2 font-semibold text-white">
                            John Doe
                        </span>
                        <div class="w-10 h-10 rounded-full overflow-hidden absolute top-2 left-2 border-4 border-blue-500">
                            <img src="./images/avt-5.jpg" alt="Profile picture">
                        </div>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-full hidden lg:grid place-items-center text-2xl bg-white absolute -right-6 top-1/2 transform -translate-y-1/2 border border-gray-200 cursor-pointer hover:bg-gray-100 shadow text-gray-500 dark:bg-dark-third dark:border-dark-third dark:text-dark-txt">
                    <i class='bx bx-right-arrow-alt'></i>
                </div>
            </div>
            <!-- END STORY -->

            <!-- POST FORM -->
            <div class="px-4 mt-4 shadow rounded-lg bg-white dark:bg-dark-second">
                <div class="p-2 border-b border-gray-300 dark:border-dark-third flex space-x-4">
                    <img src="./images/tuat.jpg" alt="Profile picture" class="w-10 h-10 rounded-full">
                    <div class="flex-1 bg-gray-100 rounded-full flex items-center justify-start pl-4 cursor-pointer dark:bg-dark-third text-gray-500 text-lg dark:text-dark-txt">
                        <span>
                            What's on your mind, Tuat?
                        </span>
                    </div>
                </div>
                <div class="p-2 flex">
                    <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl sm:text-3xl py-2 rounded-lg cursor-pointer text-red-500">
                        <i class='bx bxs-video-plus'></i>
                        <span class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-dark-txt">Live video</span>
                    </div>
                    <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl sm:text-3xl py-2 rounded-lg cursor-pointer text-green-500">
                        <i class='bx bx-images'></i>
                        <span class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-dark-txt">Live video</span>
                    </div>
                    <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl sm:text-3xl py-2 rounded-lg cursor-pointer text-yellow-500">
                        <i class='bx bx-smile'></i>
                        <span class="text-xs sm:text-sm font-semibold text-gray-500 dark:text-dark-txt">Live video</span>
                    </div>
                </div>
            </div>
            <!-- END POST FORM -->

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
                    <div class="relative cursor-pointer">
                        <img src="./images/avt-5.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="relative cursor-pointer">
                        <img src="./images/avt-2.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="relative cursor-pointer hidden sm:inline">
                        <img src="./images/avt-3.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="relative cursor-pointer hidden sm:inline">
                        <img src="./images/avt-4.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="relative cursor-pointer hidden sm:inline">
                        <img src="./images/avt-5.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="relative cursor-pointer hidden sm:inline">
                        <img src="./images/avt-7.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="relative cursor-pointer hidden sm:inline">
                        <img src="./images/avt-3.jpg" alt="Profile picture" class="rounded-full">
                        <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                    </div>
                    <div class="w-12 h-12 rounded-full hidden lg:grid place-items-center text-2xl text-gray-500 bg-white absolute right-0 top-1/2 transform -translate-y-1/2 border border-gray-200 cursor-pointer hover:bg-gray-100 shadow dark:bg-dark-third dark:border-dark-third dark:text-dark-txt">
                        <i class='bx bxs-chevron-right'></i>
                    </div>
                </div>
            </div>
            <!-- END ROOM -->

            <!-- LIST POST -->

            <div>

                <!-- POST -->
                <div class="shadow bg-white dark:bg-dark-second dark:text-dark-txt mt-4 rounded-lg">
                    <!-- POST AUTHOR -->
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex space-x-2 items-center">
                            <div class="relative">
                                <img src="./images/avt-2.jpg" alt="Profile picture" class="w-10 h-10 rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <div class="font-semibold">
                                    John Doe
                                </div>
                                <span class="text-sm text-gray-500">38m</span>
                            </div>
                        </div>
                        <div class="w-8 h-8 grid place-items-center text-xl text-gray-500 hover:bg-gray-200 dark:text-dark-txt dark:hover:bg-dark-third rounded-full cursor-pointer">
                            <i class='bx bx-dots-horizontal-rounded'></i>
                        </div>
                    </div>
                    <!-- END POST AUTHOR -->

                    <!-- POST CONTENT -->
                    <div class="text-justify px-4 py-2">
                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptates, autem earum cum ullam odio, molestias maxime aperiam in id aspernatur vel ratione odit molestiae minus ipsa obcaecati quia! Doloribus, illum.
                    </div>
                    <!-- END POST CONTENT -->

                    <!-- POST IMAGE -->
                    <div class="py-2">
                        <img src="./images/post.png" alt="Post image">
                    </div>
                    <!-- END POST IMAGE -->

                    <!-- POST REACT -->
                    <div class="px-4 py-2">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-row-reverse items-center">
                                <span class="ml-2 text-gray-500 dark:text-dark-txt">999</span>
                                <span class="rounded-full grid place-items-center text-2xl -ml-1 text-red-800">
                                    <i class='bx bxs-angry'></i>
                                </span>
                                <span class="rounded-full grid place-items-center text-2xl -ml-1 text-red-500">
                                    <i class='bx bxs-heart-circle'></i>
                                </span>
                                <span class="rounded-full grid place-items-center text-2xl -ml-1 text-yellow-500">
                                    <i class='bx bx-happy-alt'></i>
                                </span>
                            </div>
                            <div class="text-gray-500 dark:text-dark-txt">
                                <span>90 comments</span>
                                <span>66 Shares</span>
                            </div>
                        </div>
                    </div>
                    <!-- END POST REACT -->

                    <!-- POST ACTION -->
                    <div class="py-2 px-4">
                        <div class="border border-gray-200 dark:border-dark-third border-l-0 border-r-0 py-1">
                            <div class="flex space-x-2">
                                <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl py-2 rounded-lg cursor-pointer text-gray-500 dark:text-dark-txt">
                                    <i class='bx bx-like'></i>
                                    <span class="text-sm font-semibold">Like</span>
                                </div>
                                <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl py-2 rounded-lg cursor-pointer text-gray-500 dark:text-dark-txt">
                                    <i class='bx bx-comment'></i>
                                    <span class="text-sm font-semibold">Comment</span>
                                </div>
                                <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl py-2 rounded-lg cursor-pointer text-gray-500 dark:text-dark-txt">
                                    <i class='bx bx-share bx-flip-horizontal'></i>
                                    <span class="text-sm font-semibold">Share</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END POST ACTION -->

                    <!-- LIST COMMENT -->
                    <div class="py-2 px-4">
                        <!-- COMMENT -->
                        <div class="flex space-x-2">
                            <img src="./images/avt-5.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                            <div>
                                <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                    <span class="font-semibold block">John Doe</span>
                                    <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
                                </div>
                                <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                    <span class="font-semibold cursor-pointer">Like</span>
                                    <span>.</span>
                                    <span class="font-semibold cursor-pointer">Reply</span>
                                    <span>.</span>
                                    10m
                                </div>
                                <!-- COMMENT -->
                                <div class="flex space-x-2">
                                    <img src="./images/avt-7.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                                    <div>
                                        <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                            <span class="font-semibold block">John Doe</span>
                                            <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
                                        </div>
                                        <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                            <span class="font-semibold cursor-pointer">Like</span>
                                            <span>.</span>
                                            <span class="font-semibold cursor-pointer">Reply</span>
                                            <span>.</span>
                                            10m
                                        </div>
                                    </div>
                                </div>
                                <!-- END COMMENT -->
                            </div>
                        </div>
                        <!-- END COMMENT -->
                        <!-- COMMENT -->
                        <div class="flex space-x-2">
                            <img src="./images/avt-5.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                            <div>
                                <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                    <span class="font-semibold block">John Doe</span>
                                    <span>Lorem ipsum dolor sit amet consectetur, adipisicing elit. In voluptate ipsa animi corrupti unde, voluptatibus expedita suscipit, itaque, laudantium accusantium aspernatur officia repellendus nihil mollitia soluta distinctio praesentium nulla eos?</span>
                                </div>
                                <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                    <span class="font-semibold cursor-pointer">Like</span>
                                    <span>.</span>
                                    <span class="font-semibold cursor-pointer">Reply</span>
                                    <span>.</span>
                                    10m
                                </div>
                                <!-- COMMENT -->
                                <div class="flex space-x-2">
                                    <img src="./images/avt-7.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                                    <div>
                                        <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                            <span class="font-semibold block">John Doe</span>
                                            <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
                                        </div>
                                        <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                            <span class="font-semibold cursor-pointer">Like</span>
                                            <span>.</span>
                                            <span class="font-semibold cursor-pointer">Reply</span>
                                            <span>.</span>
                                            10m
                                        </div>
                                    </div>
                                </div>
                                <!-- END COMMENT -->
                            </div>
                        </div>
                        <!-- END COMMENT -->
                    </div>
                    <!-- END LIST COMMENT -->

                    <!-- COMMENT FORM -->
                    <div class="py-2 px-4">
                        <div class="flex space-x-2">
                            <img src="./images/tuat.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                            <div class="flex-1 flex bg-gray-100 dark:bg-dark-third rounded-full items-center justify-between px-3">
                                <input type="text" placeholder="Write a comment..." class="outline-none bg-transparent flex-1">
                                <div class="flex space-x-0 items-center justify-center">
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bx-smile'></i></span>
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bx-camera'></i></span>
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bxs-file-gif'></i></span>
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bx-happy-heart-eyes'></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END COMMENT FORM -->
                </div>
                <!-- END POST -->

                <!-- POST -->
                <div class="shadow bg-white dark:bg-dark-second dark:text-dark-txt mt-4 rounded-lg">
                    <!-- POST AUTHOR -->
                    <div class="flex items-center justify-between px-4 py-2">
                        <div class="flex space-x-2 items-center">
                            <div class="relative">
                                <img src="./images/avt-2.jpg" alt="Profile picture" class="w-10 h-10 rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <div class="font-semibold">
                                    John Doe
                                </div>
                                <span class="text-sm text-gray-500">38m</span>
                            </div>
                        </div>
                        <div class="w-8 h-8 grid place-items-center text-xl text-gray-500 hover:bg-gray-200 dark:text-dark-txt dark:hover:bg-dark-third rounded-full cursor-pointer">
                            <i class='bx bx-dots-horizontal-rounded'></i>
                        </div>
                    </div>
                    <!-- END POST AUTHOR -->

                    <!-- POST CONTENT -->
                    <div class="text-justify px-4 py-2">
                        Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptates, autem earum cum ullam odio, molestias maxime aperiam in id aspernatur vel ratione odit molestiae minus ipsa obcaecati quia! Doloribus, illum.
                    </div>
                    <!-- END POST CONTENT -->

                    <!-- POST IMAGE -->
                    <div class="py-2">
                        <div class="grid grid-cols-2 gap-1">
                            <img src="./images/post-2 (1).jpg" alt="Post image">
                            <img src="./images/post-2 (2).jpg" alt="Post image">
                            <img src="./images/post-2 (3).jpg" alt="Post image">
                            <img src="./images/post-2 (4).jpg" alt="Post image">
                        </div>
                    </div>
                    <!-- END POST IMAGE -->

                    <!-- POST REACT -->
                    <div class="px-4 py-2">
                        <div class="flex items-center justify-between">
                            <div class="flex flex-row-reverse items-center">
                                <span class="ml-2 text-gray-500 dark:text-dark-txt">999</span>
                                <span class="rounded-full grid place-items-center text-2xl -ml-1 text-red-800">
                                    <i class='bx bxs-angry'></i>
                                </span>
                                <span class="rounded-full grid place-items-center text-2xl -ml-1 text-red-500">
                                    <i class='bx bxs-heart-circle'></i>
                                </span>
                                <span class="rounded-full grid place-items-center text-2xl -ml-1 text-yellow-500">
                                    <i class='bx bx-happy-alt'></i>
                                </span>
                            </div>
                            <div class="text-gray-500 dark:text-dark-txt">
                                <span>90 comments</span>
                                <span>66 Shares</span>
                            </div>
                        </div>
                    </div>
                    <!-- END POST REACT -->

                    <!-- POST ACTION -->
                    <div class="py-2 px-4">
                        <div class="border border-gray-200 dark:border-dark-third border-l-0 border-r-0 py-1">
                            <div class="flex space-x-2">
                                <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl py-2 rounded-lg cursor-pointer text-gray-500 dark:text-dark-txt">
                                    <i class='bx bx-like'></i>
                                    <span class="text-sm font-semibold">Like</span>
                                </div>
                                <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl py-2 rounded-lg cursor-pointer text-gray-500 dark:text-dark-txt">
                                    <i class='bx bx-comment'></i>
                                    <span class="text-sm font-semibold">Comment</span>
                                </div>
                                <div class="w-1/3 flex space-x-2 justify-center items-center hover:bg-gray-100 dark:hover:bg-dark-third text-xl py-2 rounded-lg cursor-pointer text-gray-500 dark:text-dark-txt">
                                    <i class='bx bx-share bx-flip-horizontal'></i>
                                    <span class="text-sm font-semibold">Share</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END POST ACTION -->

                    <!-- LIST COMMENT -->
                    <div class="py-2 px-4">
                        <!-- COMMENT -->
                        <div class="flex space-x-2">
                            <img src="./images/avt-5.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                            <div>
                                <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                    <span class="font-semibold block">John Doe</span>
                                    <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
                                </div>
                                <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                    <span class="font-semibold cursor-pointer">Like</span>
                                    <span>.</span>
                                    <span class="font-semibold cursor-pointer">Reply</span>
                                    <span>.</span>
                                    10m
                                </div>
                                <!-- COMMENT -->
                                <div class="flex space-x-2">
                                    <img src="./images/avt-7.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                                    <div>
                                        <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                            <span class="font-semibold block">John Doe</span>
                                            <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
                                        </div>
                                        <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                            <span class="font-semibold cursor-pointer">Like</span>
                                            <span>.</span>
                                            <span class="font-semibold cursor-pointer">Reply</span>
                                            <span>.</span>
                                            10m
                                        </div>
                                    </div>
                                </div>
                                <!-- END COMMENT -->
                            </div>
                        </div>
                        <!-- END COMMENT -->
                        <!-- COMMENT -->
                        <div class="flex space-x-2">
                            <img src="./images/avt-5.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                            <div>
                                <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                    <span class="font-semibold block">John Doe</span>
                                    <span>Lorem ipsum dolor sit amet consectetur, adipisicing elit. In voluptate ipsa animi corrupti unde, voluptatibus expedita suscipit, itaque, laudantium accusantium aspernatur officia repellendus nihil mollitia soluta distinctio praesentium nulla eos?</span>
                                </div>
                                <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                    <span class="font-semibold cursor-pointer">Like</span>
                                    <span>.</span>
                                    <span class="font-semibold cursor-pointer">Reply</span>
                                    <span>.</span>
                                    10m
                                </div>
                                <!-- COMMENT -->
                                <div class="flex space-x-2">
                                    <img src="./images/avt-7.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                                    <div>
                                        <div class="bg-gray-100 dark:bg-dark-third p-2 rounded-2xl text-sm">
                                            <span class="font-semibold block">John Doe</span>
                                            <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
                                        </div>
                                        <div class="p-2 text-xs text-gray-500 dark:text-dark-txt">
                                            <span class="font-semibold cursor-pointer">Like</span>
                                            <span>.</span>
                                            <span class="font-semibold cursor-pointer">Reply</span>
                                            <span>.</span>
                                            10m
                                        </div>
                                    </div>
                                </div>
                                <!-- END COMMENT -->
                            </div>
                        </div>
                        <!-- END COMMENT -->
                    </div>
                    <!-- END LIST COMMENT -->

                    <!-- COMMENT FORM -->
                    <div class="py-2 px-4">
                        <div class="flex space-x-2">
                            <img src="./images/tuat.jpg" alt="Profile picture" class="w-9 h-9 rounded-full">
                            <div class="flex-1 flex bg-gray-100 dark:bg-dark-third rounded-full items-center justify-between px-3">
                                <input type="text" placeholder="Write a comment..." class="outline-none bg-transparent flex-1">
                                <div class="flex space-x-0 items-center justify-center">
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bx-smile'></i></span>
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bx-camera'></i></span>
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bxs-file-gif'></i></span>
                                    <span class="w-7 h-7 grid place-items-center rounded-full hover:bg-gray-200 cursor-pointer text-gray-500 dark:text-dark-txt dark:hover:bg-dark-second text-xl"><i class='bx bx-happy-heart-eyes'></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END COMMENT FORM -->
                </div>
                <!-- END POST -->

            </div>

            <!-- END LIST POST -->
        </div>
        <!-- END MAIN CONTENT -->

        <!-- RIGHT MENU -->
<div class="w-1/5 pt-16 h-full hidden xl:block px-4 fixed top-0 right-0">
    <div class="h-full">
        <div class="flex justify-between items-center px-4 pt-4">
            <span class="font-semibold text-gray-500 text-lg dark:text-dark-txt">طلبات الصداقة</span>
            <?php if (count($friend_requests) > 4): ?>
                <a href="friend_requests.php" class="text-blue-500 cursor-pointer hover:bg-gray-200 dark:hover:bg-dark-third p-2 rounded-md">See All</a>
            <?php endif; ?>
        </div>
        <div class="p-2">
            <?php if (!empty($friend_requests)): ?>
                <?php foreach (array_slice($friend_requests, 0, 4) as $request): ?>
                    <a href="profile.php?id=<?= $request['sender_id'] ?>" class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third rounded-lg transition-all">
                        <img src="uploads/<?= !empty($request['profile_picture']) ? $request['profile_picture'] : 'default_avatar.jpg' ?>" alt="<?= htmlentities($request['display_name']) ?>" class="w-16 h-16 rounded-full">
                        <div class="flex-1 h-full">
                            <div class="dark:text-dark-txt">
                                <span class="font-semibold"><?= htmlentities($request['display_name']) ?></span>
                                <span class="float-right text-sm text-gray-500"><?= date("d M", strtotime($request['created_at'])) ?></span>
                            </div>
                            <div class="flex space-x-2 mt-2">
                                <a href="accept_friend_request.php?id=<?= $request['id'] ?>" class="w-1/2 bg-blue-500 cursor-pointer py-1 text-center font-semibold text-white rounded-lg">
                                    Confirm
                                </a>
                                <a href="reject_friend_request.php?id=<?= $request['id'] ?>" class="w-1/2 bg-gray-300 cursor-pointer py-1 text-center font-semibold text-black rounded-lg">
                                    Delete
                                </a>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-gray-500">لا توجد طلبات صداقة جديدة.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

                <div class="border-b border-gray-200 dark:border-dark-third mt-6"></div>
                <!-- CONTACTS -->
                <div class="flex justify-between items-center px-4 pt-4 text-gray-500 dark:text-dark-txt">
                    <span class="font-semibold text-lg">Contacts</span>
                    <div class="flex space-x-1">
                        <div class="w-8 h-8 grid place-items-center text-xl hover:bg-gray-200 dark:hover:bg-dark-third rounded-full cursor-pointer">
                            <i class='bx bx-search-alt-2'></i>
                        </div>
                        <div class="w-8 h-8 grid place-items-center text-xl hover:bg-gray-200 dark:hover:bg-dark-third rounded-full cursor-pointer">
                            <i class='bx bx-dots-horizontal-rounded'></i>
                        </div>
                    </div>
                </div>
                <ul class="p-2">
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-3.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Chin Chin</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-2.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Tuat TA</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-4.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">John Doe</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-5.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Ivan Lorem</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-6.png" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Shiba san</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-4.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">John Doe</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-5.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Ivan Lorem</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-6.png" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Shiba san</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-4.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">John Doe</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-5.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Ivan Lorem</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-6.png" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Shiba san</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-4.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">John Doe</span>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center space-x-4 p-2 hover:bg-gray-200 dark:hover:bg-dark-third dark:text-dark-txt rounded-lg cursor-pointer">
                            <div class="relative">
                                <img src="./images/avt-5.jpg" alt="Friends profile picture" class="rounded-full">
                                <span class="bg-green-500 w-3 h-3 rounded-full absolute right-0 top-3/4 border-white border-2"></span>
                            </div>
                            <div>
                                <span class="font-semibold">Ivan Lorem</span>
                            </div>
                        </div>
                    </li>
                </ul>
                <!-- END CONTACTS -->
            </div>
        </div>
        <!-- END RIGHT MENU -->
    </div>
    <!-- END MAIN -->

    <script src="./static/app.js"></script>
</body>

</html>
