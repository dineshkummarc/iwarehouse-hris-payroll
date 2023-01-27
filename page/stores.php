<?php 

$program_code = 3;
require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "get-store":
    	$sql = mysqli_query($con,"SELECT * FROM store order by StoreCode ASC") or die (mysqli_error($con));
		?>
		<table class="w3-table-all w3-hoverable">
			<thead>
				<tr>
					<th colspan="3" class="w3-center">Store List's</th>
				</tr>
				<tr>
					<th>Store Name</th>
					<th>Store Location</th>
					<th>Reference</th>
				</tr>
			</thead>
			<tbody>
			<?php 
			while ($row=mysqli_fetch_array($sql)){
				$store_id = $row["StoreCode"];
				$store_name = $row["StoreName"];
				$address = $row["StoreLocation"];
				$ts = $row["TimeStamp"];
				$uid = $row["UserID"];
				$StationID = $row["StationID"];
			?>
				<tr class="w3-hover-orange store_list" id="store_<?php echo $store_id; ?>" style="cursor: pointer;" onclick="get_store_data(<?php echo $store_id; ?>)">
					<td><?php echo $store_name; ?></td>
					<td><?php echo $address; ?></td>
					<td><?php echo $uid.' | '.$StationID; ?></td>
				</tr>
				<?php 
			} ?>
			</tbody>
		</table>
		<?php
    	break;
    case "get-store-id":
    	$id = $_POST["id"];
    	get_store_data($id);
    	break;
    case "save-store":
    	$store_name = $_POST["store_name"];
    	$store_loc = $_POST["store_loc"];
        save_store($store_name,$store_loc);
        break;
    case "update-store":
    	$store_id = $_POST["store_id"];
    	$store_name = $_POST["store_name"];
    	$store_loc = $_POST["store_loc"];
        update_store($store_id,$store_name,$store_loc);
        break;
    case "del-store":
    	$store_id = $_POST["store_id"];
    	del_store($store_id);
    	break;
}


//save
function save_store($store_name,$store_loc) {
    global $db, $db_hris;

    $user_id = $_SESSION['name'];
    $ipadd = $_SERVER['REMOTE_ADDR'];

    $pos_maint = $db->prepare("INSERT INTO $db_hris.`store`(`StoreName`, `StoreLocation`, `UserID`, `StationID`) VALUES (:sname, :sloc, :uid, :ip)");

    $pos_maint->execute(array(":sname" => $store_name, ":sloc" => $store_loc, ":uid" => $user_id, ":ip" => $ipadd));

    echo json_encode(array("status" => "success"));
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

	$update_stores = $db->prepare("UPDATE `store` SET `StoreName`=:sname, `StoreLocation`=:sloc, `UserID`=:uid, `StationID`=:ip WHERE `StoreCode`=:id");

	$update_stores->execute(array(":id" => $store_id, ":sname" => $store_name, ":sloc" => $store_loc, ":uid"=> $user_id, ":ip"=> $ipadd));

	echo json_encode(array("status" => "success"));

}

//delete
function del_store($store_id) {
    global $db, $db_hris;

    $del = $db->prepare("DELETE FROM $db_hris.`store` WHERE `StoreCode`=:no");
    $del->execute(array(":no" => $store_id));
    
    echo json_encode(array("status" => "success"));
}