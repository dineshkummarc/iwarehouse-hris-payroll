<?php
require_once('../system.config.php');
include("../common_functions.php");
$cfn = new common_functions();

if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_POST["cmd"]) {
                case "fire-menu":
                    $menu_id = $_POST['menu_id'];
                    fire_menu($menu_id);
                break;
                case "get-myMenu":
                    if($_SESSION['system_menu'] === 0){
                        $actv = 0;
                    }else{
                        $actv = $_SESSION['system_menu'];
                    }
                    if($_SESSION['system_open'] === 0){
                        $_SESSION['system_open'] = 0;
                        echo json_encode(array("status" => "success", "menu_open" => $_SESSION['system_open'], "my_menu" => get_my_menu(), "active_menu" => $actv));
                    }else{
                        echo json_encode(array("status" => "success", "menu_open" => $_SESSION['system_open'], "my_menu" => get_my_menu(), "active_menu" => $actv));
                    }
                break;
                case "check-user":
                    echo get_user();
                break;
                case "default":
                    if($_SESSION['system_menu'] === 0)
                        echo json_encode(array("status" => "success", "default" => "dashboard"));
                    else
                        echo json_encode(array("status" => "success", "default" => $_SESSION['system_menu']));
                    
                break;
                case "make-default":
                    $_SESSION['system_open'] = $_POST['this_menu'];
                    if($_POST['this_menu'] === "")
                        echo json_encode(array("status" => "error", "menu_id" => "Error"));
                    else
                        echo json_encode(array("status" => "success", "menu_id" => $_SESSION['system_open']));
                    
                break;
                case "refresh-graph":
                    $_SESSION["graph_fr"] = (new DateTime($_POST["graph_fr"]))->format("Y-m-d");
                    $_SESSION["graph_to"] = (new DateTime($_POST["graph_to"]))->format("Y-m-d");
                    echo json_encode(array("status" => "success", "message" => "ok"));
                break;
            }
            $db->commit();
            return false;
        }
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(array("status" => "error", "message" => "Database error!", "e" => $e));
        exit();
    }
}

function get_user(){
    global $db, $db_hris;

    $_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `security_key`=:skey AND `user_id`=:uid AND `is_active` LIMIT 1");
    $_user->execute(array(":uid" => $_SESSION['name'], ":skey" => $_SESSION["security_key"]));
    if($_user->rowCount()){
        $user_data = json_encode(array("status" => "success", "message" => "Ok"));
    }else{
        $user_data = json_encode(array("status" => "error", "message" => "Please login again!!"));
    }
    return $user_data;
}

function fire_menu($menu_id){
    global $db, $db_hris, $cfn;

    $qmenu = $db->prepare("SELECT `_program`.`menu_name`,`_program`.`page`,`_program`.`function`,`_program_parent`.`parent_name`,`_program_parent`.`parent_data` FROM $db_hris.`_program`,$db_hris.`_program_parent` WHERE `_program`.`program_parent_no`=`_program_parent`.`parent_no` AND `_program`.`program_code`=:pcode");
    $qmenu->execute(array(":pcode"=> $menu_id));
    if ($qmenu->rowCount()) {
        $sys_menu = $qmenu->fetch(PDO::FETCH_ASSOC);
        $_SESSION['system_open'] = $sys_menu["parent_data"];
        $_SESSION['system_menu'] = $menu_id;
        $cfn->log_activity($sys_menu["menu_name"]);
        echo json_encode(array("status" => "success", "fired_menu" => $sys_menu["page"], "menu_name" => $sys_menu["menu_name"], "parent_name" => $sys_menu["parent_name"], "menu_open" => $_SESSION['system_open'], "sys_menu" => $_SESSION['system_menu'], "src" => $sys_menu["function"]));
    }
}

function get_my_menu(){
    $data = '<ul>
                <li class="active">
                    <a onclick="dashboard()" id="dashboard" class="menu-btn">
                        <span class="icon"><ion-icon name="home-outline"></ion-icon></span>
                        <span class="menu_title">Dashboard</span>
                    </a>
                </li>';
                $user = check_access($_SESSION['name']);
                $getmenu = getMenu($user['user_level'],$user['user_id']);
                foreach($getmenu as $menu){
    $data .= '  <li>
                    <a onclick="show_hide(this.id)" id="'.$menu['class'].'" class="menu-btn">
                        <span class="icon">'.$menu['icon'].'</span>
                        <span class="menu_title">'.$menu['parent_name'].'</span>
                        <span class="fas fa-caret-down arrow" id="icon_'.$menu['class'].'"></span>
                    </a>';
                    $sub_menu = getSubMenu($menu['parent_no'],$user['user_level'],$user['user_id']);
                    $count_submenu = count($sub_menu);
                    if($count_submenu >= 1){
    $data .= '          <ul id="'.$menu['class'].'_system">';
                        foreach ($sub_menu as $key ){
    $data .='               <li id="active_'.$key['program_code'].'" class="">
                                <a onclick="system_menu('.$key['program_code'].')" id="'.$key['program_code'].'" class="submenu">
                                '.$key['menu_name'].'
                                </a>
                            </li>';
                        }
    $data .='           </ul>';
                    }
    $data .='   </li>';
                }
    $data.='</ul>';

    return $data;
}

