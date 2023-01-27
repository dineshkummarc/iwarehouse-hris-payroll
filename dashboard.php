<?php
$program_code = 1;
include('modules/system/system.config.php');
include('session.php');

$check_level = mysqli_query($con, "SELECT `user_level` FROM `_user` where `user_id`='".$session_name."'");
$level = mysqli_fetch_array($check_level);

if($level['user_level'] < $program_code){
    exit();
}

function emp_total(){
    global $db, $db_hris;
    $count_emp = $db->prepare("SELECT count(*) FROM $db_hris.`master_data` WHERE !`is_inactive`");
    $count_emp->execute();
    if ($count_emp->rowCount()) {
        $number_of_rows = $count_emp->fetchColumn();
    }else{
        $number_of_rows = 0;
    }
    return $number_of_rows;
}


function running_rate_total(){
    global $db, $db_hris;
    $emp_rate = $db->prepare("SELECT SUM(`employee_rate`.`total_pay`) AS `total_rate_pay` FROM $db_hris.`employee_rate`,$db_hris.`master_data` WHERE `master_data`.`is_inactive`=:inactv AND `employee_rate`.`employee_no`=`master_data`.`employee_no`");
    $emp_rate->execute(array(":inactv" => 0));
    if ($emp_rate->rowCount()) {
        $emp_rate_data = $emp_rate->fetch(PDO::FETCH_ASSOC);

        $total_rate = $emp_rate_data['total_rate_pay'];

    }else{
        $total_rate = 0;
    }
    return $total_rate;
}

function total_net_pay(){
    global $db, $db_hris;
    $emp_net_pay = $db->prepare("SELECT SUM(`net_pay`) AS `net_pay` FROM $db_hris.`payroll_trans` WHERE `is_posted`");
    $emp_net_pay->execute();
    if ($emp_net_pay->rowCount()) {
        $emp_net_data = $emp_net_pay->fetch(PDO::FETCH_ASSOC);

        $total_running_net = $emp_net_data['net_pay'];

    }else{
        $total_running_net = 0;
    }
    return $total_running_net;
}

function payroll_date(){
    global $db, $db_hris;
    $pay = $db->prepare("SELECT `cutoff_date`,`payroll_date` FROM $db_hris.`payroll_group` WHERE `group_name`=:no");
    $pay->execute(array(":no"=> 100));
    if ($pay->rowCount()) {
        $pay_data = $pay->fetch(PDO::FETCH_ASSOC);

        $paydate = date("M j",strtotime($pay_data['cutoff_date'])).' to '.date("M j".", "."Y",strtotime($pay_data['payroll_date']));

    }else{
        $paydate = 0;
    }
    return $paydate;
}

function coming_net_pay(){
    global $db, $db_hris;
    $emp_net_pay = $db->prepare("SELECT SUM(`net_pay`) AS `net_pay` FROM $db_hris.`payroll_trans` WHERE !`is_posted`");
    $emp_net_pay->execute();
    if ($emp_net_pay->rowCount()) {
        $emp_net_data = $emp_net_pay->fetch(PDO::FETCH_ASSOC);

        $total_running_net = $emp_net_data['net_pay'];

    }else{
        $total_running_net = 0;
    }
    return $total_running_net;
}


