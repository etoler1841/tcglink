<?php
  header("Content-type: application/json");
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');
  $data = json_decode(file_get_contents("php://input"), true);
  // $stmt = prepare("SELECT password
  //                  FROM mtg_users
  //                  WHERE username = ?");
  if($data['user'] === 'pbgames' && $data['pass'] === 'Marty8504359500'){
    $return['status'] = 'ok';
    setcookie('tcglink_user', 'pbgames', strtotime("+6 hours"));
  } else {
    $return['status'] = 'failed';
  }

  echo json_encode($return);
?>
