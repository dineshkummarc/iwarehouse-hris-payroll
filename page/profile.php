<?php

$program_code = 32;
include("../system.config.php");
include("../common_functions.php");
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
};
?>
<div class="w3-row" style="width: 100%;">
    <div class="w3-third w3-container">
        <div id="form" style="width: 100%; height: 450px;"></div>
    </div>
    <div class="w3-twothird">
        <div id="my_grid" style="width: 100%; height: 450px;"></div>
    </div>      
</div>

<script type="text/javascript">

    var c = $("div#form, div#my_grid");
    var h = window.innerHeight - 185;
    c.css("height", h);

    $(document).ready(function(){
        set_ui(); 
    });

    $(function () {
        $('#form').w2form({ 
            name   : 'form',
            header : 'My Profile',
            formURL: './page/profile.html',
            fields : [
                { field: 'recid', type: 'int', required: true },
                { field: 'uid',  type: 'int', required: true },
                { field: 'level',   type: 'text' },
                { field: 'name', type: 'text', required: true },
                { field: 'user_pass', type: 'password', required:  true },
                { field: 'user_pass1', type: 'password', required:  true },
                { field: 'user_pass2', type: 'password', required:  true }
            ],
            actions: {
                save: function () {
                    w2confirm('Update profile?', function (btn) {
                        if (btn === "Yes") {
                            update_profile();
                        }
                    });
                }
            }
        });
    });

    $(function () {    
        $('#my_grid').w2grid({ 
            name: 'my_grid',
            header: 'MY ACTIVITY',
            show: { 
                header: true,
                toolbar: false,
                footer: true,
                lineNumbers: true
            },
            multiSearch: false,
            columns: [
                {field: 'recid', caption: 'No', size: '100px', hidden: true},
                {field: 'prog', caption: 'Program Opened', size: '500px'},
                {field: 'ts', caption: 'TimeStamp', size: '200px'},
                {field: 'ip', caption: 'Station', size: '200px'}
            ],
            records: []
        });
    });


    function set_ui(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-user-info",
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        setTimeout(() => {
                            $('input#recid').val(_return.recid);
                            $('input#uid').val(_return.uid);
                            $('input#level').val(_return.lvl);
                            $('input#name').val(_return.fname);
                            w2ui.my_grid.clear();
                            w2ui.my_grid.add(_return.records);
                            w2utils.unlock(div);
                        }, 150);
                    }else{
                        w2alert(_return.message);
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

    function update_profile(record){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        const acc_id = $('input#recid').val();
        const uid = $('input#uid').val();
        const name = $('input#name').val();
        const user_pass = $('input#user_pass').val();
        const user_pass1 = $('input#user_pass1').val();
        const user_pass2 = $('input#user_pass2').val();
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "update-profile",
                acc_id : acc_id,
                uid : uid,
                name : name,
                user_pass : user_pass,
                user_pass1 : user_pass1,
                user_pass2 : user_pass2
            },
            success: function(data){
                w2utils.unlock(div);
                var jObject = jQuery.parseJSON(data);
                if (jObject.status == "success") {
                    $('input#user_pass').val('');
                    $('input#user_pass1').val('');
                    $('input#user_pass2').val('');
                    $("#passwordmatch").html("");
                    $('#passwordstrength').html("");
                    w2alert(jObject.message);
                }else{
                    w2alert(jObject.message);
                    $('input#user_pass1').val('');
                    $('input#user_pass2').val('');
                    $("#passwordmatch").html("");
                    $('#passwordstrength').html("");
                }
            },
            error: function () {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!  Maybe it was too busy or call System Admin for assistance.");
            }
        });
    }


    
    /*password checker start*/
    function checkStrength(password) {
        var strength = 0;
        if (password.length < 6) {
            $('#passwordstrength').removeClass();
            $('#passwordstrength').addClass('w3-text-red');
            return "SHORT";
        }
        if (password.length > 7) {
            strength += 1;
        }
        if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/)) {
            strength += 1;
        }

        if (password.match(/([a-zA-Z])/) && password.match(/([0-9])/)) {
            strength += 1;
        }

        if (password.match(/([!,%,&,@,#,$,^,*,?,_,~])/)) {
            strength += 1;
        }

        if (password.match(/(.*[!,%,&,@,#,$,^,*,?,_,~].*[!,%,&,@,#,$,^,*,?,_,~])/)) {
            strength += 1;
        }

        if (strength < 2) {
            $('#passwordstrength').removeClass();
            $('#passwordstrength').addClass('w3-text-red');
            return "WEAK";
        }
        else if (strength === 2) {
            $('#passwordstrength').removeClass();
            $('#passwordstrength').addClass('w3-text-orange');
            return "GOOD";
        } else {
            $('#passwordstrength').removeClass();
            $('#passwordstrength').addClass('w3-text-green');
            return "STRONG";
        }
    }

    function passStrenghth() {
        $('#passwordstrength').html(checkStrength($('#user_pass1').val()));
    }

    function checkMatch() {
        var matched;
        $("#passwordmatch").removeClass();
        if ($('#user_pass1').val() === $('#user_pass2').val()) {
            $("#passwordmatch").html("MATCH");
            $("#passwordmatch").addClass('w3-text-green');
            matched = true;
        } else {
            $("#passwordmatch").html("DOES NOT MATCH");
            $("#passwordmatch").addClass('w3-text-red');
            matched = false;
        }
        return matched;
    }
</script>