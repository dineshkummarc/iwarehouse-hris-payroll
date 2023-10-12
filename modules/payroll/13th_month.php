<?php

$program_code = 17;
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
<div class="w3-responsive w3-mobile" id="master" style="margin-right: 4px; margin-left: 4px;">
    <div id="my_grid" style="width: 100%;"></div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var c = $("div#my_grid");
        var h = window.innerHeight - 150;
        c.css("height", h);
        get_records();
    });

    $(function() {
        $('#my_grid').w2grid({
            name: 'my_grid',
            header: '13TH MONTH - WORKSHEET',
            show: {
                header: true,
                toolbar: true,
                footer: true,
                toolbarReload: true,
                toolbarColumns: false,
                lineNumbers: true
            },
            records: [],
            toolbar: {
                items: [{
                        type: 'html',
                        id: 'item1',
                        html: '<div style="padding: 3px 10px;">' +
                            ' PERIOD:' +
                            '    <input id="pfr" size="15" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/>' +
                            '    <input id="pto" size="15" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/>' +
                            '</div>'
                    },
                    { type: 'button', id: 'gen', caption: 'GENERATE' },
                    { type: 'spacer' },
                    { type: 'button', id: 'post', caption: 'POST TO PAYROLL', hidden: true }
                ],
                onClick: function(event) {
                    switch (event.target) {
                        case "gen":
                            w2confirm('Proceed to generate 13th month for this year?', function(btn) {
                                if (btn === "Yes") {
                                    generate_emp_13th();
                                }
                            });
                            break;
                        case "post":
                            w2confirm('Proceed to posting the 13th month for this year to next payroll?', function(btn) {
                                if (btn === "Yes") {
                                    post_emp_13th();
                                }
                            });
                            break;
                    }
                }
            },
            columns: []
        });
    });



    function generate_emp_13th() {
        var div = $('#main');
        w2utils.lock(div, 'Generating...', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "generate",
                datef: $(":input#pfr").val(),
                datet: $(":input#pto").val()
            },
            success: function(data) {
                w2utils.unlock(div);
                if (data !== "") {
                    var _response = jQuery.parseJSON(data);
                    if (_response.status === "success") {
                        w2ui.my_grid.clear();
                        w2ui.my_grid.columns = _response.columns;
                        w2ui.my_grid.add(_response.records);
                        setTimeout(() => {
                            $("input#pfr, input#pto").w2field("date");
                        }, 100);
                        if(_response.records.length > 0){
                            w2ui.my_grid.toolbar.show("post");
                        }
                    } else {
                        w2alert(_response.message);
                    }
                }
            },
            error: function() {
                w2utils.unlock(div);
                alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
            }
        });
    }

    function get_records() {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-records"
            },
            dataType: "json",
            success: function(data) {
                w2utils.unlock(div);
                setTimeout(() => {
                    $("input#pfr, input#pto").w2field("date");
                }, 100);
                if (data !== "") {
                    if (data.status === "success") {
                        w2ui.my_grid.clear();
                        w2ui.my_grid.columns = data.columns;
                        w2ui.my_grid.add(data.records);
                        if(data.records.length > 0){
                            if(data.isPost){
                                w2ui.my_grid.toolbar.hide("post");
                            }else{
                                w2ui.my_grid.toolbar.show("post");
                            }
                        }
                    } else {
                        w2alert(data.message);
                    }
                }
            },
            error: function() {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
            }
        });
    }

    function post_emp_13th() {
        var div = $('#main');
        w2utils.lock(div, 'Posting to payroll...', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "post_to_payroll"
            },
            success: function(data) {
                w2utils.unlock(div);
                if (data !== "") {
                    var _response = jQuery.parseJSON(data);
                    if (_response.status === "success") {
                        w2alert(_response.message);
                        get_records();
                    }else{
                        w2alert(_response.message);
                    }
                }
            },
            error: function() {
                w2utils.unlock(div);
                alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
            }
        });
    }
</script>