<?php
  header("Content-type: application/json");
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');
  $data = $return['input'] = json_decode(file_get_contents("php://input"), true);
  $stmt = $conn->prepare("SELECT employee_password, password_change
                          FROM employee
                          WHERE employee_username = ?");
  $stmt->bind_param("s", strtolower($data['user']));
  $stmt->execute();
  $stmt->bind_result($password, $passChange);
  $stmt->store_result();
  if(!$stmt->num_rows){
    $return['status'] = 'err';
    $return['errrors'][] = 'Username not found';
  }
  $stmt->fetch();
  $stmt->close();
  if(password_verify($data['pass'], $password)){
    $return['status'] = 'ok';
    if($passChange) $return['passUpdate'] = true;
    setcookie('tcglink_user', $data['user'], strtotime("+1 hour"));
  } else {
    $return['status'] = 'err';
    $return['errors'][] = 'Invalid password';
  }
  echo json_encode($return);
?>
