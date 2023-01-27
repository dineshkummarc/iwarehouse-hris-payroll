<?php

$program_code = 3;

require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();

switch ($_POST["cmd"]) {
    case "get-emp-bio":
        $group_name = $_POST["_group"];
        $trans_date = $_POST["_date"];
        $date = (new DateTime($trans_date))->format("Y-m-d");
        get_emp_bio($group_name,$date);
    break;
    case "get-emp-bio-trans":
        delete_temp_time();
        $pin = $_POST["_pin"];
        $trans_date = $_POST["_date"];
        $date = (new DateTime($trans_date))->format("Y-m-d");
        $date1 = new DateTime($_POST["_date"]);
        $day = $date1->format("N");
        if (number_format($day, 0) === number_format(7, 0)) {
            $day = 0;
        }
        get_emp_bio_trans($pin,$date,$day);
    break;
    case "make-swipe-memo":
        $pin=$_POST["pin"];
        $swipe_memo_code=$_POST["code"];
        $date = $_POST["date"];
        $trans_date = (new DateTime($date))->format("Y-m-d");
        $is_new=$_POST["new"];
        $station_id = $_SERVER['REMOTE_ADDR'];
        $session_name = $_SESSION['name'];
        if($is_new=="1"){
            //check if time is generated or not
            global $db, $db_hris;
    
            $check_log = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `trans_date`=:date");
            $check_log->execute(array(":date" => $trans_date));
            if ($check_log->rowCount()) {
                $swipe = mysqli_query($con,"SELECT * FROM `swipe_memo_code` WHERE `swipe_memo_code`='$swipe_memo_code'");
                if (@mysqli_num_rows($swipe)) {
                    $swipe_data = mysqli_fetch_array($swipe);
                    if($swipe_data["is_penalized"] == 1){
                        $penalty_amt = $swipe_data["penalty_amount"];
                        $penalty_to = $swipe_data["penalty_to"];
                        make_penalty($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to);
                    }else{
                        if($swipe_data["is_update_time"] == 2){
                            global $db, $db_hris;
                            
                            $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
                            $master->execute(array(':pin' => $pin));
                            if ($master->rowCount()) {
                                while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
                                    $emp_no = $master_data['employee_no'];
                                    $time = 240;
                                    update_emp_time($emp_no,$trans_date,$time);
                                }
                            }
                        }
                    }
                }
                if(!@mysqli_num_rows(mysqli_query($con,"SELECT * FROM `swipe_memo` WHERE `trans_date`='$trans_date' AND `pin`='$pin' AND `swipe_memo_code`='$swipe_memo_code' AND !`is_cancelled`"))){
                    mysqli_query($con,"INSERT INTO `swipe_memo` (`trans_date`, `pin`, `swipe_memo_code`,`user_id`, `station_id`) VALUES ('$trans_date', '$pin', '$swipe_memo_code', '$session_name', '$station_id')");
                }else{
                    mysqli_query($con,"UPDATE `swipe_memo` SET `remark`='', `user_id`='$session_name', `station_id`='$station_id' WHERE `trans_date`='$trans_date' AND `pin`='$pin' AND `swipe_memo_code`='$swipe_memo_code' AND !`is_cancelled`");
                }
                if(@mysqli_num_rows(mysqli_query($con,"SELECT * FROM `swipe_memo` WHERE `trans_date`='$trans_date' AND `pin`='$pin' AND `swipe_memo_code`='$swipe_memo_code' AND !`is_cancelled`"))){
                    echo "1";
                }else{
                    echo "0";
                }
            }else{
                echo "Please generate time for ".$trans_date." first!";
            }
        }else{
            mysqli_query($con,"UPDATE `swipe_memo` SET `is_cancelled`='1', `user_id`='$session_name', `station_id`='$station_id', `cancelled_time`=NOW() WHERE `trans_date`='$trans_date' AND `pin`='$pin' AND `swipe_memo_code`='$swipe_memo_code' AND !`is_cancelled`");
            echo "1";
            $swipe = mysqli_query($con,"SELECT * FROM `swipe_memo_code` WHERE `swipe_memo_code`='$swipe_memo_code'");
            if (@mysqli_num_rows($swipe)) {
                $swipe_data = mysqli_fetch_array($swipe);
                if($swipe_data["is_penalized"] == 1){
                    $penalty_amt = $swipe_data["penalty_amount"];
                    $penalty_to = $swipe_data["penalty_to"];
                    cancel_penalty($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to);
                }
            }
        }
    break;

}

