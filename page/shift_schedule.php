<?php
$program_code = 29;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
switch ($_REQUEST["cmd"]) {
    case "del-shift":
        if (substr($access_rights, 4, 2) === "D+") {
            delete_shift(array("recid" => $_POST["recid"], "code" => $_POST["shift"]));
        }else{
            echo json_encode(array("status" => "error", "message" => "No access rights!!"));
        }
        break;
    case "del-duty":
        if (substr($access_rights, 4, 2) === "D+") {
            delete_duty(array("code" => $_POST["code"], "recid" => $_POST["recid"]));
        }else{
            echo json_encode(array("status" => "error", "message" => "No access rights!!"));
        }
        break;
    case "save-new-sched":
        if (substr($access_rights, 0, 2) === "A+") {
            $error = 0;
            $record = array("recid" => $_POST["recid"]);
            if (isset($_POST["time"])) {
                if (trim($_POST["time"]) !== "") {
                    if (strpos($_POST["time"], ":")) {
                        $time = explode(":", $_POST["time"]);
                        $record["hh"] = $time[0];
                        $record["mm"] = $time[1];
                    } else {
                        $error = 1;
                    }
                } else {
                    $error = 1;
                }
            } else {
                $error = 1;
            }
            if ($error) {
                echo json_encode(array("status" => "error", "message" => "Invalid time!", "d" => $_POST));
            } else {
                $record["duty"] = $_POST["duty"];
                save_new_sched($record);
            }
        }else{
            echo json_encode(array("status" => "error", "message" => "No access rights!!"));
        }
        break;
    case "save-edit-shift":
        if (substr($access_rights, 0, 4) === "A+E+") {
            $error = 0;
            $record = array("recid" => $_POST["recid"], "shift" => strtoupper(trim($_POST["shift"])));
            if (isset($_POST["time"])) {
                if (trim($_POST["time"]) !== "") {
                    if (strpos($_POST["time"], ":")) {
                        $time = explode(":", $_POST["time"]);
                        $record["hh"] = $time[0];
                        $record["mm"] = $time[1];
                    } else {
                        $error = 1;
                    }
                } else {
                $record["hh"] = $record["mm"] = 0;
                }
            } else {
                $record["hh"] = $record["mm"] = 0;
            }
            $record["late"] = $_POST["late"];
            if (isset($_POST["type"])) {
                if ($_POST["type"] === "open") {
                    $record["open"] = 1;
                    $record["off"] = 0;
                } else {
                    $record["open"] = 0;
                    $record["off"] = 1;
                }
            } else {
                $record["open"] = $record["off"] = 0;
            }
            if ($error) {
                echo json_encode(array("status" => "error", "message" => "Invalid start time!", "r" => $record, "i" => $_POST));
            } else {
                save_edit_shift($record);
            }
        }else{
            echo json_encode(array("status" => "error", "message" => "No access rights!!"));
        }
        break;
    case "save-new-shift": //save shift of shift sets
        if (substr($access_rights, 0, 2) === "A+") {
            $error = 0;
            $record = array("recid" => $_POST["shift_id"], "shift" => strtoupper(trim($_POST["shift"])));
            if (isset($_POST["time"])) {
                if (trim($_POST["time"]) !== "") {
                    if (strpos($_POST["time"], ":")) {
                        $time = explode(":", $_POST["time"]);
                        $record["hh"] = $time[0];
                        $record["mm"] = $time[1];
                    } else {
                        $error = 1;
                    }
                } else {
                $record["hh"] = $record["mm"] = 0;
                }
            } else {
                $record["hh"] = $record["mm"] = 0;
            }
            $record["late"] = $_POST["late"];
            if (isset($_POST["type"])) {
                if ($_POST["type"] === "open") {
                $record["open"] = 1;
                $record["off"] = 0;
                } else {
                $record["open"] = 0;
                $record["off"] = 1;
                }
            } else {
                $record["open"] = $record["off"] = 0;
            }
            if ($error) {
                echo json_encode(array("status" => "error", "message" => "Invalid start time!", "r" => $record, "i" => $_POST));
            } else {
                save_new_shift($record);
            }
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
        break;
    case "save-shift":
        if (substr($access_rights, 2, 2) === "E+") {
            save_set(array("set" => strtoupper(trim($_REQUEST["set"])), "recid" => $_REQUEST["shift_id"]));
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
        break;
    case "default":
        set_default();
        break;
    case "save-new-set":
        if (substr($access_rights, 0, 2) === "A+") {
            save_new_set(array("set" => strtoupper(trim($_REQUEST["set"]))));
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
        break;
    case "get-shift-detail":
        if (substr($access_rights, 6, 2) === "B+") {
            get_shift_detail($_REQUEST["shift_id"]);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
        break;
    case "get-shift-detail-shift":
        if (substr($access_rights, 6, 2) === "B+") {
            get_shift_detail_shift($_REQUEST["detail_shift_id"]);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
        break;
}

function delete_shift($record) {
    global $db, $db_hris;
    $delete_shift = $db->prepare("DELETE FROM $db_hris.`shift` WHERE `shift_code`=:code");
    $delete_shift->execute(array(":code" => $record["code"]));
    if ($delete_shift->rowCount()) {
        $w = $db->prepare("DELETE FROM $db_hris.`shift_detail` WHERE `shift_code`=:code");
        $w->execute(array(":code" => $record["code"]));
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $delete_shift->errorInfo(), "q" => $delete_shift, "d" => array(":code" => $record["code"])));
    }
}

function delete_duty($record) {
    global $db, $db_hris;
    $delete_shift = $db->prepare("DELETE FROM $db_hris.`shift_detail` WHERE `shift_code`=:no AND `shift_seq`=:code");
    $delete_shift->execute(array(":no" => $record["recid"], ":code" => $record["code"]));
    if ($delete_shift->rowCount()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $delete_shift->errorInfo(), "q" => $delete_shift, "d" => array(":no" => $record["recid"], ":code" => $record["code"])));
    }
}

function save_new_sched($record) {
    global $db, $db_hris;
    $shift = $db->prepare("INSERT INTO $db_hris.`shift_detail` (`shift_code`, `is_duty`, `hh`, `mm`) VALUES (:code, :is, :hh, :mm)");
    $shift->execute(array(":code" => $record["recid"], ":is" => $record["duty"], ":hh" => $record["hh"], ":mm" => $record["mm"]));
    if ($shift->rowCount()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $shift->errorInfo(), "q" => $shift, "d" => array(":code" => $record["recid"], ":is" => $record["duty"], ":hh" => $record["hh"], ":mm" => $record["mm"])));
    }
}

