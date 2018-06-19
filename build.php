<?php
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');

  if(isset($_GET['tcgpId'])){
    $tcgpId = $_GET['tcgpId'];
    $ch = curl_init();
    $headers = array(
      "Authorization: bearer $token"
    );
    curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products/".$tcgpId);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch));
    if($response->results){
      if($response->results[0]->categoryId == 1){
        $card = $response->results[0];
        $stmt = "SELECT categories_id, pb_code
                 FROM mtg_sets
                 WHERE tcgp_id = ".$card->groupId;
        $result = $conn->query($stmt);
        if($result->num_rows){
          //Category info
          $row = $result->fetch_array(MYSQLI_ASSOC);
          $catId = $row['categories_id'];
          $setCode = $row['pb_code'];

          //Determine if both foil and non-foil versions exist
          foreach($card->productConditions as $cond){
            if($cond->isFoil){
              $foil = true;
            } else {
              $normal = true;
            }
          }
          $cardName = $conn->real_escape_string($card->productName);

          //Check for existing product
          $stmt = "SELECT 1
                   FROM products p
                   LEFT JOIN products_description pd ON p.products_id = pd.products_id
                   WHERE pd.products_name = '$cardName'
                   AND p.master_categories_id = $catId";
          $result = $conn->query($stmt);
          if(!$result->num_rows){
            //Prepare variables for products tables
            $hyphenName = str_replace(array(" ", "/", "\\"), "-", str_replace(array(",", ".", "!", "?", "'", "(", ")", ":"), "", str_replace(array(" - ", " // "), "", $card->productName)));
            $imgName = $hyphenName.'.jpg';
            $model = substr($setCode.'-'.$hyphenName, 0, 32);

            //products
            $stmt = "INSERT INTO products
                     SET products_model = '$model',
                         products_image = 'mtgsingles/$setCode/$imgName',
                         products_date_added = '".date("Y-m-d H:i:s")."',
                         products_weight = 0.004,
                         products_status = 0,
                         products_qty_box_status = 1,
                         manufacturers_id = 0,
                         products_quantity_mixed = 1,
                         master_categories_id = $catId,
                         img_update = 1,
                         products_full_name = '$cardName'";
            $conn->query($stmt);
            $prodId = $conn->insert_id;
            $return['result']['prodId'] = $prodId;

            //products_description
            $stmt = "INSERT INTO products_description
                     SET products_id = $prodId,
                         products_name = '$cardName'";
            $conn->query($stmt);

            //products_to_categories
            $stmt = "INSERT INTO products_to_categories
                     SET products_id = $prodId,
                         categories_id = $catId";
            $conn->query($stmt);

            //mtg_card_link
            $stmt = "INSERT INTO mtg_card_link
                     SET products_id = $prodId,
                         tcgp_id = $tcgpId,
                         is_foil = ".((isset($normal)) ? 0 : 1);
            $conn->query($stmt);

            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, "$path/update_item.php?prodId=".$prodId);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            $a = curl_exec($ch2);

            //Save the product image
            $file = file_get_contents($card->image);
            if(!is_dir($imgPath."/mtgsingles/$setCode")){
              mkdir($imgPath."/mtgsingles/$setCode");
            }
            file_put_contents($imgPath."/mtgsingles/$setCode/$imgName", $file);

            //Create foil if needed (a.k.a. "Second verse, same as the first!")
            if(isset($foil) && isset($normal)){
              $cardName = $conn->real_escape_string($card->productName.' - Foil');
              $stmt = "SELECT 1
                       FROM products p
                       LEFT JOIN products_description pd ON p.products_id = pd.products_id
                       WHERE pd.products_name = '$cardName'
                       AND p.master_categories_id = $catId";
              $result = $conn->query($stmt);
              if(!$result->num_rows){
                //Prepare variables for products tables
                $hyphenName = str_replace(array(" ", "/", "\\"), "-", str_replace(array(",", ".", "!", "?", "'", "(", ")", ":"), "", str_replace(array(" - ", " // "), "", $card->productName)));
                $imgName = $hyphenName.'.jpg';
                $model = str_replace("--", "-", substr($setCode.'-'.$hyphenName, 0, 27).'-Foil');

                //products
                $stmt = "INSERT INTO products
                         SET products_model = '$model',
                             products_image = 'mtgsingles/$setCode/$imgName',
                             products_date_added = '".date("Y-m-d H:i:s")."',
                             products_weight = 0.004,
                             products_status = 0,
                             products_qty_box_status = 1,
                             manufacturers_id = 0,
                             products_quantity_mixed = 1,
                             master_categories_id = $catId,
                             img_update = 1,
                             products_full_name = '$cardName'";
                $conn->query($stmt);
                $prodId = $conn->insert_id;
                $return['result']['prodId_foil'] = $prodId;

                //products_description
                $stmt = "INSERT INTO products_description
                         SET products_id = $prodId,
                             products_name = '$cardName'";
                $conn->query($stmt);

                //products_to_categories
                $stmt = "INSERT INTO products_to_categories
                         SET products_id = $prodId,
                             categories_id = $catId";
                $conn->query($stmt);

                //mtg_card_link
                $stmt = "INSERT INTO mtg_card_link
                         SET products_id = $prodId,
                             tcgp_id = $tcgpId,
                             is_foil = 1";
                $conn->query($stmt);

                $ch2 = curl_init();
                curl_setopt($ch2, CURLOPT_URL, "$path/update_item.php?prodId=".$prodId);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                $a = curl_exec($ch2);
              }
            }
            $return['status'] = 'ok';
          } else {
            $return['status'] = 'err';
            $return['errors'][] = 'Duplicate product detected.';
          }
        } else {
          $return['status'] = 'err';
          $return['errors'] = 'Set has not been built.';
        }
      } else {
        $return['status'] = 'err';
        $return['errors'][] = 'ID does not match a Magic: The Gathering product.';
      }
    } else {
      $return['status'] = 'err';
      $return['errors'][] = 'Product not found.';
    }
  } else {
    $return['status'] = 'err';
    $return['errors'][] = 'Parameter tcgpId is not set.';
  }



  echo json_encode($return);
?>
