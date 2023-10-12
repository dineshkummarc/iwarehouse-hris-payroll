<?php

$program_code_sss = 23;
$program_code_ph = 25;
$program_code_love = 26;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights_ph = $cfn->get_user_rights($program_code_ph);
$plevel_ph = $cfn->get_program_level($program_code_ph);
$access_rights_love = $cfn->get_user_rights($program_code_love);
$plevel_love = $cfn->get_program_level($program_code_love);
$access_rights_sss = $cfn->get_user_rights($program_code_sss);
$plevel_sss = $cfn->get_program_level($program_code_sss);
$level = $cfn->get_user_level();

if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "set-grid":
                    $group = get_group();
                    $cutoff = get_cutoff();
                    if($_POST["option"] === "sss"){
                        $access_rights = $access_rights_sss;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $tool = get_toolbar($access_rights);
                            $grid = get_grid($_POST["option"]);
                        }
                    }
                    if($_POST["option"] === "phil"){
                        $access_rights = $access_rights_ph;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $tool = get_toolbar($access_rights);
                            $grid = get_grid($_POST["option"]);
                        }
                    }
                    if($_POST["option"] === "love"){
                        $access_rights = $access_rights_love;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $tool = get_toolbar($access_rights);
                            $grid = get_grid($_POST["option"]);
                        }
                    }
                    echo json_encode(array("status" => "success", "group" => $group, "cutoff" => $cutoff, "tool" => $tool, "column" => $grid));
                    break;
                case "get-records":
                    $pdate = $_REQUEST["_date"];
                    $pgroup = $_REQUEST["_group"];
                    if($_POST["option"] === "sss"){
                        $access_rights = $access_rights_sss;
                        if (substr($access_rights, 0, 4) === "A+E+") {
                            $records = get_records($pdate,$pgroup,$_POST["option"]);
                        }
                    }
                    if($_POST["option"] === "phil"){
                        $access_rights = $access_rights_ph;
                        if (substr($access_rights, 0, 4) === "A+E+") {
                            $records = get_records($pdate,$pgroup,$_POST["option"]);
                        }
                    }
                    if($_POST["option"] === "love"){
                        $access_rights = $access_rights_love;
                        if (substr($access_rights, 0, 4) === "A+E+") {
                            $records = get_records($pdate,$pgroup,$_POST["option"]);
                        }
                    }
                    echo json_encode(array("status" => "success", "records" => $records));
                    break;
                case "print":
                    $pdate = $_REQUEST["paydate"];
                    $pgroup = $_REQUEST["pay_group"];
                    if($_REQUEST["option"] === "sss"){
                        $access_rights = $access_rights_sss;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $title = "SSS REPORT FOR THE MONTH OF " . $pdate;
                            $records = get_records($pdate,$pgroup,$_REQUEST["option"]);
                            $grid = get_grid($_REQUEST["option"]);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                            return;
                        }
                    }
                    if($_REQUEST["option"] === "phil"){
                        $access_rights = $access_rights_ph;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $title = "PHIL-HEALTH REPORT FOR THE MONTH OF " . $pdate;
                            $records = get_records($pdate,$pgroup,$_REQUEST["option"]);
                            $grid = get_grid($_REQUEST["option"]);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                            return;
                        }
                    }
                    if($_REQUEST["option"] === "love"){
                        $access_rights = $access_rights_love;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $title = "PAG-IBIG REPORT FOR THE MONTH OF " . $pdate;
                            $records = get_records($pdate,$pgroup,$_REQUEST["option"]);
                            $grid = get_grid($_REQUEST["option"]);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                            return;
                        }
                    }
                    $cfn->print_register(array("columns" => array("column" => $grid), "records" => $records, "title" => $title, "is_line_number" => true, "no-company" => true, "footnote" => "<span class=\"w3-tiny\">PRINTED BY: $_SESSION[name]</span>", "footnote-date" => TRUE));
                    break;
                case "export":
                    $pdate = $_REQUEST["paydate"];
                    $pgroup = $_REQUEST["pay_group"];
                    if($_REQUEST["option"] === "sss"){
                        $access_rights = $access_rights_sss;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $title = "SSS REPORT FOR THE MONTH OF " . $pdate;
                            $records = get_records($pdate,$pgroup,$_REQUEST["option"]);
                            $grid = get_grid($_REQUEST["option"]);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                            return;
                        }
                    }
                    if($_REQUEST["option"] === "phil"){
                        $access_rights = $access_rights_ph;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $title = "PHIL-HEALTH REPORT FOR THE MONTH OF " . $pdate;
                            $records = get_records($pdate,$pgroup,$_REQUEST["option"]);
                            $grid = get_grid($_REQUEST["option"]);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                            return;
                        }
                    }
                    if($_REQUEST["option"] === "love"){
                        $access_rights = $access_rights_love;
                        if (substr($access_rights, 6, 2) === "B+") {
                            $title = "PAG-IBIG REPORT FOR THE MONTH OF " . $pdate;
                            $records = get_records($pdate,$pgroup,$_REQUEST["option"]);
                            $grid = get_grid($_REQUEST["option"]);
                        }else{
                            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                            return;
                        }
                    }
                    $cfn->download_csv($grid, $records, $filename = $title.".csv");
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