function get_emp_bio($group_name,$date){
    global $con;

    $employee_query = "SELECT `attendance_log`.`pin`, `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`, `master_data`.`group_no` FROM `attendance_log`, `master_data` WHERE `attendance_log`.`pin`=`master_data`.`pin` AND `log_date`='$date' AND `master_data`.`group_no`='$group_name' GROUP BY `attendance_log`.`pin` ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`";
    $employee =  mysqli_query($con, $employee_query);
    if(@mysqli_num_rows($employee)){ ?>
    <table class="w3-table-all w3-border w3-small w3-hoverable">
        <thead>
            <tr class="w3-orange w3-text-white">
                <th></th>
                <th>EMP PIN</th>
                <th>FULL NAME</th>
                <th>TIME</th>
            </tr> 
        </thead>
        <tbody>
        <?php
        $cnt=0;
        while($employee_data =  mysqli_fetch_assoc($employee)){
            set_time_limit(30);
            $master =  mysqli_query($con, "SELECT * FROM `master_data` WHERE `pin`=$employee_data[pin] AND !`is_inactive`") or die(mysqli_error($con));
            if(@mysqli_num_rows($master)){
                $master_data =  mysqli_fetch_assoc($master);
                $time_credit =  mysqli_query($con,"SELECT * FROM `time_credit` WHERE `trans_date`='$date' AND `employee_no`='$master_data[pin]' AND !`isDOD`") or mysqli_error($con);
                $jo_credit =  mysqli_query($con,"SELECT * FROM `time_credit` WHERE `trans_date`='$date' AND `employee_no`='$master_data[pin]' AND `isDOD`") or mysqli_error($con);
                $late =  mysqli_query($con,"SELECT * FROM `employee_late` WHERE `trans_date`='$date' AND `employee_no`='$master_data[employee_no]'") or mysqli_error($con);
                if(@mysqli_num_rows($time_credit)){
                    $time_credit_data =  mysqli_fetch_array($time_credit);
                    if($time_credit_data["credit_time"]>0){
                        $time_mins = $time_credit_data['credit_time'];
                        $time_min = $time_credit_data["credit_time"] % 60;
                        $time_hrs = ($time_mins - $time_min) / 60;
                        $credit_time = substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
                    }
                }elseif(@mysqli_num_rows($jo_credit)){
                    $jo_credit_data =  mysqli_fetch_array($jo_credit);
                    if($jo_credit_data["credit_time"]>0){
                        $time_mins = $jo_credit_data['credit_time'];
                        $time_min = $jo_credit_data["credit_time"] % 60;
                        $time_hrs = ($time_mins - $time_min) / 60;
                        $credit_time = substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
                    }
                }else{
                    $credit_time = "-0-";
                } ?>
                <tr onclick="get_emp_log(<?php echo $master_data['pin']; ?>)" id="<?php echo $master_data['pin']; ?>" style="cursor: pointer;" class="w3-hoverable emp_log <?php if(@mysqli_num_rows($late)) echo 'w3-text-red'; ?>">
                    <td><?php echo number_format(++$cnt); ?>.</td>
                    <td align="right"><?php echo $master_data["pin"]; ?></td>
                    <td><?php echo $master_data["family_name"].', '.$master_data["given_name"].' '.substr($master_data["middle_name"], 0, 1); ?></td>
                    <td><?php echo $credit_time; ?></td>
                </tr>
                <?php 
            }
        } ?>
        </tbody>
    </table>
    <?php 
    }
}

