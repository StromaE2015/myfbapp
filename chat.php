    <div class="col-md-3">
      <div class="sidebar">
        <h5>قائمة الأصدقاء</h5>
        <ul class="list-unstyled">
          <?php foreach($friends as $friend): ?>
            <li><?= $friend['display_name'] ?></li>
          <?php endforeach; ?>
        </ul>
        <hr>
        <h5>الدردشة</h5>
        <p>قائمة الدردشات الأخيرة (يمكن تطويرها لاحقًا)</p>
      </div>
    </div>
  </div>
</div>
