<?php

function compute_payroll($employee_no, $payroll_date, $change) {
  include('../modules/system/system.config.php');

  global $db_hris;
  set_time_limit(300);
  $year = substr($payroll_date, 0, 4);
  $master_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `master_data` WHERE `employee_no`='$employee_no'"));
  $master_id_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `master_id` WHERE `employee_no`='$employee_no'"));
  if (number_format(substr($payroll_date, -2)) <= number_format(15))
    $schedule = "1";
  else
    $schedule = "2";
  mysqli_query($con,"DELETE FROM `payroll_trans_ded` WHERE `employee_no`='$employee_no' AND `payroll_date`='$payroll_date' AND (SELECT COUNT(*) FROM `deduction` WHERE `is_computed` AND `deduction`.`deduction_no`=`payroll_trans_ded`.`deduction_no` AND `schedule` LIKE '%$schedule%' AND !`is_inactive`)") or die(mysqli_error($con));
  $deduction = mysqli_query($con,"SELECT * FROM `deduction` WHERE `is_computed` AND `schedule` LIKE '%$schedule%' AND !`is_inactive`") or die(mysqli_error($con));
  $employee_todate = mysqli_query($con,"SELECT * FROM `employee_todate` WHERE `employee_no`='$employee_no'") or die(mysqli_error($con));
  if (@mysqli_num_rows($employee_todate)) {
    $employee_todate_data = mysqli_fetch_array($employee_todate);
  } else {
    $employee_todate_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `employee_todate` WHERE `employee_no`='$employee_no'"));
    mysqli_query($con,"INSERT INTO `employee_todate` (`employee_no`) VALUES ('$employee_no')") or die(mysqli_error($con));
    $employee_todate_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `employee_todate` WHERE `employee_no`='$employee_no'"));
  }
  $employee_trans_pay = mysqli_query($con,"SELECT * FROM `payroll_trans_pay` WHERE `employee_no`='$employee_no' AND `payroll_date`='$payroll_date'") or die(mysqli_error($con));
  $grosspay = $sss_prem = $pagibig_prem = $phil_prem = $tax_amount = 0;
  $grosspay_tax = $employee_todate_data["grosspay_tax"];
  $grosspay_sss = $employee_todate_data["grosspay_sss"];
  $grosspay_pagibig = $employee_todate_data["grosspay_pagibig"];
  $grosspay_phil = $employee_todate_data["grosspay_phil"];
  if (@mysqli_num_rows($employee_trans_pay))
    while ($employee_trans_pay_data = mysqli_fetch_array($employee_trans_pay)) {
      $payroll_type_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `payroll_type` WHERE `payroll_type_no`='$employee_trans_pay_data[payroll_type_no]'"));
      $grosspay += $employee_trans_pay_data["pay_amount"];
      if ($payroll_type_data["is_subject_to_tax"])
        $grosspay_tax += $employee_trans_pay_data["pay_amount"];
      if ($payroll_type_data["is_subject_to_sss"]) {
        $grosspay_sss += $employee_trans_pay_data["pay_amount"];
        $grosspay_pagibig += $employee_trans_pay_data["pay_amount"];
        $grosspay_phil += $employee_trans_pay_data["pay_amount"];
      }
    }
  if (number_format($grosspay, 2, '.', '') > number_format(0, 2)) {
    if (@mysqli_num_rows($deduction))
      while ($deduction_data = mysqli_fetch_array($deduction)) {
        if (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(7, 0, '.', '') AND $master_id_data["compute_sss"]) {
          $sss_prem = compute_sss($grosspay_sss, $employee_todate_data["sss_preme"]);
          if (number_format($sss_prem, 2, '.', '') > number_format(0, 2)) {
            mysqli_query($con,"INSERT INTO `payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`) VALUES ('$employee_no', '$payroll_date', '$deduction_data[deduction_no]', '$sss_prem', '$sss_prem')") or die(mysqli_error($con));
          }
        } elseif (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(107, 0, '.', '') AND $master_id_data["compute_pagibig"]) {
          $pagibig_prem = compute_pagibig($grosspay_sss, $employee_todate_data["pagibig_mtd"], $master_id_data["max_pagibig_prem"]);
          if (number_format($pagibig_prem, 2, '.', '') > number_format(0, 2)) {
            mysqli_query($con,"INSERT INTO `payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`) VALUES ('$employee_no', '$payroll_date', '$deduction_data[deduction_no]', '$pagibig_prem', '$pagibig_prem')") or die(mysqli_error($con));
          }
        } elseif (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(207, 0, '.', '') AND $master_id_data["compute_philhealth"]) {
          $phil_prem = compute_phil($grosspay_phil, $employee_todate_data["phil_preme"]);
          if (number_format($phil_prem, 2, '.', '') > number_format(0, 2)) {
            mysqli_query($con,"INSERT INTO `payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`) VALUES ('$employee_no', '$payroll_date', '$deduction_data[deduction_no]', '$phil_prem', '$phil_prem')") or die(mysqli_error($con));
          }
        } elseif (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(307, 0, '.', '') AND $master_id_data["compute_tax"] AND number_format($grosspay_tax, 2, '.', '') > number_format(0, 2)) {
          $tax_amount = compute_tax($grosspay_tax, $master_id_data["tax_code"]);
          if (number_format($tax_amount, 2, '.', '') > number_format(0, 2)) {
            mysqli_query($con,"INSERT INTO `payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`) VALUES ('$employee_no', '$payroll_date', '$deduction_data[deduction_no]', '$tax_amount', '$tax_amount')") or die(mysqli_error($con));
          }
        }
      }
    $deduction_amount_data = mysqli_fetch_array(mysqli_query($con,"SELECT SUM(`deduction_amount`) AS `deduction_amount` FROM `payroll_trans_ded` WHERE `employee_no`='$employee_no' AND `payroll_date`='$payroll_date'"));
    $total_deduction = number_format($deduction_amount_data["deduction_amount"], 2, '.', '');
    $actual_deduction = 0;
    if (number_format($total_deduction, 2, '.', '') > number_format($grosspay, 2, '.', '')) {
      $dist_amount = $grosspay;
      $payroll_trans_ded = mysqli_query($con,"SELECT * FROM `payroll_trans_ded`, $`deduction` WHERE `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` AND `employee_no`='$employee_no' AND `payroll_date`='$payroll_date' ORDER BY `deduction`.`priority`") or die(mysqli_error($con));
      if (@mysqli_num_rows($payroll_trans_ded))
        while ($payroll_trans_ded_data = mysqli_fetch_array($payroll_trans_ded)) {
          if (number_format($dist_amount, 2, '.', '') > number_format($payroll_trans_ded_data["deduction_amount"], 2, '.', '')) {
            $dist_amount -= $payroll_trans_ded_data["deduction_amount"];
            $actual_deduction += $payroll_trans_ded_data["deduction_amount"];
            mysqli_query($con,"UPDATE `payroll_trans_ded` SET `deduction_actual`=`deduction_amount` WHERE `employee_no`='$employee_no' AND `payroll_date`='$payroll_date' AND `deduction_no`='$payroll_trans_ded_data[deduction_no]'") or die(mysqli_error($con));
          } else {
            $actual_deduction += $dist_amount;
            mysqli_query($con,"UPDATE `payroll_trans_ded` SET `deduction_actual`='$dist_amount' WHERE `employee_no`='$employee_no' AND `payroll_date`='$payroll_date' AND `deduction_no`='$payroll_trans_ded_data[deduction_no]'") or die(mysqli_error($con));
            $dist_amount = 0;
          }
        }
    } else {
      $actual_deduction = $total_deduction;
      mysqli_query($con,"UPDATE `payroll_trans_ded` SET `deduction_actual`=`deduction_amount` WHERE `employee_no`='$employee_no' AND `payroll_date`='$payroll_date'") or die(mysqli_error($con));
    }
    $netpay = $grosspay - $actual_deduction;
    if (number_format($netpay, 2, '.', '') > number_format(0, 2) AND number_format($change, 2) > number_format(0, 2)) {
      $change_amount = substr(number_format($netpay, 2, '.', ''), -5);
      if (number_format($change_amount, 2, '.', '') > 0) {
        mysqli_query($con,"INSERT INTO `payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`, `deduction_actual`) VALUES ('$employee_no', '$payroll_date', '$change', '$change_amount', '$change_amount', '$change_amount')") or die(mysqli_error($con));
        $actual_deduction += $change_amount;
        $netpay = $grosspay - $actual_deduction;
      }
    }
    mysqli_query($con,"INSERT INTO `payroll_trans` (`employee_no`, `payroll_date`, `payroll_group_no`, `gross_pay`, `deduction`, `net_pay`, `grosspay_sss`, `grosspay_tax`, `grosspay_pagibig`, `grosspay_philhealth`) VALUES ('$employee_no', '$payroll_date', '$master_data[group_no]', '$grosspay', '$actual_deduction', '$netpay', '$grosspay_sss', '$grosspay_tax', '$grosspay_pagibig', '$grosspay_phil')") or die(mysqli_error($con));
  }
}

