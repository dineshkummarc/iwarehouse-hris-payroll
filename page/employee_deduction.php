<?php

$program_code = 1;
require_once('../common/functions.php');
include '../common/master_journal.php';

switch ($_POST["cmd"]) {
    case "get-employee":
        get_employee();
    break;
    case "get-emp-ded":
        $emp_no = $_POST["emp_no"];

        global $db_hris, $db;

        $deductions ='';
        $deductions .= '<table class="w3-table-all w3-small">
                            <thead>
                                <tr>
                                    <th colspan="3" class="w3-center" id="ded_list">Deduction List</th>
                                </tr>
                                <tr>
                                    <th>Deduction</th>
                                    <th>Deduction Balance</th>
                                    <th>Deduction Amount</th>
                                </tr>
                            </thead>
                        <tbody>';


        $ded = $db->query("SELECT * FROM $db_hris.`deduction` WHERE !`is_computed` AND !`is_inactive` AND !`deduction_type` ORDER BY `deduction_description`");
        if ($ded->rowCount()) {
            $emp_ded = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `employee_no`=:no AND `deduction_no`=:dno");
            while ($ded_data = $ded->fetch(PDO::FETCH_ASSOC)) {
                $emp_ded->execute(array(":no" => $emp_no, ":dno" => $ded_data["deduction_no"]));
                if ($emp_ded->rowCount()) {
                    $emp_ded_data = $emp_ded->fetch(PDO::FETCH_ASSOC);
                    $deduction_amount = number_format($emp_ded_data["deduction_amount"],2);
                    $deduction_balance = number_format($emp_ded_data["deduction_balance"],2);

                    $deductions .= '<tr id='.$ded_data["deduction_no"].' style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white" onclick="new_deduction('.$ded_data["deduction_no"].','.$emp_no.')">
                                        <td>'.$ded_data["deduction_description"].'</td>
                                        <td>'.$deduction_balance.'</td>
                                        <td>'.$deduction_amount.'</td>
                                    </tr>';
                }else{
                    $deduction_amount1 = '';
                    $deduction_balance1 = '';

                    $deductions .= '<tr id='.$ded_data["deduction_no"].' style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white" onclick="new_deduction('.$ded_data["deduction_no"].','.$emp_no.')">
                                        <td>'.$ded_data["deduction_description"].'</td>
                                        <td>'.$deduction_balance1.'</td>
                                        <td>'.$deduction_amount1.'</td>
                                    </tr>';
                }

            }
            $deductions .= '</tbody></table>';
            
        }
        echo $deductions;
    break;
    case "get-emp-ledger":
        $emp_no = $_POST["emp_no"];
        $ded_no = $_POST["ded_no"];
        get_emp_ledger($emp_no,$ded_no);
    break;
    case "new-emp-ded":
        $ded_no = $_POST['ded_no'];
        $emp_no = $_POST['emp_no'];
        new_emp_ded($ded_no,$emp_no);
    break;
    case "new-deductions":
        $ded_no = $_POST['ded_no'];
        $emp_no = $_POST['emp_no'];
        $ded_bal = $_POST['ded_bal'];
        $ded_amt = $_POST['ded_amt'];
        new_deductions($ded_no,$emp_no,$ded_bal,$ded_amt);
    break;
    case "get-default-ded":
        get_default_emp_ded();
    break;
}

