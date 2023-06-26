<?php 

$program_code = 2;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
    if($level <= $plevel ){
        echo json_encode(array("status" => "error", "message" => "Higher level required!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
switch ($_POST["cmd"]) {
    case "get-record":
		if (substr($access_rights, 6, 2) === "B+") {
			get_pos_record();
		}else{
			echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    		return;
		}
    	break;
    case "get-jd":
    	$id = $_POST["id"];
    	get_job_desc($id);
    	break;
    case "save-data":
		if (substr($access_rights, 0, 2) === "A+") {
			$pos_name = $_POST["pos_name"];
			$jobd = $_POST["jobd"];
			save_pos($pos_name,$jobd);
		}else{
			echo json_encode(array("status" => "error", "message" => "No Access Rights"));
			return;
		}
        break;
    case "get-pos-data":
		if (substr($access_rights, 0, 4) === "A+E+"){
			$pos_id = $_POST["id"];
			get_pos_data($pos_id);
		}else{
			echo json_encode(array("status" => "error", "message" => "No Access Rights"));
			return;
		}
    	break;
    case "update-position":
		if (substr($access_rights, 2, 2) === "E+") { 
			$pos_id = $_POST["pos_id"];
			$pos_name = $_POST["pos_name"];
			$job_desc = $_POST["job_desc"];
			update_position($pos_id,$pos_name,$job_desc);
		}else{
			echo json_encode(array("status" => "error", "message" => "No Access Rights"));
			return;
		}
        break;
    case "del-pos":
		if (substr($access_rights, 4, 2) === "D+") {
			$pos_id = $_POST["pos_id"];
			del_pos($pos_id);
		}else{
			echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    		return;
		}
    	break;
}

function get_job_desc($id){
	global $db, $db_hris;

	$jd = $db->prepare("SELECT * FROM $db_hris.`position` WHERE position_no=:pid");
	$jd->execute(array(":pid" => $id));
	if($jd->rowCount()){
		while ($jd_data = $jd->fetch(PDO::FETCH_ASSOC)) {
			$job_desc = $jd_data["job_desc"];
			$pos_id = $jd_data["position_no"];
			
			echo json_encode(array('status' => 'success', 'data' => '<span class="w3-center" id="job_div">JOB DESCRIPTION</span>
				<div class="w3-margin-top" id="job_div1">
					<textarea class="w3-padding w3-round-medium" style="width: 100%; height: 200px; resize: none" id="jobd" readonly>'.$job_desc.'</textarea>
					<div class="w3-padding w3-right">
						<button class="w3-border w3-button w3-padding w3-green w3-round-medium" style="cursor: pointer;" id="'.$pos_id.'" onclick="edit_pos('.$pos_id.')">EDIT</button>
						<button class="w3-border w3-button w3-padding w3-red w3-round-medium" style="cursor: pointer;" id="'.$pos_id.'" onclick="del_pos('.$pos_id.')">DELETE</button>
					</div>
				</div>'));
		}
	}
}

function get_pos_record(){
	global $db, $db_hris;

	$pos = $db->prepare("SELECT * FROM $db_hris.`position` ORDER BY `position_no` ASC");
	$pos->execute();
	if($pos->rowCount()){
		while ($pos_data = $pos->fetch(PDO::FETCH_ASSOC)) {
			$pos_id = $pos_data["position_no"];
			$pos_name = $pos_data["position_description"];

			echo '<div class="w3-border-bottom w3-padding jdclass w3-hover-silver" style="cursor: pointer;" id="jd'.$pos_id.'" onclick="get_jd('.$pos_id.')">'.$pos_name.'</div>';
		}
	}
}

function save_pos($pos_name,$jobd) {
    global $db, $db_hris;

    $user_id = $_SESSION['name'];
    $pos_maint = $db->prepare("INSERT INTO $db_hris.`position`(`position_description`, `job_desc`, `user_id`) VALUES (:pos_desc, :jdesc, :uid)");
    $pos_maint->execute(array(":pos_desc" => $pos_name, ":jdesc" => $jobd, ":uid" => $user_id));
	if($pos_maint->rowCount()){
		echo json_encode(array("status" => "success"));
	}else{
		echo json_encode(array("status" => "success", "message" => $pos_maint));
	}
}

function get_pos_data($pos_id) {
    global $db, $db_hris;

    $get_pos = $db->prepare("SELECT * FROM $db_hris.`position` WHERE `position_no`=:pos_no");
    $get_pos->execute(array(":pos_no" => $pos_id));
    if ($get_pos->rowCount()){
        while ($pos_data = $get_pos->fetch(PDO::FETCH_ASSOC)) {
            $pos_no = $pos_data["position_no"];
            $pos_desc = $pos_data["position_description"];
            $job_desc = $pos_data["job_desc"];
        }
    }
    echo json_encode(array("status" => "success", "pos_id" => $pos_no, "pos_desc" => $pos_desc, "job_desc" => $job_desc));       
}

function update_position($pos_id,$pos_name,$job_desc) {
	global $db, $db_hris;

	$user_name = $_SESSION['name'];

	$update_pos = $db->prepare("UPDATE $db_hris.`position` SET `position_description`=:pos, `job_desc`=:jdesc, `user_id`=:uid WHERE `position_no`=:pos_no");

	$update_pos->execute(array(":pos_no" => $pos_id, ":pos" => $pos_name, ":jdesc" => $job_desc, ":uid"=> $user_name));

	echo json_encode(array("status" => "success"));

}

function del_pos($pos_id) {
    global $db, $db_hris;

    $del = $db->prepare("DELETE FROM $db_hris.`position` WHERE `position_no`=:no");
    $del->execute(array(":no" => $pos_id));
    
    echo json_encode(array("status" => "success"));
}