<?php

function sysconfig($config_name) {
	global $con;

	$config_name = $config_name;
	$sysconfig = mysqli_query($con,"SELECT `config_value` FROM `_sysconfig` WHERE `config_name` LIKE '$config_name'") or die(mysqli_error($con));
	if (@mysqli_num_rows($sysconfig)) {
		$sysconfig_data = mysqli_fetch_array($sysconfig);
		$config_value = $sysconfig_data['config_value'];
	}
	else
		$config_value = "";
	return $config_value;
}

function save_sysconfig($config_name, $config_value) {
	global $con;

	$config_name = $config_name;
	$sysconfig = mysqli_query($con,"SELECT `config_value` FROM `_sysconfig` WHERE `config_name` LIKE '$config_name'");
	if (!@mysqli_num_rows($sysconfig)) {
		$sysconfig_query = "INSERT INTO `_sysconfig` (`config_value`, `config_name`) VALUES ('$config_value', '$config_name')";
	} else {
		$sysconfig_query = "UPDATE `_sysconfig` SET `config_value`='$config_value' WHERE `config_name` LIKE '$config_name'";
	}
	mysqli_query($con,$sysconfig_query);
}

?>
