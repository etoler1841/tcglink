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
  // $page = 1;
  // do {
  //   $values = "offset=".(($page*100)-100)."&limit=100";
  //   curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/categories/1/groups?$values");
  //   $data = json_decode(curl_exec($ch));
  //   $count = 0;
  //   foreach($data->results as $group){
  //     $count++;
  //     echo "<option value='".$group->groupId."'";
  //     if(isset($_GET['group']) && $group->groupId == $_GET['group']) echo " selected";
  //     echo ">".$group->name."</option>";
  //   }
  //   $page++;
  // } while ($count == 100);
  $i = 0;
  do {
    $values = "offset=".($i*100)."&limit=100";
    curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/categories/1/groups?".$values);
    $response = json_decode(curl_exec($ch));
    if($response->results){
      $groups = $response->results;
      foreach($groups as $group){
        $sets[$group->groupId] = $group->name;
      }
    }
    $i++;
  } while($response->results);
  asort($sets);
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
      <?php
        if(isset($_GET['group'])){
          $values = "groupId=".$_GET['group']."&offset=$start&limit=100&sortOrder=productName";
          curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products?$values");
          $data = json_decode(curl_exec($ch));
          foreach($data->results as $card){
            $productIds[] = $card->productId;
            $products[$card->productId] = $card->productName;
          }
          $ids = implode(',', $productIds);
          curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/pricing/product/$ids");
          $data = json_decode(curl_exec($ch));
          foreach($data->results as $card){
            echo "<tr>
              <td>".$card->productId."</td>
              <td>".$products[$card->productId]."</td>
              <td>".$card->subTypeName."</td>
              <td>$".$card->marketPrice."</td>
              <td>$".$card->midPrice."</td>
            </tr>";
          }
        }
      ?>
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
