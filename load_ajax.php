<?php
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');
  $data = json_decode(file_get_contents("php://input"), true);
  $return['product'] = $data['prodId'];

  switch ($data['method']){
    case 'addQty':
      if(!$data['qty']){
        $return['status'] = 'qty is zero';
        break;
      }
      $stmt = "UPDATE products
               SET products_quantity = products_quantity + ".$data['qty']."
               WHERE products_id = ".$data['prodId'];
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
      } else {
        $return['status'] = 'ok';
      }
      $stmt = "SELECT products_quantity
               FROM products
               WHERE products_id = ".$data['prodId'];
      $row = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
      if($row[0] <= 0){
        $stmt = "UPDATE products
                 SET products_quantity = 0,
                     products_status = 0
                 WHERE products_id = ".$data['prodId'];
      } else {
        $stmt = "UPDATE products
                 SET products_status = 1
                 WHERE products_id = ".$data['prodId'];
      }
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
      }
      break;
    case 'update':
      switch ($data['prop']){
        case 'tcgpId':
          $stmt = "UPDATE mtg_card_link
                   SET tcgp_id = ".$data['val']."
                   WHERE products_id = ".$data['prodId'];
          $conn->query($stmt);
          if($conn->error){
            $return['status'] = 'err';
            $return['errors'][] = $conn->error;
          } else {
            $return['status'] = 'ok';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "$path/update_item.php?prodId=".$data['prodId']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $curlRes = json_decode(curl_exec($ch));
            if($curlRes->errors){
              $return['status'] = 'err';
              $return['errors']['curl'] = $curlRes->errors;
            } else {
              $return['new_price'] = number_format($curlRes->new_price, 2);
            }
          }
          break;
        case 'price':
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, "$path/update_item.php?prodId=".$data['prodId']);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $curlRes = json_decode(curl_exec($ch));
          if($curlRes->errors){
            $return['status'] = 'err';
            $return['errors']['curl'] = $curlRes->errors;
          } else {
            $return['status'] = 'ok';
            $return['new_price'] = number_format($curlRes->new_price, 2);
          }
      }
      break;
    case 'foil':
      $stmt = "UPDATE mtg_card_link
               SET is_foil = ".$data['val']."
               WHERE products_id = ".$data['prodId'];
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
      } else {
        $return['status'] = 'ok';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "$path/update_item.php?prodId=".$data['prodId']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curlRes = json_decode(curl_exec($ch));
        if($curlRes->errors){
          $return['status'] = 'err';
          $return['errors']['curl'] = $curlRes->errors;
        } else {
          $return['new_price'] = number_format($curlRes->new_price, 2);
        }
      }
      break;
  }

  echo json_encode($return);
?>
