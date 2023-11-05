<?php

$program_code = 16;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');

$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
  if ($level <= $plevel) {
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
        case "get-payroll-account": //ok
          if ($access_rights === "A+E+D+B+P+") {
            get_payroll_account(array("store" => $_POST["store"], "group" => $_POST["group"], "pay_date" => $cfn->datefromtable($_POST["date"]), "rights" => $access_rights));
          } else {
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
          }
          break;
        case "make-default": //ok
          $store = get_store();
          $group = get_group();
          $dates = get_dates();
          echo json_encode(array("status" => "success", "store_list" => $store, "group_list" => $group, "date_list" => $dates));
          break;
        case "export":
          if ($access_rights === "A+E+D+B+P+") {
            payroll_account(array("store" => $_GET["store"], "group" => $_GET["group"], "pay_date" => $cfn->datefromtable($_GET["date"])));
          } else {
            echo json_encode(array("status" => "error", "message" => "No Access Rights", "rights" => $access_rights));
            return;
          }
          break;
        case "payslip":
          if ($access_rights === "A+E+D+B+P+") {
            if ($_GET["token"] === $_SESSION["security_key"]) {
              payslip(array("store" => $_GET["store"], "group" => $_GET["pay_group"], "pay_date" => $cfn->datefromtable($_GET["date"])));
            } else {
              echo json_encode(array("status" => "error", "message" => "Invalid token! Please login again!"));
            }
          } else {
            echo json_encode(array("status" => "error", "message" => "No Access Rights", "rights" => $access_rights));
            return;
          }
          break;
          case "post-payroll":
            if ($access_rights === "A+E+D+B+P+") {
              post_payroll(array("store" => $_POST["store"], "group" => $_POST["group"]));
            } else {
              echo json_encode(array("status" => "error", "message" => "No Access Rights", "rights" => $access_rights));
              return;
            }
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

function get_group_header($record)
{
  $items = array();
  $items[] = array("span" => 3, "caption" => "");
  $items[] = array("span" => $record["earnings"] + 1, "caption" => "<b>PAYROLL EARNINGS</b>");
  $items[] = array("span" => $record["deductions"] + 1, "caption" => "<b>PAYROLL DEDUCTIONS</b>");
  $items[] = array("span" => 1, "caption" => "");
  return $items;
}

function get_columns($record)
{
  global $db, $db_hris;

  $items = array();
  $items[] = array("field" => "recid", "caption" => "<b>PIN</b>", "size" => "80px", "frozen" => true);
  $items[] = array("field" => "name", "caption" => "<b>NAME</b>", "size" => "200px", "frozen" => true);
  $items[] = array("field" => "days", "caption" => "<b>No. of Days</b>", "size" => "100px", "frozen" => true, "attr" => "align=center");

  $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_date`=:pay_date AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` LIMIT 1) ORDER BY `payroll_type_no`");
  $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_ded`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_ded`.`payroll_date`=:pay_date AND `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` LIMIT 1) ORDER BY `deduction_no`");
  $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:group_no");
  $payroll_group->execute(array(":group_no" => $record["group"]));
  if ($payroll_group->rowCount()) {
    set_time_limit(300);
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
    $payroll_type->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
    while ($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)) {
      $pay_type = $payroll_type_data["pay_type"];
      $size = strlen($pay_type) > 6 ? "100px" : "80px";
      $items[] = array("field" => $payroll_type_data["payroll_type_no"] . "1", "caption" => "<b>$pay_type</b>", "size" => $size, "attr" => "align=right", "render" => "float:2");
    }
    $items[] = array("field" => "gross", "caption" => "<b>GROSS PAY</b>", "size" => "100px", "attr" => "align=right", "render" => "float:2");
    $deduction->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
    while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
      $label = $deduction_data["deduction_label"];
      $size = strlen($label) > 8 ? "150px" : "90px";
      $items[] = array("field" => $deduction_data["deduction_no"], "caption" => "<b>$label</b>", "size" => $size, "attr" => "align=right", "render" => "float:2");
    }
  }
  $items[] = array("field" => "total_ded", "caption" => "<b>TOTAL DEDUCTION</b>", "size" => "150px", "attr" => "align=right", "render" => "float:2");
  $items[] = array("field" => "net", "caption" => "<b>NET PAY</b>", "size" => "100px", "attr" => "align=right", "render" => "float:2");
  return $items;
}

