<?php
/*******************************************/
/* 1) تعريف الدوال المساعدة */
/*******************************************/
// دالة لجلب الوسائط من post_media لكل منشور
if (!function_exists('fetchPostMediaList')) {
    function fetchPostMediaList($postId, $conn) {
        $q = "SELECT media_type, media_path FROM post_media WHERE post_id=? ORDER BY id ASC";
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

/**
 * دالة لعرض عدة وسائط (صور/فيديو) في شكل شبكة (Grid) 
 * باستخدام تنسيق داخلي فقط مع !important.
 */
if (!function_exists('renderMediaGrid')) {
    function renderMediaGrid($mediaList) {
        $count = count($mediaList);
        if ($count === 0) return '';

        $basePath = "../uploads/posts/";

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

        // شبكة بسيطة باستخدام float + نسبة عرض
        $html = '<div style="padding: 8px;">';
        $cellWidth = 100 / $cols;
        $cellStyle = "float:left; margin:2px; position:relative; overflow:hidden; height:200px !important; cursor:pointer;";

        $index = 0;
        foreach ($mediaList as $media) {
            $mType = $media['media_type'];
            $mPath = $basePath . $media['media_path'];

            // عند الضغط على الوسيط => فتح السلايدر
            $encodedList = htmlspecialchars(json_encode($mediaList), ENT_QUOTES);
            $onClick = "openMediaSlider($encodedList, $index)";

            $html .= '<div style="width:' . $cellWidth . '% !important; ' . $cellStyle . '" onclick="' . $onClick . '">';
            if ($mType === 'video') {
                $html .= '
                  <video src="' . $mPath . '" 
                         style="width:100% !important; height:100% !important; object-fit:cover !important;" 
                         controls>
                  </video>';
            } else {
                $html .= '
                  <img src="' . $mPath . '" alt="media" 
                       style="width:100% !important; height:100% !important; object-fit:cover !important; display:block;"/>
                ';
            }
            $html .= '</div>';

            $index++;
        }
        $html .= '<div style="clear:both;"></div>';
        $html .= '</div>';
        return $html;
    }
}
/*******************************************/
/* 2) عرض المنشورات بالستايل الداخلي + !important */
/*******************************************/

session_start();
require '../admin/config.php';

if (!isset($_SESSION['user_id'])) {
    die("يجب تسجيل الدخول");
}

// الاتصال بقاعدة البيانات
$conn = new mysqli($host, $username, $password, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// جلب المنشورات (user info + post info)
$sql = "SELECT p.*, u.display_name, u.profile_picture 
        FROM posts p
        JOIN users u ON p.user_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 20";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0):

  while ($post = $result->fetch_assoc()):
    $postId      = $post['id'];
    $displayName = htmlspecialchars($post['display_name']);
    $profilePic  = !empty($post['profile_picture']) ? $post['profile_picture'] : 'default_avatar.jpg';
    $createdAt   = $post['created_at'];
    $content     = nl2br(htmlspecialchars($post['content'] ?? ''));

    // إذا كانت الحقول media_type/media_path موجودة في نفس جدول posts
    $mediaType   = $post['media_type'] ?? 'none';
    $mediaPath   = $post['media_path'] ?? null;

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
        // لا توجد ملفات في post_media => نرجع للحقول الأصلية
        if (!empty($mediaPath) && $mediaType !== 'none') {
            $tempList = [[ 'media_type' => $mediaType, 'media_path' => $mediaPath ]];
            $postMediaHTML = renderMediaGrid($tempList);
        }
    }
?>
<!-- تصميم المنشور باستخدام ستايل داخلي + !important -->
<div style="
  box-shadow:0 1px 2px rgba(0,0,0,0.1) !important;
  background-color:#fff !important;
  margin-top:1rem !important;
  border-radius:8px !important;
  color:#333 !important;
  padding-bottom:1rem !important;
">
  <!-- POST AUTHOR -->
  <div style="display:flex !important; align-items:center !important; justify-content:space-between !important; padding:0.5rem 1rem !important;">
    <div style="display:flex !important; align-items:center !important; gap:0.5rem !important;">
      <div style="position:relative !important;">
        <img src="uploads/<?= htmlspecialchars($profilePic) ?>" 
             alt="Profile picture" 
             style="width:40px !important; height:40px !important; border-radius:50% !important; object-fit:cover !important;">
        <span style="
          position:absolute !important; 
          background-color:#0f0 !important;
          border:2px solid #fff !important; 
          border-radius:50% !important; 
          width:12px !important; 
          height:12px !important; 
          bottom:0 !important; 
          right:0 !important;
        "></span>
      </div>
      <div>
        <div style="font-weight:600 !important;">
          <a href="profile.php?id=<?= $postId ?>" style="text-decoration:none !important; color:inherit !important;">
            <?= $displayName ?>
          </a>
        </div>
        <small style="color:#888 !important;"><?= $timeText ?></small>
      </div>
    </div>
    <div style="
      width:32px !important; 
      height:32px !important; 
      display:flex !important; 
      align-items:center !important; 
      justify-content:center !important; 
      border-radius:50% !important; 
      color:#999 !important; 
      cursor:pointer !important;
    ">
      <i class='bx bx-dots-horizontal-rounded'></i>
    </div>
  </div>
  <!-- END POST AUTHOR -->

  <!-- POST CONTENT -->
  <div style="padding:0.5rem 1rem !important; text-align:justify !important;">
    <?= $content ?>
  </div>
  <!-- END POST CONTENT -->

  <!-- POST MEDIA (صور/فيديو) -->
  <?= $postMediaHTML ?>
  <!-- END POST MEDIA -->

  <!-- POST REACT (التصميم الجديد) -->
  <div style="padding:0.5rem 1rem !important;">
    <div class="post-react" style="margin-bottom:0.5rem;">
      <div class="react-top" style="display:flex; align-items:center; justify-content:space-between;">
        <div class="react-icons" style="display:flex; align-items:center; gap:6px;">
          <span>999</span>
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
          <span>90 comments</span>
          <span>66 Shares</span>
        </div>
      </div>
    </div>
  </div>
  <!-- END POST REACT -->

  <!-- POST ACTION (التصميم الجديد) -->
  <div style="padding:0.5rem 1rem !important;">
    <div class="post-action">
      <div class="action-bar" style="display:flex; justify-content:space-around;">
        <div class="action-btn" style="display:flex; align-items:center; gap:5px; cursor:pointer;">
          <i class='bx bx-like'></i>
          <span>Like</span>
        </div>
        <div class="action-btn" style="display:flex; align-items:center; gap:5px; cursor:pointer;">
          <i class='bx bx-comment'></i>
          <span>Comment</span>
        </div>
        <div class="action-btn" style="display:flex; align-items:center; gap:5px; cursor:pointer;">
          <i class='bx bx-share bx-flip-horizontal'></i>
          <span>Share</span>
        </div>
      </div>
    </div>
  </div>
  <!-- END POST ACTION -->

  <!-- LIST COMMENT (التصميم الجديد) -->
  <div style="padding:0.5rem 1rem !important;">
    <div class="comments-section">
      <!-- COMMENT EXAMPLE -->
      <div class="comment-item" style="display:flex; gap:0.5rem; margin-bottom:0.5rem;">
        <img src="./images/avt-5.jpg" alt="Profile" class="comment-pic" 
             style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
        <div>
          <div class="comment-bubble" style="background-color:#f1f1f1; padding:0.5rem; border-radius:12px;">
            <strong>John Doe</strong><br>
            <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
          </div>
          <div class="comment-actions" style="color:#888; font-size:0.85rem; margin-top:0.25rem;">
            <span style="cursor:pointer;">Like</span>
            <span> · </span>
            <span style="cursor:pointer;">Reply</span>
            <span> · </span>
            10m
          </div>

          <!-- COMMENT REPLY EXAMPLE -->
          <div class="comment-reply" style="display:flex; gap:0.5rem; margin-top:0.5rem;">
            <img src="./images/avt-7.jpg" alt="Profile" class="comment-pic"
                 style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
            <div>
              <div class="comment-bubble" style="background-color:#f1f1f1; padding:0.5rem; border-radius:12px;">
                <strong>John Doe</strong><br>
                <span>Lorem ipsum dolor sit amet consectetur adipisicing elit.</span>
              </div>
              <div class="comment-actions" style="color:#888; font-size:0.85rem; margin-top:0.25rem;">
                <span style="cursor:pointer;">Like</span>
                <span> · </span>
                <span style="cursor:pointer;">Reply</span>
                <span> · </span>
                10m
              </div>
            </div>
          </div>
          <!-- END COMMENT REPLY -->
        </div>
      </div>
    </div>
  </div>
  <!-- END LIST COMMENT -->

  <!-- COMMENT FORM (التصميم الجديد) -->
  <div style="padding:0.5rem 1rem !important;">
    <div class="comments-section">
      <div class="comment-form" style="display:flex; gap:0.5rem;">
        <img src="./images/tuat.jpg" alt="Profile" class="comment-pic" 
             style="width:36px; height:36px; border-radius:50%; object-fit:cover;">
        <div class="comment-input" style="flex:1; display:flex; align-items:center; background-color:#f1f1f1; border-radius:20px; padding:0.5rem;">
          <input type="text" placeholder="Write a comment..." 
                 style="border:none; background-color:transparent; flex:1; outline:none;">
          <div class="icons" style="display:flex; gap:0.25rem; margin-left:0.25rem;">
            <span><i class='bx bx-smile'></i></span>
            <span><i class='bx bx-camera'></i></span>
            <span><i class='bx bxs-file-gif'></i></span>
            <span><i class='bx bx-happy-heart-eyes'></i></span>
          </div>
        </div>
      </div>
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

<!-- مودال (Popup) لسلايدر الوسائط (Inline CSS) -->
<div id="mediaSliderModal" style="
  position:fixed !important; 
  top:0 !important; 
  left:0 !important; 
  width:100% !important; 
  height:100% !important; 
  background-color:rgba(0,0,0,0.8) !important; 
  display:none !important; 
  align-items:center !important; 
  justify-content:center !important;
  z-index:9999 !important;
">
  <!-- زر إغلاق -->
  <div style="
    position:absolute !important; 
    top:1rem !important; 
    right:1rem !important; 
    cursor:pointer !important; 
    color:#fff !important; 
    font-size:1.5rem !important;
  " onclick="closeMediaSlider()">
    &times;
  </div>
  <!-- محتوى السلايدر -->
  <div style="max-width:90% !important; max-height:90% !important; text-align:center !important;">
    <img id="sliderImage" src="" alt="" 
         style="max-width:100% !important; max-height:80vh !important; display:none !important; object-fit:contain !important; background-color:#000 !important;"/>
    <video id="sliderVideo" 
           style="max-width:100% !important; max-height:80vh !important; display:none !important; background-color:#000 !important;" 
           controls>
    </video>

    <!-- أزرار السابق والتالي -->
    <div style="margin-top:1rem !important;">
      <button style="margin-right:1rem !important; padding:0.5rem 1rem !important;" onclick="prevMedia()">Prev</button>
      <button style="padding:0.5rem 1rem !important;" onclick="nextMedia()">Next</button>
    </div>
  </div>
</div>

<script>
let currentMediaList = [];
let currentIndex = 0;

function openMediaSlider(mediaList, startIndex) {
  currentMediaList = mediaList;
  currentIndex = startIndex;
  document.getElementById('mediaSliderModal').style.display = 'flex';
  showCurrentMedia();
}

function closeMediaSlider() {
  document.getElementById('mediaSliderModal').style.display = 'none';
}

function showCurrentMedia() {
  const sliderImage = document.getElementById('sliderImage');
  const sliderVideo = document.getElementById('sliderVideo');

  sliderImage.style.display = 'none';
  sliderVideo.style.display = 'none';
  sliderVideo.pause();

  if (!currentMediaList || currentMediaList.length === 0) return;

  const basePath = "uploads/posts/";
  const item = currentMediaList[currentIndex];
  const fullPath = basePath + item.media_path;

  if (item.media_type === 'video') {
    sliderVideo.src = fullPath;
    sliderVideo.style.display = 'block';
  } else {
    sliderImage.src = fullPath;
    sliderImage.style.display = 'block';
  }
}

function prevMedia() {
  if (currentIndex > 0) {
    currentIndex--;
    showCurrentMedia();
  }
}

function nextMedia() {
  if (currentIndex < currentMediaList.length - 1) {
    currentIndex++;
    showCurrentMedia();
  }
}
</script>
