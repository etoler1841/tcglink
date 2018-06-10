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
      }
      $i++;
    } while($response->results);

    if(isset($cards)){
      foreach($cards as $id => $data){
        curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/pricing/product/".$id);
        $response = json_decode(curl_exec($ch));
        foreach($response->results as $price){
          $cards[$id]['prices'][$price->subTypeName] = array(
            'market' => $price->marketPrice,
            'mid' => $price->midPrice
          );
        }
      }
    }
    asort($cards);
    // echo "<pre>";
    // print_r($cards);
    // echo "</pre>";
    // exit();
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
      foreach($data['prices'] as $name => $prices){
        echo "<tr>
        <td>".$id."</td>
        <td>".$data['name']."</td>
        <td>".$name."</td>
        <td>$".$prices['market']."</td>
        <td>$".$prices['mid']."</td>
        </tr>";
      }
    } ?>
    </tbody>
  </table>
  <?php if($start > 0){
    ?>
      <button id="back">Back</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <?php
  } ?><button id="next">Next</button>
  <script>
    $("#back").click(() => {
      window.location.href = "?group=<?=$_GET['group']?>&page=<?=(($start+100)/100)-1?>";
    });

    $("#next").click(() => {
      window.location.href = "?group=<?=$_GET['group']?>&page=<?=(($start+100)/100)+1?>";
    });
  </script>
</body>
