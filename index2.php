<?php
include 'admin/config.php'; // الاتصال بقاعدة البيانات
session_start();
$user_id = $_SESSION['user_id']; // نفترض أن المستخدم مسجل دخول
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    echo json_encode(["total_likes" => 0]);
    exit;
}

// جلب المنشورات
$query = "SELECT posts.*, users.display_name, users.profile_picture, 
          (SELECT COUNT(*) FROM likes WHERE post_id = posts.id) AS like_count,
          (SELECT COUNT(*) FROM likes WHERE post_id = posts.id AND user_id = $user_id) AS user_liked
          FROM posts 
          JOIN users ON posts.user_id = users.id
          ORDER BY posts.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المنشورات</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="posts-container">
    <?php while ($post = mysqli_fetch_assoc($result)) { ?>
        <div class="post" id="post-<?php echo $post['id']; ?>">
            <div class="post-header">
                <img src="<?php echo $post['profile_picture']; ?>" alt="الصورة الشخصية" class="profile-pic">
                <span><?php echo $post['display_name']; ?></span>
            </div>
            <p><?php echo $post['content']; ?></p>
            <button class="like-btn" data-post-id="<?php echo $post['id']; ?>">
                <?php echo ($post['user_liked'] > 0) ? 'Unlike' : 'Like'; ?>
            </button>
            <span class="like-count">إعجابات: <?php echo $post['like_count']; ?></span>
            
            <!-- التعليقات -->
            <div class="comments" id="comments-<?php echo $post['id']; ?>">
                <?php
                $comment_query = "SELECT comments.*, users.display_name, users.profile_picture,
                                  (SELECT COUNT(*) FROM likes WHERE comment_id = comments.id) AS like_count,
                                  (SELECT COUNT(*) FROM likes WHERE comment_id = comments.id AND user_id = $user_id) AS user_liked
                                  FROM comments 
                                  JOIN users ON comments.user_id = users.id
                                  WHERE post_id = " . $post['id'] . " AND parent_id IS NULL 
                                  ORDER BY created_at ASC";
                $comment_result = mysqli_query($conn, $comment_query);
                
                while ($comment = mysqli_fetch_assoc($comment_result)) { ?>
                    <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                        <img src="<?php echo $comment['profile_picture']; ?>" class="profile-pic">
                        <span><?php echo $comment['display_name']; ?></span>
                        <p><?php echo $comment['content']; ?></p>
                        <button class="like-comment-btn" data-comment-id="<?php echo $comment['id']; ?>">
                            <?php echo ($comment['user_liked'] > 0) ? 'Unlike' : 'Like'; ?>
                        </button>
                        <span class="comment-like-count">إعجابات: <?php echo $comment['like_count']; ?></span>
                        
                        <!-- الردود -->
                        <div class="replies" id="replies-<?php echo $comment['id']; ?>">
                            <?php
                            $reply_query = "SELECT comments.*, users.display_name, users.profile_picture FROM comments 
                                            JOIN users ON comments.user_id = users.id
                                            WHERE parent_id = " . $comment['id'] . " ORDER BY created_at ASC";
                            $reply_result = mysqli_query($conn, $reply_query);
                            while ($reply = mysqli_fetch_assoc($reply_result)) { ?>
                                <div class="reply">
                                    <img src="<?php echo $reply['profile_picture']; ?>" class="profile-pic">
                                    <span><?php echo $reply['display_name']; ?></span>
                                    <p><?php echo $reply['content']; ?></p>
                                </div>
                            <?php } ?>
                        </div>
                        <input type="text" class="reply-input" placeholder="أضف ردًا" data-comment-id="<?php echo $comment['id']; ?>">
                    </div>
                <?php } ?>
            </div>
            <input type="text" class="comment-input" placeholder="أضف تعليقًا" data-post-id="<?php echo $post['id']; ?>">
        </div>
    <?php } ?>
</div>
<script>
$(document).ready(function () {
    $(".like-btn").click(function () {
        var post_id = $(this).data("post-id");
        var btn = $(this);
        $.post("like.php", { post_id: post_id }, function (data) {
            btn.text(data.status == "liked" ? "Unlike" : "Like");
            btn.next(".like-count").text("إعجابات: " + data.like_count);
        }, "json");
    });
    
    $(".like-comment-btn").click(function () {
        var comment_id = $(this).data("comment-id");
        var btn = $(this);
        $.post("like_comment.php", { comment_id: comment_id }, function (data) {
            btn.text(data.status == "liked" ? "Unlike" : "Like");
            btn.next(".comment-like-count").text("إعجابات: " + data.like_count);
        }, "json");
    });
    
    $(".comment-input").keypress(function (e) {
        if (e.which == 13) {
            var post_id = $(this).data("post-id");
            var comment_text = $(this).val();
            var commentBox = $("#comments-" + post_id);
            $(this).val("");
            $.post("comment.php", { post_id: post_id, content: comment_text }, function (data) {
                commentBox.append("<p>" + data.comment + "</p>");
            }, "json");
        }
    });
});
</script>
</body>
</html>
