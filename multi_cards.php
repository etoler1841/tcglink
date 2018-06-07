<?php
  define("SITE_ROOT", '.');
  include(SITE_ROOT.'/includes/includes.php');
  $fileRoot = '../..';

  if(isset($_POST['upload'])){
    foreach($_FILES as $prodId => $file){
      $stmt = "SELECT products_image
               FROM products
               WHERE products_id = $prodId";
      $row = $conn->query($stmt)->fetch_array(MYSQLI_NUM);
      $file = $file['tmp_name'];
      $target = $fileRoot.'/images/'.$row[0];
      if(move_uploaded_file($file, $target)){
        $results[] = '<p>'.$prodId.': image was successfully uploaded</p>';
      }
    }
  }

  $sql = "SELECT p.products_id, p.products_image, pd.products_name, cl.tcgp_id, s.pb_code
          FROM mtg_card_link cl
          LEFT JOIN products p ON cl.products_id = p.products_id
          LEFT JOIN products_description pd ON p.products_id = pd.products_id
          LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
          WHERE cl.products_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt2 = "SELECT products_id FROM mtg_update";
  $result = $conn->query($stmt2);
  while($row = $result->fetch_array(MYSQLI_NUM)){
    $stmt->bind_param("i", $row[0]);
    $stmt->execute();
    $stmt->bind_result($prodId, $img, $prodName, $tcgpId, $setCode);
    $stmt->store_result();
    $stmt->fetch();
    $cards[] = array(
      'prodId' => $prodId,
      'img' => $img,
      'prodName' => (strpos($prodName, '(') ? substr($prodName, 0, strpos($prodName, ' (')) : (strpos($prodName, '[') ? substr($prodName, 0, strpos($prodName, ' [')) : $prodName)),
      'desc' => (strpos($prodName, '(') ? substr($prodName, strpos($prodName, '(')+1, strpos($prodName, ')')-strpos($prodName, '(')-1) : (strpos($prodName, '[') ? substr($prodName, strpos($prodName, '[')+1, strpos($prodName, ']')-strpos($prodName, '[')-1) : '-')),
      'tcgpId' => $tcgpId,
      'setCode' => $setCode
    );
  }
?>
<head>
  <title>PBAdmin - Alter Multi-art Cards</title>
  <style>
    body {
      font-family: Tahoma, sans-serif;
      font-size: .95em;
    }

    table {
      display: inline-table;
      margin: 20px;
      border-collapse: collapse;
      font-size: .95em;
    }

    table, thead {
      border: solid 1px black;
    }

    tr, td, th {
      border: 0;
    }

    td, th {
      text-align: center;
      padding: 5px;
    }

    #cardData img {
      height: 35px;
    }

    #cardData table {
      min-width: 850px;
    }

    #cardData tr {
      border-bottom: solid 1px #666;
    }

    #container {
      display: flex;
      flex-flow: column nowrap;
      justify-content: flex-start;
      align-items: center;
      align-content: center;
    }

    #img-div {
      position: fixed;
      top: 0;
      right: 0;
      z-index: 1;
      width: 216px;
      height: 300px;
    }

    #img-div img {
      height: 100%;
    }

    .click-edit {
      border-bottom: dotted 1px black;
    }

    tr.foil {
      background-color: #ddd;
    }

    tr.normal {
      background-color: white;
    }

    .foil.showcase {
      background-color: #6cc;
    }

    .normal.showcase {
      background-color: #6ee;
    }

    .save-btn {
      position: fixed;
      bottom: 15px;
      right: 15px;
      width: 125px;
      height: 50px;
      background-color: #cfc;
      border: solid 1px #9f9;
      border-radius: 2px;
    }

    .save-btn:hover {
      background-color: #9f9;
    }
  </style>
</head>
<body>
  <div id='img-div'></div>
  <form action='' method='post' enctype='multipart/form-data'>
    <table>
      <thead>
        <th>Image</th>
        <th>PB ID</th>
        <th>TCG ID</th>
        <th>Name</th>
        <th>Desc.</th>
        <th>Upload</th>
      </thead>
      <tbody>
        <?php
        foreach($cards as $card){
          echo "<tr>
            <td><img src='../../".$card['img']."' onerror='this.style.display=\"none\"' /></td>
            <td class='prodId'>".$card['prodId']."</td>
            <td class='tcgpId'><span class='click-edit'>".$card['tcgpId']."</span></td>
            <td class='prodName'>".$card['prodName']." [".$card['setCode']."]</td>
            <td class='desc'><span class='click-edit'>".$card['desc']."</span></td>
            <td><input type='file' name='".$card['prodId']."' accept='.jpg' /></td>
          </tr>";
        }
        ?>
      </tbody>
    </table>
    <input type='submit' name='upload' value='Upload image(s)' class='save-btn' />
  </form>
  <?php
    if(isset($results)){
      foreach($results as $result){
        echo $result;
      }
    }
  ?>
  <script>
    $("body").on("click", ".click-edit", (e) => {
      let val = $(e.currentTarget).html();
      let cell = $(e.currentTarget).parent();
      $(cell).html("<input type='text' class='click-edit-field val_"+val+"' value='"+val+"' size='6'/>");
      $(".click-edit-field").select();
    });

    $("body").on("blur", ".click-edit-field", (e) => {
      let prop = $(e.currentTarget).parent().attr("class");
      let val = $(e.currentTarget).val();
      let prodId = $(e.currentTarget).parent().siblings(".prodId").html();
      let prodName = $(e.currentTarget).parent().siblings(".prodName").html();
      if(!val || val === '-'){
        let old = $(".click-edit-field").attr("class").replace("click-edit-field val_", "");
        $(".click-edit-field").parent().html("<span class='click-edit'>"+old+"</span>");
        return;
      }
      let params = {
        action: 'update',
        prop: prop,
        val: val,
        prodId: prodId,
        prodName: prodName,
      };
      $.post("./multi_ajax.php", JSON.stringify(params), (response) => {
        let data = JSON.parse(response);
        if(!data.errors){
          $(e.currentTarget).parent().html("<span class='click-edit'>"+val+"</span>");
        } else {
          console.info(data);
          let old = $(".click-edit-field").attr("class").replace("click-edit-field val_", "");
          $(".click-edit-field").parent().html("<span class='click-edit'>"+old+"</span>");
        }
      });
    });
  </script>
</body>