function save_edit_shift($record) {
    global $db, $db_hris;
    $shift = $db->prepare("UPDATE $db_hris.`shift` SET `shift_name`=:shift, `start_hh`=:hh, `start_mm`=:mm, `user_id`=:uid, `is_open`=:open, `is_off_duty`=:off, `past_late`=:late WHERE `shift_code`=:code");
    $shift->execute(array(":shift" => $record["shift"], ":hh" => $record["hh"], ":mm" => $record["mm"], ":uid" => $_SESSION["name"], ":code" => $record["recid"], ":open" => $record["open"], ":off" => $record["off"], ":late" => $record["late"]));
    if ($shift->rowCount()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $shift->errorInfo(), "q" => $shift, "d" => array(":shift" => $record["shift"], ":hh" => $record["hh"], ":mm" => $record["mm"], ":uid" => $_SESSION["name"], ":code" => $record["recid"], ":open" => $record["open"], ":off" => $record["off"])));
    }
}

//save shift for shift sets
function save_new_shift($record) {
    global $db, $db_hris;
    $shift = $db->prepare("INSERT INTO $db_hris.`shift` (`shift_name`, `start_hh`, `start_mm`, `user_id`, `shift_set_no`, `is_open`, `is_off_duty`, `past_late`) VALUES (:shift, :hh, :mm, :uid, :no, :open, :off, :late)");
    $shift->execute(array(":shift" => $record["shift"], ":hh" => $record["hh"], ":mm" => $record["mm"], ":uid" => $_SESSION["name"], ":no" => $record["recid"], ":open" => $record["open"], ":off" => $record["off"], ":late" => $record["late"]));
    if ($shift->rowCount()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $shift->errorInfo(), "q" => $shift, "d" => array(":shift" => $record["shift"], ":hh" => $record["hh"], ":mm" => $record["mm"], ":uid" => $_SESSION["name"], ":no" => $record["recid"], ":open" => $record["open"], ":off" => $record["off"], ":late" => $record["late"])));
    }
}

