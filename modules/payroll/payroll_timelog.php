<?php

$program_code = 13;
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
<div class="w3-panel w3-border w3-card-4 w3-round-large w3-padding" id="timelog" style="margin-left: 4px; margin-right: 4px;">
    <div class="w3-bar">
        <button class="w3-button w3-red w3-tiny w3-round-large w3-right" onclick="dashboard()">CLOSE</button>
    </div>
    <div class="w3-bar w3-block">
        <div class="w2ui-field w3-bar-item">
            <input name="group" type="list" class="w3-small" id="group" style="width: 200px;" />
            <input name="store" type="list" class="w3-small" id="store" style="width: 350px;" />
            <input name="trans_date" class="w3-small date w3-hide" id="trans_date" style="width: auto;" autocomplete="off" />
            <button class="w2ui-btn w3-hide" id="gen_time" onclick="generate_time_and_credit()"><i class="fa fa-refresh" aria-hidden="true"></i>GENERATE</button>
            <button class="w2ui-btn" id="get_data" onclick="extact_data()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                GET</button>
            <?php if (substr($access_rights, 0, 6) === "A+E+D+") { ?>
            <button class="w2ui-btn" id="regen_time" onclick="regenerate()"><i class="fa fa-refresh" aria-hidden="true"></i>
                REGENERATE TIME</button>
            <?php } ?>
            <button class="w2ui-btn w3-hide" id="cancel" onclick="cancel()"><i class="fa fa-ban" aria-hidden="true"></i>
                CANCEL</button>
        </div>
    </div>
</div>
<div class="w3-responsive w3-mobile" style="margin-left: 4px; margin-right: 4px;">
	<div id="my_grid" style="width: 100%;"></div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var c = $("div#my_grid");
        var h = window.innerHeight - 250;
        c.css("height", h);
        $(":input.date").w2field("date");
        get_default();
    });

    $(function () {
        $('#my_grid').w2grid({ 
            name: 'my_grid', 
            show: { 
                toolbar: true,
                footer: false,
                lineNumbers: true,
                toolbarReload: true,
                toolbarSearch: true,
                toolbarInput: true,
                toolbarColumns: false,
            },
            columns: [],
            records: []
        });
    });

    function get_default(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "make-default"
            },
            success: function(data) {
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        $('input#store').w2field('list', { items: _return.store_list });
                        $('input#group').w2field('list', { items: _return.group_list });
                        w2utils.unlock(div);
                    }else{
                        w2alert("Sorry, No DATA found!");
                        w2utils.unlock(div);
                    }
                }
            },
            error: function() {
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function regenerate() {
        $('#get_data').addClass('w3-hide');
        $('#gen_time').removeClass('w3-hide');
        $('#trans_date').removeClass('w3-hide');
        $('#regen_time').addClass('w3-hide');
        $('#cancel').removeClass('w3-hide');
    }

    function cancel() {
        $('#get_data').removeClass('w3-hide');
        $('#gen_time').addClass('w3-hide');
        $('#trans_date').addClass('w3-hide');
        $('#regen_time').removeClass('w3-hide');
        $('#cancel').addClass('w3-hide');
    }

    function extact_data() {
        if($('#group').val() == ''){
            $('#group').focus();
        }else if($('#store').val() == ''){
            $('#store').focus();
        }else{
            var div = $('#main');
            w2utils.lock(div, 'Extracting records..', true);
            var _group = $('#group').w2field().get().id;
            var _store = $('#store').w2field().get().id;
            $('#get_data').prop("disabled", true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-timelog",
                    _group: _group,
                    _store: _store
                },
                dataType: "json",
                success: function(jObject) {
                    if(jObject.status === "success"){
                        w2ui.my_grid.clear();
                        w2ui.my_grid.reset();
                        w2ui.my_grid.columns = jObject.columns;
                        w2ui.my_grid.refresh();
                        w2ui.my_grid.add(jObject.records);
                        $('#get_data').prop("disabled", false);
                        w2utils.unlock(div);
                    }else{
                        $('#get_data').prop("disabled", false);
                        w2alert(jObject.message);
                        w2utils.unlock(div);
                    }
                },
                error: function() {
                    w2alert("Sorry, There was a problem in server connection!");
                    $('#get_data').prop("disabled", false);
                    w2utils.unlock(div);
                }
            });
        }
    }

    function generate_time_and_credit() {
        var _date = $('#trans_date').val();
        var _group = $('#group').w2field().get().id;
        var _store = $('#store').w2field().get().id;
        if($('#group').val() == ''){
            w2alert('Please select Payroll Group!');
        }else if($('#store').val() == ''){
            w2alert('Please select Store!');
        }else if($('#trans_date').val() == '') {
            w2alert('Please select date!');
        }else{
            $('#trans_date').prop("disabled", true);
            var div = $('#main');
            w2utils.lock(div, 'Generating time...', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "generate-time-and-credit",
                    trans_date: _date,
                    _group: _group,
                    _store: _store
                },
                dataType: "json",
                success: function(data) {
                    if (data.status === "success"){
                        w2alert(data.message);
                        cancel();
                        extact_data();
                        $('#trans_date').prop("disabled", false);
                    } else {
                        w2alert(data.message);
                        $('#trans_date').prop("disabled", false);
                        w2utils.unlock(div);
                    }
                },
                error: function() {
                    w2alert("Sorry, There was a problem in server connection!");
                    $('#trans_date').prop("disabled", false);
                    w2utils.unlock(div);
                }
            });
        }
    }
</script>