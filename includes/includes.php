<?php
  //Turn off error reporting -- enable ONLY when debugging
  //error_reporting(0);

  //Import config file for DB connections
  require(SITE_ROOT.'/includes/db.php');

  //Import TCGPlayer credentials
  require(SITE_ROOT.'/includes/tcgconnect.php');

  //Import PHP files
  foreach(glob(SITE_ROOT.'/includes/autoload/php/*') as $file){
    include($file);
  }

  //Import classes
  foreach(glob(SITE_ROOT.'/includes/autoload/classes/*') as $file){
    include($file);
  }

  if(!isset($suppressMarkup)){

    ?>
    <script src='<?=SITE_ROOT?>/includes/jquery.js'></script>

    <?php

    //Import JS files
    foreach(glob(SITE_ROOT.'/includes/autoload/js/*') as $file){
      ?>
        <script src='<?=$file?>'></script>
      <?php
    }
  }

  //Bearer token
  require(SITE_ROOT.'/includes/auth.php');
?>
