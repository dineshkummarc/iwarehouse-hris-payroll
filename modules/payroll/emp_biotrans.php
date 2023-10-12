<?php
$program_code = 27;
require_once('../../system.config.php');
require_once('../../common_functions.php');
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
<div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row w3-padding-top w3-container">
    <input name="group" type="list" class="w3-small" id="group" style="width: 250px;" />
    <input name="trans_date" class="w3-small date" id="trans_date" style="width: auto;" autocomplete="off"/>
    <button class="w2ui-btn" id="get_data" onclick="extact_data()"><i class="fa fa-cloud-download" aria-hidden="true"></i>EXTRACT</button>
</div>
<div class="w3-col s12 w3-panel w3-small">
    <div class="w3-col s12 m3">
        <div id="my_grid" style="width: 95%;"></div>
    </div>
    <div class="w3-col s12 m9 w3-hide" id="bio_data">
        <div id="shift_sched"></div>
    </div>
</div>
<script type="text/javascript">

    $(document).ready(function () {
        var g = $("#my_grid");
        var h = window.innerHeight - 200;
        g.css("height", h);
        $(":input.date").w2field("date");
        get_default();
    });

    $(function () {
        $('#my_grid').w2grid({ 
            name: 'my_grid', 
            show: { 
                toolbar: false,
                footer: false,
                lineNumbers: true,
                toolbarReload: false,
                toolbarSearch: false,
                toolbarInput: false,
                toolbarColumns: false,
            },
            multiSearch: false,
            columns: [],
            records: [],
            onUnselect: function(event) {
                $("#bio_data").addClass("w3-hide");
            },
            onSelect: function(event) {
                get_emp_log(event.recid);
            }
        });
    });

    function get_default() {
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-group"
            },
            success: function(data) {
                if (data !== "") {
                    var _return = jQuery.parseJSON(data);
                    if (_return.status === "success") {
                        $('input#group').w2field('list', { items: _return.group });
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
        if($('#group').val() === ""){
            $('#group').focus();
        }else if($('#trans_date').val() === ""){
            $('#trans_date').focus();
        }else{
            $('#bio_data').addClass("w3-hide");
            w2ui.my_grid.reset();
            w2ui.my_grid.clear();
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-emp-bio",
                    _group: $('#group').w2field().get().id,
                    _date: $('#trans_date').val()
                },
                dataType: "json",
                success: function(jObject) {
                    if (jObject !== "") {
                        if (jObject.status === "success") {
                            w2ui.my_grid.columns = jObject.columns;
                            w2ui.my_grid.refresh();
                            w2ui.my_grid.add(jObject.records);
                        }else{
                            w2alert(jObject.message);
                        }
                        w2utils.unlock(div);
                    }else{
                        w2alert("Sorry, There was a problem in server connection!");
                    }
                },
                error: function() {
                    w2alert("Sorry, There was a problem in server connection!");
                }
            });
        }
    }

    function get_emp_log(recid) {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-emp-bio-trans",
                recid: recid,
                date: $('#trans_date').val()
            },
            success: function(data) {
                if (data !== "") {
                    $("#shift_sched").html(data);
                    $('#bio_data').removeClass("w3-hide");
                    w2utils.unlock(div);
                }
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!");
            }
        });
    }

    function for_memo(_code,_pin){
        var _trans_date=$("#trans_date").val();
        var _new = 1;
        $.ajax({
            url: src,
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
                        extact_data();
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
        var _new = 0;
        $.ajax({
            url: src,
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
                        extact_data();
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