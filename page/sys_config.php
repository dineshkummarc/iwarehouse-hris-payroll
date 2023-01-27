<?php

$program_code = 3;
require_once('../common/functions.php');

switch ($_POST["cmd"]) {
    case "new_group":
        $group_name = $_POST["group_name"];
        $payroll_date = (new DateTime($_POST["payroll_date"]))->format("Y-m-d");
        $cuttoff_date = (new DateTime($_POST["cuttoff_date"]))->format("Y-m-d");
        new_group($group_name,$payroll_date,$cuttoff_date);
    break;
    case "del_group":
        $group_no = $_POST["group_no"];
        del_group($group_no);
    break;
    case "update_group":
        $pay_group_code = $_POST["pay_group_code"];
        $payroll_date = (new DateTime($_POST["payroll_date"]))->format("Y-m-d");
        $cuttoff_date = (new DateTime($_POST["cuttoff_date"]))->format("Y-m-d");
        update_group($pay_group_code,$payroll_date,$cuttoff_date);
    break;
    case "get-holiday":
        $year = date('Y');
        $prev_year = date("Y",strtotime("-1 year")); ?> 
        <div class="window w3-col l12 m12 s12 w3-responsive w3-mobile w3-row" style="overflow-y: scroll;">
            <div class="w3-col s4 w3-padding w3-row-padding">
                <table class="w3-table-all w3-hoverable w3-small">
                    <thead>
                        <tr>
                            <th colspan="4" class="w3-center">PREVIOS YEAR HOLIDAYS</th>
                        </tr>
                        <tr>
                            <th colspan="2" class="w3-center">HOLIDAY DATE</th>
                            <th class="w3-center">HOLIDAY DESCRIPTION</th>
                            <th class="w3-center">is Special?</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $cnt = 0;
                        $holiday = "SELECT * FROM holiday WHERE holiday_date LIKE '%$prev_year%' ORDER BY holiday_date";
                        $rholiday = mysqli_query($con,$holiday);
                        if (@mysqli_num_rows($rholiday)) {
                            while ($holiday_data = mysqli_fetch_array($rholiday)) {
                            $hol_id = $holiday_data["holiday_id"];

                            if($holiday_data["holiday_date"] < date('Y-m-d')){ ?>
                            <tr>
                                <td align="right"><?php echo number_format(++$cnt); ?>.</td>
                                <td><?php echo $holiday_data["holiday_date"]; ?></td>
                                <td><?php echo $holiday_data["description"]; ?></td>
                                <td class="w3-center"><?php if($holiday_data["is_special"] == 1 ){ echo '<i class="fa-solid fa-check"></i>'; }else{ echo '<i class="fa-solid fa-times"></i>'; }?></td>
                            </tr>
                            <?php
                            }else{ ?>
                            <tr onclick="edit_del(<?php echo $hol_id; ?>)" style="cursor: pointer;">
                                <td align="right"><?php echo number_format(++$cnt); ?>.</td>
                                <td><?php echo $holiday_data["holiday_date"]; ?></td>
                                <td><?php echo $holiday_data["description"]; ?></td>
                                <td class="w3-center"><?php if($holiday_data["is_special"] == 1 ){ echo '<i class="fa-solid fa-check"></i>'; }else{ echo '<i class="fa-solid fa-times"></i>'; }?></td>
                            </tr>
                            <?php
                            }
                        }
                    } ?>
                    </tbody>
                </table>
            </div>
            <div class="w3-col s4 w3-padding w3-row-padding">
                <table class="w3-table-all w3-hoverable w3-small">
                    <thead>
                        <tr>
                            <th colspan="4" class="w3-center">CURRENT YEAR HOLIDAYS</th>
                        </tr>
                        <tr>
                            <th colspan="2" class="w3-center">HOLIDAY DATE</th>
                            <th class="w3-center">HOLIDAY DESCRIPTION</th>
                            <th class="w3-center">is Special?</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $cnt = 0;
                        $holiday = "SELECT * FROM holiday WHERE holiday_date LIKE '%$year%' ORDER BY holiday_date";
                        $rholiday = mysqli_query($con,$holiday);
                        if (@mysqli_num_rows($rholiday)) {
                            while ($holiday_data = mysqli_fetch_array($rholiday)) {
                            $hol_id = $holiday_data["holiday_id"];

                            if($holiday_data["holiday_date"] < date('Y-m-d',strtotime("-15 days"))){ ?>
                            <tr>
                                <td align="right"><?php echo number_format(++$cnt); ?>.</td>
                                <td><?php echo $holiday_data["holiday_date"]; ?></td>
                                <td><?php echo $holiday_data["description"]; ?></td>
                                <td class="w3-center"><?php if($holiday_data["is_special"] == 1 ){ echo '<i class="fa-solid fa-check"></i>'; }else{ echo '<i class="fa-solid fa-times"></i>'; }?></td>
                            </tr>
                            <?php
                            }else{ ?>
                            <tr onclick="edit_del(<?php echo $hol_id; ?>)" style="cursor: pointer;">
                                <td align="right"><?php echo number_format(++$cnt); ?>.</td>
                                <td><?php echo $holiday_data["holiday_date"]; ?></td>
                                <td><?php echo $holiday_data["description"]; ?></td>
                                <td class="w3-center"><?php if($holiday_data["is_special"] == 1 ){ echo '<i class="fa-solid fa-check"></i>'; }else{ echo '<i class="fa-solid fa-times"></i>'; }?></td>
                            </tr>
                            <?php
                            }
                        }
                    } ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php
    break;
    case "generate":
        generate_holiday();
    break;
    case "new-hol":
        $hol_date = (new DateTime($_POST["hol_date"]))->format("Y-m-d");
        $hol_name = $_POST["hol_name"];
        $special = $_POST["is_special"];
        new_holiday($hol_date,$hol_name,$special);
    break;
    case "del-hol":
        $hol_id = $_POST["hol_id"];
        delete_holiday($hol_id);
    break;
    case "default-pay-type":
        default_pay_type();
    break;
    case "save-pay-type":
        $pay_desc = $_POST['pay_desc'];
        if($_POST['isFactortoRate'] == 'true') $isFactortoRate=1; else $isFactortoRate=0;
        $factorAmt = $_POST['factorAmt'];
        if($_POST['tax'] == 'true') $tax=1; else $tax=0;
        if($_POST['sss'] == 'true') $sss=1; else $sss=0;
        if($_POST['cola'] == 'true') $cola=1; else $cola=0;
        if($_POST['month'] == 'true') $month=1; else $month=0;
        if($_POST['od'] == 'true') $od=1; else $od=0;
        if($_POST['adj'] == 'true') $adj=1; else $adj=0;
        if($_POST['neg'] == 'true') $neg=1; else $neg=0;
        save_pay_type($pay_desc,$isFactortoRate,$factorAmt,$tax,$sss,$cola,$month,$od,$adj,$neg);
    break;
    case "show-posted-shift":
        posted_shift($_POST["employee_no"]);
    break;
    case "post-shift":
        $current_date = (new DateTime($_POST["trans_date"]))->format("Y-m-d");
        $emp_no = $_POST["employee_no"];
        post_shift($current_date,$emp_no);
    break;

}

