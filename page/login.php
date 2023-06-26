<?php

session_start();

require_once('../modules/system/system.config.php');

switch ($_POST["cmd"]) {
    case "check_account_id":
		$user_id=$_POST['uid'];
		check_account($user_id);
    break;
    case "login":
		$uname=mysqli_real_escape_string($con,$_POST['uname']);
		$pword=mysqli_real_escape_string($con,$_POST['pword']);
		$hashpword=md5($pword);
		sign_in($uname,$hashpword);
	break;
	case "register_user":
		$account_id=$_POST['uid'];
		$uname=$_POST['uname'];
		$pword=$_POST['pword'];
		$pword1=$_POST['pword1'];
		save_user($account_id,$uname,$pword,$pword1);
    break;
	case "post-shift";
		$current_date = date('Y-m-d');
		include("../function/post_shift.php");
		post_shift($current_date);
		update_time($current_date);
	break;
}

function save_user($account_id,$uname,$pword,$pword1) {
	global $db, $db_hris;

	if($pword == $pword1){
		$reg_user = $db->prepare("UPDATE $db_hris.`_user` SET `user_id`=:uname, `user_password`=:pword, `is_active`=:actv, `registration_date`=:reg_date WHERE `account_id`=:id");
		$reg_user->execute(array(":id" => $account_id, ":uname" => $uname, ":pword" => md5($pword), ":actv"=> 1, ":reg_date"=> date('Y-m-d H:i:s')));
		if ($reg_user->rowCount()) {
			echo "success";
			$_SESSION['name']=$uname;
		}else{
			echo "<span class='w3-small w3-text-red'>Error! Please try again!</span>";
		}
	}else{
		echo "<span class='w3-small w3-text-red'>Error! Password does not match!</span>";
	}
}

function save_login_time($uname) {
	global $db, $db_hris;

	$save_login = $db->prepare("UPDATE $db_hris.`_user` SET `last_login_time`=:login_time WHERE `user_id`=:uid");
	$save_login->execute(array(":uid" => $uname, ":login_time"=> date('Y-m-d H:i:s')));

}

function update_time($current_date){
	global $db, $db_hris;

	$config = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` WHERE `config_name`=:config_name");
	$config->execute(array(":config_name" => 'trans date'));
	if($config->rowCount()){
		$config_data = $config->fetch(PDO::FETCH_ASSOC);
		set_time_limit(300);
		$sys_date = $config_data["config_value"];
		$cdate = new DateTime($sys_date);
		$dateNow = $cdate->modify('+1 day');
		if($sys_date != $current_date){
			$update_trans_date = $db->prepare("UPDATE $db_hris.`_sysconfig` SET `config_value`=:cdate WHERE `config_name`=:cname");
			$update_trans_date->execute(array(":cdate" => $dateNow->format('Y-m-d'), ":cname"=> 'trans date'));
			echo "success";
		}else{
			echo "success";
		}
	}
}

function check_account($user_id){
	global $db, $db_hris;

	$check_id=$db->prepare("SELECT * FROM $db_hris.`_user` WHERE `account_id`=:uid AND !`is_active`");
	$check_id->execute(array(":uid" => $user_id));
	if($check_id->rowCount()){
		echo 'make_user';
	}else{
		$active=$db->prepare("SELECT * FROM $db_hris.`_user` WHERE `account_id`=:uid AND `is_active`");
		$active->execute(array(":uid" => $user_id));
		if($active->rowCount()){
			echo '<span class="w3-text-red w3-small">Account ID is already Registered!!</span>';
		}else{
			echo '<span class="w3-text-red w3-small">Account ID is Invalid!!</span>';
		}
	}		
}

function sign_in($uname,$hashpword){
	global $db, $db_hris;

	set_time_limit(300);
	
	$sign_in=$db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_password`=:pwd AND `user_id`=:uname AND `is_active`");
	$sign_in->execute(array(":pwd" => $hashpword, ":uname" => $uname));
	if($sign_in->rowCount()){
		$sign_in_data = $sign_in->fetch(PDO::FETCH_ASSOC);
		$_SESSION['user_id']=$sign_in_data['user_no'];
		$_SESSION['name']=$sign_in_data['user_id'];
		$_SESSION["security_key"] = md5(uniqid(rand(1, 652032)), false);
		$skey = md5($_SESSION["security_key"]);
        $useru = $db->prepare("UPDATE $db_hris.`_user` SET `security_key`=:skey WHERE `user_no`=:uno");
        $useru->execute(array(":skey" => $skey, ":uno" => $_SESSION["user_id"]));
		if($useru->rowCount()){
			$_SESSION['system_menu'] = 0;
			$_SESSION['system_open'] = 0;
			save_login_time($_SESSION['name']);
			echo "success";
		}
	}else{
		echo "<span class='w3-small w3-text-red'>Invalid Credentials!</span>";
	}
}