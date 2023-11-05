<?php
error_reporting(0);
$program_code = 41;
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
<div class="w3-responsive w3-mobile" id="master">
    <div id="my_toolbar" style="padding: 3px;"></div>
    <div id="my_grid" style="width: 100%; height: 450px;"></div>
</div>
<script type="text/javascript">

    $(document).ready(function () {
        var c = $("div#dtr");
        var g = $("#my_grid");
        var h = window.innerHeight - 100;
        c.css("height", h);
        g.css("height", h - 50);
        get_default();
    });

    $(function () {
        $('#my_toolbar').w2toolbar({
            name: 'my_toolbar',
            items: [
                { type: 'html', html: '<input id="pay_group" type="text" class="w3-input w3-margin-left" size="30" />' },
                { type: 'html', html: '<input id="cut_off" type="text" class="w3-input w3-margin-left" size="30" />' },
                { type: 'button',  id: 'get',  text: 'EXTRACT' },
                { type: 'break' },
                { type: 'button',  id: 'print',  text: 'PRINT', disabled: true }
            ],
            onClick: function (event) {
                switch (event.target){
                    case 'get':
                        extract_data();
                    break;
                    case 'print':
                        if (w2ui.my_grid.records.length > 0) {
                            window.open(src + "?pay_group=" + $('#pay_group').w2field().get().id + "&cutoff_date=" + $('#cut_off').w2field().get().id + "&cmd=print","printarea", "width=900,height=900");
                        } else {
                            w2alert("Please generate dtr first!");
                        }
                    break;
                }
            }
        }); 

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
            columnGroups: [],
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
                cmd: "get-options"
            },
            dataType: "json",
            success: function (jObject){
                if (jObject !== ""){
                    if(jObject.status === "success"){
                        $('input#pay_group').w2field('list', { items: jObject.pay_group });
                        $('input#cut_off').w2field('list', { items: jObject.cut_off });
                    }else{
                        w2alert("Sorry, No DATA found!");
                    }
                }
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function extract_data() {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        w2ui.my_grid.clear();
        w2ui.my_grid.reset();
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-records",
                cutoff: $('#cut_off').w2field().get().id,
                group: $('#pay_group').w2field().get().id
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    w2ui.my_grid.columns = jObject.columns;
                    w2ui.my_grid.refresh();
                    w2ui.my_grid.add(jObject.records);
                    if(jObject.records.length > 0){
                        w2ui.my_toolbar.enable("print");
                    }
                } else {
                    w2alert(jObject.message);
                }
                w2utils.unlock(div);
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }
</script>