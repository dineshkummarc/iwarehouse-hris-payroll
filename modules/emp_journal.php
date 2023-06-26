<?php
$program_code = 2;
require_once('../common/functions.php');

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
		<button name="getDate" id="getDate" class="w2ui-btn w3-small" onclick="get_journal()"><i class="fa-solid fa-arrows-rotate"></i></button>
		<button name="getBack" id="getDate" class="w2ui-btn w3-small w3-right" onclick="getBack()"><i class="fa-solid fa-rotate-left"></i>&nbsp;Get Back</button>
    </div>
    <div id="changes_grid" style="width: 100%; height: 450px;"></div>
</div>
<script type="text/javascript">

var pin_no = '<?php echo substr($_GET["pin_no"], 3); ?>';

function getBack(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
	destroy_grid();
    $.ajax({
        url: 'home.php',
        success: function(data){
            $('#grid').load('page/master.php');
            $('#append_data').remove();
            w2utils.unlock(div);
        }
    });
}
//toolbar
$(":input.date").w2field("date");

$(function () {
    $('#changes_grid').w2grid({ 
        name: 'journal_grid',
        show: { 
            toolbar: true,
            footer: true,
            lineNumbers: true
        },
        multiSearch: false,
        searches: [
            { field: 'ref', caption: 'Reference', type: 'text' }

        ],
        columns: [                
            { field: 'recid', caption: 'seq_no', size: '5%', hidden: true },
            { field: 'ref', caption: 'REFERENCE', size: '15%'},
            { field: 'cf', caption: 'Change From', size: '15%'},
            { field: 'ct', caption: 'Change To', size: '15%'},
            { field: 'rm', caption: 'Remarks', size: '25%'},
            { field: 'uid', caption: 'Username', size: '15%'},
            { field: 'ip', caption: 'Station', size: '15%'},
            { field: 'ts', caption: 'TimeStamp', size: '15%'}
        ],
        records: []
    });
});

$(document).ready(function(){
	get_journal();
});

function get_journal(){
	w2ui['journal_grid'].clear();
    w2ui['journal_grid'].refresh();
	w2ui['journal_grid'].lock('Refreshing..', true);
    var df = $('#datef').val();
    var dt = $('#datet').val();
    $.ajax({
        url: "page/master1.php",
        method:"POST",
        data:{ cmd: "get-journal", df:df, dt:dt, pin:pin_no},
        dataType: "json",
        success: function(jObject){
            if (jObject.status === "success") {
                w2ui['journal_grid'].add(jObject.records);
                w2ui['journal_grid'].unlock();
            }else{
                w2alert(jObject.message);
                w2ui['journal_grid'].unlock();
            }
        }
    });
}
</script>