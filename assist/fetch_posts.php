<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require '../admin/config.php';

// تأكد من وجود جلسة تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}
$currentUserId = $_SESSION['user_id'];

/* =============== الدوال المساعدة =============== */
if (!function_exists('fetchPostMediaList')) {
    function fetchPostMediaList($postId, $conn) {
        $q = "SELECT media_type, media_path 
              FROM post_media 
              WHERE post_id=? 
              ORDER BY id ASC";
        $st = $conn->prepare($q);
        $st->bind_param("i", $postId);
        $st->execute();
        $rs = $st->get_result();
        $arr = [];
        while ($r = $rs->fetch_assoc()) {
            $arr[] = $r;
        }
        $st->close();
        return $arr;
    }
}

if (!function_exists('renderMediaGrid')) {
    function renderMediaGrid($mediaList) {
        $count = count($mediaList);
        if ($count === 0) return '';

        $basePath = "uploads/posts/";

        // منطق بسيط لتحديد عدد الأعمدة
        $cols = 1;
        if ($count == 2) {
            $cols = 2;
        } elseif ($count == 3) {
            $cols = 3;
        } elseif ($count == 4) {
            $cols = 2; 
        } elseif ($count >= 5) {
            $cols = 3;
        }

        $html = '<div style="padding:8px;">';
        $cellWidth = 100 / $cols;
        $cellStyle = "float:left; margin:2px; position:relative; overflow:hidden; height:200px !important; cursor:pointer;";

        $index = 0;
        foreach ($mediaList as $media) {
            $mType = $media['media_type'];
            $mPath = $basePath . $media['media_path'];

            // عند الضغط => فتح السلايدر
            $encodedList = htmlspecialchars(json_encode($mediaList), ENT_QUOTES);
            $onClick = "openMediaSlider($encodedList, $index)";

            $html .= '<div style="width:' . $cellWidth . '% !important; ' . $cellStyle . '" onclick="' . $onClick . '">';
            if ($mType === 'video') {
                $html .= '<video src="' . $mPath . '" 
                           style="width:100% !important; height:100% !important; object-fit:cover !important;" 
                           controls></video>';
            } else {
                $html .= '<img src="' . $mPath . '" alt="media" 
                           style="width:100% !important; height:100% !important; object-fit:cover !important; display:block;"/>';
            }
            $html .= '</div>';
            $index++;
        }
        $html .= '<div style="clear:both;"></div>';
        $html .= '</div>';
        return $html;
    }
}
/* ============================================= */

// الاتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// جلب المنشورات + اسم المستخدم وصورته
$sql = "
  SELECT p.*, u.display_name, u.profile_picture, u.id AS user_id
  FROM posts p
  JOIN users u ON p.user_id = u.id
  ORDER BY p.created_at DESC
  LIMIT 20
";
$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . $conn->error);
}

