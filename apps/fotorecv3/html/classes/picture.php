<?php

class Picture{
  public $id, $title, $img, $cat;
  function __construct($id, $title, $img, $cat){
    $this->title= $title;
    $this->img = $img;
    $this->id = $id;
    $this->cat = $cat;
  }

  static function all($cat=NULL,$order =NULL) {
    global $mysqli;
    if (!isset($cat))
      $results= $mysqli->query("SELECT * FROM pictures");
    else
      $results= $mysqli->query("SELECT * FROM pictures where cat=".$cat);

    $pictures = Array();
    if ($results) {
      while ($row = $results->fetch_assoc()) {
        $pictures[] = new Picture($row['id'],$row['title'],$row['img'],$row['cat']);
      }
    }
    else {
      echo $mysqli->error;
    }
    return $pictures;
  }

  static function render_all($pics) {
    echo "<ul>\n";
    foreach ($pics as $pic) {
      echo "\t<li>".$pic->render()."</li>\n";
    }
    echo "</ul>\n";
  }

  function render_edit() {
    $str = "<img src=\"uploads/".h($this->img)."\" alt=\"".h($this->title)."\" />";
    return $str;
  }

  function render() {
    $str = "<img src=\"admin/uploads/".h($this->img)."\" alt=\"".h($this->title)."\" />";
    return $str;
  }

  static function last() {
    global $mysqli;
    $result= $mysqli->query("SELECT * FROM pictures ORDER BY id DESC LIMIT 1");
    $row = $result->fetch_assoc();
    if (isset($row)){
      return new Picture($row['id'],$row['title'],$row['img'],$row['cat']);
    }
  }

  static function show($id) {
    global $mysqli;
    $result= $mysqli->query("SELECT * FROM pictures where id=".$id);
    $row = $result->fetch_assoc();
    if (isset($row)){
      return new Picture($row['id'],$row['title'],$row['img'],$row['cat']);
    }
  }

  static function find($id) {
    global $mysqli;
    if (!preg_match('/^[0-9]+$/', $id)) {
      die("ERROR: INTEGER REQUIRED");
    }
    $result = $mysqli->query("SELECT * FROM pictures where id=".$id);
    $row = $result->fetch_assoc();
    $picture = null;
    if (isset($row)){
      $picture = new Picture($row['id'],$row['title'],$row['img'],$row['cat']);
    }
    return $picture;
  }

  static function delete($id) {
    global $mysqli;
    if (!preg_match('/^[0-9]+$/', $id)) {
      die("ERROR: INTEGER REQUIRED");
    }
    $result = $mysqli->query("DELETE FROM pictures where id=".(int)$id);
  }

  static function create(){
    global $mysqli;
    if(isset($_FILES['image'])){
      $dir = 'uploads/';
      $file = basename($_FILES['image']['name']);

      if (!preg_match('/\w{3,12}\.\w{2,4}$/',$file)) {
        die("The filename should only contains between 3 to 8 letters");
      }
      if (preg_match('/\.php$/',$file)) {
        die("NO PHP!!");
      }

      if(!move_uploaded_file($_FILES['image']['tmp_name'], $dir . $file)) {
        die("Error during upload");
      }
      $sql = "INSERT INTO pictures (title, img, cat) VALUES ('";
      $title = $mysqli->real_escape_string($_POST["title"]);
      $img = $mysqli->real_escape_string($file);
      $cat = (int)$_POST["category"];
      $sql .= $title."','".$img."','".$cat;
      $sql.= "')";
      echo $sql;
      $result = $mysqli->query($sql);
      echo $mysqli->error;
    }
  }
}
?>
