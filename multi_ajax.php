<?php
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');

  $data = json_decode(file_get_contents("php://input"), true);
  switch($data['prop']){
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
      }
      break;
    case 'desc':
      $baseName = substr($data['prodName'], 0, strpos($data['prodName'], ' ['));
      $cardName = $baseName." (".$data['val'].")";
      $altName = str_replace(array(" ", "/", "\\"), "-", str_replace(array(",", ".", "!", "?", "'", "(", ")", ":"), "", str_replace(array(" - ", " // "), "", $cardName)));
      $imgName = $altName.'.jpg';
      $newBaseName = $conn->real_escape_string($baseName);
      $newName = $conn->real_escape_string($cardName);
      $setCode = substr($data['prodName'], strpos($data['prodName'], "[")+1, strpos($data['prodName'], "]")-strpos($data['prodName'], "[")-1);
      $model = substr($setCode.'-'.$altName, 0, 32);
      $stmt = "UPDATE products_description
               SET products_name = '$newName'
               WHERE products_id = ".$data['prodId'];
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
        $return['query'][] = $stmt;
      } else {
        $stmt = "UPDATE products
                 SET products_model = '$model',
                     products_image = 'mtgsingles/$setCode/$imgName'
                 WHERE products_id = ".$data['prodId'];
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['errors'][] = $conn->error;
          $return['query'][] = $stmt;
        } else {
          $stmt = "SELECT cd.multiverse_id
                   FROM mtg_card_data cd
                   LEFT JOIN mtg_sets s ON cd.set_name = s.set_name
                   WHERE cd.card_name = '".$newBaseName."'
                   AND s.categories_id IN (
                     SELECT master_categories_id FROM products WHERE products_id = ".$data['prodId']."
                   )";
          $row = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
          $return['query'][] = $stmt;
          if($conn->error){
            $return['status'] = 'err';
            $return['errors'][] = $conn->error;
          } else {
            $multiverseId = $row[0];
            $stmt = "UPDATE mtg_card_link
                     SET multiverse_id = $multiverseId
                     WHERE products_id = ".$data['prodId'];
            $conn->query($stmt);
            if($conn->error){
              $return['status'] = 'err';
              $return['errors'][] = $conn->error;
              $return['query'][] = $stmt;
            } else {
              $return['status'] = 'ok';
            }
          }
        }
      }
      break;
    default:
      $return['status'] = 'err';
      $return['errors'][] = 'Action not recognized.';
  }

  echo json_encode($return);
?>
