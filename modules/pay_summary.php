<?php

$program_code = 28;
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
<div class="w3-panel w3-bottombar w3-border-orange w3-padding">
    <label for="date" class="w3-small"><b>Payroll Date: </b></label>
    <input name="date" class="w3-small date" id="date" style="width: auto;" autocomplete="off"/>
    <button class="w2ui-btn" id="get_data" onclick="extract_summary()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                SHOW SUMMARY</button><i class="fa-spin fa-solid fa-spinner w3-hide" id="spinner"></i>&nbsp;<span class="w3-small" id="wait"></span>
</div>
<div class="w3-padding-bottom w3-small w3-hide" id="btn_sum">
    <button class="w3-button w3-round-medium w3-silver w3-border w3-hide">BDO ATM</button>
    <?php if (substr($access_rights, 8, 2) === "P+") { ?>
    <button class="w3-button w3-round-medium w3-silver w3-border" onclick="print_summary();">PRINT</button>
    <?php } ?>
</div>
<div class="w3-padding-top w3-hide" id="summary_data"></div>

<script type="text/javascript">

    function print_summary() {
        let token = '<?php echo $_SESSION['security_key']; ?>';
        let date = $(":input#date").val();
        window.open("page/get_payroll_summary?token="+ token + "&cmd=print-summary&date=" + date ,"_blank","toolbar=yes,scrollbars=yes,resizable=yes,top=500,left=500,width=4000,height=4000");
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