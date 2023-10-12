<?php
$program_code = 33;
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
<script>
    $(document).ready(function() {
        var c = $("#my_grid");
        var h = window.innerHeight - 185;
        c.css("height", h);
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "set-grid",
                option: "late"
            },
            dataType: "json",
            success: function(data) {
                if (data !== "") {
                    if (data.status === "success") {
                        w2ui.my_grid.columns = data.columns;
                        setTimeout(function() {
                            w2ui.my_grid.refresh();
                            $(":input.date").w2field("date");
                            w2utils.unlock(div);
                        }, 200);
                    } else {
                        w2alert(data.message);
                        w2utils.unlock(div);
                    }
                } else {
                    w2utils.unlock(div);
                    w2alert("Sorry, there was a problem in server connection!");
                }
            },
            error: function() {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    });

    $(function() {
        $('#my_grid').w2grid({
            name: 'my_grid',
            show: {
                toolbar: true,
                footer: true,
                lineNumbers: true,
                toolbarReload: false,
                toolbarSearch: false,
                toolbarInput: false,
                toolbarColumns: false,
            },
            onExpand: function (event) {
                set_date();
            },
            onCollapse: function (event) {
                set_date();
            },
            columnGroups: [],
            columns: [],
            records: [],
            toolbar: {
                items: [
                    { type: 'html', html: '<div class="w3-padding-small">Date Range: <input id="fdate" class="w3-input w3-margin-left date" size="10" /></div>' },
                    { type: 'html', html: '<div class="w3-padding-small">to <input id="tdate" class="w3-input w3-margin-left date" size="10" /></div>' },
                    { type: 'button', id: 'extract',  text: 'GET', icon: 'fa-solid fa-repeat' }
                ],
                onClick: function(event) {
                    switch (event.target) {
                        case "extract":
                            getLateRecords();
                            break;
                    }
                }
            }
        });
    });

    function getLateRecords() {
        if($("#fdate").val() === ""){
            $("#fdate").focus();
        }else if($("#tdate").val() === ""){
            $("#tdate").focus();
        }else{
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-late-records",
                    fr: $("#fdate").val(),
                    to: $("#tdate").val()
                },
                dataType: "json",
                success: function(data) {
                    if(data !== ""){
                        if(data.status === "success"){
                            w2ui.my_grid.clear();
                            w2ui.my_grid.add(data.records);
                            $(":input.date").w2field("date");
                                $("#fdate").val(data.fdate);
                                $("#tdate").val(data.tdate);
                            setTimeout(function() {
                                w2utils.unlock(div);
                            }, 100);
                        }else{
                            w2alert(data.message);
                            $("#fdate").val(data.fdate);
                            $("#tdate").val(data.tdate);
                            w2utils.unlock(div);
                        }
                    }else{
                        w2alert("No response from the server!");
                    }
                },
                error: function() {
                    w2utils.unlock(div);
                    w2alert("Please try again later!");
                }
            });
        }
    }

    function set_date() {
        var fdate = $("#fdate").val();
        var tdate = $("#tdate").val();
        setTimeout(function() {
            $(":input.date").w2field("date");
            $("#fdate").val(fdate);
            $("#tdate").val(tdate);
        },50);
        
    }
</script>

<body>
    <div class="w3-padding-top w3-container" id="summary_data">
        <div id="my_grid" style="width: 100%;"></div>
    </div>
</body>