function get_payroll_account($data)
{
  global $db, $db_hris;

  $today = date("F j, Y");
  $canPrint = substr($data["rights"], 8, 2) === "P+" ? 1 : 0;
  $records = array();
  $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:group_no");
  $payroll_group->execute(array(":group_no" => $data["group"]));
  if ($payroll_group->rowCount()) {
    set_time_limit(300);
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);

    $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_date`=:pay_date AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` LIMIT 1) ORDER BY `payroll_type_no`");

    $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_ded`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_ded`.`payroll_date`=:pay_date AND `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` LIMIT 1) ORDER BY `deduction_no`");

    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` INNER JOIN $db_hris.`master_data` ON `payroll_trans`.`employee_no` = `master_data`.`employee_no` WHERE `payroll_trans`.`payroll_date` = :pay_date AND `payroll_trans`.`payroll_group_no` = :group AND `master_data`.`store` = :store AND `master_data`.`group_no` = `payroll_trans`.`payroll_group_no` ORDER BY `family_name`, `given_name`, `middle_name`");
    $payroll_trans->execute(array(":store" => $data["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $data["pay_date"]));
    if ($payroll_trans->rowCount()) {
      $period = "Payroll Period: " . (new DateTime($payroll_group_data["cutoff_date"]))->format("M. j") . " to " . (new DateTime($payroll_group_data["payroll_date"]))->format("M. j, Y");
      $deduction->execute(array(":store" => $data["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $data["pay_date"]));
      $payroll_type->execute(array(":store" => $data["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $data["pay_date"]));
      $header_group = get_group_header(array("earnings" => $payroll_type->rowCount(), "deductions" => $deduction->rowCount()));
      $columns = get_columns($data);
      $summary = array("w2ui" => array("summary" => true), "recid" => "", "summary" => 1, "gross" => 0, "total_ded" => 0, "net" => 0, "name" => "<span class=\"w3-right\"><b>TOTALS</b></span>");
      while ($payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC)) {
        $record = array();
        $record["recid"] = $payroll_trans_data["pin"];
        $record["name"] = $payroll_trans_data["family_name"] . ", " . $payroll_trans_data["given_name"] . " " . substr($payroll_trans_data["middle_name"], 0, 1);
        $record["days"] = get_no_of_days(array("emp_no" => $payroll_trans_data["employee_no"], "pay_date" => $data["pay_date"]));
        $payroll_type->execute(array(":store" => $data["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $data["pay_date"]));
        while ($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)) {
          $record[$payroll_type_data["payroll_type_no"] . "1"] = get_earnings(array("store" => $data["store"], "pay_date" => $data["pay_date"], "emp_no" => $payroll_trans_data["employee_no"], "pay_type" => $payroll_type_data["payroll_type_no"]));
        }
        $pay_trans_data = get_pay_trans(array("emp_no" => $payroll_trans_data["employee_no"], "group" => $payroll_group_data["group_name"], "pay_date" => $data["pay_date"]));
        $record["gross"] = $pay_trans_data["gross"];
        $deduction->execute(array(":store" => $data["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $data["pay_date"]));
        while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
          $record[$deduction_data["deduction_no"]] = get_deductions(array("store" => $data["store"], "pay_date" => $data["pay_date"], "emp_no" => $payroll_trans_data["employee_no"], "ded_no" => $deduction_data["deduction_no"]));
        }
        $record["total_ded"] = $pay_trans_data["deduction"];
        $record["net"] = $pay_trans_data["net_pay"];
        $summary["gross"] += $record["gross"];
        $summary["net"] += $record["net"];
        $summary["total_ded"] += $record["total_ded"];
        $records[] = $record;
      }
      if (count($records)) {
        $records[] = $summary;
      }
    }
    $xdata = array("status" => "success", "todate" => $today, "trans_date" => $period, "colGroup" => $header_group, "columns" => $columns, "records" => $records, "can_print" => $canPrint, "posted" => check_posted($data["pay_date"],$data["group"]));
  } else {
    $xdata = array("status" => "error", "message" => "Invalid Payroll Date!");
  }
  echo json_encode($xdata);
}

function check_posted($date, $group_no){
  global $db, $db_hris;

  $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` WHERE `payroll_date`=:pay_date AND `payroll_group_no`=:group_no AND `is_posted`");
  $payroll_trans->execute(array(":pay_date" => $date, ":group_no" => $group_no));
  if($payroll_trans->rowCount()){
    $data = 1;
  }else{
    $data = 0;
  }
  return $data;
}

function get_pay_trans($record){
  global $db, $db_hris;

  $data = array("gross" => 0, "deduction" => 0, "net_pay" => 0);
  $payroll_trans_pay = $db->prepare("SELECT SUM(`gross_pay`) AS `gross_pay`, SUM(`deduction`) AS `deduction`, SUM(`net_pay`) AS `net_pay` FROM $db_hris.`payroll_trans` WHERE `payroll_date`=:pay_date AND `employee_no`=:emp_no AND `payroll_group_no`=:group");
  $payroll_trans_pay->execute(array(":emp_no" => $record["emp_no"], ":pay_date" => $record["pay_date"], ":group" => $record["group"]));
  if ($payroll_trans_pay->rowCount() > 0) {
    $payroll_trans_pay_data = $payroll_trans_pay->fetch(PDO::FETCH_ASSOC);
    $data["gross"] = $payroll_trans_pay_data["gross_pay"];
    $data["deduction"] = $payroll_trans_pay_data["deduction"];
    $data["net_pay"] = $payroll_trans_pay_data["net_pay"];
  }
  return $data;
}

function get_no_of_days($record){
  global $db, $db_hris;

  $payroll_trans_pay = $db->prepare("SELECT * FROM $db_hris.payroll_trans_pay WHERE employee_no = :emp_no AND payroll_date = :payroll_date ORDER BY payroll_type_no");
  $payroll_trans_pay->execute(array(":emp_no" => $record["emp_no"], ":payroll_date" => $record["pay_date"]));
  if ($payroll_trans_pay->rowCount() > 0) {
    $payroll_trans_pay_data = $payroll_trans_pay->fetch(PDO::FETCH_ASSOC);
    $credit = $payroll_trans_pay_data["credit"];
    if ($credit >= 8) {
      $days = floor($credit / 8);
      $remainingHours = $credit % 8;
      return ($remainingHours === 0) ? $days : "$days.$remainingHours";
    } else {
      return "0." . number_format($credit, 0);
    }
  }

  return ""; // Return an empty string if no data is found
}

function get_earnings($record)
{
  global $db, $db_hris;

  $amount = 0;
  $payroll_trans_pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`=:store AND `payroll_trans_pay`.`payroll_date`=:pay_date AND `payroll_trans_pay`.`employee_no`=:emp_no AND `payroll_trans_pay`.`payroll_type_no`=:pay_type");
  $payroll_trans_pay->execute(array(":store" => $record["store"], ":pay_date" => $record["pay_date"], ":emp_no" => $record["emp_no"], ":pay_type" => $record["pay_type"]));
  if ($payroll_trans_pay->rowCount()) {
    $payroll_trans_pay_data = $payroll_trans_pay->fetch(PDO::FETCH_ASSOC);
    $amount = $payroll_trans_pay_data["pay_amount"];
  }
  return $amount;
}

function get_deductions($record)
{
  global $db, $db_hris;

  $payroll_trans_ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`=:store AND `payroll_trans_ded`.`employee_no`=:emp_no AND `payroll_trans_ded`.`payroll_date`=:pay_date AND `payroll_trans_ded`.`deduction_no`=:ded_no");
  $payroll_trans_ded->execute(array(":store" => $record["store"], ":pay_date" => $record["pay_date"], ":emp_no" => $record["emp_no"], ":ded_no" => $record["ded_no"]));
  if ($payroll_trans_ded->rowCount()) {
    $payroll_trans_ded_data = $payroll_trans_ded->fetch(PDO::FETCH_ASSOC);
    return $payroll_trans_ded_data["deduction_actual"];
  }
}

