<?php
  require 'classes/db.php';
  require 'classes/phpfix.php';
  require 'classes/picture.php';
  require 'classes/category.php';
?>


<html>
  <head>
    <link rel="stylesheet" id="base" href="css/default.css" type="text/css" media="screen" />
    <title><?php echo (isset($site)) ? h($site) : "Lomba Fotografi Nasional 2024" ; ?></title>
  </head>
  <body>

  <div id="header">
    <div id="logo">
      <h1><a href="index.php">Lomba Fotografi Nasional 2024</a></h1>
    </div>
    <div id="menu">
      <ul>
        <li class="active">
            <a href="/"> Beranda  |</a>
        </li>
        <?php Category::render_menu(); ?>
        <li>
          <a href="/all.php">Semua Karya |</a>
        </li>

        <li>
          <a href="/admin/">Admin</a>
        </li>
        </ul>
      </div>
    </div>

  </div>

    <div id="page">
      <div id="content">



