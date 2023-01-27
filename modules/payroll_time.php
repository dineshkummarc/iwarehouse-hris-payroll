<?php
error_reporting(0);
$program_code = 7;
require_once('../common/functions.php');

?>
<div class="w3-panel w3-border w3-card-4 w3-round-large w3-padding">
    <div class="w3-bar">
        <button class="w3-button w3-red w3-tiny w3-round-large w3-right" onclick="getback()">CLOSE</button>
    </div>
    <div class="w3-bar w3-block">
        <div class="w2ui-field w3-bar-item">
            <input name="group" type="list" class="w3-small" id="group" style="width: auto;" />
            <input name="store" type="list" class="w3-small" id="store" style="width: 40%;" />
            <button class="w2ui-btn" id="get_data" onclick="extract_payroll()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                GET</button>
            <i class="fa-spin fa-solid fa-spinner w3-hide" id="spinner"></i>&nbsp;<span class="w3-small" id="wait"></span>
        </div>
    </div>
</div>
<div id="payroll_time_data"></div>

<script type="text/javascript">
    var _stores;
    var group;

    function getback() {
        get_default();
    }

    $(document).ready(function() {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        get_store();
        get_group();

    });

    function get_store() {
        $.ajax({
            url: "page/master1.php",
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
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function get_group() {
        var div = $('#main');
        $.ajax({
            url: "page/time_log.php",
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
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function extract_payroll() {
        var _group = $('#group').w2field().get().id;
        var _store = $('#store').w2field().get().id;
        $('#spinner').removeClass('w3-hide');
        $('#wait').text('Please wait..');
        $('#get_data').prop("disabled", true);
        $.ajax({
            url: "page/get_payroll_account.php",
            type: "get",
            data: {
                _group: _group,
                _store: _store
            },
            success: function(data) {
                $('#payroll_time_data').html(data);
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
        })
    }
</script>