<?php
function sync_deduction($employee_no, $deduction_no){
  include('../modules/system/system.config.php');

  $deduction_data=  mysqli_fetch_array(mysqli_query($con, "SELECT * FROM `deduction` WHERE `deduction_no`='$deduction_no'"));
  if($deduction_data["deduction_type"] AND $deduction_no){
    $user_id = $_SESSION['session_name'];
    $station_id = $_SERVER['REMOTE_ADDR'];
    $trans_date=date("Y-m-d");
    $deduction_transaction=  mysqli_query($con, "SELECT * FROM `deduction_transaction` WHERE `employee_no`='$employee_no' AND `deduction_no`='$deduction_no' AND !`is_paid`") or die(mysqli_error($con));
    if(@mysqli_num_rows($deduction_transaction))
      $deduction_transaction_data =  mysqli_fetch_array(mysqli_query($con, "SELECT COUNT(*) AS `count`, SUM(`balance`) AS `balance`, SUM(`payroll_deduction`) AS `payroll_deduction` FROM `deduction_transaction` WHERE `employee_no`='$employee_no' AND `deduction_no`='$deduction_no' AND !`is_paid`"));
      $employee_deduction=  mysqli_query($con, "SELECT * FROM `employee_deduction` WHERE `employee_no`='$employee_no' AND `deduction_no`='$deduction_no'") or die(mysqli_error($con));
      if(@mysqli_num_rows($employee_deduction)){
        $employee_deduction_data=  mysqli_fetch_array($employee_deduction);
        if(number_format($employee_deduction_data["deduction_balance"],2,'.','')!=  number_format($deduction_transaction_data["balance"],2,'.','')){
          $adjustment=$deduction_transaction_data["balance"]-$employee_deduction_data["deduction_balance"];
          mysqli_query($con, "INSERT INTO `employee_deduction_ledger` (`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `user_id`, `station_id`) VALUES ('$employee_no', '$deduction_no', '$trans_date', '$adjustment', '$deduction_transaction_data[balance]', 'transaction made from a/r system account', '$user_id', '$station_id')") or die(mysqli_error($con));
        }
        mysqli_query($con, "UPDATE `employee_deduction` SET `deduction_balance`='$deduction_transaction_data[balance]', `deduction_amount`='$deduction_transaction_data[payroll_deduction]' WHERE `employee_no`='$employee_no' AND `deduction_no`='$deduction_no'") or die(mysqli_error($con));
      }else{
        if(number_format($deduction_transaction_data["balance"],2,'.','')!=  number_format(0,2)){
          mysqli_query($con, "INSERT INTO `employee_deduction` (`employee_no`, `deduction_no`, `deduction_balance`, `deduction_amount`) VALUES ('$employee_no', '$deduction_no', '$deduction_transaction_data[balance]', '$deduction_transaction_data[payroll_deduction]')") or die(mysqli_error($con));
          mysqli_query($con, "INSERT INTO `employee_deduction_ledger` (`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `user_id`, `station_id`) VALUES ('$employee_no', '$deduction_no', '$trans_date', '$deduction_transaction_data[balance]', '$deduction_transaction_data[balance]', 'transaction made from a/r system account', '$user_id', '$station_id')") or die(mysqli_error($con));
        }
      }
      mysqli_query($con, "UPDATE `employee_deduction` SET `deduction_amount`=`deduction_balance` WHERE `employee_no`='$employee_no' AND `deduction_balance`<`deduction_amount` OR `deduction_amount<=0");
  }
}
?>
