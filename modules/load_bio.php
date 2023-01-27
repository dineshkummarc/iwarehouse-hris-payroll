<?php

$program_code = 3;
require_once('../common/functions.php');

?>
<div class="w3-responsive w3-mobile" id="att_div">
    <div id="att_toolbar" style="padding: 3px;"></div>
    <div id="att_grid" style="width: 100%; height: 500px;"></div>
</div>
<script type="text/javascript">

$(document).ready(function(){
    var c = $("div#att_div");
    var h = window.innerHeight - 100;
    c.css("height", h);
    setTimeout(function(){
        get_default_log();
   }, 300);
});

var employee_list;
var log_type;
const src = "page/get_bio";


$(function () {    
    $('#att_grid').w2grid({ 
        name: 'att_grid', 
        show: { 
            toolbar: false,
            footer: true,
            lineNumbers: true
        },
        multiSearch: false,
        columns: [
            {field: 'recid', caption: 'No', size: '100px', hidden: true},
            {field: 'pin', caption: 'EMPLOYEE NO', size: '100px'},
            {field: 'name', caption: 'NAME', size: '300px'},
            {field: 'date', caption: 'Date', size: '200px'},
            {field: 'time', caption: 'Time', size: '200px'},
            {field: 'ver', caption: 'Verified', size: '5px;'},
            {field: 'stat', caption: 'Status', size: '5px;'},
            {field: 'by', caption: 'Imported By', size: '10px;'}
        ],
        records: []
    });
});


function get_emp_list(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: "page/employee_deduction",
        type: "post",
        data: {
            cmd: "get-employee"
        },
        success: function (data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    employee_list = _return.employee_list;
                    $('input#emp_list').w2field('list', { items: employee_list });
                    w2utils.unlock(div);
                }else{
                    w2alert("Sorry, No DATA found!");
                    w2utils.unlock(div);
                }
            }
        },
        error: function (){
            w2alert("Sorry, there was a problem in server connection!");
            w2utils.unlock(div);
        }
    });
}

function get_reason(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "get-log-type"
        },
        success: function (data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    log_type = _return.log_type;
                    $('input#att_reason').w2field('list', { items: log_type });
                    w2utils.unlock(div);
                }else{
                    w2alert("Sorry, No DATA found!");
                    w2utils.unlock(div);
                }
            }
        },
        error: function (){
            w2alert("Sorry, there was a problem in server connection!");
            w2utils.unlock(div);
        }
    });
}

$(function () {
    $('#att_toolbar').w2toolbar({
        name: 'att_toolbar',
        items: [
            { type: 'html', id: 'fdate', html: '<div style="padding: 3px 10px;">' +
                        ' FROM:' +
                        '    <input id="fdate" size="15" style="padding: 3px; border-radius: 2px; border: 1px solid silver" autocomplete="off"/>' +
                        '</div>'
            },
            { type: 'button', id: 'get_att_log', caption: 'Import', icon: 'fa fa-download', hidden: true },
            { type: 'button', id: 'confirm_log', caption: 'Confirm', icon: 'fa fa-check', hidden: true },
            { type: 'break', id: 'break', hidden: true },
            { type: 'spacer' },
            { type: 'break' },
            { type: 'button', id: 'new_att', caption: 'New Attendance' },
            { type: 'break' },
            { type: 'button', id: 'del_att', caption: 'Delete Attendance' },
            { type: 'break' },
            { type: 'button',  id: 'close',  caption: 'CLOSE'}
        ],
        onClick: function (event) {
            switch (event.target){
                case 'close':
                    getBack();
                break;
                case 'new_att':
                    new_attendance();
                break;
                case 'get_att_log':
                    get_att();
                break;
                case 'del_att':
                    if(w2ui['att_grid'].getSelection().length > 0){
                        w2confirm('Delete this Attendance', function (btn){
                            if(btn === 'No'){
                                w2ui.att_grid.refresh();
                            }else{
                                delete_this_attendance(w2ui['att_grid'].getSelection()[0]);
                            }
                        });
                    }
                break;
                case 'confirm_log':
                    w2confirm('Confirm Attendance', function (btn){
                        if(btn === 'No'){
                            w2ui.att_grid.refresh();
                        }else{
                            confirm_att();
                        }
                    });
                break;
            }
        }
    });
});


