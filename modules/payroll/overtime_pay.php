<?php
$program_code = 22;
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
<div class="w3-responsive w3-mobile">
    <div class="w3-row">
        <div class="w3-half w3-container">
            <div id="my_grid" style="width: 100%;"></div>
        </div>
        <div class="w3-half w3-container">
            <div id="my_grid1" style="width: 100%;"></div>
        </div>
    </div>
</div>

<script type="text/javascript">

$(function () {    
    $('#my_grid').w2grid({ 
        name: 'my_grid',
        header: 'EMPLOYEE OVERTIME WITHIN THIS CUT OFF',
        show: { 
            toolbar: true,
            footer: true,
            header: true,
            lineNumbers: true
        },
        multiSearch: false,
        multiSelect: false,
        searches: [
            {field: 'pin', caption: 'Employee No', type: 'int'},
            {field: 'name', caption: 'Full Name', type: 'text'}
        ],
        columns: [
            {field: 'recid', caption: 'No', size: '100px', hidden: true},
            {field: 'pin', caption: 'EMPLOYEE NO', size: '100px'},
            {field: 'name', caption: 'FULL NAME', size: '250px' },
            {field: 'ot_hrs', caption: 'OT Hrs/Mins', size: '100px', attr: "align=center"},
            {field: 'date', caption: 'TransDate', size: '50%'}
        ],
        records: [],
        onUnselect: function(event) {
            w2ui.my_grid.toolbar.disable("approve");
        },
        onSelect: function(event) {
            w2ui.my_grid.toolbar.enable("approve");
        },
        toolbar: {
            items: [
                { type: 'spacer'},
                { type: 'button',  id: 'approve',  caption: 'Approve', icon: 'fa fa-thumbs-up', disabled: true}
            ],
            onClick: function (event) {
                var sel_rec_ids = w2ui.my_grid.getSelection();
                switch (event.target){
                    case "approve":
                        if(w2ui.my_grid.getSelection().length > 0){
                            var sel_rec = w2ui.my_grid.getSelection();
                            var sel_record = w2ui.my_grid.get(sel_rec[0]);
                            var emp_no = sel_record.pin;
                            var date = sel_record.date;
                            show_approve_emp_ot(emp_no,date);
                        }
                    break;
                }
            }
        }
    });
    
    $('#my_grid1').w2grid({ 
        name: 'my_grid1',
        header: 'APPROVED OVERTIME WITHIN THIS CUT OFF',
        show: { 
            toolbar: true,
            footer: true,
            header: true,
            lineNumbers: true
        },
        multiSearch: false,
        multiSelect: false,
        searches: [
            {field: 'pin', caption: 'Employee No', type: 'int'},
            {field: 'name', caption: 'Full Name', type: 'text'}
        ],
        columns: [
            {field: 'recid', caption: 'No', size: '100px', hidden: true},
            {field: 'pin', caption: 'EMPLOYEE NO', size: '100px'},
            {field: 'name', caption: 'FULL NAME', size: '250px' },
            {field: 'ot_hrs', caption: 'OT Hrs/Mins', size: '100px', attr: "align=center"},
            {field: 'date', caption: 'TransDate', size: '50%'}
        ],
        records: [],
        onUnselect: function(event) {
            w2ui.my_grid1.toolbar.disable("cancel");
        },
        onSelect: function(event) {
            w2ui.my_grid1.toolbar.enable("cancel");
        },
        toolbar: {
            items: [
                { type: 'spacer'},
                { type: 'button',  id: 'cancel',  caption: 'Cancel', icon: 'fa fa-thumbs-down', disabled: true}
            ],
            onClick: function (event) {
                switch (event.target){
                    case "cancel":
                        if(w2ui.my_grid1.getSelection().length > 0){
                            var sel_rec = w2ui.my_grid1.getSelection();
                            w2confirm('Cancel Overtime?', function (btn){
                                if(btn === 'No'){
                                    w2ui.my_grid1.refresh();
                                }else{
                                    var sel_record = w2ui.my_grid1.get(sel_rec[0]);
                                    var emp_no = sel_record.pin;
                                    var date = sel_record.date;
                                    cancel_ot(emp_no,date);
                                }
                            });
                        }
                    break;
                }
            }
        }
    });
});


