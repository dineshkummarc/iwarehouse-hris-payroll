<?php

global $db_hris, $con, $db;

$db_hris = "iWarehouse_hr";
$ip_add="localhost";
$user="root";
$password="";

$con = new mysqli();
$con->connect($ip_add, $user, $password, $db_hris);
$con->set_charset("utf8");

date_default_timezone_set('Asia/Manila');

$db = new PDO("mysql:host=$ip_add;dbname=$db_hris;charset=utf8", $user, $password);

$current_date = date('m/d/Y');

$date = "SELECT * FROM `_sysconfig` WHERE `config_name`='trans date'";
$date_master = mysqli_query($con,$date) or die(mysqli_error($con));
if (mysqli_num_rows($date_master)) {
	while ($data = mysqli_fetch_array($date_master)) {
        set_time_limit(300);
        $date = $data["config_value"];
        if($date != $current_date){
            $update_trans_date = mysqli_query($con, "UPDATE `_sysconfig` SET `config_value`='$current_date' WHERE `config_name`='trans date'");
        }
    }
}

?>