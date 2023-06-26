<?php
$program_code_prog = 7;
$program_code_backup = 36;
$program_code = 9;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights_prog = $cfn->get_user_rights($program_code_prog);
$access_rights_backup = $cfn->get_user_rights($program_code_backup);
$access_rights = $cfn->get_user_rights($program_code);
switch ($_POST["cmd"]) {
    case "save-config":
        if (substr($access_rights, 0, 2) === "A+") { 
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
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "del-prog":
        if (substr($access_rights_prog, 4, 2) === "D+") {
            del_config($_POST["recid"]);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-parent":
        if (substr($access_rights_prog, 6, 2) !== "B+") {
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }else{
            get_parent();
        }
    break;
    case "enable-disable":
        if (substr($access_rights_prog, 2, 2) === "E+") {
            enable_module($_POST["recid"]);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-all-program":
        if (substr($access_rights, 6, 2) === "B+") {
            $_SESSION["user_id2"] = $_POST["id"];
            get_all_program($_POST["id"]);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-backup-data":
        if (substr($access_rights_backup, 6, 2) !== "B+") {
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }else{
            get_backup_data();
        }
    break;
    case "make-backup":
        if (substr($access_rights_backup, 0, 2) === "A+") {
            $user = 'root';
            make_backup($user);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "del-backup-data":
        if (substr($access_rights_backup, 4, 2) === "D+") {
            delete_backup($_POST['recid']);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "get-default-user-rights":
        if (substr($access_rights, 6, 2) === "B+") {
            $_SESSION["user_id2"] = 0;
            get_default_user_rights($session_name);
        } else {
            echo json_encode(array("status" => "error", "Sorry, You have no rights to alter!"));
        }
    break;
    case "alter-rights":
        if (substr($access_rights, 2, 2) === "E+") {
            alter_rights($_POST["record"], $_POST["attrib"]);
        } else {
            echo json_encode(array("status" => "error", "Sorry, You have no rights to alter!"));
        }
    break;
    
}

function get_default_user_rights($session_name){
    global $db, $db_hris; ?>
    <div class="w3-col s12">
        <div class="w3-panel w3-bottombar">
            <span class="w3-small">User Access Rights</span>
        </div>
    </div>
    <div class="w3-col s12 m3 w3-panel">
        <div class="w3-col s12">
            <div class="w3-col s12 w3-container w3-padding">
                <?php
                $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_id` != :uid AND `user_level` < :lvl ORDER BY `user_id` ASC");
                $user->execute(array(":uid" => $session_name, ":lvl" => 8));
                ?>
                <table class="w3-table-all w3-hoverable w3-small">
                    <thead>
                        <tr>
                            <th class="w3-center">User's List</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if($user->rowCount()){
                        while($user_data = $user->fetch(PDO::FETCH_ASSOC)){
                        $uid = $user_data["user_no"];
                        $name = $user_data["name"];
                        ?>
                        <tr>
                            <td class="w3-border w3-padding select<?php echo $uid; ?> deselect" style="cursor: pointer;" id="access<?php echo $uid; ?>" onclick="get_all_user_program(<?php echo $uid; ?>)">
                                <?php echo $name; ?>
                            </td>
                        </tr>
                        <?php
                        }
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="w3-col s12 m8 w3-panel">
        <div class="w3-col s12">
            <div class="w3-col s12 w3-container w3-padding">
                <div id="program" class="w3-hide" style="width: 100%; height: 450px;"></div>
        </div>
        </div>
    </div>
</div>
<script>
    
    $(function () {    
        $('#program').w2grid({ 
            name: 'program', 
            show: { 
                toolbar: true,
                footer: true,
                lineNumbers: true
            },
            multiSearch: false,
            multiSelect: false,
            columns: [
                { field: 'system', caption: 'SYSTEM', size: '20%' },
                { field: 'name', caption: 'MENU NAME', size: '40%' },
                { field: 'rights', caption: 'ACCESS RIGHTS', size: '60%'} ,
                { field: 'hold', caption: 'HOLD', size: '50px', attr: "align=center" }
            ],
            toolbar: {
                items: [
                    { type: "break" },
                    { type: "button", id: "add", caption : "ADD" },
                    { type: "break" },
                    { type: "button", id: "edit", caption : "EDIT" },
                    { type: "break" },
                    { type: "button", id: "del", caption : "DELETE" },
                    { type: "break" },
                    { type: "button", id: "brws", caption : "BROWSE" },
                    { type: "break" },
                    { type: "button", id: "prnt", caption : "PRINT" },
                    { type: "break" },
                    { type: "spacer" },
                    { type: "button", id: "hold", caption : "HOLD" },
                    { type: "break" },
                    { type: "button", id: "clr", caption : "REVOKE" }
                ],
                onClick: function (event) {
                    switch (event.target){
                        case "add":
                            if (w2ui['program'].getSelection().length > 0) {
                                alter_rights("A", w2ui['program'].getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "edit":
                            if (w2ui['program'].getSelection().length > 0) {
                                alter_rights("E", w2ui['program'].getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "del":
                            if (w2ui['program'].getSelection().length > 0) {
                                alter_rights("D", w2ui['program'].getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "brws":
                            if (w2ui['program'].getSelection().length > 0) {
                                alter_rights("B", w2ui['program'].getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "prnt":
                            if (w2ui['program'].getSelection().length > 0) {
                                alter_rights("P", w2ui['program'].getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "hold":
                            if (w2ui['program'].getSelection().length > 0) {
                                alter_rights("H", w2ui['program'].getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "clr":
                            if (w2ui['program'].getSelection().length > 0) {
                                alter_rights("C", w2ui['program'].getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                    }
                }
            }
        });
    });

    const src = "page/system.program";

    $(document).ready(function(){
        var c = $("div#program");
        var h = window.innerHeight - 300;
        c.css("height", h);
        setTimeout(function(){
            $("#tb_program_toolbar_item_w2ui-search, #tb_program_toolbar_item_w2ui-break0, #tb_program_toolbar_item_w2ui-reload, #tb_program_toolbar_item_w2ui-column-on-off").hide();
            $('#program').removeClass('w3-hide');
        }, 0);
    });

    function get_all_user_program(id){
        $('.deselect').removeClass('w3-red');
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-all-program",
                id : id
            },
            success: function (data){
                $('.select'+id).addClass('w3-red');
                w2utils.unlock(div);
                try
                {
                    var jObject = jQuery.parseJSON(data);
                    if (jObject.status === "success") {
                        w2ui['program'].clear();
                        if (jObject.records.length) {
                            w2ui['program'].add(jObject.records);
                            w2ui['program'].refresh();
                            $("#tb_program_toolbar_item_w2ui-search, #tb_program_toolbar_item_w2ui-break0, #tb_program_toolbar_item_w2ui-reload, #tb_program_toolbar_item_w2ui-column-on-off").hide();
                        }
                    } else {
                        w2alert(jObject.message);
                    }
                }
                catch (e)
                {
                    w2alert("Sorry, There was a problem in server process!  Expecting correct reply from server.");
                }
            },
            error: function (){
                w2alert("Sorry, There was a problem in server connection!");
            }
        });
    }
    

    function alter_rights(rights, recid) {
        var record = w2ui['program'].get(recid);
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "alter-rights",
                attrib: rights,
                record: record
            },
            success: function (data) {
                w2utils.unlock(div);
                try
                {
                    var jObject = jQuery.parseJSON(data);
                    if (jObject.status === "success") {
                        w2ui['program'].set(recid, jObject.record);
                    } else {
                        w2alert(jObject.message);
                    }
                }
                catch (e)
                {
                    w2alert("Sorry, there was a problem in server process!  Expecting correct reply from server.");
                }
            },
            error: function () {
                w2alert("Sorry, There was a problem in server connection!");
            }
        });
    }
</script>
<?php
}

function get_all_program($uid){
    global $db, $db_hris, $cfn;

    $level = $cfn->get_user_level();
    $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:id");
    $user->execute(array(":id" => $uid));
    $user_data = $user->fetch(PDO::FETCH_ASSOC);
    $records = array();
    if (number_format($level, 0) >= number_format(8, 0)) {
        $prog = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `is_active` AND `program_level` <= :lvl ORDER BY `program_parent`, `seq`");
        $prog->execute(array(":lvl" => $level));
    } else {
        $prog = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `is_active` AND `program_level` <= :lvl AND (SELECT COUNT(*) FROM $db_hris.`_user_access` WHERE `_user_access`.`user_id`=:id AND `_user_access`.`program_code`=`_program`.`program_code`) ORDER BY `program_parent`, `seq`");
        $prog->execute(array(":lvl" => $level, ":id" => $_SESSION["user_id"]));
    }
    if ($prog->rowCount()) {
        $rights = $db->prepare("SELECT * FROM $db_hris.`_user_access` WHERE `program_code`=:code AND `user_id`=:id");
        while ($prog_data = $prog->fetch(PDO::FETCH_ASSOC)) {
            $rights->execute(array(":code" => $prog_data["program_code"], ":id" => $uid));
            if ($rights->rowCount()) {
                $rights_data = $rights->fetch(PDO::FETCH_ASSOC);
            }
            if ($prog_data["isAdmin_module"]) {
                if (number_format($prog_data["program_level"], 0, '.', '') <= number_format($user_data["user_level"], 0, '.', '')) {
                    if (number_format($level, 0) >= number_format(8, 0) AND $level) {
                        if ($rights->rowCount()) {
                            $pwede = 1;
                        } else {
                            $pwede = 0;
                        }
                    } else {
                        $pwede = 1;
                    }
                } else {
                    $pwede = 0;
                }
            } else {
                $pwede = 1;
            }
            if ($pwede) {
                $record = array();
                $record["recid"] = $prog_data["program_code"];
                $record["isystem"] = $cfn->get_program_parent_record($prog_data["program_parent"]);
                $record["system"] = $record["isystem"]["text"];
                $record["name"] = $prog_data["menu_name"];
                $rights->execute(array(":code" => $prog_data["program_code"], ":id" => $uid));
                if ($rights->rowCount()) {
                    $record["rights"] = $cfn->set_rights($rights_data["access_rights"]);
                    $record["gaccess"] = $rights_data["access_rights"];
                    $record["hold"] = $rights_data["is_hold"] ? "Y" : "";
                } else {
                    $record["gaccess"] = "A-E-D-B-P-";
                }
                if (number_format($level, 0) >= number_format(8, 0) AND $level) {
                    $record["maccess"] = "A+E+D+B+P+";
                } else {
                    $rights->execute(array(":code" => $prog_data["program_code"], ":id" => $_SESSION["user_id"]));
                    if ($rights->rowCount()) {
                        $rights_data = $rights->fetch(PDO::FETCH_ASSOC);
                        $record["maccess"] = $rights_data["access_rights"];
                    } else {
                        $record["maccess"] = "A-E-D-B-P-";
                    }
                }
                array_push($records, $record);
            }
        }
    }
    echo json_encode(array("status" => "success", "records" => $records, "q" => $prog, "l" => $level, "r" => $prog->rowCount(), "w" => array(":lvl" => $level, ":id" => $_SESSION["user_id2"])));
}

function alter_rights($record, $attrib) {
    global $db, $db_hris, $cfn;

    $userm = $db->prepare("SELECT * FROM $db_hris.`_user_access` WHERE `user_id`=:id AND `program_code`=:code");
    $userm->execute(array(":id" => $_SESSION["user_id2"], ":code" => $record["recid"]));
    if ($userm->rowCount()) {
        $userm_data = $userm->fetch(PDO::FETCH_ASSOC);
        if ($attrib === "C") {
            $record["gaccess"] = "A-E-D-B-P-";
        } elseif ($attrib !== "H") {
            $record["gaccess"] = $cfn->alter_it($record["maccess"], $userm_data["access_rights"], $attrib);
        }
        if ($record["gaccess"] === "A-E-D-B-P-") {
            $record["rights"] = $cfn->set_rights($record["gaccess"]);
            $rightsd = $db->prepare("DELETE FROM $db_hris.`_user_access` WHERE `user_id`=:id AND `program_code`=:code");
            $rightsd->execute(array(":id" => $_SESSION["user_id2"], ":code" => $record["recid"]));
            if ($rightsd->rowCount()) {
                $record["hold"] = "";
            }
        } else {
            if ($attrib === "H") {
                if ($userm_data["is_hold"]) {
                    $rightsu = $db->prepare("UPDATE $db_hris.`_user_access` SET `is_hold`=0, `grant_by`=:uid WHERE `user_id`=:id AND `program_code`=:code");
                } else {
                    $rightsu = $db->prepare("UPDATE $db_hris.`_user_access` SET `is_hold`=1, `grant_by`=:uid WHERE `user_id`=:id AND `program_code`=:code");
                }
                $rightsu->execute(array(":id" => $_SESSION["user_id2"], ":code" => $record["recid"], ":uid" => $_SESSION["name"]));
                if ($rightsu->rowCount()) {
                    $record["hold"] = $userm_data["is_hold"] ? "" : "Y";
                }
            } elseif ($record["gaccess"] !== $userm_data["access_rights"]) {
                $rightsu = $db->prepare("UPDATE $db_hris.`_user_access` SET `access_rights`=:rights, `grant_by`=:uid WHERE `user_id`=:id AND `program_code`=:code");
                $rightsu->execute(array(":rights" => $record["gaccess"], ":id" => $_SESSION["user_id2"], ":code" => $record["recid"], ":uid" => $_SESSION["name"]));
                if ($rightsu->rowCount()) {
                    $record["rights"] = $cfn->set_rights($record["gaccess"]);
                }
            } else {
                $record["gaccess"] = $userm_data["access_rights"];
                $record["rights"] = $cfn->set_rights($record["gaccess"]);
            }
        }
    } else {
      if ($attrib !== "C") {
        $record["gaccess"] = $cfn->alter_it($record["maccess"], $record["gaccess"], $attrib);
        if ($record["gaccess"] !== "A-E-D-B-P-") {
            $rightsu = $db->prepare("INSERT INTO $db_hris.`_user_access` (`user_id`, `program_code`, `grant_by`, `access_rights`) VALUES (:aid, :code, :uid, :rights)");
            $rightsu->execute(array(":aid" => $_SESSION["user_id2"], ":code" => $record["recid"], ":uid" => $_SESSION["name"], ":rights" => $record["gaccess"]));
            if ($rightsu->rowCount()) {
                $record["rights"] = $cfn->set_rights($record["gaccess"]);
                $record["gaccess"] = $record["gaccess"];
            }
        }
      }
    }
    echo json_encode(array("status" => "success", "record" => $record, "session" => $_SESSION["user_id2"], "skey" => $_SESSION['security_key']));
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