function get_store()
{
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

function get_group()
{
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

function get_dates()
{
  global $db, $db_hris;

  $dates = $db->prepare("SELECT `payroll_date` FROM $db_hris.`payroll_trans` GROUP BY `payroll_date` ORDER BY `payroll_date` DESC");
  $date_list = array();
  $dates->execute();
  if ($dates->rowCount()) {
    while ($data = $dates->fetch(PDO::FETCH_ASSOC)) {
      $date_list[] = array("id" => (new DateTime($data["payroll_date"]))->format("m/d/Y"), "text" => (new DateTime($data["payroll_date"]))->format("M d, Y"));
    }
  }
  return $date_list;
}

function payroll_account($record){
  global $db, $db_hris;

  $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group`, $db_hris.`employment_status` WHERE  `payroll_group`.`group_name`=`employment_status`.`employment_status_code` AND `payroll_group`.`group_name`=:group_no");
  $payroll_group->execute(array(":group_no" => $record["group"]));
  if ($payroll_group->rowCount()) {
    set_time_limit(300);
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);

    $payroll_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_pay`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_pay`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_date`=:pay_date AND `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` LIMIT 1) ORDER BY `payroll_type_no`");

    $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE (SELECT COUNT(*) FROM $db_hris.`payroll_trans_ded`,$db_hris.`master_data` WHERE `master_data`.`employee_no`=`payroll_trans_ded`.`employee_no` AND `master_data`.`store`=:store AND `master_data`.`group_no`=:group AND `payroll_trans_ded`.`payroll_date`=:pay_date AND `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` LIMIT 1) ORDER BY `deduction_no`");

    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` INNER JOIN $db_hris.`master_data` ON `payroll_trans`.`employee_no` = `master_data`.`employee_no` WHERE `payroll_trans`.`payroll_date` = :pay_date AND `payroll_trans`.`payroll_group_no` = :group AND `master_data`.`store` = :store AND `master_data`.`group_no` = `payroll_trans`.`payroll_group_no` ORDER BY `family_name`, `given_name`, `middle_name`");
    $payroll_trans->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));

    $store = $db->prepare("SELECT * FROM $db_hris.`store` WHERE `StoreCode`=:store");
    if ($payroll_trans->rowCount()) {
      $store->execute(array(":store" => $record["store"]));
      $store_data = $store->fetch(PDO::FETCH_ASSOC);

      $deduction->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
      $payroll_type->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));

      $pay_type = $payroll_type->rowCount() + 1;
      $pay_ded = $deduction->rowCount() + 1;
      $header_col = $pay_type + $pay_ded + 5;
      $content = '
                <table class="w3-table" border="1">
                    <thead>
                        <tr>
                            <th colspan="' . $header_col . '" style="text-align: center;">' . $store_data["StoreName"] . '</th>
                        </tr>
                        <tr>
                            <th colspan="' . $header_col . '" style="text-align: left;">' . date("F j" . ", " . "Y") . '</th>
                        </tr>
                        <tr>
                            <th colspan="' . $header_col . '" style="text-align: left;">Payroll Period: ' . (new DateTime($payroll_group_data["cutoff_date"]))->format("M. j") . " to " . (new DateTime($payroll_group_data["payroll_date"]))->format("M. j, Y") . '</th>
                        </tr>
                        <tr>
                            <th colspan="' . $header_col . '" style="text-align: left;">Payroll Group: ' . $payroll_group_data["description"] . '</th>
                        </tr>
                        <tr>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th style="width: 80px;"></th>
                            <th colspan="' . $pay_type . '" class="w3-center w3-border">PAYROLL EARNINGS</th>
                            <th colspan="' . $pay_ded . '" class="w3-center w3-border">PAYROLL DEDUCTION</th>
                            <th></th>
                        </tr>
                        <tr>
                            <th></th>
                            <th class="w3-center w3-border">PIN</th>
                            <th class="w3-center w3-border">NAME</th>
                            <th>No. of Days</th>';
      $payroll_type->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
      while ($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)) {
        $content .= '<th>' . $payroll_type_data["pay_type"] . '</th>';
      }
      $content .= '<th class="w3-center w3-border">GROSS PAY</th>';
      $deduction->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
      while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
        $content .= '<th>' . $deduction_data["deduction_label"] . '</th>';
      }
      $content .= '<th class="w3-center w3-border">TOTAL DED</th>';
      $content .= '<th class="w3-center w3-border">NET PAY</th>
                        </tr>
                    </thead>
                <tbody>';
      $cnt = $total_deduction = $total_gross = $total_net = 0;
      while ($payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC)) {
        $no_of_days = get_no_of_days(array("emp_no" => $payroll_trans_data["employee_no"], "pay_date" => $record["pay_date"]));
        $pay_trans_data = get_pay_trans(array("emp_no" => $payroll_trans_data["employee_no"], "group" => $payroll_group_data["group_name"], "pay_date" => $record["pay_date"]));
        $total_net += $pay_trans_data["net_pay"];
        $total_gross += $pay_trans_data["gross"];
        $total_deduction += $pay_trans_data["deduction"];
        $content .= '<tr class="register">
                        <td>' . number_format(++$cnt) . '</td>
                        <td class="w3-center w3-border">' . $payroll_trans_data["pin"] . '</td>
                        <td>' . $payroll_trans_data["family_name"] . ", " . $payroll_trans_data["given_name"] . " " . substr($payroll_trans_data["middle_name"], 0, 1) . '</td>
                        <td>' . $no_of_days . '</td>';
        $payroll_type->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
        while ($payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC)) {
          $earnings = get_earnings(array("store" => $record["store"], "pay_date" => $record["pay_date"], "emp_no" => $payroll_trans_data["employee_no"], "pay_type" => $payroll_type_data["payroll_type_no"]));
          $content .= '<td style="text-align: right;">' . number_format($earnings, 2) . '</td>';
        }
        $content .= '<td style="text-align: right;">' . number_format($pay_trans_data["gross"], 2) . '</td>';
        $deduction->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
        while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
          $ded = get_deductions(array("store" => $record["store"], "pay_date" => $record["pay_date"], "emp_no" => $payroll_trans_data["employee_no"], "ded_no" => $deduction_data["deduction_no"]));
          $content .= '<td style="text-align: right;">' . number_format($ded, 2) . '</td>';
        }
        $content .= '<td style="text-align: right;">' . number_format($pay_trans_data["deduction"], 2) . '</td>
                        <td style="text-align: right;">' . number_format($pay_trans_data["net_pay"], 2) . '</td>
                    </tr>';
      }
      $content .= '</tbody>
                    <tfoot>
                        <tr style="color: #5F9DF7;">
                            <th colspan="4"  style="text-align: right;"">GRAND TOTAL</th>
                            <th style="text-align: right;" colspan="' . $pay_type . '">' . number_format($total_gross, 2) . '</th>
                            <th style="text-align: right;" colspan="' . $pay_ded . '">' . number_format($total_deduction, 2) . '</th>
                            <th style="text-align: right;">' . number_format($total_net, 2) . '</th>
                        </tr>
                    </tfoot>
                </table>';
    }
  }
  $file_name = $store_data["StoreName"] . " PAYROLL ACCOUNT of " . $payroll_group_data["description"] . " as of " . (new DateTime($record["pay_date"]))->format('m-d-Y');
  header('Content-Type: application/xls');
  header('Content-Disposition: attachment; filename=' . strtoupper($file_name) . '.xls');
  echo $content;
}

function payslip($record){
  global $db, $db_hris;

  $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group`, $db_hris.`employment_status` WHERE  `payroll_group`.`group_name`=`employment_status`.`employment_status_code` AND `payroll_group`.`group_name`=:group_no");
  $payroll_group->execute(array(":group_no" => $record["group"]));
  if ($payroll_group->rowCount()) {
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);

    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` INNER JOIN $db_hris.`master_data` ON `payroll_trans`.`employee_no` = `master_data`.`employee_no` WHERE `payroll_trans`.`payroll_date` = :pay_date AND `payroll_trans`.`payroll_group_no` = :group AND `master_data`.`store` = :store AND `master_data`.`group_no` = `payroll_trans`.`payroll_group_no` ORDER BY `family_name`, `given_name`, `middle_name`");
    $payroll_trans->execute(array(":store" => $record["store"], ":group" => $payroll_group_data["group_name"], ":pay_date" => $record["pay_date"]));
    $payslip_report = "";
    $receipt_no = 0;
    if ($payroll_trans->rowCount()) {
      while ($payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC)) {
        $payslip = payroll_payslip($payroll_trans_data["employee_no"], $record["pay_date"], $receipt_no, $record["store"]);
        if ($payslip != "") {
          $payslip_report .= $payslip;
          $receipt_no++;
        }
      }
      if ($payslip_report != "") {
        if ($receipt_no) {
          include_once("./payslip.php");
        }
      }
    }
  }
}

function payroll_payslip($employee_no, $payroll_date, $receipt_no, $store){
  global $db, $db_hris; ?>
  <link rel="stylesheet" type="text/css" href="../../../css/w3-css.css" />
  <style type="text/css">
    .cut {
      border-right: dashed;
      border-color: grey;
      border-width: 1.5px;
    }
    .cut1 {
      border-bottom: dashed;
      border-color: grey;
      border-width: 1.5px;
    }
    .table-container {
    display: flex;
    justify-content: space-between;
  }

  .table-container table {
    width: 50%;
  }

  .table-container .w3-twothird {
    flex: 2;
  }

  .table-container .w3-third {
    flex: 1;
  }
  </style>
<?php
  $current_receipt_no = substr(number_format($receipt_no + 100001, 0, '.', ''), -5);
  $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:emp_no AND `store`=:store");
  $master->execute(array(":emp_no" => $employee_no, ":store" => $store));
  if ($master->rowCount()) {
    $master_data = $master->fetch(PDO::FETCH_ASSOC);
    $employee_rate = $db->prepare("SELECT * FROM $db_hris.`employee_rate` WHERE `employee_no`=:emp_no");
    $employee_rate->execute(array(":emp_no" => $master_data["employee_no"]));
    $employee_rate_data = $employee_rate->fetch(PDO::FETCH_ASSOC);

    $master_id = $db->prepare("SELECT * FROM $db_hris.`master_id` WHERE `employee_no`=:emp_no");
    $master_id->execute(array(":emp_no" => $master_data["employee_no"]));
    $master_id_data = $master_id->fetch(PDO::FETCH_ASSOC);

    $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:group");
    $payroll_group->execute(array(":group" => $master_id_data["pay_group"]));
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);

    $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date");
    $payroll_trans->execute(array(":emp_no" => $master_data["employee_no"], ":pay_date" => $payroll_date));
    $payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC);

    $payroll_trans_pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay`, $db_hris.`payroll_type` WHERE `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_trans_pay`.`employee_no`=:emp_no AND `payroll_trans_pay`.`payroll_date`=:pay_date AND `pay_amount`>0 ORDER BY `payroll_trans_pay`.`payroll_type_no`");
    $payroll_trans_ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded`, $db_hris.`deduction` WHERE `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` AND `payroll_trans_ded`.`employee_no`=:emp_no AND `payroll_trans_ded`.`payroll_date`=:pay_date AND `deduction_actual`>0 ORDER BY `payroll_trans_ded`.`deduction_no`");
    $content = '<div class="w3-col s12 pcont table-container" style="padding-top: 5px;">
                <div class="w3-twothird cut cut1 w3-container">
                  <table style="width: 100%;">
                    <tbody class="w3-col s12">
                      <tr class="w3-col s12">
                        <td style="width: 120px;"><span class="w3-left w3-tiny">PAY RECEIPT '.$current_receipt_no.'</span></td>
                        <td>
                          <table style="width: 100%;">
                            <tr>
                              <td><img src="../../../logo.webp" style="height: 50px;"></td>
                            </tr>
                            <tr>
                              <td class="w3-tiny">GL-20, 888 Chinatown Square, Gatuslao St., Brgy. 8, Bacolod City 6100</td>
                            </tr>
                          </table>
                        </td>
                        <th style="width: 100px;">&nbsp;</th>
                      </tr>
                      <tr class="w3-col s12">
                        <td class="w3-col s12 w3-center" style="padding-top: 10px; padding-bottom: 12px;">
                          <span class="w3-medium" style="font-weight: bolder;">EMPLOYEE PAYSLIP</span>
                        </td>
                      </tr>
                      <tr class="w3-col s12 w3-border-top w3-border-black">
                        <td class="w3-col s2">
                          <span class="w3-tiny">Employee Name:</span>
                        </td>
                        <td class="w3-col s6">
                          <span class="w3-tiny">'.$master_data["family_name"].", ".$master_data["given_name"]." ".substr($master_data["middle_name"], 0, 1).'</span>
                        </td>
                        <td class="w3-col s4">
                          <span class="w3-tiny w3-right w3-margin-right">ID No: <b>' . $master_data["pin"] . '</b></span>
                        </td>
                      </tr>
                      <tr class="w3-col s12">
                        <td class="w3-col s2"><span class="w3-tiny">Payroll Period: </span></td>
                        <td class="w3-col s10"><span class="w3-tiny">'.date("M j", strtotime($payroll_group_data["cutoff_date"])).' - '.date("M j" . ", " . "Y", strtotime($payroll_group_data["payroll_date"])).'</span></td>
                      </tr>
                      <tr class="w3-col s12">
                        <td class="w3-col s2">
                          <span class="w3-tiny">Daily Rate:</span>
                        </td>
                        <td class="w3-col s10">
                          <span class="w3-tiny">'.number_format($employee_rate_data["daily_rate"], 2).'</span>
                        </td>
                      </tr>';
                    if (number_format($employee_rate_data["incentive_cash"], 2) > 0) {
          $content .= '<tr class="w3-col s12">
                        <td class="w3-col s2"><span class="w3-tiny">Cash Incentive:</span></td>
                        <td class="w3-col s10"><span class="w3-tiny">'.number_format($employee_rate_data["incentive_cash"], 2).'</span></td>
                      </tr>';
                      }
          $payroll_trans_pay->execute(array(":emp_no" => $master_data["employee_no"], ":pay_date" => $payroll_date));
          $payroll_trans_ded->execute(array(":emp_no" => $master_data["employee_no"], ":pay_date" => $payroll_date));
          $gross_pay = number_format($payroll_trans_data["gross_pay"], 2);
          $deduction = number_format($payroll_trans_data["deduction"], 2);
          $net_pay =  number_format($payroll_trans_data["net_pay"], 2);
          $content .= '<tr class="w3-col s12 w3-border-top w3-tiny w3-border-black">
                        <td class="w3-col s7 w3-border-right w3-border-black">
                          <div class="w3-border-bottom w3-center w3-border-black"><b>EARNING\'S AND OTHER COMPENSATIONS</b></div>';
                    if ($payroll_trans_pay->rowCount()) {
                      while ($payroll_trans_pay_data =  $payroll_trans_pay->fetch(PDO::FETCH_ASSOC)) {
                        $days = number_format($payroll_trans_pay_data["credit"], 2);
                        if ($payroll_trans_pay_data["pay_type"] == "BASIC PAY") {
                          if ($days >= 8) {
                            $days = floor($payroll_trans_pay_data["credit"] / 8);
                            $remainingHours = $payroll_trans_pay_data["credit"] % 8;
                            $no_days = $remainingHours === "" ? $days . "Days" : $days . "." . $remainingHours . "Days";
                          }else{
                            $no_days = $days." Hrs";
                          }
                        } elseif ($payroll_trans_pay_data["pay_type"] == "INCENTIVE") {
                          if ($days >= 8) {
                            $days = floor($payroll_trans_pay_data["credit"] / 8);
                            $remainingHours = $payroll_trans_pay_data["credit"] % 8;
                            $no_days = $remainingHours === "" ? $days . "Days" : $days . "." . $remainingHours . "Days";
                        }else{
                          $no_days = $days." Hrs";
                          }
                        } elseif ($payroll_trans_pay_data["pay_type"] == "JOB ORDER") {
                          if ($days < 8) {
                            $no_days = number_format($days, 1) . 'Hrs';
                          }elseif ($days > 8){
                            $no_days = number_format($days / 8, 1) . 'Days';
                          } else {
                            $no_days = number_format($days / 8, 1) . 'Day';
                          }
                        } elseif ($payroll_trans_pay_data["pay_type"] == "VACATION") {
                          if ($days < 8) {
                            $no_days = number_format($days, 1) . ' Hrs';
                          } elseif ($days > 8) {
                            $no_days = number_format($days / 8, 1) . 'Days';
                          } else {
                            $no_days = number_format($days / 8, 1) . 'Day';
                          }
                        } else {
                          $no_days = $days;
                        }
          $content .= '<div class="w3-col s6 w3-text-align" style="padding-left: 2px; padding-right: 5px;">
                          <table class="w3-tiny w3-col s12">
                            <tr class="w3-col s12">
                              <td class="w3-col s5">'.$payroll_trans_pay_data["pay_type"].'</td>
                              <td class="w3-col s4">'.$no_days.'</td>
                              <td class="w3-col s3"><span class="w3-tiny">'.number_format($payroll_trans_pay_data["pay_amount"], 2).'</span></td>
                            </tr>
                          </table>
                        </div>';
                    }
                    if($payroll_trans_pay->rowCount() > 2){
                      $a = 4 - $payroll_trans_pay->rowCount();
                      $cnt = $payroll_trans_pay->rowCount() + $a;
                      $content .= str_repeat('<div class="w3-col s6 w3-text-align" style="padding-left: 2px; padding-right: 5px;">
                      <table class="w3-tiny w3-col s12">
                        <tr class="w3-col s12">
                          <td class="w3-col s5 w3-container">&nbsp;</td>
                          <td class="w3-col s4 w3-container">&nbsp;</td>
                          <td class="w3-col s3 w3-container">&nbsp;</td>
                        </tr>
                      </table>
                    </div>', $cnt);
                    }else{
                      $content .= str_repeat('<div class="w3-col s6 w3-text-align" style="padding-left: 2px; padding-right: 5px;">
                      <table class="w3-tiny w3-col s12">
                        <tr class="w3-col s12">
                          <td class="w3-col s5 w3-container">&nbsp;</td>
                          <td class="w3-col s4 w3-container">&nbsp;</td>
                          <td class="w3-col s3 w3-container">&nbsp;</td>
                        </tr>
                      </table>
                    </div>', 4);
                    }
                  }
                  
          
          $content .= '<td class="w3-col s5">
                        <div class="w3-border-bottom w3-center w3-border-black"><b>DEDUCTIONS</b></div>';
                    if ($payroll_trans_ded->rowCount()) {
                      while ($payroll_trans_ded_data =  $payroll_trans_ded->fetch(PDO::FETCH_ASSOC)) {
          $content .= '<div class="w3-col s6 w3-text-align" style="padding-left: 2px; padding-right: 5px;">
                          <table class="w3-tiny w3-col s12">
                            <tr class="w3-col s12">
                              <td class="w3-col s8">'.$payroll_trans_ded_data["deduction_label"].'</td>
                              <td class="w3-col s3">'.number_format($payroll_trans_ded_data["deduction_amount"], 2).'</td>
                            </tr>
                          </table>
                        </div>';
                      }
                    }
        $content .= '</tr>
                    <tr class="w3-col s12 w3-border-top w3-border-bottom w3-tiny w3-border-black">
                      <td class="w3-col s7 w3-border-right w3-border-black"><b>GROSS PAY:<span class="w3-right"  style="padding-right: 5px;">'.$gross_pay.'</span></b></td>
                      <td class="w3-col s5"><b>TOTAL DEDUCTIONS:<span class="w3-right"  style="padding-right: 5px;">'.$deduction.'</span></b></td>
                    </tr>
                  </tbody>
                  <tfoot>
                  <tr class="w3-col s12 w3-tiny" style="padding-top: 10px; padding-bottom: 10px;">
                    <td class="w3-col s12 w3-orange w3-small" style="padding-top: 5px; padding-bottom: 5px;"><b>&nbsp;NET Take Home Pay:</b><span class="w3-right w3-padding-right" style="font-weight: bolder;">&nbsp;'. $net_pay . '</span></td>
                  </tr>
                  </tfoot>
                </table>
              </div>
              <div class="w3-third w3-tiny cut cut1 w3-container">
                <table style="width: 100%;">
                  <tbody class="w3-col s12">
                    <tr class="w3-col s12">
                      <td class="w3-col s12 w3-tiny">
                        <table class="w3-col s12 w3-tiny">
                          <tr class="w3-col s12 w3-tiny">
                            <td class="w3-col s12 w3-tiny"><img src="../../../logo.webp" style="height: 50px;"></td>
                          </tr>
                          <tr class="w3-col s12 w3-tiny">
                            <td class="w3-col s12 w3-tiny w3-center">
                              <span style="font-size: 70%">GL-20, 888 Chinatown Square, Gatuslao St., Brgy. 8, Bacolod City 6100</span>
                              </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr class="w3-col s12" style="padding-top: 10px;">
                      <td class="w3-col s6">
                        <span class="w3-tiny">ID No: <b>' . $master_data["pin"] . '</b></span>
                      </td>
                      <td class="w3-col s6">
                        <span class="w3-left w3-tiny w3-right">PAY RECEIPT '.$current_receipt_no.'</span></td>
                      </td>
                    </tr>
                    <tr class="w3-col s12">
                      <td class="w3-col s12">
                        <span class="w3-tiny">Employee Name: '.$master_data["family_name"].", ".$master_data["given_name"]." ".substr($master_data["middle_name"], 0, 1).'</span>
                      </td>
                    </tr>
                    <tr class="w3-col s12">
                      <td class="w3-col s12">
                        <span class="w3-tiny">Payroll Period : ' . date("M j", strtotime($payroll_group_data["cutoff_date"])) . ' - ' . date("M j" . ", " . "Y", strtotime($payroll_group_data["payroll_date"])) . '</span>
                      </td>
                    </tr>
                    <tr class="w3-col s12" style="padding-top: 10px;">
                      <td class="w3-col s12">
                        <span class="w3-tiny">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;I hereby acknowledge that I am fully satisfied with the computation of my salary base on the number of days I performed for the specified payroll period as such affixing my signature below.</span>
                      </td>
                    </tr>
                    <tr class="w3-col s12" style="padding-top: 10px;">
                      <td class="w3-col s4 w3-tiny">GROSS PAY</td>
                      <td class="w3-col s1 w3-tiny">:</td>
                      <td class="w3-col s4 w3-tiny"><span class="w3-right">'.$gross_pay.'&nbsp;</span></td>
                    </tr>
                    <tr class="w3-col s12">
                      <td class="w3-col s4 w3-tiny">DEDUCTIONS</td>
                      <td class="w3-col s1 w3-tiny">:</td>
                      <td class="w3-col s4 w3-tiny"><span class="w3-right">'.$deduction.'&nbsp;</span></td>
                    </tr>
                    <tr class="w3-col s12">
                      <td class="w3-col s4 w3-tiny"><b>NET PAY</b></td>
                      <td class="w3-col s1 w3-tiny">:</td>
                      <td class="w3-col s4 w3-tiny w3-orange w3-border-top w3-border-black"><b><span class="w3-right">'.$net_pay.'&nbsp;</span></b></td>
                    </tr>
                    <tr class="w3-col s12" style="padding-top: 20px;">
                      <td class="w3-col s12 w3-tiny">Received by: ___________________________________</td>
                    </tr>
                    <tr class="w3-col s12">
                      <td class="w3-col s12 w3-tiny">'.str_repeat('&nbsp;', 20).'SIGNATURE OVER PRINTED NAME</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>';
    return $content;
  }
}

function sysconfig($config_name) {
  global $db, $db_hris;

	$config_name = $config_name;
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

function post_payroll($record){
  global $db, $db_hris;

  $payroll_days = sysconfig("payroll_days");
  
  $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group`, $db_hris.`employment_status` WHERE  `payroll_group`.`group_name`=`employment_status`.`employment_status_code` AND `payroll_group`.`group_name`=:group_no");
  $payroll_group->execute(array(":group_no" => $record["group"]));
  if ($payroll_group->rowCount()) {
    $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
    $log_cutoff = $payroll_group_data["cutoff_date"];
    $payroll_date = $payroll_group_data["payroll_date"];
    $posted = check_posted($payroll_date, $record["group"]);
    if(!$posted){
      $schedule = number_format(substr($payroll_date, -2)) <= number_format(15) ? 1 : 2;
      set_time_limit(300);
      $master = $db->prepare("SELECT * FROM $db_hris.`master_data`, $db_hris.`master_id` WHERE !`is_inactive` AND `master_data`.`store`=:store AND `master_id`.`employee_no`=`master_data`.`employee_no` AND `pay_group`=:group_no AND ((SELECT COUNT(*) FROM $db_hris.`time_credit` WHERE `time_credit`.`employee_no`=`master_data`.`pin` AND `trans_date` >= :cutt_off AND `trans_date` <= :pay_date LIMIT 1))");
      $some_update = 0;
      $master->execute(array(":store" => $record["store"], ":group_no" => $payroll_group_data["group_name"], ":cutt_off" => $log_cutoff, ":pay_date" => $payroll_date));
      if ($master->rowCount()) {
        while ($master_data = $master->fetch(PDO::FETCH_ASSOC)) {
          set_time_limit(300);
          $payroll_trans = $db->prepare("SELECT * FROM $db_hris.`payroll_trans` WHERE `payroll_date`=:pay_date AND `employee_no`=:emp_no AND !`is_posted` LIMIT 1");
          $payroll_trans->execute(array(":emp_no" => $master_data["employee_no"], ":pay_date" => $payroll_date));
          if ($payroll_trans->rowCount()) {
            $payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC);
            $ok = post_payroll_time($master_data["employee_no"], $log_cutoff, $payroll_date);
            if($ok){
              $ok1 = post_payroll_pay($master_data["employee_no"], $payroll_date, $schedule);
              if($ok1){
                $ok2 = post_deduction($master_data["employee_no"], $payroll_date);
                if($ok2){
                  $data = array("status" => "success", "message" => "ok", "e" => $ok2);
                }else{
                  $data = array("status" => "error", "message" => "Error Posting Deductions!", "e" => $ok2);
                }
              }else{
                $data = array("status" => "error", "message" => "Error Posting Payroll Pay!", "e" => $ok1);
              }
            }else{
              $data = array("status" => "error", "message" => "Error Posting Payroll Time!");
            }
            $pay_trans = $db->prepare("UPDATE $db_hris.`payroll_trans` SET `is_posted`=:isPosted, `posted_by`=:uid, `posted_at`=:station, `posted_time`=:posted_date, `bank_account`=:bank_acc  WHERE `payroll_date`=:pay_date AND `employee_no`=:emp_no");
            $pay_trans->execute(array(":isPosted" => 1, ":uid" => $_SESSION["name"], ":station" => $_SERVER["REMOTE_ADDR"], ":posted_date" => date("Y-m-d H:i:s"), ":pay_date" => $payroll_date, ":emp_no" => $master_data["employee_no"], ":bank_acc" => $master_data["bank_account"]));
            if($pay_trans->rowCount()){
              $some_update++;
            }
          }
        }
        if ($some_update) {
          $new_log_cutoff = date('Y-m-d', mktime(0, 0, 0, substr($payroll_date, 5, 2), substr($payroll_date, 8, 2) + 1, substr($payroll_date, 0, 4)));
          if (substr($payroll_date, -2) == substr($payroll_days, 0, 2)){
            $new_payroll_date = date('Y-m-d', mktime(0, 0, 0, substr($payroll_date, 5, 2), substr($payroll_days, -2), substr($payroll_date, 0, 4)));
          }else{
            $new_payroll_date = date('Y-m-d', mktime(0, 0, 0, substr($payroll_date, 5, 2) + 1, substr($payroll_days, 0, 2), substr($payroll_date, 0, 4)));
          }
          $new_set = $db->prepare("UPDATE $db_hris.`payroll_group` SET `cutoff_date`=:new_cutt_off, `payroll_date`=:new_pay_date WHERE `group_name`=:group_no");
          $new_set->execute(array(":new_cutt_off" => $new_log_cutoff, ":new_pay_date" => $new_payroll_date, ":group_no" => $payroll_group_data["group_name"]));
          if($new_set->rowCount()){
            $data = array("status" => "success", "message" => "Payroll Posted for ".$payroll_group_data["description"]."!", "posted" => check_posted($payroll_date, $record["group"]));
          }else{
            $data = array("status" => "error", "message" => "Payroll Already Posted!");
          }
        }else{
          $data = array("status" => "error", "message" => "Payroll Already Posted!");
        }
      }
    }else{
      $data = array("status" => "error", "message" => "Payroll Group already posted!");
    }
  }else{
    $data = array("status" => "error", "message" => "Invalid PayGroup!");
  }
  echo json_encode($data);
}

