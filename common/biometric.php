<?php

include("../function/sysconfig.php");
$ip = sysconfig("bio-ip");

require '../lib/zklibrary.php';

$zk = new ZKLibrary($ip, 4370);
$zk->connect();
$zk->disableDevice();

?>