function get_records($paydate, $pay_group, $option){
    if($option === "sss"){
        return get_sssrecords($paydate, $pay_group);
    }
    if($option === "phil"){
        return get_phrecords($paydate, $pay_group);
    }
    if($option === "love"){
        return get_loverecords($paydate, $pay_group);
    }
}

//pag-ibig records
function get_loverecords($paydate, $pay_group) {
    global $db, $db_hris;

    $date = (new DateTime(str_replace(",", " 1,", $paydate)))->format("Y-m-10");

    $group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name` LIKE :grp");
    $group->execute(array(":grp" => $pay_group));
    if ($group->rowCount()) {
        $group_data = $group->fetch(PDO::FETCH_ASSOC);
        $payroll_group_no = $group_data["group_name"];
    } else {
        $payroll_group_no = 0;
    }
    $pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` INNER JOIN $db_hris.`master_data` ON `master_data`.`employee_no`=`payroll_trans`.`employee_no` INNER JOIN $db_hris.`master_id` ON `master_id`.`employee_no`=`payroll_trans`.`employee_no` WHERE `payroll_trans`.`payroll_date` LIKE :date AND `payroll_trans`.`payroll_group_no`=:no ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
    $pay->execute(array(":date" => $date, ":no" => $payroll_group_no));
    if ($pay->rowCount()) {
        $ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:no AND `payroll_date` LIKE :date AND `deduction_no`=:ded_no");
        $records = array();
        $summary =array("w2ui" => array("summary" => true), "summary" => 1, "recid" => "", "fname" => "", "gname" => "", "mname" => "", "love_no" => "GRAND TOTALS", "pay" => 0, "ees" => 0, "ers" => 0, "total" => 0, "ec" => 0);
        while ($pay_data = $pay->fetch(PDO::FETCH_ASSOC)) {
            $ded->execute(array(":no" => $pay_data["employee_no"], ":date" => $date, ":ded_no"=> 107));
            if ($ded->rowCount()) {
                $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
                $record = array("recid" => $pay_data["employee_no"], "fname" => $pay_data["family_name"], "gname" => $pay_data["given_name"], "mname" => substr($pay_data["middle_name"], 0, 1), "love_no" => $pay_data["pag_ibig"]);
                $record["pay"] = $pay_data["grosspay_pagibig"];
                $record["ers"] = $record["ees"] = $ded_data["deduction_actual"];
                $record["total"] = number_format($record["ees"] + $record["ers"], 2, '.', '');
                $summary["pay"] += $record["pay"];
                $summary["ees"] += $record["ees"];
                $summary["ers"] += $record["ers"];
                $summary["total"] += $record["total"];
                $records[] = $record;
            }
        }
        if (count($records)) {
            $records[] = $summary;
        }
    }else{
        $records = array("recid" => "NO DATA", "fname" => "NO RECORDS AS OF $date");
    }
    return $records;
}

