<?php

$program_code = 1;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+"){
    if($level <= $plevel ){
        echo json_encode(array("status" => "error", "message" => "Higher level required!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
$pin_no=substr($_GET["emp_no"], 3);
$cmd = $_GET["cmd"];

$check_emp_no=mysqli_query($con, "SELECT * FROM employee_rate,master_data WHERE master_data.employee_no=employee_rate.employee_no AND master_data.employee_no='$pin_no'") or die(mysqli_error($con));
$emp_no_row=mysqli_num_rows($check_emp_no);
$row=mysqli_fetch_array($check_emp_no);
if($emp_no_row > 0){
    $emp_no = $row['employee_no'];
    $pin = $row['pin'];
    $_SESSION["emp_no"] = '100'.$emp_no;
    $rate = number_format($row['daily_rate'], 2);
    $incentive = number_format($row['incentive_cash'], 2);
    $total = number_format($row['total_pay'], 2);
    $name = $row["given_name"].'&nbsp;'.$row["middle_name"].'&nbsp;'.$row["family_name"];
    $date_hired = $row['employment_date'];
    $group = $row['group_no'];

    //get Date diff as intervals 
    $d1 = date_create($date_hired);
    $d2 = date_create(date("Y-m-d"));
    $interval = date_diff($d1,$d2);

    $year = $interval->format('%y');
    $months = $interval->format('%m');
    $days = $interval->format('%d');
    if($year == 0){
        $yr = '';
    }else{
        $yr = $interval->format('%y years & ');
    }
    if($months == 0){
        $mo = '';
    }else{
        $mo = $interval->format('%m months & ');
    }
    if($days == 1){
        $day = $interval->format('%d day');
    }else{
        $day = $interval->format('%d days');
    }

    $lengthOfStay = $yr.$mo.$day;

}else{
    $get_data1 = mysqli_query($con, "SELECT * FROM master_data WHERE employee_no='$pin_no'");
        while ($row=mysqli_fetch_array($get_data1)){
            $emp_no = $row['employee_no'];
            $rate = '0.00';
            $incentive = '0.00';
            $total = '0.00';
            $name = $row["given_name"].'&nbsp;'.$row["middle_name"].'&nbsp;'.$row["family_name"];
            $date_hired = $row['date_hired'];
            $group = $row['group_no'];

             //get Date diff as intervals 
            $d1 = date_create($date_hired);
            $d2 = date_create(date("Y-m-d"));
            $interval = date_diff($d1,$d2);

            $year = $interval->format('%y');
            $months = $interval->format('%m');
            $days = $interval->format('%d');
            if($year == 0){
                $yr = '';
            }else{
                $yr = $interval->format('%y years & ');
            }
            if($months == 0){
                $mo = '';
            }else{
                $mo = $interval->format('%m months & ');
            }
            if($days == 1){
                $day = $interval->format('%d day');
            }else{
                $day = $interval->format('%d days');
            }

            $lengthOfStay = $yr.$mo.$day;

        }

}


?>
<style type="text/css">
.tableFixHead          { overflow: auto; height: 100px; }
.tableFixHead thead th { position: sticky; top: 0; z-index: 1; }

/* Just common table stuff. Really. */
table  { border-collapse: collapse; width: 100%; }
th, td { padding: 8px 16px; }
th     { background:#eee; }

/* Borders (if you need them) */
.tableFixHead,
.tableFixHead td {
    box-shadow: inset 1px -1px #000;
}
.tableFixHead th {
    box-shadow: inset 1px 1px #000, 0 1px #000;
}
</style>
<div class="w3-col s12 w3-panel w3-small">
    <div class="w3-col s12 w3-padding">
        <button class="w2ui-btn w3-right w3-padding-bottom" onclick="close_it()">Close</button>
    </div>
    <div class="w3-col s12 w3-padding">
        <div class="w3-bottombar w3-padding">
            <span class="w3-medium">Rate Maintenance of <span class="w3-text-orange"><b><?php echo $name; ?></b></span></span>
        </div>
    </div>
    <div class="w3-col s12 m2 w3-panel w3-card-4 w3-padding-medium w3-round-medium">
        <span class="">EFFECTIVE RATE</span>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s12 w3-container">
                <label class="w3-label w3-padding-bottom">Daily Rate</label>
                <input name="emp_no" type="hidden" id="emp_no" value="<?php echo $emp_no; ?>" />
                <input name="rate" type="text" id="rate" maxlength="100" style="width: 100%" class="rate w2ui-input w3-round-medium w3-padding-small w3-border" value="<?php echo $rate; ?>">
            </div>
        </div>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s12 w3-container">
                <label class="w3-label w3-padding-bottom">Incentives</label>
                <input name="incentive" type="text" id="incentive" maxlength="100" style="width: 100%" class="rate w2ui-input w3-round-medium w3-padding-small w3-border" value="<?php echo $incentive; ?>" />
            </div>
        </div>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s12 w3-container">
                <label class="w3-label w3-padding-bottom">Total Pay</label>
                <input name="total" type="text" id="total" maxlength="100" style="width: 100%" class="w2ui-input w3-round-medium w3-padding-small w3-border" value="<?php echo $total; ?>" readonly />
            </div>
        </div>
        <?php if (substr($access_rights, 0, 4) === "A+E+"){ ?>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s12 w3-container">
                <textarea name="rate_remarks" type="text" id="rate_remarks" style="width: 100%; height: 50px; resize: none" class="w3-input" placeholder="Remarks for rate changes" required></textarea>
            </div>
        </div>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s12 w3-container">
                <button class="w3-button w3-padding-small w3-right w3-round-medium w3-green w3-hover-black" id="save" onclick="save_rate()">Save Rate</button>
            </div>
        </div>
        <?php } ?>
    </div>

    <div class="w3-col s6 m5 w3-panel">
        <span class="w3-center">EMPLOYMENT STATUS</span>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s2 w3-container">
                <label class="w3-label w3-padding-bottom">Date Hired:</label><br>
                <span><?php echo $date_hired; ?></span>
            </div>
            <div class="w3-col s4 w3-container">
                <span><?php echo $lengthOfStay; ?></span>
            </div>
            <div class="w3-col s6 w3-container">
                <label class="w3-label w3-padding-bottom">Employment Status:</label>
                <br>
                <select name="emp_status" id="emp_status" class="w3-select w3-transparent" style="width: 100%; padding: 5px;">
                    <?php
                        $sql = mysqli_query($con,"SELECT * FROM employment_status order by description ASC") or die (mysqli_error($con));
                        while ($opt=mysqli_fetch_array($sql)){
                            $emp_desc=$opt['description'];
                            $employment_status_code=$opt['employment_status_code'];
                        ?>
                        <option class="w3-small" value="<?php echo $employment_status_code; ?>" <?php if ($group == $employment_status_code) { echo 'selected'; } ?>><?php echo $emp_desc; ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <?php if (substr($access_rights, 0, 4) === "A+E+"){ ?>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s12 w3-container">
                <textarea name="remarks" type="text" id="remarks" class="w3-input" style="width: 100%; height: 50px; resize: none" placeholder="Remarks for employment status" required></textarea>
            </div>
        </div>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s12 w3-container">
                <button class="w3-button w3-padding-small w3-right w3-round-medium w3-green w3-hover-black" id="save" onclick="save_employment()">Save Status</button>
            </div>
        </div>
        <?php } ?>
        <div class="w3-col s12 w3-margin-top">
            <span class="w3-center">EMPLOYMENT HISTORY</span>
            <div class="w3-col s12 w3-container">
                <div id="status_hist" class="w3-border tableFixHead"  style="height: 150px;">
                <table class="w3-tiny w3-table-all w3-striped">
                    <thead>
                        <tr><th>Reference</th><th>From</th><th>To</th><th>Remarks</th><th colspan="2"></th></tr>
                    </thead>
                <?php
                
                $rhist = mysqli_query($con,"SELECT * FROM master_journal WHERE `reference` LIKE '%Employment Status%' AND `employee_no`='$pin' order by `seq_no` DESC") or die (mysqli_error($con));
                    while ($rhist_data=mysqli_fetch_array($rhist)){
                        $ref=$rhist_data['reference'];
                        $from=$rhist_data['change_from'];
                        $to=$rhist_data['change_to'];
                        $by=$rhist_data['user_id'];
                        $ts=$rhist_data['time_stamp'];
                        $rm=$rhist_data['remarks'];
                    ?>
                    <tr><td><?php echo $ref;?></td><td><?php echo $from;?></td><td><?php echo $to;?></td><td><?php echo $rm;?></td><td><?php echo $by;?></td><td><?php echo $ts;?></td></tr>
                <?php } ?>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="w3-col s6 m5 w3-panel">
        <span class="w3-center">RATE HISTORY</span>
        <div class="w3-col s12 w3-margin-top">
            <div id="status_hist" class="w3-border tableFixHead"  style="height: 250px;">
                <table class="w3-tiny w3-table-all w3-striped">
                    <thead>
                        <tr><th>Reference</th><th>From</th><th>To</th><th>Remarks</th><th colspan="2"></th></tr>
                    </thead>
                <?php
                
                $hist = mysqli_query($con,"SELECT * FROM master_journal WHERE `change_to` >= 0 AND `change_to` < 10000 AND `reference` NOT LIKE '%Employment%' AND `reference` NOT LIKE '%Compute%' AND `reference` NOT LIKE '%Work Schedule%'  AND `reference` NOT LIKE '%Birth%' AND `employee_no`='$pin' order by `seq_no` DESC") or die (mysqli_error($con));
                    while ($hist_data=mysqli_fetch_array($hist)){
                        $ref=$hist_data['reference'];
                        $from=$hist_data['change_from'];
                        $to=$hist_data['change_to'];
                        $by=$hist_data['user_id'];
                        $ts=$hist_data['time_stamp'];
                        $rm=$hist_data['remarks'];
                    ?>
                    <tr><td><?php echo $ref;?></td><td><?php echo $from;?></td><td><?php echo $to;?></td><td><?php echo $rm;?></td><td><?php echo $by;?></td><td><?php echo $ts;?></td></tr>
                <?php } ?>
            </div>
        </div>
    </div>

</div>
<script type="text/javascript">

function close_it(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    if(w2ui.hasOwnProperty('grid')){
        w2ui['grid'].destroy();
    }
    $.ajax({
        url: 'home',
        success: function(data){
            $('#grid').load('page/master');
            $('#append_data').remove();
            w2utils.unlock(div);
        }
    })
}

$(document).on("change", ".rate", function() {
    var sum = 0.00;
    $(".rate").each(function(){
        sum += +$(this).val();
    });
    $("#total").val(sum);
});


function save_rate(){
    if($('#rate_remarks').val() == ''){
        w2alert('Please put remarks on changes');
    }else{
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/master1",
            type: "post",
            data: {
                cmd: "save-rate",
                emp_no : $('#emp_no').val(),
                rate : $('#rate').val(),
                incentive : $('#incentive').val(),
                total : $('#total').val(),
                rm : $('#rate_remarks').val()
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success"){
                    w2alert(jObject.message);
                    $('#grid').load('modules/employee_rate_maint.php?emp_no=100'+ $('#emp_no').val() +"&&cmd=edit");
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection or Session Expired!");
                w2utils.unlock(div);
            }
        })
    }
}

function save_employment(){
    if($('#remarks').val() == ''){
        w2alert('Please put remarks on status changes');
    }else{
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/master1",
            type: "post",
            data: {
                cmd: "save-status",
                emp_no : $('#emp_no').val(),
                emp_status : $('#emp_status').val(),
                remarks : $('#remarks').val()
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success"){
                    w2alert(jObject.message);
                    $('#grid').load('modules/employee_rate_maint.php?emp_no=100'+ $('#emp_no').val() +"&&cmd=edit");
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection or Session Expired!");
                w2utils.unlock(div);
            }
        })
    }
}
</script>
</body>
</html>