function post_payroll_time($employee_no, $log_cutoff, $payroll_date) {
  global $db, $db_hris;
  // Set time limit
  try {
    set_time_limit(600);
    $user_id = $_SESSION["name"];
    $station_id = $_SERVER["REMOTE_ADDR"];
    // Delete records from payroll_trans_time
    $deletePayrollTransTime = $db->prepare("DELETE FROM $db_hris.`payroll_trans_time` WHERE `payroll_date` LIKE :payroll_date AND `employee_no` = :employee_no");
    $deletePayrollTransTime->execute(array(':payroll_date' => $payroll_date, ':employee_no' => $employee_no));

    // Insert time credits from time_credit
    $insertTimeCredit = $db->prepare("INSERT INTO $db_hris.`payroll_trans_time` (`payroll_date`, `trans_date`, `employee_no`, `mins_credit`, `user_id`, `station_id`, `is_manual`) 
        SELECT :payroll_date, `time_credit`.`trans_date`, `master_data`.`employee_no`, `time_credit`.`credit_time`, :user_id, :station_id, '0' 
        FROM $db_hris.`time_credit`, $db_hris.`master_data` 
        WHERE `time_credit`.`trans_date` >= :log_cutoff 
        AND `time_credit`.`trans_date` <= :payroll_date 
        AND `master_data`.`employee_no` = :employee_no");
    $insertTimeCredit->execute(array(':payroll_date' => $payroll_date, ':user_id' => $user_id, ':station_id' => $station_id, ':log_cutoff' => $log_cutoff, ':employee_no' => $employee_no));
    if ($insertTimeCredit->rowCount() === false) {
      throw new Exception("Error inserting time credits from time_credit.");
    }

    // Insert time credits from time_credit_ot
    $insertTimeCreditOT = $db->prepare("INSERT INTO $db_hris.`payroll_trans_time` (`payroll_date`, `trans_date`, `employee_no`, `mins_credit`, `user_id`, `station_id`, `is_manual`) 
        SELECT :payroll_date, `time_credit_ot`.`trans_date`, `master_data`.`employee_no`, `time_credit_ot`.`credit_time`, :user_id, :station_id, '0' 
        FROM $db_hris.`time_credit_ot`, $db_hris.`master_data` 
        WHERE `time_credit_ot`.`trans_date` >= :log_cutoff 
        AND `time_credit_ot`.`trans_date` <= :payroll_date 
        AND `time_credit_ot`.`is_approved` 
        AND `master_data`.`employee_no` = :employee_no");
    $insertTimeCreditOT->execute(array(':payroll_date' => $payroll_date,':user_id' => $user_id, ':station_id' => $station_id, ':log_cutoff' => $log_cutoff, ':employee_no' => $employee_no));
    if ($insertTimeCreditOT->rowCount() === false) {
      throw new Exception("Error inserting time credits from time_credit_ot.");
    }

    // Delete records from time_credit_ot
    $deleteTimeCreditOT = $db->prepare("DELETE FROM `time_credit_ot` WHERE `trans_date` >= :log_cutoff AND `trans_date` <= :payroll_date AND !`is_approved`");
    $deleteTimeCreditOT->execute(array(':log_cutoff' => $log_cutoff, ':payroll_date' => $payroll_date));
    if ($deleteTimeCreditOT->rowCount() === false) {
      throw new Exception("Error deleting records from time_credit_ot.");
    }
    return 1; // Success
  } catch (Exception $e) {
      // Handle database errors and return 0 for error
      return $e;
  }

}

function post_deduction($employee_no, $payroll_date) {
  global $db, $db_hris;

  $trans_date = date("Y-m-d");
  $reference = "PAYROLL-" . $payroll_date;
  $payroll_trans_ded = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_ded`, $db_hris.`deduction` WHERE `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` AND `payroll_trans_ded`.`payroll_date`=:pay_date AND `payroll_trans_ded`.`employee_no`=:emp_no");
  $payroll_trans_ded->execute(array(":pay_date" => $payroll_date, ":emp_no" => $employee_no));
  if ($payroll_trans_ded->rowCount()) {
    while ($payroll_trans_ded_data = $payroll_trans_ded->fetch(PDO::FETCH_ASSOC)) {
      if ($payroll_trans_ded_data["is_computed"] AND $payroll_trans_ded_data["schedule"] == "1,2") {
        if (number_format($payroll_trans_ded_data["deduction_no"], 0) == number_format(7, 0)) {
          $benefits = $db->prepare("UPDATE $db_hris.`employee_todate` SET `sss_preme`=:cont WHERE `employee_no`=:emp_no");
        } elseif (number_format($payroll_trans_ded_data["deduction_no"], 0) == number_format(107, 0)) {
          $benefits = $db->prepare("UPDATE $db_hris.`employee_todate` SET `pagibig_mtd`=:cont WHERE `employee_no`=:emp_no");
        } elseif (number_format($payroll_trans_ded_data["deduction_no"], 0) == number_format(207, 0)) {
          $benefits = $db->prepare("UPDATE $db_hris.`employee_todate` SET `pagibig_mtd`=:cont WHERE `employee_no`=:emp_no");
        }
        $benefits->execute(array(":cont" => $payroll_trans_ded_data["deduction_amount"], ":emp_no" => $payroll_trans_ded_data["employee_no"]));
      } elseif (!$payroll_trans_ded_data["is_computed"]) {
        $deduction_transaction = $db->prepare("SELECT * FROM $db_hris.`deduction_transaction` WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
        $employee_deduction = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
        $deduction_transaction->execute(array(":emp_no" => $employee_no, ":ded_no" => $payroll_trans_ded_data["deduction_no"]));
        $employee_deduction->execute(array(":emp_no" => $employee_no, ":ded_no" => $payroll_trans_ded_data["deduction_no"]));
        $amount = 0 - $payroll_trans_ded_data["deduction_amount"];
        if ($employee_deduction->rowcount()) {
          $employee_deduction_data = $employee_deduction->fetch(PDO::FETCH_ASSOC);
          $balance = $employee_deduction_data["deduction_balance"];
        } else {
          $balance = 0;
        }
        $ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_balance`=:bal WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
        $ded_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger` (`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :amount, :bal, :rm, :ref, :uid, :station)");
        if ($deduction_transaction->rowCount()) {
          while ($deduction_transaction_data = $deduction_transaction->fetch(PDO::FETCH_ASSOC)) {
            $amount = 0 - $deduction_transaction_data["payroll_deduction"];
            $balance+=$amount;
            $ded->execute(array(":bal" => $balance, ":emp_no" => $employee_no, ":ded_no" => $payroll_trans_ded_data["deduction_no"]));
            $ded_ledger->execute(array(":emp_no" => $employee_no, ":ded_no" => $payroll_trans_ded_data["deduction_no"], ":date" => $trans_date, ":amount" => $amount, ":bal" => $balance, ":rm" => "Taken from payroll ending $payroll_date", ":ref" => $reference, ":uid" => $_SESSION["name"], ":station" => $_SERVER["REMOTE_ADDR"]));
          }
        }else {
          $balance+=$amount;
          $ded->execute(array(":bal" => $balance, ":emp_no" => $employee_no, ":ded_no" => $payroll_trans_ded_data["deduction_no"]));
          $ded_ledger->execute(array(":emp_no" => $employee_no, ":ded_no" => $payroll_trans_ded_data["deduction_no"], ":date" => $trans_date, ":amount" => $amount, ":bal" => $balance, ":rm" => "Taken from payroll ending $payroll_date", ":ref" => $reference, ":uid" => $_SESSION["name"], ":station" => $_SERVER["REMOTE_ADDR"]));
        }
      }
    }
  }
  $emp_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=:ded_amount WHERE `employee_no`=:emp_no AND `deduction_balance`<=:ded_bal");
  $emp_ded->execute(array(":ded_amount" => 0, ":emp_no" => $employee_no, ":ded_bal" => 0));
  return 1;
}

