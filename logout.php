<?php
session_start();
include('system.config.php');
include("common_functions.php");
$cfn = new common_functions();
$prog_open = "LOG-OUT";
$cfn->log_activity($prog_open);
session_unset();
session_destroy();
header("Location:../iWarehouse");
?>