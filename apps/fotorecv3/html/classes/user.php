<?php

class User {
  const SITE= "PHOTOBLOG";
  static function login($user, $password) {
    global $mysqli;
    $sql = "SELECT * FROM users where login=\"";
    $sql.= $mysqli->real_escape_string($user);
    $sql.= "\" and password=md5(\"";
    $sql.= $mysqli->real_escape_string($password);
    $sql.= "\")";
    $result = $mysqli->query($sql);
    if ($result && $row = $result->fetch_assoc()) {
      if ($user === $row['login']) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
?>
