<?php

$program_code = 1;
require_once('../common/functions.php');

?>
<div class="w3-panel w3-bottombar w3-border-orange w3-padding">
    <label for="date" class="w3-small"><b>Payroll Date: </b></label>
    <input name="date" class="w3-small date" id="date" style="width: auto;" autocomplete="off"/>
    <button class="w2ui-btn" id="get_data" onclick="extract_summary()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                SHOW SUMMARY</button><i class="fa-spin fa-solid fa-spinner w3-hide" id="spinner"></i>&nbsp;<span class="w3-small" id="wait"></span>
</div>
<div class="w3-padding-bottom w3-small w3-hide" id="btn_sum">
    <button class="w3-button w3-round-medium w3-silver w3-border w3-hide">BDO ATM</button>
    <button class="w3-button w3-round-medium w3-silver w3-border" onclick="print_summary();">PRINT</button>
</div>
<div class="w3-padding-top w3-hide" id="summary_data"></div>

<script type="text/javascript">

    function print_summary() {
        var n = Math.floor(Math.random() * 11);
        var k = Math.floor(Math.random() * 1000000);
        var m = String.fromCharCode(n) + k;
        let token = $(":input#date").val();
        window.open("page/get_payroll_summary?token="+ m + "&cmd=print-summary&date=" + token ,"_blank","toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=500,width=4000,height=4000");
    }

    $(document).ready(function() {
        var c = $("div#timelog_data");
        var h = window.innerHeight - 100;
        c.css("height", h);
        $(":input.date").w2field("date");
    });

    function extract_summary() {
        var trans_date = $('#date').val();
        $('#spinner').removeClass('w3-hide');
        $('#wait').text('Please wait..');
        $.ajax({
            cmd: "get-payroll-summary",
            url: "page/get_payroll_summary",
            type: "POST",
            data: {
                cmd : "get-payroll-summary",
                trans_date : trans_date
            },
            success: function(data) {
                if(data === ""){
                    w2alert("No Payroll Summary for "+trans_date);
                    $('#spinner').addClass('w3-hide');
                    $('#wait').text('');
                }else{
                    $('#summary_data').html(data);
                    $('#spinner').addClass('w3-hide');
                    $('#wait').text('');
                    $('#summary_data').removeClass('w3-hide');
                    $('#btn_sum').removeClass('w3-hide');
                }
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
                $('#spinner').addClass('w3-hide');
                $('#wait').text('');
            }
        })
    }

</script>