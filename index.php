<?php
include('system.config.php');
include("common_functions.php");
$cfn = new common_functions();
$store = $cfn->sysconfig("company");
if(!isset($_SESSION["name"])){
    $_SESSION["name"] = "";
}
if(!isset($_SESSION["system_menu"])){
    $_SESSION["system_menu"] = "";
}
?>
<!DOCTYPE html>
</html>
<html  moznomarginboxes mozdisallowselectionprint>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/w2ui.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" type="text/css" href="css/w2ui.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-colors-flat.css">
    <link rel="stylesheet" type="text/css" href="css/sidebar.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <title>
        <?php echo $store; ?>
    </title>
</head>
<style>
.w2ui-field input {
    width: 500px;
}
.w2ui-field > div > span {
    margin-left: 20px;
}
.my-container {height: 600px;}
.w2ui-col-header, .w2ui-panel-title, .w2ui-head {text-align: center;}
.w2ui-node-dots { display: none;}
.w2ui-grid-summary{ font-weight: bolder;}
#menu_toolbar,#main { position: sticky; top: 0; z-index: 1; }
#grid { overflow: auto; height: 100px; }
.custom-shape-divider-bottom-1696286796 {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
    transform: rotate(180deg);
}

.custom-shape-divider-bottom-1696286796 svg {
    position: relative;
    display: block;
    width: calc(132% + 1.3px);
    height: 183px;
}

.custom-shape-divider-bottom-1696286796 .shape-fill {
    fill: #F77D22;
}
</style>
<body>
<header class="w3-container w3-center w3-black w3-mobile w3-hide" id="my-header">
    <h6><span id="header" class="w3-wide"><?php echo $store; ?></span></h6>
</header>
<div class="w3-animate-left w3-hide" id="sidebar">
    <nav class="sidebar">
        <div class="sidebar_menu"></div>
    </nav>
</div>
<div class="custom-shape-divider-bottom-1696286796">
    <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
        <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="shape-fill"></path>
        <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="shape-fill"></path>
        <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="shape-fill"></path>
    </svg>
</div>
<div id="main" class="active">
    <div id="menu_toolbar" class="w3-hide" style="padding: 4px; border: 1px solid #dfdfdf;"></div>
    <div id="grid" style="width: 100%; height: 450px;"></div>
</div>

