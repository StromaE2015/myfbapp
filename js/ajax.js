$(document).ready(function(){
  fetchPosts();
});

function fetchPosts(){
  $.ajax({
    url: 'assist/fetch_posts.php',
    type: 'GET',
    dataType: 'html',
    success: function(html){
      $("#postsSection").html(html);

      // تأخير بسيط لضمان تحميل كل المنشورات والتأكد من وجود comments-section
      setTimeout(() => {
        let commentsSections = $(".comments-section");
        console.log("✅ عدد comments-section:", commentsSections.length);
        
        if (commentsSections.length > 0) {
          commentsSections.each(function(){
            let postId = $(this).attr("id")?.replace("commentsContainer_", "");
            console.log("📝 Found post ID:", postId);
            fetchComments(postId); // جلب التعليقات بعد التأكد من وجود العنصر
            
          });
        } else {
          console.log("⚠️ لم يتم العثور على أي comments-section في الصفحة.");
        }
      }, 500); // تأخير بسيط عشان نضمن تحميل كل حاجة
    },
    error: function(){
      console.log("❌ Error fetching posts!");
    }
  });
}
