<?php

require_once('../session.php');
require_once('../modules/system/system.config.php');

$check_level = mysqli_query($con, "SELECT `user_level` FROM `_user` where `user_id`='".$session_name."'");
$level = mysqli_fetch_array($check_level);

if($level['user_level'] < $program_code){
    echo "No Access rights!!";
    exit();
}
?>