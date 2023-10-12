<?php
//Start session
session_start();

if (!isset($_SESSION['name']) || (trim($_SESSION['name']) == '')){
    if (file_exists("index.php")){
        header("location: index.php");
    }elseif(file_exists("../index.php")){
        header("location: ../index.php");
    }
    exit();
}
$session_name=$_SESSION['name']; 
?>
