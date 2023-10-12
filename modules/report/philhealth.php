<?php
$program_code = 25;
require_once('../../system.config.php');
require_once('../../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
    if ($level <= $plevel) {
        echo json_encode(array("status" => "error", "message" => "Higher level required!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
?>
<div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row w3-padding-small">
    <div class="w3-col s12 my_toolbar" style="height: 30px;"></div>
    <div class="w3-col s12 my_grid w3-mobile w3-responsive"></div>
</div>
<script type="text/javascript">

    $(document).ready(function() {
        var c = $("div.my_grid");
        var h = window.innerHeight - 185;
        c.css("height", h);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "set-grid",
                option: "phil"
            },
            dataType: "json",
            success: function(jObject) {
                if (jObject.status === "success") {
                    config.group = jObject.group;
                    config.payperiod = jObject.cutoff;
                    config.toolbar.items = jObject.tool;
                    config.my_grid.columns = jObject.column;
                    $("div.my_grid").w2grid(config.my_grid);
                } else {
                    w2alert(jObject.message);
                }
            },
            error: function() {
                alert("Sorry, there was a problem in server connection!");
            }
        });
    });

    var config = {
        payperiod: [],
        group: [],
        toolbar: {
            name: 'my_toolbar',
            items: [],
            onRender: function(event) {
                event.onComplete = function() {
                    $(":input#paydate").w2field("list", {
                        items: config.payperiod
                    });
                    $(":input#pay_group").w2field("list", {
                        items: config.group
                    });
                };
            },
            onClick: function(event) {
                switch (event.target) {
                    case "gen":
                        get_records();
                        break;
                    case "print":
                        if (w2ui.my_grid.records.length > 0) {
                            let _date = $('#paydate').val();
                            let _group = $('#pay_group').w2field().get().id;
                            window.open(src+"?paydate=" + _date + "&pay_group=" + _group + "&cmd=print&option=phil","printarea", "width=900,height=900");
                        } else {
                            w2alert("Please generate report!");
                        }
                        break;
                    case "export":
                        if (w2ui.my_grid.records.length > 0) {
                            let _date = $('#paydate').val();
                            let _group = $('#pay_group').w2field().get().id;
                            window.open(src+"?paydate=" + _date + "&pay_group=" + _group + "&cmd=export&option=phil");
                        } else {
                            w2alert("Please generate report!");
                        }
                        break;
                }
            }
        },
        my_grid: {
            name: 'my_grid',
            show: {
                footer: true,
                toolbarReload: false,
                toolbar: true,
                lineNumbers: true
            },
            onRender: function(event) {
                event.onComplete = function() {
                    setTimeout(function() {
                        if (w2ui.toolbar) {
                            w2ui.toolbar.destroy();
                        }
                        $("div.my_toolbar").w2toolbar(config.toolbar);
                    }, 100);
                };
            },
            multiSelect: true,
            columnGroups: [],
            columns: []
        }
    };

    function get_records(){
        w2ui.my_grid.clear();
        w2ui.my_grid.refresh();
        w2ui.my_grid.lock('Refreshing..', true);
        var _date = $('#paydate').val();
        var _group = $('#pay_group').w2field().get().id;
        $.ajax({
            url: src,
            method:"POST",
            data:{
                cmd: "get-records",
                _date : _date,
                _group: _group,
                option: "phil"
            },
            dataType: "json",
            success: function(jObject){
                if (jObject.status === "success") {
                    w2ui.my_grid.clear();
                    w2ui.my_grid.refresh();
                    w2ui.my_grid.add(jObject.records);
                    w2ui.my_grid.unlock();
                }else{
                    w2alert(jObject.message);
                    w2ui.my_grid.unlock();
                }
            }
        });
    }
</script>