<?php
$program_code = 37;
include('modules/system/system.config.php');
include('session.php');
include("common_function.class.php");
$cfn = new common_functions();
$level = $cfn->get_user_level();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
if (substr($access_rights, 6, 2) !== "B+") {
  if($level <= $plevel ){
      echo json_encode(array("status" => "error", "message" => "Higher level required!"));
      return;
  }
  echo json_encode(array("status" => "error", "message" => "No Access Rights"));
  return;
}
?>
<style type="text/css">
    td, th {
      text-align: left;
      padding: 2px;
    }
</style>
<div class="w3-small"  style="width: 70%;">
  <table class="w3-table-all w3-border">
      <thead>
          <tr>
              <th></th>
              <th>Payroll Group Name</th>
              <th>Cut Off Date</th>
              <th>Payroll Date</th>
              <th>
                  <?php if (substr($access_rights, 0, 2) === "A+") { ?>
                  <a onclick="new_pay_group()" class="w3-hover-text-orange" id="new_pay"><ion-icon class="w3-medium w3-padding-top" name="add-circle-outline"></ion-icon></a>
                  <a onclick="cancel_pay_group()" class="w3-hover-text-red w3-hide" id="cancel_pay"><ion-icon class="w3-medium w3-padding-top" name="remove-circle-outline"></ion-icon></a>
                  <?php } ?>
              </th>
              <th></th>
          </tr>
      </thead>
      <tbody>
          <tr class="w3-hide" id="new_set">
              <td></td>
              <td><input id="pay_name" name="pay_name" style="padding: 2px 4px; width: 100%;" class="w3-border w3-round-medium"></td>
              <td><input id="cuttoff_date" name="cuttoff_date" class="date"></td>
              <td><input id="payroll_date" name="payroll_date" class="date"></td>
              <td>
                  <?php if (substr($access_rights, 0, 2) === "A+") { ?>
                    <input type="checkbox" onclick="save_pay_group(0)">&nbsp;SAVE
                  <?php } ?>
              </td>
              <td></td>
          </tr>
      <?php
      global $db_hris, $db;

          $emp_status = $db->query("SELECT * FROM $db_hris.`employment_status` WHERE `employment_status_code` > 0 ORDER BY `description`");
          if ($emp_status->rowCount()) {
              $count = 0;
              $pay_group = $db->prepare("SELECT * FROM $db_hris.`payroll_group` WHERE `group_name`=:no");
              while ($emp_status_data = $emp_status->fetch(PDO::FETCH_ASSOC)) {
                  $count++;
                  $pay_group->execute(array(":no" => $emp_status_data["employment_status_code"]));
                  if ($pay_group->rowCount()) {
                      $pay_group_data = $pay_group->fetch(PDO::FETCH_ASSOC);
                      $code = $emp_status_data["employment_status_code"];
                      $desc = $emp_status_data["description"];

                      ?>
                      <tr id="<?php echo $code ?>" style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white">
                          <td><?php echo $count; ?>.</td>
                          <td><?php echo $desc; ?></td>
                          <td><input id="cuttoff_date<?php echo $code ?>" name="cuttoff_date" class="date" value="<?php echo date('m/d/Y',strtotime($pay_group_data['cutoff_date'])); ?>
                      "></td>
                          <td><input id="payroll_date<?php echo $code ?>" name="payroll_date" class="date" value="<?php echo date('m/d/Y',strtotime($pay_group_data['payroll_date'])); ?>"></td>
                          <td>
                            <?php if (substr($access_rights, 0, 4) === "A+E+") { ?>
                            <input type="checkbox" onclick="save_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;SAVE
                            <?php } ?>
                          </td>
                          <td>
                            <?php if (substr($access_rights, 4, 2) === "D+") { ?>
                            <input type="checkbox" onclick="del_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;DEL
                            <?php } ?>
                          </td>
                      </tr>
                  <?php
                  }else{ ?>
                      <tr id="<?php echo $emp_status_data['employment_status_code']; ?>" style="cursor: pointer;" class="w3-hover-orange w3-hover-text-white">
                          <td><?php echo $count; ?>.</td>
                          <td><?php echo $emp_status_data["description"]; ?></td>
                          <td><input id="cuttoff_date<?php echo $emp_status_data['employment_status_code']; ?>" name="cuttoff_date" class="date"></td>
                          <td><input id="payroll_date<?php echo $emp_status_data['employment_status_code']; ?>" name="payroll_date" class="date"></td>
                          <td>
                            <?php if (substr($access_rights, 0, 4) === "A+E+") { ?>
                            <input type="checkbox" onclick="save_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;SAVE
                            <?php } ?>
                          </td>
                          <td>
                            <?php if (substr($access_rights, 4, 2) === "D+") { ?>
                            <input type="checkbox" onclick="del_pay_group(<?php echo $emp_status_data['employment_status_code']; ?>)">&nbsp;DEL
                            <?php } ?>
                          </td>
                      </tr>
                  <?php 
                  }

              }
              ?>
          </tbody>
      </table>
  <?php
      }
  ?>
</div>

<script type="text/javascript">
    $(":input.date").w2field("date");

    const src = "page/sys_config";

    function save_pay_group($pay_group_code){
      if($pay_group_code == 0){
        var group_name = $('#pay_name').val();
        var cuttoff_date = $('#cuttoff_date').val();
        var payroll_date = $('#payroll_date').val();
        $.ajax({
          url: src,
          method: "POST",
          data:{
            cmd: "new_group",
            group_name : group_name,
            payroll_date : payroll_date,
            cuttoff_date : cuttoff_date
          },
          success: function (data){
            if (data !== ""){
              var _return = jQuery.parseJSON(data);
              if(_return.status === "success"){
                pay_group();
              }else{
                w2alert(_return.message);
              }
            }
          },
          error: function (){
            w2alert("Sorry, There was a problem in server connection!");
          }
        });
      }else{
        var cuttoff_date = $('#cuttoff_date'+$pay_group_code).val();
        var payroll_date = $('#payroll_date'+$pay_group_code).val();
        $.ajax({
          url: src,
          method: "POST",
          data:{
            cmd: "update_group",
            pay_group_code : $pay_group_code,
            payroll_date : payroll_date,
            cuttoff_date : cuttoff_date
          },
          success: function (data){
            if (data !== ""){
              var _return = jQuery.parseJSON(data);
              if(_return.status === "success"){
                pay_group();
              }else{
                w2alert(_return.message);
              }
            }
          },
          error: function (){
            w2alert("Sorry, There was a problem in server connection!");
          }
        });
      }
    }

    function new_pay_group(){
        $('#new_set').removeClass('w3-hide');
        $('#cancel_pay').removeClass('w3-hide');
        $('#new_pay').addClass('w3-hide');
    }

    function cancel_pay_group(){
        $('#new_set').addClass('w3-hide');
        $('#cancel_pay').addClass('w3-hide');
        $('#new_pay').removeClass('w3-hide');
    }

    function del_pay_group(pay_group_code){
      $.ajax({
        url: src,
        method: "POST",
        data:{
          cmd: "del_group",
          group_no : pay_group_code
        },
        success: function (data){
          if (data !== ""){
            var _return = jQuery.parseJSON(data);
            if(_return.status === "success"){
              pay_group();
            }else{
              w2alert(_return.message);
            }
          }
        },
        error: function (){
          w2alert("Sorry, There was a problem in server connection!");
        }
      });
    }
</script>