<?php

function check_access($session_name){
  global $db, $db_hris;

  $check_user = $db->prepare("SELECT * FROM $db_hris.`_user` where `user_id`=:user_id LIMIT 1");
  $check_user->execute(array(":user_id" => $session_name));
  $user_data = $check_user->fetch(PDO::FETCH_ASSOC);
  return $user_data;

}

function getMenu($user_level,$uid){
  global $db, $db_hris;

  if($user_level >= 8){
    $menu = $db->prepare("SELECT * FROM $db_hris.`_program_parent`,$db_hris.`_program` WHERE `_program_parent`.`isActive` AND `_program`.`program_parent`=`_program_parent`.`parent_no` AND `_program`.`program_level`<=:level GROUP BY `_program_parent`.`parent_no` ORDER BY `sequence` ASC");
    $menu->execute(array(":level" => $user_level));
    $menu_data = array();
    if ($menu->rowCount()) {
        while ($data = $menu->fetch(PDO::FETCH_ASSOC)) {
          $menu_data[] = array("parent_no" => $data["parent_no"], "parent_name" => $data["parent_name"], "class" => $data["parent_data"], "icon" => $data["icon"]);
        }
    }
    $data = $menu_data;
  }else{
    $menu = $db->prepare("SELECT * FROM $db_hris.`_program_parent`,$db_hris.`_user_access`,$db_hris.`_program` WHERE `_program_parent`.`isActive` AND `_user_access`.`program_code`=`_program`.`program_code` AND `_program`.`program_parent`=`_program_parent`.`parent_no` AND `_user_access`.`user_id`=:id AND `_program`.`program_level`<=:level GROUP BY `_program_parent`.`parent_no` ORDER BY `sequence` ASC");
    $menu->execute(array(":id"=> $uid, ":level" => $user_level));
    $menu_data = array();
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

  if($user_level >= 8){
    $submenu = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_parent` = :id AND `program_level`<=:level AND `is_active` ORDER BY `seq` ASC");
    $submenu->execute(array(":id"=> $parent_no, ":level" => $user_level));
    $submenu_data = array();
    if ($submenu->rowCount()) {
      while ($subdata = $submenu->fetch(PDO::FETCH_ASSOC)) {
        $submenu_data[] = array("program_code" => $subdata["program_code"], "menu_name" => $subdata["menu_name"], "function" => $subdata["function"], "icon" => $subdata["program_icon"]);
      }
    }
    $data = $submenu_data;
  }else{
    $submenu = $db->prepare("SELECT * FROM $db_hris.`_user_access`,$db_hris.`_program`,$db_hris.`_user` where `_program`.`is_active` and `_user_access`.`user_id`=`_user`.`user_no` and `_user`.`user_no`=:uid and `_user`.`user_level`>=`_program`.`program_level` and `_user_access`.`program_code`=`_program`.`program_code` and `_program`.`program_parent`=:parent_id ORDER BY `_program`.`seq` ASC");
    $submenu->execute(array(":uid"=> $uid, ":parent_id"=>$parent_no));
    $submenu_data = array();
    if ($submenu->rowCount()) {
      while ($subdata = $submenu->fetch(PDO::FETCH_ASSOC)) {
        $submenu_data[] = array("program_code" => $subdata["program_code"], "menu_name" => $subdata["menu_name"], "function" => $subdata["function"], "icon" => $subdata["program_icon"]);
      }
    }
    $data = $submenu_data;
  }
  return $data;
}

?>