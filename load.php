<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');

  $priceRanges = array(10, 4, 1, 0);
  $stmt = $conn->prepare("SELECT SUM(p.products_price*p.products_quantity) AS total, SUM(p.products_quantity) AS qty, COUNT(p.products_id) AS count
                          FROM products p
                          LEFT JOIN categories c ON p.master_categories_id = c.categories_id
                          WHERE p.products_price >= ?
                          AND p.products_quantity > 0
                          AND c.parent_id = 458");
  foreach($priceRanges as $price){
    $stmt->bind_param("i", $price);
    $stmt->execute();
    $stmt->bind_result($total, $qty, $count);
    $stmt->store_result();
    $stmt->fetch();
    $data['totals']['bayou'][] = array(
      'price' => '$'.number_format($price, 2).'+',
      'total' => '$'.number_format($total, 2),
      'qty' => intval($qty),
      'count' => intval($count)
    );
  }
  $stmt = $conn->prepare("SELECT SUM(i.product_price*i.product_stock) AS total, SUM(i.product_stock) AS qty, COUNT(i.product_id) AS count
                          FROM pos_inventory_2 i
                          LEFT JOIN products p ON i.product_id = p.products_id
                          LEFT JOIN categories c ON p.master_categories_id = c.categories_id
                          WHERE i.product_price >= ?
                          AND i.product_stock > 0
                          AND c.parent_id = 458");
  foreach($priceRanges as $price){
    $stmt->bind_param("i", $price);
    $stmt->execute();
    $stmt->bind_result($total, $qty, $count);
    $stmt->store_result();
    $stmt->fetch();
    $data['totals']['nine-mile'][] = array(
      'price' => '$'.number_format($price, 2).'+',
      'total' => '$'.number_format($total, 2),
      'qty' => intval($qty),
      'count' => intval($count)
    );
  }

  $stmt = "SELECT categories_id, set_name, pb_code
           FROM mtg_sets
           ORDER BY set_name ASC";
  $result = $conn->query($stmt);
  while($row = $result->fetch_array(MYSQLI_NUM)){
    $data['sets'][$row[0]] = $row[1].' ['.$row[2].']';
  }

  if(isset($_GET['search']) || isset($_GET['set'])){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $stmt = "SELECT cl.products_id, p.products_image, cl.tcgp_id, cl.is_foil, pd.products_name, s.pb_code, p.products_quantity, i.product_stock, p.products_price, p.foil_last_update, s.is_standard
             FROM mtg_card_link cl
             LEFT JOIN products p ON cl.products_id = p.products_id
             LEFT JOIN products_description pd ON p.products_id = pd.products_id
             LEFT JOIN mtg_sets s ON p.master_categories_id = s.categories_id
             LEFT JOIN pos_inventory_2 i ON p.products_id = i.product_id";
    if(isset($_GET['search'])){
      $stmt .= " WHERE pd.products_name like '%".$_GET['search']."%'";
    } elseif(isset($_GET['set'])){
      $stmt .= " WHERE s.categories_id = ".$_GET['set'];
    }
    $stmt .= " ORDER BY s.set_name ASC, pd.products_name ASC, p.products_id ASC";
    $result = $conn->query($stmt);
    $i = 0;
    while($row = $result->fetch_array(MYSQLI_ASSOC)){
      $data['cards'][$i]['products_id'] = $row['products_id'];
      $data['cards'][$i]['products_image'] = $row['products_image'];
      $data['cards'][$i]['tcgp_id'] = $row['tcgp_id'];
      $data['cards'][$i]['is_foil'] = $row['is_foil'];
      $data['cards'][$i]['products_name'] = $row['products_name'];
      $data['cards'][$i]['set_code'] = $row['pb_code'];
      // if(strtotime($row['foil_last_update']) < strtotime("24 hours ago") && $row['products_quantity'] > 0){
      //   curl_setopt($ch, CURLOPT_URL, "http://www.pricebustersgames.com/pbadmin/tcglink/update_item.php?prodId=".$row['products_id']);
      //   curl_exec($ch);
      //   $stmt2 = "SELECT products_price, foil_last_update FROM products WHERE products_id = ".$row['products_id'];
      //   $newResult = $conn->query($stmt2)->fetch_array(MYSQLI_ASSOC);
      //   $lastUpdate = $newResult['foil_last_update'];
      //   $data['cards'][$i]['products_price'] = number_format($newResult['products_price'], 2);
      // } else {
        $data['cards'][$i]['products_price'] = number_format($row['products_price'], 2);
        $lastUpdate = $row['foil_last_update'];
      // }
      switch (true){
        case strtotime($lastUpdate) > strtotime("6 hours ago"):
          $data['cards'][$i]['update_status'] = 'new';
          break;
        case strtotime($lastUpdate) > strtotime("24 hours ago"):
          $data['cards'][$i]['update_status'] = 'old';
          break;
        default:
          $data['cards'][$i]['update_status'] = 'ancient';
      }
      $data['cards'][$i]['showcase_status'] = (($data['cards'][$i]['products_price'] < 2 && $row['is_standard'] != 1) || ($data['cards'][$i]['products_price'] < 1 && $row['is_standard'] == 1)) ? 'box' : 'showcase' ;
      $data['cards'][$i]['qty']['bayou'] = $row['products_quantity'];
      $data['cards'][$i]['qty']['nine-mile'] = ($row['product_stock']) ? $row['product_stock'] : 0 ;
      $i++;
    }
  }
?>
<head>
  <title>PBAdmin - Load MTG Singles</title>
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

    #lookup {
      display: flex;
      flex-flow: column wrap;
      justify-content: center;
      align-items: center;
      align-content: center;
    }

    #search {
      float: left;
      margin-right: 10px;
    }

    #setSelect {
      float: left;
      margin-right: 10px;
    }

    #tables {
      display: flex;
      flex-flow: row nowrap;
      justify-content: space-evenly;
      align-items: flex-start;
      align-content: flex-start;
    }

    #tables table tr:nth-child(odd){
      background-color: #ddd;
    }

    .click-edit {
      border-bottom: dotted 1px black;
    }

    .new {
      background-color: white;
      border: 1px solid #666;
      font-weight: bold;
      color: #3f3;
    }

    .old {
      background-color: white;
      border: 1px solid #666;
      font-weight: bold;
      color: #f93;
    }

    .ancient {
      background-color: white;
      border: 1px solid #666;
      font-weight: bold;
      color: #f33;
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

    input[type=number] {
      width: 50px;
    }

    #note {
      background-color: #cfc;
      border-radius: 35px 0 0 35px;
      height: 30px;
      width: 200px;
      position: absolute;
      top: 275px;
    }

    #note span {
      position: relative;
      top: 5px;
      left: 10px;
    }

    #note:after {
      content: '';
      font-size: 0;
      width: 0;
      height: 0;
      position: relative;
      top: 0px;
      left: 35px;
      border-top: 35px solid transparent;
      border-bottom: 35px solid transparent;
      border-left: 35px solid #cfc;
      border-radius: 0;
    }

    #modal-bg {
      display: none;
      content: '';
      background-color: #999;
      width: 100%;
      height: 100%;
      overflow: hidden;
      opacity: .4;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 1000;
    }

    #progress {
      display: none;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      align-content: center;
      background-color: #fff;
      border: 2px solid #ccc;
      box-shadow: 7px 7px 20px #666;
      border-radius: 10px;
      height: 150px;
      width: 400px;
      z-index: 1010;
      position: fixed;
      left: 50%;
      top: 40%;
      transform: translate(-50%, -50%);
    }

    #progress-bar-container {
      content: '';
      display: block;
      height: 20px;
      width: 80%;
      border: #333 1px solid;
      border-radius: 5px;
    }

    #progress-bar {
      height: 20px;
      width: 0;
      background-color: #3f3;
      border-radius: 5px;
      position: relative;
      animation-timing-function: linear;
    }
  </style>
