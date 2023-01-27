<?php

$program_code = 3;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();

switch ($_REQUEST["cmd"]) {
    case "get-sssrecords":
        $pdate = $_REQUEST["_date"];
        $pgroup = $_REQUEST["_group"];
        $records = get_sssrecords($pdate,$pgroup);
        echo json_encode(array("status" => "success", "records" => $records));
    break;
    case "set-grid-philhealth":
        $group = get_group();
        $cutoff = get_cutoff();
        $tool = get_toolbar();
        $grid = get_gridph_column();
        echo json_encode(array("status" => "success", "group" => $group, "cutoff" => $cutoff, "tool" => $tool, "column" => $grid));
    break;
    case "get-phrecords":
        $records = get_phrecords($_REQUEST["paydate"], $_REQUEST["pay_group"]);
        echo json_encode(array("status" => "success", "records" => $records));
    break;
    case "get-loverecords":
        $records = get_loverecords($_REQUEST["paydate"], $_REQUEST["pay_group"]);
        echo json_encode(array("status" => "success", "records" => $records));
    break;
    case "set-grid-sss":
        $group = get_group();
        $cutoff = get_cutoff();
        $tool = get_toolbar();
        $grid = get_gridsss_column();
        echo json_encode(array("status" => "success", "group" => $group, "cutoff" => $cutoff, "tool" => $tool, "column" => $grid));
    break;
    case "print_sss":
        $pdate = $_REQUEST["paydate"];
        $pgroup = $_REQUEST["pay_group"];
        $records = get_sssrecords($pdate,$pgroup);
        $grid = get_gridsss_column();
        $title = "SSS REPORT FOR THE MONTH OF " . $_REQUEST["paydate"];
        $cfn->print_register(array("columns" => array("column" => $grid), "records" => $records, "title" => $title, "is_line_number" => FALSE, "no-company" => true, "footnote" => "<span class=\"w3-tiny\">PRINTED BY: $_SESSION[name]</span>", "footnote-date" => TRUE));
        break;
    case "export-sss":
        $pdate = $_REQUEST["paydate"];
        $pgroup = $_REQUEST["pay_group"];
        $records = get_sssrecords($pdate,$pgroup);
        $grid = get_gridsss_column();
        $cfn->download_csv($grid, $records);
    break;
    case "export-ph":
        $paydate = $_REQUEST["paydate"];
        $pay_group = $_REQUEST["pay_group"];
        $records = get_phrecords($paydate,$pay_group);
        $grid = get_gridph_column();
        $cfn->download_csv($grid, $records);
    break;
    case "export-love":
        $paydate = $_REQUEST["paydate"];
        $pay_group = $_REQUEST["pay_group"];
        $records = get_loverecords($paydate,$pay_group);
        $grid = get_gridlove_column();
        $cfn->download_csv($grid, $records);
    break;
    case "set-grid-love":
        $group = get_group();
        $cutoff = get_cutoff();
        $tool = get_toolbar();
        $grid = get_gridlove_column();
        echo json_encode(array("status" => "success", "group" => $group, "cutoff" => $cutoff, "tool" => $tool, "column" => $grid));
    break;
    case "abs-default": //absent
        set_absent_default();
    break;
    case "get-absent-records":
        getAbsentee($_REQUEST["fr"],$_REQUEST["to"]);
    break;
    case "get-emp-records":
        getEmpAbsent($_REQUEST["emp_no"],$_REQUEST["fr"],$_REQUEST["to"]);
    break;
    case "late-default": //late
        set_late_default();
    break;
    case "get-late-records":
        getLateEmp($_REQUEST["fr"],$_REQUEST["to"]);
    break;
    case "get-emp-recordsLate":
        getEmpRecords($_REQUEST["emp_no"],$_REQUEST["fr"],$_REQUEST["to"]);
    break;

}

//pag-ibig grid & records
function get_gridlove_column() {
    $items = array();
    $items[] = array("field" => "fname", "caption" => "FAMILY NAME", "size" => "250px");
    $items[] = array("field" => "gname", "caption" => "GIVEN NAME", "size" => "250px");
    $items[] = array("field" => "mname", "caption" => "M.I.", "size" => "40px");
    $items[] = array("field" => "love_no", "caption" => "PAG-IBIG NO", "size" => "120px");
    $items[] = array("field" => "pay", "caption" => "PAY", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ees", "caption" => "EE SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ers", "caption" => "ER SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "total", "caption" => "TOTAL", "size" => "100px", "render" => "float:2");
    return $items;
}

