<?php
  $stmt = "SELECT *
           FROM mtg_tcgp_config
           WHERE setting IN ('publicKey', 'privateKey', 'accessToken', 'applicationID')";
  $result = $conn->query($stmt);
  while($row = $result->fetch_array(MYSQLI_ASSOC)){
    $tcgCred[$row['setting']] = $row['value'];
  }
?>
