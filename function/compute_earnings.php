<?php


function compute_earnings($pin, $date_from, $date_to, $payroll_date = "0000-00-00") {

  include('../modules/system/system.config.php');

  set_time_limit(300);
  if ($payroll_date == "0000-00-00") {
    $payroll_date = $date_to;
  }
  mysqli_query($con, "DELETE FROM `payroll_trans_pay` WHERE `employee_no`='$pin' AND `payroll_date` LIKE '$payroll_date'") or die(mysqli_error($con));
  $employee_rate = mysqli_query($con, "SELECT * FROM `master_data`,`employee_rate` WHERE `master_data`.`employee_no`=`employee_rate`.`employee_no` AND `employee_rate`.`employee_no`='$pin'") or die(mysqli_error($con));

  if (@mysqli_num_rows($employee_rate)) {
    $employee_rate_data = mysqli_fetch_assoc($employee_rate);
    $pin_no = $employee_rate_data['pin'];

    mysqli_query($con,"INSERT INTO `payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) SELECT `employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount` FROM `payroll_adjustment` WHERE `employee_no`='$pin' AND `payroll_date` LIKE '$payroll_date'") or die(mysqli_error($con));

    //regular time
    $time_credit_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(`credit_time`) AS `mins_credit` FROM `time_credit` WHERE `employee_no`='$pin_no' AND `trans_date`>='$date_from' AND `trans_date`<='$date_to' AND !`isDOD`"));
    $time_credit_total = number_format($time_credit_data["mins_credit"] / 60, 2, '.', '');
    $total_time = number_format($time_credit_total + 0, 2, '.', '');
    $payroll_type_reg_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM `payroll_type` WHERE `pay_type` LIKE 'BASIC PAY'"));
    if (number_format($total_time, 2, '.', '') != number_format(0, 2)) {
      $pay_amount = number_format($total_time * $employee_rate_data["daily_rate"] / 8, 2, '.', '');

      mysqli_query($con, "INSERT INTO `payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES ('$pin', '$payroll_date', '$payroll_type_reg_data[payroll_type_no]', '$total_time', '$pay_amount')") or die(mysqli_error($con));
      if (number_format($employee_rate_data["incentive_cash"], 2, '.', '') > number_format(0, 2)) {
        $payroll_type_inc_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM .`payroll_type` WHERE `pay_type` LIKE 'INCENTIVE'"));
        $pay_amount = number_format($total_time * $employee_rate_data["incentive_cash"] / 8, 2, '.', '');

        mysqli_query($con, "INSERT INTO `payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES ('$pin', '$payroll_date', '$payroll_type_inc_data[payroll_type_no]', '$total_time', '$pay_amount')") or die(mysqli_error($con));
      }
    }

    //overtime credit
    $ot_credit = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(`credit_time`) AS `mins_credit` FROM `time_credit_ot` WHERE `employee_no`='$pin_no' AND `trans_date`>='$date_from' AND `trans_date`<='$date_to' AND `is_approved`"));
    $ot_credit_total = number_format($ot_credit["mins_credit"], 2, '.', '');
    $ot_time_total = number_format($ot_credit_total + 0, 2, '.', '');
    $payroll_type_reg_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM `payroll_type` WHERE `pay_type` LIKE 'REG OT'"));
    if (number_format($ot_time_total, 2, '.', '') != number_format(0, 2)) {
      $pay_amount = number_format($ot_time_total * $employee_rate_data["daily_rate"] / 8, 2, '.', '');
      
      $check = mysqli_query($con, "SELECT * FROM `payroll_trans_pay` WHERE `payroll_type_no`='$payroll_type_reg_data[payroll_type_no]' AND `employee_no`='$pin' AND `payroll_date`='$payroll_date'");
      if (@mysqli_num_rows($check)) {
        mysqli_query($con, "UPDATE `payroll_trans_pay` SET `credit`=`credit`+'$ot_time_total', `pay_amount`=`pay_amount`+'$pay_amount' WHERE `employee_no`='$pin' AND `payroll_date`='$payroll_date' AND `payroll_type_no`= '$payroll_type_reg_data[payroll_type_no]'") or die(mysqli_error($con));
      }else{
        mysqli_query($con, "INSERT INTO `payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES ('$pin', '$payroll_date', '$payroll_type_reg_data[payroll_type_no]', '$ot_time_total', '$pay_amount')") or die(mysqli_error($con));
      }
      
    }

    //employee duty on day off
    $emp_jo = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(`credit_time`) AS `mins_credit` FROM `time_credit` WHERE `employee_no`='$pin_no' AND `trans_date`>='$date_from' AND `trans_date`<='$date_to' AND `isDOD`"));
    $emp_jo_total = number_format($emp_jo["mins_credit"] / 60, 2, '.', '');
    $jo_time_total = number_format($emp_jo_total + 0, 2, '.', '');
    $payroll_type_reg_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM `payroll_type` WHERE `pay_type` LIKE 'JOB ORDER'"));
    if (number_format($jo_time_total, 2, '.', '') != number_format(0, 2)) {
      $new_rate = $employee_rate_data["daily_rate"] + $employee_rate_data["incentive_cash"];
      $pay_amount = number_format($jo_time_total * $new_rate / 8, 2, '.', '');
      
      $check = mysqli_query($con, "SELECT * FROM `payroll_trans_pay` WHERE `payroll_type_no`='$payroll_type_reg_data[payroll_type_no]' AND `employee_no`='$pin' AND `payroll_date`='$payroll_date'");
      if (@mysqli_num_rows($check)) {
        mysqli_query($con, "UPDATE `payroll_trans_pay` SET `credit`=`credit`+'$jo_time_total', `pay_amount`=`pay_amount`+'$pay_amount' WHERE `employee_no`='$pin' AND `payroll_date`='$payroll_date' AND `payroll_type_no`= '$payroll_type_reg_data[payroll_type_no]'") or die(mysqli_error($con));
      }else{
        mysqli_query($con, "INSERT INTO `payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES ('$pin', '$payroll_date', '$payroll_type_reg_data[payroll_type_no]', '$jo_time_total', '$pay_amount')") or die(mysqli_error($con));
      }
      
    }

    //vacation leave
    $credit = 8;
    $employee_vl = mysqli_query($con, "SELECT * FROM `employee_vl` WHERE `employee_no`='$pin' AND `vl_date`>='$date_from' AND `vl_date`<='$date_to' AND !`is_cancelled`");
    if (@mysqli_num_rows($employee_vl)) {
      $payroll_type_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM .`payroll_type` WHERE `pay_type` LIKE 'VACATION'"));
      while ($employee_vl_data = mysqli_fetch_assoc($employee_vl)) {
        if (!@mysqli_num_rows(mysqli_query($con, "SELECT * FROM .`time_credit` WHERE `employee_no`='$pin' AND `trans_date`='$employee_vl_data[vl_date]' AND `mins_credit`>0"))) {
          $new_rate = $employee_rate_data["daily_rate"] + $employee_rate_data["incentive_cash"];
          $pay_amount = number_format($credit * $new_rate / 8, 2, '.', '');
          if (@mysqli_num_rows(mysqli_query($con, "SELECT * FROM .`payroll_trans_pay` WHERE `employee_no`='$pin' AND `payroll_type_no`='$payroll_type_data[payroll_type_no]' AND `payroll_date`='$date_to'"))) {
            mysqli_query($con,"UPDATE `payroll_trans_pay` SET `credit`=`credit`+$credit, `pay_amount`=`pay_amount`+$pay_amount WHERE `employee_no`='$pin' AND `payroll_type_no`='$payroll_type_data[payroll_type_no]' AND `payroll_date` LIKE '$payroll_date'") or die(mysqli_error($con));
          } else {
            mysqli_query($con,"INSERT INTO .`payroll_trans_pay` (`employee_no`, `payroll_type_no`, `payroll_date`, `credit`, `pay_amount`) VALUES ('$pin', '$payroll_type_data[payroll_type_no]', '$payroll_date', '$credit', '$pay_amount')");
          }
        }
      }
    }
     //holiday
    $hired = date_create($employee_rate_data['date_hired']);
    $interval = date_diff($hired,date_create($date_to));
    $mo = $interval->format('%m');
    if($mo >= 1){
      $holiday = mysqli_query($con, "SELECT * FROM `holiday` WHERE `holiday_date`>='$date_from' AND `holiday_date`<='$date_to' AND !(SELECT COUNT(*) FROM `employee_vl` WHERE `employee_no`='$pin' AND `vl_date`=`holiday`.`holiday_date` AND !`is_cancelled` AND !`is_served`)");
      if (@mysqli_num_rows($holiday)) {
        while ($holiday_data = mysqli_fetch_assoc($holiday)) {
          $time_credit = mysqli_query($con, "SELECT * FROM `time_credit` WHERE `employee_no`='$pin_no' AND `trans_date` LIKE '$holiday_data[holiday_date]' AND `credit_time`>0");
          if (@mysqli_num_rows($time_credit)) {
            $time_credit_data = mysqli_fetch_assoc($time_credit);
            $credit = number_format($time_credit_data["credit_time"] / 60, 2, '.', '');
          } else {
            $credit = 0;
          }
          if ($holiday_data["is_special"]) {
            if (@mysqli_num_rows($time_credit)) {
              $payroll_type_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM `payroll_type` WHERE `pay_type` LIKE 'SH PREM'"));
              $pay_amount = number_format($credit * $employee_rate_data["daily_rate"] / 8 * $payroll_type_data["factor_amount"], 2, '.', '');
              if (@mysqli_num_rows(mysqli_query($con, "SELECT * FROM `payroll_trans_pay` WHERE `employee_no`='$pin' AND `payroll_type_no`='$payroll_type_data[payroll_type_no]' AND `payroll_date` LIKE '$payroll_date'"))) {
                mysqli_query($con, "UPDATE `payroll_trans_pay` SET `credit`=`credit`+$credit, `pay_amount`=`pay_amount`+$pay_amount WHERE `employee_no`='$pin' AND `payroll_type_no`='$payroll_type_data[payroll_type_no]' AND `payroll_date` LIKE '$payroll_date'") or die(mysqli_error($con));
              } else {
                mysqli_query($con, "INSERT INTO `payroll_trans_pay` (`employee_no`, `payroll_type_no`, `payroll_date`, `credit`, `pay_amount`) VALUES ('$pin', '$payroll_type_data[payroll_type_no]', '$payroll_date', '$credit', '$pay_amount')") or die(mysqli_error($con));
              }
            }
          } else {
            $credit = 8;
            $payroll_type_data = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM `payroll_type` WHERE `pay_type` LIKE 'HOL. PREM'"));
            $pay_amount = number_format($employee_rate_data["daily_rate"] * $payroll_type_data["factor_amount"], 2, '.', '');
            if (@mysqli_num_rows(mysqli_query($con, "SELECT * FROM .`payroll_trans_pay` WHERE `employee_no`='$pin' AND `payroll_type_no`='$payroll_type_data[payroll_type_no]' AND `payroll_date` LIKE '$payroll_date'"))) {
              mysqli_query($con,"UPDATE `payroll_trans_pay` SET `credit`=`credit`+$credit, `pay_amount`=`pay_amount`+$pay_amount WHERE `employee_no`='$pin' AND `payroll_type_no`='$payroll_type_data[payroll_type_no]' AND `payroll_date` LIKE '$payroll_date'") or die(mysqli_error($con));
            } else {
              mysqli_query($con,"INSERT INTO `payroll_trans_pay` (`employee_no`, `payroll_type_no`, `payroll_date`, `credit`, `pay_amount`) VALUES ('$pin', '$payroll_type_data[payroll_type_no]', '$payroll_date', '$credit', '$pay_amount')") or die(mysqli_error($con));
            }
          }
        }
      }
    }
  }
}
