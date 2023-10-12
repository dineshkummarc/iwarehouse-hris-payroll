<?php

$program_code = 27;
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
                case "get-emp-bio":
                    $record = array("group_name" => $_POST["_group"], "trans_date" => (new DateTime($_POST["_date"]))->format("Y-m-d"));
                    get_emp_bio($record);
                break;
                case "get-emp-bio-trans":
                    $day = (new DateTime($_POST["date"]))->format("N");
                    if (number_format($day, 0) === number_format(7, 0)) {
                        $day = 0;
                    }
                    $record = array("pin" => $_POST["recid"], "date" => (new DateTime($_POST["date"]))->format("Y-m-d"), "day" => $day);
                    get_emp_bio_trans($record);
                break;
                case "make-swipe-memo":
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        $record = array("pin" => $_POST["pin"], "swipe_memo_code" => $_POST["code"], "trans_date" => (new DateTime($_POST["date"]))->format("Y-m-d"), "is_new" => $_POST["new"]);
                        make_swipe_memo($record);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-group":
                    get_group();
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
    echo json_encode(array("status" => "success", "group" => $group));
}

//grid columns of employee
function grid_column() {
    $items = array();
    $items[] = array("field" => "recid", "caption" => "recid", "size" => "55px", "attr" => "align=right", "hidden" => true);
    $items[] = array("field" => "pin", "caption" => "PIN", "size" => "60px", "attr" => "align=center");
    $items[] = array("field" => "name", "caption" => "FULL NAME", "size" => "200px");
    $items[] = array("field" => "time", "caption" => "Time", "size" => "50px", "attr" => "align=center");
    return $items;
}

