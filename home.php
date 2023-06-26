<?php

include('session.php');
include('modules/system/system.config.php');
include("common_function.class.php");
$cfn = new common_functions();
/*
if (substr($access_rights, 0, 2) === "A+") { -add
if (substr($access_rights, 6, 2) !== "B+") { -browse
if (substr($access_rights, 4, 2) === "D+") { -delete
if (substr($access_rights, 2, 2) === "E+") { -edit\
if (substr($access_rights, 8, 2) === "P+") { -print
if (substr($access_rights, 0, 6) === "A+E+D+"
A+E-D-B+P-
*/
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
    <title><?php echo $cfn->sysconfig('company'); ?></title>
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

#menu_toolbar,#main { position: sticky; top: 0; z-index: 1; }
#grid { overflow: auto; height: 100px; }
.custom-shape-divider-bottom-1679394413 {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    overflow: hidden;
    line-height: 0;
    transform: rotate(180deg);
}

.custom-shape-divider-bottom-1679394413 svg {
    position: relative;
    display: block;
    width: calc(300% + 1.3px);
    height: 373px;
}

.custom-shape-divider-bottom-1679394413 .shape-fill {
    fill: #F15C19;
}
</style>
<body>
<header class="w3-container w3-center w3-black w3-mobile">
    <h6><span id="header" class="w3-wide"><?php if($cfn->sysconfig('company') == 'iWarehouse') echo '<span class="w3-text-orange">i</span>Warehouse'; else echo $cfn->sysconfig('company'); ?></span></h6>
</header>
<div class="custom-shape-divider-bottom-1679394413">
    <svg data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120" preserveAspectRatio="none">
        <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="shape-fill"></path>
        <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="shape-fill"></path>
        <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="shape-fill"></path>
    </svg>
</div>
<div class="w3-animate-left">
    <nav class="sidebar">
        <div class="sidebar_menu"></div>
    </nav>
</div>
<div id="main" class="active">
    <div id="menu_toolbar" style="padding: 4px; border: 1px solid #dfdfdf; border-radius: 3px;"></div>
    <div class="w3-container">
        <div id="grid" style="width: 100%; height: 450px;"></div>
    </div>
</div>

<script type="text/javascript">

    var uname = '<?php echo $_SESSION['name'];?>';
    var ip = '<?php echo $cfn->GetIPAdd(); ?>';
    const system = 'page/system_menu';
    var menu = '<?php echo $_SESSION['system_menu']; ?>';

    $(document).ready(function(){
        var c = $("div#grid");
        var h = window.innerHeight - 100;
        c.css("height", h);

        var c = $(".sidebar");
        var h = window.innerHeight - 20;
        c.css("height", h);
        window.history.pushState(null, "", window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, "", window.location.href);
        };
        get_default();
    });

    /*function get_default(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        destroy_grid();
        $.ajax({
            url: "home",
            success: function (data){
                $("#grid" ).load('dashboard');
                $('#active_program').text('Dashboard');
                w2utils.unlock(div);
            }
        });
    }*/

    function get_default(){
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
                        if(_return.default === "dashboard"){
                            $("#grid" ).load('dashboard');
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
                        $('.sidebar').addClass('open-sidebar');
                        $('#main').addClass('active2');
                        $('.sidebar').html(_return.my_menu);
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
                { type: 'button', id: 'logout', icon: 'fa-solid fa-arrow-right-from-bracket', hint: 'LogOut' }
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
            url: 'home',
            beforeSend: function(){
                closeMenu();
            },
            success: function(data){
                setTimeout(function () {
                    $('#grid').load('dashboard');
                    $('#active_program').text('Dashboard');
                }, 400);
                w2utils.unlock(div);
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
        //$('#active_program').text('');
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        destroy_grid();
        $.ajax({
            url: system,
            type: "post",
            data: {
                cmd: "get-menu",
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
                            $('#grid').load(_return.sys_menu);
                            $('#active_program').text(_return.main_menu).append('&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;').append('<span class="w3-text-blue">'+_return.menu_name+'</span>');
                            $('#'+menu).addClass("submenuactive");
                        }, 400);
                    }else{
                        w2alert("Sorry, No DATA found!");
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
        if(w2ui.hasOwnProperty('journal_grid')){
            w2ui['journal_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('program')){
            w2ui['program'].destroy();
        }
        if(w2ui.hasOwnProperty('activity_grid')){
            w2ui['activity_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('backup_grid')){
            w2ui['backup_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('prog_maint')){
            w2ui['prog_maint'].destroy();
        }
        if(w2ui.hasOwnProperty('user_maint')){
            w2ui['user_maint'].destroy();
        }
        if(w2ui.hasOwnProperty('master_grid')){
            w2ui['master_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('form')){
            w2ui['form'].destroy();
        }
        if(w2ui.hasOwnProperty('master_toolbar')){
            w2ui['master_toolbar'].destroy();
        }
        if(w2ui.hasOwnProperty('att_toolbar')){
            w2ui['att_toolbar'].destroy();
        }
        if(w2ui.hasOwnProperty('att_grid')){
            w2ui['att_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('worksheet_grid')){
            w2ui['worksheet_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('tabs')){
            w2ui['tabs'].destroy();
        }
        if(w2ui.hasOwnProperty('ot_grid')){
            w2ui['ot_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('approve_grid')){
            w2ui['approve_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('ph_grid')){
            w2ui['ph_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('sss_grid')){
            w2ui['sss_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('love_grid')){
            w2ui['love_grid'].destroy();
        }
        if(w2ui.hasOwnProperty('ph_toolbar')){
            w2ui['ph_toolbar'].destroy();
        }
        if(w2ui.hasOwnProperty('sss_toolbar')){
            w2ui['sss_toolbar'].destroy();
        }
        if(w2ui.hasOwnProperty('love_toolbar')){
            w2ui['love_toolbar'].destroy();
        }
        if(w2ui.hasOwnProperty('profile_form')){
            w2ui['profile_form'].destroy();
        }
    }

    function logout(){
        window.location.href = 'page/logout';
    }

    /*let list = document.querySelectorAll('.list');
    for (let i=0; i<list.length; i++){
        list[i].onclick = function(){
            let j = 0;
            while(j < list.length){
                list[j++].className = 'list';

            }
            list[i].className = 'list active';
        }
    }*/
</script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
