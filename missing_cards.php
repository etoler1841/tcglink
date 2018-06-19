<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
  if(!isset($_GET['catId'])){
    exit("Missing parameter.");
  }
  $page = (isset($_GET['page'])) ? $_GET['page'] : 0;
  $limit = (isset($_GET['limit'])) ? $_GET['limit'] : 20;

  $ch = $ch2 = curl_init();
  $headers = array(
    "Authorization: bearer $token"
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $stmt = "SELECT tcgp_id
           FROM mtg_sets
           WHERE categories_id = ".$_GET['catId']."
           ORDER BY set_name ASC";
  $stmt2 = $conn->prepare("SELECT 1
                           FROM mtg_card_link cl
                           LEFT JOIN products_description pd ON cl.products_id = pd.products_id
                           WHERE cl.tcgp_id = ?
                           AND pd.products_name NOT LIKE '%- Foil'");
  $result = $conn->query($stmt);
  if($result->num_rows == 0){
    exit("Finished");
  }
  while($row = $result->fetch_array(MYSQLI_NUM)){
    $i = $j = $k = 0;
    do {
      $values = 'groupId='.$row[0].'&limit='.$limit.'&offset='.($i+($page*$limit));
      curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products?$values");
      $data = json_decode(curl_exec($ch));
      foreach($data->results as $card){
        $i++;
        $stmt2->bind_param("i", $card->productId);
        $stmt2->store_result();
        $stmt2->execute();
        $stmt2->store_result();
        if($stmt2->num_rows == 0){
          curl_setopt($ch2, CURLOPT_URL, $path."/build.php?tcgpId=".$card->productId);
          $response2 = json_decode(curl_exec($ch2));
          curl_setopt($ch2, CURLOPT_URL, $path."/gatherer_scrape.php?prodId=".$response2->result->prodId."&tcgpId=".$card->productId);
          curl_exec($ch2);
          if(isset($response2->result->prodId_foil)){
            curl_setopt($ch2, CURLOPT_URL, $path."/gatherer_scrape.php?prodId=".$response2->result->prodId_foil."&tcgpId=".$card->productId);
            curl_exec($ch2);
            $k++;
          }
          $j++;
        }
      }
    } while ($i < $limit);
  }
  echo "$i cards found. $j cards built, plus $k foils.";
?>
<script>
  $(window).load(() => {
    let url = "?page=<?=$page+1?>&limit=<?=$limit?>";
    if(<?php echo isset($_GET['catId']) ? 'true' : 'false' ;?>) url += "&catId=<?php if(isset($_GET['catId'])) echo $_GET['catId'];?>";
    window.location.href = (url);
  });
</script>
