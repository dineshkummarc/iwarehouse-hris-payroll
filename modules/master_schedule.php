<?php
error_reporting(0);
$program_code = 3;
require_once('../common/functions.php');
include('system/system.config.php');

global $db, $hris;
$master = $db->prepare("SELECT * FROM $db_hris.`master_data` WHERE `employee_no`=:no");
$master->execute(array(":no" => substr($_REQUEST["emp_no"], 3)));
if ($master->rowCount()) {
    $master_data = $master->fetch(PDO::FETCH_ASSOC);
    $emp_no = $master_data["employee_no"];
    $emp_pin = $master_data["pin"];
    $name = $master_data["given_name"] . " " . $master_data["middle_name"] . " " . $master_data["family_name"];
    if (isset($_REQUEST["set"])) {
        $shift_set = $_REQUEST["set"];
    } else {
        if ($master_data["shift_set_no"]) {
          $shift_set = $master_data["shift_set_no"];
        } else {
          $shift_set = 1;
        }
    }
    if (!isset($_SESSION["wksfr"])) {
        $wks = new DateTime(date("m/01/Y"));
        $wks->modify("-1 months");
        $_SESSION["wksfr"] = $wks->format("Y-m-d");
        $_SESSION["wksto"] = date("Y-m-d");
    }
}
?>
<div class="w3-col s12 w3-padding w3-small">
    <button class="w3-button w3-border w3-right w3-round-medium w3-hover-red" style="padding: 6px 16px 6px 16px;" onclick="close_it()">Close</button>
</div>
<div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-row" style="overflow-y: scroll;">
<div class="w3-col s12 w3-padding">
  <div class="w3-bottombar w3-padding">
    <span class="w3-medium">Shift Schedule of <b><?php echo $name; ?></b></span>
  </div>
</div>
<div class="w3-col s8">
  <div class="w3-col s12 w3-row-padding w3-margin-bottom w3-responsive">
    <table class="w3-row-padding w3-table-all w3-small w3-hoverable">
      <thead>
        <tr>
          <th class="w3-center" colspan="3">EFFECTIVE SHIFT SCHEDULE</th>
        </tr>
        <tr>
          <th class="w3-center">DAY</th>
          <th class="w3-center">START DATE</th>
          <th class="w3-center">
            <div class="w3-col s12 w3-row-padding">
              <select class="w3-col s12 w3-select w3-transparent" id="shiftset" onchange="reset_shift('<?php echo '100'.$emp_no; ?>');">
              <?php
                $shift_sched = $db->prepare("SELECT * FROM $db_hris.`shift_set` ORDER BY `description`");
                $shift_sched->execute();
                if ($shift_sched->rowCount()) {
                  while ($shift_sched_data = $shift_sched->fetch(PDO::FETCH_ASSOC)) { ?>
                  <option value="<?php echo $shift_sched_data["shift_set_no"]; ?>" <?php
                    if (number_format($shift_sched_data["shift_set_no"], 0, '.', '') === number_format($shift_set, 0, '.', '')) {
                      echo "selected=\"\"";
                    }
                    ?>><?php echo $shift_sched_data["description"]; ?></option>
                    <?php
                  }
                } ?>
              </select>
            </div>
          </th>
        </tr>
      </thead>
      <tbody>
        <?php
          $emp_shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_set_no`=:no ORDER BY `shift_code`");
          $date = new DateTime(date("m/d/Y"));
          if ($master_data["work_schedule"] !== "") {
            $sched = explode(",", $master_data["work_schedule"]);
          } else {
            $sched = array();
          }
          for ($index = 0; $index <= 6; $index++) {
            $emp_shift->execute(array(":no" => $shift_set));
            if (number_format($date->format("N"), 0) === number_format(7, 0)) {
              $day = 0;
            } else {
              $day = $date->format("N");
            }
            ?>
            <tr>
              <td valign="center"><?php echo strtoupper($date->format("l")); ?></td>
              <td valign="center"><?php echo $date->format("m/d/Y"); ?></td>
              <td><div class="w3-col s12">
                <?php
                if($emp_shift->rowCount()){
                  while($emp_shift_data = $emp_shift->fetch(PDO::FETCH_ASSOC)){
                    if (number_format($emp_shift_data["shift_code"], 0, '.', '') === number_format($sched[$day], 0, '.', '')){
                      $color = "w3-orange" . " " . strtoupper($date->format("l"));
                    }else{
                      $color = strtoupper($date->format("l"));
                    }
                    ?>
                    <button onclick="alter_sched('<?php echo strtoupper($date->format("l")); ?>', this);" id="<?php echo $emp_shift_data["shift_code"]; ?>" data-id="<?php echo $emp_shift_data["shift_name"]; ?>" class="w3-button w3-padding w3-row-padding w3-border w3-round-medium w3-border-orange <?php echo $color; ?>"><?php echo $emp_shift_data["shift_name"]; ?></button>
                    <?php
                  }
                } ?></div></td>
              </tr>
              <?php
                $date->modify("+1 day");
            } ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3">
            <input type="text" id="remark" class="w3-input" placeholder="Provide remarks for changes in shift schedule..." style="height: 30px;"></input>
          </td>
        </tr>
        <tr>
          <td colspan="3">
          <button class="w3-col s12 w3-bar-item w3-button w3-teal w3-padding" id="save" data-recid="<?php echo '100'.$emp_no; ?>" onclick="save_changes();">SAVE CHANGES</button>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<div class="w3-col s4">
  <?php
  $sc = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` INNER JOIN $db_hris.`shift` ON `shift`.`shift_code`=`employee_work_schedule`.`shift_code` INNER JOIN $db_hris.`shift_set` ON `shift_set`.`shift_set_no`=`shift`.`shift_set_no` WHERE `employee_no`=:no AND `trans_date`>=:fr AND `trans_date`<=:to ORDER BY `trans_date` DESC");
  $sc->execute(array(":no" => $emp_no, ":fr" => $_SESSION["wksfr"], ":to" => $_SESSION["wksto"]));
  if ($sc->rowCount()) { ?>
    <div class="w3-col s12 w3-row-padding w3-margin-bottom w3-responsive">
      <table class="w3-row-padding w3-table-all w3-tiny w3-hoverable">
        <thead>
          <tr>
            <th colspan="2">
              <div class="w3-col s4 w3-row-padding">
                <input size="15" class="w3-input w3-row-padding date w3-col s12 w3-padding" id="fr" value="<?php echo (new DateTime($_SESSION["wksfr"]))->format("m/d/Y"); ?>" />
              </div>
              <div class="w3-col s4 w3-row-padding">
                <input size="15" class="w3-input w3-row-padding date w3-col s12 w3-padding" id="to" value="<?php echo (new DateTime($_SESSION["wksto"]))->format("m/d/Y"); ?>" />
              </div>
              <div class="w3-col s4 w3-row-padding">
                <button onclick="refresh_history();" class="w3-button w3-bar w3-padding w3-row-padding w3-col s12 w3-hover-orange"><i class="fa fa-refresh" aria-hidden="true"></i>&nbsp;&nbsp;REFRESH</button>
              </div>
            </th>
          </tr>
          <tr>
            <th>DATE</th>
            <th>SHIFT</th>
          </tr>
        </thead>
        <tbody>
        <?php
        while ($sc_data = $sc->fetch(PDO::FETCH_ASSOC)) { ?>
          <tr>
            <td><?php echo (new DateTime($sc_data["trans_date"]))->format("m/d/Y"); ?></td>
            <td><?php echo $sc_data["description"] . " -> <span class=\"w3-text-green\">" . $sc_data["shift_name"] . "</span>"; ?></td>
          </tr>
          <?php
        } ?>
        </tbody>
      </table>
    </div>
    <?php
  } ?>
