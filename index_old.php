<?php
  define('SITE_ROOT', '.');
  require("includes/includes.php");
  $start = (isset($_GET['start'])) ? $_GET['start'] : 0 ;

  $done = (isset($_GET['done'])) ? $_GET['done'] : 0;
?>
<head>
  <style>
    table, tr, th, td {
      border-collapse: collapse;
      border: solid black 1px;
    }

    th, td {
      padding: 5px;
    }

    #status {
      position: fixed;
      right: 0;
      top: 0;
      border: 2px solid black;
      background-color: white;
      padding: 10px;
    }
  </style>
</head>
<body>
  <div id="status"><span id="success"><?=$done?></span> cards built</div>
  <table>
    <thead>
      <tr>
        <th>TCGPlayer ID</th>
        <th>Name</th>
        <th>Set</th>
        <th>PB products_id</th>
      </tr>
    </thead>
    <tbody>

    </tbody>
  </table>
</body>
<script>
  let finished = 0;
  let success = <?=$done?>;
  $(window).load(() => {
    $.ajax("http://api.tcgplayer.com/catalog/products", {
      'headers': {
        'Authorization': 'bearer <?=$token?>'
      },
      'data': {
        'categoryId': 1,
        'limit': 100,
        'offset': <?=$start?>,
        'groupID': '1837'
      }
    }).then((response) => {
      for(let i = 0, n = response.results.length; i < n; i++){
        let product = response.results[i];
        let params = {
          'tcgID': product.productId,
          'cardName': product.productName
        };
        let row = `<tr id='${product.productId}'>
            <td class='tcgp-id'>${product.productId}</td>
            <td class='name'>${product.productName}</td>`;
        $.ajax("http://api.tcgplayer.com/catalog/groups/"+product.groupId, {
          'headers': {
            'Authorization': 'bearer <?=$token?>'
          }
        }).then((response) => {
          params['setName'] = response.results[0].name;
          row += `<td class='set'>${response.results[0].name}</td>
                  <td class='pb-id'></td>`;
        }).then(() => {
          row += `</tr>`;
          $("tbody").append(row);
          $.post("card_link.php", JSON.stringify(params), (response) => {
            let data = JSON.parse(response);
            if(data.error){
              console.log(`Error: ${data.error}`);
            } else {
              $("#"+params.tcgID+" .pb-id").html(data.pbID);
              success++;
              $("#success").html(success.toFixed(0));
            }
            finished++;
            if(finished == 100){
              window.location.href = "?start=<?=$start+100?>&done="+success;
            }
          });
        });
      }
    });
  });
</script>
