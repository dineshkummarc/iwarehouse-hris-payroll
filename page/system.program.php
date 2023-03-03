<?php

$program_code = 8;

require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "save-config":
        $menu_name = $_POST["record"]["menu_name"];
        $prog_name = $_POST["record"]["prog_name"];
        $enable = $_POST["record"]["enable"];
        $plevel = $_POST["record"]["plevel"]["text"];
        $icons = $_POST["record"]["icons"];
        $admin_mod = $_POST["record"]["admin_mod"];
        $functions = $_POST["record"]["functions"];
        $sequence = $_POST["record"]["seq"];
        $parent = $_POST["record"]["parent"]["id"];
        if($enable == 'true'){ $ena='1';}else{$ena='0';}
        if($admin_mod == 'true'){ $admin='1';}else{$admin='0';}
        $record = array();
        $record["mname"] = $menu_name;
        $record["pname"] = $prog_name;
        $record["en"] = $ena;
        $record["level"] = $plevel;
        $record["icon"] = $icons;
        $record["isadmin"] = $admin;
        $record["ftn"] = $functions;
        $record["seq"] = $sequence;
        $record["parent"]["id"] = $parent;
        save_prog($record);
    break;
    case "del-prog":
        del_config($_POST["recid"]);
    break;
    case "get-parent":
        get_parent();
    break;
    case "enable-disable":
        enable_module($_POST["recid"]);
    break;
    case "get-all-program":
        $uid = $_POST["id"];
        get_all_program($uid);
    break;
    case "get-backup-data":
        get_backup_data();
    break;
    case "make-backup":
        $user = 'root';
        make_backup($user);
    break;
    case "del-backup-data":
        delete_backup($_POST['recid']);
    break;
    case "get-user-menu":
        $uid = $_POST["id"];
        ?>
        <table class="w3-table-all w3-hoverable">
            <thead>
                <tr>
                    <th class="w3-center">Menu Granted</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $get_user_prog = mysqli_query($con,"SELECT * FROM `_program`,`_user_access`,_program_parent WHERE `_user_access`.`program_code`=`_program`.`program_code` AND `_program_parent`.`parent_no`=`_program`.`program_parent` and `_user_access`.`user_id`='$uid' ORDER BY `_program_parent`.`parent_name`, `_program`.`seq` ASC") or die (mysqli_error($con));
            while ($rows=mysqli_fetch_array($get_user_prog)){
                $prog_code = $rows["program_code"];
                $prog_name = $rows["menu_name"];
            ?>
                <tr id="submenu">
                    <td class="w3-border w3-padding" style="cursor: pointer;" id="<?php echo $prog_code; ?>" onclick="remove(<?php echo $prog_code.','.$uid; ?>)">
                    <?php echo $rows['parent_name']; ?> <i class="fa-solid fa-angle-right"></i> <?php echo $prog_name; ?>
                    </td>
                </tr>
                <?php
            } ?>
            </tbody>
        </table>
        <?php
    break;
    case "disgrant":
        $prog_id = $_POST["prog_id"];
        $user_id = $_POST["user_id"];
        remove_access($prog_id,$user_id);
    break;
    case "grant":
        $prog_id = $_POST["prog_id"];
        $user_id = $_POST["user_id"];
        grant_access($prog_id,$user_id);
    break;
    case "get-default-user-rights":
        get_default_user_rights($session_name);
    break;
}

