<!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/w2ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/w2ui.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
    <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>iWarehouse</title>
</head>
<style type="text/css">
    body {
        background-color: black;
    }
    .w3-input{
        border-color:#ffffff!important;
    }
</style>
<body>
<div class="w3-container w3-padding w3-responsive w3-mobile" id="index">
    <div class="w3-panel w3-round-large w3-border" style="border-color:#ffffff!important;">

        <div class="w3-row w3-col l8 w3-padding w3-border-right" style="height: auto;">
            <div class="w3-container w3-center">
                <img src="logo.jpg" alt="logo" width="400" height="500"> 
            </div>
        </div>

        <div class="w3-row-half w3-col l4 w3-padding" style="height: auto; border-color:#ffffff!important;">
            <div class="w3-panel w3-xlarge">
                <article style="font-family: Arial, sans-serif;" id="form_header" class="w3-text-white"><b>Sign In</b></article>
            </div>
            <div id="reg_login_form_div">
                <!--login form-->
                <form class="w3-container" action="" method="post" id="login_form" autocomplete="off">
                    <span id="err_login"></span>
                    <p>
                        <label class="w3-label w3-small w3-text-white">Username</label>
                        <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent w3-text-white" id="username" name="username" type="text" autocomplete="off" required=""/>
                    </p>
                    <p>
                        <label class="w3-label w3-small w3-text-white">Password</label>
                        <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent w3-text-white" id="password" name="password" type="password" autocomplete="off" required="" />
                    </p>
                    <div class="w3-bar w3-margin-top w3-center">
                        <button name="login" type="submit" id="login_btn" class="w3-button w3-block w3-padding w3-center" style="background-color:#ff7537">
                            <span class="w3-small w3-wide" id="login_text">CONTINUE</span>
                        </button>
                    </div>
                </form>

                <!--login form-->
                <form class="w3-container w3-hide" action="" method="post" id="reg_form" autocomplete="off">
                    <p>
                        <label class="w3-label w3-small w3-text-white">Make Username</label>
                        <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent w3-text-white" id="make_uid" name="make_uid" type="text" autocomplete="off" required=""/>
                    </p>
                    <p>
                        <label class="w3-label w3-small w3-text-white" id="pwd_label">Confirm Password</label>
                        <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent w3-text-white" id="cfn_password" name="cfn_password" type="password" autocomplete="off" required="" />
                    </p>
                    <div class="w3-bar w3-margin-top w3-center">
                        <button name="reg" type="submit" id="reg_btn" class="w3-button w3-block w3-padding w3-center" style="background-color:#ff7537">
                            <span class="w3-small w3-wide" id="reg_text">SUBMIT</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<script type="text/javascript">
$(document).ready(function(){
    
    $('#login_form').on('submit', function(event){
        if($('#username').val() == '' && $('#password').val() == ''){
            $('#username').focus();
            $('#password').focus();
        }else{
            event.preventDefault();
            if($.isNumeric($('#username').val())){
                $.ajax({
                    url: 'page/login',
                    type: 'post',
                    data: { 
                        cmd: 'check_account_id',
                        uid : $('#username').val()
                    },
                    beforeSend: function(){
                        $('#login_text').text('Validating');
                        $('#login_btn').prop('disabled', true);
                        $('#username').prop('disabled', true);
                        $('#password').prop('disabled', true);
                    },
                    success: function (data) {
                        if (data == "make_user"){
                            $('#login_form').addClass('w3-hide');
                            $('#reg_form').removeClass('w3-hide');
                        }else{
                            $('#err_login').html(data);
                            $('#username').val('');
                            $('#password').val('');
                        }
                        setTimeout(function(){
                            $('#err_login').text('');
                        },3000);
                        $('#login_text').text('CONTINUE');
                        $('#login_btn').prop('disabled', false);
                        $('#username').prop('disabled', false);
                        $('#password').prop('disabled', false);
                    }
                });
            }else{
                $.ajax({
                    url: 'page/login',
                    type: 'post',
                    data: { 
                        cmd: 'login',
                        uname : $('#username').val(),
                        pword : $('#password').val()
                    },
                    beforeSend: function(){
                        $('#login_text').text('Validating');
                        $('#login_btn').prop('disabled', true);
                        $('#username').prop('disabled', true);
                        $('#password').prop('disabled', true);
                    },
                    success: function (data) {
                        if (data == "success"){
                            window.location = "home";
                        }else{
                            $('#err_login').html(data);
                        }
                        setTimeout(function(){
                            $('#err_login').text('');
                        },3000);
                        $('#login_text').text('CONTINUE');
                        $('#login_btn').prop('disabled', false);
                        $('#username').prop('disabled', false);
                        $('#password').prop('disabled', false);
                    }
                });
            }
        }
    });


    //reg
    $('#reg_form').on('submit',function(event){
        event.preventDefault();
        $.ajax({
            url: 'page/login.php',
            type: 'post',
            data: { 
                cmd: 'register_user',
                uid : $('#username').val(),
                uname: $('#make_uid').val(),
                pword : $('#cfn_password').val(),
                pword1 : $('#password').val()
            },
            beforeSend: function(){
                $('#reg_text').text('Registering');
                $('#reg_btn').prop('disabled', true);
                $('#username').prop('disabled', true);
                $('#make_uid').prop('disabled', true);
                $('#password').prop('disabled', true);
                $('#cfn_password').prop('disabled', true);
            },
            success: function (data) {
                if (data == "success"){
                    window.location = "./home";
                }else{
                    $('#err_login').html(data);
                    $('#make_uid').val('');
                    $('#password').val('');
                    $('#cfn_password').val('');
                }
                setTimeout(function(){
                    $('#err_login').text('');
                },3000);
                $('#reg_text').text('SUBMIT');
                $('#reg_btn').prop('disabled', false);
                $('#username').prop('disabled', false);
                $('#make_uid').prop('disabled', false);
                $('#password').prop('disabled', false);
                $('#cfn_password').prop('disabled', false);
                $('#login_form').removeClass('w3-hide');
                $('#reg_form').addClass('w3-hide');
            }
        });
    });
});
</script>