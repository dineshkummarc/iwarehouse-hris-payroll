<?php
$program_code = 2;
require_once('../../system.config.php');
require_once('../../common_functions.php');
$today=date('Y-m-d');
$date1=strtotime("-90 day");
$fdate=date('Y-m-d',$date1);
$to = (new DateTime($today))->format("m/d/Y");
$fr = (new DateTime($fdate))->format("m/d/Y");
$_SESSION["emp_no"] = $_GET["pin_no"];
?>
<div class="w3-container w3-panel" style="width: 100%;">
	<div class="w3-container w3-padding-small">
		<input name="fdate" class="w3-small date" id="datef" value="<?php echo $fr; ?>" />
		<input name="tdate" class="w3-small date" id="datet" value="<?php echo $to; ?>" />
		<button name="getDate" id="getDate" class="w2ui-btn w3-small" onclick="get_journal()"><i class="fa-solid fa-arrows-rotate"></i>&nbsp;REFRESH</button>
		<button name="getBack" id="getDate" class="w2ui-btn w3-small w3-right" onclick="getBack()"><i class="fa-solid fa-rotate-left"></i>&nbsp;Get Back</button>
    </div>
    <div id="my_grid" style="width: 100%; height: 450px;"></div>
</div>
<script type="text/javascript">

var pin_no = '<?php echo substr($_GET["pin_no"], 3); ?>';

function getBack(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
	destroy_grid();
    $.ajax({
        success: function(data){
            $('#grid').load('./modules/hris/master.php');
            $('#append_data').remove();
            w2utils.unlock(div);
        }
    });
}
//toolbar
$(":input.date").w2field("date");

$(function () {
    $('#my_grid').w2grid({ 
        name: 'my_grid',
        show: { 
            toolbar: true,
            footer: true,
            lineNumbers: true
        },
        multiSearch: false,
        searches: [
            { field: 'ref', caption: 'Reference', type: 'text' }

        ],
        columns: [],
        records: []
    });
});

$(document).ready(function(){
    var c = $("div#my_grid");
    var h = window.innerHeight - 185;
    c.css("height", h);
	get_journal();
});

function get_journal(){
	w2ui.my_grid.clear();
    w2ui.my_grid.refresh();
	w2ui.my_grid.lock('Refreshing..', true);
    var df = $('#datef').val();
    var dt = $('#datet').val();
    $.ajax({
        url: src,
        method:"POST",
        data:{ 
            cmd: "get-journal",
            df:df,
            dt:dt,
            pin:pin_no
        },
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success") {
                w2ui.my_grid.columns = jObject.columns;
                w2ui.my_grid.add(jObject.records);
                w2ui.my_grid.unlock();
            }else{
                w2alert(jObject.message);
                w2ui.my_grid.unlock();
            }
        }
    });
}
</script>