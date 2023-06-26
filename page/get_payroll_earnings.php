<?php

$program_code = 14;
require_once('../common/functions.php');
include("../function/compute_earnings.php");
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
$group_name = $_GET["_group"];
$store = $_GET["_store"];
$payroll_group= mysqli_query($con, "SELECT * FROM `payroll_group` WHERE `group_name`='$group_name'");
if(@mysqli_num_rows($payroll_group)){
  $payroll_group_data= mysqli_fetch_array($payroll_group);
  $log_cutoff = $payroll_group_data["cutoff_date"];
  $payroll_cutoff = $payroll_group_data["payroll_date"];
  $master_query= "SELECT * FROM `master_data` WHERE !`is_inactive` AND `store`='$store' AND (SELECT COUNT(*) FROM `master_id` WHERE `master_id`.`pin_no`=`master_data`.`pin` AND `pay_group`='$payroll_group_data[group_name]') AND (SELECT COUNT(*) FROM `time_credit` WHERE `time_credit`.`employee_no`=`master_data`.`pin` AND `trans_date`>='$log_cutoff' AND `trans_date`<='$payroll_cutoff' LIMIT 1) ORDER BY `family_name`, `given_name`, `middle_name`";
  $payroll_trans= mysqli_query($con, "SELECT * FROM `payroll_trans` WHERE `payroll_date`='$payroll_cutoff' AND (SELECT COUNT(*) FROM `master_id` WHERE `master_id`.`pin_no`=`payroll_trans`.`employee_no` AND `pay_group`='$payroll_group_data[group_name]') AND `is_posted` LIMIT 1") or die(mysqli_error($con));
  $master= mysqli_query($con, $master_query) or die(mysqli_error($con));
  if(@mysqli_num_rows($master)){ 
    if(!@mysqli_num_rows($payroll_trans)){
      while($master_data= mysqli_fetch_array($master)){
        compute_earnings($master_data["employee_no"], $log_cutoff, $payroll_cutoff);
      }
      $master= mysqli_query($con, $master_query) or die(mysqli_error($con));
    }
  ?>
<div class="w3-panel w3-round-medium w3-border w3-padding-bottom">
<span class="w3-padding"><b>Date: <?php echo date("F j".", "."Y"); ?></b></span></br>
<span class="w3-padding"><b>Period: <?php $from = date("F j",strtotime($log_cutoff)); $to = date("F j".", "."Y",strtotime($payroll_cutoff)); echo $from." - ".$to; ?></b></span>
<table class="w3-table-all w3-small w3-padding">
  <thead>
    <tr>
      <th class="w3-border"></th>
      <th class="w3-border">ID No.</th>
      <th class="w3-border">NAME</th>
      <?php $payroll_type= mysqli_query($con, "SELECT * FROM `payroll_type` WHERE (SELECT COUNT(*) FROM `payroll_trans_pay`,`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_name' AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`='$payroll_cutoff' LIMIT 1) ORDER BY `payroll_type_no`") or die(mysqli_error($con)); 
      if(@mysqli_num_rows($payroll_type))
        while($payroll_type_data= mysqli_fetch_array($payroll_type)){ ?>
      <th colspan="2" class="w3-border" style="text-align: center;"><?php echo $payroll_type_data["pay_type"]; ?></th>
  <?php } ?>
      <th class="w3-border">TOTAL GROSS PAY</th>
    </tr>
  </thead>
  <tbody>
<?php
    $cnt=0;
    while($master_data= mysqli_fetch_array($master)){ 
      $total=0;
      ?>
    <tr class="earnings">
      <td class="w3-border"><?php echo number_format(++$cnt); ?>.</td>
      <td class="w3-border"><?php echo $master_data["pin"]; ?></td>
      <td class="w3-border"><?php echo $master_data["family_name"].", ".$master_data["given_name"]." ".  substr($master_data["middle_name"], 0,1); ?></td>
      <?php $payroll_type= mysqli_query($con, "SELECT * FROM `payroll_type` WHERE (SELECT COUNT(*) FROM `payroll_trans_pay`,`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_name' AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`='$payroll_cutoff' LIMIT 1) ORDER BY `payroll_type_no`") or die(mysqli_error($con)); 
      if(@mysqli_num_rows($payroll_type))
        while($payroll_type_data= mysqli_fetch_array($payroll_type)){ 
        $payroll_trans_pay= mysqli_query($con, "SELECT * FROM `payroll_trans_pay`,`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_name' AND  `payroll_trans_pay`.`employee_no`='$master_data[employee_no]' AND `payroll_trans_pay`.`payroll_date`='$payroll_cutoff' AND `payroll_trans_pay`.`payroll_type_no`='$payroll_type_data[payroll_type_no]'") or die(mysqli_error($con));
      if(@mysqli_num_rows($payroll_trans_pay)){
        $payroll_trans_pay_data= mysqli_fetch_array($payroll_trans_pay);
        $total+=$payroll_trans_pay_data["pay_amount"];
      }
        ?>
      <td class="w3-border" style="text-align: right;"><?php if(@mysqli_num_rows($payroll_trans_pay)){ echo number_format($payroll_trans_pay_data["credit"],2); } ?></td>
      <td style="text-align: right;" class="w3-border"><?php if(@mysqli_num_rows($payroll_trans_pay)){ echo number_format($payroll_trans_pay_data["pay_amount"],2); } ?></td>
  <?php } ?>
      <td style="text-align: right;" class="w3-border"><?php echo number_format($total,2); ?></td>
    </tr>
<?php      
    } ?>
  </tbody>
  <tfoot>
    <tr>
      <th colspan="3" style="text-align: right;" class="w3-border">TOTAL</th>
      <?php $payroll_type= mysqli_query($con, "SELECT * FROM `payroll_type` WHERE (SELECT COUNT(*) FROM `payroll_trans_pay`,`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_name' AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`='$payroll_cutoff' LIMIT 1) ORDER BY `payroll_type_no`") or die(mysqli_error($con)); 
      $total=0;
      if(@mysqli_num_rows($payroll_type))
        while($payroll_type_data= mysqli_fetch_array($payroll_type)){ ?>
      <th style="text-align: right;" class="w3-border"><?php
      $total_pay_data= mysqli_fetch_array(mysqli_query($con, "SELECT SUM(`pay_amount`) AS `pay_amount`, SUM(`credit`) AS `credit` FROM `payroll_trans_pay`, `master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`group_no`='$group_name' AND `master_data`.`store`='$store' AND `payroll_trans_pay`.`payroll_group_no`='0' AND `payroll_date`='$payroll_cutoff' AND `payroll_trans_pay`.`payroll_type_no`='$payroll_type_data[payroll_type_no]'"));
      echo number_format($total_pay_data["credit"],2);
      $total+=$total_pay_data["pay_amount"];
      ?></th>
      <th style="text-align: right;" class="w3-border"><?php echo number_format($total_pay_data["pay_amount"],2); ?></th>
  <?php } ?>
      <th style="text-align: right;" class="w3-border"><?php echo number_format($total,2); ?></th>
    </tr>
  </tfoot>
</table>
</div>
<?php
  }
}
?>
