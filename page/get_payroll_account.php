<?php

$program_code = 16;
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

$group_no = $_GET["_group"];
$store = $_GET["_store"];
$payroll_date=$cfn->datefromtable($_GET["_date"]);
$payroll_group = mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name` LIKE '$group_no'");
if (@mysqli_num_rows($payroll_group)) {
  set_time_limit(300);
  $payroll_group_data =mysqli_fetch_array($payroll_group);
  $log_cutoff = $payroll_group_data["cutoff_date"];
  $payroll_cutoff = $payroll_group_data["payroll_date"];
  $payroll_type_query= "SELECT * FROM `payroll_type` WHERE (SELECT COUNT(*) FROM `payroll_trans_pay`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_no' AND `payroll_date`='$payroll_date' AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` LIMIT 1) ORDER BY `payroll_type_no`";
  $deduction_query="SELECT * FROM `deduction` WHERE (SELECT COUNT(*) FROM `payroll_trans_ded`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group_no' AND `payroll_trans_ded`.`payroll_date`='$payroll_date' AND `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` LIMIT 1) ORDER BY `deduction_no`";
  $payroll_trans= mysqli_query($con, "SELECT * FROM `payroll_trans`, `master_data` WHERE `payroll_trans`.`payroll_date`='$payroll_date' AND `payroll_trans`.`payroll_group_no`='$payroll_group_data[group_name]' AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' AND `master_data`.`group_no`=`payroll_trans`.`payroll_group_no` ORDER BY `family_name`, `given_name`, `middle_name`");
  if(@mysqli_num_rows($payroll_trans)){ 
    $payroll_type= mysqli_query($con,$payroll_type_query);
    $deduction= mysqli_query($con,$deduction_query);
    ?>
<span class="w3-padding"><b>Date: <?php echo date("F j".", "."Y"); ?></b></span>
<span class="w3-padding w3-right w3-small">
  <?php if (substr($access_rights, 8, 2) === "P+") { ?>
    <button class="w3-button w3-silver w3-border w3-round-medium w3-hover-silver" onclick="exportExcel()">Export Excel</button>
  <?php } ?></span></br>
<span class="w3-padding"><b>Payroll Period: <?php $from = date("F j",strtotime($log_cutoff)); $to = date("F j".", "."Y",strtotime($payroll_cutoff)); echo $from." - ".$to; ?></b></span>
<table class="w3-table-all w3-small">
  <thead>
    <tr>
      <th></th>
      <th></th>
      <th></th>
      <th style="width: 80px;"></th>
      <th colspan="<?php echo @mysqli_num_rows($payroll_type)+1; ?>" class="w3-center w3-border">PAYROLL EARNINGS</th>
      <th colspan="<?php echo @mysqli_num_rows($deduction)+1; ?>" class="w3-center w3-border">PAYROLL DEDUCTION</th>
      <th></th>
    </tr>
    <tr>
      <th></th>
      <th class="w3-center w3-border">PIN</th>
      <th class="w3-center w3-border">NAME</th>
      <th>No. of Days</th>
      <?php
      while($payroll_type_data= mysqli_fetch_array($payroll_type)){ ?>
      <th class="w3-center w3-border"><?php echo $payroll_type_data["pay_type"]; ?></th>
<?php        
      }
      ?>
      <th class="w3-center w3-border">GROSS PAY</th>
      <?php 
      while($deduction_data= mysqli_fetch_array($deduction)){ ?>
      <th class="w3-center w3-border"><?php echo $deduction_data["deduction_label"]; ?></th>
<?php        
      }
      ?>
      <th class="w3-center w3-border">TOTAL DED</th>
      <th class="w3-center w3-border">NET PAY</th>
    </tr>
  </thead>
  <tbody>
<?php
    $cnt=0;
    while($payroll_trans_data= mysqli_fetch_array($payroll_trans)){ ?>
    <tr class="register">
      <td><?php echo number_format(++$cnt); ?></td>
      <td class="w3-center w3-border"><?php echo $payroll_trans_data["pin"]; ?></td>
      <td><?php echo $payroll_trans_data["family_name"] . ", " . $payroll_trans_data["given_name"] . " " . substr($payroll_trans_data["middle_name"], 0, 1); ?></td>
      <?php
      $payroll_trans_pay1 =  mysqli_query($con, "SELECT * FROM `payroll_trans_pay` WHERE `payroll_trans_pay`.`employee_no`='$payroll_trans_data[employee_no]' AND `payroll_trans_pay`.`payroll_date`='$payroll_date' ORDER BY `payroll_trans_pay`.`payroll_type_no`"); ?>
      <td class="w3-border" style="text-align: right; width: auto;">
        <?php if(@mysqli_num_rows($payroll_trans_pay1)){
          $payroll_trans_pay_data1= mysqli_fetch_array($payroll_trans_pay1);
          if (number_format($payroll_trans_pay_data1["credit"],2) > number_format(8,2)) {

            $days = floor($payroll_trans_pay_data1["credit"] / 8);
            $remainingHours = $payroll_trans_pay_data1["credit"] % 8;

            $no_days = $remainingHours === "" ? $days : $days.".".$remainingHours;
          }else{
            $no_days = $payroll_trans_pay_data1["credit"]." Hrs";
          }
          echo $no_days;
        }
        ?>
      </td>
      <?php
      $total = 0;
      $payroll_type= mysqli_query($con,$payroll_type_query);
      while($payroll_type_data= mysqli_fetch_array($payroll_type)){ 
        $payroll_trans_pay= mysqli_query($con,"SELECT * FROM `payroll_trans_pay`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`='$store' AND `payroll_trans_pay`.`payroll_date`='$payroll_date' AND `payroll_trans_pay`.`employee_no`='$payroll_trans_data[employee_no]' AND `payroll_trans_pay`.`payroll_type_no`='$payroll_type_data[payroll_type_no]'");
        ?>
      <td class="w3-border" style="text-align: right;"><?php if(@mysqli_num_rows($payroll_trans_pay)){
        $payroll_trans_pay_data= mysqli_fetch_array($payroll_trans_pay);
        echo number_format($payroll_trans_pay_data["pay_amount"],2);
                }
        ?></td>
<?php        
      }
      ?>
      <td class="w3-border" style="text-align: right;"><?php echo number_format($payroll_trans_data["gross_pay"],2); ?></td>
      <?php 
      $deduction= mysqli_query($con,$deduction_query);
      while($deduction_data= mysqli_fetch_array($deduction)){ ?>
      <td class="w3-border" style="text-align: right;"><?php
      $payroll_trans_ded= mysqli_query($con,"SELECT * FROM `payroll_trans_ded`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`='$store' AND `payroll_trans_ded`.`employee_no`='$payroll_trans_data[employee_no]' AND `payroll_trans_ded`.`payroll_date`='$payroll_date' AND `payroll_trans_ded`.`deduction_no`='$deduction_data[deduction_no]'");
      if(@mysqli_num_rows($payroll_trans_ded)){
        $payroll_trans_ded_data= mysqli_fetch_array($payroll_trans_ded);
        $total +=number_format($payroll_trans_ded_data["deduction_actual"], 2, ".", "");
        echo number_format($payroll_trans_ded_data["deduction_actual"],2);
      }
      ?></td>
<?php        
      }
      ?>
      <td class="w3-border" style="text-align: right;"><?php echo number_format($payroll_trans_data["deduction"],2); ?></td>
      <td class="w3-border" style="text-align: right;"><?php echo number_format($payroll_trans_data["net_pay"],2); ?></td>
    </tr>
<?php    
    }
    $payroll_trans_data = mysqli_fetch_array(mysqli_query($con,"SELECT SUM(`gross_pay`) AS `gross_pay`, SUM(`deduction`) AS `deduction`, SUM(`net_pay`) AS `net_pay` FROM `payroll_trans` WHERE `payroll_date`='$payroll_date' AND (SELECT COUNT(*) FROM `master_id`,`master_data` WHERE `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`='$store' AND `master_id`.`employee_no`=`payroll_trans`.`employee_no` AND `pay_group`='$payroll_group_data[group_name]')")); ?>
  </tbody>
  <tfoot>
      <tr>
        <th colspan="4"  style="text-align: right;" class="w3-border w3-text-blue">GRAND TOTAL</th>
        <th style="text-align: right;" class="w3-border w3-text-blue" colspan="<?php echo @mysqli_num_rows($payroll_type)+1; ?>"><?php echo number_format($payroll_trans_data["gross_pay"], 2); ?></th>
        <?php
          $total_deduction = $payroll_trans_data["deduction"];
          $col_count = @mysqli_num_rows($deduction)+1;
        ?>
        <th style="text-align: right;" class="w3-border w3-text-blue" colspan="<?php echo $col_count; ?>"><?php echo number_format($total_deduction, 2); ?></th>
        <th style="text-align: right;" class="w3-border w3-text-blue"><?php echo number_format($payroll_trans_data["net_pay"], 2); ?></th>
      </tr>
    </tfoot>
</table>
<?php
  }
}
?>
