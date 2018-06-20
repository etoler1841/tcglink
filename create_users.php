<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');

  $users = array(
    array(
      'user' => 'etoler',
      'first' => 'Eric',
      'last' => 'Toler',
      'pass' => password_hash('8505258645', PASSWORD_DEFAULT),
      'admin' => 1
    ),
    array(
      'user' => 'bprice',
      'first' => 'Ben',
      'last' => 'Price',
      'pass' => password_hash('8503931206', PASSWORD_DEFAULT),
      'admin' => 1
    ),
    array(
      'user' => 'mgaut',
      'first' => 'Michael',
      'last' => 'Gaut',
      'pass' => password_hash('8503938930', PASSWORD_DEFAULT),
      'admin' => 1
    ),
    array(
      'user' => 'awhite',
      'first' => 'Alex',
      'last' => 'White',
      'pass' => password_hash('8508984733', PASSWORD_DEFAULT),
      'admin' => 0
    ),
    array(
      'user' => 'amoses',
      'first' => 'Alyssa',
      'last' => 'Moses',
      'pass' => password_hash('8502611703', PASSWORD_DEFAULT),
      'admin' => 0
    ),
    array(
      'user' => 'cworth',
      'first' => 'Chris',
      'last' => 'Worth',
      'pass' => password_hash('8503328502', PASSWORD_DEFAULT),
      'admin' => 0
    ),
    array(
      'user' => 'jgibbons',
      'first' => 'Jayson',
      'last' => 'Gibbons',
      'pass' => password_hash('8506073393', PASSWORD_DEFAULT),
      'admin' => 0
    ),
    array(
      'user' => 'jmateja',
      'first' => 'James',
      'last' => 'Mateja',
      'pass' => password_hash('8477088091', PASSWORD_DEFAULT),
      'admin' => 0
    ),
    array(
      'user' => 'dbowen',
      'first' => 'David',
      'last' => 'Bowen',
      'pass' => password_hash('8155456163', PASSWORD_DEFAULT),
      'admin' => 0
    ),
    array(
      'user' => 'epolk',
      'first' => 'Evan',
      'last' => 'Polk',
      'pass' => password_hash('8505038235', PASSWORD_DEFAULT),
      'admin' => 0
    ),
  );
  $stmt = $conn->prepare("INSERT INTO employee SET admin = ?, employee_username = ?, employee_first_name = ?, employee_last_name = ?, employee_password = ?, password_change = 1");
  foreach($users as $user){
    $stmt->bind_param("issss", $user['admin'], $user['user'], $user['first'], $user['last'], $user['pass']);
    $stmt->execute();
  }
?>
