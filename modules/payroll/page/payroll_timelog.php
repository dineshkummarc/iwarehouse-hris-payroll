<?php
error_reporting(0);
$program_code = 13;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "get-timelog": //ok
                    if ($access_rights === "A+E+D+B+P+") {
                        get_time_log(array("store" => $_POST["_store"], "group_name" => $_POST["_group"]));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "generate-time-and-credit": //ongoing
                    if ($access_rights === "A+E+D+B+P+") {
                        $record = array("trans_date" => (new DateTime($_POST["trans_date"]))->format("Y-m-d"), "store" => $_POST["_store"], "group" => $_POST["_group"]);
                        generate_time_and_credit($record);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "make-default": //ok
                    $store = get_store();
                    $group = get_group();
                    echo json_encode(array("status" => "success", "store_list" => $store, "group_list" => $group));
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

//get all available stores
function get_store() {
    global $db, $db_hris;

    $store = $db->prepare("SELECT `store`.`StoreCode`,`store`.`StoreName` FROM $db_hris.`store`");
    $store_list = array();
    $store->execute();
    if ($store->rowCount()) {
        while ($store_data = $store->fetch(PDO::FETCH_ASSOC)) {
            $store_list[] = array("id" => $store_data["StoreCode"], "text" => $store_data["StoreName"]);
        }
    }
    return $store_list;
}

function get_columns($log_cutoff, $payroll_cutoff){
    global $db, $db_hris;

    $items = array();
    $items[] = array("field" => "recid", "caption" => "<b>PIN</b>", "size" => "80px");
    $items[] = array("field" => "name", "caption" => "<b>NAME</b>", "size" => "200px");
    $df = new DateTime($log_cutoff);
    $dt = new DateTime($payroll_cutoff);
    while($df <= $dt){ 
        $holiday = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date`=:date");
        $holiday->execute(array(":date" => $df->format("Y-m-d")));
        if($holiday->rowCount()){
            $holiday_data = $holiday->fetch(PDO::FETCH_ASSOC);
            $color = $holiday_data["is_special"] ? "background-color: yellow" : "background-color: #e5281a; color: white";
            $items[] = array("field" => $df->format("Y-m-d"), "caption" => "<b>".$df->format("m/d")."</b>", "size" => "80px", "attr" => "align=center", "style" => $color);
        }else{
            $items[] = array("field" => $df->format("Y-m-d"), "caption" => "<b>".$df->format("m/d")."</b>", "size" => "80px", "attr" => "align=center");
        }
        $df->modify("+1 day");
    }
    $items[] = array("field" => "total", "caption" => "<b>TOTAL HRS</b>", "size" => "100px", "attr" => "align=right");
    return $items;
}

function get_time_log($record){
    global $db, $db_hris;
    
    $datas = array();
    $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:group_name");
    $payroll_group->execute(array(":group_name" => $record["group_name"]));
    if ($payroll_group->rowCount()) {
        set_time_limit(300);
        $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
        $log_cutoff = $payroll_group_data["cutoff_date"];
        $payroll_cutoff = $payroll_group_data["payroll_date"];
        $grid = get_columns($log_cutoff, $payroll_cutoff);
        $date = $log_cutoff;
        while($date <= $payroll_cutoff){
            $date = date('Y-m-d', mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2) + 1, substr($date, 0, 4)));
            $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `group_no`=:group_no AND `store`=:store ORDER BY `family_name`, `given_name`, `middle_name`");
            $master->execute(array(":group_no" => $payroll_group_data["group_name"], ":store" => $record["store"]));
            if($master->rowCount()){
                $employee_rate = $db->prepare("SELECT * FROM $db_hris.`employee_rate` WHERE `employee_no`=:eno");
                $credit = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `trans_date`=:date AND `employee_no`=:pin");
                $evl =  $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `vl_date`=:date AND `employee_no`=:eno");
                $time_log = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date`=:date");
                $shift = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:eno AND `work_schedule` IS NULL");
                $posted_shift = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `trans_date`=:date");
                while($master_data = $master->fetch(PDO::FETCH_ASSOC)){ 
                    $employee_rate->execute(array(":eno" => $master_data["employee_no"]));
                    if($employee_rate->rowCount()){
                        $employee_rate_data = $employee_rate->fetch(PDO::FETCH_ASSOC);
                        $total=0;
                        $data["recid"] = $master_data["pin"];
                        $data["name"] = $master_data["family_name"].', '.$master_data["given_name"].' '.substr($master_data["middle_name"], 0,1);
                        $date = $log_cutoff;
                        while($date <= $payroll_cutoff){
                            $credit->execute(array(":date" => $date, ":pin" => $master_data["pin"]));
                            $evl->execute(array(":date" => $date, ":eno" => $master_data["employee_no"]));
                            $time_log->execute(array(":date" => $date, ":pin" => $master_data["pin"]));
                            $time_log->execute(array(":date" => $date, ":pin" => $master_data["pin"]));
                            $posted_shift->execute(array(":date" => $date, ":eno" => $master_data["employee_no"]));
                            $shift->execute(array(":eno" => $master_data["employee_no"]));
                            if(!$employee_rate->rowCount() OR $employee_rate->rowCount() AND number_format($employee_rate_data["daily_rate"], 2) ==  number_format(0, 2)){
                                $data["$date"] = 'No Rate';
                            }else{
                                if($credit->rowCount()){
                                    $time_credit_data = $credit->fetch(PDO::FETCH_ASSOC);
                                    if($time_credit_data["credit_time"] > 0){
                                        $data["$date"] = format_time($time_credit_data['credit_time']);
                                        $total+=$time_credit_data["credit_time"];
                                    }else{
                                        $data["$date"] = "-0-";
                                    }
                                }elseif($evl->rowCount()){
                                    $evl_data = $evl->fetch(PDO::FETCH_ASSOC);
                                    if(!$evl_data["is_cancelled"]){
                                        $data["$date"] = "-VL-";
                                    }else{
                                        $data["$date"] = "-0-";
                                    }
                                }elseif(!$posted_shift->rowCount()){
                                    $data["$date"] = "-NPS-";
                                }elseif($time_log->rowCount()){
                                    if($shift->rowCount()){
                                        $data["$date"] = "-NS-";
                                    }else{
                                        $data["$date"] = "-A-";
                                    }
                                }else{
                                    $data["$date"] = "-0-";
                                }
                            }
                            $date=date('Y-m-d', mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2) + 1, substr($date, 0, 4)));
                        }
                        if($total > 0){
                            $time_mins = $total;
                            $time_min = $total % 60;
                            $time_hrs = ($time_mins - $time_min) / 60;
                            $credit_time = $time_hrs . ":" . substr(number_format($time_min + 100), 1, 2);
                            $data["w2ui"]["style"] = "";
                            $data["total"] = "<b>".$credit_time."</b>";
                        }else{
                            $data["w2ui"]["style"] = "color: red;";
                            $data["total"] = "-0-";
                        }
                        array_push($datas, $data);
                    }
                }
            }
        }
        echo json_encode(array("status" => "success", "columns" => $grid, "records" => $datas, "log_cutoff" => $log_cutoff, "payroll_cutoff" => $payroll_cutoff));
    }else{
        echo json_encode(array("status" => "error", "message" => "No record found!!", "record" => $record));
    }
    
}

