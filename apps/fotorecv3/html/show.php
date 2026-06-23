<?php
  $site = "Lomba Fotografi Nasional 2024 - Detail Karya";
  require "header.php";
  $pic = Picture::show((int) $_GET["id"]);
?>
  <div class="block" id="block-text">
    <div class="secondary-navigation">
      <div class="content">
        <h2 class="title">Detail Karya: <?php echo h($pic->title); ?></h2>
        <div class="inner">
          <?php echo $pic->render(); ?>
          <p><a href="all.php">&laquo; Kembali ke semua karya</a></p>
        </div>
     </div>

    </div>
  </div>


<?php


  require "footer.php";
?>
