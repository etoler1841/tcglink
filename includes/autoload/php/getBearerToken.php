<?php
  function getBearerToken($tcgCred){
    global $conn;

    $stmt = "SELECT value
             FROM mtg_tcgp_config
             WHERE setting = 'bearer_expire'";
    $result = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
    if(strtotime($result[0]) < time()){
      $ch = curl_init();
      $headers = array(
        "Content-Type: application/json",
        "Accept: application/json",
        "X-Tcg-Access-Token: ".$tcgCred['accessToken']
      );
      $fields = "grant_type=client_credentials&client_id=".$tcgCred['publicKey']."&client_secret=".$tcgCred['privateKey'];

      curl_setopt($ch, CURLOPT_URL,"http://api.tcgplayer.com/token");
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

      $response = json_decode(curl_exec($ch));
      $token = $response->access_token;
      $expire = date("Y-m-d H:i:s", time() + $response->expires_in - 500);
      curl_close($ch);

      $stmt = "UPDATE mtg_tcgp_config
               SET value = '$token'
               WHERE setting = 'bearer_token'";
      $conn->query($stmt);

      $stmt = "UPDATE mtg_tcgp_config
               SET value = '$expire'
               WHERE setting = 'bearer_expire'";
      $conn->query($stmt);
    } else {
      $stmt = "SELECT value
               FROM mtg_tcgp_config
               WHERE setting = 'bearer_token'";
      $result = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
      $token = $result[0];
    }
    return $token;
  }
?>
