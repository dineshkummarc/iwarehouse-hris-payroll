<?php

$program_code = 1;
require_once('../common/functions.php');
include("../common_function.class.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 2) !== "B+"){
    if($level <= $plevel ){
        echo json_encode(array("status" => "error", "message" => "This module is disabled by the administrator!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
$current_date = date("m/d/Y");
if (date("m") <= "11") {
  $year_end = date("12/31/Y");
  $yr = date("Y");
} else {
  $yr = date("Y") + 1;
  $year_end = date("12/31/$yr");
}
if (substr($access_rights, 4, 2) === "D+") {
  $can_delete = 1;
}else{
  $can_delete = 0;
}
$mast = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
$mast->execute(array(":no" => substr($_GET["emp_no"], 3)));
if ($mast->rowCount()) {
  $mast_data = $mast->fetch(PDO::FETCH_ASSOC);
  $recid = $mast_data["employee_no"];
  $_SESSION["emp_no"] = '100'.$recid;
  $name = $mast_data["given_name"] . " " . $mast_data["middle_name"] . " " . $mast_data["family_name"];
  $evl = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:eno AND !`is_cancelled` AND `year`=:yr");
  $evl->execute(array(":eno" => $recid, ":yr" => date("Y")));
  $vl_count = $evl->rowCount();
  $vu = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:eno AND !`is_cancelled` AND `year`=:yr AND `vl_date`<:date");
  $vu->execute(array(":eno" => $recid, ":yr" => date("Y"), ":date" => date("Y-m-d")));
  $vl_used = $vu->rowCount();
  $a = $db->prepare("SELECT * FROM $db_hris.`employee_allowable_vl` WHERE `employee_no`=:no AND `year`=:yr");
  $a->execute(array(":no" => $recid, ":yr" => date("Y")));
  if ($a->rowCount()) {
    $data = $a->fetch(PDO::FETCH_ASSOC);
    $allowable = $data["no_of_days"];
  } else {
    $allowable = 0;
  }
}
?>
<div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row w3-padding-top w3-tiny" style="overflow-y: scroll;">
  <div class="w3-col s12 w3-panel w3-small">
    <div class="w3-col s12 w3-padding">
      <button class="w2ui-btn w3-right w3-padding-bottom" onclick="close_it()">Close</button>
    </div>
    <div class="w3-col s12 w3-padding">
      <div class="w3-bottombar w3-padding">
        <span class="w3-medium">Vacation Leave of <span class="w3-text-orange"><b><?php echo $name; ?></b></span></span>
      </div>
    </div>
    <div class="w3-col s4 w3-card-4 w3-padding w3-round-medium">
      <div class="w3-col s12 w3-row-padding">VACATION LEAVE MAINTENANCE</div>
        <div class="w3-bar w3-col s12 w3-padding w3-small">
          <div class="w3-col s12 w3-row-padding">
            <div class="w3-col s12 m6 w3-row-padding">
              <label class="w3-label w3-row-padding w3-tiny">V/L DATE</label>
              <input class="w3-input w3-col s12 date w3-padding" id="newdate" />
            </div>
            <div class="w3-col s12 m6 w3-row-padding">
              <label class="w3-label w3-row-padding w3-tiny">ALLOWABLE THIS YEAR</label>
              <input class="w3-input w3-col s12 w3-padding" value="<?php echo number_format($allowable, 0); ?>" id="allowed" />
            </div>
            <div class="w3-col s12 w3-row-padding w3-margin-top">
              <?php if (substr($access_rights, 0, 2) === "A+") { ?>
              <button class="w3-col s12 w3-bar-item w3-button w3-orange w3-text-white w3-padding w3-round-medium" id="save" data-recid="<?php echo $recid; ?>" onclick="save_changes();">SAVE CHANGES</button>
              <?php } ?>
            </div>
            <div class="w3-col s12 m6 w3-row-padding">
              <label class="w3-label w3-row-padding w3-tiny">TOTAL V/L PLOTTED</label>
              <input class="w3-input w3-col s12 w3-padding w3-transparent" readonly="" disabled="" value="<?php echo number_format($vl_count, 0); ?>" />
            </div>
            <div class="w3-col s12 m6 w3-row-padding">
              <label class="w3-label w3-row-padding w3-tiny">USED THIS YEAR</label>
              <input class="w3-input w3-col s12 w3-padding w3-transparent" readonly="" disabled="" value="<?php echo number_format($vl_used, 0); ?>" />
            </div>
          </div>
        </div>
      </div>
      <div class="w3-col s8">
        <div class="w3-col s6">
          <div class="w3-col s12 w3-row-padding w3-margin-bottom">PLOTTED VACATION LEAVE</div>
            <div class="w3-col s12 w3-row-padding w3-margin-bottom">
              <?php
                $vl = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:no AND `year`=:yr ORDER BY `vl_date`, `time_stamp`");
                $vl->execute(array(":no" => $recid, ":yr" => date("Y")));
                if ($vl->rowCount()) { ?>
                  <div class="w3-col s12 w3-row-padding w3-margin-bottom w3-responsive">
                    <table class="w3-row-padding w3-table-all w3-tiny w3-hoverable">
                      <thead>
                        <tr>
                          <th class="w3-center" colspan="6">FOR THE YEAR <?php echo date("Y"); ?></th>
                        </tr>
                        <tr>
                          <th></th>
                          <th class="w3-center">VL DATE</th>
                          <th class="w3-center">STATUS</th>
                          <th class="w3-center">LAST CHANGED</th>
                          <th class="w3-center">DATE FILED</th>
                          <th class="w3-center">FILED BY</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                        $cnt = 0;
                        while ($vlp_data = $vl->fetch(PDO::FETCH_ASSOC)) {
                          $vl_date = $vlp_data['vl_date'];
                          $eno = $vlp_data['employee_vl_no']; ?>
                        <tr id="<?php echo $vlp_data['employee_vl_no']; ?>" <?php if (!($vlp_data["is_cancelled"] OR $vlp_data["is_served"]) AND $can_delete) {
                          if ($vlp_data["vl_date"] >= date("Y-m-d")) {
                            echo "style=\"cursor: pointer; cursor: hand;\"";
                            echo " onclick=\"show_remarks('$vlp_data[employee_vl_no]')\"";
                          }
                        }
                        if ($vlp_data["is_cancelled"]) {
                          echo "class=\"w3-red\"";
                        } ?>>
                          <td><?php echo number_format( ++$cnt, 0); ?></td>
                          <td class="w3-center"><?php echo (new DateTime($vlp_data["vl_date"]))->format("m/d/Y"); ?></td>
                          <td><?php if ((new DateTime($vlp_data["vl_date"]))->format('m/d/Y') <= (new DateTime($current_date))->format('m/d/Y')) { echo "SERVED"; } elseif ($vlp_data["is_cancelled"]) { echo "CANCELLED"; if ($vlp_data["reason_for_cancellation"] !== "") { echo ":  " . strtoupper($vlp_data["reason_for_cancellation"]); }} ?></td>
                          <td class="w3-center"><?php echo (new DateTime($vlp_data["time_stamp"]))->format("m/d/Y H:i:s"); ?></td>
                          <td class="w3-center"><?php echo (new DateTime($vlp_data["date_filed"]))->format("m/d/Y H:i:s"); ?></td>
                          <td><?php echo $vlp_data["user_id"]; ?></td>
                        </tr>
                        <?php } ?>
                      </tbody>
                      <tfoot id="reasons" class="w3-hide">
                        <?php if ($can_delete) { ?>
                        <tr>
                          <th colspan="6">
                            <textarea style="height: 35px;" class="w3-input w3-row-padding w3-col s12" placeholder="Provide reason for cancellation..." id="remark"></textarea>
                          </th>
                        </tr>
                        <tr>
                          <th colspan="6">
                            <button class="w3-col s6 w3-bar-item w3-button w3-orange w3-padding" id="cancel" onclick="cancel_vl('<?php echo $vl_date; ?>','<?php echo $eno; ?>');">CANCEL V/L</button>
                            <button class="w3-col s6 w3-bar-item w3-button w3-red w3-padding" id="close" onclick="close_cancel('<?php echo $recid; ?>');">CLOSE</button>
                          </th>
                        </tr>
                        <?php } ?>
                      </tfoot>
                    </table>
                  </div>
                <?php
              } ?>
            </div>
          </div>
          <div class="w3-col s6">
            <div class="w3-col s12 w3-row-padding w3-margin-bottom">PLOTTED VACATION LEAVE LAST YEAR</div>
              <div class="w3-col s12 w3-row-padding w3-margin-bottom">
                <?php
                $year = date("Y") - 1;
                $vlp = $db->prepare("SELECT * FROM $db_hris.`employee_vl` WHERE `employee_no`=:no AND `year`=:yr ORDER BY `vl_date`");
                $vlp->execute(array(":no" => $recid, ":yr" => $year));
                if ($vlp->rowCount()) {
                  $a->execute(array(":no" => $recid, ":yr" => date("Y") - 1));
                  if ($a->rowCount()) {
                    $data = $a->fetch(PDO::FETCH_ASSOC);
                    $total_vl = $data["no_of_days"];
                  }
                  ?>
                <div class="w3-col s12 w3-row-padding w3-margin-bottom w3-responsive">
                  <table class="w3-row-padding w3-table-all w3-tiny w3-hoverable">
                    <thead>
                      <tr>
                        <th class="w3-center" colspan="6">FOR THE YEAR <?php echo $year; ?></th>
                      </tr>
                      <?php if ($a->rowCount()) { ?>
                      <tr>
                        <td colspan="6">ALLOWABLE V/L: <?php echo $total_vl; ?></td>
                      </tr>
                      <?php } ?>
                      <tr>
                        <th></th>
                        <th class="w3-center">DATE</th>
                        <th class="w3-center">STATUS</th>
                        <th class="w3-center">LAST CHANGED</th>
                        <th class="w3-center">DATE FILED</th>
                        <th class="w3-center">FILED BY</th>
                      </tr>
                    </thead>
                    <tbody>
                    <?php
                      $cnt = 0;
                      while ($vlp_data = $vlp->fetch(PDO::FETCH_ASSOC)) { ?>
                      <tr <?php if ($vlp_data["is_cancelled"]) { echo "class=\"w3-red\""; } ?>>
                        <td><?php echo number_format( ++$cnt, 0); ?></td>
                        <td class="w3-center"><?php echo (new DateTime($vlp_data["vl_date"]))->format("m/d/Y"); ?></td>
                        <td> <?php if ($vlp_data["is_served"]) { echo "SERVED"; } elseif ($vlp_data["is_cancelled"]) { echo "CANCELLED"; if ($vlp_data["reason_for_cancellation"] !== "") { echo ".  " . strtoupper($vlp_data["reason_for_cancellation"]); } } ?> </td>
                        <td class="w3-center"><?php echo (new DateTime($vlp_data["time_stamp"]))->format("m/d/Y H:i:s") . "<br>" . $vlp_data["user_id"]; ?></td>
                        <td class="w3-center"><?php echo (new DateTime($vlp_data["date_filed"]))->format("m/d/Y H:i:s"); ?></td>
                        <td><?php echo $vlp_data["filed_by"]; ?></td>
                      </tr>
                      <?php } ?>
                    </tbody>
                  </table>
                </div>
              <?php 
            } ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function show_remarks(no){
  $('tr#'+no).addClass('w3-orange w3-text-white');
  $('#reasons').removeClass('w3-hide');
}

function close_cancel(no){
  $('#grid').load('modules/employee_vl.php?emp_no=100'+ no);
}

function save_changes() {
  var div = $('#main');
  w2confirm('Proceed to update?', function (btn) {
    if (btn === "Yes") {
      w2utils.lock(div, 'Please wait..', true);
      $.ajax({
        url: "page/master1.php",
        type: "post",
        data: {
          cmd: "new-vl",
          date: $("#newdate").val(),
          days: $("#allowed").val(),
          recid: $("button#save").data("recid")
        },
        success: function (data) {
          w2utils.unlock(div);
          var jObject = jQuery.parseJSON(data);
          if (jObject.status === "success") {
            $('#grid').load('modules/employee_vl.php?emp_no=100'+ $('button#save').data('recid'));
          } else {
            w2alert(jObject.message);
          }
        },
        error: function () {
          w2utils.unlock(div);
          w2alert("Please try again later!");
        }
      });
    }
  });
}

function cancel_vl(date, no) {
  var div = $('#main');
  var rem = $("#remark").val();
  if (rem !== "") {
    w2confirm('Proceed to cancel this date?', function (btn) {
      if (btn === "Yes") {
        w2utils.lock(div, 'Please wait..', true);
        $.ajax({
          url: "page/master1.php",
          type: "post",
          data: {
            cmd: "cancel-vl",
            date: date,
            recid: $("button#save").data("recid"),
            remark: rem,
            no: no
          },
          success: function (data) {
            w2utils.unlock(div);
            var jObject = jQuery.parseJSON(data);
            if (jObject.status === "success") {
              $('#grid').load('modules/employee_vl.php?emp_no=100'+ $('button#save').data('recid'));
            } else {
              w2alert(jObject.message);
            }
          },
          error: function () {
            w2utils.unlock(div);
            w2alert("Please try again later!");
          }
        });
      }
    });
  } else {
    w2alert("Please provide reason for cancellation!");
  }
}

$(document).ready(function () {
  $(":input.date").w2field("date", {start: '<?php echo $current_date; ?>', end: '<?php echo $year_end; ?>'});
});
    
function close_it(){
  var div = $('#main');
  w2utils.lock(div, 'Please wait..', true);
  destroy_grid();
  $.ajax({
    url: 'home',
    success: function(data){
      $('#grid').load('page/master');
      $('#append_data').remove();
      w2utils.unlock(div);
    }
  });
}
  </script>