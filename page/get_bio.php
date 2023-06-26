<?php

$program_code_ot = 22;
$program_code_att = 12;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights_att = $cfn->get_user_rights($program_code_att);
$plevel_att = $cfn->get_program_level($program_code_att);
$access_rights_ot = $cfn->get_user_rights($program_code_ot);
$plevel_ot = $cfn->get_program_level($program_code_ot);
$level = $cfn->get_user_level();

switch ($_POST["cmd"]) {
    case "get-wall-bio":
        if (substr($access_rights_att, 0, 8) === "A+E+D+B+") {
            if($level <= $plevel_att ){
                echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                return;
            }
            $fdate = $_POST["fdate"];
            $ndate = (new DateTime($fdate))->format("Y-m-d");

            require '../common/biometric.php';

            $att_log = $zk->getAttendance();

            foreach ($att_log as $key => $data){
                $uid = $data[1];
                $date = (new DateTime($data[3]))->format("Y-m-d");
                $time = (new DateTime($data[3]))->format("H:i:s");
                $ver = '1';
                $status = $data[2];

                if($date >= $ndate){

                    $check=mysqli_query($con, "SELECT * FROM _tmp_imported_att WHERE Date='$date' and _time='$time' and pin='$uid'") or die(mysqli_error($con));
                    $time_row=mysqli_num_rows($check);
                    if($time_row > 0){
                        $update = mysqli_query($con,"UPDATE _tmp_imported_att SET Status='$status', Date='$date', _time='$time', Verified='$ver' WHERE pin='$uid'");
                    }else{
                        $filed = mysqli_query($con,"INSERT INTO _tmp_imported_att (pin,Date,_time,Verified,Status,get_by) VALUES ('$uid', '$date','$time','$ver','$status','$session_name')");
                    }
                }
            }
            $zk->clearAttendance();
            $zk->enableDevice();
            $zk->disconnect();
            
            $records = get_imported_time();
            echo json_encode(array("status" => "success", "records" => $records));
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-default":
        if (substr($access_rights_att, 6, 2) !== "B+") {
            if($level <= $plevel_att ){
                echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                return;
            }
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }else{
            $records = get_imported_time();
            echo json_encode(array("status" => "success", "records" => $records));
        }
    break;
    case "confirm-bio":
        if (substr($access_rights_att, 0, 8) === "A+E+D+B+") {
            confirm_attendance();
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "delete-bio":
        if (substr($access_rights_att, 4, 2) === "D+") {
            delete_imported_attendace();
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-log-type":
        if (substr($access_rights_att, 6, 2) === "B+") {
            get_log_type();
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "save-manual-att":
        if (substr($access_rights_att, 0, 2) === "A+") {
            $emp_no = $_POST["record"]["emp_list"]["id"];
            $log_type = $_POST["record"]["att_reason"]["id"];
            $date = $_POST["record"]["att_date"];
            $time = $_POST["record"]["att_time"];
            $ndate = (new DateTime($date))->format("Y-m-d");
            $ntime = (new DateTime($time))->format("H:i:s");
            save_manual_time($emp_no,$log_type,$ndate,$ntime);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-ot-data":
        if (substr($access_rights_ot, 6, 2) !== "B+") {
            if($level <= $plevel_ot ){
                echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                return;
            }
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }else{
            $records = get_ot_data();
            echo json_encode(array("status" => "success", "records" => $records));
        }
    break;
    case "get-approve-ot":
        if (substr($access_rights_ot, 6, 2) !== "B+") {
            if($level <= $plevel_ot ){
                echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                return;
            }
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }else{
            $records = get_approve_ot();
            echo json_encode(array("status" => "success", "records" => $records));
        }
    break;
    case "approve-ot":
        if (substr($access_rights_ot, 0, 2) === "A+") {
            $emp_no = $_POST['emp_no'];
            $trans_date = $_POST['trans_date'];
            $newdate = (new DateTime($trans_date))->format("Y-m-d");
            $trans_time = $_POST['trans_time'];
            approve_ot($emp_no,$newdate,$trans_time);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "cancel-ot":
        if (substr($access_rights_ot, 4, 2) === "D+") {
            $emp_no = $_POST['emp_no'];
            $trans_date = $_POST['date'];
            $newdate = (new DateTime($trans_date))->format("Y-m-d");
            cancel_ot($emp_no,$newdate);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "delete-att":
        if (substr($access_rights_att, 4, 2) === "D+") {
            $pin = substr($_POST["recid"],3);
            del_attendance($pin);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-overtime";
        if (substr($access_rights_ot, 2, 2) === "E+") {
            $emp_no = $_POST['emp_no'];
            $trans_date = $_POST['trans_date'];
            get_overtime($emp_no,$trans_date);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;

}

function get_overtime($emp_no,$trans_date) {
    global $db, $db_hris;

    $ot_data = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`time_credit_ot` WHERE `time_credit_ot`.`employee_no`=:no AND `time_credit_ot`.`trans_date`=:date AND !`master_data`.`is_inactive` AND `time_credit_ot`.`employee_no`=`master_data`.`pin` AND !`time_credit_ot`.`is_approved`");
    $ot_data->execute(array(":no" => $emp_no, ":date" => $trans_date));
    if ($ot_data->rowCount()) {
        while ($data = $ot_data->fetch(PDO::FETCH_ASSOC)) {
            $emp_no = $data['pin'];
            $lname = $data['family_name'];
            $fname = $data['given_name'];
            $middle_name = $data['middle_name'];
            if($middle_name != ''){
                $mname=substr($data['middle_name'], 0, 1);
            }else{
                $mname='';
            }
            $name = $lname.', '.$fname.' '.$mname;
            $ot_time=$data['credit_time'];
            $trans_date=(new DateTime($data['trans_date']))->format("Y-m-d");
        }
    }
    echo json_encode(array("status" => "success", "emp_no" => $emp_no, "emp_name" => $name, "trans_date" => $trans_date, "trans_time" => $ot_time));
}

function del_attendance($pin) {
    global $db, $db_hris;

    $del_att = $db->prepare("DELETE FROM $db_hris.`_tmp_imported_att` WHERE `attendance_log_id`=:pin");
    $del_att->execute(array(":pin"=>$pin));
    if($del_att->rowCount()){
        echo json_encode(array("status" => "success"));
    }
}

function approve_ot($emp_no,$newdate,$trans_time){
    global $db, $db_hris;

    $emp_ot = $db->prepare("UPDATE $db_hris.`time_credit_ot` SET `is_approved`=:approve, `credit_time`=:time WHERE `employee_no`=:no AND `trans_date`=:date");
    $emp_ot->execute(array(":no" => $emp_no, ":date" => $newdate, ":time" => $trans_time, ":approve" => 1));
    if($emp_ot->rowCount()){
        echo json_encode(array("status" => "success"));
    }

}

function cancel_ot($emp_no,$newdate){
    global $db, $db_hris;

    $cancel_ot = $db->prepare("UPDATE $db_hris.`time_credit_ot` SET `is_approved`=:approve WHERE `employee_no`=:no AND `trans_date`=:date");
    $cancel_ot->execute(array(":no" => $emp_no, ":date" => $newdate, ":approve" => 0));
    if($cancel_ot->rowCount()){
        echo json_encode(array("status" => "success"));
    }

}

function get_ot_data() {
    global $db, $db_hris;

    $group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:name");
    $group->execute(array(":name" => 100));
    if ($group->rowCount()) {
        while ($pay_group = $group->fetch(PDO::FETCH_ASSOC)) {
            $cutoff = $pay_group["cutoff_date"];
            $pay_date = $pay_group["payroll_date"];
        }
    }
    $records = array();

    $ot_data = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`time_credit_ot` WHERE !`master_data`.`is_inactive` AND `time_credit_ot`.`employee_no`=`master_data`.`pin` AND !`time_credit_ot`.`is_approved` AND `trans_date` BETWEEN :cuttoff AND :paydate ORDER BY `time_credit_ot`.`trans_date` DESC");
    $ot_data->execute(array(":cuttoff" => $cutoff, ":paydate" => $pay_date));
    if ($ot_data->rowCount()) {
        while ($data = $ot_data->fetch(PDO::FETCH_ASSOC)) {
            $recid = uniqid();
            $pin = $data['pin'];
            $lname = $data['family_name'];
            $fname = $data['given_name'];
            $middle_name = $data['middle_name'];
            if($middle_name != ''){
                $mname=substr($data['middle_name'], 0, 1);
            }else{
                $mname='';
            }
            $name = $lname.', '.$fname.' '.$mname;
            $ot_time=$data['credit_time'];
            $trans_date=(new DateTime($data['trans_date']))->format("Y-m-d");

            $record = array("recid" => $recid, "pin"=> $pin, "name" => $name, "ot_hrs" => $ot_time, "date" => $trans_date);
            $records[] = $record;
        }
    }
    return $records;
}

function get_approve_ot() {
    global $db, $db_hris;

    $group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:name");
    $group->execute(array(":name" => 100));
    if ($group->rowCount()) {
        while ($pay_group = $group->fetch(PDO::FETCH_ASSOC)) {
            $cutoff = $pay_group["cutoff_date"];
            $pay_date = $pay_group["payroll_date"];
        }
    }
    $records = array();

    $ot_data = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`time_credit_ot` WHERE !`master_data`.`is_inactive` AND `time_credit_ot`.`employee_no`=`master_data`.`pin` AND `time_credit_ot`.`is_approved` AND `trans_date` BETWEEN :cuttoff AND :paydate ORDER BY `time_credit_ot`.`trans_date` DESC");
    $ot_data->execute(array(":cuttoff" => $cutoff, ":paydate" => $pay_date));
    if ($ot_data->rowCount()) {
        while ($data = $ot_data->fetch(PDO::FETCH_ASSOC)) {
            $recid = uniqid();
            $pin = $data['pin'];
            $lname = $data['family_name'];
            $fname = $data['given_name'];
            $middle_name = $data['middle_name'];
            if($middle_name != ''){
                $mname=substr($data['middle_name'], 0, 1);
            }else{
                $mname='';
            }
            $name = $lname.', '.$fname.' '.$mname;
            $ot_time=$data['credit_time'];
            $trans_date=(new DateTime($data['trans_date']))->format("Y-m-d");

            $record = array("recid" => $recid, "pin"=> $pin, "name" => $name, "ot_hrs" => $ot_time, "date" => $trans_date);
            $records[] = $record;
        }
    }
    return $records;
}

function save_manual_time($emp_no,$log_type,$ndate,$ntime){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
    $master->execute(array(":no"=> $emp_no));
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $emp_pin = $master_data['pin'];
            
            $save_time = $db->prepare("INSERT INTO $db_hris.`_tmp_imported_att` (`pin`, `Date`, `_time`, `Verified`, `Status`, `get_by`) VALUES (:pin, :_date, :_time, :ver, :stat, :userid)");
            $save_time->execute(array(":pin" => $emp_pin, ":_date" => $ndate, ":_time" => $ntime, ":ver" => '0', ":stat" => $log_type, ":userid" => $_SESSION['name']));

        }
        echo json_encode(array("status" => "success"));
    }

}

function get_imported_time() {
    global $db, $db_hris;
    
    $records = array();

    $att_log = $db->prepare("SELECT * FROM $db_hris.`_tmp_imported_att`,$db_hris.`master_data` WHERE `master_data`.`pin`=`_tmp_imported_att`.`pin` AND !`master_data`.`is_inactive` ORDER BY `_tmp_imported_att`.`Date`,`_tmp_imported_att`.`_time` ASC");
    $att_log->execute();
    if ($att_log->rowCount()) {
        while ($data = $att_log->fetch(PDO::FETCH_ASSOC)) {
            $emp_no = "100".$data['attendance_log_id'];
            $pin=$data['pin'];
            $middle_name=$data['middle_name'];
            if($middle_name != ''){
                $mname=substr($data['middle_name'], 0, 1);
            }else{
                $mname='';
            }
            $name=$data['family_name'].', '.$data['given_name'].' '.$mname.'.';
            $dt=$data['Date'];
            $time=$data['_time'];
            $ver=$data['Verified'];
            if($ver == 1){
                $verify = 'Fingerprint Verified';
            }else{
                $verify = 'Fingerprint Not Verified';
            }
            $stat=$data['Status'];
            if($stat == 1){
                $rm = '<span class="w3-text-red">Time Out</span>';
            }else if($stat == 2){
                $rm = '<span class="w3-text-orange">Break-Time</span>';
            }else if($stat == 3){
                $rm = '<span class="w3-text-orange">Coffee-Break</span>';
            }else{
                $rm = '<span class="w3-text-green">Time In</span>';
            }
            $by=$data['get_by'];

            $record = array("recid" => $emp_no, "pin"=> $pin, "name" => $name, "date" => $dt, "time" => $time, "ver" => $verify, "stat" => $rm, "by" => $by);
            $records[] = $record;
        }
    }
    return $records;
}


function get_log_type() {
    global $db, $db_hris;

    $logs = array();

    $log_type = $db->prepare("SELECT * FROM $db_hris.`log_type` ORDER BY `log_type_no` ASC");
    $log_type->execute();
    if ($log_type->rowCount()) {
        while ($log_type_data = $log_type->fetch(PDO::FETCH_ASSOC)) {
            $log_value = $log_type_data['log_value'];
            $log_message=$log_type_data['log_message'];
            
            $logs[] = array("id" => $log_value, "text"=> $log_message);
        }
    }
    echo json_encode(array("status" => "success", "log_type" => $logs));
}

function confirm_attendance() {
    global $db, $db_hris;

    $cfn = $db->prepare("SELECT * FROM $db_hris.`_tmp_imported_att`");
    $cfn->execute();
    if ($cfn->rowCount()) {
        while ($data = $cfn->fetch(PDO::FETCH_ASSOC)) {
            $pin=$data['pin'];
            $date=$data['Date'];
            $time=$data['_time'];
            $is_swipe=$data['Verified'];
            $log_value=$data['Status'];

            $dayofweek = date('w', strtotime($date));
            
            $check_time = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date`=:date AND `log_time`=:time");
            $check_time->execute(array(":pin" => $pin, ":date" => $date, ":time" => $time));

            if ($check_time->rowCount()){
                $check_data = $check_time->fetch(PDO::FETCH_ASSOC);
                $emp_pin = $check_data['pin'];

                $update_time = $db->prepare("UPDATE $db_hris.`attendance_log` SET `user_id`=:uid, `station_id`=:ip WHERE `pin`=:pin");
                $update_time->execute(array(":pin" => $emp_pin, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));

            }else{
                $new_time = $db->prepare("INSERT INTO $db_hris.`attendance_log` (`pin`, `log_type`, `log_date`, `log_time`, `log_day`, `is_swipe`, `user_id`, `station_id`) VALUES (:id, :type, :ldate, :ltime, :lday, :swipe, :uid, :ip)");
                $new_time->execute(array(":id" => $pin, ":type" => $log_value, ":ldate" => $date, ":ltime" => $time, ":lday" => $dayofweek, ":swipe" => $is_swipe, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
                
            }
        }
    }
    delete_imported_attendace();
}

function delete_imported_attendace() {
    global $db, $db_hris;

    $del_att = $db->prepare("DELETE FROM $db_hris.`_tmp_imported_att`");
    $del_att->execute();

    $records = get_imported_time();
    echo json_encode(array("status" => "success", "records" => $records));
}