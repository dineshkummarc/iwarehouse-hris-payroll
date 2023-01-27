<?php

function post_shift($current_date){
  global $con, $db_hris;
    
  set_time_limit(300);
  mysqli_query($con, "DELETE FROM $db_hris.`employee_work_schedule` WHERE `trans_date`='$current_date'") or die(mysqli_error($con));
  $day = date('w', mktime(0, 0, 0, substr($current_date, 5, 2), substr($current_date, 8, 2), substr($current_date, 0, 4)));
  $master=  mysqli_query($con, "SELECT * FROM $db_hris.`master_data` WHERE !`is_inactive` AND `work_schedule`!=''") or die(mysqli_error($con));
  if(@mysqli_num_rows($master))
    while($master_data=  mysqli_fetch_array($master)){
      set_time_limit(30);
      $shift = explode(",",$master_data["work_schedule"]);
      $shift_schedule = $shift[$day];
      mysqli_query($con, "INSERT INTO $db_hris.`employee_work_schedule` (`employee_no`, `trans_date`, `shift_code`) VALUES ('$master_data[employee_no]', '$current_date', '$shift_schedule')") or die(mysqli_error($con));
    }
}
?>