<script type="text/javascript">

    var uname = '<?php echo $_SESSION["name"]; ?>';
    var ip = '<?php echo $cfn->GetIPAdd(); ?>';
    var menu = '<?php echo $_SESSION['system_menu']; ?>';
    var src;

    $(document).ready(function(){
        var c = $("div#grid");
        var h = window.innerHeight - 100;
        c.css("height", h);
        var g = $(".sidebar");
        var h = window.innerHeight - 20;
        g.css("height", h);
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, "", window.location.href);
        };
        get_default();
    });

    function get_default(){
        $.ajax({
            url: 'page/system.php',
            type: "post",
            data: {
                cmd: "default"
            },
            dataType: "json",
            success: function (data) {
                $(".w2ui-scroll-right").removeClass("w3-hide");
                if (data !== "") {
                    if(data.status === "success"){
                        $('div#grid').html(data.data);
                    }else{
                        $("div#sidebar").removeClass("w3-hide");
                        $("div#menu_toolbar").removeClass("w3-hide");
                        $("#my-header").removeClass("w3-hide");
                        get_home();
                    }
                }else{
                    w2alert("Sorry, There was a problem in server connection!");
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function get_home(){
        destroy_grid();
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        destroy_grid();
        $.ajax({
            url: "page/system_menu.php",
            type: "post",
            data: {
                cmd: "default"
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        if(_return.default === "" || _return.default === "dashboard"){
                            $("#grid" ).load('dashboard.php');
                            $('#active_program').text('Dashboard');
                            w2utils.unlock(div);
                        }else{
                            system_menu(_return.default);
                        }
                    }
                }
            }
        });
    }

    function login(){
        if($('#username').val() === ""){
            $('#username').focus();
        }else if($('#password').val() === ""){
            $('#password').focus();
        }else{
            $('#login_text').text('Validating');
            $('#login_btn').prop('disabled', true);
            $('#username').prop('disabled', true);
            $('#password').prop('disabled', true);
            if($.isNumeric($('#username').val())){
                $.ajax({
                    url: 'page/system.php',
                    type: 'post',
                    data: { 
                        cmd: 'check_account_id',
                        uid : $('#username').val()
                    },
                    dataType: "json",
                    success: function (data) {
                        if(data.status === "success"){
                            if(data.message == "make_user"){
                                $('#login_form').addClass('w3-hide');
                                $('#reg_form').removeClass('w3-hide');
                            }else{
                                w2alert(data.message);
                                $('#username').val("");
                                $('#password').val("");
                                $('#make_uid').val("");
                                $('#cfn_password').val("");
                                $('#login_text').text('CONTINUE');
                                $('#login_btn').prop('disabled', false);
                                $('#username').prop('disabled', false);
                                $('#password').prop('disabled', false);
                            }
                        }else{
                            w2alert(data.message);
                            $('#username').val("");
                            $('#password').val("");
                            $('#login_text').text('CONTINUE');
                            $('#login_btn').prop('disabled', false);
                            $('#username').prop('disabled', false);
                            $('#password').prop('disabled', false);
                        }
                    },
                    error: function (){
                        w2alert("Sorry, There was a problem in server connection!");
                        $('#login_text').text('CONTINUE');
                        $('#login_btn').prop('disabled', false);
                        $('#username').prop('disabled', false);
                        $('#password').prop('disabled', false);
                    }
                });
            }else{
                $.ajax({
                    url: 'page/system.php',
                    type: 'post',
                    data: { 
                        cmd: 'login',
                        uname : $('#username').val(),
                        pword : $('#password').val()
                    },
                    dataType: "json",
                    success: function (data) {
                        if(data.status === "success"){
                            window.location.reload();
                        }else{
                            w2alert(data.message);
                            $('#login_text').text('CONTINUE');
                            $('#login_btn').prop('disabled', false);
                            $('#username').prop('disabled', false);
                            $('#password').prop('disabled', false);
                        }
                    },
                    error: function (){
                        w2alert("Sorry, There was a problem in server connection!");
                        $('#login_text').text('CONTINUE');
                        $('#login_btn').prop('disabled', false);
                        $('#username').prop('disabled', false);
                        $('#password').prop('disabled', false);
                    }
                });
            }
        }
    }

    function register(){
        if($('#make_uid').val() === ""){
            $('#make_uid').focus();
        }else if($('#cfn_password').val() === ""){
            $('#cfn_password').focus();
        }else{
            $('#reg_text').text('REGISTERING...');
            $('#reg_btn').prop('disabled', true);
            $('#make_uid').prop('disabled', true);
            $('#cfn_password').prop('disabled', true);
            $.ajax({
                url: 'page/system.php',
                type: 'post',
                data: { 
                    cmd: 'register-user',
                    uid : $('#username').val(),
                    uname: $('#make_uid').val(),
                    pword : $('#cfn_password').val(),
                    pword1 : $('#password').val()
                },
                dataType: "json",
                success: function (data) {
                    if(data.status === "success"){
                        window.location.reload();
                    }else{
                        w2alert(data.message);
                    }
                    $('#username').val("");
                    $('#password').val("");
                    $('#make_uid').val("");
                    $('#cfn_password').val("");
                    $('#reg_text').text('REGISTER');
                    $('#reg_btn').prop('disabled', false);
                    $('#make_uid').prop('disabled', false);
                    $('#cfn_password').prop('disabled', false);
                },
                error: function (){
                    w2alert("Sorry, There was a problem in server connection!");
                    $('#username').val("");
                    $('#password').val("");
                    $('#make_uid').val("");
                    $('#cfn_password').val("");
                    $('#reg_text').text('REGISTER');
                    $('#reg_btn').prop('disabled', false);
                    $('#make_uid').prop('disabled', false);
                    $('#cfn_password').prop('disabled', false);
                }
            });
        }
    }

    function openMenu(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/system_menu.php",
            type: "post",
            data: {
                cmd: "check-user"
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        getMenu();
                        w2utils.unlock(div);
                    }else{
                        w2utils.unlock(div);
                        w2alert(_return.message);
                    }
                }
            },
            error: function (){
                w2alert("Sorry, There was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function getMenu(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/system_menu.php",
            type: "post",
            data: {
                cmd: "get-myMenu"
            },
            success: function (data){
                if(data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        w2ui['menu_toolbar'].hide('openMenu');
                        w2ui['menu_toolbar'].show('closeMenu');
                        $('nav.sidebar').addClass('open-sidebar');
                        $('#main').addClass('active2');
                        $('nav.sidebar').html(_return.my_menu);
                        w2utils.unlock(div);
                        if(_return.menu_open !== 0){
                            $('nav ul li a#'+_return.menu_open).click();
                            $('nav ul li a#'+_return.menu_open).addClass("active").siblings().removeClass("active");
                            $('nav ul li a#'+_return.menu_open).siblings().children("ul").hide("fast");
                            if($('nav ul li ul#'+_return.menu_open+'_system').is(":hidden")){
                                $('nav ul li ul#'+_return.menu_open+'_system').show(); 
                                $('nav ul li a span#icon_'+_return.menu_open).toggleClass("rotate");
                                $('nav ul li ul li a#'+_return.active_menu).addClass('active');
                            }
                        }
                    }
                }
            },
            error: function (){
                w2alert("Sorry, There was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function closeMenu(){
        $('.sidebar').removeClass('open-sidebar');
        $('.sidebar').addClass('w3-animate-right');
        $('#main').removeClass('active2');
        w2ui['menu_toolbar'].show('openMenu');
        w2ui['menu_toolbar'].hide('closeMenu');
    }


    //toolbar
    $(function () {
        $('#menu_toolbar').w2toolbar({
            name: 'menu_toolbar',
            items: [
                { type: 'button', id: 'openMenu', icon: 'fa-solid fa-bars' },
                { type: 'button', id: 'closeMenu', icon: 'fa-solid fa-xmark', hidden: true },
                { type: 'break' },
                { type: 'button', id: 'home', icon: 'fa-solid fa-house', hint: 'Dashboard' },
                { type: 'spacer' },
                { type: 'html',  id: 'system', html: '<div class="w3-padding-top w3-padding-bottom"><b><span id="active_program" class="w3-text-orange"></span></b></div>' },
                { type: 'break' },
                { type: 'html',  id: 'username', html: '<div class="w3-padding-top w3-padding-bottom"><i class="fa-solid fa-user-tie"></i>&nbsp;'+uname+'</div>' },
                { type: 'break' },
                { type: 'html', id: 'ip', html: '<div class="w3-padding-top w3-padding-bottom"><i class="fa-solid fa-desktop"></i>&nbsp;'+ip+'</div>' },
                { type: 'break' },
                { type: 'button', id: 'logout', icon: 'fa-solid fa-arrow-right-from-bracket', hint: 'Logout' }
            ],
            onClick: function (event) {
                switch (event.target) {
                    case 'openMenu':
                        openMenu();
                        break;
                    case 'closeMenu':
                        closeMenu();
                        break;
                    case 'logout':
                        logout();
                        break;
                    case 'home':
                        dashboard();
                        break;
                }
            }
        });
    });


    //side functions
    function dashboard(){
        $('nav ul li').click(function(){
            $(this).addClass("active").siblings().removeClass("active");
        });
        $('#active_program').text('');
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        destroy_grid();
        $.ajax({
            url: 'index.php',
            beforeSend: function(){
                closeMenu();
            },
            success: function(data){
                setTimeout(function () {
                    $('#grid').load('dashboard.php');
                    $('#active_program').text('Dashboard');
                }, 100);
                w2utils.unlock(div);
            },
            complete: function(){
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, There was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function show_hide(menu_id){
            $('.arrow').removeClass("rotate");
            $('nav ul li').click(function(){
                $(this).addClass("active").siblings().removeClass("active");
                $(this).siblings().children("ul").hide("fast");
            });
            if($('#'+menu_id+'_system').is(":hidden")){
                $('#'+menu_id+'_system').show(); 
                $('#icon_'+menu_id).toggleClass("rotate");
                $('nav ul li ul li.active').removeClass('active');
            }else{  
                $('#'+menu_id+'_system').hide();
                $('#icon_'+menu_id).toggleClass("rotate");
                $('nav ul li ul li.active').removeClass('active');
            }
        }

    function system_menu(menu){
        $('.submenu').removeClass('submenuactive');
        $('#active_program').text('');
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        destroy_grid();
        $.ajax({
            url: 'page/system_menu.php',
            type: "post",
            data: {
                cmd: "fire-menu",
                menu_id : menu
            },
            beforeSend: function(){
                closeMenu();
            },
            success: function(data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        setTimeout(function () {
                            $('#grid').load(_return.fired_menu);
                            $('#active_program').text(_return.parent_name).append('&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;').append('<span class="w3-text-blue">'+_return.menu_name+'</span>');
                            $('#'+menu).addClass("submenuactive");
                            src = _return.src;
                        }, 400);
                    }else{
                        w2alert(_return.message);
                        w2utils.unlock(div);
                    }
                }
            },
            complete: function(){
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        })
    }

    function destroy_grid(){
        if(w2ui.hasOwnProperty('grid')){
            w2ui['grid'].destroy();
        }
        if(w2ui.hasOwnProperty('my_grid')){
            w2ui['my_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('my_grid1')){
            w2ui['my_grid1'].destroy();
        }
        if(w2ui.hasOwnProperty('my_toolbar')){
            w2ui['my_toolbar'].destroy();
        }
        if(w2ui.hasOwnProperty('form')){
            w2ui['form'].destroy();
        }
        if(w2ui.hasOwnProperty('tabs')){
            w2ui['tabs'].destroy();
        }
        if(w2ui.hasOwnProperty('layout')){
            w2ui['layout'].destroy();
        }
    }

    function logout(){
        window.location.href = 'logout.php';
    }
</script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
