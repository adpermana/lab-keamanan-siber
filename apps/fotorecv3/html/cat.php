<?php
  require "header.php";
  $pics = Picture::all($_GET["id"]);
  $cat_name = isset($pics[0]) ? h($pics[0]->title) : 'Kategori';
?>
    <div class="block" id="block-text">
    <div class="secondary-navigation">
    <h2 class="title">Kategori: <?php
      global $mysqli;
      $cat_result = $mysqli->query("SELECT title FROM categories WHERE id=" . (int)$_GET["id"]);
      if ($cat_row = $cat_result->fetch_assoc()) {
        echo h($cat_row['title']);
      }
    ?></h2>
<?php
    foreach ($pics as $pic) {
?>
      <div class="content">
        <h2 class="title">Karya: <?php echo h($pic->title); ?></h2>
        <div class="inner" align="center">
          <p>
            <?php echo $pic->render(); ?>
          </p>
        </div>
     </div>

<?php
    }
?>

    </div>
  </div>


<?php
  require "footer.php";
?>
