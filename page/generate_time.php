<?php
$program_code = 2;
require_once('../common/functions.php');
include("../common_function.class.php");

$cfn = new common_functions();
$date = (new DateTime($_GET["trans_date"]))->format("m/d/Y");
$trans_date = $cfn->datefromtable($date);
$store = $_GET["_store"];
$group = $_GET["_group"];

$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 0, 6) !== "A+E+D+") {
    if($level <= $plevel ){
        echo json_encode(array("status" => "error", "message" => "Higher level required!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
mysqli_query($con, "DELETE FROM `_tmp_time`") or die(mysqli_error($con));

$master =  mysqli_query($con, "SELECT * FROM `attendance_log`,`master_data` WHERE `master_data`.`pin`=`attendance_log`.`pin` AND `master_data`.`store`='$store' AND `master_data`.`group_no`='$group' AND `attendance_log`.`log_date`='$trans_date' GROUP BY `attendance_log`.`pin` ORDER BY `attendance_log`.`row_id`");
$master_row=mysqli_num_rows($master);
if($master_row > 0){
    while($master_data = mysqli_fetch_array($master)){
        $emp_no = $master_data["pin"];
        $date = $master_data["log_date"];
        $time = $master_data["log_time"];

        $time_data[] = array("pin" => $emp_no, "date" => $date);
    }
    foreach($time_data as $val => $emp_data){
            
        $uid = $emp_data['pin'];
        $trans_date1 = $emp_data['date'];
        
        $attendancelog =  mysqli_query($con,"SELECT * FROM `attendance_log` WHERE `pin`='$uid' AND `log_date`='$trans_date1' ORDER BY `log_date`,`log_time`");
    ?>
    <div>
    <?php
        if (@mysqli_num_rows($attendancelog)) { ?>
            <table>
                <thead>
                    <tr>
                        <th></th>
                        <th>TIME</th>
                        <th>REASON</th>
                        <th>HRS:MINS</th>
                    </tr>
                </thead>
                <tbody>
            <?php
                $cnt = $total_time = $start_time = 0;
                while ($attendancelog_data =  mysqli_fetch_array($attendancelog)) {
                    $log_settings_data =  mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `log_type` WHERE `log_value`='$attendancelog_data[log_type]'"));
                ?>
                    <tr class="log">
                        <td><?php echo number_format(++$cnt); ?>.</td>
                        <td align="center"><?php echo substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 10); ?></td>
                        <td><?php echo $log_settings_data["log_message"]; ?></td>
                        <td align="center"><?php
                            if ($cnt !== 1) {
                                $end_time = substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 11, 2) * 60 + substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 14, 2);                                $timex =  gettime($start_time, $end_time, $log_type, $uid, $date);
                                $start_time = $end_time;
                                $date = $attendancelog_data["log_date"];
                                $log_type = $attendancelog_data["log_type"];
                            } else {
                                $timex = "";
                                $log_type = $attendancelog_data["log_type"];
                                $date = $attendancelog_data["log_date"];
                                $start_time =  substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 11, 2) * 60 + substr($attendancelog_data["log_date"].' '.$attendancelog_data["log_time"], 14, 2);
                            }
                            echo $timex;
                            ?>
                        </td>
                    </tr>
                <?php 
                }
                ?>
                </tbody>
            </table>
        <?php 
        }
    }
    echo "success";
}else{
    echo "Error";
}
?>
</div>
<div class="w3-left">
    <table>
        <thead>
            <tr>
                <th></th>
                <th>TIME DESC</th>
                <th>TIME</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $time =  mysqli_query($con, "SELECT * FROM `_tmp_time`,`log_type` WHERE `_tmp_time`.`log_value`=`log_type`.`log_value` ORDER BY `_tmp_time`.`id`");
            $cnt = 0;
            if (@mysqli_num_rows($time))
                while ($time_data =  mysqli_fetch_array($time)) { ?>
                <tr class="summary">
                    <td><?php echo number_format(++$cnt); ?>.</td>
                    <td><?php if ($time_data["log_value"] == 0) echo "TIME";
                        else echo $time_data["log_message"]; ?></td>
                    <td align="center">
                        <?php
                        $time_min = $time_data["mins"] % 60;
                        $time_hrs = ($time_data["mins"] - $time_min) / 60;
                        echo  substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);
                        ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php

function gettime($start_time, $end_time, $log_type, $uid, $date){
    global $db, $db_hris;

    $time = $end_time - $start_time;
    $time_min = $time % 60;
    $time_hrs = ($time - $time_min) / 60;
    $timex =  substr(number_format($time_hrs + 100, 0), 1, 2) . ":" . substr(number_format($time_min + 100), 1, 2);

    $check = $db->prepare("SELECT * FROM $db_hris.`_tmp_time` WHERE `pin` LIKE '$uid' AND `log_value`='$log_type'");
    $check->execute();
    if ($check->rowCount()) {
        $update = $db->prepare("UPDATE `_tmp_time` SET `mins`=`mins`+:_time WHERE `pin` LIKE :pin AND `log_value`=:log_type AND `date`=:date");
        $update->execute(array(":_time" => $time, ":pin" => $uid, ":log_type" => $log_type, ":date" => $date));
    } else {

        $insert = $db->prepare("INSERT INTO `_tmp_time` (`pin`, `log_value`, `mins`, `date`) VALUES (:pin, :log_type, :_time, :date)");

        $insert->execute(array(":pin" => $uid, ":log_type" => $log_type, ":_time" => $time, ":date" => $date));
    }
    return $timex;
}


?>