function get_default_user_rights($session_name){
    global $con; ?>
    <div class="w3-col s12 w3-margin-top w3-margin-bottom w3-padding">
        <div class="w3-panel w3-bottombar w3-padding">
            <span class="w3-medium">User Access Rights</span>
            <button class="w3-button w3-red w3-right w3-round-large" onclick="close_it()">Close</button>
        </div>
    </div>
    <div class="w3-col s12 m4 w3-panel">
        <div class="w3-col s12">
            <div class="w3-col s12 w3-container w3-padding">
                <?php
                $sql = mysqli_query($con,"SELECT * FROM _user WHERE user_id!='$session_name' AND user_level<8 order by user_id ASC") or die (mysqli_error($con));
                ?>
                <table class="w3-table-all w3-hoverable">
                    <thead>
                        <tr>
                            <th class="w3-center">User's List</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    while ($row=mysqli_fetch_array($sql)){
                        $uid = $row["user_no"];
                        $name = $row["name"];
                        ?>
                        <tr>
                            <td class="w3-border w3-padding select<?php echo $uid; ?> deselect" style="cursor: pointer;" id="access<?php echo $uid; ?>" onclick="get_all_user_program(<?php echo $uid; ?>)">
                                <?php echo $name; ?>
                            </td>
                        </tr>
                        <?php
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="w3-col s12 m4 w3-panel">
        <div class="w3-col s12">
            <div class="w3-col s12 w3-container w3-padding">
                <div id="program_list"></div>
        </div>
        </div>
    </div>
    <div class="w3-col s12 m4 w3-panel">
        <div class="w3-col s12 w3-margin-bottom">
            <div class="w3-col s12 w3-container w3-padding">
                <div id="program_given_list"></div>
            </div>
        </div>
    </div>
</div>
<script>

    const src = "page/system.program";

    function get_all_user_program(id){
        $('.deselect').removeClass('w3-red');
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-all-program",
                id : id
            },
            success: function (data){
                $('.select'+id).addClass('w3-red');
                $('#program_list').html(data);
                get_user_menu(id);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function get_user_menu(id){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-user-menu",
                id : id
            },
            success: function (data){
                $('#program_given_list').html(data);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function grant(id,uid){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "grant",
                prog_id : id,
                user_id : uid
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    get_all_user_program(uid);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function remove(id,uid){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "disgrant",
                prog_id : id,
                user_id : uid
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    get_all_user_program(uid);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function close_it(){
        get_default();
    }
</script>
<?php
}

function get_all_program($uid){
    global $con;

    $get_prog=mysqli_query($con, "SELECT * FROM `_program`,`_program_parent` WHERE `_program`.`program_parent`=`_program_parent`.`parent_no` AND !`_program`.`isAdmin_module` AND NOT EXISTS (SELECT null FROM `_user_access` WHERE `_user_access`.`program_code`=`_program`.`program_code` AND `_user_access`.`user_id`='$uid') ORDER BY `_program_parent`.`parent_name`,`_program`.`seq` ASC") or die(mysqli_error($con));
    ?>
    <table class="w3-table-all w3-hoverable">
        <thead>
            <tr>
                <th class="w3-center">List of Available Menu</th>
            </tr>
        </thead>
        <tbody>
        <?php
        while ($rows=mysqli_fetch_array($get_prog)){
            $prog_code = $rows["program_code"];
            $prog_name = $rows["menu_name"];
        ?>
            <tr id="menu">
                <td class="w3-border w3-padding" style="cursor: pointer;" id="<?php echo $prog_code; ?>" data-id="<?php echo $uid; ?>" onclick="grant(<?php echo $prog_code.','.$uid; ?>)">
                    <?php echo $rows['parent_name']; ?> <i class="fa-solid fa-angle-right"></i> <?php echo $prog_name; ?>
                </td>
            </tr>
            <?php 
        } ?>
        </tbody>
    </table>
    <?php
}

function save_prog($record){
    global $db, $db_hris;

    $check_parent = $db->prepare("SELECT * FROM $db_hris.`_program_parent` WHERE `parent_no`=:pname");
    $check_parent->execute(array(":pname" => $record["parent"]["id"]));
    if ($check_parent->rowCount()){
        $parent_data = $check_parent->fetch(PDO::FETCH_ASSOC);

        $config = $db->prepare("INSERT INTO $db_hris.`_program`(`menu_name`, `program_name`, `user_id`, `station_id`, `is_active`, `program_level`, `program_icon`, `isAdmin_module`, `function`, `seq`, `program_parent`) VALUES (:name_menu, :prog, :uid, :host, :actv, :plevel, :icons, :isAdmin, :click, :seqs, :prog_parent)");
        $config->execute(array(":name_menu" => $record["mname"], ":prog" => $record["pname"], ":uid" => $_SESSION['name'], ":host" => $_SERVER['REMOTE_ADDR'], ":actv" => $record["en"], ":plevel" => $record["level"], ":icons" => $record["icon"], ":isAdmin" => $record["isadmin"], ":click" => $record["ftn"], ":seqs" => $record["seq"], ":prog_parent" => $parent_data["parent_no"]));

        echo json_encode(array("status" => "success"));
    }else{
        echo json_encode(array("status" => "error", "message" => 'There was a problem in the server!'));
    }
}


function del_config($recid) {
    global $db, $db_hris;

    $a = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_code`=:no");
    $a->execute(array(":no" => $recid));
    if ($a->rowCount()){
        $a_data = $a->fetch(PDO::FETCH_ASSOC);

        $a = $db->prepare("DELETE FROM $db_hris.`_program` WHERE `program_code`=:no");
        $a->execute(array(":no" => $recid));
        if ($a->rowCount()){
            echo json_encode(array("status" => "success"));
        }
    }
}


function enable_module($recid) {
    global $db, $db_hris;

    $a = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_code`=:no");
    $a->execute(array(":no" => $recid));
    if ($a->rowCount()){
        $a_data = $a->fetch(PDO::FETCH_ASSOC);
        if($a_data["is_active"]){
            $is_enabled = 0;
        }else{
            $is_enabled = 1;
        }
        $a = $db->prepare("UPDATE $db_hris.`_program` SET `is_active`=:endis, `user_id`=:uid, `station_id`=:ip WHERE `program_code`=:no");
        $a->execute(array(":endis" => $is_enabled, ":no" => $recid, ":uid" => $_SESSION["name"], ":ip" => $_SERVER["REMOTE_ADDR"]));
        if ($a->rowCount()){
            echo json_encode(array("status" => "success"));
        }
    }
}

function grant_access($prog_id,$user_id){
    global $db, $db_hris;

    $grant = $db->prepare("INSERT INTO $db_hris.`_user_access`(`program_code`, `user_id`, `grant_by`) VALUES (:pcode, :uid, :by)");

    $grant->execute(array(":pcode" => $prog_id, ":uid" => $user_id, ":by" => $_SESSION['name']));

    echo json_encode(array("status" => "success"));
}

function remove_access($prog_id,$user_id) {
    global $db, $hris;

    $remove = $db->prepare("DELETE FROM $hris.`_user_access` WHERE `program_code`=:p_id AND `user_id`=:uid");
    $remove->execute(array(":p_id" => $prog_id, ":uid"=>$user_id));
    if ($remove->rowCount()){
        echo json_encode(array("status" => "success"));
    }
}

function get_parent() {
    global $db, $db_hris;

    $parent = $db->prepare("SELECT `_program_parent`.`parent_no`,`_program_parent`.`parent_name` FROM $db_hris.`_program_parent`");
    $parent_list = array();
    $parent->execute();
    if ($parent->rowCount()) {
        while ($parent_data = $parent->fetch(PDO::FETCH_ASSOC)) {
            $parent_list[] = array("id" => $parent_data["parent_no"], "text" => $parent_data["parent_name"]);
        }
    }
    echo json_encode(array("status" => "success", "parent" => $parent_list));
}

function get_backup_data(){
    global $db_hris, $db;

    $backup = $db->prepare("SELECT * FROM $db_hris.`_backup` ORDER BY `backup_id` ASC");
    $backup->execute();
    if ($backup->rowCount()) {
        $records = array();
        while ($backup_data = $backup->fetch(PDO::FETCH_ASSOC)) {
            set_time_limit(60);
            array_push($records, array("recid" => $backup_data["backup_id"], "desc" => $backup_data["backup_desc"], "date" => $backup_data["timestamp"], "size" => $backup_data["size"], "storage" => $backup_data["location"]));
        }
        echo json_encode(array("status" => "success", "total" => count($records), "records" => $records));
    } else {
        echo json_encode(array("status" => "error", "message" => "NO BACK UP FOUND!"));
    }
}

function make_backup($user){
    global $db_hris, $db;

    $backup_file = "../backup/".$db_hris ."_". date("Y-m-d-H-i-s") . ".sql";
    $filename = $db_hris ."_". date("Y-m-d-H-i-s") . ".sql";

    $command = "C:/xampp/mysql/bin/mysqldump  -u $user $db_hris > $backup_file";

    exec($command);

    if(file_exists($backup_file)){
        $insert = $db->prepare("INSERT INTO $db_hris.`_backup`(`backup_desc`, `size`, `location`) VALUES (:desc, :size, :loc)");
        $insert->execute(array(":desc" => $filename, ":size" => number_format(filesize($backup_file) / 1024) .' KB', ":loc" => $backup_file));
        if($insert->rowCount()){
            echo json_encode(array("status" => "success", "message" => "Backup created successfully: {$filename}"));
        }
    }else{
        echo json_encode(array("status" => "error", "message" => "Backup Failed"));
    }
}

function delete_backup($id){
    global $db_hris, $db;

    $backup = $db->prepare("SELECT * FROM $db_hris.`_backup` WHERE `backup_id`=:id");
    $backup->execute(array(":id" => $id));
    if ($backup->rowCount()) {
        $backup_data = $backup->fetch(PDO::FETCH_ASSOC);
        $file = $backup_data['location'];
        @unlink($file);
        $del_backup = $db->prepare("DELETE FROM $db_hris.`_backup` WHERE `backup_id`=:id");
        $del_backup->execute(array(":id" => $id));
        if ($del_backup->rowCount()) {
            echo json_encode(array("status" => "success", "message" => "Backup Deteled"));
        }
    }else{
        echo json_encode(array("status" => "error", "message" => "Delete Failed"));
    }
}