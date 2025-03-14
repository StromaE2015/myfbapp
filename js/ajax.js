$(document).ready(function(){
  // بمجرد تحميل الصفحة نجلب المنشورات
  fetchPosts();
});

// جلب المنشورات عبر AJAX من ملف fetch_posts.php
function fetchPosts(){
  $.ajax({
    url: 'assist/fetch_posts.php',
    type: 'GET',
    dataType: 'html',
    success: function(html){
      $("#postsSection").html(html);

      // بعد حقن المنشورات في DOM
      // نفعّل الأحداث (الإعجاب والتعليق)
      activatePostEvents();
      
      // نجلب التعليقات + عدد الإعجابات لكل منشور
      $(".like-btn").each(function(){
        let postId = $(this).attr("data-post-id");
        // تحدّث الإعجابات
        updateLikesCount(postId);
        // تحدّث التعليقات
        fetchComments(postId);
      });
    },
    error: function(){
      console.error("فشل في جلب المنشورات!");
    }
  });
}

// تفعيل زر Like/Comment لكل منشور
function activatePostEvents(){
  // زر الإعجاب
  $(".like-btn").off("click").on("click", function(){
    let postId = $(this).attr("data-post-id");
    // استدعاء الدالة الموجودة في likes_comments.js
    toggleLikePost(postId);
  });

  // زر إرسال التعليق
  $(".comment-send-btn").off("click").on("click", function(){
    let postId = $(this).attr("data-post-id");
    addComment(postId);
  });
}