function get_loverecords($paydate, $pay_group) {
    global $db, $db_hris;
    $date = (new DateTime(str_replace(",", " 1,", $paydate)))->format("Y-m-10");
    $group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name` LIKE :grp");
    $group->execute(array(":grp" => $pay_group));
    if ($group->rowCount()) {
        $group_data = $group->fetch(PDO::FETCH_ASSOC);
        $payroll_group_no = $group_data["payroll_group_no"];
    } else {
        $payroll_group_no = 0;
    }
    $pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` INNER JOIN $db_hris.`master_data` ON `master_data`.`employee_no`=`payroll_trans`.`employee_no` INNER JOIN $db_hris.`master_id` ON `master_id`.`employee_no`=`payroll_trans`.`employee_no` WHERE `payroll_trans`.`payroll_date` LIKE :date AND `payroll_trans`.`payroll_group_no`=:no ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
    $pay->execute(array(":date" => $date, ":no" => $payroll_group_no));
    if ($pay->rowCount()) {
        $ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:no AND `payroll_date` LIKE :date AND `deduction_no`=107");
        $records = array();
        $summary = array("recid" => "love_sum", "summary" => true, "fname" => "", "gname" => "GRAND TOTALS", "mname" => "", "love_no" => "", "pay" => 0, "ees" => 0, "ers" => 0, "total" => 0, "ec" => 0);
        while ($pay_data = $pay->fetch(PDO::FETCH_ASSOC)) {
            $ded->execute(array(":no" => $pay_data["employee_no"], ":date" => $date));
            if ($ded->rowCount()) {
                $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
                $record = array("recid" => $pay_data["employee_no"], "fname" => $pay_data["family_name"], "gname" => $pay_data["given_name"], "mname" => substr($pay_data["middle_name"], 0, 1), "sssno" => $pay_data["pag_ibig"]);
                $record["pay"] = $pay_data["grosspay_sss"];
                $record["ers"] = $record["ees"] = $ded_data["deduction_actual"];
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
        $records = $summary = array("recid" => "NO DATA", "fname" => "NO RECORDS AS OF $paydate");
    }
    return $records;
}

//philhealth records & philhealth grid
function get_gridph_column() {
    $items = array();
    $items[] = array("field" => "fname", "caption" => "FAMILY NAME", "size" => "250px");
    $items[] = array("field" => "gname", "caption" => "GIVEN NAME", "size" => "250px");
    $items[] = array("field" => "mname", "caption" => "M.I.", "size" => "40px");
    $items[] = array("field" => "ph_no", "caption" => "PHIL HEALTH NO", "size" => "120px");
    $items[] = array("field" => "pay", "caption" => "PAY", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ees", "caption" => "EE SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "ers", "caption" => "ER SHARE", "size" => "100px", "render" => "float:2");
    $items[] = array("field" => "total", "caption" => "TOTAL", "size" => "100px", "render" => "float:2");
    return $items;
}

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
        //$sss = $db->prepare("SELECT * FROM $db_hris.`table_phil_health` WHERE `share_employee`=:philhealth");
        $ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:no AND `payroll_date` LIKE :date AND `deduction_no`=207");
        $records = array();
        $summary = array("recid" => uniqid(), "summary" => true, "fname" => "", "gname" => "GRAND TOTALS", "mname" => "", "ph_no" => "", "pay" => 0, "ees" => 0, "ers" => 0, "total" => 0, "ec" => 0);
        while ($pay_data = $pay->fetch(PDO::FETCH_ASSOC)) {
            $ded->execute(array(":no" => $pay_data["employee_no"], ":date" => $date));
            if ($ded->rowCount()) {
                $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
                //$sss->execute(array(":philhealth" => $ded_data["deduction_actual"]));
                //if ($sss->rowCount()) {
                //    $sss_data = $sss->fetch(PDO::FETCH_ASSOC);
                //} else {
                //    $sss_data = array();
                //}
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
        $summary = array("summary" => true, "recid" => "sss","fname" => "", "gname" => "GRAND TOTALS", "mname" => "", "sss_no" => "", "pay" => 0, "ees" => 0, "ers" => 0, "total" => 0, "ec" => 0);
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

//get the employee absent
function  getAbsentee($from, $to){
    global $db, $db_hris;

    $df = new DateTime($from);
    $dt = new DateTime($to);

    $master = $db->prepare("SELECT SUM(`employee_absent`.`is_absent`) AS `is_absent`,`master_data`.`family_name`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`pin`,`master_data`.`employee_no` FROM $db_hris.`employee_absent`,$db_hris.`master_data` WHERE `employee_absent`.`is_absent` AND `employee_absent`.`employee_no`=`master_data`.`employee_no` AND `employee_absent`.`absent_date`>=:df AND `employee_absent`.`absent_date`<=:dt GROUP BY `employee_absent`.`employee_no` ORDER BY SUM(`employee_absent`.`is_absent`) DESC");
    $master->execute(array(":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($master->rowCount()){
        $cnt = 1;
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
            $emp_no = $master_data["employee_no"]; ?>
            <tr style="cursor: pointer;" onclick="getEmpData('<?php echo $emp_no; ?>')" id="empAbs<?php echo $emp_no; ?>">
                <td style="width: 50px;"><?php echo $cnt++; ?></td>
                <td style="width: 150px;"><?php echo $master_data["pin"]; ?></td>
                <td><?php echo $master_data["family_name"].', '.$master_data["given_name"].' '.substr($master_data["middle_name"], 0, 1); ?></td>
                <td class="w3-center"><?php echo $master_data["is_absent"]; ?></td>
            </tr>
            <tr class="w3-hide w3-red" id="empData<?php echo $emp_no; ?>">
                <td class="w3-red w3-center" colspan="4"><span id="emp_data<?php echo $emp_no; ?>"></span></td>
            </tr>
        <?php
        }
    }
}

function getEmpAbsent($emp_no, $from, $to){
    global $db, $db_hris;

    $df = new DateTime($from);
    $dt = new DateTime($to);

    $abs = $db->prepare("SELECT * FROM $db_hris.`employee_absent` WHERE `employee_no`=:no AND `is_absent` AND `employee_absent`.`absent_date`>=:df AND `employee_absent`.`absent_date`<=:dt");
    $abs->execute(array(":no" => $emp_no, ":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($abs->rowCount()){
        while($abs_data = $abs->fetch(PDO::FETCH_ASSOC)){ ?>
            <span class="w3-small" style="width: 100%;">DATE: <?php echo $abs_data["absent_date"]; ?>&nbsp;&nbsp;&nbsp;</span><br>
        <?php
        }
    }
}

//absent ui
function set_absent_default() { ?>
    <div class="w3-col s6 w3-row-padding w3-margin-bottom w3-responsive">
        <table class="w3-row-padding w3-table-all w3-small w3-hoverable">
            <thead>
                <tr>
                    <th colspan="2" class="w3-center">EMPLOYEE NO</th>
                    <th class="w3-center">NAME</th>
                    <th class="w3-center">TOTAL ABSENT</th>
                </tr>
            </thead>
            <tbody id="body-data">
            </tbody>
        </table>
    </div>
    <script type="text/javascript">
        const src = "page/reports";

        function getEmpData(emp_no){
            if($('#empData'+emp_no).is(":hidden")){
                $('#empData'+emp_no).removeClass('w3-hide');
                $('#empAbs'+emp_no).addClass('w3-orange w3-text-white');
                getData(emp_no);

            }else{
                $('#empData'+emp_no).addClass('w3-hide');
                $('#empAbs'+emp_no).removeClass('w3-orange w3-text-white');
            }
        }
        
        function getAbsentRecords() {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-absent-records",
                    fr: $("#datef").val(),
                    to: $("#datet").val()
                },
                success: function (data) {
                    w2utils.unlock(div);
                    if (data !== "") {
                        $('#body-data').html(data);
                    }
                },
                error: function () {
                    w2utils.unlock(div);
                    w2alert("Please try again later!");
                }
            });
        }

        function getData(emp_no){
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-emp-records",
                    emp_no: emp_no,
                    fr: $("#datef").val(),
                    to: $("#datet").val()
                },
                success: function (data) {
                    w2utils.unlock(div);
                    if (data !== "") {
                        $('#emp_data'+emp_no).html(data);
                    }
                },
                error: function () {
                    w2utils.unlock(div);
                    w2alert("Please try again later!");
                }
            });
        }
    </script>
    <?php
}