function get_default_emp_ded(){ ?>
    <div class="w3-row">
        <div class="w3-col s3" id="deduction_list"></div>
        <div class="w3-col s2 w3-hide w3-margin-left" id="emp_deduction">
            <form method="post" id="add_update_ded" class="w3-container w3-panel w3-border w3-round-medium">
                <div>
                    <label class="w3-label w3-small" for="emp_ded_name">Deduction Name:</label>
                    <input class="w3-input w3-border w3-border-color-orange w3-small w3-round-medium w3-input-focus" type="text" name="emp_ded_name" id="emp_ded_name" autocomplete="off" readonly />
                    <input type="hidden" name="emp_ded_no" id="emp_ded_no" autocomplete="off" />
                    <input type="hidden" name="emp_no" id="emp_no" autocomplete="off" /></br>
                </div>
                <div>
                    <label class="w3-label w3-small" for="emp_ded_bal">ADD Balance:</label>
                    <input type="hidden" name="emp_ded_total" id="emp_ded_total" autocomplete="off"/>
                    <input class="w3-input w3-border w3-border-color-orange w3-small w3-round-medium w3-input-focus" type="text" name="emp_ded_bal" id="emp_ded_bal" autocomplete="off"/>
                </div>
                <div>
                    <label class="w3-label w3-small" for="emp_ded_amt">Deduction Amount:</label>
                    <input class="w3-input w3-border w3-border-color-orange w3-small w3-round-medium w3-input-focus" type="text" name="emp_ded_amt" id="emp_ded_amt" autocomplete="off"/>
                </div>
                <div class="w3-container w3-padding-8">
                    <button type="button" class="w3-button w3-blue w3-round-medium w3-border w3-small w3-hover-teal" id="add_data" name="save" onclick="save_form()" style="float: right;">✔ Save</button>
                </div>
            </form>
        </div>
        <div class="w3-col s6 w3-margin-left" id="deduction_ledger"></div>
    </div>

    <script type="text/javascript">

	function getBack(){
        get_default();
	}

    const src = "page/employee_deduction";

	var employee_list;

	$(document).ready(function(){
		get_emp_list();  
    });

    function get_emp_list(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait.-.', true);
        $.ajax({
            url: src,
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
    }

    function get_emp(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        var employee_no = $("#emp_list").w2field().get().id;
        if(employee_no !== ""){
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-emp-ded",
                    emp_no : employee_no
                },
                success: function (data){
                    $('#deduction_list').html(data);
                    $('#clear_emp').removeClass("w3-hide");
                    $('#deduction_list').removeClass("w3-hide");
                    $('#emp_deduction').addClass("w3-hide");
                    $('#deduction_ledger').addClass("w3-hide");
                    w2utils.unlock(div);
                },
                error: function () {
                    w2alert("Sorry, there was a problem in server connection or Session Expired!");
                    w2utils.unlock(div);
                }
            });
        }else{
            w2alert("Invalid Employee!");
        }
    }

    function clear_emp(){
        get_emp_list();
        $('#deduction_list').addClass("w3-hide");
        $('#emp_deduction').addClass("w3-hide");
        $('#deduction_ledger').addClass("w3-hide");
    }

    function new_deduction($ded_no,$emp_no){
        $('div#emp_deduction').removeClass("w3-hide");
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "new-emp-ded",
                ded_no : $ded_no,
                emp_no : $emp_no
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        $('#emp_ded_name').val(_return.ded_name);
                        $('input#emp_ded_no').val(_return.ded_no);
                        $('input#emp_no').val(_return.emp_no);
                        $('input#emp_ded_bal').val(_return.ded_bal);
                        $('input#emp_ded_total').val(_return.ded_total);
                        $('input#emp_ded_amt').val(_return.ded_amt);
                        get_ledger($ded_no,$emp_no);
                    }else{
                        w2alert("Sorry, No DATA found!");
                    }
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection or Session Expired!");
            }
        });
    }

    function get_ledger($ded_no,$emp_no){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-emp-ledger",
                ded_no : $ded_no,
                emp_no : $emp_no
            },
            success: function (data){
                $('#deduction_ledger').html(data);
                $('#clear_emp').removeClass("w3-hide");
                $('#deduction_ledger').removeClass("w3-hide");
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection or Session Expired!");
            }
        });
    }

    function save_form(){
        var ded_no = $('#emp_ded_no').val();
        var emp_no = $('#emp_no').val();
        var ded_bal = $('#emp_ded_bal').val();
        var ded_amt = $('#emp_ded_amt').val();
        var ded_total = $('#emp_ded_total').val();
        var ded_bal1 = parseInt(ded_bal);
        var ded_bal2 = parseInt(ded_total);
        var total = ded_bal1+ded_bal2;
        if(ded_amt >= total){
            w2alert('Invalid Deduction Amount!');
        }else{
            $('div#emp_deduction').addClass("w3-hide");
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "new-deductions",
                    ded_no : ded_no,
                    emp_no : emp_no,
                    ded_bal : ded_bal,
                    ded_amt : ded_amt
                },
                success: function (data){
                    get_emp();
                },
                error: function () {
                    w2alert("Sorry, there was a problem in server connection or Session Expired!");
                }
            });
        }
    }
    </script>

    <?php
}

