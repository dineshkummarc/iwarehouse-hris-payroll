<?php
$program_code = 8;
require_once('../common/functions.php');
?>
<div class="w3-responsive w3-mobile w3-margin-top">
    <div id="user_maint" class="w3-round-large" style="width: 100%; height: 450px;"></div>
</div>
<script type="text/javascript">

    var levels = ['1','2','3','4','5','6','7','9'];

    const src = "page/new_user";

$(function () {
    var c = $("div#user_maint");
    var h = window.innerHeight - 185;
    c.css("height", h);
    $('#user_maint').w2grid({ 
        name: 'user_maint',
        header: 'User Maintenance',
        show: { 
            toolbar: true,
            footer: true,
            lineNumbers: true,
            header: false,
        },
        multiSearch: false,
        searches: [
            { field: 'acc_id', caption: 'Account ID', type: 'text' },
            { field: 'uname', caption: 'Username', type: 'text' }

        ],
        columns: [                
            { field: 'recid', caption: 'User No', size: '100px', hidden: true },
            { field: 'uname', caption: 'User Name', size: '7%', sortable: false },
            { field: 'name', caption: 'Registration Name', size: '30%', sortable: false },
            { field: 'acc_id', caption: 'Account ID', size: '10%', sortable: false, attr: 'align=center' },
            { field: 'last_log', caption: 'Last Login', size: '15%', sortable: false, attr: 'align=center' },
            { field: 'reg_date', caption: 'Registration Date', size: '15%', sortable: false,attr: 'align=center' },
            { field: 'actv', caption: 'Active', size: '100px', sortable: false, attr: 'align=center' },
            { field: 'lvl', caption: 'Level', size: '100px', sortable: false, attr: 'align=center' },
            { field: 'grant', caption: 'Grant By', size: '100px', sortable: false, attr: 'align=center'},
            { field: '_timestamp', caption: 'TimeStamp', size: '150px', sortable: false, attr: 'align=left' },
            { field: 'station', caption: 'Station', size: '100px', sortable: false, attr: 'align=left' }
        ],
        toolbar: {
            items: [
                { type: 'break' },
                { type: 'button',  id: 'new_user',  caption: 'Enroll'},
                { type: 'break' },
                { type: 'button',  id: 'edit',  caption: 'Edit Info'},
                { type: 'break' },
                { type: 'button',  id: 'reset',  caption: 'Reset Account'},
                { type: 'break' },
                { type: 'spacer' },
                { type: 'break' },
                { type: 'button',  id: 'ena_dis',  caption: 'Enable/Disable'}
            ],
            onClick: function (event) {
            	switch (event.target){
                    case 'new_user':
                        new_user_form();
                    break;
                    case 'ena_dis':
                        if(w2ui['user_maint'].getSelection().length > 0){
                            enable_dis(w2ui['user_maint'].getSelection()[0]);
                        }else{
                            w2alert('Please select user!');
                        }
                    break;
                    case 'edit':
                        if(w2ui['user_maint'].getSelection().length > 0){
                            edit_info(w2ui['user_maint'].getSelection()[0]);
                        }else{
                            w2alert('Please select user to edit info!');
                        }
                    break;
                    case 'del':
                        if(w2ui['user_maint'].getSelection().length > 0){
                            w2confirm('Delete this user?', function (btn){
                                if(btn === 'No'){
                                    w2ui.user_maint.refresh();
                                }else{
                                    rm_prog(w2ui['user_maint'].getSelection()[0]);
                                }
                            });
                        }
                    break;
                    case 'reset':
                        if(w2ui['user_maint'].getSelection().length > 0){
                            w2confirm('Reset Account?', function (btn){
                                if(btn === 'No'){
                                    w2ui.user_maint.refresh();
                                }else{
                                    reset_account(w2ui['user_maint'].getSelection()[0]);
                                }
                            });
                        }
                    break;
            	}
            }
        }
    });
    w2ui['user_maint'].load('page/users_list');
});


function enable_dis(recid){
    w2ui['user_maint'].lock("Please wait...", true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "enable-disable",
            recid : recid
        },
        dataType: "json",
        success: function (jObject){
            if (jObject.status === "success"){
                w2ui['user_maint'].load('page/users_list.php');
                w2ui['user_maint'].unlock();
            }else if(jObject.status === "hold"){
                w2alert("You cannot Enable/Disable this account!");
                w2ui['user_maint'].refresh();
                w2ui['user_maint'].unlock();
            }else{
                w2alert(jObject.message);
                w2ui['user_maint'].unlock();
            }
        },
        error: function () {
            w2ui['user_maint'].load('page/users_list.php');
        }
    })
}


function new_user_form(){
    destroy_grid();
    $.ajax({
        url: 'home',
        beforeSend: function(){
            closeMenu();
        },
        success: function(data){
            $('#user_maint').load('modules/new_user.php?id=0');
            $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;New User</span>');
        }
    })
}

function reset_account(recid){
    w2ui['user_maint'].lock("Please wait...", true);
    $.ajax({
        url: src,
        type: "post",
        data: {
            cmd: "reset-account",
            recid : recid
        },
        dataType: "json",
        success: function (jObject){
            if (jObject.status === "success"){
                w2ui['user_maint'].load('page/users_list.php');
                w2ui['user_maint'].unlock();
            }else{
                w2alert(jObject.message);
                w2ui['user_maint'].unlock();
            }
        },
        error: function () {
            w2ui['user_maint'].load('page/users_list.php');
        }
    })
}

function edit_info(recid){
    destroy_grid();
    $.ajax({
        url: 'home.php',
        beforeSend: function(){
            closeMenu();
        },
        success: function(data){
            $('#user_maint').load('modules/new_user.php?id='+recid);
            $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Edit User Info</span>');
        }
    })
}
</script>