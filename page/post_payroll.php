<?php

$program_code = 5;
include('../common/functions.php');

include("../common_function.class.php");
$cfn = new common_functions();

include("../function/sysconfig.php");

$payroll_days = sysconfig("payroll_days");
$user_id = $session_name;
$station_id = $_SERVER['REMOTE_ADDR'];
$group_no = $_GET["_group"];
$store = $_GET["_store"];
$payroll_group = mysqli_query($con, "SELECT * FROM `payroll_group` WHERE `group_name` LIKE '$group_no'");
if (@mysqli_num_rows($payroll_group)) {
    $payroll_group_data = mysqli_fetch_array($payroll_group);
    $log_cutoff = $payroll_group_data["cutoff_date"];
    $payroll_date = $payroll_group_data["payroll_date"];
    if (number_format(substr($payroll_date, -2)) <= number_format(15))
        $schedule = "1";
    else
        $schedule = "2";
    set_time_limit(300);
    $master_query = "SELECT * FROM `master_data`, `master_id` WHERE !`is_inactive` AND `master_id`.`employee_no`=`master_data`.`employee_no` AND `pay_group`='$payroll_group_data[group_name]' AND ((SELECT COUNT(*) FROM `time_credit` WHERE `time_credit`.`employee_no`=`master_data`.`pin` AND `trans_date`>='$log_cutoff' AND `trans_date`<='$payroll_date' LIMIT 1))";
    $some_update = 0;
    $master = mysqli_query($con,$master_query) or die(mysqli_error($con));
    if (@mysqli_num_rows($master)) {
        while ($master_data = mysqli_fetch_array($master)) {
            set_time_limit(300);
            $employee_no = $master_data["employee_no"];

            $payroll_trans = mysqli_query($con, "SELECT * FROM `payroll_trans` WHERE `payroll_date`='$payroll_date' AND `employee_no`='$employee_no' AND !`is_posted` LIMIT 1") or die(mysqli_error($con));
            if (@mysqli_num_rows($payroll_trans)) {
                $payroll_trans_data = mysqli_fetch_array($payroll_trans);
                post_payroll_time($employee_no, $log_cutoff, $payroll_date);
                post_payroll_pay($employee_no, $payroll_date, $schedule);
                post_deduction($employee_no, $payroll_date);

                mysqli_query($con, "UPDATE `payroll_trans` SET `is_posted`='1', `posted_by`='$user_id', `posted_at`='$station_id', `posted_time`=NOW()  WHERE `payroll_date`='$payroll_date' AND `employee_no`='$master_data[employee_no]'") or die(mysqli_error($con));
                $some_update++;
            }
        }
        if ($some_update) {
            $new_log_cutoff = date('Y-m-d', mktime(0, 0, 0, substr($payroll_date, 5, 2), substr($payroll_date, 8, 2) + 1, substr($payroll_date, 0, 4)));
            if (substr($payroll_date, -2) == substr($payroll_days, 0, 2))
                $new_payroll_date = date('Y-m-d', mktime(0, 0, 0, substr($payroll_date, 5, 2), substr($payroll_days, -2), substr($payroll_date, 0, 4)));
            else
                $new_payroll_date = date('Y-m-d', mktime(0, 0, 0, substr($payroll_date, 5, 2) + 1, substr($payroll_days, 0, 2), substr($payroll_date, 0, 4)));
            mysqli_query($con, "UPDATE `payroll_group` SET `cutoff_date`='$new_log_cutoff', `payroll_date`='$new_payroll_date' WHERE `group_name`='$payroll_group_data[group_name]'") or die(mysqli_error($con));
        }
    }
}

