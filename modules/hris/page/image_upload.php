<?php
global $db, $db_hris;
$program_code = 1;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$imageData = file_get_contents($_FILES['pic_id']['tmp_name']); // Read the binary data from the uploaded file
$imageType = $_FILES['pic_id']['type'];
$pin = $_POST['emp_profile'];


if (substr($imageType, 0, 5) == "image") {
    $profile = $db->prepare("UPDATE $db_hris.`master_data` SET `id_picture`=:pic WHERE `pin`=:pin");
    $profile->execute(array(":pic" => $imageData, ":pin" => $pin));
    if($profile->rowCount()){
        echo "Success";
    }else{
        echo "Error";
    }
}else{
    echo "Only images are allowed!";
}