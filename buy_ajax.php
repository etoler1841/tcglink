<?php
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');
  $data = json_decode(file_get_contents("php://input"), true);

  switch($data['method']){
    case 'setSelect':
      $return['catId'] = $data['catId'];
      $stmt = "SELECT p.products_id, pd.products_name
               FROM products p
               LEFT JOIN products_description pd ON p.products_id = pd.products_id
               WHERE p.master_categories_id = ".$data['catId']."
               ORDER BY pd.products_name ASC";
      $result = $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->err;
      } else {
        $return['status'] = 'ok';
        while($row = $result->fetch_array(MYSQLI_ASSOC)){
          $return['cards'][] = array(
            'prodId' => $row['products_id'],
            'prodName' => $row['products_name']
          );
        }
      }
      break;
    case 'cardSelect':
      $return['card']['prodId'] = $data['prodId'];
      $stmt = "SELECT pd.products_name, p.products_image, p.products_price, p.foil_last_update, s.pb_code, cl.is_foil, p.products_quantity
               FROM products p
               LEFT JOIN mtg_card_link cl ON p.products_id = cl.products_id
               LEFT JOIN products_description pd ON p.products_id = pd.products_id
               LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
               WHERE p.products_id = ".$data['prodId'];
      $result = $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
      } else {
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $return['card']['prodImage'] = $row['products_image'];
        $return['card']['prodName'] = $row['products_name'].' ['.$row['pb_code'].']';
        if(strtotime($row['foil_last_update']) < strtotime("6 hours ago")){
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, "$path/update_item.php?prodId=".$data['prodId']);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          $curlRes = json_decode(curl_exec($ch));
          $return['card']['price'] = number_format($curlRes->new_price, 2);
        } else {
          $return['card']['price'] = number_format($row['products_price'], 2);
        }
        $return['card']['foilStatus'] = ($row['is_foil'] == 1) ? 'foil' : 'normal' ;
        $return['card']['currentQty'] = $row['products_quantity'];
      }
      break;
    case 'search':
      $str = "%".$data['str']."%";
      $sql = "SELECT cl.products_id, pd.products_name, s.pb_code, p.products_image, cl.is_foil
              FROM mtg_card_link cl
              LEFT JOIN products p ON cl.products_id = p.products_id
              LEFT JOIN products_description pd ON cl.products_id = pd.products_id
              LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
              WHERE pd.products_name LIKE ?
              ORDER BY s.pb_code ASC, pd.products_name ASC";
      $stmt = $conn->prepare($sql);
      $stmt->bind_param("s", $str);
      $stmt->execute();
      $stmt->bind_result($prodId, $prodName, $setCode, $prodImg, $isFoil);
      $stmt->store_result();
      if($stmt->num_rows > 0){
        $return['status'] = 'ok';
        while($row = $stmt->fetch()){
          $return['cards'][] = array(
            'prodId' => $prodId,
            'prodName' => $prodName,
            'setCode' => $setCode,
            'prodImg' => $prodImg,
            'isFoil' => $isFoil
          );
        }
      } else {
        $return['status'] = 'zero';
      }
  }

  echo json_encode($return);
?>
