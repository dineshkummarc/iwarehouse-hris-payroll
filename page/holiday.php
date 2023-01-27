<?php

$program_code = 5;
require_once('../common/functions.php');

include('../modules/system/system.config.php');
?>
<style type="text/css">
.tableFixHead,
.tableFixHead td {
  box-shadow: inset 1px -1px #000;
}
.tableFixHead th {
  box-shadow: inset 1px 1px #000, 0 1px #000;
}
</style>
  <body>
    <div class="w3-panel w3-small">
      <input name="hol_date" class="date w3-small" id="hol_date" />
      <input name="hol_name" type="text" class="w2ui-input" size="50" id="hol_name" placeholder="Holiday Description.."/>
      <input name="special" type="checkbox" id="special" value="1" /> &nbsp;Special?
      <button type="submit" class="w3-hover-orange w3-hover-text-white w3-round-medium w3-margin-left" style="padding: 4px 6px 4px 6px;" onclick="save_holiday()">ADD HOLIDAY</button>
      <button type="button" class="w3-hover-orange w3-hover-text-white w3-round-medium w3-margin-left w3-right w3-border" style="padding: 4px 6px 4px 6px;" onclick="copy_holiday()">COPY PREVIOUS HOLIDAY</button>
    </div>
    <div id="holiday_list"></div>
  </body>
</html>
<script type="text/javascript">
  $(":input.date").w2field("date");
  const src = "page/sys_config";

  function edit_del($hol_id){
    w2confirm('Are you sure to delete this Holiday?')
    .yes(function () { delete_hol($hol_id); })
    .no(function () { w2confirm.close(); });
  }

  function delete_hol($hol_id){
    $.ajax({
      url: src,
      type: "post",
      data: {
        cmd: "del-hol",
        hol_id : $hol_id
      },
      success: function (data){
        if (data !== ""){
          var _return = jQuery.parseJSON(data);
          if(_return.status === "success"){
            get_holiday();
          }else{
            w2alert("Sorry, No DATA found!");
          }
        }
      },
      error: function (){
        w2alert("Sorry, there was a problem in server connection!");
      }
    });
  }

  $(document).ready(function(){
    get_holiday();
  });

  function get_holiday(){
    $.ajax({
      url: src,
      type: "post",
      data: {
        cmd: "get-holiday"
      },
      success: function (data){
        $('#holiday_list').html(data);
      },
      error: function (){
        w2alert("Sorry, there was a problem in server connection!");
      }
    });
  }

  function copy_holiday(){
    w2confirm('Are you sure to copy previous year holidays to this year?', function (btn){
      if(btn == "Yes"){
        generate_holidays();
      }
    });
  }

  function generate_holidays() {
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
      url: src,
      type: "post",
      data: { cmd: "generate" },
      dataType: "json",
      success: function (jObject) {
        w2utils.unlock(div);
        if (jObject.status === "success") {
          get_holiday();
        } else {
          w2alert(jObject.message);
        }
      },
      error: function () {
        w2utils.unlock(div);
      }
    });
  }

  function save_holiday(){
    var hol_date = $('#hol_date').val();
    var hol_name =$('#hol_name').val();
    if($('#special').is(':checked')){
      var is_special = 1;
    }else{
      var is_special = 0;
    }
    $.ajax({
      url: src,
      type: "post",
      data: {
        cmd: "new-hol",
        hol_date : hol_date,
        hol_name : hol_name,
        is_special : is_special
      },
      success: function (data){
        if (data !== ""){
          var _return = jQuery.parseJSON(data);
          if(_return.status === "success"){
            $('#hol_date').val('');
            $('#hol_name').val('');
            if($('#special').is(':checked')){
              $('#special').click();
            }
            get_holiday();
          }else{
            w2alert("Holiday already exist!");
          }
        }
      },
      error: function (){
        w2alert("Sorry, there was a problem in server connection!");
      }
    });
  }
</script>