//get the employee absent
function  getLateEmp($from, $to){
    global $db, $db_hris;

    $df = new DateTime($from);
    $dt = new DateTime($to);

    $master = $db->prepare("SELECT SUM(`employee_late`.`isLate`) AS `isLate`,`master_data`.`family_name`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`pin`,`master_data`.`employee_no` FROM $db_hris.`employee_late`,$db_hris.`master_data` WHERE `employee_late`.`employee_no`=`master_data`.`employee_no` AND `employee_late`.`trans_date`>=:df AND `employee_late`.`trans_date`<=:dt GROUP BY `employee_late`.`employee_no` ORDER BY SUM(`employee_late`.`isLate`) DESC");
    $master->execute(array(":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($master->rowCount()){
        $cnt = 1;
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
            $emp_no = $master_data["employee_no"]; ?>
            <tr style="cursor: pointer;" onclick="getLate('<?php echo $emp_no; ?>')" id="empLate<?php echo $emp_no; ?>">
                <td style="width: 50px;"><?php echo $cnt++; ?></td>
                <td style="width: 150px;"><?php echo $master_data["pin"]; ?></td>
                <td><?php echo $master_data["family_name"].', '.$master_data["given_name"].' '.substr($master_data["middle_name"], 0, 1); ?></td>
                <td><?php echo $master_data["isLate"]; ?></td>
            </tr>
            <tr class="w3-hide w3-red" id="empLateData<?php echo $emp_no; ?>">
                <td class="w3-red w3-center" colspan="4"><span id="emp_late_data<?php echo $emp_no; ?>"></span></td>
            </tr>
        <?php
        }
    }
}

