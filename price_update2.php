<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
  $start = (isset($_GET['start'])) ? $_GET['start'] : 0;
  $pageSize = (isset($_GET['pageSize'])) ? $_GET['pageSize'] : 100;
  $count = 0;
  $skip = true;

  $stmt = "SELECT cl.products_id
           FROM mtg_card_link cl
           LEFT JOIN products p ON cl.products_id = p.products_id
           WHERE p.foil_last_update < '2018-05-23 14:00:00'
           ORDER BY cl.products_id ASC
           LIMIT $pageSize";
  $result = $conn->query($stmt);
  if($result->num_rows == 0){
    exit("Finished");
  }
  $ch = curl_init();
  while($row = $result->fetch_array(MYSQLI_NUM)){
    curl_setopt($ch, CURLOPT_URL, "http://www.pricebustersgames.com/pbadmin/tcglink/update_item.php?prodId=".$row[0]);
    curl_exec($ch);
  }
?>
<script>
  $(window).load(() => {
    window.location.href = "?start=<?=$start+$pageSize?>&pageSize=<?=$pageSize?>";
  });
</script>