function check_access($session_name){
    global $db, $db_hris;

    $check_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `security_key`=:skey AND `user_id` LIKE :user_id LIMIT 1");
    $check_user->execute(array(":user_id" => $session_name, ":skey" => $_SESSION["security_key"]));
    if($check_user->rowCount()){
        $check_user_data = $check_user->fetch(PDO::FETCH_ASSOC);
        $user_data = $check_user_data;
    }
    return $user_data;
}

function getMenu($user_level,$user_id){
    global $db, $db_hris;
    
    $menu_data = array();
    if($user_level >= 7){
        $menu = $db->prepare("SELECT * FROM $db_hris.`_program_parent`,$db_hris.`_program` WHERE `_program_parent`.`isActive` AND `_program`.`program_parent_no`=`_program_parent`.`parent_no` GROUP BY `_program_parent`.`parent_no` ORDER BY `_program_parent`.`sequence` ASC");
        $menu->execute();
        if ($menu->rowCount()) {
            while ($data = $menu->fetch(PDO::FETCH_ASSOC)) {
                $menu_data[] = array("parent_no" => $data["parent_no"], "parent_name" => $data["parent_name"], "class" => $data["parent_data"], "icon" => $data["icon"]);
            }
        }
        $data = $menu_data;
    }else{
        $menu = $db->prepare("SELECT * FROM $db_hris.`_program_parent`,$db_hris.`_user_access`,$db_hris.`_program` WHERE `_program_parent`.`isActive` AND `_user_access`.`program_code`=`_program`.`program_code` AND !`_user_access`.`is_hold` AND `_program`.`program_parent_no`=`_program_parent`.`parent_no` AND `_user_access`.`user_id` LIKE :uid AND `_program`.`program_level`<= :level GROUP BY `_program_parent`.`parent_no` ORDER BY `_program_parent`.`sequence` ASC");
        $menu->execute(array(":uid"=> $user_id, ":level" => $user_level));
        if ($menu->rowCount()) {
            while ($data = $menu->fetch(PDO::FETCH_ASSOC)) {
                $menu_data[] = array("parent_no" => $data["parent_no"], "parent_name" => $data["parent_name"], "class" => $data["parent_data"], "icon" => $data["icon"]);
            }
        }
        $data = $menu_data;
    }
    return $data;
}

function getSubMenu($parent_no,$user_level,$uid){
    global $db, $db_hris;

    $submenu_data = array();
    if($user_level >= 7){
        $submenu = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_parent_no`=:id AND `is_active` AND `program_level`!=0  ORDER BY `seq` ASC");
        $submenu->execute(array(":id"=> $parent_no));
        if ($submenu->rowCount()) {
            while ($subdata = $submenu->fetch(PDO::FETCH_ASSOC)) {
            $submenu_data[] = array("program_code" => $subdata["program_code"], "menu_name" => $subdata["menu_name"], "function" => $subdata["page"]);
            }
        }
        $data = $submenu_data;
    }else{
        $submenu = $db->prepare("SELECT * FROM $db_hris.`_user_access`,$db_hris.`_program` WHERE `_program`.`is_active` AND `_user_access`.`user_id` LIKE :uid  AND !`_user_access`.`is_hold` AND `_program`.`program_level`<= :ulvl AND `_user_access`.`program_code`=`_program`.`program_code` AND `_program`.`program_level`!=0 AND `_program`.`program_parent_no`=:parent_id ORDER BY `_program`.`seq` ASC");
        $submenu->execute(array(":uid"=> $uid, ":parent_id" => $parent_no, ":ulvl" => $user_level));
        if ($submenu->rowCount()) {
            while ($subdata = $submenu->fetch(PDO::FETCH_ASSOC)) {
                $submenu_data[] = array("program_code" => $subdata["program_code"], "menu_name" => $subdata["menu_name"], "function" => $subdata["page"]);
            }
        }
        $data = $submenu_data;
    }
    return $data;
}
