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
            if(isset($curlRes->errors)){
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
          if(isset($curlRes->errors)){
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
        if(isset($curlRes->errors)){
          $return['status'] = 'err';
          $return['errors']['curl'] = $curlRes->errors;
        } else {
          $return['new_price'] = number_format($curlRes->new_price, 2);
        }
      }
      break;
    case 'makeFoil':
      $stmt = "SELECT pd.products_name, p.master_categories_id, p.products_image, p.products_model, s.pb_code, cl.multiverse_id, cl.tcgp_id, s.is_standard
               FROM products p
               LEFT JOIN products_description pd ON p.products_id = pd.products_id
               LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
               LEFT JOIN mtg_card_link cl ON p.products_id = cl.products_id
               WHERE p.products_id = ".$data['prodId'];
      $result = $conn->query($stmt);
      $row = $result->fetch_array(MYSQLI_ASSOC);
      $cardName = $conn->real_escape_string($row['products_name'].' - Foil');
      $model = str_replace("--", "-", substr($row['products_model'], 0, 27).'-Foil');
      $stmt = "INSERT INTO products
               SET products_model = '$model',
                   products_image = '".$row['products_image']."',
                   products_date_added = '".date("Y-m-d H:i:s")."',
                   products_weight = 0.004,
                   products_status = 0,
                   products_qty_box_status = 1,
                   manufacturers_id = 0,
                   products_quantity_mixed = 1,
                   master_categories_id = ".$row['master_categories_id'].",
                   products_full_name = '$cardName',
                   img_update = 1";
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
        exit(json_encode($return));
      }
      $prodId = $conn->insert_id;

      $stmt = "INSERT INTO products_description
               SET products_id = $prodId,
                   products_name = '$cardName'";
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
        exit(json_encode($return));
      }

      $stmt = "INSERT INTO products_to_categories
               SET products_id = $prodId,
                   categories_id = ".$row['master_categories_id'];
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
        exit(json_encode($return));
      }

      $stmt = "INSERT INTO mtg_card_link
               SET products_id = $prodId,
                   tcgp_id = ".$row['tcgp_id'].",
                   is_foil = 1";
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
        exit(json_encode($return));
      }
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "$path/update_item.php?prodId=".$prodId);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_exec($ch);
      $stmt = "SELECT products_price FROM products WHERE products_id = $prodId";
      $row2 = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
      $price = $row2[0];

      $return['status'] = 'ok';
      $return['card'] = array(
        'prodId' => $prodId,
        'showcaseStatus' => ((($price >= 2 && $row['is_standard'] == 0) || ($price >= 1 && $row['is_standard'] == 1)) ? 'showcase' : 'box'),
        'prodImg' => $row['products_image'],
        'tcgpId' => $row['tcgp_id'],
        'prodName' => $row['products_name'].' - Foil',
        'setCode' => $row['pb_code'],
        'price' => number_format($price, 2)
      );
      break;
  }

  echo json_encode($return);
?>
