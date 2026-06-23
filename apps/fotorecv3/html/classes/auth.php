<?php
  session_start();
  require('../classes/db.php');
  require('../classes/user.php');

  if (isset($_POST["user"]) && isset($_POST["password"]))
    if (User::login($_POST["user"],$_POST["password"]))
      $_SESSION["admin"] = User::SITE;

  if (!isset($_SESSION["admin"]) || $_SESSION["admin"] != User::SITE) {
    header( 'Location: /admin/login.php' );
    die();
  }

?>
