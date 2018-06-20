<?php
  define("SITE_ROOT", '.');
  require(SITE_ROOT.'/includes/includes.php');
?>
<head>
  <title>Change Password - TCGLink</title>
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
      <h3>Change Password</h3>
      <div class="form-group">
        <label for="old-pass">Old Password: </label>
        <input type="password" id="old-pass" />
      </div>
      <div class="form-group">
        <label for="new-pass-1">New Password: </label>
        <input type="password" id="new-pass-1" />
      </div>
      <div class="form-group">
        <label for="new-pass-2">Confirm Password: </label>
        <input type="password" id="new-pass-2" />
      </div>
      <button id="change">Change</button>
    </div>
    <div id="note">Passwords must be at least 8 characters long and must include at least one letter and one number.</div>
    <div id="warning"></div>
  </div>
  <script>
    $("#change").click(verify);
    $("input").on("keyup", (e) => {
      if(e.which === 13) verfiy();
    });

    function verify(){
      let oldPass = $("#old-pass").val();
      let newPass = $("#new-pass-1").val();
      let confPass = $("#new-pass-2").val();

      if(newPass.length < 8){
        $("#warning").html("Password must be at least 8 characters long.").show();
      } else if(!newPass.match(/[a-z,A-Z]/) || !newPass.match(/\d/)){
        $("#warning").html("Password must contain at least one letter and at least one number.").show();
      } else if(newPass != confPass){
        $("#warning").html("Passwords do not match.").show();
      } else {
        let params = {
          old: oldPass,
          new: newPass
        };
        $.post("./password.php", JSON.stringify(params), (r) => {
          if(r.status === 'ok'){
            $("#warning").hide();
            $("#note").html("Password changed successfully. You will be redirected in 5 seconds.");
            setTimeout(() => {window.location.href = '<?= isset($_GET['ref']) ? $_GET['ref'] : 'index.php' ?>'}, 5000);
          } else if(r.status === 'err'){
            $("#warning").html(r.errors[0]).show();
          }
        });
      }
    }
  </script>
</body>