function generate_holiday() {
    global $db, $db_hris;

    $day = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date` >=:df AND `holiday_date`<=:dt ORDER BY `holiday_date`");
    $i = $db->prepare("INSERT INTO $db_hris.`holiday` (`holiday_date`, `is_special`, `description`) VALUES (:date, :is, :desc)");
    $prev_year = date("Y",strtotime("-1 year"));
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


function payroll_date() {
    global $db, $db_hris;
    
    $config = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :name");
    $config->execute(array(":name" => "trans date"));
    if ($config->rowCount()) {
        $config_data = $config->fetch(PDO::FETCH_ASSOC);
        $date = $config_data["config_value"];
    } else {
        $date = "";
    }
    return $date;
}

function post_shift($current_date,$emp_no){
    global $con, $db_hris;
    
    set_time_limit(300);
    mysqli_query($con, "DELETE FROM $db_hris.`employee_work_schedule` WHERE `trans_date`='$current_date' AND `employee_no`='$emp_no'") or die(mysqli_error($con));
    $day = date('w', mktime(0, 0, 0, substr($current_date, 5, 2), substr($current_date, 8, 2), substr($current_date, 0, 4)));
    $master=  mysqli_query($con, "SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `work_schedule`!='' AND `employee_no`='$emp_no'") or die(mysqli_error($con));
    if(@mysqli_num_rows($master))
        while($master_data=  mysqli_fetch_array($master)){
            set_time_limit(30);
            $shift=  explode(",",$master_data["work_schedule"]);
            $shift_schedule=$shift[$day];
            mysqli_query($con, "INSERT INTO $db_hris.`employee_work_schedule` (`employee_no`, `trans_date`, `shift_code`) VALUES ('$master_data[employee_no]', '$current_date', '$shift_schedule')") or die(mysqli_error($con));
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

function delete_holiday($hol_id) {

    global $db, $db_hris;

    $del_hol = $db->prepare("DELETE FROM $db_hris.`holiday` WHERE `holiday_id`=:hol_id");
    $del_hol->execute(array(":hol_id" => $hol_id));

    echo json_encode(array("status" => "success"));       
}


function new_holiday($hol_date,$hol_name,$special){
    global $db, $db_hris;

    $check_holiday = $db->prepare("SELECT * FROM $db_hris.`holiday` WHERE `holiday_date`=:hol_date");
    $check_holiday->execute(array(":hol_date" => $hol_date));
    if ($check_holiday->rowCount()){
        echo json_encode(array("status" => "Exist"));
    }else{
        $new_holiday = $db->prepare("INSERT INTO $db_hris.`holiday`(`holiday_date`,`is_special`,`description`) VALUES (:date, :special, :desc)");
        $new_holiday->execute(array(":date" => $hol_date, ":special" => $special, ":desc" => $hol_name));

        echo json_encode(array("status" => "success"));
    }
}

function new_group($group_name,$payroll_date,$cuttoff_date){
    global $db, $db_hris;

    $new_pay_group = $db->prepare("INSERT INTO $db_hris.`employment_status`(`description`) VALUES (:desc)");
    $new_pay_group->execute(array(":desc" => strtoupper($group_name)));

    $check_pay_group = $db->prepare("SELECT * FROM $db_hris.`employment_status` WHERE `description`=:desc");
    $check_pay_group->execute(array(":desc" => strtoupper($group_name)));
    if ($check_pay_group->rowCount()){
        while($check_pay_group_data = $check_pay_group->fetch(PDO::FETCH_ASSOC)){

            $new_pay_group1 = $db->prepare("INSERT INTO $db_hris.`payroll_group`(`group_name`,`cutoff_date`,`payroll_date`) VALUES (:grp_no, :cuttoff_date, :payroll_date)");
            $new_pay_group1->execute(array(":grp_no" => $check_pay_group_data['employment_status_code'], ":cuttoff_date" => $cuttoff_date, ":payroll_date" => $payroll_date));

            echo json_encode(array("status" => "success"));
        }
    }
}

function del_group($group_no){
    global $db, $db_hris;

    $del_group = $db->prepare("DELETE FROM $db_hris.`employment_status` WHERE `employment_status_code`=:no");
    $del_group->execute(array(":no" => $group_no));
    if ($del_group->rowCount()){
        echo json_encode(array("status" => "success"));
        $pay_group = $db->prepare("DELETE FROM $db_hris.`payroll_group` WHERE `group_name`=:no");
        $pay_group->execute(array(":no" => $group_no));
    }
}

function update_group($pay_group_code,$payroll_date,$cuttoff_date){
    global $db, $db_hris;

    $check_pay_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:pay_group_code");
    $check_pay_group->execute(array(":pay_group_code" => $pay_group_code));
    if ($check_pay_group->rowCount()){

        $update_pay_group = $db->prepare("UPDATE $db_hris.`payroll_group` SET `cutoff_date`=:cutoff, `payroll_date`=:payroll_date WHERE `group_name`=:pay_group");
        $update_pay_group->execute(array(":pay_group" => $pay_group_code, ":cutoff" => $cuttoff_date, ":payroll_date" => $payroll_date));

        echo json_encode(array("status" => "success"));
    }else{

        $new_pay_group = $db->prepare("INSERT INTO $db_hris.`payroll_group`(`group_name`,`cutoff_date`,`payroll_date`) VALUES (:grp_no, :cuttoff_date, :payroll_date)");
        $new_pay_group->execute(array(":grp_no" => $pay_group_code, ":cuttoff_date" => $cuttoff_date, ":payroll_date" => $payroll_date));

        echo json_encode(array("status" => "success"));

    }
}

function save_pay_type($pay_desc,$isFactortoRate,$factorAmt,$tax,$sss,$cola,$month,$od,$adj,$neg){
    global $db, $db_hris;

    $pay_type = $db->prepare("SELECT * FROM $db_hris.`payroll_type` WHERE `pay_type` LIKE :desc");
    $pay_type->execute(array(":desc" => '%'.$pay_desc.'%'));
    if ($pay_type->rowCount()) {
        echo json_encode(array("status" => "error", "message" => "$pay_desc is already define!"));
    }else{
        $pay_ins = $db->prepare("INSERT INTO `payroll_type`(`pay_type`, `is_factor_to_payrate`, `factor_amount`, `is_subject_to_tax`, `is_subject_to_sss`, `is_subject_to_cola`, `is_subject_to_13th`, `is_subject_to_other_ded`, `is_adjustment`, `is_negative`, `user_id`, `station_id`) VALUES (:pay_desc, :ftp, :fa, :st, :ss, :cola, :13th, :sod, :adj, :neg, :userid, :station)");
        $pay_ins->execute(array(":pay_desc" => $pay_desc, ":ftp" => $isFactortoRate, ":fa" => $factorAmt, ":st" => $tax, ":ss" => $sss, ":cola" => $cola, ":13th" => $month, ":sod" => $od, ":adj" => $adj, ":neg" => $neg, ":userid" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR']));
        echo json_encode(array("status" => "success"));
    }
}

function default_pay_type(){ ?>
    <div class="w3-small">
        <table class="w3-table-all w3-border">
            <thead>
                <tr>
                    <th></th>
                    <th>Payroll Type No</th>
                    <th>Payroll Type</th>
                    <th>Factor to Payrate</th>
                    <th>Factor Amount</th>
                    <th>Subject to TAX</th>
                    <th>Subject to SSS</th>
                    <th>Subject to COLA</th>
                    <th>Subject to 13th Month</th>
                    <th>Subject to Other Deductions</th>
                    <th>Adjustment</th>
                    <th>Negative</th>
                    <th>USER ID</th>
                    <th>STATION</th>
                    <th>TIMESTAMP</th>
                </tr>
            </thead>
            <tbody>
                <tr id="new_set">
                    <td></td>
                    <td colspan="2"><input type="text" id="pay_desc" name="pay_desc" style="padding: 2px 4px; width: 100%;" class="w3-border w3-border-grey w3-round-medium w3-hover-border-orange" placeholder="Payroll Type Description.."></td>
                    <td class="w3-center"><input type="checkbox" id="fr" name="fr" style="margin-top: 4px;"></td>
                    <td class="w3-center"><input type="text" id="fa" name="fa" style="padding: 2px 4px; width: 100%;" class="w3-border w3-border-grey w3-round-medium w3-hover-border-orange" placeholder="Factor Amount.."></td>
                    <td class="w3-center"><input type="checkbox" id="st" name="st" style="margin-top: 4px;"></td>
                    <td class="w3-center"><input type="checkbox" id="ss" name="ss" style="margin-top: 4px;"></td>
                    <td class="w3-center"><input type="checkbox" id="sc" name="sc" style="margin-top: 4px;"></td>
                    <td class="w3-center"><input type="checkbox" id="s1" name="s1" style="margin-top: 4px;"></td>
                    <td class="w3-center"><input type="checkbox" id="sod" name="sod" style="margin-top: 4px;"></td>
                    <td class="w3-center"><input type="checkbox" id="adj" name="adj" style="margin-top: 4px;"></td>
                    <td class="w3-center"><input type="checkbox" id="neg" name="neg" style="margin-top: 4px;"></td>
                    <td colspan="3" class="w3-center">
                        <input type="button" onclick="save_pay_type()" id="save" style="padding: 2px 6px; cursor: pointer;" class="w3-hover-orange w3-round-medium w3-hover-text-white" value="SAVE"/>&nbsp;&nbsp;&nbsp;
                    </td>
                </tr>
            <?php
            global $db_hris, $db;

            $pay_type = $db->query("SELECT * FROM $db_hris.`payroll_type` ORDER BY `pay_type` ASC");
            $pay_type->execute();
            if ($pay_type->rowCount()) {
            $count = 0;
                while ($pay_type_data = $pay_type->fetch(PDO::FETCH_ASSOC)) {
                $count++; ?>
                <tr class="w3-hover-orange w3-hover-text-white remove">
                    <td><?php echo $count; ?>.</td>
                    <td><?php echo $pay_type_data['payroll_type_no']; ?></td>
                    <td><?php echo $pay_type_data['pay_type']; ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_factor_to_payrate'] ? 'Y' : 'N' ; ?></td>
                    <td class="w3-center"><?php echo number_format($pay_type_data['factor_amount'],2); ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_subject_to_tax'] ? 'Y' : 'N' ; ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_subject_to_sss'] ? 'Y' : 'N' ; ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_subject_to_cola'] ? 'Y' : 'N' ; ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_subject_to_13th'] ? 'Y' : 'N' ; ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_subject_to_other_ded'] ? 'Y' : 'N' ; ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_adjustment'] ? 'Y' : 'N' ; ?></td>
                    <td class="w3-center"><?php echo $pay_type_data['is_negative'] ? 'Y' : 'N' ; ?></td>
                    <td><?php echo $pay_type_data['user_id']; ?></td>
                    <td><?php echo $pay_type_data['station_id']; ?></td>
                    <td><?php echo $pay_type_data['time_stamp']; ?></td>
                </tr>
                <?php
                }
            } ?>
            </tbody>
        </table>
    </div>
    <script>
        function save_pay_type(){
            $.ajax({
                url: "page/sys_config",
                type: "post",
                data: {
                    cmd: "save-pay-type",
                    pay_desc : $('#pay_desc').val(),
                    isFactortoRate : $("#fr").is(":checked"),
                    factorAmt : $('#fa').val(),
                    tax : $("#st").is(":checked"),
                    sss : $("#ss").is(":checked"),
                    cola : $("#sc").is(":checked"),
                    month : $("#s1").is(":checked"),
                    od : $("#sod").is(":checked"),
                    adj : $("#adj").is(":checked"),
                    neg : $("#neg").is(":checked")
                },
                success: function(data) {
                    if (data !== "") {
                        var _return = jQuery.parseJSON(data);
                        if(_return.status == "success"){
                            payroll_type();
                        }else{
                            w2alert(_return.message);
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