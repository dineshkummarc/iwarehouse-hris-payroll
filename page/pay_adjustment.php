<?php

$program_code = 3;
require_once('../common/functions.php');

switch ($_REQUEST["cmd"]) {
    case "get-adjust-default":
        get_adjust_default();
    break;
    case "get-emp-no":
        $emp_no = $_POST["emp_no"];
        get_emp_no($emp_no);
    break;
    case "save-emp-adjustment":
        $credit = number_format($_POST["credit"], 2, '.', '');
        $emp_no = $_POST["emp_no"];
        $pay_type = $_POST["pay_type"];
        $user_id = $_SESSION['name'];
        $station_id = $_SERVER['REMOTE_ADDR'];

        $pay_amount = 0;
        $total = "0.00";
        $master = mysqli_query($con,"SELECT * FROM `master_data` WHERE `employee_no`='$emp_no'");
        $payroll_type = mysqli_query($con,"SELECT * FROM `payroll_type` WHERE `payroll_type_no`='$pay_type'");
        if (@mysqli_num_rows($master) AND @mysqli_num_rows($payroll_type)) {
            $master_data = mysqli_fetch_array($master);
            $payroll_type_data = mysqli_fetch_array($payroll_type);
            $master_id_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `master_id` WHERE `employee_no`='$master_data[employee_no]'"));
            $payroll_group_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name`='$master_id_data[pay_group]'"));
            $payroll_date = $payroll_group_data["payroll_date"];
            if ($payroll_type_data["is_factor_to_payrate"]) {
                $master_rate_data = mysqli_fetch_array(mysqli_query($con,"SELECT * FROM `employee_rate` WHERE `employee_no`='$emp_no'"));
                $pay_amount = number_format($master_rate_data["daily_rate"] / 8 * $credit * $payroll_type_data["factor_amount"], 2, '.', '');
            } else {
                $pay_amount = $credit;
                if (number_format($pay_type, 0, '.', '') === number_format(get_paytype13th(), 0, '.', '')) {
                    $pay_amount = number_format($credit / 12, 2, '.', '');
                }
            }
            $payroll_adjustment = mysqli_query($con,"SELECT * FROM `payroll_adjustment` WHERE `employee_no`='$emp_no' AND `payroll_date`='$payroll_date' AND `payroll_type_no`='$pay_type'");
            if (@mysqli_num_rows($payroll_adjustment)) {
                mysqli_query($con,"UPDATE `payroll_adjustment` SET `credit`='$credit', `pay_amount`='$pay_amount', `user_id`='$user_id', `station_id`='$station_id' WHERE `employee_no`='$emp_no' AND `payroll_date`='$payroll_date' AND `payroll_type_no`='$pay_type'");
            } else {
                mysqli_query($con,"INSERT INTO `payroll_adjustment` (`employee_no`, `payroll_date`, `payroll_type_no`, `credit`, `pay_amount`, `user_id`, `station_id`) VALUES ('$emp_no', '$payroll_date', '$pay_type', '$credit', '$pay_amount', '$user_id', '$station_id')");
            }
            $payroll_adjustment_data = mysqli_fetch_array(mysqli_query($con,"SELECT SUM(`pay_amount`) AS `pay_amount` FROM `payroll_adjustment` WHERE `employee_no`='$emp_no' AND `payroll_date`='$payroll_date'"));
            $total = number_format($payroll_adjustment_data["pay_amount"], 2);
            ?>
            <input type="hidden" id="credit_adj" value="<?php echo number_format($pay_amount, 2); ?>" />
            <input type="hidden" id="total_adj" value="<?php echo $total; ?>" />
            <?php
        } else {
            echo "1";
        }
    break;
    case "del-emp-adjustment":
        $emp_no = $_POST["emp_no"];
        $pay_type = $_POST["pay_type"];
        delete_adjustment($emp_no,$pay_type);
    break;
    
}

function delete_adjustment($emp_no,$pay_type){
    global $db, $db_hris;

    $del_adj = $db->prepare("DELETE FROM $db_hris.`payroll_adjustment` WHERE `payroll_type_no`=:pay_type AND `employee_no`=:emp_no");
    $del_adj->execute(array(":pay_type" => $pay_type, ":emp_no" => $emp_no));

    echo "success";
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
                    <td style="text-align: right"><b><span data-rate="<?php if(@mysqli_num_rows($employee_rate))echo number_format($employee_rate_data["daily_rate"]/8.00,2); else echo "0"; ?>" id="total_amount"><?php echo number_format($total,2); ?></span></b></td>
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
        //console.table(_emp_no,_credit,_type);
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
                if(data=="1"){
                    w2alert("Invalid Transaction Entered!");
                }else{
                    w2alert("Adjustment Saved!");
                    $("#get_emp").click();
                }
            },
            error: function() {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
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
                        if(data=="success"){
                            $("#get_emp").click();
                        }else{
                            w2alert("Error Deleting Adjustment!");
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