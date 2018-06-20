<?php
  header("Content-type: application/json");
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');
  $data = $return['input'] = json_decode(file_get_contents("php://input"), true);
  $user = strtolower($_COOKIE['tcglink_user']);
  $stmt = $conn->prepare("SELECT employee_password
                          FROM employee
                          WHERE employee_username = ?");
  $stmt->bind_param("s", $user);
  $stmt->execute();
  $stmt->bind_result($password);
  $stmt->store_result();
  if(!$stmt->num_rows){
    $return['status'] = 'err';
    $return['errors'][] = 'Your username wasn\'t found. Please log out and back in again.';
  }
  $stmt->fetch();
  $stmt->close();
  if(password_verify($data['old'], $password)){
    $newPass = password_hash($data['new'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE employee
                            SET employee_password = ?,
                                password_change = 0
                            WHERE employee_username = ?");
    $stmt->bind_param("ss", $newPass, $user);
    $stmt->execute();
    $stmt->close();
    $return['status'] = 'ok';
  } else {
    $return['status'] = 'err';
    $return['errors'][] = 'Old password is incorrect.';
  }

  echo json_encode($return);
?>
