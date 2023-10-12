<?php
$program_code = 3;
$program_code_grp = 37; //paygroup
$program_code_memo = 38; //swipe memo
$program_code_hol = 39; //holiday
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
$cfn = new common_functions();
$access_rights_grp = $cfn->get_user_rights($program_code_grp); //paygroup
$plevel_grp = $cfn->get_program_level($program_code_grp); //paygroup
$access_rights_memo = $cfn->get_user_rights($program_code_memo); //swipe memo
$plevel_memo = $cfn->get_program_level($program_code_memo); //swipe memo
$access_rights_hol = $cfn->get_user_rights($program_code_hol); //holiday
$plevel_hol = $cfn->get_program_level($program_code_hol); //holiday
$level = $cfn->get_user_level();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "get-tabs": //ok
                    if (substr($access_rights, 6, 2) !== "B+") {
                        if ($level <= $plevel) {
                            echo json_encode(array("status" => "error", "message" => "Higher level required!"));
                            return;
                        }
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    } else {
                        get_tabs($access_rights, $access_rights_grp, $access_rights_memo, $access_rights_hol, $level);
                    }
                    break;
                case "load-data":
                    if ($_REQUEST["target"] === "swipe") {
                        if (substr($access_rights_memo, 6, 2) === "B+") {
                            get_swipe($access_rights_memo);
                        }
                    }
                    if ($_REQUEST["target"] === "pay_group") {
                        if (substr($access_rights_grp, 6, 2) === "B+") {
                            get_pay_group($access_rights_grp);
                        }
                    }
                    if ($_REQUEST["target"] === "hol") {
                        if (substr($access_rights_hol, 6, 2) === "B+") {
                            get_holiday(); //ok
                        }
                    }
                    if ($_REQUEST["target"] === "pay_type") {
                        if (substr($access_rights, 6, 2) === "B+") {
                            get_pay_type(); //ok
                        }
                    }
                    if ($_REQUEST["target"] === "sys_config") {
                        if (substr($access_rights, 6, 2) === "B+") {
                            get_sys_config();
                        }
                    }
                    break;
                case "get-sys-records":
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_sys_records();
                    }
                break;
                case "save-config":
                    if ($access_rights === "A+E+D+B+P+") {
                        save_config($_POST["record"]);
                    }
                break;
                case "new_group": //ok
                    if (substr($access_rights_grp, 0, 2) === "A+") {
                        $group_name = $_POST["group_name"];
                        $payroll_date = (new DateTime($_POST["payroll_date"]))->format("Y-m-d");
                        $cuttoff_date = (new DateTime($_POST["cuttoff_date"]))->format("Y-m-d");
                        new_group($group_name, $payroll_date, $cuttoff_date);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "del_group": //ok
                    if (substr($access_rights_grp, 4, 2) === "D+") {
                        $group_no = $_POST["group_no"];
                        del_group($group_no);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "update_group": //ok
                    if (substr($access_rights_grp, 0, 4) === "A+E+") {
                        $pay_group_code = $_POST["pay_group_code"];
                        $payroll_date = (new DateTime($_POST["payroll_date"]))->format("Y-m-d");
                        $cuttoff_date = (new DateTime($_POST["cuttoff_date"]))->format("Y-m-d");
                        update_group($pay_group_code, $payroll_date, $cuttoff_date);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "generate": //ok
                    if (substr($access_rights_hol, 0, 6) === "A+E+D+") {
                        generate_holiday();
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "get-hol-records":
                    if (substr($access_rights_hol, 6, 2) === "B+") {
                        get_holiday_records();
                    }
                    break;
                case "new-hol": //ok
                    if (substr($access_rights_hol, 0, 2) === "A+") {
                        $hol_date = (new DateTime($_POST["hol_date"]))->format("Y-m-d");
                        $hol_name = $_POST["hol_name"];
                        $special = $_POST["is_special"];
                        new_holiday($hol_date, $hol_name, $special);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "del-hol": //ok
                    if (substr($access_rights_hol, 4, 2) === "D+") {
                        $hol_id = $_POST["hol_id"];
                        delete_holiday($hol_id);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "get-paytype-records": //ok
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_paytype_records();
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "save-pay-type": //ok
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        $pay_desc = $_POST['pay_desc'];
                        $isFactortoRate = $_POST['isFactortoRate'] ? 1 :  0;
                        $factorAmt = $_POST['factorAmt'];
                        $tax = $_POST['tax'] ? 1 :  0;
                        $sss = $_POST['sss'] ? 1 : 0;
                        $cola = $_POST['cola'] ? 1 :  0;
                        $month = $_POST['month'] ? 1 : 0;
                        $od = $_POST['od'] ? 1 : 0;
                        $adj = $_POST['adj'] ? 1 : 0;
                        $neg = $_POST['neg'] ? 1 : 0;
                        save_pay_type($pay_desc, $isFactortoRate, $factorAmt, $tax, $sss, $cola, $month, $od, $adj, $neg);
                    } else {
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                    break;
                case "get-swipe-data": //get data of swipe transaction
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        $memo_no = $_POST["memo_no"];
                        get_swipe_data($memo_no);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "add-update-swipe-data": //adding new swipe data
                    if (substr($access_rights, 0, 4) === "A+E+") {
                        $memo_no = $_POST["memo_no"];
                        $desc = $_POST["swipe_desc"];
                        $amount = $_POST["penalty"];
                        $penalty_to = $_POST["to"];
                        $penalized = $_POST["penalized"];
                        $update_time = $_POST["update_time"];
                        save_swipe_data($memo_no,$desc,$amount,$penalty_to,$penalized,$update_time);
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

function save_config($record){
    global $db, $db_hris;

    $q = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :name AND `config_value` LIKE :value");
    $q->execute(array(":name" => $record["config_name"], ":value" => $record["config_value"]));
    if($q->rowCount()){
        echo json_encode(array("status" => "error", "message" => "Config already exist!"));
    }else{
        $sys_in = $db->prepare("INSERT INTO $db_hris.`_sysconfig` SET `config_name`=:name, `config_value`=:value, `config_title`=:desc, `user_id`=:uid, `station_id`=:ip");
        $sys_in->execute(array(":name" => $record["config_name"], ":value" => $record["config_value"], ":desc" => $record["desc"], ":uid" => $_SESSION["name"], ":ip" =>  $_SERVER['REMOTE_ADDR']));
        if($sys_in->rowCount()){
            echo json_encode(array("status" => "success", "message" => "ok!"));
        }
    }
}

function get_sysconfig_columns()
{
    $items = array();
    $items[] = array("field" => "recid", "caption" => "No", "size" => "50px", "attr" => "align=right", "hidden" => true);
    $items[] = array("field" => "config", "caption" => "Config Name", "size" => "150px");
    $items[] = array("field" => "val", "caption" => "Value", "size" => "120px");
    $items[] = array("field" => "desc", "caption" => "Description", "size" => "500px");
    $items[] = array("field" => "uid", "caption" => "userID", "size" => "70px");
    $items[] = array("field" => "station", "caption" => "station", "size" => "150px");
    $items[] = array("field" => "ts", "caption" => "TimeStamp", "size" => "150px");
    return $items;
}

function get_sys_records(){
    global $db, $db_hris;

    $records = array();
    $q = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` ORDER BY `config_code`");
	$q->execute();
    if ($q->rowCount()) {
        while ($d = $q->fetch(PDO::FETCH_ASSOC)) {
            $record["recid"] = $d["config_code"];
			$record["config"] = $d["config_name"];
            $record["val"] = $d["config_value"];
            $record["desc"] = $d["config_title"];
            $record["uid"] = $d["user_id"];
            $record["station"] = $d["station_id"];
            $record["ts"] = $d["time_stamp"];
            array_push($records, $record);
		}
    }
    echo json_encode(array("status" => "success", "columns" => get_sysconfig_columns(), "records" => $records));
}

function get_sys_config(){ ?>
    <div id="sys_config" class="w3-col s12 w3-small w3-padding-small">
        <div id="my_grid" style="width: 100%;"></div>
    </div>
    <script>
        $(document).ready(function () {
            var c = $("div#sys_config");
            var g = $("#my_grid");
            var h = window.innerHeight - 150;
            c.css("height", h);
            g.css("height", h - 50);
            get_sysconfig_records();
        });

    $(function () {
        $('#my_grid').w2grid({ 
            name: 'my_grid', 
            show: { 
                toolbar: true,
                footer: false,
                lineNumbers: true,
                toolbarReload: true,
                toolbarSearch: false,
                toolbarInput: false,
                toolbarColumns: false,
                toolbarAdd: true
            },
            onAdd : function(event){
                if (!w2ui.form) {
                    $().w2form({
                        name: 'form',
                        style: 'border: 0px; background-color: transparent;',
                        formHTML: 
                            '<div class="w2ui-page page-0">'+
                            '    <div class="w2ui-field">'+
                            '        <label>Config Name:</label>'+
                            '        <div>'+
                            '           <input name="config_name" id="config_name" type="text" maxlength="100" style="width: 250px"/>'+
                            '        </div>'+
                            '    </div>'+
                            '    <div class="w2ui-field">'+
                            '        <label>Config Value:</label>'+
                            '        <div>'+
                            '            <input name="config_value" id="config_value" maxlength="100" style="width: 250px"/>'+
                            '        </div>'+
                            '    </div>'+
                            '    <div class="w2ui-field">'+
                            '        <label>Description:</label>'+
                            '        <div>'+
                            '            <textarea name="desc" id="desc" maxlength="500" style="width: 250px; height: 50px;"></textarea>'+
                            '        </div>'+
                            '    </div>'+
                            '</div>'+
                            '<div class="w2ui-buttons">'+
                            '    <button class="w3-button w3-red w3-small w3-round-medium" name="reset">Reset</button>'+
                            '    <button class="w3-button w3-green w3-small w3-round-medium" name="save">Save</button>'+
                            '</div>',
                        fields: [
                            { field: 'config_name', type: 'text', required: true },
                            { field: 'config_value', type: 'text', required: true },
                            { field: 'desc', type: 'text', required: true }
                        ],
                        actions: {
                            "save": function () { save_config(); },
                            "reset": function () { this.clear(); }
                        }
                    });
                }
                $().w2popup('open', {
                    title   : 'New System Configuration',
                    body    : '<div id="form" style="width: 100%; height: 100%;"></div>',
                    style   : 'padding: 15px 0px 0px 0px',
                    width   : 500,
                    height  : 300, 
                    showMax : false,
                    onToggle: function (event) {
                        $(w2ui.form.box).hide();
                        event.onComplete = function () {
                            $(w2ui.form.box).show();
                            w2ui.form.resize();
                        }
                    },
                    onOpen: function (event) {
                        event.onComplete = function () {
                            $('#w2ui-popup #form').w2render('form');
                        }
                    }
                });
            },
            columnGroups: [],
            columns: [],
            records: []
        });
    });

    function save_config(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "save-config",
                record: w2ui.form.record
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success"){
                    w2popup.close();
                    w2ui.form.clear();
                    get_sysconfig_records();
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem connecting to the server.");
                w2utils.unlock(div);
            }
        });
    }

    function get_sysconfig_records() {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-sys-records"
                },
                dataType: "json",
                success: function(data) {
                    if (data !== "") {
                        if (data.status === "success") {
                            w2ui.my_grid.clear();
                            w2ui.my_grid.columns = data.columns;
                            w2ui.my_grid.add(data.records);
                        } else {
                            w2alert(data.message);
                        }
                        w2utils.unlock(div);
                    }
                },
                error: function() {
                    w2alert("Sorry, there was a problem in server connection!");
                    w2utils.unlock(div);
                }
            });
        }

        function save_holiday() {
            var hol_date = $('#hol_date').val();
            var hol_name = $('#hol_name').val();
            if ($('#special').is(':checked')) {
                var is_special = 1;
            } else {
                var is_special = 0;
            }
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "new-hol",
                    hol_date: hol_date,
                    hol_name: hol_name,
                    is_special: is_special
                },
                success: function(data) {
                    if (data !== "") {
                        var _return = jQuery.parseJSON(data);
                        if (_return.status === "success") {
                            $('#hol_date').val('');
                            $('#hol_name').val('');
                            if ($('#special').is(':checked')) {
                                $('#special').click();
                            }
                            get_hol_records();
                            w2utils.unlock(div);
                        } else {
                            w2alert(_return.message);
                            w2utils.unlock(div);
                        }
                    }
                },
                error: function() {
                    w2alert("Sorry, there was a problem in server connection!");
                }
            });
        }
    </script>
<?php
}

//adding new swipe data
function save_swipe_data($memo_no,$desc,$amount,$penalty_to,$penalized,$update_time) {
    global $db, $db_hris;

    $check_swipe = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:memo_no");
    $check_swipe->execute(array(":memo_no" => $memo_no));
    if ($check_swipe->rowCount()){
        $update_swipe = $db->prepare("UPDATE $db_hris.`swipe_memo_code` SET `description`=:desc, `penalty_amount`=:amount, `penalty_to`=:to, `user_id`=:uid, `station_id`=:ip, `is_penalized`=:penalized, `is_update_time`=:update_time WHERE `swipe_memo_code`=:memo_no");
        $update_swipe->execute(array(":memo_no" => $memo_no, ":desc" => $desc, ":amount" => $amount, ":to" => $penalty_to, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":penalized" => $penalized, ":update_time" => $update_time));

        echo json_encode(array("status" => "success"));

    }else{
        $new_swipe = $db->prepare("INSERT INTO $db_hris.`swipe_memo_code`(`description`, `penalty_amount`, `penalty_to`, `user_id`, `station_id`, `is_penalized`, `is_update_time`) VALUES (:desc, :amount, :to, :uid, :ip, :penalized, :update_time)");
        $new_swipe->execute(array(":memo_no" => $memo_no, ":desc" => $desc, ":amount" => $amount, ":to" => $penalty_to, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":penalized" => $penalized, ":update_time" => $update_time));

        echo json_encode(array("status" => "success"));
    }
}

//get data of swipe transaction
function get_swipe_data($memo_no) {
    global $db, $db_hris;

    $swipe_memo = $db->prepare("SELECT * FROM $db_hris.`swipe_memo_code` WHERE `swipe_memo_code`=:memo_no");
    $swipe_memo->execute(array(":memo_no" => $memo_no));
    if ($swipe_memo->rowCount()) {
        $swipe_memo_data = $swipe_memo->fetch(PDO::FETCH_ASSOC);

        $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:no");
        $deduction->execute(array(":no" => $swipe_memo_data["penalty_to"]));
        if ($deduction->rowCount()) {
            $data = $deduction->fetch(PDO::FETCH_ASSOC);
            $desc = $swipe_memo_data["description"];
            $penalty_amt = $swipe_memo_data["penalty_amount"];
            $penalty_to = $data["deduction_no"];
            $penalized = $swipe_memo_data["is_penalized"];
            $update = $swipe_memo_data["is_update_time"];
        }else{
            $desc = $swipe_memo_data["description"];
            $penalty_amt = $swipe_memo_data["penalty_amount"];
            $penalty_to = $swipe_memo_data["penalty_to"];
            $penalized = $swipe_memo_data["is_penalized"];
            $update = $swipe_memo_data["is_update_time"];
        }
    }
    echo json_encode(array("status" => "success", "swipe_desc" => $desc, "penalty_amt" => $penalty_amt, "penalty_to" => $penalty_to, "penalized" => $penalized, "update" => $update));       
}

function get_swipe($access_rights){
    global $db, $db_hris; ?>
    <style type="text/css">
        td, th {
        text-align: left;
        padding: 2px;
        }
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
        }

        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
    <div class=" w3-padding-top">
        <table class="w3-small w3-table-all w3-hoverable">
            <thead>
                <tr>
                    <th></th>
                    <th>Swipe Memo Option</th>
                    <th>Penalty Amount</th>
                    <th>Penalty To</th>
                    <th>is Penalized</th>
                    <th>is UpdateTime</th>
                    <th>User ID</th>
                    <th>Station ID</th>
                    <th>TimeStamp</th>
                </tr>
            </thead>
            <tbody>
                <tr id="new_swipe">
                    <td><input type="hidden" id="swipe_code" value=""></td>
                    <td><input id="swipe_desc" type="text" name="swipe_desc" class="w3-input w3-small w3-padding-small w3-border w3-border-silver w3-round-medium"></td>
                    <td><input id="penalty_amt" type="number" name="penalty_amt" class="w3-input w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 200px;"></td>
                    <td>
                        <select id="penalty_to" type="select" name="penalty_to" class="w3-select w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 200px;">
                            <option class="w3-small" value="">Select Options..</option>
                            <option class="w3-small" value="0">None</option>
                            <?php
                                $ded = $db->query("SELECT * FROM $db_hris.`deduction` WHERE !is_computed ORDER BY `deduction_description`");
                                if ($ded->rowCount()) {
                                    $count = 0;
                                    while ($ded_data = $ded->fetch(PDO::FETCH_ASSOC)) {
                                        $ded_desc=$ded_data['deduction_description'];
                                        $ded_code=$ded_data['deduction_no'];
                                ?>
                                <option class="w3-small" value="<?php echo $ded_code; ?>"><?php echo $ded_desc; ?></option>
                            <?php }
                            } ?>
                        </select>
                    </td>
                    <td>
                        <select id="penalized" type="select" name="penalized" class="w3-select w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 100px;">
                            <option id="0" value="">Select Options</option>
                            <option id="yes" value="1">YES</option>
                            <option id="no" value="0">NO</option>
                        </select>
                    </td>
                    <td>
                        <select id="update_time" type="select" name="update_time" class="w3-select w3-small w3-padding-small w3-border w3-border-silver w3-round-medium" style="width: 100px;">
                            <option id="0" value="">Select Options</option>
                            <option id="yes" value="1">YES (Whole Day)</option>
                            <option id="yesh" value="2">YES (Half Day)</option>
                            <option id="no" value="0">NO</option>
                        </select>
                    </td>
                    <td>
                        <?php if (substr($access_rights, 0, 2) === "A+") { ?>
                        <button class="w3-small w3-margin-right" id="save" onclick="save_data();"><ion-icon class="w3-large" name="save-outline" style="padding-top: 5px;"></ion-icon></button>
                        <button class="w3-small w3-hide" id="clear" onclick="clear_data();"><ion-icon class="w3-large" name="refresh-outline" style="padding-top: 5px;"></ion-icon></button>
                        <?php } ?>
                    </td>
                    <td colspan="2"></td>
                </tr>
                <?php
                $swipe_memo = $db->query("SELECT * FROM $db_hris.`swipe_memo_code` ORDER BY `description`");
                if ($swipe_memo->rowCount()) {
                    $count = 0;
                    $deduction = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:no");
                    while ($swipe_memo_data = $swipe_memo->fetch(PDO::FETCH_ASSOC)) {
                        $count++;
                        $deduction->execute(array(":no" => $swipe_memo_data["penalty_to"]));
                        if ($deduction->rowCount()) {
                            $data = $deduction->fetch(PDO::FETCH_ASSOC); ?>
                            <tr id="<?php echo $swipe_memo_data['swipe_memo_code']; ?>" style="cursor: pointer;" onclick="edit_data(<?php echo $swipe_memo_data['swipe_memo_code']; ?>)">
                                <td><?php echo $count; ?></td>
                                <td><?php echo $swipe_memo_data['description'];; ?></td>
                                <td><?php echo $swipe_memo_data['penalty_amount']; ?></td>
                                <td><?php echo $data['deduction_description']; ?></td>
                                <td><?php if($swipe_memo_data['is_penalized']) echo "YES"; else echo "NO"; ?></td>
                                <td><?php if($swipe_memo_data['is_update_time'] == 1) echo "YES (Whole Day)"; elseif($swipe_memo_data['is_update_time'] == 2) echo "YES (Half Day)"; else echo "NO"; ?></td>
                                <td><?php echo $swipe_memo_data['user_id']; ?></td>
                                <td><?php echo $swipe_memo_data['station_id']; ?></td>
                                <td><?php echo $swipe_memo_data['_timestamp']; ?></td>
                            </tr>
                        <?php
                        }else{ ?>
                            <tr id="<?php echo $swipe_memo_data['swipe_memo_code']; ?>" style="cursor: pointer;" onclick="edit_data(<?php echo $swipe_memo_data['swipe_memo_code']; ?>)">
                                <td><?php echo $count; ?></td>
                                <td><?php echo $swipe_memo_data['description']; ?></td>
                                <td><?php echo $swipe_memo_data['penalty_amount']; ?></td>
                                <td>NONE</td>
                                <td><?php if($swipe_memo_data['is_penalized']) echo "YES"; else echo "NO"; ?></td>
                                <td><?php if($swipe_memo_data['is_update_time'] == 1) echo "YES (Whole Day)"; elseif($swipe_memo_data['is_update_time'] == 2) echo "YES (Half Day)"; else echo "NO"; ?></td>
                                <td><?php echo $swipe_memo_data['user_id']; ?></td>
                                <td><?php echo $swipe_memo_data['station_id']; ?></td>
                                <td><?php echo $swipe_memo_data['_timestamp']; ?></td>
                            </tr>
                        <?php
                        }
                    }
                } ?>
            </tbody>
        </table>
    </div>
    <script type="text/javascript">

    function clear_data(){
        $('input#swipe_code').val('');
        $('input#swipe_desc').val('');
        $('input#penalty_amt').val('');
        $('select#penalty_to').val('');
        $('select#penalized').val('');
        $('select#update_time').val('');
        $('#clear').addClass('w3-hide');
    }

    function save_data(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        let memo_no = $('input#swipe_code').val();
        let swipe_desc = $('input#swipe_desc').val();
        let penalty = $('input#penalty_amt').val();
        let to = $('select#penalty_to').val();
        let penalized = $('select#penalized').val();
        let update_time = $('select#update_time').val();
        $.ajax({
            url: src,
            method: "POST",
            data:{
                cmd: "add-update-swipe-data",
                memo_no : memo_no,
                swipe_desc : swipe_desc,
                penalty : penalty,
                to : to,
                penalized : penalized,
                update_time : update_time
            },
            success: function(data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        load_data("swipe");
                        $('#save').addClass('w3-hide');
                        $('#clear').addClass('w3-hide');
                        w2utils.unlock(div);
                    }else{
                        w2alert(_return.message);
                        w2utils.unlock(div);
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

function edit_data(memo_no){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        method: "POST",
        data:{
            cmd: "get-swipe-data",
            memo_no : memo_no
        },
        success: function (data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    $('input#swipe_code').val(memo_no);
                    $('input#swipe_desc').val(_return.swipe_desc);
                    $('input#penalty_amt').val(_return.penalty_amt);
                    $('select#penalty_to').val(_return.penalty_to);
                    $('select#penalized').val(_return.penalized);
                    $('select#update_time').val(_return.update);
                    $('#save').removeClass('w3-hide');
                    $('#clear').removeClass('w3-hide');
                    w2utils.unlock(div);
                }else{
                    w2alert(_return.message);
                    w2utils.unlock(div);
                }
            }
        },
        error: function (){
            w2alert("Sorry, there was a problem in server connection!");
            w2utils.unlock(div);
        }
    })
}

</script>
<?php
}

function generate_holiday()
{
    global $db, $db_hris;

    $day = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date` >=:df AND `holiday_date`<=:dt ORDER BY `holiday_date`");
    $i = $db->prepare("INSERT INTO $db_hris.`holiday` (`holiday_date`, `is_special`, `description`) VALUES (:date, :is, :desc)");
    $prev_year = date("Y", strtotime("-1 year"));
    $this_year = $prev_year + 1;
    $day->execute(array(":df" => $prev_year . "-01-01", ":dt" => $prev_year . "-12-31"));
    if ($day->rowCount()) {
        while ($data = $day->fetch(PDO::FETCH_ASSOC)) {
            $date = $this_year . substr($data["holiday_date"], -6);
            $i->execute(array(":date" => $date, ":is" => $data["is_special"], ":desc" => $data["description"]));
        }
    }
    echo json_encode(array("status" => "success"));
}

function delete_holiday($hol_id)
{

    global $db, $db_hris;

    $del_hol = $db->prepare("DELETE FROM $db_hris.`holiday` WHERE `holiday_id`=:hol_id");
    $del_hol->execute(array(":hol_id" => $hol_id));

    echo json_encode(array("status" => "success"));
}


function new_holiday($hol_date, $hol_name, $special)
{
    global $db, $db_hris;

    $check_holiday = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date`=:hol_date");
    $check_holiday->execute(array(":hol_date" => $hol_date));
    if ($check_holiday->rowCount()) {
        echo json_encode(array("status" => "error", "message" => "Holiday date $hol_date already exist!"));
    } else {
        $new_holiday = $db->prepare("INSERT INTO $db_hris.`holiday`(`holiday_date`,`is_special`,`description`) VALUES (:date, :special, :desc)");
        $new_holiday->execute(array(":date" => $hol_date, ":special" => $special, ":desc" => $hol_name));

        echo json_encode(array("status" => "success"));
    }
}

function new_group($group_name, $payroll_date, $cuttoff_date)
{
    global $db, $db_hris;

    $new_pay_group = $db->prepare("INSERT INTO $db_hris.`employment_status`(`description`) VALUES (:desc)");
    $new_pay_group->execute(array(":desc" => strtoupper($group_name)));

    $check_pay_group = $db->prepare("SELECT * FROM $db_hris.`employment_status` WHERE `description`=:desc");
    $check_pay_group->execute(array(":desc" => strtoupper($group_name)));
    if ($check_pay_group->rowCount()) {
        while ($check_pay_group_data = $check_pay_group->fetch(PDO::FETCH_ASSOC)) {

            $new_pay_group1 = $db->prepare("INSERT INTO $db_hris.`payroll_group`(`group_name`,`cutoff_date`,`payroll_date`) VALUES (:grp_no, :cuttoff_date, :payroll_date)");
            $new_pay_group1->execute(array(":grp_no" => $check_pay_group_data['employment_status_code'], ":cuttoff_date" => $cuttoff_date, ":payroll_date" => $payroll_date));

            echo json_encode(array("status" => "success"));
        }
    }
}

function del_group($group_no)
{
    global $db, $db_hris;

    $del_group = $db->prepare("DELETE FROM $db_hris.`employment_status` WHERE `employment_status_code`=:no");
    $del_group->execute(array(":no" => $group_no));
    if ($del_group->rowCount()) {
        echo json_encode(array("status" => "success"));
        $pay_group = $db->prepare("DELETE FROM $db_hris.`payroll_group` WHERE `group_name`=:no");
        $pay_group->execute(array(":no" => $group_no));
    }
}

function update_group($pay_group_code, $payroll_date, $cuttoff_date)
{
    global $db, $db_hris;

    $check_pay_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:pay_group_code");
    $check_pay_group->execute(array(":pay_group_code" => $pay_group_code));
    if ($check_pay_group->rowCount()) {

        $update_pay_group = $db->prepare("UPDATE $db_hris.`payroll_group` SET `cutoff_date`=:cutoff, `payroll_date`=:payroll_date WHERE `group_name`=:pay_group");
        $update_pay_group->execute(array(":pay_group" => $pay_group_code, ":cutoff" => $cuttoff_date, ":payroll_date" => $payroll_date));

        echo json_encode(array("status" => "success"));
    } else {

        $new_pay_group = $db->prepare("INSERT INTO $db_hris.`payroll_group`(`group_name`,`cutoff_date`,`payroll_date`) VALUES (:grp_no, :cuttoff_date, :payroll_date)");
        $new_pay_group->execute(array(":grp_no" => $pay_group_code, ":cuttoff_date" => $cuttoff_date, ":payroll_date" => $payroll_date));

        echo json_encode(array("status" => "success"));
    }
}

function save_pay_type($pay_desc, $isFactortoRate, $factorAmt, $tax, $sss, $cola, $month, $od, $adj, $neg)
{
    global $db, $db_hris;

    $pay_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `pay_type` LIKE :desc");
    $pay_type->execute(array(":desc" => '%' . $pay_desc . '%'));
    if ($pay_type->rowCount()) {
        echo json_encode(array("status" => "error", "message" => "$pay_desc is already define!"));
    } else {
        $pay_ins = $db->prepare("INSERT INTO $db_hris.`payroll_type`(`pay_type`, `is_factor_to_payrate`, `factor_amount`, `is_subject_to_tax`, `is_subject_to_sss`, `is_subject_to_cola`, `is_subject_to_13th`, `is_subject_to_other_ded`, `is_adjustment`, `is_negative`, `user_id`, `station_id`) VALUES (:pay_desc, :ftp, :fa, :st, :ss, :cola, :13th, :sod, :adj, :neg, :userid, :station)");
        $pay_ins->execute(array(":pay_desc" => $pay_desc, ":ftp" => $isFactortoRate, ":fa" => $factorAmt, ":st" => $tax, ":ss" => $sss, ":cola" => $cola, ":13th" => $month, ":sod" => $od, ":adj" => $adj, ":neg" => $neg, ":userid" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR']));
        echo json_encode(array("status" => "success"));
    }
}

function get_holiday_records(){
    global $db, $db_hris;

    $prev_records = array();
    $records = array();
    $year = date('Y');
    $prev_year = date("Y", strtotime("-1 year"));
    $prev_holiday = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date` LIKE :prev_yr ORDER BY `holiday_date`");
    $prev_holiday->execute(array(":prev_yr" => "%$prev_year%"));
    if ($prev_holiday->rowCount()) {
        while ($prev_holiday_data = $prev_holiday->fetch(PDO::FETCH_ASSOC)) {
            $prev_record["recid"] = $prev_holiday_data["holiday_id"];
            $prev_record["hol_date"] = $prev_holiday_data["holiday_date"];
            $prev_record["hol_desc"] = '<i class="fa-solid fa-lock"></i>&nbsp;'.$prev_holiday_data["description"];
            $prev_record["special"] = $prev_holiday_data["is_special"] ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-times"></i>';
            $prev_records[] = $prev_record;
        }
    }
    $holiday = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date` LIKE :yr ORDER BY `holiday_date`");
    $holiday->execute(array(":yr" => "%$year%"));
    if ($holiday->rowCount()) {
        while ($holiday_data = $holiday->fetch(PDO::FETCH_ASSOC)) {
            $record["recid"] = $holiday_data["holiday_id"];
            if ($holiday_data["holiday_date"] < date('Y-m-d')) {
                $record["can_delete"] = 0;
                $record["hol_desc"] = '<i class="fa-solid fa-lock"></i>&nbsp;'.$holiday_data["description"];
            }else{
                $record["can_delete"] = 1;
                $record["hol_desc"] = $holiday_data["description"];
            }
            $record["hol_date"] = $holiday_data["holiday_date"];
            $record["special"] = $holiday_data["is_special"] ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-times"></i>';
            $records[] = $record;
        }
    }
    echo json_encode(array("status" => "success", "prev_records" => $prev_records, "records" => $records, "columns" => get_hol_columns(), "prev_colgroups" => get_hol_colgroup($year), "colgroups" => get_hol_colgroup($prev_year), "copy" => $holiday->rowCount() ? 0 : 1));
}

function get_hol_colgroup($year){
    $items = array();
    if($year === date('Y')){
        $items[] = array("span" => 4, "caption" => "<b>Current Year Holiday's</b>");
    }else{
        $items[] = array("span" => 4, "caption" => "<b>Previous Year Holiday's</b>");
    }
    return $items;
}

function get_hol_columns()
{
    $items = array();
    $items[] = array("field" => "recid", "caption" => "No", "size" => "50px", "attr" => "align=right", "hidden" => true);
    $items[] = array("field" => "hol_date", "caption" => "Holiday Date", "size" => "100px", "render" => "date", "attr" => "align=center");
    $items[] = array("field" => "hol_desc", "caption" => "Holiday Name", "size" => "300px");
    $items[] = array("field" => "special", "caption" => "is Special?", "size" => "80px", "attr" => "align=center");
    return $items;
}

function get_holiday()
{ ?>
    <div id="holiday" class="w3-col s12 w3-small w3-padding-small">
        <div id="my_toolbar" class="w3-col s12 w3-margin-bottom" style="padding: 4px; border: 1px solid #dfdfdf; border-radius: 3px;"></div>
        <div class="w3-col s6 m6">
            <div id="my_grid" style="width: 95%;"></div>
        </div>
        <div class="w3-col s6 m6" id="ded_ledger">
            <div id="my_grid1" style="width: 100%;"></div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            var c = $("div#holiday");
            var g = $("#my_grid, #my_grid1");
            var h = window.innerHeight - 150;
            c.css("height", h);
            g.css("height", h - 50);
            get_hol_records();
        });

    $(function () {
        $('#my_toolbar').w2toolbar({
            name: 'my_toolbar',
            items: [
                { type: 'html', html: '<input name="hol_date" class="date w3-small w2ui-input" id="hol_date" />' },
                { type: 'html', html: '&nbsp;<input name="hol_name" type="text" class="w2ui-input" size="50" id="hol_name" placeholder="Holiday Description.."/>' },
                { type: 'html', html: '&nbsp;&nbsp;&nbsp;<input name="special" type="checkbox" id="special" value="1" /> &nbsp;Special?' },
                { type: 'break' }, 
                { type: 'button',  id: 'add',  text: 'ADD Holiday' },
                { type: 'spacer' },
                { type: 'button',  id: 'copy',  text: 'COPY PREVIOUS HOLIDAY', hidden: true }
            ],
            onClick: function (event) {
                switch (event.target){
                    case 'copy':
                        copy_holiday();
                    break;
                    case 'add':
                        if($("#hol_date").val() !== "" && $("#hol_name").val() !== ""){
                            save_holiday();
                        }else{
                            w2alert("Please fill out fields");
                        }
                    break;
                }
            }
        }); 

        $('#my_grid').w2grid({ 
            name: 'my_grid', 
            show: { 
                toolbar: false,
                footer: false,
                lineNumbers: true,
                toolbarReload: false,
                toolbarSearch: false,
                toolbarInput: false,
                toolbarColumns: false,
            },
            columnGroups: [],
            columns: [],
            records: []
        });

        $('#my_grid1').w2grid({ 
            name: 'my_grid1', 
            show: { 
                toolbar: false,
                footer: false,
                lineNumbers: true,
                toolbarReload: false,
                toolbarSearch: false,
                toolbarInput: false,
                toolbarColumns: false,
            },
            columnGroups: [],
            columns: [],
            records: [],
            onSelect: function(event) {
                event.onComplete = function () {
                    var sel_rec_ids = this.getSelection();
                    var sel_record = this.get(sel_rec_ids[0]);
                    if(sel_record.can_delete){
                        edit_del(event.recid);
                    }
                }
            }
        });
    });

    function get_hol_records() {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-hol-records"
                },
                dataType: "json",
                success: function(data) {
                    $("#hol_date").w2field("date");
                    if (data !== "") {
                        if (data.status === "success") {
                            w2ui.my_grid.clear();
                            w2ui.my_grid1.clear();
                            w2ui.my_grid.columnGroups = data.prev_colgroups;
                            w2ui.my_grid.columns = data.columns;
                            w2ui.my_grid.add(data.prev_records);
                            w2ui.my_grid1.columnGroups = data.colgroups;
                            w2ui.my_grid1.columns = data.columns;
                            w2ui.my_grid1.add(data.records);
                            if(data.copy){
                                w2ui.my_toolbar.show("copy");
                            }
                        } else {
                            w2alert(data.message);
                        }
                        w2utils.unlock(div);
                    }
                },
                error: function() {
                    w2alert("Sorry, there was a problem in server connection!");
                    w2utils.unlock(div);
                }
            });
        }
        

        function edit_del($hol_id) {
            w2confirm('Are you sure to delete this Holiday?')
                .yes(function() {
                    delete_hol($hol_id);
                })
                .no(function() {
                    w2popup.close();
                });
        }

        function delete_hol($hol_id) {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "del-hol",
                    hol_id: $hol_id
                },
                success: function(data) {
                    if (data !== "") {
                        var _return = jQuery.parseJSON(data);
                        if (_return.status === "success") {
                            get_hol_records();
                            w2utils.unlock(div);
                        } else {
                            w2alert(_return.message);
                            w2utils.unlock(div);
                        }
                    }
                },
                error: function() {
                    w2alert("Sorry, there was a problem in server connection!");
                }
            });
        }

        function copy_holiday() {
            w2confirm('Are you sure to copy previous year holidays to this year?', function(btn) {
                if (btn == "Yes") {
                    generate_holidays();
                }
            });
        }

        function generate_holidays() {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "generate"
                },
                dataType: "json",
                success: function(jObject) {
                    w2utils.unlock(div);
                    if (jObject.status === "success") {
                        get_hol_records();
                    } else {
                        w2alert(jObject.message);
                    }
                },
                error: function() {
                    w2utils.unlock(div);
                }
            });
        }

        function save_holiday() {
            var hol_date = $('#hol_date').val();
            var hol_name = $('#hol_name').val();
            if ($('#special').is(':checked')) {
                var is_special = 1;
            } else {
                var is_special = 0;
            }
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "new-hol",
                    hol_date: hol_date,
                    hol_name: hol_name,
                    is_special: is_special
                },
                success: function(data) {
                    if (data !== "") {
                        var _return = jQuery.parseJSON(data);
                        if (_return.status === "success") {
                            $('#hol_date').val('');
                            $('#hol_name').val('');
                            if ($('#special').is(':checked')) {
                                $('#special').click();
                            }
                            get_hol_records();
                            w2utils.unlock(div);
                        } else {
                            w2alert(_return.message);
                            w2utils.unlock(div);
                        }
                    }
                },
                error: function() {
                    w2alert("Sorry, there was a problem in server connection!");
                }
            });
        }
    </script>
<?php
}

function get_columns_group()
{
    $items = array();
    $items[] = array("span" => 2, "caption" => "<b>Payroll Type</b>");
    $items[] = array("span" => 2, "caption" => "<b>Factor</b>");
    $items[] = array("span" => 7, "caption" => "<b>SUBJECTED TO</b>");
    $items[] = array("span" => 3, "caption" => "<b>Reference</b>");
    return $items;
}

function get_paytpe_columns()
{
    $items = array();
    $items[] = array("field" => "recid", "caption" => "No", "size" => "50px", "attr" => "align=right");
    $items[] = array("field" => "pay_type", "caption" => "Name", "size" => "150px");
    $items[] = array("field" => "fpay", "caption" => "to Payrate", "size" => "80px", "attr" => "align=center");
    $items[] = array("field" => "famt", "caption" => "Amount", "size" => "70px", "render" => "float:2");
    $items[] = array("field" => "tax", "caption" => "TAX", "size" => "50px", "attr" => "align=center");
    $items[] = array("field" => "sss", "caption" => "SSS", "size" => "50px", "attr" => "align=center");
    $items[] = array("field" => "cola", "caption" => "COLA", "size" => "50px", "attr" => "align=center");
    $items[] = array("field" => "13th", "caption" => "13th Month", "size" => "80px", "attr" => "align=center");
    $items[] = array("field" => "other", "caption" => "Other Deductions", "size" => "120px", "attr" => "align=center");
    $items[] = array("field" => "adj", "caption" => "Adjustment", "size" => "100px", "attr" => "align=center");
    $items[] = array("field" => "neg", "caption" => "Negative", "size" => "100px", "attr" => "align=center");
    $items[] = array("field" => "uid", "caption" => "USER ID", "size" => "100px");
    $items[] = array("field" => "ip", "caption" => "STATION", "size" => "150px");
    $items[] = array("field" => "ts", "caption" => "TIMESTAMP", "size" => "200px");
    return $items;
}

function get_paytype_records()
{
    global $db, $db_hris;

    $records = array();
    $pay_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` ORDER BY `pay_type` ASC");
    $pay_type->execute();
    if ($pay_type->rowCount()) {
        while ($pay_type_data = $pay_type->fetch(PDO::FETCH_ASSOC)) {
            $record["recid"] = $pay_type_data['payroll_type_no'];
            $record["pay_type"] = $pay_type_data['pay_type'];
            $record["fpay"] = $pay_type_data['is_factor_to_payrate'] ? 'Y' : 'N';
            $record["famt"] = $pay_type_data['factor_amount'];
            $record["tax"] = $pay_type_data['is_subject_to_tax'] ? 'Y' : 'N';
            $record["sss"] = $pay_type_data['is_subject_to_sss'] ? 'Y' : 'N';
            $record["cola"] = $pay_type_data['is_subject_to_cola'] ? 'Y' : 'N';
            $record["13th"] = $pay_type_data['is_subject_to_13th'] ? 'Y' : 'N';
            $record["other"] = $pay_type_data['is_subject_to_other_ded'] ? 'Y' : 'N';
            $record["adj"] =  $pay_type_data['is_adjustment'] ? 'Y' : 'N';
            $record["neg"] = $pay_type_data['is_negative'] ? 'Y' : 'N';
            $record["uid"] = $pay_type_data['user_id'];
            $record["ip"] = $pay_type_data['station_id'];
            $record["ts"] = $pay_type_data['time_stamp'];
            array_push($records, $record);
        }
        echo json_encode(array("status" => "success", "columnGroups" => get_columns_group(), "columns" => get_paytpe_columns(), "records" => $records, "count" => count($records)));
    } else {
        echo json_encode(array("status" => "error", "message" => "No record found", "e" => $pay_type->errorInfo()));
    }
}

function get_pay_type()
{ ?>
    <div id="new_set_div" class="w3-padding-small">
        <table class="w3-table-all w3-small">
            <tr>
                <td style="width: 230px;">
                    <input type="text" id="pay_desc" name="pay_desc" style="padding: 2px 4px; width: 100%;" class="w3-border w3-border-grey w3-round-medium w3-hover-border-orange" placeholder="Payroll Type name..">
                </td>
                <td class="w3-center" style="width: 80px;"><input type="checkbox" id="fr" name="fr" style="margin-top: 4px;"></td>
                <td class="w3-center" style="width: 70px;">
                    <input type="text" id="fa" name="fa" style="padding: 2px 4px; width: 100%;" class="w3-border w3-border-grey w3-round-medium w3-hover-border-orange" placeholder="Amount..">
                </td>
                <td class="w3-center" style="width: 50px;"><input type="checkbox" id="st" name="st" style="margin-top: 4px;"></td>
                <td class="w3-center" style="width: 50px;"><input type="checkbox" id="ss" name="ss" style="margin-top: 4px;"></td>
                <td class="w3-center" style="width: 50px;"><input type="checkbox" id="sc" name="sc" style="margin-top: 4px;"></td>
                <td class="w3-center" style="width: 80px;"><input type="checkbox" id="s1" name="s1" style="margin-top: 4px;"></td>
                <td class="w3-center" style="width: 120px;"><input type="checkbox" id="sod" name="sod" style="margin-top: 4px;"></td>
                <td class="w3-center" style="width: 100px;"><input type="checkbox" id="adj" name="adj" style="margin-top: 4px;"></td>
                <td class="w3-center" style="width: 100px;"><input type="checkbox" id="neg" name="neg" style="margin-top: 4px;"></td>
                <td class="w3-left" colspan="3">
                    <input type="button" onclick="save_pay_type()" id="save" style="padding: 2px 10px; cursor: pointer;" class="w3-hover-orange w3-round-medium w3-hover-text-white w3-button" value="SAVE" />
                </td>
            </tr>
        </table>
    </div>
    <div id="my_grid1" class="w3-transparent" style="width: 100%;"></div>
    <script>
        $(document).ready(function() {
            var c = $("div#my_grid1");
            var h = window.innerHeight - 185;
            c.css("height", h);
            get_records();
        });

        function get_records() {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-paytype-records"
                },
                dataType: "json",
                success: function(data) {
                    if (data !== "") {
                        if (data.status === "success") {
                            w2ui.my_grid1.clear();
                            w2ui.my_grid1.columnGroups = data.columnGroups;
                            w2ui.my_grid1.columns = data.columns;
                            w2ui.my_grid1.add(data.records);
                        } else {
                            w2alert(data.message);
                        }
                        w2utils.unlock(div);
                    }
                },
                error: function() {
                    w2alert("Sorry, there was a problem in server connection!");
                    w2utils.unlock(div);
                }
            });
        }

        $(function() {
            $('#my_grid1').w2grid({
                name: 'my_grid1',
                show: {
                    toolbar: false,
                    footer: true,
                    lineNumbers: true,
                    header: false,
                    toolbarColumns: false,
                },
                columnGroups: [],
                columns: [],
                records: []
            });
        });

        function save_pay_type() {
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "save-pay-type",
                    pay_desc: $('#pay_desc').val(),
                    isFactortoRate: $("#fr").is(":checked"),
                    factorAmt: $('#fa').val(),
                    tax: $("#st").is(":checked"),
                    sss: $("#ss").is(":checked"),
                    cola: $("#sc").is(":checked"),
                    month: $("#s1").is(":checked"),
                    od: $("#sod").is(":checked"),
                    adj: $("#adj").is(":checked"),
                    neg: $("#neg").is(":checked")
                },
                success: function(data) {
                    if (data !== "") {
                        var _return = jQuery.parseJSON(data);
                        if (_return.status == "success") {
                            w2utils.unlock(div);
                            $('#pay_desc').val("");
                            $('#fa').val("");
                            if ($("#fr").is(":checked")) {
                                $('#fr').prop("checked", false);
                            }
                            if ($("#st").is(":checked")) {
                                $('#st').prop("checked", false);
                            }
                            if ($("#ss").is(":checked")) {
                                $('#ss').prop("checked", false);
                            }
                            if ($("#sc").is(":checked")) {
                                $('#sc').prop("checked", false);
                            }
                            if ($("#s1").is(":checked")) {
                                $('#s1').prop("checked", false);
                            }
                            if ($("#sod").is(":checked")) {
                                $('#sod').prop("checked", false);
                            }
                            if ($("#adj").is(":checked")) {
                                $('#adj').prop("checked", false);
                            }
                            if ($("#neg").is(":checked")) {
                                $('#neg').prop("checked", false);
                            }
                            get_records();
                        } else {
                            w2alert(_return.message);
                            w2utils.unlock(div);
                        }
                    } else {
                        w2alert("Sorry, There was a problem in server connection!");
                    }
                },
                error: function() {
                    w2alert("Sorry, There was a problem in server connection!");
                }
            });
        }
    </script>
<?php
}

function get_pay_group($access_rights)
{ ?>
    <style type="text/css">
        td,
        th {
            text-align: left;
            padding: 2px;
        }
    </style>
    <div class="w3-small w3-padding-top" style="width: 70%;">
        <table class="w3-table-all w3-border">
            <thead>
                <tr>
                    <th></th>
                    <th>Payroll Group Name</th>
                    <th>Cut Off Date</th>
                    <th>Payroll Date</th>
                    <th>
                        <?php if (substr($access_rights, 0, 2) === "A+") { ?>
                            <a onclick="new_pay_group()" class="w3-hover-text-orange" id="new_pay"><ion-icon class="w3-medium w3-padding-top" name="add-circle-outline"></ion-icon></a>
                            <a onclick="cancel_pay_group()" class="w3-hover-text-red w3-hide" id="cancel_pay"><ion-icon class="w3-medium w3-padding-top" name="remove-circle-outline"></ion-icon></a>
                        <?php } ?>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr class="w3-hide" id="new_set">
                    <td></td>
                    <td><input id="pay_name" name="pay_name" style="padding: 2px 4px; width: 100%;" class="w3-border w3-round-medium"></td>
                    <td><input id="cuttoff_date" name="cuttoff_date" class="date"></td>
                    <td><input id="payroll_date" name="payroll_date" class="date"></td>
                    <td>
                        <?php if (substr($access_rights, 0, 2) === "A+") { ?>
                            <input type="checkbox" onclick="save_pay_group(0)">&nbsp;SAVE
                        <?php } ?>
                    </td>
                    <td></td>
                </tr>
                <?php
                global $db_hris, $db;

                $emp_status = $db->query("SELECT * FROM $db_hris.`employment_status` WHERE `employment_status_code` > 0 ORDER BY `description`");
                if ($emp_status->rowCount()) {
                    $count = 0;
                    $pay_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:no");
                    while ($emp_status_data = $emp_status->fetch(PDO::FETCH_ASSOC)) {
                        $count++;
                        $pay_group->execute(array(":no" => $emp_status_data["employment_status_code"]));
                        if ($pay_group->rowCount()) {
                            $pay_group_data = $pay_group->fetch(PDO::FETCH_ASSOC);
                            $code = $emp_status_data["employment_status_code"];
                            $desc = $emp_status_data["description"];

                ?>
                            <tr id="<?php echo $code ?>" style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white">
                                <td><?php echo $count; ?>.</td>
                                <td><?php echo $desc; ?></td>
                                <td><input id="cuttoff_date<?php echo $code ?>" name="cuttoff_date" class="date" value="<?php echo date('m/d/Y', strtotime($pay_group_data['cutoff_date'])); ?>
                        "></td>
                                <td><input id="payroll_date<?php echo $code ?>" name="payroll_date" class="date" value="<?php echo date('m/d/Y', strtotime($pay_group_data['payroll_date'])); ?>"></td>
                                <td>
                                    <?php if (substr($access_rights, 0, 4) === "A+E+") { ?>
                                        <input type="checkbox" onclick="save_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;SAVE
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (substr($access_rights, 4, 2) === "D+") { ?>
                                        <input type="checkbox" onclick="del_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;DEL
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php
                        } else { ?>
                            <tr id="<?php echo $emp_status_data['employment_status_code']; ?>" style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white">
                                <td><?php echo $count; ?>.</td>
                                <td><?php echo $emp_status_data["description"]; ?></td>
                                <td><input id="cuttoff_date<?php echo $emp_status_data['employment_status_code']; ?>" name="cuttoff_date" class="date"></td>
                                <td><input id="payroll_date<?php echo $emp_status_data['employment_status_code']; ?>" name="payroll_date" class="date"></td>
                                <td>
                                    <?php if (substr($access_rights, 0, 4) === "A+E+") { ?>
                                        <input type="checkbox" onclick="save_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;SAVE
                                    <?php } ?>
                                </td>
                                <td>
                                    <?php if (substr($access_rights, 4, 2) === "D+") { ?>
                                        <input type="checkbox" onclick="del_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;DEL
                                    <?php } ?>
                                </td>
                            </tr>
                    <?php
                        }
                    }
                    ?>
            </tbody>
        </table>
    <?php
                }
    ?>
    </div>

    <script type="text/javascript">
        $(":input.date").w2field("date");

        function save_pay_group($pay_group_code) {
            if ($pay_group_code == 0) {
                var group_name = $('#pay_name').val();
                var cuttoff_date = $('#cuttoff_date').val();
                var payroll_date = $('#payroll_date').val();
                $.ajax({
                    url: src,
                    method: "POST",
                    data: {
                        cmd: "new_group",
                        group_name: group_name,
                        payroll_date: payroll_date,
                        cuttoff_date: cuttoff_date
                    },
                    success: function(data) {
                        if (data !== "") {
                            var _return = jQuery.parseJSON(data);
                            if (_return.status === "success") {
                                load_data("pay_group");
                            } else {
                                w2alert(_return.message);
                            }
                        }
                    },
                    error: function() {
                        w2alert("Sorry, There was a problem in server connection!");
                    }
                });
            } else {
                var cuttoff_date = $('#cuttoff_date' + $pay_group_code).val();
                var payroll_date = $('#payroll_date' + $pay_group_code).val();
                $.ajax({
                    url: src,
                    method: "POST",
                    data: {
                        cmd: "update_group",
                        pay_group_code: $pay_group_code,
                        payroll_date: payroll_date,
                        cuttoff_date: cuttoff_date
                    },
                    success: function(data) {
                        if (data !== "") {
                            var _return = jQuery.parseJSON(data);
                            if (_return.status === "success") {
                                load_data("pay_group");
                            } else {
                                w2alert(_return.message);
                            }
                        }
                    },
                    error: function() {
                        w2alert("Sorry, There was a problem in server connection!");
                    }
                });
            }
        }

        function new_pay_group() {
            $('#new_set').removeClass('w3-hide');
            $('#cancel_pay').removeClass('w3-hide');
            $('#new_pay').addClass('w3-hide');
        }

        function cancel_pay_group() {
            $('#new_set').addClass('w3-hide');
            $('#cancel_pay').addClass('w3-hide');
            $('#new_pay').removeClass('w3-hide');
        }

        function del_pay_group(pay_group_code) {
            $.ajax({
                url: src,
                method: "POST",
                data: {
                    cmd: "del_group",
                    group_no: pay_group_code
                },
                success: function(data) {
                    if (data !== "") {
                        var _return = jQuery.parseJSON(data);
                        if (_return.status === "success") {
                            load_data("pay_group");
                        } else {
                            w2alert(_return.message);
                        }
                    }
                },
                error: function() {
                    w2alert("Sorry, There was a problem in server connection!");
                }
            });
        }
    </script>
<?php
}

function get_tabs($access_rights, $access_rights_grp, $access_rights_memo, $access_rights_hol, $level)
{
    $tabs = array();
    if (substr($access_rights_grp, 6, 2) === "B+" and substr($access_rights_memo, 6, 2) === "B+" and substr($access_rights_hol, 6, 2) === "B+") {
        $active = "pay_group";
    } else if (substr($access_rights_grp, 6, 2) === "B+" and substr($access_rights_memo, 6, 2) === "B+") {
        $active = "pay_group";
    } else if (substr($access_rights_memo, 6, 2) === "B+" and substr($access_rights_hol, 6, 2) === "B+") {
        $active = "swipe";
    } else if (substr($access_rights_grp, 6, 2) === "B+" and substr($access_rights_hol, 6, 2) === "B+") {
        $active = "pay_group";
    } else {
        if (substr($access_rights_grp, 6, 2) === "B+") {
            $active = "pay_group";
        }
        if (substr($access_rights_memo, 6, 2) === "B+") {
            $active = "swipe";
        }
        if (substr($access_rights_hol, 6, 2) === "B+") {
            $active = "hol";
        }
    }
    if (substr($access_rights_grp, 6, 2) === "B+") {
        $tabs[] = array("id" => "pay_group", "text" => "Payroll Group");
    }
    if (substr($access_rights_memo, 6, 2) === "B+") {
        $tabs[] = array("id" => "swipe", "text" => "Swipe Memo");
    }
    if (substr($access_rights_hol, 6, 2) === "B+") {
        $tabs[] = array("id" => "hol", "text" => "Holidays");
    }
    if ($level > 9 and substr_count($access_rights, "A+E+D+B+P+")) {
        $tabs[] = array("id" => "pay_type", "text" => "Payroll Type");
        $tabs[] = array("id" => "sys_config", "text" => "System Configuration");
    }
    echo json_encode(array("status" => "success", "tabs" => $tabs, "active" => $active));
}
