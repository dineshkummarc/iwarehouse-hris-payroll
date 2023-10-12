<?php

$program_code = 15;
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
                case "get-payroll-register":
                    if ($access_rights === "A+E+D+B+P+") {
                        $record = array("group" => $_POST["group"], "store" => $_POST["store"]);
                        get_payroll_register($record);
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

function get_columns_group(){
  $items = array();
  $items[] = array("span" => 3, "caption" => "");
  $items[] = array("span" => 7, "caption" => "STATUTORY CONTRIBUTION SHARE/OTHER DEDUCTIONS");
  $items[] = array("span" => 1, "caption" => "");
  return $items;
}

function get_columns(){
  global $db, $db_hris;

  $items = array();
  $items[] = array("field" => "recid", "caption" => "<b>PIN</b>", "size" => "80px", "frozen" => true );
  $items[] = array("field" => "name", "caption" => "<b>NAME</b>", "size" => "200px", "frozen" => true );
  $items[] = array("field" => "gross", "caption" => "<b>GROSS PAY</b>", "size" => "100px", "attr" => "align=right", "render" => "float:2");
  $benefits = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `is_computed` AND !`is_inactive` ORDER BY `deduction_no`");
  $benefits->execute();
  if($benefits->rowCount()){
    while($benefits_data = $benefits->fetch(PDO::FETCH_ASSOC)){
      $ded_no = $benefits_data["deduction_no"];
      $label = $benefits_data["deduction_label"];
      $items[] = array("field" => $ded_no, "caption" => "<b>$label</b>", "size" => "150px", "attr" => "align=right", "render" => "float:2");
    }
  }
  $items[] = array("field" => "other", "caption" => "<b>OTHER DED</b>", "size" => "100px", "attr" => "align=right", "render" => "float:2");
  $items[] = array("field" => "net", "caption" => "<b>NET PAY</b>", "size" => "100px", "attr" => "align=right", "render" => "float:2");
  return $items;
}

function get_payroll_register($record){
  global $db, $db_hris;

  $col_group = get_columns_group();
  $grid = get_columns();
  $deductions = generate_deductions($record);
  if ($deductions) {
    $change = sysconfig("change");
    $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name` LIKE :group");
    $payroll_group->execute(array(":group" => $record["group"]));
    if ($payroll_group->rowCount()) {
      $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
      $log_cutoff = $payroll_group_data["cutoff_date"];
      $payroll_cutoff = $payroll_group_data["payroll_date"];
      set_time_limit(300);
      $del_payroll_trans = $db->prepare("DELETE FROM $db_hris.`payroll_trans` WHERE `payroll_date`=:pay_date AND (SELECT COUNT(*) FROM $db_hris.`master_id`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`=:store AND `master_id`.`employee_no`=`payroll_trans`.`employee_no` AND `pay_group`=:group) AND !`is_posted`");
      $del_payroll_trans->execute(array(":store" => $record["store"], ":pay_date" => $payroll_cutoff, ":group" => $payroll_group_data["group_name"]));
      $master = $db->prepare("SELECT * FROM $db_hris.`master_data`, $db_hris.`master_id` WHERE !`is_inactive` AND `master_id`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`group_no`=:group AND `master_data`.`store`=:store AND ((SELECT COUNT(*) FROM $db_hris.`time_credit` WHERE `time_credit`.`employee_no`=`master_data`.`pin` AND `trans_date`>=:df AND `trans_date`<=:dt LIMIT 1))");
      $master->execute(array(":store" => $record["store"], ":df" => $log_cutoff, ":dt" => $payroll_cutoff, ":group" => $payroll_group_data["group_name"]));
      if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
          $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` WHERE `payroll_date`=:pay_date AND `employee_no`=:eno AND !`is_posted`");
          $payroll_trans->execute(array(":pay_date" => $payroll_cutoff, ":eno" => $master_data["employee_no"]));
          $xdata = array("payroll_cutoff" => $payroll_cutoff, "store" => $record["store"], "group" => $payroll_group_data["group_name"]);
          if (!$payroll_trans->rowCount()) {
            $isComputed = compute_payroll($master_data["employee_no"], $payroll_cutoff, $change);
            if ($isComputed) {
              $employeeData = get_payroll_records_records($xdata);
            } else {
              $employeeData = "Error code: $isComputed! Please try again later!";
            }
          } else {
            $employeeData = get_payroll_records_records($xdata);
          }
        }
      } else {
        $employeeData = "No record found in master file!";
      }
      // Encode all employee data as JSON
      echo json_encode(array("status" => "success", "colGroup" => $col_group, "columns" => $grid, "records" => $employeeData));
    }
  } else {
    // Handle the case when $deductions is false
    echo json_encode($deductions);
  }
}

function get_payroll_records_records($xdata){
  global $db, $db_hris;

  $records = array();
  $schedule = number_format(substr($xdata["payroll_cutoff"], -2)) <= number_format(15) ? 1 : 2;
  $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `is_computed` AND !`is_inactive` AND `schedule` LIKE :sched");
  $deduction->execute(array(":sched" => "%$schedule%"));

  $master = $db->prepare("SELECT * FROM  $db_hris.`master_data` WHERE !`is_inactive` AND (SELECT COUNT(*) FROM  $db_hris.`master_id` WHERE `master_id`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `pay_group`=:group AND (SELECT COUNT(*) FROM  $db_hris.`payroll_trans` WHERE `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `payroll_date`=:pay_date)) ORDER BY `master_data`.`family_name`,`master_data`.`given_name`, `master_data`.`middle_name`");
  $master->execute(array(":store" => $xdata["store"], ":group" => $xdata["group"], ":pay_date" => $xdata["payroll_cutoff"]));
  if($master->rowCount()){
    $summary = array("w2ui" => array("summary" => true), "recid" => "", "summary" => 1, "gross" => 0, 7 => 0, 107 => 0, 207 => 0, 307 => 0, "other" => 0, "net" => 0, "name" => "<span class=\"w3-right\"><b>TOTALS</b></span>");
    while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
      $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans`, $db_hris.`master_data` WHERE `payroll_trans`.`employee_no`=:emp_no AND `master_data`.`employee_no`=`payroll_trans`.`employee_no` AND `master_data`.`store`=:store AND `payroll_trans`.`payroll_date`=:pay_date");
      $payroll_trans->execute(array(":store" => $xdata["store"], ":emp_no" => $master_data["employee_no"], ":pay_date" => $xdata["payroll_cutoff"]));
      $payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC);
      $record["recid"] = $master_data["pin"];
      $record["name"] = $master_data["family_name"] . ", " . $master_data["given_name"] . " " . substr($master_data["middle_name"], 0, 1);
      $record["gross"] = $payroll_trans_data["gross_pay"];
      $record[7] = get_ded(array("emp_no" => $master_data["employee_no"], "pay_date" => $xdata["payroll_cutoff"], "ded_no" => 7));
      $record[107] = get_ded(array("emp_no" => $master_data["employee_no"], "pay_date" => $xdata["payroll_cutoff"], "ded_no" => 107));
      $record[207] = get_ded(array("emp_no" => $master_data["employee_no"], "pay_date" => $xdata["payroll_cutoff"], "ded_no" => 207));
      $record[307] = get_ded(array("emp_no" => $master_data["employee_no"], "pay_date" => $xdata["payroll_cutoff"], "ded_no" => 307));
      $record["other"] = get_other_ded(array("emp_no" => $master_data["employee_no"], "pay_date" => $xdata["payroll_cutoff"]));
      $record["net"] = $payroll_trans_data["net_pay"];
      $summary["gross"] += $record["gross"];
      $summary[7] += $record[7];
      $summary[107] += $record[107];
      $summary[207] += $record[207];
      $summary[307] += $record[307];
      $summary["other"] += $record["other"];
      $summary["net"] += $record["net"];
      $records[] = $record;
    }
    if (count($records)) {
      $records[] = $summary;
    }
  }
  return $records;
}

function get_ded($record){
  global $db, $db_hris;

  // Check if all required keys are present in the $record array
  if (isset($record["emp_no"], $record["pay_date"], $record["ded_no"])) {
    $payroll_trans_ded = $db->prepare("SELECT `deduction_amount` FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date AND `deduction_no`=:ded_no");
    $payroll_trans_ded->execute(array(":emp_no" => $record["emp_no"], ":pay_date" => $record["pay_date"], ":ded_no" => $record["ded_no"]));
    if($payroll_trans_ded->rowCount()){
      $ded_amount = $payroll_trans_ded->fetch(PDO::FETCH_ASSOC);
      return $ded_amount["deduction_amount"];
    }
  }
  // If the required keys are not present or the query fails, you may return an appropriate error value or handle it as needed.
  return null; // You can choose an appropriate error handling strategy.
}

function get_other_ded($record){
  global $db, $db_hris;

  $schedule = number_format(substr($record["pay_date"], -2)) <= number_format(15) ? 1 : 2;
  $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `is_computed` AND !`is_inactive` AND `schedule` LIKE :sched");
  $deduction->execute(array(":sched" => "%$schedule%"));
  if($deduction->rowCount()){
    while($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)){
      $payroll_trans_ded = $db->prepare("SELECT SUM(`deduction_amount`) AS `deduction_amount` FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date AND `deduction_no`!=:ded_no");
      $payroll_trans_ded->execute(array(":emp_no" => $record["emp_no"], ":pay_date" => $record["pay_date"], ":ded_no" => $deduction_data["deduction_no"]));
      if($payroll_trans_ded->rowCount()){
        $ded_amount = $payroll_trans_ded->fetch(PDO::FETCH_ASSOC);
        return $ded_amount["deduction_amount"];
      }
    }
  }
}

function generate_deductions($record){
  global $db, $db_hris;

  $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name` LIKE :group");
  $payroll_group->execute(array(":group" => $record["group"]));
  if ($payroll_group->rowCount()) {
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
    $log_cutoff = $payroll_group_data["cutoff_date"];
    $payroll_cutoff = $payroll_group_data["payroll_date"];
    $schedule = number_format(substr($payroll_cutoff, -2)) <= number_format(15) ? 1 : 2;
    $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE !`is_computed` AND !`is_inactive` AND `schedule` LIKE :sched AND (SELECT COUNT(*) FROM $db_hris.`employee_deduction` WHERE `employee_deduction`.`deduction_no`=`deduction`.`deduction_no` LIMIT 1) ORDER BY `deduction_label`");
    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` WHERE `payroll_date`=:pay_date AND (SELECT COUNT(*) FROM $db_hris.`master_id`,$db_hris.`master_data` WHERE `master_id`.`employee_no`=`payroll_trans`.`employee_no` AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `pay_group`=:group) AND !`is_posted` LIMIT 1");
    $payroll_trans->execute(array(":pay_date" => $payroll_cutoff, ":store" => $record["store"], ":group" => $payroll_group_data["group_name"]));
    if ($payroll_trans->rowCount()) {
      $payroll_trans_ded = $db->prepare("DELETE FROM $db_hris.`payroll_trans_ded` WHERE `payroll_date`=:pay_date AND (SELECT COUNT(*) FROM $db_hris.`master_id`,$db_hris.`master_data` WHERE `master_id`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `payroll_trans_ded`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `pay_group`=:group)");
      $payroll_trans_ded->execute(array(":pay_date" => $payroll_cutoff, ":store" => $record["store"], ":group" => $payroll_group_data["group_name"]));
      $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND (SELECT COUNT(*) FROM $db_hris.`master_id` WHERE `master_id`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`=:store AND `pay_group`=:group) ORDER BY `family_name`, `given_name`, `middle_name`");
      $master->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"]));
      if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
          set_time_limit(60);
          $deduction->execute(array(":sched" => "%$schedule%"));
          if ($deduction->rowCount()) {
            while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
              $sync = sync_deduction($master_data["employee_no"], $deduction_data["deduction_no"]);
              if($sync){
                $employee_deduction = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no AND `deduction_balance`>0");
                $employee_deduction->execute(array(":emp_no" => $master_data["employee_no"], ":ded_no" => $deduction_data["deduction_no"]));
                if ($employee_deduction->rowCount()) {
                  $employee_deduction_data = $employee_deduction->fetch(PDO::FETCH_ASSOC);
                  if (number_format($employee_deduction_data["deduction_balance"], 2, '.', '') < number_format($employee_deduction_data["deduction_amount"], 2, '.', '')) {
                    $deduction_amount = $employee_deduction_data["deduction_balance"];
                  } else {
                    $deduction_amount = $employee_deduction_data["deduction_amount"];
                  }
                  $ins_payroll_ded = $db->prepare("INSERT INTO $db_hris.`payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`) VALUES (:emp_no, :pay_date, :ded_no, :amt, :bal)");
                  $ins_payroll_ded->execute(array(":emp_no" => $master_data["employee_no"], ":pay_date" => $payroll_cutoff, ":ded_no" => $deduction_data["deduction_no"], ":amt" => $deduction_amount, ":bal" => $employee_deduction_data["deduction_balance"]));
                  $data = $ins_payroll_ded->rowCount() ? 1 : $ins_payroll_ded->errorInfo;
                }
              }else{
                $data = array("status" => "error", "message" => "Error synchronizing deductions!", "e" => $sync);
              }
            }
          }else{
            $data = array("status" => "error", "message" => "Deduction not found!", "e" => $deduction->errorInfo());
          }
        }
      }else{
        $data = array("status" => "error", "message" => "No record found in master file!", "e" => $master->errorInfo());
      }
    }else{
      $data = array("status" => "error", "message" => "Payroll Already Posted!", "e" => $payroll_trans->errorInfo());
    }
  } else {
    $data = array("status" => "error", "message" => "Payroll Group not found!", "e" => $payroll_group->errorInfo());
  }
  return $data;
}

