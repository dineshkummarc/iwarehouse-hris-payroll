<?php



function payroll_payslip($employee_no, $payroll_date, $receipt_no,$store){
  include('modules/system/system.config.php');
  ?>
  <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
  <style type="text/css">
    .cut { border-right:  dashed;}
  </style>
  <?php
  $current_receipt_no = substr(number_format($receipt_no+100001,0,'.',''), -5);
  $master_data = mysqli_fetch_array(mysqli_query($con, "SELECT * FROM `master_data` WHERE `employee_no`='$employee_no' AND `store`='$store'"));
  $employee_rate_data = mysqli_fetch_array(mysqli_query($con, "SELECT * FROM `employee_rate` WHERE `employee_no`='$employee_no'"));
  $master_id_data = mysqli_fetch_array(mysqli_query($con, "SELECT * FROM `master_id` WHERE `employee_no`='$employee_no'"));
  $payroll_group_data = mysqli_fetch_array(mysqli_query($con, "SELECT * FROM `payroll_group` WHERE `group_name`='$master_id_data[pay_group]'"));
  $company_name=  '<img src="images/logo.png"/>';
  $payroll_trans_data= mysqli_fetch_array(mysqli_query($con, "SELECT * FROM `payroll_trans` WHERE `employee_no`='$employee_no' AND `payroll_date`='$payroll_date'"));
  $payroll_trans_pay=  mysqli_query($con, "SELECT * FROM `payroll_trans_pay`, `payroll_type` WHERE `payroll_trans_pay`.`payroll_type_no`=`payroll_type`.`payroll_type_no` AND `payroll_trans_pay`.`employee_no`='$employee_no' AND `payroll_trans_pay`.`payroll_date`='$payroll_date' AND `pay_amount`>0 ORDER BY `payroll_trans_pay`.`payroll_type_no`");

  $payroll_trans_ded= mysqli_query($con, "SELECT * FROM `payroll_trans_ded`, `deduction` WHERE `payroll_trans_ded`.`deduction_no`=`deduction`.`deduction_no` AND `payroll_trans_ded`.`employee_no`='$employee_no' AND `payroll_trans_ded`.`payroll_date`='$payroll_date' AND `deduction_actual`>0 ORDER BY `payroll_trans_ded`.`deduction_no`");
  

    $header ='<div class="pcont w3-transparent">
                <div class="w3-row w3-border w3-border-black">
                  <div class="w3-container w3-half cut">
                    <div class="w3-third">
                      <span class="w3-left w3-tiny">PAY RECEIPT '.$current_receipt_no.'</span>
                    </div>
                    <div class="w3-third">
                      <span>'.$company_name.'</span>
                    </div>
                    <div class="w3-third w3-container">
                    </div>
                    <div class="w3-col s12 w3-center w3-tiny" style="margin-top: 2px;">
                      <span class="">GL-20, 888 Chinatown Square, Gatuslao St., Brgy. 8, Bacolod City 6100</span>
                    </div>
                    <div class="w3-col s12 w3-center w3-margin-top">
                        <span class="w3-medium">EMPLOYEE PAYSLIP</span>
                    </div>';

          $header.='<div class="w3-col s3 w3-left w3-margin-top">
                      <span class="w3-tiny">Employee\'s Name:</span>
                    </div>
                    <div class="w3-col s6 w3-left w3-margin-top">
                      <span class="w3-tiny">'.$master_data["family_name"].", ".$master_data["given_name"]." ".  substr($master_data["middle_name"], 0,1).'</span>
                    </div>
                    <div class="w3-col s3 w3-left w3-margin-top">
                      <span class="w3-tiny w3-right">ID No: '.$master_data["pin"].'</span>
                    </div>';
          $header.='<div class="w3-col s3 w3-left">
                      <span class="w3-tiny">Payroll Period:</span>
                    </div>
                    <div class="w3-col s9 w3-left">
                      <span class="w3-tiny">'.date("M j",strtotime($payroll_group_data["cutoff_date"])).' - '.date("M j".", "."Y",strtotime($payroll_group_data["payroll_date"])).'</span>
                    </div>';
          $header.='<div class="w3-col s3 w3-left">
                      <span class="w3-tiny">Daily Rate:</span>
                    </div>
                    <div class="w3-col s9 w3-left">
                      <span class="w3-tiny">'.number_format($employee_rate_data["daily_rate"],2).'</span>
                    </div>';
                    if(number_format($employee_rate_data["incentive_cash"],2) > 0){
                      $inctv ='<div class="w3-col s3 w3-left">
                                <span class="w3-tiny">Cash Incentive:</span>
                              </div>
                              <div class="w3-col s9 w3-left">
                                <span class="w3-tiny">'.number_format($employee_rate_data["incentive_cash"],2).'</span>
                              </div>';
                    }else{
                      $inctv = '';
                    }
          $header.= $inctv;

          $pay_count=@mysqli_num_rows($payroll_trans_pay);
          $ded_count=@mysqli_num_rows($payroll_trans_ded);
          $gross_pay= number_format($payroll_trans_data["gross_pay"],2);
          $deduction= number_format($payroll_trans_data["deduction"],2);
          $net_pay=  number_format($payroll_trans_data["net_pay"],2);
          $detail="";
          if($pay_count){
            $detail.='<div class="w3-col s12 w3-left w3-margin-top">
                            <span class="w3-tiny">Pay Earnings:</span>
                        </div>';
            while ($payroll_trans_pay_data=  mysqli_fetch_array($payroll_trans_pay)){
              $days = number_format($payroll_trans_pay_data["credit"],2);
              if($payroll_trans_pay_data["pay_type"] == "BASIC PAY"){
                if($days >= 8){
                  $days = floor($payroll_trans_pay_data["credit"] / 8);
                  $remainingHours = $payroll_trans_pay_data["credit"] % 8;
                  $no_days = $remainingHours === "" ? $days." Days" : $days.".".$remainingHours." Days";
                }
              }elseif($payroll_trans_pay_data["pay_type"] == "INCENTIVE"){
                if($days >= 8){
                  $days = floor($payroll_trans_pay_data["credit"] / 8);
                  $remainingHours = $payroll_trans_pay_data["credit"] % 8;
                  $no_days = $remainingHours === "" ? $days." Days" : $days.".".$remainingHours." Days";
                }
              }elseif($payroll_trans_pay_data["pay_type"] == "JOB ORDER"){
                if($days < 8){
                  $no_days = number_format($days,1) .' Hrs';
                }elseif($days > 8){
                  $no_days = number_format($days/8,1).' Days';
                }else{
                  $no_days = number_format($days/8,1).' Day';
                }
              }elseif($payroll_trans_pay_data["pay_type"] == "VACATION"){
                if($days < 8){
                  $no_days = number_format($days,1) .' Hrs';
                }elseif($days > 8){
                  $no_days = number_format($days/8,1).' Days';
                }else{
                  $no_days = number_format($days/8,1).' Day';
                }
              }else{
                $no_days = $days;
              }
              
              $detail.='<div class="w3-col s3 w3-left">
                          <span class="w3-tiny">'.$payroll_trans_pay_data["pay_type"].'</span>
                        </div>
                        <div class="w3-col s3 w3-left">
                          <span class="w3-tiny">'.$no_days.'</span>
                        </div>
                        <div class="w3-col s6 w3-right">
                          <span class="w3-tiny w3-right">'.number_format($payroll_trans_pay_data["pay_amount"],2).'</span>
                        </div>';
            }
          }
          $detail.='<div class="w3-col s10 w3-left">
                      <span class="w3-tiny">GROSS PAY</span>
                    </div>
                    <div class="w3-col s2 w3-right w3-border-top w3-border-black">
                      <span class="w3-tiny w3-right">'.$gross_pay.'</span>
                    </div>';

          if($ded_count){
            $detail.='<div class="w3-col s12 w3-left w3-margin-top">
                          <span class="w3-tiny">Deductions:</span>
                        </div>';
              while ($payroll_trans_ded_data =  mysqli_fetch_array($payroll_trans_ded)){
                $detail.='<div class="w3-col s3 w3-left">
                            <span class="w3-tiny">'.$payroll_trans_ded_data["deduction_label"].'</span>
                          </div>
                          <div class="w3-col s9 w3-right">
                            <span class="w3-tiny w3-right">'.number_format($payroll_trans_ded_data["deduction_amount"],2).'</span>
                          </div>';
              }
          }
          $detail.='<div class="w3-col s10 w3-left">
                      <span class="w3-tiny">TOTAL DEDUCTION</span>
                    </div>
                    <div class="w3-col s2 w3-right w3-border-top w3-border-black">
                      <span class="w3-tiny w3-right">'.$deduction.'</span>
                    </div>';
          $detail.='<div class="w3-col s10 w3-left w3-margin-top">
                  <span class="w3-tiny">NET Take Home Pay</span>
                </div>
                <div class="w3-col s2 w3-right w3-margin-top w3-border-bottom w3-orange w3-border-black">
                  <span class="w3-tiny w3-right">'.$net_pay.'</span>
                </div>
                <div class="w3-col s12 w3-left w3-margin-top">
                </div>
              </div>';

    $footer = '<div class="w3-container w3-half">
                <div class="w3-third">
                  <span class="w3-left w3-tiny">PAY RECEIPT '.$current_receipt_no.'</span>
                </div>
                <div class="w3-third">
                  <span>'.$company_name.'</span>
                </div>
                <div class="w3-third w3-container">
                </div>
                <div class="w3-col s12 w3-center w3-tiny" style="margin-top: 2px;">
                  <span class="">GL-20, 888 Chinatown Square, Gatuslao St., Brgy. 8, Bacolod City 6100</span>
                </div>'; 
      $footer.='<div class="w3-col s12 w3-left w3-margin-top w3-container">
                </div>
                <div class="w3-col s12 w3-left w3-margin-top">
                <span class="w3-tiny">ID No: '.$master_data["pin"].'</span>
                </div>
                <div class="w3-col s12 w3-left">
                  <span class="w3-tiny">'.$master_data["family_name"].", ".$master_data["given_name"]." ".  substr($master_data["middle_name"], 0,1).'</span>
                </div>';
      $footer.='<div class="w3-col s12 w3-left w3-margin-top">
                  <span class="w3-tiny">I hereby acknowledge that I am fully satisfied with the computation of my salary base on the number of days I performed for the specified payroll period as such affixing my signature below.</span>
                </div>';
      $footer.='<div class="w3-col s3 w3-margin-top w3-left">
                  <span class="w3-tiny">GROSS PAY :</span>
                </div>
                <div class="w3-col s2 w3-left w3-margin-top">
                  <span class="w3-tiny w3-right">'.$gross_pay.'</span>
                </div>
                <div class="w3-col s12 w3-container">
                </div>
                <div class="w3-col s3">
                  <span class="w3-tiny">DEDUCTIONS :</span>
                </div>
                <div class="w3-col s2 w3-left w3-border-bottom w3-border-black">
                  <span class="w3-tiny w3-right">'.$deduction.'</span>
                </div>
                <div class="w3-col s12 w3-container">
                </div>
                <div class="w3-col s3">
                <span class="w3-tiny">NET Pay :</span>
                </div>
                <div class="w3-col s2 w3-left">
                  <span class="w3-tiny w3-right">'.$net_pay.'</span>
                </div>
                  <div class="w3-col s12 w3-container">
                  </div>
                  <div class="w3-col s12 w3-left w3-margin-top">
                    <span class="w3-tiny">Received by:___________________________________</span>
                  </div>
                </div>
              </div>
            </div>';
  return $header.$detail.$footer;
  }  
?>
