<?php

$program_code = 3;
require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "get-swipe-data": //get data of swipe transaction
        $memo_no = $_POST["memo_no"];
        get_swipe_data($memo_no);
    break;
    case "add-update-swipe-data": //adding new swipe data
        $memo_no = $_POST["memo_no"];
        $desc = $_POST["swipe_desc"];
        $amount = $_POST["penalty"];
        $penalty_to = $_POST["to"];
        $penalized = $_POST["penalized"];
        $update_time = $_POST["update_time"];
        save_swipe_data($memo_no,$desc,$amount,$penalty_to,$penalized,$update_time);
    break;
}

//adding new swipe data
function save_swipe_data($memo_no,$desc,$amount,$penalty_to,$penalized,$update_time) {
    global $db, $db_hris;

    $check_swipe = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:memo_no");
    $check_swipe->execute(array(":memo_no" => $memo_no));
    if ($check_swipe->rowCount()){
        $update_swipe = $db->prepare("UPDATE $db_hris.`swipe_memo_code` SET `description`=:desc, `penalty_amount`=:amount, `penalty_to`=:to, `user_id`=:uid, `station_id`=:ip, `is_penalized`=:penalized, `is_update_time`=:update_time WHERE `swipe_memo_code`=:memo_no");
        $update_swipe->execute(array(":memo_no" => $memo_no, ":desc" => $desc, ":amount" => $amount, ":to" => $penalty_to, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":penalized" => $penalized, ":update_time" => $update_time));

        echo json_encode(array("status" => "success"));

    }else{
        $new_swipe = $db->prepare("INSERT INTO $db_hris.`swipe_memo_code`(`description`, `penalty_amount`, `penalty_to`, `user_id`, `station_id`, `is_penalized`, `is_update_time`) VALUES (:desc, :amount, :to, :uid, :ip, :penalized, :update_time)");
        $new_swipe->execute(array(":memo_no" => $memo_no, ":desc" => $desc, ":amount" => $amount, ":to" => $penalty_to, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":penalized" => $penalized, ":update_time" => $update_time));

        echo json_encode(array("status" => "success"));
    }
}

//get data of swipe transaction
function get_swipe_data($memo_no) {
    global $db, $db_hris;

    $swipe_memo = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:memo_no");
    $swipe_memo->execute(array(":memo_no" => $memo_no));
    if ($swipe_memo->rowCount()) {
        $swipe_memo_data = $swipe_memo->fetch(PDO::FETCH_ASSOC);

        $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:no");
        $deduction->execute(array(":no" => $swipe_memo_data["penalty_to"]));
        if ($deduction->rowCount()) {
            $data = $deduction->fetch(PDO::FETCH_ASSOC);
            $desc = $swipe_memo_data["description"];
            $penalty_amt = $swipe_memo_data["penalty_amount"];
            $penalty_to = $data["deduction_no"];
            $penalized = $swipe_memo_data["is_penalized"];
            $update = $swipe_memo_data["is_update_time"];
        }else{
            $desc = $swipe_memo_data["description"];
            $penalty_amt = $swipe_memo_data["penalty_amount"];
            $penalty_to = $swipe_memo_data["penalty_to"];
            $penalized = $swipe_memo_data["is_penalized"];
            $update = $swipe_memo_data["is_update_time"];
        }
    }
    echo json_encode(array("status" => "success", "swipe_desc" => $desc, "penalty_amt" => $penalty_amt, "penalty_to" => $penalty_to, "penalized" => $penalized, "update" => $update));       
}