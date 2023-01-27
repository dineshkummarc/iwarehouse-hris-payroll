<?php
$program_code = 8;
require_once('../common/functions.php');
?>
<div class="w3-col s12 w3-panel w3-small" id="access_div"></div>
<script text="type/javascript">
    $(document).ready(function(){
        $.ajax({
            url: "page/system.program",
            type: "post",
            data: {
                cmd: "get-default-user-rights"
            },
            success: function (data){
                $('#access_div').html(data);
            },
            error: function (){
                w2alert("Sorry, there was a problem in server connection!");
            }
        });
    });
</script>