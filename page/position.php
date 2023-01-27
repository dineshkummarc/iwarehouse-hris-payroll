<?php 

$program_code = 2;
require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "get-record":
    	$sql = mysqli_query($con,"SELECT * FROM position order by position_no ASC") or die (mysqli_error($con));
			while ($row=mysqli_fetch_array($sql)){
				$pos_id = $row["position_no"];
				$pos_name = $row["position_description"];

				echo '<div class="w3-border-bottom w3-padding jdclass w3-hover-silver" style="cursor: pointer;" id="jd'.$pos_id.'" onclick="get_jd('.$pos_id.')">'.$pos_name.'</div>';
			}
    	break;
    case "get-jd":
    	$id = $_POST["id"];
    	$jd = mysqli_query($con,"SELECT * FROM position WHERE position_no='$id'") or die (mysqli_error($con));
			while ($row=mysqli_fetch_array($jd)){
				$job_desc = $row["job_desc"];
				$pos_id = $row["position_no"];

				echo '<span class="w3-center" id="job_div">JOB DESCRIPTION</span>
						<div class="w3-margin-top" id="job_div1">
							<textarea class="w3-padding w3-round-medium" style="width: 100%; height: 200px; resize: none" id="jobd" readonly>'.$job_desc.'</textarea>
							<div class="w3-padding w3-right">
								<button class="w3-border w3-button w3-padding w3-green w3-round-medium" style="cursor: pointer;" id="'.$pos_id.'" onclick="edit_pos('.$pos_id.')">EDIT</button>
								<button class="w3-border w3-button w3-padding w3-red w3-round-medium" style="cursor: pointer;" id="'.$pos_id.'" onclick="del_pos('.$pos_id.')">DELETE</button>
							</div>
						</div>';
			}
    	break;
    case "save-data":
    	$pos_name = $_POST["pos_name"];
    	$jobd = $_POST["jobd"];
        save_pos($pos_name,$jobd);
        break;
    case "get-pos-data":
    	$pos_id = $_POST["id"];
    	get_pos_data($pos_id);
    	break;
    case "update-position":
    	$pos_id = $_POST["pos_id"];
    	$pos_name = $_POST["pos_name"];
    	$job_desc = $_POST["job_desc"];
        update_position($pos_id,$pos_name,$job_desc);
        break;
    case "del-pos":
    	$pos_id = $_POST["pos_id"];
    	del_pos($pos_id);
    	break;
}

function save_pos($pos_name,$jobd) {
    global $db, $db_hris;

    $user_id = $_SESSION['name'];

    $pos_maint = $db->prepare("INSERT INTO $db_hris.`position`(`position_description`, `job_desc`, `user_id`) VALUES (:pos_desc, :jdesc, :uid)");

    $pos_maint->execute(array(":pos_desc" => $pos_name, ":jdesc" => $jobd, ":uid" => $user_id));

    echo json_encode(array("status" => "success"));
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

	$update_pos = $db->prepare("UPDATE `position` SET `position_description`=:pos, `job_desc`=:jdesc, `user_id`=:uid WHERE `position_no`=:pos_no");

	$update_pos->execute(array(":pos_no" => $pos_id, ":pos" => $pos_name, ":jdesc" => $job_desc, ":uid"=> $user_name));

	echo json_encode(array("status" => "success"));

}

function del_pos($pos_id) {
    global $db, $db_hris;

    $del = $db->prepare("DELETE FROM $db_hris.`position` WHERE `position_no`=:no");
    $del->execute(array(":no" => $pos_id));
    
    echo json_encode(array("status" => "success"));
}