function compute_sss($grosspay, $preme) {
  include('../modules/system/system.config.php');
  $grosspay = number_format($grosspay, 2, '.', '');
  $table_sss = mysqli_query($con,"SELECT * FROM `table_sss` WHERE `pay_from`<=$grosspay AND `pay_to`>=$grosspay ORDER BY `bracket` LIMIT 1") or die(mysqli_error($con));
  if (@mysqli_num_rows($table_sss)) {
    $table_sss_data = mysqli_fetch_array($table_sss);
  } else {
    $table_sss_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `table_sss` ORDER BY `bracket` LIMIT 1"));
  }
  if (number_format($preme, 2, '.', '') >= number_format($table_sss_data["share_employee"], 2, '.', '')){
    $premium = 0;
  }else{
    $premium = $table_sss_data["share_employee"] - $preme;
  }
  return $premium/2;
}

function compute_pagibig($grosspay, $preme, $max_premium) {
  include('../modules/system/system.config.php');
  $premium = number_format($grosspay * 0.02, 2, '.', '');
  if (number_format($premium, 2, '.', '') > number_format($max_premium, 2, '.', '')){
    $premium = $max_premium;
  }if (number_format($preme, 2, '.', '') >= number_format($premium, 2, '.', '')){
    $premium = 0;
  }else{
    $premium -= $preme;
  }
  return $premium;
}

