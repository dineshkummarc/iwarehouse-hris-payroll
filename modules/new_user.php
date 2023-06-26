<?php
$program_code = 6;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+") {
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
};
?>
<div class="w3-panel w3-container w3-border w3-round-medium">
    <div class="w3-col s12 w3-panel w3-small">
        <div class="w3-col s12 w3-padding">
            <div class="w3-panel w3-bottombar w3-padding">
                <span class="w3-medium" id="reg_header">User Registration</span>
                <button class="w3-button w3-red w3-right w3-round-large" id="close_it" onclick="close_it()">Get Back</button>
            </div>
        </div>
        <div class="w3-col s12 w3-panel">
            <div class="w3-col s5 w3-margin-bottom">
                <div class="w3-col s12 w3-container">
                    <label class="w3-label">Name:</label>
                    <input name="user_id" type="hidden" id="user_id"/>
                    <input name="name" type="text" id="name" maxlength="100" style="width: 100%" class="w2ui-input w3-round-medium w3-padding-small w3-border" placeholder="Fullname..">
                </div>
            </div>
            <div class="w3-container w3-col s5">
                <label class="w3-label">Level:</label>
                <select id="lvl" name="lvl" class="w3-padding-small w3-round-medium" style="width: 100%;">
                    <option value="">Select User Level</option>
                    <?php if(number_format($level, 2) > number_format(8,2)){ ?>
                    <option value="9">System Owner</option>
                    <option value="8">Admin</option>
                    <?php } ?>
                    <option value="7">Supervisor</option>
                    <option value="5">User Level 3</option>
                    <option value="3">User Level 2</option>
                    <option value="2">User Level 1</option>
                </select>
            </div>
            <div class="w3-container w3-margin-bottom w3-margin-top w3-col s2">
                <button class="w3-button w3-padding-small w3-right w3-round-medium w3-green w3-hover-black" id="enroll" onclick="enroll()">Save</button>
                <button class="w3-button w3-padding-small w3-right w3-round-medium w3-orange w3-hover-black w3-margin-right" id="reset" onclick="reset()">Reset</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

    var user_id = '<?php echo $_GET["id"] ?>';

    $(document).ready(function(){
        if(user_id != 0){
            get_user_info(user_id);
        }
        
    });

    function get_user_info(user_id){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: "page/new_user",
            type: "post",
            data: {
                cmd: "get-user",
                user_id: user_id
            },
            success: function (data){
                if (data !== ""){
                    var _return = jQuery.parseJSON(data);
                    if(_return.status === "success"){
                        $('#reg_header').text('Edit User Information of '+ _return.name);
                        $('input#name').val(_return.name);
                        $('input#user_id').val(_return.id);
                        $('select#lvl').val(_return.level);
                        $('button#enroll').text('Update');
                        w2utils.unlock(div);
                    }else{
                        w2alert("Sorry, No DATA found!");
                        w2utils.unlock(div);
                    }
                }
            },
            error: function () {
                w2alert("Sorry, there was a problem in server connection or Session Expired!");
                w2utils.unlock(div);
            }
        })
    }

    function enroll(){
        var name = $("#name").val();
        var lvl = $("#lvl").val();
        var user_id = $("#user_id").val();
        if(user_id == ''){
            if(name !== "" && lvl !== ""){
                $.ajax({
                    url: "page/new_user",
                    type: "post",
                    data: {
                        cmd: "enroll",
                        name: name,
                        lvl : lvl
                    },
                    dataType: "json",
                    success: function (jObject){
                        if (jObject.status === "success"){
                            close_it();
                            reset();
                        }else{
                            w2alert(jObject.message);
                        }
                    },
                    error: function () {
                        w2alert("Sorry, there was a problem in server connection or Session Expired!");
                    }
                });
            }else{
                w2alert("Please supply all required data!");
            }
        }else{
            $.ajax({
                url: "page/new_user",
                type: "post",
                data: {
                    cmd: "update",
                    name: name,
                    lvl : lvl,
                    user_id : user_id
                },
                dataType: "json",
                success: function (jObject){
                    if (jObject.status === "success"){
                        close_it();
                        reset();
                    }else{
                        w2alert(jObject.message);
                    }
                },
                error: function () {
                    w2alert("Sorry, there was a problem in server connection or Session Expired!");
                }
            });
        }
    }

function close_it(){
    destroy_grid();
    $.ajax({
        url: 'home',
        beforeSend: function(){
            closeMenu();
            $('#close_it').prop('disabled', true);
        },
        success: function(data){
            $('#grid').load('modules/user_reg');
            $('#close_it').prop('disabled', false);
            $('#append_data').remove();
        }
    })
}

function reset(){
    $('#name').val('');
    $('lvl').empty();
}
</script>