function post_deduction($employee_no, $payroll_date) {
    include('../modules/system/system.config.php');

    $trans_date = date("Y-m-d");
    $reference = "PY" . $payroll_date;
    $user_id = $_SESSION['name'];
    $station_id = $_SERVER['REMOTE_ADDR'];
    $payroll_trans_ded = mysqli_query($con, "SELECT * FROM `payroll_trans_ded`, `deduction` WHERE `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` AND `payroll_trans_ded`.`payroll_date`='$payroll_date' AND `payroll_trans_ded`.`employee_no`='$employee_no'") or die(mysqli_error($con));
    if (@mysqli_num_rows($payroll_trans_ded)) {
        while ($payroll_trans_ded_data = mysqli_fetch_array($payroll_trans_ded)) {
            if ($payroll_trans_ded_data["is_computed"] AND $payroll_trans_ded_data["schedule"] == "1,2") {
                if (number_format($payroll_trans_ded_data["deduction_no"], 0) == number_format(7, 0)) {
                    mysqli_query($con, "UPDATE `employee_todate` SET `sss_preme`='$payroll_trans_ded_data[deduction_amount]' WHERE `employee_no`='$payroll_trans_ded_data[employee_no]'") or die(mysqli_error($con));
                } elseif (number_format($payroll_trans_ded_data["deduction_no"], 0) == number_format(107, 0)) {
                    mysqli_query($con, "UPDATE `employee_todate` SET `pagibig_mtd`='$payroll_trans_ded_data[deduction_amount]' WHERE `employee_no`='$payroll_trans_ded_data[employee_no]'") or die(mysqli_error($con));
                } elseif (number_format($payroll_trans_ded_data["deduction_no"], 0) == number_format(207, 0)) {
                    mysqli_query($con, "UPDATE `employee_todate` SET `phil_preme`='$payroll_trans_ded_data[deduction_amount]' WHERE `employee_no`='$payroll_trans_ded_data[employee_no]'") or die(mysqli_error($con));
                }
            } elseif (!$payroll_trans_ded_data["is_computed"]) {
                $deduction_transaction = mysqli_query($con, "SELECT * FROM `deduction_transaction` WHERE `employee_no`='$employee_no' AND `deduction_no`='$payroll_trans_ded_data[deduction_no]'") or die(mysqli_error($con));
                $employee_deduction = mysqli_query($con, "SELECT * FROM `employee_deduction` WHERE `employee_no`='$employee_no' AND `deduction_no`='$payroll_trans_ded_data[deduction_no]'") or die(mysqli_error($con));
                $amount = 0 - $payroll_trans_ded_data["deduction_amount"];
                if (@mysqli_num_rows($employee_deduction)) {
                    $employee_deduction_data = mysqli_fetch_array($employee_deduction) or die(mysqli_error($con));
                    $balance = $employee_deduction_data["deduction_balance"];
                } else {
                    $balance = 0;
                }
                if (@mysqli_num_rows($deduction_transaction)) {
                    while ($deduction_transaction_data = mysqli_fetch_array($deduction_transaction)) {
                        $amount = 0 - $deduction_transaction_data["payroll_deduction"];

                        $reference = "payroll";
                        $balance+=$amount;
                        mysqli_query($con, "UPDATE `employee_deduction` SET `deduction_balance`='$balance' WHERE `employee_no`='$employee_no' AND `deduction_no`='$payroll_trans_ded_data[deduction_no]'") or die(mysqli_error($con));
                        mysqli_query($con, "INSERT INTO `employee_deduction_ledger` (`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES ('$employee_no', '$payroll_trans_ded_data[deduction_no]', '$trans_date', '$amount', '$balance', 'Taken from payroll ending $payroll_date', '$reference', '$user_id', '$station_id')") or die(mysqli_error($con));
                    }
                }else {
                    $balance+=$amount;
                    mysqli_query($con, "UPDATE `employee_deduction` SET `deduction_balance`='$balance' WHERE `employee_no`='$employee_no' AND `deduction_no`='$payroll_trans_ded_data[deduction_no]'") or die(mysqli_error($con));
                    mysqli_query($con, "INSERT INTO `employee_deduction_ledger` (`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES ('$employee_no', '$payroll_trans_ded_data[deduction_no]', '$trans_date', '$amount', '$balance', 'Taken from payroll ending $payroll_date', 'payroll', '$user_id', '$station_id')") or die(mysqli_error($con));
                }
            }
        }
    }
    mysqli_query($con, "UPDATE `employee_deduction` SET `deduction_amount`='0' WHERE `employee_no`='$employee_no' AND `deduction_balance`<=0") or die(mysqli_error($con));
}

