<?php

$program_code = 30;
require_once('../common/functions.php');
include("../common_function.class.php");
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
switch ($_REQUEST["cmd"]) {
    case "get-adjust-default":
        get_adjust_default();
    break;
    case "get-emp-no":
        $emp_no = $_POST["emp_no"];
        get_emp_no($emp_no);
    break;
    case "save-emp-adjustment":
        if (substr($access_rights, 0, 4) === "A+E+") {
            $record = array("credit" => number_format($_POST["credit"], 2, '.', ''), "emp_no" => $_POST["emp_no"], "pay_type" => $_POST["pay_type"],  );
            save_emp_adjustment($record);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    case "del-emp-adjustment":
        if (substr($access_rights, 4, 2) === "D+") {
            $emp_no = $_POST["emp_no"];
            $pay_type = $_POST["pay_type"];
            delete_adjustment($emp_no,$pay_type);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
    
}

function save_emp_adjustment($record){
    global $db, $db_hris;

    $pay_amount = 0;
    //$total = "0.00";
    $master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:emp_no AND !`is_inactive`");
    $master->execute(array(":emp_no" => $record["emp_no"]));
    $payroll_type = $db->prepare("SELECT * FROM `payroll_type` WHERE `payroll_type_no`=:pay_type");
    $payroll_type->execute(array(":pay_type" => $record["pay_type"]));
    if ($master->rowCount() AND $payroll_type->rowCount()) {
        $master_data = $master->fetch(PDO::FETCH_ASSOC);
        $payroll_type_data = $payroll_type->fetch(PDO::FETCH_ASSOC);
        $master_id = $db->prepare("SELECT * FROM $db_hris.`master_id` WHERE `employee_no`=:emp_no");
        $master_id->execute(array(":emp_no" => $master_data['employee_no']));
        if($master_id->rowCount()){
            $master_id_data = $master_id->fetch(PDO::FETCH_ASSOC);
            $payroll_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:pay_group");
            $payroll_group->execute(array(":pay_group" => $master_id_data['pay_group']));
            if($payroll_group->rowCount()){
                $payroll_group_data = $payroll_group->fetch(PDO::FETCH_ASSOC);
                $payroll_date = $payroll_group_data["payroll_date"];
                if ($payroll_type_data["is_factor_to_payrate"]) {
                    $master_rate = $db->prepare("SELECT * FROM $db_hris.`employee_rate` WHERE `employee_no`=:emp_no");
                    $master_rate->execute(array(":emp_no" => $record["emp_no"]));
                    if($master_rate->rowCount()){
                        $master_rate_data = $master_rate->fetch(PDO::FETCH_ASSOC);
                        $pay_amount = number_format($master_rate_data["daily_rate"] / 8 * $record["credit"] * $payroll_type_data["factor_amount"], 2, '.', '');
                    }
                } else {
                    $pay_amount = $record["credit"];
                    if (number_format($record["pay_type"], 0, '.', '') === number_format(get_paytype13th(), 0, '.', '')) {
                        $pay_amount = number_format($record["credit"] / 12, 2, '.', '');
                    }
                }
                $payroll_adjustment = $db->prepare("SELECT * FROM $db_hris.`payroll_adjustment` WHERE `employee_no`=:emp_no AND `payroll_date`=:pay_date AND `payroll_type_no`=:ptype");
                $payroll_adjustment->execute(array(":emp_no" => $record["emp_no"], ":pay_date" => $payroll_date, ":ptype" => $record["pay_type"]));
                if ($payroll_adjustment->rowCount()) {
                    $adjustment = $db->prepare("UPDATE $db_hris.`payroll_adjustment` SET `credit`=:credit, `pay_amount`=:pay_amount , `user_id`=:uid, `station_id`=:station WHERE `employee_no`=:emp_no AND `payroll_date`=:pdate AND `payroll_type_no`=:ptype");
                    $adjustment->execute(array(":credit" => $record["credit"], ":pay_amount" => $pay_amount, ":uid" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR'], ":emp_no" => $record["emp_no"], ":pdate" => $payroll_date, ":ptype" => $record['pay_type']));
                } else {
                    $adjustment = $db->prepare("INSERT INTO $db_hris.`payroll_adjustment` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`, `user_id`, `station_id`) VALUES (:emp_no, :pdate, :ptype, :credit, :pay_amount, :uid, :station)");
                    $adjustment->execute(array(":emp_no" => $record["emp_no"], ":pdate" => $payroll_date, ":ptype" => $record['pay_type'], ":credit" => $record["credit"], ":pay_amount" => $pay_amount, ":uid" => $_SESSION['name'], ":station" => $_SERVER['REMOTE_ADDR']));
                }
                $payroll_adjustment_data = $db->prepare("SELECT SUM(`pay_amount`) AS `pay_amount` FROM $db_hris.`payroll_adjustment` WHERE `employee_no`=:emp_no AND `payroll_date`=:pdate");
                $payroll_adjustment_data->execute(array(":emp_no" => $record["emp_no"], ":pdate" => $payroll_date));
                if($payroll_adjustment_data->rowCount()){
                    $_adjustment_data = $payroll_adjustment_data->fetch(PDO::FETCH_ASSOC);
                    /*$total = number_format($_adjustment_data["pay_amount"], 2); ?>
                    <input type="hidden" id="credit_adj" value="<?php echo number_format($pay_amount, 2); ?>" />
                    <input type="hidden" id="total_adj" value="<?php echo $total; ?>" />
                    <?php */
                    echo json_encode(array("status" => "success"));
                }
            }else{
                echo json_encode(array("status" => "error", "message" => "Invalid Transaction Entered!"));
            }
        }
    }
}

function delete_adjustment($emp_no,$pay_type){
    global $db, $db_hris;

    $del_adj = $db->prepare("DELETE FROM $db_hris.`payroll_adjustment` WHERE `payroll_type_no`=:pay_type AND `employee_no`=:emp_no");
    $del_adj->execute(array(":pay_type" => $pay_type, ":emp_no" => $emp_no));
    if($del_adj->rowCount()){
        echo json_encode(array("status" => "success"));
    }else{
        echo json_encode(array("status" => "error", "message" => $del_adj->errorInfo()));
    }
}

function get_paytype13th() {
    global $db, $db_hris;
    $sysconfig = $db->prepare("SELECT * FROM $db_hris.`_sysconfig` WHERE `config_name` LIKE :name");
    $sysconfig->execute(array(":name" => "13th code"));
    if ($sysconfig->rowCount()) {
        $sysconfig_data = $sysconfig->fetch(PDO::FETCH_ASSOC);
        $code = $sysconfig_data["config_value"];
    } else {
        $code = 0;
    }
    return $code;
}

function get_emp_no($emp_no){
    global $con;

    $master = mysqli_query($con,"SELECT * FROM `master_data` WHERE `employee_no`='$emp_no'") or die(mysqli_error($con));
    $master_data = mysqli_fetch_array($master);
    $employee_rate = mysqli_query($con,"SELECT * FROM `employee_rate` WHERE `employee_no`='$master_data[employee_no]'");
    if(@mysqli_num_rows($employee_rate)) $employee_rate_data = mysqli_fetch_array ($employee_rate);
        $master_id_data=  mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `master_id` WHERE `employee_no`='$master_data[employee_no]'"));
        $payroll_group_data=  mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name`='$master_id_data[pay_group]'"));
        $payroll_date= $payroll_group_data["payroll_date"];
        if(@mysqli_num_rows($master)){ ?>
        <table class="w3-table-all w3-small" style="width: 40%;">
            <thead>
                <tr>
                    <th></th>
                    <th class="w3-center">ADJUSTMENT</th>
                    <th class="w3-center">CREDIT</th>
                    <th class="w3-center">AMOUNT</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $cnt=$total=0;
            $payroll_type =  mysqli_query($con,"SELECT * FROM `payroll_type` WHERE `is_adjustment` ORDER BY `payroll_type_no`");
            if(@mysqli_num_rows($payroll_type))
            while($payroll_type_data=  mysqli_fetch_array($payroll_type)){ 
                $payroll_adjustment=  mysqli_query($con,"SELECT * FROM `payroll_adjustment` WHERE `employee_no`='$master_data[employee_no]' AND `payroll_type_no`='$payroll_type_data[payroll_type_no]' AND `payroll_date`='$payroll_date'");
                if(@mysqli_num_rows($payroll_adjustment)){
                    $payroll_adjustment_data = mysqli_fetch_array($payroll_adjustment);
                    $total+=$payroll_adjustment_data["pay_amount"];
                } ?>
                <tr>
                    <td><?php echo number_format(++$cnt); ?>.</td>
                    <td><?php echo $payroll_type_data["pay_type"]; ?></td>
                    <td><input pattern="\d+" class="credit" data-factor="<?php echo $payroll_type_data["is_factor_to_payrate"];  ?>" data-factor_amount="<?php echo $payroll_type_data["factor_amount"];  ?>" id="<?php echo $payroll_type_data["payroll_type_no"]; ?>" type="text" size="10" value="<?php if(@mysqli_num_rows($payroll_adjustment))echo number_format($payroll_adjustment_data["credit"],2,'.',''); ?>" /></td>
                    <td style="text-align: right"><span class="computed_value" data-value="<?php if(@mysqli_num_rows($payroll_adjustment))echo $payroll_adjustment_data["pay_amount"]; else echo "0.00"; ?>" id="a<?php echo $payroll_type_data["payroll_type_no"]; ?>"><?php if(@mysqli_num_rows($payroll_adjustment))echo number_format($payroll_adjustment_data["pay_amount"],2); ?></span></td>
                    <td style="text-align: right"><button data-empno="<?php echo $master_data["employee_no"]; ?>" data-type="<?php echo $payroll_type_data["payroll_type_no"]; ?>" class="save_adjustment w3-margin-right"><ion-icon class="w3-medium" name="save-outline"></ion-icon></button>
                        <?php
                        if(@mysqli_num_rows($payroll_adjustment)){
                            echo '<button data-empno="'.$master_data["employee_no"].'" data-type="'.$payroll_type_data["payroll_type_no"].'" class="del_adjustment"><ion-icon class="w3-medium" name="trash-outline"></ion-icon></button>';
                        } ?>
                    </td>
                </tr>
                <?php
            } ?>
            </tbody>
            <tfoot>
                <tr class="total">
                    <td colspan="3" style="text-align: center"><b>TOTAL</b></td>
                    <td style="text-align: right"><b><span data-rate="<?php if(@mysqli_num_rows($employee_rate))echo number_format($employee_rate_data["daily_rate"]/8.00,2); else echo "0"; ?>" id="total_amount"><?php echo number_format($total, 2); ?></span></b></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    <?php
    }

}

function get_adjust_default(){ ?>
    <div class="w3-container w3-padding-small">
		<input name="emp_list" id="emp_list" class="w3-small" type="list" style="width: 20%;" />
		<button name="get_emp" id="get_emp" class="w2ui-btn w3-small" onclick="get_employee()">GET EMPLOYEE</button>
		<button name="getBack" id="getDate" class="w2ui-btn w3-small w3-right" onclick="getBack()"><i class="fa-solid fa-rotate-left"></i>&nbsp;Get Back</button>
    </div>
    <div id="adjustment_list"></div>

<script type="text/javascript">

	function getBack(){
        get_default();
	}

	var employee_list;
    const src = "page/pay_adjustment";

	$(document).ready(function(){
		var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/employee_deduction",
            type: "post",
            data: {
                cmd: "get-employee"
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        employee_list = _return.employee_list;
                        $('input#emp_list').w2field('list', { items: employee_list });
                        w2utils.unlock(div);
                    }else{
                        w2alert("Sorry, No DATA found!");
                        w2utils.unlock(div);
                    }
                }
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    });

    function get_employee(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
    	var emp_no = $("#emp_list").w2field().get().id;
    	if(emp_no !== ""){
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-emp-no",
                    emp_no : emp_no
                },
                success: function (data){
                	$('#adjustment_list').html(data);
                    $(".credit").keyup(compute_it);
                    $(".save_adjustment").click(save_adjustment);
                    $(".del_adjustment").click(del_adjustment);
                    w2utils.unlock(div);
                },
                error: function () {
                    w2alert("Sorry, there was a problem in server connection or Session Expired!");
                    w2utils.unlock(div);
                }
            })
        }else{
            w2alert("Invalid Employee!");
        }
    }

    function compute_it(){
        var _id = "#a"+this.id;
        var _value = 0;
        if($(this).data("factor")=="1"){
            _value = +$("#total_amount").data("rate") * +$(this).data("factor_amount") * $(this).val();
            _value = _value.toFixed(2);
        }else{
            _value = +$(this).val();
            _value = _value.toFixed(2);
        }
        $(_id).data("value", _value);
        $(_id).text(_value);
        var _total = 0;
        $(".computed_value").each(function(){
            _value =+$(this).data("value");
            _total += _value;
        });
        $("#total_amount").text(_total.toFixed(2));
    }

    function save_adjustment(){
        var _emp_no = $(this).data("empno");
        var _credit = $("#"+$(this).data("type")).val();
        var _type = $(this).data("type");
        $('.credit').removeClass('w3-border-red');
        //console.table(_emp_no,_credit,_type);
        if(_credit == ""){
            $("#"+$(this).data("type")).focus().addClass('w3-border-red');
        }else{
            $.ajax({
                url: src,
                type: "POST",
                data: {
                    cmd: "save-emp-adjustment",
                    emp_no: _emp_no,
                    pay_type: _type,
                    credit: _credit
                },
                success: function(data) {
                    if(data !==""){
                        var _response = jQuery.parseJSON(data);
                        if (_response.status === "success") {
                            w2alert("Adjustment Saved!");
                            $("#get_emp").click();
                        }else{
                            w2alert(_response.message);
                        }
                    }else{
                        w2alert("Sorry, There was a problem in server connection!");
                    }
                },
                error: function() {
                    w2alert("Sorry, there was a problem in server connection!");
                }
            });
        }
    }

    function del_adjustment(){
        var _emp_no = $(this).data("empno");
        var _type = $(this).data("type");
        w2confirm('Are you sure to delete this adjustment?', function (btn) {
            if (btn === "Yes") {
                //console.table(_emp_no,_credit,_type);
                $.ajax({
                    url: src,
                    type: "POST",
                    data: {
                        cmd: "del-emp-adjustment",
                        emp_no: _emp_no,
                        pay_type: _type
                    },
                    success: function(data) {
                        if(data !==""){
                            var _response = jQuery.parseJSON(data);
                            if (_response.status === "success") {
                                $("#get_emp").click();
                            }else{
                                w2alert(_response.message);
                            }
                        }else{
                            w2alert("Sorry, There was a problem in server connection!");
                        }
                    },
                    error: function() {
                        w2alert("Sorry, there was a problem in server connection!");
                    }
                });
            }
        })
    }
    </script>
    <?php
}