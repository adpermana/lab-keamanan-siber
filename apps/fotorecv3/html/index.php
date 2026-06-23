<?php
  $site = "Lomba Fotografi Nasional 2024 - Karya Terbaru";
  require "header.php";
  $pic = Picture::last();
?>
  <div class="block" id="block-text">
    <div class="secondary-navigation">
      <div class="content">
        <?php if ($pic): ?>
        <h2 class="title">Karya Terbaru: <?php echo h($pic->title); ?></h2>

        <div class="inner" align="center">
          <p>
            <?php echo $pic->render(); ?>
          </p>
        </div>
        <?php else: ?>
        <h2 class="title">Selamat Datang di Lomba Fotografi Nasional 2024</h2>
        <div class="inner" align="center">
          <p>Belum ada karya yang dikirim. Jadilah yang pertama!</p>
        </div>
        <?php endif; ?>
     </div>

    </div>
  </div>


<?php


  require "footer.php";
?>