//philhealth records
function get_phrecords($paydate, $pay_group) {
    global $db, $db_hris;
    $date = (new DateTime(str_replace(",", " 1,", $paydate)))->format("Y-m-10");
    $group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name` LIKE :grp");
    $group->execute(array(":grp" => $pay_group));
    if ($group->rowCount()) {
        $group_data = $group->fetch(PDO::FETCH_ASSOC);
        $payroll_group_no = $group_data["group_name"];
    } else {
        $payroll_group_no = 0;
    }
    $pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` INNER JOIN $db_hris.`master_data` ON `master_data`.`employee_no`=`payroll_trans`.`employee_no` INNER JOIN $db_hris.`master_id` ON `master_id`.`employee_no`=`payroll_trans`.`employee_no` WHERE `payroll_trans`.`payroll_date` LIKE :date AND `payroll_trans`.`payroll_group_no`=:no ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
    $pay->execute(array(":date" => $date, ":no" => $payroll_group_no));
    if ($pay->rowCount()) {
        $ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:no AND `payroll_date` LIKE :date AND `deduction_no`=207");
        $records = array();
        $summary = array("w2ui" => array("summary" => true), "summary" => 1, "recid" => "", "fname" => "", "ph_no" => "GRAND TOTALS", "mname" => "", "gname" => "", "pay" => 0, "ees" => 0, "ers" => 0, "total" => 0, "ec" => 0);
        while ($pay_data = $pay->fetch(PDO::FETCH_ASSOC)) {
            $ded->execute(array(":no" => $pay_data["employee_no"], ":date" => $date));
            if ($ded->rowCount()) {
                $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
            $record = array("recid" => $pay_data["employee_no"], "fname" => $pay_data["family_name"], "gname" => $pay_data["given_name"], "mname" => substr($pay_data["middle_name"], 0, 1), "ph_no" => $pay_data["phil_health"]);
            $record["pay"] = $pay_data["grosspay_philhealth"];
            $record["ees"] = $ded_data["deduction_actual"];
            $record["ers"] = $ded_data["deduction_actual"];
            $record["total"] = number_format($record["ees"] + $record["ers"], 2, '.', '');
            $summary["pay"] += $record["pay"];
            $summary["ees"] += $record["ees"];
            $summary["ers"] += $record["ers"];
            $summary["total"] += $record["total"];
            $records[] = $record;
            }
        }
        if (count($records)) {
            $records[] = $summary;
        }
    }else{
        $records = $summary = array("recid" => "NO DATA", "fname" => "NO RECORDS AS OF $paydate");
    }
    return $records;
}
//end philhealth records

//sss records
function get_sssrecords($pdate,$pgroup) {
    global $db, $db_hris;
    
    $records = array();
    $date = (new DateTime(str_replace(",", " 1,", $pdate)))->format("Y-m");
    $group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name` LIKE :grp");
    $group->execute(array(":grp" => $pgroup));
    if($group->rowCount()) {
        $group_data = $group->fetch(PDO::FETCH_ASSOC);
        $payroll_group_no = $group_data["group_name"];
    }else{
        $payroll_group_no = 0;
    }
    $pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` INNER JOIN $db_hris.`master_data` ON `master_data`.`employee_no`=`payroll_trans`.`employee_no` INNER JOIN $db_hris.`master_id` ON `master_id`.`employee_no`=`payroll_trans`.`employee_no` WHERE `payroll_trans`.`payroll_date` LIKE :date AND `payroll_trans`.`payroll_group_no`=:no AND `payroll_trans`.`is_posted` GROUP BY `payroll_trans`.`employee_no` ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
    $pay->execute(array(":date" => '%'.$date.'%', ":no" => $payroll_group_no));
    if ($pay->rowCount()) {
        $sss = $db->prepare("SELECT * FROM $db_hris.`table_sss` WHERE `share_employee`>=:sss");
        $ded = $db->prepare("SELECT SUM(`deduction_actual`) AS `sss_amount` FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:no AND `payroll_date` LIKE :date AND `deduction_no`=7 GROUP BY `employee_no`");
        
        $summary = array("w2ui" => array("summary" => true), "summary" => 1, "recid" => "", "fname" => "", "gname" => "", "mname" => "", "sss_no" => "GRAND TOTALS",  "pay" => 0, "ees" => 0, "ers" => 0, "total" => 0, "ec" => 0);
        while ($pay_data = $pay->fetch(PDO::FETCH_ASSOC)) {
            $ded->execute(array(":no" => $pay_data["employee_no"], ":date" => '%'.$date.'%'));
            $record["recid"] = $pay_data["employee_no"];
            $record["fname"] = $pay_data["family_name"];
            $record["gname"] = $pay_data["given_name"];
            $record["mname"] = substr($pay_data["middle_name"], 0, 1);
            $record["sss_no"] = $pay_data["sss"];
            $record["pay"] = $pay_data["grosspay_sss"];
            if ($ded->rowCount()) {
                $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
                $record["ees"] = $ded_data["sss_amount"];
                $sss->execute(array(":sss" => $ded_data["sss_amount"]));
                if ($sss->rowCount()) {
                    $sss_data = $sss->fetch(PDO::FETCH_ASSOC);
                    $record["ers"] = $sss_data["share_employer"];
                    $record["ec"] = $sss_data["ecc"];
                }
                $record["total"] = $record["ees"] + $record["ers"];
                $summary["pay"] += $record["pay"];
                $summary["ees"] += $record["ees"];
                $summary["ers"] += $record["ers"];
                $summary["ec"] += $record["ec"];
                $summary["total"] += $record["total"];
            }
            $records[] = $record;
        }
        if (count($records)) {
            $records[] = $summary;
        }
    }else{
        $records = $summary = array("recid" => "NO DATA", "fname" => "NO RECORDS AS OF $pdate");
    }
    return $records;
}
//end

