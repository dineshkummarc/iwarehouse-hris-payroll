<?php
error_reporting(0);
$program_code = 36;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
if (substr($access_rights, 6, 2) !== "B+") {
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
?>
<div class="w3-responsive w3-mobile w3-margin-top">
	<div id="grid" class="w3-round-large" style="width: 100%;"></div>
</div>
<script type="text/javascript">

$(function () {
    var c = $("div#grid");
    var h = window.innerHeight - 100;
    c.css("height", h);
    $('#grid').w2grid({ 
        name: 'backup_grid',
        header: 'Back Up Maintenance',
        show: { 
            toolbar: true,
            footer: true,
            lineNumbers: true,
            header: false,
        },
        multiSearch: false,
        columns: [                
            { field: 'recid', caption: 'Back Up ID', size: '100px'},
            { field: 'desc', caption: 'Back Up Description', size: '500px'},
            { field: 'date', caption: 'Date', size: '200px', sortable: false },
            { field: 'size', caption: 'Back Up Size', size: '150px', sortable: false, attr: "align=right" },
            { field: 'storage', caption: 'Location', size: '500px', sortable: false }
        ],
        toolbar: {
            items: [
                { type: 'spacer' },
                { type: 'button',  id: 'make',  caption: 'Make Back Up'},
                { type: 'break' },
                { type: 'button',  id: 'down',  caption: 'Download'},
                { type: 'break' },
                { type: 'button',  id: 'del',  caption: 'Delete'}
            ],
            onClick: function (event) {
            	switch (event.target){
                    case 'make':
                        make_backup();
                    break;
                    case 'down':
                        if(w2ui['backup_grid'].getSelection().length > 0){
                            var sel_rec = w2ui['backup_grid'].getSelection();
                            var record = w2ui['backup_grid'].get(sel_rec[0]);
                            var desc = record.desc;
                            download(desc);
                        }
                    break;
                    case 'del':
                        if(w2ui['backup_grid'].getSelection().length > 0){
                            w2confirm('Delete this backup?', function (btn){
                                if(btn === 'No'){
                                    w2ui.backup_grid.refresh();
                                }else{
                                    delete_backup(w2ui['backup_grid'].getSelection()[0]);
                                }
                            });
                        }
                    break;
            	}
            }
        }
    });
});

$(document).ready(function () {
    $("#tb_backup_grid_toolbar_item_w2ui-reload, #tb_backup_grid_toolbar_item_w2ui-column-on-off, #tb_backup_grid_toolbar_item_w2ui-break0, #tb_backup_grid_toolbar_item_w2ui-search").hide();
    get_backup_data();
});

function download(desc){
    let url = './backup/'+desc;
    downloadFile(url, desc);
}

function downloadFile(url, desc) {
    fetch(url, { method: 'get', mode: 'no-cors', referrerPolicy: 'no-referrer' })
        .then(res => res.blob())
        .then(res => {
            const aElement = document.createElement('a');
            aElement.setAttribute('download', desc);
            const href = URL.createObjectURL(res);
            aElement.href = href;
            aElement.setAttribute('target', '_blank');
            aElement.click();
            URL.revokeObjectURL(href);
        });
};

function delete_backup(recid){
    w2ui['backup_grid'].lock('Please wait...', true);
    $.ajax({
        url: "page/system.program.php",
        type: "post",
        data: {
            cmd : "del-backup-data",
            recid : recid
        },
        success: function (data) {
            if (data !== "") {
                var _response = jQuery.parseJSON(data);
                if (_response.status === "success") {
                    w2alert(_response.message);
                    get_backup_data();
                } else if (_response.status === "error") {
                    w2ui['backup_grid'].unlock();
                    w2alert(_response.message);
                }
            }
        },
        error: function () {
            w2ui['backup_grid'].unlock();
            alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
        }
    });
}

function get_backup_data() {
    w2ui['backup_grid'].lock('Please wait...', true);
    $.ajax({
        url: "page/system.program.php",
        type: "post",
        data: {
            cmd: "get-backup-data"
        },
        success: function (data) {
            if (data !== "") {
                var _response = jQuery.parseJSON(data);
                if (_response.status === "success") {
                    w2ui['backup_grid'].clear();
                    w2ui['backup_grid'].add(_response.records);
                    w2ui['backup_grid'].unlock();
                } else {
                    w2ui['backup_grid'].unlock();
                    w2alert(_response.message);
                }
            }
        },
        error: function () {
            w2ui['backup_grid'].unlock();
            alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
        }
    });
}

function make_backup(){
    w2ui['backup_grid'].lock('Please wait...', true);
    $.ajax({
        url: "page/system.program.php",
        type: "post",
        data: {
            cmd: "make-backup"
        },
        success: function (data) {
            if (data !== "") {
                var _response = jQuery.parseJSON(data);
                if (_response.status === "success") {
                    w2alert(_response.message);
                    get_backup_data();
                } else if (_response.status === "error") {
                    w2ui['backup_grid'].unlock();
                    w2alert(_response.message);
                }
            }
        },
        error: function () {
            w2ui['backup_grid'].unlock();
            alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
        }
    });
}
</script>