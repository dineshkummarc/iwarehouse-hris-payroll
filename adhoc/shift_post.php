<?php
error_reporting(0);
$program_code = 35;
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
};
$system_date = $cfn->sysconfig("trans date");
?>
<div class="w3-container w3-panel" style="width: 100%;">
    <div class="w3-container w3-padding-small">
        <input name="emp_list" id="emp_list" class="w3-small w3-padding-small" type="list" style="width: 20%;" />
        <?php if (substr($access_rights, 0, 6) === "A+E+D+") { ?>
        <input name="date" class="w3-small date" id="date" style="width: auto;" autocomplete="off"/>
        <button name="post_emp" id="post_emp" class="w2ui-btn w3-small" onclick="post_shift()">POST SHIFT</button>
        <?php } ?>
        <button name="get_emp" id="get_emp" class="w2ui-btn w3-small" onclick="show_posted_shift()">SHOW SHIFT</button>
        <button name="clear_emp" id="clear_emp" class="w2ui-btn w3-small w3-hide" onclick="clear_emp()">CLEAR</button>
        <?php if($system_date != date('Y-m-d')){
            if (substr($access_rights, 0, 6) === "A+E+D+" AND $level >= 8) { ?>
            <button name="post_shift_all" id="post_shift_all" class="w2ui-btn w3-small" onclick="post_shift_all('<?php echo $system_date; ?>')">POST ALL SHIFT  for <?php echo date('m/d/Y', strtotime($system_date)); ?></button>
        <?php } } ?>
        <button name="getBack" id="getDate" class="w2ui-btn w3-small w3-right" onclick="getBack()"><i class="fa-solid fa-rotate-left"></i>&nbsp;Get Back</button>
    </div>
    <div style="width: 100%; height: 450px;" id="emp_shift"></div>
</div>
<script type="text/javascript">
    var employee;

    $(document).ready(function(){
        $(":input.date").w2field("date");
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
                        employee = _return.employee_list;
                        $('input#emp_list').w2field('list', { items: employee });
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
    });

    function post_shift_all(trans_date){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/sys_config",
            type: "post",
            data: {
                cmd : "post-shift-all",
                trans_date: trans_date
            },
            success: function(data) {
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        w2alert(_return.message);
                        w2utils.unlock(div);
                        system_menu(35);
                    }else{
                        w2alert(_return.message);
                        w2utils.unlock(div);
                    }
                }
            },
            error: function() {
                $('#trans_date').prop("disabled", false);
                w2utils.unlock(div);
            }
        });
    }

    function post_shift(){
        if($('#emp_list').val() == ""){
            $('#emp_list').focus();
        }else if($('#date').val() == ""){
            $('#date').focus();
        }else{
            $.ajax({
                url: "page/sys_config",
                type: "post",
                data: {
                    cmd : "post-shift",
                    employee_no : $("#emp_list").w2field().get().id,
                    trans_date: $("#date").val()
                },
                success: function(data) {
                    show_posted_shift();
                },
                error: function() {
                    $('#trans_date').prop("disabled", false);
                    $('#spinner').addClass('w3-hide');
                    $('#wait').text('');
                }
            });
        }
    }

    function show_posted_shift(){
        var emp_no = $("#emp_list").w2field().get().id;
        if($('#emp_list').val() == ""){
            $('#emp_list').focus();
        }else{
            $.ajax({
                url: "page/sys_config",
                type: "post",
                data: {
                    cmd : "show-posted-shift",
                    employee_no : emp_no
                },
                success: function(data) {
                    $('#emp_shift').html(data);
                },
                error: function() {
                    w2alert("Error Posting Shift");
                }
            });
        }
    }
</script>
