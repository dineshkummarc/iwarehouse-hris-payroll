<?php

$program_code = 31;
require_once('../../../system.config.php');
require_once('../../../common_functions.php');
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
if (isset($_REQUEST["cmd"])) {
    try {
        if ($db->beginTransaction()) {
            switch ($_REQUEST["cmd"]) {
                case "remove-attendee":
                    if (substr($access_rights, 4, 2) === "D+") {
                      remove_attendee($_POST["df"], $_POST["dt"], $_POST["record"]);
                    }else{
                      echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                      return;
                    }
                    break;
                case "add-attendee":
                    if (substr($access_rights, 0, 2) === "A+") {
                      add_attendee($_POST["df"], $_POST["dt"], $_POST["name"]);
                    }else{
                      echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                    return;
                    }
                    break;
                case "attendee":
                    echo json_encode(array("status" => "success", "records" => get_enrolled(), "attendee" => get_name()));
                    break;
                case "plot":
                    if (substr($access_rights, 8, 2) === "P+") {
                      $data = get_attendance($_POST["df"], $_POST["dt"]);
                      echo json_encode(array("status" => "success", "records" => $data["records"], "columns" => $data["columns"]));
                    }else{
                      echo json_encode(array("status" => "error", "message" => "No Access Rights"));
                    return;
                    }
                    break;
            }
            $db->commit();
            return false;
        }
    } catch (PDOException $e) {
        $db->rollBack();
        echo json_encode(array("status" => "error", "message" => "database is not ready!", "e" => $e));
        exit();
    }
}

//remove selected attendee
function remove_attendee($df, $dt, $record) {
  global $db, $db_hris;
  
  $rem = $db->prepare("DELETE FROM $db_hris.`attendee` WHERE `user_id` LIKE :uid AND `pin` LIKE :id");
  $rem->execute(array(":uid" => $_SESSION['name'], ":id" => $record["recid"]));
  if ($rem->rowCount()) {
    $data = get_attendance($df, $dt);
    echo json_encode(array("status" => "success", "records" => $data["records"], "attendee" => get_enrolled()));
  } else {
    echo json_encode(array("status" => "error", "message" => "record cannot be removed.  please try again later!", "e" => $rem->errorInfo()));
  }
}

//adding attendee
function add_attendee($df, $dt, $name) {
  global $db, $db_hris;
  
  $ref = explode(" ", $name);
  $add = $db->prepare("INSERT INTO $db_hris.`attendee` (`pin`, `user_id`) VALUES (:id, :uid)");
  $add->execute(array(":id" => $ref[0], ":uid" => $_SESSION['name']));
  $data = get_attendance($df, $dt);
  echo json_encode(array("status" => "success", "records" => $data["records"], "attendee" => get_enrolled(), "e" => $add->errorInfo(), "q" => $add, "d" => array(":id" => $ref[0], ":uid" => $_SESSION['name'])));
}

