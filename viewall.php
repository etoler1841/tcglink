<?php
  define("SITE_ROOT", ".");
  require(SITE_ROOT.'/includes/includes.php');
  $start = (isset($_GET['page'])) ? ($_GET['page']*100)-100 : 0 ;

  $ch = curl_init();
  $headers = array(
    "Authorization: bearer $token"
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $i = 0;
  do {
    $values = "offset=".($i*100)."&limit=100";
    curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/categories/1/groups?".$values);
    $response = json_decode(curl_exec($ch));
    foreach($response->results as $set){
      $sets[$set->groupId] = $set->name;
    }
    $i++;
  } while($response->results);
  asort($sets);

  if(isset($_GET['group'])){
    $i = 0;
    do {
      $values = "groupId=".$_GET['group']."&offset=".($i*100)."&limit=100";
      curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products?".$values);
      $response = json_decode(curl_exec($ch));
      foreach($response->results as $card){
        $cards[$card->productId] = array(
          'name' => $card->productName
        );
        $ids[] = $card->productId;
      }
      $i++;
    } while($response->results);
    asort($cards);

    if(isset($cards)){
      $i = 0;
      while($i < sizeof($ids)) {
        for($j = 0; $j < 100; $i++, $j++){
          if($i === sizeof($ids)){
            break;
          }
          $search[] = $ids[$i];
        }
        curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/pricing/product/".implode(",", $search));
        $response = json_decode(curl_exec($ch));
        foreach($response->results as $data){
          $cards[$data->productId]['prices'][$data->subTypeName] = array(
            'market' => number_format($data->marketPrice, 2),
            'mid' => number_format($data->midPrice, 2)
          );
        }
      }
    }
  }
?>
<head>
  <style>
    table, tr, td, th {
      border-collapse: collapse;
      border: solid black 1px;
    }

    td, th {
      padding: 5px;
    }
  </style>
</head>
<body>
  <form action="" method="get">
    <select name='group'>
      <option value=''>Choose....</option>
      <?php foreach($sets as $id => $name){
        echo "<option value='$id'>$name</option>";
      } ?>
    </select>
    <input type="submit" value="submit"/>
  </form>
  <?php if(!isset($cards)) exit(); ?>
  <table id="cards">
    <thead>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>subTypeName</th>
        <th>Market Price</th>
        <th>Mid Price</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($cards as $id => $data){
      if(isset($data['prices']['Normal']) && ($data['prices']['Normal']['market'] != 0 || $data['prices']['Normal']['mid'] != 0)){
        echo "<tr>
        <td>".$id."</td>
        <td>".$data['name']."</td>
        <td>Normal</td>
        <td>$".$data['prices']['Normal']['market']."</td>
        <td>$".$data['prices']['Normal']['mid']."</td>
        </tr>";
      }
      if(isset($data['prices']['Foil']) && ($data['prices']['Foil']['market'] != 0 || $data['prices']['Foil']['mid'] != 0)){
        echo "<tr>
        <td>".$id."</td>
        <td>".$data['name']."</td>
        <td>Foil</td>
        <td>$".$data['prices']['Foil']['market']."</td>
        <td>$".$data['prices']['Foil']['mid']."</td>
        </tr>";
      }
    } ?>
    </tbody>
  </table>
</body>
