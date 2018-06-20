<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
?>
<head>
  <title>Login - TCGLink</title>
  <style>
    body {
      font-family: Tahoma, sans-serif;
      font-size: .95em;
    }

    #container {
      width: auto;
      display: flex;
      flex-flow: column nowrap;
      justify-content: center;
      align-items: center;
      height: 60%;
    }

    #login-box {
      border-radius: 5px;
      background-color: #aaf;
      box-shadow: 5px 5px 5px #999;
      display: flex;
      flex-flow: column nowrap;
      justify-content: center;
      align-items: center;
    }

    #login-box h3 {
      color: #333;
      font-size: 1.5em;
    }

    #login-box .form-group {
      margin: 15px;
      font-weight: bold;
    }

    #login-box input {
      border-radius: 5px;
      height: 2.5em;
      border: 1px solid #666;
      padding: 5px;
    }

    #login-box button {
      margin: 15px;
      padding: 10px;
      border-radius: 5px;
      background-color: white;
      border: 2px solid #ccc;
      font-family: Tahoma, sans-serif;
      font-size: 1em;
      font-weight: bold;
    }

    #login-box button:hover {
      background-color: #eee;
    }

    #note {
      background-color: #dfd;
      border: 2px solid #9f9;
      border-radius: 5px;
      padding: 5px;
      margin: 15px;
      max-width: 300px;
      text-align: center;
    }

    #warning {
      background-color: #fdd;
      border: 2px solid #f99;
      border-radius: 5px;
      padding: 5px;
      margin: 15px;
      display: none;
    }
  </style>
</head>
<body>
  <div id="container">
    <div id="login-box">
      <h3>Login to TCGLink</h3>
      <div class="form-group">
        <label for="user">Username: </label>
        <input type="text" id="user" />
      </div>
      <div class="form-group">
        <label for="pass">Password: </label>
        <input type="password" id="pass" />
      </div>
      <button id="login">Login</button>
    </div>
    <div id="note">Your username is your first inital and last name. Your default password is your 10-digit phone number.</div>
    <div id="warning"></div>
  </div>
  <script>
    function login(){
      let user = $("#user").val();
      let pass = $("#pass").val();
      if(!user || !pass){
        $("#warning").html("Invalid username or password").show();
        $("#pass").val("");
        return;
      }
      let params = {
        user: user,
        pass: pass
      };
      $.post("./login_check.php", JSON.stringify(params), (r) => {
        if(r.status === 'ok'){
          if(r.passUpdate){
            window.location.href = 'pass_change.php<?php if(isset($_GET['ref'])) echo '?ref='.$_GET['ref']; ?>'
          } else {
            window.location.href = '<?= isset($_GET['ref']) ? $_GET['ref'] : 'index.php'; ?>';
          }
        } else if(r.status === 'err'){
          $("#warning").html(r.errors[0]).show();
          $("#pass").val("");
        }
      });
    }

    $(window).load(() => {
      $("#user").focus();
    });

    $("#login").click(() => {
      login();
    });

    $("#login-box .form-group input").on("keypress", (e) => {
      if(e.which == 13){
        login();
      }
    });
  </script>
</body>
