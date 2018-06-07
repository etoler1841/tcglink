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
      $newName = $data['prodName']." (".$data['val'].")";
      $stmt = "UPDATE products_description
               SET products_name = '$newName'
               WHERE products_id = ".$data['prodId'];
      $conn->query($stmt);
      if($conn->error){
        $return['status'] = 'err';
        $return['errors'][] = $conn->error;
      } else {
        $stmt = "SELECT cd.multiverse_id
                 FROM mtg_card_data cd
                 LEFT JOIN mtg_sets s ON cd.set_name = s.set_name
                 WHERE card_name = '".$data['prodName']."'
                 AND s.categories_id IN (
                   SELECT master_categories_id FROM products WHERE products_id = ".$data['prodId']."
                 )";
        $row = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
        $multiverseId = $row[0];
        $stmt = "UPDATE mtg_card_link
                 SET multiverse_id = $multiverseId
                 WHERE products_id = ".$data['prodId'];
        $conn->query($stmt);
        if($conn->error){
          $return['status'] = 'err';
          $return['errors'][] = $conn->error;
        } else {
          $return['status'] = 'ok';
        }
      }
      break;
    default:
      $return['status'] = 'err';
      $return['errors'][] = 'Action not recognized.';
  }

  echo json_encode($return);
?>