function format_time($time) {
    $t = number_format($time, 0, '.', '');
    $day = new DateTime(date("m/d/Y"));
    $day->modify("+$t minutes");
    return $day->format("H:i");
}

function generate_time_and_credit($record){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`attendance_log` AS al INNER JOIN $db_hris.`master_data` AS md ON al.`pin` = md.`pin` WHERE md.`store` = :store AND !md.`is_inactive` AND md.`group_no` = :group_no AND al.`log_date` = :trans_date GROUP BY al.`pin` ORDER BY al.`row_id`");
    $master->execute(array(":store" => $record["store"], ":group_no" => $record["group"], ":trans_date" => $record["trans_date"]));
    if($master->rowCount()){
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
            $tdate = new DateTime($master_data["log_date"]);
            $df = $tdate->format("Y-m-d") . " 04:00:00";
            $dt = $tdate->format("Y-m-d") . " 03:59:59";
            $time_data = get_time(array("pin" => $master_data["pin"], "df" =>$df, "dt" => $dt));
            $ok = credit_emp_time(array("pin" => $master_data["pin"], "trans_date" => $record["trans_date"], "credit_time" => $time_data["credit"], "group" => $record["group"], "store" => $record["store"]));
            $data = $ok ? array("status" => "success", "message" => "Done generate time and time credit for ".(new DateTime($record["trans_date"]))->format("m-d-Y")."!", "count" => $ok) : array("status" => "error", "message" => "Error crediting time!", "e" => $ok, "count" => $master->rowCount());
        }
    }else{
        $data = array("status" => "error", "message" => "No record of attendance on ".(new DateTime($record["trans_date"]))->format("m-d-Y")."!!", "record" => $record,"e" => $master->errorInfo());
    }
    echo json_encode($data);
}