function new_deductions($ded_no,$emp_no,$ded_bal,$ded_amt){
    global $db, $db_hris;

    $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
    $emp_deductions->execute(array(":ded_no" => $ded_no, ":emp_no" => $emp_no));
    if ($emp_deductions->rowCount()){
        $update_ded = $db->prepare("UPDATE $db_hris.`employee_deduction` SET `deduction_amount`=:ded_amt, `deduction_balance`=`deduction_balance`+:ded_bal, `user_id`=:uid, `station_id`=:ip WHERE `employee_no`=:emp_no AND `deduction_no`=:ded_no");
        $update_ded->execute(array(":emp_no" => $emp_no, ":ded_amt" => $ded_amt, ":ded_bal" => $ded_bal, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $ded_no));
    }else{
        $new_ded = $db->prepare("INSERT INTO $db_hris.`employee_deduction`(`employee_no`, `deduction_no`, `deduction_amount`, `deduction_balance`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :ded_amt, :ded_bal, :uid, :ip)");
        $new_ded->execute(array(":emp_no" => $emp_no, ":ded_amt" => $ded_amt, ":ded_bal" => $ded_bal, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR'], ":ded_no" => $ded_no));
    }
    echo json_encode(array("status" => "success"));    
    insert_ledger($ded_no,$emp_no,$ded_bal,$ded_amt);
}

function insert_ledger($ded_no,$emp_no,$ded_bal,$ded_amt){
    global $db, $db_hris;

    $ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:dno");
    $ded->execute(array(":dno" => $ded_no));
    if($ded->rowCount()){
        $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
        
        $remark = "Changes Deduction of ".$ded_data["deduction_description"];
        $ref = $ded_data["deduction_description"];

        $emp_ledger = $db->prepare("INSERT INTO $db_hris.`employee_deduction_ledger`(`employee_no`, `deduction_no`, `trans_date`, `amount`, `balance`, `remark`, `reference`, `user_id`, `station_id`) VALUES (:emp_no, :ded_no, :date, :ded_amt, :ded_bal, :remark, :ref, :uid, :ip)");
        $emp_ledger->execute(array(":emp_no" => $emp_no, ":ded_no" => $ded_no, ":date" => date('Y-m-d'), ":ded_amt" => $ded_amt, ":ded_bal" => $ded_bal, ":remark" => $remark, ":ref" => $ref, ":uid" => $_SESSION['name'], ":ip" => $_SERVER['REMOTE_ADDR']));
    }
    echo json_encode(array("status" => "success")); 
}

function get_emp_ledger($emp_no,$ded_no){
    global $db_hris, $db;
    
    $ded = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:dno");
    $ded->execute(array(":dno" => $ded_no));
    $ded_data = $ded->fetch(PDO::FETCH_ASSOC);
    ?>
    <table class="w3-table-all w3-small">
        <thead>
            <tr>
                <th colspan="8" class="w3-center" id="ded_list">
                    Deduction Ledger of <?php echo $ded_data['deduction_description']; ?>
                </th>
            </tr>
            <tr>
                <th>Ledger No</th>
                <th>Transaction Date</th>
                <th>Deduction Amount</th>
                <th>Deduction Balance</th>
                <th>Remarks</th>
                <th>Reference</th>
                <th>User ID</th>
                <th>Station ID</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $emp_ledger = $db->prepare("SELECT * FROM $db_hris.`employee_deduction_ledger` WHERE `employee_no`=:no AND `deduction_no`=:dno");
        $emp_ledger->execute(array(":no" => $emp_no, ":dno" => $ded_no));
        if ($emp_ledger->rowCount()) {
            while ($ledger_data = $emp_ledger->fetch(PDO::FETCH_ASSOC)) {
            $deduction_amount = number_format($ledger_data["amount"],2);
            $deduction_balance = number_format($ledger_data["balance"],2);
        ?>
            <tr id="<?php echo $ledger_data["ledger_no"]; ?>" style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white">
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">'.$ledger_data["ledger_no"].'</span>'; else echo $ledger_data["ledger_no"]; ?></td>
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">'.$ledger_data["trans_date"].'</span>'; else echo $ledger_data["trans_date"]; ?></td>
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">-'.$deduction_amount.'</span>'; else echo $deduction_amount; ?></td>
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">-'.$deduction_balance.'</span>'; else echo $deduction_balance; ?></td>
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">'.$ledger_data["remark"].'</span>'; else echo $ledger_data["remark"]; ?></td>
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">'.$ledger_data["reference"].'</span>'; else echo $ledger_data["reference"]; ?></td>
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">'.$ledger_data["user_id"].'</span>'; else echo $ledger_data["user_id"]; ?></td>
                <td><?php if(strpos($ledger_data["remark"], "Cancel") !== false ) echo '<span class="w3-text-red">'.$ledger_data["station_id"].'</span>'; else echo $ledger_data["station_id"]; ?></td>
            </tr>
            <?php
            }
        }else{ ?>
            <tr style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white">
                <td colspan="8" class="w3-center">No Deductions Ledger</td>
            </tr>
            <?php
        } ?>
        </tbody>
    </table>
    <?php
}


function new_emp_ded($ded_no,$emp_no) {
    global $db, $db_hris;

    $deductions = $db->prepare("SELECT * FROM $db_hris.`deduction` WHERE `deduction_no`=:ded_no");
    $deductions->execute(array(":ded_no" => $ded_no));
    if ($deductions->rowCount()){
        while ($deductions_data = $deductions->fetch(PDO::FETCH_ASSOC)) {
            $emp_deductions = $db->prepare("SELECT * FROM $db_hris.`employee_deduction` WHERE `deduction_no`=:ded_no AND `employee_no`=:emp_no");
            $emp_deductions->execute(array(":ded_no" => $ded_no, ":emp_no" => $emp_no));
            if ($emp_deductions->rowCount()){
                while ($emp_deductions_data = $emp_deductions->fetch(PDO::FETCH_ASSOC)) {
                    $emp_no = $emp_no;
                    $ded_name = $deductions_data["deduction_description"];
                    $ded_no = $deductions_data["deduction_no"];
                    $ded_amt = $emp_deductions_data["deduction_amount"];
                    $ded_total = $emp_deductions_data["deduction_balance"];
                    $ded_bal = '';
                }
            }else{
                $emp_no = $emp_no;
                $ded_name = $deductions_data["deduction_description"];
                $ded_no = $deductions_data["deduction_no"];
                $ded_total = '';
                $ded_amt = '';
                $ded_bal = '';
            }
        }
    }
    echo json_encode(array("status" => "success", "ded_name" => $ded_name, "ded_no" => $ded_no, "emp_no" => $emp_no, "ded_amt" => $ded_amt, "ded_bal" => $ded_bal, "ded_total" => $ded_total));       
}


function get_employee() {
    global $db, $db_hris;

    $emp_list = $db->prepare("SELECT `master_data`.`employee_no`,`master_data`.`pin`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`family_name` FROM $db_hris.`master_data` ORDER BY `master_data`.`family_name`");
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