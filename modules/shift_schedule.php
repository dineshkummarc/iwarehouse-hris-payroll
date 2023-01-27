<?php

$program_code = 1;
require_once('../common/functions.php');
?>
<div id="shifts_data"></div>
<script type="text/javascript">

    const src = "page/shift_schedule";

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