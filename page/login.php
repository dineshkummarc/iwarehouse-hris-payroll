<?php

session_start();

require_once('../modules/system/system.config.php');

switch ($_POST["cmd"]) {
    case "check_account_id":
    	$user_id=$_POST['uid'];

		$check_id=mysqli_query($con, "SELECT * FROM _user WHERE account_id='$user_id' and is_active='0'") or die(mysqli_error($con));
		$uid_row=mysqli_num_rows($check_id);
			if($uid_row > 0){
				echo 'make_user';
			}else{
				$active=mysqli_query($con, "SELECT * FROM _user WHERE account_id='$user_id' and is_active='1'") or die(mysqli_error($con));
				$active_row=mysqli_num_rows($active);
				if($active_row > 0){
					echo '<span class="w3-text-red w3-small">Account ID is already Registered!!</span>';
				}else{
					echo '<span class="w3-text-red w3-small">Account ID is Invalid!!</span>';
				}
			}		
    break;
    case "login":
    	$uname=mysqli_real_escape_string($con,$_POST['uname']);
		$pword=mysqli_real_escape_string($con,$_POST['pword']);
    	$hashpword=md5($pword);

    	$query=mysqli_query($con, "SELECT * FROM _user WHERE user_password='$hashpword' and user_id='$uname' and is_active='1'") or die(mysqli_error($con));
		$row=mysqli_fetch_array($query);
		$num_row=mysqli_num_rows($query);
			if($num_row > 0){
				save_login_time($uname);
				$current_date = date('Y-m-d');
				include("../function/post_shift.php");
				post_shift($current_date);
				$_SESSION['name']=$row['user_id'];
				echo "success";
			}else{
				echo "<span class='w3-small w3-text-red'>Invalid Credentials!</span>";
			}
		break;
	case "register_user":
		$account_id=$_POST['uid'];
		$uname=$_POST['uname'];
    	$pword=$_POST['pword'];
		$pword1=$_POST['pword1'];
    	save_user($account_id,$uname,$pword,$pword1);
    break;
}

function save_user($account_id,$uname,$pword,$pword1) {
	global $db, $db_hris;

	if($pword == $pword1){
		$reg_user = $db->prepare("UPDATE $db_hris.`_user` SET `user_id`=:uname, `user_password`=:pword, `is_active`=:actv, `registration_date`=:reg_date WHERE `account_id`=:id");
		$reg_user->execute(array(":id" => $account_id, ":uname" => $uname, ":pword" => md5($pword), ":actv"=> 1, ":reg_date"=> date('Y-m-d H:i:s')));
		if ($reg_user->rowCount()) {
			echo "success";
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