function sysconfig($config_name) {
	global $db, $db_hris;

	$sysconfig = $db->prepare("SELECT `config_value` FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :config");
  $sysconfig->execute(array(":config" => $config_name));
	if ($sysconfig->rowCount()) {
		$sysconfig_data = $sysconfig->fetch(PDO::FETCH_ASSOC);
		$config_value = $sysconfig_data['config_value'];
	}else{
		$config_value = "";
  }
	return $config_value;
}


function sync_deduction($employee_no, $deduction_no){
  global $db, $db_hris;

  $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
  $deduction->execute(array(":ded_no" => $deduction_no));
  if ($deduction->rowCount()) {
    $deduction_data = $deduction->fetch(PDO::FETCH_ASSOC);
    if ($deduction_data["deduction_type"] and $deduction_no) {
      $trans_date = date("Y-m-d");
      $deduction_transaction = $db->prepare("SELECT * FROM $db_hris.`deduction_transaction` WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no AND !`is_paid`");
      $deduction_transaction->execute(array(":emp_no" => $employee_no, ":ded_no" => $deduction_no));
      if ($deduction_transaction->rowCount()) {
        $deduction_transaction1 = $db->prepare("SELECT COUNT(*) AS `count`, SUM(`balance`) AS `balance`, SUM(`payroll_deduction`) AS `payroll_deduction` FROM $db_hris.`deduction_transaction` WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no AND !`is_paid`");
        $employee_deduction = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
        $employee_deduction->execute(array(":emp_no" => $employee_no, ":ded_no" => $deduction_no));
        $ins_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger` (`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :adj, :bal, :rm, :uid, :station)");
        $update_emp_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_balance`=:bal, `deduction_amount`=:amount WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
        $deduction_transaction1->execute(array(":emp_no" => $employee_no, ":ded_no" => $deduction_no));
        if ($deduction_transaction1->rowCount()) {
          $u = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=`deduction_balance` WHERE `employee_no`=:emp_no AND `deduction_balance`<`deduction_amount` OR `deduction_amount<=0");
          $deduction_transaction_data = $deduction_transaction1->fetch(PDO::FETCH_ASSOC);
          if ($employee_deduction->rowCount()) {
            $employee_deduction_data = $employee_deduction->fetch(PDO::FETCH_ASSOC);
            if (number_format($employee_deduction_data["deduction_balance"], 2, '.', '') != number_format($deduction_transaction_data["balance"], 2, '.', '')) {
              $adjustment = $deduction_transaction_data["balance"] - $employee_deduction_data["deduction_balance"];
              $ins_ledger->execute(array(":emp_no" => $employee_no, ":ded_no" => $deduction_no, ":date" => $trans_date, ":adj" => $adjustment, ":bal" => $deduction_transaction_data["balance"], ":rm" => "System generated transaction", ":uid" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR']));
              $update_emp_ded->execute(array(":bal" => $deduction_transaction_data["balance"], ":amount" => $deduction_transaction_data["payroll_deduction"], ":emp_no" => $employee_no, ":ded_no" => $deduction_no));
              if ($ins_ledger->rowCount()) {
                if ($update_emp_ded->rowCount()) {
                  $data = 1;
                } else {
                  $data = array("message" => "Error updating employee deduction!", "e" => $update_emp_ded->errorInfo());
                }
              } else {
                $data = array("message" => "Error inserting ledger!", "e" => $ins_ledger->errorInfo());
              }
            }
          } else {
            if (number_format($deduction_transaction_data["balance"], 2, '.', '') !=  number_format(0, 2)) {
              $ins_ded = $db->prepare("INSERT INTO $db_hris.`employee_deduction` (`employee_no`, `deduction_no`, `deduction_balance`, `deduction_amount`) VALUES (:emp_no, :ded_no, :bal, :amount)");
              $ins_ded->execute(array(":emp_no" => $employee_no, ":ded_no" => $deduction_no, ":bal" => $deduction_transaction_data["balance"], ":amount" => $deduction_transaction_data["payroll_deduction"]));
              if ($ins_ded->rowCount()) {
                $ins_ledger->execute(array(":emp_no" => $employee_no, ":ded_no" => $deduction_no, ":date" => $trans_date, ":adj" => $deduction_transaction_data["balance"], ":bal" => $deduction_transaction_data["balance"], ":rm" => "System generated transaction", ":uid" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR']));
              }
            }
          }
          $u->execute(array(":emp_no" => $employee_no));
        }
      }else{
        $data = 1;
      }
    }else{
      $data = "Deduction type and Deduction no is empty!";
    }
  }else{
    $data = "No deduction found!";
  }
  return $data;
}

function compute_payroll($employee_no, $payroll_date, $change){
  global $db, $db_hris;

  set_time_limit(300);
  $year = substr($payroll_date, 0, 4);
  $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:emp_no");
  $master_id = $db->prepare("SELECT * FROM $db_hris.`master_id` WHERE `employee_no`=:emp_no");
  $master->execute(array(":emp_no" => $employee_no));
  $master_id->execute(array(":emp_no" => $employee_no));
  $master_data = $master->fetch(PDO::FETCH_ASSOC);
  $master_id_data = $master_id->fetch(PDO::FETCH_ASSOC);
  $schedule = number_format(substr($payroll_date, -2)) <= number_format(15) ? "1" : "2";
  $del_trans_ded = $db->prepare("DELETE FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date AND (SELECT COUNT(*) FROM  $db_hris.`deduction` WHERE `is_computed` AND `deduction`.`deduction_no`=`payroll_trans_ded`.`deduction_no` AND `schedule` LIKE :sched AND !`is_inactive`)");
  $del_trans_ded->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":sched" => "%$schedule%"));
  $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `is_computed` AND `schedule` LIKE :sched AND !`is_inactive`");
  $deduction->execute(array(":sched" => "%$schedule%"));
  $employee_todate = $db->prepare("SELECT * FROM $db_hris.`employee_todate` WHERE `employee_no`=:emp_no");
  $employee_todate->execute(array(":emp_no" => $employee_no));
  if ($employee_todate->rowCount()) {
    $employee_todate_data = $employee_todate->fetch(PDO::FETCH_ASSOC);
  } else {
    $ins = $db->prepare("INSERT INTO $db_hris.`employee_todate` (`employee_no`) VALUES (:emp_no)");
    $ins->execute(array(":emp_no" => $employee_no));
    if ($ins->rowCount()) {
      $employee_todate->execute(array(":emp_no" => $employee_no));
      if ($employee_todate->rowCount()) {
        $employee_todate_data = $employee_todate->fetch(PDO::FETCH_ASSOC);
      }
    }
  }
  $grosspay = $sss_prem = $pagibig_prem = $phil_prem = $tax_amount = 0;
  $grosspay_tax = $employee_todate_data["grosspay_tax"];
  $grosspay_sss = $employee_todate_data["grosspay_sss"];
  $grosspay_pagibig = $employee_todate_data["grosspay_pagibig"];
  $grosspay_phil = $employee_todate_data["grosspay_phil"];
  $employee_trans_pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date");
  $employee_trans_pay->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date));
  if ($employee_trans_pay->rowCount()) {
    $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `payroll_type_no`=:pay_type");
    while ($employee_trans_pay_data = $employee_trans_pay->fetch(PDO::FETCH_ASSOC)) {
      $payroll_type->execute(array(":pay_type" => $employee_trans_pay_data["payroll_type_no"]));
      if ($payroll_type->rowCount()) {
        $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
        $grosspay += $employee_trans_pay_data["pay_amount"];
        if ($payroll_type_data["is_subject_to_tax"])
          $grosspay_tax += $employee_trans_pay_data["pay_amount"];
        if ($payroll_type_data["is_subject_to_sss"]) {
          $grosspay_sss += $employee_trans_pay_data["pay_amount"];
          $grosspay_pagibig += $employee_trans_pay_data["pay_amount"];
          $grosspay_phil += $employee_trans_pay_data["pay_amount"];
        }
      }
    }
  }
  if (number_format($grosspay, 2, '.', '') > number_format(0, 2)) {
    if ($deduction->rowCount())
      while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
        $benefits = $db->prepare("INSERT INTO $db_hris.`payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`) VALUES (:emp_no, :pay_date, :ded_no, 
          :ded_amt, :ded_todate)");
        if (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(7, 0, '.', '') and $master_id_data["compute_sss"]) {
          $sss_prem = compute_sss($grosspay_sss, $employee_todate_data["sss_preme"]);
          if (number_format($sss_prem, 2, '.', '') > number_format(0, 2)) {
            $benefits->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":ded_no" => $deduction_data["deduction_no"], ":ded_amt" => $sss_prem, ":ded_todate" => $sss_prem));
          }
        } elseif (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(107, 0, '.', '') and $master_id_data["compute_pagibig"]) {
          $pagibig_prem = compute_pagibig($grosspay_sss, $employee_todate_data["pagibig_mtd"], $master_id_data["max_pagibig_prem"]);
          if (number_format($pagibig_prem, 2, '.', '') > number_format(0, 2)) {
            $benefits->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":ded_no" => $deduction_data["deduction_no"], ":ded_amt" => $pagibig_prem, ":ded_todate" => $pagibig_prem));
          }
        } elseif (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(207, 0, '.', '') and $master_id_data["compute_philhealth"]) {
          $phil_prem = compute_phil($grosspay_phil, $employee_todate_data["phil_preme"]);
          if (number_format($phil_prem, 2, '.', '') > number_format(0, 2)) {
            $benefits->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":ded_no" => $deduction_data["deduction_no"], ":ded_amt" => $phil_prem, ":ded_todate" => $phil_prem));
          }
        } elseif (number_format($deduction_data["deduction_no"], 0, '.', '') == number_format(307, 0, '.', '') and $master_id_data["compute_tax"] and number_format($grosspay_tax, 2, '.', '') > number_format(0, 2)) {
          $tax_amount = compute_tax($grosspay_tax, $master_id_data["tax_code"]);
          if (number_format($tax_amount, 2, '.', '') > number_format(0, 2)) {
            $benefits->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":ded_no" => $deduction_data["deduction_no"], ":ded_amt" => $tax_amount, ":ded_todate" => $tax_amount));
          }
        }
      }
    $deduction_amount = $db->prepare("SELECT SUM(`deduction_amount`) AS `deduction_amount` FROM $db_hris.`payroll_trans_ded` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date");
    $deduction_amount->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date));
    $deduction_amount_data = $deduction_amount->fetch(PDO::FETCH_ASSOC);
    $total_deduction = number_format($deduction_amount_data["deduction_amount"], 2, '.', '');
    $actual_deduction = 0;
    if (number_format($total_deduction, 2, '.', '') > number_format($grosspay, 2, '.', '')) {
      $dist_amount = $grosspay;
      $payroll_trans_ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded`, $db_hris.`deduction` WHERE `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` AND `employee_no`=:emp_no AND `payroll_date`=:pay_date ORDER BY `deduction`.`priority`");
      $payroll_trans_ded->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date));
      if ($payroll_trans_ded->rowCount()) {
        while ($payroll_trans_ded_data = $payroll_trans_ded->fetch(PDO::FETCH_ASSOC)) {
          if (number_format($dist_amount, 2, '.', '') > number_format($payroll_trans_ded_data["deduction_amount"], 2, '.', '')) {
            $dist_amount -= $payroll_trans_ded_data["deduction_amount"];
            $actual_deduction += $payroll_trans_ded_data["deduction_amount"];
            $ptd = $db->prepare("UPDATE $db_hris.`payroll_trans_ded` SET `deduction_actual`=`deduction_amount` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date AND `deduction_no`=:ded_no");
            $ptd->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":ded_no" => $payroll_trans_ded_data["deduction_no"]));
          } else {
            $actual_deduction += $dist_amount;
            $ptd1 = $db->prepare("UPDATE $db_hris.`payroll_trans_ded` SET `deduction_actual`=:amt WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date AND `deduction_no`=:ded_no");
            $ptd1->execute(array(":amt" => $dist_amount, ":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":ded_no" => $payroll_trans_ded_data["deduction_no"]));
            $dist_amount = 0;
          }
        }
      }
    } else {
      $actual_deduction = $total_deduction;
      $u = $db->prepare("UPDATE $db_hris.`payroll_trans_ded` SET `deduction_actual`=`deduction_amount` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date");
      $u->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date));
    }
    $netpay = $grosspay - $actual_deduction;
    if (number_format($netpay, 2, '.', '') > number_format(0, 2) and number_format($change, 2) > number_format(0, 2)) {
      $change_amount = substr(number_format($netpay, 2, '.', ''), -5);
      if (number_format($change_amount, 2, '.', '') > 0) {
        $ins_ded = $db->prepare("INSERT INTO $db_hris.`payroll_trans_ded` (`employee_no`, `payroll_date`, `deduction_no`, `deduction_amount`, `deduction_todate`, `deduction_actual`) VALUES (:emp_no, :pay_date, :ded_no, :ded_amt, :ded_todate, :ded_actual)");
        $ins_ded->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":ded_no" => $change, ":ded_amt" => $change_amount, ":ded_todate" => $change_amount, ":ded_actual" => $change_amount));
        $actual_deduction += $change_amount;
        $netpay = $grosspay - $actual_deduction;
      }
    }
    $trans = $db->prepare("INSERT INTO $db_hris.`payroll_trans` (`employee_no`, `payroll_date`, `payroll_group_no`, `gross_pay`, `deduction`, `net_pay`, `grosspay_sss`, `grosspay_tax`, `grosspay_pagibig`, `grosspay_philhealth`) VALUES (:emp_no, :pay_date, :group, :gross, :actual_ded, :net, :sss, :tax, :love, :phil)");
    $trans->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date, ":group" => $master_data["group_no"], ":gross" => $grosspay, ":actual_ded" => $actual_deduction, ":net" => $netpay, ":sss" => $grosspay_sss, ":tax" => $grosspay_tax, ":love" => $grosspay_pagibig, ":phil" => $grosspay_phil));
    $data = 1;
  }else{
    $data = 0;
  }
  return $data;
}

function compute_sss($grosspay, $preme){
  global $db, $db_hris;

  $grosspay = number_format($grosspay, 2, '.', '');
  $table_sss = $db->prepare("SELECT * FROM $db_hris.`table_sss` WHERE `pay_from`<=:gross AND `pay_to`>=:gross ORDER BY `bracket` LIMIT 1");
  $table_sss->execute(array(":gross" => $grosspay));
  if ($table_sss->rowCount()) {
    $table_sss_data = $table_sss->fetch(PDO::FETCH_ASSOC);
  } else {
    $table_sss = $db->prepare("SELECT * FROM $db_hris.`table_sss` ORDER BY `bracket` LIMIT 1");
    $table_sss->execute();
    if ($table_sss->rowCount()) {
      $table_sss_data = $table_sss->fetch(PDO::FETCH_ASSOC);
    }
  }
  if (number_format($preme, 2, '.', '') >= number_format($table_sss_data["share_employee"], 2, '.', '')) {
    $premium = 0;
  } else {
    $premium = $table_sss_data["share_employee"] - $preme;
  }
  return $premium;
}

function compute_pagibig($grosspay, $preme, $max_premium){

  $premium = number_format($grosspay * 0.02, 2, '.', '');
  if (number_format($premium, 2, '.', '') > number_format($max_premium, 2, '.', '')) {
    $premium = $max_premium;
  }else{
    $premium = $max_premium;
  }
  if (number_format($preme, 2, '.', '') >= number_format($premium, 2, '.', '')) {
    $premium = 0;
  } else {
    $premium -= $preme;
  }
  return $premium;
}

function compute_phil($grosspay, $preme){
  if (number_format($grosspay, 2, '.', '') <= number_format(10000, 2, '.', '')) {
    $premium = number_format(200, 2, '.', '');
  } else {
    $premium = number_format($grosspay * 0.02, 2, '.', '');
  }
  return $premium;
}

function compute_tax($grosspay, $tax_code){
  global $db, $db_hris;

  $tax_amount = 0;
  $tax_code = "01" . substr($tax_code + 10000, -2);
  $table_tax = $db->prepare("SELECT * FROM $db_hris.`table_tax` WHERE `tax_code`=:tax ORDER BY `table_no`");
  $table_tax->execute(array(":tax" => $tax_code));
  if ($table_tax->rowCount()) {
    while ($table_tax_data = $table_tax->fetch(PDO::FETCH_ASSOC)) {
      if (number_format($table_tax_data["taxable_amount_from"], 2, '.', '') <= number_format($grosspay, 2, '.', '') and number_format($table_tax_data["taxable_amount_to"], 2, '.', '') >= number_format($grosspay, 2, '.', '')) {
        $tax_amount = number_format($table_tax_data["fixed_amount"] + ($grosspay - $table_tax_data["taxable_amount_from"]) * $table_tax_data["percent_amount"] / 100, 2, '.', '');
        break;
      }
    }
  }
  return $tax_amount;
}