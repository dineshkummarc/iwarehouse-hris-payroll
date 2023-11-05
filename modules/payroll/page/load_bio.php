<?php

$program_code = 12;
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
                case "get-wall-bio":
                    if (substr($access_rights, 0, 8) === "A+E+D+B+") {
                        if($level <= $plevel ){
                            echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                            return;
                        }
                        get_wall_bio($_POST["fdate"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-default":
                    if (substr($access_rights, 6, 2) !== "B+") {
                        if($level <= $plevel_att ){
                            echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                            return;
                        }
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }else{
                        $columns = get_columns();
                        $records = get_imported_time();
                        echo json_encode(array("status" => "success", "records" => $records, "columns" => $columns));
                    }
                break;
                case "confirm-bio":
                    if (substr($access_rights, 0, 8) === "A+E+D+B+") {
                        confirm_attendance();
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "delete-bio":
                    if (substr($access_rights, 4, 2) === "D+") {
                        delete_imported_attendace();
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-emp-list-and-reason":
                    if (substr($access_rights, 6, 2) === "B+") {
                        $log_type = get_log_type();
                        $emp_list = get_employee();
                        echo json_encode(array("status" => "success", "emp_list" => $emp_list, "log_type" => $log_type));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "save-manual-att":
                    if (substr($access_rights, 0, 2) === "A+") {
                        $record = array("pin" => $_POST["record"]["emp_list"]["id"], "log_type" => $_POST["record"]["att_reason"]["id"], "date" => (new DateTime($_POST["record"]["att_date"]))->format("Y-m-d"), "time" => (new DateTime($_POST["record"]["att_time"]))->format("H:i:s"));
                        save_manual_time($record);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "delete-att":
                    if (substr($access_rights, 4, 2) === "D+") {
                        $pin = substr($_POST["recid"],3);
                        del_attendance($pin);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "delete-all":
                    if (substr($access_rights, 4, 2) === "D+") {
                        del_all_attendance();
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
                case "upload_att_file":
                    if ($access_rights === "A+E+D+B+P+"){
                        if (isset($_POST["file"])) {
                            upload_att_file($_POST["file"]);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "Please try again later!"));
                        }
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights", "rights" => $access_rights));
                        return;
                    }
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

function get_wall_bio($fdate){
    global $db, $db_hris;
    
    $ndate = (new DateTime($fdate))->format("Y-m-d");
    $ip = sysconfig("bio-ip");
    require_once('../lib/zklibrary.php');

    $zk = new ZKLibrary($ip, 4370);
    $zk->connect();
    $zk->disableDevice();
    $att_log = $zk->getAttendance();
    foreach ($att_log as $key => $data){
        $pin = $data[1];
        $date = (new DateTime($data[3]))->format("Y-m-d");
        $time = (new DateTime($data[3]))->format("H:i:s");
        $ver = '1';
        $status = $data[2];
        if($date >= $ndate){
            $time_check = $db->prepare("SELECT * FROM $db_hris.`_tmp_imported_att` WHERE `Date`=:date and `_time`=:time and `pin`=:pin");
            $time_check->execute(array(":date" => $date, ":time" => $time, ":pin" => $pin));
            if($time_check->rowCount()){
                $update = $db->prepare("UPDATE $db_hris.`_tmp_imported_att` SET `Status`=:status, `Date`=:date, `_time`=:time, Verified=:ver WHERE pin=:pin");
                $update->execute(array(":status" => $status, ":date" => $date, ":time" => $time, ":ver" => $ver, ":pin" => $pin));
            }else{
                $update = $db->prepare("INSERT INTO $db_hris.`_tmp_imported_att` (`pin`, `Date`, `_time`, `Verified`, `Status`, `get_by`) VALUES (:pin, :date, :time, :ver, :status, :uid)");
                $update->execute(array(":pin" => $pin, ":date" => $date, ":time" => $time, ":ver" => $ver, ":status" => $status, ":uid" => $_SESSION["name"]));
            }
        }
    }
    $zk->clearAttendance();
    $zk->enableDevice();
    $zk->disconnect();
    echo json_encode(array("status" => "success", "ip" => $ip, "message" => "Wall Biometric Attendance Imported", "e" => "Error connecting in biometric $ip"));
}

function sysconfig($config_name) {
	global $db, $db_hris;

	$sysconfig = $db->prepare("SELECT `config_value` FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :config");
    $sysconfig->execute(array(":config" => $config_name));
	if ($sysconfig->rowCount()) {
		$sysconfig_data = $sysconfig->fetch(PDO::FETCH_ASSOC);
		$config_value = $sysconfig_data['config_value'];
	}else{
		$config_value = "";
    }
	return $config_value;
}

function del_all_attendance(){
    global $db, $db_hris;

    $del = $db->prepare("DELETE FROM $db_hris.`_tmp_imported_att`");
    $del->execute();
    echo json_encode(array("status" => "success", "message" => "All attendance deleted!"));
}

function upload_att_file($files) {
    global $db, $db_hris;

    set_time_limit(300);
    $csv = base64_decode($files);
    $file_name = tempnam("../", "tmp");
    $file = fopen($file_name, "w");
    fwrite($file, $csv);
    fclose($file);
    $file_handle = fopen($file_name, "r");
    $codes = fgetcsv($file_handle, 1000, ',', '"');
    if ($codes !== false) { // Check if codes were successfully read
        $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin` LIKE :pin AND !`is_inactive`");
        while (($file_content = fgetcsv($file_handle, 1000, ',', '"')) !== false) {
            if (!empty($file_content[0])) {
                $master->execute(array(":pin" => $file_content[0]));
                if (!$master->rowCount()) {
                    $master->execute(array(":pin" => number_format($file_content[0], 0, '.', '')));
                }
                if (!$master->rowCount()) {
                    $master->execute(array(":pin" => "0" . number_format($file_content[0], 0, '.', '')));
                }
                if ($master->rowCount()) {
                    $master_data = $master->fetch(PDO::FETCH_ASSOC);
                    
                    $ins = $db->prepare("INSERT INTO $db_hris.`_tmp_imported_att` SET `pin`=:pin, `Date`=:date, `_time`=:time, `Verified`=:ver, `Status`=:stat");
                    if(!empty($file_content[3])){
                        $ins->execute(array(":pin" => $master_data["pin"], ":date" => (new DateTime($file_content[2]))->format("Y-m-d"), ":time" => $file_content[3], ":ver" => '0', ":stat" => 0));
                    }
                    if(!empty($file_content[4])){
                        $ins->execute(array(":pin" => $master_data["pin"], ":date" => (new DateTime($file_content[2]))->format("Y-m-d"), ":time" => $file_content[4], ":ver" => '0', ":stat" => 2));
                    }
                    if(!empty($file_content[5])){
                        $ins->execute(array(":pin" => $master_data["pin"], ":date" => (new DateTime($file_content[2]))->format("Y-m-d"), ":time" => $file_content[5], ":ver" => '0', ":stat" => 0));
                    }
                    if(!empty($file_content[6])){
                        $ins->execute(array(":pin" => $master_data["pin"], ":date" => (new DateTime($file_content[2]))->format("Y-m-d"), ":time" => $file_content[6], ":ver" => '0', ":stat" => 2));
                    }
                    if(!empty($file_content[7])){
                        $ins->execute(array(":pin" => $master_data["pin"], ":date" => (new DateTime($file_content[2]))->format("Y-m-d"), ":time" => $file_content[7], ":ver" => '0', ":stat" => 0));
                    }
                    if(!empty($file_content[8])){
                        $ins->execute(array(":pin" => $master_data["pin"], ":date" => (new DateTime($file_content[2]))->format("Y-m-d"), ":time" => $file_content[8], ":ver" => '0', ":stat" => 1));
                    }
                }
            }
        }
    }
    unlink($file_name);
    echo json_encode(array("status" => "success", "master" => $master->errorInfo(), "csv" => $csv, "codes" => $codes));
}

function get_columns(){
    $items = array();
    $items[] = array('field' => 'recid', 'caption' => 'No', 'size' => '100px', "hidden" => true );
    $items[] = array('field' => 'pin', 'caption' => 'EMPLOYEE NO', 'size' => '100px' );
    $items[] = array('field' => 'name', 'caption' => 'NAME', 'size' => '300px' );
    $items[] = array('field' => 'date', 'caption' => 'Date', 'size' => '200px' );
    $items[] = array('field' => 'time', 'caption' => 'Time', 'size' => '200px' );
    $items[] = array('field' => 'ver', 'caption' => 'Verified', 'size' => '5px;' );
    $items[] = array('field' => 'stat', 'caption' => 'Status', 'size' => '5px;' );
    $items[] = array('field' => 'by', 'caption' => 'Imported By', 'size' => '10px;' );
    return $items;
}

function del_attendance($pin) {
    global $db, $db_hris;

    $del_att = $db->prepare("DELETE FROM $db_hris.`_tmp_imported_att` WHERE `attendance_log_id`=:pin");
    $del_att->execute(array(":pin"=>$pin));
    if($del_att->rowCount()){
        echo json_encode(array("status" => "success"));
    }
}

function save_manual_time($record){
    global $db, $db_hris;
    
    $save_time = $db->prepare("INSERT INTO $db_hris.`_tmp_imported_att` (`pin`, `Date`, `_time`, `Verified`, `Status`, `get_by`) VALUES (:pin, :_date, :_time, :ver, :stat, :userid)");
    $save_time->execute(array(":pin" => $record["pin"], ":_date" => $record["date"], ":_time" => $record["time"], ":ver" => '0', ":stat" => $record["log_type"], ":userid" => $_SESSION['name']));
    if($save_time->rowCount()){
        echo json_encode(array("status" => "success", "message" => "Time Encoded!"));
    }else{
        echo json_encode(array("status" => "success", "message" => "Error saving time!"));
    }
}

function get_imported_time() {
    global $db, $db_hris;
    
    $records = array();
    $att_log = $db->prepare("SELECT * FROM $db_hris.`_tmp_imported_att`,$db_hris.`master_data` WHERE `master_data`.`pin`=`_tmp_imported_att`.`pin` AND !`master_data`.`is_inactive` ORDER BY `_tmp_imported_att`.`Date`,`_tmp_imported_att`.`_time` ASC");
    $att_log->execute();
    if ($att_log->rowCount()) {
        while ($data = $att_log->fetch(PDO::FETCH_ASSOC)) {
            $record["recid"] = "100".$data['attendance_log_id'];
            $record["pin"] = $data['pin'];
            $mname = $data['middle_name'] != '' ? substr($data['middle_name'], 0, 1) : '';
            $record["name"] = $data['family_name'].', '.$data['given_name'].' '.$mname;
            $record["date"] = $data['Date'];
            $record["time"] = $data['_time'];
            $record["ver"] = $data['Verified'] ? 'Fingerprint Verified' : 'Fingerprint Not Verified';
            if($data['Status'] == 1){
                $record["stat"] = '<span class="w3-text-red">Time Out</span>';
            }else if($data['Status'] == 2){
                $record["stat"] = '<span class="w3-text-orange">Break-Time</span>';
            }else if($data['Status'] == 3){
                $record["stat"] = '<span class="w3-text-orange">Coffee-Break</span>';
            }else{
                $record["stat"] = '<span class="w3-text-green">Time In</span>';
            }
            $record["by"] = $data['get_by'];
            array_push($records, $record);
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
            $logs[] = array("id" => $log_type_data['log_value'], "text"=> $log_type_data['log_message']);
        }
    }
    return $logs;
}

function get_employee() {
    global $db, $db_hris;

    $employee = array();
    $master = $db->prepare("SELECT `pin`,`given_name`,`middle_name`,`family_name`,`employee_no` FROM $db_hris.`master_data` WHERE !`is_inactive` ORDER BY `family_name` ASC");
    $master->execute();
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $mname = $master_data['middle_name'] != '' ? substr($master_data['middle_name'], 0, 1) : '';
            $name = $master_data['family_name'].', '.$master_data['given_name'].' '.$mname;
            $employee[] = array("id" => $master_data['pin'], "text"=> $name);
        }
    }
    return $employee;
}

function confirm_attendance() {
    global $db, $db_hris;

    $cfn = $db->prepare("SELECT * FROM $db_hris.`_tmp_imported_att`");
    $cfn->execute();
    if ($cfn->rowCount()) {
        while ($data = $cfn->fetch(PDO::FETCH_ASSOC)) {
            $dayofweek = date('w', strtotime($data['_time']));
            
            $check_time = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date`=:date AND `log_time`=:time");
            $check_time->execute(array(":pin" => $data['pin'], ":date" => $data['Date'], ":time" => $data['_time']));
            if ($check_time->rowCount()){
                $check_data = $check_time->fetch(PDO::FETCH_ASSOC);
                $update_time = $db->prepare("UPDATE $db_hris.`attendance_log` SET `user_id`=:uid, `station_id`=:ip WHERE `pin`=:pin");
                $update_time->execute(array(":pin" => $check_data['pin'], ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
            }else{
                $new_time = $db->prepare("INSERT INTO $db_hris.`attendance_log` (`pin`, `log_type`, `log_date`, `log_time`, `log_day`, `is_swipe`, `user_id`, `station_id`) VALUES (:id, :type, :ldate, :ltime, :lday, :swipe, :uid, :ip)");
                $new_time->execute(array(":id" => $data['pin'], ":type" => $data['Status'], ":ldate" => $data['Date'], ":ltime" => $data['_time'], ":lday" => $dayofweek, ":swipe" => $data['Verified'], ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
            }
        }
    }
    delete_imported_attendace();
}

function delete_imported_attendace() {
    global $db, $db_hris;

    $del_att = $db->prepare("DELETE FROM $db_hris.`_tmp_imported_att`");
    $del_att->execute();
    
    echo json_encode(array("status" => "success", "message" => "Attendace confirmed!"));
}