function get_emp_bio_trans($pin,$date,$day){
    global $con, $db, $db_hris;

    $attendancelog=  mysqli_query($con, "SELECT * FROM `attendance_log` WHERE `pin`='$pin' AND `log_date`='$date' ORDER BY `log_date`,`log_time`");
    $master =  mysqli_query($con, "SELECT * FROM `master_data` WHERE `pin`='$pin' AND !`is_inactive`") or die(mysqli_error($con));
    $master_data =  mysqli_fetch_assoc($master);
    $work_schedule = explode(",", $master_data["work_schedule"]);
    if(@mysqli_num_rows($attendancelog)){ ?>
    <div class="w3-right w3-panel">
            <button class="w3-button w3-red w3-tiny w3-round-medium" onclick="extact_data();">CLOSE</button>
        </div>
    <div class = "w3-panel w3-card-4 w3-padding-large w3-round-medium">
        <div class="w3-left">
        <?php
            $w = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:code");
            $w->execute(array(":code" => $work_schedule[$day]));
                if ($w->rowCount()) {
                    $data = $w->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <table class="w3-table-all w3-row-padding">
                        <thead>
                            <tr>
                                <th colspan="5" class="w3-center"><?php echo date("M j", strtotime($date)); ?> - SHIFT SCHEDULE</th>
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
                $cnt=$total_time=$start_time=0;
                while($attendancelog_data =  mysqli_fetch_array($attendancelog)){ 
                    $log_type_data =  mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `log_type` WHERE `log_value`='$attendancelog_data[log_type]'"));
                    ?>
                    <tr class="log">
                        <td><?php echo number_format(++$cnt); ?>.</td>
                        <td align="center"><?php echo $attendancelog_data["log_time"]; ?></td>
                        <td><?php echo $log_type_data["log_message"]; ?></td>
                        <td align="center"><?php
                                if ($cnt !== 1) {
                                    $end_time = substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 11, 2) * 60 + substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 14, 2);
                                    $timex =  gettime($start_time, $end_time, $log_type, $pin, $date);
                                    $start_time = $end_time;
                                    $log_type = $attendancelog_data["log_type"];
                                    $total_time = $timex;
                                } else {
                                    $timex = "";
                                    $log_type = $attendancelog_data["log_type"];
                                    $start_time =  substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 11, 2) * 60 + substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 14, 2);
                                    $total_time = $timex;
                                }
                                echo $total_time; ?></td>
                    </tr>
                    <?php
                }
            } ?>
                </tbody>
            </table>
        </div>
        <div class="w3-left">
            <table class="w3-table-all" style="margin-top: 22%;">
                <thead>
                    <tr>
                        <th></th>
                        <th>TIME DESC</th>
                        <th>TIME</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $time =  mysqli_query($con,"SELECT * FROM `_tmp_time`, `log_type` WHERE `_tmp_time`.`log_value`=`log_type`.`log_value` ORDER BY `_tmp_time`.`log_value`");
                    $cnt=0;
                    if(@mysqli_num_rows($time))
                    while($time_data=  mysqli_fetch_array($time)){ ?>
                    <tr class="summary">
                        <td><?php echo number_format(++$cnt); ?>.</td>
                        <td><?php if($time_data["log_value"]==0) echo 'TIME'; else echo $time_data["log_message"]; ?></td>
                        <td align="center"><?php
                            $time_min=$time_data["mins"] % 60;
                            $time_hrs=($time_data["mins"] - $time_min) / 60;
                            echo substr(number_format($time_hrs+100,0), 1,2).":".substr(number_format($time_min+100), 1,2); ?>
                        </td>
                    </tr>
                    <?php
                } ?>
                </tbody>
            </table>
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
                    $swipe_memo_code=  mysqli_query($con,"SELECT * FROM `swipe_memo_code`");
                    $cnt=0;
                    if(@mysqli_num_rows($swipe_memo_code))
                    while($swipe_memo_code_data=  mysqli_fetch_array($swipe_memo_code)){ 
                        $swipe_memo =  mysqli_query($con,"SELECT * FROM `swipe_memo` WHERE `trans_date`='$date' AND `pin`='$pin' AND `swipe_memo_code`='$swipe_memo_code_data[swipe_memo_code]' AND !`is_cancelled`");
                        if(@mysqli_num_rows($swipe_memo)){
                            $swipe_memo_data =  mysqli_fetch_array($swipe_memo);
                        } ?>
                        <tr class="w3-hover-red <?php if(@mysqli_num_rows($swipe_memo)) echo 'w3-red'; ?>" style="cursor: pointer;">
                            <td><?php echo number_format(++$cnt); ?>.</td>
                            <td><span data-code="<?php echo $swipe_memo_code_data["swipe_memo_code"]; ?>"><?php echo $swipe_memo_code_data["description"]; ?></span></td>
                            <td align="center">
                                <div id="p<?php echo $swipe_memo_code_data["swipe_memo_code"]; ?>">
                                    <?php if(@mysqli_num_rows($swipe_memo)){
                                        echo '<button class="trash" id="c'.$swipe_memo_code_data["swipe_memo_code"].'" style="padding: 2px 5px 2px 4px;" onclick="cancel_memo('.$swipe_memo_code_data["swipe_memo_code"].','.$pin.');"><ion-icon name="trash-outline"></ion-icon></button>';
                                    }else{
                                        echo '<button id="s'.$swipe_memo_code_data["swipe_memo_code"].'" style="padding: 2px 5px 2px 4px;" onclick="for_memo('.$swipe_memo_code_data["swipe_memo_code"].','.$pin.')"><ion-icon name="save-outline"></ion-icon></button>';
                                    } ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                    } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

