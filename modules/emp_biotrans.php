<?php
$program_code = 27;
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
?>
<div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row w3-padding-top" style="overflow-y: scroll;">
    <input name="group" type="list" class="w3-small" id="group" style="width: 250px;" />
    <input name="trans_date" class="w3-small date" id="trans_date" style="width: auto;" autocomplete="off"/>
    <button class="w2ui-btn" id="get_data" onclick="extact_data()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                EXTRACT</button>
</div>
<div class="w3-col s12 w3-panel w3-small">
    <div class="w3-col s12 m3">
        <div id="emp_data"></div>
    </div>
    <div class="w3-col s12 m9 w3-panel">
        <div id="emp_log_data"></div>
    </div>
</div>
<script type="text/javascript">
    var group;

    function getBack() {
        get_default();
    }

    $(document).ready(function() {
        var c = $("div#grid");
        var h = window.innerHeight - 100;
        c.css("height", h);
        $(":input.date").w2field("date");
        get_group();
    });

    function get_group() {
        $.ajax({
            url: "page/time_log",
            type: "post",
            data: {
                cmd: "get-group"
            },
            success: function(data) {
                if (data !== "") {
                    var _return = jQuery.parseJSON(data);
                    if (_return.status === "success") {
                        group = _return.group;
                        $('input#group').w2field('list', {
                            items: group
                        });
                    } else {
                        w2alert("Sorry, No DATA found!");
                    }
                }
            },
            error: function() {
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function extact_data() {
        $('#emp_log_data').hide();
        var _group = $('#group').w2field().get().id;
        var _date = $('#trans_date').val();
        $.ajax({
            url: "page/emp_log",
            type: "post",
            data: {
                cmd: "get-emp-bio",
                _group: _group,
                _date: _date
            },
            success: function(data) {
                $('#emp_data').html(data);
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!");
            }
        })
    }

    function get_emp_log(pin) {
        $('#emp_log_data').hide();
        $('.emp_log').removeClass('w3-black');
        let _date = $('#trans_date').val();
        //console.log(_date,pin)
        $.ajax({
            url: "page/emp_log",
            type: "post",
            data: {
                cmd: "get-emp-bio-trans",
                _pin: pin,
                _date: _date
            },
            success: function(data) {
                $('#emp_log_data').html(data);
                $('#emp_log_data').show();
                $('#'+pin).addClass('w3-black');
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!");
            }
        })
    }

    function for_memo(_code,_pin){
        var _trans_date=$("#trans_date").val();
        var _new="1";
        $.ajax({
            url: "page/emp_log",
            type: "post",
            data: {
                cmd : "make-swipe-memo",
                date: _trans_date,
                pin: _pin,
                code: _code,
                new: _new
            },
            success: function(data) {
                if (data !== "") {
                    var _return = jQuery.parseJSON(data);
                    if (_return.status === "success") {
                        get_emp_log(_pin);
                    }else{
                        w2alert(_return.message);
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
    function cancel_memo(_code,_pin){
        var _trans_date=$("#trans_date").val();
        var _new="0";
        $.ajax({
            url: "page/emp_log",
            type: "post",
            data: {
                cmd : "make-swipe-memo",
                date: _trans_date,
                pin: _pin,
                code: _code,
                new: _new
            },
            success: function(data) {
                if (data !== "") {
                    var _return = jQuery.parseJSON(data);
                    if (_return.status === "success") {
                        get_emp_log(_pin);
                    }else{
                        w2alert(_return.message);
                    }
                }else{
                    w2alert("Sorry, There was a problem in server connection!");
                }
            },
            error: function() {
                alert("Sorry, there was a problem in server connection!");
            }
        });
    }
</script>