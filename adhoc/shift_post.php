<?php
error_reporting(0);
$program_code = 7;

?>
<div class="w3-container w3-panel" style="width: 100%;">
    <div class="w3-container w3-padding-small">
        <input name="emp_list" id="emp_list" class="w3-small w3-padding-small" type="list" style="width: 20%;" />
        <input name="date" class="w3-small date" id="date" style="width: auto;" autocomplete="off"/>
        <button name="post_emp" id="post_emp" class="w2ui-btn w3-small" onclick="post_shift()">POST SHIFT</button>
        <button name="get_emp" id="get_emp" class="w2ui-btn w3-small" onclick="show_posted_shift()">SHOW SHIFT</button>
        <button name="clear_emp" id="clear_emp" class="w2ui-btn w3-small w3-hide" onclick="clear_emp()">CLEAR</button>
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

    function post_shift(){
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
                w2alert("No Attendance Imported for "+ _date);
                $('#trans_date').prop("disabled", false);
                $('#spinner').addClass('w3-hide');
                $('#wait').text('');
            }
        });
    }

    function show_posted_shift(){
        var emp_no = $("#emp_list").w2field().get().id;
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
</script>
