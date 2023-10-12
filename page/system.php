<?php

require_once('../system.config.php');
require_once('../common_functions.php');
$cfn = new common_functions();
if (isset($_REQUEST["cmd"])) {
	try {
        if ($db->beginTransaction()) {
			switch ($_POST["cmd"]) {
                case "default":
                    if($_SESSION["name"] == ""){
                        echo json_encode(array("status" => "success", "message" => "login", "data" => get_default()));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "already logged in!"));
                    }
				break;
                case "check_account_id":
                    $user_id=$_POST['uid'];
                    check_account($user_id);
                break;
				case "login":
					$record = array("user_id" => $_POST['uname'], "pass" => $_POST['pword']);
					check_user($record);
				break;
                case "register-user":
                    $record = array("account_id" => $_POST['uid'], "user_id" => $_POST['uname'], "pass" => $_POST['pword'], "pass1" => $_POST['pword1']);
                    save_user($record);
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

function check_account($user_id){
	global $db, $db_hris;

	$check_id=$db->prepare("SELECT * FROM $db_hris.`_user` WHERE `account_id`=:uid AND !`is_active`");
	$check_id->execute(array(":uid" => $user_id));
	if($check_id->rowCount()){
		echo json_encode(array("status" => "success", "message" => "make_user"));
	}else{
		$active=$db->prepare("SELECT * FROM $db_hris.`_user` WHERE `account_id`=:uid AND `is_active`");
		$active->execute(array(":uid" => $user_id));
		if($active->rowCount()){
			echo json_encode(array("status" => "success", "message" => "Account ID is already Registered!!"));
		}else{
			echo json_encode(array("status" => "error", "message" => "Account ID is Invalid!!"));
		}
	}		
}

function save_user($record) {
	global $db, $db_hris;

	if($record["pass"] == $record["pass1"]){
		$reg_user = $db->prepare("UPDATE $db_hris.`_user` SET `user_id`=:uname, `user_password`=:pword, `is_active`=:actv, `registration_date`=:reg_date WHERE `account_id`=:id");
		$reg_user->execute(array(":id" => $record["account_id"], ":uname" => $record["user_id"], ":pword" => md5($record["pass"]), ":actv"=> 1, ":reg_date"=> date('Y-m-d H:i:s')));
		if ($reg_user->rowCount()) {
			check_user($record);
		}else{
			echo json_encode(array("status" => "error", "message" => "Error! Please try again!"));
		}
	}else{
		echo json_encode(array("status" => "error", "message" => "Error! Password does not match!"));
	}
}

function check_user($record){
	global $db, $db_hris, $cfn;

	$check_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_id`=:uid");
	$check_user->execute(array(":uid" => $record['user_id']));
	if($check_user->rowCount()){
		$check_user_data = $check_user->fetch(PDO::FETCH_ASSOC);
		$check_pass=$db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_password`=:pwd");
		$check_pass->execute(array(":pwd" => md5($record['pass'])));
		if($check_pass->rowCount()){
			$check_pass_data = $check_pass->fetch(PDO::FETCH_ASSOC);
            $current_date = date('Y-m-d');
            $cfn->post_shift($current_date);
            update_time($current_date);
			sign_in($check_user_data['user_id'],$check_pass_data['user_password']);
		}else{
			echo json_encode(array("status" => "error", "message" => "Invalid password!!"));
		}
	}else{
		echo json_encode(array("status" => "error", "message" => "Invalid username!!"));
	}
}

function sign_in($uname,$hashpword){
	global $db, $db_hris;
	
	$sign_in = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_password`=:pwd AND `user_id`=:uname");
	$sign_in->execute(array(":pwd" => $hashpword, ":uname" => $uname));
	if($sign_in->rowCount()){
		$sign_in_data = $sign_in->fetch(PDO::FETCH_ASSOC);
		$_SESSION["name"] = $sign_in_data['user_id'];
        $_SESSION["security_key"] = $sign_in_data['security_key'];
		$_SESSION['system_menu'] = null;
		$_SESSION['system_open'] = null;
        $_SESSION["filter"] = 1;
        log_activity($_SESSION["name"]);
		echo json_encode(array("status" => "success"));
	}else{
		echo json_encode(array("status" => "error", "message" => "Invalid username!!"));
	}
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
		}
	}
}

function log_activity($user_id){
    global $db, $db_hris;

    $date = new DateTime();
    $activity = $db->prepare("INSERT INTO $db_hris.`log_activity` SET `user_id`=:user_id, `fired_menu`=:menu, `trans_date`=:tdate");
    $activity->execute(array(":user_id" => $user_id, ":menu" => "LOG-IN", ":tdate" => $date->format("Y-m-d H:i:s")));
}


function get_default(){
	$data = '<div class="w3-container w3-padding w3-responsive w3-mobile" id="index">
                <div class="w3-panel w3-round-large">
                    <div class="w3-row w3-col l8 w3-padding w3-border-right" style="height: auto;">
                        <div class="w3-container w3-center">
                            <img src="logo.webp" alt="logo" width="400" height="500"> 
                        </div>
                    </div>
                    <div class="w3-row-half w3-col l4 w3-padding" style="height: auto;">
                        <div class="w3-panel w3-xlarge">
                            <article style="font-family: Arial, sans-serif;" id="form_header" class="w3-text-orange"><b>Sign In</b></article>
                        </div>
                        <div id="reg_login_form_div">
                            <div id="login_form">
                                <p>
                                    <label class="w3-label w3-small w3-text-orange">Username</label>
                                    <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent" id="username" name="username" type="text" autocomplete="off" required=""/>
                                </p>
                                <p>
                                    <label class="w3-label w3-small w3-text-orange">Password</label>
                                    <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent" id="password" name="password" type="password" autocomplete="off" required="" />
                                </p>
                                <div class="w3-bar w3-margin-top w3-center">
                                    <button name="login" type="submit" id="login_btn" onclick="login()" class="w3-button w3-block w3-padding w3-center" style="background-color:#f77d26">
                                        <span class="w3-small w3-wide" id="login_text">CONTINUE</span>
                                    </button>
                                </div>
                            </div>
                            <div class="w3-hide" action="" method="post" id="reg_form" autocomplete="off">
                                <p>
                                    <label class="w3-label w3-small w3-text-orange>Make Username</label>
                                    <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent" id="make_uid" name="make_uid" type="text" autocomplete="off" required=""/>
                                </p>
                                <p>
                                    <label class="w3-label w3-small w3-text-orange" id="pwd_label">Confirm Password</label>
                                    <input class="w3-input w3-small w3-border-0 w3-border-bottom w3-hover-border-0 w3-transparent" id="cfn_password" name="cfn_password" type="password" autocomplete="off" required="" />
                                </p>
                                <div class="w3-bar w3-margin-top w3-center">
                                    <button name="reg" type="submit" id="reg_btn" onclick="register()" class="w3-button w3-block w3-padding w3-center" style="background-color:#f77d26;">
                                        <span class="w3-small w3-wide" id="reg_text">REGISTER</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                $("#password, #username").keyup(function(event) {
                    if (event.keyCode === 13) {
                        $("#login_btn").click();
                    }
                });
                $("#cfn_password, #make_uid").keyup(function(event) {
                    if (event.keyCode === 13) {
                        $("#reg_btn").click();
                    }
                });
            </script>';
    return $data;
}