function post_payroll_pay($employee_no, $payroll_date, $schedule) {
  global $db, $db_hris;

  $grosspay_sss=$grosspay_tax=$grosspay_pagibig=$grosspay_phil=$vl_days=0;
  $payroll_trans_pay = $db->prepare("SELECT * FROM $db_hris.`payroll_trans_pay`, $db_hris.`payroll_type` WHERE `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `employee_no`=:emp_no AND `payroll_trans_pay`.`payroll_date`=:pay_date");
  $payroll_trans_pay->execute(array(":emp_no" => $employee_no, ":pay_date" => $payroll_date));
  if ($payroll_trans_pay->rowCount()) {
    while ($payroll_trans_pay_data = $payroll_trans_pay->fetch(PDO::FETCH_ASSOC)) {
      $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `is_computed`");
      $deduction->execute();
      if ($deduction->rowCount()) {
        while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
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
  $emp_todate = $db->prepare("UPDATE $db_hris.`employee_todate` SET `grosspay_sss`=:sss, `grosspay_tax`=:tax, `grosspay_pagibig`=:love, `grosspay_phil`=:phil, `vl_days`=`vl_days`+:vl WHERE `employee_no`=:emp_no");
  $emp_todate->execute(array(":sss" => $grosspay_sss, ":tax" => $grosspay_tax, ":love" => $grosspay_pagibig, ":phil" => $grosspay_phil, ":vl" => $vl_days, ":emp_no" => $employee_no));
  if($emp_todate->rowCount()){
    return true;
  }else{
    return $emp_todate->errorInfo();
  }
}