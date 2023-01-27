<?php
$program_code = 3;
require_once('../common/functions.php');

?>
<script>
    const src = "page/reports";

    $(document).ready(function () {
        var c = $("#late-container");
        var h = window.innerHeight - 185;
        c.css("height", h);
        var div = $('#main');
        w2utils.lock(div, 'Please wait..', true);
        $(":input.date").w2field("date");
        $.ajax({
            url: src,
            type: "post",
            data: {
                cmd: "late-default"
            },
            success: function (data) {
                w2utils.unlock(div);
                if (data !== "") {
                    $('#late_data').html(data);
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
    <div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row w3-padding-top" style="overflow-y: scroll;" id="late-container">
        <div class="w3-small w3-container">
            DATE RANGE: <input name="datef" class="w3-small date" id="datef" style="width: auto;" autocomplete="off"/>
            <input name="datet" class="w3-small date" id="datet" style="width: auto;" autocomplete="off"/>
            <input id="extract" type="button" onclick="getLateRecords();" class="w3-hover-green" style="cursor: pointer; padding: 3px 20px 3px 20px; border: 1px solid silver; border-radius: 4px;" value="EXTRACT"/>
        </div>
        <div id="late_data" style="width: 100%;" class="w3-padding-top"></div>
    </div>
    
</body>