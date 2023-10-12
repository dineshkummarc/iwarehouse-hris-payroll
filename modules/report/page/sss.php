<?php
$program_code = 23;
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
                case "get-sssrecords":
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        $pdate = $_REQUEST["_date"];
                        $pgroup = $_REQUEST["_group"];
                        $records = get_sssrecords($pdate,$pgroup);
                        echo json_encode(array("status" => "success", "records" => $records));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "set-grid":
                    if (substr($access_rights, 6, 2) !== "B+") {
                        if($level <= $plevel ){
                            echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                            return;
                        }
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }else{
                        $group = get_group();
                        $cutoff = get_cutoff();
                        $tool = get_toolbar();
                        $grid = get_gridsss_column();
                        echo json_encode(array("status" => "success", "group" => $group, "cutoff" => $cutoff, "tool" => $tool, "column" => $grid));
                    }
                    break;
                case "print":
                    if (substr($access_rights, 8, 2) === "P+") {
                        $pdate = $_REQUEST["paydate"];
                        $pgroup = $_REQUEST["pay_group"];
                        $records = get_sssrecords($pdate,$pgroup);
                        $grid = get_gridsss_column();
                        $title = "SSS REPORT FOR THE MONTH OF " . $_REQUEST["paydate"];
                        $cfn->print_register(array("columns" => array("column" => $grid), "records" => $records, "title" => $title, "is_line_number" => FALSE, "no-company" => true, "footnote" => "<span class=\"w3-tiny\">PRINTED BY: $_SESSION[name]</span>", "footnote-date" => TRUE));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "export":
                    if (substr($access_rights, 8, 2) === "P+") {
                        $pdate = $_REQUEST["paydate"];
                        $pgroup = $_REQUEST["pay_group"];
                        $date = new DateTime($pdate);
                        $records = get_sssrecords($pdate,$pgroup);
                        $grid = get_gridsss_column();
                        $cfn->download_csv($grid, $records, $filename = "SSS-".$pgroup.".".$date->format('m-d-Y').".csv");
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

//sss grid & records
function get_gridsss_column() {
    $items = array();
    $items[] = array("field" => "fname", "caption" => "FAMILY NAME", "size" => "250px");
    $items[] = array("field" => "gname", "caption" => "GIVEN NAME", "size" => "250px");
    $items[] = array("field" => "mname", "caption" => "M.I.", "size" => "40px");
    $items[] = array("field" => "sss_no", "caption" => "SSS NO", "size" => "120px");
    $items[] = array("field" => "pay", "caption" => "PAY", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ees", "caption" => "EE SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ers", "caption" => "ER SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "total", "caption" => "TOTAL", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ec", "caption" => "EC", "size" => "100px", "render" => "float:2");
    return $items;
}

function get_sssrecords($pdate,$pgroup) {
    global $db, $db_hris;
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
        
        $records = array();
        $summary = array("w2ui" => array("summary" => true), "summary" => 1, "recid" => "sss","fname" => "", "gname" => "GRAND TOTALS", "mname" => "", "sss_no" => "", "pay" => 0, "ees" => 0, "ers" => 0, "total" => 0, "ec" => 0);
        while ($pay_data = $pay->fetch(PDO::FETCH_ASSOC)) {
            $ded->execute(array(":no" => $pay_data["employee_no"], ":date" => '%'.$date.'%'));
            if ($ded->rowCount()) {
                $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
                $sss->execute(array(":sss" => $ded_data["sss_amount"]));
                if ($sss->rowCount()) {
                    $sss_data = $sss->fetch(PDO::FETCH_ASSOC);
                } else {
                    $sss_data = array();
                }
                $record = array("recid" => $pay_data["employee_no"], "fname" => $pay_data["family_name"], "gname" => $pay_data["given_name"], "mname" => substr($pay_data["middle_name"], 0, 1), "sss_no" => $pay_data["sss"]);
                $record["pay"] = $pay_data["grosspay_sss"];
                $record["ees"] = $ded_data["sss_amount"];
                $record["ers"] = $sss_data["share_employer"];
                $record["ec"] = $sss_data["ecc"];
                $record["total"] = number_format($record["ees"] + $record["ers"], 2, '.', '');
                $summary["pay"] += $record["pay"];
                $summary["ees"] += $record["ees"];
                $summary["ers"] += $record["ers"];
                $summary["ec"] += $record["ec"];
                $summary["total"] += $record["total"];
                $records[] = $record;
            }
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
        $items[] = strtoupper($date->format("M, Y"));
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

function get_toolbar() {
    $html = '<input id="paydate" type="text" class="w3-input" size="30" />&nbsp;&nbsp;<input class="w3-input" id="pay_group" type="text" size="30" />';
    $items = array();
    $items[] = array("type" => "html", "html" => $html);
    $items[] = array("type" => "break");
    $items[] = array("type" => "button", "id" => "gen", "caption" => "GENERATE");
    $items[] = array("type" => "break");
    $items[] = array("type" => "button", "id" => "print", "caption" => "PRINT", "hidden" => true);
    $items[] = array("type" => "break", "hidden" => true);
    $items[] = array("type" => "button", "id" => "export", "caption" => "EXPORT");
    $items[] = array("type" => "break");
    return $items;
}
//end