if ($result->num_rows > 0):
  while ($post = $result->fetch_assoc()):
    $postId      = $post['id'];
    $userId      = $post['user_id'];
    $displayName = htmlspecialchars($post['display_name']);
    $profilePic  = !empty($post['profile_picture']) ? $post['profile_picture'] : 'default_avatar.jpg';
    $createdAt   = $post['created_at'];
    $content     = nl2br(htmlspecialchars($post['content'] ?? ''));

    // حساب وقت النشر
    $timeDiff = time() - strtotime($createdAt);
    if ($timeDiff < 3600) {
      $minutes = floor($timeDiff / 60);
      $timeText = $minutes . 'm';
    } elseif ($timeDiff < 86400) {
      $hours = floor($timeDiff / 3600);
      $timeText = $hours . 'h';
    } else {
      $days = floor($timeDiff / 86400);
      $timeText = $days . 'd';
    }

    // جلب جميع الوسائط من post_media
    $mediaList = fetchPostMediaList($postId, $conn);
    // تحضير كود عرض الوسائط
    $postMediaHTML = '';
    if (!empty($mediaList)) {
        $postMediaHTML = renderMediaGrid($mediaList);
    } else {
        // لا توجد ملفات في post_media => نرجع للحقول الأصلية في جدول posts
        $mediaType = $post['media_type'] ?? 'none';
        $mediaPath = $post['media_path'] ?? null;
        if (!empty($mediaPath) && $mediaType !== 'none') {
            $tempList = [[ 'media_type' => $mediaType, 'media_path' => $mediaPath ]];
            $postMediaHTML = renderMediaGrid($tempList);
        }
    }

    // استعلام واحد لجلب عدد الإعجابات + هل المستخدم أعجب بالمنشور
    $likeSql = "
      SELECT 
        COUNT(*) AS total_likes,
        SUM(IF(user_id=?,1,0)) AS i_liked
      FROM likes
      WHERE post_id=?
    ";
    $stLike = $conn->prepare($likeSql);
    $stLike->bind_param("ii", $currentUserId, $postId);
    $stLike->execute();
    $likeRes = $stLike->get_result()->fetch_assoc();
    $stLike->close();
    $totalLikes = (int)$likeRes['total_likes'];
    $iLiked     = ((int)$likeRes['i_liked'] > 0);

    // النص الافتراضي لزر الإعجاب
    $likeBtnText = $iLiked ? "Unlike" : "Like";
?>
<!-- تصميم المنشور -->
<div style="
  box-shadow:0 1px 2px rgba(0,0,0,0.1);
  background-color:#fff;
  margin-top:1rem;
  border-radius:8px;
  color:#333;
  padding-bottom:1rem;
