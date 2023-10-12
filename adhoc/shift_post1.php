<?php
$program_code = 35;
include("../system.config.php");
include("../common_functions.php");
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
                case "get-default": //ok
                    if (substr($access_rights, 6, 2) === "B+") {
                        get_employee();
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "show-posted-shift":
                    if (substr($access_rights, 6, 2) === "B+") {
                        posted_shift($_POST["employee_no"]);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "post-shift":
                    if (substr($access_rights, 0, 6) === "A+E+D+") {
                        $current_date = (new DateTime($_POST["trans_date"]))->format("Y-m-d");
                        $emp_no = $_POST["employee_no"];
                        post_shift($current_date,$emp_no);
                    }else{
                        echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                        return;
                    }
                break;
                case "post-shift-all":
                    if (substr($access_rights, 0, 6) === "A+E+D+" AND $level>= 8) {
                        $system_date = (new DateTime($cfn->sysconfig("trans date")))->format("Y-m-d");
                        post_shift_all($system_date);
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

function post_shift($current_date,$emp_no){
    global $db, $db_hris;
    
    set_time_limit(300);
    $del_shift = $db->prepare("DELETE FROM $db_hris.`employee_work_schedule` WHERE `trans_date`=:tdate AND `employee_no`=:eno");
    $del_shift->execute(array(":tdate" => $current_date, ":eno" => $emp_no));
    $day = date('w', mktime(0, 0, 0, substr($current_date, 5, 2), substr($current_date, 8, 2), substr($current_date, 0, 4)));
    $master =  $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `work_schedule`!='' AND `employee_no`=:eno");
    $master->execute(array(":eno" => $emp_no));
    if($master->rowCount()){
        $master_data = $master->fetch(PDO::FETCH_ASSOC);
        set_time_limit(30);
        $shift =  explode(",",$master_data["work_schedule"]);
        $shift_schedule = $shift[$day];
        $work_sched = $db->prepare("INSERT INTO $db_hris.`employee_work_schedule` (`employee_no`, `trans_date`, `shift_code`) VALUES (:eno, :cdate, :sched)");
        $work_sched->execute(array(":eno" => $master_data["employee_no"], ":cdate" => $current_date, ":sched" => $shift_schedule));
        if($work_sched->rowCount()){
            echo json_encode(array("status" => "success", "message" => "ok"));
        }else{
            echo json_encode(array("status" => "success", "message" => "Error in posting shifts", "e" => $work_sched->errorInfo()));
        }
    }else{
        echo json_encode(array("status" => "error", "message" => "Employee not found!"));
    }
}

function post_shift_all($system_date){
    global $db, $db_hris;

    set_time_limit(300);
    $del_shift = $db->prepare("DELETE FROM $db_hris.`employee_work_schedule` WHERE `trans_date`=:tdate");
    $del_shift->execute(array(":tdate" => $system_date));
    $day = date('w', mktime(0, 0, 0, substr($system_date, 5, 2), substr($system_date, 8, 2), substr($system_date, 0, 4)));
    $master =  $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `work_schedule`!=''");
    $master->execute();
    if($master->rowCount()){
        while($master_data = $master->fetch(PDO::FETCH_ASSOC)){
            set_time_limit(30);
            $shift = explode(",",$master_data["work_schedule"]);
            $shift_schedule = $shift[$day];
            $plot_shift = $db->prepare("INSERT INTO $db_hris.`employee_work_schedule` (`employee_no`, `trans_date`, `shift_code`) VALUES (:emp_no, :tdate, :shift)");
            $plot_shift->execute(array(":emp_no" => $master_data["employee_no"], ":tdate" => $system_date, ":shift" => $shift_schedule));
        }
    }
    update_time($system_date);
}

function update_time($system_date){
	global $db, $db_hris;

    set_time_limit(300);
    $current_date = date('Y-m-d');
    $cdate = new DateTime($system_date);
	$dateNow = $cdate->modify('+1 day');
	if($system_date != $current_date){
		$update_trans_date = $db->prepare("UPDATE $db_hris.`_sysconfig` SET `config_value`=:cdate WHERE `config_name`=:cname");
		$update_trans_date->execute(array(":cdate" => $dateNow->format('Y-m-d'), ":cname"=> 'trans date'));
		echo json_encode(array("status" => "success", "message" => "DONE POSTING SHIFT FOR $system_date"));
	}else{
        echo json_encode(array("status" => "success", "message" => "DONE POSTING SHIFT FOR $system_date"));
	}
}


function posted_shift($emp_no){
    global $db, $db_hris; ?>
    <div class="w3-col s4">
    <?php
        $sc = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` INNER JOIN $db_hris.`shift` ON `shift`.`shift_code`=`employee_work_schedule`.`shift_code` INNER JOIN $db_hris.`shift_set` ON `shift_set`.`shift_set_no`=`shift`.`shift_set_no` WHERE `employee_no`=:no ORDER BY `trans_date` DESC");
        $sc->execute(array(":no" => $emp_no));
        if ($sc->rowCount()) { ?>
            <div class="w3-col s12 w3-row-padding w3-margin-bottom w3-responsive">
            <table class="w3-row-padding w3-table-all w3-tiny w3-hoverable">
                <thead>
                <tr>
                    <th>DATE</th>
                    <th>SHIFT</th>
                </tr>
                </thead>
                <tbody>
                <?php
                while ($sc_data = $sc->fetch(PDO::FETCH_ASSOC)) { ?>
                <tr>
                    <td><?php echo (new DateTime($sc_data["trans_date"]))->format("m/d/Y"); ?></td>
                    <td><?php echo $sc_data["description"] . " -> <span class=\"w3-text-green\">" . $sc_data["shift_name"] . "</span>"; ?></td>
                </tr>
                <?php
                } ?>
                </tbody>
            </table>
        </div>
        <?php
        } ?>
    </div>
<?php
}

function get_employee() {
    global $db, $db_hris;

    $emp_list = $db->prepare("SELECT `master_data`.`employee_no`,`master_data`.`pin`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`family_name` FROM $db_hris.`master_data` WHERE !`is_inactive` ORDER BY `master_data`.`family_name`");
    $employee_list = array();
    $emp_list->execute();
    if ($emp_list->rowCount()) {
        while ($emp_data = $emp_list->fetch(PDO::FETCH_ASSOC)) {
            $pin=$emp_data['pin'];
            $lname=$emp_data['family_name'];
            $fname=$emp_data['given_name'];
            $middle_name=$emp_data['middle_name'];
            if($middle_name != ''){
                $mname=substr($emp_data['middle_name'], 0, 1);
            }else{
                $mname='';
            }
            $name = $lname.', '.$fname.' '.$mname.' ('.$pin.')';

            $employee_list[] = array("id" => $emp_data["employee_no"], "text" => $name);
        }
    }
    echo json_encode(array("status" => "success", "employee_list" => $employee_list));
}