function get_grid($option) {
    $items = array();
    $items[] = array("field" => "fname", "caption" => "FAMILY NAME", "size" => "250px", "attr" => "align=left");
    $items[] = array("field" => "gname", "caption" => "GIVEN NAME", "size" => "250px", "attr" => "align=left");
    $items[] = array("field" => "mname", "caption" => "M.I.", "size" => "40px", "attr" => "align=center");
    if($option === "phil"){
        $items[] = array("field" => "ph_no", "caption" => "PHIL HEALTH NO", "size" => "120px", "attr" => "align=center");
    }
    if($option === "love"){
        $items[] = array("field" => "love_no", "caption" => "PAG-IBIG NO", "size" => "120px", "attr" => "align=center");
    }
    if($option === "sss"){
        $items[] = array("field" => "sss_no", "caption" => "SSS NO", "size" => "120px", "attr" => "align=center");
    }
    $items[] = array("field" => "pay", "caption" => "PAY", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ees", "caption" => "EE SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ers", "caption" => "ER SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "total", "caption" => "TOTAL", "size" => "100px", "render" => "float:2");
    if($option === "sss"){
        $items[] = array("field" => "ec", "caption" => "EC", "size" => "100px", "render" => "float:2");
    }
    return $items;
}

//default set
function get_cutoff() {
    $items = array();
    if (number_format(date("d"), 0, '.', '') > number_format(10, 0)) {
        $date = new DateTime(date("m/01/Y"));
    } else {
        $date = new DateTime(date("m/01/Y"));
        $date->modify("-1 month");
    }
    $count = 6;
    while ($count) {
        $items[] = strtoupper($date->format("M. Y"));
        $date->modify("-1 month");
        $count--;
    }
    return $items;
}

function get_group() {
    global $db, $db_hris;

    $group_list = $db->prepare("SELECT `employment_status`.`employment_status_code`,`employment_status`.`description` FROM $db_hris.`employment_status`,$db_hris.`payroll_group` WHERE `employment_status`.`employment_status_code`=`payroll_group`.`group_name`");
    $group = array();
    $group_list->execute();
    if ($group_list->rowCount()) {
        while ($data = $group_list->fetch(PDO::FETCH_ASSOC)) {
            $group[] = array("id" => $data["employment_status_code"], "text" => $data["description"]);
        }
    }
    return $group;
}

function get_toolbar($access_rights) {
    $html = '<input id="paydate" type="text" class="w3-input" size="30" />&nbsp;&nbsp;<input class="w3-input" id="pay_group" type="text" size="30" />';
    $items = array();
    $items[] = array("type" => "html", "html" => $html);
    $items[] = array("type" => "break");
    $items[] = array("type" => "button", "id" => "gen", "caption" => "GENERATE");
    if((substr($access_rights, 8, 2) === "P+")){
        $items[] = array("type" => "break");
        $items[] = array("type" => "button", "id" => "print", "caption" => "PRINT");
    }
    $items[] = array("type" => "break" );
    $items[] = array("type" => "button", "id" => "export", "caption" => "EXPORT");
    $items[] = array("type" => "break");
    return $items;
}
