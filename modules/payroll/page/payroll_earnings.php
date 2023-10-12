<?php
$program_code = 14;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
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
if (isset($_REQUEST["cmd"])) {
  try {
    if ($db->beginTransaction()) {
      switch ($_REQUEST["cmd"]) {
        case "extract-earnings": //ok
          if ($access_rights === "A+E+D+B+P+") {
            get_earnings(array("store" => $_POST["store"], "group" => $_POST["group"]));
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

function get_columns_group($record){
  global $db, $db_hris;

  $items = array();
  $items[] = array("span" => 2, "caption" => "");
  $payroll_type= $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`=:cutt_off LIMIT 1) ORDER BY `payroll_type_no`");
  $payroll_type->execute(array(":store" => $record["store"], ":group" => $record["group_no"], ":cutt_off" => $record["payroll_date"]));
  if($payroll_type->rowCount()){
    while($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)){
      $payroll_type= $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`=:cutt_off LIMIT 1) ORDER BY `payroll_type_no`");
      $payroll_type->execute(array(":store" => $record["store"], ":group" => $record["group_no"], ":cutt_off" => $record["payroll_date"]));
      if($payroll_type->rowCount()){
        while($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)){
          $pay_type = $payroll_type_data["pay_type"];
          if(!$payroll_type_data["is_factor_to_payrate"]){
            $items[] = array("span" => 1, "caption" => "<b>$pay_type</b>");
          }else{
            $items[] = array("span" => 2, "caption" => "<b>$pay_type</b>");
          }
        }
      }
    }
  }
  $items[] = array("span" => 2, "caption" => "<b>TOTAL</b>");
  return $items;
}

function get_columns($record){
  global $db, $db_hris;

  $items = array();
  $items[] = array("field" => "recid", "caption" => "<b>PIN</b>", "size" => "80px");
  $items[] = array("field" => "name", "caption" => "<b>NAME</b>", "size" => "200px");
  $payroll_type= $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`=:cutt_off LIMIT 1) ORDER BY `payroll_type_no`");
  $payroll_type->execute(array(":store" => $record["store"], ":group" => $record["group_no"], ":cutt_off" => $record["payroll_date"]));
  if($payroll_type->rowCount()){
    while($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)){
      $payroll_type= $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`=:cutt_off LIMIT 1) ORDER BY `payroll_type_no`");
      $payroll_type->execute(array(":store" => $record["store"], ":group" => $record["group_no"], ":cutt_off" => $record["payroll_date"]));
      if($payroll_type->rowCount()){
        while($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)){
          $pay_type = $payroll_type_data["payroll_type_no"];
          $pay_type1 = $payroll_type_data["payroll_type_no"].'1';
          if(!$payroll_type_data["is_factor_to_payrate"]){
            $items[] = array("field" => "$pay_type1", "caption" => "", "size" => "80px", "attr" => "align=right", "render" => "float:2");
          }else{
            $items[] = array("field" => "$pay_type", "caption" => "", "size" => "80px", "attr" => "align=right", "render" => "float:2");
            $items[] = array("field" => "$pay_type1", "caption" => "", "size" => "80px", "attr" => "align=right", "render" => "float:2");
          }
        }
      }
    }
  }
  $items[] = array("field" => "gross", "caption" => "<b>GROSS PAY</b>", "size" => "100px", "attr" => "align=right", "render" => "float:2");
  return $items;
}

function get_sum($record){
  global $db, $db_hris;

  $payroll_type= $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`=:cutt_off LIMIT 1) ORDER BY `payroll_type_no`");
  $payroll_type->execute(array(":store" => $record["store"], ":group" => $record["group_no"], ":cutt_off" => $record["payroll_date"]));
  if($payroll_type->rowCount()){
    while($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)){
      $payroll_type= $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`=:cutt_off LIMIT 1) ORDER BY `payroll_type_no`");
      $payroll_type->execute(array(":store" => $record["store"], ":group" => $record["group_no"], ":cutt_off" => $record["payroll_date"]));
      if($payroll_type->rowCount()){
        while($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)){
          $pay_type = $payroll_type_data["payroll_type_no"];
          $pay_type1 = $payroll_type_data["payroll_type_no"].'1';
          $sum = [$pay_type1 => 0];
        }
      }
    }
  }
  $items = array("w2ui" => array("summary" => true),"recid" => "", "summary" => 1, $sum, "gross" => 0, "name" => "<span class=\"w3-right\"><b>TOTALS</b></span>");
  return $items;
}

