<?php
error_reporting(0);
$program_code = 16;
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
<div class="w3-panel w3-border w3-card-4 w3-round-large w3-padding" style="margin-left: 4px; margin-right: 4px;">
    <div class="w3-bar">
        <button class="w3-button w3-red w3-tiny w3-round-large w3-right" onclick="dashboard()">CLOSE</button>
    </div>
    <div class="w3-bar w3-block">
        <div class="w2ui-field w3-bar-item">
            <input name="token" type="hidden" class="w3-small" id="token" style="width: 200px;" value="<?php echo $_SESSION["security_key"]; ?>"/>
            <input name="group" type="list" class="w3-small" id="group" style="width: 200px;" />
            <input name="store" type="list" class="w3-small" id="store" style="width: 350px;" />
            <input name="date" type="list" class="w3-small" id="date" style="width: 120px;"/>
            <!--<input name="date" class="w3-small date" id="date" style="width: auto;" autocomplete="off"/>-->
            <button class="w2ui-btn" id="get_data" onclick="extact_data()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                EXTRACT</button>
            <?php if (substr($access_rights, 8, 2) === "P+") { ?>
            <button class="w2ui-btn w3-hide" id="payslip" onclick="payslip()">PAYSLIP</button>
            <?php } if (substr($access_rights, 0, 6) === "A+E+D+") { ?>
            <button class="w2ui-btn w3-hide" id="post_payroll" onclick="post_payroll()">POST PAYROLL</button>
            <?php } ?>
        </div>
    </div>
</div>
<div class="w3-responsive w3-mobile w3-hide" style="margin-left: 4px; margin-right: 4px;" id="payroll_earnings">
    <div class="">
        <span id="todate" style="font-weight: bolder;"></span>
    </div>
    <div class="">
        <span id="trans_date" style="font-weight: bolder;"></span>
    </div>
	<div id="my_grid" style="width: 100%;"></div>
</div>

<script type="text/javascript">

    function payslip() {
        const token = $("#token").val();
        const store = $('#store').w2field().get().id;
        const pay_date = $("#date").w2field().get().id;
        const pay_group = $("#group").w2field().get().id;
        window.open(src+"?cmd=payslip&token="+token+"&store="+store+"&date="+pay_date+"&pay_group="+pay_group,"printarea","width=900,height=1000");
    }

    $(document).ready(function() {
        var c = $("div#my_grid");
        var h = window.innerHeight - 300;
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
            multiSearch: false,
            searches: [
                {field: 'name', caption: 'Employee Name', type: 'text'},
                {field: 'recid', caption: 'Employee No', type: 'int'}
            ],
            columnGroups: [],
            columns: [],
            records: [],
            toolbar: {
                items: [
                    { type: 'spacer' },
                    { type: 'break' },
                    { type: 'button',  id: 'excel',  text: 'EXCEL', icon: 'fa-solid fa-file-excel', hidden: true }
                ],
                onClick: function (event) {
                    var group = $('#group').w2field().get().id;
                    var store = $('#store').w2field().get().id;
                    var date = $('#date').w2field().get().id;
                    window.open(src+"?cmd=export&store="+store+"&group="+group+"&date="+date);
                }
            }
        });
    });

    function get_default() {
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
                        $('input#date').w2field('list', { items: _return.date_list });
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

    function extact_data() {
        if($('#group').val() == ''){
            $('#group').focus();
        }else if($('#store').val() == ''){
            $('#store').focus();
        }else if($("#date").val() == ''){
            $("#date").focus();
        }else{
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            w2ui.my_grid.toolbar.hide("excel");
            w2ui.my_grid.clear();
            w2ui.my_grid.reset();
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-payroll-account",
                    group: $('#group').w2field().get().id,
                    store: $('#store').w2field().get().id,
                    date : $('#date').w2field().get().id
                },
                dataType: "json",
                success: function(jObject) {
                    if(jObject.status === "success"){
                        $("#payroll_earnings, #payslip").removeClass("w3-hide");
                        $("#todate").text(jObject.todate);
                        $("#trans_date").text(jObject.trans_date);
                        w2ui.my_grid.columnGroups = jObject.colGroup;
                        w2ui.my_grid.columns = jObject.columns;
                        w2ui.my_grid.refresh();
                        w2ui.my_grid.add(jObject.records);
                        if(jObject.can_print){
                            w2ui.my_grid.toolbar.show("excel");
                        }
                        if(jObject.posted){
                            $("#post_payroll").addClass("w3-hide");
                        }else{
                            $("#post_payroll").removeClass("w3-hide");
                        }
                        w2utils.unlock(div);
                    }else{
                        w2alert(jObject.message);
                        w2utils.unlock(div);
                    }
                },
                error: function() {
                    w2alert("Sorry, There was a problem in server connection!");
                    w2utils.unlock(div);
                }
            });
        }
    }

    function post_payroll(){
        var div = $('#main');
        w2utils.lock(div, 'Posting Payroll..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd : "post-payroll",
                group: $('#group').w2field().get().id,
                store: $('#store').w2field().get().id
            },
            dataType: "json",
            success: function(data) {
                if(data.status === "success"){
                    w2alert(data.message);
                    w2utils.unlock(div);
                    if(data.posted){
                        $("#post_payroll").addClass("w3-hide");
                    }else{
                        $("#post_payroll").removeClass("w3-hide");
                    }
                }else{
                    w2alert(data.message);
                    w2utils.unlock(div);
                }
            },
            error: function() {
                w2utils.unlock(div);
                w2alert("Sorry, There was a problem in server connection!  Maybe it was too busy or call the System Admin for assistance.");
            }
        });
    }

</script>