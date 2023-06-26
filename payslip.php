<?php

$program_code = 16;
require_once('session.php');
require_once('modules/system/system.config.php');
include("common_function.class.php");
include("function/payroll_payslip.php");
$cfn = new common_functions();
$access_rights = $cfn->get_user_rights($program_code);
$plevel = $cfn->get_program_level($program_code);
$level = $cfn->get_user_level();
if (substr($access_rights, 6, 4) !== "B+P+") {
    if($level <= $plevel ){
        echo json_encode(array("status" => "error", "message" => "Higher level required!"));
        return;
    }
    echo json_encode(array("status" => "error", "message" => "No Access Rights"));
    return;
}
$group_no = $_GET["pay_group"];
$store = $_GET["store"];
$payroll_date=$cfn->datefromtable($_GET["date"]);

$payroll_group = mysqli_query($con,"SELECT * FROM `payroll_group` WHERE `group_name` LIKE '$group_no'");
if (@mysqli_num_rows($payroll_group)) {
  $payroll_group_data = mysqli_fetch_array($payroll_group);
  $payroll_trans = mysqli_query($con,"SELECT `payroll_trans`.`employee_no` FROM `payroll_trans`, `master_data` WHERE `payroll_date`='$payroll_date' AND `payroll_trans`.`payroll_group_no`='$payroll_group_data[group_name]' AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `master_data`.`store`='$store' ORDER BY `family_name`, `given_name`, `middle_name`");
  $payslip_report = "";
  $receipt_no = 0;
  if (@mysqli_num_rows($payroll_trans))
    while ($payroll_trans_data = mysqli_fetch_array($payroll_trans)) {
      $payslip = payroll_payslip($payroll_trans_data["employee_no"], $payroll_date, $receipt_no,$store);
      if ($payslip != "") {
        $payslip_report.=$payslip;
        $receipt_no++;
      }
    }
  if ($payslip_report != "")
    if($receipt_no){
      ?>
      <!DOCTYPE html>
      <html>
        <head>
          <title>EMPLOYEE PLAYSLIP</title>
              <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
              <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
              <script type="text/javascript" src="js/jquery.min.js"></script>
              <script type="text/javascript" src="js/w2ui.min.js"></script>
              <link rel="stylesheet" type="text/css" href="css/w2ui.min.css"/>
              <link rel="stylesheet" type="text/css" href="css/w3-css.css"/>
              <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
              <style type="text/css" media="print">
                @media all{
                  p {font-size: 60%; margin: 0 0 0 0; padding: 0 0 0 0;}
                }
                @media print
                {
                  .noprint, .noprint * {display:none !important; height: 0;}
                  .pgsize {height: 960px;}
                  body { background:#FFF; }
                  @page {
                    size: 8.5in 11in;
                  }
                  .w3-orange{color:#000!important;background-color:#f78902!important}
                }
                .pcont { page-break-inside : avoid;  }
                .breakpoint { page-break-after: always; }
              </style>
            </head>
            <body>
            <?php
                }
              
              echo $payslip_report;
              ?>
            </body>
          </html>
          <script>
            window.onload = function () {
              window.print();
            };
            JsBarcode("#barcode").init();
        </script>
  <?php
}
?>
