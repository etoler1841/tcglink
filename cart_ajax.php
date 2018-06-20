<?php
  header("Content-type: application/json");
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');
  $data = json_decode(file_get_contents("php://input"), true);

  switch($data['action']){
    case 'add':
      $stmt = $conn->prepare("SELECT pd.products_name, s.set_name, p.products_price
                              FROM products p
                              LEFT JOIN products_description pd ON p.products_id = pd.products_id
                              LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
                              WHERE p.products_id = ?");
      $stmt->bind_param("i", $data['sku']);
      $stmt->execute();
      $stmt->bind_result($name, $set, $price);
      $stmt->store_result();
      if($stmt->num_rows){
        $stmt->fetch();
        $return['status'] = 'ok';
        $return['result'] = array(
          'name' => $name,
          'set' => $set,
          'price' => number_format($price, 2)
        );
      } else {
        $return['status'] = 'err';
        $return['errors'][] = 'Product not found.';
      }
      break;
    default:
      $return['status'] = 'err';
      $return['errors'][] = 'Action not recognized.';
  }

  echo json_encode($return);
?>
