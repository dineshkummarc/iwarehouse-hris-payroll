<?php
global $db, $db_hris;
$program_code = 1;
require_once('../../system.config.php');
require_once('../../common_functions.php');
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
$cfn->log_activity("EMPLOYEE RATE MAINTENANCE");
$pin_no = substr($_GET["emp_no"], 3);
$cmd = $_GET["cmd"];

$check_emp_no = $db->prepare("SELECT * FROM $db_hris.`employee_rate` AS er JOIN $db_hris.`master_data` AS md ON er.`employee_no` = md.`employee_no` WHERE md.`employee_no` = :pin");
$check_emp_no->execute(array(":pin" => $pin_no));
if($check_emp_no->rowCount()){
    $emp_data = $check_emp_no->fetch(PDO::FETCH_ASSOC);
    $emp_no = $emp_data['employee_no'];
    $pin = $emp_data['pin'];
    $_SESSION["emp_no"] = '100'.$emp_no;
    $rate = number_format($emp_data['daily_rate'], 2);
    $incentive = number_format($emp_data['incentive_cash'], 2);
    $total = number_format($emp_data['total_pay'], 2);
    $name = $emp_data["given_name"].'&nbsp;'.$emp_data["middle_name"].'&nbsp;'.$emp_data["family_name"];
    $date_hired = $emp_data['employment_date'];
    $group = $emp_data['group_no'];

    //get Date diff as intervals 
    $d1 = date_create($date_hired);
    $d2 = date_create(date("Y-m-d"));
    $interval = date_diff($d1,$d2);

    $year = $interval->format('%y');
    $months = $interval->format('%m');
    $days = $interval->format('%d');
    $yr = $year == 0 ? $yr = '' : $yr = $year > 1 ? $interval->format('%y years & ') : $interval->format('%y year & ');
    $mo = $months == 0 ? '' : $mo = $months > 1 ? $interval->format('%m months & ') : $interval->format('%m month & ');
    $day = $days > 1 ? $interval->format('%d days') : $interval->format('%d day');
    $lengthOfStay = $yr.$mo.$day;

}else{
    $get_data1 = $db->prepare("SELECT * FROM  $db_hris.`master_data` WHERE `employee_no` = :eno");
    $get_data1->execute(array(":eno" => $pin_no));
    if($get_data1->rowCount()){
        while($data = $get_data1->fetch(PDO::FETCH_ASSOC)){
            $pin = $data['pin'];
            $emp_no = $data['employee_no'];
            $rate = '0.00';
            $incentive = '0.00';
            $total = '0.00';
            $name = $data["given_name"].'&nbsp;'.$data["middle_name"].'&nbsp;'.$data["family_name"];
            $date_hired = $data['date_hired'];
            $group = $data['group_no'];

             //get Date diff as intervals 
            $d1 = date_create($date_hired);
            $d2 = date_create(date("Y-m-d"));
            $interval = date_diff($d1,$d2);

            $year = $interval->format('%y');
            $months = $interval->format('%m');
            $days = $interval->format('%d');
            $yr = $year == 0 ? $yr = '' : $yr = $year > 1 ? $interval->format('%y years & ') : $interval->format('%y year & ');
            $mo = $months == 0 ? '' : $mo = $months > 1 ? $interval->format('%m months & ') : $interval->format('%m month & ');
            $day = $days > 1 ? $interval->format('%d days') : $interval->format('%d day');
            $lengthOfStay = $yr.$mo.$day;
        }
    }

} ?>
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
        <span class=""><b>EFFECTIVE RATE</b></span>
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
        <span class="w3-center"><b>EMPLOYMENT STATUS</b></span>
        <div class="w3-col s12 w3-margin-top">
            <div class="w3-col s2 w3-container">
                <label class="w3-label w3-padding-bottom">Date Hired:</label><br>
                <span><?php echo (new DateTime($date_hired))->format("m-d-Y"); ?></span>
            </div>
            <div class="w3-col s4 w3-container">
                <span><?php echo $lengthOfStay; ?></span>
            </div>
            <div class="w3-col s6 w3-container">
                <label class="w3-label w3-padding-bottom">Employment Status:</label>
                <br>
                <select name="emp_status" id="emp_status" class="w3-select w3-transparent" style="width: 100%; padding: 5px;">
                    <?php
                        $status = $db->prepare("SELECT * FROM $db_hris.`employment_status` order by `description` ASC");
                        $status->execute();
                        if($status->rowCount()){
                            while ($status_data = $status->fetch(PDO::FETCH_ASSOC)){
                                $emp_desc=$status_data['description'];
                                $employment_status_code=$status_data['employment_status_code'];
                            ?>
                            <option class="w3-small" value="<?php echo $employment_status_code; ?>" <?php if ($group == $employment_status_code) { echo 'selected'; } ?>><?php echo $emp_desc; ?></option>
                    <?php }
                    } ?>
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
            <span class="w3-center"><b>EMPLOYMENT HISTORY</b></span>
            <div class="w3-col s12 w3-container">
                <div id="status_hist" class="w3-border tableFixHead"  style="height: 250px;">
                <table class="w3-tiny w3-table-all w3-striped">
                    <thead>
                        <tr><th>Reference</th><th>From</th><th>To</th><th>Remarks</th><th colspan="2"></th></tr>
                    </thead>
                <?php
                
                $rhist = $db->prepare("SELECT * FROM $db_hris.`master_journal` WHERE `reference` LIKE :estatus AND `employee_no`=:eno order by `seq_no` DESC");
                $rhist->execute(array(":eno" => $pin, ":estatus" => "%Employment Status%"));
                if($rhist->rowCount()){
                    while ($rhist_data = $rhist->fetch(PDO::FETCH_ASSOC)){
                        $ref=$rhist_data['reference'];
                        $from=$rhist_data['change_from'];
                        $to=$rhist_data['change_to'];
                        $by=$rhist_data['user_id'];
                        $ts=$rhist_data['time_stamp'];
                        $rm=$rhist_data['remarks'];
                    ?>
                    <tr><td><?php echo $ref;?></td><td><?php echo $from;?></td><td><?php echo $to;?></td><td><?php echo $rm;?></td><td><?php echo $by;?></td><td><?php echo $ts;?></td></tr>
                <?php }
                } ?>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="w3-col s6 m5 w3-panel">
        <span class="w3-center"><b>RATE HISTORY</b></span>
        <div class="w3-col s12 w3-margin-top">
            <div id="status_hist" class="w3-border tableFixHead" style="height: 450px;">
                <table class="w3-tiny w3-table-all w3-striped">
                    <thead>
                        <tr><th>Reference</th><th>From</th><th>To</th><th>Remarks</th><th colspan="2"></th></tr>
                    </thead>
                <?php
                
                $hist = $db->prepare("SELECT * FROM $db_hris.`master_journal` WHERE (`reference` = :daily OR `reference` LIKE :inctv) AND `employee_no` = :eno ORDER BY `seq_no` DESC");
                $hist->execute(array(":daily" => "Daily Rate", ":inctv" => "Incentive Cash", ":eno" => $pin));
                while ($hist_data = $hist->fetch(PDO::FETCH_ASSOC)){
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
        destroy_grid();
        $.ajax({
            success: function(data){
                $('#grid').load('./modules/hris/master.php');
                $('#append_data').remove();
                w2utils.unlock(div);
            }
        });
    }

    $(document).on("change", ".rate", function() {
        var sum = 0.00;
        $(".rate").each(function(){
            sum += +$(this).val();
        });
        $("#total").val(sum.toFixed(2));
    });


    function save_rate(){
        if($('#rate_remarks').val() == ''){
            w2alert('Please put remarks on changes');
        }else{
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
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
                        $('#grid').load('./modules/hris/employee_rate_maint.php?emp_no=100'+ $('#emp_no').val() +"&&cmd=edit");
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
                url: src,
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
                        $('#grid').load('./modules/hris/employee_rate_maint.php?emp_no=100'+ $('#emp_no').val() +"&&cmd=edit");
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
