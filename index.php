<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
?>
<head>
  <title>TCGLink</title>
  <style>
    html {
      height: 100%;
      overflow-y: hidden;
    }

    body {
      font-family: Tahoma, sans-serif;
      font-size: .95em;
      background: linear-gradient(to bottom right, #aaf, #99f);
      height: 100%;
    }

    #container {
      display: flex;
      flex-flow: column nowrap;
      justify-content: flex-start;
      align-items: center;
      align-content: center;
    }

    h1 {
      color: white;
      border: 3px solid #333;
      border-radius: 35px;
      background-color: #f66;
      padding: 15px;
    }

    ul {
      list-style-type: none;
      padding: 0;
    }

    li {
      margin: 10px;
      text-align: center;
    }

    li a {
      font-size: 1.3em;
      width: 250px;
      border: 3px solid #333;
      border-radius: 35px;
      box-shadow: 5px 5px 5px #999;
      background-color: #ffc;
      text-decoration: none;
      padding: 15px;
      color: #333;
      display: inline-block;
    }

    li a:hover {
      background-color: #ff9;
    }
  </style>
</head>
<body>
  <div id="container">
    <h1>Welcome to TCGLink</h1>
    <ul>
      <li><a href='./buy.php'>Buy MTG</a></li>
      <li><a href='./load.php'>Load MTG</a></li>
      <li><a href='./new_sets.php'>Build New Sets</a></li>
      <li><a href='../CantonmentInventory/mtgcart.php'>MTG Cart</a></li>
      <li><a href='./labels.php'>Print MTG Labels</a></li>
      <li><a href='./viewall.php'>View All TCGPlayer Data</a></li>
    </ul>
  </div>
</body>
</html>
