<?php

include('session.php');
include('modules/system/system.config.php');
include("common_function.class.php");
$cfn = new common_functions();

$title = mysqli_query($con,"SELECT * FROM _sysconfig WHERE isDefault") or die (mysqli_error($con));
    while ($row=mysqli_fetch_array($title)){
    $header = $row['config_title'];
    if($header == 'iWarehouse'){
        $title1 = '<span class="w3-text-orange">i</span>Warehouse';
    }else{
        $title1 = $header;
    }
    $header1 = $row['config_value'];
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
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    clifford: '#da373d',
                }
            }
        }
    }
    </script>
    <link rel="stylesheet" type="text/css" href="css/w2ui.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-colors-flat.css">
    <link rel="stylesheet" type="text/css" href="css/sidebar.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <title><?php echo $header1 ?></title>
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
</style>
<body>
<header class="w3-container w3-center w3-black w3-mobile">
    <h6><span id="header" class="w3-wide"><?php echo $title1; ?></span></h6>
</header>
<div class="w3-animate-left">
    <nav class="sidebar">
        <?php include('function/system_menu.php'); ?>
        <ul>
            <li class="active">
                <a onclick="dashboard()" id="dashboard" class="menu-btn">
                    <span class="icon"><ion-icon name="home-outline"></ion-icon></span>
                    <span class="menu_title">Dashboard</span>
                </a>
            </li>
            <?php
                $user = check_access($session_name);
                $getmenu = getMenu($user['user_level'],$user['user_no']);
                foreach($getmenu as $menu){
                ?>
            <li>
                <a onclick="show_hide(this.id)" id="<?php echo $menu['class']; ?>" class="menu-btn">
                    <span class="icon"><?php echo $menu['icon'];?></span>
                    <span class="menu_title"><?php echo $menu['parent_name'];?></span>
                    <span class="fas fa-caret-down arrow" id="icon_<?php echo $menu['class']; ?>"></span>
                </a>
                <?php
                    $sub_menu = getSubMenu($menu['parent_no'],$user['user_level'],$user['user_no']);
                    $count_submenu = count($sub_menu);
                    if($count_submenu >=1){
                    ?>
                    <ul id="<?php echo $menu['class']; ?>_system">
                    <?php
                        foreach ($sub_menu as $key ){
                    ?>
                        <li>
                            <a onclick="system_menu(<?php echo $key['program_code']; ?>)" id="<?php echo $key['program_code']; ?>" class="submenu">
                                <?php echo $key['menu_name']; ?>
                            </a>
                        </li>
                    <?PHP } ?>
                    </ul>
                <?php } ?>
            </li>
            <?php } ?>
        </ul>
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

    function get_default(){
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
    }

    function openMenu(){
        $('.sidebar').addClass('open-sidebar');
        $('#main').addClass('active2');
    }

    function closeMenu(){
        $('.sidebar').removeClass('open-sidebar');
        $('.sidebar').addClass('w3-animate-right');
        $('#main').removeClass('active2');
        w2ui['menu_toolbar'].show('openMenu');
        w2ui['menu_toolbar'].hide('closeMenu');
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
                        this.hide('openMenu');
                        this.show('closeMenu');
                        break;
                    case 'closeMenu':
                        closeMenu();
                        this.show('openMenu');
                        this.hide('closeMenu');
                        break;
                    case 'logout':
                        logout();
                        break;
                    case 'home':
                        get_default();
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

    let list = document.querySelectorAll('.list');
    for (let i=0; i<list.length; i++){
        list[i].onclick = function(){
            let j = 0;
            while(j < list.length){
                list[j++].className = 'list';

            }
            list[i].className = 'list active';
        }
    }
</script>
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
