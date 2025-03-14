// إنشاء HTML لتعليق واحد (أو رد)
function createCommentHTML(comment) {
  // إذا كان لديه parent_id => يعتبره رد (يمكنك تمييز التصميم إن شئت)
  const isReply = comment.parent_id ? true : false;

  // يمكنك تعديل التنسيق بما يناسبك، هنا مجرد مثال قريب من تنسيقك
  return `
    <div style="display:flex; gap:0.5rem; margin-bottom:0.5rem; margin-left:${isReply ? '40px' : '0'};">
      <img src="uploads/${comment.profile_picture || 'default_avatar.jpg'}" alt="Profile" 
           style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
      <div>
        <div style="background-color:#f1f1f1; padding:0.5rem; border-radius:12px;">
          <strong>${comment.display_name}</strong><br>
          <span>${comment.content}</span>
        </div>
        <div style="color:#888; font-size:0.85rem; margin-top:0.25rem;">
          <span style="cursor:pointer;" onclick="toggleLikeComment(${comment.id}, this)">Like</span>
          <span> · </span>
          <span style="cursor:pointer;" onclick="openReplyForm(${comment.id}, ${comment.post_id})">Reply</span>
          <span> · </span>
          ${comment.time_ago || 'just now'}
        </div>
        <!-- هنا سيحقن أي ردود فرعية (لو أردت مزيد من التعشيق) -->
        <div id="repliesContainer_${comment.id}"></div>
      </div>
    </div>
  `;
}

// دالة بناء شجرة التعليقات (مستوى واحد من الردود)
function buildCommentsTree(comments) {
  // نجمع التعليقات في خريطة { commentId => commentData }
  let map = {};
  comments.forEach(c => {
    c.replies = [];
    map[c.id] = c;
  });

  // نفصلهم إلى top-level أو ردود
  let roots = [];
  comments.forEach(c => {
    if (c.parent_id && map[c.parent_id]) {
      // هذا رد
      map[c.parent_id].replies.push(c);
    } else {
      // هذا تعليق رئيسي
      roots.push(c);
    }
  });
  return roots;
}

// عرض التعليقات شجريًا
function renderCommentsTree(roots) {
  let html = "";
  roots.forEach(root => {
    // عرض التعليق الأساسي
    html += createCommentHTML(root);
    // عرض ردود هذا التعليق
    root.replies.forEach(r => {
      html += createCommentHTML(r);
    });
  });
  return html;
}

// دالة جلب التعليقات
window.fetchComments = function(postId) {
  fetch("assist/fetch_comments.php?post_id=" + postId)
    .then(r => r.json())
    .then(data => {
      let container = document.getElementById("commentsContainer_" + postId);
      if (!container) return;

      // بناء شجرة التعليقات
      let roots = buildCommentsTree(data);
      let html = renderCommentsTree(roots);
      container.innerHTML = html;

      // بعد جلب التعليقات نحدث العدد
      let commentsCountSpan = document.getElementById("commentsCount_" + postId);
      if (commentsCountSpan) {
        commentsCountSpan.textContent = data.length; 
      }
    })
    .catch(err => console.error("fetchComments error:", err));
};

// عند الضغط على زر Like في المنشور
window.toggleLikePost = function(postId) {
  fetch("assist/like_post.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "post_id=" + encodeURIComponent(postId)
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === "liked") {
      // ...
    } else if (data.status === "unliked") {
      // ...
    }
    updateLikesCount(postId);
  })
  .catch(err => console.error("toggleLikePost error:", err));
};

// تحديث عدد الإعجابات
window.updateLikesCount = function(postId) {
  fetch("assist/fetch_likes_count.php?post_id=" + encodeURIComponent(postId))
    .then(res => res.json())
    .then(data => {
      let countSpan = document.getElementById("likesCount_" + postId);
      if (countSpan) {
        countSpan.textContent = data.total_likes;
      }
    })
    .catch(err => console.error("updateLikesCount error:", err));
};

// إضافة تعليق (أو رد إذا parent_id != null)
window.addComment = function(postId, parentId=null) {
  let inputId = parentId ? "replyInput_" + parentId : "commentInput_" + postId;
  let input = document.getElementById(inputId);
  if (!input) return;

  let content = input.value.trim();
  if (!content) return;

  let formData = new FormData();
  formData.append("post_id", postId);
  formData.append("content", content);
  if (parentId) {
    formData.append("parent_id", parentId);
  }

  fetch("assist/comment_post.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === "ok") {
      // أعد جلب التعليقات
      fetchComments(postId);
      input.value = "";
      // إخفاء فورم الرد لو موجود
      if (parentId) {
        let form = document.getElementById("replyForm_" + parentId);
        if (form) form.remove();
      }
    }
  })
  .catch(err => console.error("addComment error:", err));
};

// فتح حقل للرد
window.openReplyForm = function(commentId, postId) {
  // نتأكد ما في فورم رد مفتوح مسبقًا
  let existing = document.getElementById("replyForm_" + commentId);
  if (existing) return; // بالفعل مفتوح

  let repliesContainer = document.getElementById("repliesContainer_" + commentId);
  if (!repliesContainer) return;

  let div = document.createElement("div");
  div.id = "replyForm_" + commentId;
  div.innerHTML = `
    <div style="display:flex; gap:0.5rem; margin: 5px 0 0 40px;">
      <input id="replyInput_${commentId}" type="text" style="flex:1; border:1px solid #ccc; border-radius:4px; padding:4px;" placeholder="Write a reply...">
      <button onclick="addComment(${postId}, ${commentId})">Send</button>
    </div>
  `;
  repliesContainer.appendChild(div);
};

// نظام الإعجاب للتعليقات (اختياري)
window.toggleLikeComment = function(commentId, el) {
  fetch("assist/like_comment.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "comment_id=" + encodeURIComponent(commentId)
  })
  .then(r => r.json())
  .then(d => {
    if (d.status === "liked") {
      el.textContent = "Unlike";
    } else if (d.status === "unliked") {
      el.textContent = "Like";
    }
  })
  .catch(err => console.error("toggleLikeComment error:", err));
};


// عند الضغط على Enter في حقل التعليق الأساسي
document.addEventListener("DOMContentLoaded", function(){
  // الاستماع لزر Enter في حقول التعليق الرئيسية
  document.querySelectorAll('input[id^="commentInput_"]').forEach(inp => {
    inp.addEventListener("keypress", function(e){
      if(e.key === "Enter"){
        let postId = this.id.replace("commentInput_", "");
        addComment(postId);
      }
    });
  });
});
