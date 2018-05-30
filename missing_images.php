<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
  $start = (isset($_GET['start'])) ? $_GET['start'] : 0 ;
  $pageSize = (isset($_GET['pageSize'])) ? $_GET['pageSize'] : 10 ;

  $stmt = "SELECT cl.products_id, cl.tcgp_id, p.products_image
           FROM mtg_card_link cl
           LEFT JOIN products p ON cl.products_id = p.products_id
           ORDER BY cl.products_id ASC
           LIMIT $start, $pageSize";
  $stmt2 = $conn->prepare("SELECT multiverse_id FROM mtg_card_link WHERE products_id = ?");
  $ch = curl_init();
  $headers = array(
    "Authorization: bearer $token"
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $result = $conn->query($stmt);
  if($result->num_rows == 0){
    exit("Finished");
  }
  while($row = $result->fetch_array(MYSQLI_ASSOC)){
    if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/images/'.$row['products_image']) || filesize($_SERVER['DOCUMENT_ROOT'].'/images/'.$row['products_image']) < 2000){
      $stmt2->bind_param("i", $row['products_id']);
      $stmt2->execute();
      $stmt2->bind_result($multiverse);
      $stmt2->store_result();
      $stmt2->fetch();
      if($multiverse){
        $image = file_get_contents("http://gatherer.wizards.com/Handlers/Image.ashx?multiverseid=".$multiverse."&type=card");
      } else {
        curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/products/".$row['tcgp_id']);
        $data = json_decode(curl_exec($ch));
        $image = file_get_contents($data->results[0]->image);
      }
      file_put_contents($_SERVER['DOCUMENT_ROOT'].'/images/'.$row['products_image'], $image);
      echo "<p>".$row['products_image']."</p>";
      echo "<p><img src='../../images/".$row['products_image']."' /></p>";
    }
  }
?>
<script>
  $(window).load(() => {
    window.location.href = "?start=<?=$start+$pageSize?>";
  });
</script>