//get attendee
function get_name() {
  global $db, $db_hris;
  
  set_time_limit(600);
  $records = array();
  $fpusers = $db->prepare("SELECT * FROM $db_hris.`master_data` ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
  $fpusers->execute();
  if ($fpusers->rowCount()) {
    while ($fpusers_data = $fpusers->fetch(PDO::FETCH_ASSOC)) {
      if ($fpusers_data["pin"] !== "") {
        $records[] = $fpusers_data["pin"] . " " . $fpusers_data["family_name"] . ", " . $fpusers_data["given_name"] . " " . $fpusers_data["middle_name"];
        set_time_limit(60);
      }
    }
  }
  return $records;
}

//get enrolled attendee to plot
function get_enrolled() {
  global $db, $db_hris;
  
  set_time_limit(600);
  $attendee = $db->prepare("SELECT * FROM $db_hris.`master_data`, $db_hris.`attendee` WHERE `master_data`.`pin` LIKE `attendee`.`pin` AND `attendee`.`user_id` LIKE :uid ORDER BY `master_data`.`family_name`");
  $attendee->execute(array(":uid" => $_SESSION['name']));
  $records = array();
  if ($attendee->rowCount()) {
    while ($attendee_data = $attendee->fetch(PDO::FETCH_ASSOC)) {
      array_push($records, array("recid" => $attendee_data["pin"], "name" => $attendee_data["family_name"] . ", " . $attendee_data["given_name"] . " " . substr($attendee_data["middle_name"],0,1)));
      set_time_limit(60);
    }
  }
  return $records;
}

//plotting attendance
function get_attendance($datef, $datet, $option = "grid") {
  global $db, $db_hris;
  
  set_time_limit(0);
  $df = new DateTime($datef);
  $dt = new DateTime($datet);
  $records = array();
  $count = 0;
  $attendee = $db->prepare("SELECT * FROM $db_hris.`attendee`, $db_hris.`master_data` WHERE `attendee`.`user_id` LIKE :uid AND `attendee`.`pin` LIKE `master_data`.`pin` ORDER BY `master_data`.`family_name`, `master_data`.`given_name`, `master_data`.`middle_name`");
  $attendee->execute(array(":uid" => $_SESSION['name']));
  if ($attendee->rowCount()) {
    $logs = $db->prepare("SELECT * FROM $db_hris.`log_type` WHERE `log_value`=:log");
    $attendance = $db->prepare("SELECT DISTINCT `log_date` AS `log_date`, `log_time` AS `log_time`, `log_type` FROM $db_hris.`attendance_log` WHERE `pin` LIKE :id AND CONCAT(`log_date`, ' ',`log_time`)>=:df AND CONCAT(`log_date`, ' ',`log_time`)<=:dt ORDER BY `log_date`,`log_time`");
    while ($attendee_data = $attendee->fetch(PDO::FETCH_ASSOC)) {
      if ($attendee_data["pin"] !== "") {
        $record = array();
        $record["recid"] = $attendee_data["pin"] . "." . gregoriantojd($df->format("m"), $df->format("d"), $df->format("Y"));
        $record["name"] = $attendee_data["pin"] . " ".$attendee_data["family_name"] . ", " . $attendee_data["given_name"] . " " . substr($attendee_data["middle_name"],0,1);
        $dxf = new DateTime($df->format("m/d/Y"));
        while ($dxf->format("Ymd") <= $dt->format("Ymd")) {
          $record["day"] = strtoupper($dxf->format("D"));
          $record["date"] = $dxf->format("m/d/Y");
          $tcount = 0;
          $dxt = new DateTime($dxf->format("m/d/Y"));
          $dxt->modify("+1 day");
          $attendance->execute(array(":id" => $attendee_data["pin"], ":df" => $dxf->format("Y-m-d") . " 01:00:00", ":dt" => $dxt->format("Y-m-d") . " 02:59:59"));
          $hour = $min = 0;
          if ($attendance->rowCount()) {
            $get = false;
            $break = get_time(array("recid" => $attendee_data["pin"], "df" => $dxf->format("Y-m-d"), "dt" => $dxf->format("Y-m-d")));
            $record["break"] = $break["break"];
            while ($attendance_data = $attendance->fetch(PDO::FETCH_ASSOC)) {
              if ($get) {
                $since = $time->diff(new DateTime($attendance_data["log_date"].' '.$attendance_data["log_time"]));
                $hour += $since->h;
                $min += $since->i;
              }
              $tcount++;
              $time = new DateTime($attendance_data["log_date"].' '.$attendance_data["log_time"]);
              $record["d$tcount"] = $time->format("H:i:s");
              $logs->execute(array(":log" => $attendance_data["log_type"]));
              if ($logs->rowCount()) {
                $logs_data = $logs->fetch(PDO::FETCH_ASSOC);
                $record["x$tcount"] = $logs_data["log_value"];
              }
              if ($get) {
                $record["t$tcount"] = substr(number_format(100 + $since->h), -2) . ":" . substr(number_format(100 + $since->i), -2);
                if ($option === "grid") {
                  $record["t$tcount"] = "<span style='color:green; font-weight:bolder;'>" . $record["t$tcount"] . "</span>";
                }
              }
              $get = !$get;
            }
          }
          if ($hour + $min > 0) {
            $mins = $min % 60;
            $hours = $hour + ($min - $min % 60) / 60;
            if ($option === "grid") {
              $record["tot"] = "<span style='color:orange; font-weight:bold;'>" . substr(number_format($hours + 100), -2) . ":" . substr(number_format(100 + $mins), -2) . "</span>";
            } else {
              $record["tot"] = substr(number_format($hours + 100), -2) . ":" . substr(number_format(100 + $mins), -2);
            }
          }
          if (number_format($tcount, 0, '.', '') > number_format($count, 0, '.', '')) {
            $count = $tcount;
          }
          array_push($records, $record);
          $dxf->modify("+1 day");
          $record = array();
          $record["recid"] = $attendee_data["pin"] . "." . gregoriantojd($dxf->format("m"), $dxf->format("d"), $dxf->format("Y"));
        }
        $dt = new DateTime($datet);
      }
    }
  }
  return array("records" => $records, "columns" => get_column($count, $option));
}

//get grid column
function get_column($count, $option = "grid") {
  $records = array();
  $records[] = array("field" => "name", "caption" => "NAME", "size" => "220px");
  $records[] = array("field" => "day", "caption" => "DAY", "size" => "40px", "attr" => "align=center");
  $records[] = array("field" => "date", "caption" => "DATE", "size" => "95px", "attr" => "align=center");
  $records[] = array("field" => "break", "caption" => "BREAK", "size" => "60px", "attr" => "align=center");
  $records[] = array("field" => "tot", "caption" => "TOTAL", "size" => "60px", "attr" => "align=center");
  $tcount = 0;
  $in = true;
  while (number_format($tcount, 0, '.', '') <= number_format($count, 0, '.', '')) {
    $tcount++;
    if ($in) {
      $cap = "IN";
    } else {
      $cap = "OUT";
    }
    $records[] = array("field" => "d$tcount", "caption" => $cap, "size" => "60px", "attr" => "align=center");
    $in = !$in;
    if ($in) {
      $fld = array();
      $fld[] = "x" . $tcount;
      if ($option === "grid") {
        $info = array("fields" => $fld, "showEmpty" => true);
      } else {
        $info = false;
      }
      $records[] = array("field" => "t$tcount", "caption" => "", "size" => "90px", "attr" => "align=center");
    }
  }
  return $records;
}

function get_time($record) {
  global $db, $db_hris;

  $b = $db->prepare("SELECT `log_date`, `log_time`, `log_type` FROM $db_hris.`attendance_log` WHERE `pin` LIKE :pin AND CONCAT(`log_date`, ' ',`log_time`) >= :df AND `log_date`<=:dt ORDER BY CONCAT(`log_date`, ' ',`log_time`)");
  $b->execute(array(":pin" => $record["recid"], ":df" => $record["df"], ":dt" => $record["dt"]));
  if ($b->rowCount()) {
    $type = $cnt = $start = $credit = $break = $coffee = 0;
    while ($data = $b->fetch(PDO::FETCH_ASSOC)) {
      if ($cnt++) {
        $end = substr($data["log_date"]." ".$data["log_time"], 11, 2) * 60 + substr($data["log_date"]." ".$data["log_time"], 14, 2);
        $time = number_format($end - $start, 0, '.', '');
        switch (number_format($type, 0, '.', '')) {
          case number_format(2, 0):
            //break
            $break += $time;
            break;
          case number_format(3, 0):
            //coffee
            $coffee += $time;
            break;
          default :
            $credit += $time;
        }
        $start = $end;
      } else {
        $start = substr($data["log_date"]." ".$data["log_time"], 11, 2) * 60 + substr($data["log_date"]." ".$data["log_time"], 14, 2);
      }
      $type = $data["log_type"];
    }
    if (number_format($credit, 0, '.', '') !== number_format(0, 0)) {
      $time = number_format($credit, 0, '.', '');
      $d = new DateTime(date("m/d/Y"));
      $d->modify("+$time minutes");
      $credit = $d->format("H:i");
    }
    if (number_format($break, 0, '.', '') !== number_format(0, 0)) {
      $time = number_format($break, 0, '.', '');
      $d = new DateTime(date("m/d/Y"));
      $d->modify("+$time minutes");
      $break = $d->format("H:i");
    }else{
      $break = "00:00";
    }
    if (number_format($coffee, 0, '.', '') !== number_format(0, 0)) {
      $time = number_format($coffee, 0, '.', '');
      $d = new DateTime(date("m/d/Y"));
      $d->modify("+$time minutes");
      $coffee = $d->format("H:i");
    }
  }
  return array("credit" => $credit, "break" => $break, "coffee" => $coffee);
}