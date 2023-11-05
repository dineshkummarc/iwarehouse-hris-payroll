<?php

$program_code = 41;
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
                case "get-options": //ok view details
                    if ($access_rights === "A+E+D+B+P+") {
                        echo json_encode(array("status" => "success", "pay_group" => get_pay_group(), "cut_off" => get_cutoff()));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-records": //ok view details
                    if ($access_rights === "A+E+D+B+P+") {
                        get_trans(array("period" => get_date($_POST["cutoff"]), "group" => $_POST["group"]));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "print":
                    if ($access_rights === "A+E+D+B+P+") {
                        plot_dtr(array("period" => get_date($_REQUEST["cutoff_date"]), "group" => $_REQUEST["pay_group"]));
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

function get_trans($option) {
    $columns = get_column($option["period"]);
    $records = get_records($option);
    echo json_encode(array("status" => "success", "records" => $records, "columns" => $columns, "options" => $option));
}

//grid columns
function get_column($period) {
    $item = array();
    $item[] = array("field" => "pin", "caption" => "PIN", "size" => "80px", "attr" => "align=right");
    $item[] = array("field" => "name", "caption" => "EMPLOYEE NAME", "size" => "250px");
    $item[] = array("field" => "thrs", "caption" => "TOTAL HOURS", "size" => "100px", "attr" => "align=right");
    $df = new DateTime($period["df"]);
    $dt = new DateTime($period["dt"]);
    while ($df->format("Ymd") <= $dt->format("Ymd")) {
        $item[] = array("field" => "d" . $df->format("md"), "caption" => $df->format("m/d"), "size" => "50px", "attr" => "align=center");
        $df->modify("+1 day");
    }
    return $item;
}

function get_pay_group() {
    global $db, $db_hris;

    $pgroup = $db->prepare("SELECT * FROM $db_hris.`payroll_group`,$db_hris.`employment_status` WHERE `employment_status`.`employment_status_code`=`payroll_group`.`group_name` ORDER BY `payroll_group`.`payroll_group_id` ASC");
    $pgroup->execute();
    $pay_group = array();
    if ($pgroup->rowCount()) {
        while ($pgroup_data = $pgroup->fetch(PDO::FETCH_ASSOC)) {
            $pay_group[] = array("id" => $pgroup_data["employment_status_code"], "text" => $pgroup_data["description"]);
        }
    }
    return $pay_group;
}

function get_cutoff() {
    $items = array();
    $date = new DateTime(date("m/d/Y"));
    for ($x = 1; $x <= 6; $x++) {
        $day = number_format($date->format("d"), 0, '.', '');
        $time = new DateTime($date->format("m/1/Y"));
        if ($day >= number_format(11, 0) AND $day <= number_format(25, 0)) {
            $items[] = "First half of " . $date->format("M, Y");
            $time->modify("-1 day");
        } else {
            $time->modify("+15 day");
            $items[] = "End month of " . $date->format("M, Y");
        }
        $date = new DateTime($time->format("m/d/Y"));
    }
    return $items;
}

function get_date($cut_off) {
    $day = substr($cut_off, -9);
    $date = new DateTime(str_replace(",", " 1,", $day));
    if (substr($cut_off, 0, 3) === "End") {
        $item = array("df" => $date->format("Y-m-10"), "dt" => $date->format("Y-m-25"));
    } else {
        $item = array("dt" => $date->format("Y-m-10"));
        $date->modify("-1 day");
        $item["df"] = $date->format("Y-m-25");
    }
    return $item;
}

function get_records($option) {
    global $db, $db_hris;

    $records = array();
    $mast = $db->prepare("SELECT `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`, `master_data`.`employee_no`, `master_id`.`pin_no` AS `pin_code` FROM $db_hris.`master_data` INNER JOIN $db_hris.`master_id` USING(`employee_no`) WHERE `master_data`.`group_no`=:pgno AND !`master_data`.`is_inactive` ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
    $mast->execute(array(":pgno" => $option["group"]));
    if ($mast->rowCount()) {
        while ($mast_data = $mast->fetch(PDO::FETCH_ASSOC)) {
            $mast_data["period"] = $option["period"];
            $record = get_time($mast_data);
            if (count($record)) {
                $record["data"] = $mast_data;
                $records[] = $record;
            }
        }
    }
    return $records;
}

function get_time($mast_data) {
    global $db, $db_hris;

    $timeb = $db->prepare("SELECT SUM(`credit_time`) AS `credit_time` FROM $db_hris.`time_credit` WHERE `employee_no`=:no AND `trans_date` LIKE :date");
    $timea = $db->prepare("SELECT SUM(`credit_time`) AS `credit_time` FROM $db_hris.`time_credit_ot` WHERE `employee_no`=:no AND `trans_date` LIKE :date AND `is_approved`");
    $record = array();
    $date = new DateTime($mast_data["period"]["df"]);
    $total_time = 0;
    while ($date->format("Y-m-d") <= $mast_data["period"]["dt"]) {
        $timea->execute(array(":no" => $mast_data["pin_code"], ":date" => $date->format("Y-m-d")));
        $adj_data = $timea->fetch(PDO::FETCH_ASSOC);
        $timeb->execute(array(":no" => $mast_data["pin_code"], ":date" => $date->format("Y-m-d")));
        $bio_data = $timeb->fetch(PDO::FETCH_ASSOC);
        $hrmin = number_format($adj_data["credit_time"] + $bio_data["credit_time"], 2);
        if ($hrmin !== number_format(0, 0)) {
            $fld = "d" . $date->format("md");
            $record[$fld] = get_format_time($hrmin);
            $total_time += $hrmin;
        }
        $date->modify("+1 day");
    }
    if (count($record)) {
        $record["thrs"] = get_format_time($total_time);
        $record["name"] = $mast_data["family_name"] . ", " . $mast_data["given_name"];
        if (strlen($mast_data["middle_name"])) {
            $record["name"] .= " " . substr($mast_data["middle_name"], 0, 1) . ".";
        }
        $record["recid"] = $mast_data["employee_no"];
        $record["pin"] = $mast_data["pin_code"];
    }
    return $record;
}

function get_format_time($time) {
    $mins = $time % 60;
    $hrs = number_format(($time - $mins) / 60, 0, '.', '');
    if ($hrs < number_format(100, 0)) {
        $hrs = substr(number_format(($time - $mins) / 60 + 100, 0, '.', ''), -2);
    }
    return $hrs . ":" . substr(number_format(100 + $mins, 0, '.', ''), -2);
}

function plot_dtr($option){
    global $db, $db_hris, $cfn;
    
    $mast = $db->prepare("SELECT `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`, `master_data`.`employee_no`, `master_data`.`store`, `master_id`.`pin_no` AS `pin_code`, `master_id`.`sss`, `master_id`.`tin`, `master_id`.`phil_health` FROM $db_hris.`master_data` INNER JOIN $db_hris.`master_id` USING(`employee_no`) WHERE `master_data`.`group_no`=:pgno AND !`master_data`.`is_inactive` ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
    $mast->execute(array(":pgno" => $option["group"]));
    set_header();
    $company = strtoupper($cfn->sysconfig("company"));
    $count = 0;
    $df = (new DateTime($option["period"]["df"]))->format("m/d/Y");
    $dt = (new DateTime($option["period"]["dt"]))->format("m/d/Y");
    $time = $db->prepare("SELECT `credit_time` FROM $db_hris.`time_credit` WHERE `employee_no`=:no AND `trans_date`>= :df AND `trans_date`<=:dt UNION SELECT `credit_time` FROM $db_hris.`time_credit_ot` WHERE `employee_no`=:no AND `trans_date`>= :df AND `trans_date`<=:dt");
    while ($mast_data = $mast->fetch(PDO::FETCH_ASSOC)) {
        $mast_data["period"] = $option["period"];
        $mast_data["company"] = $company;
        $mast_data["dates"] = (new DateTime($df))->format("M. j, Y") . " TO " . (new DateTime($dt))->format("M. j, Y");
        $time->execute(array(":no" => $mast_data["pin_code"], ":df" => $option["period"]["df"], ":dt" => $option["period"]["dt"]));
        if ($time->rowCount()) {
            plot_my_dtr($mast_data);
        }
    }
    set_footer();
}

function plot_my_dtr($mast_data) {
    global $db, $db_hris;

    $name = $mast_data["family_name"] . ", " . $mast_data["given_name"];
    if (strlen($mast_data["middle_name"])) {
        $name .= " " . substr($mast_data["middle_name"], 0, 1) . ".";
    }
    $total_hours = 0; ?>
    <div class="pgsize pcont">
        <div class="w3-col s12 w3-small">
            <div class="w3-col s6">
                <div class="w3-col s12 w3-center">
                    <span class="w3-small"><b><?php echo $mast_data["company"]; ?></b></span>
                </div>
                <div class="w3-col s12 w3-center">
                    <span class="w3-small">DAILY TIME RECORD</span>
                </div>
                <div class="w3-col s12">
                    <div class="w3-col s12 w3-container">&nbsp;</div>
                </div>
                <div class="w3-col s12">
                    <div class="w3-col s8 w3-container">EMP NO:&nbsp;<?php echo $mast_data["pin_code"]; ?></div>
                    <div class="w3-col s4 w3-container">&nbsp;&nbsp;&nbsp;&nbsp;SSS#&nbsp;<?php echo $mast_data["sss"]; ?></div>
                </div>
                <div class="w3-col s12">
                    <div class="w3-col s8 w3-container">NAME:&nbsp;<?php echo $name; ?></div>
                    <div class="w3-col s4 w3-container">&nbsp;&nbsp;&nbsp;&nbsp;TIN:&nbsp;<?php echo $mast_data["tin"]; ?></div>
                </div>
                <div class="w3-col s12">
                    <div class="w3-col s8 w3-container">PAYROLL PERIOD:&nbsp;<?php echo $mast_data["dates"]; ?></div>
                    <div class="w3-col s4 w3-container">&nbsp;&nbsp;&nbsp;&nbsp;PHIL#:&nbsp;<?php echo $mast_data["phil_health"]; ?></div>
                </div>
                <div class="w3-col s12 w3-margin-top w3-small">
                    <div style="font-size:90%;">
                        <div class="w3-col s1 w3-center"><b>DATE</b></div>
                        <div class="w3-col s1 w3-center"><b>IN</b></div>
                        <div class="w3-col s1 w3-center"><b>OUT</b></div>
                        <div class="w3-col s1 w3-center"><b>IN</b></div>
                        <div class="w3-col s1 w3-center"><b>OUT</b></div>
                        <div class="w3-col s1 w3-center"><b>IN</b></div>
                        <div class="w3-col s1 w3-center"><b>OUT</b></div>
                        <div class="w3-col s1 w3-center"><b>IN</b></div>
                        <div class="w3-col s1 w3-center"><b>OUT</b></div>
                        <div class="w3-col s1 w3-center"><b>CREDIT</b></div>
                        <div class="w3-col s2 w3-center"><b>SIGNATURE</b></div>
                    </div>
                </div>
            <?php
            $vl = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:no AND `vl_date` LIKE :date AND !`is_cancelled`");
            $att_log = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date` LIKE :date ORDER BY `row_id`");
            $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:no");
            $eshift = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:no AND `trans_date` LIKE :date");
            $timeb = $db->prepare("SELECT SUM(`credit_time`) AS `credit_time` FROM $db_hris.`time_credit` WHERE `employee_no`=:no AND `trans_date` LIKE :date");
            $timea = $db->prepare("SELECT SUM(`credit_time`) AS `credit_time` FROM $db_hris.`time_credit_ot` WHERE `employee_no`=:no AND `trans_date` LIKE :date AND `is_approved`");
            $time = $db->prepare("SELECT `credit_time` FROM $db_hris.`time_credit` WHERE `employee_no`=:no AND `trans_date` LIKE :date UNION SELECT `credit_time` FROM $db_hris.`time_credit_ot` WHERE `employee_no`=:no AND `trans_date` LIKE :date");
            $holiday = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date` LIKE :date");
            $df = new DateTime($mast_data["period"]["df"]);
            $dt = new DateTime($mast_data["period"]["dt"]);
            $vl_count = 0;
            $hol30 = $holf = "";
            while ($df->format("Ymd") <= $dt->format("Ymd")) {
                $tdate = $df->format("Y-m-d");
                $time->execute(array(":no" => $mast_data["pin_code"], ":date" => $tdate));
                $vl->execute(array(":no" => $mast_data["employee_no"], ":date" => $tdate));
                $holiday->execute(array(":date" => $tdate));
                if ($holiday->rowCount()) {
                    $holpay = "w3-text-red";
                } else {
                    $holpay = "";
                }
            $credit = "&nbsp;";
            ?>
            <div class="w3-col s12 w3-small <?php echo $holpay; ?>">
                <div style="height: 2em; font-size: 90%;">
                    <div class="w3-col s1 w3-center"><?php echo $df->format("m/d"); ?></div>
                    <?php
                        $att_log->execute(array(":pin" => $mast_data["pin_code"], ":date" => $tdate));
                        if ($att_log->rowCount()) {
                            while($att_log_data = $att_log->fetch(PDO::FETCH_ASSOC)){
                                if($att_log->rowCount() == 4){ ?>
                                    <div class="w3-col s1 w3-center"><?php echo $att_log_data["log_time"]; ?></div>
                                <?php }else{ ?>
                                    <div class="w3-col s1 w3-center"><?php echo $att_log_data["log_time"]; ?></div>
                                <?php }
                            }
                            if($att_log->rowCount() == 7){ ?>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                            <?php }
                            if($att_log->rowCount() == 6){ ?>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                            <?php }
                            if($att_log->rowCount() == 5){ ?>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                            <?php }
                            if($att_log->rowCount() == 4){ ?>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                            <?php }
                            if($att_log->rowCount() == 2){ ?>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                            <?php }
                            if($att_log->rowCount() == 3){ ?>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                                <div class="w3-col s1 w3-center">&nbsp;</div>
                            <?php }
                        } else {
                            $eshift->execute(array(":no" => $mast_data["employee_no"], ":date" => $tdate));
                            if ($eshift->rowCount()) {
                                $eshift_data = $eshift->fetch(PDO::FETCH_ASSOC);
                                $shift_code = $eshift_data["shift_code"];
                                $shift->execute(array(":no" => $shift_code));
                                if ($shift->rowCount()) {
                                    $shift_data = $shift->fetch(pdo::FETCH_ASSOC);
                                    if ($shift_data["is_off_duty"]) { ?>
                                        <div class="w3-col s9 w3-center">
                                                <span class="w3-small">--&nbsp;&nbsp;O&nbsp;F&nbsp;F&nbsp;-&nbsp;D&nbsp;U&nbsp;T&nbsp;Y&nbsp;&nbsp;--</span>
                                            </div>
                                        <?php
                                    }else{
                                        if ($vl->rowCount()) {
                                            $vl_count++;
                                            ?>
                                            <div class="w3-col s9 w3-center">
                                                <span class="w3-small">--&nbsp;&nbsp;V&nbsp;A&nbsp;C&nbsp;A&nbsp;T&nbsp;I&nbsp;O&nbsp;N&nbsp;&nbsp;&nbsp;L&nbsp;E&nbsp;A&nbsp;V&nbsp;E&nbsp;&nbsp;--</span>
                                            </div>
                                            <?php
                                        }else{  ?>
                                            <div class="w3-col s9 w3-center">
                                                <span class="w3-small">--&nbsp;&nbsp;A&nbsp;B&nbsp;S&nbsp;E&nbsp;N&nbsp;T&nbsp;&nbsp;--</span>
                                            </div>
                                            <?php
                                        }
                                    }
                                }else{ ?>
                                    <div class="w3-col s9 w3-center">&nbsp;</div>
                                <?php }
                            }else{ ?>
                            <div class="w3-col s9 w3-center">&nbsp;</div>
                            <?php
                            }
                        }
                        $timea->execute(array(":no" => $mast_data["pin_code"], ":date" => $tdate));
                        $adj_data = $timea->fetch(PDO::FETCH_ASSOC);
                        $timeb->execute(array(":no" => $mast_data["pin_code"], ":date" => $tdate));
                        $bio_data = $timeb->fetch(PDO::FETCH_ASSOC);
                        $hrmin = number_format($adj_data["credit_time"] + $bio_data["credit_time"], 0, '.', '');
                        if ($hrmin !== number_format(0, 0)) {
                            $total_hours += $hrmin;
                            $credit = get_format_time($hrmin);
                        }else{
                            $credit = "";
                        }
                        if ($time->rowCount()) {
                            if ($holiday->rowCount()) {
                                $hdata = $holiday->fetch(PDO::FETCH_ASSOC);
                                if ($hdata["is_special"]) {
                                    if ($hol30 !== "") {
                                        $hol30 .= ", " . $df->format("m/d");
                                    } else {
                                        $hol30 = $df->format("m/d");
                                    }
                                } else {
                                    if ($holf !== "") {
                                        $holf .= ", " . $df->format("m/d");
                                    } else {
                                        $holf = $df->format("m/d");
                                    }
                                }
                            }
                        }
                        $df->modify("+1 day"); ?>
                        <div class="w3-col s1 w3-center"><?php echo $credit; ?></div>
                        <div class="w3-col s2 w3-border-bottom">&nbsp;</div>
                    </div>
                </div>
            <?php
            }  ?>
            <div class="w3-col s12 w3-small w3-center w3-margin-top w3-margin-bottom">WORKING SCHEDULE DETAILS</div>
            <div class="w3-col s12">
                <div class="w3-col s12">
                    <div class="w3-col s8 w3-container">
                        <div class="w3-col s8 w3-small">TOTAL HOURS WORKED:&nbsp;<b><?php echo get_format_time($total_hours); ?></b></div>
                    </div>
                    <div class="w3-col s4 w3-container">
                        <div class="w3-col s10 w3-small">
                            <?php if($vl_count > 0){ ?>
                                VACATION LEAVE:&nbsp;<?php echo number_format($vl_count, 0); 
                            }?>
                        </div>
                    </div>
                </div>
                <div class="w3-col s12 w3-container w3-margin-bottom">
                    <div class="w3-col s12 w3-small">HOLIDAY PAY:</div>
                    <div class="w3-col s12 w3-container">
                        <div class="w3-col s2 w3-container w3-right-align w3-small">30%</div>
                        <div class="w3-col s10 w3-border-bottom w3-small"><?php echo $hol30 . " &nbsp;"; ?></div>
                    </div>
                    <div class="w3-col s12 w3-container">
                        <div class="w3-col s2 w3-container w3-right-align w3-small">100%</div>
                        <div class="w3-col s10 w3-border-bottom w3-small"><?php echo $holf . " &nbsp;"; ?></div>
                    </div>
                </div>
                <div class="w3-col s12 w3-margin-bottom w3-container">
                    <p class="w3-container w3-small">I certify that above is true and correct report of number of hours of work performed.&nbsp;&nbsp;Record of which was made daily at the time of arrival and at departure from office.</p>
                </div>
                <div class="w3-margin-top w3-col s12">
                    <div class="w3-col s1 w3-center w3-margin-top w3-margin-bottom">&nbsp;</div>
                    <div class="w3-col s4 w3-center w3-margin-top w3-margin-bottom">
                        <div class="w3-col s12 w3-center w3-tiny w3-border-top">
                            <span class="w3-margin-top" style="font-size: 90%;"><?php echo $name; ?></span>
                        </div>
                        <div class="w3-col s12 w3-center w3-tiny">
                            <span style="font-size: 90%;">SIGNATURE</span>
                        </div>
                    </div>
                    <div class="w3-col s2 w3-center w3-margin-top w3-margin-bottom">&nbsp;</div>
                    <div class="w3-col s4 w3-margin-top w3-margin-bottom">
                        <div class="w3-col s12 w3-center w3-tiny  w3-border-top">
                            <span style="font-size: 90%;">SIGNATURE OVER PRINTED NAME</span>
                        </div>
                        <div class="w3-col s12 w3-center w3-tiny">
                            <span style="font-size: 90%;">VERIFIED BY SUPERVISOR</span>
                        </div>
                    </div>
                    <div class="w3-col s1 w3-center w3-margin-top w3-margin-bottom">&nbsp;</div>
                </div>
            </div>
        </div>
        <div class="w3-col s6 w3-container">&nbsp;</div>
        </div>
    </div>
    <p class="breakpoint">&nbsp;</p>
<?php
return;
}

function set_header() { ?>
    <!DOCTYPE html>
    <html>
        <head>
        <title>DAILY TIME RECORD</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="../../../css/w3-css.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
        <style type="text/css" media="print">
            @media all{
                p {font-size: 60%; margin: 0 0 0 0; padding: 0 0 0 0;}
            }
            @media print{
                .noprint, .noprint * {display:none !important; height: 0;}
                .pgsize {height: 960px;}
                @page {
                    size: 10in 8.5in;
                }
            }
            .pcont { page-break-inside : avoid }
            .breakpoint { page-break-after: always; }
        </style>
        </head>
    <body>
    <?php
}

function set_footer() { ?>
    </body>
    </html>
    <script>
    window.onload = function () {
        window.print();
    };
    </script>
    <?php
}