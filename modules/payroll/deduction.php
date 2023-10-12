<?php
$program_code = 18;
require_once('../../system.config.php');
require_once('../../common_functions.php');
global $db, $db_hris;
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
<div class="w3-responsive w3-mobile">
	<div id="my_grid" style="width: 100%;"></div>
</div>
<script type="text/javascript">
  $(document).ready(function(){
    var c = $("div#my_grid");
    var h = window.innerHeight - 185;
    c.css("height", h);
    get_default_records();
  });

  $(function () {
    $('#my_grid').w2grid({ 
      name: 'my_grid',
      header: 'Deduction Master List',
      show: { 
        toolbar: true,
        footer: true,
        lineNumbers: true,
        header: true,
      },
      columnGroups: [],
      columns: [],
      records: [],
      onUnselect: function(event) {
        w2ui.my_grid.toolbar.disable('edit');
        w2ui.my_grid.toolbar.disable('enable');
      },
      onSelect: function(event) {
        w2ui.my_grid.toolbar.enable('edit');
        w2ui.my_grid.toolbar.enable('enable');
      },
      toolbar: {
        items: [
          { type: 'break' },
          { type: 'button',  id: 'add',  caption: 'Add Deduction', icon: 'fa-solid fa-plus' },
          { type: 'break' },
          { type: 'button',  id: 'edit',  caption: 'Edit Deduction', icon: 'fa-solid fa-pencil', disabled: true },
          { type: 'spacer' },
          { type: 'break' },
          { type: 'button',  id: 'enable',  caption: 'Active/InActive', icon: 'fa-solid fa-clipboard-check', disabled: true},
          { type: 'break' },
        ],
        onClick: function (event) {
          switch(event.target){
            case "add":
              $().w2popup('open',{
                showMax: false,
                showClose: true,
                body: '<div id="form" style="width: 100%; height: 150px; margin-top: 50px;"></div>',
                width: 450,
                height: 350,
                title: "NEW DEDUCTION",
                onOpen: function (event) {
                  event.onComplete = function () {
                    $("div#form").load("./modules/payroll/page/ded_form.php");
                  };
                }
              });
              break;
            case "edit":
              if(w2ui['my_grid'].getSelection().length > 0){
                $().w2popup('open',{
                  showMax: false,
                  showClose: true,
                  body: '<div id="form" style="width: 100%; height: 150px; margin-top: 50px;"></div>',
                  width: 450,
                  height: 400,
                  title: "NEW DEDUCTION",
                  onOpen: function (event) {
                    event.onComplete = function () {
                      var div = $('#main');
                      w2utils.lock(div, 'Please wait..', true);
                      $("div#form").load("./modules/payroll/page/ded_form.php");
                      if($('input#mid[value="1"]').is(":checked") && $('input#end[value="2"]').is(":checked")){
                        $('input#ded_id').val('');
                        $('input#ded_name').val('');
                        $('input#ded_option').w2field('list', { items: ded_opt });
                        $('input#mid[value="1"]').click();
                        $('input#end[value="2"]').click();
                      }
                      $.ajax({
                        url: src,
                        type: "post",
                        data: {
                          cmd: "get_ded_data",
                          ded_no : w2ui.my_grid.getSelection()[0]
                        },
                        dataType: "json",
                        success: function (jObject){
                          if (jObject !== ""){
                            if(jObject.status === "success"){
                              setTimeout(() => {
                                $('input#ded_id').val(jObject.ded_no);
                                $('input#ded_label').val(jObject.ded_label);
                                $('input#ded_name').val(jObject.ded_name);
                                $('input#ded_option').w2field().set({id: jObject.ded_type_no, text: jObject.ded_type});
                                $('input#mid[value="' + jObject.mid + '"]').click();
                                $('input#end[value="' + jObject.end + '"]').click();
                                $('button#save').text('UPDATE DEDUCTION');
                              }, 100);
                              w2utils.unlock(div);
                            }else{
                              w2alert(jObject.message);
                              w2utils.unlock(div);
                            }
                            w2utils.unlock(div);
                          }
                          w2utils.unlock(div);
                        },
                        error: function (){
                          w2alert("Sorry, there was a problem in server connection!");
                          w2utils.unlock(div);
                        }
                      });
                    };
                  }
                });
              }
              break;
            case "enable":
              if(w2ui.my_grid.getSelection().length > 0){
                w2confirm('Enable/Disable this deduction?', function (btn){
                  if(btn === 'No'){
                    w2ui.my_grid.refresh();
                  }else{
                    update_status(w2ui.my_grid.getSelection()[0]);
                  }
                });
              }
              break;
            }
          }
        }
    });
  });

  function update_status(recid){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
      url: src,
      method: "POST",
      data:{
        cmd: "update-status",
        recid: recid
      },
      dataType: "json",
      success: function (data){
        if (data !== ""){
          if(data.status === "success"){
            get_default_records();
          }else{
            w2utils.unlock(div);
            w2alert(data.message);
          }
        }else{
          w2utils.unlock(div);
          w2alert("Sorry, There was a problem in server connection!");
        }
      },
      error: function (){
        w2alert("Sorry, There was a problem in server connection!");
      }
    })
  }

  function get_default_records(){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
      url: src,
      type: "post",
      data: {
        cmd: "get-default"
      },
      dataType: "json",
      success: function (jObject){
        if (jObject.status === "success"){
          w2ui.my_grid.clear();
          w2ui.my_grid.refresh();
          w2ui.my_grid.columnGroups = jObject.col_group;
          w2ui.my_grid.columns = jObject.columns;
          w2ui.my_grid.add(jObject.records);
          w2utils.unlock(div);
        }
      },
      error: function () {
        w2alert("Sorry, there was a problem in server connection!");
        w2utils.unlock(div);
      }
    });
  }

  function save_ded(){
    var data = get_data();
    if (data !== "") {
      save_update_ded(data);
    }else{
			w2alert("No data to validate!");
		}
  }

  function get_data(){
    var data = {}, record = "";
    data["ded_no"] = $("#ded_id").val();
    data["ded_label"] = $("#ded_label").val();
    data["ded_name"] = $("#ded_name").val();
    data["type"] = $('input#ded_option').w2field().get().id;
    data["mid"] = $("#mid").is(":checked") ? "1" : "";
    data["end"] = $("#end").is(":checked") ? "1" : "";
    record = data;
    return record;
  }

  function save_update_ded(data){
    var div = $('#main');
    w2utils.lock(div, 'Please wait..', true);
    $.ajax({
      url: src,
      method: "POST",
      data:{
        cmd: "save-update-ded",
        record: data
      },
      dataType: "json",
      success: function (data){
        if (data !== ""){
          if(data.status === "success"){
            w2popup.close();
            w2utils.unlock(div);
            get_default_records();
          }else{
            w2popup.close();
            w2utils.unlock(div);
            w2alert(data.message);
          }
        }else{
          w2alert("Sorry, There was a problem in server connection!");
        }
      },
      error: function (){
        w2alert("Sorry, There was a problem in server connection!");
      }
    });
  }

</script>