function get_earnings($data){
  global $db, $db_hris;

  $records = array();
  $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:group_no");
  $payroll_group->execute(array(":group_no" => $data["group"]));
  if($payroll_group->rowCount()){
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `store`=:store AND (SELECT COUNT(*) FROM $db_hris.`master_id` WHERE `master_id`.`pin_no`=`master_data`.`pin` AND `pay_group`=:pay_group) AND (SELECT COUNT(*) FROM $db_hris.`time_credit` WHERE `time_credit`.`employee_no`=`master_data`.`pin` AND `trans_date`>=:log_cutoff AND `trans_date`<=:payroll_cutoff LIMIT 1) ORDER BY `family_name`, `given_name`, `middle_name`");
    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` WHERE `payroll_date`=:payroll_cutoff AND (SELECT COUNT(*) FROM $db_hris.`master_id` WHERE `master_id`.`pin_no`=`payroll_trans`.`employee_no` AND `pay_group`=:pay_group) AND `is_posted` LIMIT 1");
    $master->execute(array(":store" => $data["store"], ":pay_group" => $payroll_group_data["group_name"], ":log_cutoff" => $payroll_group_data["cutoff_date"], ":payroll_cutoff" => $payroll_group_data["payroll_date"]));
    $payroll_trans->execute(array(":pay_group" => $payroll_group_data["group_name"], ":payroll_cutoff" => $payroll_group_data["payroll_date"]));
    $payroll_type= $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_date`=:cutt_off LIMIT 1) ORDER BY `payroll_type_no`");
    $trans_date = date("F j",strtotime($payroll_group_data["cutoff_date"]))." to ".date("F j".", "."Y",strtotime($payroll_group_data["payroll_date"]));
    if($master->rowCount()){
      if(!$payroll_trans->rowCount()){
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
          compute_earnings(array("eno" => $master_data["employee_no"], "df" => $payroll_group_data["cutoff_date"], "dt" => $payroll_group_data["payroll_date"], "payroll_date" => "0000-00-00"));
        }
      }
      $master->execute(array(":store" => $data["store"], ":pay_group" => $payroll_group_data["group_name"], ":log_cutoff" => $payroll_group_data["cutoff_date"], ":payroll_cutoff" => $payroll_group_data["payroll_date"]));
      $grid_col_group = get_columns_group(array("store" => $data["store"], "group_no" => $payroll_group_data["group_name"], "payroll_date" => $payroll_group_data["payroll_date"]));
      $grid = get_columns(array("store" => $data["store"], "group_no" => $payroll_group_data["group_name"], "payroll_date" => $payroll_group_data["payroll_date"]));
      while($master_data = $master->fetch(PDO::FETCH_ASSOC)){ 
        $record["recid"] = $master_data["pin"];
        $record["name"] = $master_data["family_name"].", ".$master_data["given_name"]." ".  substr($master_data["middle_name"], 0,1);
        $payroll_type->execute(array(":store" => $data["store"], ":group" => $payroll_group_data["group_name"], ":cutt_off" => $payroll_group_data["payroll_date"]));
        if($payroll_type->rowCount()){
          $summary = get_sum(array("store" => $data["store"], "group_no" => $payroll_group_data["group_name"], "payroll_date" => $payroll_group_data["payroll_date"]));
          while($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)){
            $pay_type = $payroll_type_data["payroll_type_no"];
            $pay_type1 = $payroll_type_data["payroll_type_no"].'1';
            $trans_pay_data = get_credit_and_amount(array("eno" => $master_data["employee_no"], "payroll_date" => $payroll_group_data["payroll_date"], "pay_type" => $payroll_type_data["payroll_type_no"]));
            $record["$pay_type"] = $trans_pay_data["credit"];
            $record["$pay_type1"] = $trans_pay_data["amount"];
            $record["gross"] = get_total(array("eno" => $master_data["employee_no"], "payroll_date" => $payroll_group_data["payroll_date"]));
            $summary["$pay_type1"] = get_total_pay(array("group" => $payroll_group_data["group_name"], "store" => $data["store"], "group_no" => 0, "cutt_off" => $payroll_group_data["payroll_date"], "pay_type" => $pay_type));
            $summary["gross"] = get_total_gross(array("group" => $payroll_group_data["group_name"], "store" => $data["store"], "group_no" => 0, "cutt_off" => $payroll_group_data["payroll_date"]));
          }
        }
        $records[] = $record;
      }
      if (count($records)) {
        $records[] = $summary;
      }
      $d = array("status" => "success", "todate" => "DATE: ".date("F j".", "."Y"), "trans_date" => "PAYROLL CUT-OFF: ".$trans_date, "colGroup" => $grid_col_group, "columns" => $grid, "records" => $records);
    }else{
      $d = array("status" => "error", "message" => "Error! Inactive Group", "e" => $master->errorInfo());
    }
  }else{
    $d = array("status" => "error", "message" => "Error getting group!", "e" => $payroll_group->errorInfo());
  }
  echo json_encode($d);
}

