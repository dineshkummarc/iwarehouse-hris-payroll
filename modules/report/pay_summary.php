<?php

$program_code = 28;
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
<style>
.w2ui-grid-summary {
    color:#2196F3!important;
    font-weight: bold;
}
</style>
<div class="w3-panel w3-padding-small w3-bottombar w3-border-orange w3-container">
    <label for="date" class="w3-small"><b>Payroll Date: </b></label>
    <input name="date" type="list" class="w3-small date" id="date" style="width: 150px;" autocomplete="off"/>
    <button class="w3-round-medium w3-orange w3-text-white w3-small w3-hover-black" style="padding: 4px 10px 4px 10px;" id="get_data" onclick="extract_summary()">
    <i class="fa fa-cloud-download" aria-hidden="true"></i>SHOW SUMMARY</button>
</div>
<div class="w3-padding-top w3-container" id="summary_data">
    <div id="my_grid" style="width: 100%;"></div>
</div>

<script type="text/javascript">

    $(document).ready(function() {
        var c = $("div#my_grid");
        var h = window.innerHeight - 200;
        c.css("height", h);
        get_payroll_dates();
    });

    $(function () {
        $('#my_grid').w2grid({ 
            name: 'my_grid', 
            show: { 
                toolbar: true,
                footer: true,
                lineNumbers: true,
                toolbarReload: true,
                toolbarSearch: true,
                toolbarInput: true,
                toolbarColumns: false,
            },
            columnGroups: [],
            columns: [],
            records: [],
            toolbar: {
                items: [
                    { type: 'spacer' },
                    { type: 'button',  id: 'print',  text: 'PRINT SUMMARY', icon: 'fa-solid fa-print', hidden: true }
                ],
                onClick: function (event) {
                    let token = '<?php echo $_SESSION['security_key']; ?>';
                    var date = $('#date').w2field().get().id;
                    window.open(src+"?token="+ token + "&cmd=print-summary&date=" + date ,"printarea", "width=900,height=900");
                }
            }
        });
    });

    function get_payroll_dates(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "POST",
            data: {
                cmd : "default"
            },
            dataType: "json",
            success: function(data) {
                if(data !== ""){
                    if(data.status === "success"){
                        $('input#date').w2field('list', { items: data.pay_dates });
                        $('input#date').w2field().set({id: data.pay_dates[0].id, text:data.pay_dates[0].text});
                    }
                }
                w2utils.unlock(div);
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
                $('#spinner').addClass('w3-hide');
                $('#wait').text('');
            }
        });
    }

    function extract_summary() {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "POST",
            data: {
                cmd : "get-payroll-summary",
                trans_date : $('input#date').w2field().get().id
            },
            dataType: "json",
            success: function(data) {
                if(data !== ""){
                    if(data.status === "success" && data.records.length > 0){
                        w2ui.my_grid.columns = data.columns;
                        w2ui.my_grid.reset();
                        w2ui.my_grid.clear();
                        w2ui.my_grid.add(data.records);
                        if(data.can_print){
                            w2ui.my_grid.toolbar.show("print");
                        }
                    }
                }
                w2utils.unlock(div);
            },
            error: function() {
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
                w2utils.unlock(div);
            }
        });
    }

</script>