//update shift sets
function save_set($record) {
    global $db, $db_hris;
    $shift = $db->prepare("UPDATE $db_hris.`shift_set` SET `description`=:set WHERE `shift_set_no`=:no");
    $shift->execute(array(":set" => $record["set"], ":no" => $record["recid"]));
    if ($shift->rowCount()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $shift->errorInfo(), "q" => $shift, "d" => array(":set" => $record["set"], ":no" => $record["recid"])));
    }
}

//save new shift
function save_new_set($record) {
    global $db, $db_hris;
    $shift = $db->prepare("INSERT INTO $db_hris.`shift_set` (`description`) VALUES (:set)");
    $shift->execute(array(":set" => $record["set"]));
    if ($shift->rowCount()) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error", "message" => "Please try again later!", "e" => $shift->errorInfo(), "q" => $shift, "d" => array(":set" => $record["set"])));
    }
}

function get_shift_detail_shift($detail_shift_id) {
    global $db, $db_hris, $cfn;
    $access_rights = $cfn->get_user_rights(29);
    $shifts = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:no");
    $shifts->execute(array(":no" => $detail_shift_id)); 
    if ($shifts->rowCount()) {
        $shifts_data = $shifts->fetch(PDO::FETCH_ASSOC);
    } ?>
    <table class="w3-tiny">
        <thead>
            <tr>
                <th></th>
                <th class="w3-center">SHIFT NAME</th>
                <th class="w3-center" style="width: 100px;">START TIME</th>
                <th class="w3-center" style="width: 100px;">LATE</th>
                <th class="w3-center">OPEN</th>
                <th class="w3-center">OFF DUTY</th>
                <th style="width: 50px;"></th>
            </tr>
            <tr>
                <td colspan="2"><input id="nshift_e" type="text" class="w3-input w3-border w3-round-medium  w3-small" value="<?php echo $shifts_data["shift_name"]; ?>" /></td>
                <td><input id="start_e" type="text" class="w3-input w3-border w3-round-medium w3-small" placeholder="time.." style="width: 100%;" value="<?php echo $shifts_data["start_hh"] . ":" . $shifts_data["start_mm"]; ?>" /></td>
                <td><input id="late_e" type="text" class="w3-input w3-border w3-round-medium  w3-small" placeholder="late time.." style="width: 100%;" value="<?php echo $shifts_data["past_late"]; ?>" /></td>
                <td class="w3-center"><input id="open_e" <?php
                    if ($shifts_data["is_open"]) {
                        echo "checked";
                    }
                    ?> name="opt_e" type="radio" value="open" /></td>
                <td class="w3-center"><input id="off_e" <?php
                    if ($shifts_data["is_off_duty"]) {
                        echo "checked";
                    }
                    ?> name="opt_e" type="radio" value="off" /></td>
                <td class="w3-center">
                    <?php if (substr($access_rights, 0, 4) === "A+E+") { ?>
                    <i class="fa-solid fa-floppy-disk  w3-small" style="cursor: pointer;" onclick="save_edit_shift('<?php echo $_REQUEST["detail_shift_id"]; ?>');" aria-hidden="true"></i>&nbsp;&nbsp;
                    <i class="fa-solid fa-file  w3-small" style="cursor: pointer;" title="Create new schedule..." onclick="new_sched_shift();" aria-hidden="true"></i>
                    <?php } ?>
                </td>
            </tr>
        </thead>
    </table>
    <table class="w3-table-all w3-hoverable w3-small">
        <thead>
            <tr>
                <th style="width: 50px;"></th>
                <th style="width: 50px;" class="w3-center">DUTY</th>
                <th style="width: 80px;" class="w3-center">TIME</th>
                <th class="w3-center">SCHEDULE</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php
            $detail_shift = $db->prepare("SELECT * FROM $db_hris.`shift_detail` WHERE `shift_code`=:code ORDER BY `shift_seq`");
            $detail_shift->execute(array(":code" => $detail_shift_id));
            if ($detail_shift->rowCount()) {
                $cnt = 0;
                $break = 0;
                $date = new DateTime(date("m/d/Y"));
                if (number_format($shifts_data["start_hh"], 0, '.', '') !== number_format(0, 0)) {
                    $date->modify("+$shifts_data[start_hh] HOURS");
                }
                if (number_format($shifts_data["start_mm"], 0, '.', '') !== number_format(0, 0)) {
                    $date->modify("+$shifts_data[start_mm] MINUTES");
                }
                while ($detail_shift_data = $detail_shift->fetch(PDO::FETCH_ASSOC)) {
                    $startt = $date->format("h:i A");
                    if (number_format($detail_shift_data["hh"], 0, '.', '') !== number_format(0, 0)) {
                        $date->modify("+$detail_shift_data[hh] HOURS");
                    }
                    if (number_format($detail_shift_data["mm"], 0, '.', '') !== number_format(0, 0)) {
                        $date->modify("+$detail_shift_data[mm] MINUTES");
                    }
                    $endt = $date->format("h:i A");
                    if (!$detail_shift_data["is_duty"]) {
                        $break += $detail_shift_data["hh"] * 60 + $detail_shift_data["mm"];
                    } ?>
                <tr>
                    <td><?php echo number_format( ++$cnt, 0); ?></td>
                    <td class="w3-center">
                    <?php
                        if ($detail_shift_data["is_duty"]) {
                        echo "<i class=\"fa-solid fa-check\" aria-hidden=\"true\"></i>";
                        } ?>
                    </td>
                    <td class="w3-center"><?php echo $detail_shift_data["hh"] . ":" . $detail_shift_data["mm"]; ?></td>
                    <td class="w3-center"><?php echo $startt . " - " . $endt; ?></td>
                    <td class="w3-center">
                    <?php if (substr($access_rights, 4, 2) === "D+") { ?>
                        <i class="fa-solid fa-eraser" style="cursor: pointer;" onclick="del_duty('<?php echo $detail_shift_data["shift_seq"]; ?>', '<?php echo $detail_shift_id; ?>')" aria-hidden="true"></i></td>
                    <?php } ?>
                </tr>
                <?php
                } ?>
                <tr>
                    <td colspan="5" class="w3-black">
                        ALLOWABLE BREAK TIME: <?php
                        $b = number_format($break, 0, '.', '');
                        $d = new DateTime(date("m/d/Y"));
                        $d->modify("+$b minutes");
                        echo $d->format("H:i");
                        ?>
                    </td>
                </tr><?php
            } ?>
        </tbody>
        <tfoot>
            <tr id="newsched" class="w3-hide">
                <td></td>
                <td class="w3-center">
                    <input id="sduty" type="checkbox" />
                </td>
                <td>
                    <input id="dtime" type="text" class="w3-border-bottom w3-border-grey" />
                </td>
                <td></td>
                <td class="w3-center">
                    <i class="fa-solid fa-floppy-disk" style="cursor: pointer;" onclick="save_new_sched('<?php echo $detail_shift_id; ?>');" aria-hidden="true"></i>&nbsp;&nbsp;
                </td>
            </tr>
        </tfoot>        
    </table>
<?php
}

