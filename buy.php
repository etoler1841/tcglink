<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');

  $stmt = "SELECT categories_id, set_name, pb_code
           FROM mtg_sets
           ORDER BY set_name ASC";
  $result = $conn->query($stmt);
  while($row = $result->fetch_array(MYSQLI_ASSOC)){
    $data['sets'][] = array(
      'catId' => $row['categories_id'],
      'setName' => $row['set_name'],
      'setCode' => $row['pb_code']
    );
  }
?>
<head>
  <title>PBAdmin - Buy MTG</title>
  <style>
    body {
      font-family: Tahoma, sans-serif;
      font-size: .95em;
    }

    div {
      padding: 15px;
    }

    table {
      display: inline-table;
      margin: 20px;
      border-collapse: collapse;
      font-size: .95em;
      min-width: 850px;
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

    tr.foil {
      background-color: #ddd;
    }

    tr.normal {
      background-color: white;
    }

    .qty > input {
      width: 50px;
    }

    .warning {
      background-color: #f99;
    }

    #container, #finder {
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
      display: none;
    }

    #img-div img {
      height: 100%;
    }

    #products img {
      height: 35px;
    }

    #products tr {
      border-bottom: solid 1px #666;
    }

    #products table .qty {
      width: 10%;
    }

    #products table .image {
      width: 10%;
    }

    #products table .name {
      width: 50%;
    }

    #products table .price {
      width: 15%;
    }

    #products table .current-qty {
      width: 15%;
    }

    #search-results {
      padding: 0px;
      border: 1px solid #333;
      box-shadow: 2px 2px 10px #666;
      width: 380px;
      max-height: 350px;
      overflow-x: hidden;
      overflow-y: auto;
      background-color: white;
      margin-top: 0;
      position: absolute;
      display: none;
    }

    #search-results .search-result {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      align-content: stretch;
      padding: 10px;
      height: 50px;
      width: auto;
      border-bottom: solid 1px #999;
    }

    #search-results .search-result img {
      height: 100%;
      width: auto;
      float: left;
      margin-right: 5px;
    }

    #search-results .search-result.highlight {
      background-color: #eff;
    }

    #totals, #add-ins {
      display: flex;
      flex-flow: row nowrap;
      justify-content: space-evenly;
      align-items: stretch;
      align-content: flex-start;
    }

    #totals, #add-ins, #finder {
      width: 100%;
    }

  </style>
