<?php
error_reporting(0);
$program_code = 7;
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
<div class="w3-responsive w3-mobile w3-margin-top">
	<div id="grid" class="w3-round-large" style="width: 100%;"></div>
</div>
<script type="text/javascript">

    var levels = ['0','1','2','3','8','9','10'];
    var parent;

$(function () {
    var c = $("div#grid");
    var h = window.innerHeight - 100;
    c.css("height", h);
    $('#grid').w2grid({ 
        name: 'prog_maint',
        header: 'Program Maintenance',
        show: { 
            toolbar: true,
            footer: true,
            lineNumbers: true,
            header: false,
        },
        multiSearch: false,
        searches: [
            { field: 'menu', caption: 'Menu Name', type: 'text' }

        ],
        columns: [                
            { field: 'recid', caption: 'Program Code', size: '100px', hidden: true},
            { field: 'parent', caption: 'Program Parent', size: '150px'},
            { field: 'menu', caption: 'Menu Name', size: '200px', sortable: false },
            { field: 'prog', caption: 'Program ID', size: '150px', sortable: false },
            { field: 'active', caption: 'Active', size: '50px', sortable: false, attr: 'align=center' },
            { field: 'level', caption: 'Program Level', size: '100px', sortable: false,attr: 'align=center' },
            { field: 'icon', caption: 'Program Icon', size: '150px', sortable: false, attr: 'align=center' },
            { field: 'is_admin', caption: 'Admin Module', size: '100px', sortable: false, attr: 'align=center' },
            { field: 'uid', caption: 'Grant By', size: '100px', sortable: false, attr: 'align=center'},
            { field: 'function', caption: 'Program Function', size: '300px', sortable: false },
            { field: 'seq', caption: 'Sequence', size: '80px', sortable: false },
            { field: '_timestamp', caption: 'TimeStamp', size: '150px', sortable: false, attr: 'align=left' },
            { field: 'station', caption: 'Station', size: '100px', sortable: false, attr: 'align=left' }
        ],
        toolbar: {
            items: [
                { type: 'break' },
                { type: 'button',  id: 'new_prog',  caption: 'New'},
                { type: 'break' },
                { type: 'button',  id: 'edit',  caption: 'Edit'},
                { type: 'break' },
                { type: 'spacer' },
                { type: 'break' },
                { type: 'button',  id: 'active',  caption: 'Enable/Disable'},
                { type: 'break' },
                { type: 'button',  id: 'del',  caption: 'Delete'}
            ],
            onClick: function (event) {
            	switch (event.target){
                    case 'new_prog':
                        new_prog();
                    break;
                    case 'active':
                        if(w2ui['prog_maint'].getSelection().length > 0){
                            set_active(w2ui['prog_maint'].getSelection()[0]);
                        }
                    break;
                    case 'del':
                        if(w2ui['prog_maint'].getSelection().length > 0){
                            w2confirm('Delete this Module?', function (btn){
                                if(btn === 'No'){
                                    w2ui.prog_maint.refresh();
                                }else{
                                    rm_prog(w2ui['prog_maint'].getSelection()[0]);
                                }
                            });
                        }
                    break;
            	}
            }
        }
    });
    w2ui['prog_maint'].load('modules/program_list');
});


function new_prog() {
    if (!w2ui.form) {
        $().w2form({
            name: 'form',
            style: 'border: 0px; background-color: transparent;',
            formURL: "page/prog_form",
            fields: [
                { field: 'menu_name', type: 'text', required: true },
                { field: 'prog_name', type: 'text', required: true },
                { field: 'enable', type: 'checkbox', required: true },
                { field: 'plevel', type: 'list', required: true, options: {items: levels} },
                { field: 'parent', type: 'list', required: true, options: {items: parent} },
                { field: 'icons', type: 'text' },
                { field: 'admin_mod', type: 'checkbox'},
                { field: 'functions', type: 'text', required: true },
                { field: 'seq', type: 'int', required: true }
            ],
            actions: {
                "save" : function () {
                    save_prog();
                },
                "reset" : function () { this.clear(); }
            }
        });
    }
    $().w2popup('open', {
        title   : 'Program Form',
        body    : '<div id="form" style="width: 100%; height: 100%;"></div>',
        style   : 'padding: 15px 0px 0px 0px',
        width   : 700,
        height  : 450, 
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
                get_parent();
            }
        }
    });
}

function save_prog(){
    w2popup.close();
    w2ui['prog_maint'].lock("Please wait...", true);
    var menu_name = $(":input#menu_name").val();
    var prog_name = $(":input#prog_name").val();
    var prog_parent = $(":input#parent").val();
    var plevel = $(":input#plevel").val();
    var icons = $(":input#icons").val();
    if (menu_name !== "" && prog_name !== "" && plevel !== "" && icons !== ""){
        $.ajax({
            url: "page/system.program",
            type: "post",
            data: {
                cmd: "save-config",
                record: w2ui.form.record
            },
            dataType: "json",
            success: function (jObject){
                if (jObject.status === "success"){
                    w2ui.form.clear();
                    w2ui['prog_maint'].load('modules/program_list.php');
                    w2ui['prog_maint'].unlock();
                }else{
                    w2alert(jObject.message);
                    w2ui['prog_maint'].unlock();
                }
            },
            error: function () {
                w2alert("Sorry, There was a problem in server connection or Session Expired!");
                w2ui['prog_maint'].unlock();
            }
        });
    }else{
        w2alert("Please supply all required fields!");
    }
}

function rm_prog(recid){
    w2ui['prog_maint'].lock("Please wait...", true);
    $.ajax({
        url: "page/system.program",
        type: "post",
        data: {
            cmd: "del-prog",
            recid: recid
        },
        success: function (data) {
            w2ui['prog_maint'].unlock();
            w2ui['prog_maint'].load('modules/program_list.php');
        },
        error: function () {
            w2ui['prog_maint'].unlock();
            w2alert("Sorry, There was a problem in server connection or Session Expired!");
        }
    });
}

function set_active(recid){
    w2ui['prog_maint'].lock("Please wait...", true);
    $.ajax({
        url: "page/system.program",
        type: "post",
        data: {
            cmd: "enable-disable",
            recid : recid
        },
        dataType: "json",
        success: function (jObject){
            if (jObject.status === "success"){
                w2ui['prog_maint'].load('modules/program_list.php');
                w2ui['prog_maint'].unlock();
            }else{
                w2alert(jObject.message);
                w2ui['prog_maint'].unlock();
            }
        },
        error: function () {
            w2ui['prog_maint'].unlock();
            w2alert("Sorry, There was a problem in server connection or Session Expired!");
            w2ui['prog_maint'].load('modules/program_list.php');
        }
    })
}

function get_parent(){
    $.ajax({
        url: "page/system.program",
        type: "post",
        data: {
            cmd: "get-parent"
        },
        success: function (data){
            if (data !== ""){
                var _return = jQuery.parseJSON(data);
                if(_return.status === "success"){
                    parent = _return.parent;
                    $('input#parent').w2field('list', { items: parent });
                }else{
                    w2alert("Sorry, No DATA found!");
                }
            }
        },
        error: function (){
            w2alert("Sorry, there was a problem in server connection!");
        }
    });
}
</script>