<?php

$program_code = 3;
require_once('../common/functions.php');

?>
<div class="w3-panel w3-border w3-card-4 w3-round-large w3-padding" id="timelog">
    <div class="w3-bar">
        <button class="w3-button w3-red w3-tiny w3-round-large w3-right" onclick="getBack()">CLOSE</button>
    </div>
    <div class="w3-bar w3-block">
        <div class="w2ui-field w3-bar-item">
            <input name="group" type="list" class="w3-small" id="group" style="width: 40%;" />
            <input name="store" type="list" class="w3-small" id="store" style="width: 60%;" />
            <input name="trans_date" class="w3-small date w3-hide" id="trans_date" style="width: auto;" autocomplete="off" />
            <button class="w2ui-btn w3-hide" id="gen_time" onclick="generate_time_credit()"><i class="fa fa-refresh" aria-hidden="true"></i>GENERATE</button>
            <button class="w2ui-btn" id="get_data" onclick="extact_data()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                GET</button>
            <button class="w2ui-btn" id="regen_time" onclick="regenerate()"><i class="fa fa-refresh" aria-hidden="true"></i>
                REGENERATE TIME</button>
            <button class="w2ui-btn w3-hide" id="cancel" onclick="cancel()"><i class="fa fa-ban" aria-hidden="true"></i>
                CANCEL</button>
            <i class="fa-spin fa-solid fa-spinner w3-hide" id="spinner"></i>&nbsp;<span class="w3-small" id="wait"></span>
        </div>
    </div>
</div>
<div id="timelog_data"></div>

<script type="text/javascript">
    $(":input.date").w2field("date");

    var c = $("div#grid");
    var h = window.innerHeight - 100;
    c.css("height", h);

    var _stores;
    var group;

    function getBack() {
        get_default();
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

    $(document).ready(function() {
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
                        $('input#store').w2field('list', { items: _stores });
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
                        $('input#group').w2field('list', { items: group });
                        w2utils.unlock(div);
                    } else {
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

    function extact_data() {
        if($('#group').val() == ''){
            w2alert('Please select Payroll Group!');
        }else if($('#store').val() == ''){
            w2alert('Please select Store!');
        }else{
            var _group = $('#group').w2field().get().id;
            var _store = $('#store').w2field().get().id;
            $('#spinner').removeClass('w3-hide');
            $('#wait').text('Please wait..');
            $('#get_data').prop("disabled", true);
            $.ajax({
                url: "page/time_log",
                type: "post",
                data: {
                    cmd: "get-data",
                    _group: _group,
                    _store: _store
                },
                success: function(data) {
                    $('#timelog_data').html(data);
                    $('#spinner').addClass('w3-hide');
                    $('#wait').text('');
                    $('#get_data').prop("disabled", false);
                },
                error: function() {
                    w2alert("Sorry, There was a problem in server connection!");
                    $('#spinner').addClass('w3-hide');
                    $('#wait').text('');
                    $('#get_data').prop("disabled", false);
                }
            });
        }
    }

    function generate_time_credit() {
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
            $('#spinner').removeClass('w3-hide');
            $('#wait').text('Generating time, Please wait..');
            $.ajax({
                url: "page/generate_time",
                type: "get",
                data: {
                    trans_date: _date,
                    _group: _group,
                    _store: _store
                },
                success: function(data) {
                    if (data.includes('success')) {
                        //post_shift(_date);
                        credit_time(_date,_group,_store);
                        $('#wait').text('Crediting time, Please wait..');
                    } else {
                        w2alert("No Attendance Imported for "+ _date);
                        $('#trans_date').prop("disabled", false);
                        $('#spinner').addClass('w3-hide');
                        $('#wait').text('');
                    }
                },
                error: function() {
                    w2alert("No Attendance Imported for "+ _date);
                    $('#trans_date').prop("disabled", false);
                    $('#spinner').addClass('w3-hide');
                    $('#wait').text('');
                }
            });
        }
    }


    function credit_time(_date,_group,_store) {
        $.ajax({
            url: "page/time_log",
            type: "post",
            data: {
                cmd: "generate-time-credit",
                date : _date,
                _group: _group,
                _store: _store
            },
            success: function(data) {
                if (data.includes('success')) {
                    w2alert('Done Generate Time for ' + _date);
                    $('#trans_date').addClass('w3-hide');
                    $('#gen_time').addClass('w3-hide');
                    $('#spinner').addClass('w3-hide');
                    $('#get_data').removeClass('w3-hide');
                    $('#trans_date').prop("disabled", false);
                    $('#regen_time').removeClass('w3-hide');
                    $('#cancel').addClass('w3-hide');
                    $('#wait').text('');
                    extact_data();
                } else {
                    w2alert("No Attendance Imported for "+ _date);
                    $('#trans_date').prop("disabled", false);
                    $('#wait').text('');
                    $('#spinner').addClass('w3-hide');
                }
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!");
            }
        });
    }

    function get_emp_time(id,date){
        console.log(id,date);
    }
</script>