function getEmpRecords($emp_no,$from,$to){
    global $db, $db_hris;

    $df = new DateTime($from);
    $dt = new DateTime($to);

    $late = $db->prepare("SELECT * FROM $db_hris.`employee_late` WHERE `employee_no`=:no AND `employee_late`.`trans_date`>=:df AND `employee_late`.`trans_date`<=:dt");
    $late->execute(array(":no" => $emp_no, ":df" => $df->format('Y-m-d'), ":dt" => $dt->format('Y-m-d')));
    if($late->rowCount()){
        while($late_data = $late->fetch(PDO::FETCH_ASSOC)){ ?>
            <span class="w3-small" style="width: 100%;">DATE: <?php echo $late_data["trans_date"]; ?> |MINS LATE: <?php echo (new DateTime($late_data["mins_late"]))->format("H:i"); ?> |DUTY START: <?php echo (new DateTime($late_data["start_time"]))->format("H:i"); ?> |LOG TIME: <?php echo (new DateTime($late_data["log_time"]))->format("H:i"); ?></span><br>
        <?php
        }
    }
}

//late ui
function set_late_default() { ?>
    <div class="w3-col s6 w3-row-padding w3-margin-bottom w3-responsive">
        <table class="w3-row-padding w3-table-all w3-small w3-hoverable">
            <thead>
                <tr>
                    <th colspan="2">EMPLOYEE NO</th>
                    <th>NAME</th>
                    <th>TOTAL LATE</th>
                </tr>
            </thead>
            <tbody id="body-late-data">
            </tbody>
        </table>
    </div>
    <script type="text/javascript">
        const src = "page/reports";

        function getLate(emp_no){
            if($('#empLateData'+emp_no).is(":hidden")){
                $('#empLateData'+emp_no).removeClass('w3-hide');
                $('#empLate'+emp_no).addClass('w3-orange w3-text-white');
                getDataEmp(emp_no);
            }else{
                $('#empLateData'+emp_no).addClass('w3-hide');
                $('#empLate'+emp_no).removeClass('w3-orange w3-text-white');
            }
        }
        
        function getLateRecords() {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-late-records",
                    fr: $("#datef").val(),
                    to: $("#datet").val()
                },
                success: function (data) {
                    w2utils.unlock(div);
                    if (data !== "") {
                        $('#body-late-data').html(data);
                    }
                },
                error: function () {
                    w2utils.unlock(div);
                    w2alert("Please try again later!");
                }
            });
        }

        function getDataEmp(emp_no){
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-emp-recordsLate",
                    emp_no: emp_no,
                    fr: $("#datef").val(),
                    to: $("#datet").val()
                },
                success: function (data) {
                    w2utils.unlock(div);
                    if (data !== "") {
                        $('#emp_late_data'+emp_no).html(data);
                    }
                },
                error: function () {
                    w2utils.unlock(div);
                    w2alert("Please try again later!");
                }
            });
        }
    </script>
    <?php
}