function compute_phil($grosspay, $preme) {
  include('../modules/system/system.config.php');
  /*   this is old version based on table
    $table_phil = mysqli_query($con,"SELECT * FROM $`table_phil_health` WHERE `pay_from`<=$grosspay AND `pay_to`>=$grosspay ORDER BY `bracket` LIMIT 1") or die(mysqli_error());
    if (@mysqli_num_rows($table_phil)) {
    $table_phil_data = mysqli_fetch_array($table_phil);
    } else {
    $table_phil_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `table_phil_health` ORDER BY `bracket` LIMIT 1"));
    }
    if (number_format($preme, 2, '.', '') >= number_format($table_phil_data["share_employee"], 2, '.', ''))
    $premium = 0;
    else
    $premium = $table_phil_data["share_employee"] - $preme;
   *
   */
  // current is based on minimum of  150 for 10k and below
  // for more than 10k 1.5% is computed
  // implemented on jan 2020
  if (number_format($grosspay, 2, '.', '') <= number_format(10000, 2, '.', '')) {
    $premium = number_format(200, 2, '.', '');
  } else {
    $premium = number_format($grosspay * 0.02, 2, '.', '');
  }
  return $premium;
  //$premium = number_format($grosspay * 0.04, 2, '.', '')/2;
  //return $premium;
}

function compute_tax($grosspay, $tax_code) {
  include('../modules/system/system.config.php');
  $tax_amount = 0;
  $tax_code = "01" . substr($tax_code + 10000, -2);
  $table_tax = mysqli_query($con,"SELECT * FROM `table_tax` WHERE `tax_code`='$tax_code' ORDER BY `table_no`") or die(mysqli_error($con));
  if (@mysqli_num_rows($table_tax))
    while ($table_tax_data = mysqli_fetch_array($table_tax)) {
      if (number_format($table_tax_data["taxable_amount_from"], 2, '.', '') <= number_format($grosspay, 2, '.', '') AND number_format($table_tax_data["taxable_amount_to"], 2, '.', '') >= number_format($grosspay, 2, '.', '')) {
        $tax_amount = number_format($table_tax_data["fixed_amount"] + ($grosspay - $table_tax_data["taxable_amount_from"]) * $table_tax_data["percent_amount"] / 100, 2, '.', '');
        break;
      }
    }
  return $tax_amount;
}