function get_time($record) {
    global $db, $db_hris;

    $b = $db->prepare("SELECT `log_date`, `log_time`, `log_type` FROM $db_hris.`attendance_log` WHERE `pin` LIKE :pin AND CONCAT(`log_date`, ' ',`log_time`) >= :df AND `log_date`<=:dt ORDER BY CONCAT(`log_date`, ' ',`log_time`)");
    $b->execute(array(":pin" => $record["pin"], ":df" => $record["df"], ":dt" => $record["dt"]));
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
            $credit = number_format($credit, 0, '.', '');
        }
        if (number_format($break, 0, '.', '') !== number_format(0, 0)) {
            $break = number_format($break, 0, '.', '');
        }
        if (number_format($coffee, 0, '.', '') !== number_format(0, 0)) {
            $coffee = number_format($coffee, 0, '.', '');
        }
    }
    return array("credit" => $credit, "break" => $break, "coffee" => $coffee);
}

function credit_emp_time($record){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `store`=:store AND `group_no`=:grp AND `pin`=:pin ORDER BY `family_name` ASC");
    $master->execute(array(":store" => $record["store"], ":grp" => $record["group"], ":pin" => $record["pin"]));
    if ($master->rowCount()) {
        $master_data = $master->fetch(PDO::FETCH_ASSOC);
        $absent = save_absent($record["trans_date"], $record["group"], $record["store"]);
        $late = save_late($record);
        $del = remove_trans($record);
        if($late > 0 || $absent > 0 || $del > 0){
            $emp_sched = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `trans_date`=:sdate");
            $emp_sched->execute(array(":eno" => $master_data['employee_no'], ":sdate" => $record["trans_date"]));
            if ($emp_sched->rowCount()) {
                $sched_data = $emp_sched->fetch(PDO::FETCH_ASSOC);
                $shift_code = $sched_data["shift_code"];
                $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:code");
                $shift->execute(array(":code" => $shift_code));
                if ($shift->rowCount()){
                    $shift_data = $shift->fetch(PDO::FETCH_ASSOC);
                    if ($shift_data["is_off_duty"]){
                        $ok = credited_time($master_data['employee_no'],$record["credit_time"],$record["trans_date"],1);
                        $credit = $ok ? 1 : $ok;
                    }elseif($shift_data["is_open"]){
                        $ok = credited_time($master_data['employee_no'],$record["credit_time"],$record["trans_date"],0);
                        $credit = $ok ? 1 : $ok;
                    }else{
                        $ok = credited_time($master_data['employee_no'],$record["credit_time"],$record["trans_date"],0);
                        $credit = $ok ? 1 : $ok;
                    }
                }
            }
        }else{
            $credit = array("abs" => $absent, "late" => $late, "del" => $del);
        }
        $credit = array("abs" => $absent, "late" => $late, "del" => $del);
    }else{
        $credit = array("message" => "Record not found in master file!", "e" => $master->errorInfo());
    }
    return $credit;
}