function get_total_pay($record){
  global $db, $db_hris;

  $total_pay = $db->prepare("SELECT SUM(`pay_amount`) AS `pay_amount` FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`group_no`=:group AND `master_data`.`store`=:store AND `payroll_trans_pay`.`payroll_group_no`=:group_no AND `payroll_trans_pay`.`payroll_date`=:cutt_off AND `payroll_trans_pay`.`payroll_type_no`=:pay_type");
  $total_pay->execute(array(":group" => $record["group"], ":store" => $record["store"], ":group_no" => $record["group_no"], ":cutt_off" => $record["cutt_off"], ":pay_type" => $record["pay_type"]));
  if($total_pay->rowCount()){
    $total_pay_data = $total_pay->fetch(PDO::FETCH_ASSOC);
    $data = $total_pay_data["pay_amount"];
  }
  return $data;
}

function get_total_gross($record){
  global $db, $db_hris;

  $total_pay = $db->prepare("SELECT SUM(`pay_amount`) AS `pay_amount`, SUM(`credit`) AS `credit` FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `payroll_trans_pay`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`group_no`=:group AND `master_data`.`store`=:store AND `payroll_trans_pay`.`payroll_group_no`=:group_no AND `payroll_date`=:cutt_off");
  $total_pay->execute(array(":group" => $record["group"], ":store" => $record["store"], ":group_no" => $record["group_no"], ":cutt_off" => $record["cutt_off"]));
  if($total_pay->rowCount()){
    $total_pay_data = $total_pay->fetch(PDO::FETCH_ASSOC);
    return $total_pay_data["pay_amount"];
  }
}

function get_credit_and_amount($record){
  global $db, $db_hris;

  $trans_pay = $db->prepare("SELECT SUM(`credit`) AS `credit`, SUM(`pay_amount`) AS `pay_amount` FROM $db_hris.`payroll_trans_pay` WHERE `employee_no`=:eno AND `payroll_date`=:cutt_off AND `payroll_type_no`=:pay_type");
  $trans_pay->execute(array("eno" => $record["eno"], ":cutt_off" => $record["payroll_date"], ":pay_type" => $record["pay_type"]));
  if($trans_pay->rowCount()){
    $trans_pay_data = $trans_pay->fetch(PDO::FETCH_ASSOC);
    $credit = $trans_pay_data["credit"];
    $amount = $trans_pay_data["pay_amount"];
  }
  return array("credit" => $credit, "amount" => $amount);
}

