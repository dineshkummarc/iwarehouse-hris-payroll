<?php

$program_code = 19;
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
                case "get-default": //ok
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_employee();
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "view-deduction-details": //ok view details
                    if ($access_rights === "A+E+D+B+P+") {
                        view_deduction_details($_POST["recid"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "dm-balance": //dm balance
                    if (substr($access_rights, 2, 4) === "E+D+") {
                        edit_bal(array("empno" => $_POST["emp_no"], "ded_no" => $_POST["ded_no"], "rem" => $_POST["remarks"], "amount" => $_POST["cm_amount"]));
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-employee-deductions": //ok
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_employee_deductions($_POST["recid"], $_POST["option"]);
                    }
                break;
                case "get-deduction-ledger": //ok
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_emp_ledger($_POST["recid"]);
                    }
                break;
                case "new-emp-ded": //ok
                    if (substr($access_rights, 2, 4) === "E+D+") {
                        new_emp_ded($_POST["recid"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-ded-data": //ok
                    if (substr($access_rights, 2, 4) === "E+D+") {
                        get_ded_data($_POST["recid"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "add-update-deductions": //ok
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        $bal = $_POST["record"]["ded_bal"] === "" ? 0 : floatval($_POST["record"]["ded_bal"]);
                        $ded_bal1 = str_replace(array('(', ')', ','), '', $_POST["record"]["ded_bal1"]);
                        $total_bal = floatval($bal) + floatval($ded_bal1);
                        $ded_amount = $_POST["record"]["ded_amt"];
                        if($total_bal < $_POST["record"]["ded_amt"]){
                            echo json_encode(array("status" => "error", "message" => "Invalid Deduction Amount! Balance (".number_format($total_bal, 2).") is less than Deduction Amout (".number_format($ded_amount, 2).")"));
                            return;
                        }else{
                            if($bal < 0){
                                echo json_encode(array("status" => "error", "message" => "Invalid Balance Amount (Negative Amount)!!"));
                                return;
                            }else{
                                add_update_deductions($_POST["record"]);
                            }
                        }
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "show-all-emp-ded": //ok
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        show_all_emp_ded($_POST["option"]);
                    }else{
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
        echo json_encode(array("status" => "error", "message" => "database is not ready!", "e" => $e));
        exit();
    }
}

//col group
function grid_column_group($option) {
    $items = array();
    $items[] = $option ? array("span" => 5, "caption" => "<b>Deduction List</b>") : array("span" => 4, "caption" => "<b>List of Employee w/ Deductions</b>");
    return $items;
}

//grid columns
function grid_column($option) {
    $items = array();
    $items[] = array("field" => "recid", "caption" => "No", "size" => "100px", "hidden" => "true");
    $items[] = $option ? array("field" => "ded", "caption" => "Deduction Name", "size" => "200px") : array("field" => "name", "caption" => "Employee Name", "size" => "300px");
    if($option){
        $items[] = array("field" => "bal", "caption" => "Deduction Balance", "size" => "50%", "attr" => "align=right");
    }
    $items[] = array("field" => "amount", "caption" => "Deduction Amount", "size" => "50%", "attr" => "align=right" );
    return $items;
}

//get employee deductions
function get_employee_deductions($recid, $option){
    global $db, $db_hris;
    
    $records = array();
    $ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE !`is_computed` AND !`is_inactive` AND !`deduction_type` ORDER BY `deduction_description`");
    $ded->execute();
    if ($ded->rowCount()) {
        $emp_ded = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `employee_no`=:no AND `deduction_no`=:dno");
        while ($ded_data = $ded->fetch(PDO::FETCH_ASSOC)) {
            $emp_ded->execute(array(":no" => $recid, ":dno" => $ded_data["deduction_no"]));
            $emp_ded_data = $emp_ded->fetch(PDO::FETCH_ASSOC);
            $record["recid"] = $ded_data["deduction_no"].$recid;
            $record["amount"] = $emp_ded->rowCount() ? number_format($emp_ded_data["deduction_amount"],2) : "";
            $record["bal"] = $emp_ded->rowCount() ? number_format($emp_ded_data["deduction_balance"],2) : "";
            $record["ded"] = $ded_data["deduction_description"];
            array_push($records, $record);
        }
    }
    echo json_encode(array("status" => "success", "col_group" => grid_column_group($option), "columns" => grid_column($option), "records" => $records));
}

function edit_bal($record){
    global $db, $db_hris;

    $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:empno");
    $emp_deductions->execute(array(":ded_no" => $record["ded_no"], ":empno" => $record["empno"]));
    if ($emp_deductions->rowCount()){
        $emp_deductions_data = $emp_deductions->fetch(PDO::FETCH_ASSOC);
        if(floatval($record["amount"]) > floatval($emp_deductions_data["deduction_balance"])){
            echo json_encode(array("status" => "error", "message" => "Invalid DM Amount (DM Amount is higher than the balance)!"));
        }else{
            if(number_format($record["amount"], 2) === number_format($emp_deductions_data["deduction_amount"], 2)){
                $new_ded_amount = $emp_deductions_data["deduction_amount"];
            }else{
                $new_ded_amount = 0;
            }
            $update_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_balance`=`deduction_balance`-:ded_bal, `deduction_amount`=:amount, `user_id`=:uid, `station_id`=:ip WHERE `employee_no`=:empno AND `deduction_no`=:ded_no");
            $update_ded->execute(array(":empno" => $record["empno"], ":ded_bal" => $record["amount"], ":amount" => $new_ded_amount, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $record["ded_no"]));
            if($update_ded->rowCount()){
                $new_bal = $emp_deductions_data['deduction_balance'] - $record["amount"];
                $emp_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger`(`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :ded_amt, :ded_bal, :remark, :ref, :uid, :ip)");
                $emp_ledger->execute(array(":emp_no" => $record["empno"], ":ded_no" => $record["ded_no"], ":date" => date('Y-m-d'), ":ded_amt" => "-".$record["amount"], ":ded_bal" => $new_bal, ":remark" => "Cancel: ".$record["rem"], ":ref" => "DEBIT MEMO", ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
                if($emp_ledger->rowCount()){
                    echo json_encode(array("status" => "success", "record" => $record));
                }else{
                    echo json_encode(array("status" => "error", "e" => $emp_ledger->errorInfo()));
                }
            }else{
                echo json_encode(array("status" => "error", "e" => $update_ded->errorInfo()));
            }
        }
    }
}

function get_ded_data($recid){
    global $db, $db_hris;

    $ded = $db->prepare("SELECT * FROM $db_hris.`employee_deduction`,$db_hris.`deduction` WHERE `deduction`.`deduction_no`=`employee_deduction`.`deduction_no` AND `employee_deduction`.`employee_no`=:eno AND `employee_deduction`.`deduction_no`=:ded_no");
    $ded->execute(array(":eno" => substr($recid, 4), ":ded_no" => substr($recid, 0, 4)));
    if ($ded->rowCount()) {
        $deduction_data = $ded->fetch(PDO::FETCH_ASSOC);
        $empno = $deduction_data['employee_no'];
        $ded_no = $deduction_data['deduction_no'];
        $cm_name = "DM DEDUCTIONS FOR ".$deduction_data['deduction_description'];
        $avail_amount = "AVAILABLE AMOUNT TO DM: ".number_format($deduction_data['deduction_balance'], 2);
    }
    echo json_encode(array("status" => "success", "emp_no" => $empno, "ded_no" => $ded_no, "avail_amount" => $avail_amount, "cm_name" => $cm_name));
}

function show_all_emp_ded($option){
    global $db_hris, $db;
    
    $records = array();
    $employee = $db->prepare("SELECT * FROM $db_hris.`employee_deduction`,$db_hris.`master_data` WHERE `employee_deduction`.`employee_no`=`master_data`.`employee_no` AND `employee_deduction`.`deduction_balance` > 1 GROUP BY `master_data`.`employee_no` ORDER BY `master_data`.`family_name` ASC");
    $employee->execute();
    if($employee->rowCount()) {
        $emp_ded = $db->prepare("SELECT SUM(`deduction_balance`) AS `balance`,`employee_no` FROM $db_hris.`employee_deduction` WHERE `employee_no`=:no");
        while ($emp_data = $employee->fetch(PDO::FETCH_ASSOC)) {
            set_time_limit(60);
            $emp_ded->execute(array(":no" => $emp_data["employee_no"]));
            if($emp_ded->rowCount()) {
                while ($emp_ded_data = $emp_ded->fetch(PDO::FETCH_ASSOC)){
                    $record["recid"] = abs($emp_data['employee_no']);
                    $record["name"] = $emp_data['pin']." ".$emp_data['family_name'].', '.$emp_data['given_name'];
                    $record["amount"] = number_format($emp_ded_data['balance'], 2);
                    array_push($records, $record);
                }
            }
        }
    }
    echo json_encode(array("status" => "success", "col_group" => grid_column_group($option), "columns" => grid_column($option), "records" => $records));
}

//col group
function grid_column_group1() {
    $items = array();
    $items[] = array("span" => 5, "caption" => "Deduction List");
    return $items;
}

//grid columns
function grid_column1() {
    $items = array();
    $items[] = array("field" => "recid", "caption" => "No", "size" => "100px", "hidden" => "true");
    $items[] = array("field" => "ded_name", "caption" => "Deduction Name", "size" => "200px");
    $items[] = array("field" => "ded_bal", "caption" => "Deduction Balance", "size" => "50%", "attr" => "align=right");
    $items[] = array("field" => "ref", "caption" => "Reference", "size" => "50%");
    return $items;
}

function view_deduction_details($recid){
    global $db, $db_hris;
    
    $records = array();
    $emp_ded = $db->prepare("SELECT * FROM $db_hris.`employee_deduction`,$db_hris.`master_data` WHERE `employee_deduction`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`employee_no`=:pin");
    $emp_ded->execute(array(":pin" => $recid));
    if ($emp_ded->rowCount()) {
        $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
        while ($emp_ded_data = $emp_ded->fetch(PDO::FETCH_ASSOC)) {
            $deduction->execute(array(":ded_no" => $emp_ded_data['deduction_no']));
            if($deduction->rowCount()){
                while ($deduction_data = $deduction->fetch(PDO::FETCH_ASSOC)) {
                    $emp_deduction = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:eno AND `deduction_balance`>1");
                    $emp_deduction->execute(array(":ded_no" => $deduction_data['deduction_no'], ":eno" => $emp_ded_data['employee_no']));
                    if($emp_deduction->rowCount()){
                        while ($emp_deduction_data = $emp_deduction->fetch(PDO::FETCH_ASSOC)) {
                            $record["recid"] = $emp_deduction_data['deduction_no'].$emp_deduction_data['employee_no'];
                            $record["ded_name"] = $deduction_data['deduction_description'];
                            $record["ded_bal"] = number_format($emp_deduction_data['deduction_balance'], 2);
                            $record["ref"] = $emp_deduction_data['user_id'].' | '.$emp_deduction_data['time_stamp'];
                            array_push($records, $record);
                        }
                    }
                }
            }
        }
    }
    echo json_encode(array("status" => "success", "col_group" => grid_column_group1(), "columns" => grid_column1(), "records" => $records));
}


function add_update_deductions($record){
    global $db, $db_hris;

    $bal = $record["ded_bal"] === "" ? "0.00" : $record["ded_bal"];
    $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
    $emp_deductions->execute(array(":ded_no" => $record["ded_no"], ":emp_no" => $record["emp_no"]));
    if ($emp_deductions->rowCount()){
        $update_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=:ded_amt, `deduction_balance`=`deduction_balance`+:ded_bal, `user_id`=:uid, `station_id`=:ip WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
        $update_ded->execute(array(":emp_no" => $record["emp_no"], ":ded_amt" => $record["ded_amt"], ":ded_bal" => $bal, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $record["ded_no"]));
        if($update_ded->rowCount()){
            $ok = insert_ledger($record);
            if($ok){
                echo json_encode(array("status" => "success", "record" => $record, "recid" => $record["ded_no"].abs($record["emp_no"])));
            }else{
                echo json_encode(array("status" => "error", "message" => "Sorry, problem in updating the ledger!", "e" => $ok));
            }
        }
    }else{
        $new_ded = $db->prepare("INSERT INTO $db_hris.`employee_deduction`(`employee_no`, `deduction_no`, `deduction_amount`, `deduction_balance`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :ded_amt, :ded_bal, :uid, :ip)");
        $new_ded->execute(array(":emp_no" => $record["emp_no"], ":ded_amt" => $record["ded_amt"], ":ded_bal" => $bal, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $record["ded_no"]));
        if($new_ded->rowCount()){
            $ok = insert_ledger($record);
            if($ok){
                echo json_encode(array("status" => "success", "record" => $record, "recid" => $record["ded_no"].abs($record["emp_no"])));
            }else{
                echo json_encode(array("status" => "error", "message" => "Sorry, problem in updating the ledger!", "e" => $ok));
            }
        }
    }
}

function insert_ledger($record){
    global $db, $db_hris;

    $ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:dno");
    $ded->execute(array(":dno" => $record["ded_no"]));
    if($ded->rowCount()){
        $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
        $ded_bal = $record["ded_bal"] === "" ? "" : "(Add: ".number_format($record["ded_bal"], 2).")";
        $remark = "Changes Deduction of ".$ded_data["deduction_description"].$ded_bal;
        $ref = $ded_data["deduction_description"];

        $emp_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger`(`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :ded_amt, :ded_bal, :remark, :ref, :uid, :ip)");

        $ledger = $db->prepare("SELECT * FROM $db_hris.`employee_deduction_ledger` WHERE `employee_no`=:eno AND `deduction_no`=:ded_no ORDER BY `ledger_no` DESC LIMIT 1");
        $ledger->execute(array(":ded_no" => $record["ded_no"], ":eno" => $record["emp_no"]));
        $bal = $record["ded_bal"] === "" ? "0.00" : $record["ded_bal"];
        if($ledger->rowCount()){
            $leder_data = $ledger->fetch(PDO::FETCH_ASSOC);
            $emp_ledger->execute(array(":emp_no" => $record["emp_no"], ":ded_no" => $record["ded_no"], ":date" => date('Y-m-d'), ":ded_amt" => $record["ded_amt"], ":ded_bal" => $leder_data["balance"]+$bal, ":remark" => $remark, ":ref" => $ref, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
        }else{
            $emp_ledger->execute(array(":emp_no" => $record["emp_no"], ":ded_no" => $record["ded_no"], ":date" => date('Y-m-d'), ":ded_amt" => $record["ded_amt"], ":ded_bal" => $bal, ":remark" => $remark, ":ref" => $ref, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
        }
        if($emp_ledger->rowCount()){
            $data = 1;
        }else{
            $data = $emp_ledger->errorInfo();
        }
    }
    return $data;
}

//col group of ledger
function grid_column_group_ledger($ded_name) {
    $items = array();
    $items[] = array("span" => 10, "caption" => "Deduction Ledger of $ded_name");
    return $items;
}

//grid columns of ledger
function grid_column_ledger() {
    $items = array();
    $items[] = array("field" => "recid", "caption" => "Ledger<br>No", "size" => "55px", "attr" => "align=right");
    $items[] = array("field" => "date", "caption" => "Transaction<br>Date", "size" => "80px", "attr" => "align=center");
    $items[] = array("field" => "amount", "caption" => "Deduction<br>Amount", "size" => "80px", "attr" => "align=right");
    $items[] = array("field" => "bal", "caption" => "Deduction<br>Balance", "size" => "80px", "attr" => "align=right");
    $items[] = array("field" => "rm", "caption" => "Remarks", "size" => "300px");
    $items[] = array("field" => "ref", "caption" => "Reference", "size" => "150px");
    $items[] = array("field" => "uid", "caption" => "User ID", "size" => "80px");
    $items[] = array("field" => "station", "caption" => "Station ID", "size" => "120px");
    return $items;
}

function get_emp_ledger($recid){
    global $db_hris, $db;
    
    $records = array();
    $ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:dno");
    $ded->execute(array(":dno" => substr($recid, 0, 4)));
    if($ded->rowCount()){
        $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
        $col_group = grid_column_group_ledger($ded_data['deduction_description']);
        $col_ledger = grid_column_ledger();
        $emp_ledger = $db->prepare("SELECT * FROM $db_hris.`employee_deduction_ledger` WHERE `employee_no`=:eno AND `deduction_no`=:dno ORDER BY `ledger_no` DESC");
        $emp_ledger->execute(array(":eno" => substr($recid, 4), ":dno" => substr($recid, 0, 4)));
        if ($emp_ledger->rowCount()) {
            while ($ledger_data = $emp_ledger->fetch(PDO::FETCH_ASSOC)) {
                $record["recid"] = abs($ledger_data["ledger_no"]);
                $record["date"] = (new DateTime($ledger_data["trans_date"]))->format("m-d-Y");
                $record["amount"] = number_format($ledger_data["amount"],2);
                $record["bal"] = number_format($ledger_data["balance"],2);
                $record["rm"] = $ledger_data["remark"];
                $record["ref"] = strtoupper($ledger_data["reference"]);
                $record["uid"] = $ledger_data["user_id"];
                $record["station"] = $ledger_data["station_id"];
                $record["w2ui"]["style"] = strpos($ledger_data["remark"], "Cancel") !== false ? "color: red;" : "";
                array_push($records, $record);
            }
        }
        echo json_encode(array("status" => "success", "col_group" => $col_group, "columns" => $col_ledger, "records" => $records, "ded_no" => substr($recid, 0, 4), "eno" => substr($recid, 4)));
    }else{
        echo json_encode(array("status" => "error", "ded_no" => substr($recid, 0, 4), "eno" => substr($recid, 4)));
    }
}


function new_emp_ded($recid) {
    global $db, $db_hris;

    $deductions = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
    $deductions->execute(array(":ded_no" => substr($recid, 0, 4)));
    if ($deductions->rowCount()){
        while ($deductions_data = $deductions->fetch(PDO::FETCH_ASSOC)) {
            $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
            $emp_deductions->execute(array(":ded_no" => substr($recid, 0, 4), ":emp_no" => substr($recid, 4)));
            $emp_deductions_data = $emp_deductions->fetch(PDO::FETCH_ASSOC);
            $emp_no = $emp_deductions->rowCount() ? $emp_deductions_data["employee_no"] : substr($recid, 4);
            $ded_name = $deductions_data["deduction_description"];
            $ded_no = $deductions_data["deduction_no"];
            $ded_amt = $emp_deductions->rowCount() ? number_format($emp_deductions_data["deduction_amount"], 2) : "";
            $ded_bal = $emp_deductions->rowCount() ? "(".number_format($emp_deductions_data["deduction_balance"], 2).")" : "";
        }
    }
    echo json_encode(array("status" => "success", "emp_no" => $emp_no, "ded_no" => $ded_no, "ded_bal" => $ded_bal, "ded_amt" => $ded_amt,"ded_name" => $ded_name));       
}


function get_employee() {
    global $db, $db_hris;

    $emp_list = $db->prepare("SELECT `master_data`.`employee_no`,`master_data`.`pin`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`family_name` FROM $db_hris.`master_data` WHERE !`is_inactive` ORDER BY `master_data`.`family_name`");
    $employee_list = array();
    $emp_list->execute();
    if ($emp_list->rowCount()) {
        while ($emp_data = $emp_list->fetch(PDO::FETCH_ASSOC)) {
            $pin=$emp_data['pin'];
            $lname=$emp_data['family_name'];
            $fname=$emp_data['given_name'];
            $middle_name=$emp_data['middle_name'];
            if($middle_name != ''){
                $mname=substr($emp_data['middle_name'], 0, 1);
            }else{
                $mname='';
            }
            $name = $lname.', '.$fname.' '.$mname.' ('.$pin.')';

            $employee_list[] = array("id" => $emp_data["employee_no"], "text" => $name);
        }
    }
    echo json_encode(array("status" => "success", "employee_list" => $employee_list));
}