function save_late($record){
    global $db, $db_hris;

    $att_log = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date`=:trans_date AND `log_type`=:no ORDER BY `log_date`,`log_time` DESC LIMIT 1"); //check attendance log
    $att_log->execute(array(":pin" => $record["pin"], ":trans_date" => $record["trans_date"], ":no" => 0));
    if ($att_log->rowCount()) {
        $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin"); //check if exist
        $emp_sched = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `trans_date`=:sdate"); //check employee work sched
        $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:code AND !`is_off_duty`"); //check shift
        $exist = $db->prepare("SELECT * FROM $db_hris.`employee_late` WHERE `employee_no`=:eno AND `trans_date`=:date"); //check if exist
        $att_log_data = $att_log->fetch(PDO::FETCH_ASSOC);
        $master->execute(array(":pin" => $record["pin"]));
        if ($master->rowCount()) {
            $master_data = $master->fetch(PDO::FETCH_ASSOC);
            $emp_sched->execute(array(":eno" => $master_data["employee_no"], ":sdate" => $record["trans_date"]));
            if ($emp_sched->rowCount()) {
                $sched_data = $emp_sched->fetch(PDO::FETCH_ASSOC);
                $shift->execute(array(":code" => $sched_data["shift_code"]));
                if ($shift->rowCount()) {
                    $shift_data = $shift->fetch(PDO::FETCH_ASSOC);
                    $time = $shift_data['is_open'] ? '24:00:00' : $shift_data["past_late"].':59';
                    $time_start = new DateTime($record["trans_date"].' '.$att_log_data['log_time']);
                    $time_sched = new DateTime($record["trans_date"].' '.$time);
                    $interval = $time_start->diff($time_sched);
                    $time = $shift_data['is_open'] ? '24:00:00' : $shift_data["past_late"].':59';
                    if($interval->format('%i') < 10){
                        $mins = $interval->format('%h').":0".$interval->format('%i');
                    }else{
                        $mins = $interval->format('%h').":".$interval->format('%i');
                    }
                    if($att_log_data['log_time'] > $time){
                        $exist->execute(array(":eno" => $master_data["employee_no"], ":date" => $record["trans_date"]));
                        if($exist->rowCount()){
                            $late = $db->prepare("UPDATE $db_hris.`employee_late` SET `mins_late`=:mins, `start_time`=:start, `log_time`=:log_time, `isLate`=:isLate WHERE `employee_no`=:eno AND `trans_date`=:tdate");
                        }else{
                            $late = $db->prepare("INSERT INTO $db_hris.`employee_late` SET `employee_no`=:eno, `trans_date`=:tdate, `mins_late`=:mins, `start_time`=:start, `log_time`=:log_time, `isLate`=:isLate");
                        }
                        $late->execute(array(":eno" => $master_data["employee_no"], ":tdate" => $record["trans_date"], ":mins" => $mins, ":start" => $time, ":log_time" => $att_log_data['log_time'], ":isLate" => 1));
                        $data = $late->rowCount() ? 1 : $late->errorInfo();
                    }else{
                        $data = 1;
                    }
                }else{
                    $data = "No Shift Found!";
                }
            }else{
                $data = "NO Shift sched found!";
            }
        }else{
            $data = "No record in master file";
        }
    }else{
        $data ="error";
    }
    return $data;
}

function save_absent($trans_date,$group,$store){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `group_no`=:group AND `store`=:store");
    $master->execute(array(":group" => $group, ":store" => $store));
    if ($master->rowCount()) {
        $check = $db->prepare("SELECT * FROM $db_hris.`employee_absent` WHERE `employee_no`=:eno AND `absent_date`=:date"); //check if exist
        $del_abs = $db->prepare("DELETE FROM $db_hris.`employee_absent` WHERE `employee_no`=:eno AND `absent_date`=:date"); //delete
        $att = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date`=:date"); //attendance
        $sched = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `shift_code` > 0 AND `trans_date`=:date"); //shift sched
        $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:scode AND !`is_off_duty`"); //check shift
        $abs = $db->prepare("INSERT INTO $db_hris.`employee_absent`(`employee_no`, `absent_date`, `is_absent`) VALUES (:eno, :date, :abs)"); //insert absent
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
            $check->execute(array(":eno" => $master_data["employee_no"], ":date" => $trans_date));
            if($check->rowCount()){
                $del_abs->execute(array(":eno" => $master_data["employee_no"], ":date" => $trans_date));
                if($del_abs->rowCount()){
                    $att->execute(array(":pin" => $master_data["pin"], ":date" => $trans_date));
                    if (!$att->rowCount()) {
                        $sched->execute(array(":eno" => $master_data["employee_no"], ":date" => $trans_date));
                        if ($sched->rowCount()) {
                            $sched_data = $sched->fetch(PDO::FETCH_ASSOC);
                            $shift->execute(array(":scode" => $sched_data["shift_code"]));
                            if($shift->rowCount()){
                                $abs->execute(array(":eno" => $master_data["employee_no"], ":date" => $trans_date, ":abs" => 1));
                                $data = $abs->rowCount() ? 1 : $abs->errorInfo();
                            }else{
                                $data = array("message" => "error checking shift", "e" =>$shift->errorInfo());
                            }
                        }else{
                            $data = array("message" => "error checking sched", "e" =>$sched->errorInfo());
                        }
                    }else{
                        $data = 1;
                    }
                }else{
                    $data = array("message" => "error deleting employee absent", "e" => $del_abs->errorInfo());
                }
            }else{
                $att->execute(array(":pin" => $master_data["pin"], ":date" => $trans_date));
                if (!$att->rowCount()) {
                    $sched->execute(array(":eno" => $master_data["employee_no"], ":date" => $trans_date));
                    if ($sched->rowCount()) {
                        $sched_data = $sched->fetch(PDO::FETCH_ASSOC);
                        $shift->execute(array(":scode" => $sched_data["shift_code"]));
                        if($shift->rowCount()){
                            $abs->execute(array(":eno" => $master_data["employee_no"], ":date" => $trans_date, ":abs" => 1));
                            $data = $abs->rowCount() ? 1 : $abs->errorInfo();
                        }else{
                            $data = array("message" => "error checking shift", "e" =>$shift->errorInfo());
                        }
                    }else{
                        $data = array("message" => "error checking sched", "e" =>$sched->errorInfo());
                    }
                }else{
                    $data = 1;
                }
            }
        }
    }
    return $data;
}

