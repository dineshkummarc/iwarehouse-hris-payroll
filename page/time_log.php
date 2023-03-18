<?php
error_reporting(0);
$program_code = 3;

require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();

switch ($_POST["cmd"]) {
    case "get-group":
        get_group();
    break;
    case "plot":
        $data = get_attendance($_POST["df"], $_POST["dt"]);
        echo json_encode(array("status" => "success", "records" => $data["records"], "columns" => $data["columns"]));
    break;
    case "get-data":
        $store = $_POST["_store"];
        $group_name = $_POST["_group"];

        $data = '';

        $payroll_group =  mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name`='$group_name'");
        if (@mysqli_num_rows($payroll_group)) {
            $payroll_group_data =  mysqli_fetch_array($payroll_group);
            $log_cutoff = $payroll_group_data["cutoff_date"];
            $payroll_cutoff = $payroll_group_data["payroll_date"];

                $data .= '<table class="w3-table-all w3-small w3-border">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th class="w3-border">PIN</th>
                                    <th class="w3-border">NAME</th>';
                $date = $log_cutoff;
                while($date <= $payroll_cutoff){ 
                    $holiday =  mysqli_query($con,"SELECT * FROM `holiday` WHERE `holiday_date`='$date'");
                    if(@mysqli_num_rows($holiday)){
                        $holiday_data =  mysqli_fetch_array($holiday);
                        if($holiday_data["is_special"]){
                            $color = "w3-yellow"; 
                        }else{
                            $color = "w3-red w3-text-white";
                        }
                    }else{
                        $color = "";
                    }
                    $data .= '<th class="w3-border '.$color.'" style="text-align: center;">'.substr($cfn->datefromdb($date), 0,5).'</th>';
                    $date = date('Y-m-d', mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2) + 1, substr($date, 0, 4)));
                }
                $data .= '<th class="w3-border" style="text-align: center;">TOTAL HRS</th>
                        </tr>
                    </thead>
                    <tbody>';
                $cnt=0;
                $master =  mysqli_query($con,"SELECT * FROM `master_data` WHERE !`is_inactive` AND `group_no`='$payroll_group_data[group_name]' AND  `master_data`.`store`='$store' ORDER BY `family_name`, `given_name`, `middle_name`");
                if(@mysqli_num_rows($master)){
                    while($master_data=  mysqli_fetch_array($master)){ 
                        $employee_rate=  mysqli_query($con,"SELECT * FROM `employee_rate` WHERE `employee_no`='$master_data[employee_no]'") or die(mysqli_error($con));
                            if(@mysqli_num_rows($employee_rate))$employee_rate_data =  mysqli_fetch_array ($employee_rate);
                        $total=0;
                
                    $data .= '<tr class="time">
                                <td class="w3-border">'.number_format(++$cnt).'.</td>
                                <td class="w3-border" style="text-align: center;">'.$master_data["pin"].'</td>
                                <td class="w3-border">'.$master_data["family_name"].', '.$master_data["given_name"].' '.  substr($master_data["middle_name"], 0,1).'</td>';
                $date = $log_cutoff;
                while($date<=$payroll_cutoff){
                    $credit =  mysqli_query($con,"SELECT * FROM `time_credit` WHERE `trans_date`='$date' AND `employee_no`='$master_data[pin]' AND !`isDOD`") or die(mysqli_error($con));
                    $jo_credit =  mysqli_query($con,"SELECT * FROM `time_credit` WHERE `trans_date`='$date' AND `employee_no`='$master_data[pin]' AND `isDOD`") or die(mysqli_error($con));
                    $evl =  mysqli_query($con,"SELECT * FROM `employee_vl` WHERE `vl_date`='$date' AND `employee_no`='$master_data[employee_no]'") or die(mysqli_error($con));
                    $time_log =  mysqli_query($con, "SELECT * FROM `attendance_log` WHERE `pin`='$master_data[pin]' AND `log_date`='$date'") or die(mysqli_error($con));
                    
                    $data .= '<td align="center" class="w3-border" style="text-align: center;"><span>';
                                if(!@mysqli_num_rows($employee_rate) OR @mysqli_num_rows($employee_rate) AND number_format($employee_rate_data["daily_rate"],2) ==  number_format(0,2)){
                                    $data .= 'No Rate';
                                }else{ 
                                    if(@mysqli_num_rows($credit)){
                                        $time_credit_data =  mysqli_fetch_array($credit);
                                        if($time_credit_data["credit_time"]>0){
                                            $time_mins = $time_credit_data['credit_time'];

                                            $time_min = $time_credit_data["credit_time"] % 60;
                                            $time_hrs = ($time_mins - $time_min) / 60;
                                            $credit_time = substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
                                            $data .= $credit_time;
                                            $total+=$time_credit_data["credit_time"];
                                        }else{
                                            $data .= "-0-";
                                        }
                                    }elseif(@mysqli_num_rows($jo_credit)){
                                        $jo_credit_data =  mysqli_fetch_array($jo_credit);
                                        if($jo_credit_data["credit_time"]>0){
                                            $time_mins = $jo_credit_data['credit_time'];

                                            $time_min = $jo_credit_data["credit_time"] % 60;
                                            $time_hrs = ($time_mins - $time_min) / 60;
                                            $credit_time = substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
                                            $data .= $credit_time;
                                            $total+=$jo_credit_data["credit_time"];
                                        }else{
                                            $data .= "-0-";
                                        }
                                    }elseif(@mysqli_num_rows($evl)){
                                        $evl_data =  mysqli_fetch_array($evl);
                                        if(!$evl_data["is_cancelled"]){
                                            $data .= "-VL-";
                                        }else{
                                            $data .= "-0-";
                                        }
                                    }elseif(@mysqli_num_rows($time_log)){
                                        $data .= "-A-";
                                    }else{
                                        $data .= "-0-";
                                    }
                                }
                        $data .= '</span></td>';
                        $date=date('Y-m-d', mktime(0, 0, 0, substr($date, 5, 2), substr($date, 8, 2) + 1, substr($date, 0, 4)));
                            
                }
                $data .= '<td class="w3-border" style="text-align: right;"><span><b>';
                            if($total>0){
                                $time_mins = $total;
                                $time_min = $total % 60;
                                $time_hrs = ($time_mins - $time_min) / 60;
                                $credit_time = $time_hrs . ":" . substr(number_format($time_min + 100), 1, 2);
                                $data .= $credit_time;
                            }else{
                                $data .= "-0-";
                            }
                $data .= '</b></span>
                        </td>
                    </tr>';
                }
                $data .= '</tbody>
                            </table>';
            }
            
        }else{
            $data .= '<script>w2alert("No Data Found");</script>';
        }
        echo $data;
        break;
    case "generate-time-credit":
        $date = (new DateTime($_POST["date"]))->format("Y-m-d");
        $store = $_POST["_store"];
        $group = $_POST["_group"];
        credit_emp_time($group,$store,$date);
        //credited_time();
        break;
    case "check-log":
        $date = date("Y-m-d");
        check_log_time($date);
    break;
    case "post-shift":
        include("../function/post_shift.php");
        $current_date = (new DateTime($_POST["trans_date"]))->format("Y-m-d");
        post_shift($current_date);
    break;


}

function save_late($group,$store,$date){
    global $db, $db_hris;

    $emp_late = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `store`=:store AND `group_no`=:grp ORDER BY `family_name` ASC");
    $emp_late->execute(array(":grp" => $group, ":store" => $store));
    if ($emp_late->rowCount()) {
        while ($emp_late_data = $emp_late->fetch(PDO::FETCH_ASSOC)) {
            $late[] = array("pin" => $emp_late_data["pin"], "trn_date" => $date); //store into array
        }
        foreach($late as $val => $emp_data){
            $epin = $emp_data['pin'];
            $trans_date = $emp_data['trn_date'];

            $att_log = $db->prepare("SELECT * FROM `attendance_log` WHERE `pin`=:pin AND `log_date`=:trans_date AND `log_type`=:no ORDER BY `log_date`,`log_time` LIMIT 1");
            $att_log->execute(array(":pin" => $epin, ":trans_date" => $trans_date, ":no" => 0));
            if ($att_log->rowCount()) {
                while ($att_log_data = $att_log->fetch(PDO::FETCH_ASSOC)){
                    $time_in = $att_log_data['log_time'];
                    $emp_pin = $att_log_data['pin'];
                    $t_date = $trans_date;
                }
                $master = $db->prepare("SELECT * FROM `master_data` WHERE `pin`=:pin");
                $master->execute(array(":pin" => $emp_pin));
                if ($master->rowCount()) {
                    while ($master_data = $master->fetch(PDO::FETCH_ASSOC)){
                        $eno = $master_data['employee_no'];
                    }
                    $emp_sched = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `trans_date`=:sdate");
                    $emp_sched->execute(array(":eno" => $eno, ":sdate" => $t_date));
                    if ($emp_sched->rowCount()) {
                        $sched_data = $emp_sched->fetch(PDO::FETCH_ASSOC);
                        $shift_code = $sched_data["shift_code"];

                        $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:code AND !`is_off_duty`");
                        $shift->execute(array(":code" => $shift_code));
                        if ($shift->rowCount()) {
                            while ($shift_data = $shift->fetch(PDO::FETCH_ASSOC)) {
                                if($shift_data['is_open']){
                                    $time = '24:00:00';
                                }else{
                                    $time = $shift_data["past_late"].':59';
                                }
                            }
                            $time_start = new DateTime($trans_date.' '.$time_in);
                            $time_sched = new DateTime($trans_date.' '.$time);
                            $interval = $time_start->diff($time_sched);
                            $mins = $interval->format('%h').":".$interval->format('%i');

                            if($time_in > $time){
                                $exist = $db->prepare("SELECT * FROM $db_hris.`employee_late` WHERE `employee_no`=:eno AND `trans_date`=:date");
                                $exist->execute(array(":eno" => $eno, ":date" => $t_date));
                                if ($exist->rowCount()) {
                                    $update_late = $db->prepare("UPDATE $db_hris.`employee_late` SET `mins_late`=:mins, `start_time`=:start, `log_time`=:log_time, `isLate`=:isLate WHERE `employee_no`=:eid AND `trans_date`=:tdate");
                                    $update_late->execute(array(":eid" => $eno, ":tdate" => $t_date, ":mins" => $mins, ":start" => $time, ":log_time" => $time_in, ":isLate" => 1));
                                }else{
                                    $insert_late = $db->prepare("INSERT INTO $db_hris.`employee_late`(`employee_no`, `trans_date`, `mins_late`, `start_time`, `log_time`, `isLate`) VALUES (:eid, :tdate, :mins, :start, :log_time, :isLate)");
                                    $insert_late->execute(array(":eid" => $eno, ":tdate" => $t_date, ":mins" => $mins, ":start" => $time, ":log_time" => $time_in, ":isLate" => 1));
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

function save_absent($group,$store,$date){
    global $con;

    
    $master = mysqli_query($con,"SELECT * FROM `master_data` WHERE !`is_inactive` AND `store`='$store' AND `group_no`='$group'");
    if (@mysqli_num_rows($master)) {
        while($master_data = mysqli_fetch_array($master)){
            mysqli_query($con, "DELETE FROM `employee_absent` WHERE `employee_absent`.`employee_no`='$master_data[employee_no]' AND `employee_absent`.`absent_date`='$date'") or die(mysqli_error($con));
            $att = mysqli_query($con,"SELECT * FROM `attendance_log` WHERE `pin`='$master_data[pin]' AND `log_date`='$date'");
            if (!@mysqli_num_rows($att)) {
                $sched = mysqli_query($con,"SELECT * FROM `employee_work_schedule` WHERE `employee_no`='$master_data[employee_no]' AND `shift_code`!=0 AND `trans_date`='$date'");
                if (@mysqli_num_rows($sched)) {
                    while($sched_data = mysqli_fetch_array($sched)){
                        $shift = mysqli_query($con,"SELECT * FROM `shift` WHERE `shift_code`='$sched_data[shift_code]' AND `is_off_duty`");
                        if(@mysqli_num_rows($shift)){
                            mysqli_query($con,"INSERT INTO `employee_absent`(`employee_no`, `absent_date`, `is_absent`) VALUES ('$master_data[employee_no]','$date', '0')");
                        }else{
                            mysqli_query($con,"INSERT INTO `employee_absent`(`employee_no`, `absent_date`, `is_absent`) VALUES ('$master_data[employee_no]','$date', '1')");
                        }
                    }
                }
            }
        }
    }
}

function check_log_time($date){

    global $db, $db_hris;
    
    $check_log = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `trans_date`=:date");
    $check_log->execute(array(":date" => $date));
    if ($check_log->rowCount()) {
        echo "success";
    }else{
        echo "No Generated Time for ".$date;
    }
}

function remove_trans($group,$store,$date){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `store`=:store AND `group_no`=:grp AND !`is_inactive`");
    $master->execute(array(":grp" => $group, ":store" => $store));
    if($master->rowCount()){
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){

            $del = $db->prepare("DELETE FROM $db_hris.`time_credit` WHERE `trans_date`=:date AND `employee_no`=:eno");
            $del->execute(array(":eno" => $master_data['pin'], ":date" => $date));
            
            $del_ot = $db->prepare("DELETE FROM $db_hris.`time_credit_ot` WHERE `trans_date`=:date AND `employee_no`=:eno");
            $del_ot->execute(array(":eno" => $master_data['pin'], ":date" => $date));
        }
    }
    
}

function credit_emp_time($group,$store,$date){
    global $db, $db_hris;

    save_absent($group,$store,$date);
    save_late($group,$store,$date);
    remove_trans($group,$store,$date);

    $_temp_time = $db->prepare("SELECT * FROM $db_hris.`_tmp_time`,$db_hris.`master_data` WHERE !`master_data`.`is_inactive` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:grp AND `_tmp_time`.`pin`=`master_data`.`pin` AND `_tmp_time`.`date`=:date ORDER BY `master_data`.`family_name` ASC");
    $_temp_time->execute(array(":store" => $store, ":grp" => $group, ":date" => $date));
    if ($_temp_time->rowCount()) {
        while ($_temp_time_data = $_temp_time->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array("pin" => $_temp_time_data["pin"], "trn_date" => $date); //store into array
        }
        foreach($data as $val => $emp_data){
            $emp_pin = $emp_data['pin'];
            $trans_date = $emp_data['trn_date'];

            $tmp_time = $db->prepare("SELECT * FROM `_tmp_time` WHERE `pin`=:pin AND `date`=:trans_date AND `log_value`=:no LIMIT 1");
            $tmp_time->execute(array(":pin" => $emp_pin, ":trans_date" => $trans_date, ":no" => 0));
            if ($tmp_time->rowCount()) {
                while ($tmp_log_data = $tmp_time->fetch(PDO::FETCH_ASSOC)){
                    $emp_pin = $tmp_log_data['pin'];
                    $t_date = $trans_date;
                }
                $master = $db->prepare("SELECT * FROM `master_data` WHERE `pin`=:pin");
                $master->execute(array(":pin" => $emp_pin));
                if ($master->rowCount()) {
                    while ($master_data = $master->fetch(PDO::FETCH_ASSOC)){
                        $emp_no = $master_data['employee_no'];
                    }
                    $emp_sched = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `trans_date`=:sdate");
                    $emp_sched->execute(array(":eno" => $emp_no, ":sdate" => $t_date));
                    if ($emp_sched->rowCount()) {
                        $sched_data = $emp_sched->fetch(PDO::FETCH_ASSOC);
                        $shift_code = $sched_data["shift_code"];

                        $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:code");
                        $shift->execute(array(":code" => $shift_code));
                        if ($shift->rowCount()){
                            $shift_data = $shift->fetch(PDO::FETCH_ASSOC);
                            if ($shift_data["is_off_duty"]){
                                //credit_job_order($emp_no,$t_date);
                                credited_time($emp_no,$t_date,'1');
                            }elseif($shift_data["is_open"]){
                                credited_time($emp_no,$t_date,'0');
                            }else{
                                credited_time($emp_no,$t_date,'0');
                            }
                        }
                    }
                }
            }
        }
    }
}

function credited_time($emp_no,$t_date,$option){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
    $master->execute(array(":no" => $emp_no));
    if($master->rowCount()){
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $emp_pin = $master_data['pin'];
        }
        $time_credited = $db->prepare("SELECT * FROM $db_hris.`_tmp_time` WHERE `pin`=:pin AND `Date`=:date AND `log_value`=:no");
        $time_credited->execute(array(":no" => 0, ":pin" => $emp_pin, ":date" => $t_date));
        if ($time_credited->rowCount()) {
            while ($time_credited_data = $time_credited->fetch(PDO::FETCH_ASSOC)) {
                $time_pin = $time_credited_data['pin'];
                $time_mins = $time_credited_data['mins'];
                $time_date= $time_credited_data['date'];

                if($time_mins >= 480){
                    $new_credit_time = $db->prepare("INSERT INTO $db_hris.`time_credit` (`employee_no`, `trans_date`, `credit_time`, `isDOD`) VALUES (:pin, :trans_date, :ctime, :dod)");
                    $new_credit_time->execute(array(":pin" => $time_pin, ":trans_date" => $time_date, ":ctime" => 480, ":dod" => $option));
                }else{
                    $whole_time = $time_mins/60;
                    $final_time = floor($whole_time) * 60;
                    
                    $new_credit_time = $db->prepare("INSERT INTO $db_hris.`time_credit` (`employee_no`, `trans_date`, `credit_time`, `isDOD`) VALUES (:pin, :trans_date, :ctime, :dod)");
                    $new_credit_time->execute(array(":pin" => $time_pin, ":trans_date" => $time_date, ":ctime" => $final_time, ":dod" => $option));
                }
                credit_overtime($time_pin,$time_date);
            }
        }
    }
}

function credit_job_order($emp_no,$t_date){
    global $db, $db_hris;

    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
    $master->execute(array(":no" => $emp_no));
    if($master->rowCount()){
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
            $emp_pin = $master_data['pin'];
        }
        $time_credited = $db->prepare("SELECT * FROM $db_hris.`_tmp_time` WHERE `pin`=:pin AND `Date`=:date AND `log_value`=:no");
        $time_credited->execute(array(":no" => 0, ":pin" => $emp_pin, ":date" => $t_date));
        if ($time_credited->rowCount()) {
            while ($time_credited_data = $time_credited->fetch(PDO::FETCH_ASSOC)) {
                $time_pin = $time_credited_data['pin'];
                $time_mins = $time_credited_data['mins'];
                $time_date= $time_credited_data['date'];

                if($time_mins >= 480){
                    $new_job_order = $db->prepare("INSERT INTO $db_hris.`time_credit` (`employee_no`, `trans_date`, `credit_time`, `isDOD`) VALUES (:pin, :trans_date, :ctime, :dod)");
                    $new_job_order->execute(array(":pin" => $time_pin, ":trans_date" => $time_date, ":ctime" => 480, ":dod" => 1));
                }else{
                    $whole_time = $time_mins/60;
                    $final_time = floor($whole_time) * 60;
                    
                    $new_job_order = $db->prepare("INSERT INTO $db_hris.`time_credit` (`employee_no`, `trans_date`, `credit_time`, `isDOD`) VALUES (:pin, :trans_date, :ctime, :dod)");
                    $new_job_order->execute(array(":pin" => $time_pin, ":trans_date" => $time_date, ":ctime" => $final_time, ":dod" => 1));
                }
                credit_overtime($time_pin,$time_date);
            }
        }
    }
}

function credit_overtime($time_pin,$time_date){

    global $db, $db_hris;

    $time_credited = $db->prepare("SELECT * FROM $db_hris.`_tmp_time` WHERE `pin`=:pin AND `Date`=:date AND `log_value`=:no");
    $time_credited->execute(array(":no" => 0, ":pin" => $time_pin, ":date" => $time_date));
    if ($time_credited->rowCount()) {
        while ($time_credited_data = $time_credited->fetch(PDO::FETCH_ASSOC)) {
            $emp_no = $time_credited_data['pin'];
            $time = $time_credited_data['mins'];
            $trans_date = $time_credited_data['date'];
            $ot_time1 = $time-480;

            $time_min=$ot_time1 % 60;
            $time_hrs=($ot_time1 - $time_min) / 60;
            $ot_time = substr(number_format($time_hrs+100,0), 1,2).".".substr(number_format($time_min+100), 1,2);
            
            if($ot_time1 >= 60){
                $new_credit_ot = $db->prepare("INSERT INTO $db_hris.`time_credit_ot` (`employee_no`, `trans_date`, `credit_time`) VALUES (:pin, :trans_date, :ctime)");
                $new_credit_ot->execute(array(":pin" => $emp_no, ":trans_date" => $trans_date, ":ctime" => $ot_time));
            }
        }
        delete_temp_time($emp_no,$trans_date);
    }
    
}

function delete_temp_time($emp_no,$trans_date){
    global $db, $db_hris;

    $del_time = $db->prepare("DELETE FROM $db_hris.`_tmp_time` WHERE `pin`=:pin AND `date`=:date");
    $del_time->execute(array(":pin" => $emp_no, ":date" => $trans_date));
    if($del_time->rowCount()){
        echo "success";
    }else{
        echo "error";
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