function get_shift_detail($shift_id) {
    global $db, $db_hris, $cfn;
    $access_rights = $cfn->get_user_rights(29);
    $shift = $db->prepare("SELECT * FROM $db_hris.`shift_set` WHERE `shift_set_no`=:no");
    $shift->execute(array(":no" => $shift_id)); 
    if ($shift->rowCount()) {
        $shift_data = $shift->fetch(PDO::FETCH_ASSOC);
    }
    ?>
    <table class="w3-table-all w3-hoverable w3-small">
        <thead>
            <tr>
                <th colspan="6">
                    <input id="set_e" type="text" class="w3-input" value="<?php echo $shift_data["description"]; ?>"  />
                </th>
                <th class="w3-center">
                    <?php if (substr($access_rights, 2, 2) === "E+") { ?>
                    <i class="fa-solid fa-floppy-disk w3-margin-top" style="cursor: pointer;" onclick="save_shift('<?php echo $shift_id; ?>');" aria-hidden="true"></i>&nbsp;&nbsp;&nbsp;
                    <?php } if (substr($access_rights, 0, 2) === "A+") { ?>
                    <i class="fa-solid fa-file w3-margin-top" style="cursor: pointer;" title="Create new shift of shift schedule..." onclick="new_shift();" aria-hidden="true"></i>
                    <?php } ?>
                </th>
            </tr>
            <tr>
                <th></th>
                <th class="w3-center">SHIFT NAME</th>
                <th class="w3-center">START TIME</th>
                <th class="w3-center">LATE</th>
                <th class="w3-center">OPEN</th>
                <th class="w3-center">OFF DUTY</th>
                <th style="width: 50px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php
        $cnt = 0;
        $shift_detail = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_set_no`=:no ORDER BY `shift_code`");
        $shift_detail->execute(array(":no" => $shift_id)); 
        if ($shift_detail->rowCount()) {
            $cnt = 0;
            while ($shift_detail_data = $shift_detail->fetch(PDO::FETCH_ASSOC)) { ?>
            <tr style="cursor: pointer;" onclick="show_detail('<?php echo $shift_detail_data["shift_code"]; ?>');">
                <td><?php echo number_format(++$cnt, 0); ?></td>
                <td><?php echo $shift_detail_data["shift_name"]; ?></td>
                <td class="w3-center"><?php echo $shift_detail_data["start_hh"] . ":" . $shift_detail_data["start_mm"]; ?></td>
                <td class="w3-center"><?php echo $shift_detail_data["past_late"]; ?></td>
                <td class="w3-center"><?php
                    if ($shift_detail_data["is_open"]) {
                        echo "<i class=\"fa-solid fa-check\" aria-hidden=\"true\"></i>";
                    } ?></td>
                <td class="w3-center"><?php
                    if ($shift_detail_data["is_off_duty"]) {
                        echo "<i class=\"fa-solid fa-check\" aria-hidden=\"true\"></i>";
                    } ?></td>
                <td class="w3-center">
                    <?php if (substr($access_rights, 4, 2) === "D+") {  ?> 
                    <i class="fa-solid fa-eraser" style="cursor: pointer;" onclick="del_shift('<?php echo $shift_detail_data["shift_code"]; ?>', '<?php echo $shift_id; ?>');" aria-hidden="true"></i>
                    <?php } ?>
                </td>
            </tr>
        <?php
            }
        }
        ?>
        </tbody>
        <tfoot>
            <tr id="shift_new" class="w3-hide">
                <td colspan="2">
                    <input id="nshift" type="text" class="w3-input" placeholder="Shift name..." style="width: 100%;" />
                    </td>
                <td><input id="start" type="text" class="w3-input" placeholder="Start" style="width: 100%;" /></td>
                <td><input id="late" type="text" class="w3-input" placeholder="Late" style="width: 100%;" /></td>
                <td class="w3-center"><input id="open" class="w3-margin-top" name="opt" type="radio" value="open" /></td>
                <td class="w3-center"><input id="off" class="w3-margin-top" name="opt" type="radio" value="off" /></td>
                <td class="w3-center">
                    <i class="fa-solid fa-floppy-disk w3-margin-top" style="cursor: pointer;" onclick="save_new_shift('<?php echo $shift_id; ?>');" aria-hidden="true"></i>
                    &nbsp;
                    <i class="fa-solid fa-rotate-left w3-margin-top" style="cursor: pointer;" onclick="cancel_new_shift();" aria-hidden="true"></i>
                </td>
            </tr>
        </tfoot>
    </table>
    <?php
}


function set_default() {
    global $db, $db_hris, $cfn;
    $access_rights = $cfn->get_user_rights(29);
    ?>
    <div class="window w3-col l12 m12 s12 w3-responsive w3-mobile w3-row" style="overflow-y: scroll;">
        <div class="w3-col s4 w3-padding w3-row-padding">
            <table class="w3-table-all w3-hoverable w3-small">
            <thead>
                <tr>
                    <th colspan="2" class="w3-center">
                        AVAILABLE SHIFT'S SCHEDULE
                        <?php if (substr($access_rights, 0, 2) === "A+") { ?>
                        <i class="fa-solid fa-file w3-right w3-padding" style="cursor: pointer;" title="Create new set of shift schedule..." onclick="set_new();" aria-hidden="true"></i>
                        <?php } ?>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php
                $shift = $db->prepare("SELECT * FROM $db_hris.`shift_set` ORDER BY `description`");
                $shift->execute();
                if ($shift->rowCount()) {
                    $cnt = 0;
                    while ($shift_data = $shift->fetch(PDO::FETCH_ASSOC)) { ?>
                    <tr style="cursor: pointer;">
                        <td style="width: 15px;"><?php echo number_format( ++$cnt, 0); ?>.</td>
                        <td onclick="show_shift('<?php echo $shift_data["shift_set_no"]; ?>')"><?php echo $shift_data["description"]; ?></td>
                    </tr>
            <?php
                    }
                }?>
            </tbody>
            <tfoot>
                <tr id="newset" class="w3-hide">
                    <td colspan="2">
                        <input type="text" id="shift_set_new" class="w3-left w3-input" style="width: 80%;" size="50" placeholder="New set of shift schedule.." />
                        <div class="w3-right w3-padding">
                            <i class="fa-solid fa-floppy-disk" style="cursor: pointer;" onclick="save_new();" aria-hidden="true"></i>
                            &nbsp;&nbsp;&nbsp;
                            <i class="fa fa-undo" style="cursor: pointer;" onclick="cancel_new();" aria-hidden="true"></i>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="w3-col s4 w3-padding w3-row-padding" id="shift_detail"></div>
    <div class="w3-col s4 w3-padding w3-row-padding" id="shift_detail_shift"></div>
    <script type="text/javascript">
        const src = "page/shift_schedule";
        
        function del_shift(shift, shift_id) {
            console.log(shift, shift_id);
            w2confirm('Are you sure to delete this shift?', function (btn) {
                if (btn === "Yes") {
                    $.ajax({
                        url: src,
                        type: "post",
                        data: {
                            cmd: "del-shift",
                            shift: shift,
                            recid: shift_id
                        },
                        success: function (data) {
                            var jObject = jQuery.parseJSON(data);
                            if (jObject.status === "success") {
                                show_shift(shift_id);
                            } else {
                                w2alert(jObject.message);
                            }
                        },
                        error: function () {
                            show_shift(shift_id);
                        }
                    });
                }
            });
        }

        function del_duty(detail_shift_id, recid) {
            w2confirm('Are you sure to delete this time?', function (btn) {
                if (btn === "Yes") {
                    $.ajax({
                        url: src,
                        type: "post",
                        data: {
                            cmd: "del-duty",
                            code: detail_shift_id,
                            recid: recid
                        },
                        success: function (data) {
                            var jObject = jQuery.parseJSON(data);
                            if (jObject.status === "success") {
                                show_detail(recid)
                            } else {
                                w2alert(jObject.message);
                            }
                        },
                        error: function () {
                            show_detail(recid)
                        }
                    });
                }
            });
        }

        function new_sched_shift() {
            $("#newsched").removeClass("w3-hide");
        }

        function save_new_sched(recid) {
            var time = $(":input#dtime");
            if (time.val() === "") {
            time.focus();
            } else {
                var duty = 0;
                if ($(":input#sduty").is(":checked")) {
                    duty = 1;
                }
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                        cmd: "save-new-sched",
                        time: time.val(),
                        duty: duty,
                        recid: recid
                    },
                    success: function (data) {
                        var jObject = jQuery.parseJSON(data);
                        if (jObject.status === "success") {
                            show_detail(recid);
                        } else {
                            w2alert(jObject.message);
                        }
                    },
                    error: function () {
                        w2alert(jObject.message);
                    }
                });
            }
        }

        function save_edit_shift(recid) {
            var shift = $(":input#nshift_e");
            var time = $(":input#start_e");
            var type = $(":input[type='radio'][name='opt_e']:checked");
            var late = $(":input#late_e");
            if (shift.val() === "") {
            shift.focus();
            } else {
                if (late.val() === "") {
                    late.focus();
                } else {
                    $.ajax({
                        url: src,
                        type: "post",
                        data: {
                            cmd: "save-edit-shift",
                            shift: shift.val(),
                            time: time.val(),
                            type: type.val(),
                            late: late.val(),
                            recid: recid
                        },
                        success: function (data) {
                            var jObject = jQuery.parseJSON(data);
                            if (jObject.status === "success") {
                                show_detail(recid);

                            } else {
                                w2alert(jObject.message);
                            }
                        },
                        error: function () {
                            w2alert(jObject.message);
                        }
                    });
                }
            }
        }

        //new shift of shift sets
        function save_new_shift(shift_id) {
            var shift = $(":input#nshift");
            var time = $(":input#start");
            var type = $(":input[type='radio'][name='opt']:checked");
            var late = $(":input#late");
            if (shift.val() === "") {
                shift.focus();
            } else {
            if (late.val() === "") {
                late.focus();
            } else {
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                        cmd: "save-new-shift",
                        shift: shift.val(),
                        time: time.val(),
                        type: type.val(),
                        late: late.val(),
                        shift_id: shift_id
                    },
                    success: function (data) {
                        var jObject = jQuery.parseJSON(data);
                        if (jObject.status === "success") {
                            show_shift(shift_id);
                        } else {
                            w2alert(jObject.message);
                        }
                    },
                    error: function () {
                        show_shift(shift_id);
                    }
                });
            }
            }
        }

        //update shift set name
        function save_shift(shift_id) {
            var set = $(":input#set_e");
            if (set.val() === "") {
                set.focus();
            } else {
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "save-shift",
                    set: set.val(),
                    shift_id: shift_id
                },
                success: function (data) {
                    var jObject = jQuery.parseJSON(data);
                    if (jObject.status === "success") {
                        set_shift_default();
                        show_shift(shift_id);
                    } else {
                        w2alert(jObject.message);
                    }
                },
                error: function () {
                    set_shift_default();
                    show_shift(shift_id);
                }
            });
            }
        }

        function show_detail(detail_shift_id) {
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-shift-detail-shift",
                    detail_shift_id: detail_shift_id
                },
                success: function (data) {
                    $('#shift_detail_shift').html(data);
                    $('#shift_detail_shift').show();
                }
            });
        }

        function show_shift(shift_id) {
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-shift-detail",
                    shift_id: shift_id
                },
                success: function (data) {
                    $('#shift_detail').html(data);
                    $('#shift_detail_shift').hide();
                }
            });
        }

        //create new shift schedule
        function save_new() {
            var set = $(":input#shift_set_new");
            if (set.val() === "") {
                set.focus();
            } else {
                w2confirm('Save new shift set?', function (btn) {
                    if (btn === "Yes") {
                        $.ajax({
                            url: src,
                            type: "post",
                            data: {
                            cmd: "save-new-set",
                            set: set.val()
                            },
                            success: function (data) {
                                var jObject = jQuery.parseJSON(data);
                                if (jObject.status === "success") {
                                    set_shift_default();
                                } else {
                                    w2alert(jObject.message);
                                }
                            },
                            error: function () {
                                set_shift_default();
                            }
                        });
                    }
                });
            }
        }

        //button click for new set of shift
        function set_new() {
            $("#newset").removeClass("w3-hide");
            $(":input#shift_set_new").focus();
        }

        //button click for cancel new set of shift
        function cancel_new() {
            $("#newset").addClass("w3-hide");
            $(":input#shift_set_new").val("");
        }

        function cancel_new_shift() {
            $("#shift_new").addClass("w3-hide");
            $(":input#nshift").val("");
        }

        function new_shift() {
            $("#shift_new").removeClass("w3-hide");
            $(":input#nshift").focus();
        }

    </script>
  <?php
}