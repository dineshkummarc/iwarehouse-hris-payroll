<?php
$program_code = 3;
require_once('../common/functions.php');
?>
<div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row" style="overflow-y: scroll;">
    <div class="w3-col s12 sss-toolbar" style="height: 30px;"></div>
    <div class="w3-col s12 sss-window w3-mobile w3-responsive"></div>
</div>

<script type="text/javascript">

    const src = "page/reports";

    $(document).ready(function () {
        var c = $("div.sss-window");
        var h = window.innerHeight - 185;
        c.css("height", h);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "set-grid-sss"
            },
            dataType: "json",
            success: function (jObject) {
                if (jObject.status === "success") {
                    config.group = jObject.group;
                    config.payperiod = jObject.cutoff;
                    config.toolbar.items = jObject.tool;
                    config.sss_grid.columns = jObject.column;
                    $("div.sss-window").w2grid(config.sss_grid);
                }else if(jObject.status === "error"){
                    w2alert(jObject.message);
                }
            },
            error: function () {
                alert("Sorry, there was a problem in server connection!");
            }
        });
    });

    var config = {
        payperiod: [],
        group: [],
        toolbar: {
            name: 'sss_toolbar',
            items: [],
            onRender: function (event) {
                event.onComplete = function () {
                    $(":input#paydate").w2field("list", {items: config.payperiod});
                    $(":input#pay_group").w2field("list", {items: config.group});
                };
            },
            onClick: function (event) {
                switch (event.target) {
                    case "gen":
                        get_sss_records();
                        break;
                    case "print":
                        if (w2ui.sss_grid.records.length > 0) {
                            let _date = $('#paydate').val();
                            let _group = $('#pay_group').w2field().get().id;
                            window.open("page/reports.php?paydate=" + _date + "&pay_group=" + _group + "&cmd=print_sss");
                        } else {
                            w2alert("Please generate report!");
                        }
                        break;
                    case "export":
                        if (w2ui.sss_grid.records.length > 0) {
                            let _date = $('#paydate').val();
                            let _group = $('#pay_group').w2field().get().id;
                            window.open("page/reports.php?paydate=" + _date + "&pay_group=" + _group + "&cmd=export-sss");
                        } else {
                            w2alert("Please generate report!");
                        }
                        break;
                }
            }
        },
        sss_grid: {
            name: 'sss_grid',
            show: {
                footer: true,
                toolbarReload: false,
                toolbar: true,
                lineNumbers: true
            },
            onRender: function (event) {
                event.onComplete = function () {
                    setTimeout(function () {
                        if (w2ui.toolbar) {
                            w2ui.toolbar.destroy();
                        }
                        $("div.sss-toolbar").w2toolbar(config.toolbar);
                    }, 500);
                };
            },
            multiSelect: true,
            columnGroups: [],
            columns: []
        }
    };

    function get_sss_records(){
        w2ui['sss_grid'].clear();
        w2ui['sss_grid'].refresh();
        w2ui['sss_grid'].lock('Refreshing..', true);
        var _date = $('#paydate').val();
        var _group = $('#pay_group').w2field().get().id;
        $.ajax({
            url: src,
            method:"POST",
            data:{
                cmd: "get-sssrecords",
                _date : _date,
                _group: _group
            },
            dataType: "json",
            success: function(jObject){
                if (jObject.status === "success") {
                    w2ui['sss_grid'].clear();
                    w2ui['sss_grid'].refresh();
                    w2ui['sss_grid'].add(jObject.records);
                    w2ui['sss_grid'].unlock();
                }else{
                    w2alert(jObject.message);
                    w2ui['sss_grid'].unlock();
                }
            }
        });
    }
    
</script>