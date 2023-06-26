<?php
$program_code = 22;
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
<div class="w3-responsive w3-mobile">
    <div class="w3-row">
        <div class="w3-half w3-container">
            <div id="ot_grid" style="width: 100%; height: 450px;"></div>
        </div>
        <div class="w3-half w3-container">
            <div id="approve_grid" style="width: 100%; height: 450px;"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
const src = "page/get_bio";

$(function () {    
    $('#ot_grid').w2grid({ 
        name: 'ot_grid',
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
        toolbar: {
            items: [
                { type: 'spacer'},
                { type: 'button',  id: 'approve',  caption: 'Approve', icon: 'fa fa-thumbs-up', disabled: true}
            ],
            onClick: function (event) {
                var sel_rec_ids = w2ui.ot_grid.getSelection();
                switch (event.target){
                    case "approve":
                        if(w2ui['ot_grid'].getSelection().length > 0){
                            var sel_rec = w2ui['ot_grid'].getSelection();
                            var sel_record = w2ui['ot_grid'].get(sel_rec[0]);
                            var emp_no = sel_record.pin;
                            var date = sel_record.date;
                            show_approve_emp_ot(emp_no,date);
                        }
                    break;
                }
            }
        }
    });
});

$(function () {    
    $('#approve_grid').w2grid({ 
        name: 'approve_grid',
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
        toolbar: {
            items: [
                { type: 'spacer'},
                { type: 'button',  id: 'cancel',  caption: 'Cancel', icon: 'fa fa-thumbs-down', disabled: true}
            ],
            onClick: function (event) {
                switch (event.target){
                    case "cancel":
                        if(w2ui['approve_grid'].getSelection().length > 0){
                            var sel_rec = w2ui['approve_grid'].getSelection();
                            w2confirm('Cancel Overtime?', function (btn){
                                if(btn === 'No'){
                                    w2ui['approve_grid'].refresh();
                                }else{
                                    var sel_record = w2ui['approve_grid'].get(sel_rec[0]);
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
                '            <input name="emp_no" id="emp_no" type="text" maxlength="100" style="width: 250px" readonly/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>Employee Name:</label>'+
                '        <div>'+
                '           <input name="emp_name" id="emp_name" type="text" maxlength="100" style="width: 250px" readonly/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>Trans Date:</label>'+
                '        <div>'+
                '            <input name="trans_date" id="trans_date" maxlength="100" style="width: 250px" readonly/>'+
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
                '    <button class="w2ui-btn" name="reset">Reset</button>'+
                '    <button class="w2ui-btn" name="save">Approve</button>'+
                '</div>',
            fields: [
                { field: 'emp_no', type: 'text', required: true },
                { field: 'emp_name', type: 'text', required: true },
                { field: 'trans_date', type: 'date', required: true },
                { field: 'trans_time', type: 'text', required: true }
            ],
            actions: {
                "save": function () { approve_ot(); },
                "reset": function () { this.clear(); }
            }
        });
    }
    $().w2popup('open', {
        title   : 'Employee OverTime',
        body    : '<div id="form" style="width: 100%; height: 100%;"></div>',
        style   : 'padding: 15px 0px 0px 0px',
        width   : 500,
        height  : 300, 
        showMax : true,
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
                                $("input#trans_time").val(_response.trans_time);
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
    var c = $("div#ot_grid");
    var h = window.innerHeight - 185;
    c.css("height", h);
    var c = $("div#approve_grid");
    var h = window.innerHeight - 185;
    c.css("height", h);
    get_emp_ot();
    get_approved_ot();
});

w2ui['ot_grid'].on('click', function(event) {
    w2ui['ot_grid'].toolbar.enable('approve');
});

w2ui['approve_grid'].on('click', function(event) {
    w2ui['approve_grid'].toolbar.enable('cancel');
});

function get_emp_ot(){
    w2ui['ot_grid'].toolbar.disable('approve');
    w2ui['ot_grid'].clear();
    w2ui['ot_grid'].refresh();
    w2ui['ot_grid'].lock('Refreshing..', true);
    $.ajax({
        url: src,
        method:"POST",
        data:{ cmd: "get-ot-data" },
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success") {
                w2ui['ot_grid'].clear();
                w2ui['ot_grid'].refresh();
                w2ui['ot_grid'].add(jObject.records);
                w2ui['ot_grid'].unlock();
            }else{
                w2alert(jObject.message);
            }
        }
    });
}

function get_approved_ot(){
    w2ui['approve_grid'].toolbar.disable('approve');
    w2ui['approve_grid'].clear();
    w2ui['approve_grid'].refresh();
    w2ui['approve_grid'].lock('Refreshing..', true);
    $.ajax({
        url: src,
        method:"POST",
        data:{ cmd: "get-approve-ot" },
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success") {
                w2ui['approve_grid'].clear();
                w2ui['approve_grid'].refresh();
                w2ui['approve_grid'].add(jObject.records);
                w2ui['approve_grid'].unlock();
            }else{
                w2alert(jObject.message);
            }
        }
    });
}

function approve_ot(){
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
                get_emp_ot();
                get_approved_ot();
                w2popup.close();
            }else{
                w2alert(data.message);
            }
        }
    });
}

function cancel_ot(emp_no,date){
    //console.table(emp_no,trans_date);
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
                get_emp_ot();
                get_approved_ot();
            }else{
                w2alert(data.message);
            }
        }
    });
}


</script>