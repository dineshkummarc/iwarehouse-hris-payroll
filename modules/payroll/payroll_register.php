<style>
.w2ui-col-group {
    color:#2196F3!important;
    font-weight: bold;
}
</style>
<?php
error_reporting(0);
$program_code = 15;
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
<div class="w3-panel w3-border w3-card-4 w3-round-large w3-padding" style="margin-left: 4px; margin-right: 4px;">
    <div class="w3-bar">
        <button class="w3-button w3-red w3-tiny w3-round-large w3-right" onclick="dashboard()">CLOSE</button>
    </div>
    <div class="w3-bar w3-block">
        <div class="w2ui-field w3-bar-item">
            <input name="group" type="list" class="w3-small" id="group" style="width: 200px;" />
            <input name="store" type="list" class="w3-small" id="store" style="width: 350px;" />
            <?php if (substr($access_rights, 0, 6) === "A+E+D+") { ?>
            <button class="w2ui-btn" id="get_data" onclick="extract_data()"><i class="fa fa-cloud-download" aria-hidden="true"></i>
                GET</button>
            <?php } ?>
        </div>
    </div>
</div>
<div class="w3-responsive w3-mobile w3-hide" style="margin-left: 4px; margin-right: 4px;" id="payroll_register">
	<div id="my_grid" style="width: 100%;"></div>
</div>

<script type="text/javascript">

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
            records: []
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

    function extract_data() {
        if($('#group').val() == ''){
            $('#group').focus();
        }else if($('#store').val() == ''){
            $('#store').focus();
        }else{
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            w2ui.my_grid.clear();
            w2ui.my_grid.reset();
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-payroll-register",
                    group: $('#group').w2field().get().id,
                    store: $('#store').w2field().get().id
                },
                dataType: "json",
                success: function(jObject) {
                    if(jObject.status === "success"){
                        $("#payroll_register").removeClass("w3-hide");
                        w2ui.my_grid.columnGroups = jObject.colGroup;
                        w2ui.my_grid.columns = jObject.columns;
                        w2ui.my_grid.refresh();
                        w2ui.my_grid.add(jObject.records);
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

</script>