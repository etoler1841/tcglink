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
  do {
    curl_setopt($ch, CURLOPT_URL, "http://api.tcgplayer.com/catalog/categories/1/groups?limit=100&offset=".($i*100));
    $response = json_decode(curl_exec($ch));
    $data = $response->results;
    foreach($data as $set){
      $sets[$set->groupId] = $set->name;
    }
    $i++;
  } while($response->results);
  asort($sets);
?>
<head>
  <title>New card - TCGLink</title>
  <style>

  </style>
</head>
<body>
  <p><input type='text' size='50' id='card-name' placeholder='Card name...' /></p>
  <p>
    <select id='set-select'>
      <option>Choose a set...</option>
      <?php foreach($sets as $id => $name){
        echo "<option value='$id'>$name</option>";
      } ?>
    </select>
    <button id='build'>Build</button>
  </p>
  <script>
    $("#build").click(() => {
      let groupId = $("#set-select").val();
      let cardName = $("#card-name").val();
      $.ajax("http://api.tcgplayer.com/catalog/products?groupId="+groupId+"&productName="+cardName, {
        headers: {
          Authorization: 'bearer <?=$token?>'
        },
        success: (data) => {
          console.log(data);
          $.get("<?=$path?>/build.php?tcgpId="+data.results[0].productId, (response) => {
            console.log(response);
            let data = JSON.parse(response);
            console.info(data);
          });
        }
      });
    });
  </script>
</body>