function get_total($record){
  global $db, $db_hris;

  $total_pay = $db->prepare("SELECT SUM(`pay_amount`) AS `pay_amount` FROM $db_hris.`payroll_trans_pay` WHERE `employee_no`=:eno AND `payroll_date`=:cutt_off");
  $total_pay->execute(array("eno" => $record["eno"], ":cutt_off" => $record["payroll_date"]));
  if($total_pay->rowCount()){
    $total_pay_data = $total_pay->fetch(PDO::FETCH_ASSOC);
    $gross = $total_pay_data["pay_amount"];
  }
  return $gross;
}
function adjustment($record){
  global $db, $db_hris;

  $adj = $db->prepare("INSERT INTO $db_hris.`payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) SELECT `employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount` FROM $db_hris.`payroll_adjustment` WHERE `employee_no`=:eno AND `payroll_date` LIKE :pay_date");
  $adj->execute(array(":eno" => $record["eno"], ":pay_date" => $record["pay_date"]));
}

function regular_time($record){
  global $db, $db_hris;

  $time_credit = $db->prepare("SELECT SUM(`credit_time`) AS `mins_credit` FROM $db_hris.`time_credit` WHERE `employee_no`=:pin AND `trans_date`>=:df AND `trans_date`<=:dt AND !`isDOD`");
  $time_credit->execute(array(":pin" => $record["pin"], ":df" => $record["df"], ":dt" => $record["dt"]));
  if($time_credit->rowCount()){
    $time_credit_data = $time_credit->fetch(PDO::FETCH_ASSOC);
    $time_credit_total = number_format($time_credit_data["mins_credit"] / 60, 2, '.', '');
    $total_time = number_format($time_credit_total + 0, 2, '.', '');
    $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `pay_type` LIKE :pay_type");
    $trans_pay = $db->prepare("INSERT INTO $db_hris.`payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES (:eno, :pay_date, :pay_type, :total_time, :pay_amt)");
    if (number_format($total_time, 2, '.', '') != number_format(0, 2)) {
      if (number_format($record["rate"], 2, '.', '') > number_format(0, 2)) {
        $payroll_type->execute(array("pay_type" => 'BASIC PAY'));
        $pay_amount = number_format($total_time * $record["rate"] / 8, 2, '.', '');
        $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
        $trans_pay->execute(array(":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"], ":total_time" => $total_time, ":pay_amt" => $pay_amount));
      }
      if (number_format($record["incentives"], 2, '.', '') > number_format(0, 2)) {
        $payroll_type->execute(array("pay_type" => 'INCENTIVE'));
        $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
        $pay_amount = number_format($total_time * $record["incentives"] / 8, 2, '.', '');
        $trans_pay->execute(array(":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"], ":total_time" => $total_time, ":pay_amt" => $pay_amount));
      }
    }
  }
}

