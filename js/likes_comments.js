/****************************************/
/*  دوال التعامل مع الإعجابات والتعليقات  */
/****************************************/

// كاش تخزين بيانات التعليقات لكل منشور، كي نعيد عرضها جزئيًا أو كليًا
const globalCommentsCache = {};

// إنشاء HTML للتعليق مع مراعاة حالة الإعجاب
function createCommentHTML(comment) {
  // لو السيرفر يعيد comment.is_liked = true/false
  let likeText = comment.is_liked ? "Unlike" : "Like";

  return `
    <div id="comment_${comment.id}" style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
      <img src="uploads/${comment.profile_picture || 'default_avatar.jpg'}" alt="Profile"
           style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
      <div>
        <div style="background-color:#f1f1f1; padding:0.5rem; border-radius:12px;">
          <strong>${comment.display_name}</strong><br>
          <span>${comment.content}</span>
        </div>
        <div style="color:#888; font-size:0.85rem; margin-top:0.25rem;">
          <!-- نعرض Like/Unlike حسب حالة is_liked -->
          <span style="cursor:pointer;" onclick="toggleLikeComment(${comment.id}, this)">${likeText}</span>
          <span> · </span>
          <span style="cursor:pointer;" onclick="showReplyInput(${comment.id})">Reply</span>
          <span> · </span>
          ${comment.time_ago || 'just now'}
        </div>
      </div>
    </div>
  `;
}

// عند الضغط على "Like" أو "Unlike" للتعليق
window.toggleLikeComment = function(commentId, btnEl) {
  if (!commentId) {
    console.error("Comment ID is missing");
    return;
  }

  fetch("assist/like_comment.php?comment_id=" + encodeURIComponent(commentId))
    .then(res => res.json())
    .then(data => {
      // تحديث نص الزر بناءً على الحالة
      if (data.status === "liked") {
        btnEl.textContent = "Unlike";
      } else if (data.status === "unliked") {
        btnEl.textContent = "Like";
      }
      // يمكنك تحديث عداد الإعجابات لو لديك عداد
    })
    .catch(err => {
      const errorObject = {
        status: "error",
        message: "toggleLikeComment error",
        details: err ? err.toString() : "No error details"
      };
      console.error(JSON.stringify(errorObject));
    });
};

// إظهار حقل الرد
window.showReplyInput = function(commentId) {
  let commentEl = document.getElementById("comment_" + commentId);
  if (!commentEl) return;

  if (commentEl.querySelector('.reply-input')) return; // لو موجود، لا تكرر
  let replyHtml = `
    <div class="reply-input" style="margin-top:0.5rem;">
      <input type="text" id="replyInput_${commentId}" placeholder="اكتب رد..." style="width:80%;"/>
      <button onclick="addReply(${commentId})">إرسال</button>
    </div>
  `;
  commentEl.insertAdjacentHTML("beforeend", replyHtml);
};

// إرسال الرد
window.addReply = function(commentId) {
  if (!commentId) {
    console.error("Comment ID is missing");
    return;
  }
  let input = document.getElementById("replyInput_" + commentId);
  if (!input) return;

  let content = input.value.trim();
  if (!content) return;

  // نرسل بالـ GET (كما في الكود الحالي)
  fetch(`assist/comment_reply.php?parent_comment_id=${encodeURIComponent(commentId)}&content=${encodeURIComponent(content)}`)
    .then(res => res.json())
    .then(data => {
      if (data.status === "ok") {
        console.log("تم إضافة الرد بنجاح");
        input.value = "";
        // يمكنك إعادة جلب التعليقات أو تحديث واجهة الردود
      } else {
        console.error("Reply error:", data);
      }
    })
    .catch(err => console.error("addReply error:", err));
};

// تبديل الإعجاب للمنشور مع تحسين رسالة الخطأ
window.toggleLikePost = function(postId, btnEl) {
  fetch("assist/like_post.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "post_id=" + encodeURIComponent(postId)
  })
  .then(res => res.text())
  .then(text => {
    try {
      const data = JSON.parse(text);
      if (data.status === "liked") {
        btnEl.querySelector("span").textContent = "Unlike";
      } else if (data.status === "unliked") {
        btnEl.querySelector("span").textContent = "Like";
      }
      updateLikesCount(postId);
    } catch (e) {
      console.error(JSON.stringify({
        status: "error",
        message: "toggleLikePost JSON parse error",
        details: e.toString(),
        responseText: text
      }));
    }
  })
  .catch(err => {
    console.error(JSON.stringify({
      status: "error",
      message: "toggleLikePost fetch error",
      details: err.toString()
    }));
  });
};

