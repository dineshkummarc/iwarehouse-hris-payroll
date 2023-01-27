<?php
$program_code = 1;
require_once('../common/functions.php');

$imageData = addslashes(file_get_contents($_FILES['profile']['tmp_name']));
$imageType = $_FILES['profile']['type'];
$pin = $_POST['emp_profile'];

if(substr($imageType,0,5) == "image"){
    mysqli_query($con, "UPDATE `master_data` SET `id_picture`='$imageData' WHERE `pin`='$pin'") or die(mysqli_error($con));
    echo "Success";
}else{
    echo "Only images are allowed!";
}