</head>
<body>
  <script src='http://labelwriter.com/software/dls/sdk/js/DYMO.Label.Framework.latest.js'></script>
  <script src='./includes/dymo.js'></script>
  <?php if(!isset($_GET['set']) && !isset($_GET['search'])){
    echo "<div id='note'><span>Yes, this actually works.</span></div>";
  } ?>
  <div id='img-div'></div>
  <div id='modal-bg'></div>
  <div id='progress'>
    <p>Saving...</p>
    <span id='progress-num'>0/0</span>
    <div id='progress-bar-container'><div id='progress-bar'></div></div>
  </div>
  <div id='container'>
    <div id='tables'>
      <?php
        foreach($data['totals'] as $store => $info){
          echo "<table id='$store'>
            <thead>
              <tr>
                <th colspan='4'>".ucwords(str_replace("-", " ", $store))."</th>
              </tr>
              <tr>
                <th>Price</th>
                <th>Total</th>
                <th>Quantity</th>
                <th>Unique cards</th>
              </tr>
            </thead>
          <tbody>";
          foreach($info as $row){
            echo "<tr>
              <td>".$row['price']."</td>
              <td>".$row['total']."</td>
              <td>".$row['qty']."</td>
              <td>".$row['count']."</td>
            </tr>";
          }
          echo "</tbody>
          </table>";
        }
      ?>
    </div>
    <div id='lookup'>
      <span>
        <select id='setSelect'>
          <option value=''>Choose...</option>
          <?php
          foreach($data['sets'] as $id => $name){
            echo "<option value='$id'>$name</option>";
          }
          ?>
        </select>
        <button id='setSubmit'>Select</button>
      </span>
      <p><strong>-OR-</strong></p>
      <span>
        <input id='search' type='text' placeholder='Search...' />
        <button id='searchSubmit'>Search</button>
      </span>
    </div>
    <?php
      if(isset($data['cards'])){
        ?> <div>
          <table id='cardData'>
            <thead>
              <tr>
                <th>Image</th>
                <th>TCG ID</th>
                <th>Foil Status</th>
                <th>Name</th>
                <?php foreach($data['cards'][0]['qty'] as $store => $qty){
                  echo "<th>".ucwords(str_replace("-", " ", $store))."</th>";
                } ?>
                <th>+/- Qty.</th>
                <th>Price</th>
                <th>Label</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($data['cards'] as $card){
                echo "<tr id='".$card['products_id']."' class='".$card['showcase_status'].($card['is_foil'] == 1 ? ' foil' : ' normal')."'>
                  <td><img src='../../images/".$card['products_image']."' /></td>
                  <td class='tcgpId'><span class='click-edit'>".$card['tcgp_id']."</span></td>
                  <td>
                    <select class='foil-status' tabindex='-1'>
                      <option value='0'".($card['is_foil'] == 1 ? '' : ' selected').">Normal</option>
                      <option value='1'".($card['is_foil'] == 1 ? ' selected' : '').">Foil</option>
                    </select>
                  </td>
                  <td class='card-name'>".$card['products_name']." [".$card['set_code']."]</td>";
                  foreach($card['qty'] as $store => $qty){
                    echo "<td class='".$store."-qty'>".$qty."</td>";
                  }
                  echo "<td class='qty'><input type='number' /></td>
                  <td><button class='label-print' tabindex='-1'>Print</button></td>
                  <td class='price ".$card['update_status']."'>$".$card['products_price']."</td>
                </tr>";
              } ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan='<?=7+sizeof($data['cards'][0]['qty'])?>'><button id='save' class='save-btn'>Save changes</button></td>
              </tr>
            </tfoot>
          </table>
        </div> <?php
      }
    ?>
  </div>
