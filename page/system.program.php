<?php
$program_code = 7;
$program_code1 = 9;
require_once('../system.config.php');
require_once("../common_functions.php");

$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$access_rights1 = $cfn->get_user_rights($program_code1);

if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_POST["cmd"]) {
                case "default":
                    if (substr($access_rights, 6, 2) !== "B+") {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }else{
                        get_default();
                    }
                break;
                case "get-parent":
                    if (substr($access_rights, 6, 2) !== "B+") {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }else{
                        get_parent();
                    }
                break;
                case "save-config":
                    if (substr($access_rights, 0, 2) === "A+") {
                        save_prog($_POST["record"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "del-config":
                    if (substr($access_rights, 4, 2) === "D+") {
                        delete_config($_POST["recid"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-all-program":
                    if (substr($access_rights1, 6, 2) === "B+") {
                        $_SESSION["user_id2"] = $_POST["user_id"];
                        get_all_program($_POST["user_id"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "get-default-user-rights":
                    if (substr($access_rights1, 6, 2) === "B+") {
                        $_SESSION["user_id2"] = 0;
                        get_default_user_rights($_SESSION['name']);
                    } else {
                        echo json_encode(array("status" => "error", "Sorry, You have no rights to alter!"));
                    }
                break;
                case "alter-rights":
                    if (substr($access_rights1, 2, 2) === "E+") {
                        alter_rights($_POST["record"], $_POST["attrib"]);
                    } else {
                        echo json_encode(array("status" => "error", "Sorry, You have no rights to alter!"));
                    }
                break;
            }
            $db->commit();
            return false;
        }
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(array("status" => "error", "message" => "Database error!", "e" => $e));
        exit();
    }
}

function get_default(){
    $col = set_prog_column();
    $records = get_program_records();
    echo json_encode(array("status" => "success", "columns" => $col, "records" => $records));
}

function set_prog_column() {
    $items = array();
    $items[] = array("field" => "recid", "caption" => "ID", "size" => "250px", "hidden" => true);
    $items[] = array("field" => "parent", "caption" => "PARENT NAME", "size" => "250px");
    $items[] = array("field" => "prog", "caption" => "PROGRAM NAME", "size" => "250px");
    $items[] = array("field" => "actv", "caption" => "Active", "size" => "50px", "attr" => "align=center");
    $items[] = array("field" => "level", "caption" => "Program Level", "size" => "100px", "attr" => "align=center");
    $items[] = array("field" => "seq", "caption" => "Program Seq", "size" => "100px", "attr" => "align=center");
    $items[] = array("field" => "function", "caption" => "Program Link", "size" => "250px");
    $items[] = array("field" => "src", "caption" => "Program Source", "size" => "300px");
    $items[] = array("field" => "ts", "caption" => "TimeStamp", "size" => "150px", "attr" => "align=right");
    return $items;
}

function get_program_records() {
    global $db, $db_hris;

    $records = array();

    $parent = $db->prepare("SELECT * FROM $db_hris.`_program_parent` ORDER BY `sequence` ASC");
    $parent->execute();

    while ($parent_data = $parent->fetch(PDO::FETCH_ASSOC)) {
        $prog = get_child_program($parent_data["parent_no"], $parent_data["parent_name"]);
        if (empty($prog)) {
            $prog[] = array(
                "recid" => uniqid(),
                "prog" => "NO DATA",
                "actv" => "NO DATA",
                "function" => "NO DATA"
            );
        }

        $records = array_merge($records, $prog);
    }

    return $records;
}

function get_child_program($parentID, $parentName) {
    global $db, $db_hris;

    $progRecords = array();

    $progQuery = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `program_parent_no`=:parent_no ORDER BY `seq`");
    $progQuery->execute(array(":parent_no" => $parentID));

    while ($prog_data = $progQuery->fetch(PDO::FETCH_ASSOC)) {
        $active = $prog_data["is_active"] ? "<b><i class=\"fa fa-check\" aria-hidden=\"true\"></i></b>" : "<b><i class=\"fa fa-times\" aria-hidden=\"true\"></i></b>";
        $record = array(
            "recid" => $prog_data["program_code"],
            "parent" => $parentName,
            "prog" => $prog_data["menu_name"],
            "actv" => $active,
            "level" => $prog_data["program_level"],
            "seq" => $prog_data["seq"],
            "function" => $prog_data["page"],
            "src" => $prog_data["function"],
            "ts" => $prog_data["time_stamp"]
        );
        $progRecords[] = $record;
    }

    return $progRecords;
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
    echo json_encode(array("status" => "success", "parent" => $parent_list, "level" => get_level()));
}

function get_level(){
    $levels = array();
    $levels[] = array("id" => 1, "text" => 1);
    $levels[] = array("id" => 2, "text" => 2);
    $levels[] = array("id" => 3, "text" => 3);
    $levels[] = array("id" => 4, "text" => 4);
    $levels[] = array("id" => 5, "text" => 5);
    $levels[] = array("id" => 6, "text" => 6);
    $levels[] = array("id" => 7, "text" => 7);
    $levels[] = array("id" => 8, "text" => 8);
    $levels[] = array("id" => 9, "text" => 9);
    return $levels;
}

function getParentData($parentID) {
    global $db, $db_hris;
    
    $check_parent = $db->prepare("SELECT * FROM $db_hris.`_program_parent` WHERE `parent_no`=:pno");
    $check_parent->execute(array(":pno" => $parentID));
    return $check_parent->fetch(PDO::FETCH_ASSOC);
}

function saveProgram($record, $parentData) {
    global $db, $db_hris;

    $active = $record["enable"] ? 1 : 0;
    $save_config = $db->prepare("INSERT INTO $db_hris.`_program` SET `program_parent_no`=:pno, `menu_name`=:menu_name, `program_name`=:pclass, `program_level`=:plvl, `is_active`=:actv, `page`=:page, `seq`=:seq, `function`=:src");
    $save_config->execute(array(":pno" => $parentData["parent_no"], ":menu_name" => $record["menu_name"], ":pclass" => $record["prog_name"], ":plvl" => $record["plevel"]["id"], ":actv" => $active, ":page" => $record["functions"], ":seq" => $record["seq"], ":src" => $record["source"]));
    return $save_config->rowCount();
}

function jsonResponse($status, $data = array()) {
    $response = array("status" => $status);
    if (!empty($data)) {
        $response["data"] = $data;
    }
    echo json_encode($response);
}

function save_prog($record) {
    global $db, $db_hris;

    $parentID = $record["parent"]["id"];
    $parentData = getParentData($parentID, $db);
    if ($parentData) {
        $rowCount = saveProgram($record, $parentData, $db);
        if ($rowCount) {
            jsonResponse("success", $record);
        } else {
            jsonResponse("error", array("message" => "Failed to save program."));
        }
    } else {
        jsonResponse("error", array("message" => "Parent not found."));
    }
}

function delete_config($recid){
    global $db, $db_hris;

    $prog = $db->prepare("DELETE FROM $db_hris.`_program` WHERE `program_code`=:prog_id");
    $prog->execute(array(":prog_id" => $recid));
    if($prog->rowCount()){
        echo json_encode(array("status" => "success", "recid" => $recid));
        return;
    }
}

function get_default_user_rights($session_name){
    global $db, $db_hris; ?>
    <div class="w3-col s12 m3 w3-panel">
        <div class="w3-col s12">
            <div class="w3-col s12 w3-container w3-padding">
            <?php
                $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_id` != :uid AND `user_level` < :lvl ORDER BY `user_id` ASC");
                $user->execute(array(":uid" => $session_name, ":lvl" => 8)); ?>
                <table class="w3-table-all w3-hoverable w3-small">
                    <thead>
                        <tr>
                            <th colspan="3" class="w3-center">User's List</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if($user->rowCount()){
                        $cnt = 0;
                        while($user_data = $user->fetch(PDO::FETCH_ASSOC)){
                        $uid = $user_data["user_id"];
                        $name = $user_data["name"];
                        $cnt++;
                        ?>
                        <tr  class="w3-border w3-padding select<?php echo $uid; ?> deselect" style="cursor: pointer;" id="access<?php echo $uid; ?>" onclick="get_all_user_program('<?php echo $uid; ?>')">
                            <td><?php echo $cnt; ?></td>
                            <td><?php echo $uid; ?></td>
                            <td><?php echo $name; ?></td>
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
                <div id="my_grid" class="w3-hide" style="width: 100%; height: 450px;"></div>
        </div>
        </div>
    </div>
</div>
<script>
    
    $(function () {    
        $('#my_grid').w2grid({ 
            name: 'my_grid', 
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
                            if (w2ui.my_grid.getSelection().length > 0) {
                                alter_rights("A", w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "edit":
                            if (w2ui.my_grid.getSelection().length > 0) {
                                alter_rights("E", w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "del":
                            if (w2ui.my_grid.getSelection().length > 0) {
                                alter_rights("D", w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "brws":
                            if (w2ui.my_grid.getSelection().length > 0) {
                                alter_rights("B", w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "prnt":
                            if (w2ui.my_grid.getSelection().length > 0) {
                                alter_rights("P", w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "hold":
                            if (w2ui.my_grid.getSelection().length > 0) {
                                alter_rights("H", w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                        case "clr":
                            if (w2ui.my_grid.getSelection().length > 0) {
                                alter_rights("C", w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert("Please select user to alter access rights attributes!");
                            }
                        break;
                    }
                }
            }
        });
    });

    $(document).ready(function(){
        var c = $("div#my_grid");
        var h = window.innerHeight - 100;
        c.css("height", h);
        setTimeout(function(){
            $("#tb_my_grid_toolbar_item_w2ui-search, #tb_my_grid_toolbar_item_w2ui-break0, #tb_my_grid_toolbar_item_w2ui-reload, #tb_my_grid_toolbar_item_w2ui-column-on-off").hide();
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
                user_id : id
            },
            success: function (data){
                $('.select'+id).addClass('w3-red');
                w2utils.unlock(div);
                try
                {
                    var jObject = jQuery.parseJSON(data);
                    if (jObject.status === "success") {
                        w2ui.my_grid.clear();
                        if (jObject.records.length) {
                            w2ui.my_grid.add(jObject.records);
                            w2ui.my_grid.refresh();
                            $('#my_grid').removeClass('w3-hide');
                            $("#tb_my_grid_toolbar_item_w2ui-search, #tb_my_grid_toolbar_item_w2ui-break0, #tb_my_grid_toolbar_item_w2ui-reload, #tb_my_grid_toolbar_item_w2ui-column-on-off").hide();
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
        var record = w2ui.my_grid.get(recid);
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
                        w2ui.my_grid.set(recid, jObject.record);
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

function get_all_program($user_id){
    global $db, $db_hris, $cfn;

    $level = $cfn->get_user_level();
    $user = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `user_no`=:id");
    $user->execute(array(":id" => $user_id));
    $user_data = $user->fetch(PDO::FETCH_ASSOC);
    $records = array();
    if (number_format($level, 0) >= number_format(8, 0)) {
        $prog = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `is_active` AND `program_level` <= :lvl ORDER BY `program_parent_no`, `seq`");
        $prog->execute(array(":lvl" => $level));
    } else {
        $prog = $db->prepare("SELECT * FROM $db_hris.`_program` WHERE `is_active` AND `program_level` <= :lvl AND (SELECT COUNT(*) FROM $db_hris.`_user_access` WHERE `_user_access`.`user_id`=:id AND `_user_access`.`program_code`=`_program`.`program_code`) ORDER BY `_program`.`program_parent_no`, `_program`.`seq`");
        $prog->execute(array(":lvl" => $level, ":id" => $_SESSION["name"]));
    }
    if ($prog->rowCount()) {
        $rights = $db->prepare("SELECT * FROM $db_hris.`_user_access` WHERE `program_code`=:code AND `user_id` LIKE :id");
        while ($prog_data = $prog->fetch(PDO::FETCH_ASSOC)) {
            $rights->execute(array(":code" => $prog_data["program_code"], ":id" => $user_id));
            if ($rights->rowCount()) {
                $rights_data = $rights->fetch(PDO::FETCH_ASSOC);
            }
            if ($prog_data["isAdmin_module"]) {
                $pwede = 0;
            } else {
                $pwede = 1;
            }
            if ($pwede) {
                $record = array();
                $record["recid"] = $prog_data["program_code"];
                $record["isystem"] = $cfn->get_program_parent_record($prog_data["program_parent_no"]);
                $record["system"] = $record["isystem"]["text"];
                $record["name"] = $prog_data["menu_name"];
                $rights->execute(array(":code" => $prog_data["program_code"], ":id" => $user_id));
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
                    $rights->execute(array(":code" => $prog_data["program_code"], ":id" => $_SESSION["name"]));
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
    echo json_encode(array("status" => "success", "records" => $records, "q" => $prog, "l" => $level, "r" => $prog->rowCount(), "w" => array(":lvl" => $level, ":user_id" => $_SESSION["user_id2"])));
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
