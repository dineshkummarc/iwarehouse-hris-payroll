<?php

$program_code = 28;
require_once('../common/functions.php');
include("../common_function.class.php");
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

switch ($_REQUEST["cmd"]) {
    case "get-payroll-summary":
        $trans_date = $_REQUEST["trans_date"];
        $payroll_date=$cfn->datefromtable($trans_date);
        get_payroll_summary($payroll_date);
    break;
    case "print-summary":
        if (substr($access_rights, 8, 2) === "P+") {
            $trans_date = $_REQUEST["date"];
            $token = $_REQUEST["token"];
            $payroll_date=$cfn->datefromtable($trans_date);
            print_summary($payroll_date,$token);
        }else{
            echo json_encode(array("status" => "error", "message" => "No Access Rights"));
            return;
        }
    break;
}

function print_summary($payroll_date,$token){
    global $db, $db_hris;

    $skey = $db->prepare("SELECT * FROM $db_hris.`_user` WHERE `security_key`=:token AND `user_no`=:uno");
    $skey->execute(array(":token" => md5($token), ":uno" => $_SESSION['user_id']));
    if($skey->rowCount()){
        $payroll_trans = $db->prepare("SELECT * FROM  $db_hris.`payroll_trans`, $db_hris.`master_data`,  $db_hris.`master_id` WHERE `payroll_date`=:pdate AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `payroll_trans`.`employee_no`=`master_id`.`employee_no` AND `net_pay` > 0 ORDER BY `family_name`, `given_name`, `middle_name`");
        $payroll_trans->execute(array(":pdate" => $payroll_date));
        if($payroll_trans->rowCount()){ ?>
            <!DOCTYPE html>
            <html>
                <head>
                    <title>PAYROLL SUMMARY</title>
                    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
                    <meta name="viewport" content="width=device-width,height=device-height,initial-scale=1.0"/>
                    <script type="text/javascript" src="js/jquery.min.js"></script>
                    <script type="text/javascript" src="js/w2ui.min.js"></script>
                    <link rel="stylesheet" type="text/css" href="../css/w2ui.min.css"/>
                    <link rel="stylesheet" type="text/css" href="../css/w3-css.css"/>
                    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
                    <style type="text/css" media="print">
                        @media all{
                            p {font-size: 60%; margin: 0 0 0 0; padding: 0 0 0 0;}
                        }
                        @media print{
                            .noprint, .noprint * {
                                display:none !important; height: 0;
                            }
                            .pgsize {
                                height: 960px;
                            }
                            @page {
                                size: 8.5in 11in;
                            }
                            .w3-orange{
                                color:#000!important;background-color:#f78902!important;
                            }
                        }
                        .pcont {
                            page-break-inside : avoid;
                        }
                        .breakpoint {
                            page-break-after: always;
                        }
                    </style>
                </head>
                <body>
                    <div class="pcont">
                    <table style="width: 100%; border: 1px solid black; border-collapse: collapse;">
                        <thead style="border: 1px solid black; border-collapse: collapse;">
                            <tr style="border: 1px solid black; border-collapse: collapse;">
                                <th colspan="6" class="w3-padding w3-orange w3-text-white">PAYROLL SUMMARY OF <?php echo date("M".'. - '."j".', '."Y",strtotime($payroll_date)); ?></th>
                            </tr>
                            <tr style="border: 1px solid black; border-collapse: collapse;">
                                <th></th>
                                <th>EMP NO</th>
                                <th>BANK ACCOUNT NO</th>
                                <th>NAME</th>
                                <th>ATM Pay</th>
                                <th>CASH Pay</th>
                            </tr>
                        </thead>
                        <tbody style="border: 1px solid black; border-collapse: collapse;">
                        <?php
                        $cnt=$atm_count=$total_net_pay=$atm=$cash=$cash_count=0;
                        while($payroll_trans_data = $payroll_trans->fetch(PDO::FETCH_ASSOC)){
                            $total_net_pay+=$payroll_trans_data["net_pay"]; ?>
                            <tr style="border: 1px solid black; border-collapse: collapse;">
                                <td align="center" style="border: 1px solid black; border-collapse: collapse;"><?php echo number_format(++$cnt); ?></td>
                                <td align="center" style="border: 1px solid black; border-collapse: collapse;"><?php echo $payroll_trans_data["pin"]; ?></td>
                                <td align="center" style="border: 1px solid black; border-collapse: collapse;"><?php echo $payroll_trans_data["bank_account"]; ?></td>
                                <td align="left" style="border: 1px solid black; border-collapse: collapse;"><?php echo $payroll_trans_data["family_name"].", ".$payroll_trans_data["given_name"]." ".  substr($payroll_trans_data["middle_name"], 0,1); ?></td>
                                <td align="right" style="border: 1px solid black; border-collapse: collapse;">
                                    <?php if($payroll_trans_data["bank_account"]){
                                        echo number_format($payroll_trans_data["net_pay"],2);
                                        $atm+=$payroll_trans_data["net_pay"];
                                        $atm_count++;
                                    } ?>
                                </td>
                                <td align="right" style="border: 1px solid black; border-collapse: collapse;">
                                    <?php if(!$payroll_trans_data["bank_account"]){
                                        $cash+=$payroll_trans_data["net_pay"];
                                        echo number_format($payroll_trans_data["net_pay"],2); 
                                        $cash_count++;
                                    } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                        <tfoot style="border: 1px solid black; border-collapse: collapse;">
                            <tr style="border: 1px solid black; border-collapse: collapse;">
                                <th colspan="4" style="border: 1px solid black; border-collapse: collapse;">GRAND TOTAL</th>
                                <th align="right" style="border: 1px solid black; border-collapse: collapse;"><?php echo number_format($atm,2); ?></th>
                                <th align="right" style="border: 1px solid black; border-collapse: collapse;"><?php echo number_format($cash,2); ?></th>
                            </tr>
                            <tr>
                                <th colspan="4" style="border: 1px solid black; border-collapse: collapse;">RECORD COUNT</th>
                                <th align="right" style="border: 1px solid black; border-collapse: collapse;"><?php echo number_format($atm_count,0); ?></th>
                                <th  align="right" style="border: 1px solid black; border-collapse: collapse;"><?php echo number_format($cash_count,0); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </html>
            <?php
        } ?>
        <script>
            window.onload = function () {
                window.print();
            };
        </script>
        <?php
    }
}

function get_payroll_summary($payroll_date){
    global $con;
    $payroll_trans = mysqli_query($con,"SELECT * FROM `payroll_trans`, `master_data`, `master_id` WHERE `payroll_date`='$payroll_date' AND `payroll_trans`.`employee_no`=`master_data`.`employee_no` AND `payroll_trans`.`employee_no`=`master_id`.`employee_no` AND `net_pay` > 0 ORDER BY `family_name`, `given_name`, `middle_name`") or die(mysqli_error($con));
    if(@mysqli_num_rows($payroll_trans)){ ?>
        <table class="w3-small w3-table-all">
            <thead>
                <tr>
                    <th></th>
                    <th>EMP NO</th>
                    <th>BANK ACCOUNT NO</th>
                    <th>NAME</th>
                    <th>ATM Pay</th>
                    <th>CASH Pay</th>
                </tr>
            </thead>
            <tbody>
            <?php
                $cnt=$atm_count=$total_net_pay=$atm=$cash=$cash_count=0;
                while($payroll_trans_data= mysqli_fetch_assoc($payroll_trans)){
                    $total_net_pay+=$payroll_trans_data["net_pay"];
                ?>
                <tr class="register">
                    <td><?php echo number_format(++$cnt); ?></td>
                    <td><?php echo $payroll_trans_data["pin"]; ?></td>
                    <td><?php echo $payroll_trans_data["bank_account"]; ?></td>
                    <td><?php echo $payroll_trans_data["family_name"].", ".$payroll_trans_data["given_name"]." ".  substr($payroll_trans_data["middle_name"], 0,1); ?></td>
                    <td>
                        <?php if($payroll_trans_data["bank_account"]){
                            echo number_format($payroll_trans_data["net_pay"],2);
                            $atm+=$payroll_trans_data["net_pay"];
                            $atm_count++;
                        } ?>
                    </td>
                    <td >
                        <?php if(!$payroll_trans_data["bank_account"]){
                            $cash+=$payroll_trans_data["net_pay"];
                            echo number_format($payroll_trans_data["net_pay"],2); 
                            $cash_count++;
                        } ?>
                    </td>
                </tr>
                <?php
                } ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3"><span class="w3-right">GRAND TOTAL</span></th>
                    <th></th>
                    <th><?php echo number_format($atm,2); ?></th>
                    <th><?php echo number_format($cash,2); ?></th>
                </tr>
                <tr>
                    <th colspan="3"><span class="w3-right">RECORD COUNT</span></th>
                    <th></th>
                    <th><?php echo number_format($atm_count,0); ?></th>
                    <th><?php echo number_format($cash_count,0); ?></th>
                </tr>
            </tfoot>
        </table>
    <?php
    }
}