function overtime($record){
  global $db, $db_hris;

  $ot_credit = $db->prepare("SELECT SUM(`credit_time`) AS `mins_credit` FROM $db_hris.`time_credit_ot` WHERE `employee_no`=:pin AND `trans_date`>=:df AND `trans_date`<=:dt AND `is_approved`");
  $ot_credit->execute(array(":pin" => $record["pin"], ":df" => $record["df"], ":dt" => $record["dt"]));
  if($ot_credit->rowCount()){
    $ot_credit_data = $ot_credit->fetch(PDO::FETCH_ASSOC);
    $mins_credit = number_format($ot_credit_data["mins_credit"], 2, '.', '');
    $ot_time_total = number_format($mins_credit + 0, 2, '.', '');
    $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `pay_type` LIKE :pay_type");
    $payroll_type->execute(array("pay_type" => 'REG OT'));
    if($payroll_type->rowCount()){
      $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
      if (number_format($ot_time_total, 2, '.', '') != number_format(0, 2)) {
        $pay_amount = number_format($ot_time_total * $record["rate"] / 8, 2, '.', '');
        $check = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay` WHERE `payroll_type_no`=:pay_type AND `employee_no`=:eno AND `payroll_date`=:pay_date");
        $check->execute(array(":pay_type" => $payroll_type_data["payroll_type_no"], ":eno" => $record["eno"], ":pay_date" => $record["pay_date"]));
        if ($check->rowCount()) {
          $update_trans_pay = $db->prepare("UPDATE $db_hris.`payroll_trans_pay` SET `credit`=`credit`+:credit, `pay_amount`=`pay_amount`+:amount WHERE `employee_no`=:eno AND `payroll_date`=:pay_date AND `payroll_type_no`=:pay_type");
          $update_trans_pay->execute(array(":credit" => $ot_time_total, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
        }else{
          $in_trans_pay = $db->prepare("INSERT INTO $db_hris.`payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES (:eno, :pay_date, :pay_type, :credit, :amount)");
          $in_trans_pay->execute(array(":credit" => $ot_time_total, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
        }
      }
    }
  }
}

function dod($record){
  global $db, $db_hris;

  $time_credit = $db->prepare("SELECT SUM(`credit_time`) AS `mins_credit` FROM $db_hris.`time_credit` WHERE `employee_no`=:pin AND `trans_date`>=:df AND `trans_date`<=:dt AND `isDOD`");
  $time_credit->execute(array(":pin" => $record["pin"], ":df" => $record["df"], ":dt" => $record["dt"]));
  if($time_credit->rowCount()){
    $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `pay_type` LIKE :pay_type");
    $time_credit_data = $time_credit->fetch(PDO::FETCH_ASSOC);
    $mins_credit = number_format($time_credit_data["mins_credit"] / 60, 2, '.', '');
    $mins_total = number_format($mins_credit + 0, 2, '.', '');
    $payroll_type->execute(array("pay_type" => 'JOB ORDER'));
    if($payroll_type->rowCount()){
      $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
      if (number_format($mins_total, 2, '.', '') != number_format(0, 2)) {
        $total_rate = $record["rate"] + $record["incentives"];
        $pay_amount = number_format($mins_total * $total_rate / 8, 2, '.', '');
        $check = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay` WHERE `payroll_type_no`=:pay_type AND `employee_no`=:eno AND `payroll_date`=:pay_date");
        $check->execute(array(":pay_type" => $payroll_type_data["payroll_type_no"], ":eno" => $record["eno"], ":pay_date" => $record["pay_date"]));
        if ($check->rowCount()) {
          $update_trans_pay = $db->prepare("UPDATE $db_hris.`payroll_trans_pay` SET `credit`=`credit`+:credit, `pay_amount`=`pay_amount`+:amount WHERE `employee_no`=:eno AND `payroll_date`=:pay_date AND `payroll_type_no`=:pay_type");
          $update_trans_pay->execute(array(":credit" => $mins_total, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
        }else{
          $in_trans_pay = $db->prepare("INSERT INTO $db_hris.`payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES (:eno, :pay_date, :pay_type, :credit, :amount)");
          $in_trans_pay->execute(array(":credit" => $mins_total, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
        }
      }
    }
  }
}

function vacation($record){
    global $db, $db_hris;

  $credit = 8;
  $employee_vl = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:eno AND `vl_date`>=:df AND `vl_date`<=:dt AND !`is_cancelled`");
  $employee_vl->execute(array(":eno" => $record["eno"], ":df" => $record["df"], ":dt" => $record["dt"]));
  if ($employee_vl->rowCount()) {
    $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `pay_type` LIKE :pay_type");
    $payroll_type->execute(array("pay_type" => 'VACATION'));
    if($payroll_type->rowCount()){
      $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
      while ($employee_vl_data = $employee_vl->fetch(PDO::FETCH_ASSOC)) {
        $time_credit = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `employee_no`=:pin AND `trans_date`='' AND `credit_time`>0");
        $time_credit->execute(array(":pin" => $record["pin"], ":trans_date" => $employee_vl_data["vl_date"]));
        if (!$time_credit->rowCount()) {
          $total_rate = $record["rate"] + $record["incentives"];
          $pay_amount = number_format($credit * $total_rate / 8, 2, '.', '');
          $check = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay` WHERE `payroll_type_no`=:pay_type AND `employee_no`=:eno AND `payroll_date`=:pay_date");
          $check->execute(array(":pay_type" => $payroll_type_data["payroll_type_no"], ":eno" => $record["eno"], ":pay_date" => $record["pay_date"]));
          if ($check->rowCount()) {
            $update_trans_pay = $db->prepare("UPDATE $db_hris.`payroll_trans_pay` SET `credit`=`credit`+:credit, `pay_amount`=`pay_amount`+:amount WHERE `employee_no`=:eno AND `payroll_date`=:pay_date AND `payroll_type_no`=:pay_type");
            $update_trans_pay->execute(array(":credit" => $credit, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
          }else{
            $in_trans_pay = $db->prepare("INSERT INTO $db_hris.`payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES (:eno, :pay_date, :pay_type, :credit, :amount)");
            $in_trans_pay->execute(array(":credit" => $credit, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
          }
        }
      }
    }
  }
}

function holiday($record){
  global $db, $db_hris;

  $hired = date_create($record['date_hired']);
  $interval = date_diff($hired,date_create($record['df']));
  $mo = $interval->format('%m');
  if($mo >= 1){
    $holiday = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date`>=:df AND `holiday_date`<=:dt AND !(SELECT COUNT(*) FROM $db_hris.`employee_vl` WHERE `employee_no`=:eno AND `vl_date`=`holiday`.`holiday_date` AND !`is_cancelled` AND !`is_served`)");
    $holiday->execute(array(":df" => $record["df"], ":dt" => $record["dt"], ":eno" => $record["eno"]));
    if ($holiday->rowCount()) {
      while ($holiday_data = $holiday->fetch(PDO::FETCH_ASSOC)) {
        $time_credit = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `employee_no`=:pin AND `trans_date`=:trans_date AND `credit_time`>0");
        $time_credit->execute(array(":pin" => $record["pin"], ":trans_date" => $holiday_data["holiday_date"]));
        if ($time_credit->rowCount()) {
          $time_credit_data = $time_credit->fetch(PDO::FETCH_ASSOC);
          $credit = number_format($time_credit_data["credit_time"] / 60, 2, '.', '');
        } else {
          $credit = 0;
        }
        $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `pay_type` LIKE :pay_type");
        $update_trans_pay = $db->prepare("UPDATE $db_hris.`payroll_trans_pay` SET `credit`=`credit`+:credit, `pay_amount`=`pay_amount`+:amount WHERE `employee_no`=:eno AND `payroll_date`=:pay_date AND `payroll_type_no`=:pay_type");
        $in_trans_pay = $db->prepare("INSERT INTO $db_hris.`payroll_trans_pay` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`) VALUES (:eno, :pay_date, :pay_type, :credit, :amount)");
        $check = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay` WHERE `payroll_type_no`=:pay_type AND `employee_no`=:eno AND `payroll_date`=:pay_date");
        if ($holiday_data["is_special"]) {
          if ($time_credit->rowCount()) {
            $payroll_type->execute(array("pay_type" => 'SH PREM'));
            if($payroll_type->rowCount()){
              $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
              $pay_amount = number_format($credit * $record["rate"] / 8 * $payroll_type_data["factor_amount"], 2, '.', '');
              $check->execute(array(":pay_type" => $payroll_type_data["payroll_type_no"], ":eno" => $record["eno"], ":pay_date" => $record["pay_date"]));
              if ($check->rowCount()) {
                $update_trans_pay->execute(array(":credit" => $credit, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
              }else{
                $in_trans_pay->execute(array(":credit" => $credit, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
              }
            }
          }
        } else {
          $trans_date = check_absent(array("hol_date" => $holiday_data["holiday_date"], "pin" => $record["pin"]));
          $time_creditx = $db->prepare("SELECT * FROM $db_hris.`time_credit` WHERE `employee_no`=:pin AND `trans_date`=:trans_date AND `credit_time`>0");
          $time_creditx->execute(array(":pin" => $record["pin"], ":trans_date" => $trans_date));
          if ($time_credit->rowCount()) {
            $credit = 8;
            $payroll_type->execute(array("pay_type" => 'HOL. PREM'));
            if($payroll_type->rowCount()){
              $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
              $pay_amount = number_format($record["rate"] * $payroll_type_data["factor_amount"], 2, '.', '');
              if ($check->rowCount()) {
                $update_trans_pay->execute(array(":credit" => $credit, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
              }else{
                $in_trans_pay->execute(array(":credit" => $credit, ":amount" => $pay_amount, ":eno" => $record["eno"], ":pay_date" => $record["pay_date"], ":pay_type" => $payroll_type_data["payroll_type_no"]));
              }
            }
          }
        }
      }
    }
  }
}

function compute_earnings($record) {
  global $db, $db_hris;
  
  set_time_limit(300);
  if ($record["payroll_date"] == "0000-00-00") {
    $payroll_date = $record["dt"];
  }
  $del_trans_pay = $db->prepare("DELETE FROM $db_hris.`payroll_trans_pay` WHERE `employee_no`=:eno AND `payroll_date` LIKE :payroll_date");
  $del_trans_pay->execute(array(":eno" => $record["eno"], ":payroll_date" => $payroll_date));
  $employee_rate = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`employee_rate` WHERE `master_data`.`employee_no`=`employee_rate`.`employee_no` AND `employee_rate`.`employee_no`=:eno");
  $employee_rate->execute(array(":eno" => $record["eno"]));
  if ($employee_rate->rowCount()) {
    $employee_rate_data = $employee_rate->fetch(PDO::FETCH_ASSOC);
    $record = array("pin" => $employee_rate_data["pin"], "eno" => $employee_rate_data["employee_no"], "df" => $record["df"], "dt" => $record["dt"], "rate" => $employee_rate_data["daily_rate"], "incentives" => $employee_rate_data["incentive_cash"], "pay_date" => $payroll_date, "date_hired" => $employee_rate_data["date_hired"]);
    adjustment($record); //adjustment
    regular_time($record);  //regular time
    overtime($record);   //overtime credit
    dod($record);  //employee duty on day off
    vacation($record); //vacation leave
    holiday($record);  //holiday
  }
}

function check_absent($record){
  global $db, $db_hris;

  //check if day off yesterday
  $trans_date = date('Y-m-d', mktime(0, 0, 0, substr($record["hol_date"], 5, 2), substr($record["hol_date"], 8, 2) - 1, substr($record["hol_date"], 0, 4)));
  $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `pin`=:pin");
  $att = $db->prepare("SELECT * FROM $db_hris.`attendance_log` WHERE `pin`=:pin AND `log_date`=:trans_date");
  $sched = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `shift_code`!=0 AND `trans_date`=:trans_date");
  $master->execute(array(":pin" => $record["pin"]));
  if ($master->rowCount()) {
    $master_data = $master->fetch(PDO::FETCH_ASSOC);
    $att->execute(array(":pin" => $master_data["pin"], ":trans_date" => $trans_date));
    if ($att->rowCount()) {
      return $trans_date;
    }else{
      $sched->execute(array(":eno" => $master_data["employee_no"], ":trans_date" => $trans_date));
      if ($sched->rowCount()) {
        $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:scode AND `is_off_duty`");
        $sched_data = $sched->fetch(PDO::FETCH_ASSOC);
        $shift->execute(array(":scode" => $sched_data["shift_code"]));
        if($shift->rowCount()){
          return date('Y-m-d', mktime(0, 0, 0, substr($record["hol_date"], 5, 2), substr($record["hol_date"], 8, 2) - 2, substr($record["hol_date"], 0, 4)));
        }
      }
    }
  }
}

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