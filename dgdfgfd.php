<!-- فورم انشاء القصص -->
<!-- زر لفتح المودال يدوياً (اختياري) -->
<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStoryModal">
  Create Story
</button>

<!-- مودال إنشاء قصة (Bootstrap 5) -->
<div class="modal fade" id="createStoryModal" tabindex="-1" aria-labelledby="createStoryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <!-- العنوان -->
      <div class="modal-header">
        <h5 class="modal-title" id="createStoryModalLabel">Create Story</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <!-- جسم المودال: فورم إدخال -->
      <div class="modal-body">
        <form id="createStoryForm" enctype="multipart/form-data">
          <div class="mb-3">
            <label for="storyFileInput" class="form-label">Select Image/Video (<= 39s)</label>
            <input type="file" class="form-control" id="storyFileInput" name="story_media" accept="image/*,video/*">
          </div>
        </form>
      </div>
      
      <!-- ذيل المودال: أزرار -->
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <!-- زر النشر -->
        <button type="button" class="btn btn-primary" id="submitStoryBtn">Publish</button>
      </div>
    </div>
  </div>
</div>
