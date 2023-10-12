<?php

$program_code = 6;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();

if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "set-defaults": //ok
                    $columns = get_grid();
                    echo json_encode(array("status" => "success", "columns" => $columns));
                break;
                case "get-records":
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_records($level);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "enable-disable":
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        enable_disable($_POST["recid"]);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "reset-account":
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        reset_account($_POST["recid"]);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "enroll":
                    if (substr($access_rights, 0, 2) !== "A+") {
                        if($level >= 8 ){
                            echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                            return;
                        }
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }else{
                        $name = $_POST["name"];
                        $lvl = $_POST["lvl"];
                        enroll_user($name,$lvl);
                    }
                break;
                case "get-user":
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        get_user($_POST["user_id"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "update":
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        $name = $_POST["name"];
                        $lvl = $_POST["lvl"];
                        $user_id = $_POST["user_id"];
                        update_user($name,$lvl,$user_id);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
            }
            $db->commit();
            return false;
        }
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(array("status" => "error", "message" => "database is not ready!", "e" => $e));
        exit();
    }
}

function get_user($user_id) {
    global $db, $db_hris;

    $get_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:user_no");
    $get_user->execute(array(":user_no" => $user_id));
    if ($get_user->rowCount()){
        while ($user_data = $get_user->fetch(PDO::FETCH_ASSOC)) {
            $user_no = $user_data["user_no"];
            $user_name = $user_data["name"];
            $user_level = $user_data["user_level"];
        }
    }
    echo json_encode(array("status" => "success", "id" => $user_no, "name" => $user_name, "level" => $user_level));       
}

