<?php

$program_code = 18;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
    if($level <= $plevel ){
        echo json_encode(array("status" => "error", "message" => "Higher level required!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "get_ded_data":
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        get_ded_data($_POST["ded_no"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "save-update-ded":
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        save_update_deduction($_POST["record"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-default":
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_default($level);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "update-status":
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        update_status($_POST["recid"]);
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


function get_default($level){
    $colgroup = get_columns_grp();
    $columns = get_columns();
    $records = get_records($level);
    echo json_encode(array("status" => "success", "col_group" => $colgroup, "columns" => $columns, "records" => $records));
}

function get_columns_grp() {
    $items = array();
    $items[] = array("span" => 4, "caption" => "");
    $items[] = array("span" => 2, "caption" => "SCHEDULE");
    $items[] = array("span" => 1, "caption" => "");
    $items[] = array("span" => 3, "caption" => "REFRENCE");
    return $items;
}

function get_columns() {
    $items = array();
    $items[] = array("field" => "recid", "caption" => "No", "size" => "100px", "hidden" => "true");
    $items[] = array("field" => "ded", "caption" => "DEDUCTION NAME", "size" => "300px");
    $items[] = array("field" => "label", "caption" => "DEDUCTION LABEL", "size" => "150px");
    $items[] = array("field" => "type", "caption" => "TYPE", "size" => "100px", "attr" => "align=center");
    $items[] = array("field" => "mid", "caption" => "MID", "size" => "80px", "attr" => "align=center" );
    $items[] = array("field" => "end", "caption" => "END", "size" => "80px", "attr" => "align=center" );
    $items[] = array("field" => "stat", "caption" => "STATUS", "size" => "80px", "attr" => "align=center");
    $items[] = array("field" => "uid", "caption" => "USER ID", "size" => "120px", "attr" => "align=center");
    $items[] = array("field" => "ip", "caption" => "STATION ID", "size" => "100px", "attr" => "align=right");
    $items[] = array("field" => "ts", "caption" => "TIME STAMP", "size" => "170px", "attr" => "align=right");
    return $items;
}

function get_records($level){
    global $db, $db_hris;

    $records = array();
    $filter = number_format($level, 2) > number_format(8, 2) ? "" : "WHERE !`is_computed`";
    $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` $filter ORDER BY `deduction_label`");
    $deduction->execute();
    if($deduction->rowCount()){
        while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
            $record["recid"] = $deduction_data["deduction_no"];
            $record["ded"] = $deduction_data["deduction_description"];
            $record["label"] = $deduction_data["deduction_label"];
            $record["type"] = number_format($deduction_data["deduction_type"],2) ==  number_format(1,2) ? "Invoice" : "Others";
            $record["mid"] = number_format(substr($deduction_data["schedule"], 0,1)) ==  number_format(1) ? '<i class="fa-solid fa-check"></i>' : '';
            $record["end"]  = number_format(substr($deduction_data["schedule"], -1)) ==  number_format(2) ? '<i class="fa-solid fa-check"></i>' : '';
            $record["stat"] = $deduction_data["is_inactive"] ? "N" : "Y";
            $record["uid"] = $deduction_data["user_id"];
            $record["ip"] = $deduction_data["station_id"];
            $record["ts"] = (new DateTime($deduction_data["time_stamp"]))->format("m-d-Y H:i:s a");
            array_push($records, $record);
        } 
    }
    return $records;
}

function save_update_deduction($record) {
    global $db, $db_hris;

    $sched = get_sched($record["mid"], $record["end"]);
    $check_ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
    $check_ded->execute(array(":ded_no" => $record["ded_no"]));
    if ($check_ded->rowCount()){
        $update_ded = $db->prepare("UPDATE $db_hris.`deduction` SET `deduction_label`=:label, `deduction_description`=:desc, `deduction_type`=:type, `schedule`=:sched, `user_id`=:uid, `station_id`=:ip WHERE `deduction_no`=:ded_no");
        $update_ded->execute(array(":ded_no" => $record["ded_no"], ":label" => $record["ded_label"], ":desc" => $record["ded_name"],":type" => $record["type"], ":sched" => $sched, ":uid" => $_SESSION["name"], ":ip" => $_SERVER["REMOTE_ADDR"]));
        if($update_ded->rowCount()){
            echo json_encode(array("status" => "success", "u" => $update_ded->errorInfo()));
        }
    }else{
        $new_ded = $db->prepare("INSERT INTO $db_hris.`deduction`(`deduction_label`, `deduction_type`, `schedule`, `user_id`, `station_id`, `deduction_description`, `is_computed`, `remittance_code`, `is_inactive`, `priority`, `seq_no`) VALUES (:label, :type, :sched, :uid, :ip, :desc, :computed, :rem_code, :actv, :prio, :seq_no)");
        $new_ded->execute(array(":label" => $record["ded_name"], ":type" => $record["type"], ":sched" => $sched, ":uid" => $_SESSION["name"], ":ip" => $_SERVER["REMOTE_ADDR"], ":desc" => $record["ded_label"], ":computed" => 0, ":rem_code" => 0000, ":actv" => 0, ":prio" => 0, ":seq_no" => 00000));
        if($new_ded->rowCount()){
            echo json_encode(array("status" => "success"));
        }
    }
}

function get_sched($mid, $end){
    if($mid){
        $sched = "1";
    }
    if($end){
        $sched = "2";
    }
    if($mid === "1" && $end === "1"){
        $sched = "1,2";
    }
    return $sched;
}

function get_ded_data($ded_no) {
    global $db, $db_hris;

    $get_ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
    $get_ded->execute(array(":ded_no" => $ded_no));
    if ($get_ded->rowCount()){
        $ded_data = $get_ded->fetch(PDO::FETCH_ASSOC);
        $ded_no = $ded_data["deduction_no"];
        $ded_name = $ded_data["deduction_description"];
        $ded_label = $ded_data["deduction_label"];
        if($ded_data["deduction_type"]){
            $ded_type_id = '1';
            $ded_type_name = 'Invoice';
        }else{
            $ded_type_id = '0';
            $ded_type_name = 'Others';
        }
        if($ded_data["schedule"] == '1'){
            $mid = '1';
            $end = '0';
        }elseif($ded_data["schedule"] == '2'){
            $mid = '0';
            $end = '2';
        }else{
            $mid = '1';
            $end = '2';
        }
        echo json_encode(array("status" => "success", "ded_no" => $ded_no, "ded_label" => $ded_label, "ded_name" => $ded_name, "ded_type_no" => $ded_type_id, "ded_type" => $ded_type_name, "mid" => $mid, "end" => $end));
    }  
}

function update_status($recid){
    global $db, $db_hris;

    $check = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
    $check->execute(array(":ded_no" => $recid));
    if ($check->rowCount()){
        $check_data = $check->fetch(PDO::FETCH_ASSOC);
        $active = $check_data["is_inactive"] ? 0 : 1;
        $set_ded = $db->prepare("UPDATE $db_hris.`deduction` SET `is_inactive`=:actv WHERE `deduction_no`=:ded_no");
        $set_ded->execute(array(":actv" => $active, ":ded_no" => $recid));
        if ($set_ded->rowCount()){
            echo json_encode(array("status" => "success", "u" => $set_ded->errorInfo()));
        }else{
            echo json_encode(array("status" => "error", "message" => "Error, deduction can not be set!", "u" => $set_ded->errorInfo()));
        }
    }
}