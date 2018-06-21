<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
?>
<head>
  <title>Cart - TCGLink</title>
  <style>
    body {
      display: flex;
      flex-flow: column nowrap;
      justify-content: flex-start;
      align-items: center;
      align-content: flex-start;
      font-family: Tahoma, Verdana, sans-serif;
    }

    table {
      display: inline-table;
      margin: 20px;
      border-collapse: collapse;
      min-width: 850px;
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

    tr {
      border-bottom: solid 1px #666;
    }

    input {
      padding: 5px;
      border-radius: 5px;
    }

    #container {
      font-family: Tahoma, Verdana, sans-serif;
    }

    #totals {
      border: none;
      min-width: auto;
      margin: 0;
    }

    #totals tr {
      border: none;
    }

    #totals th {
      text-align: right;
    }

    #totals td {
      text-align: left;
    }

    #img-div {
      position: fixed;
      top: 0;
      right: 0;
      z-index: 1;
      height: 300px;
      display: none;
    }

    #img-div img {
      height: 100%;
    }

    #cart img {
      height: 35px;
    }

    .qty {
      width: 55px;
    }
  </style>
</head>
<body>
  <div id="container">
    <input type="text" id="sku" placeholder="Scan..." />
    <button id="submit">Submit</button>
  </div>
  <div id="img-div"></div>
  <table id="cart">
    <thead>
      <tr>
        <th>Qty.</th>
        <th>Image</th>
        <th>Card Name</th>
        <th>Set</th>
        <th>Price Ea.</th>
        <th>Line Total</th>
      </tr>
    </thead>
    <tbody id="items">

    </tbody>
    <tfoot>
      <tr>
        <td colspan="5"></td>
        <td>
          <table id="totals">
            <tr class="subtotal">
              <th>Subtotal:</th>
              <td class="amt">$0.00</td>
            </tr>
            <tr class="tax">
              <th>Tax:</th>
              <td class="amt">$0.00</td>
            </tr>
            <tr class="grand">
              <th>Grand Total:</th>
              <td class="amt">$0.00</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td><button id="empty">Empty Cart</button></td>
        <td colspan="4"></td>
        <td><button id="checkout">Checkout</button></td>
      </tr>
    </tfoot>
  </table>
  <script>
    $("#sku").on("keyup", (e) => {
      if(e.which === 13){
        addToCart();
      }
    });

    $("#submit").click(addToCart);

    $("#empty").click(clearCart);

    $("#items").on("change keyup click", ".qty", (e) => {
      let elem = e.currentTarget;
      let row = $(elem).parent().parent();
      if($(elem).val() == 0){
        $(row).remove();
        totals();
      } else {
        let sku = $(row).attr("id");
        lineTotal(sku);
      }
    });

    $("#items").on("mouseover", "img", (e) => {
      let img = $(e.currentTarget).attr("src");
      $("#img-div").html("<img src='"+img+"' />").show();
    });

    $("#items").on("mouseout", "img", () => {
      $("#img-div").hide();
    });

    $("#checkout").click(() => {
      let rows = $("#cart tbody tr");
      for(let i = 0; i < rows.length; i++){
        let params = {
          action: 'remove',
          prodId: $(rows[i]).attr("id"),
          qty: $(rows[i]).children().children(".qty").val()
        };
        $.post("./cart_ajax.php", JSON.stringify(params), (r) => {
          if(r.status === 'ok'){
            $(rows[i]).remove();
            totals();
          } else {
            console.log(r.errors);
          }
        });
      }
    });

    function increment(sku){
      let e = $("#"+sku+" .qty");
      let q = parseInt($(e).val())+1;
      let p = stripMoney($("#"+sku+" .price-ea").html());
      $(e).val(q);
      lineTotal(sku);
    }

    function addToCart(){
      let sku = $("#sku").val().replace(/\D/g, "");
      if($("#"+sku).length){
        increment(sku);
      } else {
        let params = {
          action: 'add',
          sku: sku
        };
        $.post("./cart_ajax.php", JSON.stringify(params), (r) => {
          if(r.status === 'ok'){
            $("#items").prepend(`<tr id="${sku}">
              <td><input type="number" class="qty" value="1" /></td>
              <td><img src="<?=$imgPath?>${r.result.img}" onerror="this.style.display = 'none'" /></td>
              <td>${r.result.name}</td>
              <td>${r.result.set}</td>
              <td class="price-ea">$${r.result.price}</td>
              <td class="price-line">$${r.result.price}</td>
            </tr>`);
            totals();
          } else if (r.status === 'err'){
            console.log(r.errors);
          }
        });
      }
      $("#sku").val("");
    }

    function clearCart(){
      $("#items").html("");
      totals();
    }

    function lineTotal(sku){
      let p = stripMoney($("#"+sku+" .price-ea").html());
      let q = $("#"+sku+" .qty").val();
      let line = parseFloat(p*q).toFixed(2);
      $("#"+sku+" .price-line").html("$"+line);
      totals();
    }

    function totals(){
      let rows = $("#items tr") || null;
      let subtotal = 0;
      let tax = 0;
      let grand = 0;
      if(rows.length){
        for(let i = 0; i < rows.length; i++){
          let line = stripMoney($(rows[i]).children(".price-line").html());
          subtotal = (parseFloat(subtotal)+parseFloat(line)).toFixed(2);
        }
        tax = stripMoney(subtotal*.075);
        grand = stripMoney(subtotal*1.075);
      }
      $("#totals .subtotal .amt").html("$"+parseFloat(subtotal).toFixed(2));
      $("#totals .tax .amt").html("$"+parseFloat(tax).toFixed(2));
      $("#totals .grand .amt").html("$"+parseFloat(grand).toFixed(2));
    }

    //Returns numeric val as string (no "$")
    function stripMoney(m){
      m = String(m).replace("$", "");
      if(m == 0){
        return parseFloat(0.00);
      }
      m = parseFloat(m);
      m = Math.round(m*100)/100;
      return m;
    }
  </script>
</body>