function show_approve_emp_ot (emp_no,date) {
    if (!w2ui.emp_ot) {
        $().w2form({
            name: 'emp_ot',
            style: 'border: 0px; background-color: transparent;',
            formHTML: 
                '<div class="w2ui-page page-0">'+
                '    <div class="w2ui-field">'+
                '        <label>Employee Name:</label>'+
                '        <div>'+
                '            <input name="emp_no" id="emp_no" type="text" maxlength="100" style="width: 250px" readonly class="w3-white"/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>Employee Name:</label>'+
                '        <div>'+
                '           <input name="emp_name" id="emp_name" type="text" maxlength="100" style="width: 250px" readonly class="w3-white"/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>Trans Date:</label>'+
                '        <div>'+
                '            <input name="trans_date" id="trans_date" maxlength="100" style="width: 250px" readonly class="w3-white"/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>OverTime:</label>'+
                '        <div>'+
                '            <input name="trans_time" id="trans_time" style="width: 250px"/>'+
                '        </div>'+
                '    </div>'+
                '</div>'+
                '<div class="w2ui-buttons">'+
                '    <button class="w3-button w3-red w3-round-medium" name="cancel">Cancel</button>'+
                '    <button class="w3-button w3-orange w3-text-white w3-round-medium" name="save">Approve</button>'+
                '</div>',
            fields: [
                { field: 'emp_no', type: 'text', required: true },
                { field: 'emp_name', type: 'text', required: true },
                { field: 'trans_date', type: 'date', required: true },
                { field: 'trans_time', type: 'text', required: true }
            ],
            actions: {
                "save": function () { approve_ot(); },
                "cancel": function () { w2popup.close(); }
            }
        });
    }
    $().w2popup('open', {
        title   : 'Employee OverTime',
        body    : '<div id="form" style="width: 100%; height: 100%;"></div>',
        style   : 'padding: 15px 0px 0px 0px',
        width   : 500,
        height  : 280, 
        showMax : false,
        onToggle: function (event) {
            $(w2ui.emp_ot.box).hide();
            event.onComplete = function () {
                $(w2ui.emp_ot.box).show();
                w2ui.emp_ot.resize();
            }
        },
        onOpen: function (event) {
            event.onComplete = function () {
                // specifying an onOpen handler instead is equivalent to specifying an onBeforeOpen handler, which would make this code execute too early and hence not deliver.
                $('#w2ui-popup #form').w2render('emp_ot');
                $.ajax({
                    url: src,
                    type: "post",
                    data: {
                        cmd: "get-overtime",
                        emp_no: emp_no,
                        trans_date: date
                    },
                    success: function (data){
                        if (data !== "") {
                            var _response = jQuery.parseJSON(data);
                            if (_response.status === "success") {
                                $("input#emp_no").val(_response.emp_no);
                                $("input#emp_name").val(_response.emp_name);
                                $("input#trans_date").val(_response.trans_date);
                                $("input#trans_time").val(_response.trans_time).focus();
                            }else{
                                w2popup.close();
                                w2alert(_response.message);
                            }
                        }
                    }
                });
            }
        }
    });
}

$(document).ready(function(){
    var c = $("div#my_grid, div#my_grid1");
    var h = window.innerHeight - 100;
    c.css("height", h);
    get_overtime_log();
});

function get_overtime_log(){
    w2ui.my_grid.toolbar.disable('approve');
    w2ui.my_grid.clear();
    w2ui.my_grid.refresh();
    w2ui.my_grid1.toolbar.disable('cancel');
    w2ui.my_grid1.clear();
    w2ui.my_grid1.refresh();
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        method:"POST",
        data:{
            cmd: "get-ot-data"
        },
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success") {
                w2ui.my_grid.clear();
                w2ui.my_grid.refresh();
                w2ui.my_grid.add(jObject.records);
                w2ui.my_grid1.clear();
                w2ui.my_grid1.refresh();
                w2ui.my_grid1.add(jObject.records_approve);
                w2utils.unlock(div);
            }else{
                w2alert(jObject.message);
                w2utils.unlock(div);
            }
        },
        error: function() {
            w2alert("Sorry, there was a problem in server connection!");
            w2utils.unlock(div);
        }
    });
}

function approve_ot(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    let emp_no = $('#emp_no').val();
    let trans_date = $('#trans_date').val();
    let trans_time = $('#trans_time').val();
    //console.table(emp_no,trans_date);
    $.ajax({
        url: src,
        type: 'post',
        data: {
            cmd : "approve-ot",
            emp_no: emp_no,
            trans_date: trans_date,
            trans_time: trans_time
        },
        dataType: "json",
        success: function(data){
            if(data.status === "success"){
                get_overtime_log();
                w2popup.close();
                w2utils.unlock(div);
            }else{
                w2alert(data.message);
                w2utils.unlock(div);
            }
        },
        error: function() {
            w2alert("Sorry, there was a problem in server connection!");
            w2utils.unlock(div);
        }
    });
}

function cancel_ot(emp_no,date){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: 'post',
        data: {
            cmd : "cancel-ot",
            emp_no: emp_no,
            date: date
        },
        dataType: "json",
        success: function(data){
            if(data.status === "success"){
                get_overtime_log();
                w2utils.unlock(div);
            }else{
                w2alert(data.message);
                w2utils.unlock(div);
            }
        },
        error: function() {
            w2alert("Sorry, there was a problem in server connection!");
            w2utils.unlock(div);
        }
    });
}


</script>