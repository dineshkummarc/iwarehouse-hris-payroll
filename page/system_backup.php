<?php
error_reporting(0);
$program_code = 36;
require_once('../system.config.php');
require_once('../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
if (substr($access_rights, 6, 2) !== "B+") {
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
?>
<div class="w3-responsive w3-mobile w3-padding-top" style="margin-left: 4px; margin-right: 4px;">
	<div id="my_grid" style="width: 100%;"></div>
</div>
<script type="text/javascript">

$(function () {
    $('#my_grid').w2grid({ 
        name: 'my_grid',
        header: 'Back Up Maintenance',
        show: { 
            toolbar: true,
            footer: true,
            lineNumbers: true,
            header: false,
            toolbarReload: false,
            toolbarSearch: false,
            toolbarInput: false,
            toolbarColumns: false,
        },
        multiSearch: false,
        columns: [                
            { field: 'recid', caption: 'Back Up ID', size: '100px'},
            { field: 'desc', caption: 'Back Up Description', size: '500px'},
            { field: 'date', caption: 'Date', size: '150px', sortable: false },
            { field: 'size', caption: 'Back Up Size', size: '120px', sortable: false, attr: "align=right" },
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
                        if(w2ui.my_grid.getSelection().length > 0){
                            var sel_rec = w2ui.my_grid.getSelection();
                            var record = w2ui.my_grid.get(sel_rec[0]);
                            var desc = record.desc;
                            download(desc);
                        }
                    break;
                    case 'del':
                        if(w2ui.my_grid.getSelection().length > 0){
                            w2confirm('Delete this backup?', function (btn){
                                if(btn === 'No'){
                                    w2ui.backup_grid.refresh();
                                }else{
                                    delete_backup(w2ui.my_grid.getSelection()[0]);
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
    var c = $("div#my_grid");
    var h = window.innerHeight - 150;
    c.css("height", h);
    get_backup_data();
});

function download(desc){
    let url = './backup/'+desc;
    downloadFile(url, desc);
}

function downloadFile(url, desc) {
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
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
            w2utils.unlock(div);
        });
};

function delete_backup(recid){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd : "del-backup-data",
            recid : recid
        },
        success: function (data) {
            if (data !== "") {
                var _response = jQuery.parseJSON(data);
                if (_response.status === "success") {
                    w2utils.unlock(div);
                    get_backup_data();
                } else if (_response.status === "error") {
                    w2utils.unlock(div);
                    w2alert(_response.message);
                }
            }
        },
        error: function () {
            w2utils.unlock(div);
            w2alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
        }
    });
}

function get_backup_data() {
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "get-backup-data"
        },
        success: function (data) {
            if (data !== "") {
                var _response = jQuery.parseJSON(data);
                if (_response.status === "success") {
                    w2ui.my_grid.clear();
                    w2ui.my_grid.add(_response.records);
                    w2utils.unlock(div);
                } else {
                    w2utils.unlock(div);
                    w2alert(_response.message);
                }
            }
        },
        error: function () {
            w2utils.unlock(div);
            w2alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
        }
    });
}

function make_backup(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "make-backup"
        },
        success: function (data) {
            if (data !== "") {
                var _response = jQuery.parseJSON(data);
                if (_response.status === "success") {
                    w2alert(_response.message);
                    w2utils.unlock(div);
                    get_backup_data();
                } else if (_response.status === "error") {
                    w2utils.unlock(div);
                    w2alert(_response.message);
                }
            }
        },
        error: function () {
            w2utils.unlock(div);
            w2alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call EDP for assistance.");
        }
    });
}
</script>