</head>
<body>
  <div id='container'>
    <div id='img-div'></div>
    <div id='totals'>
      <span class='value'>Sale price: <span id='sale-amt'>$0.00</span></span>
      <span class='value'>Singles price: <span id='singles-amt'>$0.00</span></span>
      <span class='value'>Store credit price: <span id='store-credit-amt'>$0.00</span></span>
      <span class='value'>Cash price: <span id='cash-amt'>$0.00</span></span>
    </div>
    <div id='add-ins'>
      <span class='add-in'>Bulk (inches) <input type='text' id='bulk-qty' size='3' /></span>
      <span class='add-in'>Standard rares <input type='text' id='s-rare-qty' size='3' /></span>
      <span class='add-in'>Non-standard rares <input type='text' id='ns-rare-qty' size='3' /></span>
    </div>
    <div id='finder'>
      <div id='search'>
        <input type='text' size='50' id='search-field' placeholder='Start typing...' />
        <div id='search-results'>
          <div class='search-result'><p>No results</p></div>
        </div>
      </div>
      <p><strong>- OR -</strong></p>
      <div>
        <select id='set-select'>
          <option value=''>Choose...</option>
          <?php foreach($data['sets'] as $set){
            echo "<option value='".$set['catId']."'>".$set['setName']." [".$set['setCode']."]</option>";
          } ?>
        </select>
      </div>
      <div>
        <select id='card-select'>
          <option value=''>Please select a set</option>
        </select>
        <button id='card-add'>Add</button>
      </div>
    </div>
    <div id='products'>
      <table>
        <thead>
          <th class='qty'>Qty.</th>
          <th class='image'>Image</th>
          <th class='name'>Item</th>
          <th class='price'>Price</th>
          <th class='current-qty'>Current Qty.</th>
        </thead>
        <tbody>

        </tbody>
      </table>
    </div>
  </div>
  <script>
    //functions
    function addItem(prodId) {
      if($("#"+prodId).length > 0){
        let field = $("#"+prodId+" .qty input");
        field.val(parseInt(field.val())+1);
      } else {
        let params = {
          'method': 'cardSelect',
          'prodId': prodId
        };
        $.post("./buy_ajax.php", JSON.stringify(params), (response) => {
          let data = JSON.parse(response);
          if(data.errors){
            console.info(data.errors);
          } else {
            $("#products tbody").append(`
              <tr id='${data.card.prodId}' class=${data.card.foilStatus}>
                <td class='qty'><input type='number' value='1' /></td>
                <td class='image'><img src='../../images/${data.card.prodImage}' /></td>
                <td class='name'>${data.card.prodName}</td>
                <td class='price'>$${data.card.price}</td>
                <td class='current-qty'>${data.card.currentQty}</td>
              </tr>
            `);
          }
        });
      }
      updateTotals();
    }

    function itemSearch(str){
      let params = {
        'method': 'search',
        'str': str
      };
      $.post("./buy_ajax.php", JSON.stringify(params), (response) => {
        let data = JSON.parse(response);
        if(data.cards.length == 0){
          $("#search-results").html(`
            <div class='search-result'><p>No results</p></div>
          `);
          $("#search-results").hide();
        } else {
          $("#search-results").show();
          $("#search-results").html('');
          for(card of data.cards){
            $("#search-results").append(`
              <div class='search-result' id='res_${card.prodId}'>
                <img src='../../images/${card.prodImg}' alt=' />
                <span class='card-name'>${card.prodName} [${card.setCode}]</span>
              </div>
            `);
          }
        }
      });
    }

    function updateTotals(){
      let total = singles = storeCredit = cash = 0;
      let addIns = $("#add-ins input");
      for(let i = 0, n = addIns.length; i < n; i++){
        let val = parseFloat($(addIns[i]).val())
        if(!isNaN(val)){
          switch($(addIns[i]).attr("id")){
            case 'bulk-qty':
              total += val*.15;
              singles += val*.10;
              storeCredit += val*.10;
              cash += val*.05;
              break;
            case 's-rare-qty':
              total += val*.05;
              singles += val*.05;
              storeCredit += val*.05;
              cash += val*.02;
              break;
            case 'ns-rare-qty':
              total += val*.10;
              singles += val*.08;
              storeCredit += val*.08;
              cash += val*.04;
              break;
          }
        }
      }
      let rows = $("#products tbody tr");
      for(let i = 0, n = rows.length; i < n; i++){
        let price = parseFloat($(rows[i]).children(".price").html().replace("$", ""));
        let j = $(rows[i]).children(".qty").children("input").val();
        let k = parseInt($(rows[i]).children(".current-qty").html());
        for(l = 0; l < j; l++, k++){
          switch(true){
            case k < 8:
              total += price;
              singles += price*.66;
              storeCredit += price*.6;
              cash += price*.5;
              break;
            case k < 12:
              total += price;
              singles += price*.55;
              storeCredit += price*.5;
              cash += price*.4;
              break;
            case k < 20:
              total += price;
              singles += price*.44;
              storeCredit += price*.4;
              cash += price*.35;
              break;
            case k < 32:
              total += price;
              singles += price*.33;
              storeCredit += price*.3;
              cash += price*.2;
              break;
            default:
              total += price;
              singles += price*.2;
              storeCredit += price*.2;
              cash += price*.15;
          }
        }
      }
      $("#sale-amt").html("$"+(Math.round(total*100)/100).toFixed(2));
      $("#singles-amt").html("$"+(Math.round(singles*100)/100).toFixed(2));
      $("#store-credit-amt").html("$"+(Math.round(storeCredit*100)/100).toFixed(2));
      $("#cash-amt").html("$"+(Math.round(cash*100)/100).toFixed(2));
    }

    //listeners
    $(document).ready(() => {
      $("#search-field").focus();
      $("#search-results").css("top", $("#search-field").position().top+$("#search-field").height);
      $("#search-results").css("left", $("#search-field").position().left);
      $("#search-results").css("width", $("#search-field").width);
    });

    $("#add-ins input").keyup((e) => {
      let elem = e.currentTarget;
      if(isNaN($(elem).val())){
        $(elem).addClass("warning");
      } else {
        $(elem).removeClass("warning");
      }
      updateTotals();
    });

    $("body").on("click", "input", (e) => {
      $(e.currentTarget).select();
    });

    $("body").on("click", ".search-result", (e) => {
      let prod = e.currentTarget;
      let prodId = $(prod).attr("id").replace("res_", "");
      addItem(prodId);
      $("#search-results").hide();
    });

    $("body").on("mouseover", ".search-result", (e) => {
      $(".highlight").removeClass("highlight");
      $(e.currentTarget).addClass("highlight");
      $("#img-div").html("<img src='"+$(e.currentTarget).children("img").attr("src")+"' alt='' />");
    });

    $("#card-add").click(() => {
      let prodId = $("#card-select").val();
      addItem(prodId);
    });

    $("#products").on("mouseover", ".image img", (e) => {
      let img = $(e.currentTarget).attr("src");
      $("#img-div").show();
      $("#img-div").html("<img src='"+$(e.currentTarget).attr("src")+"' alt='' />");
    });

    $("#products").on("mouseout", ".image img", (e) => {
      $("#img-div").hide();
    });

    $("#products").on("change keyup click", ".qty input", (e) => {
      let elem = e.currentTarget;
      if($(elem).val() == 0){
        $(elem).parent().parent().remove();
      }
      updateTotals();
    });

    $("#search-field").keyup((e) => {
      let box = $("#search-results");
      let items = $(box).children(".search-result");
      switch(e.which){
        case 40:
          if($(box).css("display") == 'none'){
            $(box).show();
            $(".highlight").removeClass("highlight");
            $(items[0]).addClass("highlight");
            $(box).scrollTop(0);
          } else {
            for(let i = 0, n = items.length; i < n; i++){
              if($(items[i]).hasClass("highlight")){
                let j = $(items[i]).index();
                if(j+1 < items.length){
                  $(items[j]).removeClass("highlight");
                  $(items[j+1]).addClass("highlight");
                  let height = $(items[1]).position().top-$(items[0]).position().top;
                  if($(".highlight").position().top > ($(box).position().top+$(box).height()-height)){
                    let top = ((j+1)*height);
                    $(box).scrollTop(top+height-$(box).height());
                  }
                }
                break;
              }
              if($(box).children(".highlight").length == 0){
                $(items[0]).addClass("highlight");
                $(box).scrollTop(0);
              }
            }
          }
<<<<<<< HEAD
          $("#img-div").html("<img src='"+$(".highlight").children("img").attr("src")+"' />").show();
=======
          $("#img-div").html("<img src='"+$(".highlight").children("img").attr("src")+"' alt='' />").show();
>>>>>>> buylist-search
          break;
        case 38:
          //up arrow
          for(let i = 0, n = items.length; i < n; i++){
            if($(items[i]).hasClass("highlight")){
              let j = $(items[i]).index();
              if(j != 0){
                $(items[j]).removeClass("highlight");
                $(items[j-1]).addClass("highlight");
                console.log($(".highlight").position().top);
                if($(".highlight").position().top < $(box).position().top){
                  let height = $(items[1]).position().top-$(items[0]).position().top;
                  let top = ((j-1)*height);
                  $(box).scrollTop(top);
                }
              }
              break;
            }
          }
<<<<<<< HEAD
          $("#img-div").html("<img src='"+$(".highlight").children("img").attr("src")+"' />").show();
=======
          $("#img-div").html("<img src='"+$(".highlight").children("img").attr("src")+"' alt='' />").show();
>>>>>>> buylist-search
          break;
      case 13:
        //enter
        if($(".highlight").length == 1){
          let prodId = $(".highlight").attr("id").replace("res_", "");
          addItem(prodId);
          $("#search-results").hide();
        }
        $("#img-div").hide();
        break;
      case 27:
        //esc
        $("#img-div").hide();
        $("#search-results").hide();
        break;
      default:
        let str = $("#search-field").val();
        if(str.length >= 3){
          itemSearch(str);
        }
      }
    });

    $("#set-select").change(() => {
      let params = {
        'method': 'setSelect',
        'catId': $("#set-select").val()
      };
      $.post("./buy_ajax.php", JSON.stringify(params), (response) => {
        let data = JSON.parse(response);
        if(data.errors){
          console.info(data.errors);
        } else {
          $("#card-select").html("<option value=''>Choose...</option>");
          for(let i = 0, n = data.cards.length; i < n; i++){
            $("#card-select").append(`<option value='${data.cards[i].prodId}'>${data.cards[i].prodName}</option>`);
          }
        }
      });
    });
  </script>
</body>
