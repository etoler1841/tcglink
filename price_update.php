<?php
  define("SITE_ROOT", ".");
  require(SITE_ROOT.'/includes/includes.php');
  $start = (isset($_GET['start'])) ? $_GET['start'] : 0 ;

  $stmt1 = "SELECT products_id, tcgp_id
            FROM mtg_card_link
            WHERE tcgp_id IS NOT NULL
            ORDER BY products_id ASC
            LIMIT $start, 100";
  $result = $conn->query($stmt1);
  if($result->num_rows === 0){
    exit("Finished");
  }

  $stmt2 = $conn->prepare("UPDATE products
                           SET products_price = ?,
                               foil_last_update = '".date("Y-m-d H:i:s")."'
                           WHERE products_id = ?");

  while($row = $result->fetch_array(MYSQLI_ASSOC)){
    $ch = curl_init();
    $headers = array(
      "Authorization: bearer $token"
    );
    curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/pricing/product/".$row['tcgp_id']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = json_decode(curl_exec($ch));

    foreach($data->results as $subType){
      if($subType->subTypeName == 'Normal'){
        echo '<p>'.$row['products_id'].' updated: ';
        if($subType->midPrice > $subType->marketPrice*1.5){
          $price = $subType->midPrice;
          echo '(mid)';
        } else {
          $price = $subType->marketPrice;
          echo '(market)';
        }
        if($price == 0) continue;
        if($price < .22) $price = .22;
        echo ' $'.$price;
        $stmt2->bind_param("di", number_format($price, 4), $row['products_id']);
        $stmt2->execute();
        if($conn->error){
          echo '<br />'.$conn->error;
        }
        echo '</p>';
      }
    }
  }
?>
<script>
  $(window).load(() => {
    window.location.href = "?start=<?=$start+100?>";
  });
</script>