mysqli_query($con, "DELETE FROM `_tmp_time`");
function gettime($start_time, $end_time, $log_type, $pin, $date){
    global $db, $db_hris;

    $time = $end_time - $start_time;
    $time_min = $time % 60;
    $time_hrs = ($time - $time_min) / 60;
    $timex =  substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
    
    $check = $db->prepare("SELECT * FROM $db_hris.`_tmp_time` WHERE `pin` LIKE '$pin' AND `log_value`='$log_type'");
    $check->execute();
    if ($check->rowCount()) {
        $update = $db->prepare("UPDATE `_tmp_time` SET `mins`=`mins`+:_time WHERE `pin` LIKE :pin AND `log_value`=:log_type AND `date`=:date");
        $update->execute(array(":_time" => $time, ":pin" => $pin, ":log_type" => $log_type, ":date" => $date));
    } else {
        $insert = $db->prepare("INSERT INTO `_tmp_time` (`pin`, `log_value`, `mins`, `date`) VALUES (:pin, :log_type, :_time, :date)");

        $insert->execute(array(":pin" => $pin, ":log_type" => $log_type, ":_time" => $time, ":date" => $date));
    }
    return $timex;
}

function delete_temp_time(){
    global $db, $db_hris;

    $del_time = $db->prepare("DELETE FROM $db_hris.`_tmp_time`");
    $del_time->execute();
}

function make_penalty($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
    $master->execute(array(':pin' => $pin));
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $emp_no = $master_data['employee_no'];

            $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
            $emp_deductions->execute(array(":ded_no" => $penalty_to, ":emp_no" => $emp_no));

            if ($emp_deductions->rowCount()){
                $update_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=`deduction_amount`+:ded_amt, `deduction_balance`=`deduction_balance`+:ded_bal, `user_id`=:uid, `station_id`=:ip WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
                $update_ded->execute(array(":emp_no" => $emp_no, ":ded_amt" => number_format($penalty_amt,2), ":ded_bal" => number_format($penalty_amt,2), ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $penalty_to));
                insert_ledger($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to,2);

                $time = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:code");
                $time->execute(array(":code" => $swipe_memo_code));
                if ($master->rowCount()) {
                    $time_data = $time->fetch(PDO::FETCH_ASSOC);
                    if($time_data['is_update_time'] == 1){
                        $time = 480;
                    }elseif($time_data['is_update_time'] == 2){
                        $time = 240;
                    }
                    update_emp_time($emp_no,$trans_date,$time);
                }
            }else{
                $new_ded = $db->prepare("INSERT INTO $db_hris.`employee_deduction`(`employee_no`, `deduction_no`, `deduction_amount`, `deduction_balance`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :ded_amt, :ded_bal, :uid, :ip)");
                $new_ded->execute(array(":emp_no" => $emp_no, ":ded_amt" => number_format($penalty_amt,2), ":ded_bal" => number_format($penalty_amt,2), ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $penalty_to));
                insert_ledger($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to,2);

                $time = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:code");
                $time->execute(array(":code" => $swipe_memo_code));
                if ($master->rowCount()) {
                    $time_data = $time->fetch(PDO::FETCH_ASSOC);
                    if($time_data['is_update_time'] == 1){
                        $time = 480;
                    }elseif($time_data['is_update_time'] == 2){
                        $time = 240;
                    }
                    update_emp_time($emp_no,$trans_date,$time);
                }
            }
        }
    }
}

