<?php
$program_code = 8;
include("../system.config.php");
include("../common_functions.php");
$cfn = new common_functions();

$access_rights = $cfn->get_user_rights($program_code);
if (substr($access_rights, 6, 2) !== "B+") {
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
?>
<div class="w3-col s12 w3-small" id="access_div"></div>

<script text="type/javascript">
    $(document).ready(function(){
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "get-default-user-rights"
            },
            success: function (data){
                $('#access_div').html(data);
                w2utils.unlock(div);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
                w2utils.unlock(div);
            }
        });
    });
</script>