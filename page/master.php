<?php
$program_code = 1;
require_once('../common/functions.php');
if (!isset($_SESSION["emp_no"])) {
    $_SESSION["emp_no"] = '';
}
if (!isset($_SESSION["filter"])) {
    $_SESSION["filter"] = 'non_del';
}
unset($_SESSION["wksfr"]);
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
    <div id="master_toolbar" style="padding: 3px;"></div>
    <div id="master_grid" style="width: 100%; height: 450px;"></div>
</div>

<script type="text/javascript">
    var filter = '<?php echo $_SESSION["filter"]; ?>';
    let emp_no = '<?php echo $_SESSION["emp_no"]; ?>';

    var c = $("div#master_grid");
    var h = window.innerHeight - 185;
    c.css("height", h);
    const home = "home";

$(function () {    
    $('#master_grid').w2grid({ 
        name: 'master_grid', 
        show: { 
            toolbar: true,
            footer: true,
            lineNumbers: true
        },
        multiSearch: false,
        searches: [
            {field: 'lname', caption: 'Last Name', type: 'text'},
            {field: 'fname', caption: 'First Name', type: 'text'},
            {field: 'pin', caption: 'Employee No', type: 'int'},
            {field: 'status', caption: 'Status', type: 'text'},
            {field: 'grp', caption: 'Store', type: 'text'}
        ],
        columns: [
            {field: 'recid', caption: 'No', size: '100px', hidden: true},
            {field: 'pin', caption: 'EMPLOYEE NO', size: '100px'},
            {field: 'lname', caption: 'LAST NAME', size: '200px'},
            {field: 'fname', caption: 'FIRST NAME', size: '200px'},
            {field: 'mname', caption: 'M.I.', size: '50px', attr: "align=center"},
            {field: 'pos', caption: 'POSITION', size: '200px'},
            {field: 'grp', caption: 'STORE', size: '50%'},
            {field: 'status', caption: 'Employment Status', size: '50%'}
        ],
        records: [],
        toolbar: {
            items: [
                { type: 'button',  id: 'add',  caption: 'Add New', icon: 'fa-solid fa-circle-plus' },
                { type: 'button',  id: 'del',  caption: 'Delete', icon: 'fa-solid fa-circle-minus', disabled: true},
                { type: 'button',  id: 'recall',  caption: 'Recall', icon: 'fa-solid fa-undo', hidden: true}
            ],
            onClick: function (event) {
                var sel_rec_ids = w2ui.master_grid.getSelection();
                switch (event.target){
                    case "add":
                        add_new();
                        break;
                    case "del":
                        if(w2ui['master_grid'].getSelection().length > 0){
                            w2confirm('Delete this Employee?', function (btn){
                                if(btn === 'No'){
                                    w2ui['master_grid'].refresh();
                                }else{
                                    delete_emp(w2ui['master_grid'].getSelection()[0]);
                                }
                            })
                        }
                    break;
                    case "recall":
                        if(w2ui['master_grid'].getSelection().length > 0){
                            w2confirm('Recall this Employee?', function (btn){
                                if(btn === 'No'){
                                    w2ui['master_grid'].refresh();
                                }else{
                                    recall_emp(w2ui['master_grid'].getSelection()[0]);
                                }
                            })
                        }
                    break;
                }
            }
        }
    });
});

$(document).ready(function(){
    get_master_data(filter);
});

w2ui['master_grid'].on('unselect', function(event) {
    w2ui['master_grid'].toolbar.disable('del');
});

w2ui['master_grid'].on('select', function(event) {
    w2ui['master_grid'].toolbar.enable('del');
});

w2ui['master_grid'].on('unselect', function(event) {
    w2ui['master_grid'].toolbar.disable('recall');
});

w2ui['master_grid'].on('select', function(event) {
    w2ui['master_grid'].toolbar.enable('recall');
});



function get_master_data(filter){
    w2ui['master_grid'].toolbar.disable('del');
    w2ui['master_grid'].clear();
    w2ui['master_grid'].refresh();
    w2ui['master_grid'].lock('Refreshing..', true);
    $.ajax({
        url: "page/master1",
        method:"POST",
        data:{
            cmd: "get-master-data",
            filter : filter
        },
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success") {
                w2ui['master_grid'].clear();
                w2ui['master_grid'].refresh();
                w2ui['master_grid'].add(jObject.records);
                w2ui['master_grid'].unlock();
                w2ui['master_grid'].select(emp_no);
                if(filter == "all"){
                    w2ui['master_grid'].toolbar.show('recall');
                    w2ui['master_grid'].toolbar.disable('recall');
                    w2ui['master_toolbar'].check('all');
                    w2ui['master_grid'].toolbar.disable('del');
                }else{
                    w2ui['master_grid'].toolbar.hide('recall');
                    w2ui['master_toolbar'].check('non_del');
                    w2ui['master_grid'].toolbar.disable('del');
                }
            }else{
                w2alert(jObject.message);
            }
        }
    });
}

