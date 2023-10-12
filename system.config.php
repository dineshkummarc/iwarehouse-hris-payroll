<?php

global $db_hris, $db;
date_default_timezone_set('Asia/Manila');

$db_hris = "iwarehouse_hr";
$db_host = "localhost";
$user = "root";
$password = "";

$db = new PDO("mysql:host=$db_host;charset=utf8", $user, $password);
//echo $db->getAttribute(PDO::ATTR_CONNECTION_STATUS);
set_time_limit(0);