<?php
$program_code = 34;
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
<script>
    const src = "page/reports";

    $(document).ready(function () {
        var c = $("#absent-container");
        var h = window.innerHeight - 185;
        c.css("height", h);
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $(":input.date").w2field("date");
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "abs-default"
            },
            success: function (data) {
                w2utils.unlock(div);
                if (data !== "") {
                    $('#absent_data').html(data);
                }
            },
            error: function () {
                w2utils.unlock(div);
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    });

</script>
<body>
    <div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row w3-padding-top" style="overflow-y: scroll;" id="absent-container">
        <div class="w3-small w3-container">
            DATE RANGE: <input name="datef" class="w3-small date" id="datef" style="width: auto;" autocomplete="off"/>
            <input name="datet" class="w3-small date" id="datet" style="width: auto;" autocomplete="off"/>
            <input id="extract" type="button" onclick="getAbsentRecords();" class="w3-hover-green" style="cursor: pointer; padding: 3px 20px 3px 20px; border: 1px solid silver; border-radius: 4px;" value="EXTRACT"/>
        </div>
        <div id="absent_data" style="width: 100%;" class="w3-padding-top"></div>
    </div>
    
</body>