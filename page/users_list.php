<?php

header('Content-type:application/json');
$program_code = 8;
require_once('../common/functions.php');

$sql = mysqli_query($con,"SELECT * FROM _user WHERE user_level <= '$level[user_level]' AND user_id!='$session_name'") or die (mysqli_error($con));

$rows=array();
while ($row=mysqli_fetch_array($sql)){
	if($row['is_active'] == '1'){
			$active = 'Y';
		}else{
			$active = 'N';
		}
	if(empty($row['user_id'])){
			$user_name = '';
		}else{
			$user_name = $row['user_id'];;
		}

		if($row['is_active'] == '0'){
			$id = '100'.$row['user_no'];
			$uname = '<span class="w3-text-red">'.$user_name.'</span>';
			$name = '<span class="w3-text-red">'.$row['name'].'</span>';
			$acc_id = '<span class="w3-text-red">'.$row['account_id'].'</span>';
			$reg_date = '<span class="w3-text-red">'.$row['registration_date'].'</span>';
			$actv = '<span class="w3-text-red">'.$active.'</span>';
			$lvl = '<span class="w3-text-red">User Level-'.$row['user_level'].'</span>';
			$grant = '<span class="w3-text-red">'.$row['granted_by'].'</span>';
			$_timestamp = '<span class="w3-text-red">'.$row['time_stamp'].'</span>';
			$station = '<span class="w3-text-red">'.$row['station_id'].'</span>';
			$last_login = '<span class="w3-text-red">'.$row['last_login_time'].'</span>';
		}else{
			$id = "100".$row['user_no'];
			$uname = $user_name;
			$name = $row['name'];
			$acc_id = $row['account_id'];
			$reg_date = $row['registration_date'];
			$actv = $active;
			$lvl = "User Level-".$row['user_level'];
			$grant = $row['granted_by'];
			$_timestamp = $row['time_stamp'];
			$station = $row['station_id'];
			$last_login = $row['last_login_time'];
		}
		$rows[] = array(
			'recid'=>$id,
			'uname' => $uname,
			'name'=> $name,
			'acc_id'=> $acc_id,
			'reg_date'=> $reg_date,
			'actv'=> $actv,
			'lvl'=> $lvl,
			'grant'=> $grant,
			'_timestamp'=> $_timestamp,
			'station'=> $station,
			'last_log'=> $last_login

	);
}
echo json_encode($rows);

?>