<?php
  define('SITE_ROOT', '.');
  $suppressMarkup = true;
  require(SITE_ROOT.'/includes/includes.php');

  $data = $_GET;
  if(!isset($data['tcgpId'])){
    $return['status'] = 'err';
    $return['error'] = 'Parameter missing';
  } else {
    $ch = curl_init();
    $headers = array(
      "Authorization: bearer $token"
    );
    curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products/".$data['tcgpId']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch));
    if($response->results[0]->categoryId != 1){
      $return['status'] = 'err';
      $return['error'] = 'ID does not match a Magic: The Gathering product';
    } else {
      $card = $response->results[0];
      $stmt = "SELECT categories_id, pb_code
               FROM mtg_sets
               WHERE tcgp_id = ".$card->groupId;
      $result = $conn->query($stmt);
      if($result->num_rows == 0){
        curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/groups/".$card->groupId);
        $response = json_decode(curl_exec($ch));
        $setName = $conn->real_escape_string($response->results[0]->name);
        $setCode = $response->results[0]->abbreviation;

        $stmt = "INSERT INTO categories
                 SET parent_id = 458,
                     sort_order = 0,
                     date_added = '".date("Y-m-d H:i:s")."',
                     categories_status = 1";
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        } else {
          $catId = $conn->insert_id;
        }

        $stmt = "INSERT INTO categories_description
                 SET categories_id = $catId,
                     language_id = 1,
                     categories_name = '$setName',
                     categories_description = '',
                     buy_list_active = 0,
                     buy_list_order = 0,
                     buy_list_format = ''";
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        }

        $stmt = "INSERT INTO mtg_sets
                 SET set_name = '$setName',
                     pb_code = '$setCode',
                     categories_id = $catId,
                     tcgp_id = ".$card->groupId;
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        }
      } else {
        $row = $result->fetch_array(MYSQLI_NUM);
        $catId = $row[0];
        $setCode = $row[1];
      }
      foreach($card->productConditions as $cond){
        if($cond->isFoil){
          $foil = true;
        } else {
          $normal = true;
        }
      }
      $altName = str_replace(array(" ", "/", "\\"), "-", str_replace(array(",", ".", "!", "?", "'", "(", ")", ":"), "", str_replace(array(" - ", " // "), "", $card->productName)));
      $imgName = $altName.'.jpg';
      $cardName = $conn->real_escape_string($card->productName);
      $model = substr($setCode.'-'.$altName, 0, 32);
      $stmt = "SELECT 1
               FROM products p
               LEFT JOIN products_description pd ON p.products_id = pd.products_id
               WHERE pd.products_name = '$cardName'
               AND master_categories_id = $catId";
      $result = $conn->query($stmt);
      if($result->num_rows > 0){
        $return['status'] = 'err';
        $return['error'] = 'Duplicate entry';
        exit(json_encode($return));
      } elseif($conn->error){
        $return['status'] = 'err';
        $return['error'] = $conn->error;
        exit(json_encode($return));
      }
      $stmt = "INSERT INTO products
               SET products_model = '$model',
                   products_image = 'images/mtgsingles/$setCode/$imgName',
                   products_date_added = '".date("Y-m-d H:i:s")."',
                   products_weight = 0.004,
                   products_status = 0,
                   products_qty_box_status = 1,
                   manufacturers_id = 0,
                   products_quantity_mixed = 1,
                   master_categories_id = $catId,
                   img_update = 1";
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['error'] = $conn->error;
        exit(json_encode($return));
      }
      $prodId = $conn->insert_id;

      $stmt = "INSERT INTO products_description
               SET products_id = $prodId,
                   products_name = '$cardName'";
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['error'] = $conn->error;
        exit(json_encode($return));
      }

      $stmt = "INSERT INTO products_to_categories
               SET products_id = $prodId,
                   categories_id = $catId";
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['error'] = $conn->error;
        exit(json_encode($return));
      }

      $stmt = "INSERT INTO mtg_card_link
               SET products_id = $prodId,
                   tcgp_id = ".$data['tcgpId'].",
                   is_foil = ";
      $stmt .= ($normal && $foil) ? 0 : 1 ;
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['error'] = $conn->error;
        exit(json_encode($return));
      }
      curl_setopt($ch, CURLOPT_URL, "http://www.pricebustersgames.com/pbadmin/tcglink/update_item.php?prodId=".$prodId);
      curl_exec($ch);
      if($foil && $normal){
        $cardName = $conn->real_escape_string($card->productName.' - Foil');
        $model = substr($setCode.'-'.$altName, 0, 27).'-Foil';
        $stmt = "SELECT 1
                 FROM products p
                 LEFT JOIN products_description pd ON p.products_id = pd.products_id
                 WHERE pd.products_name = '$cardName'
                 AND master_categories_id = $catId";
        $result = $conn->query($stmt);
        if($result->num_rows > 0){
          $return['status'] = 'err';
          $return['error'] = 'Duplicate entry';
          exit(json_encode($return));
        } elseif($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        }
        $stmt = "INSERT INTO products
                 SET products_model = '$model',
                     products_image = 'images/mtgsingles/$setCode/$imgName',
                     products_date_added = '".date("Y-m-d H:i:s")."',
                     products_weight = 0.004,
                     products_status = 0,
                     products_qty_box_status = 1,
                     manufacturers_id = 0,
                     products_quantity_mixed = 1,
                     master_categories_id = $catId,
                     img_update = 1";
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        }
        $prodId = $conn->insert_id;

        $stmt = "INSERT INTO products_description
                 SET products_id = $prodId,
                     products_name = '$cardName'";
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        }

        $stmt = "INSERT INTO products_to_categories
                 SET products_id = $prodId,
                     categories_id = $catId";
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        }

        $stmt = "INSERT INTO mtg_card_link
                 SET products_id = $prodId,
                     tcgp_id = ".$data['tcgpId'].",
                     is_foil = 1";
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['error'] = $conn->error;
          exit(json_encode($return));
        }
        curl_setopt($ch, CURLOPT_URL, "http://www.pricebustersgames.com/pbadmin/tcglink/update_item.php?prodId=".$prodId);
        curl_exec($ch);
      }

      if(!is_dir($_SERVER['DOCUMENT_ROOT']."/images/mtgsingles/$setCode")){
        mkdir($_SERVER['DOCUMENT_ROOT']."/images/mtgsingles/$setCode");
      }
      $image = file_get_contents($card->url);
      file_put_contents($_SERVER['DOCUMENT_ROOT']."/images/mtgsingles/$setCode/$imgName", $image);
      $return['status'] = 'ok';
    }
  }

  echo json_encode($return);
?>
