<?php

header('Content-type:application/json');

include('../modules/system/system.config.php');

$sql = mysqli_query($con,"SELECT * FROM `master_data`,`store`,`position` where `master_data`.`is_inactive`=0 AND`store`.`StoreCode`=`master_data`.`store` AND `master_data`.`position_no`=`position`.`position_no` ORDER BY `master_data`.`family_name` ASC ") or die (mysqli_error($con));

$rows=array();
while ($row=mysqli_fetch_array($sql)){
	$rows[] = array(
		'recid'=>"100".$row['employee_no'],
		'pin' => $row["pin"],
		'lname'=> $row['family_name'],
		'fname'=> $row['given_name'],
		'mname'=> substr($row['middle_name'], 0, 1),
		'pos'=> $row['position_description'],
		'grp'=> $row["StoreName"]

	);
}
echo json_encode($rows);

?>