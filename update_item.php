<?php
  define("SITE_ROOT", ".");
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');
  $prodId = $_GET['prodId'];

  $stmt = "SELECT tcgp_id, is_foil FROM mtg_card_link WHERE products_id = $prodId";
  $result = $conn->query($stmt);
  if($result->num_rows == 0){
    $stmt = "INSERT INTO mtg_update_errors
    SET products_id = $prodId,
        error = 'Product missing from link table'";
    $conn->query($stmt);
    $return['errors'][] = $prodId.' error: Product missing from link table';
    exit();
  }
  $row = $result->fetch_array(MYSQLI_NUM);
  $tcgpID = $row[0];
  $cond = ($row[1] == 1) ? 'Foil' : 'Normal' ;

  $ch = curl_init();
  $headers = array(
    "Authorization: bearer $token"
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

  //check ID
  // $stmt = "SELECT pd.products_name, s.tcgp_id, p.products_quantity
  //          FROM products p
  //          LEFT JOIN products_description pd ON p.products_id = pd.products_id
  //          LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
  //          WHERE p.products_id = $prodId";
  // $row = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
  // curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products/$tcgpID");
  // $data = json_decode(curl_exec($ch));
  // if($data->results[0]->productName != $row[0] || $data->results[0]->groupId != $row[1]){
  //   $values = "groupId=".$row[1]."&productName=".str_replace(" ", "%20", htmlspecialchars(str_replace(array(" - Foil", "!", "?"), "", $row[0]), ENT_QUOTES));
  //   curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products?$values");
  //   $data2 = json_decode(curl_exec($ch));
  //   if(!$data2->results){
  //     if($row[2] == 0){
  //       $stmt = "DELETE FROM products WHERE products_id = $prodId";
  //       $conn->query($stmt);
  //       $stmt = "DELETE FROM products_description WHERE products_id = $prodId";
  //       $conn->query($stmt);
  //       $stmt = "DELETE FROM products_to_categories WHERE products_id = $prodId";
  //       $conn->query($stmt);
  //       $stmt = "DELETE FROM products_description WHERE products_id = $prodId";
  //       $conn->query($stmt);
  //       $stmt = "DELETE FROM mtg_card_link WHERE products_id = $prodId";
  //       $conn->query($stmt);
  //       $stmt = "DELETE FROM mtg_update_errors WHERE products_id = $prodId";
  //       $conn->query($stmt);
  //       exit('<p>'.$prodId.' does not exist; deleted from database.</p>');
  //     }
  //     exit('<p>'.$prodId.' not found: '.$values.'</p>');
  //   }
  //   if($data2->results[0]->productId != $tcgpID){
  //     $stmt = "UPDATE mtg_card_link
  //              SET tcgp_id = ".$data2->results[0]->productId."
  //              WHERE products_id = $prodId";
  //     $conn->query($stmt);
  //     $tcgpID = $data2->results[0]->productId;
  //     echo '<p>'.$prodId.' TCGPlayer ID changed</p>';
  //   }
  // }

  //get price and update
  curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/pricing/product/$tcgpID");
  $data = json_decode(curl_exec($ch));
  if(sizeof($data->errors) > 0){
    foreach($data->errors as $err){
      $error = $conn->real_escape_string($err);
      $stmt = "INSERT INTO mtg_update_errors
               SET products_id = $prodId,
                   error = '$error'";
      $conn->query($stmt);
      $return['errors'][] = $err;
    }
  }
  if(isset($data->results)){
    $error = 1;
    foreach($data->results as $price){
      if($price->subTypeName == $cond){
        // if($price->midPrice == '' && $price->marketPrice == ''){
        //   $newCond = ($cond == 'Foil') ? 0 : 1 ;
        //   $stmt = "UPDATE mtg_card_link
        //   SET is_foil = $newCond
        //   WHERE products_id = $prodId";
        //   $conn->query($stmt);
        //   echo $stmt;
        //   if($conn->error){
        //     $err = $conn->real_escape_string($conn->error);
        //     $query = $conn->real_escape_string($stmt);
        //     $stmt = "INSERT INTO mtg_update_errors
        //              SET products_id = $prodId,
        //                  error = '$err',
        //                  query = '$query'";
        //     $conn->query($stmt);
        //     echo '<p>'.$prodId.' error: '.$conn->error.'</p>';
        //   } else {
        //     $newCondText = ($newCond == 0) ? 'Normal' : 'Foil' ;
        //     echo '<p>'.$prodId.' condition changed to '.$newCondText.'.</p>';
        //   }
        //   exit();
        // }
        if($price->midPrice > $price->marketPrice*1.33 || ($price->marketPrice > 150 && $price->midPrice && $price->marketPrice < $price->midPrice)){
          $error = 0;
          if($price->midPrice < .22 && $cond == 'Normal'){
            $newPrice = .2200;
          } elseif($price->midPrice < .5 && $cond == 'Foil'){
            $newPrice = .5000;
          } else {
            $newPrice = number_format($price->midPrice, 4, '.', '');
          }
        } else {
          if($price->marketPrice){
            $error = 0;
            if($price->marketPrice < .22 && $cond == 'Normal'){
              $newPrice = .2200;
            } elseif($price->marketPrice < .5 && $cond == 'Foil'){
              $newPrice = .5000;
            } else {
              $newPrice = number_format($price->marketPrice, 4, '.', '');
            }
          }
        }
        $stmt = "UPDATE products
                 SET products_price = $newPrice,
                     foil_last_update = '".date("Y-m-d H:i:s")."'
                 WHERE products_id = $prodId";
        $conn->query($stmt);
        if($conn->error){
          $err = $conn->real_escape_string($conn->error);
          $query = $conn->real_escape_string($stmt);
          $stmt = "INSERT INTO mtg_update_errors
                   SET products_id = $prodId,
                       error = '$err',
                       query = '$query'";
          $conn->query($stmt);
          $return['errors'][] = $conn->error;
        } else {
          $stmt = "DELETE FROM mtg_update_errors WHERE products_id = $prodId";
          $conn->query($stmt);
          $return['products_id'] = $prodId;
          $return['new_price'] = $newPrice;
        }
        if($error > 0){
          $stmt = "INSERT INTO mtg_update_errors
                   SET products_id = $prodId,
                       error = 'Prices not found for specified condition ($cond)'";
          $conn->query($stmt);
          $return['errors'][] = $prodId.' error: Prices not found for specified condition ('.$cond.')';
        }
      }
    }
  } else {
    if(!$data->errors){
      $stmt = "INSERT INTO mtg_update_errors
               SET products_id = $prodId,
                   error = 'No prices found'";
      $conn->query($stmt);
      $return['errors'][] = $prodId.' error: No prices found';
    }
  }

  echo json_encode($return);
?>