function enroll_user($name,$lvl) {
    global $db, $db_hris;

    $account_id = get_new_account_id();
    $new_user = $db->prepare("INSERT INTO $db_hris.`_user` (`name`, `account_id`, `user_level`,`granted_by`,`station_id`) VALUES (:name, :acc_id, :lvl, :by, :ip)");
    $new_user->execute(array(":name" => $name, ":acc_id" => $account_id, ":lvl" => $lvl, ":by" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
    echo json_encode(array("status" => "success", "e" => $new_user->errorInfo()));
}

function get_new_account_id() {
    global $db, $db_hris;

    // Generate a random 6-digit number
    $random = sprintf('%06d', mt_rand(1, 999999));

    // Check if the generated ID already exists in the database
    $user = $db->prepare("SELECT COUNT(*) as count FROM $db_hris.`_user` WHERE `account_id`=:id");
    $user->execute(array(":id" => $random));
    $result = $user->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        // If the ID already exists, recursively call the function to generate a new one
        return get_new_account_id();
    }

    // If the generated ID is unique, return it
    $id = date("ym") . $random;
    return $id;
}

function update_user($name,$lvl,$user_id) {
    global $db, $db_hris;

    $user_update = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:no");
    $user_update->execute(array(":no" => $user_id));
    if ($user_update->rowCount()){

        $update = $db->prepare("UPDATE $db_hris.`_user` SET `name`=:name, `user_level`=:lvl, `granted_by`=:grant, `station_id`=:station WHERE `user_no`=:no");
        $update->execute(array(":name" => $name, ":lvl" => $lvl, ":no" => $user_id, ":station" => $_SERVER['REMOTE_ADDR'], ":grant" => $_SESSION["name"]));

        echo json_encode(array("status" => "success"));
    }
}

function reset_account($recid) {
    global $db, $db_hris;

    $reset = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:no");
    $reset->execute(array(":no" => $recid));
    if ($reset->rowCount()){

        $reset_account = $db->prepare("UPDATE $db_hris.`_user` SET `user_id`=:user_id, `user_password`=:pwd, `is_active`=:actv, `granted_by`=:uid, `station_id`=:ip WHERE `user_no`=:no");
        $reset_account->execute(array(":user_id" => "", ":pwd" => "", ":actv" => 0, ":no" => $recid, ":uid" => $_SESSION["name"], ":ip" => $_SERVER["REMOTE_ADDR"]));

        echo json_encode(array("status" => "success"));
    }
}

function enable_disable($recid) {
    global $db, $db_hris;

    // Check if the user exists and is not the current user
    $check_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:no AND `user_id`!=:name");
    $check_user->execute(array(":no" => $recid, ":name" => $_SESSION['name']));

    if ($check_user->rowCount() > 0) {
        $user_data = $check_user->fetch(PDO::FETCH_ASSOC);
        $is_active = $user_data["is_active"] ? 0 : 1;

        // Update the user's active status
        $update_user = $db->prepare("UPDATE $db_hris.`_user` SET `is_active`=:actv, `granted_by`=:uid, `station_id`=:ip WHERE `user_no`=:no");
        $update_user->execute(array(":actv" => $is_active, ":no" => $recid, ":uid" => $_SESSION["name"], ":ip" => $_SERVER["REMOTE_ADDR"]));

        if ($update_user->rowCount() > 0) {
            echo json_encode(array("status" => "success", "message" => "User status updated successfully."));
        } else {
            echo json_encode(array("status" => "error", "message" => "Failed to update user status."));
        }
    } else {
        echo json_encode(array("status" => "error", "message" => "User not found or you don't have permission to modify this user."));
    }
}


function  get_records($level){
    global $db, $db_hris;

    $records = array();
    $users = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_level` < :lvl AND `user_id`!=:uid");
    $users->execute(array(":lvl" => $level, ":uid" => $_SESSION["name"]));
    if($users->rowCount()){
        while ($users_data = $users->fetch(PDO::FETCH_ASSOC)) {
            $record["recid"] = $users_data["user_no"];
		    $record["actv"] = $users_data['is_active'] ? 'Y' : 'N';
	        $record["uname"] = empty($users_data['user_id']) ? '' : $users_data['user_id'];
            if($users_data['user_level'] === 10){
                $record["lvl"] = 'Administrator';
            }else if($users_data['user_level'] === 9){
                $record["lvl"] = 'System Owner';
            }else if($users_data['user_level']=='8'){
                $record["lvl"] = 'Admin';
            }else if($users_data['user_level']=='5'){
                $record["lvl"] = "User Level 3";
            }else if($users_data['user_level']=='3'){
                $record["lvl"] = "User Level 2";
            }else{
                $record["lvl"] = "User Level 1";
            }
            $record["w2ui"]["style"] = $users_data['is_active'] ? '' : 'color: red;';
            $record["name"] = $users_data['name'];
			$record["acc_id"] = $users_data['account_id'];
			$record["reg_date"] = $users_data['registration_date'];
			$record["grant"] = $users_data['granted_by'];
			$record["_timestamp"] = $users_data['time_stamp'];
			$record["station"] = $users_data['station_id'];
			$record["last_log"] = $users_data['last_login_time'];
            $records[] = $record;
        }
	}
    echo json_encode(array("status" => "success", "records" => $records, "uid" => $_SESSION["name"], "level" => $level));
}


function get_grid() {
    $items = array();
    $items[] = array("field" => "recid", "caption" => "userID", "size" => "100px", "hidden" => true );
    $items[] = array("field" => "uname", "caption" => "User Name", "size" => "7%" );
    $items[] = array("field" => "name", "caption" => "Registration Name", "size" => "30%" );
    $items[] = array("field" => "acc_id", "caption" => "Account ID", "size" => "10%", "attr" => "align=center" );
    $items[] = array("field" => "last_log", "caption" => "Last Login", "size" => "15%", "attr" => "align=center" );
    $items[] = array("field" => "reg_date", "caption" => "Registration Date", "size" => "15%", "attr" => "align=center" );
    $items[] = array("field" => "actv", "caption" => "Active", "size" => "100px", "attr" => "align=center" );
    $items[] = array("field" => "lvl", "caption" => "Level", "size" => "100px", "attr" => "align=center" );
    $items[] = array("field" => "grant", "caption" => "Grant By", "size" => "100px", "attr" => "align=center" );
    $items[] = array("field" => "_timestamp", "caption" => "TimeStamp", "size" => "150px", "attr" => "align=left" );
    $items[] = array("field" => "station", "caption" => "Station", "size" => "100px", "attr" => "align=left" );
    return $items;
}