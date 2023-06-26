<?php
$program_code = 3;
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
<div class="w3-container">
  <div id="tabs" style="width: 100%;"></div>
  <div id="selected-tab" class="w3-padding-top w3-transparent"></div>
</div>
<script type="text/javascript">

  $(function () {
    $('#tabs').w2tabs({
      name: 'tabs',
      active: "pay_group",
      tabs: [],
      onClick: function (event) {
        switch (event.target){
          case 'pay_group': pay_group();
            break;
          case 'hol': holidays();
            break;
          case 'sys_config': sys_utils();
            break;
          case 'swipe': swipe_memo();
            break;
          case 'pay_type': payroll_type();
            break;
        }
      }
    });
  });

  $(document).ready(function(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
      url: "page/sys_config",
      type: "post",
      data: {
        cmd: "get-tabs"
      },
      success: function(data) {
        var jObject = jQuery.parseJSON(data);
        if (jObject.status === "success") {
          w2ui.tabs.tabs = jObject.tabs;
          w2ui.tabs.active = jObject.active;
          w2ui.tabs.refresh();
          w2utils.unlock(div);
          setTimeout(function(){
            if(jObject.active === "pay_group"){
              pay_group();
            }else if(jObject.active === "hol"){
              holidays();
            }else if(jObject.active === "swipe"){
              swipe_memo();
            }else if(jObject.active === "sys_config"){
              sys_utils();
            }else if(jObject.active === "pay_type"){
              payroll_type();
            }
          }, 100);
        } else {
          w2alert(data.message);
          w2utils.unlock(div);
        }
      },
      error: function() {
        w2alert("Sorry, there was a problem in server connection!");
      }
    });
  });

  function payroll_type(){
    $.ajax({
      url: "page/sys_config",
      type: "post",
      data: {
        cmd: "default-pay-type"
      },
      success: function(data) {
        if (data !== "") {
          $('#append_data').remove();
          $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Payroll Type</span>');
          $('#selected-tab').html(data);
        } else {
          w2alert("Sorry, No DATA found!");
        }
      },
      error: function() {
        w2alert("Sorry, there was a problem in server connection!");
      }
    });
  }

  function pay_group(){
    $('#append_data').remove();
    $('#selected-tab').load('pay_group');
    $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Payroll Group</span>');
  }

  function swipe_memo(){
    $('#append_data').remove();
    $('#selected-tab').load('swipe_memo');
    $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Swipe Memo</span>');
  }

  function sys_utils(){
    $('#append_data').remove();
    $('#selected-tab').load('sys_utils');
    $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;System Configuration</span>');
  }
  function holidays(){
    $('#append_data').remove();
    $('#selected-tab').load('page/holiday');
    $('#active_program').append('<span class="w3-text-black" id="append_data">&nbsp;<i class="fa-solid fa-angle-right"></i>&nbsp;Holiday</span>');
  }
</script>