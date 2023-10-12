<?php

$program_code = 22;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$level = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();

if (isset($_REQUEST["cmd"])) {
  try {
    if ($db->beginTransaction()) {
      switch ($_REQUEST["cmd"]) {
        case "get-ot-data":
          if (substr($access_rights, 6, 2) !== "B+") {
            if ($level <= $plevel_ot) {
              echo json_encode(array("status" => "error", "message" => "Higher level required!"));
              return;
            }
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
          } else {
            get_ot_data();
          }
          break;
        case "approve-ot":
          if (substr($access_rights, 0, 2) === "A+") {
            $emp_no = $_POST['emp_no'];
            $trans_date = $_POST['trans_date'];
            $newdate = (new DateTime($trans_date))->format("Y-m-d");
            $trans_time = $_POST['trans_time'];
            approve_ot($emp_no, $newdate, $trans_time);
          } else {
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
          }
          break;
        case "cancel-ot":
          if (substr($access_rights, 4, 2) === "D+") {
            $emp_no = $_POST['emp_no'];
            $trans_date = $_POST['date'];
            $newdate = (new DateTime($trans_date))->format("Y-m-d");
            cancel_ot($emp_no, $newdate);
          } else {
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
          }
          break;
        case "get-overtime";
          if (substr($access_rights, 2, 2) === "E+") {
            $emp_no = $_POST['emp_no'];
            $trans_date = $_POST['trans_date'];
            get_overtime($emp_no, $trans_date);
          } else {
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
          }
          break;
      }
      $db->commit();
      return false;
    }
  } catch (PDOException $e) {
    $db->rollBack();
    echo json_encode(array("status" => "error", "message" => "Database error!", "e" => $e));
    exit();
  }
}


function get_overtime($emp_no, $trans_date){
  global $db, $db_hris;

  $ot_data = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`time_credit_ot` WHERE `time_credit_ot`.`employee_no`=:no AND `time_credit_ot`.`trans_date`=:date AND !`master_data`.`is_inactive` AND `time_credit_ot`.`employee_no`=`master_data`.`pin` AND !`time_credit_ot`.`is_approved`");
  $ot_data->execute(array(":no" => $emp_no, ":date" => $trans_date));
  if ($ot_data->rowCount()) {
    $data = $ot_data->fetch(PDO::FETCH_ASSOC);
    $emp_no = $data['pin'];
    $lname = $data['family_name'];
    $fname = $data['given_name'];
    $mname = $data['middle_name'] != '' ? substr($data['middle_name'], 0, 1) : "";
    $name = $lname . ', ' . $fname . ' ' . $mname;
    $ot_time = $data['credit_time'];
    $trans_date = (new DateTime($data['trans_date']))->format("Y-m-d");
  }
  echo json_encode(array("status" => "success", "emp_no" => $emp_no, "emp_name" => $name, "trans_date" => $trans_date, "trans_time" => $ot_time));
}

function approve_ot($emp_no, $newdate, $trans_time){
  global $db, $db_hris;

  $emp_ot = $db->prepare("UPDATE $db_hris.`time_credit_ot` SET `is_approved`=:approve, `credit_time`=:time WHERE `employee_no`=:no AND `trans_date`=:date");
  $emp_ot->execute(array(":no" => $emp_no, ":date" => $newdate, ":time" => $trans_time, ":approve" => 1));
  if ($emp_ot->rowCount()) {
    echo json_encode(array("status" => "success"));
  }
}

function cancel_ot($emp_no, $newdate){
  global $db, $db_hris;

  $cancel_ot = $db->prepare("UPDATE $db_hris.`time_credit_ot` SET `is_approved`=:approve WHERE `employee_no`=:no AND `trans_date`=:date");
  $cancel_ot->execute(array(":no" => $emp_no, ":date" => $newdate, ":approve" => 0));
  if ($cancel_ot->rowCount()) {
    echo json_encode(array("status" => "success"));
  }
}

function get_ot_data(){
  global $db, $db_hris;

  $records = array();
  $records1 = array();
  $group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:name");
  $group->execute(array(":name" => 100));
  if ($group->rowCount()) {
    $ot = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`time_credit_ot` WHERE !`master_data`.`is_inactive` AND `time_credit_ot`.`employee_no`=`master_data`.`pin` AND !`time_credit_ot`.`is_approved` AND `trans_date` BETWEEN :cuttoff AND :paydate ORDER BY `time_credit_ot`.`trans_date` DESC");
    $approve = $db->prepare("SELECT * FROM $db_hris.`master_data`,$db_hris.`time_credit_ot` WHERE !`master_data`.`is_inactive` AND `time_credit_ot`.`employee_no`=`master_data`.`pin` AND `time_credit_ot`.`is_approved` AND `trans_date` BETWEEN :cuttoff AND :paydate ORDER BY `time_credit_ot`.`trans_date` DESC");
    $pay_group = $group->fetch(PDO::FETCH_ASSOC);
    $ot->execute(array(":cuttoff" =>$pay_group["cutoff_date"], ":paydate" => $pay_group["payroll_date"]));
    if ($ot->rowCount()) {
      while ($ot_data = $ot->fetch(PDO::FETCH_ASSOC)) {
        $recid = uniqid();
        $pin = $ot_data['pin'];
        $lname = $ot_data['family_name'];
        $fname = $ot_data['given_name'];
        $mname = $ot_data['middle_name'] != '' ?substr($ot_data['middle_name'], 0, 1) : '';
        $name = $lname . ', ' . $fname . ' ' . $mname;
        $ot_time = $ot_data['credit_time'];
        $trans_date = (new DateTime($ot_data['trans_date']))->format("Y-m-d");
  
        $record = array("recid" =>uniqid(), "pin" => $pin, "name" => $name, "ot_hrs" => $ot_time, "date" => $trans_date);
        $records[] = $record;
      }
    }
    $approve->execute(array(":cuttoff" =>$pay_group["cutoff_date"], ":paydate" => $pay_group["payroll_date"]));
    if ($approve->rowCount()) {
      while ($approve_data = $approve->fetch(PDO::FETCH_ASSOC)) {
        $recid = uniqid();
        $pin = $approve_data['pin'];
        $lname = $approve_data['family_name'];
        $fname = $approve_data['given_name'];
        $mname = $approve_data['middle_name'] != '' ?substr($approve_data['middle_name'], 0, 1) : '';
        $name = $lname . ', ' . $fname . ' ' . $mname;
        $ot_time = $approve_data['credit_time'];
        $trans_date = (new DateTime($approve_data['trans_date']))->format("Y-m-d");
  
        $record1 = array("recid" => $recid, "pin" => $pin, "name" => $name, "ot_hrs" => $ot_time, "date" => $trans_date);
        $records1[] = $record1;
      }
    }
  }
  echo json_encode(array("status" => "success", "records" => $records, "records_approve" => $records1));
}