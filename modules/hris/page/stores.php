<?php 

$program_code = 8;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
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
if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "get-store":
                    get_store();
                break;
                case "get-store-id":
                    $id = $_POST["id"];
                    get_store_data($id);
                break;
                case "save-store":
                    $store_name = $_POST["store_name"];
                    $store_loc = $_POST["store_loc"];
                    if (substr($access_rights, 0, 2) === "A+") {
                        save_store($store_name,$store_loc);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "update-store":
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        $store_id = $_POST["store_id"];
                        $store_name = $_POST["store_name"];
                        $store_loc = $_POST["store_loc"];
                        update_store($store_id,$store_name,$store_loc);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "del-store":
                    if (substr($access_rights, 4, 2) === "D+") {
                        $store_id = $_POST["store_id"];
                        del_store($store_id);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
            }
            $db->commit();
            return false;
        }
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(array("status" => "error", "message" => "database is not ready!", "e" => $e));
        exit();
    }
}

function get_store(){
    global $db, $db_hris;

    $data = "";
    $store = $db->prepare("SELECT * FROM $db_hris.`store` order by `StoreCode` ASC");
    $store->execute();
    $data .= '<table class="w3-table-all w3-hoverable">
                <thead>
                    <tr>
                        <th colspan="3" class="w3-center">Store Lists</th>
                    </tr>
                    <tr>
                        <th>Store Name</th>
                        <th>Store Location</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>';
    if($store->rowCount()){
        while ($store_data = $store->fetch(PDO::FETCH_ASSOC)){
            $store_id = $store_data["StoreCode"];
            $store_name = $store_data["StoreName"];
            $address = $store_data["StoreLocation"];
            $ts = $store_data["TimeStamp"];
            $uid = $store_data["UserID"];
            $StationID = $store_data["StationID"];
            $data .= '<tr class="w3-hover-orange w3-hover-text-white store_list" id="store'.$store_id.'" style="cursor: pointer;" onclick="get_store_data('.$store_id.')">
                        <td>'.$store_name.'</td>
                        <td>'.$address.'</td>
                        <td>'.$uid.' | '.$StationID.'</td>
                    </tr>';
        }
    }
        $data .= '</tbody>
            </table>';
    echo json_encode(array("status" => "success", "data" => $data));
}


//save
function save_store($store_name,$store_loc) {
    global $db, $db_hris;

    $user_id = $_SESSION['name'];
    $ipadd = $_SERVER['REMOTE_ADDR'];
    $pos_maint = $db->prepare("INSERT INTO $db_hris.`store`(`StoreName`, `StoreLocation`, `UserID`, `StationID`) VALUES (:sname, :sloc, :uid, :ip)");
    $pos_maint->execute(array(":sname" => $store_name, ":sloc" => $store_loc, ":uid" => $user_id, ":ip" => $ipadd));
	if($pos_maint->rowCount()){
		echo json_encode(array("status" => "success"));
	}
}

//get data
function get_store_data($id) {
    global $db, $db_hris;

    $get_data = $db->prepare("SELECT * FROM $db_hris.`store` WHERE `StoreCode`=:no");
    $get_data->execute(array(":no" => $id));
    if ($get_data->rowCount()){
        while ($data = $get_data->fetch(PDO::FETCH_ASSOC)) {
            $store_id = $data["StoreCode"];
            $store_name = $data["StoreName"];
            $address = $data["StoreLocation"];
        }
    }
    echo json_encode(array("status" => "success", "store_id" => $store_id, "store_name" => $store_name, "address" => $address));       
}

//update
function update_store($store_id,$store_name,$store_loc) {
	global $db, $db_hris;

	$user_id = $_SESSION['name'];
	$ipadd = $_SERVER['REMOTE_ADDR'];

	$update_stores = $db->prepare("UPDATE $db_hris.`store` SET `StoreName`=:sname, `StoreLocation`=:sloc, `UserID`=:uid, `StationID`=:ip WHERE `StoreCode`=:id");
	$update_stores->execute(array(":id" => $store_id, ":sname" => $store_name, ":sloc" => $store_loc, ":uid"=> $user_id, ":ip"=> $ipadd));
	if($update_stores->rowCount()){
		echo json_encode(array("status" => "success"));
	}
}

//delete
function del_store($store_id) {
    global $db, $db_hris;

    $del = $db->prepare("DELETE FROM $db_hris.`store` WHERE `StoreCode`=:no");
    $del->execute(array(":no" => $store_id));
    if($del->rowCount()){
		echo json_encode(array("status" => "success"));
	}
}