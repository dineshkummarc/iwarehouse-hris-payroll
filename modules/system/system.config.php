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