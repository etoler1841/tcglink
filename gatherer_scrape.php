<?php
  define("SITE_ROOT", '.');
  $suppressMarkup = 1;
  require(SITE_ROOT.'/includes/includes.php');

  if(isset($_GET['prodId']) && isset($_GET['tcgpId'])){
    $prodId = $_GET['prodId'];
    $tcgpId = $_GET['tcgpId'];
    $stmt = "SELECT pd.products_name, s.set_name
             FROM products p
             LEFT JOIN products_description pd ON p.products_id = pd.products_id
             LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
             WHERE p.products_id = $prodId";
    $result = $conn->query($stmt);
    if($result->num_rows){
      $row = $result->fetch_array(MYSQLI_ASSOC);
      $cardName = $row['products_name'];
      $prepCardName = "+[".str_replace(" ", "]+[". $cardName)."]";
      $setName = $row['set_name'];
      $prepSetName = "[".$setName."]";

      //Find the Multiverse ID
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_URL, "http://gatherer.wizards.com/Pages/Search/Default.aspx?name=".$cardName."&set=".$setName);
      $response = curl_exec($ch);
      preg_match("/<span class=\"cardTitle\">\s*<a.*?href=\"\.\.\/Card\/Details.aspx\?multiverseid=([0-9])+/si", $response, $match);
      $return['result']['multiverseId'] = $match[1];

      //Get data from the card's individual Gatherer page
      curl_setopt($ch, CURLOPT_URL, "http://gatherer.wizards.com/Pages/Card/Details.aspx?multiverseid=".$match[1]);
      $data = curl_exec($ch);

      //Start scraping
      preg_match("/<div class=\"label\">.*?Card Name:.*?<\/div>.*?<div class=\"value\">(.*?)<\/div>/si", $gatherer, $collectorNumber);
      preg_match("/<div.*?class=\".*?manaRow\">.*?<div class=\"value\">(.*?)<\/div>/si", $gatherer, $manaCost);
      preg_match("/<div class=\"label\">.*?Color Indicator:.*?<\/div>.*?<div class=\"value\">(.*?)<\/div>/si", $gatherer, $colorIndicator);
      preg_match("/<div class=\"label\">.*?Type:.*?<\/div>.*?<div class=\"value\">(.*?)<\/div>/si", $gatherer, $type);
      if(strpos(trim($type[1]), '  ')) $type[1] = substr(trim($type[1]), 0, strpost(trim($type[1]), '  '));
      if(strpos($type[1], 'Basic Land')) $type[1] = 'Basic Land';
      if(!$manaCost[1]) $manaCost[1] = "0";

      $info = array(
        'collectorNumber' => trim($collectorNumber[1]),
        'manaCost' => simplify_mana($manaCost[1]),
        'type' => trim($type[1])
      );
      $info['colors'] = (strpos($info['type'], 'Land') === false) ? get_colors($info['manaCost']) : 'Land';
      if($colorIndicator[1] != '') $info['colors'] = trim(color_indicator($colorIndicator[1]));

      //Insert data
      $escCardName = $conn->real_escape_string($cardName);
      $escSetName = $conn->real_escape_string($setName);
      $stmt = "INSERT INTO mtg_card_data
               SET multiverse_id = ".$match[1].",
                   card_name = '$escCardName',
                   set_name = '$escSetName',
                   type = '".$info['type']."'',
                   collector_number = '".$info['collectorNumber']."'',
                   mana_cost = '".$info['collectorNumber']."',
                   colors = '".$info['colors']."'";
      $conn->query($stmt);

      //Link cards
      $stmt = "UPDATE mtg_card_link
               SET multiverse_id = ".$match[1]."
               WHERE tcgp_id = $tcgpId";
      $conn->query($stmt);

      $return['status'] = 'ok';
    } else {
      $return['status'] = 'err';
      $return['errors'][] = 'Product not found.';
    }
  } else {
    $return['status'] = 'err';
    $return['errors'][] = 'Parameter prodId is not set.';
  }

  echo json_encode($return);

  function simplify_mana($data){
    if($data === "0"){
      $cost = "0";
    } else {
      $symbols = scrub("@<(?:.*?)alt=\"(.*?)\"@si", $data);
      $cost = '';
      $colors = array('White' => 'W',
                      'Blue' => 'U',
                      'Black' => 'B',
                      'Red' => 'R',
                      'Green' => 'G',
                      'Variable Colorless' => 'X',
                      'White or Blue' => 'W/U',
                      'Blue or Black' => 'U/B',
                      'Black or Red' => 'B/R',
                      'Red or Green' => 'R/G',
                      'Green or White' => 'G/W',
                      'White or Black' => 'W/B',
                      'Blue or Red' => 'U/R',
                      'Black or Green' => 'B/G',
                      'Red or White' => 'R/W',
                      'Green or Blue' => 'G/U',
                      'Half a White' => 'w',
                      'Half a Blue' => 'u',
                      'Half a Black' => 'b',
                      'Half a Red' => 'r',
                      'Half a Green' => 'g',
                      'Two or White' => '2/W',
                      'Two or Blue' => '2/U',
                      'Two or Black' => '2/B',
                      'Two or Red' => '2/R',
                      'Two or Green' => '2/G',
                      'Phyrexian White' => '!W',
                      'Phyrexian Blue' => '!U',
                      'Phyrexian Black' => '!B',
                      'Phyrexian Red' => '!R',
                      'Phyrexian Green' => '!G'
      );
      foreach($symbols as $symbol){
        if(is_numeric($symbol)){
          $cost .= $symbol;
        } else {
          $cost .= $colors[$symbol];
        }
      }
    }
    return $cost;
  }

  function get_colors($cost){
    $stripped = strtoupper(str_replace(array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'X', '/', '!'), '', $cost));
    if($stripped == ''){
      $card_colors = 'Colorless';
    } else {
      $colors = array('W', 'U', 'B', 'R', 'G');
      $card_colors = '';
      foreach($colors as $color){
        if(strpos($stripped, $color) === 0 || strpos($stripped, $color)){
          $card_colors .= $color;
        }
      }
    }
    return $card_colors;
  }

  function color_indicator($indicator){
    return str_replace(
      ', ', '', str_replace(
        'White', 'W', str_replace(
          'Blue', 'U', str_replace(
            'Black', 'B', str_replace(
              'Red', 'R', str_replace(
                'Green', 'G', trim($indicator)
              )
            )
          )
        )
      )
    );
  }
?>