</div>
<script type="text/javascript">

//close window
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

//change selection of shift sched
function alter_sched(day, item) {
  var daysched = $("button").hasClass(day);
  $("button").filter("." + day).each(function (i) {
    if ($(this).hasClass("w3-orange")) {
      $(this).removeClass("w3-orange");
    }
  });
  $(item).addClass("w3-orange");
}

//change options
function reset_shift(emp_no) {
  $('#grid').load('modules/master_schedule.php?emp_no='+ emp_no +'&cmd=edit&set=' + $("select#shiftset").val());
}

//get schedule
function get_schedule_desc() {
  var sched_desc = "";
  $("button").filter(".w3-orange").each(function (i) {
    if (sched_desc === "") {
      sched_desc = $(this).attr("data-id");
    } else {
      sched_desc += "," + $(this).attr("data-id");
    }
  });
  return sched_desc;
}

function get_schedule() {
  var sched = "";
  $("button").filter(".w3-orange").each(function (i) {
    if (sched === "") {
      sched = $(this).attr("id");
    } else {
      sched += "," + $(this).attr("id");
    }
  });
  return sched;
}

//save schedule
function save_changes() {
  var sched = get_schedule();
  var sched_desc = get_schedule_desc();
  var rem = $("#remark").val();
  if (rem !== "") {
    w2confirm('Effect changes in shift schedule?', function (btn) {
      if (btn === "Yes") {
        $.ajax({
          url: "page/master1",
          type: "post",
          data: {
            cmd: "update-workschedule",
            sched: sched,
            sched_desc : sched_desc,
            set: $("#shiftset").val(),
            remark: rem,
            recid: $("#save").data("recid")
          },
          success: function (data) {
            var jObject = jQuery.parseJSON(data);
            if (jObject.status === "success") {
              $('#grid').load('modules/master_schedule.php?emp_no='+ $('button#save').data('recid') + '&cmd=edit');
            } else {
              w2alert(jObject.message);
            }
          },
          error: function () {
            w2alert("Please try again later!");
          }
        });
      }
    });
  } else {
    w2alert("Please provide remark for changes in shift schedule!");
  }
}
    
    function refresh_history() {
      var div = $('#main');
      w2utils.lock(div, 'Please wait..', true);
      $.ajax({
        url: "page/master1",
        type: "post",
        data: {
          cmd: "refresh-workschedule",
          fr: $("#fr").val(),
          to: $("#to").val()
        },
        success: function (data) {
          w2utils.unlock(div);
          var jObject = jQuery.parseJSON(data);
          if (jObject.status === "success") {
            $('#grid').load('modules/master_schedule.php?emp_no='+ $('button#save').data('recid') + '&cmd=edit');
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

    $(document).ready(function () {
      $(":input.date").w2field("date");
    });
  </script>
