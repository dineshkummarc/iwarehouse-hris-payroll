<?php
session_start();
include('modules/system/system.config.php');
?>

<!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/w2ui.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/w2ui.min.css"/>
    <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>iWarehouse</title>
</head>
<body>
<div class="w3-container w3-mobile w3-responsive">
  <div class="w3-row w3-border">
    <div class="w3-container w3-half">
      <div class="w3-card-4 w3-round-large w3-border w3-margin">
        <div class="w3-panel w3-center">
          <span class="w3-xxlarge w3-padding"><?php echo date("D - F j, Y"); ?></span>
        </div>
        <div class="w3-panel w3-center">
          <input type="hidden" name="" value="" id="time_now"></input>
          <input type="hidden" name="" value="" id="date_now"></input>
          <span class="w3-xxxlarge" id="time"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i>refreshing..</span>
          <input type="hidden" value="Get Server Time" id="clock_btn" onclick="timer_function();">
        </div>
        <div class="w3-panel w3-round-large w3-border w3-margin">
          <div class="w3-container w3-margin">
            <select name="log_type" id="log_type" class="w3-select w3-small w3-dropdown w3-border w3-round-medium" style="background: none;">
              <?php
                $sql = mysqli_query($con,"SELECT log_value,log_message FROM log_type") or die (mysqli_error($con));
                while ($row=mysqli_fetch_array($sql)){
                  $log_message=$row['log_message'];
                  $log_value=$row['log_value'];
                  ?>
                  <option class="w3-small" value="<?php echo $log_value; ?>" <?php if ($log_value == 1) { echo 'selected'; } ?>><?php echo $log_message; ?></option>
                <?php } ?>
            </select>
          </div>
          <div class="w3-container w3-margin">
            <input type="text" name="emp_no" class="w3-input w3-border w3-round-medium" placeholder="Enter Employee Number"></input>
          </div>
          <div class="w3-container w3-margin">
            <input type="submit" name="emp_no" class="w3-button w3-blue w3-round-medium" value="SUBMIT" style="width: 100%;"></input>
          </div>
        </div>
      </div>
    </div>
    <div class="w3-container w3-half w3-border-left">
      <div class="w3-panel w3-margin">
        <span>Employee Log</span>
      </div>
    </div>
  </div>
</div>
<script type="text/javascript">
$(document).ready(function(){
  setTimeout(function(){
    $('#clock_btn').click();
  }, 1000);
});

//this is the date time
function timer_function() {
  var x = new Date()
  var ampm = x.getHours( ) >= 12 ? ' PM' : ' AM';
  hours = x.getHours( ) % 12;
  hours = hours ? hours : 12;
  hours=hours.toString().length==1? 0+hours.toString() : hours;

  var minutes=x.getMinutes().toString()
  minutes=minutes.length==1 ? 0+minutes : minutes;

  var seconds=x.getSeconds().toString()
  seconds=seconds.length==1 ? 0+seconds : seconds;

  var month=(x.getMonth() +1).toString();
  month=month.length==1 ? 0+month : month;

  var dt=x.getDate().toString();
  dt=dt.length==1 ? 0+dt : dt;

  var x1=month + "/" + dt + "/" + x.getFullYear();
  var x3=x.getFullYear() + "-" + month + "-" + dt;
  x1 = x1 + " - " +  hours + ":" +  minutes + ":" +  seconds + " " + ampm;
  x2 = hours + ":" +  minutes + ":" +  seconds;
  document.getElementById('time').innerHTML = x2 + " " + ampm;
  document.getElementById('time_now').value = x2;
  display_c7();
}

function display_c7(){
  var refresh=1000; // Refresh rate in milli seconds
  mytime=setTimeout('timer_function()',refresh)
}

display_c7()
</script>
</body>
</html>