</body>
<script>
  $(window).load(() => {
    $("#note").css("left", ($("#search").position().left-250)+"px");
    $("#note").css("top", ($("#search").position().top-5)+"px");
  });

  $(window).resize(() => {
    $("#note").css("left", ($("#search").position().left-250)+"px");
    $("#note").css("top", ($("#search").position().top-5)+"px");
  });

  $("body").on("click", ".click-edit", (e) => {
    let val = $(e.currentTarget).html();
    let cell = $(e.currentTarget).parent();
    $(cell).html("<input type='text' class='click-edit-field' value='"+val+"' size='2'/>");
    $(".click-edit-field").select();
  });

  $("body").on("blur", ".click-edit-field", (e) => {
    let val = $(e.currentTarget).val();
    let prop = $(e.currentTarget).parent().attr("class");
    let id = $(e.currentTarget).parent().parent().attr("id");
    let params = {
      'method': 'update',
      'prop': prop,
      'prodId': id,
      'val': val
    };
    $.post("./load_ajax.php", JSON.stringify(params), (response) => {
      console.log(response);
      let data = JSON.parse(response);
      if(!data.errors){
        $(e.currentTarget).parent().siblings(".price").html("$"+data.new_price).removeClass("new old ancient").addClass("new");
        $(e.currentTarget).parent().html("<span class='click-edit'>"+val+"</span>");
      } else {
        console.info(data.errors);
      }
    });
  });

  $("body").on("blur", ".qty input", (e) => {
    if($(e.currentTarget).val() <= 0 || $(e.currentTarget).val() == ''){
      return;
    }
    let id = $(e.currentTarget).parent().parent().attr("id");
    let params = {
      'method': 'update',
      'prop': 'price',
      'prodId': id,
    };
    $.post("./load_ajax.php", JSON.stringify(params), (response) => {
      let data = JSON.parse(response);
      console.info(data);
      if(!data.errors){
        $(e.currentTarget).parent().siblings(".price").html("$"+data.new_price).removeClass("new old ancient").addClass("new");
      } else {
        console.info(data.errors);
      }
    });
  });

  $("#cardData img").mouseover((e) => {
    let img = $(e.currentTarget).attr("src");
    console.log(img);
    $("#img-div").html("<img src='"+img+"' />")
  });

  $("#cardData img").mouseout((e) => {
    $("#img-div").html("");
  });

  $("#cardData tbody input").keyup((e) => {
    if(e.which == 13){
      $("#save").click();
    }
  });

  $("#save").click(() => {
    $("#save").attr("id", "");
    $("#modal-bg").show();
    $("#progress").css("display", "flex");
    let rows = $("#cardData tbody tr");
    let j = 0;
    for(let i = 0, n = rows.length; i < n; i++){
      let qty = $(rows[i]).children(".qty").children("input").val();
      if(qty != ''){
        j++;
        $("#progress-num").html("0/"+j);
      }
    }
    if(j == 0){
      window.location.href = "?";
    }
    let k = 0;
    for(let i = 0, n = rows.length; i < n; i++){
      let qty = $(rows[i]).children(".qty").children("input").val();
      if(qty != ''){
        let prodId = $(rows[i]).attr("id");
        let params = {
          'method': 'addQty',
          'prodId': prodId,
          'qty': qty
        };
        $.post("./load_ajax.php", JSON.stringify(params), () => {
          k++;
          $("#progress-num").html(k+"/"+j);
          $("#progress-bar").width(k*100/j+"%");
        });
      }
    }
    $(document).ajaxStop(() => {
      window.location.href = "?";
    });
  });

  $("#search").keyup((e) => {
    if(e.which == 13){
      $("#searchSubmit").click();
    }
  });

  $("#searchSubmit").click(() => {
    let val = $("#search").val();
    window.location.href = "?search="+val;
  });

  $("#setSubmit").click(() => {
    let val = $("#setSelect").val();
    window.location.href = "?set="+val;
  });

  $(".foil-status").change((e) => {
    let val = $(e.currentTarget).val();
    let id = $(e.currentTarget).parent().parent().attr("id");
    let params = {
      'method': 'foil',
      'prodId': id,
      'val': val
    };
    $.post("./load_ajax.php", JSON.stringify(params), (response) => {
      console.log(response);
      let data = JSON.parse(response);
      console.info(data.result);
      if(!data.errors){
        $(e.currentTarget).parent().siblings(".price").html("$"+data.new_price).removeClass("new old ancient").addClass("new");
      } else {
        console.info(data.errors);
      }
    });
  });

  $(".label-print").click((e) => {
    let text = $(e.currentTarget).parent().siblings(".card-name").html();
    let id = $(e.currentTarget).parent().parent().attr("id");
    let qty = $(e.currentTarget).parent().siblings(".qty").children("input").val();
    printMTGLabel(text, id, qty);
  });
</script>
