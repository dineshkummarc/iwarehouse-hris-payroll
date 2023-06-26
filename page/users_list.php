<?php
global $db, $db_hris;
header('Content-type:application/json');
$program_code = 8;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
  echo json_encode(array("status" => "error", "message" => "No Access Rights"));
  return;
}
$_users = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_level` < :lvl AND `user_id`!=:uid");
$_users->execute(array(":lvl" => $level, ":uid" => $session_name));
$user_data=array();
if($_users->rowCount()){
	while ($_users_data = $_users->fetch(PDO::FETCH_ASSOC)) {
		if($_users_data['is_active']){
			$active = 'Y';
		}else{
			$active = 'N';
		}
		if(empty($_users_data['user_id'])){
			$user_name = '';
		}else{
			$user_name = $_users_data['user_id'];
		}
		if($_users_data['user_level']=='10'){
			$lvl = 'Administrator';
		}else if($_users_data['user_level']=='9'){
			$lvl = 'System Owner';
		}else if($_users_data['user_level']=='8'){
			$lvl = 'Admin';
		}else if($_users_data['user_level']=='5'){
			$lvl = "User Level 3";
		}else if($_users_data['user_level']=='3'){
			$lvl = "User Level 2";
		}else{
			$lvl = "User Level 1";
		}
			

		if(!$_users_data['is_active']){
			$id = '100'.$_users_data['user_no'];
			$uname = '<span class="w3-text-red">'.$user_name.'</span>';
			$name = '<span class="w3-text-red">'.$_users_data['name'].'</span>';
			$acc_id = '<span class="w3-text-red">'.$_users_data['account_id'].'</span>';
			$reg_date = '<span class="w3-text-red">'.$_users_data['registration_date'].'</span>';
			$actv = '<span class="w3-text-red">'.$active.'</span>';
			$lvl = '<span class="w3-text-red">'.$lvl.'</span>';
			$grant = '<span class="w3-text-red">'.$_users_data['granted_by'].'</span>';
			$_timestamp = '<span class="w3-text-red">'.$_users_data['time_stamp'].'</span>';
			$station = '<span class="w3-text-red">'.$_users_data['station_id'].'</span>';
			$last_login = '<span class="w3-text-red">'.$_users_data['last_login_time'].'</span>';
		}else{
			$id = "100".$_users_data['user_no'];
			$uname = $user_name;
			$name = $_users_data['name'];
			$acc_id = $_users_data['account_id'];
			$reg_date = $_users_data['registration_date'];
			$actv = $active;
			$lvl = $lvl;
			$grant = $_users_data['granted_by'];
			$_timestamp = $_users_data['time_stamp'];
			$station = $_users_data['station_id'];
			$last_login = $_users_data['last_login_time'];
		}
		$user_data[] = array(
			'recid'=>$id,
			'uname' => $uname,
			'name'=> $name,
			'acc_id'=> $acc_id,
			'reg_date'=> $reg_date,
			'actv'=> $actv,
			'lvl'=> $lvl,
			'grant'=> $grant,
			'_timestamp'=> $_timestamp,
			'station'=> $station,
			'last_log'=> $last_login
		);
	}
	echo json_encode($user_data);
}


?>