function post_payroll_time($employee_no, $log_cutoff, $payroll_date) {
    include('../modules/system/system.config.php');
    $user_id = $_SESSION['name'];
    $station_id = $_SERVER['REMOTE_ADDR'];

    set_time_limit(600);
    mysqli_query($con, "DELETE FROM `payroll_trans_time` WHERE `payroll_date` LIKE '$payroll_date' AND `employee_no`='$employee_no'");
    mysqli_query($con, "INSERT INTO `payroll_trans_time` (`payroll_date`, `trans_date`, `employee_no`, `mins_credit`, `user_id`, `station_id`, `is_manual`) SELECT '$payroll_date', `time_credit`.`trans_date`, `master_data`.`employee_no`, `time_credit`.`credit_time`, '$user_id', '$station_id', '0' FROM `time_credit`,`master_data` WHERE `time_credit`.`trans_date`>='$log_cutoff' AND `time_credit`.`trans_date`<='$payroll_date' AND `master_data`.`employee_no`='$employee_no'") or die(mysqli_error($con));
    mysqli_query($con, "INSERT INTO `payroll_trans_time` (`payroll_date`, `trans_date`, `employee_no`, `mins_credit`, `user_id`, `station_id`, `is_manual`) SELECT '$payroll_date', `time_credit_ot`.`trans_date`, `master_data`.`employee_no`, `time_credit_ot`.`credit_time`, '$user_id', '$station_id', '0' FROM `time_credit_ot`,`master_data` WHERE `time_credit_ot`.`trans_date`>='$log_cutoff' AND `time_credit_ot`.`trans_date`<='$payroll_date' AND `time_credit_ot`.`is_approved` AND `master_data`.`employee_no`='$employee_no'") or die(mysqli_error($con));
    mysqli_query($con, "DELETE FROM `time_credit_ot` WHERE `trans_date`>='$log_cutoff' AND `time_credit_ot`.`trans_date`<='$payroll_date' AND !`is_approved`") or die(mysqli_error($con));
}

function post_payroll_pay($employee_no, $payroll_date, $schedule) {
    include('../modules/system/system.config.php');

    $payroll_trans_pay_query = "SELECT * FROM `payroll_trans_pay`, `payroll_type` WHERE `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `employee_no`='$employee_no' AND `payroll_trans_pay`.`payroll_date`='$payroll_date'";
    $grosspay_sss = $grosspay_tax = $grosspay_pagibig = $grosspay_phil = $vl_days = 0;
    $payroll_trans_pay = mysqli_query($con,$payroll_trans_pay_query) or die(mysqli_error($con));
    if (@mysqli_num_rows($payroll_trans_pay)) {
        while ($payroll_trans_pay_data = mysqli_fetch_array($payroll_trans_pay)) {
            $deduction = mysqli_query($con, "SELECT * FROM `deduction` WHERE `is_computed`") or die(mysqli_error($con));
            if (@mysqli_num_rows($deduction)) {
                while ($deduction_data = mysqli_fetch_array($deduction)) {
                    if (number_format($deduction_data["deduction_no"], 0) == number_format(7, 0) AND $payroll_trans_pay_data["is_subject_to_sss"]) {
                        $grosspay_sss+=$payroll_trans_pay_data["pay_amount"];
                    }
                    if (number_format($deduction_data["deduction_no"], 0) == number_format(107, 0) AND $payroll_trans_pay_data["is_subject_to_sss"]) {
                        if (!(substr($deduction_data["schedule"], 0, 1) == $schedule OR substr($deduction_data["schedule"], -1) == $schedule)) {
                            $grosspay_pagibig+=$payroll_trans_pay_data["pay_amount"];
                        }
                    }
                    if (number_format($deduction_data["deduction_no"], 0) == number_format(207, 0) AND $payroll_trans_pay_data["is_subject_to_sss"]) {
                        if (!(substr($deduction_data["schedule"], 0, 1) == $schedule OR substr($deduction_data["schedule"], -1) == $schedule)) {
                            $grosspay_phil+=$payroll_trans_pay_data["pay_amount"];
                        }
                    }
                    if (number_format($deduction_data["deduction_no"], 0) == number_format(307, 0) AND $payroll_trans_pay_data["is_subject_to_tax"]) {
                        if (!(substr($deduction_data["schedule"], 0, 1) == $schedule OR substr($deduction_data["schedule"], -1) == $schedule)) {
                            $grosspay_tax+=$payroll_trans_pay_data["pay_amount"];
                        }
                    }
                }
            }
            if (number_format($payroll_trans_pay_data["payroll_type_no"], 0) == number_format(507, 0)) {
                $vl_days+=$payroll_trans_pay_data["credit"] / 8;
            }
        }
    }
    mysqli_query($con, "UPDATE `employee_todate` SET `grosspay_sss`='$grosspay_sss', `grosspay_tax`='$grosspay_tax', `grosspay_pagibig`='$grosspay_pagibig', `grosspay_phil`='$grosspay_phil', `vl_days`=`vl_days`+$vl_days WHERE `employee_no`='$employee_no'") or die(mysqli_error($con));
}

?>
