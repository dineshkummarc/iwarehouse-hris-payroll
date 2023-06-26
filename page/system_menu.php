<?php

require_once('../common/functions.php');
include("../common_function.class.php");

$cfn = new common_functions();
switch ($_POST["cmd"]) {
    case "get-menu":
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

function get_user(){
    global $db, $db_hris;

    $_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `security_key`=:skey AND `user_no`=:uid AND `is_active` LIMIT 1");
    $_user->execute(array(":uid" => $_SESSION['user_id'], ":skey" => md5($_SESSION["security_key"])));
    if($_user->rowCount()){
        $user_data = json_encode(array("status" => "success", "message" => "Ok"));
    }else{
        $user_data = json_encode(array("status" => "error", "message" => "Please login again!!"));
    }
    return $user_data;
}

function fire_menu($menu_id){
    global $db, $db_hris;

    $qmenu = $db->prepare("SELECT `_program`.`menu_name`,`_program`.`function`,`_program_parent`.`parent_name`,`_program_parent`.`parent_data` FROM $db_hris.`_program`,$db_hris.`_program_parent` WHERE `_program`.`program_parent`=`_program_parent`.`parent_no` AND `_program`.`program_code`=:pcode");
    $qmenu->execute(array(":pcode"=> $menu_id));
    if ($qmenu->rowCount()) {
        while ($sys_menu = $qmenu->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['system_open'] = $sys_menu["parent_data"];
            
            $menu_name = $sys_menu["menu_name"];
            $main_menu = $sys_menu["parent_name"];
            if($sys_menu["function"] == "function/profile.php")
                $menu = $sys_menu["function"];
            else
                $menu = substr($sys_menu["function"],0 ,-4);
        }
        $_SESSION['system_menu'] = $menu_id;
    }
    echo json_encode(array("status" => "success", "sys_menu" => $menu, "menu_name" => $menu_name, "main_menu" => $main_menu, "menu_open" => $_SESSION['system_open']));
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
                $getmenu = getMenu($user['user_level'],$user['user_no']);
                foreach($getmenu as $menu){
    $data .= '  <li>
                    <a onclick="show_hide(this.id)" id="'.$menu['class'].'" class="menu-btn">
                        <span class="icon">'.$menu['icon'].'</span>
                        <span class="menu_title">'.$menu['parent_name'].'</span>
                        <span class="fas fa-caret-down arrow" id="icon_'.$menu['class'].'"></span>
                    </a>';
                    $sub_menu = getSubMenu($menu['parent_no'],$user['user_level'],$user['user_no']);
                    $count_submenu = count($sub_menu);
                    if($count_submenu >=1){
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

    $check_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `security_key`=:skey AND `user_id`=:user_id LIMIT 1");
    $check_user->execute(array(":user_id" => $session_name, ":skey" => md5($_SESSION["security_key"])));
    if($check_user->rowCount()){
        $check_user_data = $check_user->fetch(PDO::FETCH_ASSOC);
        $user_data = $check_user_data;
    }
    return $user_data;
}

function getMenu($user_level,$uid){
    global $db, $db_hris;
    
    $menu_data = array();
    if($user_level >= 8){
        $menu = $db->prepare("SELECT * FROM $db_hris.`_program_parent`,$db_hris.`_program` WHERE `_program_parent`.`isActive` AND `_program`.`program_parent`=`_program_parent`.`parent_no` AND `_program`.`program_level`!=:def AND `_program`.`program_level`<=:level GROUP BY `_program_parent`.`parent_no` ORDER BY `sequence` ASC");
        $menu->execute(array(":level" => $user_level, ":def" => 0));
        if ($menu->rowCount()) {
            while ($data = $menu->fetch(PDO::FETCH_ASSOC)) {
                $menu_data[] = array("parent_no" => $data["parent_no"], "parent_name" => $data["parent_name"], "class" => $data["parent_data"], "icon" => $data["icon"]);
            }
        }
        $data = $menu_data;
    }else{
        $menu = $db->prepare("SELECT * FROM $db_hris.`_program_parent`,$db_hris.`_user_access`,$db_hris.`_program` WHERE `_program_parent`.`isActive` AND `_user_access`.`program_code`=`_program`.`program_code` AND !`_user_access`.`is_hold` AND `_program`.`program_level`!=:def AND `_program`.`program_parent`=`_program_parent`.`parent_no` AND `_user_access`.`user_id`=:id AND `_program`.`program_level`<=:level GROUP BY `_program_parent`.`parent_no` ORDER BY `sequence` ASC");
        $menu->execute(array(":id"=> $uid, ":level" => $user_level, ":def" => 0));
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
    if($user_level >= 8){
        $submenu = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_parent`=:id AND `program_level`!=0 AND `program_level`<=:level AND `is_active` ORDER BY `seq` ASC");
        $submenu->execute(array(":id"=> $parent_no, ":level" => $user_level));
        if ($submenu->rowCount()) {
            while ($subdata = $submenu->fetch(PDO::FETCH_ASSOC)) {
            $submenu_data[] = array("program_code" => $subdata["program_code"], "menu_name" => $subdata["menu_name"], "function" => $subdata["function"], "icon" => $subdata["program_icon"]);
            }
        }
        $data = $submenu_data;
    }else{
        $submenu = $db->prepare("SELECT * FROM $db_hris.`_user_access`,$db_hris.`_program` WHERE `_program`.`program_level`!=0 AND  `_program`.`is_active` AND `_user_access`.`user_id`=:uid  AND !`_user_access`.`is_hold` AND `_program`.`program_level`<=:ulvl AND `_user_access`.`program_code`=`_program`.`program_code` AND `_program`.`program_parent`=:parent_id ORDER BY `_program`.`seq` ASC");
        $submenu->execute(array(":uid"=> $uid, ":parent_id"=>$parent_no, ":ulvl" => $user_level));
        if ($submenu->rowCount()) {
            while ($subdata = $submenu->fetch(PDO::FETCH_ASSOC)) {
            $submenu_data[] = array("program_code" => $subdata["program_code"], "menu_name" => $subdata["menu_name"], "function" => $subdata["function"], "icon" => $subdata["program_icon"]);
            }
        }
        $data = $submenu_data;
    }
    return $data;
}