function delete_this_attendance(recid){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "delete-att",
            recid: recid
        },
        dataType: "json",
        success: function (jObject){
            if (jObject.status === "success"){
                get_default_log();
                w2utils.unlock(div);
            }else{
                w2alert(jObject.message);
                w2utils.unlock(div);
            }
        },
        error: function () {
            w2alert(jObject.message);
            w2utils.unlock(div);
        }
    });
}

function new_attendance () {
    if (!w2ui.new_timelog) {
        $().w2form({
            name: 'new_timelog',
            style: 'border: 0px; background-color: transparent;',
            formHTML: 
                '<div class="w2ui-page page-0">'+
                '    <div class="w2ui-field">'+
                '        <label>Employee Name:</label>'+
                '        <div>'+
                '           <input name="emp_list" id="emp_list" type="list" maxlength="100" style="width: 250px"/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>Log Reason:</label>'+
                '        <div>'+
                '            <input name="att_reason" id="att_reason" maxlength="100" style="width: 250px"/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>Trans Date:</label>'+
                '        <div>'+
                '            <input name="att_date" id="att_date" maxlength="100" style="width: 250px"/>'+
                '        </div>'+
                '    </div>'+
                '    <div class="w2ui-field">'+
                '        <label>Trans Time:</label>'+
                '        <div>'+
                '            <input name="att_time" id="att_time" style="width: 250px"/>'+
                '        </div>'+
                '    </div>'+
                '</div>'+
                '<div class="w2ui-buttons">'+
                '    <button class="w2ui-btn" name="reset">Reset</button>'+
                '    <button class="w2ui-btn" name="save">Save</button>'+
                '</div>',
            fields: [
                { field: 'emp_list', type: 'list', required: true, options: { items: employee_list } },
                { field: 'att_reason', type: 'list', required: true, options: { items: log_type } },
                { field: 'att_date', type: 'date', required: true },
                { field: 'att_time', type: 'time', required: true }
            ],
            actions: {
                "save": function () { save_manual_time(); },
                "reset": function () { this.clear(); }
            }
        });
    }
    $().w2popup('open', {
        title   : 'New Employee Time Log',
        body    : '<div id="form" style="width: 100%; height: 100%;"></div>',
        style   : 'padding: 15px 0px 0px 0px',
        width   : 500,
        height  : 300, 
        showMax : true,
        onToggle: function (event) {
            $(w2ui.new_timelog.box).hide();
            event.onComplete = function () {
                $(w2ui.new_timelog.box).show();
                w2ui.new_timelog.resize();
            }
        },
        onOpen: function (event) {
            event.onComplete = function () {
                // specifying an onOpen handler instead is equivalent to specifying an onBeforeOpen handler, which would make this code execute too early and hence not deliver.
                $('#w2ui-popup #form').w2render('new_timelog');
                get_emp_list();
                get_reason();
            }
        }
    });
}

function getBack(){
    get_default();
}

function save_manual_time(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    var emp_list = $(":input#emp_list").w2field().get().id;
    var att_reason = $(":input#att_reason").w2field().get().id;
    var att_date = $(":input#att_date").val();
    var att_time = $(":input#att_time").val();
    if (emp_list !== "" && att_reason !== "" && att_date !== "" && att_time !== ""){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "save-manual-att",
                record: w2ui.new_timelog.record
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success"){
                    w2popup.close();
                    w2ui.new_timelog.clear();
                    get_default_log();
                    w2utils.unlock(div);
                }else{
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function () {
                w2alert(jObject.message);
                w2utils.unlock(div);
            }
        });
    }else{
        w2alert("Please supply all required fields!");
    }
}

