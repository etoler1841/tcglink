<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
  $start = (isset($_GET['start'])) ? $_GET['start'] : 0 ;

  $ch = $ch2 = curl_init();
  $headers = array(
    "Authorization: bearer $token"
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $stmt = "SELECT tcgp_id FROM mtg_sets ORDER BY set_name ASC LIMIT $start, 1";
  $stmt2 = $conn->prepare("SELECT 1 FROM mtg_card_link WHERE tcgp_id = ?");
  $result = $conn->query($stmt);
  if($result->num_rows == 0){
    exit("Finished");
  }
  while($row = $result->fetch_array(MYSQLI_NUM)){
    $stop = false;
    $i = $j = 0;
    while(!$stop){
      $values = 'groupId='.$row[0].'&limit=100&offset='.$i;
      curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products?$values");
      $data = json_decode(curl_exec($ch));
      if(sizeof($data->results) == 0){
        $stop = true;
      }
      foreach($data->results as $card){
        $i++;
        $stmt2->bind_param("i", $card->productId);
        $stmt2->execute();
        $stmt2->store_result();
        if($stmt2->num_rows == 0){
          curl_setopt($ch2, CURLOPT_URL, "http://www.pricebustersgames.com/pbadmin/tcglink/build_cards.php?tcgpId=".$card->productId);
          curl_exec($ch2);
          $j++;
        }
      }
    }
  }
  echo "$i cards found. $j cards built.";
?>
<script>
  $(window).load(() => {
    window.location.href = "?start=<?=$start+1?>";
  });
</script>
