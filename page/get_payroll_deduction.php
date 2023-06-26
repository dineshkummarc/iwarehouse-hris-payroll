<?php
$program_code = 15;
require_once('../common/functions.php');
include("../common_function.class.php");
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
include("../function/sync_deduction.php");

set_time_limit(300);
$group_name = $_GET["_group"];
$store = $_GET["_store"];
$payroll_group = mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name` LIKE '$group_name'");
if (@mysqli_num_rows($payroll_group)) {
    $payroll_group_data = mysqli_fetch_array($payroll_group);
    $log_cutoff = $payroll_group_data["cutoff_date"];
    $payroll_cutoff = $payroll_group_data["payroll_date"];
    if (number_format(substr($payroll_cutoff, -2)) <= number_format(15))
        $schedule = "1";
    else
        $schedule = "2";
    $deduction_query = "SELECT * FROM `deduction` WHERE !`is_computed` AND !`is_inactive` AND `schedule` LIKE '%$schedule%' AND (SELECT COUNT(*) FROM `employee_deduction` WHERE `employee_deduction`.`deduction_no`=`deduction`.`deduction_no` LIMIT 1) ORDER BY `deduction_label`";
    $payroll_trans = mysqli_query($con,"SELECT * FROM `payroll_trans` WHERE `payroll_date`='$payroll_cutoff' AND (SELECT COUNT(*) FROM `master_id`,`master_data` WHERE `master_id`.`employee_no`=`payroll_trans`.`employee_no` AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `pay_group`='$payroll_group_data[group_name]') AND !`is_posted` LIMIT 1");
    if (@mysqli_num_rows($payroll_trans)) {
        mysqli_query($con,"DELETE FROM `payroll_trans_ded` WHERE `payroll_date`='$payroll_cutoff' AND (SELECT COUNT(*) FROM `master_id`,`master_data` WHERE `master_id`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `payroll_trans_ded`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `pay_group`='$payroll_group_data[group_name]')") or die(mysqli_error($con));
        $master = mysqli_query($con,"SELECT * FROM `master_data` WHERE !`is_inactive` AND (SELECT COUNT(*) FROM `master_id` WHERE `master_id`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `pay_group`='$payroll_group_data[group_name]') ORDER BY `family_name`, `given_name`, `middle_name`");
        if (@mysqli_num_rows($master)) {
            while ($master_data = mysqli_fetch_array($master)) {
                set_time_limit(60);
                $deduction = mysqli_query($con,$deduction_query) or die(mysqli_error($con));
                if (@mysqli_num_rows($deduction)) {
                    while ($deduction_data = mysqli_fetch_array($deduction)) {
                        sync_deduction($master_data["employee_no"], $deduction_data["deduction_no"]);
                        $employee_deduction = mysqli_query($con,"SELECT * FROM `employee_deduction` WHERE `employee_no`='$master_data[employee_no]' AND `deduction_no`='$deduction_data[deduction_no]' AND `deduction_balance`>0");
                        if (@mysqli_num_rows($employee_deduction)) {
                            $employee_deduction_data = mysqli_fetch_array($employee_deduction);
                            if (number_format($employee_deduction_data["deduction_balance"], 2, '.', '') < number_format($employee_deduction_data["deduction_amount"], 2, '.', '')){
                                $deduction_amount = $employee_deduction_data["deduction_balance"];
                            }
                            else{
                                $deduction_amount = $employee_deduction_data["deduction_amount"];
                            }
                            mysqli_query($con,"INSERT INTO `payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`) VALUES ('$master_data[employee_no]', '$payroll_cutoff', '$deduction_data[deduction_no]', '$deduction_amount', '$employee_deduction_data[deduction_balance]')") or die(mysqli_error($con));
                        }
                    }
                }
            }
        }
    }
    echo "success";
} else {
    echo "Invalid payroll group!";
}
?>
