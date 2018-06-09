<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');

  $stmt = "SELECT set_name, pb_code
           FROM mtg_sets
           ORDER BY set_name ASC";
  $result = $conn->query($stmt);
  while($row = $result->fetch_array(MYSQLI_ASSOC)){
    $sets[] = array(
      'name' => $row['set_name'],
      'code' => $row['pb_code'],
      'img' => 'http://www.pricebustersgames.com/pbadmin/tcglink/img/'.$row['pb_code'].'.jpg'
    );
  }
?>
<head>
  <style>
    body, table {
      font-family: Tahoma, sans-serif;
      font-size: .95em;
    }

    table {
      border-collapse: collapse;
    }

    table {
      border: 1px solid black;
    }

    tr, th, td {
      border: none;
    }

    th, td {
      padding: 5px;
    }

    tr {
      border-bottom: 1px solid #333;
    }

    input[type=number] {
      width: 50px;
    }
  </style>
</head>
<body>
  <script src='http://labelwriter.com/software/dls/sdk/js/DYMO.Label.Framework.latest.js'></script>
  <script src='./includes/dymo.js'></script>
  <table id='sets'>
    <tbody>
      <?php
      foreach($sets as $set){
        echo "<tr>
          <td class='name'><span class='setName'>".$set['name']."</span> [<span class='setCode'>".$set['code']."</span>]<input type='hidden' value='".$set['img']."' class='img'/></td>
          <td><input type='number' value='0' class='qty' /></td>
          <td><button class='box'>Box Label</button></td>
          <td><button class='tab'>Tab Label</button></td>
        </tr>";
      }
      ?>
    </tbody>
  </table>
</body>
<script>
  $("button").click((e) => {
    let type = $(e.currentTarget).attr("class");
    let name = $(e.currentTarget).parent().siblings(".name").children(".setName").html();
    let code = $(e.currentTarget).parent().siblings(".name").children(".setCode").html();
    let img = $(e.currentTarget).parent().siblings(".name").children(".img").val();
    let qty = $(e.currentTarget).parent().siblings().children(".qty").val();
    let params = {
      type: type,
      name: name,
      code: code,
      img: img,
      qty: qty,
    };
    printSetLabel(params);
  });
</script>
