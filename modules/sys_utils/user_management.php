<?php
$program_code = 6;
require_once('../../system.config.php');
require_once('../../common_functions.php');
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
if (substr($access_rights, 6, 2) !== "B+") {
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
?>
<div class="w3-responsive w3-mobile w3-margin-top" style="padding-left: 4px; padding-right: 5px;">
    <div id="my_grid" style="width: 100%;"></div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        var c = $("div#my_grid");
        var h = window.innerHeight - 185;
        c.css("height", h);
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "set-defaults"
            },
            dataType: "json",
            success: function(data) {
                if (data !== "") {
                    if (data.status === "success") {
                        w2ui.my_grid.columns = data.columns;
                        w2ui.my_grid.refresh();
                        get_records();
                        w2utils.unlock(div);
                    } else {
                        w2alert(data.message);
                        w2utils.unlock(div);
                    }
                } else {
                    w2utils.unlock(div);
                    w2alert("Sorry, there was a problem in server connection!");
                }
            },
            error: function() {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    });

    $(function() {
        $('#my_grid').w2grid({
            name: 'my_grid',
            header: 'User Maintenance',
            show: {
                toolbar: true,
                footer: true,
                lineNumbers: true,
                header: false,
                toolbarColumns: false,
            },
            columns: [],
            toolbar: {
                items: [
                    { type: 'break' },
                    { type: 'button', id: 'new_user', caption: 'Enroll' },
                    { type: 'break' },
                    { type: 'button', id: 'edit', caption: 'Edit Info' },
                    { type: 'break' },
                    { type: 'button', id: 'reset',  caption: 'Reset Account' },
                    { type: 'break' },
                    { type: 'spacer' },
                    { type: 'break' },
                    { type: 'button', id: 'ena_dis', caption: 'Enable/Disable' }
                ],
                onClick: function(event) {
                    switch (event.target) {
                        case 'new_user':
                            new_user_form();
                            break;
                        case 'ena_dis':
                            if (w2ui.my_grid.getSelection().length > 0) {
                                enable_dis(w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert('Please select user!');
                            }
                            break;
                        case 'edit':
                            if (w2ui.my_grid.getSelection().length > 0) {
                                edit_info(w2ui.my_grid.getSelection()[0]);
                            } else {
                                w2alert('Please select user to edit info!');
                            }
                            break;
                        case 'del':
                            if (w2ui.my_grid.getSelection().length > 0) {
                                w2confirm('Delete this user?', function(btn) {
                                    if (btn === 'No') {
                                        w2ui.user_maint.refresh();
                                    } else {
                                        rm_prog(w2ui.my_grid.getSelection()[0]);
                                    }
                                });
                            }
                            break;
                        case 'reset':
                            if (w2ui.my_grid.getSelection().length > 0) {
                                w2confirm('Reset Account?', function(btn) {
                                    if (btn === 'No') {
                                        w2ui.user_maint.refresh();
                                    } else {
                                        reset_account(w2ui.my_grid.getSelection()[0]);
                                    }
                                });
                            }
                            break;
                    }
                }
            }
        });
    });

    function get_records() {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-records"
            },
            dataType: "json",
            success: function(data) {
                if (data !== "") {
                    if (data.status === "success") {
                        w2ui.my_grid.clear();
                        w2ui.my_grid.add(data.records);
                        w2utils.unlock(div);
                    } else {
                        w2alert(data.message);
                        w2utils.unlock(div);
                    }
                } else {
                    w2utils.unlock(div);
                    w2alert("Sorry, there was a problem in server connection!");
                }
            },
            error: function() {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }

    function enable_dis(recid) {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "enable-disable",
                recid: recid
            },
            dataType: "json",
            success: function(jObject) {
                if (jObject.status === "success") {
                    get_records();
                    w2utils.unlock(div);
                } else if (jObject.status === "hold") {
                    w2alert("You cannot Enable/Disable this account!");
                    w2ui.my_grid.refresh();
                    w2utils.unlock(div);
                } else {
                    w2alert(jObject.message);
                    w2utils.unlock(div);
                }
            },
            error: function() {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!");
            }
        })
    }


    function new_user_form() {
        $('#grid').load('./modules/sys_utils/page/user_form.php?id=0');
        $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;New User</span>');
    }

    function reset_account(recid) {
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "reset-account",
                recid: recid
            },
            dataType: "json",
            success: function(jObject) {
                if (jObject.status === "success") {
                    get_records();
                    w2ui.my_grid.unlock();
                } else {
                    w2alert(jObject.message);
                    w2ui.my_grid.unlock();
                }
            },
            error: function() {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!");
            }
        })
    }

    function edit_info(recid) {
        $('#grid').load('./modules/sys_utils/page/user_form.php?id=' + recid);
        $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Edit User Info</span>');
    }
</script>