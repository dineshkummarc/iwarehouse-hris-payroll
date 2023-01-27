<?php

$program_code = 1;
require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "get-menu":
        $menu_id = $_POST['menu_id'];
        get_system_menu($menu_id);
    break;
}

function get_system_menu($menu_id){
    global $db, $db_hris;

    $qmenu = $db->prepare("SELECT `_program`.`menu_name`,`_program`.`function`,`_program_parent`.`parent_name` FROM $db_hris.`_program`,$db_hris.`_program_parent` WHERE `_program`.`program_parent`=`_program_parent`.`parent_no` AND `_program`.`program_code`=:pcode");
    $qmenu->execute(array(":pcode"=> $menu_id));
    if ($qmenu->rowCount()) {
        while ($sys_menu = $qmenu->fetch(PDO::FETCH_ASSOC)) {
            
            $menu_name = $sys_menu["menu_name"];
            $main_menu = $sys_menu["parent_name"];
            if($sys_menu["function"] == "function/profile.php")
                $menu = $sys_menu["function"];
            else
                $menu = substr($sys_menu["function"],0 ,-4);
        }
    }
    echo json_encode(array("status" => "success", "sys_menu" => $menu, "menu_name" => $menu_name, "main_menu" => $main_menu));
}