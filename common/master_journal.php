<?php

function master_journal($change_from, $change_to, $reference, $remark, $employee_no){
    global $con, $db_hris;

    $user_id = $_SESSION['name'];
    $station_id =  $_SERVER['REMOTE_ADDR'];
    $date=date("Y-m-d H:i:s");

    mysqli_query($con, "INSERT INTO $db_hris.`master_journal` (`employee_no`, `reference`, `change_from`, `change_to`, `remarks`, `user_id`, `station_id`) VALUES ('$employee_no', '$reference', '$change_from', '$change_to', '$remark', '$user_id', '$station_id')") or die(mysqli_error($con));

    if(@mysqli_num_rows(mysqli_query($con, "SELECT * FROM $db_hris.`master_journal` WHERE `employee_no`='$employee_no' AND `reference` LIKE '$reference' AND `change_from` LIKE '$change_from' AND `change_to` LIKE '$change_to' AND `remarks` LIKE '$remark' AND `user_id` LIKE '$user_id' AND `station_id` LIKE '$station_id' AND `time_stamp` >='$date'"))) return "1"; else return "0";
}
?>