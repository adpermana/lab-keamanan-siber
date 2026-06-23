<?php

class Category {
  const SITE = "PHOTOBLOG";

  public $title, $id;
  function __construct($id,$title) {
    $this->id = $id;
    $this->title = $title;
  }

  static function all() {
    global $mysqli;
    $sql = "SELECT * FROM categories";
    $results = $mysqli->query($sql);
    $categories = Array();
    while ($row = $results->fetch_assoc()) {
      $categories[] = new Category($row['id'],$row['title']);
    }
    return $categories;
  }

  static function render_menu() {
    foreach (Category::all() as $cat) {
      echo "\t<li><a href=\"cat.php?id=".h($cat->id)."\">".h($cat->title)." | </a></li>\n";
    }
  }

  static function render_select() {
    echo "<select name=\"category\">";
    foreach (Category::all() as $cat) {
      echo "<option value=".h($cat->id).">".h($cat->title)."</option>";
    }
    echo "</select>";
  }
}
?>