$(function () {
    $('#master_toolbar').w2toolbar({
        name: 'master_toolbar',
        items: [
            { type: 'radio', id: 'all', group: '1', text: 'All', icon: 'fa-solid fa-eye' },
            { type: 'radio', id: 'non_del', group: '1', text: 'None-Delete', icon: 'fa-solid fa-bookmark'},
            { type: 'spacer' },
            { type: 'break' },
            { type: 'button',  id: 'shift',  caption: 'EMPLOYEE SHIFT SCHEDULE', icon: 'fa-solid fa-server'},
            { type: 'break' },
            { type: 'button',  id: 'data',  caption: 'DATA', icon: 'fa-solid fa-file-shield'},
            { type: 'break' },
            { type: 'button',  id: 'vl',  caption: 'VACATION LEAVE', icon: 'fa fa-tags'},
            { type: 'break' },
            { type: 'button',  id: 'rate',  caption: 'RATE & EMPLOYMENT STATUS', icon: 'fa-solid fa-person-walking-luggage'},
            { type: 'break' },
            { type: 'button',  id: 'changes',  caption: 'CHANGES', icon: 'fa-solid fa-chart-simple'}
        ],
        onClick: function (event) {
            switch (event.target){
                case 'data':
                    if(w2ui['master_grid'].getSelection().length > 0){
                        var emp_no = w2ui['master_grid'].getSelection()[0]
                        user_data_form(emp_no);
                    }
                break;
                case 'rate':
                    if(w2ui['master_grid'].getSelection().length > 0){
                        view_rate(w2ui['master_grid'].getSelection()[0]);
                    }
                break;
                case 'changes':
                    if(w2ui['master_grid'].getSelection().length > 0){
                        view_changes(w2ui['master_grid'].getSelection()[0]);
                    }
                break;
                case "all":
                case "non_del":
                    get_master_data(event.target);
                break;
                case 'shift':
                    if(w2ui['master_grid'].getSelection().length > 0){
                        let emp_no = w2ui['master_grid'].getSelection()[0]
                        shift_schedule(emp_no);
                    }
                break;
                case 'vl':
                    if(w2ui['master_grid'].getSelection().length > 0){
                        let emp_no = w2ui['master_grid'].getSelection()[0]
                        vacation_leave(emp_no);
                    }
                break;
            }
        }
    });
});

function add_new(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    destroy_grid();
    closeMenu();
    $.ajax({
        url: home,
        success: function(data){
            $('#grid').load('modules/master.php?emp_no=0&cmd=add');
            $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;New Employee</span>');
            w2utils.unlock(div);
        }
    })
}

function delete_emp(pin){
    $.ajax({
        url: 'page/master1',
        type: 'post',
        data: { cmd : "del-emp", pin:pin},
        success: function(data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    get_master_data(filter);
                }else{
                    w2alert(_return.message);
                }
            }
        }
    })
}

function recall_emp(emp_no){
    $.ajax({
        url: 'page/master1',
        type: 'post',
        data: { cmd : "recall-emp", emp_no:emp_no},
        success: function(data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    get_master_data(filter);
                }else{
                    w2alert(_return.message);
                }
            }
        }
    });
}

function shift_schedule(emp_no){
    destroy_grid();
    $.ajax({
        url: home,
        success: function(data){
            $('#grid').load('modules/master_schedule.php?emp_no='+ emp_no);
            $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Employee Shift Schedule</span>');
        }
    })
}

function vacation_leave(emp_no){
    var level = '<?php echo $level = $cfn->get_user_level(); ?>';
    if(level >= 10){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        destroy_grid();
        closeMenu();
        $.ajax({
            url: home,
            success: function(data){
                $('#grid').load('modules/employee_vl.php?emp_no='+emp_no);
                $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Vacation Leave</span>');
                w2utils.unlock(div);
            }
        });
    }else{
        w2alert('This module is disabled by administrator! Contact the admin for assistance.');
    }
}

function user_data_form(emp_no){
    destroy_grid();
    $.ajax({
        url: home,
        success: function(data){
            $('#grid').load('modules/master.php?emp_no='+ emp_no +"&cmd=edit");
            $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Employee Info</span>');
        }
    })
}

function view_changes(recid){
    destroy_grid();
    $.ajax({
        url: home,
        success: function(data){
            $('#grid').load('modules/emp_journal.php?pin_no='+ recid);
            $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Employee Changes</span>');
        }
    })
}

function view_rate(recid){
    destroy_grid();
    $.ajax({
        url: home,
        success: function(data){
            $('#grid').load("modules/employee_rate_maint.php?emp_no="+ recid+"&cmd=edit");
            $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Employee Rate & Position</span>');
        }
    })  
}


</script>