function get_emp_bio($record){
    global $db, $db_hris;

    $records = array();
    $employee = $db->prepare("SELECT `attendance_log`.`pin`, `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`, `master_data`.`group_no` FROM $db_hris.`attendance_log`, $db_hris.`master_data` WHERE `attendance_log`.`pin`=`master_data`.`pin` AND `log_date`=:date AND `master_data`.`group_no`=:grp_name GROUP BY `attendance_log`.`pin` ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
    $employee->execute(array(":date" => $record["trans_date"], ":grp_name" => $record["group_name"]));
    if($employee->rowCount()){
        while($employee_data =  $employee->fetch(PDO::FETCH_ASSOC)){
            set_time_limit(30);
            $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin AND !`is_inactive`");
            $master->execute(array(":pin" => $employee_data["pin"]));
            if($master->rowCount()){
                $master_data = $master->fetch(PDO::FETCH_ASSOC);
                $record["recid"] = $record["pin"] = $master_data["pin"];
                $record["name"] = $master_data["family_name"].', '.$master_data["given_name"].' '.substr($master_data["middle_name"], 0, 1);
                $time_credit = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `trans_date`=:date AND `employee_no`=:eno AND !`isDOD`");
                $jo_credit = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `trans_date`=:date AND `employee_no`=:eno AND `isDOD`");
                $time_credit->execute(array(":date" => $record["trans_date"], ":eno" => $master_data["pin"]));
                $jo_credit->execute(array(":date" => $record["trans_date"], ":eno" => $master_data["pin"]));
                $late =  $db->prepare("SELECT * FROM $db_hris.`employee_late` WHERE `trans_date`=:date AND `employee_no`=:eno");
                $late->execute(array(":date" => $record["trans_date"], ":eno" => $master_data["employee_no"]));
                if($time_credit->rowCount()){
                    $time_credit_data = $time_credit->fetch(PDO::FETCH_ASSOC);
                    if($time_credit_data["credit_time"]>0){
                        $time_mins = $time_credit_data['credit_time'];
                        $time_min = $time_credit_data["credit_time"] % 60;
                        $time_hrs = ($time_mins - $time_min) / 60;
                        $record["time"] = substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
                    }
                }elseif($jo_credit->rowCount()){
                    $jo_credit_data = $jo_credit->fetch(PDO::FETCH_ASSOC);
                    if($jo_credit_data["credit_time"]>0){
                        $time_mins = $jo_credit_data['credit_time'];
                        $time_min = $jo_credit_data["credit_time"] % 60;
                        $time_hrs = ($time_mins - $time_min) / 60;
                        $record["time"] = substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
                    }
                }else{
                    $record["time"] = "-0-";
                }
            }
            array_push($records, $record);
        }
        echo json_encode(array("status" => "success", "columns" => grid_column(), "records" => $records));
    }else{
        echo json_encode(array("status" => "error", "message" => "No record found for date ".$record["trans_date"]));
    }
}

function get_emp_bio_trans($record){
    global $db, $db_hris;

    $attendancelog = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date`=:date ORDER BY `log_date`,`log_time`");
    $attendancelog->execute(array(":pin" => $record["pin"], ":date" => $record["date"]));
    $master =  $db->prepare("SELECT * FROM  $db_hris.`master_data` WHERE `pin`=:pin AND !`is_inactive`");
    $master->execute(array(":pin" => $record["pin"]));
    $master_data =  $master->fetch(PDO::FETCH_ASSOC);
    $work_schedule = explode(",", $master_data["work_schedule"]);
    if($attendancelog->rowCount()){ ?>
        <div class="w3-right w3-panel">
            <button class="w3-button w3-red w3-tiny w3-round-medium" onclick="extact_data();">CLOSE</button>
        </div>
        <div class = "w3-panel w3-card-4 w3-padding-large w3-round-medium">
            <div class="w3-left">
        <?php
        $w = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:code");
        $w->execute(array(":code" => $work_schedule[$record["day"]]));
        if ($w->rowCount()) {
            $data = $w->fetch(PDO::FETCH_ASSOC); ?>
                <table class="w3-table-all w3-row-padding">
                    <thead>
                        <tr>
                            <th colspan="5" class="w3-center"><?php echo (new DateTime($record["date"]))->format("M j"); ?> - SHIFT SCHEDULE</th>
                        </tr>
                        <tr>
                            <th colspan="5">SHIFT SCHED: <?php
                                echo $data["shift_name"];
                                if (!($data["is_off_duty"])) {
                                    echo "<span class=\"w3-right\">TIME IN: " . $data["start_hh"] . ":" . $data["start_mm"] . "</span>";
                                }
                                ?></th>
                        </tr>
                    </thead>
                    <tbody>
            <?php
            if (!($data["is_off_duty"] OR $data["is_open"])) {
                $break = 0;
                $h = $data["start_hh"];
                $m = $data["start_mm"];
                $sd = $db->prepare("SELECT * FROM $db_hris.`shift_detail` WHERE `shift_code`=:code ORDER BY `shift_seq`");
                $sd->execute(array(":code" => $data["shift_code"]));
                if ($sd->rowCount()) {
                    $t = new DateTime(date("m/d/Y"));
                    $t->modify("+$h HOURS");
                    $t->modify("+$m minutes");
                    $cnt = 0;
                    while ($sd_data = $sd->fetch(PDO::FETCH_ASSOC)) {
                        $start = $t->format("h:i A");
                        if (number_format($sd_data["hh"], 0, '.', '') !== number_format(0, 0)) {
                            $t->modify("+$sd_data[hh] HOURS");
                        }
                        if (number_format($sd_data["mm"], 0, '.', '') !== number_format(0, 0)) {
                            $t->modify("+$sd_data[mm] minutes");
                        }
                        $end = $t->format("h:i A");
                        if ($sd_data["is_duty"]) {
                            $ty = "DUTY";
                        } else {
                            $ty = "BREAK";
                            $break += $sd_data["hh"] * 60 + $sd_data["mm"];
                        } ?>
                        <tr>
                            <td><?php echo number_format(++$cnt, 0); ?></td>
                            <td><?php echo $ty; ?></td>
                            <td class="w3-center"><?php echo $start; ?></td>
                            <td class="w3-center"><?php echo $end; ?></td>
                            <td><?php echo $sd_data["hh"] . ":" . $sd_data["mm"]; ?></td>
                        </tr>
                        <?php
                    }
                }
            } ?>
                    </tbody>
                </table>
                <div class="w3-col s12 w3-black w3-row-padding w3-padding">
                    ALLOWABLE BREAK TIME: <?php
                    if (!($data["is_off_duty"] OR $data["is_open"])) {
                        if($break == 0){
                            $break1 = "00:00";
                        }else{
                            $break1 = $break;
                        }
                        $time = number_format($break1, 0, '.', '');
                        $d = new DateTime(date("m/d/Y"));
                        $d->modify("+$time minutes");
                        echo $d->format("H:i");
                    }else{
                        echo '00:00';
                    }
                    ?>
                </div>
            <?php
        } ?>
        </div>
        <div class="w3-left w3-margin-left">
            <table class="w3-table-all">
                <thead>
                    <tr class="w3-black">
                        <th colspan="4"><?php echo $master_data["family_name"].', '.$master_data["given_name"].' '.substr($master_data["middle_name"], 0, 1); ?></th>
                    </tr>
                    <tr>
                        <th></th>
                        <th>TIME</th>
                        <th>REASON</th>
                        <th>HRS:MINS</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $dated = new DateTime($record["date"]);
                $df = $dated->format("Y-m-d") . " 04:00:00";
                $dt = $dated->format("Y-m-d") . " 03:59:59";
                $duty = get_time(array("recid" => $record["pin"], "date" => $record["date"], "ltn" => $record["pin"], "df" => $df, "dt" => $dt));
                $time = date("Y-m-d H:i:s");
                $cnt=0;
                $log_type = $db->prepare("SELECT * FROM $db_hris.`log_type` WHERE `log_value`=:log_value");
                while($attendancelog_data = $attendancelog->fetch(PDO::FETCH_ASSOC)){ 
                    if ($cnt) {
                        $f = new DateTime($time);
                        $t = new DateTime($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"]);
                        $i = $f->diff($t);
                        $hrs = substr(number_format(100 + $i->h, 0), -2) . ":" . substr(number_format(100 + $i->i, 0), -2);
                    } else {
                        $hrs = "";
                    }
                    $time = $attendancelog_data["log_date"].' '.$attendancelog_data["log_time"];
                    $log_type->execute(array(":log_value" => $attendancelog_data["log_type"]));
                    if ($log_type->rowCount()) {
                        $log_type_data = $log_type->fetch(PDO::FETCH_ASSOC);
                        $desc = $log_type_data["log_message"];
                    } else {
                        $desc = "";
                    }
                    $d = new DateTime($time);
                    ?>
                    <tr class="log">
                        <td><?php echo number_format(++$cnt, 0); ?>.</td>
                        <td align="center"><?php echo $d->format("h:i:s A"); ?></td>
                        <td><?php echo $desc; ?></td>
                        <td align="center"><?php echo $hrs; ?></td>
                    </tr>
                    <?php
                } ?>
                </tbody>
            </table>
        </div>
        <div class="w3-left">
            <table class="w3-table-all" style="margin-top: 26%;">
                <thead>
                    <tr>
                        <th>TIME DESC</th>
                        <th>TIME</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if(!empty($duty["credit"])){ ?>
                    <tr>
                        <td>DUTY</td>
                        <td><?php echo $duty["credit"]; ?></td>
                    </tr>
                <?php } 
                if(!empty($duty["break"])){ ?>
                    <tr>
                        <td>BREAK-TIME</td>
                        <td><?php echo $duty["break"]; ?></td>
                    </tr>
                <?php } 
                if(!empty($duty["coffee"])){ ?>
                    <tr>
                        <td>COFFEE BREAK</td>
                        <td><?php echo $duty["coffee"]; ?></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <?php
            } ?>
        </div>
        <div class="w3-left w3-margin-left" id="swipe_memo">
            <table class="w3-table-all w3-small">
                <thead>
                    <tr>
                        <th colspan="3" class="w3-center">MEMO LIST</th>
                    </tr>
                </thead>
                <tbody>
            <?php
            $swipe_memo_code = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code`");
            $cnt=0;
            $swipe_memo_code->execute();
            if($swipe_memo_code->rowCount()){
                while($swipe_memo_code_data = $swipe_memo_code->fetch(PDO::FETCH_ASSOC)){ 
                    $swipe_memo = $db->prepare("SELECT * FROM $db_hris.`swipe_memo` WHERE `trans_date`=:date AND `pin`=:pin AND `swipe_memo_code`=:scode AND !`is_cancelled`");
                    $swipe_memo->execute(array(":date" => $record["date"], ":pin" => $record["pin"], ":scode" => $swipe_memo_code_data["swipe_memo_code"]));
                    ?>
                    <tr class="w3-hover-red <?php if($swipe_memo->rowCount()) echo 'w3-red'; ?>" style="cursor: pointer;">
                        <td><?php echo number_format(++$cnt); ?>.</td>
                        <td><span data-code="<?php echo $swipe_memo_code_data["swipe_memo_code"]; ?>"><?php echo $swipe_memo_code_data["description"]; ?></span></td>
                        <td align="center">
                            <div id="p<?php echo $swipe_memo_code_data["swipe_memo_code"]; ?>">
                                <?php if($swipe_memo->rowCount()){
                                    echo '<button class="trash" id="c'.$swipe_memo_code_data["swipe_memo_code"].'" style="padding: 2px 5px 2px 4px;" onclick="cancel_memo('.$swipe_memo_code_data["swipe_memo_code"].','.$record["pin"].');"><ion-icon name="trash-outline"></ion-icon></button>';
                                }else{
                                    echo '<button id="s'.$swipe_memo_code_data["swipe_memo_code"].'" style="padding: 2px 5px 2px 4px;" onclick="for_memo('.$swipe_memo_code_data["swipe_memo_code"].','.$record["pin"].')"><ion-icon name="save-outline"></ion-icon></button>';
                                } ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                        }
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

function get_time($record) {
    global $db, $db_hris;

    $b = $db->prepare("SELECT `log_date`, `log_time`, `log_type` FROM $db_hris.`attendance_log` WHERE `pin` LIKE :pin AND CONCAT(`log_date`, ' ',`log_time`) >= :df AND `log_date`<=:dt ORDER BY CONCAT(`log_date`, ' ',`log_time`)");
    $b->execute(array(":pin" => $record["recid"], ":df" => $record["df"], ":dt" => $record["dt"]));
    if ($b->rowCount()) {
        $type = $cnt = $start = $credit = $break = $coffee = 0;
        while ($data = $b->fetch(PDO::FETCH_ASSOC)) {
            if ($cnt++) {
                $end = substr($data["log_date"]." ".$data["log_time"], 11, 2) * 60 + substr($data["log_date"]." ".$data["log_time"], 14, 2);
                $time = number_format($end - $start, 0, '.', '');
                switch (number_format($type, 0, '.', '')) {
                    case number_format(2, 0):
                        //break
                        $break += $time;
                        break;
                    case number_format(3, 0):
                        //coffee
                        $coffee += $time;
                        break;
                    default :
                        $credit += $time;
                }
                $start = $end;
            } else {
                $start = substr($data["log_date"]." ".$data["log_time"], 11, 2) * 60 + substr($data["log_date"]." ".$data["log_time"], 14, 2);
            }
            $type = $data["log_type"];
        }
        if (number_format($credit, 0, '.', '') !== number_format(0, 0)) {
            $time = number_format($credit, 0, '.', '');
            $d = new DateTime(date("m/d/Y"));
            $d->modify("+$time minutes");
            $credit = $d->format("H:i");
        }
        if (number_format($break, 0, '.', '') !== number_format(0, 0)) {
            $time = number_format($break, 0, '.', '');
            $d = new DateTime(date("m/d/Y"));
            $d->modify("+$time minutes");
            $break = $d->format("H:i");
        }
        if (number_format($coffee, 0, '.', '') !== number_format(0, 0)) {
            $time = number_format($coffee, 0, '.', '');
            $d = new DateTime(date("m/d/Y"));
            $d->modify("+$time minutes");
            $coffee = $d->format("H:i");
        }
    }
    return array("credit" => $credit, "break" => $break, "coffee" => $coffee);
}

function make_swipe_memo($record){
    global $db, $db_hris;

    if($record["is_new"]){
        $check_log = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `trans_date`=:date");
        $check_log->execute(array(":date" => $record["trans_date"]));
        if ($check_log->rowCount()) {
            $swipe_memo = $db->prepare("SELECT * FROM $db_hris.`swipe_memo` WHERE `trans_date`=:trans_date AND `pin`=:pin AND `swipe_memo_code`=:scode AND !`is_cancelled`");
            $swipe_memo->execute(array(":trans_date" => $record["trans_date"], ":pin" => $record["pin"], ":scode" => $record["swipe_memo_code"]));
            if($swipe_memo->rowCount()){
                $ins = $db->prepare("UPDATE $db_hris.`swipe_memo` SET `user_id`=:uid, `station_id`=:station WHERE `trans_date`=:date AND `pin`=:pin AND `swipe_memo_code`=:scode AND !`is_cancelled`");
            }else{
                $ins = $db->prepare("INSERT INTO $db_hris.`swipe_memo` (`trans_date`, `pin`, `swipe_memo_code`,`user_id`, `station_id`) VALUES (:date, :pin, :scode, :uid, :station)");
            }
            $ins->execute(array(":uid" => $_SESSION["name"], ":station" => $_SERVER["REMOTE_ADDR"], ":date" => $record["trans_date"], ":pin" => $record["pin"], ":scode" => $record["swipe_memo_code"]));
            if($ins->rowCount()){
                $swipe = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:scode");
                $swipe->execute(array(":scode" => $record["swipe_memo_code"]));
                if ($swipe->rowCount()) {
                    $swipe_data = $swipe->fetch(PDO::FETCH_ASSOC);
                    if($swipe_data["is_penalized"]){
                        $ok = make_penalty(array("pin" => $record["pin"], "swipe_memo_code" => $record["swipe_memo_code"], "trans_date" => $record["trans_date"], "penalty_amt" => $swipe_data["penalty_amount"], "penalty_to" => $swipe_data["penalty_to"]));
                        if($ok){
                            echo json_encode(array("status" => "success", "record" => $record, "ok" => $ok));
                        }else{
                            echo json_encode(array("status" => "error", "message" => "Error making penalty!", "ok" => $ok));
                            return;
                        }
                    }else{
                        if($swipe_data["is_update_time"] == 2){
                            $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
                            $master->execute(array(':pin' => $record["pin"]));
                            if ($master->rowCount()) {
                                $master_data = $master->fetch(PDO::FETCH_ASSOC);
                                $ok = update_emp_time(array("emp_no" => $master_data['employee_no'],"trans_date" => $record["trans_date"], "time" => 240));
                                if($ok){
                                    echo json_encode(array("status" => "success", "record" => $record, "ok" => $ok));
                                }else{
                                    echo json_encode(array("status" => "error", "message" => "Error updating time!", "ok" => $ok));
                                    return;
                                }
                            }else{
                                echo json_encode(array("status" => "error", "message" => "Error! Employee pin not found!!", "record" => $record, "master" => $master->errorInfo(), "name" => $_SESSION["name"]));
                                return;
                            }
                        }
                    }
                }else{
                    echo json_encode(array("status" => "error", "message" => "Error! Swipe memo not found!!", "record" => $record, "swipe" => $swipe->errorInfo(), "name" => $_SESSION["name"]));
                    return;
                }
            }else{
                echo json_encode(array("status" => "error", "message" => "Error in updating/inserting swipe memo!!", "record" => $record, "e" => $ins->errorInfo(), "name" => $_SESSION["name"]));
                return;
            }
        }else{
            echo json_encode(array("status" => "error", "message" => "Please generate time for ".(new DateTime($record["trans_date"]))->format("m-d-Y")." first!"));
            return;
        }
    }else{
        $rm = $db->prepare("UPDATE $db_hris.`swipe_memo` SET `is_cancelled`=:cancel, `user_id`=:uid, `station_id`=:station, `cancelled_time`=:time WHERE `trans_date`=:date AND `pin`=:pin AND `swipe_memo_code`=:scode AND !`is_cancelled`");
        $rm->execute(array(":cancel" => 1, ":uid" => $_SESSION["name"], ":station" => $_SERVER["REMOTE_ADDR"], ":time" => date("Y-m-d H:i:s"), ":date" => $record["trans_date"], ":pin" => $record["pin"], ":scode" => $record["swipe_memo_code"]));
        if($rm->rowCount()){
            $swipe = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:scode");
            $swipe->execute(array(":scode" => $record["swipe_memo_code"]));
            if ($swipe->rowCount()) {
                $swipe_data = $swipe->fetch(PDO::FETCH_ASSOC);
                $ok = cancel_penalty(array("pin" => $record["pin"], "swipe_memo_code" => $record["swipe_memo_code"], "trans_date" => $record["trans_date"], "penalty_amt" => $swipe_data["penalty_amount"], "penalty_to" => $swipe_data["penalty_to"]));
                if($ok){
                    echo json_encode(array("status" => "success", "record" => $record, "ok" => $ok));
                }else{
                    echo json_encode(array("status" => "error", "message" => "Error updating penalty!", "ok" => $ok));
                }
            }else{
                echo json_encode(array("status" => "error", "message" => "Error! Swipe memo not found!!", "record" => $record, "swipe" => $swipe->errorInfo(), "name" => $_SESSION["name"]));
            }
        }else{
            echo json_encode(array("status" => "error", "message" => "Error in cancelling swipe memo!!", "record" => $record, "e" => $rm->errorInfo(), "name" => $_SESSION["name"]));
        }
    }
}

function make_penalty($record){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
    $master->execute(array(':pin' => $record["pin"]));
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
            $emp_deductions->execute(array(":ded_no" => $record["penalty_to"], ":emp_no" => $master_data['employee_no']));
            if ($emp_deductions->rowCount()){
                if($record["penalty_amt"] > 0){
                    $update_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=`deduction_amount`+:ded_amt, `deduction_balance`=`deduction_balance`+:ded_bal, `user_id`=:uid, `station_id`=:ip WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
                    $update_ded->execute(array(":emp_no" => $master_data['employee_no'], ":ded_amt" => $record["penalty_amt"], ":ded_bal" => $record["penalty_amt"], ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $record["penalty_to"]));
                }
                $ledger = insert_ledger(array("pin" => $master_data["pin"], "swipe_memo_code" => $record["swipe_memo_code"], "trans_date" => $record["trans_date"], "penalty_amt" => $record["penalty_amt"], "penalty_to" => $record["penalty_to"], "option" => 2));
                if($ledger){
                    $time = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:code");
                    $time->execute(array(":code" => $record["swipe_memo_code"]));
                    if ($master->rowCount()) {
                        $time_data = $time->fetch(PDO::FETCH_ASSOC);
                        $credit_time = 0;
                        if($time_data['is_update_time'] === 1){
                            $credit_time += 480;
                        }elseif($time_data['is_update_time'] === 2){
                            $credit_time += 240;
                        }
                        $ok = update_emp_time(array("emp_no" => $master_data['employee_no'], "trans_date" => $record["trans_date"] ,"time" => $credit_time));
                        if($ok){
                            $data = 1;
                        }else{
                            $data = $ok;
                        }
                    }else{
                        $data = array("status" => "error", "message" => "Employee Pin not found!", "e" => $master->errorInfo());
                    }
                }else{
                    $data = array("status" => "error", "message" => "Error updating the Employee deduction ledger!");
                }
            }else{
                $new_ded = $db->prepare("INSERT INTO $db_hris.`employee_deduction`(`employee_no`, `deduction_no`, `deduction_amount`, `deduction_balance`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :ded_amt, :ded_bal, :uid, :ip)");
                $new_ded->execute(array(":emp_no" => $master_data['employee_no'], ":ded_amt" => $record["penalty_amt"], ":ded_bal" => $record["penalty_amt"], ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $record["penalty_to"]));
                if($new_ded->rowCount()){
                    $ledger = insert_ledger(array("pin" => $master_data["pin"], "swipe_memo_code" => $record["swipe_memo_code"], "trans_date" => $record["trans_date"], "penalty_amt" => $record["penalty_amt"], "penalty_to" => $record["penalty_to"], "option" => 2));
                    if($ledger){
                        $time = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:code");
                        $time->execute(array(":code" => $record["swipe_memo_code"]));
                        if ($master->rowCount()) {
                            $time_data = $time->fetch(PDO::FETCH_ASSOC);
                            $credit_time = 0;
                            if($time_data['is_update_time'] === 1){
                                $credit_time += 480;
                            }elseif($time_data['is_update_time'] === 2){
                                $credit_time += 240;
                            }
                            $ok = update_emp_time(array("emp_no" => $master_data['employee_no'], "trans_date" => $record["trans_date"] ,"time" => $credit_time));
                            if($ok){
                                $data = 1;
                            }else{
                                $data = $ok;
                            }
                        }else{
                            $data = array("status" => "error", "message" => "Employee Pin not found!", "e" => $master->errorInfo());
                        }
                    }else{
                        $data = array("status" => "error", "message" => "Error updating the Employee deduction ledger!");
                    }
                }else{
                    $data = array("status" => "error", "message" => "Error updating the Employee deduction!");
                }
            }
        }
    }
    return $data;
}

function cancel_penalty($record){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
    $master->execute(array(':pin' => $record["pin"]));
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
            $emp_deductions->execute(array(":ded_no" => $record["penalty_to"], ":emp_no" => $master_data['employee_no']));
            if($record["penalty_to"] > 0){
                if ($emp_deductions->rowCount()){
                    if($record["penalty_amt"] > 0){
                        $update_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=`deduction_amount`-:ded_amt, `deduction_balance`=`deduction_balance`-:ded_bal, `user_id`=:uid, `station_id`=:ip WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
                        $update_ded->execute(array(":emp_no" =>$master_data['employee_no'], ":ded_amt" => number_format($record["penalty_amt"],2), ":ded_bal" => number_format($record["penalty_amt"],2), ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $record["penalty_to"]));
                    }
                    $ok = insert_ledger(array("pin" => $record["pin"], "swipe_memo_code" => $record["swipe_memo_code"], "trans_date" => $record["trans_date"], "penalty_amt" => $record["penalty_amt"], "penalty_to" => $record["penalty_to"], "option" => 1));
                    if($ok){
                        $data = 1;
                    }else{
                        $data = $ok;
                    }
                }else{
                    $data = array("status" => "error", "message" => "Error updating the Employee deduction!");
                }
            }else{
                $data = 1;
            }
        }
    }
    return $data;
}

