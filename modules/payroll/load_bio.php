<?php
$program_code = 12;
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
<div class="w3-responsive w3-mobile" id="att_div" style="padding-left: 5px; padding-right: 5px;">
    <div id="my_toolbar" style="padding: 3px;"></div>
    <div id="my_grid" style="width: 100%;"></div>
</div>
<script type="text/javascript">

    $(document).ready(function(){
        var c = $("div#my_grid");
        var h = window.innerHeight - 150;
        c.css("height", h);
        setTimeout(function(){
            get_default_log();
        }, 100);
    });


    $(function () {    
        $('#my_toolbar').w2toolbar({
            name: 'my_toolbar',
            items: [
                { type: 'html', id: 'fdate', html: '<div style="padding: 3px 10px;">' +
                            ' FROM:' +
                            '    <input id="fdate" size="15" style="padding: 3px; border-radius: 2px; border: 1px solid silver" autocomplete="off"/>' +
                            '</div>', hidden: true
                },
                { type: 'button', id: 'get_att_log', caption: 'Import', icon: 'fa fa-download', hidden: true },
                { type: 'button', id: 'confirm_log', caption: 'Confirm', icon: 'fa fa-check', hidden: true },
                { type: 'break', id: 'break', hidden: true },
                { type: 'spacer' },
                { type: 'break' },
                { type: 'button', id: 'load_att', caption: 'Load Attendance' },
                { type: 'break' },
                { type: 'button', id: 'new_att', caption: 'New Attendance' },
                { type: 'break' },
                { type: 'button', id: 'del_att', caption: 'Delete Attendance' },
                { type: 'break' },
                { type: 'button', id: 'del_all', caption: 'Delete All Attendance' }
            ],
            onClick: function (event) {
                switch (event.target){
                    case 'new_att':
                        new_attendance();
                    break;
                    case 'get_att_log':
                        get_att();
                    break;
                    case 'del_att':
                        if(w2ui.my_grid.getSelection().length > 0){
                            w2confirm('Delete this Attendance', function (btn){
                                if(btn === 'No'){
                                    w2ui.my_grid.refresh();
                                }else{
                                    delete_this_attendance(w2ui.my_grid.getSelection()[0]);
                                }
                            });
                        }
                    break;
                    case 'del_all':
                        w2confirm('Delete all this Attendance', function (btn){
                            if(btn === 'No'){
                                w2ui.my_grid.refresh();
                            }else{
                                delete_all_attendance();
                            }
                        });
                    break;
                    case 'confirm_log':
                        w2confirm('Confirm Attendance', function (btn){
                            if(btn === 'No'){
                                w2ui.my_grid.refresh();
                            }else{
                                confirm_att();
                            }
                        });
                    break;
                    case "load_att":
                        $().w2popup('open',{
                            showMax: false,
                            showClose: true,
                            body: '<div id="form" style="width: 100%; height: 150px; margin-top: 50px;"></div>',
                            width: 500,
                            height: 250,
                            title: "Upload Attendance in CSV",
                            onOpen: function (event) {
                                event.onComplete = function () {
                                    $("div#form").load("./modules/payroll/page/load_att.php");
                                };
                            }
                        });
                    break;
                }
            }
        });

        $('#my_grid').w2grid({ 
            name: 'my_grid', 
            show: { 
                toolbar: false,
                footer: true,
                lineNumbers: true
            },
            multiSearch: false,
            columns: [],
            records: []
        });
    });


    function get_emp_list_and_reason(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-emp-list-and-reason"
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        $('input#emp_list').w2field('list', { items: _return.emp_list });
                        $('input#att_reason').w2field('list', { items: _return.log_type });
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

    function upload_att(){
        var data = $("#att_file").data("selected");
        $("button#upload_att").addClass("w3-disabled");
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "upload_att_file",
                file: data[0].content
            },
            dataType: "json",
            success: function (jObject){
                $("button#upload_att").removeClass("w3-disabled");
                if (jObject.status === "success"){
                    get_default_log();
                    w2popup.close();
                }else{
                    w2alert(jObject.message);
                }
                w2utils.unlock(div);
            },
            error: function () {
                w2alert("Sorry, there was a problem connecting to the server.");
                w2popup.close();
                w2utils.unlock(div);
            }
        });
    }

    function delete_all_attendance(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "delete-all"
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
                w2alert("Sorry, there was a problem connecting to the server.");
                w2utils.unlock(div);
            }
        });
    }


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
                w2alert("Sorry, there was a problem connecting to the server.");
                w2utils.unlock(div);
            }
        });
    }

    function new_attendance () {
        if (!w2ui.form) {
            $().w2form({
                name: 'form',
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
                    { field: 'emp_list', type: 'list', required: true },
                    { field: 'att_reason', type: 'list', required: true },
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
                $(w2ui.form.box).hide();
                event.onComplete = function () {
                    $(w2ui.form.box).show();
                    w2ui.form.resize();
                }
            },
            onOpen: function (event) {
                event.onComplete = function () {
                    // specifying an onOpen handler instead is equivalent to specifying an onBeforeOpen handler, which would make this code execute too early and hence not deliver.
                    $('#w2ui-popup #form').w2render('form');
                    get_emp_list_and_reason();
                }
            }
        });
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
                    record: w2ui.form.record
                },
                dataType: "json",
                success: function (jObject){
                    if (jObject.status === "success"){
                        w2popup.close();
                        w2ui.form.clear();
                        get_default_log();
                        w2utils.unlock(div);
                    }else{
                        w2alert(jObject.message);
                        w2utils.unlock(div);
                    }
                },
                error: function () {
                    w2alert("Sorry, there was a problem connecting to the server.");
                    w2utils.unlock(div);
                }
            });
        }else{
            w2alert("Please supply all required fields!");
        }
    }

    function get_att(){
        let fdate = $(":input#fdate").val();
        if(fdate == ""){
            w2alert("Please select date from!");
        }else{
            var div = $('#main');
            w2utils.lock(div, 'Please wait..', true);
            $.ajax({
                url: src,
                type: "post",
                data: {
                    cmd: "get-wall-bio",
                    fdate : fdate
                },
                dataType: "json",
                success: function(jObject){
                    w2utils.unlock(div);
                    if (jObject.status === "success") {
                        get_default_log();
                    }else{
                        w2alert(jObject.message);
                    }
                },
                error: function (){
                    w2alert("Sorry, Error connecting to Biometric!");
                    w2utils.unlock(div);
                }
            });
        }
    }

    function confirm_att(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "confirm-bio"
            },
            dataType: "json",
            success: function(jObject){
                if (jObject.status === "success") {
                    get_default_log();
                }else{
                    w2alert(jObject.message);
                }
            },
            error: function (){
                w2alert("Sorry, There was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }

    function get_default_log(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-default"
            },
            dataType: "json",
            success: function(jObject){
                if (jObject.status === "success") {
                    w2ui.my_grid.clear();
                    w2ui.my_grid.refresh();
                    w2ui.my_grid.columns = jObject.columns;
                    w2ui.my_grid.add(jObject.records);
                    if(jObject.records.length === 0){
                        w2ui.my_toolbar.show('fdate');
                        w2ui.my_toolbar.show('get_att_log');
                        w2ui.my_toolbar.hide('confirm_log');
                        w2ui.my_toolbar.show('break');
                    }else{
                        w2ui.my_toolbar.hide('get_att_log');
                        w2ui.my_toolbar.show('confirm_log');
                        w2ui.my_toolbar.show('break');
                        w2ui.my_toolbar.hide('fdate');
                    }
                    setTimeout(function (){
                        $(":input#fdate").w2field("date");
                    }, 50);
                    w2utils.unlock(div);
                }else{
                    w2alert("Sorry, There was a problem in server connection!");
                    $(":input#fdate").w2field("date");
                    w2utils.unlock(div);
                }
            },
            error: function (){
                w2alert("Sorry, There was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    }
</script>