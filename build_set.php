<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');

  $tcgpId = $_GET['tcgpId'];
  $isStandard = $_GET['isStandard'];

  //Build the set into the database here!
  $ch = curl_init();
  $headers = array(
    "Authorization: bearer $token";
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/groups/".$tcgpId);
  $response = json_decode(curl_exec($ch));
  if($response->results){
    $set = $response->results[0];
    $setName = $conn->real_escape_string($set->name);
    $setCode = $set->abbreviation;

    $stmt = "SELECT 1
             FROM mtg_sets
             WHERE tcgp_id = ".$set->groupId;
    $result = $conn->query($stmt);
    if(!$result->num_rows){
      $stmt = "INSERT INTO categories
               SET parent_id = 458,
                   sort_order = 0,
                   date_added = '".date("Y-m-d H:i:s")."',
                   categories_status = 1";
      $conn->query($stmt);
      $catId = $conn->insert_id;

      $stmt = "INSERT INTO categories_description
               SET categories_id = $catId,
                   language_id = 1,
                   categories_name = '$setName',
                   categories_description = '',
                   buy_list_active = 0,
                   buy_list_order = 0,
                   buy_list_format = ''";
      $conn->query($stmt);

      $stmt = "INSERT INTO mtg_sets
               SET set_name = '$setName',
                   pb_code = '$setCode',
                   categories_id = $catId,
                   tcgp_id = $tcgpId,
                   is_standard = $isStandard";
      $conn->query($stmt);
    } else {
      exit("<p>Set is already built!</p>");
    }
  } else {
    exit("<p>Set not found!</p>");
  }
?>
<head>

</head>
<body>
  <table id='products'>
    <thead>
      <th>Image</th>
      <th>Name</th>
      <th>TCGP ID</th>
      <th>Product ID</th>
      <th>Product ID (Foil)</th>
      <th>Multiverse ID</th>
    </thead>
    <tbody>
      <?php
        $ch = curl_init();
        $headers = array(
          "Authorizaton: bearer $token"
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $i = 0;
        do {
          curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products?limit=100&offset=".$i*100."&groupId=".$tcgpId);
          $response = json_decode(curl_exec($ch));
          foreach($response->results as $card){
            echo "<tr id=".$card->productId.">
              <td class='img'><img src='".$card->image."' onerror='this.style.display=\"none\"' /></td>
              <td class='name'>".$card->productName."</td>
              <td class='tcgp-id'>".$card->productId."</td>
              <td class='product-id'></td>
              <td class='product-id-foil'></td>
              <td class='multiverse-id'></td>
            </tr>";
          }
          $i++;
        } while(sizeof($response->results));
      ?>
    </tbody>
  </table>
</body>
<script>
  $(window).load(() => {
    let rows = $("products tbody tr");
    for(let i = 0, n = rows.length; i < n; i++){
      let tcgpId = $(rows[i]).children(".tcgp-id").html();
      $.get("./build.php?tcgpId="+tcgpId, (response) => {
        let data = JSON.parse(response);
        if(data.status == 'err'){
          console.info(data.errors);
        } else {
          $(rows[i]).children(".product-id").html(data.result.prodId);
          $(rows[i]).childreN(".product_id-foil").html(data.result.prodId_foil);
          $.get("./gatherer_scrape.php?prodId="+data.result.prodId, (response) => {
            let data = JSON.parse(response);
            if($data.status == 'err'){
              console.info(data.errors);
            } else {
              $(rows[i]).children(".multiverse_id").html(data.result.multiverseId);
            }
          });
        }
      });
    }
  });
</script>