function cancel_penalty($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
    $master->execute(array(':pin' => $pin));
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $emp_no = $master_data['employee_no'];

            $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
            $emp_deductions->execute(array(":ded_no" => $penalty_to, ":emp_no" => $emp_no));

            if ($emp_deductions->rowCount()){
                $update_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=`deduction_amount`-:ded_amt, `deduction_balance`=`deduction_balance`-:ded_bal, `user_id`=:uid, `station_id`=:ip WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
                $update_ded->execute(array(":emp_no" => $emp_no, ":ded_amt" => number_format($penalty_amt,2), ":ded_bal" => number_format($penalty_amt,2), ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $penalty_to));
            }
        }
    }
    insert_ledger($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to,1);
}

function insert_ledger($pin,$swipe_memo_code,$trans_date,$penalty_amt,$penalty_to,$opt){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `pin`=:pin");
    $master->execute(array(':pin' => $pin));
    if ($master->rowCount()) {
        $master_data = $master->fetch(PDO::FETCH_ASSOC);
        $emp_no = $master_data['employee_no'];

        $swipe_memo = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:code");
        $swipe_memo->execute(array(":code" => $swipe_memo_code));
        if($swipe_memo->rowCount()){
            $swipe_memo_data = $swipe_memo->fetch(PDO::FETCH_ASSOC);

            if($opt == 1){
                $remark = "Cancel ".$swipe_memo_data['description']." on ".$trans_date;
                $ref = "Swipe Memo";

                $emp_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger`(`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :ded_amt, :ded_bal, :remark, :ref, :uid, :ip)");
                $emp_ledger->execute(array(":emp_no" => $emp_no, ":ded_no" => $penalty_to, ":date" => date('Y-m-d'), ":ded_amt" => $penalty_amt, ":ded_bal" => $penalty_amt, ":remark" => $remark, ":ref" => $ref, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
            }else{
                $remark = $swipe_memo_data['description']." on ".$trans_date;
                $ref = "Swipe Memo";

                $emp_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger`(`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :ded_amt, :ded_bal, :remark, :ref, :uid, :ip)");
                $emp_ledger->execute(array(":emp_no" => $emp_no, ":ded_no" => $penalty_to, ":date" => date('Y-m-d'), ":ded_amt" => $penalty_amt, ":ded_bal" => $penalty_amt, ":remark" => $remark, ":ref" => $ref, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
            }
        }
    }
}

function update_emp_time($emp_no,$trans_date,$time){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:emp_no");
    $master->execute(array(':emp_no' => $emp_no));
    if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $pin = $master_data['pin'];

            $time_credited_check = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `employee_no`=:pin AND `trans_date`=:date");
            $time_credited_check->execute(array(":pin" => $pin, ":date" => $trans_date));
            if ($time_credited_check->rowCount()){
                
                $update_credit_time = $db->prepare("UPDATE $db_hris.`time_credit` SET `credit_time`=:ctime WHERE `employee_no`=:pin AND `trans_date`=:date");
                $update_credit_time->execute(array(":pin" => $pin, ":date" => $trans_date, ":ctime" => $time));
            }else{
                $new_credit_time = $db->prepare("INSERT INTO $db_hris.`time_credit` (`employee_no`, `trans_date`, `credit_time`) VALUES (:pin, :trans_date, :ctime)");
                $new_credit_time->execute(array(":pin" => $pin, ":trans_date" => $trans_date, ":ctime" => $time));

            }
        }
    }
}