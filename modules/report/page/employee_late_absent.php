<?php

$program_code_late = 33;
$program_code_abs = 34;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights_late = $cfn->get_user_rights($program_code_late);
$plevel_late = $cfn->get_program_level($program_code_late);
$access_rights_abs = $cfn->get_user_rights($program_code_abs);
$plevel_abs = $cfn->get_program_level($program_code_abs);
$level = $cfn->get_user_level();

if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "set-grid": //ok
                    $columns = get_grid($_POST["option"]);
                    echo json_encode(array("status" => "success", "columns" => $columns));
                break;
                case "get-absent-records":
                    if (substr($access_rights_abs, 6, 2) === "B+") {
                        getAbsentee($_REQUEST["fr"],$_REQUEST["to"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "get-late-records":
                    if (substr($access_rights_late, 6, 2) === "B+") {
                        getLateEmp($_REQUEST["fr"],$_REQUEST["to"]);
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

//get the employee absent
function  getAbsentee($from, $to){
    global $db, $db_hris;

    $df = new DateTime($from);
    $dt = new DateTime($to);
    $records = array();

    $master = $db->prepare("SELECT SUM(`employee_absent`.`is_absent`) AS `is_absent`,`master_data`.`family_name`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`pin`,`master_data`.`employee_no` FROM $db_hris.`employee_absent`,$db_hris.`master_data` WHERE `employee_absent`.`is_absent` AND `employee_absent`.`employee_no`=`master_data`.`employee_no` AND `employee_absent`.`absent_date`>=:df AND `employee_absent`.`absent_date`<=:dt GROUP BY `employee_absent`.`employee_no` ORDER BY SUM(`employee_absent`.`is_absent`) DESC");
    $master->execute(array(":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($master->rowCount()){
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
            $record = array();
            $record["recid"] = $master_data["pin"];
            $record["name"] = $master_data["family_name"] . ", " . $master_data["given_name"] . " " . substr($master_data["middle_name"], 0, 1);
            $record["total"] = $master_data["is_absent"];
            $record["w2ui"]["children"] = getEmpAbsent($master_data["employee_no"], $df, $dt);
            $records[] = $record;
        }
        echo json_encode(array("status" => "success", "records" => $records, "fdate" => $from, "tdate" => $to));
    }else{
        echo json_encode(array("status" => "error", "message" => "No Records of Late found!", "fdate" => $from, "tdate" => $to));
    }
}

function getEmpAbsent($emp_no, $df, $dt){
    global $db, $db_hris;

    $abs = $db->prepare("SELECT * FROM $db_hris.`employee_absent` WHERE `employee_no`=:no AND `is_absent` AND `absent_date`>=:df AND `absent_date`<=:dt ORDER BY `absent_date`");
    $abs->execute(array(":no" => $emp_no, ":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($abs->rowCount()){
        $cnt = 0;
        while($abs_data = $abs->fetch(PDO::FETCH_ASSOC)){
            $data = "Date: " .(new Datetime($abs_data["absent_date"]))->format("m-d-Y");
            $records[] = array("recid" => ++$cnt, "name" => $data, "w2ui" => array("style" => "background-color: red; color: white;"));
        }
    }
    return $records;
}

//get the employee late
function getLateEmp($from, $to){
    global $db, $db_hris;

    $df = new DateTime($from);
    $dt = new DateTime($to);
    $records = array();

    $master = $db->prepare("SELECT SUM(`employee_late`.`isLate`) AS `isLate`,`master_data`.`family_name`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`pin`,`master_data`.`employee_no` FROM $db_hris.`employee_late`,$db_hris.`master_data` WHERE `employee_late`.`employee_no`=`master_data`.`employee_no` AND `employee_late`.`trans_date`>=:df AND `employee_late`.`trans_date`<=:dt GROUP BY `employee_late`.`employee_no` ORDER BY SUM(`employee_late`.`isLate`) DESC");
    $master->execute(array(":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($master->rowCount()){
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
            $record = array();
            $record["recid"] = $master_data["pin"];
            $record["name"] = $master_data["family_name"] . ", " . $master_data["given_name"] . " " . substr($master_data["middle_name"], 0, 1);
            $record["total"] = $master_data["isLate"];
            $record["w2ui"]["children"] = getLateRecords($master_data["employee_no"], $df, $dt);
            $records[] = $record;
        }
        echo json_encode(array("status" => "success", "records" => $records, "fdate" => $from, "tdate" => $to));
    }else{
        echo json_encode(array("status" => "error", "message" => "No Records of Late found!", "fdate" => $from, "tdate" => $to));
    }
}

function getLateRecords($emp_no, $df, $dt){
    global $db, $db_hris;

    $late_records = array();

    $late = $db->prepare("SELECT `trans_date`, `mins_late`, `start_time`, `log_time` FROM $db_hris.`employee_late` WHERE `employee_no`=:no AND `trans_date`>=:df AND `trans_date`<=:dt ORDER BY `trans_date` DESC");
    $late->execute(array(":no" => $emp_no, ":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($late->rowCount()){
        $cnt = 0;
        while($late_data = $late->fetch(PDO::FETCH_ASSOC)){
            $data = "Date: " . $late_data["trans_date"] . "| Mins late: " . $late_data["mins_late"]. "| Duty Start: " .(new DateTime($late_data["start_time"]))->format("h:i a") . "| Log Time: " . (new DateTime($late_data["log_time"]))->format("h:i:s a");
            $late_records[] = array("recid" => ++$cnt, "name" => $data, "w2ui" => array("style" => "background-color: red; color: white;"));
        }
    }

    return $late_records;
}


function get_grid($option) {
    if($option === "late"){
        $cap = "TOTAL LATE";
        $size = "100px";
    }else{
        $cap = "TOTAL ABSENT'S";
        $size = "150px";
    }
    $items = array();
    $items[] = array("field" => "recid", "caption" => "EMPLOYEE NO", "size" => "100px", "attr" => "align=center", "info" => true);
    $items[] = array("field" => "name", "caption" => "FAMILY NAME", "size" => "500px", "attr" => "align=left");
    $items[] = array("field" => "total", "caption" => $cap, "size" => $size, "attr" => "align=center");
    return $items;
}