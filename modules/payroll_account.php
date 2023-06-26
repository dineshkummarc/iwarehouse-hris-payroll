<?php
error_reporting(0);
$program_code = 16;
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
<div class="w3-panel w3-border w3-card-4 w3-round-large w3-padding">
    <div class="w3-bar">
        <button class="w3-button w3-red w3-tiny w3-round-large w3-right" onclick="getBack()">CLOSE</button>
    </div>
    <div class="w3-bar w3-block">
        <div class="w2ui-field w3-bar-item">
            <input name="group" type="list" class="w3-small" id="group" style="width: 200px;" />
            <input name="store" type="list" class="w3-small" id="store" style="width: 350px;" />
            <input name="date" class="w3-small date" id="date" style="width: auto;" autocomplete="off"/>
            <button class="w2ui-btn" id="get_data" onclick="extact_data()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                EXTRACT</button>
            <?php if (substr($access_rights, 8, 2) === "P+") { ?>
            <button class="w2ui-btn w3-hide" id="payslip" onclick="payslip()">PAYSLIP</button>
            <?php } if (substr($access_rights, 0, 6) === "A+E+D+") { ?>
            <button class="w2ui-btn w3-hide" id="post_payroll" onclick="post_payroll()">POST PAYROLL</button>
            <?php } ?>
            <i class="fa-spin fa-solid fa-spinner w3-hide" id="spinner"></i>&nbsp;<span class="w3-small" id="wait"></span>
        </div>
    </div>
</div>
<div class="w3-panel w3-border w3-round-medium w3-padding w3-hide w3-card-4" id="account_data"></div>

<script type="text/javascript">
    $(":input.date").w2field("date");
    var _stores;
    var group;

    function getBack() {
        get_default();
    }

    function payslip() {
        window.open("payslip?store=" + $('#store').w2field().get().id +"&date=" + $(":input#date").val() +"&pay_group=" +$('#group').w2field().get().id,"_blank","toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=500,width=4000,height=4000");
    }

    $(document).ready(function() {
        var c = $("div#timelog_data");
        var h = window.innerHeight - 100;
        c.css("height", h);
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        get_store_data();
        get_group();
    });

    function get_store_data() {
        $.ajax({
            url: "page/master1",
            type: "post",
            data: {
                cmd: "get-store"
            },
            success: function(data) {
                if (data !== "") {
                    var _return = jQuery.parseJSON(data);
                    if (_return.status === "success") {
                        _stores = _return.store;
                        $('input#store').w2field('list', {
                            items: _stores
                        });
                    } else {
                        w2alert("Sorry, No DATA found!");
                    }
                }
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
            }
        });
    }


    function get_group() {
        var div = $('#main');
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
                        w2utils.unlock(div);
                    } else {
                        w2alert("Sorry, No DATA found!");
                        w2utils.unlock(div);
                    }
                }
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
                w2utils.unlock(div);
            }
        });
    }

    function extact_data() {
        var _group = $('#group').w2field().get().id;
        var _store = $('#store').w2field().get().id;
        var _date = $('#date').val();
        $('#spinner').removeClass('w3-hide');
        $('#wait').text('Please wait..');
        $.ajax({
            url: "page/get_payroll_account",
            type: "get",
            data: {
                _group: _group,
                _store: _store,
                _date : _date
            },
            success: function(data) {
                $('#account_data').html(data);
                $('#spinner').addClass('w3-hide');
                $('#post_payroll').removeClass('w3-hide');
                $('#payslip').removeClass('w3-hide');
                $('#wait').text('');
                $('#account_data').removeClass('w3-hide');
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
                $('#spinner').addClass('w3-hide');
                $('#wait').text('');
            }
        })
    }

    function post_payroll(){
        var div = $('#account_data');
        var _group = $('#group').w2field().get().id;
        var _store = $('#store').w2field().get().id;
        w2utils.lock(div, 'Posting Payroll..', true);
        $.ajax({
            url: "page/post_payroll",
            type: "get",
            data: {
                _group: _group,
                _store: _store
            },
            success: function(data) {
                w2utils.unlock();
                if(data!=""){
                    w2alert('Error on Payroll Posting!');
                    w2utils.unlock(div);
                }else{
                    w2alert("Payroll Posted for "+$('#group').w2field().get().text +"!");
                    w2utils.unlock(div);
                }
            },
            error: function() {
                w2utils.unlock();
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
            }
        });
    }

    function exportExcel() {
        var _group = $('#group').w2field().get().id;
        var _store = $('#store').w2field().get().id;
        var _date = $('#date').val();
        window.open("reports/exportToExcel?pay_group=" + _group +"&store=" +_store+ "&pay_date=" +_date, "_blank");
    }

</script>