// تحديث عدد الإعجابات للمنشور
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

// إضافة تعليق (جديد) على المنشور
window.addComment = function(postId) {
  let input = document.getElementById("commentInput_" + postId);
  if (!input) return;
  let content = input.value.trim();
  if (!content) return;

  let formData = new FormData();
  formData.append("post_id", postId);
  formData.append("content", content);

  fetch("assist/comment_post.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    console.log("addComment response:", data);
    if (data.status === "ok") {
      // أعد جلب التعليقات
      fetchComments(postId);
      input.value = "";
    }
  })
  .catch(err => console.error("addComment error:", err));
};

// إرسال التعليق بالضغط على Enter
document.addEventListener("DOMContentLoaded", function(){
  document.querySelectorAll('input[id^="commentInput_"]').forEach(inp => {
    inp.addEventListener("keypress", function(e){
      if(e.key === "Enter"){
        let postId = this.id.replace("commentInput_", "");
        addComment(postId);
      }
    });
  });
});

/* 
  جلب التعليقات:
  - نخزّنها في globalCommentsCache[postId]
  - نعرض 4 تعليقات جذرية فقط (مع رابط لعرض الباقي)
  - لكل تعليق، نعرض ردًا واحدًا فقط (مع رابط لعرض الباقي)
*/
window.fetchComments = function(postId) {
  fetch("assist/fetch_comments.php?post_id=" + encodeURIComponent(postId))
    .then(r => r.json())
    .then(data => {
      // نخزّن البيانات
      globalCommentsCache[postId] = data;

      let container = document.getElementById("commentsContainer_" + postId);
      if (!container) return;

      let nestedComments = buildCommentsHierarchy(data);
      // نعرض جزئيًا
      container.innerHTML = renderPartialComments(nestedComments, true, postId);
    })
    .catch(err => console.error("fetchComments error:", err));
};

// عند الضغط على "عرض المزيد من التعليقات"
window.showAllComments = function(postId) {
  let container = document.getElementById("commentsContainer_" + postId);
  if (!container) return;

  let data = globalCommentsCache[postId] || [];
  let nestedComments = buildCommentsHierarchy(data);
  // الآن نعرض الكل
  container.innerHTML = renderPartialComments(nestedComments, false, postId);
};

// بناء شجرة التعليقات (كما هو)
function buildCommentsHierarchy(comments) {
  const map = {};
  comments.forEach(c => {
    c.replies = [];
    map[c.id] = c;
  });

  const roots = [];
  comments.forEach(c => {
    if (c.parent_id && c.parent_id !== 0 && c.parent_id !== "0") {
      if (map[c.parent_id]) {
        map[c.parent_id].replies.push(c);
      } else {
        roots.push(c);
      }
    } else {
      roots.push(c);
    }
  });
  return roots;
}

/*
  renderPartialComments(comments, isPartialRoot, postId)
  - لو isPartialRoot = true => نعرض 4 تعليقات جذرية فقط، مع رابط "عرض المزيد"
  - لكل تعليق، نعرض ردًا واحدًا فقط، مع رابط "عرض المزيد من الردود"
  - لو isPartialRoot = false => نعرض كل التعليقات الجذرية
*/
function renderPartialComments(comments, isPartialRoot, postId, depth = 0) {
  let html = "";

  // 1) هل نحن في الجذر؟
  if (depth === 0 && isPartialRoot) {
    // عرض 4 فقط
    let total = comments.length;
    let visible = Math.min(4, total);
    for (let i = 0; i < visible; i++) {
      html += createCommentBlock(comments[i], postId, depth);
    }
    let hidden = total - visible;
    if (hidden > 0) {
      // رابط "عرض المزيد من التعليقات (X)"
      html += `
        <div style="cursor:pointer; color:blue;" onclick="showAllComments(${postId})">
          عرض ${hidden} تعليقات أخرى
        </div>
      `;
    }
  } else {
    // إما الجذر دون اقتصاص أو مستوى رد
    for (let i = 0; i < comments.length; i++) {
      html += createCommentBlock(comments[i], postId, depth);
    }
  }

  return html;
}