function get_att(){
    w2ui['att_toolbar'].hide('get_att_log');
    w2ui['att_toolbar'].hide('break');
    w2ui['att_grid'].lock('Please wait..', true);
    let fdate = $(":input#fdate").val();
    if(fdate == ""){
        w2alert("Please select date from!");
    }else{
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-wall-bio",
                fdate : fdate
            },
            dataType: "json",
            success: function(jObject){
                if (jObject.status === "success") {
                    w2ui['att_grid'].clear();
                    w2ui['att_grid'].refresh();
                    w2ui['att_grid'].add(jObject.records);
                    w2ui['att_grid'].unlock();
                    w2ui['att_toolbar'].show('confirm_log');
                    w2ui['att_toolbar'].hide('get_att_log');
                    w2ui['att_toolbar'].show('break');
                    w2ui['att_toolbar'].hide('fdate');
                    w2alert('Attendance Imported');
                }else{
                    w2alert(jObject.message);
                    w2ui['att_toolbar'].show('get_att_log');
                    w2ui['att_toolbar'].show('break');
                    w2ui['att_toolbar'].show('fdate');
                    w2ui['att_grid'].unlock();
                }
            },
            error: function (){
                w2alert("Sorry, Error connecting to Biometric!");
                w2ui['att_toolbar'].show('get_att_log');
                w2ui['att_toolbar'].show('break');
                w2ui['att_grid'].unlock();
            }
        });
    }
}

function confirm_att(){
    w2ui['att_toolbar'].hide('confirm_log');
    w2ui['att_toolbar'].hide('break');
    w2ui['att_grid'].lock('Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "confirm-bio"
        },
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success") {
                w2ui['att_grid'].clear();
                w2ui['att_grid'].refresh();
                w2ui['att_grid'].add(jObject.records);
                w2ui['att_grid'].unlock();
                w2ui['att_toolbar'].show('get_att_log');
                w2ui['att_toolbar'].show('break');
                w2ui['att_toolbar'].show('fdate');
                w2alert('Imported Attendance Confirmed!');
            }else{
                w2alert(jObject.message);
                w2ui['att_toolbar'].show('get_att_log');
                w2ui['att_toolbar'].show('break');
                w2ui['att_toolbar'].show('fdate');
                w2ui['att_grid'].unlock();
            }
        },
        error: function (){
            w2alert("Sorry, There was a problem in server connection!");
            w2ui['att_toolbar'].show('get_att_log');
            w2ui['att_toolbar'].show('break');
            w2ui['att_grid'].unlock();
        }
    });
}

function get_default_log(){
    w2ui['att_toolbar'].hide('break');
    w2ui['att_toolbar'].hide('get_att_log');
    w2ui['att_grid'].lock('Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "get-default"
        },
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success" && jObject.records.length === 0) {
                w2ui['att_grid'].clear();
                w2ui['att_grid'].refresh();
                w2ui['att_grid'].add(jObject.records);
                w2ui['att_grid'].unlock();
                w2ui['att_toolbar'].show('get_att_log');
                w2ui['att_toolbar'].hide('confirm_log');
                w2ui['att_toolbar'].show('break');
                $(":input#fdate").w2field("date");
            }else{
                w2ui['att_grid'].clear();
                w2ui['att_grid'].refresh();
                w2ui['att_grid'].add(jObject.records);
                w2ui['att_grid'].unlock();
                w2ui['att_toolbar'].hide('get_att_log');
                w2ui['att_toolbar'].show('confirm_log');
                w2ui['att_toolbar'].show('break');
                w2ui['att_toolbar'].hide('fdate');
            }
        },
        error: function (){
            w2alert("Sorry, There was a problem in server connection!");
            w2ui['att_grid'].unlock();
            w2ui['att_toolbar'].show('break');
        }
    });
}
</script>