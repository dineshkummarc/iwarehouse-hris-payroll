<?php
global $db_hris, $db;

include('session.php');
include('modules/system/system.config.php');
include("common_function.class.php");
$cfn = new common_functions();

$title = mysqli_query($con,"SELECT * FROM _sysconfig WHERE isDefault") or die (mysqli_error($con));
    while ($row=mysqli_fetch_array($title)){
    $header = $row['config_title'];
    if($header == 'iWarehouse'){
        $title1 = '<span class="w3-text-orange">i</span>Warehouse';
    }else{
        $title1 = $header;
    }
    $header1 = $row['config_value'];
}

?>
<!DOCTYPE html>
</html>
<html  moznomarginboxes mozdisallowselectionprint>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/w2ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/w2ui.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <title><?php echo $header1 ?></title>
</head>
<body>
<div class="w3-container w3-padding w3-border">
  <table class="w3-table-all w3-small">
    <thead>
      <tr>
        <th>PIN</th>
        <th>Name</th>
        <th>Date</th>
        <th>Total Late</th>
        <th>Minutes Late</th>
      </tr>
    </thead>
    <tbody>
    <?php
      $date = date('Y-m-14');
      //select employee who have late
      $emp_late = $db->prepare("SELECT * FROM $db_hris.`swipe_memo` WHERE `swipe_memo_code` IN (407,507) AND `trans_date`=:date AND !`is_cancelled` ORDER BY `time_stamp` ASC");
      $emp_late->execute(array(":date" => $date));
      if ($emp_late->rowCount()) {
        while ($emp_late_data = $emp_late->fetch(PDO::FETCH_ASSOC)) {
          $late[] = array("pin" => $emp_late_data["pin"], "trn_date" => $emp_late_data["trans_date"]); //store into array
        }
        foreach($late as $val => $emp_data){
          $epin = $emp_data['pin'];
          $trans_date = $emp_data['trn_date'];

          $att_log = $db->prepare("SELECT * FROM `attendance_log` WHERE `pin`=:pin AND `log_date`=:trans_date ORDER BY `log_date`,`log_time` LIMIT 1");
          $att_log->execute(array(":pin" => $epin, ":trans_date" => $trans_date));
          if ($att_log->rowCount()) {
            while ($att_log_data = $att_log->fetch(PDO::FETCH_ASSOC)){
              $time_in = $att_log_data['log_time'];
              $emp_pin = $att_log_data['pin'];
              $t_date = $trans_date; //store into array
            }
            $master = $db->prepare("SELECT * FROM `master_data` WHERE `pin`=:pin");
            $master->execute(array(":pin" => $emp_pin));
            if ($master->rowCount()) {
              while ($master_data = $master->fetch(PDO::FETCH_ASSOC)){
                $eno = $master_data['employee_no'];
              }
              $emp_sched = $db->prepare("SELECT * FROM $db_hris.`employee_work_schedule` WHERE `employee_no`=:eno AND `trans_date`=:sdate");
              $emp_sched->execute(array(":eno" => $eno, ":sdate" => $t_date));
              if ($emp_sched->rowCount()) {
                $sched_data = $emp_sched->fetch(PDO::FETCH_ASSOC);
                $shift_code = $sched_data["shift_code"];
                
                echo $shift_code.'<br>';

                $shift = $db->prepare("SELECT * FROM $db_hris.`shift` WHERE `shift_code`=:code");
                $shift->execute(array(":code" => $shift_code));
                if ($shift->rowCount()) {
                  while ($shift_data = $shift->fetch(PDO::FETCH_ASSOC)) {
                    $scode = $shift_data["shift_code"];
                    $time = $shift_data["start_hh"].':'.$shift_data["start_mm"];
                  }
                  $datetime1 = new DateTime($trans_date.' '.$time_in);
                  $datetime2 = new DateTime($trans_date.' '.$time);
                  $interval = $datetime1->diff($datetime2);
                  $mins = $interval->format('%h').":".$interval->format('%i');
                  
                  if($time_in > $time){
                    $delete = $db->prepare("DELETE FROM `employee_late` WHERE `trans_date`=:tdate AND `employee_no`=:eid");
                    $delete->execute(array(":eid" => $eno, ":tdate" => $t_date));
                    $insert_late = $db->prepare("INSERT INTO `employee_late`(`employee_no`, `trans_date`, `mins_late`, `start_time`, `log_time`, `isLate`) VALUES (:eid, :tdate, :mins, :start, :log_time, :isLate)");
                    $insert_late->execute(array(":eid" => $eno, ":tdate" => $t_date, ":mins" => $mins, ":start" => $time, ":log_time" => $time_in, ":isLate" => 1));
                  }
                }
              }
            }
          }
        }
      }
    ?>
    </tbody>
  </table>
</div>
</body>
</html>
</body>
</html>