/*
  createCommentBlock(comment, postId, depth)
  - ينشئ HTML للتعليق
  - يعرض ردًا واحدًا فقط من replies
  - يضيف رابط "عرض المزيد من الردود" لو في أكثر من رد
*/
function createCommentBlock(comment, postId, depth) {
  let html = createCommentHTML(comment);

  // لو عنده ردود
  if (comment.replies && comment.replies.length > 0) {
    html += `<div style="margin-left: 20px;">`;

    // نعرض رداً واحداً فقط (بدون استدعاء renderPartialComments)
    let totalReplies = comment.replies.length;
    let firstReply = comment.replies[0];
    if (firstReply) {
      html += createCommentHTML(firstReply);
    }

    let hiddenReplies = totalReplies - 1;
    if (hiddenReplies > 0) {
      // زر "عرض المزيد من الردود"
      html += `
        <div style="cursor:pointer; color:blue;" onclick="showAllReplies(${comment.id}, ${postId})">
          عرض ${hiddenReplies} ردود أخرى
        </div>
      `;
    }
    html += `</div>`; // إغلاق الـ margin-left
  }
  return html;
}

/*
  createCommentBlockExpand: عند الضغط على "عرض المزيد من الردود" 
  إذا كان التعليق الحالي هو المطلوب توسيعه (expandId)، نعرض كل ردوده
  وإلا نعرض ردًا واحدًا فقط
*/
function createCommentBlockExpand(comment, expandId, postId, depth) {
  let html = createCommentHTML(comment);

  if (comment.replies && comment.replies.length > 0) {
    html += `<div style="margin-left: 20px;">`;

    if (comment.id === expandId) {
      // نعرض كل الردود
      for (let r of comment.replies) {
        html += createCommentHTML(r);
      }
    } else {
      // نعرض رد واحد فقط
      let totalReplies = comment.replies.length;
      let firstReply = comment.replies[0];
      if (firstReply) {
        html += createCommentHTML(firstReply);
      }
      let hiddenReplies = totalReplies - 1;
      if (hiddenReplies > 0) {
        html += `
          <div style="cursor:pointer; color:blue;" onclick="showAllReplies(${comment.id}, ${postId})">
            عرض ${hiddenReplies} ردود أخرى
          </div>
        `;
      }
    }

    html += `</div>`;
  }
  return html;
}

// عند الضغط على "عرض المزيد من الردود" لنفس التعليق
window.showAllReplies = function(commentId, postId) {
  let data = globalCommentsCache[postId] || [];
  let nested = buildCommentsHierarchy(data);

  let container = document.getElementById("commentsContainer_" + postId);
  if (!container) return;

  container.innerHTML = renderAllButExpandOne(nested, commentId, postId);
};

/*
  renderAllButExpandOne: نعرض 4 تعليقات جذرية كالمعتاد
  لكن لو وصلنا للتعليق المحدد، نعرض كل ردوده
*/
function renderAllButExpandOne(comments, expandId, postId, depth = 0) {
  let html = "";
  if (depth === 0) {
    let total = comments.length;
    let visible = Math.min(4, total);
    for (let i = 0; i < visible; i++) {
      html += createCommentBlockExpand(comments[i], expandId, postId, depth);
    }
    let hidden = total - visible;
    if (hidden > 0) {
      html += `
        <div style="cursor:pointer; color:blue;" onclick="showAllComments(${postId})">
          عرض ${hidden} تعليقات أخرى
        </div>
      `;
    }
  } else {
    for (let i = 0; i < comments.length; i++) {
      html += createCommentBlockExpand(comments[i], expandId, postId, depth);
    }
  }
  return html;
}

function trackAdView(adId, userId) {
    fetch("assist/track_ad_interaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "ad_id=" + encodeURIComponent(adId) + "&user_id=" + encodeURIComponent(userId) + "&interaction_type=view"
    });
}

function trackAdInteraction(adId, userId, interactionType) {
    fetch("assist/track_ad_interaction.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "ad_id=" + encodeURIComponent(adId) + "&user_id=" + encodeURIComponent(userId) + "&interaction_type=" + encodeURIComponent(interactionType)
    });
}

function trackAdClick(adId, userId) {
    trackAdInteraction(adId, userId, "click");
}

// تتبع المشاهدات بمجرد ظهور الإعلان على الشاشة
let observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            let adId = entry.target.getAttribute("data-ad-id");
            let userId = entry.target.getAttribute("data-user-id");
            trackAdInteraction(adId, userId, "view");
            observer.unobserve(entry.target); // تسجيل المشاهدة مرة واحدة فقط
        }
    });
}, { threshold: 0.5 });

document.querySelectorAll('.ad-card').forEach(ad => observer.observe(ad));
