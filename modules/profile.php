<?php

$program_code = 32;
include("../system.config.php");
include("../common_functions.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();

if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "get-user-info":
                    if (substr($access_rights, 6, 2) !== "B+") {
                        if($level <= $plevel ){
                            echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                            return;
                        }
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }else{
                        get_user_info($_SESSION['name']);
                    }
                break;
                case "update-profile":
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        $acc_id = $_POST["acc_id"];
                        $user_pass = $_POST["user_pass"];
                        $user_pass1 = $_POST["user_pass1"];
                        $user_pass2 = $_POST["user_pass2"];
                        update_profile($acc_id,$user_pass,$user_pass1,$user_pass2);
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

function update_profile($acc_id,$user_pass,$user_pass1,$user_pass2){
    global $db, $db_hris;

    $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `account_id`=:aid");
    $user->execute(array(":aid" => $acc_id));
    if ($user->rowCount()) {
        $user_data = $user->fetch(PDO::FETCH_ASSOC);
        if (md5($user_pass) === $user_data["user_password"]) {
            if ($user_pass1 !== "" AND md5($user_pass1) === md5($user_pass2)) {
                $pass = md5($user_pass2);
                $set = $db->prepare("UPDATE $db_hris.`_user` SET `user_password`=:pass WHERE `account_id`=:aid");
                $set->execute(array(":pass" => $pass, ":aid" => $acc_id));
                if ($set->rowCount()) {
                    echo json_encode(array("status" => "success", "message" => "Password Changed!"));
                }
            }else{
                echo json_encode(array("status" => "error", "message" => "Password does not match!"));
            }
        }
    }else{
        echo json_encode(array("status" => "error", "message" => "Out of focus, Please try again later!", "e" => $user->errorInfo()));
    }
}

function get_user_info($name) {
    global $db, $db_hris;

    $get_user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_id`=:name");
    $get_user->execute(array(":name" => $name));
    if ($get_user->rowCount()){
        while ($user_data = $get_user->fetch(PDO::FETCH_ASSOC)) {
            $recid = $user_data["account_id"];
            $uid = $user_data["user_id"];
            $fname = $user_data["name"];
            $level = $user_data["user_level"];
            $last_login = $user_data["last_login_time"];
            if($level=='10'){
                $lvl = 'Administrator';
            }else if($level=='9'){
                $lvl = 'System Owner';
            }else if($level=='8'){
                $lvl = 'Admin';
            }else if($level=='5'){
                $lvl = "User Level 3";
            }else if($level=='3'){
                $lvl = "User Level 2";
            }else{
                $lvl = "User Level 1";
            }
                
        }
    }
    echo json_encode(array("status" => "success", "recid" => $recid, "uid" => $uid, "fname" => $fname, "last_login" => $last_login, "lvl" => $lvl, "records" => get_activity($name)));       
}

function get_activity($name){
    global $db, $db_hris;

    $records = array();
    $q = $db->prepare("SELECT * FROM $db_hris.`log_activity` WHERE `user_id` LIKE :name ORDER BY `log_id` DESC");
    $q->execute(array(":name" => $name));
    if($q->rowCount()){
        while($data = $q->fetch(PDO::FETCH_ASSOC)){
            $record["recid"] = $data["log_id"];
            $record["prog"] = $data["fired_menu"];
            $record["ts"] = $data["trans_date"];
            $record["ip"] = "";
            array_push($records, $record);
        }
    }
    return $records;
}