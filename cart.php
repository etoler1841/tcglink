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
      justify-content: center;
      align-items: flex-start;
      align-content: center;
    }

    #container {
      font-family: Tahoma, Verdana, sans-serif;
    }

    input {
      padding: 5px;
      border-radius: 5px;
    }
  </style>
</head>
<body>
  <div id="container">
    <input type="text" id="sku" />
    <button id="submit">Submit</button>
  </div>
  <script>
    $("#sku").on("keyup", (e) => {
      if(e.which === 13){
        addToCart();
      }
    });

    $("#submit").click(addToCart);

    function addToCart(){
      let sku = $("#sku").val();
      let params = {
        action: 'add',
        sku: sku
      };
      $.post("./cart_ajax.php", JSON.stringify(params), (r) => {
        if(r.status === 'ok'){

        } else if (r.status === 'err'){
          console.log(r.errors);
        }
      });
    }
  </script>
</body>
