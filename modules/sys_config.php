<?php
$program_code = 3;
require_once('../common/functions.php');
?>
<div class="w3-container">
  <div id="tabs" style="width: 100%;"></div>
  <div id="selected-tab" class="w3-padding-top w3-transparent"></div>
</div>
<script type="text/javascript">

  var userlvl = "<?php echo $level['user_level']; ?>";

  $(function () {
    $('#tabs').w2tabs({
      name: 'tabs',
      active: 'tab1',
      tabs: [
        { id: 'tab1', text: 'Payroll Group' },
        { id: 'tab4', text: 'Swipe Memo' },
        { id: 'tab2', text: 'Holidays' },
        { id: 'pay_type', text: 'Payroll Type' },
        { id: 'tab3', text: 'System Configuration'}
      ],
      onClick: function (event) {
        switch (event.target){
          case 'tab1': pay_group();
            break;
          case 'tab2': holidays();
            break;
          case 'tab3': sys_utils();
            break;
          case 'tab4': swipe_memo();
            break;
          case 'pay_type': payroll_type();
            break;
        }
      }
    });
  });

  $(document).ready(function(){
    if(userlvl < 9){
      w2ui.tabs.hide('tab3');
      w2ui.tabs.hide('pay_type');
    }else{
      w2ui.tabs.show('tab3');
      w2ui.tabs.show('pay_type');
    }
    pay_group();
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