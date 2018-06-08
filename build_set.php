<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');

  $tcgpId = $_GET['tcgpId'];
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
          $.get("./gatherer_scrape.php?prodId="+data.prodId, (response) => {
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
