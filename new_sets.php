<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');

  $ch = curl_init();
  $headers = array(
    "Authorization: bearer $token"
  );
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $i = 0;
  $stmt = $conn->prepare("SELECT categories_id
                          FROM mtg_sets
                          WHERE tcgp_id = ?");
  do {
    curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/categories/1/groups?limit=100&offset=".$i*100);
    $response = json_decode(curl_exec($ch));
    foreach($response->results as $set){
      $stmt->bind_param("i", $set->groupId);
      $stmt->execute();
      $stmt->store_result();
      if($stmt->num_rows){
        //echo "<p>".$set->name." [".$set->abbreviation."] found</p>";
      } else {
        echo "<p>".$set->name." [".$set->abbreviation."] not found. <button id='".$set->groupId."' class='build'>Build</button></p>";
      }
    }
    $i++;
  } while(sizeof($response->results));
?>
<script>
  $(".build").click((e) => {
    let tcgpId = $(e.currentTarget).attr("id");
    window.location.href = "./build_set.php?tcgpId="+tcgpId;
  });
</script>