function remove_trans($record){
    global $db, $db_hris;

    $del = $db->prepare("DELETE FROM $db_hris.`time_credit` WHERE `trans_date`=:date AND `employee_no`=:eno");
    $del_ot = $db->prepare("DELETE FROM $db_hris.`time_credit_ot` WHERE `trans_date`=:date AND `employee_no`=:eno");
    $del->execute(array(":eno" => $record['pin'], ":date" => $record["trans_date"]));
    $del_ot->execute(array(":eno" => $record['pin'], ":date" => $record["trans_date"]));
    if($del->rowCount() OR $del_ot->rowCount()){
        $remove = 1;
    }else{
        $remove = 1;
    }
    return 1;
}

function credited_time($emp_no,$credit_time,$date,$option){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
    $master->execute(array(":no" => $emp_no));
    if($master->rowCount()){
        $master_data = $master->fetch(PDO::FETCH_ASSOC);
        $new_credit_time = $db->prepare("INSERT INTO $db_hris.`time_credit` (`employee_no`, `trans_date`, `credit_time`, `isDOD`) VALUES (:pin, :trans_date, :ctime, :dod)");
        if($credit_time >= 480){
            $new_credit_time->execute(array(":pin" => $master_data["pin"], ":trans_date" => $date, ":ctime" => 480, ":dod" => $option));
            $credited_time = $new_credit_time->rowCount() ? credit_overtime($master_data["pin"],$credit_time,$date) : $new_credit_time->errorInfo();
        }else{
            $whole_time = $credit_time/60;
            $final_time = floor($whole_time) * 60;
            $new_credit_time->execute(array(":pin" => $master_data["pin"], ":trans_date" => $date, ":ctime" => $final_time, ":dod" => $option));
            $credited_time = $new_credit_time->rowCount() ? 1 : $new_credit_time->errorInfo();
        }
    }
    return $credited_time;
}

function credit_overtime($pin,$credit_time,$trans_date){
    global $db, $db_hris;
    
    $time = $credit_time-480;
    $time_min = $time % 60;
    $time_hrs = ($time - $time_min) / 60;
    $ot_time = substr(number_format($time_hrs+100,0), 1,2).".".substr(number_format($time_min+100), 1,2);
    if($time >= 60){
        $new_credit_ot = $db->prepare("INSERT INTO $db_hris.`time_credit_ot` (`employee_no`, `trans_date`, `credit_time`, `is_approved`) VALUES (:pin, :trans_date, :ctime, :approved)");
        $new_credit_ot->execute(array(":pin" => $pin, ":trans_date" => $trans_date, ":ctime" => $ot_time, ":approved" => 0));
        $data = $new_credit_ot->rowCount() ? 1 : $new_credit_ot->errorInfo();
    }else{
        $data = 1;
    }
    return $data;
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