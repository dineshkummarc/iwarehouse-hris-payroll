<?php

header('Content-type:application/json');

include('system/system.config.php');

$sql = mysqli_query($con,"SELECT * FROM `_program`,`_program_parent` WHERE `_program`.`program_parent`=`_program_parent`.`parent_no` ORDER BY `_program_parent`.`sequence`,`_program`.`seq` ASC") or die (mysqli_error($con));

$rows=array();
	while ($row=mysqli_fetch_array($sql)){
		if($row['is_active'] == '1'){
			$active = 'Y';
		}else{
			$active = 'N';
		}
		if($row['isAdmin_module'] == '1'){
			$admin = 'Y';
		}else{
			$admin = 'N';
		}

		if($row['is_active'] == '0'){
			$program_code = $row['program_code'];
			$parent = '<span class="w3-text-red">'.$row['parent_name'].'</span>';
			$menu_name = '<span class="w3-text-red">'.$row['menu_name'].'</span>';
			$program_name = '<span class="w3-text-red">'.$row['program_name'].'</span>';
			$user_id = '<span class="w3-text-red">'.$row['user_id'].'</span>';
			$time_stamp = '<span class="w3-text-red">'.$row['time_stamp'].'</span>';
			$station_id = '<span class="w3-text-red">'.$row['station_id'].'</span>';
			$is_active = '<span class="w3-text-red">'.$active.'</span>';
			$program_level = '<span class="w3-text-red">'.$row['program_level'].'</span>';
			$program_icon = '<span class="w3-text-red"><i class="'.$row['program_icon'].'"></i></span>';
			$isAdmin_module = '<span class="w3-text-red">'.$admin.'</span>';
			$function = '<span class="w3-text-red">'.substr($row['function'], 0 , -4).'</span>';
			$seq = '<span class="w3-text-red">'.$row['seq'].'</span>';
		}else{
			$program_code = $row['program_code'];
			$parent = $row['parent_name'];
			$menu_name = $row['menu_name'];
			$program_name = $row['program_name'];
			$user_id = $row['user_id'];
			$time_stamp = $row['time_stamp'];
			$station_id = $row['station_id'];
			$is_active = $active;
			$program_level = $row['program_level'];
			$program_icon = '<i class="'.$row['program_icon'].'"></i>';
			$isAdmin_module = $admin;
			$function = substr($row['function'], 0 , -4);
			$seq = $row['seq'];
		}
		$rows[] = array(
			'recid' => $program_code,
			'parent' => $parent,
			'menu' => $menu_name,
			'prog'=> $program_name,
			'active' => $is_active,
			'level' => $program_level,
			'icon' => $program_icon,
			'is_admin'=> $isAdmin_module,
			'uid' => $user_id,
			'function' => $function,
			'seq' => $seq,
			'_timestamp'=> $time_stamp,
			'station' => $station_id
		);
	}
	echo json_encode($rows);


?>