">
  <!-- POST AUTHOR -->
  <div style="display:flex; align-items:center; justify-content:space-between; padding:0.5rem 1rem;">
    <div style="display:flex; align-items:center; gap:0.5rem;">
      <div style="position:relative;">
        <img src="uploads/<?= htmlspecialchars($profilePic) ?>" 
             alt="Profile picture" 
             style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
        <span style="
          position:absolute; 
          background-color:#0f0;
          border:2px solid #fff; 
          border-radius:50%; 
          width:12px; 
          height:12px; 
          bottom:0; 
          right:0;
        "></span>
      </div>
      <div>
        <div style="font-weight:600;">
          <a href="profile.php?id=<?= $userId ?>" style="text-decoration:none; color:inherit;">
            <?= $displayName ?>
          </a>
        </div>
        <small style="color:#888;"><?= $timeText ?></small>
      </div>
    </div>
    <div style="
      width:32px; 
      height:32px; 
      display:flex; 
      align-items:center; 
      justify-content:center; 
      border-radius:50%; 
      color:#999; 
      cursor:pointer;
    ">
      <i class='bx bx-dots-horizontal-rounded'></i>
    </div>
  </div>
  <!-- END POST AUTHOR -->

  <!-- POST CONTENT -->
  <div style="padding:0.5rem 1rem; text-align:justify;">
    <?= $content ?>
  </div>
  <!-- END POST CONTENT -->

  <!-- POST MEDIA -->
  <?= $postMediaHTML ?>
  <!-- END POST MEDIA -->

  <!-- POST REACT -->
  <div style="padding:0.5rem 1rem;">
    <div style="margin-bottom:0.5rem; display:flex; align-items:center; justify-content:space-between;">
      <div style="display:flex; align-items:center; gap:6px;">
        <span id="likesCount_<?= $postId ?>"><?= $totalLikes ?></span>
        <span style="color:#b00; font-size:1.25rem; margin-left:-4px; display:flex; align-items:center;">
          <i class='bx bxs-angry'></i>
        </span>
        <span style="color:red; font-size:1.25rem; margin-left:-4px; display:flex; align-items:center;">
          <i class='bx bxs-heart-circle'></i>
        </span>
        <span style="color:gold; font-size:1.25rem; margin-left:-4px; display:flex; align-items:center;">
          <i class='bx bx-happy-alt'></i>
        </span>
      </div>
      <div style="color:#888;">
        <!-- عداد التعليقات -->
        <span id="commentsCount_<?= $postId ?>"></span>
        <span style="margin-left:10px;"></span>
      </div>
    </div>
  </div>
  <!-- END POST REACT -->

  <!-- POST ACTION -->
  <div style="padding:0.5rem 1rem;">
    <div style="display:flex; justify-content:space-around;">
      <!-- زر الإعجاب -->
      <div class="action-btn like-btn" data-post-id="<?= $postId ?>"
           style="display:flex; align-items:center; gap:5px; cursor:pointer;"
           onclick="toggleLikePost(<?= $postId ?>, this)">
        <i class='bx bx-like'></i>
        <span><?= $likeBtnText ?></span>
      </div>

      <div style="display:flex; align-items:center; gap:5px; cursor:pointer;">
        <i class='bx bx-comment'></i>
        <span></span>
      </div>
      <div style="display:flex; align-items:center; gap:5px; cursor:pointer;">
        <i class='bx bx-share bx-flip-horizontal'></i>
        <span></span>
      </div>
    </div>
  </div>
  <!-- END POST ACTION -->

  <!-- LIST COMMENT -->
  <div style="padding:0.5rem 1rem;">
  <div class="comments-section" id="commentsContainer_<?php echo $post['id']; ?>" style="margin-top:0.5rem;">
      <!-- يتم حقن التعليقات ديناميكيًا عبر fetchComments(postId) -->
    </div>
  </div>
  <!-- END LIST COMMENT -->

  <!-- COMMENT FORM -->
  <div style="padding:0.5rem 1rem;">
    <div style="display:flex; gap:0.5rem;">
      <img src="./images/tuat.jpg" alt="Profile" 
           style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
      <div style="flex:1; display:flex; align-items:center; background-color:#f1f1f1; border-radius:20px; padding:0.5rem;">
        <input id="commentInput_<?= $postId ?>" type="text" placeholder="Write a comment..."
               style="border:none; background-color:transparent; flex:1; outline:none;"
               onkeypress="if(event.key==='Enter'){ addComment(<?= $postId ?>); }">
        <div style="display:flex; gap:0.25rem; margin-left:0.25rem;">
          <span><i class='bx bx-smile'></i></span>
          <span><i class='bx bx-camera'></i></span>
          <span><i class='bx bxs-file-gif'></i></span>
          <span><i class='bx bx-happy-heart-eyes'></i></span>
        </div>
      </div>
      <!-- زر إرسال تعليق -->
      <button class="comment-send-btn" data-post-id="<?= $postId ?>" style="margin-left:4px;"
              onclick="addComment(<?= $postId ?>)">
        Send
      </button>
    </div>
  </div>
  <!-- END COMMENT FORM -->
</div>
<!-- END POST -->
<?php
  endwhile;
else:
  echo '<p style="text-align:center; margin-top:1rem; color:#666;">لا توجد منشورات حتى الآن.</p>';
endif;

$conn->close();
?>

<!-- مودال لسلايدر الوسائط -->
<!-- (أبقينا تعريفًا واحدًا فقط كي لا يتكرر currentMediaList) -->
<div id="mediaSliderModal" style="
  position:fixed; top:0; left:0; width:100%; height:100%;
  background-color:rgba(0,0,0,0.8); display:none;
  align-items:center; justify-content:center; z-index:9999;
">
  <div style="position:absolute; top:1rem; right:1rem; cursor:pointer; color:#fff; font-size:1.5rem;"
       onclick="closeMediaSlider()">
    &times;
  </div>
  <div style="max-width:90%; max-height:90%; text-align:center;">
    <img id="sliderImage" src="" alt="" 
         style="max-width:100%; max-height:80vh; display:none; object-fit:contain; background-color:#000;"/>
    <video id="sliderVideo" controls 
           style="max-width:100%; max-height:80vh; display:none; background-color:#000;">
    </video>
    <div style="margin-top:1rem;">
      <button style="margin-right:1rem; padding:0.5rem 1rem;" onclick="prevMedia()">Prev</button>
      <button style="padding:0.5rem 1rem;" onclick="nextMedia()">Next</button>
    </div>
  </div>
</div>