function insert_ledger($record){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
    $master->execute(array(':pin' =>$record["pin"]));
    if ($master->rowCount()) {
        $master_data = $master->fetch(PDO::FETCH_ASSOC);
        $emp_no = $master_data['employee_no'];

        $swipe_memo = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:code");
        $swipe_memo->execute(array(":code" => $record["swipe_memo_code"]));
        if($swipe_memo->rowCount()){
            $ledger = $db->prepare("SELECT * FROM $db_hris.`employee_deduction_ledger` WHERE `employee_no`=:eno AND `deduction_no`=:ded_no ORDER BY `ledger_no` DESC LIMIT 1");
            $swipe_memo_data = $swipe_memo->fetch(PDO::FETCH_ASSOC);
            $ledger->execute(array(":ded_no" => $record["penalty_to"], ":eno" => $emp_no));
            if($ledger->rowCount()){
                $ledger_data = $ledger->fetch(PDO::FETCH_ASSOC);

                if($record["option"] === 1){
                    $remark = "Cancel ".$swipe_memo_data['description']." on ".$record["trans_date"];
                    $pen = '-'.$record["penalty_amt"];
                    if($ledger_data["balance"] > $record["penalty_amt"]){
                        $ded_bal = $ledger_data["balance"]-$record["penalty_amt"];
                    }else{
                        $ded_bal = 0;
                    }
                }else{
                    $remark = $swipe_memo_data['description']." on ".$record["trans_date"];
                    $pen = $record["penalty_amt"];
                    $ded_bal = $ledger_data["balance"]+$record["penalty_amt"];
                }
                $ref = "Swipe Memo";

                $emp_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger`(`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :ded_amt, :ded_bal, :remark, :ref, :uid, :ip)");
                $emp_ledger->execute(array(":emp_no" => $emp_no, ":ded_no" => $record["penalty_to"], ":date" => date('Y-m-d'), ":ded_amt" => $pen, ":ded_bal" => $ded_bal, ":remark" => $remark, ":ref" => $ref, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
                if($emp_ledger->rowCount()){
                    $data = 1;
                }else{
                    $data = $emp_ledger->errorInfo();
                }
            }else{
                $remark = $swipe_memo_data['description']." on ".$record["trans_date"];
                $ref = "Swipe Memo";

                $emp_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger`(`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :ded_amt, :ded_bal, :remark, :ref, :uid, :ip)");
                $emp_ledger->execute(array(":emp_no" => $emp_no, ":ded_no" => $record["penalty_to"], ":date" => date('Y-m-d'), ":ded_amt" => $record["penalty_amt"], ":ded_bal" => $record["penalty_amt"], ":remark" => $remark, ":ref" => $ref, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
                if($emp_ledger->rowCount()){
                    $data = 1;
                }else{
                    $data = $emp_ledger->errorInfo();
                }
            }
        }
    }
    return $data;
}

function update_emp_time($record){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:emp_no");
    $master->execute(array(':emp_no' => $record["emp_no"]));
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $time_credited_check = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `employee_no`=:pin AND `trans_date`=:date");
            $time_credited_check->execute(array(":pin" => $master_data["pin"], ":date" => $record["trans_date"]));
            if ($time_credited_check->rowCount()){
                $update_credit_time = $db->prepare("UPDATE $db_hris.`time_credit` SET `credit_time`=:ctime WHERE `employee_no`=:pin AND `trans_date`=:date");
                $update_credit_time->execute(array(":pin" => $master_data["pin"], ":date" => $record["trans_date"], ":ctime" => $record["time"]));
                if($update_credit_time->rowCount()){
                    $data = 1;
                }else{
                    $data = $update_credit_time->errorInfo();
                }
            }else{
                $new_credit_time = $db->prepare("INSERT INTO $db_hris.`time_credit` (`employee_no`, `trans_date`, `credit_time`) VALUES (:pin, :trans_date, :ctime)");
                $new_credit_time->execute(array(":pin" => $master_data["pin"], ":trans_date" => $record["trans_date"], ":ctime" => $record["time"]));
                if($new_credit_time->rowCount()){
                    $data = 1;
                }else{
                    $data = $new_credit_time->errorInfo();
                }
            }
        }
    }
    return $data;
}