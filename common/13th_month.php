<?php

$program_code = 17;
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
<div class="w3-responsive w3-mobile" id="master">
    <div id="worksheet_grid" style="width: 100%; height: 450px;"></div>
</div>

<script type="text/javascript">

    var c = $("div#worksheet_grid");
    var h = window.innerHeight - 185;
    c.css("height", h);

    const src = "page/generate_13th";

$(function () {    
    $('#worksheet_grid').w2grid({ 
        name: 'worksheet_grid', 
        header: '13TH MONTH - WORKSHEET',
        multiSearch: false,
        show: {
            header: true,
            toolbar: true,
            footer: true
        },
        searches: [
            {field: 'pin', caption: 'EMPLOYEE NO', type: 'int'},
            {field: 'name', caption: 'EMPLOYEE NAME', type: 'text'}
        ],
        records: [],
        toolbar: {
            items: [
                {type: 'html', id: 'item1',
                    html: '<div style="padding: 3px 10px;">' +
                          ' PERIOD:' +
                          '    <input id="pfr" size="15" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/>' +
                          '    <input id="pto" size="15" style="padding: 3px; border-radius: 2px; border: 1px solid silver"/>' +
                          '</div>'
                },
                {type: 'button', id: 'gen', caption: 'GENERATE'},
                {type: 'spacer'},
                {type: 'button', id: 'post', caption: 'POST TO PAYROLL'}
            ],
            onClick: function (event) {
                switch (event.target){
                    case "gen":
                        w2confirm('Proceed to generate 13th month for this year?', function (btn) {
                            if (btn === "Yes") {
                                generate_emp_13th();
                            }
                        });
                    break;
                    case "post":
                        w2confirm('Proceed to posting the 13th month for this year to next payroll?', function (btn) {
                            if (btn === "Yes") {
                                post_emp_13th();
                            }
                        });
                    break;
                }
            }
        },
        columns: [
            {field: 'empno', caption: 'EMP NO', size: '80px', resizable: true, attr: "align=right"},
            {field: 'name', caption: 'EMPLOYEE`S NAME', size: '300px', resizable: true},
            {field: 'group', caption: 'PAYROLL GROUP', size: '200px', resizable: true},
            {field: 'store', caption: 'STORE NAME', size: '350px', resizable: true},
            {field: 'credit', caption: 'CREDIT(HRS)', size: '120px', resizable: true, attr: "align=right", render: 'float:2'},
            {field: 'amount', caption: 'PAY AMOUNT(ANNUAL)', size: '200px', resizable: true, attr: "align=right", render: 'float:2'},
            {field: 'net', caption: '13Month Pay', size: '200px', resizable: true, attr: "align=right", render: 'float:2'}
        ]
    });
});

            $(document).ready(function () {
                set_input("", "");
                get_records();
            });

            function set_input(date_f, date_t) {
                $("#tb_worksheet_grid_toolbar_item_w2ui-reload, #tb_worksheet_grid_toolbar_item_w2ui-column-on-off, #tb_worksheet_grid_toolbar_item_w2ui-break0, #tb_worksheet_grid_toolbar_item_w2ui-search").hide();
                var _grid = $("div#worksheet_grid");
                var _datef = _grid.find(":input#pfr");
                var _datet = _grid.find(":input#pto");
                _datef.w2field('date');
                _datet.w2field('date');
                _datef.val(date_f);
                _datet.val(date_t);
            }

            function generate_emp_13th() {
                var _grid = $("div#worksheet_grid");
                var _datef = _grid.find(":input#pfr").val();
                var _datet = _grid.find(":input#pto").val();
                w2ui['worksheet_grid'].lock('Generating...', true);
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                        cmd: "generate",
                        datef: _datef,
                        datet: _datet
                    },
                    success: function (data) {
                        w2ui['worksheet_grid'].unlock();
                        if (data !== "") {
                            var _response = jQuery.parseJSON(data);
                            if (_response.status === "success") {
                                w2ui['worksheet_grid'].clear();
                                w2ui['worksheet_grid'].add(_response.records);
                                set_input(_datef, _datet);
                            } else {
                                w2alert(_response.message);
                            }
                        }
                    },
                    error: function () {
                        w2ui['worksheet_grid'].unlock();
                        alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
                    }
                });
            }

            function get_records() {
                var _grid = $("div#worksheet_grid");
                var _datef = _grid.find(":input#pfr").val();
                var _datet = _grid.find(":input#pto").val();
                w2ui['worksheet_grid'].lock('Please wait...', true);
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                        cmd: "get-records"
                    },
                    success: function (data) {
                        w2ui['worksheet_grid'].unlock();
                        if (data !== "") {
                            var _response = jQuery.parseJSON(data);
                            if (_response.status === "success") {
                                w2ui['worksheet_grid'].clear();
                                w2ui['worksheet_grid'].add(_response.records);
                                set_input(_datef, _datet);
                            } else {
                                w2alert(_response.message);
                            }
                        }
                    },
                    error: function () {
                        w2ui['worksheet_grid'].unlock();
                        alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
                    }
                });
            }

            function post_emp_13th() {
                w2ui['worksheet_grid'].lock('Posting to payroll...', true);
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                        cmd: "post_to_payroll"
                    },
                    success: function (data) {
                        w2ui['worksheet_grid'].unlock();
                        if (data !== "") {
                            var _response = jQuery.parseJSON(data);
                            if (_response.status === "success") {
                                w2alert("Generation of 13th month payroll Successful!");
                                get_records();
                            } else {
                                w2alert(_response.message);
                            }
                        }
                    },
                    error: function () {
                        w2ui['worksheet_grid'].unlock();
                        alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
                    }
                });
            }
</script>