<?php

$program_code = 29;
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
<div id="shifts_data"></div>
<script type="text/javascript">
    $(document).ready(function () {
        var c = $("div.window");
        var h = window.innerHeight - 160;
        c.css("height", h);
        set_shift_default();
    });

    function set_shift_default(){
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "default"
            },
            success: function (data){
                $('#shifts_data').html(data);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    }
</script>