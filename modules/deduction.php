<?php

$program_code = 3;
require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "get_ded_data":
        $ded_id = $_POST["ded_id"];
        get_ded_data($ded_id);
    break;
    case "add_ded_data":
        $ded_id = $_POST["ded_id"];
        $ded_name = $_POST["ded_name"];
        $type = $_POST["ded_type"];
        $sched = $_POST["ded_sched"];
        save_deduction($ded_id,$ded_name,$type,$sched);
    break;
    case "new_group":
        $group_name = $_POST["group_name"];
        $payroll_date = $_POST["payroll_date"];
        $cuttoff_date = $_POST["cuttoff_date"];
        new_group($group_name,$payroll_date,$cuttoff_date);
    break;
    case "update_group":
        $pay_group_code = $_POST["pay_group_code"];
        $payroll_date = $_POST["payroll_date"];
        $cuttoff_date = $_POST["cuttoff_date"];
        update_group($pay_group_code,$payroll_date,$cuttoff_date);
    break;
}


function save_deduction($ded_id,$ded_name,$type,$sched) {
    global $db, $db_hris;

    $check_ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
    $check_ded->execute(array(":ded_no" => $ded_id));
    if ($check_ded->rowCount()){
        $update_ded = $db->prepare("UPDATE $db_hris.`deduction` SET `deduction_label`=:label, `deduction_type`=:type, `schedule`=:sched, `user_id`=:uid, `station_id`=:ip WHERE `deduction_no`=:ded_no");
        $update_ded->execute(array(":ded_no" => $ded_id, ":label" => $ded_name, ":type" => $type, ":sched" => $sched, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));

        echo json_encode(array("status" => "success"));

    }else{
        $new_ded = $db->prepare("INSERT INTO $db_hris.`deduction`(`deduction_label`, `deduction_type`, `schedule`, `user_id`, `station_id`, `deduction_description`, `is_computed`, `remittance_code`, `is_inactive`, `priority`, `seq_no`) VALUES (:label, :type, :sched, :uid, :ip, :desc, :computed, :rem_code, :actv, :prio, :seq_no)");

        $new_ded->execute(array(":label" => $ded_name, ":type" => $type, ":sched" => $sched, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":desc" => $ded_name, ":computed" => 0, ":rem_code" => 0000, ":actv" => 0, ":prio" => 0, ":seq_no" => 00000));

        echo json_encode(array("status" => "success"));
    }
}

function get_ded_data($ded_id) {

    global $db, $db_hris;

    $get_ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
    $get_ded->execute(array(":ded_no" => $ded_id));
    if ($get_ded->rowCount()){
        while ($ded_data = $get_ded->fetch(PDO::FETCH_ASSOC)) {
            $ded_no = $ded_data["deduction_no"];
            $ded_name = $ded_data["deduction_label"];
            $ded_type = $ded_data["deduction_type"];
            if($ded_type =='1'){
                $ded_type_id = '1';
                $ded_type_name = 'Invoice';
            }else{
                $ded_type_id = '0';
                $ded_type_name = 'Others';
            }
            $sched = $ded_data["schedule"];
            if($sched == '1'){
                $mid = '1';
                $end = '0';
            }elseif($sched == '2'){
                $mid = '0';
                $end = '2';
            }else{
                $mid = '1';
                $end = '2';
            }
        }
    }
    echo json_encode(array("status" => "success", "ded_id" => $ded_no, "ded_name" => $ded_name, "ded_id" => $ded_type_id, "ded_type" => $ded_type_name, "mid" => $mid, "end" => $end));       
}