?>
<div class="w3-col l12 m12 s12 w3-mobile w3-responsive w3-padding">
    <div class="w3-col l2 m2 s2 w3-responsive w3-mobile w3-padding-right">
        <div class="panel w3-border w3-round-large w3-flat-carrot">
            <header class="w3-container w3-padding-large">
                <h3><b><?php echo number_format(emp_total(),0); ?></b><i class="fa-solid fa-user-group w3-right w3-margin-top" onclick="system_menu(1)" style="cursor: pointer;"></i></h3>
                <span class="w3-small w3-text-light-black">Total Active Employee</span>
            </header>
            <p class="w3-padding w3-border-top w3-border-white">Employee</p>
        </div>
    </div>
    <div class="w3-col l2 m2 s2 w3-responsive w3-mobile w3-padding-right">
        <div class="panel w3-border w3-round-large w3-flat-pomegranate">
            <header class="w3-container w3-padding-large">
                <h3><b>&#8369;&nbsp;<?php echo number_format(running_rate_total(),2); ?></b><i class="fa-solid fa-credit-card w3-right w3-margin-top"></i></h3>
                <span class="w3-small w3-text-light-black">Total Running Rate</span>
            </header>
            <p class="w3-padding w3-border-top w3-border-white">Employee Rate</p>
        </div>
    </div>
    <div class="w3-col l2 m2 s2 w3-responsive w3-mobile w3-padding-right">
        <div class="panel w3-border w3-round-large w3-flat-amethyst">
            <header class="w3-container w3-padding-large">
                <h3><b>&#8369;&nbsp;<?php echo number_format(coming_net_pay(),2); ?></b><i class="fa-solid fa-hand-holding-dollar w3-right w3-margin-top"></i></h3>
                <span class="w3-small w3-text-light-black">Total Upcoming Net Pay</span>
            </header>
            <p class="w3-padding w3-border-top w3-border-white">Total Employee Net Pay</p>
        </div>
    </div>
    <div class="w3-col l3 m3 s3 w3-responsive w3-mobile w3-padding-right">
        <div class="panel w3-border w3-round-large w3-flat-wet-asphalt">
            <header class="w3-container w3-padding-large">
                <h3><b><?php echo payroll_date(); ?></b><i class="fa-solid fa-calendar w3-right w3-margin-top" onclick="system_menu(3)" style="cursor: pointer;"></i></h3>
                <span class="w3-small w3-text-light-black">Payroll Date</span>
            </header>
            <p class="w3-padding w3-border-top w3-border-white">Payroll Cut-Off & Payroll Date</p>
        </div>
    </div>
    <div class="w3-col l3 m3 s3 w3-responsive w3-mobile w3-padding-right">
        <div class="panel w3-border w3-round-large w3-flat-belize-hole">
            <header class="w3-container w3-padding-large">
                <h3><b><span id="clock"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i>refreshing..</span><i class="fa-solid fa-clock w3-right w3-margin-top"></i></b></h3>
                <input type="hidden" value="Get Server Time" id="clock_btn" onclick="timer_function();">
                <span class="w3-small w3-text-light-black"><?php echo date("D - F j, Y"); ?></span>
            </header>
            <p class="w3-padding w3-border-top w3-border-white">Date & Time</p>
        </div>
    </div>
