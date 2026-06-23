<?php
  require("../classes/auth.php");
  require("header.php");
  require("../classes/db.php");
  require("../classes/phpfix.php");
  require("../classes/picture.php");
  require("../classes/category.php");
?>

<h2>Tambah Karya Baru</h2>
  <form action="index.php" method="POST" enctype="multipart/form-data">
    Judul Karya: <input type="text" name="title" /><br/>
    File Gambar: <input type="file" name="image"><br/>
    Kategori:
  <?php Category::render_select(); ?><br/>
    <input type="submit" name="Add" value="Upload Karya">

  </form>

<?php
  require("footer.php");

?>