</div>
<div class="w3-col l12 m12 s12 w3-mobile w3-responsive w3-padding">
    <div class="w3-col l6 m6 s6 w3-responsive w3-mobile w3-container">
        <div class="w3-col l12 m12 s12 w3-responsive w3-mobile w3-border w3-container w3-round-medium">
            <?php
            $year = date('Y');
            $query = $con->query("SELECT SUM(`net_pay`) AS `net_pay`,SUM(`deduction`) AS `deduction`,`payroll_date` AS `pay_date` FROM `payroll_trans` WHERE `is_posted` AND `payroll_date` GROUP BY `payroll_date` LIMIT 6");

            foreach($query as $data){
                $payroll_date[] = $data['pay_date'];
                $net_pay[] = $data['net_pay'];
                $deduction[] = $data['deduction'];
            }
            ?>
            <div style="width: 100%;">
                <canvas id="netPayChart" height="400px"></canvas>
            </div>
            <script type="text/javascript">
                const year = '<?php echo date('Y') ?>';
                const labels = <?php echo json_encode($payroll_date) ?>;
                const data = {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Deductions',
                            data: <?php echo json_encode($deduction) ?>,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(255, 159, 64, 0.2)',
                                'rgba(255, 205, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(201, 203, 207, 0.2)'
                            ],
                            borderColor: [
                                'rgb(255, 99, 132)',
                                'rgb(255, 159, 64)',
                                'rgb(255, 205, 86)',
                                'rgb(75, 192, 192)',
                                'rgb(54, 162, 235)',
                                'rgb(153, 102, 255)',
                                'rgb(201, 203, 207)'
                            ],
                            borderWidth: 2,
                            borderRadius: 5, // This will round the corners
                            borderSkipped: false, // To make all side rounded
                        },
                        {
                            label: 'Total Net Pay',
                            data: <?php echo json_encode($net_pay) ?>,
                            backgroundColor: [
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(255, 159, 64, 0.2)',
                                'rgba(255, 205, 86, 0.2)',
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(201, 203, 207, 0.2)'
                            ],
                            borderColor: [
                                'rgb(255, 99, 132)',
                                'rgb(255, 159, 64)',
                                'rgb(255, 205, 86)',
                                'rgb(75, 192, 192)',
                                'rgb(54, 162, 235)',
                                'rgb(153, 102, 255)',
                                'rgb(201, 203, 207)'
                            ],
                            borderWidth: 2,
                            borderRadius: 5, // This will round the corners
                            borderSkipped: false, // To make all side rounded
                        }
                    ]
                };

                const config = {
                    type: 'bar',
                    data: data,
                    options: {
                        maintainAspectRatio: false,
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false //This will do the task
                            },
                            title: {
                                display: true,
                                position: 'top',
                                text: 'Employee Running Net Pay & Deductions as of '+year,
                                fullSize: true,
                                padding: 30,
                                align: 'start'
                            }
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: true
                                }
                            },
                            y: {
                                beginAtZero: true
                            }
                        }
                    },
                };
                let netPayChart = new Chart(document.getElementById('netPayChart'), config);
            </script>
        </div>
    </div>
    <div class="w3-col l3 m3 s3 w3-responsive w3-mobile w3-padding-right">
        <div class="panel w3-border w3-round-large w3-flat-green-sea">
            <table class="w3-table w3-medium w3-text-light-black w3-container">
                <thead>
                    <tr>
                        <th class="w3-center">NAME</th>
                        <th class="w3-center">Total LATE</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                global $db, $db_hris;

                $df = new DateTime();
                $dt = new DateTime();

                $late = $db->prepare("SELECT SUM(`employee_late`.`isLate`) AS `isLate`,`master_data`.`family_name`,`master_data`.`given_name`,`master_data`.`middle_name` FROM $db_hris.`employee_late`,$db_hris.`master_data` WHERE `employee_late`.`employee_no`=`master_data`.`employee_no` AND `employee_late`.`trans_date`>=:df AND `employee_late`.`trans_date`<=:dt GROUP BY `employee_late`.`employee_no` ORDER BY SUM(`employee_late`.`isLate`) DESC LIMIT 10");
                $late->execute(array(":df" => $df->format('Y-m-01'), ":dt" => $dt->format('Y-m-d')));
                $cnt = 0;
                if ($late->rowCount()) {
                    while($late_data = $late->fetch(PDO::FETCH_ASSOC)){ ?>
                    <tr>
                        <td><?php echo number_format(++$cnt).'. '.$late_data['family_name'].', '.$late_data["given_name"].' '.substr($late_data["middle_name"], 0, 1); ?></td>
                        <td class="w3-center"><?php echo $late_data['isLate']; ?></td>
                    </tr>
                    <?php
                    }
                } ?>
                </tbody>
            </table>
            <p class="w3-padding w3-border-top w3-border-white">TOP 10 LATE OF <?php echo strtoupper(date('F Y')); ?><ion-icon class="w3-right w3-xlarge w3-hover-text-grey" style="cursor: pointer;" name="eye-outline" onclick="system_menu(33)"></ion-icon></p>
        </div>
    </div>
    <div class="w3-col l3 m3 s3 w3-responsive w3-mobile w3-padding-right">
        <div class="panel w3-border w3-round-large w3-flat-concrete">
            <table class="w3-table w3-medium w3-text-light-black w3-container">
                <thead>
                    <tr>
                        <th class="w3-center">NAME</th>
                        <th class="w3-center">Total ABSENT's</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                global $db, $db_hris;

                $df = new DateTime();
                $dt = new DateTime();

                $emp_abs = $db->prepare("SELECT SUM(`employee_absent`.`is_absent`) AS `is_absent`,`master_data`.`family_name`,`master_data`.`given_name`,`master_data`.`middle_name`,`master_data`.`pin`,`master_data`.`employee_no` FROM $db_hris.`employee_absent`,$db_hris.`master_data` WHERE `employee_absent`.`is_absent` AND `employee_absent`.`employee_no`=`master_data`.`employee_no` AND `employee_absent`.`absent_date`>=:df AND `employee_absent`.`absent_date`<=:dt GROUP BY `employee_absent`.`employee_no` ORDER BY SUM(`employee_absent`.`is_absent`) DESC LIMIT 10");
                $emp_abs->execute(array(":df" => $df->format('Y-m-01'), ":dt" => $dt->format('Y-m-d')));
                $cnt = 0;
                if ($emp_abs->rowCount()) {
                    while($emp_abs_data = $emp_abs->fetch(PDO::FETCH_ASSOC)){ ?>
                    <tr>
                        <td><?php echo number_format(++$cnt).'. '.$emp_abs_data['family_name'].', '.$emp_abs_data["given_name"].' '.substr($emp_abs_data["middle_name"], 0, 1); ?></td>
                        <td class="w3-center"><?php echo $emp_abs_data['is_absent']; ?></td>
                    </tr>
                    <?php
                    }
                } ?>
                </tbody>
            </table>
            <p class="w3-padding w3-border-top w3-border-white">TOP 10 ABSENTEE OF <?php echo strtoupper(date('F Y')); ?><ion-icon class="w3-right w3-xlarge w3-hover-text-grey" style="cursor: pointer;" name="eye-outline" onclick="system_menu(34)"></ion-icon></p>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    setTimeout(function(){
        $('#clock_btn').click();
    }, 100);
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
    $('#clock').text(x2 + " " + ampm);
    display_c7();
}

function display_c7(){
    var refresh=1000; // Refresh rate in milli seconds
    mytime=setTimeout('timer_function()',refresh)
}

display_c7();
</script>